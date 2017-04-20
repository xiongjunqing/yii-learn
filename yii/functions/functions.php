<?php

/**
 * 基础函数库, 涉及一些 Phalcon\DI 的调用, 及其他一些基本的功能调用
 */

/**
 * 加载一些不常用的函数库 如load('array', ...)
 */
function load($names)
{
    static $cached = [];

    foreach (func_get_args() as $name) {
        if (isset($cached[$name])) {
            continue;
        }

        $cached[$name] = 1;

        if (is_file($file = FUNC_PATH . $name . '.php')) {
            require_once $file;
        }

    }
}

/**
 * 将当前环境转换为字符串
 */
function env()
{
    return PRODUCTION
    ? 'production'
    : (TESTING ? 'testing' : 'development');
}

/**
 * 简化 Phalcon\Di::getDefault()->getShared($service)
 *
 *     service('url')
 *     service('db')
 *     ...
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_DI.html
 *
 * @param  string   $service
 * @return object
 */
function service($service)
{
    return Phalcon\DI::getDefault()->getShared($service);
}

/**
 * 加载配置文件数据
 *
 *     config('database')
 *     config('database.default.adapter')
 *
 * @param  string  $name
 * @return mixed
 */
function config($name, $default = null)
{
    return service('config')->get($name, $default);
}

/**
 * 实例化一个 model
 *
 *     model('user_data')
 *     model('UserData')
 *
 * @param  string   $name
 * @return object
 */
function model($name)
{
    // 格式化类名
    $class = implode('_', array_map('ucfirst', explode('_', $name)));

    return new $class(Phalcon\DI::getDefault());
}

/**
 * 简化日志写入方法
 *
 *      Phalcon\Logger::SPECIAL
 *      Phalcon\Logger::CUSTOM
 *      Phalcon\Logger::DEBUG
 *      Phalcon\Logger::INFO
 *      Phalcon\Logger::NOTICE
 *      Phalcon\Logger::WARNING
 *      Phalcon\Logger::ERROR
 *      Phalcon\Logger::ALERT
 *      Phalcon\Logger::CRITICAL
 *      Phalcon\Logger::EMERGENCE
 *      Phalcon\Logger::MERGENCY
 *
 *
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Logger.html
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Logger_Adapter_File.html
 *
 * @param string $name    日志名称
 * @param string $message 日志内容
 * @param string $type    日志类型
 */
function logger($name, $message, $type = null)
{
    return service('logger')->log($name, $message, $type);
}
function write_log($name, $message, $type = null)
{
    return logger($name, $message, $type);
}

/**
 * 针对swoole写入日志
 */
function logger_swoole($name, $message, $type = null)
{
    return service('logger')->log_swoole($name, $message, $type);
}

/**
 * 和var_dump功能类似，只是利于在浏览器格式化显示
 */
function dump()
{
    if (function_exists('xdebug_var_dump')) {
        return call_user_func_array('xdebug_var_dump', func_get_args());
    }

    echo "<xmp>\n";
    foreach (func_get_args() as $var) {
        var_dump($var);
    }
    echo '</xmp>';
}

/**
 * 将 Phalcon 的查询结果转化成 array
 * @param  mixed   $res
 * @return array
 */
function toArray($res)
{
    return $res ? $res->toArray() : [];
}

/**
 * 和print_r功能类似，只是利于在浏览器格式化显示
 */
function pr()
{
    echo "<xmp>\n";
    foreach (func_get_args() as $var) {
        print_r($var);
    }
    echo '</xmp>';
}

/**
 * 和pr功能类似，只是在最末尾截断后面的输出
 */
function diepr()
{
    echo "<xmp>\n";
    foreach (func_get_args() as $var) {
        print_r($var);
    }
    echo '</xmp>';
    die;
}

/**
 * 执行 curl 请求，并返回响应内容
 *
 * @param  string   $url
 * @param  array    $data
 * @param  array    $options
 * @return string
 */
