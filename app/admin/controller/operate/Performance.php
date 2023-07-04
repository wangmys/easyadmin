<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller\operate;


use app\common\controller\AdminController;
use think\App;
use app\admin\model\SystemMenu;

/**
 * Class Admin
 * @package app\admin\controller\operate
 * @ControllerAnnotation(title="店铺业绩")
 */
class Performance extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemMenu;
    }

    /**
     * 展示业绩对比
     */
    public function list()
    {
         // 昨天
        $yesterday = strtotime(date('Y-m-d').'-1day');
        $limitDate['setTime1'] = date('Y-m-d',$yesterday).' ~ '.date('Y-m-d',$yesterday);
        $limitDate['setTime2'] = date('Y-m-d').' ~ '.date('Y-m-d');
        if ($this->request->isAjax()) {
            // 本期时间
            $setTime1 = $this->request->param('setTime1',$limitDate['setTime1']);
            // 对比时间
            $setTime2 = $this->request->param('setTime2',$limitDate['setTime2']);

            $current_time = explode('~',$setTime1);
            // 本期时间
            $current_time_1 = trim($current_time[0]);
            $current_time_2 = trim($current_time[1]).' 23:59:59';
            // 查询本期数据
            $current_data = $this->model->getPerformanceData($current_time_1,$current_time_2);


            $contrast_time = explode('~',$setTime2);
            // 对比时间
            $contrast_time_1 = trim($contrast_time[0]);
            $contrast_time_2 = trim($contrast_time[1]).' 23:59:59';

            // 查询对比数据
            $contrast_data = $this->model->getPerformanceData($contrast_time_1,$contrast_time_2);

            // 对比值数据
            $contrast_value_data = [];
            // 本期数据
            foreach ($current_data as $k => $v){

                // 对比数据
                $contrast_item = $contrast_data[$k]??[];
                if($contrast_item){

                    // 公共数据
                    $contrast_value_data[$k]['State'] = $v['State'];
                    $contrast_value_data[$k]['CustomerName'] = $v['CustomerName'];
                    $contrast_value_data[$k]['CustomerCode'] = $v['CustomerCode'];
                    $contrast_value_data[$k]['CustomItem19'] = $v['CustomItem19'];
                    $contrast_value_data[$k]['CustomItem18'] = $v['CustomItem18'];
                    $contrast_value_data[$k]['StoreArea'] = $v['StoreArea'];

                    // 本期数据
                    $contrast_value_data[$k]['current_ranking'] = $v['ranking'];
                    $contrast_value_data[$k]['current_num'] = $v['有效件量'];
                    $contrast_value_data[$k]['current_singular'] = $v['有效单数'];
                    $contrast_value_data[$k]['current_performance'] = $v['有效业绩'];
                    $contrast_value_data[$k]['current_performance_total'] = $v['总业绩'];
                    $contrast_value_data[$k]['current_unitprice'] = round($v['件单价'],2);
                    $contrast_value_data[$k]['current_customerprice'] = round($v['客单价'],2);
                    $contrast_value_data[$k]['current_joint_rate'] = round($v['连带率'],2);
                    $contrast_value_data[$k]['current_efficiency'] = bcadd(round( $v['人效'] * 0.01,2) * 100,0,2);

                    // 对比数据
                    $contrast_value_data[$k]['contrast_ranking'] = $contrast_item['ranking'];
                    $contrast_value_data[$k]['contrast_num'] = $contrast_item['有效件量'];
                    $contrast_value_data[$k]['contrast_singular'] = $contrast_item['有效单数'];
                    $contrast_value_data[$k]['contrast_performance'] = $contrast_item['有效业绩'];
                    $contrast_value_data[$k]['contrast_performance_total'] = $contrast_item['总业绩'];
                    $contrast_value_data[$k]['contrast_unitprice'] = round($contrast_item['件单价'],2);
                    $contrast_value_data[$k]['contrast_customerprice'] = round($contrast_item['客单价'],2);
                    $contrast_value_data[$k]['contrast_joint_rate'] = round($contrast_item['连带率'],2);
                    $contrast_value_data[$k]['contrast_efficiency'] = bcadd(round( $contrast_item['人效'] * 0.01,2) * 100,0,2);


                    // 对比值
                    $contrast_value_data[$k]['ranking'] =  $v['ranking'] - $contrast_item['ranking'];
                    $contrast_value_data[$k]['num'] = $v['有效件量'] - $contrast_item['有效件量'];
                    $contrast_value_data[$k]['singular'] = $v['有效单数'] - $contrast_item['有效单数'];
                    $contrast_value_data[$k]['performance'] = $v['有效业绩'] - $contrast_item['有效业绩'];
                    $contrast_value_data[$k]['performance_total'] = round(($v['总业绩'] - $contrast_item['总业绩']) / $contrast_item['总业绩'] * 100,2).'%';
                    $contrast_value_data[$k]['unitprice'] = round($contrast_value_data[$k]['current_unitprice'] - $contrast_value_data[$k]['contrast_unitprice'],2);
                    $contrast_value_data[$k]['customerprice'] = round($contrast_value_data[$k]['current_customerprice'] - $contrast_value_data[$k]['contrast_customerprice'],2);
                    $contrast_value_data[$k]['joint_rate'] = round($contrast_value_data[$k]['current_joint_rate'] - $contrast_value_data[$k]['contrast_joint_rate'],2);
                    $contrast_value_data[$k]['efficiency'] = bcadd(($contrast_value_data[$k]['current_efficiency'] - $contrast_value_data[$k]['contrast_efficiency']) / $contrast_value_data[$k]['contrast_efficiency'] * 100 ,0,2).'%';
                }
            }

//            $contrast_value_data['sum']['State'] = '';
//            $contrast_value_data['sum']['CustomerName'] = '';
//            $contrast_value_data['sum']['CustomerCode'] = '';
//            $contrast_value_data['sum']['CustomItem19'] = '';
//            $contrast_value_data['sum']['CustomItem18'] = '';
//            $contrast_value_data['sum']['StoreArea'] = round(array_sum(array_column($contrast_value_data,'StoreArea')) / count($contrast_value_data),2);
//            $contrast_value_data['sum']['current_ranking'] = '-';
//            $contrast_value_data['sum']['current_num'] = '-';
//            $contrast_value_data['sum']['current_singular'] = array_sum(array_column($contrast_value_data,'current_singular'));
//            $contrast_value_data['sum']['current_performance'] = array_sum(array_column($contrast_value_data,'current_performance'));
//            $contrast_value_data['sum']['current_performance_total'] = array_sum(array_column($contrast_value_data,'current_performance_total'));
//            $contrast_value_data['sum']['current_unitprice'] = array_sum(array_column($contrast_value_data,'current_unitprice'));
//            $contrast_value_data['sum']['current_customerprice'] = array_sum(array_column($contrast_value_data,'current_customerprice'));
//            $contrast_value_data['sum']['current_joint_rate'] = array_sum(array_column($contrast_value_data,'current_joint_rate'));
//            $contrast_value_data['sum']['current_efficiency'] = array_sum(array_column($contrast_value_data,'current_efficiency'));
//
//            $contrast_value_data['sum']['contrast_ranking'] = '-';
//            $contrast_value_data['sum']['contrast_num'] = array_sum(array_column($contrast_value_data,'contrast_num'));
//            $contrast_value_data['sum']['contrast_singular'] = array_sum(array_column($contrast_value_data,'contrast_singular'));
//            $contrast_value_data['sum']['contrast_performance'] = array_sum(array_column($contrast_value_data,'contrast_performance'));
//            $contrast_value_data['sum']['contrast_performance_total'] = array_sum(array_column($contrast_value_data,'contrast_performance_total'));
//            $contrast_value_data['sum']['contrast_unitprice'] = array_sum(array_column($contrast_value_data,'contrast_unitprice'));
//            // 平均件单价
//            $contrast_value_data['sum']['contrast_customerprice'] = array_sum(array_column($contrast_value_data,'contrast_customerprice')) / count($contrast_value_data);
//            // 平均连带率
//            $contrast_value_data['sum']['contrast_joint_rate'] = array_sum(array_column($contrast_value_data,'contrast_joint_rate'));
//            // 总人效
//            $contrast_value_data['sum']['contrast_efficiency'] = array_sum(array_column($contrast_value_data,'contrast_efficiency'));
//
//            // 对比值
//            // 排名
//            $contrast_value_data['sum']['ranking'] = '-';
//            // 有效件单量
//            $contrast_value_data['sum']['num'] = array_sum(array_column($contrast_value_data,'num'));
//            // 有效单数
//            $contrast_value_data['sum']['singular'] =  array_sum(array_column($contrast_value_data,'singular'));
//            // 有效业绩
//            $contrast_value_data['sum']['performance'] = array_sum(array_column($contrast_value_data,'performance')) / count($contrast_value_data);
//            // 平均总流水
//            $contrast_value_data['sum']['performance_total'] = array_sum(array_column($contrast_value_data,'performance_total')) / count($contrast_value_data);
//            // 平均件单价
//            $contrast_value_data['sum']['unitprice'] = array_sum(array_column($contrast_value_data,'unitprice')) / count($contrast_value_data);
//            // 平均客单价
//            $contrast_value_data['sum']['customerprice'] = array_sum(array_column($contrast_value_data,'customerprice')) / count($contrast_value_data);
//            // 平均连带率
//            $contrast_value_data['sum']['joint_rate'] = array_sum(array_column($contrast_value_data,'joint_rate')) / count($contrast_value_data);
//            // 平均人效
//            $contrast_value_data['sum']['efficiency'] = array_sum(array_column($contrast_value_data,'efficiency')) / count($contrast_value_data);

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($contrast_value_data),
                'data'  => $contrast_value_data,
            ];
            return json($data);
        }
        return $this->fetch('',[
            'limitDate' => $limitDate
        ]);
    }


    // 获取城市
    public static function getProvince() {
        $res = self::field('省份')
        ->group('省份')
        ->select()
        ->toArray();
        return $res;
    }
}
