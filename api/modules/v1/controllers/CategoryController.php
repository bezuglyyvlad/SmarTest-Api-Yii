<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\CategoryForm;
use common\models\CorsAuthBehaviors;
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

        // off actions
        unset($actions['index'], $actions['view'], $actions['update'], $actions['create'], $actions['delete']);

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
        $model = Category::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Object not found: $id");
        }
        return $model;
    }

    public function actionIndex()
    {
        return new ActiveDataProvider([
            'query' => Category::find()->select(['category_id', 'name'])->orderBy('category_id'),
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
        if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->validate()) {
            $model->updateCategory($id);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update category.');
        }
        return $model;
    }

    public function actionDelete($id)
    {
        $model = $this->myFindModel($id);
        $this->checkAccess('delete');
        $oldUserId = $model->user_id;
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
        RbacController::actionRemoveExpert($oldUserId);

        Yii::$app->getResponse()->setStatusCode(204);
    }
}