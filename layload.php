<?php
// 判断Layload是否已经定义
if(defined('INIT_LAYLOAD')) {
    return;
}
// 定义标记
define('INIT_LAYLOAD', true);

/**
 * @var global loadpath and classpath
 */
global $_LOADPATH, $_CLASSPATH;


/**
 * Layload autoload class
 * 加载优先级：类全名映射->命名空间文件夹查找->纯类名映射->正则匹配前缀类全名映射查找->正则匹配文件夹顺序查找
 *
 * @author Lay Li
 * @version 1.0.0 (build 131010)
 */
final class Layload {
    /**
     *
     * @staticvar all class mappings
     */
    public static $classes = array(
            '_prefixes' => array()
    );
    
    /**
     * initialize autoload function
     *
     * @param boolean $debug
     *            debug opened
     * @return void
     */
    public static function initialize($debug = '') {
        spl_autoload_register('Layload::autoload');
        if($debug !== '')
            Debugger::initialize($debug);
        Debugger::info('initilize layload', 'APPLICATION');
    }
    
    /**
     * set class path
     *
     * @param string $classpath
     *            class directory path,default is empty
     * @param boolean $append
     *            is append
     * @return void
     */
    public static function classpath($classpath = '', $append = true) {
        global $_CLASSPATH;
        if(is_dir($classpath)) {
            $classpath = str_replace("\\", "/", $classpath);
            if(! $append || ! $_CLASSPATH) {
                $_CLASSPATH = $classpath;
            } else {
                $paths = explode(';', $_CLASSPATH);
                array_push($paths, $classpath);
                $paths = array_flip(array_flip($paths));
                $_CLASSPATH = implode(';', $paths);
            }
        } else if(is_string($classpath) && $classpath) {
            $paths = explode(';', $classpath);
            foreach($paths as $path) {
                if(is_dir($path))
                    Layload::classpath($path, $append);
            }
        } else if(is_array($classpath) && ! empty($classpath)) {
            foreach($classpath as $path) {
                Layload::classpath($path, $append);
            }
        } else {
            Debugger::warn('$classpath isnot a real path string', 'CLASSPATH');
            // TODO warning given path isnot a real path
        }
    }
    /**
     * set load path
     *
     * @param string $loadpath
     *            class mapping config load directory path,default is empty
     * @return void
     */
    public static function loadpath($loadpath = '') {
        global $_LOADPATH;
        if(is_dir($loadpath)) {
            $_LOADPATH = str_replace("\\", "/", $loadpath);
        } else {
            // TODO warning given path isnot a real path
        }
    }
    
