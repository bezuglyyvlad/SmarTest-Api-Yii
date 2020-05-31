<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Answer;
use api\modules\v1\models\Category;
use api\modules\v1\models\Question;
use api\modules\v1\models\Subcategory;
use api\modules\v1\models\Test;
use common\models\CorsAuthBehaviors;
use common\models\ImportForm;
use common\models\Upload;
use common\models\UploadForm;
use common\models\Utils;
use SimpleXMLElement;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

class ExpertController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Category';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'index',
            'subcategories',
            'questions',
            'question',
            'upload',
            'delete-image',
            'import',
            'export',
            'test-statistics'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия
        unset($actions['index'], $actions['view'], $actions['update'], $actions['create'], $actions['delete']);

        return $actions;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['index']) && !Yii::$app->user->can('expert')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        if (in_array($action, ['subcategories']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' => $params['category_id']])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        if (in_array($action, ['questions', 'createQuestion', 'upload', 'deleteImage',
                'import', 'export', 'testStatistics']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' => $model->category_id])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }

    public function actionIndex()
    {
        $this->checkAccess('index');
        return new ActiveDataProvider([
            'query' => Category::find()->where(['user_id' => Yii::$app->user->getId()]),
            'pagination' => false
        ]);
    }

    public function actionSubcategories($category_id)
    {
        $this->checkAccess('subcategories', null, ['category_id' => $category_id]);
        return new ActiveDataProvider([
            'query' => Subcategory::find()->where(['category_id' => $category_id]),
            'pagination' => false
        ]);
    }

    public function actionQuestions($subcategory_id)
    {
        $subcategory = Subcategory::findOne(['subcategory_id' => $subcategory_id]);
        $this->checkAccess('questions', $subcategory);
        return new ActiveDataProvider([
            'query' => Question::find()->where(['subcategory_id' => $subcategory_id]),
            'pagination' => false
        ]);
    }

    public function actionQuestion($data = [])
    {
        $data = $data ? $data : Yii::$app->request->post();
        $question = new Question();
        if ($question->load($data, '') && $question->validate()) {
            $subcategory = Subcategory::findOne(['subcategory_id' => $question->subcategory_id]);
            $this->checkAccess('createQuestion', $subcategory);
            $answers = array_key_exists('answers', $data) ? $data['answers'] : null;
            if (!$answers) throw new ServerErrorHttpException('Необхідно заповнити відповіді.');

            $answerModels = [];
            foreach ($answers as $item) {
                $model = new Answer();
                if ($model->load($item, '') && $model->validate(['text', 'is_right'])) {
                    array_push($answerModels, $model);
                } elseif (!$model->hasErrors()) {
                    throw new ServerErrorHttpException('Не вдалося завантажити відповіді.');
                } else {
                    return $model;
                }
            }

            //validate count of right answers
            $countIsRight = 0;
            foreach ($answers as $item) {
                if ($item['is_right'] == 1) {
                    $countIsRight++;
                }
            }
            if (count($answers) < 2 || $countIsRight == 0) {
                throw new ServerErrorHttpException('Некоректні відповіді.');
            }

            $uploadedFile = UploadedFile::getInstanceByName('image');
            if ($uploadedFile) {
                $model = $this->actionUpload(null, $question, $uploadedFile);
                if ($model->hasErrors()) return $model;
            } else {
                $question->save();
            }

            foreach ($answerModels as $answer) {
                $answer['question_id'] = $question->question_id;
                $answer->save();
            }

            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$question->hasErrors()) {
            throw new ServerErrorHttpException('Не вдалося завантажити запитання.');
        }

        return $question;
    }

    public function actionUpload($id, $model = null, $uploadedFile = null)
    {
        $model = $model ? $model : Question::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Object not found: $id");
        }
        $subcategory = Subcategory::findOne(['subcategory_id' => $model->subcategory_id]);
        $this->checkAccess('upload', $subcategory);
        $uploadModel = new UploadForm();

        $uploadModel->imageFile = $uploadedFile ? $uploadedFile : UploadedFile::getInstanceByName('image');
        $uploadModel->newImageName = Yii::$app->security->generateRandomString(100);

        if ($uploadModel->upload('question')) {
            Upload::deleteOldImage($model->image, 'question');
            $model->image = $uploadModel->newImageName . '.' . $uploadModel->imageFile->extension;
            $model->save();
            return $model;
        }
        return $uploadModel;
    }

    public function actionDeleteImage($id)
    {
        $model = Question::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Object not found: $id");
        }
        $subcategory = Subcategory::findOne(['subcategory_id' => $model->subcategory_id]);
        $this->checkAccess('deleteImage', $subcategory);

        Upload::deleteOldImage($model->image, 'question');
        $model->image = null;
        $model->save();
        return $model;
    }

    public function actionImport()
    {
        $uploadModel = new ImportForm();
        $uploadModel->file = UploadedFile::getInstanceByName('import');
        $uploadModel->subcategory_id = Yii::$app->request->post('subcategory_id');

        if ($uploadModel->validate()) {
            $subcategory = Subcategory::findOne(['subcategory_id' => $uploadModel->subcategory_id]);
            $this->checkAccess('import', $subcategory);

            $xmlString = file_get_contents($uploadModel->file->tempName);
            try {
                $simpleXmlArray = simplexml_load_string($xmlString);
            } catch (\Exception $e) {
                throw new ServerErrorHttpException('Помилка парсингу файла.');
            }
            if (!stripos($simpleXmlArray->asXML(), 'encoding="utf-8"')) {
                throw new ServerErrorHttpException('Вкажіть кодування utf-8.');
            }

            $json = json_encode($simpleXmlArray);
            $data = json_decode($json, true);

            if (array_key_exists('question', $data)) {
                if (is_array($data['question'])) {
                    $data['question']['subcategory_id'] = $uploadModel->subcategory_id;
                    $model = $this->actionQuestion($data['question']);
                    if ($model->hasErrors()) return $model;
                } else {
                    foreach ($data['question'] as $item) {
                        $item['subcategory_id'] = $uploadModel->subcategory_id;
                        $model = $this->actionQuestion($item);
                        if ($model->hasErrors()) return $model;
                    }
                }
            } else {
                throw new ServerErrorHttpException('Некоректно завантажені дані.');
            }
        } elseif (!$uploadModel->hasErrors()) {
            throw new ServerErrorHttpException('Некоректно завантажені дані.');
        }

        return $uploadModel->hasErrors() ? $uploadModel : null;
    }

    public function actionExport($id)
    {
        $subcategory = Subcategory::findOne(['subcategory_id' => $id]);
        if (!$subcategory) {
            throw new NotFoundHttpException("Object not found: $id");
        }
        $this->checkAccess('export', $subcategory);
        $data = Question::find()->select(['question_id', 'text', 'lvl', 'type', 'description'])
            ->with('answers')->where(['subcategory_id' => $id])->asArray()->all();

        if (!count($data)) throw new NotFoundHttpException("Data not found.");

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><root/>');
        Utils::array_to_xml(['question' => $data], $xml, ['question_id', 'answer_id']);

        $tempFile = tempnam(sys_get_temp_dir(), '');
        rename($tempFile, $tempFile .= '.xml');
        file_put_contents($tempFile, $xml->asXML());

        if (file_exists($tempFile)) {
            return Yii::$app->response->sendFile($tempFile);
        } else {
            throw new ServerErrorHttpException('Не вдалося зробити експорт.');
        }
    }

    public function actionTestStatistics($id)
    {
        $subcategory = Subcategory::findOne(['subcategory_id' => $id]);
        if (!$subcategory) {
            throw new NotFoundHttpException("Object not found: $id");
        }
        $this->checkAccess('testStatistics', $subcategory);
        return Test::find()->where(['subcategory_id' => $id])->orderBy(['test_id' => SORT_DESC])->all();
    }
}