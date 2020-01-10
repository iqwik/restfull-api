<?php
// TODO: написать сервис авторизации с использованием PHP-JWT
class Auth
{
    public static function execute($api = null, $action = null)
    {
        return [ 'code' => 200, 'data' => [ 'api' => $api, 'action' => $action ] ];
    }
}