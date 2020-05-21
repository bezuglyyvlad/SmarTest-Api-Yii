<?php


namespace common\models;


use Yii;
use yii\helpers\Url;

class Image
{
    public static function getImage($className, $image)
    {
        $imagePath = Yii::getAlias("@api") . '/web/images/' . $className . '/' . $image;
        $imageSize = getimagesize($imagePath);
        $width = $imageSize['0'];
        $height = $imageSize['1'];
        return Url::base(true) . "/images/{$className}/{$image}?w={$width}&h={$height}";
    }
}