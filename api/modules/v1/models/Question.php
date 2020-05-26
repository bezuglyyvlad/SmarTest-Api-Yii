<?php

namespace api\modules\v1\models;

use common\models\Image;

/**
 * This is the model class for table "question".
 *
 * @property int $question_id
 * @property string $text
 * @property int $lvl
 * @property int $type
 * @property string|null $description
 * @property string|null $image
 * @property int $subcategory_id
 *
 * @property Answer[] $answers
 * @property Subcategory $subcategory
 * @property TestQuestion[] $testQuestions
 */
class Question extends \yii\db\ActiveRecord
{
    const NUMBER_OF_TYPES = 1;
    const COUNT_OF_LVL = 3;
    const TYPE_WITH_ONE_ANSWER = [1];

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
            [['text', 'description'], 'string'],
            [['subcategory_id'], 'integer'],
            [['lvl'], 'integer', 'min' => 1, 'max' => self::COUNT_OF_LVL],
            [['type'], 'integer', 'min' => 1, 'max' => 2],
            [['image'], 'string', 'max' => 255],
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
            'description' => 'Description',
            'image' => 'Image',
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestQuestions()
    {
        return $this->hasMany(TestQuestion::className(), ['question_id' => 'question_id']);
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['image'] = function () {
            return $this->image ? Image::getImage('question', $this->image) : $this->image;
        };
        return $fields;
    }

    public function extraFields()
    {
        return ['answers'];
    }
}
