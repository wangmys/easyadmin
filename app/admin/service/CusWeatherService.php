<?php

//skc 服务层
namespace app\admin\service;
use app\admin\model\weather\CusWeatherBase;
use app\admin\model\weather\CusWeatherData;
use app\admin\model\weather\CusWeatherDataCapital;
use app\admin\model\weather\CusWeatherUrl;
use app\admin\model\weather\CusWeatherOutput;
use app\common\traits\Singleton;
use think\facade\Db;

class CusWeatherService
{

    use Singleton;

    public function get_cus_weather_excel($code, $params, $field='cwb.weather_prefix, cwb.customer_name, cwb.province, cwb.city, cwb.area, cwb.store_type, cwb.wendai, cwb.wenqu, cwb.goods_manager, cwb.yuncang, cwb.store_level, cwb.nanzhongbei,  cwd.min_c, cwd.max_c, cwd.weather_time') {

        $pageLimit = $params['limit'] ?? 1000;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $where = $this->return_where($params);

        $count = CusWeatherData::field($field)->alias('cwd')->
        join('cus_weather_base cwb', 'cwd.weather_prefix=cwb.weather_prefix', 'LEFT')->where($where)->order('cwd.id asc')->count();

        if ($count > config('weather.init_output_num')) {

            // CusWeatherOutput::create(['code' => $code]);

            //采用其他方案生成得到excel
            $data = [
                'count' => $count,
                'data'  => [],
                'sign'  => 'other',
            ];
        } else {
            $list = CusWeatherData::field($field)->alias('cwd')->
            join('cus_weather_base cwb', 'cwd.weather_prefix=cwb.weather_prefix', 'LEFT')->where($where)->order('cwd.id asc')
            ->paginate([
                'list_rows'=> $pageLimit,
                'page' => $page,
            ]);
            $list = $list ? $list->toArray() : [];
            $data = [
                'count' => $list ? $list['total'] : 0,
                'data'  => $list ? $list['data'] : 0,
                'sign'  => 'normal',
            ];

        }
        
        return $data;

    }

    protected function return_where($params) {

        $customer_name = $params['customer_name'] ?? '';
        $customer_批量 = $params['店铺批量'] ?? '';
        $province = $params['province'] ?? '';
        $city = $params['city'] ?? '';
        $area = $params['area'] ?? '';
        $store_type = $params['store_type'] ?? '';
        $wendai = $params['wendai'] ?? '';
        $wenqu = $params['wenqu'] ?? '';
        $goods_manager = $params['goods_manager'] ?? '';
        $yuncang = $params['yuncang'] ?? '';
        $store_level = $params['store_level'] ?? '';
        $nanzhongbei = $params['nanzhongbei'] ?? '';
        $setTime1 = $params['setTime1'] ?? '';
        $setTime2 = $params['setTime2'] ?? '';

        $where = [];
        $where[] = ['cwb.weather_prefix', '<>', ''];
        if ($customer_name && !$customer_批量) {
            $where[] = ['cwb.customer_name', 'in', $customer_name];
        }
        // cwl修改
        if ($customer_批量) {
            $customer_批量;
            
            $exploadDate = explode(' ', $customer_批量);
            // dump($exploadDate);die;
            $map = "";
            foreach ($exploadDate as $key => $val) {
                $map .=  $val . ",";
            }

            $where[] = ['cwb.customer_name', 'in', $map];
        } 
        // echo '<pre>';
        // print_r($where);
        if ($province) {
            $where[] = ['cwb.province', 'in', $province];
        }
        if ($city) {
            $where[] = ['cwb.city', 'in', $city];
        }
        if ($area) {
            $where[] = ['cwb.area', 'in', $area];
        }
        if ($store_type) {
            $where[] = ['cwb.store_type', 'in', $store_type];
        }
        if ($wendai) {
            $where[] = ['cwb.wendai', 'in', $wendai];
        }
        if ($wenqu) {
            $where[] = ['cwb.wenqu', 'in', $wenqu];
        }
        if ($goods_manager) {
            $where[] = ['cwb.goods_manager', 'in', $goods_manager];
        }
        if ($yuncang) {
            $where[] = ['cwb.yuncang', 'in', $yuncang];
        }
        if ($store_level) {
            $where[] = ['cwb.store_level', 'in', $store_level];
        }
        // if ($nanzhongbei) {
        //     $where[] = ['cwb.nanzhongbei', 'in', $nanzhongbei];
        // }
        if ($setTime1 && $setTime2) {
            $where[] = ['cwd.weather_time', 'between', [$setTime1, $setTime2]];
        }
        // print_r($where);die;
        return $where;

    }

    protected function return_where_capital($params) {

        $province = $params['province'] ?? '';
        $yuncang = $params['yuncang'] ?? '';
        $setTime1 = $params['setTime1'] ?? '';
        $setTime2 = $params['setTime2'] ?? '';

        $where = [];
        $where[] = ['cwbc.weather_prefix', '<>', ''];
        if ($province) {
            $where[] = ['cwbc.province', 'in', $province];
        }
        if ($yuncang) {
            $where[] = ['cwbc.yuncang', 'in', $yuncang];
        }
        if ($setTime1 && $setTime2) {
            $where[] = ['cwdc.weather_time', 'between', [$setTime1, $setTime2]];
        }
        // print_r($where);die;
        return $where;

    }

