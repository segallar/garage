<?php

$PressDecFloat = 0;
$TempDecFloat = 0;

$mysql_host = "localhost";
$mysql_database = "garage";
$mysql_user = "barometer";
$mysql_password = "pass";

try {
    $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
	die("Database error"); 
    mysql_select_db($mysql_database, $db); 

#echo "input ".$argv[1]." ".$argv[2]."\n";

if(isset($argv[1])&&$argv[1] != "") {
    $PressRaw = $argv[1];
    if(strlen($PressRaw) == 6) {
        $PressBin = base_convert($PressRaw,16,2);
        if(strlen($PressBin)<24) { 
            $PressDec = (int) base_convert($PressBin,2,10);
        } else {
            if($PressBin[0] == "0") {
                $PressDec = (int) base_convert($PressBin,2,10);      
            } else {
                for($i=0;$i<strlen($PressBin);$i++) {
                    if($PressBin[$i] == "0") {
                        $PressBin[$i] = "1"; 
                    } else { 
                        $PressBin[$i] = "0";
                    }
                }
                $PressDec = (int) base_convert($PressBin,2,10);
                $PressDec = -$PressDec - 1;
            }
        }
        $PressDecFloat = $PressDec/4096;
    }
}

if(isset($argv[2])&&$argv[2] != "") {
    $TempRaw = $argv[2];
    if(strlen($TempRaw) == 4) {
        $TempBin = base_convert($TempRaw,16,2);
        if(strlen($TempBin) < 16) { 
            $TempDec = (int) base_convert($TempBin,2,10);
        } else {
            if($TempBin[0] == "0") {
                $TempDec = (int) base_convert($TempBin,2,10);
            } else {
                for($i = 0; $i < strlen($TempBin); $i++) {
                    if($TempBin[$i] == "0") {
                        $TempBin[$i] = "1"; 
                    } else { 
                        $TempBin[$i] = "0";
                    }
                }
                $TempDec = (int) base_convert($TempBin,2,10);
                $TempDec = -$TempDec - 1;
            }
        }
        $TempDecFloat = 42.5 + $TempDec / 480;
    }
}
mysql_query("insert into events (press,temp) values  ($PressDecFloat, $TempDecFloat);");
mysql_close($db);
}
catch (Exception $e) {
        $e->getMessage();
}

?>
