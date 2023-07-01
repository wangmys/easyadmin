<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\service\CusWeatherService;
use jianyan\excel\Excel;
use think\route\Domain;

/**
 * Class CusWeather
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="店铺天气")
 */
class CusWeather extends AdminController
{
    protected $create_time = '';

    protected $service;
    protected $db_cus_weather;

    public function __construct()
    {
        $this->create_time = date('Y-m-d H:i:s', time());
        
        $this->service = new CusWeatherService();
        $this->db_cus_weather = Db::connect('tianqi');
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {

        $customer_name = $this->db_cus_weather->query("select customer_name as name, customer_name as value from cus_weather_base where weather_prefix!='' group by customer_name;");
        $province = $this->db_cus_weather->query("select province as name, province as value from cus_weather_base where weather_prefix!='' group by province;");
        $city = $this->db_cus_weather->query("select city as name, city as value from cus_weather_base where weather_prefix!='' group by city;");
        $area = $this->db_cus_weather->query("select area as name, area as value from cus_weather_base where weather_prefix!='' group by area;");
        $store_type = $this->db_cus_weather->query("select store_type as name, store_type as value from cus_weather_base where weather_prefix!='' and store_type<>'' group by store_type;");
        $wendai = $this->db_cus_weather->query("select wendai as name, wendai as value from cus_weather_base where weather_prefix!='' and wendai<>'' group by wendai;");
        $wenqu = $this->db_cus_weather->query("select wenqu as name, wenqu as value from cus_weather_base where weather_prefix!='' and wenqu<>'' group by wenqu;");
        $goods_manager = $this->db_cus_weather->query("select goods_manager as name, goods_manager as value from cus_weather_base where weather_prefix!='' and goods_manager<>'' group by goods_manager;");
        $yuncang = $this->db_cus_weather->query("select yuncang as name, yuncang as value from cus_weather_base where weather_prefix!='' and yuncang<>'' group by yuncang;");
        $store_level = $this->db_cus_weather->query("select store_level as name, store_level as value from cus_weather_base where weather_prefix!='' and store_level<>'' group by store_level;");
        $nanzhongbei = $this->db_cus_weather->query("select nanzhongbei as name, nanzhongbei as value from cus_weather_base where weather_prefix!='' and nanzhongbei<>'' group by nanzhongbei;");

        return json(["code" => "0", "msg" => "", "data" => ['customer_name' => $customer_name, 'province' => $province, 'city' => $city, 'area' => $area, 'store_type' => $store_type,
        'wendai' => $wendai, 'wenqu' => $wenqu, 'goods_manager' => $goods_manager, 'yuncang' => $yuncang, 'store_level' => $store_level, 'nanzhongbei' => $nanzhongbei
        ]]);
        
    }

    /**
     * @NodeAnotation(title="店铺天气") 
     */
    public function cus_weather() {

        if (request()->isAjax()) {

            $params = input();
            $data = $this->service->get_cus_weather($params);

            return json(["code" => "0", "msg" => "", "count" => $data['count'], "data" => $data['data'],  'create_time' => date('Y-m-d')]);
        } else {
            return View('system/cus_weather/cus_weather', [

            ]);
        }        
    }

    //excel导出
    public function excel_cus_weather() {

        if (request()->isAjax()) {
            
            $params = input();
            $code = rand_code(6);
            cache($code, json_encode($params), 36000);
            $count = $this->service->get_cus_weather_count($params);
            if ($count > config('weather.init_output_num')) {
                $select = $this->service->get_cus_weather_excel($code, $params, 'cwb.customer_name, cwb.province, cwb.city, cwb.area, cwb.store_type, cwb.wendai, cwb.wenqu, cwb.goods_manager, cwb.yuncang, cwb.store_level, cwb.nanzhongbei,  cwd.min_c, cwd.max_c, cwd.weather_time');
            }

            return json([
                'app_domain' => env('app.APP_DOMAIN'),
                'init_output_num' => config('weather.init_output_num'),
                'status' => 1,
                'code' => $code,
                'count' => $count
            ]);

        } else {

            ini_set('memory_limit','1024M');

            $code = input('code');
            $params = cache($code);
            $params = $params ? json_decode($params, true) : [];

            $header = [
                ['店铺名称', 'customer_name'],
                ['省', 'province'],
                ['市', 'city'],
                ['区', 'area'],
                ['经营模式', 'store_type'],
                ['温带', 'wendai'],
                ['温区', 'wenqu'],
                ['商品负责人', 'goods_manager'],
                ['云仓', 'yuncang'],
                ['店铺等级', 'store_level'],
                ['南中北', 'nanzhongbei'],
                ['最低温', 'min_c'],
                ['最高温', 'max_c'],
                ['日期', 'weather_time'],
            ];

            $params['limit'] = 100000000;
            $select = $this->service->get_cus_weather_excel($code, $params, 'cwb.customer_name, cwb.province, cwb.city, cwb.area, cwb.store_type, cwb.wendai, cwb.wenqu, cwb.goods_manager, cwb.yuncang, cwb.store_level, cwb.nanzhongbei,  cwd.min_c, cwd.max_c, cwd.weather_time');
            if ($select['sign'] == 'normal') {
                return Excel::exportData($select['data'], $header, 'customer_weather_' .$select['count'] , 'xlsx');
            } else {//其他方案导出
            }
            
        }

    }

}
