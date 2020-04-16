<?php

namespace common\models;

use Yii;
use yii\base\Model;

class SignUpForm extends Model
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

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        $user->status = 10;
        return $user->save();

    }

    public function whenSelfUnique($model, $attribute) {
        if (Yii::$app->user->identity){
            return Yii::$app->user->identity->$attribute !== $model->$attribute;
        }
        return true;
    }

    public function updateUser($id)
    {
        if (!$this->validate()) {
            return null;
        }
        $model = User::findOne($id);
        $model->username = $this->username;
        $model->email = $this->email;
        $model->setPassword($this->password);
        return $model->save();
    }
}
