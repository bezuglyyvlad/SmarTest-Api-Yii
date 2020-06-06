<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\Subcategory;
use common\models\CorsAuthBehaviors;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;

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
            'index', 'view', 'update', 'create', 'delete'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // off actions
        unset($actions['index']);

        return $actions;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['update', 'delete']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' => $model->category_id])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        if (in_array($action, ['create']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' => Yii::$app->request->post()['category_id']])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }

    public function actionIndex($category_id)
    {
        return new ActiveDataProvider([
            'query' => Subcategory::find()->where(['category_id' => $category_id, 'is_open' => 1])
        ]);
    }
}