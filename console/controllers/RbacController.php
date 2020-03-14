<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        $admin = $auth->createRole('admin');
        $admin->description = 'Admin';
        $auth->add($admin);

        $expert = $auth->createRole('expert');
        $expert->description = 'Expert';
        $auth->add($expert);
    }

    public function actionAdmin()
    {
        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');
        $auth->assign($admin, 1);
    }

    public static function actionExpert($user_id)
    {
        $auth = Yii::$app->authManager;
        $expert = $auth->getRole('expert');
        $auth->assign($expert, 2);
    }
}