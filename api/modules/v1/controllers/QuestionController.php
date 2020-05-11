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

class QuestionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Question';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'delete',
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
        if (in_array($action, ['delete']) &&
            !Yii::$app->user->can('editOwnCategory',
                ['category' => Category::findOne(['category_id' =>
                    Subcategory::findOne(['subcategory_id' => $model->subcategory_id])->category_id])])) {
            throw new ForbiddenHttpException("You don't have enough permission");
        }
    }
}