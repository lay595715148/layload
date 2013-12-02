<?php
// 判断Layload是否已经定义
if(defined('INIT_LAYLOAD')) {
    return;
}
// 定义标记
define('INIT_LAYLOAD', true);

/**
 *
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
class Layload {
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
            Debugger::warn('given $classpath isnot a real path string', 'CONFIGURE');
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
            Debugger::warn('given $loadpath isnot a real path', 'CONFIGURE');
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
            Debugger::warn('no classpath to load by Layload', 'CLASS_AUTOLOAD');
            self::checkAutoloadFunctions();
        } else {
            foreach($paths as $path) {
                if(class_exists($classname, false) || interface_exists($classname, false)) {
                    break;
                } else {
                    Layload::autoloadPerPath($classname, $path);
                }
            }
            if(! class_exists($classname, false) && ! interface_exists($classname, false)) {
                Debugger::warn($classname . ':class not found by Layload', 'CLASS_AUTOLOAD');
                self::checkAutoloadFunctions();
            }
        }
    }
    /**
     * 判断是否还有其他自动加载函数，如没有则抛出异常
     * 
     * @throws Exception
     */
    private static function checkAutoloadFunctions() {
        // 判断是否还有其他自动加载函数，如没有则抛出异常
        $funs = spl_autoload_functions();
        $count = count($funs);
        foreach($funs as $i => $fun) {
            if($fun[0] == 'Layload' && $fun[1] == 'autoload' && $count == $i + 1) {
                throw new Exception('class not found by layload');
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
            }
        }
        if(! class_exists($classname, false) && ! interface_exists($classname, false)) {
            $tmparr = explode("\\", $classname);
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
                }
            }
            if(! class_exists($classname, false) && ! interface_exists($classname, false) && preg_match_all('/([A-Z]{1,}[a-z0-9]{0,}|[a-z0-9]{1,})_{0,1}/', $classname, $matches) > 0) {
                // 正则匹配后进行查找
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
                            }
                        }
                    }
                }
                // 如果正则匹配前缀没有找到
                if(! class_exists($classname, false) && ! interface_exists($classname, false)) {
                    // 直接以类名作为文件名查找
                    foreach($suffixes as $i => $suffix) {
                        $tmppath = $classpath . '/' . $classname;
                        if(is_file($tmppath . $suffix)) {
                            Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE');
                            require_once $tmppath . $suffix;
                            break;
                        }
                    }
                }
                // 如果以上没有匹配，则使用类名递归文件夹查找，如使用小写请保持（如果第一递归文件夹使用了小写，即之后的文件夹名称保持小写）
                if(! class_exists($classname, false) && ! interface_exists($classname, false)) {
                    $path = $lowerpath = $classpath;
                    foreach($matches[1] as $index => $item) {
                        $path .= '/' . $item; // Debugger::debug('$path :'.$path);
                        $lowerpath .= '/' . strtolower($item); // Debugger::debug('$lowerpath:'.$lowerpath);
                        if(($isdir = is_dir($path)) || is_dir($lowerpath)) { // 顺序文件夹查找
                            $tmppath = (($isdir) ? $path : $lowerpath) . '/' . $classname;
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
                                    Debugger::info((($isfile) ? $path : $lowerpath) . $suffix, 'REQUIRE_ONCE');
                                    require_once (($isfile) ? $path : $lowerpath) . $suffix;
                                    break 2;
                                }
                            }
                            break;
                        }
                    }
                }
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
                        Debugger::warn('classname exists in classes mapping', 'CONFIGURE');
                    } else if(is_numeric($cls)) {
                        Debugger::warn('nonnumericial classname in classes mapping', 'CONFIGURE');
                    } else {
                        $prefixes[$prefixDir['prefix']][$cls] = $prefixDir['dir'] . $path;
                    }
                } else if(array_key_exists($cls, $classes)) {
                    Debugger::warn('classname exists in classes mapping', 'CONFIGURE');
                } else if(is_numeric($cls)) {
                    Debugger::warn('nonnumericial classname in classes mapping', 'CONFIGURE');
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
                Debugger::warn('configuration is not a real file path', 'CONFIGURE');
                $tmparr = array();
            }
            
            if(array_key_exists('classes', $tmparr)) {
                Layload::configure($tmparr['classes'], false);
            } else {
                Debugger::warn('no class mapping in configuration file', 'CONFIGURE');
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
            }
            if(array_key_exists('files', $tmparr)) {
                Layload::configure($tmparr['files']);
            } else {
                Debugger::warn('no files in configuration file', 'CONFIGURE');
            }
        }
    }
}
?>
