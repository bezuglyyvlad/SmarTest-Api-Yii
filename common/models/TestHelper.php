<?php

namespace common\models;

use api\modules\v1\models\Answer;
use api\modules\v1\models\Question;
use api\modules\v1\models\Test;
use api\modules\v1\models\TestAnswer;
use api\modules\v1\models\TestQuestion;
use DateTime;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class TestHelper
{
    //переделать
    public static function getUserTestIds()
    {
        $tests = Test::find()->where(['user_id' => Yii::$app->user->getId()])->all();
        $testIds = [];
        foreach ($tests as $t) {
            if (self::testIsFinished($t)) {
                $testIds[] = $t->test_id;
            }
        }
        return $testIds;
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
        return $timeIsOver || $lastQuestion && $isRealLastQuestion && $last_answer;
    }

    public static function checkAccessForView($id)
    {
        $test = Test::findOne(['test_id' => $id, 'user_id' => Yii::$app->user->getId()]);
        //если теста нет
        if (!$test) throw new NotFoundHttpException();
        $testIsFinished = self::testIsFinished($test);
        if ($testIsFinished) {
            throw new ForbiddenHttpException();
        }
    }

    public static function saveQuestion($testQuestion, $question, $number_question)
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

    public static function checkUserAnswer($answer, $userAnswer)
    {
        if ($answer->test_answer_id == $userAnswer) {
            $answer->is_user_answer = 1;
            $answer->save();
        }
    }

    public static function saveUserAnswer($answers, $userAnswer, $type)
    {
        if (in_array($type, Question::TYPE_WITH_ONE_ANSWER)) {
            foreach ($answers as $item) {
                self::checkUserAnswer($item, $userAnswer);
            }
        } else {
            $userAnswer = array_keys($userAnswer, true);
            foreach ($answers as $item) {
                foreach ($userAnswer as $answer) {
                    self::checkUserAnswer($item, $answer);
                }
            }
        }
    }

    public static function getPoints($answers, $points, $onePoint)
    {
        foreach ($answers as $item) {
            if ($item->is_right == 1 && $item->is_user_answer == 0) $points -= $onePoint;
            if ($item->is_right == 0 && $item->is_user_answer == 1) {
                return 0;
            }
        }
        return $points;
    }

    public static function updateTestStatistics($test, $answers, $lastQuestion)
    {
        $points = round(100 / $test->count_of_questions, 2);
        $countIsRights = count(array_filter($answers, function ($a) {
            return $a->is_right == 1;
        }));
        $onePoint = round($points / $countIsRights, 2);
        $calcPoints = self::getPoints($answers, $points, $onePoint);
        $isRightAnswer = $points == $calcPoints;
        $score = $test->score + $calcPoints > 100 ? 100 : $test->score + $calcPoints;
        $test->count_of_right_answers += $isRightAnswer;
        $test->score = $score;
        if ($test->count_of_questions === $lastQuestion->number_question) {
            $currentDate = new DateTime();
            $dateFormat = DateTime::ISO8601;
            $test->date_finish = $currentDate->format($dateFormat);
        }
        $test->save();
        $lastQuestion->right_answer += $isRightAnswer;
        $lastQuestion->score += $calcPoints;
        $lastQuestion->save();
    }

    public static function generateNewQuestion($lvl, $subcategory_id, $test_id)
    {
        $questionsArray = Question::findAll(['subcategory_id' => $subcategory_id, 'lvl' => $lvl]);
        $testQuestionIds = TestQuestion::find()->select('question_id')->where(['test_id' => $test_id])->all();
        $uniqQuestions = array_udiff($questionsArray, $testQuestionIds, function ($obj1, $obj2) {
            return $obj1->question_id - $obj2->question_id;
        });
        $question = $uniqQuestions ? $uniqQuestions[array_rand($uniqQuestions)] : $questionsArray[array_rand($questionsArray)];
        return $question;
    }

    public static function checkAccessForQuestion($test, $userAnswer, $type)
    {
        $incorrectAnswer = false;
        if ($type == 1 && !is_string($userAnswer)) $incorrectAnswer = true;
        if (self::testIsFinished($test) || !$userAnswer || $incorrectAnswer || $test->subcategory_id == null) {
            throw new ForbiddenHttpException();
        }
    }

    public static function checkAccessForResult($test)
    {
        if (!$test) throw new NotFoundHttpException();
        $testIsFinished = self::testIsFinished($test);
        if (!$testIsFinished) {
            throw new ForbiddenHttpException();
        }
    }

    public static function createFirstQuestion($test_id, $subcategory_id)
    {
        $testQuestion = new TestQuestion();
        $testQuestion->test_id = $test_id;
        $question = Question::find()->where(['subcategory_id' => $subcategory_id, 'lvl' => 1])
            ->orderBy('RAND()')->one();
        $testQuestion = self::saveQuestion($testQuestion, $question, 1);
        return $testQuestion;
    }

    public static function checkAccessForCreate($subcategory_id)
    {
        $test = Test::find()->where(['subcategory_id' => $subcategory_id, 'user_id' => Yii::$app->user->getId()])
            ->orderBy(['test_id' => SORT_DESC])->one();
        $testIsFinished = $test ? self::testIsFinished($test) : false;
        $enoughQuestions = Question::find()->where(['subcategory_id' => $subcategory_id,
                'lvl' => range(1, Question::COUNT_OF_LVL)])->groupBy('lvl')->count() === '3';
        //тест есть и он не закончен и вопросов недостаточно (не все сложности) для теста
        if ($test && !$testIsFinished || !$enoughQuestions) {
            throw new ForbiddenHttpException();
        }
    }
}