function curl($url, array $data = null, array $options = null)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_URL            => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
    ]);

    if ($data) {
        $data = http_build_query($data);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($data),
        ]);
    }

    if ($options) {
        curl_setopt_array($ch, $options);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

/**
 * CURL POST 请求
 *
 * @param  string   $url
 * @param  array    $postdata
 * @param  array    $opts
 * @return string
 */
function post($url, $postdata = '', array $opts = null)
{

    if (isset($postdata['key'])) {
        $key             = $postdata['key'];
        $sign            = sign_front($postdata, $key);
        $postdata['key'] = $sign;
    }
    $ch = curl_init();
    if ('' !== $postdata && is_array($postdata)) {
        $postdata = http_build_query($postdata);
    }

    curl_setopt_array($ch, [
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_URL            => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => $postdata,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FAILONERROR    => 1,
    ]);

    // 是否获取http状态码
    $httpcode = isset($opts['httpcode']) ? $opts['httpcode'] : null;
    unset($opts['httpcode']);

    if (is_array($opts) && $opts) {
        curl_setopt_array($ch, $opts);
    }

    $result = curl_exec($ch);

    $code = (string) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpcode ? ['res' => $result, 'httpcode' => $code] : $result;
}

/**
 * CURL GET 请求
 *
 * @param  string   $url
 * @param  array    $curl_opts
 * @return string
 */
function get($url, array $curl_opts = null)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_URL            => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
    ]);

    if (null !== $curl_opts) {
        curl_setopt_array($ch, $curl_opts);
    }

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

// 通过curl获取文件
function init_curl($url, $username, $password)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    $output = curl_exec($ch); // 执行
    curl_close($ch);          // 关闭

    return $output;
}

/**
 * 简化三元表达式
 *
 * @param  $boolean $boolValue
 * @param  mixed    $trueValue
 * @param  mixed    $falseValue
 * @return mixed
 */
function on($boolValue, $trueValue, $falseValue = null)
{
    return $boolValue ? $trueValue : $falseValue;
}

/**
 * 写入缓存
 *
 * @see    http://docs.phalconphp.com/en/latest/reference/cache.html
 *
 * @param  string  $key
 * @param  mixed   $data
 * @param  integer $lifetime
 * @param  array   $opts
 * @return mixed
 */
function cache($key, $data = '', $lifetime = 86400, $opts = [])
{
    static $cached = [];

    $item = $key . '.cache';
    if ('' === $data) {
        // 取

        return $cached[$item] = service('cache')->get($item);
    }

    if (null === $data) {
        // 删
        unset($cached[$item]);

        return service('cache')->delete($item);
    }

    service('cache')->save($item, $cached[$item] = $data, $lifetime); // 存

    return $data;
}

/**
 * 写入redis缓存
 *
 * @param  string  $key
 * @param  mixed   $data
 * @param  integer $lifetime
 * @param  array   $opts
 * @return mixed
 */
function redis($key, $data = '', $lifetime = 86400, $opts = [])
{
    static $cached = [];

    $item = $key . '.cache';
    if ('' === $data) {
        // 取
        return $cached[$item] = service('redis')->get($item);
    }

    if (null === $data) {
        // 删
        unset($cached[$item]);

        return service('redis')->delete($item);
    }

    service('redis')->save($item, $cached[$item] = $data, $lifetime); // 存

    return $data;
}

/**
 * 设置 cookie 值
 *
 * @param  string  $name
 * @param  mixed   $value
 * @param  integer $expire
 * @return mixed
 */
function cookie($name, $value = '', $expire = null)
{
    if ('' === $value) {
        return service('cookies')->get($name);
    }
    //get

    if (null === $value) {
        return service('cookies')->delete($name);
    }
                                                            // delete
    return service('cookies')->set($name, $value, $expire); // set
}

/**
 * 设置 session 值
 *
 * @see   http://docs.phalconphp.com/en/latest/reference/session.html
 * @see   http://docs.phalconphp.com/en/latest/api/Phalcon_Session_AdapterInterface.html
 *
 * @param string $name
 * @param mixed  $value
 */
function session($name, $value = '')
{
    static $cached = [];
    // get
    if ('' === $value) {
        if (isset($cached[$name])) {
            return $cached[$name];
        }

        if (strpos($name, '.') > 0) {
            // 支持按点获取
            $keys    = explode('.', $name);
            $session = service('session')->get(array_shift($keys), null);
            foreach ($keys as $k) {
                if (is_object($session) && isset($session->$k)) {
                    $session = $session->$k;
                    continue;
                }
                if (is_array($session) && isset($session[$k])) {
                    $session = $session[$k];
                    continue;
                }

                return null;
            }

            return $cached[$name] = $session;
        }

        return $cached[$name] = service('session')->get($name, null);
    }

    // delete
    if (null === $value) {
        unset($cached[$name]);

        return service('session')->remove($name);
    }

    return $cached[$name] = service('session')->set($name, $value); // set
}

