<?php
/**
 * 加载优先级：类全名映射->命名空间文件夹查找->纯类名映射->正则匹配前缀类全名映射查找->正则匹配文件夹顺序查找
 */

/**
 * @var global rootpath and classpath
 */
global $_ROOTPATH,$_CLASSPATH;

/**
 * default rootpath and classpath
 */
$_ROOTPATH = $_CLASSPATH = str_replace("\\", "/", __DIR__);

/**
 * Layload autoload class
 * @author Lay Li
 */
class Layload {
    /**
     * @staticvar all class mappings
     */
    public static $classes = array('_prefixes'=>array());
    
    /**
     * initialize autoload function
     * @return void
     */
    public static function initialize() {
        spl_autoload_register('L::autoload');
    }
    
    /**
     * set class path
     * @param $classpath class directory path,default is empty
     * @return void
     */
    public static function classpath($classpath = '') {
        global $_CLASSPATH;
        $_CLASSPATH = str_replace("\\", "/", is_dir($classpath)?$classpath:__DIR__);
    }
    /**
     * set root path
     * @param $rootpath web root directory path,default is empty
     * @return void
     */
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
            } else {
                //TODO mapping is error
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
                    foreach($suffixes as $i=>$suffix) {
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
                    } else {
                        //TODO mapping is error
                    }
                } else {
                    //TODO not found by namespace dir or not found by class basename
                }
            } else if(preg_match_all('/([A-Z]{1,}[a-z]{0,}|[a-z]{1,})_{0,1}/', $classname, $matches)) {
                //TODO autoload class by regular
                $prefix = array_shift(array_values($matches[1]));;
                //正则匹配前缀查找
                if(array_key_exists($prefix,$prefixes)) {
                    if(strpos($prefixes[$prefix][$classname], $_CLASSPATH) === 0) {
                        echo 'require_once '.$prefixes[$prefix][$classname].'<br>';
                        require_once $prefixes[$prefix][$classname];
                    } else if(is_file($_CLASSPATH.$prefixes[$prefix][$classname])) {
                        echo 'require_once '.$_CLASSPATH.$prefixes[$prefix][$classname].'<br>';
                        require_once $_CLASSPATH.$prefixes[$prefix][$classname];
                    } else {
                        //TODO mapping is error by regular match
                    }
                } else {
                    $path = $_CLASSPATH;
                    foreach($matches[1] as $index=>$item) {
                        $path .= '/'.$item;
                        if(is_dir($path)) {//顺序文件夹查找
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
                        } else {
                            //TODO not found by regular match
                        }
                    }
                }
            }
        }
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
        $prefixes = &Layload::$classes['_prefixes'];//print_r($prefixDir);print_r($configuration);echo '<br>';
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
    }
}

/**
 * Layload autoload class
 * @author Lay Li
 */
final class L extends Layload {}//short classname
?>
