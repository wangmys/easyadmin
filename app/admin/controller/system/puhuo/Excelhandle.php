<?php

namespace app\admin\controller\system\puhuo;

use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\CustomerModel;
use app\admin\service\ExcelhandleService;
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
 * @ControllerAnnotation(title="",auth=true)
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

    public function index()
    {


        if (request()->isAjax()) {

            $get = $this->request->param();

            $where = [];
            $db = Db::connect('mysql');
            $query = $db->table('sp_lyp_puhuo_excel');
            $count = $query->where($where)->count();
            $res = $query->where($where)->select();
            $data = [
                "code" => "0", "msg" => "", "count" => $count, "data" => $res ?: []
            ];
            return json($data);
        }

        return $this->fetch();

    }

    public function xm_select()
    {

        $data = [
            'customer' => [['name' => '132', 'value' => '885']]
        ];
        return json($data);
    }


    /**
     * @return void
     * @NodeAnotation(title="导出",auth=false)
     */
    public function export_excel(){

        $res = $this->mysql->table('sp_lyp_puhuo_excel')->select();


        $excel_output_data = [];
        foreach ($res as $k_res=>$v_res) {

            $tmp_arr = [
                'order_no' => $v_res['uuid'],
                'WarehouseCode' =>$v_res['WarehouseCode'],
                'CustomerCode' =>$v_res['WarehouseCode'],
                'send_out' => 'Y',
                're_confirm' => 'Y',
                'GoodsNo' => $v_res['GoodsNo'],
                'Size' => '',//
                'ColorCode' => $v_res['ColorCode'],
                'puhuo_num' => 0,//
                'status' => 2,
                'remark' => $v_res['CategoryName1']?mb_substr($v_res['CategoryName1'], 0, 1):'',
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
            // print_r([$size_arr,   $wait_goods_info]);die;
            foreach ($size_arr as $k_size_arr=>$v_size_arr) {
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
        // print_r($end_output_data);die;

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

        return Excel::exportData($end_output_data, $header, 'import_excel_zhuanhuan' .count($end_output_data) , 'xlsx');


    }

    /**
     * @NodeAnotation(title="excel转换")
     */
    public function import_excel()
    {

        if (request()->isAjax()) {
            $file = request()->file('file');
            $new_name = "import_excel" . '_' . uuid('zhuanhuan') . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/' . date('Ymd', time()) . '/';   //文件保存路径
            $info = $file->move($save_path, $new_name);
            //读取导入的需要转单的excel
            $read_column = [
                'A' => '单号',
                'B' => '云仓',
                'C' => '货号',
                'D' => '店铺',
                'E' => '44/28/',
                'F' => '46/29/165/38/M/105',
                'G' => '48/30/170/39/L/110',
                'H' => '50/31/175/40/XL/115',
                'I' => '52/32/180/41/2XL/120',
                'J' => '54/33/185/42/3XL/125',
                'K' => '56/34/190/43/4XL/130',
                'L' => '58/35/195/44/5XL/',
                'M' => '60/36/6XL/',
                'N' => '38/7XL',
                'O' => '40',
                'P' => '42',
                'Q' => '合计',
            ];
            try {
                $res = importExcel($save_path . $new_name, $read_column);

                $arr = [];
                foreach ($res as $key => &$item) {
                    $erp = $this->erp->table('ErpGoods')->alias('a')->where('a.GoodsNo', $item['货号'])
                        ->leftjoin('ErpGoodsColor c', 'c.GoodsId=a.GoodsId')
                        ->field('a.*,c.ColorDesc, c.ColorCode')
                        ->find();

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

                    $erpSize=$this->erp->query($sql);
                    $erpSize=array_column($erpSize,'Size');

                    $where = [
                        ['CustomerName', '=', $item['店铺']],
                        ['RegionId', '<>', 55]
                    ];
                    $erpCustomer = $this->erp->table('ErpCustomer')->where($where)->find();
                    $ErpWarehouse = $this->erp->table('ErpWarehouse')->where('WarehouseName',$item['云仓'])->find();

                    $yc_data=$this->service->get_yunchang_goods_data();
                    $ycRes=[];
                    foreach ($yc_data as $yc_v){
                        $ycRes[$yc_v['WarehouseName'].$yc_v['GoodsNo']]=$yc_v;
                    }

                    $arr[] = [
                        'uuid' => $item['单号'] ?? '',
                        'WarehouseName' => $item['云仓'] ?? '',
                        'GoodsNo' => $item['货号'] ?? '',
                        'CustomerName' => $item['店铺'] ?? '',
//                    '' => $item['合计'],
                        'Stock_00_puhuo' => $item['44/28/'] ?? '',
                        'Stock_29_puhuo' => $item['46/29/165/38/M/105'] ?? '',
                        'Stock_30_puhuo' => $item['48/30/170/39/L/110'] ?? '',
                        'Stock_31_puhuo' => $item['50/31/175/40/XL/115'] ?? '',
                        'Stock_32_puhuo' => $item['52/32/180/41/2XL/120'] ?? '',
                        'Stock_33_puhuo' => $item['54/33/185/42/3XL/125'] ?? '',
                        'Stock_34_puhuo' => $item['34/43/56/190/4XL/'] ?? '',
                        'Stock_35_puhuo' => $item['58/35/195/44/5XL/'] ?? '',
                        'Stock_36_puhuo' => $item['60/36/6XL/'] ?? '',
                        'Stock_38_puhuo' => $item['38/7XL'] ?? '',
                        'Stock_40_puhuo' => $item['40'] ?? '',
                        'Stock_42_puhuo' => $item['42'] ?? '',
                        'Stock_44_puhuo' => $item['44'] ?? '',
                        'Stock_00_size' =>$erpSize[0] ??'',
                        'Stock_29_size' =>$erpSize[1]??'',
                        'Stock_30_size' =>$erpSize[2]??'',
                        'Stock_31_size' =>$erpSize[3]??'',
                        'Stock_32_size' =>$erpSize[4]??'',
                        'Stock_33_size' =>$erpSize[5]??'',
                        'Stock_34_size' =>$erpSize[6]??'',
                        'Stock_35_size' =>$erpSize[7]??'',
                        'Stock_36_size' =>$erpSize[8]??'',
                        'Stock_38_size' =>$erpSize[9]??'',
                        'Stock_40_size' =>$erpSize[10]??'',
                        'Stock_42_size' =>$erpSize[11]??'',
                        'Stock_44_size' =>$erpSize[12]??'',
                        'Stock_00' => $ycRes[$item['云仓'].$item['货号']]['Stock_00'] ?? '',
                        'Stock_29' => $ycRes[$item['云仓'].$item['货号']]['Stock_29'] ?? '',
                        'Stock_30' => $ycRes[$item['云仓'].$item['货号']]['Stock_30'] ?? '',
                        'Stock_31' => $ycRes[$item['云仓'].$item['货号']]['Stock_31'] ?? '',
                        'Stock_32' => $ycRes[$item['云仓'].$item['货号']]['Stock_32'] ?? '',
                        'Stock_33' => $ycRes[$item['云仓'].$item['货号']]['Stock_33'] ?? '',
                        'Stock_34' => $ycRes[$item['云仓'].$item['货号']]['Stock_34'] ?? '',
                        'Stock_35' => $ycRes[$item['云仓'].$item['货号']]['Stock_35'] ?? '',
                        'Stock_36' => $ycRes[$item['云仓'].$item['货号']]['Stock_36'] ?? '',
                        'Stock_38' => $ycRes[$item['云仓'].$item['货号']]['Stock_38'] ?? '',
                        'Stock_40' => $ycRes[$item['云仓'].$item['货号']]['Stock_40'] ?? '',
                        'Stock_42' => $ycRes[$item['云仓'].$item['货号']]['Stock_42'] ?? '',
                        'Stock_44' => $ycRes[$item['云仓'].$item['货号']]['Stock_44'] ?? '',

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
                        'CustomerCode' => $erpCustomer['CustomerCode'],
                        'State' => $erpCustomer['State'],
                        'CustomItem17' => $erpCustomer['CustomItem17'],
                        'Mathod' => $erpCustomer['MathodId'] == 4 ? '直营' : '加盟',
                        'CustomerGrade' => $erpCustomer['CustomerGrade'],
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