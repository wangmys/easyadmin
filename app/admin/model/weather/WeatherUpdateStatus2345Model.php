<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\model\weather;


use app\common\model\TimeModel;

class WeatherUpdateStatus2345Model extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'tianqi';

    // 表名
    protected $name = 'weather_update_status2345';
}