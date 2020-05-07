<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
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
            'subcategories'
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

    public function actionIndex()
    {
        if (!Yii::$app->user->can('expert')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        return new ActiveDataProvider([
            'query' => Category::find()->where(['user_id' => Yii::$app->user->getId()]),
            'pagination' => false
        ]);
    }

    public function actionSubcategories($category_id)
    {
        return new ActiveDataProvider([
            'query' => Subcategory::find()->where(['category_id' => $category_id]),
            'pagination' => false
        ]);
    }
}