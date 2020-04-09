<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\CategoryForm;
use common\models\CorsAuthBehaviors;
use common\models\User;
use common\models\UserForm;
use console\controllers\RbacController;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class CategoryController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Category';

//    public $serializer = [
//        'class' => 'yii\rest\Serializer',
//        'collectionEnvelope' => 'items',
//    ];

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

    public function myFindModel($id)
    {
        if (!Category::findOne($id)) {
            throw new NotFoundHttpException("Object not found: $id");
        }
    }

    public function actionIndex()
    {
        $isAdmin = Yii::$app->user->can('admin');
        $query = $isAdmin ? Category::find()->with('user') : Category::find()->select(['category_id', 'name']);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    public function actionView($id)
    {
        $this->myFindModel($id);
        $isAdmin = Yii::$app->user->can('admin');
        return $isAdmin ? Category::find()->where(['category_id' => $id])->with('user')->one() :
            Category::find()->select(['category_id', 'name'])->where(['category_id' => $id])->one();
    }

    public function actionCreate()
    {
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        $model = new CategoryForm();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            if ($model->createCategory()) {
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
        $this->myFindModel($id);
        if (!Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        $model = new CategoryForm();
        $category = new Category();
        if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->validate()) {
            $model->updateCategory($id);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update category.');
        }
        return $model;
    }
}