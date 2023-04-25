<?php

namespace app\api\service;

use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Db;
use think\facade\Config;

/**
 * 天气服务层
 * Class WeatherService
 * @package app\common\service
 */
class WeatherService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];

    /**
     * 初始化
     * WeatherService constructor.
     */
    public function __construct()
    {
        $cityUrl = 2;
        $service = new self();
    }

    /**
     * 拉取省会城市天气
     */
    public function pullCapitalWeather()
    {
        // 获取省会列表
    }

    /**
     * 省会列表
     */
    public function getCapitalList()
    {
        
    }

    /**
     * 拉取城市天气
     */
    public function pullWeather()
    {
        
    }
}