<?php


namespace common\models;


class Utils
{
    public static function getClassName($fullClassName)
    {
        return strtolower(explode('Controller', explode("\\", $fullClassName)[2])[0]);
    }
}