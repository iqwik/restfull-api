<?php

class App
{
    static $config = null;

    public static function run()
    {
        self::set_config();
        Headers::set();
        self::api_switch();
    }

    private static function set_config()
    {
        global $config;
        self::$config = $config;
    }

    private static function api_switch()
    {
        $url = isset($_GET['r']) && !empty($_GET['r']) ? rtrim($_GET['r'],'/') : '';

        if (empty($url))
            Response::send(404);

        @list($table, $id, $action) = explode('/', $url);

        $table = htmlspecialchars(strip_tags($table));
        $id = !is_integer($id) ? htmlspecialchars(strip_tags($id)) : (int)$id;
        $action = htmlspecialchars(strip_tags($action));

        $api = self::api_method($table);
        Response::send($api::execute($table, $id, $action, $api));
    }

    private static function api_method($table)
    {
        $className = ucfirst(strtolower($table));
        foreach (scandir(self::$config['api_dir']) as $file)
        {
            if ($file === "$className.php")
                return $className;
        }

        if (!in_array($table, Db::instance()->Tables()))
            Response::send(404);

        return App::$config['default_classname'];
    }
}
