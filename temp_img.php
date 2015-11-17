<?php

#
# garage project 
#

// Параметры:

// определяем параметры подключения к базе
if($_SERVER["DOCUMENT_ROOT"] == "/Users/rosipov/progs/garage") {
    $mysql_host = "192.168.5.252";
    $mysql_user = "mac";
    $mysql_password = "macpass";
    
} else {
    $mysql_host = "localhost";
    $mysql_user = "www";
    $mysql_password = "passwww";
}
$mysql_database = "garage";
$server_version = '0.1';

//определяем переданные переменные
if(isset($_GET["interval"])&&$_GET["interval"]!=""&&isset($_GET["div"])&&$_GET["div"]!="") {
    $divs = (int)$_GET['div']*1;
    $interval = (int)$_GET['interval']*1;
} else {
    $interval = 3;  // в часах
    $divs = 5;      // столбцов (реально на один больше)
}

// массив точек
$points_count = 0; //
$points = [];
$fiels = Array("t","tmax","tmin","p","pmax","pmin");

// параметры графика (глобальные переменные) 
$diagramWidth = 710; 
$diagramHeight = 400;  

$where = " WHERE ts BETWEEN now() - INTERVAL ".($divs*$interval)." HOUR AND now() ";
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
        $cols = " max(temp) AS tmax, min(temp) AS tmin, max(press) AS pmax, min(press) AS pmin, ";
        $query = "SELECT count(id) AS c, $cols max(ts) AS max_ts, min(ts) as min_ts FROM events $where ;";
        //echo $query;
        if ($result = mysql_query($query)) {
            $arr = mysql_fetch_array($result);
            $items_count = $arr['c'];
            $minmax['t','max'] = (float)$arr['tmax'];
            $minmax['t','min'] = (float)$arr['tmin'];
            $minmax['p','max'] = (float)$arr['pmax'];
            $minmax['p','min'] = (float)$arr['pmin'];
            //$max_ts = $arr['max_ts'];
            //$min_ts = $arr['min_ts'];
        } else {
            $items_count = 0;
            $error = "No data from query : ".$query;
        }
        // выбираем данные
        if($items_count > 0) {
            $cols = "avg(temp) AS t, avg(press) AS p, $cols day(ts) AS d, month(ts) as m, hour(ts) AS h ";
            $query = "SELECT $cols , $group_fld FROM events $where $group ;";
            //echo  $query;
            $points_count = 0;
            if($result = mysql_query($query)) {
                while($arr = mysql_fetch_assoc($result)) {
                    foreach($fieds as $key) {
                        $points[$key][$points_count] = (float)$arr[$key];
                    }
                    $points['hour'][$points_count]  = (int)$arr['h'];
                    $points['day'][$points_count]   = (int)$arr['d'];
                    $points['month'][$points_count] = (int)$arr['m'];
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

// вычисляем коэффиценты
foreach( Array('p','t') as $key) { 
    $minmax[$key,'scale'] = ($diagramHeight - 50) / ($minmax[$key,'max'] - $minmax[$key,'min']) ;
}

// содаем изображение 
$image = imageCreate($diagramWidth, $diagramHeight); 

// Регистрируем используемые цвета 
$colorBackgr        = imageColorAllocate($image, 192, 192, 192); 
$colorForegr        = imageColorAllocate($image, 255, 255, 255); 
$colorGrid          = imageColorAllocate($image, 208, 208, 208); 
$colorCross         = imageColorAllocate($image, 0, 0, 0); 
$colorTAvg          = imageColorAllocate($image, 0, 0, 0);     // black
$colorTMin          = imageColorAllocate($image, 0, 0, 255);   //blue 
$colorTMax          = imageColorAllocate($image, 255, 0, 0);   //red
$colorPAvg          = imageColorAllocate($image, 0, 255, 0);   // зеленый
$colorPMin          = imageColorAllocate($image, 0, 255, 192); // 
$colorPMax          = imageColorAllocate($image, 192, 255, 0); // 

// выбираем шрифт
$font = 1;

// заливаем цветом фона 
imageFilledRectangle($image, 0, 0, $diagramWidth - 1, $diagramHeight - 1, $colorBackgr);  

// выводим текст
$text_color = imagecolorallocate($image, 233, 14, 91);
//imagestring($image, 1, 5, 5, "points(steps): ".$points_count." items(data): ".$items_count." max t ".$max_temp." min t ".$min_temp." scale ".$scale, $text_color);
if(isset($error))
    imagestring($image, 1, 5, 5, "ERROR : ".$error, $text_color);

$colWidht = (int)($diagramWidth/$points_count);
// делаем график
for($i=0;$i<$points_count;$i++) {
    // считеам позиции
    foreach($fieds as $key) {
        $y_pos[$key] = (int)(abs($points[$key][$i]-$minmax[substr($key,0,1),'max'])*$minmax[substr($key,0,1),'scale'])+10;
    }
    // позиция начала столбца
    $x_pos = $colWidht*$i;
    // делим весь рисунок на части
    if($i>0)
        imageline($image, $x_pos , 10 , $x_pos , $diagramHeight-30 ,$colorGrid);
    // выводим значения столбцов
    $str_out = $points['day'][$i].".".$points['month'][$i]." ".$points['hour'][$i].":00";
    imagestring($image, $font, $x_pos_begin + 25, $diagramHeight - 25, $str_out, $text_color);
    // строим линии 
    foreach($fieds as $key) {
        imageline($image, $x_pos+35, $y_pos[$key], $x_pos+$colWidht-1 , $y_pos[$key], $colorTAvg);
    }
    /*
    imageline($image, $x_pos_begin+35, $y_t_pos_min   , $x_pos_begin+$colWidht-1 , $y_t_pos_min ,$colorTMin);
    imageline($image, $x_pos_begin+35, $y_t_pos_max   , $x_pos_begin+$colWidht-1 , $y_t_pos_max ,$colorTMax);
    // и давления
    imageline($image, $x_pos_begin+35, $y_p_pos       , $x_pos_begin+$colWidht-1 , $y_p_pos     ,$colorPAvg);
    imageline($image, $x_pos_begin+35, $y_p_pos_min   , $x_pos_begin+$colWidht-1 , $y_p_pos_min ,$colorPMin);
    imageline($image, $x_pos_begin+35, $y_p_pos_max   , $x_pos_begin+$colWidht-1 , $y_p_pos_max ,$colorPMax);
   
    // надписи к линиям
    // начинаем с мин
    $y_t_pos_str_min = $y_t_pos_min-imagefontheight($font)/2; 
    imagestring($image, $font, $x_pos_begin+2, $y_t_pos_str_min , ((int)($points['tmin'][$i]*100))/100, $colorTMin);
    // вычисляем где следующая строка (среднее) 
    $y_t_pos_str = $y_t_pos-imagefontheight($font)/2;
    // если она накладываеться на мин
    if(($y_t_pos_str_min-$y_t_pos_str)<(imagefontheight($font)+1)) 
        // то добавляем (вычитаем)
        $y_t_pos_str = $y_t_pos_str_min - imagefontheight($font)+1; 
    imagestring($image, $font, $x_pos_begin+2, $y_t_pos_str , ((int)($points['t'][$i]*100))/100, $colorTAvg);
    // то же для макс
    $y_t_pos_str_max = $y_t_pos_max-imagefontheight($font)/2;
    if(($y_t_pos_str-$y_t_pos_str_max)<(imagefontheight($font)+1))
        $y_t_pos_str_max = $y_t_pos_str - imagefontheight($font)+1;
    imagestring($image, $font, $x_pos_begin+2, $y_t_pos_str_max , ((int)($points['tmax'][$i]*100))/100, $colorTMax);
    // надписи к давлению. пока у линии !!!
    $y_p_pos_str = $y_p_pos-imagefontheight($font)/2; 
    imagestring($image, $font, $x_pos_begin+2, $y_p_pos_str , (int)($points['p'][$i]), $colorPAvg);
    
    $y_p_pos_str_min = $y_p_pos_min-imagefontheight($font)/2;   
    imagestring($image, $font, $x_pos_begin+2, $y_p_pos_str_min , (int)($points['p'][$i]), $colorPMin);
    
    $y_p_pos_str_max = $y_p_pos_max-imagefontheight($font)/2; 
    imagestring($image, $font, $x_pos_begin+2, $y_p_pos_str_max , (int)($points['p'][$i]), $colorPMax);
    
    // линии к подписям
    imageline($image, $x_pos_begin+30, $y_t_pos_str     + (imagefontheight($font)/2)      , $x_pos_begin+35 , $y_t_pos     ,$colorTAvg);
    imageline($image, $x_pos_begin+30, $y_t_pos_str_min + (imagefontheight($font)/2)      , $x_pos_begin+35 , $y_t_pos_min ,$colorTMin);
    imageline($image, $x_pos_begin+30, $y_t_pos_str_max + (imagefontheight($font)/2)      , $x_pos_begin+35 , $y_t_pos_max ,$colorTMax);
    // давление
    imageline($image, $x_pos_begin+30, $y_p_pos_str     + (imagefontheight($font)/2)      , $x_pos_begin+35 , $y_p_pos     ,$colorPAvg);
    imageline($image, $x_pos_begin+30, $y_p_pos_str_min + (imagefontheight($font)/2)      , $x_pos_begin+35 , $y_p_pos_min ,$colorPMin);
    imageline($image, $x_pos_begin+30, $y_p_pos_str_max + (imagefontheight($font)/2)      , $x_pos_begin+35 , $y_p_pos_max ,$colorPMax);
     */
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