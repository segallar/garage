<?php

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

        $query = "select count(id) from barometer;"; 
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
case "store":
    try {
        
        $uuid = $_GET["UUID"];
        $session = $_GET["session"];
        $date = $_GET["date"];
        $speed = $_GET["speed"];
        $lon = $_GET["lon"];
        $lat = $_GET["lat"];
        $heading = $_GET["heading"];
        $acc = $_GET["acc"];

        // http://accmon.segalla.ru/test.php?cmd=store&UUID=550e8400-e29b-41d4-a716-446655440000&date=0:00:00&speed=0.00&lon=0.0&lat=0.0&heading=0.0
        
        // http://accmon.segalla.ru/server.php?cmd=store&UUID=054FCF2D-EA0F-4424-B1C9-44F1B31922AE&date=1416136615&speed=64.06&lat=37.71585977&lon=-122.44931077&heading=37.62
        
        if( isset($uuid)&&isset($session)&&isset($date)&&
           isset($speed)&&isset($lon)&&isset($lat)&&isset($heading)&&isset($acc) ) {
            
            $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or die("Database error"); 
            mysql_select_db($mysql_database, $db); 

            //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
            //$result = mysql_query("set names 'utf8'"); 

            $adate = new DateTime("@$date");
            $bdate = $adate->format('Y-m-d H:i:s');
                        
            $query = "insert into track (UUID,session,date,speed,acc,lon,lat,heading) values ". 
                " ('$uuid','$session','$bdate',$speed,$acc,$lon,$lat,$heading);"; 
            $result = mysql_query($query); 
            if (!$result) {
                die(json_encode('Invalid query: ' . mysql_error()));
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
case "list_track":
    try {
        
        $uuid = $_GET["UUID"];
        $session = $_GET["session"];
        $range = $_GET["range"];
        $test = $_GET["test"];
        
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die(json_encode("Database error")); 
        mysql_select_db($mysql_database, $db); 

        //SOLUTION::  add this comment before your 1st query -- force multiLanuage support 
        //$result = mysql_query("set names 'utf8'"); 
        
        $where = "";
        if (isset($uuid)&&$uuid!="")
            $where .= " UUID='".$uuid."'";
        
        if (isset($session)&&$session!="") {
            if ($where != "") {
                $where .= " AND ";
            }
           $where .= " session='".$session."'";
        }
        
        if (isset($range)&&$range!="") {
            $limit = " LIMIT ".$range;
        }
        
        $query = "SELECT * FROM track ";
        if ($where != "") {
            $query .= " WHERE ".$where." ";
        }
    
        if (isset($limit)) {
            $query .= $limit;
        }
        
        $query .= ";";
        
        $returnQuery = [];
        
        $result = mysql_query($query); 
        if (!$result) {
            die(json_encode('Invalid query: ' . mysql_error()));
        } else {
            while ($arr= mysql_fetch_array($result, MYSQL_ASSOC)) {
                $returnQuery[] = $arr;    
            }
            if ($test!="ok") {
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
