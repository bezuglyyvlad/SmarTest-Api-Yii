<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "test_answer".
 *
 * @property int $test_answer_id
 * @property string $text
 * @property int $is_right
 * @property int $is_user_answer
 * @property int $test_question_id
 *
 * @property TestQuestion $testQuestion
 */
class TestAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'is_right', 'test_question_id'], 'required'],
            [['text'], 'string'],
            [['is_right', 'is_user_answer', 'test_question_id'], 'integer'],
            [['test_question_id'], 'exist', 'skipOnError' => true, 'targetClass' => TestQuestion::className(), 'targetAttribute' => ['test_question_id' => 'test_question_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'test_answer_id' => 'Test Answer ID',
            'text' => 'Text',
            'is_right' => 'Is Right',
            'is_user_answer' => 'Is User Answer',
            'test_question_id' => 'Test Question ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestion()
    {
        return $this->hasOne(TestQuestion::className(), ['test_question_id' => 'test_question_id']);
    }
}
