<?php

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Signup form
 */
class UpdateUserForm extends Model
{
    public $username;
    public $email;
    public $password;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\common\models\User', 'when' => [$this, 'whenSelfUnique'], 'message' => 'Это имя пользователя уже занято.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'when' => [$this, 'whenSelfUnique'], 'message' => 'Этот адрес электронной почты уже занят.'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 255],
        ];
    }

    public function whenSelfUnique($model, $attribute) {
        return Yii::$app->user->identity->$attribute !== $model->$attribute;
    }

    public function updateUser($id)
    {
        if (!$this->validate()) {
            return null;
        }
        $model = User::findOne($id);
        return $model;
    }
}
