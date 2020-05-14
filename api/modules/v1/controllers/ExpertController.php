<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Answer;
use api\modules\v1\models\Category;
use api\modules\v1\models\Question;
use api\modules\v1\models\Subcategory;
use common\models\CorsAuthBehaviors;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

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
            'question'
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
        if (in_array($action, ['questions', 'createQuestion']) &&
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

            //validate
            $countIsRight = 0;
            foreach ($answers as $item) {
                if ($item['is_right'] == 1) {
                    $countIsRight++;
                }
            }
            if (count($answers) < 2 || $countIsRight == 0) {
                throw new ServerErrorHttpException('Incorrect answers.');
            }

            $answerModels = [];
            foreach ($answers as $item) {
                $model = new Answer();
                if ($model->load($item, '') && $model->validate(['text', 'is_right'])) {
                    array_push($answerModels, $model);
                } elseif (!$model->hasErrors()) {
                    throw new ServerErrorHttpException('Failed to load answers.');
                } else {
                    return $model;
                }
            }

            $question->save();
            foreach ($answerModels as $answer) {
                $answer['question_id'] = $question->question_id;
                $answer->save();
            }

            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$question->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create question.');
        }

        return $question;
    }

    public function actionImport()
    {
        return $this->actionQuestion([['question' => ['text' => 'temp']]]);
    }
}