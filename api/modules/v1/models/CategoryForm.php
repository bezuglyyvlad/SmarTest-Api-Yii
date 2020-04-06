<?php

namespace api\modules\v1\models;

use common\models\User;
use yii\base\Model;

class CategoryForm extends Model
{
    public $name;
    public $userEmail;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['userEmail'], 'string', 'max' => 255],
            [['userEmail'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(),
                'targetAttribute' => ['userEmail' => 'email'],
                'message' => 'Пользователя с таким Email не существует.'],
        ];
    }

    private function saveCategory($model, $user_id){
        $model->name = $this->name;
        $model->user_id = $user_id;
        return $model->save();
    }

    public function createCategory($user_id){
        $model = new Category();
        $this->saveCategory($model, $user_id);
    }

    public function updateCategory($category_id, $user_id){
        $model = Category::findOne(['category_id' => $category_id]);
        $this->saveCategory($model, $user_id);
    }
}
