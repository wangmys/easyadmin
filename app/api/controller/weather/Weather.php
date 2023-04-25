<?php
declare (strict_types = 1);

namespace app\api\controller\weather;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Weather as WeatherM;
use app\admin\model\weather\Customers;
use app\common\service\WeatherService;
use app\admin\model\weather\BiCustomers;
use app\admin\model\weather\Capital;
use app\admin\model\weather\CityUrl;

/**
 * 天气接口应用类
 * Class Weather
 * @package app\api\controller\weather
 */
class Weather
{

    public function __construct()
    {
        $this->capital = new Capital;
        $this->biCustomers = new BiCustomers;
        $this->city = new CityUrl;
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
        return $result;
    }

    /**
     * 拉取天气数据
     */
    public function PullWeather()
    {
        $service = new WeatherService;
        // 省会城市列表
        $capitalList = $service->getCapitalList();
        // 实例化
        $biCustomer = (new BiCustomers);
        // 现有店铺的省份列表
        $list = $biCustomer->getList();
        foreach ($capitalList as $key => $val){
            // 省份前两个字
            $province_name = $val['province_name'];
            if(($key = array_search($province_name,$list)) !== false){
                // 获取指定省会城市未来40天,天气
                $weather = $service->getWeather40($val['cid']);
                // 保存省会城市天气数据
                if(!empty($weather)) $result = $service->saveWeatherData($weather);
            }
        }
    }

    /**
     * 更新省会天气省份/城市
     * @return array
     */
    public function updateName()
    {
        // 查询城市ID列表
        $cidList = $this->capital->group('cid')->column('cid');
        // 查询城市名称列表
        $list = $this->city->whereIn('cid',$cidList)->column('LEFT(province,2) as province,city','cid');
        // 批量组合更新
        $update_data = [];
        // 更新数据
        foreach ($list as $key => $val){
            $res[$val['cid']][] = $this->capital->where([
                'cid' => $val['cid']
            ])->update([
                'province' => $val['province'],
                'name' => $val['city']
            ]);
        }
        return $res;
    }

    /**
     * 拉取省会城市天气
     */
    public function pullProvincialWeather()
    {

    }
}
