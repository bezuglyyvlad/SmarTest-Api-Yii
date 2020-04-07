<?php

namespace console\controllers;

use yii\rbac\Rule;

/**
 * Checks if authorID matches user passed via params
 */
class ExpertRule extends Rule
{
    public $name = 'isExpert';

    /**
     * @param string|int $user the user ID.
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return bool a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params)
    {
        return isset($params['category']) ? $params['category']->user_id == $user : false;
    }
}