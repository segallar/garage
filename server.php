<?php

#
# garage project 
#

$mysql_host = "localhost";
$mysql_database = "garage";
$mysql_user = "root";
$mysql_password = "nigthfal";

$server_version = '0.1.1';

header('Content-type: application/json; charset=utf-8');

$cmd = "no_command";    
if(isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
}

$debug = false;
if($_GET["debug"]=="on") {
    $debug = true;
}  

$return = array( 'test' => 'ok' , 'cmd' => $cmd ,'time' => date("d.m.Y H:i:s"), "version" => $server_version );

$params = array ('year','mouth','day','hour','minute');

function events_cols() {
    //, AVG(temp) AS temp, AVG(press)
    $cols = ", AVG(temp) AS temp, AVG(press) AS press ";
    if(isset($_GET['minmax'])&&$_GET['minmax']=='on') {
        $cols .= ", MIN(temp) as min_temp, MAX(temp) as max_temp ";
        $cols .= ", MIN(press) as min_press, MAX(press) as max_press ";
    }
    return $cols;
}

function events_where() {
    global $params;
    $where = "";
    foreach($params as &$param) {
        if(isset($_GET[$param])&&$_GET[$param]!="") {
            if($where!="")
                $where .= " AND ";
            $where .= " $param(ts) = $_GET[$param] ";
        }
    }
    if($where!="") 
        $where = " WHERE $where";
    return $where;
}

switch ( $cmd ) {
case "hello":
    break;
case "mysql_test":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $query = "SELECT COUNT(id) AS ITEMS FROM events;"; 
        $result = mysql_query($query); 
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            $return = mysql_fetch_array($result);
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;
case "events":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $return = [];

        $group = $_GET['group'];
        if(!in_array($group,$params)) {
            $group = 'year';
        }
       
        $query = "SELECT $group(ts) AS $group ".events_cols()." FROM events ".events_where()." GROUP BY $group;";                   $result = mysql_query($query);
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            while($arr = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $return[] = $arr;
            }     
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;

default :
    $return["cmd"] = "no_command"; 
}

if(isset($db))
    mysql_close($db);

if($debug) 
    echo "$query \n\n";  

echo json_encode($return);

?>
