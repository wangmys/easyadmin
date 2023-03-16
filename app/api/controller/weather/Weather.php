<?php
declare (strict_types = 1);

namespace app\api\controller\weather;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Weather as WeatherM;
use app\admin\model\weather\Customers;

/**
 * 天气接口应用类
 * Class Weather
 * @package app\api\controller\weather
 */
class Weather
{
    public function index()
    {
        echo '<pre>';
        print_r(554);
        die;    
    }
    
    /**
     * 同步店铺数据
     */
    public function SyncStoreData()
    {
        // 获取店铺数据
        $where = [['ShutOut','=','0'],['RegionId','not in','0,8,84,85,40']];
        $field = 'CustomerId,CustomerCode,CustomerName,RegionId,State,City,SendGoodsGroup,CustomItem17,CustomItem18';
        $result = Db::connect("sqlsrv")->table('ErpCustomer')
            ->field($field)
            ->where($where)
            ->select();
        echo '<pre>';
        print_r($result->toArray());
        die;
        return $result;

    }

    /**
     * 同步区域数据
     */
    public function SyncAreaData()
    {

    }

    /**
     * 拉取天气数据
     */
    public function PullWeather()
    {

    }
}
