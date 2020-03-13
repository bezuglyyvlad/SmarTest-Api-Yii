<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Answer;
use api\modules\v1\models\Category;
use api\modules\v1\models\Question;
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
use yii\web\NotFoundHttpException;
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
            'next-question'
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

    public static function testIsFinished($testModel)
    {
        $dateFormat = DateTime::ISO8601;
        $currentDate = new DateTime();
        $dateFinish = DateTime::createFromFormat($dateFormat, $testModel->date_finish);
        $timeIsOver = $dateFinish <= $currentDate;
        $lastQuestion = TestQuestion::find()->where(['test_id' => $testModel->test_id])->orderBy(['number_question' => SORT_DESC])
            ->one();
        if ($lastQuestion) {
            $isRealLastQuestion = $testModel->count_of_question == $lastQuestion->number_question;
            $last_answer = TestAnswer::findAll(['test_question_id' => $lastQuestion->test_question_id,
                'is_user_answer' => !null]);
        };
        // если время вышло или есть ответ на последний вопрос (действительно последний)
        return $timeIsOver || $isRealLastQuestion && $last_answer;
    }

    private function checkAccessForView($id)
    {
        $test = Test::findOne(['test_id' => $id, 'user_id' => Yii::$app->user->getId()]);
        //если теста нет
        if (!$test) throw new NotFoundHttpException();
        $testIsFinished = $this->testIsFinished($test);
        if ($testIsFinished) {
            throw new ForbiddenHttpException();
        }
    }

    //может не быть вообще вопросов
    public function actionView($id)
    {
        $this->checkAccessForView($id);
        $condition = ['test_id' => $id];
        $test = Test::findOne($condition);
        $question = TestQuestion::find()->where($condition)->orderBy(['number_question' => SORT_DESC])
            ->one();
        $answers = TestAnswer::findAll(['test_question_id' => $question->test_question_id]);
        return ['test' => $test, 'question' => $question, 'answers' => $answers];
    }

    private function checkAccessForQuestion($id, $numberNextQuestion)
    {
        $test = Test::findOne(['test_id' => $id, 'user_id' => Yii::$app->user->getId()]);
        //если теста нет или нужен вопрос выходящий за пределы
        if (!$test || $numberNextQuestion > $test->count_of_question) {
            throw new ForbiddenHttpException();
        }
    }


    private function saveQuestion($testQuestion, $question, $number_question)
    {
        $testQuestion->text = $question->text;
        $testQuestion->lvl = $question->lvl;
        $testQuestion->type = $question->type;
        $testQuestion->number_question = $number_question;
        if ($testQuestion->save()) {
            $answers = Answer::findAll(['question_id' => $question->question_id]);
            shuffle($answers);
            foreach ($answers as $item) {
                $testAnswers = new TestAnswer();
                $testAnswers->text = $item->text;
                $testAnswers->is_right = $item->is_right;
                $testAnswers->test_question_id = $testQuestion->test_question_id;
                $testAnswers->save();
            }
            return $testQuestion;
        }
    }

    //проверять как-то ответы (мб создать другой клас для приема данных)
    //защитать предидущий ответ
    //поменять очки и количество правильных ответов у теста
    public function actionNextQuestion()
    {
        $testQuestion = new TestQuestion();
        if ($testQuestion->load(Yii::$app->request->post(), '') && $testQuestion->validate('test_id')) {
            $numberNextQuestion = count(TestQuestion::findAll(['test_id' => $testQuestion->test_id])) + 1;
            $this->checkAccessForQuestion($testQuestion->test_id, $numberNextQuestion);
            $test = Test::findOne(['test_id' => $testQuestion->test_id]);
            if ($numberNextQuestion == 1) {
                $question = Question::find()->where(['subcategory_id' => $test->subcategory_id, 'lvl' => 2])
                    ->orderBy('RAND()')->one();
            } else {
                //подбираем новый вопрос по нужному алгоритму (проверка был ли такой вопрос уже в тесте)
            }

            $testQuestion = $this->saveQuestion($testQuestion, $question, $numberNextQuestion);
            return ['question' => $testQuestion,
                'answers' => TestAnswer::findAll(['test_question_id' => $testQuestion->test_question_id])];
        } elseif (!$testQuestion->hasErrors()) {
            throw new ServerErrorHttpException('Failed to generate new question.');
        }
        return $testQuestion;
    }

    //защитать последний вопрос
    public function actionResult()
    {
        return 'Result';
    }


    private function createFirstQuestion($test_id, $subcategory_id)
    {
        $testQuestion = new TestQuestion();
        $testQuestion->test_id = $test_id;
        $question = Question::find()->where(['subcategory_id' => $subcategory_id, 'lvl' => 2])
            ->orderBy('RAND()')->one();
        $testQuestion = $this->saveQuestion($testQuestion, $question, 1);
        return $testQuestion;
    }

    private function checkAccessForCreate($subcategory_id)
    {
        $test = Test::find()->where(['subcategory_id' => $subcategory_id, 'user_id' => Yii::$app->user->getId()])
            ->orderBy(['test_id' => SORT_DESC])->one();
        $testIsFinished = $test ? $this->testIsFinished($test) : false;

        //достаточно ли вопросов для прохождения теста

        if ($test && !$testIsFinished) {
            throw new ForbiddenHttpException();
        }
    }

    public function actionCreate()
    {
        $model = new Test();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate('subcategory_id')) {
            $this->checkAccessForCreate($model->subcategory_id);
            $currentDate = new DateTime();
            $dateFormat = DateTime::ISO8601;
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
                $question = $this->createFirstQuestion($model->test_id, $model->subcategory_id);
                $answers = TestAnswer::findAll(['test_question_id' => $question->test_question_id]);
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to start new test.');
        }
        return ['test' => $model, 'question' => $question, 'answers' => $answers];
    }
}