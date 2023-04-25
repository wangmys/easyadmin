<?php

namespace app\admin\model\weather;


use app\common\model\TimeModel;

/** 省会天气数据
 * Class Capital
 * @package app\admin\model\weather
 */
class Capital extends TimeModel
{
    // 表名
    protected $name = 'capital_weather';
}