<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\Question;
use api\modules\v1\models\Subcategory;
use common\models\CorsAuthBehaviors;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class QuestionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Question';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'delete', 'view', 'update'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия
        unset($actions['index'], $actions['update'], $actions['create']);

        return $actions;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['delete', 'view', 'update']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' =>
                    Subcategory::findOne(['subcategory_id' => $model->subcategory_id])->category_id])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }

    public function myFindModel($id)
    {
        $model = Question::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Object not found: $id");
        }
        return $model;
    }

    public function actionUpdate($id)
    {
        $model = $this->myFindModel($id);
        $this->checkAccess('update', $model);
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->validate()) {
            $model->save();
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update question.');
        }
        return $model;
    }
}