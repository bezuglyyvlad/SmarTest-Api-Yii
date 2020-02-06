<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "subcategory".
 *
 * @property int $subcategory_id
 * @property string $name
 * @property int $time
 * @property int $category_id
 *
 * @property Category $category
 */
class Subcategory extends \yii\db\ActiveRecord
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
            [['name', 'time', 'category_id'], 'required'],
            [['time', 'category_id'], 'integer'],
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
            'category_id' => 'Category ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }
}