/**
 * 转换驼峰式字符串为下划线风格
 *
 *     uncamel('lowerCamelCase') === 'lower_camel_case'
 *     uncamel('UpperCamelCase') === 'upper_camel_case'
 *     uncamel('ThisIsAString') === 'this_is_a_string'
 *     uncamel('notcamelcase') === 'notcamelcase'
 *     uncamel('lowerCamelCase', ' | ') === 'lower | camel | case'
 *
 * @param  string    $string
 * @param  string    $separator
 * @return string
 */
function uncamel($string, $separator = '_')
{
    return str_replace('_', $separator, Phalcon\Text::uncamelize($string));
}

/**
 * 转换下划线字符串为驼峰式风格
 *
 *     camel('lower_camel_case') === 'lowerCamelCase'
 *     camel('upper_camel_case', true) === 'UpperCamelCase'
 *
 * @param  string   $string
 * @param  string   $lower
 * @return string
 */
function camel($string, $upper = false, $separator = '_')
{
    $string = str_replace($separator, '_', $string);

    return $upper ? Phalcon\Text::camelize($string) : lcfirst(Phalcon\Text::camelize($string));
}

/**
 * 隐藏当前系统路径
 *
 *     maskroot('/web/myapp/app/config/db.php') // ~/app/config/db.php
 *
 * @param  string   $path
 * @return string
 */
function maskroot($path)
{
    return str_replace(ROOT_PATH, '~', $path);
}

/**
 * 加载局部视图, 常用于view中
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 *
 * @param  string   $partialPath
 * @param  array    $params
 * @return string
 */
function partial($partialPath, array $params = null)
{
    return service('view')->partial($partialPath, $params);
}

/**
 * 选择不同的视图来渲染，并做为最后的 controller/action 输出，常用于controller中的action
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 *
 * @param  string   $renderView
 * @return string
 */
function pick($renderView)
{
    return service('view')->pick($renderView);
}

/**
 * 获取视图内容
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 *
 * @return string
 */
function content()
{
    return service('view')->getContent();
}

/**
 * 返回一个图片资源对象
 *
 * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Image_Adapter_Imagick.html
 * @see http://docs.phalconphp.com/en/latest/api/Phalcon_Image_Adapter_GD.html
 *
 * @return string
 */
function image($img_file)
{
    if (\Phalcon\Image\Adapter\GD::check()) {
        return new Phalcon\Image\Adapter\GD($img_file);
    }

    if (\Phalcon\Image\Adapter\Imagick::check()) {
        return new Phalcon\Image\Adapter\Imagick($img_file);
    }

    throw new Exception('Please install and enable Imagick/GD library');
}

// ==========================================
// 以下是 单字母缩写方法，和 ThinkPHP 中的简写方式功能类似
// ==========================================

/**
 * 加载配置文件数据
 *
 *     C('database')
 *     C('database.default.adapter')
 *
 * @param  string  $name
 * @return mixed
 */
function C($name, $default = null)
{
    // 可能被多次调用，这里直接用server,减少一次函数调用
    return service('config')->get($name, $default);
}

/**
 * 实例化一个 model
 *
 *     D('UserData')
 *
 * @param  string   $name
 * @return object
 */
function D($name)
{
    return model($name);
}

/**
 * 分割手机号码中的国家码
 * @param  string $phone    手机号码
 * @return array  ['c_code' => xxx , 'phone' => 'xxx']
 */
function splitPhone($phone = '')
{

    if ('' == $phone) {
        return false;
    }

    preg_match('/^(\d{2,4})\s+(\d+)/', trim($phone), $out);
    if (empty($out)) {
        // 没有 再进行匹配是否是中国的手机号码
        if (!preg_match('/^(?:1[34578]\d)-?\d{5}(\d{3})$/', $phone)) {
            return false;
        }

        $arr = [
            'c_code' => '86',
            'phone'  => $phone,
        ];
    } else {
        $arr = [
            'c_code' => $out[1],
            'phone'  => $out[2],
        ];
    }

    return $arr;
}

