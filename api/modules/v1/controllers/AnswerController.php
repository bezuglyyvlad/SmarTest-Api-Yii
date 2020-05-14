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
use yii\web\NotFoundHttpException;

class AnswerController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Answer';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'index', 'delete', 'update', 'create'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия
        unset($actions['index'], $actions['view']);

        return $actions;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        $question_id = null;
        if (in_array($action, ['index'])) $question_id = $params['question_id'];
        if (in_array($action, ['delete', 'update'])) $question_id = $model->question_id;
        if (in_array($action, ['create'])) $question_id = Yii::$app->request->post()['question_id'];

        $subcategory_id = Question::findOne(['question_id' => $question_id])->subcategory_id;
        if (in_array($action, ['delete', 'index', 'update', 'create']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' =>
                    Subcategory::findOne(['subcategory_id' => $subcategory_id])->category_id])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }

    public function myFindModel($id)
    {
        if (!Question::findOne($id)) {
            throw new NotFoundHttpException("Question not found: $id");
        }
    }

    public function actionIndex($question_id)
    {
        $this->myFindModel($question_id);
        $this->checkAccess('index', null, ['question_id' => $question_id]);
        return new ActiveDataProvider([
            'query' => Answer::find()->where(['question_id' => $question_id]),
            'pagination' => false
        ]);
    }
}