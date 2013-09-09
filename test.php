<?php
namespace core;
class Test {
    public $var = array('var'=>'I am a var!');
    public function fun() {
        echo '<pre>';print_r($this->var);echo '</pre>';
    }
}
?>