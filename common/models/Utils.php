<?php


namespace common\models;


class Utils
{
    public static function getClassName($fullClassName)
    {
        return strtolower(explode('Controller', explode("\\", $fullClassName)[2])[0]);
    }

    public static function array_to_xml($data, &$xml_data, $itemsToExept)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $itemsToExept) || !$value) continue;
            if (is_array($value)) {
                foreach ($value as $arrayElem) {
                    $subnode = $xml_data->addChild($key);
                    self::array_to_xml($arrayElem, $subnode, $itemsToExept);
                }
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}