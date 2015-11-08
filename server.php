<?php

#
# garage project 
#

$mysql_host = "localhost";
$mysql_database = "garage";
$mysql_user = "root";
$mysql_password = "nigthfal";

$server_version = '0.1';

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

        $query = "select count(id) from events;"; 
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
case "list_dev":
    try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 

        $return = [];

        $query = "select DISTINCT UUID from track;";
        $result = mysql_query($query);
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $query = "select distinct session from track where uuid='".$row['UUID']."';";
               	$result1 = mysql_query($query);
                $i=0;
                while ( $row1 = mysql_fetch_array($result1, MYSQL_ASSOC)) {
                    $query1 = "select session, max(date) as date_max , min(date) as date_min";
                    $query1 .= " from track where session='".$row1['session']."';";
                    $result2 = mysql_query($query1);
                    $row2 = mysql_fetch_array($result2, MYSQL_ASSOC);
                    $return[$row['UUID']][$i++] = $row2;
                }
            }
        }
    }
    catch (Exception $e) {
        $e->getMessage();
    }
    break;
case get_track:
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
