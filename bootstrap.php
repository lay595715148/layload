<?php
global $_ROOTPATH,$_CLASSPATH;

$_ROOTPATH = $_CLASSPATH = __DIR__;

class Layload {
    public static $classes = array();
    public static $prefixes = array();
    
    public function __construct() {
        $this->register($this->autoload);
    }
    
    //类自动加载函数
    public static function autoload($classname) {
        global $_CLASSPATH;
        $classes = &Layload::$classes;
        $prefixes = &Layload::$prefixes;
        $suffixes = array('.php','.class.php','.inc');
        if(array_key_exists($classname, $classes)) {
            if(strpos($classes[$classname], $_CLASSPATH) === 0) {
                require_once $classes[$classname];
            } else if(file_exists($_CLASSPATH.$classes[$classname])) {
                require_once $_CLASSPATH.$classes[$classname];
            }
        } else {
            $tmparr = explode("\\",$classname);
            if(count($tmparr) > 1) {//if is namespace
                $name = end($tmparr);
                if(array_key_exists($name,$classes)) {
                    if(file_exists($_CLASSPATH.$classes[$name])) {
                        require_once $_CLASSPATH.$classes[$name];
                    }
                }
            } else if(preg_match_all('/([A-Z]{1,}[a-z]{0,}|[a-z]{1,})_{0,1}/', $classname, $matches)) {
                //$matches[0];$matches[1];
                $prefix = array_shift(array_values($matches[1]));;
                if(array_key_exists($prefix,$prefixes)) {
                    if(file_exists($_CLASSPATH.$prefixes[$prefix][$classname])) {
                        require_once $_CLASSPATH.$prefixes[$prefix][$classname];
                    }
                } else {
                    $path = $_CLASSPATH;
                    foreach($matches[1] as $index=>$item) {
                        $path .= '/'.$item;
                        if(is_dir($path)) {
                            $tmppath = $path.substr($classname, strpos($classname, $item) + strlen($item));
                            foreach($suffixes as $i=>$suffix) {
                                if(is_file($tmppath.$suffix)) {
                                    require_once $tmppath.$suffix;
                                    break 2;
                                }
                            }
                            continue;
                        } else if($index == count($matches[1]) - 1) {
                            foreach($suffixes as $i=>$suffix) {
                                if(is_file($path.$suffix)) {
                                    require_once $path.$suffix;
                                    break 2;
                                }
                            }
                            break;
                        }
                    }
                    echo 'path:'.$path;
                }
                echo '<pre>';print_r($matches);echo '</pre>';
            }
            //TODO autoload class by regular
        }
        //$pattern = '/[^a-z0-9]/';
    }
    /**
     * configure class mapping
     * @param $configuration a class mapping file or class mapping file array or class mapping array
     * @param $isFile sign file,default is true
     * @param $prefixDir class prefix to dir,default is empty
     * @return void
     */
    public static function configure($configuration, $isFile = true, $prefixDir = '') {
        global $_CLASSPATH;
        $classes = &Layload::$classes;
        $prefixes = &Layload::$prefixes;
        if(is_array($configuration) && !$isFile) {
            foreach($configuration as $cls=>$path) {
                if(is_array($prefixDir) && !empty($prefixDir)) {
                    if(array_key_exists($cls, $prefixes[$prefixDir['prefix']])) {
                        //TODO class mapping exists, give a warning
                    } else if(is_numeric($cls)) {
                        //TODO numeric class mapping, give a warning
                    } else {
                        $prefixes[$prefixDir['prefix']][$cls] = $prefixDir['dir'].$path;
                    }
                } else if(array_key_exists($cls, $classes)) {
                    //TODO class mapping exists, give a warning
                } else if(is_numeric($cls)) {
                    //TODO numeric class mapping, give a warning
                } else {
                    $classes[$cls] = $path;
                }
            }
        } else if(is_array($configuration)) {
            foreach($configuration as $index=>$configfile) {
                Layload::configure($configfile);
            }
        } else if(file_exists($_CLASSPATH.$configuration)) {
            $tmparr = include $_CLASSPATH.$configuration;
            if(array_key_exists('classes',$tmparr)) {
                Layload::configure($tmparr['classes'], false, isset($tmparr['prefix-dir'])?$tmparr['prefix-dir']:'');
            } else {
                //TODO no class mapping
            }
        } else {
            //TODO no config file
        }
    }
    public static function register($autoload) {
        //spl_autoload_register($autoload);
    }
}

final class L extends Layload {}//short classname

spl_autoload_register('L::autoload');
?>
