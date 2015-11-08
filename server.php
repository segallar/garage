<?php

#
# garage project 
#

$mysql_host = "localhost";
$mysql_database = "garage";
$mysql_database_sms = "smsd";
$mysql_user = "root";
$mysql_password = "nigthfal";

$server_version = '0.1.1';

header('Content-type: application/json; charset=utf-8');

$cmd = "no_command";    
if(isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
}

$debug = false;
if(isset($_GET['debug'])&&$_GET['debug']=="on") {
    $debug = true;
}  

$return = array( 'test' => 'ok' , 'cmd' => $cmd ,'time' => date("d.m.Y H:i:s"), "version" => $server_version );

$params = array ('year','month','day','hour','minute');

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

//
// For barometer board
//      
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

//
// SMS subsystem
//
// show_sms_outbox, show_sms_inbox, send_sms, show_balance
//

case "send_sms":
    try {
        
        $number = $_GET["number"];
        $text = $_GET["text"];

        if( isset($number)&&isset($text) ) {
            
            $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or die("Database error"); 
            mysql_select_db($mysql_database_sms, $db); 
            $result = mysql_query("set names 'utf8'"); 

            // insert into outbox (number,text) values('+31972123456', 'Tetsing Testing everyone');
            
            $query = "insert into outbox (DestinationNumber,TextDecoded) values ". 
                " ('$number','$text');"; 
            $result = mysql_query($query); 
            if (!$result) {
                $return['result'] = 'Invalid query: ' . mysql_error();
            } else {
                $return['result'] = 'ok';
            }
        } else {
            $return['result'] = 'false URL';
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;
case "show_sms_inbox":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database_sms, $db); 
        $result = mysql_query("set names 'utf8'"); 
        
        $where = "";
  
        if (isset($range)&&$range!="") {
            $limit = " LIMIT ".$range;
        }
        
        $query = "SELECT id, SenderNumber as number, ReceivingDateTime as date, TextDecoded as text FROM inbox ";
        if ($where != "") {
            $query .= " WHERE ".$where." ";
        }
    
        if (isset($limit)) {
            $query .= $limit;
        }
        
        $query .= " ORDER BY id DESC;";
        
        $returnQuery = [];
        
        $result = mysql_query($query); 
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            while ($arr= mysql_fetch_array($result, MYSQL_ASSOC)) {
                $returnQuery[] = $arr;    
            }
            if (!$debug) {
                $return = [];
                $return = $returnQuery;
            } else {
                $return["sql"] = $query;
                $return["result"] = $returnQuery;
            } 
        }
    }
    catch (Exception $e) {
        echo("SQL:".$query."</br>");
        $e->getMessage();
    }
    break;
case "show_sms_outbox":
    try {
       
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database_sms, $db); 
        $result = mysql_query("set names 'utf8'"); 
        
        if(isset($_GET["range"])) {
            $limit = " LIMIT ".$_GET["range"];
        }
        
        $query = "SELECT id, SendingDateTime AS date, TextDecoded AS text, DestinationNumber AS number FROM sentitems ";
                
        $where = "";
        if ($where != "") {
            $query .= " WHERE ".$where." ";
        }
    
        if (isset($limit)) {
            $query .= $limit;
        }
        
        $query .= " ORDER BY id DESC;";
        
        $returnQuery = [];
        
        $result = mysql_query($query); 
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            while ($arr= mysql_fetch_array($result, MYSQL_ASSOC)) {
                $returnQuery[] = $arr;    
            }
            if (!$debug) {
                $return = [];
                $return = $returnQuery;
            } else {
                $return["sql"] = $query;
                $return["result"] = $returnQuery;
            } 
        }
    }
    catch (Exception $e) {
        echo("SQL:".$query."</br>");
        $e->getMessage();
    }
    break;
case 'balance':
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database_sms, $db); 
        $result = mysql_query("set names 'utf8'"); 
        
        $balance = -1;
        
        $result = mysql_query("SELECT balance, balance_time FROM balance ORDER BY id DESC LIMIT 1;");
        if ($arr = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $balance = $arr['balance'];
            $return = $arr;
        } else { 
            $return['balance'] = '-1';
        }
    }
    catch (Exception $e) {
        echo("SQL:".$query."</br>");
        $e->getMessage();
    }    
    break;
        
//
// Default action
//

default :
    $return["cmd"] = "no_command"; 
}

if(isset($db))
    mysql_close($db);

if($debug) 
    echo "$query \n\n";  

echo json_encode($return);

?>