    /**
     * class autoload function
     *
     * @param string $classname
     *            autoload class name
     * @return void
     */
    public static function autoload($classname) {
        global $_CLASSPATH;
        $paths = explode(';', $_CLASSPATH);
        if(empty($paths)) {
            // TODO warning no class autoload path
        } else {
            foreach($paths as $path) {
                if(class_exists($classname) || interface_exists($classname)) {
                    break;
                } else {
                    Layload::autoloadPerPath($classname, $path);
                }
            }
            if(! class_exists($classname) && ! interface_exists($classname)) {
                // TODO warning no class mapping by layload class autoload function
                Debugger::warn($classname . ':class no found by layload class autoload function', 'CLASS_AUTOLOAD');
            }
        }
    }
    /**
     * autoload class by classpath
     *
     * @param string $classname            
     * @param string $classpath            
     * @return void
     */
    private static function autoloadPerPath($classname, $classpath) {
        $classes = &Layload::$classes;
        $prefixes = &Layload::$classes['_prefixes'];
        $suffixes = array(
                '.php',
                '.class.php'
        );
        // 全名映射查找
        if(array_key_exists($classname, $classes)) {
            if(is_file($classes[$classname])) {
                Debugger::info($classes[$classname], 'REQUIRE_ONCE');
                require_once $classes[$classname];
            } else if(is_file($classpath . $classes[$classname])) {
                Debugger::info($classpath . $classes[$classname], 'REQUIRE_ONCE');
                require_once $classpath . $classes[$classname];
            } else {
                // TODO mapping is error
            }
        }
        if(! class_exists($classname) && ! interface_exists($classname)) {
            $tmparr = explode("\\", $classname);
            // if is namespace
            // 通过命名空间查找
            if(count($tmparr) > 1) {
                $name = array_pop($tmparr);
                $path = $classpath . '/' . implode('/', $tmparr);
                $required = false;
                // 命名空间文件夹查找
                if(is_dir($path)) {
                    $tmppath = $path . '/' . $name;
                    foreach($suffixes as $i => $suffix) {
                        if(is_file($tmppath . $suffix)) {
                            Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE');
                            require_once $tmppath . $suffix;
                            $required = true;
                            break;
                        }
                    }
                } else {
                    // TODO not found by namespace dir or not found by class basename
                }
            }
            if(! class_exists($classname) && ! interface_exists($classname) && preg_match_all('/([A-Z]{1,}[a-z0-9]{0,}|[a-z0-9]{1,})_{0,1}/', $classname, $matches) > 0) {
                // TODO autoload class by regular
                $tmparr = array_values($matches[1]);
                $prefix = array_shift($tmparr);
                // 正则匹配前缀查找
                if(array_key_exists($prefix, $prefixes)) { // prefix is not good
                    if(is_file($prefixes[$prefix][$classname])) {
                        Debugger::info($prefixes[$prefix][$classname], 'REQUIRE_ONCE');
                        require_once $prefixes[$prefix][$classname];
                    } else if(is_file($classpath . $prefixes[$prefix][$classname])) {
                        Debugger::info($classpath . $prefixes[$prefix][$classname], 'REQUIRE_ONCE');
                        require_once $classpath . $prefixes[$prefix][$classname];
                    } else {
                        foreach($suffixes as $i => $suffix) {
                            $tmppath = $prefixes[$prefix]['_dir'] . '/' . $classname;
                            if(is_file($tmppath . $suffix)) {
                                Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE');
                                require_once $tmppath . $suffix;
                                break;
                            } else if(is_file($classpath . $tmppath . $suffix)) {
                                Debugger::info($classpath . $tmppath . $suffix, 'REQUIRE_ONCE');
                                require_once $classpath . $tmppath . $suffix;
                                break;
                            } else {
                                // TODO not found by prefix-dir directly
                            }
                        }
                        if(! class_exists($classname) && ! interface_exists($classname)) {
                            // TODO mapping is error by regular match
                        }
                    }
                }
                // 如果正则匹配前缀没有找到
                if(! class_exists($classname) && ! interface_exists($classname)) {
                    // 直接以类名作为文件名查找
                    foreach($suffixes as $i => $suffix) {
                        $tmppath = $classpath . '/' . $classname;
                        if(is_file($tmppath . $suffix)) {
                            Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE');
                            require_once $tmppath . $suffix;
                            break;
                        } else {
                            // TODO not found by classname directly
                        }
                    }
                }
                // 如果以上没有匹配，则使用类名递归文件夹查找，如使用小写请保持（如果第一递归文件夹使用了小写，即之后的文件夹名称保持小写）
                if(! class_exists($classname) && ! interface_exists($classname)) {
                    $path = $lowerpath = $classpath;
                    foreach($matches[1] as $index => $item) {
                        $path .= '/' . $item;Debugger::debug('$path     :'.$path);
                        $lowerpath .= '/' . strtolower($item);Debugger::debug('$lowerpath:'.$lowerpath);
                        if(($isdir = is_dir($path)) || is_dir($lowerpath)) { // 顺序文件夹查找
                            $tmppath = (($isdir)?$path:$lowerpath) . '/' . $classname;
                            foreach($suffixes as $i => $suffix) {
                                if(is_file($tmppath . $suffix)) {
                                    Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE');
                                    require_once $tmppath . $suffix;
                                    break 2;
                                }
                            }
                            
                            continue;
                        } else if($index == count($matches[1]) - 1) {
                            foreach($suffixes as $i => $suffix) {
                                if(($isfile = is_file($path . $suffix)) || is_file($lowerpath . $suffix)) {
                                    Debugger::info((($isfile)?$path:$lowerpath) . $suffix, 'REQUIRE_ONCE');
                                    require_once (($isfile)?$path:$lowerpath) . $suffix;
                                    break 2;
                                }
                            }
                            break;
                        } else {
                            // TODO not found by regular recursive
                        }
                    }
                }
            } else {
                // TODO not found
            }
        }
    }
    /**
     * configure class mapping,all config file is load in $_LOADPATH
     *
     * @param string|array<string> $configuration
     *            a class mapping file or class mapping file array or class mapping array
     * @param boolean $isFile
     *            sign file,default is true
     * @param array $prefixDir
     *            class prefix to dir,default is empty,example: array('prefix'=>'Example','dir'=>'/example')
     * @return void
     */
    public static function configure($configuration, $isFile = true, $prefixDir = array()) {
        global $_CLASSPATH;
        global $_LOADPATH;
        $classes = &Layload::$classes;
        $prefixes = &Layload::$classes['_prefixes'];
        if(is_array($configuration) && ! $isFile) {
            foreach($configuration as $cls => $path) {
                if(is_array($prefixDir) && ! empty($prefixDir) && $prefixDir['dir'] && $prefixDir['prefix']) {
                    if(array_key_exists($cls, $prefixes[$prefixDir['prefix']])) {
                        // TODO class mapping exists, give a warning
                    } else if(is_numeric($cls)) {
                        // TODO numeric class mapping, give a warning
                    } else {
                        $prefixes[$prefixDir['prefix']][$cls] = $prefixDir['dir'] . $path;
                    }
                } else if(array_key_exists($cls, $classes)) {
                    // TODO class mapping exists, give a warning
                } else if(is_numeric($cls)) {
                    // TODO numeric class mapping, give a warning
                } else {
                    $classes[$cls] = $path;
                }
            }
        } else if(is_array($configuration)) {
            if(! empty($configuration)) {
                foreach($configuration as $index => $configfile) {
                    Layload::configure($configfile);
                }
            }
        } else {
            if(is_file($configuration)) {
                $tmparr = include_once $configuration;
            } else if(is_file($_LOADPATH . $configuration)) {
                $tmparr = include_once $_LOADPATH . $configuration;
            } else {
                $tmparr = array();
            }
            
            if(array_key_exists('classes', $tmparr)) {
                Layload::configure($tmparr['classes'], false);
            } else {
                // TODO no class mapping
            }
            // 正则匹配前缀
            if(array_key_exists('prefix-dir', $tmparr)) {
                if($tmparr['prefix-dir']['prefix'] && $tmparr['prefix-dir']['dir']) {
                    $prefix = $tmparr['prefix-dir']['prefix'];
                    $prefixes[$prefix]['_dir'] = $tmparr['prefix-dir']['dir'];
                }
                if(array_key_exists('classes', $tmparr['prefix-dir'])) {
                    Layload::configure($tmparr['prefix-dir']['classes'], false, $tmparr['prefix-dir']);
                }
            } else {
                // TODO no prefix-dir or class mapping
            }
            if(array_key_exists('files', $tmparr)) {
                Layload::configure($tmparr['files']);
            } else {
                // TODO no class config file array
            }
        }
    }
}

if(! class_exists('L', false)) {
    /**
     * Layload autoload class
     *
     * @author Lay Li
     */
    class_alias('Layload', 'L');
}
?>
