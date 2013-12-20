<?php
// 判断Layload是否已经定义
if(defined('INIT_LAYLOAD')) {
    return;
}
// 定义标记
define('INIT_LAYLOAD', true);

/**
 * Layload autoload class
 * 加载优先级：类全名映射->命名空间文件夹查找->纯类名映射->正则匹配前缀类全名映射查找->正则匹配文件夹顺序查找
 *
 * @author Lay Li
 * @version 1.0.0 (build 131010)
 */
class Layload {
    /**
     * global classpath
     *
     * @var string
     */
    public static $_CLASSPATH;
    /**
     * global class config load path
     *
     * @var string
     */
    public static $_LOADPATH;
    /**
     *
     * @var Layload
     */
    private static $instance = null;
    /**
     *
     * @return Layload
     */
    public static function getInstance() {
        if(! self::$instance) {
            self::$instance = new Layload();
        }
        return self::$instance;
    }
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
        $_CLASSPATH = &self::$_CLASSPATH;
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
        $_LOADPATH = &self::$_LOADPATH;
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
        $_CLASSPATH = &self::$_CLASSPATH;
        $paths = explode(';', $_CLASSPATH);
        if(empty($paths)) {
            Debugger::warn('no classpath to load by Layload', 'CLASS_AUTOLOAD');
            self::checkAutoloadFunctions();
        } else {
            foreach($paths as $path) {
                if(class_exists($classname, false) || interface_exists($classname, false)) {
                    break;
                } else {
                    self::getInstance()->loadClass($classname, $path);
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
     * configure class mapping,all config file is load in $_LOADPATH
     *
     * @param string|array<string> $configuration
     *            a class mapping file or class mapping file array or class mapping array
     * @param boolean $isFile
     *            sign file,default is true
     * @param array $prefixDir
     *            class prefix to dir,default is empty,example: array('prefix'=>'Example','dir'=>'/example')
     * @return void
     * @deprecated
     *
     *
     *
     *
     */
    public static function configure($configuration, $isFile = true, $prefixDir = array()) {
        self::getInstance()->setClassesPath($configuration, $isFile, $prefixDir);
    }
    
    /**
     * all class mappings
     *
     * @var array
     */
    private $classes = array();
    private $caches = array();
    private $cached = false;
    /**
     * 构造方法，同时执行读取缓存
     */
    private function __construct() {
        $this->loadCache();
    }
    /**
     * 析构方法，同时执行更新缓存
     */
    public function __destruct() {
        $this->updateCache();
    }
    /**
     * autoload class by classpath
     *
     * @param string $classname            
     * @param string $classpath            
     * @return void
     */
    public function loadClass($classname, $classpath) {
        $classes = $this->classes;
        // 去除使用prefix功能
        // $prefixes = is_array($this->classes['_prefixes'])?$this->classes['_prefixes']:array();
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
                            $this->setCache($classname, $tmppath . $suffix);
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
                // 正则匹配前缀查找 //去除使用prefix功能
                /*
                 * if(array_key_exists($prefix, $prefixes)) { // prefix is not good if(is_file($prefixes[$prefix][$classname])) { Debugger::info($prefixes[$prefix][$classname], 'REQUIRE_ONCE'); $this->setCache($classname, $prefixes[$prefix][$classname]); require_once $prefixes[$prefix][$classname]; } else if(is_file($classpath . $prefixes[$prefix][$classname])) { Debugger::info($classpath . $prefixes[$prefix][$classname], 'REQUIRE_ONCE'); $this->setCache($classname, $classpath . $prefixes[$prefix][$classname]); require_once $classpath . $prefixes[$prefix][$classname]; } else { foreach($suffixes as $i => $suffix) { $tmppath = $prefixes[$prefix]['_dir'] . '/' . $classname; if(is_file($tmppath . $suffix)) { Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE'); $this->setCache($classname, $tmppath . $suffix); require_once $tmppath . $suffix; break; } else if(is_file($classpath . $tmppath . $suffix)) { Debugger::info($classpath . $tmppath . $suffix, 'REQUIRE_ONCE'); $this->setCache($classname, $classpath . $tmppath . $suffix); require_once $classpath . $tmppath . $suffix; break; } } } }
                 */
                // 如果正则匹配前缀没有找到
                if(! class_exists($classname, false) && ! interface_exists($classname, false)) {
                    // 直接以类名作为文件名查找
                    foreach($suffixes as $i => $suffix) {
                        $tmppath = $classpath . '/' . $classname;
                        if(is_file($tmppath . $suffix)) {
                            Debugger::info($tmppath . $suffix, 'REQUIRE_ONCE');
                            $this->setCache($classname, $tmppath . $suffix);
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
                                    $this->setCache($classname, $tmppath . $suffix);
                                    require_once $tmppath . $suffix;
                                    break 2;
                                }
                            }
                            
                            continue;
                        } else if($index == count($matches[1]) - 1) {
                            foreach($suffixes as $i => $suffix) {
                                if(($isfile = is_file($path . $suffix)) || is_file($lowerpath . $suffix)) {
                                    Debugger::info((($isfile) ? $path : $lowerpath) . $suffix, 'REQUIRE_ONCE');
                                    $this->setCache($classname, (($isfile) ? $path : $lowerpath) . $suffix);
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
     * set classes mapping,all file is load by $_LOADPATH
     *
     * @param string|array<string> $configuration
     *            a class mapping file or class mapping file array or class mapping array
     * @param boolean $isFile
     *            sign file,default is true
     * @param array $prefixDir
     *            class prefix to dir,default is empty,example: array('prefix'=>'Example','dir'=>'/example')
     * @return void
     * @deprecated
     *
     *
     *
     *
     */
    public function setClassesPath($configuration, $isFile = true, $prefixDir = array()) {
        $_LOADPATH = &self::$_LOADPATH;
        $classes = &$this->classes;
        // 去除使用prefix功能
        // $prefixes = &$this->classes['_prefixes'];
        if(is_array($configuration) && ! $isFile) {
            foreach($configuration as $cls => $path) {
                // 去除使用prefix功能
                /*
                 * if(is_array($prefixDir) && ! empty($prefixDir) && $prefixDir['dir'] && $prefixDir['prefix']) { if(array_key_exists($cls, $prefixes[$prefixDir['prefix']])) { Debugger::warn('classname exists in classes mapping', 'CONFIGURE'); } else if(is_numeric($cls)) { Debugger::warn('nonnumericial classname in classes mapping', 'CONFIGURE'); } else { $prefixes[$prefixDir['prefix']][$cls] = $prefixDir['dir'] . $path; } } else
                 */
                if(array_key_exists($cls, $classes)) {
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
                    $this->setClassesPath($configfile);
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
                $this->setClassesPath($tmparr['classes'], false);
            } else {
                Debugger::warn('no class mapping in configuration file', 'CONFIGURE');
            }
            // 正则匹配前缀
            // 去除使用prefix功能
            /*
             * if(array_key_exists('prefix-dir', $tmparr)) { if($tmparr['prefix-dir']['prefix'] && $tmparr['prefix-dir']['dir']) { $prefix = $tmparr['prefix-dir']['prefix']; $prefixes[$prefix]['_dir'] = $tmparr['prefix-dir']['dir']; } if(array_key_exists('classes', $tmparr['prefix-dir'])) { $this->setClassesPath($tmparr['prefix-dir']['classes'], false, $tmparr['prefix-dir']); } }
             */
            if(array_key_exists('files', $tmparr)) {
                $this->setClassesPath($tmparr['files']);
            } else {
                Debugger::warn('no files in configuration file', 'CONFIGURE');
            }
        }
    }
    public function getClassPath($classname) {
        if(isset($this->classes[$classname])) {
            return $this->classes[$classname];
        } else {
            return false;
        }
    }
    public function getClassesPath() {
        return $this->classes;
    }
    private function loadCache() {
        $cachename = __DIR__ . '/cache/classes.php';
        if(is_file($cachename)) {
            $this->caches = include $cachename;
        } else {
            $this->caches = array();
        }
        if(is_array($this->caches) && ! empty($this->caches)) {
            $this->classes = array_merge($this->classes, $this->caches);
        }
    }
    /**
     * 更新缓存的类文件映射
     *
     * @return number
     */
    private function updateCache() {
        if($this->cached) {
            if(! is_dir(__DIR__ . '/cache'))
                mkdir(__DIR__ . '/cache');
            $content = self::array2PHPContent($this->caches);
            $cachename = __DIR__ . '/cache/classes.php';
            $handle = fopen($cachename, 'w');
            $result = fwrite($handle, $content);
            $return = fflush($handle);
            $return = fclose($handle);
            $this->cached = false;
            return $result;
        } else {
            return 0;
        }
    }
    /**
     * 将类文件映射缓存起来
     *
     * @param string $classname            
     * @param string $filepath            
     * @return void
     */
    private function setCache($classname, $filepath) {
        // TODO to implement
        $this->cached = true;
        $this->caches[$classname] = $filepath;
    }
    /**
     * 获取缓存起来的类文件映射
     *
     * @return array string
     */
    public function getCache($classname = '') {
        // TODO to implement
        if(is_string($classname) && $classname && isset($this->caches[$classname])) {
            return $this->caches[$classname];
        } else {
            return $this->caches;
        }
    }
    /**
     * php array to php content
     *
     * @param array $arr
     *            convert array
     * @param boolean $encrypt
     *            if encrypt
     * @return string
     */
    public static function array2PHPContent($arr, $encrypt = false) {
        if($encrypt) {
            $r = '';
            $r .= self::array2String($arr);
        } else {
            $r = "<?php return ";
            self::a2s($r, $arr);
            $r .= ";?>\n";
        }
        return $r;
    }
    /**
     * convert a multidimensional array to url save and encoded string
     *
     * 在Array和String类型之间转换，转换为字符串的数组可以直接在URL上传递
     *
     * @param array $Array
     *            convert array
     */
    public static function array2String($Array) {
        $Return = '';
        $NullValue = "^^^";
        foreach($Array as $Key => $Value) {
            if(is_array($Value))
                $ReturnValue = '^^array^' . self::array2String($Value);
            else
                $ReturnValue = (strlen($Value) > 0) ? $Value : $NullValue;
            $Return .= urlencode(base64_encode($Key)) . '|' . urlencode(base64_encode($ReturnValue)) . '||';
        }
        return urlencode(substr($Return, 0, - 2));
    }
    /**
     * convert a string generated with Array2String() back to the original (multidimensional) array
     *
     * @param string $String
     *            convert string
     */
    public static function string2Array($String) {
        $Return = array();
        $String = urldecode($String);
        $TempArray = explode('||', $String);
        $NullValue = urlencode(base64_encode("^^^"));
        foreach($TempArray as $TempValue) {
            list($Key, $Value) = explode('|', $TempValue);
            $DecodedKey = base64_decode(urldecode($Key));
            if($Value != $NullValue) {
                $ReturnValue = base64_decode(urldecode($Value));
                if(substr($ReturnValue, 0, 8) == '^^array^')
                    $ReturnValue = self::string2Array(substr($ReturnValue, 8));
                $Return[$DecodedKey] = $ReturnValue;
            } else {
                $Return[$DecodedKey] = NULL;
            }
        }
        return $Return;
    }
    /**
     * array $a to string $r
     *
     * @param string $r
     *            output string pointer address
     * @param array $a
     *            input array pointer address
     * @return void
     */
    public static function a2s(&$r, array &$a, $l = "", $b = "    ") {
        $f = false;
        $h = false;
        $i = 0;
        $r .= 'array(' . "\n";
        foreach($a as $k => $v) {
            if(! $h)
                $h = array(
                        'k' => $k,
                        'v' => $v
                );
            if($f)
                $r .= ',' . "\n";
            $j = ! is_string($k) && is_numeric($k) && $h['k'] === 0;
            self::o2s($r, $k, $v, $i, $j, $l, $b);
            $f = true;
            if($j && $k >= $i)
                $i = $k + 1;
        }
        $r .= "\n$l" . ')';
    }
    /**
     * to string $r
     *
     * @param string $r
     *            output string pointer address
     * @param string $k            
     * @param string $v            
     * @param string $i            
     * @param string $j            
     * @return void
     */
    private static function o2s(&$r, $k, $v, $i, $j, $l, $b) {
        if($k !== $i) {
            if($j)
                $r .= "$l$b$k => ";
            else
                $r .= "$l$b'$k' => ";
        } else {
            $r .= "$l$b";
        }
        if(is_array($v))
            self::a2s($r, $v, $l . $b);
        else if(is_numeric($v))
            $r .= "" . $v;
        else
            $r .= "'" . str_replace(array(
                    "\\",
                    "'"
            ), array(
                    "\\\\",
                    "\'"
            ), $v) . "'";
    }
}
?>
