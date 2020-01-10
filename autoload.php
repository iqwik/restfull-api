<?php

spl_autoload_register(function ($className)
{
    global $config;
    $found = false;
    foreach ($config['autoload_dirs'] as $dir)
    {
        if (is_file($fileName = $config['root'].'/'.$dir.'/'.$className.'.php'))
        {
            require_once($fileName);
            $found = true;
        }
    }

    if (!$found)
        Response::send(502, [ 'class' => "not found {$className}" ]);

    return true;
});