    public function get_cus_weather($params, $field='cwb.weather_prefix, cwb.customer_name, cwb.province, cwb.city, cwb.area, cwb.store_type, cwb.wendai, cwb.wenqu, cwb.goods_manager, cwb.yuncang, cwb.store_level, cwb.nanzhongbei,  cwd.min_c, cwd.max_c, SUBSTRING(cwd.weather_time, 1, 10) as weather_time') {

        $pageLimit = $params['limit'] ?? 1000;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $where = $this->return_where($params);

        $list = CusWeatherData::field($field)->alias('cwd')->
        join('cus_weather_base cwb', 'cwd.weather_prefix=cwb.weather_prefix', 'LEFT')->where($where)->order('cwd.id asc')
        // ->fetchSql(1)
        ->paginate([
            'list_rows'=> $pageLimit,
            'page' => $page,
        ]);
        // echo CusWeatherData::getLastSql();die;
        // echo $list;die;
        $list = $list ? $list->toArray() : [];
        $data = [
            'count' => $list ? $list['total'] : 0,
            'data'  => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    //获取统计数
    public function get_cus_weather_count($params) {

        $pageLimit = $params['limit'] ?? 1000;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $where = $this->return_where($params);

        $count = CusWeatherData::field('cwb.weather_prefix, cwb.customer_name, cwb.province, cwb.city, cwb.area, cwb.store_type, cwb.wendai, cwb.wenqu, cwb.goods_manager, cwb.yuncang, cwb.store_level, cwb.nanzhongbei,  cwd.min_c, cwd.max_c, cwd.weather_time')->alias('cwd')->
        join('cus_weather_base cwb', 'cwd.weather_prefix=cwb.weather_prefix', 'LEFT')->where($where)->order('cwd.id asc')->count();
        return $count;

    }

    //获取新店数据
    public function get_customer_list() {

        $customer_list = CusWeatherBase::field('id, weather_prefix, customer_name, province, city, area')->where([['weather_prefix', '=', '']])->select();
        $customer_list = $customer_list ? $customer_list->toArray() : [];
        return $customer_list;

    }

    //保存新店数据
    public function save_customer_info($post) {

        return CusWeatherBase::where([['id', '=', $post['id']]])->update(['weather_prefix' => $post['weather_prefix']]);

    }


    public function get_capital_weather($params, $field='cwbc.weather_prefix, cwbc.yuncang, cwbc.province, cwbc.city, cwdc.min_c, cwdc.max_c, SUBSTRING(cwdc.weather_time, 1, 10) as weather_time') {

        $pageLimit = $params['limit'] ?? 1000;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $where = $this->return_where_capital($params);

        $list = CusWeatherDataCapital::field($field)->alias('cwdc')->
        join('cus_weather_base_capital cwbc', 'cwdc.weather_prefix=cwbc.weather_prefix', 'LEFT')->where($where)->order('cwdc.id asc')
        ->paginate([
            'list_rows'=> $pageLimit,
            'page' => $page,
        ]);
        $list = $list ? $list->toArray() : [];
        $data = [
            'count' => $list ? $list['total'] : 0,
            'data'  => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    //获取统计数(省会)
    public function get_capital_weather_count($params) {

        $pageLimit = $params['limit'] ?? 1000;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $where = $this->return_where_capital($params);

        $count = CusWeatherDataCapital::field('cwbc.id')->alias('cwdc')->
        join('cus_weather_base_capital cwbc', 'cwdc.weather_prefix=cwbc.weather_prefix', 'LEFT')->where($where)->order('cwdc.id asc')->count();
        return $count;

    }

    public function get_capital_weather_excel($code, $params, $field='cwbc.province, cwbc.yuncang, cwdc.min_c, cwdc.max_c, cwdc.weather_time') {

        $pageLimit = $params['limit'] ?? 1000;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $where = $this->return_where_capital($params);

        $count = CusWeatherDataCapital::field($field)->alias('cwdc')->
        join('cus_weather_base_capital cwbc', 'cwdc.weather_prefix=cwbc.weather_prefix', 'LEFT')->where($where)->order('cwdc.id asc')->count();

        if ($count > config('weather.init_output_num')) {

            // CusWeatherOutput::create(['code' => $code]);

            //采用其他方案生成得到excel
            $data = [
                'count' => $count,
                'data'  => [],
                'sign'  => 'other',
            ];

        } else {

            $list = CusWeatherDataCapital::field($field)->alias('cwdc')->
            join('cus_weather_base_capital cwbc', 'cwdc.weather_prefix=cwbc.weather_prefix', 'LEFT')->where($where)->order('cwdc.id asc')
            ->paginate([
                'list_rows'=> $pageLimit,
                'page' => $page,
            ]);
            $list = $list ? $list->toArray() : [];
            $data = [
                'count' => $list ? $list['total'] : 0,
                'data'  => $list ? $list['data'] : 0,
                'sign'  => 'normal',
            ];

        }
        
        return $data;

    }

}