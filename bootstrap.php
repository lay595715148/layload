<?php
global $_ROOTPATH,$_CLASSPATH;

class Layload {
    public static $classes = array('_prefixes'=>array());
    
    public static function initialize() {
    }
    
    public static function classpath($classpath = '') {
        global $_CLASSPATH;
        $_CLASSPATH = str_replace("\\", "/", is_dir($classpath)?$classpath:__DIR__);
    }
    public static function rootpath($rootpath = '') {
        global $_ROOTPATH;
        $_ROOTPATH = str_replace("\\", "/", is_dir($rootpath)?$rootpath:__DIR__);
    }
    
    /**
     * class autoload function
     * @param $classname autoload class name
     * @return void
     */
    public static function autoload($classname) {
        global $_CLASSPATH;
        $classes = &Layload::$classes;
        $prefixes = &Layload::$classes['_prefixes'];
        $suffixes = array('.php','.class.php','.inc');
        if(array_key_exists($classname, $classes)) {//全名映射
            if(strpos($classes[$classname], $_CLASSPATH) === 0) {
                echo 'require_once '.$classes[$classname].'<br>';
                require_once $classes[$classname];
            } else if(is_file($_CLASSPATH.$classes[$classname])) {
                echo 'require_once '.$_CLASSPATH.$classes[$classname].'<br>';
                require_once $_CLASSPATH.$classes[$classname];
            }
        } else {
            $tmparr = explode("\\",$classname);
            if(count($tmparr) > 1) {//if is namespace
                $name = array_pop($tmparr);
                $path = $_CLASSPATH.'/'.implode('/', $tmparr);
                $required = false;
                //命名空间文件夹查找
                if(is_dir($path)) {
                    $tmppath = $path.'/'.$name;
                    foreach($suffixes as $i=>$suffix) {//print_r(array($name,$tmppath.$suffix));
                        if(is_file($tmppath.$suffix)) {
                            echo 'require_once '.$tmppath.$suffix.'<br>';
                            require_once $tmppath.$suffix;
                            $required = true;
                            break;
                        }
                    }
                }
                //纯类名映射
                if(!$unrequired && array_key_exists($name,$classes)) {
                    if(strpos($classes[$name], $_CLASSPATH) === 0) {
                        echo 'require_once '.$classes[$name].'<br>';
                        require_once $classes[$name];
                    } else if(is_file($_CLASSPATH.$classes[$name])) {
                        echo 'require_once '.$_CLASSPATH.$classes[$name].'<br>';
                        require_once $_CLASSPATH.$classes[$name];
                    }
                }
                /*if(array_key_exists($name,$classes)) {
                    if(strpos($classes[$name], $_CLASSPATH) === 0) {
                        echo 'require_once '.$classes[$name].'<br>';
                        require_once $classes[$name];
                    } else if(is_file($_CLASSPATH.$classes[$name])) {
                        echo 'require_once '.$_CLASSPATH.$classes[$name].'<br>';
                        require_once $_CLASSPATH.$classes[$name];
                    }
                } else {
                    $path = $_CLASSPATH.'/'.str_replace("\\", "/", $classname);
                    foreach($suffixes as $i=>$suffix) {
                        if(is_file($path.$suffix)) {
                            echo 'require_once '.$path.$suffix.'<br>';
                            require_once $path.$suffix;
                            break;
                        }
                    }
                }*/
            } else if(preg_match_all('/([A-Z]{1,}[a-z]{0,}|[a-z]{1,})_{0,1}/', $classname, $matches)) {
                //$matches[0];$matches[1];
                $prefix = array_shift(array_values($matches[1]));;
                if(array_key_exists($prefix,$prefixes)) {
                    if(strpos($prefixes[$prefix][$classname], $_CLASSPATH) === 0) {
                        echo 'require_once '.$prefixes[$prefix][$classname].'<br>';
                        require_once $prefixes[$prefix][$classname];
                    } else if(is_file($_CLASSPATH.$prefixes[$prefix][$classname])) {
                        echo 'require_once '.$_CLASSPATH.$prefixes[$prefix][$classname].'<br>';
                        require_once $_CLASSPATH.$prefixes[$prefix][$classname];
                    }
                } else {
                    $path = $_CLASSPATH;
                    foreach($matches[1] as $index=>$item) {
                        $path .= '/'.$item;
                        if(is_dir($path)) {
                            $tmppath = $path.'/'.substr($classname, strpos($classname, $item) + strlen($item));
                            echo $tmppath.'<br>';
                            foreach($suffixes as $i=>$suffix) {
                                if(is_file($tmppath.$suffix)) {
                                    echo 'require_once '.$tmppath.$suffix.'<br>';
                                    require_once $tmppath.$suffix;
                                    break 2;
                                }
                            }
                            continue;
                        } else if($index == count($matches[1]) - 1) {
                            foreach($suffixes as $i=>$suffix) {
                                if(is_file($path.$suffix)) {
                                    echo 'require_once '.$path.$suffix.'<br>';
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
     * @param $prefixDir class prefix to dir,default is empty,example: array('prefix'=>'Example','dir'=>'/example')
     * @return void
     */
    public static function configure($configuration, $isFile = true, $prefixDir = array()) {
        global $_CLASSPATH;
        global $_ROOTPATH;
        $classes = &Layload::$classes;
        $prefixes = &Layload::$classes['_prefixes'];print_r($prefixDir);print_r($configuration);echo '<br>';
        if(is_array($configuration) && !$isFile) {
            foreach($configuration as $cls=>$path) {
                if(is_array($prefixDir) && !empty($prefixDir) && $prefixDir['dir'] && $prefixDir['prefix']) {
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
        } else {
            if(strpos($configuration, $_ROOTPATH) === 0) {
                $tmparr = include_once $configuration;
            } else if(file_exists($_ROOTPATH.$configuration)) {
                $tmparr = include_once $_ROOTPATH.$configuration;
            } else {
                $tmparr = array();
            }
            
            if(array_key_exists('classes',$tmparr)) {
                Layload::configure($tmparr['classes'], false, isset($tmparr['prefix-dir'])?$tmparr['prefix-dir']:'');
            } else {
                //TODO no class mapping
            }
            if(array_key_exists('files',$tmparr)) {
                Layload::configure($tmparr['files']);
            } else {
                //TODO no class config file array
            }
            
        }
        /*} else if(file_exists($_ROOTPATH.$configuration)) {
            $tmparr = include_once $_ROOTPATH.$configuration;
            if(array_key_exists('classes',$tmparr)) {
                Layload::configure($tmparr['classes'], false, isset($tmparr['prefix-dir'])?$tmparr['prefix-dir']:'');
            } else if(array_key_exists('files',$tmparr)) {
                Layload::configure($tmparr['files']);
            } else {
                //TODO no class mapping and no class config file array
            }
        } else {
            //TODO no config file
        }*/
    }
}

final class L extends Layload {}//short classname


spl_autoload_register('L::autoload');
L::rootpath();
L::classpath();
?>
