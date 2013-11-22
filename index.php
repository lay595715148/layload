<?php
$st = date('Y-m-d H:i:s').'.'.floor(microtime()*1000);
include_once __DIR__.'/layload.php';
include_once __DIR__.'/lib/index.php';

Debugger::initialize(array(true, false));
Layload::classpath(__DIR__.'/classes');
Layload::classpath(__DIR__.'/classes/example');
Layload::configure(array('/config_0.php','/config_1.php','/config_2.php'));
Layload::initialize();

Debugger::debug(Layload::$classes);
$t = new core\Test();
Debugger::debug($t);
$t->fun();

$e1 = new DemoMyT();
Debugger::debug($e1);
$e2 = new DemoT();
Debugger::debug($e2);
$e3 = new ServiceMysql();
Debugger::debug($e3);
$et = date('Y-m-d H:i:s').'.'.floor(microtime()*1000);
Debugger::debug(array($st,$et));
?>