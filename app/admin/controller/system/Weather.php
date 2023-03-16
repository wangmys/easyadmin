<?php


namespace app\admin\controller\system;

use app\admin\model\weather\Weather as WeatherM;
use app\admin\model\weather\Customers as CustomersM;
use app\admin\model\weather\CityUrl;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use app\common\service\WeatherService;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use think\cache\driver\Redis;
use app\admin\model\weather\Region;

/**
 * Class Weather
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="天气管理")
 */
class Weather extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new WeatherM;
        $this->customers = new CustomersM;
    }

    /**
     * @NodeAnotation(title="天气列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();
            $count = $this->customers
            ->alias('c')
            ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
            ->leftJoin('city_url cu','cu.cid = c.cid')
            ->where(function ($query) use ($where) {
                if (!empty($where['CustomerName'])) $query->where('c.CustomerName','in', $where['CustomerName']);
                if (!empty($where['State'])) $query->where('c.State', $where['State']);
                if (!empty($where['Region'])) $query->where('c.RegionId', $where['Region']);
                if (!empty($where['SendGoodsGroup'])) $query->where('c.SendGoodsGroup', $where['SendGoodsGroup']);
                if (!empty($where['City'])) $query->where('c.City', $where['City']);
                if (!empty($where['liable'])) $query->where('c.liable', $where['liable']);
                $query->where(1);
            })
            ->where('c.RegionId','<>',55)->count();

            $list = $this->customers
            ->field('c.CustomerId,c.CustomerName,c.State,c.City,c.SendGoodsGroup,cr.Region,c.dudao,c.cid')
            ->field(['cu.City'=>'BdCity'])
            ->alias('c')
            ->leftJoin('customers_region cr','c.RegionId = cr.RegionId')
            ->leftJoin('city_url cu','cu.cid = c.cid')
            ->where(function ($query) use ($where) {
                if (!empty($where['CustomerName'])) $query->where('c.CustomerName','in', $where['CustomerName']);
                if (!empty($where['State'])) $query->where('c.State', $where['State']);
                if (!empty($where['Region'])) $query->where('c.RegionId', $where['Region']);
                if (!empty($where['SendGoodsGroup'])) $query->where('c.SendGoodsGroup', $where['SendGoodsGroup']);
                if (!empty($where['City'])) $query->where('c.City', $where['City']);
                if (!empty($where['liable'])) $query->where('c.liable', $where['liable']);
                $query->where(1);
            })
            ->where('c.RegionId','<>',55)
            ->order('State asc,Region asc')
            ->page($page, $limit)
            ->select();


            // 获取日期列表
            $dateList = $this->getDateList(1);
            $list = $list->toArray();
            if(!empty($list)){
                $cid_list = array_column($list,'cid');
                // 查询天气
                $weather_list = $this->model->field('cid,id,min_c,max_c,weather_time,temperature')->whereIn('cid',$cid_list)->where('weather_time','in',array_values($dateList))->select();
                foreach ($weather_list as $kk => $vv){
                    foreach ($list as $k => $v){
                        if($vv['cid'] == $v['cid']){
                            $key = date('m-d',strtotime($vv['weather_time']));
                            $list[$k][$key] = $vv['min_c'].' ~ '.$vv['max_c'].'℃';
                        }
                    }
                }
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @param int $type 返回格式 0 天数 1 Y-m-d
     * @return array
     */
    public function getDateList($type = 0)
    {
        $str = 'Y-m-d';
        if($type == 0){
            $str = 'm-d';
        }
        // 开始日期
        $start_date = date('Y-m-d',strtotime(date('Y-m-d').'-3day'));
        // 日期列表
        $date_list = [];
        for ($i = 0;$i <= 17;$i++){
            $date_list[] = date($str,strtotime($start_date."+{$i}day"));
        }
        return $date_list;
    }

    /**
     * 获取天气日期字段列
     */
    public function getWeatherField()
    {
        // 日期列表
        $list = $this->getDateList(0);
        // 店铺信息列表
        $info_list = $this->customers->where('RegionId','<>',55)->column('State,City,CustomerName,RegionId');
        // 区域列表
        $area_list = [];
        // 省列表
        $province_list = [];
        // 门店列表
        $store_list = [];
        // 城市列表
        $city_list = [];
        // 分别取出,省列表,区域列表,城市列表用作筛选条件
        if(!empty($info_list)){
            $area_list_temp = array_unique(array_column($info_list,'RegionId'));
            $area_list = Region::whereIn('RegionId',$area_list_temp)->column('Region','RegionId');
            $province_list_temp = array_unique(array_column($info_list,'State'));
            $province_list = array_combine($province_list_temp,$province_list_temp);
            $city_list_temp = array_unique(array_column($info_list,'City'));
            $city_list = array_combine($city_list_temp,$city_list_temp);
            $store_list_temp = array_unique(array_column($info_list,'CustomerName'));
            $store_list = array_combine($store_list_temp,$store_list_temp);
        }
        // 省列表
        // 区域列表
        $data = [
                'code'  => 1,
                'msg'   => '',
                'province_list'  => $province_list,
                'area_list'  => $area_list,
                'store_list'  => $store_list,
                'city_list'  => $city_list,
                'data'  => $list
            ];
        return json($data);
    }

    /**
     * 店铺绑定城市
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function city()
    {
        // 获取店铺ID
        $id = $this->request->get('CustomerId');
        $city_model = new CityUrl;
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $city_id = $post['city']??0;
            if(empty($city_id)){
                $this->error('请选择绑定城市');
            }
            // 查询城市
            $city_cid = $city_model->where(['cid' => $city_id])->value('cid');
            if(empty($city_cid)){
                $this->error('城市不存在');
            }
            // 执行绑定
            $res = $this->customers->where([
                'CustomerId' => $id
            ])->update(['cid' => $city_cid]);
            // 绑定城市后,更新该城市的天气数据
            (new WeatherService)->updateCityWeather($city_id);
            if($res){
                // 绑定城市
                $this->success('绑定成功');
            }
            $this->error('绑定失败');
        }

        // 查询店铺记录
        $customer = $this->customers->field('CustomerName,State,City,cid')->where(['CustomerId' => $id])->find();
        if(empty($customer)){
            $this->error('记录不存在');
        }
        // 提取店铺城市关键字
        $keywords = [];
        foreach (['CustomerName','City'] as $k=>$v){
            if(empty($customer[$v])) continue;
            switch ($v){
                case 'CustomerName':
                    $keywords[$v] = mb_substr($customer[$v],0,-2).'%';
                    break;
                case 'City':
                    $keywords[$v] = mb_substr($customer[$v],0,2).'%';
                    break;
            }
        }
        // 查询匹配店铺的城市列表
        $city_list = $city_model->where(function ($q)use($keywords){
            if(!empty($keywords)) $q->where('city', 'like',$keywords,'OR');
        })->order('cid','desc')->column('cid,city,province');
        // 给城市加上省前缀
        foreach ($city_list as $kk => $vv){
            $prefix = mb_substr($vv['province'],0,-7);
            $city_list[$kk]['city'] = $prefix.'---'.$city_list[$kk]['city'];
        }
        $this->assign([
            'city_list' => $city_list,
            'cid' => $customer['cid'] ?? 0
        ]);
        return $this->fetch();
    }


}
