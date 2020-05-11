<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\Question;
use api\modules\v1\models\Subcategory;
use common\models\CorsAuthBehaviors;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;

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
        if (in_array($action, ['questions']) &&
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
}