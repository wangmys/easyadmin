<?php
namespace app\admin\controller\system\puhuo;

use app\common\controller\AdminController;
use app\admin\service\PuhuoService;
use jianyan\excel\Excel;
use think\Request;

/**
 * Class Puhuo
 * @package app\admin\controller\system
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
     * 单店上装明细
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

    // 获取筛选栏多选参数
    public function getXmMapSelect() {

        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);
        
    }


}
