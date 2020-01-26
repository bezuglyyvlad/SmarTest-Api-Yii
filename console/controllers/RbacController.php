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
    }

    public function actionAdmin()
    {
        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');
        $auth->assign($admin, 1);
    }
}