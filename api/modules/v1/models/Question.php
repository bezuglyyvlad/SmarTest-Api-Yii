<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "question".
 *
 * @property int $question_id
 * @property string $text
 * @property int $lvl
 * @property int $type
 * @property int $subcategory_id
 *
 * @property Answer[] $answers
 * @property Subcategory $subcategory
 */
class Question extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'lvl', 'type', 'subcategory_id'], 'required'],
            [['text'], 'string'],
            [['lvl', 'type', 'subcategory_id'], 'integer'],
            [['subcategory_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subcategory::className(), 'targetAttribute' => ['subcategory_id' => 'subcategory_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'question_id' => 'Question ID',
            'text' => 'Text',
            'lvl' => 'Lvl',
            'type' => 'Type',
            'subcategory_id' => 'Subcategory ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(Answer::className(), ['question_id' => 'question_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcategory()
    {
        return $this->hasOne(Subcategory::className(), ['subcategory_id' => 'subcategory_id']);
    }
}
