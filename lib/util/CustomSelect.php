<?php

/**
 * Class CustomSelect для выборки по строке
 */
class CustomSelect
{
    private $table = null;
    private $var;
    private $field = null;

    public function __construct($table, $var)
    {
        $this->set($table, $var);
    }

    private function set($table, $var)
    {
        $this->table = $table;
        $this->var = $var;
        if(is_numeric($var))
        {
            $this->field = 'id';
            $this->var = (int)$var;
        }
        else
        {
            $this->field = 'uri';
        }
    }

    public function query()
    {
        $field = $this->field;
        return 'SELECT * FROM '.$this->table.' WHERE '.$field.' = :'.$field;
    }

    public function params()
    {
        return [$this->field => $this->var];
    }
}