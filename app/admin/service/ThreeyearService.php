<?php

//三年趋势 服务层
namespace app\admin\service;
use app\admin\model\bi\SpCustomerStockSaleWeekDateModel;
use app\admin\model\bi\SpCustomerStockSaleThreeyear2Model;
use app\admin\model\bi\SpCustomerStockSaleThreeyear2WeekModel;
use app\admin\model\bi\SpCustomerStockSaleThreeyear2WeekCacheModel;
use app\admin\model\weather\CusWeatherBase;
use app\admin\model\weather\CusWeatherData;
use app\common\traits\Singleton;
use think\facade\Db;

class ThreeyearService
{

    use Singleton;
    protected $easy_db;
    protected $threeyear2_week_model;
    protected $week_date_model;
    protected $weather_data_model;
    protected $weather_base_model;

    public function __construct() {
        $this->easy_db = Db::connect("mysql");
        $this->threeyear2_week_model = new SpCustomerStockSaleThreeyear2WeekModel();
        $this->week_date_model = new SpCustomerStockSaleWeekDateModel();
        $this->weather_data_model = new CusWeatherData();
        $this->weather_base_model = new CusWeatherBase();
    }

    public function index($params) {

        //test....
        // $weather_data_model = new CusWeatherData();
        // $where_weather_2021[] = ['d.weather_time', 'like', ['%2021-01%'], 'or'];
        // $field_weather = "CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' ) as Start_time, 
        // max(d.weather_time) as End_time,   b.customer_name,max(d.max_c) as max_c, min(d.min_c) as min_c, 
        // CONCAT(SUBSTRING(  CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' )  , 6, 5), ' 到 ', SUBSTRING( max(d.weather_time) , 6, 5)) as '周期'";
        // $weather_2021 = $this->weather_data_model::where($where_weather_2021)->alias('d')->field($field_weather)
        //     ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
        // $weather_2021 = $weather_2021 ? $weather_2021->toArray() : [];
        // print_r($weather_2021);die;

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $Year = $params['Year'] ?? '';
        $Month = $params['Month'] ?? '';
        $YunCang = $params['YunCang'] ?? '';
        $WenDai = $params['WenDai'] ?? '';
        $WenQu = $params['WenQu'] ?? '';
        $State = $params['State'] ?? '';
        $Mathod = $params['Mathod'] ?? '';
        $NewOld = $params['NewOld'] ?? '';
        $Season = $params['Season'] ?? '';
        $StyleCategoryName = $params['StyleCategoryName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $CategoryName2 = $params['CategoryName2'] ?? '';
        $CategoryName = $params['CategoryName'] ?? '';
        $CustomItem46 = $params['CustomItem46'] ?? '';//深浅色

        $week_dates = $this->week_date_model::where([['year', '=', '2023']])->field("year, week, start_time, end_time, CONCAT(SUBSTRING(start_time, 6, 5), ' 到 ', SUBSTRING(end_time, 6, 5)) as '周期'")->select();
        $week_dates = $week_dates ? $week_dates->toArray() : [];
        // print_r($week_dates);die;

        $field = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
        CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
        '100.0%' as '业绩占比', '100.0%' as '库存占比', '100.0%' as '效率', max(NUM) as '店铺数', sum(SaleQuantity) as '销量(周)', sum(StockQuantity) as '库存量', 
        CONCAT( Round(sum(SalesVolume)/sum(RetailAmount), 2)*100, '%') as '折扣',  sum(SaleQuantity) as '店均周销量', 
        sum(StockQuantity) as '店均库存量', '' as '店周转(天)'";
        $field_weather = "CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' ) as Start_time, max(d.weather_time) as End_time, b.customer_name,max(d.max_c) as max_c, min(d.min_c) as min_c, CONCAT(SUBSTRING(  CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' )  , 6, 5), ' 到 ', SUBSTRING( max(d.weather_time) , 6, 5)) as '周期'";


        if (!$Year) {//没有选择年份，要看三年的数据

            if (!$Year && !$Month && !$YunCang && !$WenDai && !$WenQu && !$State && !$Mathod && !$NewOld && !$Season && !$StyleCategoryName && !$CategoryName1 && !$CategoryName2 && !$CategoryName && !$CustomItem46) {
                //先查缓存数据是否存在
                $cache = SpCustomerStockSaleThreeyear2WeekCacheModel::where([['index_str', '=', 'threeyear_index']])->field('cache_data')->find();
                if ($cache && $cache['cache_data']) {
                    $cache = json_decode($cache['cache_data'], true);
                    if ($cache) {
                        return $cache;
                    }
                }
            }

            //where条件组装
            $where_yeji_2021 = [['Year', '=', '2021']];
            $where_yeji_2022 = [['Year', '=', '2022']];
            $where_yeji_2023 = [['Year', '=', '2023']];

            $where_weather_2021 = [['d.weather_time', '>=', '2021-01-04'], ['d.weather_time', '<=', '2022-01-02']];
            $where_weather_2022 = [['d.weather_time', '>=', '2022-01-03'], ['d.weather_time', '<=', '2023-01-01']];
            $where_weather_2023 = [['d.weather_time', '>=', '2023-01-02']];

            $where_customer_2021 = [['Year', '=', '2021']];
            $where_customer_2022 = [['Year', '=', '2022']];
            $where_customer_2023 = [['Year', '=', '2023']];

            $where_customer_num = [];

            //选年份
            if ($Year) {
                switch ($Year) {
                    case '2021': 
                        $where_yeji_2022 = [];
                        $where_yeji_2023 = [];

                        $where_weather_2022 = [];
                        $where_weather_2023 = [];

                        $where_customer_2022 = [];
                        $where_customer_2023 = [];
                        break;
                    case '2022': 
                        $where_yeji_2021 = [];
                        $where_yeji_2023 = [];

                        $where_weather_2021 = [];
                        $where_weather_2023 = [];

                        $where_customer_2021 = [];
                        $where_customer_2023 = [];
                        break;
                    case '2023': 
                        $where_yeji_2021 = [];
                        $where_yeji_2022 = [];

                        $where_weather_2021 = [];
                        $where_weather_2022 = [];

                        $where_customer_2021 = [];
                        $where_customer_2022 = [];
                        break;    
                }
                $where_customer_num[] = ['Year', '=', $Year];
            }

            //选月份
            if ($Month) {

                if ($where_yeji_2021) {
                    $where_yeji_2021[] = ['Month', 'in', $Month];
                }
                if ($where_yeji_2022) {
                    $where_yeji_2022[] = ['Month', 'in', $Month];
                }
                if ($where_yeji_2023) {
                    $where_yeji_2023[] = ['Month', 'in', $Month];
                }

                if ($where_customer_2021) {
                    $where_customer_2021[] = ['Month', 'in', $Month];
                }
                if ($where_customer_2022) {
                    $where_customer_2022[] = ['Month', 'in', $Month];
                }
                if ($where_customer_2023) {
                    $where_customer_2023[] = ['Month', 'in', $Month];
                }

                if ($Year) {
                    
                    $ex_month = explode(',', $Month);
                    // foreach ($ex_month as &$v_month) {
                    //     $v_month = "'%".$Year.'-'.$v_month."%'";
                    // }
                    //天气 月份取多一个月，以防最后一周无数据的情况
                    $count = count($ex_month);
                    $max_month = $ex_month[$count-1];
                    if ($max_month != 12) {
                        $cur_month = ++$max_month;
                        if ($cur_month < 10)  $cur_month = '0'.$cur_month;
                        $ex_month[] = $cur_month;
                    } 
                    foreach ($ex_month as &$v_month) {
                        $v_month = "%{$Year}-{$v_month}%";//'"%'.$Year.'-'.$v_month.'%"';
                    }

                    switch ($Year) {
                        case '2021': 
                            
                            $where_weather_2021[] = ['d.weather_time', 'like', $ex_month, 'or'];
                            $where_weather_2022 = [];
                            $where_weather_2023 = [];

                            break;
                        case '2022': 

                            $where_weather_2021 = [];
                            $where_weather_2022[] = ['d.weather_time', 'like', $ex_month, 'or'];
                            $where_weather_2023 = [];
                            
                            break;
                        case '2023': 
                            
                            $where_weather_2021 = [];
                            $where_weather_2022 = [];
                            $where_weather_2023[] = ['d.weather_time', 'like', $ex_month, 'or'];

                            break;    
                    }

                }

                $where_customer_num[] = ['Month', 'in', $Month];

            }

            //大区
            if ($YunCang) {

                $where_yeji_2021[] = ['YunCang', '=', $YunCang];
                $where_yeji_2022[] = ['YunCang', '=', $YunCang];
                $where_yeji_2023[] = ['YunCang', '=', $YunCang];

                $which_yuncang = config('weather.yuncang_arr')[$YunCang];
                $where_weather_2021[] = ['b.yuncang', 'in', $which_yuncang];
                $where_weather_2022[] = ['b.yuncang', 'in', $which_yuncang];
                $where_weather_2023[] = ['b.yuncang', 'in', $which_yuncang];

                $where_customer_2021[] = ['YunCang', '=', $YunCang];
                $where_customer_2022[] = ['YunCang', '=', $YunCang];
                $where_customer_2023[] = ['YunCang', '=', $YunCang];

                $where_customer_num[] = ['YunCang', '=', $YunCang];

            }

            //温带
            if ($WenDai) {

                $where_yeji_2021[] = ['WenDai', 'in', $WenDai];
                $where_yeji_2022[] = ['WenDai', 'in', $WenDai];
                $where_yeji_2023[] = ['WenDai', 'in', $WenDai];

                $where_weather_2021[] = ['b.wendai', 'in', $WenDai];
                $where_weather_2022[] = ['b.wendai', 'in', $WenDai];
                $where_weather_2023[] = ['b.wendai', 'in', $WenDai];

                $where_customer_2021[] = ['WenDai', 'in', $WenDai];
                $where_customer_2022[] = ['WenDai', 'in', $WenDai];
                $where_customer_2023[] = ['WenDai', 'in', $WenDai];

                $where_customer_num[] = ['WenDai', 'in', $WenDai];

            }

            //温区
            if ($WenQu) {

                $where_yeji_2021[] = ['WenQu', 'in', $WenQu];
                $where_yeji_2022[] = ['WenQu', 'in', $WenQu];
                $where_yeji_2023[] = ['WenQu', 'in', $WenQu];

                $where_weather_2021[] = ['b.wenqu', 'in', $WenQu];
                $where_weather_2022[] = ['b.wenqu', 'in', $WenQu];
                $where_weather_2023[] = ['b.wenqu', 'in', $WenQu];

                $where_customer_2021[] = ['WenQu', 'in', $WenQu];
                $where_customer_2022[] = ['WenQu', 'in', $WenQu];
                $where_customer_2023[] = ['WenQu', 'in', $WenQu];

                $where_customer_num[] = ['WenQu', 'in', $WenQu];

            }

            //省份
            if ($State) {

                $where_yeji_2021[] = ['State', 'in', $State];
                $where_yeji_2022[] = ['State', 'in', $State];
                $where_yeji_2023[] = ['State', 'in', $State];

                $where_weather_2021[] = ['b.province', 'in', $State];
                $where_weather_2022[] = ['b.province', 'in', $State];
                $where_weather_2023[] = ['b.province', 'in', $State];

                $where_customer_2021[] = ['State', 'in', $State];
                $where_customer_2022[] = ['State', 'in', $State];
                $where_customer_2023[] = ['State', 'in', $State];

                $where_customer_num[] = ['State', 'in', $State];

            }

            //经营模式
            if ($Mathod) {

                $where_yeji_2021[] = ['Mathod', '=', $Mathod];
                $where_yeji_2022[] = ['Mathod', '=', $Mathod];
                $where_yeji_2023[] = ['Mathod', '=', $Mathod];

                $where_weather_2021[] = ['b.store_type', '=', $Mathod];
                $where_weather_2022[] = ['b.store_type', '=', $Mathod];
                $where_weather_2023[] = ['b.store_type', '=', $Mathod];

                $where_customer_2021[] = ['Mathod', '=', $Mathod];
                $where_customer_2022[] = ['Mathod', '=', $Mathod];
                $where_customer_2023[] = ['Mathod', '=', $Mathod];

                $where_customer_num[] = ['Mathod', '=', $Mathod];

            }

            ##############################货品属性筛选####################################

            //新旧品
            if ($NewOld) {

                $cur_year = date('Y');
                if ($NewOld == '新品') {

                    $where_yeji_2021[] = ['TimeCategoryName1', '=', $cur_year];
                    $where_yeji_2022[] = ['TimeCategoryName1', '=', $cur_year];
                    $where_yeji_2023[] = ['TimeCategoryName1', '=', $cur_year];

                } else {

                    $where_yeji_2021[] = ['TimeCategoryName1', '<', $cur_year];
                    $where_yeji_2022[] = ['TimeCategoryName1', '<', $cur_year];
                    $where_yeji_2023[] = ['TimeCategoryName1', '<', $cur_year];

                }

            }

            //季节
            if ($Season) {

                $where_yeji_2021[] = ['Season', 'in', $Season];
                $where_yeji_2022[] = ['Season', 'in', $Season];
                $where_yeji_2023[] = ['Season', 'in', $Season];

            }

            //风格
            if ($StyleCategoryName) {

                $where_yeji_2021[] = ['StyleCategoryName', '=', $StyleCategoryName];
                $where_yeji_2022[] = ['StyleCategoryName', '=', $StyleCategoryName];
                $where_yeji_2023[] = ['StyleCategoryName', '=', $StyleCategoryName];

            }

            //一级分类
            if ($CategoryName1) {

                $where_yeji_2021[] = ['CategoryName1', 'in', $CategoryName1];
                $where_yeji_2022[] = ['CategoryName1', 'in', $CategoryName1];
                $where_yeji_2023[] = ['CategoryName1', 'in', $CategoryName1];

            }

            //二级分类
            if ($CategoryName2) {

                $where_yeji_2021[] = ['CategoryName2', 'in', $CategoryName2];
                $where_yeji_2022[] = ['CategoryName2', 'in', $CategoryName2];
                $where_yeji_2023[] = ['CategoryName2', 'in', $CategoryName2];

            }

            //分类
            if ($CategoryName) {

                $where_yeji_2021[] = ['CategoryName', 'in', $CategoryName];
                $where_yeji_2022[] = ['CategoryName', 'in', $CategoryName];
                $where_yeji_2023[] = ['CategoryName', 'in', $CategoryName];

            }

            //深浅色
            if ($CustomItem46) {

                $where_yeji_2021[] = ['CustomItem46', '=', $CustomItem46];
                $where_yeji_2022[] = ['CustomItem46', '=', $CustomItem46];
                $where_yeji_2023[] = ['CustomItem46', '=', $CustomItem46];

            }

            
            //业绩占比/库存占比/效率 计算(当有选择货品属性的情况)
            $customer_threeyear_2021 = $customer_threeyear_2022 = $customer_threeyear_2023 = [];
            $if_select_goods = 0;
            if ($NewOld || $Season || $StyleCategoryName || $CategoryName1 || $CategoryName2 || $CategoryName || $CustomItem46) {
                $if_select_goods = 1;

                $field = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
            CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
            sum(SalesVolume) as '业绩', sum(StockCost) as '库存', '100.0%' as '效率', max(NUM) as '店铺数', sum(SaleQuantity) as '销量(周)', sum(StockQuantity) as '库存量', 
            CONCAT( Round(sum(SalesVolume)/sum(RetailAmount), 2)*100, '%') as '折扣',  sum(SaleQuantity) as '店均周销量', 
            sum(StockQuantity) as '店均库存量', '' as '店周转(天)'";

                $field_customer = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
            CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
            sum(SalesVolume) as '业绩', sum(StockCost) as '库存'";

                if ($where_customer_2021) {
                    $customer_threeyear_2021 = $this->threeyear2_week_model::where($where_customer_2021)->group('Week')->field($field_customer)->select();
                    $customer_threeyear_2021 = $customer_threeyear_2021 ? $customer_threeyear_2021->toArray() : [];
                    $customer_threeyear_2021_weeks = $customer_threeyear_2021 ? array_column($customer_threeyear_2021, 'Week') : [];
                    $customer_threeyear_2021 = array_combine($customer_threeyear_2021_weeks, $customer_threeyear_2021);
                }

                if ($where_customer_2022) {
                    $customer_threeyear_2022 = $this->threeyear2_week_model::where($where_customer_2022)->group('Week')->field($field_customer)->select();
                    $customer_threeyear_2022 = $customer_threeyear_2022 ? $customer_threeyear_2022->toArray() : [];
                    $customer_threeyear_2022_weeks = $customer_threeyear_2022 ? array_column($customer_threeyear_2022, 'Week') : [];
                    $customer_threeyear_2022 = array_combine($customer_threeyear_2022_weeks, $customer_threeyear_2022);
                }

                if ($where_customer_2023) {
                    $customer_threeyear_2023 = $this->threeyear2_week_model::where($where_customer_2023)->group('Week')->field($field_customer)->select();
                    $customer_threeyear_2023 = $customer_threeyear_2023 ? $customer_threeyear_2023->toArray() : [];
                    $customer_threeyear_2023_weeks = $customer_threeyear_2023 ? array_column($customer_threeyear_2023, 'Week') : [];
                    $customer_threeyear_2023 = array_combine($customer_threeyear_2023_weeks, $customer_threeyear_2023);
                }

            }


            //店铺数获取处理(当有店铺属性筛选时有效)
            $customer_num_list = $this->threeyear2_week_model::where($where_customer_num)->group('aa,year_week')->field("Year, Week, concat(YunCang, WenDai, WenQu, State, Mathod) as aa, max(NUM) as max_num, concat(Year, Week) as year_week")->select();
            $customer_num_list = $customer_num_list ? $customer_num_list->toArray() : [];
            $customer_num_list_new = [];
            if ($customer_num_list) {
                foreach ($customer_num_list as $v_customer_num_list) {
                    if (isset($customer_num_list_new[$v_customer_num_list['year_week']])) {
                        $customer_num_list_new[$v_customer_num_list['year_week']] += $v_customer_num_list['max_num'];
                    } else {
                        $customer_num_list_new[$v_customer_num_list['year_week']] = $v_customer_num_list['max_num'];
                    }
                }
            }
            // print_r($customer_num_list_new);die;


            $threeyear_2021 = $threeyear_2022 = $threeyear_2023 = [];
            //2021年业绩、库存等情况
            if ($where_yeji_2021) {
                $threeyear_2021 = $this->threeyear2_week_model::where($where_yeji_2021)->group('Week')->field($field)->select();
                $threeyear_2021 = $threeyear_2021 ? $threeyear_2021->toArray() : [];
                $threeyear_2021_weeks = $threeyear_2021 ? array_column($threeyear_2021, 'Week') : [];
                $threeyear_2021 = array_combine($threeyear_2021_weeks, $threeyear_2021);

                if ($threeyear_2021) {
                    if ($if_select_goods) {
                        foreach ($threeyear_2021 as $k_threeyear_2021=>&$v_threeyear_2021) {
                            
                            $yeji_num = ($customer_threeyear_2021 && isset($customer_threeyear_2021[$k_threeyear_2021])) ? (round($v_threeyear_2021['业绩']/$customer_threeyear_2021[$k_threeyear_2021]['业绩'], 3) * 100) : 0;
                            $kucun_num = ($customer_threeyear_2021 && isset($customer_threeyear_2021[$k_threeyear_2021])) ? (round($v_threeyear_2021['库存']/$customer_threeyear_2021[$k_threeyear_2021]['库存'], 3) * 100) : 0;
                            $v_threeyear_2021['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                            $v_threeyear_2021['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                            $v_threeyear_2021['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
        
                        }
                    }
                }
            }

            //2022年业绩、库存等情况
            if ($where_yeji_2022) {

                $threeyear_2022 = $this->threeyear2_week_model::where($where_yeji_2022)->group('Week')->field($field)->select();
                $threeyear_2022 = $threeyear_2022 ? $threeyear_2022->toArray() : [];
                $threeyear_2022_weeks = $threeyear_2022 ? array_column($threeyear_2022, 'Week') : [];
                $threeyear_2022 = array_combine($threeyear_2022_weeks, $threeyear_2022);

                if ($threeyear_2022) {
                    if ($if_select_goods) {
                        foreach ($threeyear_2022 as $k_threeyear_2022=>&$v_threeyear_2022) {
                            
                            $yeji_num = ($customer_threeyear_2022 && isset($customer_threeyear_2022[$k_threeyear_2022])) ? (round($v_threeyear_2022['业绩']/$customer_threeyear_2022[$k_threeyear_2022]['业绩'], 3) * 100) : 0;
                            $kucun_num = ($customer_threeyear_2022 && isset($customer_threeyear_2022[$k_threeyear_2022])) ? (round($v_threeyear_2022['库存']/$customer_threeyear_2022[$k_threeyear_2022]['库存'], 3) * 100) : 0;
                            $v_threeyear_2022['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                            $v_threeyear_2022['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                            $v_threeyear_2022['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
        
                        }
                    }
                }
            }

            //2023年业绩、库存等情况
            if ($where_yeji_2023) {
                $threeyear_2023 = $this->threeyear2_week_model::where($where_yeji_2023)->group('Week')->field($field)->select();
                $threeyear_2023 = $threeyear_2023 ? $threeyear_2023->toArray() : [];
                $threeyear_2023_weeks = $threeyear_2023 ? array_column($threeyear_2023, 'Week') : [];
                $threeyear_2023 = array_combine($threeyear_2023_weeks, $threeyear_2023);

                if ($threeyear_2023) {
                    if ($if_select_goods) {
                        foreach ($threeyear_2023 as $k_threeyear_2023=>&$v_threeyear_2023) {
                            
                            $yeji_num = ($customer_threeyear_2023 && isset($customer_threeyear_2023[$k_threeyear_2023])) ? (round($v_threeyear_2023['业绩']/$customer_threeyear_2023[$k_threeyear_2023]['业绩'], 3) * 100) : 0;
                            $kucun_num = ($customer_threeyear_2023 && isset($customer_threeyear_2023[$k_threeyear_2023])) ? (round($v_threeyear_2023['库存']/$customer_threeyear_2023[$k_threeyear_2023]['库存'], 3) * 100) : 0;
                            $v_threeyear_2023['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                            $v_threeyear_2023['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                            $v_threeyear_2023['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
        
                        }
                    }
                }
            }


            $weather_2021 = $weather_2022 = $weather_2023 = [];
            //最高温、最低温 2021
            if ($where_weather_2021) {
                $weather_2021 = $this->weather_data_model::where($where_weather_2021)->alias('d')->field($field_weather)
                ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
                $weather_2021 = $weather_2021 ? $weather_2021->toArray() : [];
            }
            $weather_2021_arr = [];
            if ($weather_2021) {
                foreach ($weather_2021 as $v_weather_2021) {
                    $zhouqi = $v_weather_2021['周期'];
                    $weather_2021_arr[$zhouqi][] = $v_weather_2021;
                    if (isset($weather_2021_arr[$zhouqi]['max_c'])) {
                        $weather_2021_arr[$zhouqi]['max_c'] += $v_weather_2021['max_c'];
                    } else {
                        $weather_2021_arr[$zhouqi]['max_c'] = $v_weather_2021['max_c'];
                    }
                    if (isset($weather_2021_arr[$zhouqi]['min_c'])) {
                        $weather_2021_arr[$zhouqi]['min_c'] += $v_weather_2021['min_c'];
                    } else {
                        $weather_2021_arr[$zhouqi]['min_c'] = $v_weather_2021['min_c'];
                    }
                    if (isset($weather_2021_arr[$zhouqi]['customer_num'])) {
                        $weather_2021_arr[$zhouqi]['customer_num'] += 1;
                    } else {
                        $weather_2021_arr[$zhouqi]['customer_num'] = 1;
                    }
                }
            }
            // print_r($weather_2021_arr);die;

            //最高温、最低温 2022
            if ($where_weather_2022) {
                $weather_2022 = $this->weather_data_model::where($where_weather_2022)->alias('d')->field($field_weather)
                ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
                $weather_2022 = $weather_2022 ? $weather_2022->toArray() : [];
            }
            $weather_2022_arr = [];
            if ($weather_2022) {
                foreach ($weather_2022 as $v_weather_2022) {
                    $zhouqi = $v_weather_2022['周期'];
                    $weather_2022_arr[$zhouqi][] = $v_weather_2022;
                    if (isset($weather_2022_arr[$zhouqi]['max_c'])) {
                        $weather_2022_arr[$zhouqi]['max_c'] += $v_weather_2022['max_c'];
                    } else {
                        $weather_2022_arr[$zhouqi]['max_c'] = $v_weather_2022['max_c'];
                    }
                    if (isset($weather_2022_arr[$zhouqi]['min_c'])) {
                        $weather_2022_arr[$zhouqi]['min_c'] += $v_weather_2022['min_c'];
                    } else {
                        $weather_2022_arr[$zhouqi]['min_c'] = $v_weather_2022['min_c'];
                    }
                    if (isset($weather_2022_arr[$zhouqi]['customer_num'])) {
                        $weather_2022_arr[$zhouqi]['customer_num'] += 1;
                    } else {
                        $weather_2022_arr[$zhouqi]['customer_num'] = 1;
                    }
                }
            }
            // print_r($weather_2022_arr);die;

            //最高温、最低温 2023
            if ($where_weather_2023) {
                $weather_2023 = $this->weather_data_model::where($where_weather_2023)->alias('d')->field($field_weather)
                ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
                $weather_2023 = $weather_2023 ? $weather_2023->toArray() : [];
            }
            $weather_2023_arr = [];
            if ($weather_2023) {
                foreach ($weather_2023 as $v_weather_2023) {
                    $zhouqi = $v_weather_2023['周期'];
                    $weather_2023_arr[$zhouqi][] = $v_weather_2023;
                    if (isset($weather_2023_arr[$zhouqi]['max_c'])) {
                        $weather_2023_arr[$zhouqi]['max_c'] += $v_weather_2023['max_c'];
                    } else {
                        $weather_2023_arr[$zhouqi]['max_c'] = $v_weather_2023['max_c'];
                    }
                    if (isset($weather_2023_arr[$zhouqi]['min_c'])) {
                        $weather_2023_arr[$zhouqi]['min_c'] += $v_weather_2023['min_c'];
                    } else {
                        $weather_2023_arr[$zhouqi]['min_c'] = $v_weather_2023['min_c'];
                    }
                    if (isset($weather_2023_arr[$zhouqi]['customer_num'])) {
                        $weather_2023_arr[$zhouqi]['customer_num'] += 1;
                    } else {
                        $weather_2023_arr[$zhouqi]['customer_num'] = 1;
                    }
                }
            }
            // print_r($weather_2023_arr);die;

            //重新组装出最终数据
            $arr = [];
            if ($week_dates) {
                foreach ($week_dates as $v_date) {

                    $each_threeyear_2021 = $threeyear_2021[$v_date['week']] ?? [];
                    $each_threeyear_2022 = $threeyear_2022[$v_date['week']] ?? [];
                    $each_threeyear_2023 = $threeyear_2023[$v_date['week']] ?? [];

                    $tmp_arr = [];
                    $tmp_arr['年'] = $v_date['year'];
                    $tmp_arr['周'] = '第'.$v_date['week'].'周';
                    $tmp_arr['月'] = ($v_date['start_time'] ? substr($v_date['start_time'], 5, 2) : '').'月';
                    $tmp_arr['周期'] = $v_date['周期'];

                    //前年
                    $tmp_arr['前年业绩占比'] = $each_threeyear_2021 ? $each_threeyear_2021['业绩占比'] : '';
                    $tmp_arr['前年库存占比'] = $each_threeyear_2021 ? $each_threeyear_2021['库存占比'] : '';
                    $tmp_arr['前年效率'] = $each_threeyear_2021 ? $each_threeyear_2021['效率'] : '';
                    $tmp_arr['前年店铺数'] = $each_threeyear_2021 ? ($customer_num_list_new[$each_threeyear_2021['年'].$each_threeyear_2021['Week']] ?? '') : '';//
                    $tmp_arr['前年销量(周)'] = $each_threeyear_2021 ? $each_threeyear_2021['销量(周)'] : '';
                    $tmp_arr['前年库存量'] = $each_threeyear_2021 ? $each_threeyear_2021['库存量'] : '';
                    $tmp_arr['前年折扣'] = $each_threeyear_2021 ? $each_threeyear_2021['折扣'] : '';
                    $tmp_arr['前年店均周销量'] = $each_threeyear_2021 ? ($tmp_arr['前年店铺数'] ? round( ($each_threeyear_2021['店均周销量']/$tmp_arr['前年店铺数']), 1) : '') : '';//
                    $tmp_arr['前年店均库存量'] = $each_threeyear_2021 ? ($tmp_arr['前年店铺数'] ? round( ($each_threeyear_2021['店均库存量']/$tmp_arr['前年店铺数']/7), 0) : '') : '';//
                    $tmp_arr['前年店周转(天)'] = $each_threeyear_2021 ? ( $tmp_arr['前年店均周销量'] ? round( ($tmp_arr['前年店均库存量'] ?: 0)/$tmp_arr['前年店均周销量']*7, 0 ) : '' ) : '';//
                    if ($each_threeyear_2021) {
                        $tmp_arr['前年最高温'] = isset($weather_2021_arr[$each_threeyear_2021['周期']]) ? round($weather_2021_arr[$each_threeyear_2021['周期']]['max_c'] / $weather_2021_arr[$each_threeyear_2021['周期']]['customer_num'], 0) : '';
                        $tmp_arr['前年最低温'] = isset($weather_2021_arr[$each_threeyear_2021['周期']]) ? round($weather_2021_arr[$each_threeyear_2021['周期']]['min_c'] / $weather_2021_arr[$each_threeyear_2021['周期']]['customer_num'], 0) : '';
                    } else {
                        $tmp_arr['前年最高温'] = '';
                        $tmp_arr['前年最低温'] = '';
                    }

                    //去年
                    $tmp_arr['去年业绩占比'] = $each_threeyear_2022 ? $each_threeyear_2022['业绩占比'] : '';
                    $tmp_arr['去年库存占比'] = $each_threeyear_2022 ? $each_threeyear_2022['库存占比'] : '';
                    $tmp_arr['去年效率'] = $each_threeyear_2022 ? $each_threeyear_2022['效率'] : '';
                    $tmp_arr['去年店铺数'] = $each_threeyear_2022 ? ($customer_num_list_new[$each_threeyear_2022['年'].$each_threeyear_2022['Week']] ?? '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店铺数'] : '';
                    $tmp_arr['去年销量(周)'] = $each_threeyear_2022 ? $each_threeyear_2022['销量(周)'] : '';
                    $tmp_arr['去年库存量'] = $each_threeyear_2022 ? $each_threeyear_2022['库存量'] : '';
                    $tmp_arr['去年折扣'] = $each_threeyear_2022 ? $each_threeyear_2022['折扣'] : '';
                    $tmp_arr['去年店均周销量'] = $each_threeyear_2022 ? ($tmp_arr['去年店铺数'] ? round( ($each_threeyear_2022['店均周销量']/$tmp_arr['去年店铺数']), 1) : '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店均周销量'] : '';//
                    $tmp_arr['去年店均库存量'] = $each_threeyear_2022 ? ($tmp_arr['去年店铺数'] ? round( ($each_threeyear_2022['店均库存量']/$tmp_arr['去年店铺数']/7), 0) : '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店均库存量'] : '';//
                    $tmp_arr['去年店周转(天)'] = $each_threeyear_2022 ? ( $tmp_arr['去年店均周销量'] ? round( ($tmp_arr['去年店均库存量'] ?: 0)/$tmp_arr['去年店均周销量']*7, 0 ) : '' ) : '';////$each_threeyear_2022 ? $each_threeyear_2022['店周转(天)'] : '';//
                    if ($each_threeyear_2022) {
                        $tmp_arr['去年最高温'] = isset($weather_2022_arr[$each_threeyear_2022['周期']]) ? round($weather_2022_arr[$each_threeyear_2022['周期']]['max_c'] / $weather_2022_arr[$each_threeyear_2022['周期']]['customer_num'], 0) : '';
                        $tmp_arr['去年最低温'] = isset($weather_2022_arr[$each_threeyear_2022['周期']]) ? round($weather_2022_arr[$each_threeyear_2022['周期']]['min_c'] / $weather_2022_arr[$each_threeyear_2022['周期']]['customer_num'], 0) : '';
                    } else {
                        $tmp_arr['去年最高温'] = '';
                        $tmp_arr['去年最低温'] = '';
                    }

                    //今年
                    $tmp_arr['今年业绩占比'] = $each_threeyear_2023 ? $each_threeyear_2023['业绩占比'] : '';
                    $tmp_arr['今年库存占比'] = $each_threeyear_2023 ? $each_threeyear_2023['库存占比'] : '';
                    $tmp_arr['今年效率'] = $each_threeyear_2023 ? $each_threeyear_2023['效率'] : '';
                    $tmp_arr['今年店铺数'] = $each_threeyear_2023 ? ($customer_num_list_new[$each_threeyear_2023['年'].$each_threeyear_2023['Week']] ?? '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店铺数'] : '';//
                    $tmp_arr['今年销量(周)'] = $each_threeyear_2023 ? $each_threeyear_2023['销量(周)'] : '';
                    $tmp_arr['今年库存量'] = $each_threeyear_2023 ? $each_threeyear_2023['库存量'] : '';
                    $tmp_arr['今年折扣'] = $each_threeyear_2023 ? $each_threeyear_2023['折扣'] : '';
                    $tmp_arr['今年店均周销量'] = $each_threeyear_2023 ? ($tmp_arr['今年店铺数'] ? round( ($each_threeyear_2023['店均周销量']/$tmp_arr['今年店铺数']), 1) : '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店均周销量'] : '';//
                    $tmp_arr['今年店均库存量'] = $each_threeyear_2023 ? ($tmp_arr['今年店铺数'] ? round( ($each_threeyear_2023['店均库存量']/$tmp_arr['今年店铺数']/7), 0) : '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店均库存量'] : '';//
                    $tmp_arr['今年店周转(天)'] = $each_threeyear_2023 ? ( $tmp_arr['今年店均周销量'] ? round( ($tmp_arr['今年店均库存量'] ?: 0)/$tmp_arr['今年店均周销量']*7, 0 ) : '' ) : '';//$each_threeyear_2023 ? $each_threeyear_2023['店周转(天)'] : '';//
                    if ($each_threeyear_2023) {
                        $tmp_arr['今年最高温'] = isset($weather_2023_arr[$each_threeyear_2023['周期']]) ? round($weather_2023_arr[$each_threeyear_2023['周期']]['max_c'] / $weather_2023_arr[$each_threeyear_2023['周期']]['customer_num'], 0) : '';
                        $tmp_arr['今年最低温'] = isset($weather_2023_arr[$each_threeyear_2023['周期']]) ? round($weather_2023_arr[$each_threeyear_2023['周期']]['min_c'] / $weather_2023_arr[$each_threeyear_2023['周期']]['customer_num'], 0) : '';
                    } else {
                        $tmp_arr['今年最高温'] = '';
                        $tmp_arr['今年最低温'] = '';
                    }

                    $arr[] = $tmp_arr;

                }
            }

            // print_r($arr);die;
            return $arr;


        } else {//选择年份，只要看某一年的

            return $this->get_one_year($params, $week_dates, $field, $field_weather);

        }


    }


    //只返回一年数据
    protected function get_one_year($params, $week_dates, $field, $field_weather) {


        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $Year = $params['Year'] ?? '';
        $Month = $params['Month'] ?? '';
        $YunCang = $params['YunCang'] ?? '';
        $WenDai = $params['WenDai'] ?? '';
        $WenQu = $params['WenQu'] ?? '';
        $State = $params['State'] ?? '';
        $Mathod = $params['Mathod'] ?? '';
        $NewOld = $params['NewOld'] ?? '';
        $Season = $params['Season'] ?? '';
        $StyleCategoryName = $params['StyleCategoryName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $CategoryName2 = $params['CategoryName2'] ?? '';
        $CategoryName = $params['CategoryName'] ?? '';
        $CustomItem46 = $params['CustomItem46'] ?? '';//深浅色


        //where条件组装
        $where_yeji_new = [['Year', '=', $Year]];

        $where_weather_new = [];
        switch ($Year) {
            case '2021': 
                $where_weather_new = [['d.weather_time', '>=', '2021-01-04'], ['d.weather_time', '<=', '2022-01-02']];
                break;
            case '2022': 
                $where_weather_new = [['d.weather_time', '>=', '2022-01-03'], ['d.weather_time', '<=', '2023-01-01']];
                break;
            case '2023': 
                $where_weather_new = [['d.weather_time', '>=', '2023-01-02'], ['d.weather_time', '<=', '2023-12-31']];
                break;
        }

        $where_customer_new = [['Year', '=', $Year]];

        $where_customer_num = [];

        //选年份
        if ($Year) {
            $where_customer_num[] = ['Year', '=', $Year];
        }

        //选月份
        if ($Month) {

            $where_yeji_new[] = ['Month', 'in', $Month];

            $where_customer_new[] = ['Month', 'in', $Month];

            if ($Year) {
                
                $ex_month = explode(',', $Month);
                //天气 月份取多一个月，以防最后一周无数据的情况
                $count = count($ex_month);
                $max_month = $ex_month[$count-1];
                if ($max_month != 12) {
                    $cur_month = ++$max_month;
                    if ($cur_month < 10)  $cur_month = '0'.$cur_month;
                    $ex_month[] = $cur_month;
                } 
                foreach ($ex_month as &$v_month) {
                    $v_month = "%{$Year}-{$v_month}%";//'"%'.$Year.'-'.$v_month.'%"';
                }
                // else {//如果是12月，要取下一年的数据
                //     foreach ($ex_month as &$v_month) {
                //         $v_month = "%{$Year}-{$v_month}%";
                //     }
                //     $cur_year = ++$Year;
                //     $ex_month[] = "%{$cur_year}-01%";
                // }

                $where_weather_new[] = ['d.weather_time', 'like', $ex_month, 'or'];

            }

            $where_customer_num[] = ['Month', 'in', $Month];

        }

        //大区
        if ($YunCang) {

            $where_yeji_new[] = ['YunCang', '=', $YunCang];

            $which_yuncang = config('weather.yuncang_arr')[$YunCang];
            $where_weather_new[] = ['b.yuncang', 'in', $which_yuncang];

            $where_customer_new[] = ['YunCang', '=', $YunCang];
            $where_customer_num[] = ['YunCang', '=', $YunCang];

        }
        // print_r($where_weather_new);die;

        //温带
        if ($WenDai) {

            $where_yeji_new[] = ['WenDai', 'in', $WenDai];
            $where_weather_new[] = ['b.wendai', 'in', $WenDai];
            $where_customer_new[] = ['WenDai', 'in', $WenDai];
            $where_customer_num[] = ['WenDai', 'in', $WenDai];

        }

        //温区
        if ($WenQu) {

            $where_yeji_new[] = ['WenQu', 'in', $WenQu];
            $where_weather_new[] = ['b.wenqu', 'in', $WenQu];
            $where_customer_new[] = ['WenQu', 'in', $WenQu];
            $where_customer_num[] = ['WenQu', 'in', $WenQu];

        }

        //省份
        if ($State) {

            $where_yeji_new[] = ['State', 'in', $State];
            $where_weather_new[] = ['b.province', 'in', $State];
            $where_customer_new[] = ['State', 'in', $State];
            $where_customer_num[] = ['State', 'in', $State];

        }

        //经营模式
        if ($Mathod) {

            $where_yeji_new[] = ['Mathod', '=', $Mathod];
            $where_weather_new[] = ['b.store_type', '=', $Mathod];
            $where_customer_new[] = ['Mathod', '=', $Mathod];
            $where_customer_num[] = ['Mathod', '=', $Mathod];

        }

        ##############################货品属性筛选####################################

        //新旧品
        if ($NewOld) {

            $cur_year = date('Y');
            if ($NewOld == '新品') {
                $where_yeji_new[] = ['TimeCategoryName1', '=', $cur_year];
            } else {
                $where_yeji_new[] = ['TimeCategoryName1', '<', $cur_year];
            }

        }

        //季节
        if ($Season) {
            $where_yeji_new[] = ['Season', 'in', $Season];
        }

        //风格
        if ($StyleCategoryName) {
            $where_yeji_new[] = ['StyleCategoryName', '=', $StyleCategoryName];
        }

        //一级分类
        if ($CategoryName1) {
            $where_yeji_new[] = ['CategoryName1', 'in', $CategoryName1];
        }

        //二级分类
        if ($CategoryName2) {
            $where_yeji_new[] = ['CategoryName2', 'in', $CategoryName2];
        }

        //分类
        if ($CategoryName) {
            $where_yeji_new[] = ['CategoryName', 'in', $CategoryName];
        }

        //深浅色
        if ($CustomItem46) {
            $where_yeji_new[] = ['CustomItem46', '=', $CustomItem46];
        }
        
        //业绩占比/库存占比/效率 计算(当有选择货品属性的情况)
        $customer_threeyear_new = [];
        $if_select_goods = 0;
        if ($NewOld || $Season || $StyleCategoryName || $CategoryName1 || $CategoryName2 || $CategoryName || $CustomItem46) {
            $if_select_goods = 1;

            $field = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
        CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
        sum(SalesVolume) as '业绩', sum(StockCost) as '库存', '100.0%' as '效率', max(NUM) as '店铺数', sum(SaleQuantity) as '销量(周)', sum(StockQuantity) as '库存量', 
        CONCAT( Round(sum(SalesVolume)/sum(RetailAmount), 2)*100, '%') as '折扣',  sum(SaleQuantity) as '店均周销量', 
        sum(StockQuantity) as '店均库存量', '' as '店周转(天)'";

            $field_customer = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
        CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
        sum(SalesVolume) as '业绩', sum(StockCost) as '库存'";

            if ($where_customer_new) {
                $customer_threeyear_new = $this->threeyear2_week_model::where($where_customer_new)->group('Week')->field($field_customer)->select();
                $customer_threeyear_new = $customer_threeyear_new ? $customer_threeyear_new->toArray() : [];
                $customer_threeyear_new_weeks = $customer_threeyear_new ? array_column($customer_threeyear_new, 'Week') : [];
                $customer_threeyear_new = array_combine($customer_threeyear_new_weeks, $customer_threeyear_new);
            }

        }


        //店铺数获取处理(当有店铺属性筛选时有效)
        $customer_num_list = $this->threeyear2_week_model::where($where_customer_num)->group('aa,year_week')->field("Year, Week, concat(YunCang, WenDai, WenQu, State, Mathod) as aa, max(NUM) as max_num, concat(Year, Week) as year_week")->select();
        $customer_num_list = $customer_num_list ? $customer_num_list->toArray() : [];
        $customer_num_list_new = [];
        if ($customer_num_list) {
            foreach ($customer_num_list as $v_customer_num_list) {
                if (isset($customer_num_list_new[$v_customer_num_list['year_week']])) {
                    $customer_num_list_new[$v_customer_num_list['year_week']] += $v_customer_num_list['max_num'];
                } else {
                    $customer_num_list_new[$v_customer_num_list['year_week']] = $v_customer_num_list['max_num'];
                }
            }
        }
        // print_r($customer_num_list_new);die;


        $threeyear_new = [];
        if ($where_yeji_new) {
            $threeyear_new = $this->threeyear2_week_model::where($where_yeji_new)->group('Week')->field($field)->select();
            $threeyear_new = $threeyear_new ? $threeyear_new->toArray() : [];
            $threeyear_new_weeks = $threeyear_new ? array_column($threeyear_new, 'Week') : [];
            $threeyear_new = array_combine($threeyear_new_weeks, $threeyear_new);

            if ($threeyear_new) {
                if ($if_select_goods) {
                    foreach ($threeyear_new as $k_threeyear_new=>&$v_threeyear_new) {
                        
                        $yeji_num = ($customer_threeyear_new && isset($customer_threeyear_new[$k_threeyear_new])) ? (round($v_threeyear_new['业绩']/$customer_threeyear_new[$k_threeyear_new]['业绩'], 3) * 100) : 0;
                        $kucun_num = ($customer_threeyear_new && isset($customer_threeyear_new[$k_threeyear_new])) ? (round($v_threeyear_new['库存']/$customer_threeyear_new[$k_threeyear_new]['库存'], 3) * 100) : 0;
                        $v_threeyear_new['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                        $v_threeyear_new['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                        $v_threeyear_new['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
    
                    }
                }
            }
        }

        $weather_new = [];
        if ($where_weather_new) {
            $weather_new = $this->weather_data_model::where($where_weather_new)->alias('d')->field($field_weather)
            ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
            $weather_new = $weather_new ? $weather_new->toArray() : [];
            // print_r([$where_weather_new, $weather_new]);die;
        }

        $weather_new_arr = [];
        if ($weather_new) {
            foreach ($weather_new as $v_weather_new) {
                $zhouqi = $v_weather_new['周期'];
                $weather_new_arr[$zhouqi][] = $v_weather_new;
                if (isset($weather_new_arr[$zhouqi]['max_c'])) {
                    $weather_new_arr[$zhouqi]['max_c'] += $v_weather_new['max_c'];
                } else {
                    $weather_new_arr[$zhouqi]['max_c'] = $v_weather_new['max_c'];
                }
                if (isset($weather_new_arr[$zhouqi]['min_c'])) {
                    $weather_new_arr[$zhouqi]['min_c'] += $v_weather_new['min_c'];
                } else {
                    $weather_new_arr[$zhouqi]['min_c'] = $v_weather_new['min_c'];
                }
                if (isset($weather_new_arr[$zhouqi]['customer_num'])) {
                    $weather_new_arr[$zhouqi]['customer_num'] += 1;
                } else {
                    $weather_new_arr[$zhouqi]['customer_num'] = 1;
                }
            }
        }
        // print_r([$weather_new_arr]);die;


        //重新组装出最终数据
        $arr = [];
        if ($week_dates) {
            foreach ($week_dates as $v_date) {

                $each_threeyear_2021 = $each_threeyear_2022 = $each_threeyear_2023 = [];
                if ($Year == '2021') {
                    $each_threeyear_2021 = $threeyear_new[$v_date['week']] ?? [];
                }
                if ($Year == '2022') {
                    $each_threeyear_2022 = $threeyear_new[$v_date['week']] ?? [];
                }
                if ($Year == '2023') {
                    $each_threeyear_2023 = $threeyear_new[$v_date['week']] ?? [];
                }

                $tmp_arr = [];
                $tmp_arr['年'] = $v_date['year'];
                $tmp_arr['周'] = '第'.$v_date['week'].'周';
                $tmp_arr['月'] = ($v_date['start_time'] ? substr($v_date['start_time'], 5, 2) : '').'月';
                $tmp_arr['周期'] = $v_date['周期'];

                //前年
                $tmp_arr['前年业绩占比'] = $each_threeyear_2021 ? $each_threeyear_2021['业绩占比'] : '';
                $tmp_arr['前年库存占比'] = $each_threeyear_2021 ? $each_threeyear_2021['库存占比'] : '';
                $tmp_arr['前年效率'] = $each_threeyear_2021 ? $each_threeyear_2021['效率'] : '';
                $tmp_arr['前年店铺数'] = $each_threeyear_2021 ? ($customer_num_list_new[$each_threeyear_2021['年'].$each_threeyear_2021['Week']] ?? '') : '';//
                $tmp_arr['前年销量(周)'] = $each_threeyear_2021 ? $each_threeyear_2021['销量(周)'] : '';
                $tmp_arr['前年库存量'] = $each_threeyear_2021 ? $each_threeyear_2021['库存量'] : '';
                $tmp_arr['前年折扣'] = $each_threeyear_2021 ? $each_threeyear_2021['折扣'] : '';
                $tmp_arr['前年店均周销量'] = $each_threeyear_2021 ? ($tmp_arr['前年店铺数'] ? round( ($each_threeyear_2021['店均周销量']/$tmp_arr['前年店铺数']), 1) : '') : '';//
                $tmp_arr['前年店均库存量'] = $each_threeyear_2021 ? ($tmp_arr['前年店铺数'] ? round( ($each_threeyear_2021['店均库存量']/$tmp_arr['前年店铺数']/7), 0) : '') : '';//
                $tmp_arr['前年店周转(天)'] = $each_threeyear_2021 ? ( $tmp_arr['前年店均周销量'] ? round( ($tmp_arr['前年店均库存量'] ?: 0)/$tmp_arr['前年店均周销量']*7, 0 ) : '' ) : '';//
                if ($each_threeyear_2021) {
                    $tmp_arr['前年最高温'] = isset($weather_new_arr[$each_threeyear_2021['周期']]) ? round($weather_new_arr[$each_threeyear_2021['周期']]['max_c'] / $weather_new_arr[$each_threeyear_2021['周期']]['customer_num'], 0) : '';
                    $tmp_arr['前年最低温'] = isset($weather_new_arr[$each_threeyear_2021['周期']]) ? round($weather_new_arr[$each_threeyear_2021['周期']]['min_c'] / $weather_new_arr[$each_threeyear_2021['周期']]['customer_num'], 0) : '';
                } else {
                    $tmp_arr['前年最高温'] = '';
                    $tmp_arr['前年最低温'] = '';
                }

                //去年
                $tmp_arr['去年业绩占比'] = $each_threeyear_2022 ? $each_threeyear_2022['业绩占比'] : '';
                $tmp_arr['去年库存占比'] = $each_threeyear_2022 ? $each_threeyear_2022['库存占比'] : '';
                $tmp_arr['去年效率'] = $each_threeyear_2022 ? $each_threeyear_2022['效率'] : '';
                $tmp_arr['去年店铺数'] = $each_threeyear_2022 ? ($customer_num_list_new[$each_threeyear_2022['年'].$each_threeyear_2022['Week']] ?? '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店铺数'] : '';
                $tmp_arr['去年销量(周)'] = $each_threeyear_2022 ? $each_threeyear_2022['销量(周)'] : '';
                $tmp_arr['去年库存量'] = $each_threeyear_2022 ? $each_threeyear_2022['库存量'] : '';
                $tmp_arr['去年折扣'] = $each_threeyear_2022 ? $each_threeyear_2022['折扣'] : '';
                $tmp_arr['去年店均周销量'] = $each_threeyear_2022 ? ($tmp_arr['去年店铺数'] ? round( ($each_threeyear_2022['店均周销量']/$tmp_arr['去年店铺数']), 1) : '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店均周销量'] : '';//
                $tmp_arr['去年店均库存量'] = $each_threeyear_2022 ? ($tmp_arr['去年店铺数'] ? round( ($each_threeyear_2022['店均库存量']/$tmp_arr['去年店铺数']/7), 0) : '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店均库存量'] : '';//
                $tmp_arr['去年店周转(天)'] = $each_threeyear_2022 ? ( $tmp_arr['去年店均周销量'] ? round( ($tmp_arr['去年店均库存量'] ?: 0)/$tmp_arr['去年店均周销量']*7, 0 ) : '' ) : '';////$each_threeyear_2022 ? $each_threeyear_2022['店周转(天)'] : '';//
                if ($each_threeyear_2022) {
                    $tmp_arr['去年最高温'] = isset($weather_new_arr[$each_threeyear_2022['周期']]) ? round($weather_new_arr[$each_threeyear_2022['周期']]['max_c'] / $weather_new_arr[$each_threeyear_2022['周期']]['customer_num'], 0) : '';
                    $tmp_arr['去年最低温'] = isset($weather_new_arr[$each_threeyear_2022['周期']]) ? round($weather_new_arr[$each_threeyear_2022['周期']]['min_c'] / $weather_new_arr[$each_threeyear_2022['周期']]['customer_num'], 0) : '';
                } else {
                    $tmp_arr['去年最高温'] = '';
                    $tmp_arr['去年最低温'] = '';
                }

                //今年
                $tmp_arr['今年业绩占比'] = $each_threeyear_2023 ? $each_threeyear_2023['业绩占比'] : '';
                $tmp_arr['今年库存占比'] = $each_threeyear_2023 ? $each_threeyear_2023['库存占比'] : '';
                $tmp_arr['今年效率'] = $each_threeyear_2023 ? $each_threeyear_2023['效率'] : '';
                $tmp_arr['今年店铺数'] = $each_threeyear_2023 ? ($customer_num_list_new[$each_threeyear_2023['年'].$each_threeyear_2023['Week']] ?? '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店铺数'] : '';//
                $tmp_arr['今年销量(周)'] = $each_threeyear_2023 ? $each_threeyear_2023['销量(周)'] : '';
                $tmp_arr['今年库存量'] = $each_threeyear_2023 ? $each_threeyear_2023['库存量'] : '';
                $tmp_arr['今年折扣'] = $each_threeyear_2023 ? $each_threeyear_2023['折扣'] : '';
                $tmp_arr['今年店均周销量'] = $each_threeyear_2023 ? ($tmp_arr['今年店铺数'] ? round( ($each_threeyear_2023['店均周销量']/$tmp_arr['今年店铺数']), 1) : '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店均周销量'] : '';//
                $tmp_arr['今年店均库存量'] = $each_threeyear_2023 ? ($tmp_arr['今年店铺数'] ? round( ($each_threeyear_2023['店均库存量']/$tmp_arr['今年店铺数']/7), 0) : '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店均库存量'] : '';//
                $tmp_arr['今年店周转(天)'] = $each_threeyear_2023 ? ( $tmp_arr['今年店均周销量'] ? round( ($tmp_arr['今年店均库存量'] ?: 0)/$tmp_arr['今年店均周销量']*7, 0 ) : '' ) : '';//$each_threeyear_2023 ? $each_threeyear_2023['店周转(天)'] : '';//
                if ($each_threeyear_2023) {
                    $tmp_arr['今年最高温'] = isset($weather_new_arr[$each_threeyear_2023['周期']]) ? round($weather_new_arr[$each_threeyear_2023['周期']]['max_c'] / $weather_new_arr[$each_threeyear_2023['周期']]['customer_num'], 0) : '';
                    $tmp_arr['今年最低温'] = isset($weather_new_arr[$each_threeyear_2023['周期']]) ? round($weather_new_arr[$each_threeyear_2023['周期']]['min_c'] / $weather_new_arr[$each_threeyear_2023['周期']]['customer_num'], 0) : '';
                } else {
                    $tmp_arr['今年最高温'] = '';
                    $tmp_arr['今年最低温'] = '';
                }

                $arr[] = $tmp_arr;

            }
        }

        // print_r($arr);die;
        return $arr;

    }


    public function getXmMapSelect() {

        $Year = [['name'=>'2021', 'value'=>'2021'], ['name'=>'2022', 'value'=>'2022'], ['name'=>'2023', 'value'=>'2023']];
        $Month = [
            ['name'=>'1', 'value'=>'01'], ['name'=>'2', 'value'=>'02'], ['name'=>'3', 'value'=>'03'],
            ['name'=>'4', 'value'=>'04'], ['name'=>'5', 'value'=>'05'], ['name'=>'6', 'value'=>'06'],
            ['name'=>'7', 'value'=>'07'], ['name'=>'8', 'value'=>'08'], ['name'=>'9', 'value'=>'09'],
            ['name'=>'10', 'value'=>'10'], ['name'=>'11', 'value'=>'11'], ['name'=>'12', 'value'=>'12'],
        ];
        $YunCang = [['name'=>'两广', 'value'=>'两广'], ['name'=>'长江以南', 'value'=>'长江以南'], ['name'=>'长江以北', 'value'=>'长江以北'], ['name'=>'西南片区', 'value'=>'西南片区']];
        $WenDai = $this->easy_db->query("select CustomItem30 as name, CustomItem30 as value from customer where CustomItem30<>'' group by CustomItem30;");
        $WenQu = $this->easy_db->query("select CustomItem36 as name, CustomItem36 as value from customer where CustomItem36<>'' group by CustomItem36;");
        $State = $this->easy_db->query("select State as name, State as value from customer  group by State;");
        $Mathod = [['name'=>'加盟', 'value'=>'加盟'], ['name'=>'直营', 'value'=>'直营']];
        $NewOld = [['name'=>'新品', 'value'=>'新品'], ['name'=>'旧品', 'value'=>'旧品']];
        $Season = [['name'=>'春季', 'value'=>'春季'], ['name'=>'夏季', 'value'=>'夏季'], ['name'=>'秋季', 'value'=>'秋季'], ['name'=>'冬季', 'value'=>'冬季']];
        $StyleCategoryName = [['name'=>'基本款', 'value'=>'基本款'], ['name'=>'引流款', 'value'=>'引流款']];
        $CategoryName1 = $this->easy_db->query("select 一级分类 as name, 一级分类 as value from sjp_goods group by 一级分类;");
        $CategoryName2 = $this->easy_db->query("select 二级分类 as name, 二级分类 as value from sjp_goods group by 二级分类;");
        $CategoryName = $this->easy_db->query("select 分类 as name, 分类 as value from sjp_goods group by 分类;");
        $CustomItem46 = [['name'=>'深色系', 'value'=>'深色系'], ['name'=>'浅色系', 'value'=>'浅色系']];

        return ['Year' => $Year, 'Month' => $Month, 'YunCang'=>$YunCang, 'WenDai'=>$WenDai, 'WenQu' => $WenQu, 
        'State' => $State, 'Mathod' => $Mathod, 'NewOld'=>$NewOld, 'Season'=>$Season, 'StyleCategoryName' => $StyleCategoryName, 
        'CategoryName1' => $CategoryName1, 'CategoryName2' => $CategoryName2, 'CategoryName'=>$CategoryName, 'CustomItem46'=>$CustomItem46,
        ];

    }







    public function index_bak($params) {

        //test....
        // $weather_data_model = new CusWeatherData();
        // $where_weather_2021[] = ['d.weather_time', 'like', ['%2021-01%'], 'or'];
        // $field_weather = "CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' ) as Start_time, 
        // max(d.weather_time) as End_time,   b.customer_name,max(d.max_c) as max_c, min(d.min_c) as min_c, 
        // CONCAT(SUBSTRING(  CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' )  , 6, 5), ' 到 ', SUBSTRING( max(d.weather_time) , 6, 5)) as '周期'";
        // $weather_2021 = $this->weather_data_model::where($where_weather_2021)->alias('d')->field($field_weather)
        //     ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
        // $weather_2021 = $weather_2021 ? $weather_2021->toArray() : [];
        // print_r($weather_2021);die;

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $Year = $params['Year'] ?? '';
        $Month = $params['Month'] ?? '';
        $YunCang = $params['YunCang'] ?? '';
        $WenDai = $params['WenDai'] ?? '';
        $WenQu = $params['WenQu'] ?? '';
        $State = $params['State'] ?? '';
        $Mathod = $params['Mathod'] ?? '';
        $NewOld = $params['NewOld'] ?? '';
        $Season = $params['Season'] ?? '';
        $StyleCategoryName = $params['StyleCategoryName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $CategoryName2 = $params['CategoryName2'] ?? '';
        $CategoryName = $params['CategoryName'] ?? '';
        $CustomItem46 = $params['CustomItem46'] ?? '';//深浅色

        $threeyear2_week_model = new SpCustomerStockSaleThreeyear2WeekModel();
        $week_date_model = new SpCustomerStockSaleWeekDateModel();
        $weather_data_model = new CusWeatherData();
        $weather_base_model = new CusWeatherBase();

        $where = $list = [];

        $week_dates = $this->week_date_model::where([['year', '=', '2023']])->field("year, week, start_time, end_time, CONCAT(SUBSTRING(start_time, 6, 5), ' 到 ', SUBSTRING(end_time, 6, 5)) as '周期'")->select();
        $week_dates = $week_dates ? $week_dates->toArray() : [];
        // print_r($week_dates);die;

        $field = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
        CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
        '100.0%' as '业绩占比', '100.0%' as '库存占比', '100.0%' as '效率', max(NUM) as '店铺数', sum(SaleQuantity) as '销量(周)', sum(StockQuantity) as '库存量', 
        CONCAT( Round(sum(SalesVolume)/sum(RetailAmount), 2)*100, '%') as '折扣',  sum(SaleQuantity) as '店均周销量', 
        sum(StockQuantity) as '店均库存量', '' as '店周转(天)'";
        $field_weather = "CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' ) as Start_time, 
        max(d.weather_time) as End_time,   b.customer_name,max(d.max_c) as max_c, min(d.min_c) as min_c, 
        CONCAT(SUBSTRING(  CONCAT( FROM_DAYS(TO_DAYS(d.weather_time) - MOD(TO_DAYS(d.weather_time) -2, 7)), ' 00:00:00' )  , 6, 5), ' 到 ', SUBSTRING( max(d.weather_time) , 6, 5)) as '周期'";

        
        //where条件组装
        $where_yeji_2021 = [['Year', '=', '2021']];
        $where_yeji_2022 = [['Year', '=', '2022']];
        $where_yeji_2023 = [['Year', '=', '2023']];

        $where_weather_2021 = [['d.weather_time', '>=', '2021-01-04'], ['d.weather_time', '<=', '2022-01-02']];
        $where_weather_2022 = [['d.weather_time', '>=', '2022-01-03'], ['d.weather_time', '<=', '2023-01-01']];
        $where_weather_2023 = [['d.weather_time', '>=', '2023-01-02']];

        $where_customer_2021 = [['Year', '=', '2021']];
        $where_customer_2022 = [['Year', '=', '2022']];
        $where_customer_2023 = [['Year', '=', '2023']];

        $where_customer_num = [];

        //选年份
        if ($Year) {
            switch ($Year) {
                case '2021': 
                    $where_yeji_2022 = [];
                    $where_yeji_2023 = [];

                    $where_weather_2022 = [];
                    $where_weather_2023 = [];

                    $where_customer_2022 = [];
                    $where_customer_2023 = [];
                    break;
                case '2022': 
                    $where_yeji_2021 = [];
                    $where_yeji_2023 = [];

                    $where_weather_2021 = [];
                    $where_weather_2023 = [];

                    $where_customer_2021 = [];
                    $where_customer_2023 = [];
                    break;
                case '2023': 
                    $where_yeji_2021 = [];
                    $where_yeji_2022 = [];

                    $where_weather_2021 = [];
                    $where_weather_2022 = [];

                    $where_customer_2021 = [];
                    $where_customer_2022 = [];
                    break;    
            }
            $where_customer_num[] = ['Year', '=', $Year];
        }

        //选月份
        if ($Month) {

            if ($where_yeji_2021) {
                $where_yeji_2021[] = ['Month', 'in', $Month];
            }
            if ($where_yeji_2022) {
                $where_yeji_2022[] = ['Month', 'in', $Month];
            }
            if ($where_yeji_2023) {
                $where_yeji_2023[] = ['Month', 'in', $Month];
            }

            if ($where_customer_2021) {
                $where_customer_2021[] = ['Month', 'in', $Month];
            }
            if ($where_customer_2022) {
                $where_customer_2022[] = ['Month', 'in', $Month];
            }
            if ($where_customer_2023) {
                $where_customer_2023[] = ['Month', 'in', $Month];
            }

            if ($Year) {
                
                $ex_month = explode(',', $Month);
                foreach ($ex_month as &$v_month) {
                    $v_month = "'%".$Year.'-'.$v_month."%'";
                }

                switch ($Year) {
                    case '2021': 
                        
                        $where_weather_2021[] = ['d.weather_time', 'like', $ex_month, 'or'];
                        $where_weather_2022 = [];
                        $where_weather_2023 = [];

                        break;
                    case '2022': 

                        $where_weather_2021 = [];
                        $where_weather_2022[] = ['d.weather_time', 'like', $ex_month, 'or'];
                        $where_weather_2023 = [];
                        
                        break;
                    case '2023': 
                        
                        $where_weather_2021 = [];
                        $where_weather_2022 = [];
                        $where_weather_2023[] = ['d.weather_time', 'like', $ex_month, 'or'];

                        break;    
                }

            }

            $where_customer_num[] = ['Month', 'in', $Month];

        }

        //大区
        if ($YunCang) {

            $where_yeji_2021[] = ['YunCang', '=', $YunCang];
            $where_yeji_2022[] = ['YunCang', '=', $YunCang];
            $where_yeji_2023[] = ['YunCang', '=', $YunCang];

            $which_yuncang = config('weather.yuncang_arr')[$YunCang];
            $where_weather_2021[] = ['b.yuncang', 'in', $which_yuncang];
            $where_weather_2022[] = ['b.yuncang', 'in', $which_yuncang];
            $where_weather_2023[] = ['b.yuncang', 'in', $which_yuncang];

            $where_customer_2021[] = ['YunCang', '=', $YunCang];
            $where_customer_2022[] = ['YunCang', '=', $YunCang];
            $where_customer_2023[] = ['YunCang', '=', $YunCang];

            $where_customer_num[] = ['YunCang', '=', $YunCang];

        }

        //温带
        if ($WenDai) {

            $where_yeji_2021[] = ['WenDai', 'in', $WenDai];
            $where_yeji_2022[] = ['WenDai', 'in', $WenDai];
            $where_yeji_2023[] = ['WenDai', 'in', $WenDai];

            $where_weather_2021[] = ['b.wendai', 'in', $WenDai];
            $where_weather_2022[] = ['b.wendai', 'in', $WenDai];
            $where_weather_2023[] = ['b.wendai', 'in', $WenDai];

            $where_customer_2021[] = ['WenDai', 'in', $WenDai];
            $where_customer_2022[] = ['WenDai', 'in', $WenDai];
            $where_customer_2023[] = ['WenDai', 'in', $WenDai];

            $where_customer_num[] = ['WenDai', 'in', $WenDai];

        }

        //温区
        if ($WenQu) {

            $where_yeji_2021[] = ['WenQu', 'in', $WenQu];
            $where_yeji_2022[] = ['WenQu', 'in', $WenQu];
            $where_yeji_2023[] = ['WenQu', 'in', $WenQu];

            $where_weather_2021[] = ['b.wenqu', 'in', $WenQu];
            $where_weather_2022[] = ['b.wenqu', 'in', $WenQu];
            $where_weather_2023[] = ['b.wenqu', 'in', $WenQu];

            $where_customer_2021[] = ['WenQu', 'in', $WenQu];
            $where_customer_2022[] = ['WenQu', 'in', $WenQu];
            $where_customer_2023[] = ['WenQu', 'in', $WenQu];

            $where_customer_num[] = ['WenQu', 'in', $WenQu];

        }

        //省份
        if ($State) {

            $where_yeji_2021[] = ['State', 'in', $State];
            $where_yeji_2022[] = ['State', 'in', $State];
            $where_yeji_2023[] = ['State', 'in', $State];

            $where_weather_2021[] = ['b.province', 'in', $State];
            $where_weather_2022[] = ['b.province', 'in', $State];
            $where_weather_2023[] = ['b.province', 'in', $State];

            $where_customer_2021[] = ['State', 'in', $State];
            $where_customer_2022[] = ['State', 'in', $State];
            $where_customer_2023[] = ['State', 'in', $State];

            $where_customer_num[] = ['State', 'in', $State];

        }

        //经营模式
        if ($Mathod) {

            $where_yeji_2021[] = ['Mathod', '=', $Mathod];
            $where_yeji_2022[] = ['Mathod', '=', $Mathod];
            $where_yeji_2023[] = ['Mathod', '=', $Mathod];

            $where_weather_2021[] = ['b.store_type', '=', $Mathod];
            $where_weather_2022[] = ['b.store_type', '=', $Mathod];
            $where_weather_2023[] = ['b.store_type', '=', $Mathod];

            $where_customer_2021[] = ['Mathod', '=', $Mathod];
            $where_customer_2022[] = ['Mathod', '=', $Mathod];
            $where_customer_2023[] = ['Mathod', '=', $Mathod];

            $where_customer_num[] = ['Mathod', '=', $Mathod];

        }

        ##############################货品属性筛选####################################

        //新旧品
        if ($NewOld) {

            $cur_year = date('Y');
            if ($NewOld == '新品') {

                $where_yeji_2021[] = ['TimeCategoryName1', '=', $cur_year];
                $where_yeji_2022[] = ['TimeCategoryName1', '=', $cur_year];
                $where_yeji_2023[] = ['TimeCategoryName1', '=', $cur_year];

            } else {

                $where_yeji_2021[] = ['TimeCategoryName1', '<', $cur_year];
                $where_yeji_2022[] = ['TimeCategoryName1', '<', $cur_year];
                $where_yeji_2023[] = ['TimeCategoryName1', '<', $cur_year];

            }

        }

        //季节
        if ($Season) {

            $where_yeji_2021[] = ['Season', 'in', $Season];
            $where_yeji_2022[] = ['Season', 'in', $Season];
            $where_yeji_2023[] = ['Season', 'in', $Season];

        }

        //风格
        if ($StyleCategoryName) {

            $where_yeji_2021[] = ['StyleCategoryName', '=', $StyleCategoryName];
            $where_yeji_2022[] = ['StyleCategoryName', '=', $StyleCategoryName];
            $where_yeji_2023[] = ['StyleCategoryName', '=', $StyleCategoryName];

        }

        //一级分类
        if ($CategoryName1) {

            $where_yeji_2021[] = ['CategoryName1', 'in', $CategoryName1];
            $where_yeji_2022[] = ['CategoryName1', 'in', $CategoryName1];
            $where_yeji_2023[] = ['CategoryName1', 'in', $CategoryName1];

        }

        //二级分类
        if ($CategoryName2) {

            $where_yeji_2021[] = ['CategoryName2', 'in', $CategoryName2];
            $where_yeji_2022[] = ['CategoryName2', 'in', $CategoryName2];
            $where_yeji_2023[] = ['CategoryName2', 'in', $CategoryName2];

        }

        //分类
        if ($CategoryName) {

            $where_yeji_2021[] = ['CategoryName', 'in', $CategoryName];
            $where_yeji_2022[] = ['CategoryName', 'in', $CategoryName];
            $where_yeji_2023[] = ['CategoryName', 'in', $CategoryName];

        }

        //深浅色
        if ($CustomItem46) {

            $where_yeji_2021[] = ['CustomItem46', '=', $CustomItem46];
            $where_yeji_2022[] = ['CustomItem46', '=', $CustomItem46];
            $where_yeji_2023[] = ['CustomItem46', '=', $CustomItem46];

        }

        
        //业绩占比/库存占比/效率 计算(当有选择货品属性的情况)
        $customer_threeyear_2021 = $customer_threeyear_2022 = $customer_threeyear_2023 = [];
        $if_select_goods = 0;
        if ($NewOld || $Season || $StyleCategoryName || $CategoryName1 || $CategoryName2 || $CategoryName || $CustomItem46) {
            $if_select_goods = 1;

            $field = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
        CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
        sum(SalesVolume) as '业绩', sum(StockCost) as '库存', '100.0%' as '效率', max(NUM) as '店铺数', sum(SaleQuantity) as '销量(周)', sum(StockQuantity) as '库存量', 
        CONCAT( Round(sum(SalesVolume)/sum(RetailAmount), 2)*100, '%') as '折扣',  sum(SaleQuantity) as '店均周销量', 
        sum(StockQuantity) as '店均库存量', '' as '店周转(天)'";

            $field_customer = "Week, Start_time, End_time, Year as '年', CONCAT('第', Week, '周') as '周', CONCAT(Month, '月') as '月', 
        CONCAT(SUBSTRING(Start_time, 6, 5), ' 到 ', SUBSTRING(End_time, 6, 5)) as '周期', 
        sum(SalesVolume) as '业绩', sum(StockCost) as '库存'";

            if ($where_customer_2021) {
                $customer_threeyear_2021 = $this->threeyear2_week_model::where($where_customer_2021)->group('Week')->field($field_customer)->select();
                $customer_threeyear_2021 = $customer_threeyear_2021 ? $customer_threeyear_2021->toArray() : [];
                $customer_threeyear_2021_weeks = $customer_threeyear_2021 ? array_column($customer_threeyear_2021, 'Week') : [];
                $customer_threeyear_2021 = array_combine($customer_threeyear_2021_weeks, $customer_threeyear_2021);
            }

            if ($where_customer_2022) {
                $customer_threeyear_2022 = $this->threeyear2_week_model::where($where_customer_2022)->group('Week')->field($field_customer)->select();
                $customer_threeyear_2022 = $customer_threeyear_2022 ? $customer_threeyear_2022->toArray() : [];
                $customer_threeyear_2022_weeks = $customer_threeyear_2022 ? array_column($customer_threeyear_2022, 'Week') : [];
                $customer_threeyear_2022 = array_combine($customer_threeyear_2022_weeks, $customer_threeyear_2022);
            }

            if ($where_customer_2023) {
                $customer_threeyear_2023 = $this->threeyear2_week_model::where($where_customer_2023)->group('Week')->field($field_customer)->select();
                $customer_threeyear_2023 = $customer_threeyear_2023 ? $customer_threeyear_2023->toArray() : [];
                $customer_threeyear_2023_weeks = $customer_threeyear_2023 ? array_column($customer_threeyear_2023, 'Week') : [];
                $customer_threeyear_2023 = array_combine($customer_threeyear_2023_weeks, $customer_threeyear_2023);
            }

        }


        //店铺数获取处理(当有店铺属性筛选时有效)
        $customer_num_list = $this->threeyear2_week_model::where($where_customer_num)->group('aa,year_week')->field("Year, Week, concat(YunCang, WenDai, WenQu, State, Mathod) as aa, max(NUM) as max_num, concat(Year, Week) as year_week")->select();
        $customer_num_list = $customer_num_list ? $customer_num_list->toArray() : [];
        $customer_num_list_new = [];
        if ($customer_num_list) {
            foreach ($customer_num_list as $v_customer_num_list) {
                if (isset($customer_num_list_new[$v_customer_num_list['year_week']])) {
                    $customer_num_list_new[$v_customer_num_list['year_week']] += $v_customer_num_list['max_num'];
                } else {
                    $customer_num_list_new[$v_customer_num_list['year_week']] = $v_customer_num_list['max_num'];
                }
            }
        }
        // print_r($customer_num_list_new);die;


        $threeyear_2021 = $threeyear_2022 = $threeyear_2023 = [];
        //2021年业绩、库存等情况
        if ($where_yeji_2021) {
            $threeyear_2021 = $this->threeyear2_week_model::where($where_yeji_2021)->group('Week')->field($field)->select();
            $threeyear_2021 = $threeyear_2021 ? $threeyear_2021->toArray() : [];
            $threeyear_2021_weeks = $threeyear_2021 ? array_column($threeyear_2021, 'Week') : [];
            $threeyear_2021 = array_combine($threeyear_2021_weeks, $threeyear_2021);

            if ($threeyear_2021) {
                if ($if_select_goods) {
                    foreach ($threeyear_2021 as $k_threeyear_2021=>&$v_threeyear_2021) {
                        
                        $yeji_num = ($customer_threeyear_2021 && isset($customer_threeyear_2021[$k_threeyear_2021])) ? (round($v_threeyear_2021['业绩']/$customer_threeyear_2021[$k_threeyear_2021]['业绩'], 1) * 100) : 0;
                        $kucun_num = ($customer_threeyear_2021 && isset($customer_threeyear_2021[$k_threeyear_2021])) ? (round($v_threeyear_2021['库存']/$customer_threeyear_2021[$k_threeyear_2021]['库存'], 1) * 100) : 0;
                        $v_threeyear_2021['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                        $v_threeyear_2021['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                        $v_threeyear_2021['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
    
                    }
                }
            }
            // print_r($threeyear_2021);die;
        }

        //2022年业绩、库存等情况
        if ($where_yeji_2022) {

            $threeyear_2022 = $this->threeyear2_week_model::where($where_yeji_2022)->group('Week')->field($field)->select();
            $threeyear_2022 = $threeyear_2022 ? $threeyear_2022->toArray() : [];
            $threeyear_2022_weeks = $threeyear_2022 ? array_column($threeyear_2022, 'Week') : [];
            $threeyear_2022 = array_combine($threeyear_2022_weeks, $threeyear_2022);

            if ($threeyear_2022) {
                if ($if_select_goods) {
                    foreach ($threeyear_2022 as $k_threeyear_2022=>&$v_threeyear_2022) {
                        
                        $yeji_num = ($customer_threeyear_2022 && isset($customer_threeyear_2022[$k_threeyear_2022])) ? (round($v_threeyear_2022['业绩']/$customer_threeyear_2022[$k_threeyear_2022]['业绩'], 1) * 100) : 0;
                        $kucun_num = ($customer_threeyear_2022 && isset($customer_threeyear_2022[$k_threeyear_2022])) ? (round($v_threeyear_2022['库存']/$customer_threeyear_2022[$k_threeyear_2022]['库存'], 1) * 100) : 0;
                        $v_threeyear_2022['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                        $v_threeyear_2022['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                        $v_threeyear_2022['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
    
                    }
                }
            }
            // print_r($threeyear_2022);die;
        }

        //2023年业绩、库存等情况
        if ($where_yeji_2023) {
            $threeyear_2023 = $this->threeyear2_week_model::where($where_yeji_2023)->group('Week')->field($field)->select();
            $threeyear_2023 = $threeyear_2023 ? $threeyear_2023->toArray() : [];
            $threeyear_2023_weeks = $threeyear_2023 ? array_column($threeyear_2023, 'Week') : [];
            $threeyear_2023 = array_combine($threeyear_2023_weeks, $threeyear_2023);

            if ($threeyear_2023) {
                if ($if_select_goods) {
                    foreach ($threeyear_2023 as $k_threeyear_2023=>&$v_threeyear_2023) {
                        
                        $yeji_num = ($customer_threeyear_2023 && isset($customer_threeyear_2023[$k_threeyear_2023])) ? (round($v_threeyear_2023['业绩']/$customer_threeyear_2023[$k_threeyear_2023]['业绩'], 1) * 100) : 0;
                        $kucun_num = ($customer_threeyear_2023 && isset($customer_threeyear_2023[$k_threeyear_2023])) ? (round($v_threeyear_2023['库存']/$customer_threeyear_2023[$k_threeyear_2023]['库存'], 1) * 100) : 0;
                        $v_threeyear_2023['业绩占比'] = $yeji_num ? $yeji_num.'%' : '';
                        $v_threeyear_2023['库存占比'] = $kucun_num ? $kucun_num.'%' : '';
                        $v_threeyear_2023['效率'] = $kucun_num ? (round($yeji_num/$kucun_num, 2)*100).'%' : '';
    
                    }
                }
            }
        }
        // print_r($threeyear_2023);die;


        $weather_2021 = $weather_2022 = $weather_2023 = [];
        //最高温、最低温 2021
        if ($where_weather_2021) {
            $weather_2021 = $this->weather_data_model::where($where_weather_2021)->alias('d')->field($field_weather)
            ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
            $weather_2021 = $weather_2021 ? $weather_2021->toArray() : [];
        }
        $weather_2021_arr = [];
        if ($weather_2021) {
            foreach ($weather_2021 as $v_weather_2021) {
                $zhouqi = $v_weather_2021['周期'];
                $weather_2021_arr[$zhouqi][] = $v_weather_2021;
                if (isset($weather_2021_arr[$zhouqi]['max_c'])) {
                    $weather_2021_arr[$zhouqi]['max_c'] += $v_weather_2021['max_c'];
                } else {
                    $weather_2021_arr[$zhouqi]['max_c'] = $v_weather_2021['max_c'];
                }
                if (isset($weather_2021_arr[$zhouqi]['min_c'])) {
                    $weather_2021_arr[$zhouqi]['min_c'] += $v_weather_2021['min_c'];
                } else {
                    $weather_2021_arr[$zhouqi]['min_c'] = $v_weather_2021['min_c'];
                }
                if (isset($weather_2021_arr[$zhouqi]['customer_num'])) {
                    $weather_2021_arr[$zhouqi]['customer_num'] += 1;
                } else {
                    $weather_2021_arr[$zhouqi]['customer_num'] = 1;
                }
            }
        }
        // print_r($weather_2021_arr);die;

        //最高温、最低温 2022
        if ($where_weather_2022) {
            $weather_2022 = $this->weather_data_model::where($where_weather_2022)->alias('d')->field($field_weather)
            ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
            $weather_2022 = $weather_2022 ? $weather_2022->toArray() : [];
        }
        $weather_2022_arr = [];
        if ($weather_2022) {
            foreach ($weather_2022 as $v_weather_2022) {
                $zhouqi = $v_weather_2022['周期'];
                $weather_2022_arr[$zhouqi][] = $v_weather_2022;
                if (isset($weather_2022_arr[$zhouqi]['max_c'])) {
                    $weather_2022_arr[$zhouqi]['max_c'] += $v_weather_2022['max_c'];
                } else {
                    $weather_2022_arr[$zhouqi]['max_c'] = $v_weather_2022['max_c'];
                }
                if (isset($weather_2022_arr[$zhouqi]['min_c'])) {
                    $weather_2022_arr[$zhouqi]['min_c'] += $v_weather_2022['min_c'];
                } else {
                    $weather_2022_arr[$zhouqi]['min_c'] = $v_weather_2022['min_c'];
                }
                if (isset($weather_2022_arr[$zhouqi]['customer_num'])) {
                    $weather_2022_arr[$zhouqi]['customer_num'] += 1;
                } else {
                    $weather_2022_arr[$zhouqi]['customer_num'] = 1;
                }
            }
        }
        // print_r($weather_2022_arr);die;

        // print_r($where_weather_2023);die;
        //最高温、最低温 2023
        if ($where_weather_2023) {
            $weather_2023 = $this->weather_data_model::where($where_weather_2023)->alias('d')->field($field_weather)
            ->join(['cus_weather_base' => 'b'], 'd.weather_prefix=b.weather_prefix', 'left')->group('Start_time, b.customer_name')->select();
            $weather_2023 = $weather_2023 ? $weather_2023->toArray() : [];
        }
        // print_r($weather_2023);die;
        $weather_2023_arr = [];
        if ($weather_2023) {
            foreach ($weather_2023 as $v_weather_2023) {
                $zhouqi = $v_weather_2023['周期'];
                $weather_2023_arr[$zhouqi][] = $v_weather_2023;
                if (isset($weather_2023_arr[$zhouqi]['max_c'])) {
                    $weather_2023_arr[$zhouqi]['max_c'] += $v_weather_2023['max_c'];
                } else {
                    $weather_2023_arr[$zhouqi]['max_c'] = $v_weather_2023['max_c'];
                }
                if (isset($weather_2023_arr[$zhouqi]['min_c'])) {
                    $weather_2023_arr[$zhouqi]['min_c'] += $v_weather_2023['min_c'];
                } else {
                    $weather_2023_arr[$zhouqi]['min_c'] = $v_weather_2023['min_c'];
                }
                if (isset($weather_2023_arr[$zhouqi]['customer_num'])) {
                    $weather_2023_arr[$zhouqi]['customer_num'] += 1;
                } else {
                    $weather_2023_arr[$zhouqi]['customer_num'] = 1;
                }
            }
        }
        // print_r($weather_2023_arr);die;

        //重新组装出最终数据
        $arr = [];
        if ($week_dates) {
            foreach ($week_dates as $v_date) {

                $each_threeyear_2021 = $threeyear_2021[$v_date['week']] ?? [];
                $each_threeyear_2022 = $threeyear_2022[$v_date['week']] ?? [];
                $each_threeyear_2023 = $threeyear_2023[$v_date['week']] ?? [];

                $tmp_arr = [];
                $tmp_arr['年'] = $v_date['year'];
                $tmp_arr['周'] = '第'.$v_date['week'].'周';
                $tmp_arr['月'] = ($v_date['start_time'] ? substr($v_date['start_time'], 5, 2) : '').'月';
                $tmp_arr['周期'] = $v_date['周期'];

                //前年
                $tmp_arr['前年业绩占比'] = $each_threeyear_2021 ? $each_threeyear_2021['业绩占比'] : '';
                $tmp_arr['前年库存占比'] = $each_threeyear_2021 ? $each_threeyear_2021['库存占比'] : '';
                $tmp_arr['前年效率'] = $each_threeyear_2021 ? $each_threeyear_2021['效率'] : '';
                $tmp_arr['前年店铺数'] = $each_threeyear_2021 ? ($customer_num_list_new[$each_threeyear_2021['年'].$each_threeyear_2021['Week']] ?? '') : '';//
                $tmp_arr['前年销量(周)'] = $each_threeyear_2021 ? $each_threeyear_2021['销量(周)'] : '';
                $tmp_arr['前年库存量'] = $each_threeyear_2021 ? $each_threeyear_2021['库存量'] : '';
                $tmp_arr['前年折扣'] = $each_threeyear_2021 ? $each_threeyear_2021['折扣'] : '';
                $tmp_arr['前年店均周销量'] = $each_threeyear_2021 ? ($tmp_arr['前年店铺数'] ? round( ($each_threeyear_2021['店均周销量']/$tmp_arr['前年店铺数']), 1) : '') : '';//
                $tmp_arr['前年店均库存量'] = $each_threeyear_2021 ? ($tmp_arr['前年店铺数'] ? round( ($each_threeyear_2021['店均库存量']/$tmp_arr['前年店铺数']/7), 0) : '') : '';//
                $tmp_arr['前年店周转(天)'] = $each_threeyear_2021 ? ( $tmp_arr['前年店均周销量'] ? round( ($tmp_arr['前年店均库存量'] ?: 0)/$tmp_arr['前年店均周销量']*7, 0 ) : '' ) : '';//
                if ($each_threeyear_2021) {
                    $tmp_arr['前年最高温'] = isset($weather_2021_arr[$each_threeyear_2021['周期']]) ? round($weather_2021_arr[$each_threeyear_2021['周期']]['max_c'] / $weather_2021_arr[$each_threeyear_2021['周期']]['customer_num'], 0) : '';
                    $tmp_arr['前年最低温'] = isset($weather_2021_arr[$each_threeyear_2021['周期']]) ? round($weather_2021_arr[$each_threeyear_2021['周期']]['min_c'] / $weather_2021_arr[$each_threeyear_2021['周期']]['customer_num'], 0) : '';
                } else {
                    $tmp_arr['前年最高温'] = '';
                    $tmp_arr['前年最低温'] = '';
                }

                //去年
                $tmp_arr['去年业绩占比'] = $each_threeyear_2022 ? $each_threeyear_2022['业绩占比'] : '';
                $tmp_arr['去年库存占比'] = $each_threeyear_2022 ? $each_threeyear_2022['库存占比'] : '';
                $tmp_arr['去年效率'] = $each_threeyear_2022 ? $each_threeyear_2022['效率'] : '';
                $tmp_arr['去年店铺数'] = $each_threeyear_2022 ? ($customer_num_list_new[$each_threeyear_2022['年'].$each_threeyear_2022['Week']] ?? '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店铺数'] : '';
                $tmp_arr['去年销量(周)'] = $each_threeyear_2022 ? $each_threeyear_2022['销量(周)'] : '';
                $tmp_arr['去年库存量'] = $each_threeyear_2022 ? $each_threeyear_2022['库存量'] : '';
                $tmp_arr['去年折扣'] = $each_threeyear_2022 ? $each_threeyear_2022['折扣'] : '';
                $tmp_arr['去年店均周销量'] = $each_threeyear_2022 ? ($tmp_arr['去年店铺数'] ? round( ($each_threeyear_2022['店均周销量']/$tmp_arr['去年店铺数']), 1) : '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店均周销量'] : '';//
                $tmp_arr['去年店均库存量'] = $each_threeyear_2022 ? ($tmp_arr['去年店铺数'] ? round( ($each_threeyear_2022['店均库存量']/$tmp_arr['去年店铺数']/7), 0) : '') : '';////$each_threeyear_2022 ? $each_threeyear_2022['店均库存量'] : '';//
                $tmp_arr['去年店周转(天)'] = $each_threeyear_2022 ? ( $tmp_arr['去年店均周销量'] ? round( ($tmp_arr['去年店均库存量'] ?: 0)/$tmp_arr['去年店均周销量']*7, 0 ) : '' ) : '';////$each_threeyear_2022 ? $each_threeyear_2022['店周转(天)'] : '';//
                if ($each_threeyear_2022) {
                    $tmp_arr['去年最高温'] = isset($weather_2022_arr[$each_threeyear_2022['周期']]) ? round($weather_2022_arr[$each_threeyear_2022['周期']]['max_c'] / $weather_2022_arr[$each_threeyear_2022['周期']]['customer_num'], 0) : '';
                    $tmp_arr['去年最低温'] = isset($weather_2022_arr[$each_threeyear_2022['周期']]) ? round($weather_2022_arr[$each_threeyear_2022['周期']]['min_c'] / $weather_2022_arr[$each_threeyear_2022['周期']]['customer_num'], 0) : '';
                } else {
                    $tmp_arr['去年最高温'] = '';
                    $tmp_arr['去年最低温'] = '';
                }

                //今年
                $tmp_arr['今年业绩占比'] = $each_threeyear_2023 ? $each_threeyear_2023['业绩占比'] : '';
                $tmp_arr['今年库存占比'] = $each_threeyear_2023 ? $each_threeyear_2023['库存占比'] : '';
                $tmp_arr['今年效率'] = $each_threeyear_2023 ? $each_threeyear_2023['效率'] : '';
                $tmp_arr['今年店铺数'] = $each_threeyear_2023 ? ($customer_num_list_new[$each_threeyear_2023['年'].$each_threeyear_2023['Week']] ?? '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店铺数'] : '';//
                $tmp_arr['今年销量(周)'] = $each_threeyear_2023 ? $each_threeyear_2023['销量(周)'] : '';
                $tmp_arr['今年库存量'] = $each_threeyear_2023 ? $each_threeyear_2023['库存量'] : '';
                $tmp_arr['今年折扣'] = $each_threeyear_2023 ? $each_threeyear_2023['折扣'] : '';
                $tmp_arr['今年店均周销量'] = $each_threeyear_2023 ? ($tmp_arr['今年店铺数'] ? round( ($each_threeyear_2023['店均周销量']/$tmp_arr['今年店铺数']), 1) : '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店均周销量'] : '';//
                $tmp_arr['今年店均库存量'] = $each_threeyear_2023 ? ($tmp_arr['今年店铺数'] ? round( ($each_threeyear_2023['店均库存量']/$tmp_arr['今年店铺数']/7), 0) : '') : '';//$each_threeyear_2023 ? $each_threeyear_2023['店均库存量'] : '';//
                $tmp_arr['今年店周转(天)'] = $each_threeyear_2023 ? ( $tmp_arr['今年店均周销量'] ? round( ($tmp_arr['今年店均库存量'] ?: 0)/$tmp_arr['今年店均周销量']*7, 0 ) : '' ) : '';//$each_threeyear_2023 ? $each_threeyear_2023['店周转(天)'] : '';//
                if ($each_threeyear_2023) {
                    $tmp_arr['今年最高温'] = isset($weather_2023_arr[$each_threeyear_2023['周期']]) ? round($weather_2023_arr[$each_threeyear_2023['周期']]['max_c'] / $weather_2023_arr[$each_threeyear_2023['周期']]['customer_num'], 0) : '';
                    $tmp_arr['今年最低温'] = isset($weather_2023_arr[$each_threeyear_2023['周期']]) ? round($weather_2023_arr[$each_threeyear_2023['周期']]['min_c'] / $weather_2023_arr[$each_threeyear_2023['周期']]['customer_num'], 0) : '';
                } else {
                    $tmp_arr['今年最高温'] = '';
                    $tmp_arr['今年最低温'] = '';
                }

                $arr[] = $tmp_arr;

            }
        }

        // print_r($arr);die;
        return $arr;



    }

    


}