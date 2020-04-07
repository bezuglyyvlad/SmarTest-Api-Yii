<?php


namespace api\modules\v1\controllers;

use api\modules\v1\models\AccessToken;
use api\modules\v1\models\Category;
use common\models\CorsAuthBehaviors;
use common\models\LoginForm;
use common\models\UserForm;
use common\models\User;
use console\controllers\RbacController;
use Yii;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class UserController extends ActiveController
{
    public $modelClass = 'common\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'index',
            'view',
            'update',
            'delete',
            'logout'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия
        unset($actions['create'], $actions['index'], $actions['update'], $actions['view']);

        return $actions;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['view']) && $model->id !== Yii::$app->user->getId() && !Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        if (in_array($action, ['delete']) && $model->id !== Yii::$app->user->getId()) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }

    public function myFindModel($id)
    {
        if (!User::findOne($id)) {
            throw new NotFoundHttpException("Object not found: $id");
        }
    }

    public function actionIndex()
    {
        return ['user' => Yii::$app->user->identity,
            'role' => Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId())];
    }

    public function actionCreate()
    {
        $model = new UserForm();
        if ($model->load(Yii::$app->request->post(), '')) {
            if ($model->signup()) {
                $response = Yii::$app->getResponse();
                $response->setStatusCode(201);
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create user.');
        }
        return $model;
    }

    public function actionUpdate($id)
    {
        $this->myFindModel($id);
        if (Yii::$app->user->getId() !== (int)$id) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
        $model = new UserForm();
        if ($model->load(Yii::$app->request->getBodyParams(), '')) {
            $model->updateUser($id);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create user.');
        }
        $model->password = null;
        return $model;
    }

    public function actionLogin()
    {
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post(), '')) {
            $result = $model->login();
            if (is_array($result)) {
                throw new BadRequestHttpException(array_shift($result));
            }
            return ['access_token' => $result];
        }
        throw new ForbiddenHttpException();
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        $token = User::getAccessToken();
        $access_token = AccessToken::findOne(['access_token' => $token]);
        $access_token->delete();
    }
}