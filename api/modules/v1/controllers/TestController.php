<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Category;
use api\modules\v1\models\Subcategory;
use api\modules\v1\models\Test;
use api\modules\v1\models\TestAnswer;
use api\modules\v1\models\TestQuestion;
use common\models\CorsAuthBehaviors;
use common\models\TestHelper;
use DateTime;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\ServerErrorHttpException;

class TestController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Test';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors = CorsAuthBehaviors::getCorsAuthSettings($behaviors);

        $behaviors['authenticator']['only'] = [
            'create',
            'view',
            'next-question',
            'result',
            'index',
            'rating'
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // off actions
        unset($actions['create'], $actions['view'], $actions['index'], $actions['update'], $actions['delete']);

        return $actions;
    }

    public function actionIndex()
    {
        $testIds = TestHelper::getUserTestIds();
        return new ActiveDataProvider([
            'query' => Test::find()->where(['test_id' => $testIds])->orderBy(['test_id' => SORT_DESC]),
        ]);
    }

    public function actionRating()
    {
        $testIds = TestHelper::getUserTestIds();
        $tests = Test::find()->where(['test_id' => $testIds])->andWhere(['not', ['subcategory_id' => null]])->all();
        //rating by category
        $categories = Category::find()->select(['category_id', 'name'])->asArray()
            ->orderBy('category_id')->all();
        $ratingByCategory = [];
        foreach ($categories as &$item) {
            $subcategoryIds = Subcategory::find()->where(['category_id' => $item['category_id']])->column();
            $score = Test::find()->select(['score' => 'avg(score)'])
                ->where(['test_id' => $testIds, 'subcategory_id' => $subcategoryIds])
                ->andWhere(['not', ['subcategory_id' => null]])->column();
            $item['score'] = round(array_shift($score), 2);
            if ($item['score'] != null) array_push($ratingByCategory, $item);
        }
        //rating
        $arrayOfPoints = [];
        foreach ($ratingByCategory as $i) {
            $arrayOfPoints[] = $i['score'];
        }
        $rating = $arrayOfPoints ? array_sum($arrayOfPoints) / count($arrayOfPoints) : 0;
        //chartData
        $chartData = [];
        foreach ($tests as $i) {
            $dateFormat = DateTime::ISO8601;
            $days30ago = (new DateTime())->modify('-30 day');
            if (DateTime::createFromFormat($dateFormat, $i['date_start']) >= $days30ago) {
                $chartData[] = $i['score'];
            }
        }
        return ['rating' => round($rating, 2), 'ratingByCategory' => $ratingByCategory, 'chartData' => $chartData];
    }

    public function actionView($id)
    {
        TestHelper::checkAccessForView($id);
        $condition = ['test_id' => $id];
        $test = Test::findOne($condition);
        $question = TestQuestion::find()->where($condition)->orderBy(['number_question' => SORT_DESC])
            ->one();
        unset($question->description);
        $answers = TestAnswer::find()->select(['test_answer_id', 'text'])
            ->where(['test_question_id' => $question->test_question_id])->all();
        return ['test' => $test, 'question' => $question, 'answers' => $answers];
    }

    public function actionNextQuestion()
    {
        $testQuestion = new TestQuestion();
        if ($testQuestion->load(Yii::$app->request->post(), '')
            && $testQuestion->validate('test_id')) {
            $test_id = $testQuestion->test_id;
            $post = Yii::$app->request->post();
            $userAnswer = array_key_exists('answer', $post) ? $post['answer'] : null;
            $test = Test::findOne(['test_id' => $test_id, 'user_id' => Yii::$app->user->getId()]);
            $lastQuestion = TestQuestion::find()->where(['test_id' => $test_id])
                ->orderBy(['number_question' => SORT_DESC])->one();
            TestHelper::checkAccessForQuestion($test, $userAnswer, $lastQuestion->type);
            $answers = TestAnswer::findAll(['test_question_id' => $lastQuestion->test_question_id]);
            TestHelper::saveUserAnswer($answers, $userAnswer, $lastQuestion->type);
            TestHelper::updateTestStatistics($test, $answers, $lastQuestion);
            $numberNextQuestion = $lastQuestion->number_question + 1;
            //if need question
            if ($numberNextQuestion <= $test->count_of_questions) {
                //select lvl new question
                $newLvl = 1;
                if ($test->score >= 64 && $test->score < 82) {
                    $newLvl = 2;
                } else if ($test->score >= 82) {
                    $newLvl = 3;
                }
                //generate new question (check already exists such a question)
                $question = TestHelper::generateNewQuestion($newLvl, $test->subcategory_id, $test_id);
                $testQuestion = TestHelper::saveQuestion($testQuestion, $question, $numberNextQuestion);
                unset($testQuestion->description);
                return ['question' => $testQuestion,
                    'answers' => TestAnswer::find()->select(['test_answer_id', 'text'])->where(['test_question_id' => $testQuestion->test_question_id])->all()];
            }
            return [];
        } elseif (!$testQuestion->hasErrors()) {
            throw new ServerErrorHttpException('Failed to generate new question.');
        }

        return $testQuestion;
    }

    public function actionResult($test_id)
    {
        $test = Test::findOne(['test_id' => $test_id, 'user_id' => Yii::$app->user->getId()]);
        TestHelper::checkAccessForResult($test);
        $questions = TestQuestion::find()->where(['test_id' => $test_id])->all();
        $answers = [];
        foreach ($questions as $item) {
            $answers[$item->test_question_id] = TestAnswer::findAll(['test_question_id' => $item->test_question_id]);
        }
        return ['test' => $test, 'questions' => $questions, 'answers' => $answers];
    }

    public function actionCreate()
    {
        $model = new Test();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate('subcategory_id')) {
            TestHelper::checkAccessForCreate($model->subcategory_id);
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
                $question = TestHelper::createFirstQuestion($model->test_id, $model->subcategory_id);
                $answers = TestAnswer::find()->select('text')->where(['test_question_id' => $question->test_question_id])->all();
            }
            return ['test' => $model, 'question' => $question, 'answers' => $answers];
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to start new test.');
        }
        return $model;
    }
}