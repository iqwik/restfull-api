<?php
// Общая модель для всех АПИшек
class Model
{
    protected static $table = null;
    protected static $id = null;
    protected static $action = null;
    protected static $requestData = null;

    protected static function set_data($table, $id, $action)
    {
        self::$table = $table;
        self::$id = $id;
        self::$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? $id : $action;
        self::$requestData = json_decode(file_get_contents('php://input'), true);
    }

    // FIXME: костыль - возвращаем true, если запрос на приватные табл., но разрешаем, если добавили табл. в разрешенные
    // вообще здесь будет проверка прав пользователя/авторизован или нет
    protected static function privateAccessTable($table)
    {
        if (in_array($table, App::$config['db']['privateTables']))
        {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET' && in_array($table, App::$config['db']['acceptPostTables']))
            {
                return false;
            }
            return true;
        }
        return false;
    }

    public static function execute($table = null, $id = null, $action = null, $api = null)
    {
        if (self::privateAccessTable($table))
            return ['code' => 403, 'data' => ['error' => 'Private Access']];

        self::set_data($table, $id, $action);
        $method = $_SERVER['REQUEST_METHOD'];

        if (!is_null($api) && method_exists($api, $method))
        {
            return $api::$method();
        }

        return [ 'code' => 404 ];
    }

    protected static function GET()
    {
        if (!empty(self::$action))
            return [ 'code' => 405 ];

        $table = self::$table;
        $query = "SELECT * FROM {$table}";
        $params = [];

        // выборка строки из таблицы по параметру
        if ($id = self::$id)
        {
            // кастомная выборка, если таблица есть в кофиге
            if (in_array($table, App::$config['db']['customSelect']))
            {
                $select = new CustomSelect($table, $id);
                $query = $select->query();
                $params = $select->params();
            }
            // иначе стандартная выборка по id
            else
            {
                $query .= ' WHERE id = :id LIMIT 1';
                $params['id'] = $id;
            }
        }
        // сортировка для массовой выборки
        else
        {
            $sort = App::$config['db']['sortTables'];
            empty($sort[$table]) ?: $query .= ' '.$sort[$table];
        }

        try
        {
            if ($res = Db::instance()->Select($query, $params))
            {
                $res = is_array($res) && count($res) > 1 ? $res : $res[0];
                $res = [
                    'code' => 200,
                    'show_code' => App::$config['show_code_in_response_body'],
                    'data' => [ 'data' => $res ]
                ];
            }
            else
            {
                $res = [ 'code' => 400, 'data' => [ 'error' => 'doesn\'t exists field in table' ] ];
            }
        }
        catch (Exception $e)
        {
            $res = [ 'code' => 500, 'data' => [ 'error' => $e->getMessage() ] ];
        }

        return $res;
    }

    protected static function POST()
    {
        if (empty(self::$action) || self::$action != 'create')
            return [ 'code' => 405, 'data' => [ 'error' => self::$action ] ];

        try
        {
            Db::instance()->Insert(self::$table, self::$requestData);
            $res = [ 'code' => 201 ];
        }
        catch (Exception $e)
        {
            $res = [ 'code' => 400, 'data' => [ 'error' => $e->getMessage() ] ];
        }
        return $res;
    }

    // обновление данных
    protected static function PUT()
    {
        if (empty(self::$id) && empty(self::$action))
            return [ 'code' => 405, 'data' => [ 'error' => 'empty id & action' ] ];

        $res = [ 'code' => 400, 'data' => [ 'error' => 'wrong params' ] ];
        if (($params = self::$requestData) && !is_null($params) && self::$action === 'update' && ($id = (int)self::$id))
        {
            $table = self::$table;
            try
            {
                Db::instance()->Update($table, $params, [ 'id', $id ]);
                $res = [ 'code' => 200 ];
            }
            catch (Exception $e)
            {
                $res = [ 'code' => 500, 'error' => $e->getMessage() ];
            }
        }
        return $res;
    }

    protected static function DELETE()
    {
        if (empty(self::$id))
            return [ 'code' => 405, 'data' => [ 'error' => 'empty id & action' ] ];

        $table = self::$table;
        $query = "DELETE FROM {$table} WHERE ";
        $params = [];

        if (self::$id === 'delete')
        {
            $requestData = self::$requestData;
            // кастомное удаление по произовльному полю && можно массово
            if ( isset($requestData['field']) && ($field = htmlspecialchars(strip_tags($requestData['field'])))
                && isset($requestData['value']) && !empty($requestData['value'])
                && (isset($requestData['equal']) && ($equal = $requestData['equal'])
                    && ($equal === '=' || $equal === '<' || $equal === '>')) )
            {
                $value = is_numeric($requestData['value'])
                    ? (int)$requestData['value']
                    : htmlspecialchars(strip_tags($requestData['value']));

                $query .= "{$field} {$equal} :{$field}";
                $params = [ $field => $value ];
            }
            else
            {
                return [ 'code' => 400, 'data' => [ 'error' => 'wrong parametres' ] ];
            }
        }
        elseif (($id = (int)self::$id) && self::$action === 'delete')
        {
            $query .= "id = :id";
            $params = [ 'id' => $id ];
        }

        try
        {
            Db::instance()->Query($query, $params);
            $res = [ 'code' => 200 ];
        }
        catch (Exception $e)
        {
            $res = [ 'code' => 400, 'data' => [ 'error' => $e->getMessage() ] ];
        }
        return $res;
    }
}