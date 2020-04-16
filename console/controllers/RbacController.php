<?php

namespace console\controllers;

use api\modules\v1\models\Category;
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

    public function actionCreateExpertRule()
    {
        $auth = Yii::$app->authManager;
        $rule = new ExpertRule();
        $auth->add($rule);
    }

    public function actionUpdateOwnCategory()
    {
        $auth = Yii::$app->authManager;
        // добавляем разрешение "updateOwnProduct" и привязываем к нему правило.
        $editOwnCategory = $auth->createPermission('editOwnCategory');
        $editOwnCategory->description = 'Edit own category';
        $rule = $auth->getRule('isExpert');
        $editOwnCategory->ruleName = $rule->name;
        $auth->add($editOwnCategory);

        // разрешаем "автору" обновлять его посты
        $manager = $auth->getRole('expert');
        $auth->addChild($manager, $editOwnCategory);
    }

    public static function actionAddExpert($user_id)
    {
        $auth = Yii::$app->authManager;
        if (!$auth->checkAccess($user_id, 'expert')) {
            $expert = $auth->getRole('expert');
            $auth->assign($expert, $user_id);
        }
    }

    public static function actionRemoveExpert($user_id)
    {
        if (count(Category::findAll(['user_id' => $user_id])) == 0) {
            $auth = Yii::$app->authManager;
            $expert = $auth->getRole('expert');
            $auth->revoke($expert, $user_id);
        }
    }
}