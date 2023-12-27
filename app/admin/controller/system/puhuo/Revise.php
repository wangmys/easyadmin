<?php

namespace app\admin\controller\system\puhuo;

use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\CustomerModel;
use app\admin\service\puhuo\ReviseService;
use app\common\service\ExcelService;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use think\App;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * Class Revise
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="铺货修订",auth=true)
 */
class Revise extends AdminController
{
    protected $service;
    protected $request;
    protected $erp;
    protected $mysql;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new ReviseService();
        $this->erp = Db::connect('sqlsrv');
        $this->mysql = Db::connect('mysql');
    }


    /**
     * @return mixed|\think\response\Json
     * @NodeAnotation(title="列表",auth=true)
     */
    public function index()
    {


        $param = $this->request->param();
        if (request()->isAjax()) {

            $res = $this->service->list($param);

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
            }

            return json(array_merge(["code" => "0", "msg" => "", "count" => $res['count'], "data" => $res['data'], 'create_time' => date('Y-m-d')]));
        }
//        $this->assign();
        return $this->fetch();

    }


    /**
     * @return \think\response\Json
     * @NodeAnotation(title="XM",auth=false)
     */
    public function getXmMapSelect(){


        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);

    }


    /**
     * @return mixed
     * @NodeAnotation(title="设置页面",auth=false)
     */
    public function set(){

        return $this->fetch();

    }


    /**
     * @return void
     * @NodeAnotation(title="保存",auth=false)
     */
    public function set_revise(){

        $param = $this->request->param();

        $this->service->set_revise($param);

        $this->success('ok');

    }


    /**
     * @return void
     * @NodeAnotation(title="导单功能",auth=false)
     */
    public function excel(){


        $header = array_merge($arr2, $arr3);

        $data = $this->service->index($where);

        $path = 'm/excel/' . date('Ymd') . '/';
        $fileName = '导购月业绩' . date('Ymd');
        ExcelService::export($data['list'], [$header], $fileName, $path);
        $url = $this->request->domain() . '/' . $path . $fileName . '.xlsx';
        $this->success('ok', ['url' => $url]);



    }




}