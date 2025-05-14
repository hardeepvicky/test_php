<?php

function debug($data)
{
    $bt = debug_backtrace();
    $caller = array_shift($bt);
    
    echo "<pre>";
    echo "<b>" . $caller["file"] . " : " . $caller["line"] . "</b><br/>";
    print_r($data);
    echo "</pre>";
}

/**
 * return array to where sql string
 * @param type $conditions
 * @return string
 */
function get_where($conditions)
{
    $where = array();
    
    $raw_where = '';
    
    foreach($conditions as $operator => $data)
    {
        foreach($data as $arr)
        {
            if (isset($arr["field"]) && isset($arr["value"]))
            {
                $arr["op"] = isset($arr["op"]) ? $arr["op"] : "=";
                
                $where[] = $arr["field"] . " " . $arr["op"] . " '" . $arr["value"] . "'";
            }
            else
            {
                $where[] = get_where($arr);
            }            
        }
        
        $raw_where .= "(" . implode(" $operator ",  $where) . ")";
    }
    
    return $raw_where;
}

function str_contain($str, $needle, $start = false, $end = false)
{
    $str = strtolower(trim($str));
    $needle = strtolower(trim($needle));
    
    if ($start !== false)
    {
        $str = substr($str, $start);
    }
    
    if ($end !== false)
    {
        $str = substr($str, 0, $end);
    }
    
    return strpos($str, $needle) !== false;
}
