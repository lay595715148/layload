<?php
/**
 * @var global loadpath and classpath
 */
global $_LOADPATH,$_CLASSPATH;

/**
 * default loadpath and classpath
 */
$_LOADPATH = $_CLASSPATH = str_replace("\\", "/", __DIR__);

/**
 * Layload autoload class
 * 加载优先级：类全名映射->命名空间文件夹查找->纯类名映射->正则匹配前缀类全名映射查找->正则匹配文件夹顺序查找
 * @author Lay Li
 */
final class Layload {
    /**
     * @staticvar debug
     */
    public static $debug = false;
    /**
     * @staticvar all class mappings
     */
    public static $classes = array('_prefixes'=>array());
    
    /**
     * initialize autoload function
     * @return void
     */
    public static function initialize($debug = false) {
        spl_autoload_register('Layload::autoload');
        Layload::$debug = $debug === true;
    }
    
    /**
     * set class path
     * @param $classpath class directory path,default is empty
     * @return void
     */
    public static function classpath($classpath = '') {
        global $_CLASSPATH;
        if(is_dir($classpath)) {
            $_CLASSPATH = str_replace("\\", "/", $classpath);
        } else {
            //TODO warning given path isnot a real path
        }
    }
    /**
     * set load path
     * @param $loadpath class mapping config load directory path,default is empty
     * @return void
     */
    public static function loadpath($loadpath = '') {
        global $_LOADPATH;
        if(is_dir($loadpath)) {
            $_LOADPATH = str_replace("\\", "/", $loadpath);
        } else {
            //TODO warning given path isnot a real path
        }
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
        //全名映射查找
        if(array_key_exists($classname, $classes)) {
            if(is_file($classes[$classname])) {
                if(Layload::$debug) {
                    Debugger::info($classes[$classname], 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                }
                require_once $classes[$classname];
            } else if(is_file($_CLASSPATH.$classes[$classname])) {
                if(Layload::$debug) {
                    Debugger::info($_CLASSPATH.$classes[$classname], 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                }
                require_once $_CLASSPATH.$classes[$classname];
            } else {
                //TODO mapping is error
            }
        }
        if(!class_exists($classname) && !interface_exists($classname)) {
            $tmparr = explode("\\",$classname);
            //if is namespace
            //通过命名空间查找
            if(count($tmparr) > 1) {
                $name = array_pop($tmparr);
                $path = $_CLASSPATH.'/'.implode('/', $tmparr);
                $required = false;
                //命名空间文件夹查找
                if(is_dir($path)) {
                    $tmppath = $path.'/'.$name;
                    foreach($suffixes as $i=>$suffix) {
                        if(is_file($tmppath.$suffix)) {
                            if(Layload::$debug) {
                                Debugger::info($tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                            }
                            require_once $tmppath.$suffix;
                            $required = true;
                            break;
                        }
                    }
                } else {
                    //TODO not found by namespace dir or not found by class basename
                }
            }
            if(!class_exists($classname) && !interface_exists($classname) && preg_match_all('/([A-Z]{1,}[a-z0-9]{0,}|[a-z0-9]{1,})_{0,1}/', $classname, $matches) > 0) {
                //TODO autoload class by regular
                $tmparr = array_values($matches[1]);
                $prefix = array_shift($tmparr);
                //正则匹配前缀查找
                if(array_key_exists($prefix, $prefixes)) {//prefix is not good
                    if(is_file($prefixes[$prefix][$classname])) {
                        if(Layload::$debug) {
                            Debugger::info($prefixes[$prefix][$classname], 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                        }
                        require_once $prefixes[$prefix][$classname];
                    } else if(is_file($_CLASSPATH.$prefixes[$prefix][$classname])) {
                        if(Layload::$debug) {
                            Debugger::info($_CLASSPATH.$prefixes[$prefix][$classname], 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                        }
                        require_once $_CLASSPATH.$prefixes[$prefix][$classname];
                    } else {
                        foreach($suffixes as $i=>$suffix) {
                            $tmppath = $prefixes[$prefix]['_dir'].'/'.$classname;
                            if(is_file($tmppath.$suffix)) {
                                if(Layload::$debug) {
                                    Debugger::info($tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                                }
                                require_once $tmppath.$suffix;
                                break;
                            } else if($_CLASSPATH.$tmppath.$suffix) {
                                if(Layload::$debug) {
                                    Debugger::info($_CLASSPATH.$tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                                }
                                require_once $_CLASSPATH.$tmppath.$suffix;
                                break;
                            } else {
                                //TODO not found by  prefix-dir directly
                            }
                        }
                        if(!class_exists($classname) && !interface_exists($classname)) {
                            //TODO mapping is error by regular match
                        }
                    }
                }
                //如果正则匹配前缀没有找到
                if(!class_exists($classname) && !interface_exists($classname)) {
                    //直接以类名作为文件名查找
                    foreach($suffixes as $i=>$suffix) {
                        $tmppath = $_CLASSPATH.'/'.$classname;
                        if(is_file($tmppath.$suffix)) {
                            if(Layload::$debug) {
                                Debugger::info($tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                            }
                            require_once $tmppath.$suffix;
                            break;
                        } else {
                            //TODO not found by classname directly
                        }
                    }
                }
                //如果以上没有匹配，则递归文件夹查找
                if(!class_exists($classname) && !interface_exists($classname)) {
                    $path = $_CLASSPATH;
                    foreach($matches[1] as $index=>$item) {
                        $path .= '/'.$item;
                        if(is_dir($path)) {//顺序文件夹查找
                            $tmppath = $path.'/'.substr($classname, strpos($classname, $item) + strlen($item));
                            foreach($suffixes as $i=>$suffix) {
                                if(is_file($tmppath.$suffix)) {
                                    if(Layload::$debug) {
                                        Debugger::info($tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                                    }
                                    require_once $tmppath.$suffix;
                                    break 2;
                                }
                            }
                            continue;
                        } else if($index == count($matches[1]) - 1) {
                            foreach($suffixes as $i=>$suffix) {
                                if(is_file($path.$suffix)) {
                                    if(Layload::$debug) {
                                        Debugger::info($path.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                                    }
                                    require_once $path.$suffix;
                                    break 2;
                                }
                            }
                            break;
                        } else {
                            //TODO not found by regular recursive
                        }
                    }
                }
                if(!class_exists($classname) && !interface_exists($classname)) {
                    //TODO warning no class mapping by layload class autoload function
                    Debugger::warn($classname.':no class mapping by layload class autoload function', 'CLASS_AUTOLOAD', __LINE__, __METHOD__, __CLASS__);
                }
            } else {
                //TODO not found
            }
        }
    }
    /**
     * configure class mapping,all config file is load in $_LOADPATH
     * @param $configuration a class mapping file or class mapping file array or class mapping array
     * @param $isFile sign file,default is true
     * @param $prefixDir class prefix to dir,default is empty,example: array('prefix'=>'Example','dir'=>'/example')
     * @return void
     */
    public static function configure($configuration, $isFile = true, $prefixDir = array()) {
        global $_CLASSPATH;
        global $_LOADPATH;
        $classes = &Layload::$classes;
        $prefixes = &Layload::$classes['_prefixes'];
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
            if(!empty($configuration)) {
                foreach($configuration as $index=>$configfile) {
                    Layload::configure($configfile);
                }
            }
        } else {
            if(is_file($configuration)) {
                $tmparr = include_once $configuration;
            } else if(is_file($_LOADPATH.$configuration)) {
                $tmparr = include_once $_LOADPATH.$configuration;
            } else {
                $tmparr = array();
            }
            
            if(array_key_exists('classes', $tmparr)) {
                Layload::configure($tmparr['classes'], false);
            } else {
                //TODO no class mapping
            }
            //正则匹配前缀
            if(array_key_exists('prefix-dir', $tmparr)) {
                if($tmparr['prefix-dir']['prefix'] && $tmparr['prefix-dir']['dir']) {
                    $prefix = $tmparr['prefix-dir']['prefix'];
                    $prefixes[$prefix]['_dir'] = $tmparr['prefix-dir']['dir'];
                }
                if(array_key_exists('classes', $tmparr['prefix-dir'])) {
                    Layload::configure($tmparr['prefix-dir']['classes'], false, $tmparr['prefix-dir']);
                }
            } else {
                //TODO no prefix-dir or class mapping
            }
            if(array_key_exists('files',$tmparr)) {
                Layload::configure($tmparr['files']);
            } else {
                //TODO no class config file array
            }
            
        }
    }
}

/**
 * Layload autoload class
 * @author Lay Li
 */
class_alias('Layload', 'L');//Layload class alias
?>
