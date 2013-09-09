<?php
include_once './bootstrap.php';
//$layload = new Layload();
L::configure(array('/config_0.php','/config_1.php','/config_2.php'),true);
echo '<pre>';print_r(L::$classes);echo '</pre>';
echo '<pre>';print_r(L::$prefixes);echo '</pre>';
$t = new core\Test();
echo '<pre>';print_r($t);echo '</pre>';
$t->fun();

$e = new exampleMyT();
echo '<pre>';print_r($e);echo '</pre>';
?>