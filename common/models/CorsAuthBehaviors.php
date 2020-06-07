<?php


namespace common\models;


use yii\filters\auth\HttpBearerAuth;

class CorsAuthBehaviors
{
    public static function getCorsAuthSettings($behaviors)
    {
        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['http://localhost:3000', 'http://192.168.13.3:3000', 'https://smartest.netlify.app',
                    'http://localhost:5000', 'http://192.168.13.3:5000', 'https://pre-diploma.netlify.app',
                    'https://closed-smartest.netlify.app'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
            ],
        ];

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;


        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        return $behaviors;
    }
}