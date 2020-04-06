<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Subcategory;
use common\models\CorsAuthBehaviors;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;

class SubcategoryController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Subcategory';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'index',
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия
        unset($actions['index']);

        return $actions;
    }

    public function actionIndex($category_id)
    {
        return new ActiveDataProvider([
            'query' => Subcategory::find()->where(['category_id' => $category_id, 'is_open' => 1])
        ]);
    }
}