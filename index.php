<?php
include_once './bootstrap.php';
//$layload = new Layload();
L::configure(array('/config_0.php','/config_1.php','/config_2.php'),true);
L::classpath(__DIR__.'/classes');

echo '<pre>';print_r(L::$classes);echo '</pre>';
$t = new core\Test();
echo '<pre>';print_r($t);echo '</pre>';
$t->fun();

$e = new DemoMyT();
echo '<pre>';print_r($e);echo '</pre>';
$e2 = new DemoT();
echo '<pre>';print_r($e2);echo '</pre>';
$e3 = new ServiceMysql();
echo '<pre>';print_r($e3);echo '</pre>';
?>