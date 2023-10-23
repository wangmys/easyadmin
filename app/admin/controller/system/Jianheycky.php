<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatistics;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class Jianheycky
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="检核云仓可用")
 */
class Jianheycky extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_sqlsrv = '';
    protected $db_bi = '';
    // 用户信息
    protected $authInfo = '';
    
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_bi = Db::connect('mysql2');

        $this->authInfo = session('admin');
        // $this->rand_code = $this->rand_code(10);
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    /**
     * @NodeAnotation(title="检核云仓可用") 
     */
    public function ycky() {
        if (request()->isAjax()) {
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');
            // $pageParams1 = (2 - 1) * 100;
            // $pageParams2 = 100;
            $sql1 = "
                select 
                    * 
                from cwl_jianhe_ycky 
                where 
                    aid='{$this->authInfo['id']}' 
                    and 提醒 is not null
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql1);

            $sql2 = "
                select count(*) as total from cwl_jianhe_ycky where aid='{$this->authInfo['id']}' and 提醒 is not null
                     
            ";
            $total = $this->db_easyA->query($sql2);
            
            return json(["code" => "0", "msg" => "", "data" => $select, "count" => $total[0]['total']]);
        } else {
            // $find_qudaodiaobo = $this->db_easyA->table('cwl_qudaodiaobo_2')->where([
            //     ['aid', '=', $this->authInfo['id']]
            // ])->field('create_time')
            // ->order('create_time DESC')
            // ->find();
            return View('ycky',[
                // 'create_time' => $find_qudaodiaobo ? $find_qudaodiaobo['create_time'] : '无记录'
            ]);
        }
    }

    /** 补货
     * 读取excel里面的内容保存为数组
     * @param string $file_path  导入文件的路径
     * @param array $read_column  要返回的字段
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function readExcel($file_path = '/', $read_column = array())
    {
        $reader = IOFactory::createReader('Xlsx');
    
        $reader->setReadDataOnly(TRUE);
    
        //载入excel表格
        $spreadsheet = $reader->load($file_path);
    
        // 读取第一個工作表
        $sheet = $spreadsheet->getSheet(0);
    
        // 取得总行数
        $highest_row = $sheet->getHighestRow();
    
        // 取得总列数
        $highest_column = $sheet->getHighestColumn();
    
        //读取内容
        $data_origin = array();
        $data = array();
        for ($row = 2; $row <= $highest_row; $row++) { //行号从2开始
            for ($column = 'A'; $column <= $highest_column; $column++) { //列数是以A列开始
                $str = $sheet->getCell($column . $row)->getValue();
                //保存该行的所有列
                $data_origin[$column] = $str;
                if ($column == "C" || $column == "D") {
                    if (is_numeric($data_origin[$column])) {
                        $t1 = intval(($data_origin[$column]- 25569) * 3600 * 24); //转换成1970年以来的秒数
                        $data_origin[$column] = gmdate('Y/m/d',$t1);
                    } else {
                        $data_origin[$column] = $data_origin[$column];
                    }
                }
            }

            // 删除空行，好用的很
            if(!implode('', $data_origin)){
                //删除空行
                continue;
            }

            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = @$data_origin[$key] ? $data_origin[$key] : '';
            }
        }
        return $data;
    }


    // 上传excel 店铺补货
    public function uploadExcel_ycky() {
        if (request()->isAjax()) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $new_name = "商品通云仓可用_". $this->authInfo['name'] . '_' . rand(100, 999) . time() . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);
            // dump($info);die;
            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                //读取数据

                $read_column = [
                    'B' => '仓库编号',
                    'C' => '店铺编号',
                    'F' => '货号',
                    'G' => '尺码',
                    'I' => '铺货数',
                ];
        
        
                $data = $this->readExcel($info, $read_column);
                $date = date('Y-m-d');
                foreach ($data as $key => $val) {
                    $data[$key]['aid'] = $this->authInfo['id'];
                    $data[$key]['aname'] = $this->authInfo['name'];
                    $data[$key]['更新日期'] = $date;
                }
        
                // echo '<pre>';
                // print_r($data);die;
        
                $chunk_list = array_chunk($data, 500);
                $this->db_easyA->table('cwl_jianhe_ycky')->where([
                    // ['更新日期' , '=', $date],
                    ['aid' , '=', $this->authInfo['id']],
                ])->delete();
                foreach($chunk_list as $key => $val) {
                    // 基础结果 
                    $this->db_easyA->table('cwl_jianhe_ycky')->strict(false)->insertAll($val);
                }
        
                $sql = "
                    SELECT
                        aid,aname,
                        仓库编号,
                        货号,
                        尺码,
                        sum(铺货数) as 铺货数,
                        更新日期 
                    FROM
                        `cwl_jianhe_ycky`
                    group BY
                        仓库编号,货号,尺码
                ";
                $select = $this->db_easyA->query($sql);
                if ($select) {
                    $chunk_list2 = array_chunk($select, 500);
                    $this->db_easyA->table('cwl_jianhe_ycky')->where([
                        // ['更新日期' , '=', $date],
                        ['aid' , '=', $this->authInfo['id']],
                    ])->delete();
                    foreach($chunk_list2 as $key2 => $val2) {
                        // 基础结果 
                        $this->db_easyA->table('cwl_jianhe_ycky')->strict(false)->insertAll($val2);
                    }
                }

                return json(['code' => 0, 'msg' => '上传成功']);

            }
        }
    }

    // 计算
    public function handle() {
        if (request()->isAjax()) {
            $date = date('Y-m-d');
            $select = $this->db_easyA->table('cwl_jianhe_ycky')->where([
                ['aid' , '=', $this->authInfo['id']],
            ])
            // ->limit(10)
            ->select()->toArray();
            foreach ($select as $key => $val) {
                // dump($val);
                $res = $this->getYcky($val['仓库编号'], $val['货号'], $val['尺码']);
                if ($res) {
                    $select[$key]['云仓可用'] = $res['Quantity'];  
                    if ($select[$key]['铺货数'] > $res['Quantity']) {   
                        $select[$key]['提醒'] = '云仓可用不足';   
                    }
                } else {
                    $select[$key]['云仓可用'] = 'UNKNOW';  
                    $select[$key]['提醒'] = 'UNKNOW'; 
                }
            }
            
            $chunk_list = array_chunk($select, 500);
            $this->db_easyA->table('cwl_jianhe_ycky')->where([
                // ['更新日期' , '=', $date],
                ['aid' , '=', $this->authInfo['id']],
            ])->delete();
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_jianhe_ycky')->strict(false)->insertAll($val);
            }

            return json(['status' => 1, 'msg' => '执行成功']);
        }
    }


    // 云仓可用 本地测试
    public function redExcel_test() {
        // $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/补货申请_黎亿炎_ccccccccccccc.xlsx';   //文件保存路径
        $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/商品通9.7.1.xlsx';   //文件保存路径
        $read_column = [
            'B' => '仓库编号',
            'C' => '店铺编号',
            'F' => '货号',
            'G' => '尺码',
            'I' => '铺货数',
        ];


        $data = $this->readExcel($save_path, $read_column);
        $date = date('Y-m-d');
        foreach ($data as $key => $val) {
            $data[$key]['aid'] = $this->authInfo['id'];
            $data[$key]['aname'] = $this->authInfo['name'];
            $data[$key]['更新日期'] = $date;
        }

        // echo '<pre>';
        // print_r($data);die;

        $chunk_list = array_chunk($data, 500);
        $this->db_easyA->table('cwl_jianhe_ycky')->where([
            ['更新日期' , '=', $date],
            ['aid' , '=', $this->authInfo['id']],
        ])->delete();
        foreach($chunk_list as $key => $val) {
            // 基础结果 
            $this->db_easyA->table('cwl_jianhe_ycky')->strict(false)->insertAll($val);
        }

        $sql = "
            SELECT
                aid,aname,
                仓库编号,
                货号,
                尺码,
                sum(铺货数) as 铺货数,
                更新日期 
            FROM
                `cwl_jianhe_ycky`
            group BY
                仓库编号,货号,尺码
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $chunk_list2 = array_chunk($select, 500);
            $this->db_easyA->table('cwl_jianhe_ycky')->where([
                ['更新日期' , '=', $date],
                ['aid' , '=', $this->authInfo['id']],
            ])->delete();
            foreach($chunk_list2 as $key2 => $val2) {
                // 基础结果 
                $this->db_easyA->table('cwl_jianhe_ycky')->strict(false)->insertAll($val2);
            }
        }
    } 

    protected function getYcky($WarehouseCode, $GoodsNo, $Size) {
        $sql = "
            SELECT 
                    CAST(GETDATE() AS DATE) as Date,
                    T.WarehouseName,
                    T.WarehouseCode,
                    EG.GoodsNo,
                    EBGS.[Size],
                    SUM(T.Quantity) AS Quantity
            FROM 
            (
            SELECT 
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EWS.GoodsId,
                    EWSD.SizeId,
                    SUM(EWSD.Quantity) AS Quantity
            FROM ErpWarehouseStock EWS
            LEFT JOIN ErpWarehouseStockDetail EWSD ON EWS.StockId=EWSD.StockId
            LEFT JOIN ErpWarehouse EW ON EWS.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EWS.GoodsId=EG.GoodsId
            WHERE EG.TimeCategoryName1>2022
                    AND EG.CategoryName1 NOT IN ('物料','人事物料')
                    AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY  
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EW.WarehouseCode,
                    EWS.GoodsId,
                    EWSD.SizeId

            UNION ALL 
            --出货指令单占用库存
            SELECT
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    ESG.GoodsId,
                    ESGD.SizeId,
                    -SUM ( ESGD.Quantity ) AS SumQuantity
            FROM ErpSorting ES
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID= ESG.SortingID
            LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID=ESGD.SortingGoodsID
            LEFT JOIN ErpWarehouse EW ON ES.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            WHERE ES.IsCompleted= 0
                    AND EG.TimeCategoryName1>2022
                    AND EG.CategoryName1 NOT IN ('物料','人事物料')
                    AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    ESG.GoodsId,
                    ESGD.SizeId 
            UNION ALL
                    --仓库出货单占用库存
            SELECT
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EDG.GoodsId,
                    EDGD.SizeId,
                    -SUM ( EDGD.Quantity ) AS SumQuantity
            FROM ErpDelivery ED
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
            LEFT JOIN ErpWarehouse EW ON ED.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
            WHERE ED.CodingCode= 'StartNode1' 
                    AND EDG.SortingID IS NULL
                    AND EG.TimeCategoryName1>2022
                    AND EG.CategoryName1 NOT IN ('物料','人事物料')
                    AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EDG.GoodsId,
                    EDGD.SizeId
            UNION ALL
                    --采购退货指令单占用库存
            SELECT
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EPRNG.GoodsId,
                    EPRNGD.SizeId,
                    -SUM ( EPRNGD.Quantity ) AS SumQuantity
            FROM ErpPuReturnNotice EPRN
            LEFT JOIN ErpPuReturnNoticeGoods EPRNG ON EPRN.PuReturnNoticeId= EPRNG.PuReturnNoticeId
            LEFT JOIN ErpPuReturnNoticeGoodsDetail EPRNGD ON EPRNG.PuReturnNoticeGoodsId=EPRNGD.PuReturnNoticeGoodsId
            LEFT JOIN ErpWarehouse EW ON EPRN.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EPRNG.GoodsId=EG.GoodsId
            WHERE (EPRN.IsCompleted = 0 OR EPRN.IsCompleted IS NULL)
                    AND EG.TimeCategoryName1>2022
                    AND EG.CategoryName1 NOT IN ('物料','人事物料')
                    AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EPRNG.GoodsId,
                    EPRNGD.SizeId

            UNION ALL
                    --采购退货单占用库存
            SELECT
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EPCRG.GoodsId,
                    EPCRGD.SizeId,
                    -SUM ( EPCRGD.Quantity ) AS SumQuantity
            FROM ErpPurchaseReturn EPCR
            LEFT JOIN ErpPurchaseReturnGoods EPCRG ON EPCR.PurchaseReturnId= EPCRG.PurchaseReturnId
            LEFT JOIN ErpPurchaseReturnGoodsDetail EPCRGD ON EPCRG.PurchaseReturnGoodsId=EPCRGD.PurchaseReturnGoodsId
            LEFT JOIN ErpWarehouse EW ON EPCR.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EPCRG.GoodsId=EG.GoodsId
            WHERE EPCR.CodingCode= 'StartNode1'
                    AND EG.TimeCategoryName1>2022
                    AND EG.CategoryName1 NOT IN ('物料','人事物料')
                    AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EPCRG.GoodsId,
                    EPCRGD.SizeId
            UNION ALL
                    --仓库调拨占用库存
            SELECT
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EIG.GoodsId,
                    EIGD.SizeId,
                    -SUM ( EIGD.Quantity ) AS SumQuantity
            FROM ErpInstruction EI
            LEFT JOIN ErpInstructionGoods EIG ON EI.InstructionId= EIG.InstructionId
            LEFT JOIN ErpInstructionGoodsDetail EIGD ON EIG.InstructionGoodsId=EIGD.InstructionGoodsId
            LEFT JOIN ErpWarehouse EW ON EI.OutItemId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            WHERE EI.Type= 1
                    AND (EI.CodingCode= 'StartNode1' OR (EI.CodingCode= 'EndNode2' AND EI.IsCompleted=0 ))
                    AND EG.TimeCategoryName1>2022
                    AND EG.CategoryName1 NOT IN ('物料','人事物料')
                    AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                    EW.WarehouseName,
                    EW.WarehouseCode,
                    EIG.GoodsId,
                    EIGD.SizeId 
            ) T
            LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId
            LEFT JOIN ErpGoods EG ON T.GoodsId=EG.GoodsId

            WHERE 
                EG.GoodsNo = '{$GoodsNo}'
            -- 	AND WarehouseName = '长沙云仓'
                AND WarehouseCode = '{$WarehouseCode}'
                AND EBGS.[Size] = '{$Size}'
            GROUP BY 
                    T.WarehouseName,
                    T.WarehouseCode,
                    EG.GoodsNo,
                    EG.TimeCategoryName1,
                    EG.TimeCategoryName2,
                    EG.CategoryName1,
                    EG.CategoryName2,
                    EG.CategoryName,
                    EG.GoodsName,
                    EG.StyleCategoryName,
                    EG.GoodsNo,
                    EG.StyleCategoryName1,
                    EG.StyleCategoryName2,
                    EBGS.[Size]
        ";
        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            return $select[0];
        } else {
            return false;
        }
        
    }
}
