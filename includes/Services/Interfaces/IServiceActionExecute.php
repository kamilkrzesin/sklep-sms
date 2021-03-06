<?php
namespace App\Services\Interfaces;

interface IServiceActionExecute
{
    /**
     * Execute action
     *
     * @param string $action Action to execute
     * @param array  $body
     *
     * @return string
     */
    public function actionExecute($action, $body);
}
