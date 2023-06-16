<?php
namespace app\admin\controller\system;

use app\common\controller\AdminController;
use app\admin\service\SkcService;
use jianyan\excel\Excel;
use think\Request;

/**
 * Class Skc
 * @package app\admin\controller\system
 */
class Skc extends AdminController
{
    protected $service;
    protected $request;

    public function __construct(Request $request)
    {
        $this->service = new SkcService();
        $this->request = $request;
    }

    /**
     * 单店上装明细
     */
    public function get_sz_index() {

        if (request()->isAjax()) {

            $params = $this->request->param();
            $res = $this->service->get_sz_index($params);
            if ($res['data']) {

                foreach ($res['data'] as &$v_data) {

                    $v_data['week_sales_fl'] = $v_data['week_sales_fl']>0 ? $v_data['week_sales_fl'].'%' : '';
                    $v_data['week_sales_yl'] = $v_data['week_sales_yl']>0 ? $v_data['week_sales_yl'].'%' : '';
                    $v_data['week_sales_qt'] = $v_data['week_sales_qt']>0 ? $v_data['week_sales_qt'].'%' : '';
                    $v_data['week_sales_xxdc'] = $v_data['week_sales_xxdc']>0 ? $v_data['week_sales_xxdc'].'%' : '';
                    $v_data['week_sales_ztdc'] = $v_data['week_sales_ztdc']>0 ? $v_data['week_sales_ztdc'].'%' : '';
                    $v_data['week_sales_jk'] = $v_data['week_sales_jk']>0 ? $v_data['week_sales_jk'].'%' : '';
                    $v_data['week_sales_tz'] = $v_data['week_sales_tz']>0 ? $v_data['week_sales_tz'].'%' : '';
                    $v_data['week_sales_dxxj'] = $v_data['week_sales_dxxj']>0 ? $v_data['week_sales_dxxj'].'%' : '';
                    $v_data['week_sales_ct'] = $v_data['week_sales_ct']>0 ? $v_data['week_sales_ct'].'%' : '';
                    $v_data['week_sales_ztcc'] = $v_data['week_sales_ztcc']>0 ? $v_data['week_sales_ztcc'].'%' : '';
                    $v_data['week_sales_xxcc'] = $v_data['week_sales_xxcc']>0 ? $v_data['week_sales_xxcc'].'%' : '';
                    $v_data['week_sales_zzs'] = $v_data['week_sales_zzs']>0 ? $v_data['week_sales_zzs'].'%' : '';
                    $v_data['week_sales_wy'] = $v_data['week_sales_wy']>0 ? $v_data['week_sales_wy'].'%' : '';
                    $v_data['week_sales_cxxj'] = $v_data['week_sales_cxxj']>0 ? $v_data['week_sales_cxxj'].'%' : '';
                    $v_data['week_sales_dx'] = $v_data['week_sales_dx']>0 ? $v_data['week_sales_dx'].'%' : '';
                    $v_data['week_sales_wtjk'] = $v_data['week_sales_wtjk']>0 ? $v_data['week_sales_wtjk'].'%' : '';
                    $v_data['week_sales_nzy'] = $v_data['week_sales_nzy']>0 ? $v_data['week_sales_nzy'].'%' : '';
                    $v_data['week_sales_py'] = $v_data['week_sales_py']>0 ? $v_data['week_sales_py'].'%' : '';
                    $v_data['week_sales_txk'] = $v_data['week_sales_txk']>0 ? $v_data['week_sales_txk'].'%' : '';
                    $v_data['week_sales_tx'] = $v_data['week_sales_tx']>0 ? $v_data['week_sales_tx'].'%' : '';
                    $v_data['week_sales_wtxj'] = $v_data['week_sales_wtxj']>0 ? $v_data['week_sales_wtxj'].'%' : '';

                    $v_data['skc_fl'] = $v_data['skc_fl'] ?: '';
                    $v_data['skc_yl'] = $v_data['skc_yl'] ?: '';
                    $v_data['skc_qt'] = $v_data['skc_qt'] ?: '';
                    $v_data['skc_xxdc'] = $v_data['skc_xxdc'] ?: '';
                    $v_data['skc_ztdc'] = $v_data['skc_ztdc'] ?: '';
                    $v_data['skc_jk'] = $v_data['skc_jk'] ?: '';
                    $v_data['skc_tz'] = $v_data['skc_tz'] ?: '';
                    $v_data['skc_dxxj'] = $v_data['skc_dxxj'] ?: '';
                    $v_data['skc_ct'] = $v_data['skc_ct'] ?: '';
                    $v_data['skc_ztcc'] = $v_data['skc_ztcc'] ?: '';
                    $v_data['skc_xxcc'] = $v_data['skc_xxcc'] ?: '';
                    $v_data['skc_zzs'] = $v_data['skc_zzs'] ?: '';
                    $v_data['skc_wy'] = $v_data['skc_wy'] ?: '';
                    $v_data['skc_cxxj'] = $v_data['skc_cxxj'] ?: '';
                    $v_data['skc_dx'] = $v_data['skc_dx'] ?: '';
                    $v_data['skc_wtjk'] = $v_data['skc_wtjk'] ?: '';
                    $v_data['skc_nzy'] = $v_data['skc_nzy'] ?: '';
                    $v_data['skc_py'] = $v_data['skc_py'] ?: '';
                    $v_data['skc_tx'] = $v_data['skc_tx'] ?: '';
                    $v_data['skc_wtxj'] = $v_data['skc_wtxj'] ?: '';

                    $v_data['win_num_fl'] = $v_data['win_num_fl'] ?: '';
                    $v_data['win_num_yl'] = $v_data['win_num_yl'] ?: '';
                    $v_data['win_num_xxdc'] = $v_data['win_num_xxdc'] ?: '';
                    $v_data['win_num_ztdc'] = $v_data['win_num_ztdc'] ?: '';
                    $v_data['win_num_dxxj'] = $v_data['win_num_dxxj'] ?: '';
                    $v_data['win_num_ct'] = $v_data['win_num_ct'] ?: '';
                    $v_data['win_num_ztcc'] = $v_data['win_num_ztcc'] ?: '';
                    $v_data['win_num_xxcc'] = $v_data['win_num_xxcc'] ?: '';
                    $v_data['win_num_zzs'] = $v_data['win_num_zzs'] ?: '';
                    $v_data['win_num_wy'] = $v_data['win_num_wy'] ?: '';
                    $v_data['win_num_cxxj'] = $v_data['win_num_cxxj'] ?: '';
                    $v_data['win_num_dx'] = $v_data['win_num_dx'] ?: '';
                    $v_data['win_num_wtjk'] = $v_data['win_num_wtjk'] ?: '';
                    $v_data['win_num_nzy'] = $v_data['win_num_nzy'] ?: '';
                    $v_data['win_num_py'] = $v_data['win_num_py'] ?: '';
                    $v_data['win_num_tx'] = $v_data['win_num_tx'] ?: '';
                    $v_data['win_num_wtxj'] = $v_data['win_num_wtxj'] ?: '';

                }

            }
            // print_r($res);die;

            return json(["code" => "0", "msg" => "", "count" => $res['count'], "data" => $res['data'],  'create_time' => date('Y-m-d')]);
        } else {
            return View('get_sz_index', [

            ]);
        }        

    }

     /**
     * 夏季上装窗数满足情况
     */
    public function get_sz_statistic() {

        if (request()->isAjax()) {

            $res = $this->service->get_sz_statistic();

            return json(["code" => "0", "msg" => "", "count" => $res['count'], "data" => $res['data'],  'create_time' => date('Y-m-d')]);
        } else {
            return View('get_sz_statistic', [

            ]);
        }        

    }

}
