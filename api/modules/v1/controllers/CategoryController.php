<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\CategoryForm;
use common\models\CorsAuthBehaviors;
use common\models\User;
use common\models\UserForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

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
            'index', 'view', 'create', 'update', 'delete'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия
        unset($actions['index'], $actions['view'], $actions['update'], $actions['create']);

        return $actions;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['delete']) && !Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }

    public function actionIndex()
    {
        $isAdmin = Yii::$app->user->can('admin');
        $query = $isAdmin ? Category::find()->with('user') : Category::find()->select(['category_id', 'name']);
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public function actionView()
    {
        $isAdmin = Yii::$app->user->can('admin');
        return $isAdmin ? Category::find()->with('user')->one() :
            Category::find()->select(['category_id', 'name'])->one();
    }

    public function actionCreate()
    {
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        $model = new CategoryForm();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $user = User::findOne(['email' => $model->userEmail]);
            $user_id = $user ? $user->getId() : $user;
            if ($model->createCategory($user_id)) {
                $response = Yii::$app->getResponse();
                $response->setStatusCode(201);
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create category.');
        }
        return $model;
    }

    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        $model = new CategoryForm();
        if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->validate()) {
            $user = User::findOne(['email' => $model->userEmail]);
            $user_id = $user ? $user->getId() : $user;
            $model->updateCategory($id, $user_id);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update category.');
        }
        return $model;
    }
}