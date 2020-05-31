<?php

namespace api\modules\v1\models;

use common\models\User;
use Yii;

/**
 * This is the model class for table "test".
 *
 * @property int $test_id
 * @property string $category_name
 * @property string $subcategory_name
 * @property int $time
 * @property int $count_of_questions
 * @property string $date_start
 * @property string $date_finish
 * @property int $count_of_right_answers
 * @property float $score
 * @property int $user_id
 * @property int|null $subcategory_id
 *
 * @property User $user
 * @property Subcategory $subcategory
 * @property TestQuestion[] $testQuestions
 */
class Test extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_name', 'subcategory_name', 'time', 'count_of_questions', 'date_start', 'date_finish', 'user_id'], 'required'],
            [['time', 'count_of_questions', 'count_of_right_answers', 'user_id', 'subcategory_id'], 'integer'],
            [['score'], 'number'],
            [['category_name', 'subcategory_name'], 'string', 'max' => 255],
            [['date_start', 'date_finish'], 'string', 'max' => 30],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['subcategory_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subcategory::className(),
                'targetAttribute' => ['subcategory_id' => 'subcategory_id'], 'filter' => ['is_open' => 1]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'test_id' => 'Test ID',
            'category_name' => 'Category Name',
            'subcategory_name' => 'Subcategory Name',
            'time' => 'Time',
            'count_of_questions' => 'Count Of Question',
            'date_start' => 'Date Start',
            'date_finish' => 'Date Finish',
            'count_of_right_answers' => 'Count Of Right Anwers',
            'score' => 'Score',
            'user_id' => 'User ID',
            'subcategory_id' => 'Subcategory ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
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
        return $this->hasMany(TestQuestion::className(), ['test_id' => 'test_id']);
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['user_id']);
        return $fields;
    }

    public function extraFields()
    {
        return ['user'];
    }
}
