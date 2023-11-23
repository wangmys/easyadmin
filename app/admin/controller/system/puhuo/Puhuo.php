<?php
namespace app\admin\controller\system\puhuo;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\service\PuhuoService;
use jianyan\excel\Excel;
use think\Request;

/**
 * Class Puhuo
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="自动铺货")
 */
class Puhuo extends AdminController
{
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->service = new PuhuoService();
        $this->request = $request;
    }

    /**
     * @NodeAnotation(title="最终铺货结果")
     */
    public function puhuo_index() {

        $params = $this->request->param();
        // print_r($params);die;
        $statistic = $this->service->puhuo_statistic($params);

        if (request()->isAjax()) {

            $res = $this->service->puhuo_index($params);
            if ($res['data']) {
                foreach ($res['data'] as &$v_data) {
                    if ($v_data['is_total'] == 1) {
                        $v_data['StoreArea'] = '';
                        $v_data['xiuxian_num'] = '';
                        $v_data['score_sort'] = '';
                        $v_data['StyleCategoryName1'] = '';
                    }
                    $v_data['Stock_00_puhuo'] = $v_data['Stock_00_puhuo'] ?: '';
                    $v_data['Stock_29_puhuo'] = $v_data['Stock_29_puhuo'] ?: '';
                    $v_data['Stock_30_puhuo'] = $v_data['Stock_30_puhuo'] ?: '';
                    $v_data['Stock_31_puhuo'] = $v_data['Stock_31_puhuo'] ?: '';
                    $v_data['Stock_32_puhuo'] = $v_data['Stock_32_puhuo'] ?: '';
                    $v_data['Stock_33_puhuo'] = $v_data['Stock_33_puhuo'] ?: '';
                    $v_data['Stock_34_puhuo'] = $v_data['Stock_34_puhuo'] ?: '';
                    $v_data['Stock_35_puhuo'] = $v_data['Stock_35_puhuo'] ?: '';
                    $v_data['Stock_36_puhuo'] = $v_data['Stock_36_puhuo'] ?: '';
                    $v_data['Stock_38_puhuo'] = $v_data['Stock_38_puhuo'] ?: '';
                    $v_data['Stock_40_puhuo'] = $v_data['Stock_40_puhuo'] ?: '';
                    $v_data['Stock_42_puhuo'] = $v_data['Stock_42_puhuo'] ?: '';
                    $v_data['Stock_44_puhuo'] = $v_data['Stock_44_puhuo'] ?: '';
                    $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity_puhuo'] ?: '';
                    $v_data['img'] = $v_data['GoodsNo'] ? 'https://ff211-1254425741.cos.ap-guangzhou.myqcloud.com/'.$v_data['GoodsNo'].'.jpg' : '';
                    // print_r($v_data);die;
                }
            }

            return json(array_merge(["code" => "0", "msg" => "", "count" => $res['count'], "data" => $res['data'],  'create_time' => date('Y-m-d')], $statistic));
        } else {
            return View('system/puhuo/puhuo_index', $statistic);
        }        

    }

    /**
     * @NodeAnotation(title="导单转换")
     */
    public function puhuo_daodan() {

        $params = $this->request->param();

        if (request()->isAjax()) {

            $params['is_puhuo'] = '可铺';
            $params['page'] = 1;
            $params['limit'] = 1000000;

            $code = rand_code(6);
            cache($code, json_encode($params), 36000);	

            $res = $this->service->puhuo_daodan($params);
            // print_r($res);die;

            if ($res && $res['data']) {
                return json([
                    'status' => 1,
                    'code' => $code,
                ]);
            } else {
                return json([
                    'status' => 400,
                    'msg' => '无数据',
                ]);
            }

        } else {

            $code = input('code');
            $params = cache($code);
            $params = $params ? json_decode($params, true) : [];
            
            $res = $this->service->puhuo_daodan($params);
            // print_r($res);die;

            if ($res && $res['data']) {

                $excel_output_data = [];
                foreach ($res['data'] as $k_res=>$v_res) {

                    if ($v_res['is_total']) continue;

                    $order_no = uuid('SX');//date('Ymd').(++$k_res);

                    $tmp_arr = [
                        'order_no' => $order_no,
                        'WarehouseCode' => $v_res['WarehouseCode'],
                        'CustomerCode' => $v_res['CustomerCode'],
                        'send_out' => 'Y',
                        're_confirm' => 'Y',
                        'GoodsNo' => $v_res['GoodsNo'],
                        'Size' => '',//
                        'ColorCode' => $v_res['ColorCode'],
                        'puhuo_num' => 0,//
                        'status' => 2,
                        'remark' => $v_res['CategoryName1'],
                    ];

                    $size_arr = [
                        ['puhuo_num'=>$v_res['Stock_00_puhuo'], 'Size'=>$v_res['Stock_00_size']],
                        ['puhuo_num'=>$v_res['Stock_29_puhuo'], 'Size'=>$v_res['Stock_29_size']],
                        ['puhuo_num'=>$v_res['Stock_30_puhuo'], 'Size'=>$v_res['Stock_30_size']],
                        ['puhuo_num'=>$v_res['Stock_31_puhuo'], 'Size'=>$v_res['Stock_31_size']],
                        ['puhuo_num'=>$v_res['Stock_32_puhuo'], 'Size'=>$v_res['Stock_32_size']],
                        ['puhuo_num'=>$v_res['Stock_33_puhuo'], 'Size'=>$v_res['Stock_33_size']],
                        ['puhuo_num'=>$v_res['Stock_34_puhuo'], 'Size'=>$v_res['Stock_34_size']],
                        ['puhuo_num'=>$v_res['Stock_35_puhuo'], 'Size'=>$v_res['Stock_35_size']],
                        ['puhuo_num'=>$v_res['Stock_36_puhuo'], 'Size'=>$v_res['Stock_36_size']],
                        ['puhuo_num'=>$v_res['Stock_38_puhuo'], 'Size'=>$v_res['Stock_38_size']],
                        ['puhuo_num'=>$v_res['Stock_40_puhuo'], 'Size'=>$v_res['Stock_40_size']],
                        ['puhuo_num'=>$v_res['Stock_42_puhuo'], 'Size'=>$v_res['Stock_42_size']],
                    ];
                    foreach ($size_arr as $k_size_arr=>&$v_size_arr) {
                        if ($v_size_arr['puhuo_num']) {
                            $tmp_arr['Size'] = $v_size_arr['Size'];
                            $tmp_arr['puhuo_num'] = $v_size_arr['puhuo_num'];
                            $excel_output_data[] = $tmp_arr;
                        }
                    }

                }

                $this->service->add_puhuo_daodan($excel_output_data);
                // $this->success('该云仓下，以下货号已存在，请剔除6666:', $excel_output_data);

                $header = [
                    ['*订单号', 'order_no'],
                    ['*仓库编号', 'WarehouseCode'],
                    ['*店铺编号', 'CustomerCode'],
                    ['*打包后立即发出', 'send_out'],
                    ['*差异出货需二次确认', 're_confirm'],
                    ['*货号', 'GoodsNo'],
                    ['*尺码', 'Size'],
                    ['*颜色编码', 'ColorCode'],
                    ['*铺货数', 'puhuo_num'],
                    ['*状态/1草稿,2预发布,3确定发布', 'status'],
                    ['备注', 'remark'],
                ];
    
                return Excel::exportData($excel_output_data, $header, 'puhuo_daodan_' .count($excel_output_data) , 'xlsx');

            }

        }        

    }

    /**
     * @NodeAnotation(title="导单转换-草稿")
     */
    public function puhuo_daodan_caogao() {

        $params = $this->request->param();

        if (request()->isAjax()) {

            // $params['is_puhuo'] = '可铺';
            $params['page'] = 1;
            $params['limit'] = 1000000;

            $code = rand_code(6);
            cache($code, json_encode($params), 36000);

            $res = $this->service->puhuo_daodan_caogao($params);
            // print_r($res);die;

            if ($res && $res['data']) {
                return json([
                    'status' => 1,
                    'code' => $code,
                ]);
            } else {
                return json([
                    'status' => 400,
                    'msg' => '无数据，请检查筛选条件',
                ]);
            }

        } else {

            $code = input('code');
            $params = cache($code);
            $params = $params ? json_decode($params, true) : [];
            
            $res = $this->service->puhuo_daodan_caogao($params);
            // print_r($res);die;

            if ($res && $res['data']) {

                $excel_output_data = [];
                foreach ($res['data'] as $k_res=>$v_res) {

                    if ($v_res['is_total']) continue;

                    $order_no = uuid('SX');//date('Ymd').(++$k_res);

                    $tmp_arr = [
                        'order_no' => $order_no,
                        'WarehouseCode' => $v_res['WarehouseCode'],
                        'CustomerCode' => $v_res['CustomerCode'],
                        'send_out' => 'Y',
                        're_confirm' => 'Y',
                        'GoodsNo' => $v_res['GoodsNo'],
                        'Size' => '',//
                        'ColorCode' => $v_res['ColorCode'],
                        'puhuo_num' => 0,//
                        'status' => 2,
                        'remark' => $v_res['CategoryName1'],
                    ];

                    $size_arr = [
                        ['puhuo_num'=>$v_res['Stock_00_puhuo'], 'Size'=>$v_res['Stock_00_size']],
                        ['puhuo_num'=>$v_res['Stock_29_puhuo'], 'Size'=>$v_res['Stock_29_size']],
                        ['puhuo_num'=>$v_res['Stock_30_puhuo'], 'Size'=>$v_res['Stock_30_size']],
                        ['puhuo_num'=>$v_res['Stock_31_puhuo'], 'Size'=>$v_res['Stock_31_size']],
                        ['puhuo_num'=>$v_res['Stock_32_puhuo'], 'Size'=>$v_res['Stock_32_size']],
                        ['puhuo_num'=>$v_res['Stock_33_puhuo'], 'Size'=>$v_res['Stock_33_size']],
                        ['puhuo_num'=>$v_res['Stock_34_puhuo'], 'Size'=>$v_res['Stock_34_size']],
                        ['puhuo_num'=>$v_res['Stock_35_puhuo'], 'Size'=>$v_res['Stock_35_size']],
                        ['puhuo_num'=>$v_res['Stock_36_puhuo'], 'Size'=>$v_res['Stock_36_size']],
                        ['puhuo_num'=>$v_res['Stock_38_puhuo'], 'Size'=>$v_res['Stock_38_size']],
                        ['puhuo_num'=>$v_res['Stock_40_puhuo'], 'Size'=>$v_res['Stock_40_size']],
                        ['puhuo_num'=>$v_res['Stock_42_puhuo'], 'Size'=>$v_res['Stock_42_size']],
                    ];
                    foreach ($size_arr as $k_size_arr=>&$v_size_arr) {
                        if ($v_size_arr['puhuo_num']) {
                            $tmp_arr['Size'] = $v_size_arr['Size'];
                            $tmp_arr['puhuo_num'] = $v_size_arr['puhuo_num'];
                            $excel_output_data[] = $tmp_arr;
                        }
                    }

                }

                //变更导单状态
                $this->service->change_caogao_status($res['data']);

                $this->service->add_puhuo_daodan($excel_output_data);

                $header = [
                    ['*订单号', 'order_no'],
                    ['*仓库编号', 'WarehouseCode'],
                    ['*店铺编号', 'CustomerCode'],
                    ['*打包后立即发出', 'send_out'],
                    ['*差异出货需二次确认', 're_confirm'],
                    ['*货号', 'GoodsNo'],
                    ['*尺码', 'Size'],
                    ['*颜色编码', 'ColorCode'],
                    ['*铺货数', 'puhuo_num'],
                    ['*状态/1草稿,2预发布,3确定发布', 'status'],
                    ['备注', 'remark'],
                ];
    
                return Excel::exportData($excel_output_data, $header, 'puhuo_daodan_caogao_' .count($excel_output_data) , 'xlsx');

            }

        }        

    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {

        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);
        
    }

    // 获取筛选栏多选参数-草稿
    public function getXmMapSelectCaogao() {

        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect(2)]);
        
    }

    /**
     * @NodeAnotation(title="保存草稿")
     */
    public function save_caogao() {

        $params = $this->request->param();

        if (request()->isAjax()) {

            $res = $this->service->deal_caogao($params);
            return $res;

        } else {

            return json(["code" => "500", "msg" => "error", "data" => []]);

        }        

    }

    /**
     * @NodeAnotation(title="铺货草稿列表")
     */
    public function caogao_index() {

        $params = $this->request->param();
        // print_r($params);die;
        $statistic = $this->service->puhuo_statistic($params);

        if (request()->isAjax()) {

            $res = $this->service->caogao_index($params);
            if ($res['data']) {
                foreach ($res['data'] as &$v_data) {
                    if ($v_data['is_total'] == 1) {
                        $v_data['StoreArea'] = '';
                        $v_data['xiuxian_num'] = '';
                        $v_data['score_sort'] = '';
                        $v_data['StyleCategoryName1'] = '';
                    }
                    $v_data['Stock_00_puhuo'] = $v_data['Stock_00_puhuo'] ?: '';
                    $v_data['Stock_29_puhuo'] = $v_data['Stock_29_puhuo'] ?: '';
                    $v_data['Stock_30_puhuo'] = $v_data['Stock_30_puhuo'] ?: '';
                    $v_data['Stock_31_puhuo'] = $v_data['Stock_31_puhuo'] ?: '';
                    $v_data['Stock_32_puhuo'] = $v_data['Stock_32_puhuo'] ?: '';
                    $v_data['Stock_33_puhuo'] = $v_data['Stock_33_puhuo'] ?: '';
                    $v_data['Stock_34_puhuo'] = $v_data['Stock_34_puhuo'] ?: '';
                    $v_data['Stock_35_puhuo'] = $v_data['Stock_35_puhuo'] ?: '';
                    $v_data['Stock_36_puhuo'] = $v_data['Stock_36_puhuo'] ?: '';
                    $v_data['Stock_38_puhuo'] = $v_data['Stock_38_puhuo'] ?: '';
                    $v_data['Stock_40_puhuo'] = $v_data['Stock_40_puhuo'] ?: '';
                    $v_data['Stock_42_puhuo'] = $v_data['Stock_42_puhuo'] ?: '';
                    $v_data['Stock_44_puhuo'] = $v_data['Stock_44_puhuo'] ?: '';
                    $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity_puhuo'] ?: '';
                    $v_data['create_time'] = $v_data['create_time'] ? substr($v_data['create_time'], 0, 10) : '';
                    $v_data['is_delete_str'] = $v_data['is_delete']==1 ? '已导' : '未导';
                }
            }

            return json(array_merge(["code" => "0", "msg" => "", "count" => $res['count'], "data" => $res['data'],  'create_time' => date('Y-m-d')], $statistic));
        } else {
            return View('system/puhuo/caogao_index', ['setTime1' => date('Y-m-d'), 'setTime2' => date('Y-m-d')]);
        }        

    }


}
