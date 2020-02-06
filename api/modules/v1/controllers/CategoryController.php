<?php

namespace api\modules\v1\controllers;

use common\models\CorsAuthBehaviors;
use yii\rest\ActiveController;

class CategoryController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Category';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'index', 'view'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

//        // отключить действия "create"
//        unset($actions['index']);

        return $actions;
    }
}