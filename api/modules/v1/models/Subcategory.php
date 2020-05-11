<?php

namespace api\modules\v1\models;

use api\modules\v1\controllers\TestController;
use common\models\TestHelper;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "subcategory".
 *
 * @property int $subcategory_id
 * @property string $name
 * @property int $time
 * @property int $count_of_questions
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
            [['name', 'count_of_questions', 'category_id'], 'required'],
            [['is_open', 'category_id'], 'integer'],
            [['time'], 'integer', 'min' => 1, 'max' => 1440],
            [['count_of_questions'], 'integer', 'min' => 5, 'max' => 500],
            [['name'], 'string', 'max' => 255],
            ['name', 'unique', 'targetClass' => Subcategory::className(),
                'targetAttribute' => ['name', 'category_id'], 'when' => [$this, 'whenSelfUnique'],
                'message' => 'Тест з такою назвою вже існує в даній категорії.'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'category_id']],
        ];
    }

    public function whenSelfUnique($model, $attribute)
    {
        $subcategory = Subcategory::findOne(['subcategory_id' => Yii::$app->request->get('id')]);
        if ($subcategory) {
            return $subcategory->$attribute !== $model->$attribute;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'subcategory_id' => 'Subcategory ID',
            'name' => 'Name',
            'time' => 'Час',
            'count_of_questions' => 'Кількість запитань',
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
        if ($test && !TestHelper::testIsFinished($test)) return $test;
        return [];
    }

    public function extraFields()
    {
        return ['test'];
    }
}
