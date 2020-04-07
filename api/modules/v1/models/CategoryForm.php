<?php

namespace api\modules\v1\models;

use common\models\User;
use console\controllers\RbacController;
use Yii;
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
            ['name', 'required'],
            ['name', 'string', 'max' => 255],
            ['name', 'unique', 'targetClass' => Category::className(), 'when' => [$this, 'whenSelfUnique'],
                'message' => 'Категория с таким именем уже существует.'],
            ['userEmail', 'string', 'max' => 255],
            ['userEmail', 'exist', 'skipOnError' => true, 'targetClass' => User::className(),
                'targetAttribute' => ['userEmail' => 'email'],
                'message' => 'Пользователя с таким Email не существует.'],
        ];
    }

    public function whenSelfUnique($model, $attribute)
    {
        $category = Category::findOne(['category_id' => Yii::$app->request->get('id')]);
        if ($category){
            return $category->$attribute !== $model->$attribute;
        }
        return true;
    }

    private function saveCategory($model, $user)
    {
        $user_id = $user ? $user->getId() : $user;

        $model->name = $this->name;
        $model->user_id = $user_id;
        return $model->save();
    }

    public function createCategory()
    {
        $user = User::findOne(['email' => $this->userEmail]);
        $user && RbacController::actionAddExpert($user->getId());

        $model = new Category();
        $this->saveCategory($model, $user);
    }

    public function updateCategory($category_id)
    {
        $user = User::findOne(['email' => $this->userEmail]);

        $model = Category::findOne(['category_id' => $category_id]);
        $oldUserId = $model->user_id;
        $this->saveCategory($model, $user);

        if ($user) {
            RbacController::actionAddExpert($user->getId());
        }
        if ($oldUserId) {
            RbacController::actionRemoveExpert($oldUserId);
        }
    }
}
