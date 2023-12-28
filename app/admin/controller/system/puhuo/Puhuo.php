<?php
namespace app\admin\controller\system\puhuo;

use app\common\service\ExcelService;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\service\PuhuoService;
use app\admin\model\bi\SpLypPuhuoWaitGoodsModel;
use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\CustomerModel;
use jianyan\excel\Excel;
use think\facade\Db;
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
    protected $mysql;

    public function __construct(Request $request)
    {
        $this->service = new PuhuoService();
        $this->request = $request;
        $this->mysql = Db::connect("mysql");

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

                    $order_no = 'SX'.date('Ymd').sprintf("%03d", ++$k_res);//uuid('SX');//date('Ymd').(++$k_res);

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
     * @return void
     * @NodeAnotation(title="草稿导单功能",auth=false)
     */
    public function excel_caogao()
    {

        $param=$this->request->param();

        $where=[
            ['uuid','in',$param['caogao_uuid']]
        ];

        $res = $this->service->caogao_order_no($where);

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
            $Color = $this->service->Color($v_res['GoodsNo']);
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
                'ColorCode' => $Color['ColorCode'],
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
        return $this->export_excel_caogao_runing($tag);


    }


    /**
     * @param $tag
     * @return string|void
     * @NodeAnotation(title="运行",auth=false)
     */
    public function export_excel_caogao_runing($tag)
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

    /**
     * @NodeAnotation(title="excel转换")
     */
    public function import_excel() {

        if (request()->isAjax()) {
            $file = request()->file('file');

            $new_name = "import_excel" . '_' . uuid('zhuanhuan') . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            if ($info) {

                return json(['code' => 0, 'msg'=>'导入成功', 'data'=>$save_path.$new_name]);

            } else {

                return json(['code' => 500, 'msg'=>'error,请联系系统管理员']);

            }

        } else {

            $params = $this->request->param();
            $excel_url = $params['excel_url'] ?? '';
            if (!$excel_url) {
                return json(['code' => 400, 'msg'=>'error,excel文件不存在']);
            }

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

            $res = importExcel($excel_url, $read_column);
            // print_r($res);die;

            if ($res) {

                $excel_output_data = [];
                foreach ($res as $k_res=>$v_res) {

                    // $order_no = uuid('SX');//date('Ymd').(++$k_res);

                    //查询云仓-货号 对应数据
                    $wait_goods_info = SpLypPuhuoYuncangkeyongModel::where([['WarehouseName', '=', $v_res['云仓']], ['GoodsNo', '=', $v_res['货号']]])->find();
                    $wait_goods_info = $wait_goods_info ? $wait_goods_info->toArray() : [];

                    $customer_code = CustomerModel::where([['CustomerName', '=', $v_res['店铺']]])->field('CustomerCode')->find();
                    $customer_code = $customer_code ? $customer_code->toArray() : [];
                    // print_r([$wait_goods_info, $customer_code]);die;

                    $tmp_arr = [
                        'order_no' => $v_res['单号'],
                        'WarehouseCode' => $this->return_ware_code($v_res['云仓']),
                        'CustomerCode' => $customer_code ? $customer_code['CustomerCode'] : '',
                        'send_out' => 'Y',
                        're_confirm' => 'Y',
                        'GoodsNo' => $v_res['货号'],
                        'Size' => '',//
                        'ColorCode' => $wait_goods_info ? $wait_goods_info['ColorCode'] : '',
                        'puhuo_num' => 0,//
                        'status' => 2,
                        'remark' => $wait_goods_info ? mb_substr($wait_goods_info['CategoryName1'], 0, 1) : '',
                    ];

                    $size_arr = [
                        ['puhuo_num'=>$v_res['44/28/'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_00_size'] : ''],
                        ['puhuo_num'=>$v_res['46/29/165/38/M/105'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_29_size'] : ''],
                        ['puhuo_num'=>$v_res['48/30/170/39/L/110'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_30_size'] : ''],
                        ['puhuo_num'=>$v_res['50/31/175/40/XL/115'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_31_size'] : ''],
                        ['puhuo_num'=>$v_res['52/32/180/41/2XL/120'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_32_size'] : ''],
                        ['puhuo_num'=>$v_res['54/33/185/42/3XL/125'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_33_size'] : ''],
                        ['puhuo_num'=>$v_res['56/34/190/43/4XL/130'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_34_size'] : ''],
                        ['puhuo_num'=>$v_res['58/35/195/44/5XL/'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_35_size'] : ''],
                        ['puhuo_num'=>$v_res['60/36/6XL/'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_36_size'] : ''],
                        ['puhuo_num'=>$v_res['38/7XL'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_38_size'] : ''],
                        ['puhuo_num'=>$v_res['40'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_40_size'] : ''],
                        ['puhuo_num'=>$v_res['42'], 'Size'=>$wait_goods_info ? $wait_goods_info['Stock_42_size'] : ''],
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

        }

    }

    public function return_ware_code($yuncang) {

        $arr = [
            '长沙云仓' => 'CK006',
            '武汉云仓' => 'CK003',
            '南昌云仓' => 'CK002',
            '广州云仓' => 'CK004',
            '贵阳云仓' => 'CK005',
        ];
        return $arr[$yuncang] ?? '';

    }


    /**
     * @return null
     * @NodeAnotation(title="",auth=false)
     */
    public function delete()
    {

        $param = $this->request->param();

        $db = Db::connect('mysql');

        if ($param['all'] == 1) {
            $res = $db->table('sp_lyp_puhuo_caogao')->where([['admin_id', '=', session('admin.id')]])->delete();
        } else {
            $res = $db->table('sp_lyp_puhuo_caogao')->where([['admin_id', '=', session('admin.id')], ['uid', 'IN', explode(',', $param['uuid'])]])->delete();
        }

        return $this->success('ok', $param);

    }

    /**
     * @return void
     * @NodeAnotation(title="保存修订")
     */
    public function save_revise(){

        $this->service->revise();

        $this->success('ok');
    }


}
