<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\Subcategory;
use api\modules\v1\models\Test;
use api\modules\v1\models\TestAnswer;
use api\modules\v1\models\TestQuestion;
use common\models\CorsAuthBehaviors;
use DateTime;
use DateTimeZone;
use Yii;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class TestController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Test';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'create',
            'view',
            'question'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // отключить действия "create"
        unset($actions['create'], $actions['view']);

        return $actions;
    }

    private function checkAccessForView($id)
    {
        $test = Test::findOne(['test_id' => $id, 'user_id' => Yii::$app->user->getId()]);
        $dateFormat = 'Y-m-d H:i:s';
        $currentDate = new DateTime(null, new DateTimeZone(Yii::$app->timeZone));
        $dateFinish = DateTime::createFromFormat($dateFormat, $test->date_finish);
        $timeIsOver = $dateFinish <= $currentDate;
        $lastQuestion = TestQuestion::find()->where(['test_id' => $id])->orderBy(['number_question' => SORT_DESC])
            ->one();
        if ($lastQuestion) {
            $isRealLastQuestion = $test->count_of_question == $lastQuestion->number_question;
            $last_answer = TestAnswer::findAll(['test_question_id' => $lastQuestion->test_question_id,
                'is_user_answer' => !null]);
        };
        //если теста нет или время вышло или есть ответ на последний вопрос (действительно последний)
        if (!$test || $timeIsOver || $isRealLastQuestion && $last_answer) {
            throw new ForbiddenHttpException();
        }
    }

    public function actionView($id)
    {
        $this->checkAccessForView($id);
        $condition = ['test_id' => $id];
        $test = Test::findOne($condition)->toArray();
        //может не быть вообще вопросов
        $test['last_question'] = TestQuestion::find()->where($condition)->orderBy(['number_question' => SORT_DESC])
            ->one()->toArray();
        $answers = TestAnswer::findAll(['test_question_id' => $test['last_question']['test_question_id']]);
        shuffle($answers);
        $test['last_question']['answers'] = $answers;
        return $test;
    }

    private function checkAccessForQuestion($id, $number_question)
    {
        $test = Test::findOne(['test_id' => $id, 'user_id' => Yii::$app->user->getId()]);
        $questions = TestQuestion::findAll(['test_id' => $id]);
        //если теста нет или нужен не следующий вопрос
        if (!$test || count($questions) + 1 != $number_question) {
            throw new ForbiddenHttpException();
        }
    }

    //проверять как-то ответы (мб создать другой клас для приема данных)
    //защитать предидущий ответ
    //поменять очки и количество правильных ответов у теста
    public function actionQuestion()
    {
        $model = new TestQuestion();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate(['test_id', 'number_question'])) {
            $this->checkAccessForQuestion($model->test_id, $model->number_question);
            if ($model->number_question == 1) {
                //генерируем первый вопрос
            } else {
                //подбираем новый вопрос по нужному алгоритму
            }
            return $model;
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to generate new question.');
        }
        return $model;
    }

    //защитать последний вопрос
    public function actionResult()
    {
        return 'Result';
    }

    //нет проверки на то, что тест еще не закончен и его можно продолжить
    public function actionCreate()
    {
        $model = new Test();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate('subcategory_id')) {
            $currentDate = new DateTime(null, new DateTimeZone(Yii::$app->timeZone));
            $dateFormat = 'Y-m-d H:i:s';
            $subcategory = Subcategory::findOne(['subcategory_id' => $model->subcategory_id]);
            $model->category_name = Category::findOne(['category_id' => $subcategory->category_id])->name;
            $model->subcategory_name = $subcategory->name;
            $model->time = $subcategory->time;
            $model->count_of_question = $subcategory->count_of_question;
            $model->date_start = $currentDate->format($dateFormat);
            $model->date_finish = $currentDate->modify("+{$model->time} minute")->format($dateFormat);
            $model->user_id = Yii::$app->user->getId();
            if ($model->save()) {
                $response = Yii::$app->getResponse();
                $response->setStatusCode(201);
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to start new test.');
        }
        return $model;
    }
}