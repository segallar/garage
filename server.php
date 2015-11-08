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

$cmd = $_GET["cmd"];    
if (!isset($cmd)) {
    $cmd = "no_command";
}

$return = array( 'test' => 'ok' , 'cmd' => $cmd ,'time' => date("d.m.Y H:i:s"), "version" => $server_version );

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
case "events_years":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $return = [];

        $query = "SELECT YEAR(ts) AS years, AVG(temp) AS temp, AVG(press) AS press FROM events GROUP BY years;";
        $result = mysql_query($query);
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            $arr2 = [];
            while($arr = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $arr2[] = $arr;
            }     
            $return = $arr2;
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;
case "events_months":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $return = [];

        $WHERE = "";
        
        $year = $_GET["year"];
        if(isset($year)&&year!="") {
            $WHERE = " WHERE YEAR(ts)=$year "; 
        }
        
        $query = "SELECT  MONTH(ts) AS months, AVG(temp) AS temp, AVG(press) AS press FROM events $WHERE GROUP BY months;";
        $result = mysql_query($query);
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            $arr2 = [];
            while($arr = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $arr2[] = $arr;
            }         
            $return = $arr2;
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;       
case "events_days":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $return = [];

        $WHERE = "";
        
        $year = $_GET["year"];
        if(isset($year)&&year!="") {
            $WHERE = " YEAR(ts)=$year "; 
        }
        
        $month = $_GET["month"];
        if(isset($month)&&month!="") {
            if($WHERE!="")
                $WHERE .= " AND ";
            $WHERE = " MONTH(ts)=$month "; 
        }
        
        if($WHERE!="")
            $WHERE = " WHERE $WHERE";
        
        $query = "SELECT DAY(ts) AS days, AVG(temp) AS temp, AVG(press) AS press FROM events $WHERE GROUP BY days;";
        $result = mysql_query($query);
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            $arr2 = [];
            while($arr = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $arr2[] = $arr;
            }         
            $return = $arr2;
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;       
case "get_track":
    /* GeoJSON example 
    { "type": "MultiLineString",
    "coordinates": [
        [ [100.0, 0.0], [101.0, 1.0] ],
        [ [102.0, 2.0], [103.0, 3.0] ]
      ]
    } */
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $return = [];
        $return['type'] = "MultiLineString";
        $return['coordinates'] = [];
        $first = true;
        $prevLon = 0;
        $prevLat = 0;

        $session = $_GET["session"];
        if(isset($session)&&$session!="") {
            $query = "select lat, lon from track where session='".$session."' order by id;";
            $result = mysql_query($query);
            if (!$result) {
                die(json_encode('Invalid query: ' . mysql_error()));
            } else {
                while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                    $lon = floatval($row['lon']);
                    $lat = floatval($row['lat']);
                    if($first) {
                        $first = false;
                    } else {
                        $return['coordinates'][] = [[$prevLon,$prevLat],[$lon,$lat]];
                    }
                    $prevLon = $lon;
                    $prevLat = $lat;
                }
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

echo json_encode($return);

?>
