<?php

class Mysql
{
    public static $queryLog = true;
    public static $conn, $db, $logs;
    
    public function __construct($config)
    {
        $config['port'] = $config['port'] ?? 3306;
        self::$db = $config['database'];
        if (!self::$conn)
        {
            self::$conn = mysqli_connect($config['server'], $config['username'], $config['password'], $config['database'], $config['port']);
        }
    }
    
    public function query($q)
    {
        self::$logs[] = $q;
        
        return mysqli_query(self::$conn, $q);
    }

    public function multi_query($query)
    {
        Mysql::$conn->multi_query($query);
        
        while (Mysql::$conn->next_result()) {;} 
    }

    public function getLastInsertId()
    {
        return mysqli_insert_id(self::$conn);
    }
    
    public function select($q)
    {
        $result = $this->query($q);
        
        $records = array();
        
        while($row = mysqli_fetch_assoc($result))
        {
            $records[] = $row;
        }
        
        return $records;
    }
    
    public function transactionBegin()
    {
        $this->query("SET AUTOCOMMIT=0");
        $this->query("START TRANSACTION");
    }
    
    public function transactionCommit()
    {
        $this->query("COMMIT");
    }
    
    public function transactionRollback()
    {
        $this->query("ROLLBACK");
    }

    public function getInsertQuery($table, $data)
    {
        $field_list = $value_list = [];
        
        foreach($data as $k => $v)
        {
            if (is_numeric($k))
            {
                throw new Exception("Key should be string");
            }

            if (is_bool($v))
            {
                if ($v)
                {
                    $data[$k] = "1";
                }
                else
                {
                    $data[$k] = "0";
                }
            }
            else if (is_array($v))
            {
                if ($v)
                {
                    $data[$k] = serialize($v);
                }
                else
                {
                    $data[$k] = "";
                }
            }
            else if (is_null($v))
            {
                $data[$k] = "NULL";                
            }

            $field_list[$k] = "`" . $k . "`";

            $data[$k] = trim($data[$k], '\'"');
            
            if (substr($v, 0, 1) == "@")
            {
                $value_list[$k] = $data[$k];
            }
            else
            {
                $value_list[$k] = "'" . $data[$k] . "'";
            }
        }

        if (empty($value_list))
        {
            throw new Exception("Value not found");
        }

        $q = "INSERT INTO `$table`";

        $q .= "(" . implode(", ", $field_list) . ")";

        $q .= " VALUES (" . implode(", ", $value_list) . ");";

        return $q;
    }

    public function getUpdateQuery($table, $data, $where)
    {
        $field_list = $value_list = [];
        
        foreach($data as $k => $v)
        {
            if (is_numeric($k))
            {
                throw new Exception("Key should be string");
            }

            if (is_bool($v))
            {
                if ($v)
                {
                    $data[$k] = "1";
                }
                else
                {
                    $data[$k] = "0";
                }
            }
            else if (is_array($v))
            {
                if ($v)
                {
                    $data[$k] = serialize($v);
                }
                else
                {
                    $data[$k] = "";
                }
            }
            else if (is_null($v))
            {
                $data[$k] = "NULL";                
            }

            $field_list[$k] = "`" . $k . "`";

            $data[$k] = trim($data[$k], '\'"');

            if (substr($v, 0, 1) == "@")
            {
                $value_list[$k] = $data[$k];
            }
            else
            {
                $value_list[$k] = "'" . $data[$k] . "'";
            }
        }

        if (empty($value_list))
        {
            throw new Exception("Value not found");
        }

        $q = "UPDATE `$table` SET ";

        $list = [];

        foreach($value_list as $f => $v)
        {
            $list = "`" . $f . "` = " . $v;
        }

        $q .= implode(", ", $list);

        $q .= " WHERE $where ";

        return $q;
    }

    public function save($table, $data, $primary_field = "id")
    {
        if (isset($data[$primary_field]))
        {
            $this->query($this->getUpdateQuery($table, $data, "`$primary_field` = " . $data[$primary_field]));
        }
        else
        {
            $this->query($this->getInsertQuery($table, $data));

            return $this->getLastInsertId();
        }
    }

    public function getInsertManyQuery($table, $records)
    {
        $field_list = $group_value_list = [];
        
        foreach($records as $index => $data)
        {
            foreach($data as $k => $v)
            {
                if (is_numeric($k))
                {
                    throw new Exception("Key should be string");
                }

                if (is_bool($v))
                {
                    if ($v)
                    {
                        $data[$k] = "1";
                    }
                    else
                    {
                        $data[$k] = "0";
                    }
                }
                else if (is_array($v))
                {
                    if ($v)
                    {
                        $data[$k] = serialize($v);
                    }
                    else
                    {
                        $data[$k] = "";
                    }
                }
                else if (is_null($v))
                {
                    $data[$k] = "NULL";                
                }

                if ($index == 0)
                {
                    $field_list[$k] = "`" . $k . "`";
                }

                $data[$k] = trim($data[$k], '\'"');

                if (substr($v, 0, 1) == "@")
                {
                    $group_value_list[$index][$k] = $data[$k];
                }
                else
                {
                    $group_value_list[$index][$k] = "'" . $data[$k] . "'";
                }
            }
        }

        if (empty($group_value_list))
        {
            throw new Exception("value not found");
        }
            
        $q = "INSERT INTO `$table`";

        $q .= "(" . implode(", ", $field_list) . ")";

        $list = [];
        foreach($group_value_list as $value_list)
        {
            $list[] = "(" . implode(", ", $value_list) . ")";
        }

        $q .= " VALUES " . implode(",", $list) . ";";

        return $q;
    }

    public function insertMany($table, $records)
    {
        $this->query($this->getInsertManyQuery($table, $records));

        return $this->getLastInsertId();
    }
}
