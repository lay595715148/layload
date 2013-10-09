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
        if(array_key_exists($classname, $classes)) {//全名映射
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
                            if(Layload::$debug) {
                                Debugger::info($tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                            }
                            require_once $tmppath.$suffix;
                            $required = true;
                            break;
                        }
                    }
                /*}
                //纯类名映射
                if(!$unrequired && array_key_exists($name,$classes)) {
                    if(is_file($classes[$name])) {
                        if(Layload::$debug) echo 'require_once '.$classes[$name].'<br>';
                        require_once $classes[$name];
                    } else if(is_file($_CLASSPATH.$classes[$name])) {
                        if(Layload::$debug) echo 'require_once '.$_CLASSPATH.$classes[$name].'<br>';
                        require_once $_CLASSPATH.$classes[$name];
                    } else {
                        //TODO mapping is error
                    }*/
                } else {
                    //TODO not found by namespace dir or not found by class basename
                }
            } else if(preg_match_all('/([A-Z]{1,}[a-z0-9]{0,}|[a-z0-9]{1,})_{0,1}/', $classname, $matches) > 0) {
                //TODO autoload class by regular
                $tmparr = array_values($matches[1]);
                $prefix = array_shift($tmparr);
                //正则匹配前缀查找
                if(array_key_exists($prefix,$prefixes)) {
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
                        //TODO mapping is error by regular match
                    }
                } else {
                    $path = $_CLASSPATH;
                    //直接以类名作为文件名查找
                    foreach($suffixes as $i=>$suffix) {
                        $tmppath = $path.'/'.$classname;
                        if(is_file($tmppath.$suffix)) {
                            if(Layload::$debug) {
                                Debugger::info($tmppath.$suffix, 'REQUIRE_ONCE', __LINE__, __METHOD__, __CLASS__);
                            }
                            require_once $tmppath.$suffix;
                        } else {
                            //TODO not found by classname directly
                        }
                    }
                    //如果以上没有匹配，则递归文件夹查找
                    if(!class_exists($classname) && !interface_exists($classname)) {
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
                    }
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
//final class L extends Layload {}//short classname
class_alias('Layload', 'L');//Layload class alias

if(!class_exists('Debugger', false)) {
    class Debugger {
        const DEBUG_LEVEL_DEBUG = 1;
        const DEBUG_LEVEL_INFO = 2;
        const DEBUG_LEVEL_WARN = 4;
        const DEBUG_LEVEL_ERROR = 8;
        const DEBUG_LEVEL_ALL = 15;
        public static $out = false;
        public static $log = false;
        /**
         * initialize Debugger
         * @return void
         */
        public static function initialize($debug = '') {
            if(is_bool($debug)) {
                self::$out = self::$log = $debug;
            } else if(is_array($debug)) {
                $debug['out'] = isset($debug['out']) ? $debug['out'] : isset($debug[0])?$debug[0]:false;
                $debug['log'] = isset($debug['log']) ? $debug['log'] : isset($debug[1])?$debug[1]:false;
                self::$out = ($debug['out'] === true)?true:intval($debug['out']);
                self::$log = ($debug['log'] === true)?true:intval($debug['log']);
            } else if(is_int($debug)) {
                self::$out = self::$log = $debug;
            } else if($debug === '') {
                $debug = Laywork::get('debug');
                if($debug === '' || $debug === null) {
                    self::$out = self::$log = false;
                } else {
                    self::initialize($debug);
                }
            } else {
                self::$out = self::$log = false;
            }
        }
        /**
         * print out debug infomation
         * @return void
         */
        public static function debug($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(1, 3, 5, 7, 9, 11, 13, 15)))) {
                self::pre($msg, self::DEBUG_LEVEL_DEBUG, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(1, 3, 5, 7, 9, 11, 13, 15)))) {
                self::log(json_encode($msg), self::DEBUG_LEVEL_DEBUG, $tag, $line, $method, $class);
            }
        }
        /**
         * print out info infomation
         * @return void
         */
        public static function info($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(2, 3, 6, 7, 10, 11, 14, 15)))) {
                self::out($msg, self::DEBUG_LEVEL_INFO, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(2, 3, 6, 7, 10, 11, 14, 15)))) {
                self::log($msg, self::DEBUG_LEVEL_INFO, $tag, $line, $method, $class);
            }
        }
        /**
         * print out warning infomation
         * @return void
         */
        public static function warning($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(4, 5, 6, 7, 12, 13, 14, 15)))) {
                self::out($msg, self::DEBUG_LEVEL_WARN, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(4, 5, 6, 7, 12, 13, 14, 15)))) {
                self::log($msg, self::DEBUG_LEVEL_WARN, $tag, $line, $method, $class);
            }
        }
        /**
         * print out warning infomation
         * @return void
         */
        public static function warn($msg, $tag = '', $line = '', $method = '', $class = '') {
            self::warning($msg, $tag, $line, $method, $class);
        }
        /**
         * print out error infomation
         * @return void
         */
        public static function error($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(8, 9, 10, 11, 12, 13, 14, 15)))) {
                self::out($msg, self::DEBUG_LEVEL_ERROR, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(8, 9, 10, 11, 12, 13, 14, 15)))) {
                self::log($msg, self::DEBUG_LEVEL_ERROR, $tag, $line, $method, $class);
            }
        }
        
        /**
         * syslog infomation
         * @return void
         */
        public static function log($msg = '', $lv = 1, $tag = '', $line = '', $method = '', $class = '') {
            if(!$method) $method = $class;
            if(!$tag || !is_string($tag)) $tag = 'main';
            $lv = self::parseLevel($lv);
            $ip = self::ip();
            syslog(LOG_INFO, date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip LAYWORK [$lv] [$tag] $method:$line $msg");
        }
        /**
         * print infomation
         * @return void
         */
        public static function out($msg = '', $lv = 1, $tag = '', $line = '', $method = '', $class = '') {
            if(!$method) $method = $class;
            if(!$tag || !is_string($tag)) $tag = 'main';
            $lv = self::parseLevel($lv);
            $ip = self::ip();
            echo '<pre style="padding:0px;margin:0px;border:0px;">';
            echo date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip [$lv] [$tag] $method:$line $msg\r\n";
            echo '</pre>';
        }
        /**
         * print mixed infomation
         * @return void
         */
        public static function pre($msg = '', $lv = 1, $tag = '', $line = '', $method = '', $class = '') {
            if(!$method) $method = $class;
            if(!$tag || !is_string($tag)) $tag = 'main';
            $lv = self::parseLevel($lv);
            $ip = self::ip();
            echo '<pre style="padding:0px;margin:0px;border:0px;">';
            echo date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip [$lv] [$tag] $method:$line\r\n";
            echo '</pre>';
            echo '<pre style="padding:0px;margin:0 0 0 20px;border:0px;">';
            print_r($msg);
            echo '</pre>';
        }
        /**
         * parse level to string or integer
         * @return string|integer
         */
        public static function parseLevel($lv) {
            switch($lv) {
                case self::DEBUG_LEVEL_DEBUG:
                    $lv = 'DEBUG';
                    break;
                case self::DEBUG_LEVEL_INFO:
                    $lv = 'INFO';
                    break;
                case self::DEBUG_LEVEL_WARN:
                    $lv = 'WARN';
                    break;
                case self::DEBUG_LEVEL_ERROR:
                    $lv = 'ERROR';
                    break;
                case 'DEBUG':
                    $lv = self::DEBUG_LEVEL_DEBUG;
                    break;
                case 'INFO':
                    $lv = self::DEBUG_LEVEL_INFO;
                    break;
                case 'WARN':
                    $lv = self::DEBUG_LEVEL_WARN;
                    break;
                case 'ERROR':
                    $lv = self::DEBUG_LEVEL_ERROR;
                    break;
            }
            return $lv;
        }
        /**
         * get client ip
         * @return string
         */
        public static function ip() {
            if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
                $ip = getenv('REMOTE_ADDR');
            } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
        }
    }
}
?>