/**
 * 隐藏手机号码中间4位
 * @param  string $phone                     电话号码
 * @param  string $replace                   隐藏替换字符
 * @return string 隐藏后的电话号码
 */
function maskPhone($phone, $replace = '*')
{
    $suffix = substr($phone, -4);
    $prefix = substr($phone, 0, -8);
    return $prefix . str_pad($replace, 4, $replace) . $suffix;
}

/**
 * 获取URL中的参数, 不支持hash值
 * @param  string $url                                   需要取参的url
 * @param  string $field                                 参数字段
 * @return string 字段值，没有返回空字符串
 */
function getUrlParam($url, $field)
{
    $parts = parse_url($url);
    if (!isset($parts['query'])) {
        return '';
    }

    $result = '';
    $items  = explode('&', $parts['query']);

    foreach ($items as $key => $item) {
        $kv = explode('=', $item);
        if ($field == $kv[0]) {
            $result = $kv[1];
            break;
        }
    }

    return $result;
}

/**
 * 判断是否为移动设备
 * @return boolean
 */
function isMobile()
{
    //如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }

    //脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = [
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile',
        ];
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (
            preg_match(
                "/(" . implode('|', $clientkeywords) . ")/i",
                strtolower($_SERVER['HTTP_USER_AGENT'])
            )
        ) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        //如果只支持wml并且不支持html那一定是移动设备
        //如果支持wml和html但是wml在html之前则是移动设备
        if (
            (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) &&
            (
                strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false ||
                (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))
            )
        ) {
            return true;
        }
    }
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息  部分服务窗可能加上了 via  但是没有wap 比如 阿里
    if (isset($_SERVER['HTTP_VIA'])) {
        //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }

    return false;
}

/**
 * 生成订单号
 * @return string 订单号
 */
function genOrderNo()
{
    return date('Ymd') . substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 切割字符串兼容中英文
 * @param  [type] $str            [description]
 * @param  [type] $bytelength     [description]
 * @param  string $emptyReplace   [description]
 * @return [type] [description]
 */
function cutUtf8String($str, $bytelength, $emptyReplace = "")
{
    if (empty($str)) {
        return $emptyReplace;
    }
    $res    = "";
    $reslen = 0;
    $len    = strlen($str);
    if ($len <= $bytelength) {
        return $str;
    }
    $bytelength = $bytelength - 2;
    for ($i = 0; $i < $len; $i++) {
        $chr = substr($str, $i, 1);
        if (ord($chr) > 127) {
            if ($reslen + 2 <= $bytelength) {
                $res .= substr($str, $i, 3);
                $i += 2;
                $reslen += 2;
            } else {
                $res .= "..";
                return $res;
            }
        } else {
            if ($reslen + 1 <= $bytelength) {
                $res .= substr($str, $i, 1);
                $reslen += 1;
            } else {
                $res .= "..";
                return $res;
            }
        }
    }
    return $res;
}

/*
 * 数字 向下 保留几位小数,不会进行四舍五入的操作
 */
function num_under($num, $bit)
{
    $arr = preg_split('/\./', $num, -1);
    return count($arr) == 1 ? $arr[0] . '.00' : $arr[0] . '.' . substr($arr[1], 0, $bit);
}

/*
 * 数字保留几位小数，小数位不足时补足0。相当与js的toFixed
 * 如num_round(1.1, 2) 返回1.10
 * @return string
 */
function num_round($num, $bit)
{
    $num = round($num, $bit);
    return sprintf('%.' . $bit . 'f', $num);
}

function storage_curl($url, $method = 'GET', $data = [])
{
    $ch = curl_init();
    switch ($method) {
        case 'GET':
            //拼接get参数
            $url = [] == $data ? $url : $url . '?' . http_build_query($data);
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        case 'PUT':
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        default:
            break;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_errno($ch) ? curl_error($ch) : curl_exec($ch);
    $info     = curl_getinfo($ch);
    curl_close($ch);
    //2xx 和 3xx 成功
    if (in_array(substr($info['http_code'], 0, 1), [2, 3])) {
        return $response;
    }
    throw new \Exception($response);
}

function get_prize_result($arr)
{
    if (!is_array($arr)) {
        return false;
    }
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($arr);

    //概率数组循环
    foreach ($arr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset($arr);
    return $result;
}
