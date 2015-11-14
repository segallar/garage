<?php

#
# garage project 
#

$mysql_host = "localhost";
$mysql_database = "garage";
$mysql_database_sms = "smsd";
$mysql_user = "www";
$mysql_password = "passwww";

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

$auth = false;
//if (isset($_REQUEST[session_name()]))
session_start();
if (isset($_SESSION['user_id']) AND $_SESSION['ip'] == $_SERVER['REMOTE_ADDR']) {
    $auth = true;
}

$return = array( 'test' => 'ok' , 'cmd' => $cmd , 'auth' => $auth, 'time' => date("d.m.Y H:i:s"), "version" => $server_version );

$params = array ('year','month','day','hour','minute');

function events_cols() {
    //, AVG(temp) AS temp, AVG(press)
    $cols = ", COUNT(id) AS count, AVG(temp) AS temp, AVG(press) AS press ";
    if(isset($_GET['minmax'])&&$_GET['minmax']=='on') {
        $cols .= ", MIN(temp) AS temp_min, MAX(temp) AS temp_max ";
        $cols .= ", MIN(press) AS press_min, MAX(press) AS press_max ";
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
            $where .= " $param(ts) = ".((int)$_GET[$param]*1)." ";
        }
    }
        
    if($where!="") 
        $where = " WHERE $where";
    return $where;
}

switch ( $cmd ) {
case "hello":
    break;
case "logout":
  session_destroy();
  $return['auth'] = "logout";
  break;
case "auth":// auth
    try{
        $return["auth"] = "invalid_request";
        if (isset($_GET['auth_name'])&&isset($_GET['auth_pass'])) {
            $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
                die(json_encode(Array("error" => "Database error"))); 
            mysql_select_db($mysql_database, $db); 
            $result = mysql_query("set names 'utf8'");
            $name = mysql_real_escape_string($_GET['auth_name']);
            $pass = mysql_real_escape_string($_GET['auth_pass']);
            $query = "SELECT id FROM users WHERE email='$name' AND password='$pass';";
            $return['query'] = $query;
            $res = mysql_query($query) or die(json_encode(Array('Invalid query ' => mysql_error() ,"query" => $query)));
            if ($row = mysql_fetch_assoc($res)) {
                if(isset($row['id'])&&$row['id']!="") {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                    $auth = true;
                    $return["auth"] = "true";
                } else {
                    $auth = false;
                    $return["auth"] = "no_user";
                }
                $return["auth"] = "no_user";
            }
            //header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;
case "mysql_test":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 
        $result = mysql_query("set names 'utf8'");
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
        if(!$auth) die(json_encode(array("auth" => "need_auth"))); 
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 
        $result = mysql_query("set names 'utf8'"); 
        $return = [];
        
        $group = $_GET['group'];
        if(!in_array($group,$params)) {
            $group = 'year';
        }

        if(isset($_GET["interval"])&&$_GET["interval"]!=""&&isset($_GET["div"])&&$_GET["div"]!="") {
            //SELECT day(ts),hour(ts), hour(ts) div 3 as hdiv, avg(temp) from events  where ts between now() - interval 3*5 hour and now() group by hdiv ;
            $div = (int)$_GET['div']*1;
            $interval = (int)$_GET['interval']*1;
            $intwhere = " ts BETWEEN now() - INTERVAL $interval * $div HOUR AND now()";
            $where = events_where();
            if($where=="")
                $where = " WHERE $intwhere";
            else
                $where .= "AND $intwhere";
            $query = "SELECT $group(ts) div $interval AS div$group, hour(ts) AS hour, day(ts) AS day ".events_cols();
            $query .= " FROM events $where GROUP BY div$group;";
        } else { 
            $query = "SELECT $group(ts) AS $group ".events_cols()." FROM events ".events_where()." GROUP BY $group;";
        }
        $result = mysql_query($query);
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error() . " query ->".$query));
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
        if(!$auth) die(json_encode(array("auth" => "need_auth"))); 
        $number = $_GET["number"];
        $text = $_GET["text"];
        
        //!! mast check for sql injection

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
case "show_sms":
    try {
        if(!$auth) die(json_encode(array("auth" => "need_auth"))); 
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database_sms, $db); 
        $result = mysql_query("set names 'utf8'"); 
        
        $where = "";
  
        if (isset($range)&&$range!="") {
            $limit = " LIMIT ".$range;
        }
        
        if(isset($_GET['box'])&&$_GET['box']=='inbox')
            $query = "SELECT id, SenderNumber as number, ReceivingDateTime as date, TextDecoded as text FROM inbox ";
        if(isset($_GET['box'])&&$_GET['box']=='outbox')
            $query = "SELECT id, DestinationNumber AS number, SendingDateTime AS date, TextDecoded AS text FROM sentitems ";
  
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
        if(!$auth) die(json_encode(array("auth" => "need_auth"))); 
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
