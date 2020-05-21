<?php


namespace common\models;


use Yii;

class Upload
{
    public static function deleteOldImage($name, $className)
    {
        $filePath = Yii::getAlias("@api") . '/web/images/' . $className . '/' . $name;
        if ($name != '' && file_exists($filePath)) {
            unlink($filePath);
        }
    }
}