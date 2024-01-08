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


namespace app\common\model;


use think\Model;


/**
 * 有关时间的模型
 * Class TimeModel
 * @package app\common\model
 */
class MqxWeatherCustomer  extends TimeModel
{

    protected $connection = 'mysql';

    protected $prefix='';
    protected $table = 'mqx_weather_customer';

}