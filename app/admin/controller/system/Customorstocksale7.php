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
        库存占比：二级分类的库存成本金额 / 整个店的成本金额
        销存比：  流水占比/库存占比
        折率：销售金额/零售金额
        店均销：前七天销售数量
        店均库量：前七天库存数量
        金额周转天：库存成本金额 / 销售成本金额（前七天成本金额）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 某省所有店铺
            $res1 = $this->model::where([
                '省份' => '广东省'
            ])
            ->field('省份,气温区域,经营模式,店铺名称,店铺等级,一级分类,二级分类')
            // ->limit(1000)
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

                // 总成本金额
                $res2_7cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前七天成本金额');
                $res2_6cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前六天成本金额');
                $res2_5cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前五天成本金额');
                $res2_4cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前四天成本金额');
                $res2_3cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前三天成本金额');
                $res2_2cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前二天成本金额');
                $res2_1cbje = $this->model::where(['店铺名称' => $value['店铺名称']])->sum('前一天成本金额');
                $res1[$key]['总店前七天成本金额'] = $res2_7cbje;
                $res1[$key]['总店前六天成本金额'] = $res2_6cbje;
                $res1[$key]['总店前五天成本金额'] = $res2_5cbje;
                $res1[$key]['总店前四天成本金额'] = $res2_4cbje;
                $res1[$key]['总店前三天成本金额'] = $res2_3cbje;
                $res1[$key]['总店前二天成本金额'] = $res2_2cbje;
                $res1[$key]['总店前一天成本金额'] = $res2_1cbje;
            }

            // dump($res1);

            $res3 = $this->model::where([
                '省份' => '广东省'
            ])
            ->field('省份,气温区域,经营模式,店铺名称,店铺等级,一级分类,二级分类,前七天销售数量,前六天销售数量,前五天销售数量,前四天销售数量,前三天销售数量,前二天销售数量,前一天销售数量,
            前七天库存数量,前六天库存数量,前五天库存数量,前四天库存数量,前三天库存数量,前二天库存数量,前一天库存数量,前七天库存成本,前六天库存成本,前五天库存成本,前四天库存成本,前三天库存成本,前二天库存成本,
            前一天库存成本,前七天成本金额,前六天成本金额,前五天成本金额,前四天成本金额,前三天成本金额,前二天成本金额,前一天成本金额,前七天销售金额,前六天销售金额,前五天销售金额,前四天销售金额,前三天销售金额,
            前二天销售金额,前一天销售金额,前七天零售金额,前六天零售金额,前五天零售金额,前四天零售金额,前三天零售金额,前二天零售金额,前一天零售金额,ifnull((前七天库存成本/前七天成本金额), 0) as 前七天金额周转天,
            ifnull((前六天库存成本/前六天成本金额), 0) as 前六天金额周转天,ifnull((前五天库存成本/前五天成本金额),0) as 前五天金额周转天,ifnull((前四天库存成本/前四天成本金额),0) as 前四天金额周转天,
            ifnull((前三天库存成本/前三天成本金额), 0) as 前三天金额周转天,ifnull((前二天库存成本/前二天成本金额), 0) as 前二天金额周转天, ifnull((前一天库存成本/前一天成本金额), 0) as 前一天金额周转天')
            ->limit(1000)
            ->select()
            ->toArray();

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

                $res3[$key]['前七天库存占比'] = $this->zeroHandle($res3[$key]['前七天库存成本'], $res3[$key]['总店前七天成本金额']);
                $res3[$key]['前六天库存占比'] = $this->zeroHandle($res3[$key]['前六天库存成本'], $res3[$key]['总店前六天成本金额']);
                $res3[$key]['前五天库存占比'] = $this->zeroHandle($res3[$key]['前五天库存成本'], $res3[$key]['总店前五天成本金额']);
                $res3[$key]['前四天库存占比'] = $this->zeroHandle($res3[$key]['前四天库存成本'], $res3[$key]['总店前四天成本金额']);
                $res3[$key]['前三天库存占比'] = $this->zeroHandle($res3[$key]['前三天库存成本'], $res3[$key]['总店前三天成本金额']);
                $res3[$key]['前二天库存占比'] = $this->zeroHandle($res3[$key]['前二天库存成本'], $res3[$key]['总店前二天成本金额']);
                $res3[$key]['前一天库存占比'] = $this->zeroHandle($res3[$key]['前一天库存成本'], $res3[$key]['总店前一天成本金额']);

                $res3[$key]['前七天销存比'] = $this->zeroHandle($res3[$key]['前七天流水占比'], $res3[$key]['前七天库存占比']);
                $res3[$key]['前六天销存比'] = $this->zeroHandle($res3[$key]['前六天流水占比'], $res3[$key]['前六天库存占比']);
                $res3[$key]['前五天销存比'] = $this->zeroHandle($res3[$key]['前五天流水占比'], $res3[$key]['前五天库存占比']);
                $res3[$key]['前四天销存比'] = $this->zeroHandle($res3[$key]['前四天流水占比'], $res3[$key]['前四天库存占比']);
                $res3[$key]['前三天销存比'] = $this->zeroHandle($res3[$key]['前三天流水占比'], $res3[$key]['前三天库存占比']);
                $res3[$key]['前二天销存比'] = $this->zeroHandle($res3[$key]['前二天流水占比'], $res3[$key]['前二天库存占比']);
                $res3[$key]['前一天销存比'] = $this->zeroHandle($res3[$key]['前一天流水占比'], $res3[$key]['前一天库存占比']);

                $res3[$key]['前七天折率'] = $this->zeroHandle($res3[$key]['前七天销售金额'], $res3[$key]['前七天零售金额']);
                $res3[$key]['前六天折率'] = $this->zeroHandle($res3[$key]['前六天销售金额'], $res3[$key]['前六天零售金额']);
                $res3[$key]['前五天折率'] = $this->zeroHandle($res3[$key]['前五天销售金额'], $res3[$key]['前五天零售金额']);
                $res3[$key]['前四天折率'] = $this->zeroHandle($res3[$key]['前四天销售金额'], $res3[$key]['前四天零售金额']);
                $res3[$key]['前三天折率'] = $this->zeroHandle($res3[$key]['前三天销售金额'], $res3[$key]['前三天零售金额']);
                $res3[$key]['前二天折率'] = $this->zeroHandle($res3[$key]['前二天销售金额'], $res3[$key]['前二天零售金额']);
                $res3[$key]['前一天折率'] = $this->zeroHandle($res3[$key]['前一天销售金额'], $res3[$key]['前一天零售金额']);
            }  
            // 前七天销售总额
            // echo '<pre>';
            // print_r($res3);
            // die;
            $data = [
                'code'  => 0,
                'msg'   => 'success',
                'count' => count($res3),
                'data'  => $res3
            ];
            return json($data);
        }
        return $this->fetch();
    }

    public function getField() {
        return json([
            'code' => 1,
            'msg'  => '',
            'data' => ['前七天销售数量','前六天销售数量','前五天销售数量','前四天销售数量','前三天销售数量', '前二天销售数量','前一天销售数量','前七天库存数量','前六天库存数量',
            '前五天库存数量','前四天库存数量','前三天库存数量','前二天库存数量','前一天库存数量','前七天库存成本','前六天库存成本','前五天库存成本','前四天库存成本','前三天库存成本','前二天库存成本',
            '前一天库存成本','前七天成本金额','前六天成本金额','前五天成本金额','前四天成本金额', '前三天成本金额','前二天成本金额','前一天成本金额','前七天销售金额','前六天销售金额','前五天销售金额',
            '前四天销售金额','前三天销售金额','前二天销售金额','前一天销售金额','前七天零售金额','前六天零售金额','前五天零售金额','前四天零售金额','前三天零售金额','前二天零售金额','前一天零售金额',
            '前七天金额周转天','前六天金额周转天','前三天金额周转天','前二天金额周转天','前一天金额周转天']
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

                $arr['总店前七天成本金额'] = $value['总店前七天成本金额'];
                $arr['总店前六天成本金额'] = $value['总店前六天成本金额'];
                $arr['总店前五天成本金额'] = $value['总店前五天成本金额'];
                $arr['总店前四天成本金额'] = $value['总店前四天成本金额'];
                $arr['总店前三天成本金额'] = $value['总店前三天成本金额'];
                $arr['总店前二天成本金额'] = $value['总店前二天成本金额'];
                $arr['总店前一天成本金额'] = $value['总店前一天成本金额'];
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
            return $num1 / $num2;
        }
    } 


}
