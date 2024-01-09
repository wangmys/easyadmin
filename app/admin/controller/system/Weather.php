<?php


namespace app\admin\controller\system;

use app\admin\model\CustomerModel;
use app\admin\model\weather\Weather as WeatherM;
use app\admin\model\weather\Weather2345Model;
use app\common\model\MqxWeatherCustomer;
use app\common\model\MqxWeatherCode;
use app\common\model\MqxWeather;
use app\admin\model\weather\CityUrl;
use app\admin\model\weather\WeatherUpdateStatus2345Model;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use app\common\service\command\MqxWeatherService;
use app\common\service\WeatherService;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\Exception;
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
    protected $sort = [
        'sort' => 'desc',
        'id' => 'desc',
    ];
    const ProductMemberAuth = 7;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MqxWeather;
        $this->customers = new CustomerModel();
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

            $query = $this->customers
                ->field('c.CustomerId,c.CustomerName,c.CustomItem17,c.State,c.CustomItem30,c.CustomItem36,c.City,c.SendGoodsGroup,c.Mathod,wc1.area as BdCity,wc.code as weather_code')
                ->alias('c')
                ->leftJoin('easyadmin2.mqx_weather_customer wc', 'wc.CustomerId = c.CustomerId')
                ->leftJoin('easyadmin2.mqx_weather_code wc1', 'wc1.code = wc.code')
                ->where(function ($query) use ($where) {
                    if (!empty($where['CustomerName'])) $query->where('c.CustomerName', 'in', $where['CustomerName']);
                    if (!empty($where['State'])) $query->where('c.State', $where['State']);
                    if (!empty($where['Region'])) $query->where('c.RegionId', $where['Region']);
                    if (!empty($where['CustomItem17'])) $query->where('c.CustomItem17', $where['CustomItem17']);
                    if (!empty($where['CustomItem30'])) $query->where('c.CustomItem30', $where['CustomItem30']);
                    if (!empty($where['CustomItem36'])) $query->where('c.CustomItem36', $where['CustomItem36']);
                    if (!empty($where['liable'])) $query->whereIn('c.liable', $where['liable']);
                    if (!empty(input('商品专员'))) $query->whereIn('c.liable', input('商品专员'));
                    if (!empty(input('省份'))) $query->whereIn('c.State', input('省份'));
                    if (!empty(input('店铺名称'))) $query->whereIn('c.CustomerName', input('店铺名称'));
                    if (!empty(input('温区'))) $query->whereIn('c.CustomItem36', input('温区'));
                    if (!empty($where['Mathod'])) $query->whereIn('c.Mathod', $where['Mathod']);
                    if (!empty($where['CustomerGrade'])) $query->whereIn('c.CustomerGrade', $where['CustomerGrade']);
                    $query->where(1);
                })
                ->where(['c.ShutOut' => 0])
                ->order('c.State asc,c.CustomItem30 asc,c.CustomItem36 asc,c.CustomerName asc,c.CustomerCode asc');

            $count = $query->count();
            $list = $query->page($page, $limit)->select();
            // 获取日期列表
            $dateList = $this->getDateListM(1);
            $list = $list->toArray();

            if (!empty($list)) {

                $weather_code = array_column($list, 'weather_code');
                // 查询天气
                $weather_list = $this->model->field('*')->whereIn('code', $weather_code)->where('date', 'in', array_values($dateList))->select()->toArray();

                foreach ($weather_list as $kk => $vv) {
                    foreach ($list as $k => &$v) {

                        $v['State']=mb_substr($v['State'],0,2);
                        if ($vv['code'] == $v['weather_code']) {
                            $key = date('m-d', strtotime($vv['date']));
                            // 使用最高温
//                            if (in_array(date('m', strtotime($vv['date'])), [2, 3, 4, 5, 6, 7])) {
//                                $value_c = $vv['max_c'];
//                            } else {
                                // 使用最低温
                                // $value_c = $vv['min_c'];
                                $diff = $vv['max_c'] - $vv['min_c'];
                                if ($vv['max_c'] > 30) {
                                    $value_c = $vv['max_c'];
                                    // } elseif ($diff <= 5) {
                                } elseif ($diff <= 5 || $vv['max_c'] <= 18) { // 新增的 $vv['max_c'] <= 18
                                    $value_c = round(($vv['max_c'] + $vv['min_c']) / 2, 1);
                                } elseif ($diff > 5 && $diff <= 10) {
                                    $value_c = round(($vv['max_c'] + $vv['min_c']) / 2, 1) + 2;
                                } elseif ($diff > 10) {
                                     $value_c = round( ($vv['max_c']+$vv['min_c'])/2, 1 ) + 4;
                                }

//                            }


                            if ($value_c < 10) {
                                $bgCol = 'rgb(47,117,181)';
                                $fontCol = '#000000';
                            } else if ($value_c < 18) {
                                $bgCol = 'rgb(163,200,232)';
                                $fontCol = '#000000';
                            } else if ($value_c < 22) {
                                $bgCol = 'rgb(254,250,186)';
                                $fontCol = '#000000';
                            } else if ($value_c < 26) {
                                $bgCol = 'rgb(252,216,84)';
                                $fontCol = '#000000';
                            } else if ($value_c <= 30) {
                                $bgCol = 'rgb(251,184,5)';
                                $fontCol = '#000000';
                            } else if ($value_c > 30) {
                                $bgCol = 'rgb(239,33,33)';
                                $fontCol = '#000000';
                            } else {
                                $bgCol = '#fecc51';
                                $fontCol = '#000000';
                            }
//                             $list[$k][$key] = $vv['min_c'].' ~ '.$vv['max_c'].'℃';
//                             $list[$k]['_'.$key] = $bgCol;
                            $list[$k][$key] = "<span style='width: 100%;display: block; background:{$bgCol}; color:{$fontCol}' >" . $vv['min_c'] . '~' . $vv['max_c'] . "</span>";


                            // $list[$k][$key] = [
                            //     'min_c' => $vv['min_c'],
                            //     'max_c' => $vv['max_c']
                            // ];
                        }
                    }
                }
            }
            $data = [
                'code' => 0,
                'msg' => '',
                'today_date' => date('m-d'),
                'count' => $count,
                'data' => $list
            ];
            return json($data);
        }
        if (isMobile()) {
            // system.Shangguitips/weather_mobile
            $this->redirect(url('admin/system.Shangguitips/weather_mobile'));
            // return $this->fetch('system/shangguitips/weather_mobile');
        } else {
            return $this->fetch();
            // return $this->fetch('index_mobile2');
        }

    }

    /**
     * @param int $type 返回格式 0 天数 1 Y-m-d
     * @return array
     */
    public function getDateList($type = 0)
    {
        $str = 'Y-m-d';
        if ($type == 0) {
            $str = 'm-d';
        }
        // 开始日期
        $start_date = date('Y-m-d', strtotime(date('Y-m-d') . '-3day'));
        // 日期列表
        $date_list = [];
        for ($i = 0; $i <= 23; $i++) {
            $date_list[] = date($str, strtotime($start_date . "+{$i}day"));
        }
        return $date_list;
    }

    /**
     * @param int $type 返回格式 0 天数 1 Y-m-d
     * @return array
     */
    public function getDateListM($type = 0)
    {
        $str = 'Ymd';
        if ($type == 0) {
            $str = 'md';
        }
        // 开始日期
        $start_date = date('Ymd',strtotime(date('Ymd').'-3day'));
        // 日期列表
        $date_list = [];
        for ($i = 0; $i <= 23; $i++) {
            $date_list[] = date($str, strtotime($start_date . "+{$i}day"));
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
        $info_list = $this->customers->where('ShutOut', '=', 0)->column('State,CustomItem17,City,CustomerName,CustomItem30,CustomItem36,Mathod,CustomerGrade');
        // 区域列表
        $area_list = [];
        // 省列表
        $province_list = [];
        // 门店列表
        $store_list = [];
        // 城市列表
        $city_list = [];
        $mathod = [];
        // 分别取出,省列表,区域列表,城市列表用作筛选条件
        if (!empty($info_list)) {
            $province_list_temp = array_unique(array_column($info_list, 'State'));
            $province_list = array_combine($province_list_temp, $province_list_temp);
            $city_list_temp = array_unique(array_column($info_list, 'City'));
            $city_list = array_combine($city_list_temp, $city_list_temp);
            $store_list_temp = array_unique(array_column($info_list, 'CustomerName'));
            $store_list = array_combine($store_list_temp, $store_list_temp);
            // 温带
            $wendai_list_temp = array_unique(array_column($info_list, 'CustomItem30'));
            $wendai_list = array_combine($wendai_list_temp, $wendai_list_temp);
            // 气温区域
            $wenqu_list_temp = array_unique(array_column($info_list, 'CustomItem36'));
            $wenqu_list = array_combine($wenqu_list_temp, $wenqu_list_temp);
            // 商品负责人
            $liable_list_temp = array_unique(array_column($info_list, 'CustomItem17'));
            $liable_list = array_combine($liable_list_temp, $liable_list_temp);
            $liable_list = array_filter($liable_list, function ($value) {
                return !is_null($value) && !empty($value);
            });
            //经营模式
            $mathod = array_filter(array_unique(array_column($info_list, 'Mathod')));
            $mathod = array_combine($mathod, $mathod);
            //店铺等级
            $CustomerGrade = array_filter(array_unique(array_column($info_list, 'CustomerGrade')));
            $CustomerGrade = array_combine($CustomerGrade, $CustomerGrade);
        }

        //获取 绑定城市 字段权限
        $current_admin_id = session()['admin']['id'] ?? 0;
        $auth_ids = session()['admin']['auth_ids'] ?? 0;
        $auth_ids = $auth_ids ? explode(',', $auth_ids) : [];
        $if_can_see = 1;
        if (($current_admin_id != AdminConstant::SUPER_ADMIN_ID) && in_array(self::ProductMemberAuth, $auth_ids)) {
            $if_can_see = 0;
        }

        $last_update_time = WeatherUpdateStatus2345Model::where([])->field('update_time')->order('update_time desc')->find();

        //是否已绑网址
        $bang_url_list = ['已绑' => '已绑', '未绑' => '未绑'];

        // 省列表
        // 区域列表
        $data = [
            'code' => 1,
            'msg' => '',
            'province_list' => $province_list,
//                'area_list'  => $area_list,
            'store_list' => $store_list,
            'city_list' => $city_list,
            'wendai_list' => $wendai_list,
            'wenqu_list' => $wenqu_list,
            'liable_list' => $liable_list,
            'if_can_see' => $if_can_see,
            'mathod' => $mathod,
            'CustomerGrade' => $CustomerGrade,
            'last_update_time' => $last_update_time ? $last_update_time['update_time'] : '',
            'bang_url_list' => $bang_url_list,
            'data' => $list
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

            $wc=explode(',',$post['code']);
            if(count($wc)>1){
                $this->error('只能选一个地区');
            }

            $CustomerName = Db::connect('mysql')->table('customer')->where(['CustomerId' => $id])->value('CustomerName');
            $cc = Db::connect('mysql')->table('mqx_weather_customer')->where(['CustomerId' => $id])->find();
            $arr = [
                'CustomerId' => $id,
                'CustomerName' => $CustomerName,
                'code' => $post['code']
            ];
            if (empty($cc)) {
                 Db::connect('mysql')->table('mqx_weather_customer')->insert($arr);
            } else {
                 Db::connect('mysql')->table('mqx_weather_customer')->where(['CustomerId' => $id])->update($arr);
            }
            try {
                (new MqxWeatherService)->update_weather($post['code']);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }

            $this->success('绑定成功');

        }
        $customerCode = MqxWeatherCustomer::where(['CustomerId' => $id])->value('code');

        if (empty(cache('city_code'))) {
            $cityList = $this->citySon($customerCode);
            cache('city_code', $cityList, 3600 * 24 * 100);
        }
        $cityList = cache('city_code');

        foreach ($cityList as &$item) {
            foreach ($item['children'] as &$item_1) {
                foreach ($item_1['children'] as &$item_2) {
                    if ($item_2['value'] == $customerCode) {
                        $item_2['selected'] = true;
                    }

                }
            }
        }


        $this->assign([
            'city_list' => json_encode($cityList),
        ]);
        return $this->fetch();
    }


    public function citySon()
    {

        $cityList = MqxWeatherCode::where(1)->select()->toArray();
        $province = array_values(array_unique(array_column($cityList, 'province')));
        $res = [];
        foreach ($province as $key => $value) {
            $citys = MqxWeatherCode::where('province', $value)->group('city')->select()->toArray();
            $arr = [
                'name' => $value,
                'value' => $value,
            ];
            foreach ($citys as $city_v) {
                $cityA = [
                    'name' => $city_v['city'],
                    'value' => $city_v['code']
                ];
                $area = MqxWeatherCode::where(['city' => $city_v, 'province' => $value])->select()->toArray();
                foreach ($area as $area_v) {
                    $cityA['children'][] = [
                        'name' => $area_v['area'],
                        'value' => $area_v['code'],
                        'selected' => false
                    ];

                }
                $arr['children'][] = $cityA;

            }


            $res[] = $arr;


        }


        return $res;
    }


    /**
     * 店铺绑定天气网址链接
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tianqi_url()
    {
        // 获取店铺ID
        $id = $this->request->get('CustomerId');
        $city_model = new CityUrl;
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $url_2345 = $post['url_2345'] ? trim($post['url_2345']) : '';
            $url_2345 = $url_2345 ? str_replace(['https'], ['http'], $url_2345) : '';

            if ($url_2345) {
                //抓取该链接天气数据得到 所在城市名称
                $res = (new WeatherService)->getWeather15_2345_byurl($url_2345);
                // print_r($res);die;
                if ($res['add_data']) {

                    $res = $this->customers->where([
                        'CustomerId' => $id
                    ])->update(['url_2345' => $url_2345, 'url_2345_cid' => $res['cid']]);

                } else {

                    $this->error('绑定失败，请检查天气链接网址,必须使用2345天气网15天天气页面url');

                }

            } else {

                $res = $this->customers->where([
                    'CustomerId' => $id
                ])->update(['url_2345' => '', 'url_2345_cid' => 0]);

            }

            $this->success('绑定成功');
        }

        //查询店铺记录
        $customer = $this->customers->field('url_2345')->where(['CustomerId' => $id])->find();

        $this->assign([
            'url_2345' => $customer ? $customer['url_2345'] : ''
        ]);
        return $this->fetch();
    }

    /**
     * 根据温带获取温区
     * @return void
     */
    public function get_wenqu()
    {

        $wendai = input('wendai');
        $sql = "select distinct CustomItem36 from customers where CustomItem30='{$wendai}'";
        $res = Db::connect('tianqi')->query($sql);
        return json(["code" => "0", "msg" => "", "data" => $res]);

    }

    /**
     * 根据省份获取温带
     * @return void
     */
    public function get_wendai()
    {

        $province = input('province');
        $sql = "select distinct CustomItem30 from customers where State='{$province}' and CustomItem30!=''";
        $res = Db::connect('tianqi')->query($sql);
        $citys = $this->customers->where('RegionId', '<>', 55)->where('ShutOut', '=', 0)->where('State', '=', $province)->distinct(true)->column('City');

        return json(["code" => "0", "msg" => "", "data" => $res, 'citys' => $citys]);

    }


}
