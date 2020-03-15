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
            $isRealLastQuestion = $testModel->count_of_questions == $lastQuestion->number_question;
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


    private function saveQuestion($testQuestion, $question, $number_question)
    {
        $testQuestion->text = $question->text;
        $testQuestion->lvl = $question->lvl;
        $testQuestion->type = $question->type;
        $testQuestion->description = $question->description;
        $testQuestion->question_id = $question->question_id;
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

    private function checkUserAnswer($answer, $userAnswer)
    {
        if ($answer->text == $userAnswer) {
            $answer->is_user_answer = 1;
            $answer->save();
        }
    }

    private function saveUserAnswer($answers, $userAnswer, $type)
    {
        foreach ($answers as $item) {
            if (in_array($type, Question::TYPE_WITH_ONE_ANSWER)) {
                $this->checkUserAnswer($item, $userAnswer);
            } else {
                foreach ($userAnswer as $answer) {
                    $this->checkUserAnswer($item, $answer);
                }
            }
        }
    }

    private function getPoints($answers, $points, $onePoint)
    {
        foreach ($answers as $item) {
            if ($item->is_right == 1 && $item->is_user_answer == 0) $points -= $onePoint;
            if ($item->is_right == 0 && $item->is_user_answer == 1) {
                return 0;
            }
        }
        return $points;
    }

    private function updateTestStatistics($test, $answers, $lastQuestion)
    {
        $points = round(100 / $test->count_of_questions, 2);
        $countIsRights = count(array_filter($answers, function ($a) {
            return $a->is_right == 1;
        }));
        $onePoint = round($points / $countIsRights, 2);
        $calcPoints = $this->getPoints($answers, $points, $onePoint);
        $isRightAnswer = $points == $calcPoints;
        $score = $test->score + $calcPoints > 100 ? 100 : $test->score + $calcPoints;
        $test->count_of_right_answers += $isRightAnswer;
        $test->score = $score;
        $test->save();
        $lastQuestion->right_answer = $isRightAnswer;
        $lastQuestion->save();
    }

    private function generateNewQuestion($lvl, $subcategory_id, $test_id)
    {
        $questionsArray = Question::findAll(['subcategory_id' => $subcategory_id, 'lvl' => $lvl]);
        $testQuestionIds = TestQuestion::find()->select('question_id')->where(['test_id' => $test_id])->all();
        $uniqQuestions = array_udiff($questionsArray, $testQuestionIds, function ($obj1, $obj2) {
            return $obj1->question_id - $obj2->question_id;
        });
        $question = $uniqQuestions ? $uniqQuestions[array_rand($uniqQuestions)] : $questionsArray[array_rand($questionsArray)];
        return $question;
    }

    private function checkAccessForQuestion($test, $userAnswer, $type)
    {
        $incorrectAnswer = false;
        if ($type == 1 && !is_string($userAnswer)) $incorrectAnswer = true;
        if (self::testIsFinished($test) || !$userAnswer || $incorrectAnswer || $test->subcategory_id == null) {
            throw new ForbiddenHttpException();
        }
    }

    //поменять очки и количество правильных ответов у теста
    public function actionNextQuestion()
    {
        $testQuestion = new TestQuestion();
        if ($testQuestion->load(Yii::$app->request->post(), '') && $testQuestion->validate('test_id')) {
            $test_id = $testQuestion->test_id;
            $post = Yii::$app->request->post();
            $userAnswer = array_key_exists('answer', $post) ? $post['answer'] : null;
            $test = Test::findOne(['test_id' => $test_id, 'user_id' => Yii::$app->user->getId()]);
            $lastQuestion = TestQuestion::find()->where(['test_id' => $test_id])->orderBy(['number_question' => SORT_DESC])
                ->one();
            $this->checkAccessForQuestion($test, $userAnswer, $lastQuestion->type);
            $answers = TestAnswer::findAll(['test_question_id' => $lastQuestion->test_question_id]);
            $this->saveUserAnswer($answers, $userAnswer, $lastQuestion->type);
            $this->updateTestStatistics($test, $answers, $lastQuestion);
            $numberNextQuestion = $lastQuestion->number_question + 1;
            //если требуеться не выходящий за пределы вопрос
            if ($numberNextQuestion <= $test->count_of_questions) {
                //подбираем новый вопрос по нужному алгоритму (проверка был ли такой вопрос уже в тесте)
                //подбор сложности нового вопроса
                $newLvl = 1;
                if ($test->score >= 64 && $test->score < 82) {
                    $newLvl = 2;
                } else if ($test->score >= 82) {
                    $newLvl = 3;
                }
                $question = $this->generateNewQuestion($newLvl, $test->subcategory_id, $test_id);
                $testQuestion = $this->saveQuestion($testQuestion, $question, $numberNextQuestion);
                return ['question' => $testQuestion,
                    'answers' => TestAnswer::findAll(['test_question_id' => $testQuestion->test_question_id])];
            }
            return [];
        } elseif (!$testQuestion->hasErrors()) {
            throw new ServerErrorHttpException('Failed to generate new question.');
        }

        return $testQuestion;
    }

    public function actionResult()
    {
        return 'Result';
    }


    private function createFirstQuestion($test_id, $subcategory_id)
    {
        $testQuestion = new TestQuestion();
        $testQuestion->test_id = $test_id;
        $question = Question::find()->where(['subcategory_id' => $subcategory_id, 'lvl' => 1])
            ->orderBy('RAND()')->one();
        $testQuestion = $this->saveQuestion($testQuestion, $question, 1);
        return $testQuestion;
    }

    private function checkAccessForCreate($subcategory_id)
    {
        $test = Test::find()->where(['subcategory_id' => $subcategory_id, 'user_id' => Yii::$app->user->getId()])
            ->orderBy(['test_id' => SORT_DESC])->one();
        $testIsFinished = $test ? $this->testIsFinished($test) : false;
        $enoughQuestions = Question::find()->where(['subcategory_id' => $subcategory_id,
                'lvl' => range(1, Question::COUNT_OF_LVL)])->groupBy('lvl')->count() === '3';
        //тест есть и он не закончен и вопросов недостаточно (не все сложности) для теста
        if ($test && !$testIsFinished || !$enoughQuestions) {
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
            $model->count_of_questions = $subcategory->count_of_questions;
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