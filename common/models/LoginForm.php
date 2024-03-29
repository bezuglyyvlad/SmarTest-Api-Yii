<?php
namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // email and password are both required
            [['email', 'password'], 'required'],
            ['email', 'trim'],
            ['email', 'email'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неправильна електронна пошта або пароль.');
            }
        }
    }

    /**
     * Logs in a user using the provided email and password.
     *
     * @return bool|array whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            $_user = $this->getUser();
            Yii::$app->user->login($_user, 0);
            if ($_user->save()) {
                return $_user->generateAccessToken();
            }
        }

        $errors = array_values($this->getErrors());
        return array_shift($errors);
    }

    /**
     * Finds user by [[email]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByEmail($this->email);
        }

        return $this->_user;
    }
}
