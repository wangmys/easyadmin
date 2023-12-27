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
    public function getXmMapSelect()
    {


        return json(["code" => "0", "msg" => "", "data" => $this->service->getXmMapSelect()]);

    }


    /**
     * @return mixed
     * @NodeAnotation(title="设置页面",auth=false)
     */
    public function set()
    {

        return $this->fetch();

    }


    /**
     * @return void
     * @NodeAnotation(title="保存",auth=false)
     */
    public function set_revise()
    {

        $param = $this->request->param();

        $this->service->set_revise($param);

        $this->success('ok');

    }


    /**
     * @return void
     * @NodeAnotation(title="导单功能",auth=false)
     */
    public function excel()
    {

        $where = [
            ['is_total', '=', '0']
        ];
        $res = $this->service->order_no($where);
        $date = date('Y-m-d');
        $tag = date('YmdHis');
        $excel_output_data = [];
        $config = Db::connect('mysql')->table('sp_lyp_puhuo_excel_config')->where(1)->find();
        $config['商品负责人'] = json_decode($config['商品负责人'], true);

        $remarkArr = [];
        foreach ($res as $item) {
            $str = $item['CategoryName1'] ? mb_substr($item['CategoryName1'], 0, 1) : '';
            if (!isset($remarkArr[$item['sort']])) {
                $remarkArr[$item['sort']] = [];
            }
            if (!in_array($str, $remarkArr[$item['sort']])) {
                $remarkArr[$item['sort']][] = $str;
            }
        }

        foreach ($res as $k_res => $v_res) {
            $tmp_arr = [
                'uuid' => $v_res['uuid'],
                'date' => $date,
                'sort' => $v_res['sort'],
                'CustomItem17' => $v_res['CustomItem17'],
                'tag' => $tag,
                'WarehouseCode' => $v_res['WarehouseCode'],
                'CustomerCode' => $v_res['CustomerCode'],
                'send_out' => 'Y',
                're_confirm' => 'Y',
                'GoodsNo' => $v_res['GoodsNo'],
                'Size' => '',
                'ColorCode' => $v_res['ColorCode'],
                'puhuo_num' => 0,
                'status' => 2,
                'remark' => implode('/', $remarkArr[$v_res['sort']]),
                'admin_id' => session('admin.id')
            ];
            $Size = $this->service->Size($v_res['GoodsNo']);

            $size_arr = [
                ['puhuo_num' => $v_res['Stock_00_puhuo'], 'Size' => $Size[0] ?? ''],
                ['puhuo_num' => $v_res['Stock_29_puhuo'], 'Size' => $Size[1] ?? ''],
                ['puhuo_num' => $v_res['Stock_30_puhuo'], 'Size' => $Size[2] ?? ''],
                ['puhuo_num' => $v_res['Stock_31_puhuo'], 'Size' => $Size[3] ?? ''],
                ['puhuo_num' => $v_res['Stock_32_puhuo'], 'Size' => $Size[4] ?? ''],
                ['puhuo_num' => $v_res['Stock_33_puhuo'], 'Size' => $Size[5] ?? ''],
                ['puhuo_num' => $v_res['Stock_34_puhuo'], 'Size' => $Size[6] ?? ''],
                ['puhuo_num' => $v_res['Stock_35_puhuo'], 'Size' => $Size[7] ?? ''],
                ['puhuo_num' => $v_res['Stock_36_puhuo'], 'Size' => $Size[8] ?? ''],
                ['puhuo_num' => $v_res['Stock_38_puhuo'], 'Size' => $Size[9] ?? ''],
                ['puhuo_num' => $v_res['Stock_40_puhuo'], 'Size' => $Size[10] ?? ''],
                ['puhuo_num' => $v_res['Stock_42_puhuo'], 'Size' => $Size[11] ?? ''],
            ];
            foreach ($size_arr as $k_size_arr => $v_size_arr) {
                if ($v_size_arr['puhuo_num'] && $v_size_arr['Size']) {
                    $tmp_arr['Size'] = $v_size_arr['Size'];
                    $tmp_arr['puhuo_num'] = $v_size_arr['puhuo_num'];
                    $excel_output_data[] = $tmp_arr;
                }
            }

        }
        try {
            $chunk_list = array_chunk($excel_output_data, 500);
            if ($chunk_list) {
                foreach ($chunk_list as $key => $val) {
                    $this->mysql->table('sp_lyp_puhuo_excel_data')->strict(false)->insertAll($val);
                }
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
        return $this->export_excel_runing($tag);


    }


    /**
     * @param $tag
     * @return string|void
     * @NodeAnotation(title="运行",auth=false)
     */
    public function export_excel_runing($tag)
    {

        $data = $this->mysql->table('sp_lyp_puhuo_excel_data')->where('tag', $tag)->select()->toArray();

        if (empty($data)) {
            echo '数据为空,请核对';
            die;
        }
        $where = [
            ['date', '=', date('Y-m-d')],
            ['CustomItem17', '=', $data[0]['CustomItem17']],
        ];
        $count = $this->mysql->table('sp_lyp_puhuo_excel_data')
            ->where($where)
            ->group('tag')
            ->select()->toArray();
        $count = count($count);
        if ($data) {
            $header = [
                ['*订单号', 'uuid', 'text', 18],
                ['*仓库编号', 'WarehouseCode', 'text'],
                ['*店铺编号', 'CustomerCode', 'text'],
                ['*打包后立即发出', 'send_out', 'text'],
                ['*差异出货需二次确认', 're_confirm', 'text'],
                ['*货号', 'GoodsNo', 'text', 12],
                ['*尺码', 'Size', 'text'],
                ['*颜色编码', 'ColorCode', 'text'],
                ['*铺货数', 'puhuo_num', 'number'],
                ['*状态/1草稿,2预发布,3确定发布', 'status', 'number'],
                ['备注', 'remark'],
            ];

            $path = 'm/excel/' . date('Ymd') . '/';
            $fileName = date('Ymd') . '_' . $data[0]['CustomItem17'] . '_' . $count;
            ExcelService::export($data, [$header], $fileName, $path);

            $url = $this->request->domain() . '/' . $path . $fileName . '.xlsx';
            $this->success('ok', ['url' => $url]);
        }


        return 'error';


    }


}