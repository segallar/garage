<?php

#
# garage project 
#

$mysql_database = "garage";
$mysql_database_sms = "smsd";

// определяем параметры подключения к базе
if($_SERVER["DOCUMENT_ROOT"] == "/Users/rosipov/progs/garage/www" ||
   $_SERVER["DOCUMENT_ROOT"] == "/Users/romanosipov/test/garage/www") {
    $mysql_host = "192.168.5.252";
    $mysql_user = "mac";
    $mysql_password = "macpass";
    
} else {
    $mysql_host = "localhost";
    $mysql_user = "www";
    $mysql_password = "passwww";
}

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
    $_SESSION['count']++;
    $date = date_create();
    $_SESSION['last_time'] = date_timestamp_get($date);
}

$return = array( 'hello' => 'ok' , 'cmd' => $cmd , 'auth' => $auth, 'time' => date("d.m.Y H:i:s"), "version" => $server_version );

if($auth) {
    $return['session_ip'] = $_SESSION['ip'];
    $return['session_begin_time'] = $_SESSION['begin_time'];
    $return['session_last_time'] = $_SESSION['last_time'];
    $return['session_count'] = $_SESSION['count'];
    $return['session_user_id'] = $_SESSION['user_id'];
}

if($debug) {
    foreach($_SERVER as $key => $val) {
        $return["ENV_".$key] = $val;
    }
}

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
                die(json_encode(Array($return, "error" => "Database error")));
            mysql_select_db($mysql_database, $db); 
            $result = mysql_query("set names 'utf8'");
            $name = mysql_real_escape_string($_GET['auth_name']);
            $pass = mysql_real_escape_string($_GET['auth_pass']);
            $query = "SELECT id FROM users WHERE email='$name' AND password='$pass';";
            $return['query'] = $query;
            session_destroy();
            session_start();
            $res = mysql_query($query) 
                or die(json_encode(Array($return, "error" => "Invalid query", "mysql_error" => mysql_error(), "query" => $query)));
            if ($row = mysql_fetch_assoc($res)) {
                if(isset($row['id'])&&$row['id']!="") {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['count'] = 0;
                    $date = date_create();
                    $_SESSION['last_time'] = date_timestamp_get($date);
                    $_SESSION['begin_time'] = $_SESSION['last_time'];
                    $auth = true;
                    $return["auth"] = "true";
                } else {
                    $auth = false;
                    $return["auth"] = "no_user";
                }
            } else {
                $return["auth"] = "no_user";   
            }
        }
    }
    catch (Exception $e) {
        if(isset($query)) $return['query'] = $query;
        $return["error"] = $e->getMessage();
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
        if(isset($query)) $return['query'] = $query;
        $return["error"] = $e->getMessage();
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

        // interval in hours
        if(isset($_GET["interval"])&&$_GET["interval"]!=""&&isset($_GET["div"])&&$_GET["div"]!="") {
            $div = (int)$_GET['div']*1;
            $interval = (int)$_GET['interval']*1;
            $intwhere = " ts BETWEEN now() - INTERVAL $interval * $div HOUR AND now()";
            $where = events_where();
            if($where=="")
                $where = " WHERE $intwhere";
            else
                $where .= "AND $intwhere";
            $query = "SELECT $group(ts) div $interval AS div$group, hour(ts) AS hour, day(ts) AS day, month(ts) AS month ".events_cols();
            $query .= " FROM events $where GROUP BY month, day, div$group;";
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
       if(isset($query)) $return['query'] = $query;
        $return["error"] = $e->getMessage();
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
        $number = mysql_real_escape_string($_GET["number"]);
        $text = mysql_real_escape_string($_GET["text"]);
        
        if( isset($number)&&isset($text) ) {
            
            $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or die("Database error"); 
            mysql_select_db($mysql_database_sms, $db); 
            $result = mysql_query("set names 'utf8';"); 

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
        if(isset($query)) $return['query'] = $query;
        $return["error"] = $e->getMessage();
    }
    break;
case "show_sms":
    try {
        if(!$auth) die(json_encode(array("auth" => "need_auth"))); 
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        if(!mysql_select_db($mysql_database_sms, $db)) {
            $return["error"] = "database select error";
            break;
        } 
        $result = mysql_query("set names 'utf8';"); 
        
        $where = "";
  
        if (isset($range)&&$range!="") {
            $limit = " LIMIT ".$range;
        }
        
        if(!(isset($_GET['box'])&&($_GET['box']=='inbox'||$_GET['box']=='outbox'))) {
            $return["error"] = "no_parametr";
            break;
        }
        if(isset($_GET['box'])&&$_GET['box']=='inbox')
            $query = "SELECT id, SenderNumber AS number, ReceivingDateTime AS date, TextDecoded AS text, UDH FROM inbox ";
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
            $udh = 0;
            $text = "";
            while ($arr= mysql_fetch_array($result, MYSQL_ASSOC)) {
                if($_GET['box']=='inbox'&&$arr['UDH']!="") {
                    if( abs(hexdec($arr['UDH']) - $udh) > 10 ) {
                        // new sequence
                        if($udh>0) {
                            // save last sequence
                            $arr1['text'] = $text;
                            $returnQuery[] = $arr1;
                        }
                        $text = $arr['text'];
                    } else {
                        // add one more message to udh
                        $text = $arr['text'].$text;
                    }
                    $arr1 = $arr;
                    $udh = hexdec($arr['UDH']);     
                } else {
                    $returnQuery[] = $arr; 
                }
            }
            $return = $returnQuery; 
        }
    }
    catch (Exception $e) {
        if(isset($query)) $return['query'] = $query;
        $return["error"] = $e->getMessage();
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
        
        if($result = mysql_query("SELECT balance, balance_time FROM balance ORDER BY id DESC LIMIT 1;")) {
            if ($arr = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $balance = $arr['balance'];
                $return = $arr;
            } else { 
                $return['balance'] = '-1';
            }
        }
    }
    catch (Exception $e) {
        if(isset($query)) $return['query'] = $query;
        $return["error"] = $e->getMessage();
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

if($debug&&isset($query)) 
    $return["query"] = $query;  

echo json_encode($return);

?>
