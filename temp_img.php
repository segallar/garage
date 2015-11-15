<?php

// Параметры:
// &begin_ts
// &end_ts
// Пример: temp_img.php?begin_ts=2015-10-20%2023:40:02&end_ts=2015-10-20%2024:40:02

// определяем параметры подключения к базе
$mysql_host = "192.168.5.252";
$mysql_database = "garage";
$mysql_user = "mac";
$mysql_password = "macpass";

$server_version = '0.1';

//определяем переменные
/*
$begin_ts = $_GET["begin_ts"];    
if (!isset($begin_ts)) {
    $begin_ts = " now() - INTERVAL 10 MINUTE ";
}
$end_ts = $_GET["end_ts"];
if (!isset($end_ts)) {
    $end_ts = " now() ";
}
*/

$interval = 3; // in hours
$points_count = 6; //
$points = [];
$points_min = [];
$points_max = [];
$points_d = [];


$where = " WHERE ts BETWEEN now() - INTERVAL ".$points_count*$interval." HOUR AND now() ";
$group = " GROUP BY d, grp ";
$group_fld = " HOUR(ts) DIV $interval AS grp ";

//подключаемся к базе    
try {
        $db = mysql_connect($mysql_host, $mysql_user, $mysql_password) or 
            die("Database error"); 
        mysql_select_db($mysql_database, $db); 
        $result = mysql_query("set names 'utf8';"); 
        
        // определяем есть ли параметры
        /*
        $where = "";
        if($begin_ts!=""&&$end_ts!="") 
            $where .= " WHERE ts BETWEEN $begin_ts AND $end_ts"; */
        
        // получаем количество значений максимальные и минимальные значения и максимум и минимум времени
        $query = "SELECT count(id) AS c, max(temp) AS max, min(temp) AS min, max(ts) AS max_ts, min(ts) as min_ts FROM events $where ;";
        //echo $query;
        if ($result = mysql_query($query)) {
            $arr = mysql_fetch_array($result);
            $items_count = $arr['c'];
            $max_temp = (float)$arr['max'];
            $min_temp = (float)$arr['min'];
            $max_ts = $arr['max_ts'];
            $min_ts = $arr['min_ts'];
        } else {
            $items_count = 0;
            $error = "No data from query : ".$query;
        }
        // выбираем данные
        if($items_count > 0) {
            $cols = "avg(temp) AS t, min(temp) AS mi, max(temp) AS mx, day(ts) AS d, avg(hour(ts)) AS h";
            $query = "SELECT $cols , $group_fld FROM events $where $group ;";
            //echo  $query;
            $points_count = 0;
            if($result = mysql_query($query)) {
                while($arr = mysql_fetch_assoc($result)) {
                    $points['val'][$points_count] = (float)$arr['t'];
                    $points['min'][$points_count] = (float)$arr['mi'];
                    $points['max'][$points_count] = (float)$arr['mx'];
                    $points['day'][$points_count] = $arr['d'];
                    $points['hour'][$points_count] = (int)$arr['h'];
                    $points_count++;
                }
            }
        }
        // Закрываем подключение к базе
        mysql_close($db);
}
catch (Exception $e) {
        $e->getMessage();
}

// параметры графика (глобальные переменные) 
$diagramWidth = 710; 
$diagramHeight = 400;  

//
$scale = ($diagramHeight - 50) / ($max_temp - $min_temp) ;

// содаем изображение 
$image = imageCreate($diagramWidth, $diagramHeight); 

// Регистрируем используемые цвета 
$colorBackgr       = imageColorAllocate($image, 192, 192, 192); 
$colorForegr       = imageColorAllocate($image, 255, 255, 255); 
$colorGrid         = imageColorAllocate($image, 0, 0, 0); 
$colorCross        = imageColorAllocate($image, 0, 0, 0); 
$colorAvg          = imageColorAllocate($image, 0, 0, 0); // black
$colorMin          = imageColorAllocate($image, 0, 0, 255); //blue 
$colorMax          = imageColorAllocate($image, 255, 0, 0); //red

// заливаем цветом фона 
imageFilledRectangle($image, 0, 0, $diagramWidth - 1, $diagramHeight - 1, $colorBackgr);  

// выводим текст
$text_color = imagecolorallocate($image, 233, 14, 91);
imagestring($image, 1, 5, 5, "points(steps): ".$points_count." items(data): ".$items_count." max t ".$max_temp." min t ".$min_temp." scale ".$scale, $text_color);
if(isset($error))
    imagestring($image, 1, 5, 15, "ERROR : ".$error, $text_color);

// делаем график
for($i=0;$i<$points_count;$i++) {
    $y_pos = (int)(abs($points['val'][$i]-$max_temp)*$scale)+10;
    $y_pos_min = (int)(abs($points['min'][$i]-$max_temp)*$scale)+10;
    $y_pos_max = (int)(abs($points['max'][$i]-$max_temp)*$scale)+10;
    $x_pos_begin = $diagramWidth/$points_count*$i;
    imagestring($image, 1, 5, 25+10*$i , "*** ".$i." ".$y_pos, $text_color);
    // делим весь рисунок на части
    if($i>0)
        imageline($image, $x_pos_begin , 10 , $x_pos_begin , $diagramHeight-30 ,$colorCross);
    // выводим значения столбцов
    imagestring($image, 1, $x_pos_begin+5, $diagramHeight-40 , $i." ".$points['day'][$i].".11 ".$points['hour'][$i].":00 ", $text_color);
    // строим линии
    imageline($image, $x_pos_begin, $y_pos , $diagramWidth/$points_count*($i+1)-1 , $y_pos ,$colorAvg);
    imagestring($image, 1, $x_pos_begin+5, $y_pos-imagefontheight(1)-1 , ((int)($points['val'][$i]*100))/100, $colorAvg);

    imageline($image, $x_pos_begin, $y_pos_min , $diagramWidth/$points_count*($i+1)-1 , $y_pos_min ,$colorMin);
    imagestring($image, 1, $x_pos_begin+5, $y_pos_min-imagefontheight(1)-1 , ((int)($points['min'][$i]*100))/100, $colorMin);
    
    imageline($image, $x_pos_begin, $y_pos_max , $diagramWidth/$points_count*($i+1)-1 , $y_pos_max ,$colorMax);
    imagestring($image, 1, $x_pos_begin+5, $y_pos_max-imagefontheight(1)-1 , ((int)($points['max'][$i]*100))/100, $colorMax);

}

/*
// рисуем линии креста по середине
imageline($image, 10, $diagramHeight/2 , $diagramWidth - 10, $diagramHeight/2 ,$colorCross);
imageline($image, $diagramWidth/2, 10 , $diagramWidth/2, $diagramHeight-10 ,$colorCross);
*/

// Отправляем заголовок Content-type 
header("Content-type:  image/png"); 

// задаем чересстрочный режим 
imageInterlace($image, 1); 

// делаем цвет фона прозрачным 
imageColorTransparent($image, $colorBackgr); 

// и выводим изображение 
imagePNG($image); 

?>