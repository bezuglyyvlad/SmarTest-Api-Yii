<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;
    public $newImageName;

    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg, webp', 'maxSize' => 204800],
        ];
    }

    public function upload($className)
    {
        if ($this->validate()) {
            $this->imageFile->saveAs(Yii::getAlias("@api") . '/web/images/' . $className . '/' . $this->newImageName . '.' . $this->imageFile->extension);
            return true;
        } else {
            return false;
        }
    }
}