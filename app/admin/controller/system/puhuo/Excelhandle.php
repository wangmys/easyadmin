<?php

namespace app\admin\controller\system\puhuo;

use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\CustomerModel;
use app\admin\service\ExcelhandleService;
use app\common\logic\execl\PHPExecl;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use think\App;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class Excel
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="铺货excel",auth=true)
 */
class Excelhandle extends AdminController
{
    protected $service;
    protected $request;
    protected $erp;
    protected $mysql;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new ExcelhandleService();
        $this->erp = Db::connect('sqlsrv');
        $this->mysql = Db::connect('mysql');
    }

    /**
     * @return mixed|\think\response\Json
     * @NodeAnotation(title="铺货excel列表",auth=true)
     */
    public function index()
    {


        if (request()->isAjax()) {

            $param = $this->request->param();
            $where = [];

            if (isset($param['WarehouseName']) && $param['WarehouseName']) {
                $where[] = ['WarehouseName', 'IN', explode(',', $param['WarehouseName'])];
            }
            if (isset($param['CategoryName1']) && $param['CategoryName1']) {
                $where[] = ['CategoryName1', 'IN', explode(',', $param['CategoryName1'])];
            }
            if (isset($param['CustomerName']) && $param['CustomerName']) {
                $where[] = ['CustomerName', 'IN', explode(',', $param['CustomerName'])];
            }
            if (isset($param['GoodsNo']) && $param['GoodsNo']) {
                $where[] = ['GoodsNo', 'IN', explode(',', $param['GoodsNo'])];
            }

            $db = Db::connect('mysql');
            $query = $db->table('sp_lyp_puhuo_excel');
            $count = $query->where($where)->count();
            $res = $query->where($where)
                ->where(function ($q) {
                    $q->whereOr('Stock_00', '=', '0')
                        ->whereOr('Stock_29', '=', '0')
                        ->whereOr('Stock_30', '=', '0')
                        ->whereOr('Stock_31', '=', '0')
                        ->whereOr('Stock_32', '=', '0')
                        ->whereOr('Stock_33', '=', '0')
                        ->whereOr('Stock_34', '=', '0')
                        ->whereOr('Stock_35', '=', '0')
                        ->whereOr('Stock_36', '=', '0')
                        ->whereOr('Stock_38', '=', '0')
                        ->whereOr('Stock_40', '=', '0')
                        ->whereOr('Stock_42', '=', '0');
                })
                ->select()->toArray();
//                ->fetchSql(1)->select();
            foreach ($res as &$item) {
                $item['Stock_Quantity_puhuo'] = $item['Stock_00_puhuo']
                    + $item['Stock_29_puhuo'] + $item['Stock_30_puhuo']
                    + $item['Stock_31_puhuo'] + $item['Stock_32_puhuo']
                    + $item['Stock_33_puhuo'] + $item['Stock_34_puhuo']
                    + $item['Stock_35_puhuo'] + $item['Stock_36_puhuo']
                    + $item['Stock_38_puhuo'] + $item['Stock_40_puhuo']
                    + $item['Stock_42_puhuo'];
                $item['Stock_00_puhuo'] = $item['Stock_00_puhuo'] != 0 ? $item['Stock_00_puhuo'] : ' ';
                $item['Stock_29_puhuo'] = $item['Stock_29_puhuo'] != 0 ? $item['Stock_29_puhuo'] : ' ';
                $item['Stock_30_puhuo'] = $item['Stock_30_puhuo'] != 0 ? $item['Stock_30_puhuo'] : ' ';
                $item['Stock_31_puhuo'] = $item['Stock_31_puhuo'] != 0 ? $item['Stock_31_puhuo'] : ' ';
                $item['Stock_32_puhuo'] = $item['Stock_32_puhuo'] != 0 ? $item['Stock_32_puhuo'] : ' ';
                $item['Stock_33_puhuo'] = $item['Stock_33_puhuo'] != 0 ? $item['Stock_33_puhuo'] : ' ';
                $item['Stock_34_puhuo'] = $item['Stock_34_puhuo'] != 0 ? $item['Stock_34_puhuo'] : ' ';
                $item['Stock_35_puhuo'] = $item['Stock_35_puhuo'] != 0 ? $item['Stock_35_puhuo'] : ' ';
                $item['Stock_36_puhuo'] = $item['Stock_36_puhuo'] != 0 ? $item['Stock_36_puhuo'] : ' ';
                $item['Stock_38_puhuo'] = $item['Stock_38_puhuo'] != 0 ? $item['Stock_38_puhuo'] : ' ';
                $item['Stock_40_puhuo'] = $item['Stock_40_puhuo'] != 0 ? $item['Stock_40_puhuo'] : ' ';
                $item['Stock_42_puhuo'] = $item['Stock_42_puhuo'] != 0 ? $item['Stock_42_puhuo'] : ' ';
                $item['Stock_44_puhuo'] = $item['Stock_44_puhuo'] != 0 ? $item['Stock_44_puhuo'] : ' ';
//                dd($item['Stock_00_puhuo']);
            }
            $data = [
                "code" => "0", "msg" => "", "count" => $count, "data" => $res
            ];
//            dd($res);
            return json($data);
        }

        return $this->fetch();

    }

    public function xm_select()
    {
        $res = $this->mysql->table('sp_lyp_puhuo_excel')->where(function ($q) {
            $q->whereOr('Stock_00', '=', '0')
                ->whereOr('Stock_29', '=', '0')
                ->whereOr('Stock_30', '=', '0')
                ->whereOr('Stock_31', '=', '0')
                ->whereOr('Stock_32', '=', '0')
                ->whereOr('Stock_33', '=', '0')
                ->whereOr('Stock_34', '=', '0')
                ->whereOr('Stock_35', '=', '0')
                ->whereOr('Stock_36', '=', '0')
                ->whereOr('Stock_38', '=', '0')
                ->whereOr('Stock_40', '=', '0')
                ->whereOr('Stock_42', '=', '0');
        })->select()->toArray();

        $CustomerName = array_unique(array_column($res, 'CustomerName'));
        $CustomerName = $this->service->xm_select($CustomerName);
        $CategoryName1 = array_unique(array_column($res, 'CategoryName1'));
        $CategoryName1 = $this->service->xm_select($CategoryName1);

        $GoodsNo = array_unique(array_column($res, 'GoodsNo'));
        $GoodsNo = $this->service->xm_select($GoodsNo);

        $WarehouseName = array_unique(array_column($res, 'WarehouseName'));
        $WarehouseName = $this->service->xm_select($WarehouseName);

        return json(compact('WarehouseName', 'CustomerName', 'CategoryName1', 'GoodsNo'));
    }


    /**
     * @return void
     * @NodeAnotation(title="导出",auth=false)
     */
    public function export_excel($all = '')
    {

        $where = [];
        if (!$all) {
            $where = [
                ['Stock_00', '=', '1'],
                ['Stock_29', '=', '1'],
                ['Stock_30', '=', '1'],
                ['Stock_31', '=', '1'],
                ['Stock_32', '=', '1'],
                ['Stock_33', '=', '1'],
                ['Stock_34', '=', '1'],
                ['Stock_35', '=', '1'],
                ['Stock_36', '=', '1'],
                ['Stock_38', '=', '1'],
                ['Stock_40', '=', '1'],
                ['Stock_42', '=', '1'],
            ];

        }

        $res = $this->mysql->table('sp_lyp_puhuo_excel')->where($where)->select();

        $excel_output_data = [];
        foreach ($res as $k_res => $v_res) {

            $tmp_arr = [
                'order_no' => $v_res['uuid'],
                'WarehouseCode' => $v_res['WarehouseCode'],
                'CustomerCode' => $v_res['CustomerCode'],
                'send_out' => 'Y',
                're_confirm' => 'Y',
                'GoodsNo' => $v_res['GoodsNo'],
                'Size' => '',//
                'ColorCode' => $v_res['ColorCode'],
                'puhuo_num' => 0,//
                'status' => 2,
                'remark' => $v_res['CategoryName1'] ? mb_substr($v_res['CategoryName1'], 0, 1) : '',
            ];

            $size_arr = [
                ['puhuo_num' => $v_res['Stock_00_puhuo'], 'Size' => $v_res['Stock_00_size']],
                ['puhuo_num' => $v_res['Stock_29_puhuo'], 'Size' => $v_res['Stock_29_size']],
                ['puhuo_num' => $v_res['Stock_30_puhuo'], 'Size' => $v_res['Stock_30_size']],
                ['puhuo_num' => $v_res['Stock_31_puhuo'], 'Size' => $v_res['Stock_31_size']],
                ['puhuo_num' => $v_res['Stock_32_puhuo'], 'Size' => $v_res['Stock_32_size']],
                ['puhuo_num' => $v_res['Stock_33_puhuo'], 'Size' => $v_res['Stock_33_size']],
                ['puhuo_num' => $v_res['Stock_34_puhuo'], 'Size' => $v_res['Stock_34_size']],
                ['puhuo_num' => $v_res['Stock_35_puhuo'], 'Size' => $v_res['Stock_35_size']],
                ['puhuo_num' => $v_res['Stock_36_puhuo'], 'Size' => $v_res['Stock_36_size']],
                ['puhuo_num' => $v_res['Stock_38_puhuo'], 'Size' => $v_res['Stock_38_size']],
                ['puhuo_num' => $v_res['Stock_40_puhuo'], 'Size' => $v_res['Stock_40_size']],
                ['puhuo_num' => $v_res['Stock_42_puhuo'], 'Size' => $v_res['Stock_42_size']],
            ];
            // print_r([$size_arr,   $wait_goods_info]);die;
            foreach ($size_arr as $k_size_arr => $v_size_arr) {
                if ($v_size_arr['puhuo_num'] && $v_size_arr['Size']) {
                    $tmp_arr['Size'] = $v_size_arr['Size'];
                    $tmp_arr['puhuo_num'] = $v_size_arr['puhuo_num'];
                    $excel_output_data[] = $tmp_arr;
                }
            }

        }

        //相同单号，备注处理
        $excel_output_data2 = [];
        $end_output_data = [];
        if ($excel_output_data) {
            foreach ($excel_output_data as $v_output_data) {
                $excel_output_data2[$v_output_data['order_no']][] = $v_output_data;
            }
            foreach ($excel_output_data2 as $vv_output_data) {
                $remarks = array_unique(array_column($vv_output_data, 'remark'));
                $remarks = $remarks ? implode('/', $remarks) : '';
                foreach ($vv_output_data as $vvv_output_data) {
                    $vvv_output_data['remark'] = $remarks;
                    $end_output_data[] = $vvv_output_data;
                }
            }
        }
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


        return Excel::exportData($end_output_data, $header, date('Ymd') . '_CTY_1', 'xlsx');


    }

    /**
     * @NodeAnotation(title="excel转换")
     */
    public function import_excel()
    {
        set_time_limit(0);
        if (request()->isAjax()) {
            $file = request()->file('file');
            $new_name = "import_excel" . '_' . uuid('zhuanhuan') . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/' . date('Ymd', time()) . '/';   //文件保存路径
            $info = $file->move($save_path, $new_name);
            //读取导入的需要转单的excel
            $read_column = [
                'A' => '云仓',
                'B' => '货号',
                'C' => '店铺',
                'D' => '44/28/',
                'E' => '46/29/165/38/M/105',
                'F' => '48/30/170/39/L/110',
                'G' => '50/31/175/40/XL/115',
                'H' => '52/32/180/41/2XL/120',
                'I' => '54/33/185/42/3XL/125',
                'J' => '56/34/190/43/4XL/130',
                'K' => '58/35/195/44/5XL/',
                'L' => '60/36/6XL/',
                'M' => '38/7XL',
                'N' => '40',
                'O' => '42',
                'P' => '合计',
                'Q' => '性质',
            ];
            try {
                $yc_data = $this->service->get_yunchang_goods_data();
                $ycRes = [];
                foreach ($yc_data as $yc_v) {
                    $yc_v['Stock_00'] = $yc_v['Stock_00'] > 0 ? $yc_v['Stock_00'] : 0;
                    $yc_v['Stock_29'] = $yc_v['Stock_29'] > 0 ? $yc_v['Stock_29'] : 0;
                    $yc_v['Stock_30'] = $yc_v['Stock_30'] > 0 ? $yc_v['Stock_30'] : 0;
                    $yc_v['Stock_31'] = $yc_v['Stock_31'] > 0 ? $yc_v['Stock_31'] : 0;
                    $yc_v['Stock_32'] = $yc_v['Stock_32'] > 0 ? $yc_v['Stock_32'] : 0;
                    $yc_v['Stock_33'] = $yc_v['Stock_33'] > 0 ? $yc_v['Stock_33'] : 0;
                    $yc_v['Stock_34'] = $yc_v['Stock_34'] > 0 ? $yc_v['Stock_34'] : 0;
                    $yc_v['Stock_35'] = $yc_v['Stock_35'] > 0 ? $yc_v['Stock_35'] : 0;
                    $yc_v['Stock_36'] = $yc_v['Stock_36'] > 0 ? $yc_v['Stock_36'] : 0;
                    $yc_v['Stock_38'] = $yc_v['Stock_38'] > 0 ? $yc_v['Stock_38'] : 0;
                    $yc_v['Stock_40'] = $yc_v['Stock_40'] > 0 ? $yc_v['Stock_40'] : 0;
                    $yc_v['Stock_42'] = $yc_v['Stock_42'] > 0 ? $yc_v['Stock_42'] : 0;

                    $ycRes[$yc_v['WarehouseName'] . $yc_v['GoodsNo']] = $yc_v;
                }

                $res = importExcel_m($save_path . $new_name, $read_column);
                $arr = [];

                foreach ($res as $key => &$item) {
                    $erp = $this->erp->table('ErpGoods')->alias('a')->where('a.GoodsNo', $item['货号'])
                        ->leftjoin('ErpGoodsColor c', 'c.GoodsId=a.GoodsId')
                        ->field('a.*,c.ColorDesc, c.ColorCode')
                        ->find();
                    if (!$erp) {
                        continue;
                    }
                    $sql = "SELECT
    bgs.Size
FROM
    ErpGoods a
    LEFT JOIN ErpGoodsSize gs ON gs.GoodsId= a.GoodsId
    LEFT JOIN ErpBaseGoodsSize bgs ON bgs.SizeId= gs.SizeId 
WHERE
    gs.IsEnable=1 and
    a.GoodsNo= '{$item['货号']}'
ORDER BY
    bgs.ViewOrder ASC";

                    $erpSize = $this->erp->query($sql);
                    $erpSize = array_column($erpSize, 'Size');

                    $where = [
                        ['CustomerName', '=', $item['店铺']],
                        ['RegionId', '<>', 55]
                    ];
                    $erpCustomer = $this->erp->table('ErpCustomer')->where($where)->find();
                    $ErpWarehouse = $this->erp->table('ErpWarehouse')->where('WarehouseName', $item['云仓'])->find();

                    $Stock_00_puhuo = $item['44/28/'] ?? 0;
                    $Stock_29_puhuo = $item['46/29/165/38/M/105'] ?? 0;
                    $Stock_30_puhuo = $item['48/30/170/39/L/110'] ?? 0;
                    $Stock_31_puhuo = $item['50/31/175/40/XL/115'] ?? 0;
                    $Stock_32_puhuo = $item['52/32/180/41/2XL/120'] ?? 0;
                    $Stock_33_puhuo = $item['54/33/185/42/3XL/125'] ?? 0;
                    $Stock_34_puhuo = $item['56/34/190/43/4XL/130'] ?? 0;
                    $Stock_35_puhuo = $item['58/35/195/44/5XL/'] ?? 0;
                    $Stock_36_puhuo = $item['60/36/6XL/'] ?? 0;
                    $Stock_38_puhuo = $item['38/7XL'] ?? 0;
                    $Stock_40_puhuo = $item['40'] ?? 0;
                    $Stock_42_puhuo = $item['42'] ?? 0;

                    if (isset($ycRes[$item['云仓'] . $item['货号']])) {  //云仓存在货号


                        $Stock_00 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_00']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_00'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_00'] : 0;
                        $Stock_29 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_29']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_29'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_29'] : 0;
                        $Stock_30 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_30']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_30'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_30'] : 0;
                        $Stock_31 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_31']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_31'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_31'] : 0;
                        $Stock_32 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_32']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_32'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_32'] : 0;
                        $Stock_33 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_33']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_33'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_33'] : 0;
                        $Stock_34 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_34']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_34'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_34'] : 0;
                        $Stock_35 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_35']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_35'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_35'] : 0;
                        $Stock_36 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_36']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_36'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_36'] : 0;
                        $Stock_38 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_38']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_38'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_38'] : 0;
                        $Stock_40 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_40']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_40'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_40'] : 0;
                        $Stock_42 = isset($ycRes[$item['云仓'] . $item['货号']]['Stock_42']) && $ycRes[$item['云仓'] . $item['货号']]['Stock_42'] > 0 ? $ycRes[$item['云仓'] . $item['货号']]['Stock_42'] : 0;
                        

                        $Stock_00_sub = $Stock_00 - (int)$Stock_00_puhuo;
                        if ($Stock_00_sub >= 0) {
                            $Stock_00_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_00'] = $Stock_00_sub;
                        } else {
                            $Stock_00_m = 0;
                        }
                        $Stock_29_sub = $Stock_29 - (int)$Stock_29_puhuo;
                        if ($Stock_29_sub >= 0) {
                            $Stock_29_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_29'] = $Stock_29_sub;
                        } else {
                            $Stock_29_m = 0;
                        }

                        $Stock_30_sub = $Stock_30 - (int)$Stock_30_puhuo;

                        if ($Stock_30_sub >= 0) {
                            $Stock_30_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_30'] = $Stock_30_sub;
                        } else {
                            $Stock_30_m = 0;
                        }

                        $Stock_31_sub = $Stock_31 - (int)$Stock_31_puhuo;
                        if ($Stock_31_sub >= 0) {
                            $Stock_31_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_31'] = $Stock_31_sub;
                        } else {
                            $Stock_31_m = 0;
                        }

                        $Stock_32_sub = $Stock_32 - (int)$Stock_32_puhuo;
                        if ($Stock_32_sub >= 0) {
                            $Stock_32_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_32'] = $Stock_32_sub;
                        } else {
                            $Stock_32_m = 0;
                        }

                        $Stock_33_sub = $Stock_33 - (int)$Stock_33_puhuo;
                        if ($Stock_33_sub >= 0) {
                            $Stock_33_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_33'] = $Stock_33_sub;
                        } else {
                            $Stock_33_m = 0;
                        }

                        $Stock_34_sub = $Stock_34 - (int)$Stock_34_puhuo;
                        if ($Stock_34_sub >= 0) {
                            $Stock_34_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_34'] = $Stock_34_sub;
                        } else {
                            $Stock_34_m = 0;
                        }

                        $Stock_35_sub = $Stock_35 - (int)$Stock_35_puhuo;
                        if ($Stock_35_sub >= 0) {
                            $Stock_35_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_35'] = $Stock_35_sub;
                        } else {
                            $Stock_35_m = 0;
                        }

                        $Stock_36_sub = $Stock_36 - (int)$Stock_36_puhuo;
                        if ($Stock_36_sub >= 0) {
                            $Stock_36_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_36'] = $Stock_36_sub;
                        } else {
                            $Stock_36_m = 0;
                        }

                        $Stock_38_sub = $Stock_38 - (int)$Stock_38_puhuo;
                        if ($Stock_38_sub >= 0) {
                            $Stock_38_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_38'] = $Stock_38_sub;
                        } else {
                            $Stock_38_m = 0;
                        }

                        $Stock_40_sub = $Stock_40 - (int)$Stock_40_puhuo;
                        if ($Stock_40_sub >= 0) {
                            $Stock_40_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_40'] = $Stock_40_sub;
                        } else {
                            $Stock_40_m = 0;
                        }

                        $Stock_42_sub = $Stock_42 - (int)$Stock_42_puhuo;
                        if ($Stock_42_sub >= 0) {
                            $Stock_42_m = 1;
                            $ycRes[$item['云仓'] . $item['货号']]['Stock_42'] = $Stock_42_sub;
                        } else {
                            $Stock_42_m = 0;
                        }
                    } else {
                        $Stock_00_m = 0;
                        $Stock_29_m = 0;
                        $Stock_30_m = 0;
                        $Stock_31_m = 0;
                        $Stock_32_m = 0;
                        $Stock_33_m = 0;
                        $Stock_34_m = 0;
                        $Stock_35_m = 0;
                        $Stock_36_m = 0;
                        $Stock_38_m = 0;
                        $Stock_40_m = 0;
                        $Stock_42_m = 0;
                    }


                    $arr[] = [
                        'uuid' => $item['单号'] ?? '',
                        'WarehouseName' => $item['云仓'] ?? '',
                        'GoodsNo' => $item['货号'] ?? '',
                        'CustomerName' => $item['店铺'] ?? '',
                        'xingzhi' => $item['性质'] ?? '',
//                    '' => $item['合计'],
                        'Stock_00_puhuo' => $Stock_00_puhuo,
                        'Stock_29_puhuo' => $Stock_29_puhuo,
                        'Stock_30_puhuo' => $Stock_30_puhuo,
                        'Stock_31_puhuo' => $Stock_31_puhuo,
                        'Stock_32_puhuo' => $Stock_32_puhuo,
                        'Stock_33_puhuo' => $Stock_33_puhuo,
                        'Stock_34_puhuo' => $Stock_34_puhuo,
                        'Stock_35_puhuo' => $Stock_35_puhuo,
                        'Stock_36_puhuo' => $Stock_36_puhuo,
                        'Stock_38_puhuo' => $Stock_38_puhuo,
                        'Stock_40_puhuo' => $Stock_40_puhuo,
                        'Stock_42_puhuo' => $Stock_42_puhuo,

                        'Stock_00_size' => $erpSize[0] ?? '',
                        'Stock_29_size' => $erpSize[1] ?? '',
                        'Stock_30_size' => $erpSize[2] ?? '',
                        'Stock_31_size' => $erpSize[3] ?? '',
                        'Stock_32_size' => $erpSize[4] ?? '',
                        'Stock_33_size' => $erpSize[5] ?? '',
                        'Stock_34_size' => $erpSize[6] ?? '',
                        'Stock_35_size' => $erpSize[7] ?? '',
                        'Stock_36_size' => $erpSize[8] ?? '',
                        'Stock_38_size' => $erpSize[9] ?? '',
                        'Stock_40_size' => $erpSize[10] ?? '',
                        'Stock_42_size' => $erpSize[11] ?? '',
                        'Stock_00' => $Stock_00_m,
                        'Stock_29' => $Stock_29_m,
                        'Stock_30' => $Stock_30_m,
                        'Stock_31' => $Stock_31_m,
                        'Stock_32' => $Stock_32_m,
                        'Stock_33' => $Stock_33_m,
                        'Stock_34' => $Stock_34_m,
                        'Stock_35' => $Stock_35_m,
                        'Stock_36' => $Stock_36_m,
                        'Stock_38' => $Stock_38_m,
                        'Stock_40' => $Stock_40_m,
                        'Stock_42' => $Stock_42_m,

                        'WarehouseCode' => $ErpWarehouse['WarehouseCode'] ?? '',

                        'TimeCategoryName2' => $erp['TimeCategoryName2'],
                        'CategoryName1' => $erp['CategoryName1'],
                        'CategoryName2' => $erp['CategoryName2'],
                        'CategoryName' => $erp['CategoryName'],
                        'Lingxing' => mb_substr($erp['CategoryName'], 0, 2),
                        'ColorDesc' => $erp['ColorDesc'],
                        'ColorCode' => $erp['ColorCode'],
                        'UnitPrice' => $erp['UnitPrice'],
                        'StyleCategoryName2' => $erp['StyleCategoryName2'],
                        'StyleCategoryName' => $erp['StyleCategoryName'],
                        'CustomerCode' => $erpCustomer['CustomerCode'] ?? '',
                        'State' => $erpCustomer['State'] ?? '',
                        'CustomItem17' => $erpCustomer['CustomItem17'] ?? '',
                        'Mathod' => $erpCustomer['MathodId'] == 4 ? '直营' : '加盟',
                        'CustomerGrade' => $erpCustomer['CustomerGrade'] ?? '',
                        'StoreArea' => $erpCustomer['CustomItem27'] ?: $erpCustomer['StoreArea'],
                    ];

                }

                try {
                    $this->mysql->Query("truncate table sp_lyp_puhuo_excel;");
                    //铺货日志批量入库
                    $chunk_list = $arr ? array_chunk($arr, 500) : [];
                    if ($chunk_list) {
                        foreach ($chunk_list as $key => $val) {
                            $this->mysql->table('sp_lyp_puhuo_excel')->strict(false)->insertAll($val);
                        }
                    }
                } catch (Exception $e) {
                    dd($e->getMessage());
                }
//                Cache::store('cache')->set('im_excel', $res, 3600);
            } catch (Exception $e) {
                return json(['code' => 400, 'msg' => $e->getMessage()]);
            }

            if ($res) {
                return json(['code' => 0, 'msg' => '导入成功', 'data' => $save_path . $new_name]);
            } else {
                return json(['code' => 400, 'msg' => 'error,请联系系统管理员']);
            }

        }

    }


}