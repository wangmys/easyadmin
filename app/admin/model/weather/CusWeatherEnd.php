<?php

namespace app\admin\model\weather;

use app\common\model\TimeModel;

class CusWeatherEnd extends TimeModel
{

    // 设置当前模型的数据库连接
    protected $connection = 'tianqi';

    // 表名
    protected $name = 'cus_weather_end';
}