<?php 

//phpinfo();

session_start(); 

if(isset($_GET['cmd'])&&$_GET['cmd']=='destroy') {
  session_destroy();
  header("Location: http://".$_SERVER['PHP_SELF']."/");
  exit;
}

if (!isset($_SESSION['counter'])) $_SESSION['counter']=0;
echo "Вы обновили эту страницу ".$_SESSION['counter']++." раз. ";
echo "<br><a href=".$_SERVER['PHP_SELF'].">обновить"; 

echo "<br><a href=".$_SERVER['PHP_SELF']."?cmd=destroy>Удалить сессию</a>";

?>