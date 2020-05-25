<?php

namespace common\models;

use api\modules\v1\models\Subcategory;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class ImportForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $file;
    public $subcategory_id;

    public function rules()
    {
        return [
            [['file', 'subcategory_id'], 'required'],
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'xml'],
            [['subcategory_id'], 'integer'],
            [['subcategory_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subcategory::className(), 'targetAttribute' => ['subcategory_id' => 'subcategory_id']]
        ];
    }
}