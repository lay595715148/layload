<?php
$st = date('Y-m-d H:i:s').'.'.floor(microtime()*1000);
include_once './layload.php';

L::classpath(__DIR__.'/classes');
L::configure(array('/config_0.php','/config_1.php','/config_2.php'));
L::initialize();

echo '<pre>';print_r(L::$classes);echo '</pre>';
$t = new core\Test();
echo '<pre>';print_r($t);echo '</pre>';
$t->fun();

$e1 = new DemoMyT();
echo '<pre>';print_r($e1);echo '</pre>';
$e2 = new DemoT();
echo '<pre>';print_r($e2);echo '</pre>';
$e3 = new ServiceMysql();
echo '<pre>';print_r($e3);echo '</pre>';
$et = date('Y-m-d H:i:s').'.'.floor(microtime()*1000);
echo '<pre>';print_r(array($st,$et));echo '</pre>';
?>