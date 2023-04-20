<?php
// 应用公共文件

use app\common\service\AuthService;
use think\facade\Cache;

if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @deprecated 不建议使用，建议直接使用框架自带的log组件
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

if (!function_exists('sysconfig')) {

    /**
     * 获取系统配置信息
     * @param $group
     * @param null $name
     * @return array|mixed
     */
    function sysconfig($group, $name = null)
    {
        $where = ['group' => $group];
        $value = empty($name) ? Cache::get("sysconfig_{$group}") : Cache::get("sysconfig_{$group}_{$name}");
        if (empty($value)) {
            if (!empty($name)) {
                $where['name'] = $name;
                $value = \app\admin\model\SystemConfig::where($where)->value('value');
                Cache::tag('sysconfig')->set("sysconfig_{$group}_{$name}", $value, 3600);
            } else {
                $value = \app\admin\model\SystemConfig::where($where)->column('value', 'name');
                Cache::tag('sysconfig')->set("sysconfig_{$group}", $value, 3600);
            }
        }
        return $value;
    }
}

if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }

}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new AuthService(session('admin.id'));
        $check = $authService->checkNode($node);
        return $check;
    }

}

if (!function_exists('curl_post')) {

    /**
     * post请求
     * @param $url
     * @param array $data
     * @param string $cookiePath
     * @return bool|string
     */
    function curl_post($url, $data = [], $cookiePath = '')
    {
        $ch = curl_init(); // 初始化
        curl_setopt($ch, CURLOPT_URL, $url); // 抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); // POST提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // 请求参数
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 连接结束后保存cookie信息的文件
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 包含cookie信息的文件
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 禁用后cURL将终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 检查服务器SSL证书中是否存在一个公用名
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ));
        $res = curl_exec($ch); // 执行一个cURL会话
        curl_close($ch); // 关闭一个cURL会话
        return $res;
    }
}

/**
 * @param $time
 * @return string
 */
function date_to_week($time)
{
    $weekarray = array("日", "一", "二", "三", "四", "五", "六");
    return "周" . $weekarray[date("w", strtotime($time))];
}

/**
 * 获取周一到今日的时间区间
 */
function getThisDayToStartDate()
{
    // 指定结束日期
    $end_date = date('Y-m-d');
    // 获取今日星期几
    $week = date('w');
    $subtractDay = 0;
    if($week == 0){
        $subtractDay = 7 - 1;
    }else{
        $subtractDay = $week - 1;
    }
    // 获得开始日期
    $start_date = date('Y-m-d',strtotime(date('Y-m-d')."-{$subtractDay}day"));
    return [$start_date,$end_date];
}

/**
 * 获取周一到今日的时间区间
 */
function getIntervalDays()
{
    $datetime_start = new DateTime(getThisDayToStartDate()[0]);
    $datetime_end = new DateTime(getThisDayToStartDate()[1]);
    return $datetime_start->diff($datetime_end)->days;
}

/**
 * 一维数组转字符串
 *  [
        ["万年一店"]
        ["万年二店"]
    ]
     
    str ='万年一店','万年二店'
 */
function arrToStr($arr) {
    $str = '';
    $len = count($arr);
    foreach ($arr as $key => $val) {
        if ($key < $len -1 ) {
            $str .= "'{$val}'" . ",";
        } else {
            $str .= "'{$val}'";
        }
        
    }
    return $str;
}