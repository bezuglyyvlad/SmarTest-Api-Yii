<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "test_question".
 *
 * @property int $test_question_id
 * @property string $text
 * @property int $lvl
 * @property int $type
 * @property string|null $description
 * @property int $number_question
 * @property int $right_answer
 * @property float $score
 * @property int $test_id
 * @property int|null $question_id
 *
 * @property TestAnswer[] $testAnswers
 * @property Test $test
 * @property Question $question
 */
class TestQuestion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'lvl', 'type', 'number_question', 'test_id'], 'required'],
            [['text', 'description'], 'string'],
            [['lvl', 'type', 'number_question', 'right_answer', 'test_id', 'question_id'], 'integer'],
            [['score'], 'number'],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'test_id'], 'filter' => ['user_id' => Yii::$app->user->getId()]],
            [['question_id'], 'exist', 'skipOnError' => true, 'targetClass' => Question::className(), 'targetAttribute' => ['question_id' => 'question_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'test_question_id' => 'Test Question ID',
            'text' => 'Text',
            'lvl' => 'Lvl',
            'type' => 'Type',
            'description' => 'Description',
            'number_question' => 'Number Question',
            'right_answer' => 'Right Answer',
            'score' => 'Score',
            'test_id' => 'Test ID',
            'question_id' => 'Question ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestAnswers()
    {
        return $this->hasMany(TestAnswer::className(), ['test_question_id' => 'test_question_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(Test::className(), ['test_id' => 'test_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(Question::className(), ['question_id' => 'question_id']);
    }

    public function extraFields()
    {
        return ['testAnswer'];
    }
}
