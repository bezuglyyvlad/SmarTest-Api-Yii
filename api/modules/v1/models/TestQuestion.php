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
 * @property int $number_question
 * @property int $test_id
 *
 * @property TestAnswer[] $testAnswers
 * @property Test $test
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
            [['text'], 'string'],
            [['lvl', 'type', 'number_question', 'test_id'], 'integer'],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'test_id']],
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
            'number_question' => 'Number Question',
            'test_id' => 'Test ID',
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
}
