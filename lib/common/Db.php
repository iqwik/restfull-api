<?php
class Db
{
    private static $_instance = null;
    private $db;
    public static function instance()
    {
        return self::$_instance = self::$_instance == null ? self::$_instance = new Db() : self::$_instance;
    }
    private function __construct()
    {
        global $config;
        $db = $config['db'];
        $this->Connect($db['username'], $db['password'], $db['name'], $db['host']);
    }
    private function __sleep(){}
    private function __clone() {}
    private function __wakeup() {}
    public function Connect($username, $password, $base, $host)
    {
        $this->db = new PDO(
            'mysql:host='.$host.';port=3306;dbname='.$base.';charset=utf8;', $username, $password,
            [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    public function Tables()
    {
        $res = $this->Query("SHOW TABLES");
        return $res->fetchAll(PDO::FETCH_COLUMN);
    }
    public function Query($query, $params = [])
    {
        $res = $this->db->prepare($query);
        $res->execute($params);
        return $res;
    }
    public function Insert($table, $params = [])
    {
        $fields = [];
        $values = [];
        foreach ($params as $key => $value)
        {
            $fields[] = "$key = :$key";
            $v = htmlspecialchars(strip_tags($value));
            $values[$key] = $key == 'password' ? password_hash($v, PASSWORD_BCRYPT) : $v;
        }
        $keys = implode(", ",$fields);
        $stmt = $this->db->prepare("INSERT INTO {$table} SET {$keys}");
        return $stmt->execute($values) ? true : false;
    }
    public function Select($query, $params = [])
    {
        return ($result = $this->Query($query, $params)) ? $result->fetchAll() : false;
    }
    public function Update($table, $params = [], $where = [ 'id', null ])
    {
        $fields = [];
        $values = [];
        foreach ($params as $key => $value)
        {
            $fields[] = "$key = :$key";
            $v = htmlspecialchars(strip_tags($value));
            $values[$key] = $key == 'password' ? password_hash($v, PASSWORD_BCRYPT) : $v;
        }
        $keys = implode(", ",$fields);
        $stmt = $this->db->prepare("UPDATE {$table} SET {$keys} WHERE {$where[0]} = :{$where[0]}");
        foreach ($values as $k => &$v)
        {
            $stmt->bindParam(":$k", $v);
        }
        $stmt->bindParam(":{$where[0]}", $where[1]);
        return $stmt->execute() ? true : false;
    }
    public function SelectRow($query, $params = [])
    {
        return ($result = $this->Query($query, $params)) ? $result->fetchColumn() : false;
    }
}