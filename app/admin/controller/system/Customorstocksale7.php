<?php


namespace app\admin\controller\system;

use app\admin\model\weather\Weather as WeatherM;
use app\admin\model\weather\Customers as CustomersM;
use app\admin\model\Customorstocksale7 as Customorstocksale7M;
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
 * Class CustomorStockSale7
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="客户库存出货")
 */
class Customorstocksale7 extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Customorstocksale7M;
        
    }

    /**
     * @NodeAnotation(title="客户库存出货")
     * select a.省份,a.气温区域,a.经营模式,a.店铺名称,a.店铺等级,a.一级分类,a.二级分类,
        (a.前七天销售金额 / (select sum(`前七天销售金额`) from sp_customer_stock_sale_7day as b where b.店铺名称=a.店铺名称 )) as 前七天流水占比,
        (a.前七天库存成本 / (select sum(`前七天成本金额`) from sp_customer_stock_sale_7day as b where b.店铺名称=a.店铺名称 )) as 前七天库存占比,
        (a.前七天销售金额 / (select sum(`前七天销售金额`) from sp_customer_stock_sale_7day as b where b.店铺名称=a.店铺名称 )) / (a.前七天库存成本 / (select sum(`前七天库存成本`) from sp_customer_stock_sale_7day as b where b.店铺名称=a.店铺名称 )) as 前七天销存比,
        a.前七天销售金额 / a.前七天零售金额 as 前七天折率,
        前七天销售数量  as 前七天店均销,
        前七天库存数量  as 前七天店均库量
        from sp_customer_stock_sale_7day as a  where 省份='青海省'  limit 5

        select  IFNULL(`前七天库存数量`,0) as 前七天库存数量, `店铺名称` from sp_customer_stock_sale_7day where `店铺名称`='海口二店'

        流水占比：二级分类的销售金额/整个店的销售金额
        库存占比：二级分类的库存成本金额 / 整个店的库存成本
        销存比：  流水占比/库存占比
        折率：销售金额/零售金额
        店均销：前七天销售数量
        店均库量：前七天库存数量
        金额周转天：库存成本金额 / 销售成本金额（前七天成本金额）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
        // if (1) {
            list($page, $limit, $params) = $this->buildTableParames();
            
            // 某省所有店铺
            $res1 = $this->model::where(
                // $this->getParms()
                $params
            )->field('省份,店铺名称')
            ->group('店铺名称')
            ->select()
            ->toArray();

            // 销售总金额 、库存总成本
            foreach($res1 as $key => $value) {
                // 销售总金额 
                $res2_7xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前七天销售金额');
                $res2_6xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前六天销售金额');
                $res2_5xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前五天销售金额');
                $res2_4xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前四天销售金额');
                $res2_3xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前三天销售金额');
                $res2_2xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前二天销售金额');
                $res2_1xsje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前一天销售金额');
                $res1[$key]['总店前七天销售金额'] = $res2_7xsje;
                $res1[$key]['总店前六天销售金额'] = $res2_6xsje;
                $res1[$key]['总店前五天销售金额'] = $res2_5xsje;
                $res1[$key]['总店前四天销售金额'] = $res2_4xsje;
                $res1[$key]['总店前三天销售金额'] = $res2_3xsje;
                $res1[$key]['总店前二天销售金额'] = $res2_2xsje;
                $res1[$key]['总店前一天销售金额'] = $res2_1xsje;
                $res1[$key]['总店近一周销售金额'] = $res2_1xsje + $res2_2xsje + $res2_3xsje + $res2_4xsje + $res2_5xsje + $res2_6xsje + $res2_7xsje;

                // 总成本金额 前七天库存成本
                $res2_7cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前七天库存成本');
                $res2_6cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前六天库存成本');
                $res2_5cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前五天库存成本');
                $res2_4cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前四天库存成本');
                $res2_3cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前三天库存成本');
                $res2_2cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前二天库存成本');
                $res2_1cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前一天库存成本');
                $res1[$key]['总店前七天库存成本'] = $res2_7cbje;
                $res1[$key]['总店前六天库存成本'] = $res2_6cbje;
                $res1[$key]['总店前五天库存成本'] = $res2_5cbje;
                $res1[$key]['总店前四天库存成本'] = $res2_4cbje;
                $res1[$key]['总店前三天库存成本'] = $res2_3cbje;
                $res1[$key]['总店前二天库存成本'] = $res2_2cbje;
                $res1[$key]['总店前一天库存成本'] = $res2_1cbje;
                $res1[$key]['总店近一周库存成本'] = $res2_1cbje + $res2_2cbje + $res2_3cbje + $res2_4cbje + $res2_5cbje + $res2_6cbje + $res2_7cbje;
            }

            $res3 = $this->model::where(
                // $this->getParms()
                $params
            )
            ->field('省份,气温区域,风格,季节,分类,经营模式,店铺名称,店铺等级,一级分类,二级分类,前七天销售数量 as 前七天店均销,前六天销售数量 as 前六天店均销,前五天销售数量 as 前五天店均销,前四天销售数量 as 前四天店均销,
            前三天销售数量 as 前三天店均销,前二天销售数量 as 前二天店均销,前一天销售数量 as 前一天店均销, ((前一天库存数量 + 前二天库存数量 + 前三天库存数量 + 前四天库存数量 + 前五天库存数量 + 前六天库存数量 + 前七天库存数量)/7) as 近一周店均库存,
            ((前七天销售数量+前六天销售数量+前五天销售数量+前四天销售数量+前三天销售数量+前二天销售数量+前一天销售数量) / 7) as 近一周店均销,
            前七天库存数量 as 前七天店均库存,前六天库存数量 as 前六天店均库存,前五天库存数量 as 前五天店均库存,前四天库存数量 as 前四天店均库存,前三天库存数量 as 前三天店均库存,前二天库存数量 as 前二天店均库存,
            前一天库存数量 as 前一天店均库存, 前七天库存成本,前六天库存成本,前五天库存成本,前四天库存成本,前三天库存成本,前二天库存成本,
            前一天库存成本,前七天成本金额,前六天成本金额,前五天成本金额,前四天成本金额,前三天成本金额,前二天成本金额,前一天成本金额,前七天销售金额,前六天销售金额,前五天销售金额,前四天销售金额,前三天销售金额,
            前二天销售金额,前一天销售金额,前七天零售金额,前六天零售金额,前五天零售金额,前四天零售金额,前三天零售金额,前二天零售金额,前一天零售金额,ifnull((前七天库存成本/前七天成本金额), 0) as 前七天金额周转天,
            ifnull((前六天库存成本/前六天成本金额), 0) as 前六天金额周转天,ifnull((前五天库存成本/前五天成本金额),0) as 前五天金额周转天,ifnull((前四天库存成本/前四天成本金额),0) as 前四天金额周转天,
            ifnull((前三天库存成本/前三天成本金额), 0) as 前三天金额周转天,ifnull((前二天库存成本/前二天成本金额), 0) as 前二天金额周转天, ifnull((前一天库存成本/前一天成本金额), 0) as 前一天金额周转天,
            ifnull(((前一天库存成本+前二天库存成本+前三天库存成本+前四天库存成本+前五天库存成本+前六天库存成本+前七天库存成本)/(前一天成本金额+前二天成本金额+前三天成本金额+前四天成本金额+前五天成本金额+前六天成本金额+前七天成本金额)), 0) as 近一周金额周转天,
            预计库存 as 昨日门店预计库存')
            // ->limit(1000)
            ->page($page, $limit)
            ->select()
            ->toArray();

            $count = $this->model::where(
                // $this->getParms()
                $params
            )->count();

            // 合并总店数据：库存成本，销售金额
            foreach($res3 as $key => $value) {
                $addArr = $this->pingRes1($res1, $value['店铺名称']);
                // 合并
                $res3[$key] = array_merge($res3[$key], $addArr);

                // 计算
                $res3[$key]['前七天流水占比'] = $this->zeroHandle($res3[$key]['前七天销售金额'], $res3[$key]['总店前七天销售金额']);
                $res3[$key]['前六天流水占比'] = $this->zeroHandle($res3[$key]['前六天销售金额'], $res3[$key]['总店前六天销售金额']);
                $res3[$key]['前五天流水占比'] = $this->zeroHandle($res3[$key]['前五天销售金额'], $res3[$key]['总店前五天销售金额']);
                $res3[$key]['前四天流水占比'] = $this->zeroHandle($res3[$key]['前四天销售金额'], $res3[$key]['总店前四天销售金额']);
                $res3[$key]['前三天流水占比'] = $this->zeroHandle($res3[$key]['前三天销售金额'], $res3[$key]['总店前三天销售金额']);
                $res3[$key]['前二天流水占比'] = $this->zeroHandle($res3[$key]['前二天销售金额'], $res3[$key]['总店前二天销售金额']);
                $res3[$key]['前一天流水占比'] = $this->zeroHandle($res3[$key]['前一天销售金额'], $res3[$key]['总店前一天销售金额']);
                $res3[$key]['近一周流水占比'] = $this->zeroHandle($res3[$key]['前一天销售金额'] + $res3[$key]['前二天销售金额'] + $res3[$key]['前三天销售金额'] + $res3[$key]['前四天销售金额'] + $res3[$key]['前五天销售金额'] + $res3[$key]['前六天销售金额'] + $res3[$key]['前七天销售金额'], $res3[$key]['总店近一周销售金额']);

                $res3[$key]['前七天库存占比'] = $this->zeroHandle($res3[$key]['前七天库存成本'], $res3[$key]['总店前七天库存成本']);
                $res3[$key]['前六天库存占比'] = $this->zeroHandle($res3[$key]['前六天库存成本'], $res3[$key]['总店前六天库存成本']);
                $res3[$key]['前五天库存占比'] = $this->zeroHandle($res3[$key]['前五天库存成本'], $res3[$key]['总店前五天库存成本']);
                $res3[$key]['前四天库存占比'] = $this->zeroHandle($res3[$key]['前四天库存成本'], $res3[$key]['总店前四天库存成本']);
                $res3[$key]['前三天库存占比'] = $this->zeroHandle($res3[$key]['前三天库存成本'], $res3[$key]['总店前三天库存成本']);
                $res3[$key]['前二天库存占比'] = $this->zeroHandle($res3[$key]['前二天库存成本'], $res3[$key]['总店前二天库存成本']);
                $res3[$key]['前一天库存占比'] = $this->zeroHandle($res3[$key]['前一天库存成本'], $res3[$key]['总店前一天库存成本']);
                $res3[$key]['近一周库存占比'] = $this->zeroHandle($res3[$key]['前一天库存成本'] + $res3[$key]['前二天库存成本'] + $res3[$key]['前三天库存成本'] + $res3[$key]['前四天库存成本'] + $res3[$key]['前五天库存成本'] + $res3[$key]['前六天库存成本'] + $res3[$key]['前七天库存成本'], $res3[$key]['总店近一周库存成本']);

                $res3[$key]['前七天销存占比'] = $this->zeroHandle($res3[$key]['前七天流水占比'], $res3[$key]['前七天库存占比']);
                $res3[$key]['前六天销存占比'] = $this->zeroHandle($res3[$key]['前六天流水占比'], $res3[$key]['前六天库存占比']);
                $res3[$key]['前五天销存占比'] = $this->zeroHandle($res3[$key]['前五天流水占比'], $res3[$key]['前五天库存占比']);
                $res3[$key]['前四天销存占比'] = $this->zeroHandle($res3[$key]['前四天流水占比'], $res3[$key]['前四天库存占比']);
                $res3[$key]['前三天销存占比'] = $this->zeroHandle($res3[$key]['前三天流水占比'], $res3[$key]['前三天库存占比']);
                $res3[$key]['前二天销存占比'] = $this->zeroHandle($res3[$key]['前二天流水占比'], $res3[$key]['前二天库存占比']);
                $res3[$key]['前一天销存占比'] = $this->zeroHandle($res3[$key]['前一天流水占比'], $res3[$key]['前一天库存占比']);
                $res3[$key]['近一周销存占比'] = $this->zeroHandle($res3[$key]['近一周流水占比'], $res3[$key]['近一周库存占比']);

                $res3[$key]['前七天折率'] = $this->zeroHandle($res3[$key]['前七天销售金额'], $res3[$key]['前七天零售金额']);
                $res3[$key]['前六天折率'] = $this->zeroHandle($res3[$key]['前六天销售金额'], $res3[$key]['前六天零售金额']);
                $res3[$key]['前五天折率'] = $this->zeroHandle($res3[$key]['前五天销售金额'], $res3[$key]['前五天零售金额']);
                $res3[$key]['前四天折率'] = $this->zeroHandle($res3[$key]['前四天销售金额'], $res3[$key]['前四天零售金额']);
                $res3[$key]['前三天折率'] = $this->zeroHandle($res3[$key]['前三天销售金额'], $res3[$key]['前三天零售金额']);
                $res3[$key]['前二天折率'] = $this->zeroHandle($res3[$key]['前二天销售金额'], $res3[$key]['前二天零售金额']);
                $res3[$key]['前一天折率'] = $this->zeroHandle($res3[$key]['前一天销售金额'], $res3[$key]['前一天零售金额']);
                $res3[$key]['近一周折率'] = $this->zeroHandle($res3[$key]['前一天销售金额'] + $res3[$key]['前二天销售金额'] + $res3[$key]['前三天销售金额'] + $res3[$key]['前四天销售金额'] + $res3[$key]['前五天销售金额']
                + $res3[$key]['前六天销售金额'] + $res3[$key]['前七天销售金额'], $res3[$key]['前一天零售金额'] + $res3[$key]['前二天零售金额'] + $res3[$key]['前三天零售金额'] + $res3[$key]['前四天零售金额']
                + $res3[$key]['前五天零售金额'] + $res3[$key]['前六天零售金额'] + $res3[$key]['前七天零售金额']);
            }  
            // 前七天销售总额
            // echo '<pre>';
            // print_r($res3);
            // die;
            $data = [
                'code'  => 0,
                'msg'   => 'success',
                'count' => $count,
                'data'  => $res3
            ];
            return json($data);
        }
        // http://www.easyadmin1.com/admin/system.Customorstocksale7/index?page=1&limit=100&filter=%7B%22%E7%9C%81%E4%BB%BD%22%3A%22%E4%BA%91%E5%8D%97%E7%9C%81%2C%E5%9B%9B%E5%B7%9D%E7%9C%81%22%2C%22%E9%A3%8E%E6%A0%BC%22%3A%22%E5%BC%95%E6%B5%81%E6%AC%BE%22%2C%22%E5%AD%A3%E8%8A%82%22%3A%22%E5%86%AC%E5%AD%A3%2C%E5%88%9D%E5%86%AC%22%7D&op=%7B%22%E7%9C%81%E4%BB%BD%22%3A%22in%22%2C%22%E9%A3%8E%E6%A0%BC%22%3A%22%3D%22%2C%22%E5%AD%A3%E8%8A%82%22%3A%22in%22%7D
        // list($page, $limit, $params) = $this->buildTableParames();
        // dump($this->request->get());
        // dump($this->getParms());
        // dump($this->request->get());
        // die;
        // dump($params);


        // 某省所有店铺
        // $res1 = $this->model::whereIn(
        //     $this->getParms()
        // )
        // ->field('省份,店铺名称')
        // ->group('店铺名称')
        // ->select()
        // ->toArray();
        // dump($params);
        // die;
        return $this->fetch();
    }

    // 获取展示字段 
    public function getField() {
        $res1 = $this->model::where(1)->group('省份')->column('省份');
        $province_list = array_combine($res1, $res1);
        $res2 = $this->model::where(1)->group('气温区域')->column('气温区域');
        $air_temperature_list = array_combine($res2, $res2);
        $res3 = $this->model::where(1)->group('经营模式')->column('经营模式');
        $management_model_list = array_combine($res3, $res3);
        $res4 = $this->model::where(1)->group('店铺等级')->column('店铺等级');
        $grade_list = array_combine($res4, $res4);
        $res5 = $this->model::where(1)->group('一级分类')->column('一级分类');
        $level1_list = array_combine($res5, $res5);
        $res6 = $this->model::where(1)->group('二级分类')->column('二级分类');
        $level2_list = array_combine($res6, $res6);
        $res7 = $this->model::where(1)->group('店铺名称')->column('店铺名称');
        $store_list = array_combine($res7, $res7);
        $res8 = $this->model::where(1)->group('风格')->column('风格');
        $style_list = array_combine($res8, $res8);
        $res9 = $this->model::where(1)->group('季节')->column('季节');
        $season_list = array_combine($res9, $res9);
        // dump($res4 );die;
        return json([
            'code' => 1,
            'msg'  => '',
            'province_list' => $province_list,
            'air_temperature_list' => $air_temperature_list,
            'management_model_list' => $management_model_list,
            'grade_list' => $grade_list,
            'level1_list' => $level1_list,
            'level2_list' => $level2_list,
            'store_list' => $store_list,
            'style_list' => $style_list,
            'season_list' => $season_list,
            'data' => [
                '近一周流水占比','近一周库存占比','近一周销存占比','近一周折率','近一周店均销','近一周店均库存', '近一周金额周转天', 
                '前一天流水占比','前一天库存占比','前一天销存占比','前一天折率','前一天店均销','前一天店均库存', '昨日门店预计库存', '前一天金额周转天',
                '前二天流水占比','前二天库存占比','前二天销存占比','前二天折率','前二天店均销','前二天店均库存', '前二天金额周转天',
                '前三天流水占比','前三天库存占比','前三天销存占比','前三天折率','前三天店均销','前三天店均库存', '前三天金额周转天',
                '前四天流水占比','前四天库存占比','前四天销存占比','前四天折率','前四天店均销','前四天店均库存', '前四天金额周转天',
                '前五天流水占比','前五天库存占比','前五天销存占比','前五天折率','前五天店均销','前五天店均库存', '前五天金额周转天',
                '前六天流水占比','前六天库存占比','前六天销存占比','前六天折率','前六天店均销','前六天店均库存', '前六天金额周转天',
                '前七天流水占比','前七天库存占比','前七天销存占比','前七天折率','前七天店均销','前七天店均库存', '前七天金额周转天',
            //     '前五天销售数量','前四天销售数量','前三天销售数量', '前二天销售数量','前一天销售数量','前七天库存数量','前六天库存数量',
            // '前五天库存数量','前四天库存数量','前三天库存数量','前二天库存数量','前一天库存数量','前七天库存成本','前六天库存成本','前五天库存成本','前四天库存成本','前三天库存成本','前二天库存成本',
            // '前一天库存成本','前七天成本金额','前六天成本金额','前五天成本金额','前四天成本金额', '前三天成本金额','前二天成本金额','前一天成本金额','前七天销售金额','前六天销售金额','前五天销售金额',
            // '前四天销售金额','前三天销售金额','前二天销售金额','前一天销售金额','前七天零售金额','前六天零售金额','前五天零售金额','前四天零售金额','前三天零售金额','前二天零售金额','前一天零售金额',
            // '前七天金额周转天','前六天金额周转天','前三天金额周转天','前二天金额周转天','前一天金额周转天'
            ]
        ]);
    }

    // 返回商店信息 
    private function pingRes1($res1 = [], $dianpuName) {
        $arr = [];
        foreach($res1 as $key => $value) {
            if ($value['店铺名称'] == $dianpuName) {
                $arr['总店前七天销售金额'] = $value['总店前七天销售金额'];
                $arr['总店前六天销售金额'] = $value['总店前六天销售金额'];
                $arr['总店前五天销售金额'] = $value['总店前五天销售金额'];
                $arr['总店前四天销售金额'] = $value['总店前四天销售金额'];
                $arr['总店前三天销售金额'] = $value['总店前三天销售金额'];
                $arr['总店前二天销售金额'] = $value['总店前二天销售金额'];
                $arr['总店前一天销售金额'] = $value['总店前一天销售金额'];
                $arr['总店近一周销售金额'] = $value['总店近一周销售金额'];

                $arr['总店前七天库存成本'] = $value['总店前七天库存成本'];
                $arr['总店前六天库存成本'] = $value['总店前六天库存成本'];
                $arr['总店前五天库存成本'] = $value['总店前五天库存成本'];
                $arr['总店前四天库存成本'] = $value['总店前四天库存成本'];
                $arr['总店前三天库存成本'] = $value['总店前三天库存成本'];
                $arr['总店前二天库存成本'] = $value['总店前二天库存成本'];
                $arr['总店前一天库存成本'] = $value['总店前一天库存成本'];
                $arr['总店近一周库存成本'] = $value['总店前一天库存成本'];
                break;
            } 
        }  
        return $arr;
    }

    // 0除以任何数都得0
    private function zeroHandle($num1, $num2) {
        if ($num1 == 0 || $num2 == 0) {
            return 0;
        } else {
            $res = $num1 / $num2;
            // $res = sprintf("%.3f", $res);
            // $res = $this->precision_restore($num1, $num2, '除法');
            return $res;
        }
    } 

    /**
     * 这里我用这种方法是发现内置方法在foreach里有问题,所以用了这种方法
     * 思路就是将两个数乘以最大的那个小数位再根据运算符去除
     * @param $one
     * @param $two
     * @param $type
     * @return float|int|string
     */
    public function precision_restore($one, $two, $type){
        $one = (double)$one;
        $two = (double)$two;
        $one_dec = $this->sum_length($one);
        $two_dec = $this->sum_length($two);
        $sum = '';
        if($one_dec>$two_dec){
            $dec = $one_dec;
        }else{
            $dec = $two_dec;
        }
        $ten = pow(10,$dec);
        switch ($type) {
            case '加法':
                $sum = ($one*$ten + $two*$ten) / $ten;
                break;
            case '减法':
                $sum = ($one*$ten - $two*$ten) / $ten;
                break;
            case '乘法':
                $sum = (($one*$ten) * ($two*$ten)) / pow($ten,2);
                break;
            case '除法':
                $sum = ($one*$ten) / ($two*$ten);
                break;
            default:
                dd('参数错误');
        }
        return (string)$sum;
    }

    public function sum_length($num) {
        $count = 0;
        $temp = explode('.',$num);
        if (sizeof($temp) > 1) {
            $decimal = end($temp);
            $count = strlen($decimal);
        }
        return $count;
    }


    public function test() {
        // $sum = $this->model::where(['店铺名称' => '西宁二店'])->sum('前七天成本金额'); // 错误
        // $sum2 = $this->model::where(['店铺名称' => '西宁二店'])->sum('前七天库存成本'); // 正确
        $sum2 = $this->model::where(['店铺名称' => '西宁二店'])->sum('前六天销售金额'); // 
        // dump($sum);
        dump($sum2);

        $res = sprintf("%.8f", 0.041930618401207);
        dump($res);
    }


}
