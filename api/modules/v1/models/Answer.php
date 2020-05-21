<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "answer".
 *
 * @property int $answer_id
 * @property string $text
 * @property int $is_right
 * @property int $question_id
 *
 * @property Question $question
 */
class Answer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'is_right', 'question_id'], 'required'],
            [['text'], 'string'],
            [['question_id'], 'integer'],
            [['is_right'], 'integer', 'min' => 0, 'max' => 1],
            [['question_id'], 'exist', 'skipOnError' => true, 'targetClass' => Question::className(), 'targetAttribute' => ['question_id' => 'question_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'answer_id' => 'Answer ID',
            'text' => 'Text',
            'is_right' => 'Is Right',
            'question_id' => 'Question ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(Question::className(), ['question_id' => 'question_id']);
    }
}
