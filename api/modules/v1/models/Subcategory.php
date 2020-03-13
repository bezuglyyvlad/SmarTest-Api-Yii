<?php

namespace api\modules\v1\models;

use api\modules\v1\controllers\TestController;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "subcategory".
 *
 * @property int $subcategory_id
 * @property string $name
 * @property int $time
 * @property int $count_of_question
 * @property int $is_open
 * @property int $category_id
 *
 * @property Question[] $questions
 * @property Category $category
 * @property Test[] $tests
 */
class Subcategory extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subcategory';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'count_of_question', 'category_id'], 'required'],
            [['time', 'count_of_question', 'is_open', 'category_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'category_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'subcategory_id' => 'Subcategory ID',
            'name' => 'Name',
            'time' => 'Time',
            'count_of_question' => 'Count Of Question',
            'is_open' => 'Is Open',
            'category_id' => 'Category ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(Question::className(), ['subcategory_id' => 'subcategory_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }

    /**
     * @return array|ActiveRecord
     */
    public function getTest()
    {
        $test = $this->hasMany(Test::className(), ['subcategory_id' => 'subcategory_id'])
            ->orderBy(['test_id' => SORT_DESC])->one();
        if ($test && !TestController::testIsFinished($test)) return $test;
        return [];
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['is_open']);
        return $fields;
    }

    public function extraFields()
    {
        return ['test'];
    }
}
