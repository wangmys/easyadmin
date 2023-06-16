<?php

//skc 服务层
namespace app\admin\service;
use app\admin\model\bi\SpSkcSzDetailModel;
use app\admin\model\bi\SpSkcWinNumModel;
use app\admin\model\bi\SpSkcConfigModel;
use app\common\traits\Singleton;
use think\facade\Db;

class SkcService
{

    use Singleton;

    public function get_sz_index($params) {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页

        $list = SpSkcSzDetailModel::where([])->paginate([
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

    public function get_sz_statistic() {

        $all_goods_manager = SpSkcSzDetailModel::where([])->group('goods_manager')->field("count(*) as manager_store_num,goods_manager")->select();
        $all_goods_manager = $all_goods_manager ? $all_goods_manager->toArray() : [];
        $res_arr = [];
        if ($all_goods_manager) {

            $stores_statistic = $this->get_store_statistic();

            //满足率总占比
            $res_arr[] = [
                
                'goods_manager' => '-',

                '5_five_item_num' => '',
                '6_five_item_num' => '',
                '7_five_item_num' => '',
                '8_five_item_num' => '',
                '9_five_item_num' => '',
                '10_five_item_num' => '',
                '11_five_item_num' => '',
                '12_five_item_num' => '',
                '13_five_item_num' => '',
                'manager_store_num' => '总占比：',

                '5_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['5_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['5_five_item_num_100']/$stores_statistic['store_statistic'][0]['5_five_item_num'], 2)*100 ).'%' : '',
                '6_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['6_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['6_five_item_num_100']/$stores_statistic['store_statistic'][0]['6_five_item_num'], 2)*100 ).'%' : '',
                '7_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['7_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['7_five_item_num_100']/$stores_statistic['store_statistic'][0]['7_five_item_num'], 2)*100 ).'%' : '',
                '8_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['8_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['8_five_item_num_100']/$stores_statistic['store_statistic'][0]['8_five_item_num'], 2)*100 ).'%' : '',
                '9_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['9_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['9_five_item_num_100']/$stores_statistic['store_statistic'][0]['9_five_item_num'], 2)*100 ).'%' : '',
                '10_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['10_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['10_five_item_num_100']/$stores_statistic['store_statistic'][0]['10_five_item_num'], 2)*100 ).'%' : '',
                '11_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['11_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['11_five_item_num_100']/$stores_statistic['store_statistic'][0]['11_five_item_num'], 2)*100 ).'%' : '',
                '12_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['12_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['12_five_item_num_100']/$stores_statistic['store_statistic'][0]['12_five_item_num'], 2)*100 ).'%' : '',
                '13_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['13_five_item_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['13_five_item_num_100']/$stores_statistic['store_statistic'][0]['13_five_item_num'], 2)*100 ).'%' : '',
                'manager_store_num_100' => $stores_statistic['store_statistic_100'][0]['manager_store_num_100'] ? ( round($stores_statistic['store_statistic_100'][0]['manager_store_num_100']/$stores_statistic['store_statistic'][0]['manager_store_num'], 2)*100 ).'%' : '',

                '5_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['5_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['5_five_item_num_90']/$stores_statistic['store_statistic'][0]['5_five_item_num'], 2)*100 ).'%' : '',
                '6_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['6_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['6_five_item_num_90']/$stores_statistic['store_statistic'][0]['6_five_item_num'], 2)*100 ).'%' : '',
                '7_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['7_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['7_five_item_num_90']/$stores_statistic['store_statistic'][0]['7_five_item_num'], 2)*100 ).'%' : '',
                '8_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['8_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['8_five_item_num_90']/$stores_statistic['store_statistic'][0]['8_five_item_num'], 2)*100 ).'%' : '',
                '9_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['9_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['9_five_item_num_90']/$stores_statistic['store_statistic'][0]['9_five_item_num'], 2)*100 ).'%' : '',
                '10_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['10_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['10_five_item_num_90']/$stores_statistic['store_statistic'][0]['10_five_item_num'], 2)*100 ).'%' : '',
                '11_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['11_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['11_five_item_num_90']/$stores_statistic['store_statistic'][0]['11_five_item_num'], 2)*100 ).'%' : '',
                '12_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['12_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['12_five_item_num_90']/$stores_statistic['store_statistic'][0]['12_five_item_num'], 2)*100 ).'%' : '',
                '13_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['13_five_item_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['13_five_item_num_90']/$stores_statistic['store_statistic'][0]['13_five_item_num'], 2)*100 ).'%' : '',
                'manager_store_num_90' => $stores_statistic['store_statistic_90'][0]['manager_store_num_90'] ? ( round($stores_statistic['store_statistic_90'][0]['manager_store_num_90']/$stores_statistic['store_statistic'][0]['manager_store_num'], 2)*100 ).'%' : '',

                '5_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['5_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['5_five_item_num_80']/$stores_statistic['store_statistic'][0]['5_five_item_num'], 2)*100 ).'%' : '',
                '6_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['6_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['6_five_item_num_80']/$stores_statistic['store_statistic'][0]['6_five_item_num'], 2)*100 ).'%' : '',
                '7_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['7_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['7_five_item_num_80']/$stores_statistic['store_statistic'][0]['7_five_item_num'], 2)*100 ).'%' : '',
                '8_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['8_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['8_five_item_num_80']/$stores_statistic['store_statistic'][0]['8_five_item_num'], 2)*100 ).'%' : '',
                '9_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['9_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['9_five_item_num_80']/$stores_statistic['store_statistic'][0]['9_five_item_num'], 2)*100 ).'%' : '',
                '10_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['10_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['10_five_item_num_80']/$stores_statistic['store_statistic'][0]['10_five_item_num'], 2)*100 ).'%' : '',
                '11_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['11_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['11_five_item_num_80']/$stores_statistic['store_statistic'][0]['11_five_item_num'], 2)*100 ).'%' : '',
                '12_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['12_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['12_five_item_num_80']/$stores_statistic['store_statistic'][0]['12_five_item_num'], 2)*100 ).'%' : '',
                '13_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['13_five_item_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['13_five_item_num_80']/$stores_statistic['store_statistic'][0]['13_five_item_num'], 2)*100 ).'%' : '',
                'manager_store_num_80' => $stores_statistic['store_statistic_80'][0]['manager_store_num_80'] ? ( round($stores_statistic['store_statistic_80'][0]['manager_store_num_80']/$stores_statistic['store_statistic'][0]['manager_store_num'], 2)*100 ).'%' : '',

                '5_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['5_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['5_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['5_five_item_num'], 2)*100 ).'%' : '',
                '6_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['6_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['6_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['6_five_item_num'], 2)*100 ).'%' : '',
                '7_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['7_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['7_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['7_five_item_num'], 2)*100 ).'%' : '',
                '8_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['8_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['8_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['8_five_item_num'], 2)*100 ).'%' : '',
                '9_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['9_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['9_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['9_five_item_num'], 2)*100 ).'%' : '',
                '10_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['10_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['10_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['10_five_item_num'], 2)*100 ).'%' : '',
                '11_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['11_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['11_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['11_five_item_num'], 2)*100 ).'%' : '',
                '12_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['12_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['12_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['12_five_item_num'], 2)*100 ).'%' : '',
                '13_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['13_five_item_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['13_five_item_num_80_less']/$stores_statistic['store_statistic'][0]['13_five_item_num'], 2)*100 ).'%' : '',
                'manager_store_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['manager_store_num_80_less'] ? ( round($stores_statistic['store_statistic_80_less'][0]['manager_store_num_80_less']/$stores_statistic['store_statistic'][0]['manager_store_num'], 2)*100 ).'%' : '',

            ];

            foreach ($all_goods_manager as $v_goods_manager) {

                $stores_num = $this->get_stores_num($v_goods_manager['goods_manager']);
                
                $arr['goods_manager'] = $v_goods_manager['goods_manager'];

                $arr['5_five_item_num'] = $stores_num['stores_num'][0]['5_five_item_num'] ?: '';
                $arr['6_five_item_num'] = $stores_num['stores_num'][0]['6_five_item_num'] ?: '';
                $arr['7_five_item_num'] = $stores_num['stores_num'][0]['7_five_item_num'] ?: '';
                $arr['8_five_item_num'] = $stores_num['stores_num'][0]['8_five_item_num'] ?: '';
                $arr['9_five_item_num'] = $stores_num['stores_num'][0]['9_five_item_num'] ?: '';
                $arr['10_five_item_num'] = $stores_num['stores_num'][0]['10_five_item_num'] ?: '';
                $arr['11_five_item_num'] = $stores_num['stores_num'][0]['11_five_item_num'] ?: '';
                $arr['12_five_item_num'] = $stores_num['stores_num'][0]['12_five_item_num'] ?: '';
                $arr['13_five_item_num'] = $stores_num['stores_num'][0]['13_five_item_num'] ?: '';
                $arr['manager_store_num'] = $stores_num['stores_num'][0]['manager_store_num'] ?: '';

                $arr['5_five_item_num_100'] = $stores_num['stores_num_100'][0]['5_five_item_num_100'] ?: '';
                $arr['6_five_item_num_100'] = $stores_num['stores_num_100'][0]['6_five_item_num_100'] ?: '';
                $arr['7_five_item_num_100'] = $stores_num['stores_num_100'][0]['7_five_item_num_100'] ?: '';
                $arr['8_five_item_num_100'] = $stores_num['stores_num_100'][0]['8_five_item_num_100'] ?: '';
                $arr['9_five_item_num_100'] = $stores_num['stores_num_100'][0]['9_five_item_num_100'] ?: '';
                $arr['10_five_item_num_100'] = $stores_num['stores_num_100'][0]['10_five_item_num_100'] ?: '';
                $arr['11_five_item_num_100'] = $stores_num['stores_num_100'][0]['11_five_item_num_100'] ?: '';
                $arr['12_five_item_num_100'] = $stores_num['stores_num_100'][0]['12_five_item_num_100'] ?: '';
                $arr['13_five_item_num_100'] = $stores_num['stores_num_100'][0]['13_five_item_num_100'] ?: '';
                $arr['manager_store_num_100'] = $stores_num['stores_num_100'][0]['manager_store_num_100'] ?: '';
                
                $arr['5_five_item_num_90'] = $stores_num['stores_num_90'][0]['5_five_item_num_90'] ?: '';
                $arr['6_five_item_num_90'] = $stores_num['stores_num_90'][0]['6_five_item_num_90'] ?: '';
                $arr['7_five_item_num_90'] = $stores_num['stores_num_90'][0]['7_five_item_num_90'] ?: '';
                $arr['8_five_item_num_90'] = $stores_num['stores_num_90'][0]['8_five_item_num_90'] ?: '';
                $arr['9_five_item_num_90'] = $stores_num['stores_num_90'][0]['9_five_item_num_90'] ?: '';
                $arr['10_five_item_num_90'] = $stores_num['stores_num_90'][0]['10_five_item_num_90'] ?: '';
                $arr['11_five_item_num_90'] = $stores_num['stores_num_90'][0]['11_five_item_num_90'] ?: '';
                $arr['12_five_item_num_90'] = $stores_num['stores_num_90'][0]['12_five_item_num_90'] ?: '';
                $arr['13_five_item_num_90'] = $stores_num['stores_num_90'][0]['13_five_item_num_90'] ?: '';
                $arr['manager_store_num_90'] = $stores_num['stores_num_90'][0]['manager_store_num_90'] ?: '';
                
                $arr['5_five_item_num_80'] = $stores_num['stores_num_80'][0]['5_five_item_num_80'] ?: '';
                $arr['6_five_item_num_80'] = $stores_num['stores_num_80'][0]['6_five_item_num_80'] ?: '';
                $arr['7_five_item_num_80'] = $stores_num['stores_num_80'][0]['7_five_item_num_80'] ?: '';
                $arr['8_five_item_num_80'] = $stores_num['stores_num_80'][0]['8_five_item_num_80'] ?: '';
                $arr['9_five_item_num_80'] = $stores_num['stores_num_80'][0]['9_five_item_num_80'] ?: '';
                $arr['10_five_item_num_80'] = $stores_num['stores_num_80'][0]['10_five_item_num_80'] ?: '';
                $arr['11_five_item_num_80'] = $stores_num['stores_num_80'][0]['11_five_item_num_80'] ?: '';
                $arr['12_five_item_num_80'] = $stores_num['stores_num_80'][0]['12_five_item_num_80'] ?: '';
                $arr['13_five_item_num_80'] = $stores_num['stores_num_80'][0]['13_five_item_num_80'] ?: '';
                $arr['manager_store_num_80'] = $stores_num['stores_num_80'][0]['manager_store_num_80'] ?: '';
                
                $arr['5_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['5_five_item_num_80_less'] ?: '';
                $arr['6_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['6_five_item_num_80_less'] ?: '';
                $arr['7_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['7_five_item_num_80_less'] ?: '';
                $arr['8_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['8_five_item_num_80_less'] ?: '';
                $arr['9_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['9_five_item_num_80_less'] ?: '';
                $arr['10_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['10_five_item_num_80_less'] ?: '';
                $arr['11_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['11_five_item_num_80_less'] ?: '';
                $arr['12_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['12_five_item_num_80_less'] ?: '';
                $arr['13_five_item_num_80_less'] = $stores_num['stores_num_80_less'][0]['13_five_item_num_80_less'] ?: '';
                $arr['manager_store_num_80_less'] = $stores_num['stores_num_80_less'][0]['manager_store_num_80_less'] ?: '';

                $res_arr[] = $arr;
                
            }

            //总计：
            $res_arr[] = [
                
                'goods_manager' => '总计',

                '5_five_item_num' => $stores_statistic['store_statistic'][0]['5_five_item_num'] ?: '',
                '6_five_item_num' => $stores_statistic['store_statistic'][0]['6_five_item_num'] ?: '',
                '7_five_item_num' => $stores_statistic['store_statistic'][0]['7_five_item_num'] ?: '',
                '8_five_item_num' => $stores_statistic['store_statistic'][0]['8_five_item_num'] ?: '',
                '9_five_item_num' => $stores_statistic['store_statistic'][0]['9_five_item_num'] ?: '',
                '10_five_item_num' => $stores_statistic['store_statistic'][0]['10_five_item_num'] ?: '',
                '11_five_item_num' => $stores_statistic['store_statistic'][0]['11_five_item_num'] ?: '',
                '12_five_item_num' => $stores_statistic['store_statistic'][0]['12_five_item_num'] ?: '',
                '13_five_item_num' => $stores_statistic['store_statistic'][0]['13_five_item_num'] ?: '',
                'manager_store_num' => $stores_statistic['store_statistic'][0]['manager_store_num'] ?: '',

                '5_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['5_five_item_num_100'] ?: '',
                '6_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['6_five_item_num_100'] ?: '',
                '7_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['7_five_item_num_100'] ?: '',
                '8_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['8_five_item_num_100'] ?: '',
                '9_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['9_five_item_num_100'] ?: '',
                '10_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['10_five_item_num_100'] ?: '',
                '11_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['11_five_item_num_100'] ?: '',
                '12_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['12_five_item_num_100'] ?: '',
                '13_five_item_num_100' => $stores_statistic['store_statistic_100'][0]['13_five_item_num_100'] ?: '',
                'manager_store_num_100' => $stores_statistic['store_statistic_100'][0]['manager_store_num_100'] ?: '',

                '5_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['5_five_item_num_90'] ?: '',
                '6_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['6_five_item_num_90'] ?: '',
                '7_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['7_five_item_num_90'] ?: '',
                '8_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['8_five_item_num_90'] ?: '',
                '9_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['9_five_item_num_90'] ?: '',
                '10_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['10_five_item_num_90'] ?: '',
                '11_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['11_five_item_num_90'] ?: '',
                '12_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['12_five_item_num_90'] ?: '',
                '13_five_item_num_90' => $stores_statistic['store_statistic_90'][0]['13_five_item_num_90'] ?: '',
                'manager_store_num_90' => $stores_statistic['store_statistic_90'][0]['manager_store_num_90'] ?: '',

                '5_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['5_five_item_num_80'] ?: '',
                '6_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['6_five_item_num_80'] ?: '',
                '7_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['7_five_item_num_80'] ?: '',
                '8_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['8_five_item_num_80'] ?: '',
                '9_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['9_five_item_num_80'] ?: '',
                '10_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['10_five_item_num_80'] ?: '',
                '11_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['11_five_item_num_80'] ?: '',
                '12_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['12_five_item_num_80'] ?: '',
                '13_five_item_num_80' => $stores_statistic['store_statistic_80'][0]['13_five_item_num_80'] ?: '',
                'manager_store_num_80' => $stores_statistic['store_statistic_80'][0]['manager_store_num_80'] ?: '',

                '5_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['5_five_item_num_80_less'] ?: '',
                '6_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['6_five_item_num_80_less'] ?: '',
                '7_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['7_five_item_num_80_less'] ?: '',
                '8_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['8_five_item_num_80_less'] ?: '',
                '9_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['9_five_item_num_80_less'] ?: '',
                '10_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['10_five_item_num_80_less'] ?: '',
                '11_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['11_five_item_num_80_less'] ?: '',
                '12_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['12_five_item_num_80_less'] ?: '',
                '13_five_item_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['13_five_item_num_80_less'] ?: '',
                'manager_store_num_80_less' => $stores_statistic['store_statistic_80_less'][0]['manager_store_num_80_less'] ?: '',

            ];

        }
        // print_r($res_arr);die;
        $data = [
                'count' => count($all_goods_manager),
                'data'  => $res_arr
        ];
        return $data;

    }

    /**
     * 门店数 && 各个满足率家数
     */
    public function get_stores_num($goods_manager) {

        ##每个商品负责人的门店数统计
        $sql_stores_num = "select count(*) as 
        manager_store_num,
        sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num, 
        sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num, 
        sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num,
        sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num,
        sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num,
        sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num,
        sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num,
        sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num,
        sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num
        from sp_skc_sz_detail where goods_manager='{$goods_manager}';";

        ##每个商品负责人的满足率100%统计
        $sql_stores_num_100 = "select count(*) as 
manager_store_num_100,
sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_100, 
sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_100, 
sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_100,
sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_100,
sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_100,
sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_100,
sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_100,
sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_100,
sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_100
from sp_skc_sz_detail where goods_manager='{$goods_manager}' and fill_rate>=1;";

        ##每个商品负责人的满足率90%统计
        $sql_stores_num_90 = "select count(*) as 
manager_store_num_90,
sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_90, 
sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_90, 
sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_90,
sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_90,
sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_90,
sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_90,
sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_90,
sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_90,
sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_90
from sp_skc_sz_detail where goods_manager='{$goods_manager}' and fill_rate>=0.9 and fill_rate<1;";

        ##每个商品负责人的满足率80%统计
        $sql_stores_num_80 = "select count(*) as 
manager_store_num_80,
sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_80, 
sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_80, 
sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_80,
sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_80,
sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_80,
sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_80,
sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_80,
sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_80,
sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_80
from sp_skc_sz_detail where goods_manager='{$goods_manager}' and fill_rate>=0.8 and fill_rate<0.9;";

        ##每个商品负责人的满足率80%以下统计
        $sql_stores_num_80_less = "select count(*) as 
manager_store_num_80_less,
sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_80_less, 
sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_80_less, 
sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_80_less,
sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_80_less,
sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_80_less,
sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_80_less,
sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_80_less,
sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_80_less,
sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_80_less
from sp_skc_sz_detail where goods_manager='{$goods_manager}' and fill_rate<0.8;";

        $stores_num = Db::connect("mysql2")->Query($sql_stores_num);
        $stores_num_100 = Db::connect("mysql2")->Query($sql_stores_num_100);
        $stores_num_90 = Db::connect("mysql2")->Query($sql_stores_num_90);
        $stores_num_80 = Db::connect("mysql2")->Query($sql_stores_num_80);
        $stores_num_80_less = Db::connect("mysql2")->Query($sql_stores_num_80_less);

        return [
            'stores_num' => $stores_num,
            'stores_num_100' => $stores_num_100,
            'stores_num_90' => $stores_num_90,
            'stores_num_80' => $stores_num_80,
            'stores_num_80_less' => $stores_num_80_less,
        ];

    }

    /**
     * 获取总计统计数
     */
    public function get_store_statistic() {

        $sql_store_statistic = "select count(*) as 
        manager_store_num,
        sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num, 
        sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num, 
        sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num,
        sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num,
        sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num,
        sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num,
        sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num,
        sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num,
        sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num
        from sp_skc_sz_detail where 1;";

        $sql_store_statistic_100 = "select count(*) as 
        manager_store_num_100,
        sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_100, 
        sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_100, 
        sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_100,
        sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_100,
        sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_100,
        sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_100,
        sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_100,
        sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_100,
        sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_100
        from sp_skc_sz_detail where fill_rate>=1;";

        $sql_store_statistic_90 = "select count(*) as 
        manager_store_num_90,
        sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_90, 
        sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_90, 
        sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_90,
        sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_90,
        sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_90,
        sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_90,
        sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_90,
        sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_90,
        sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_90
        from sp_skc_sz_detail where fill_rate>=0.9 and fill_rate<1;";

        $sql_store_statistic_80 = "select count(*) as 
        manager_store_num_80,
        sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_80, 
        sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_80, 
        sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_80,
        sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_80,
        sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_80,
        sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_80,
        sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_80,
        sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_80,
        sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_80
        from sp_skc_sz_detail where fill_rate>=0.8 and fill_rate<0.9;";

        $sql_store_statistic_80_less = "select count(*) as 
        manager_store_num_80_less,
        sum(case when five_item_num=5 then 1 else 0 END) as 5_five_item_num_80_less, 
        sum(case when five_item_num=6 then 1 else 0 END) as 6_five_item_num_80_less, 
        sum(case when five_item_num=7 then 1 else 0 END) as 7_five_item_num_80_less,
        sum(case when five_item_num=8 then 1 else 0 END) as 8_five_item_num_80_less,
        sum(case when five_item_num=9 then 1 else 0 END) as 9_five_item_num_80_less,
        sum(case when five_item_num=10 then 1 else 0 END) as 10_five_item_num_80_less,
        sum(case when five_item_num=11 then 1 else 0 END) as 11_five_item_num_80_less,
        sum(case when five_item_num=12 then 1 else 0 END) as 12_five_item_num_80_less,
        sum(case when five_item_num>12 then 1 else 0 END) as 13_five_item_num_80_less
        from sp_skc_sz_detail where fill_rate<0.8;";

        $store_statistic = Db::connect("mysql2")->Query($sql_store_statistic);
        $store_statistic_100 = Db::connect("mysql2")->Query($sql_store_statistic_100);
        $store_statistic_90 = Db::connect("mysql2")->Query($sql_store_statistic_90);
        $store_statistic_80 = Db::connect("mysql2")->Query($sql_store_statistic_80);
        $store_statistic_80_less = Db::connect("mysql2")->Query($sql_store_statistic_80_less);
        return [
            'store_statistic' => $store_statistic,
            'store_statistic_100' => $store_statistic_100,
            'store_statistic_90' => $store_statistic_90,
            'store_statistic_80' => $store_statistic_80,
            'store_statistic_80_less' => $store_statistic_80_less,
        ];

    }

    public function get_skc_win_num() {

        $list = SpSkcWinNumModel::where([])->field('area_range,win_num,skc_fl,skc_yl,skc_xxdc,skc_num')->select();
        $list = $list ? $list->toArray() : [];
        return $list;

    }

    /**
     * 保存窗数陈列标准配置
     */
    public function save_skc_win_num($data) {

        $id = null;
        if ($data) {
            $sign_id = $data['sign_id'];
            if ($sign_id != '') {//更新

                unset($data['sign_id']);
                $data['key_str'] = $data['win_num'].$data['area_range'];
                $id = SpSkcWinNumModel::where([['key_str', '=', $sign_id]])->update($data);
                $id = $sign_id;

            } else {//插入

                unset($data['sign_id']);
                $data['key_str'] = $data['win_num'].$data['area_range'];
                $id = SpSkcWinNumModel::create($data);
                $id = $id->id;

            }
        }
        return $id;

    }

    /**
     * 检测是否已存在
     */
    public function check_skc_win_num($sign_id) {

        return SpSkcWinNumModel::where([['key_str', '=', $sign_id]])->field('id')->find();

    }

    /**
     * 删除窗数陈列标准配置
     */
    public function del_skc_win_num($sign_id) {

        return SpSkcWinNumModel::where([['key_str', '=', $sign_id]])->delete();

    }

    /**
     * 获取skc价格配置
     */
    public function get_skc_config() {

        $list = SpSkcConfigModel::where([['config_str', '=', 'skc_price_config']])->field('config_str,dt_price,dc_price')->find();
        $list = $list ? $list->toArray() : [];
        return $list;

    }

    /**
     * 保存skc价格配置
     */
    public function save_skc_config($data) {

        $id = null;
        if ($data) {
            $sign_id = $data['sign_id'];
            unset($data['sign_id']);
            $id = SpSkcConfigModel::where([['config_str', '=', $sign_id]])->update($data);
            $id = $sign_id;
        }
        return $id;

    }

}