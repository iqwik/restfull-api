<?php

class Headers
{
    public static function set()
    {
        $allowed_domains = App::$config['allowed_domains'];
        $allowed_methods = implode(',',App::$config['allowed_methods']);
        if (isset($_SERVER['HTTP_ORIGIN']) && ($http_origin = $_SERVER['HTTP_ORIGIN'])
            && in_array(strtolower($http_origin), $allowed_domains) )
        {
            header("Access-Control-Allow-Origin: $http_origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS'
            && in_array($_SERVER['REQUEST_METHOD'], App::$config['allowed_methods']))
        {
            header("Access-Control-Allow-Methods: {$allowed_methods}");
            header('Access-Control-Allow-Headers: DNT,User-Agent,X-Custom-Header,If-Modified-Since,Authorization,Origin,X-Requested-With,Accept,Cache-Control,Content-Type,Range,X-PINGOTHER');
            header('Content-Length:0');
            Response::send(204);
        }
        else
        {
            header("Access-Control-Allow-Methods: {$allowed_methods}");
            header('Access-Control-Allow-Headers: User-Agent,X-Custom-Header,Authorization,Origin,X-Requested-With,Accept,X-PINGOTHER,Cache-Control,Content-Type');
            header('Access-Control-Expose-Headers: Content-Length,Content-Range');
        }

        header('Accept-Encoding: compress, gzip');
        $content_type = App::$config['ctype_json'] ? 'application/json' : 'text/html';
        header("Content-Type: {$content_type}; charset=UTF-8");
    }
}