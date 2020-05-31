<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
//    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'api\modules\v1\Module',
        ],
    ],
    'language' => 'uk',
    'sourceLanguage' => 'uk',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-api',
            'baseUrl' => '/api',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser'
            ],
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-api', 'httpOnly' => true],
            'enableSession' => false,
        ],
        'session' => [
            // this is the name of the session cookie used for login on the api
            'name' => 'advanced-api',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/category'],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/admin'],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/expert'],
                    'extraPatterns' => [
                        'GET subcategories' => 'subcategories',
                        'OPTIONS subcategories' => 'options',

                        'GET questions' => 'questions',
                        'OPTIONS questions' => 'options',

                        'POST question' => 'question',
                        'OPTIONS question' => 'options',

                        'POST upload' => 'upload',
                        'OPTIONS upload' => 'options',

                        'DELETE deleteImage' => 'delete-image',
                        'OPTIONS deleteImage' => 'options',

                        'POST import' => 'import',
                        'OPTIONS import' => 'options',

                        'GET export' => 'export',
                        'OPTIONS export' => 'options',

                        'GET testStatistics' => 'test-statistics',
                        'OPTIONS testStatistics' => 'options',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/question'],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/answer'],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/subcategory'],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/test'],
                    'extraPatterns' => [
                        'POST nextQuestion' => 'next-question',
                        'OPTIONS nextQuestion' => 'options',

                        'GET result' => 'result',
                        'OPTIONS result' => 'options',

                        'GET rating' => 'rating',
                        'OPTIONS rating' => 'options',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/user'],
                    'extraPatterns' => [
                        'POST login' => 'login',
                        'DELETE logout' => 'logout',

                        'OPTIONS login' => 'options',
                        'OPTIONS logout' => 'options',
                    ],
                ],
            ],
        ],

    ],
    'params' => $params,
];
