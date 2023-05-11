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
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="店铺补货2.0")
 */
class ShopbuhuoB extends AdminController
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
     * 读取excel里面的内容保存为数组
     * @param string $file_path  导入文件的路径
     * @param array $read_column  要返回的字段
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function readExcel1($file_path = '/', $read_column = array())
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
            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = $data_origin[$key];
            }
        }
        return $data;
    }

    /**
     * 读取excel里面的内容保存为数组
     * @param string $file_path  导入文件的路径
     * @param array $read_column  要返回的字段
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function readExcel2($file_path = '/', $read_column = array())
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
                if ($column == "B" || $column == "C") {
                    if (is_numeric($data_origin[$column])) {
                        $t1 = intval(($data_origin[$column]- 25569) * 3600 * 24); //转换成1970年以来的秒数
                        $data_origin[$column] = gmdate('Y/m/d',$t1);
                    } else {
                        $data_origin[$column] = $data_origin[$column];
                    }
                }
            }
            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = $data_origin[$key];
            }
        }
        return $data;
    }

    /**
     * @NodeAnotation(title="补货检验表2.0")
     * 仓库给店铺补货 7天内调空
     * 使用出货指令单的excel
     */
    public function chuhuozhiling() {      
        if (request()->isAjax()) {
            // 筛选条件
            $find_chuhuozhiling = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
                ['aid', '=', $this->authInfo['id']],
                ['清空时间', 'exp', new Raw('IS NOT NULL')]
            ])->order('create_time DESC')
            ->select();
            return json(["code" => "0", "msg" => "", "data" => $find_chuhuozhiling, "count" => count($find_chuhuozhiling), 'create_time' => $this->create_time]);
        } else {
            $find_chuhuozhiling = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->field('create_time')
            ->order('create_time DESC')
            ->find();
            return View('chuhuozhiling',[
                'create_time' => $find_chuhuozhiling ? $find_chuhuozhiling['create_time'] : '无记录'
            ]);
        }
    }  

    /**
     * @NodeAnotation(title="调拨检验表2.0")
     * 店铺给店铺调拨
     * 操作人是调入方
     * 渠道调拨申请单的excel 
     */
    public function qudaodiaobo() {
        if (request()->isAjax()) {
        // if (1) {
        //     echo 111;die;
        //     die;
            // 筛选条件
            $data = $this->qudaodiaobo_group();
            return json(["code" => "0", "msg" => "", "data" => $data, "count" => count($data), 'create_time' => $this->create_time]);
        } else {
            $find_qudaodiaobo = $this->db_easyA->table('cwl_qudaodiaobo_2')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->field('create_time')
            ->order('create_time DESC')
            ->find();
            return View('qudaodiaobo',[
                'create_time' => $find_qudaodiaobo ? $find_qudaodiaobo['create_time'] : '无记录'
            ]);
        }
    }  

    // 康雷在途 给区域调拨用   调出负责人$diaochufuzheren
    public function qudaodiaobo_zaitu($diaochufuzheren = "") {
        $sql = "
        SELECT
            T.CustomItem17 商品专员,
            T.CustomerName 店铺名称,
            T.GoodsNo 货号,
            SUM ( T.intransit_quantity ) 在途数量 
        FROM
            (
            SELECT
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName ,
                EG.GoodsNo,
                SUM ( EDG.Quantity ) AS intransit_quantity 
            FROM
                ErpDelivery ED
                LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
                LEFT JOIN ErpCustomer EC ON ED.CustomerId= EC.CustomerId
                LEFT JOIN ErpGoods EG ON EDG.GoodsId= EG.GoodsId 
            WHERE
                ED.CodingCode= 'EndNode2' 
                AND ED.IsCompleted= 0 --AND ED.IsReceipt IS NULL
                
                AND ED.DeliveryID NOT IN (
                SELECT
                    ERG.DeliveryId 
                FROM
                    ErpCustReceipt ER
                    LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID= ERG.ReceiptID 
                WHERE
                    ER.CodingCodeText= '已审结' 
                    AND ERG.DeliveryId IS NOT NULL 
                    AND ERG.DeliveryId!= '' 
                GROUP BY
                    ERG.DeliveryId 
                ) 
                AND EC.CustomItem17 = '{$diaochufuzheren}' 
            GROUP BY
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName,
                EG.GoodsNo UNION ALL--店店调拨在途
            SELECT
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName AS dept_name,
                EG.GoodsNo,
                SUM ( EIG.Quantity ) AS intransit_quantity 
            FROM
                ErpCustOutbound EI
                LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId= EIG.CustOutboundId
                LEFT JOIN ErpCustomer EC ON EI.InCustomerId= EC.CustomerId
                LEFT JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId 
            WHERE
                EI.CodingCodeText= '已审结' 
                AND EI.IsCompleted= 0 
                AND EI.CustOutboundId NOT IN (
                SELECT
                    ERG.CustOutboundId 
                FROM
                    ErpCustReceipt ER
                    LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID= ERG.ReceiptID 
                WHERE
                    ER.CodingCodeText= '已审结' 
                    AND ERG.CustOutboundId IS NOT NULL 
                    AND ERG.CustOutboundId!= '' 
                GROUP BY
                    ERG.CustOutboundId 
                ) 
                AND EC.CustomItem17 = '{$this->authInfo["name"]}' 
                AND EC.ShutOut= 0 
            GROUP BY
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName,
                EG.GoodsNo 
            ) T 
        GROUP BY
            T.CustomItem17,
            T.CustomerName,
            T.GoodsNo;
        ";
        // 在途 调出店铺是不能有在途的，这样没意义
        $zaitu = $this->db_sqlsrv->query($sql);
        return $zaitu;
    }

    // 康雷库存 给区域调拨用   调出负责人$diaochufuzheren
    public function qudaodiaobo_kucun($diaochufuzheren = "") {
        // 店铺库存
        $sql = "
            SELECT
                EC.CustomItem17,
                EC.CustomerName ,
                EG.GoodsNo ,
                SUM(ECS.Quantity) AS actual_quantity
            FROM ErpCustomerStock ECS 
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE  EC.ShutOut=0
                AND EC.CustomItem17 = '{$diaochufuzheren}' 
                AND EG.TimeCategoryName1=2023
            GROUP BY 
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo 
            HAVING SUM(ECS.Quantity)!=0
        ";

        // 可查是否完成，未完成数量
        $sql2 = "
            SELECT
                EC.CustomItem17,
                EC.CustomerName ,
                EG.GoodsNo ,
                SUM ( ECS.Quantity ) AS actual_quantity,
                EIA.IsCompleted AS '是否完成',
                EIAG.Quantity AS '调入数量' 
            FROM
                ErpCustomerStock ECS
                LEFT JOIN ErpCustomer EC ON ECS.CustomerId= EC.CustomerId
                LEFT JOIN ErpGoods EG ON ECS.GoodsId= EG.GoodsId
                LEFT JOIN ErpInstructionApplyGoods EIAG ON ECS.GoodsId= EIAG.GoodsId
                LEFT JOIN ErpInstructionApply EIA ON EIA.InstructionApplyId= EIAG.InstructionApplyId 
            WHERE
                EC.ShutOut= 0 
                AND EC.CustomItem17 = '{$this->authInfo["name"]}' 
                AND EG.TimeCategoryName1= 2023 
            GROUP BY
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo,
                EIA.IsCompleted,
                EIAG.Quantity 
            HAVING
            SUM ( ECS.Quantity ) !=0
        ";

        $kucun = $this->db_sqlsrv->query($sql2);
        return $kucun;
    }

    // bi 商品上市天数 <=7  调出负责人$diaochufuzheren
    public function qudaodiaobo_elt7day($diaochufuzheren = "") {
        $elt7day = $this->db_bi->query("
            SELECT
                店铺名称,货号,上市天数 
            FROM
                sp_ww_budongxiao_detail 
            WHERE
                `上市天数` <= 7 
                AND `商品负责人` = '{$diaochufuzheren}' 
                AND 大类 <> '配饰'
        ");  
        return $elt7day;
    }

    // 康雷7天 近七天做了调出清空的数据  店铺商品负责人$shangpingfuzheren
    public function day7($shangpingfuzheren = '') {
        $sql = "
            SELECT
                T.CustomItem17 商品专员,
                T.CustomerName 店铺名称,
                T.GoodsNo 货号,
                T.[单据类型],
                T.BillId 调出单号,
                T.Quantity 调出数量,
                T.[库存数量],
                T.[清空时间]
            FROM
            (
            SELECT
                EC.CustomerName,
                EC.CustomItem17,
                ECS.StockId,
                ECS.BillId,
                CASE WHEN ECS.BillType='ErpCustOutbound' THEN '店铺调出单' WHEN ECS.BillType='ErpCustReceipt' THEN '店铺收货单' WHEN ECS.BillType='ErpRetail' THEN '零售核销单' ELSE '其他' END AS 单据类型,
                ECS.GoodsId,
                EG.GoodsNo,
                ECS.Quantity,
                SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime) AS 库存数量,
                ECS.CreateTime,
                CASE WHEN SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime)<=0 AND ECS.BillType= 'ErpCustOutbound' AND ECS.Quantity<=-2 THEN '调出清空' END AS 清空操作,
                CASE WHEN SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime)<=0 AND ECS.BillType= 'ErpCustOutbound' AND ECS.Quantity<=-2
                                                THEN ECS.CreateTime END AS 清空时间
            FROM ErpCustomer EC
            LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId=ECS.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE EC.CustomItem17='{$shangpingfuzheren}'-- EC.CustomerName='大石二店'
            AND EG.TimeCategoryName1=2023
            ) T
            WHERE T.[清空时间] > GETDATE()-7
        ";
        $day7 = $this->db_sqlsrv->query($sql);
        foreach ($day7 as $key => $val) {
            $day7[$key]['aname'] = $this->authInfo['name'];
            $day7[$key]['aid'] = $this->authInfo['id'];
            $day7[$key]['create_time'] = $this->create_time;
        }
        return $day7;
    }



    // qudaodiaobo康雷数据合并 相同货号 统计数量累加
    public function qudaodiaobo_group() {
        // 旧
        // $select_qudaodiaobo = $this->db_easyA->query("
        //     select aa.*,b.CustomerName as 调出店铺名称, c.CustomerName as 调入店铺名称, '' as 备注 from (
        //     SELECT a.*,sum(a.数量) as `调出店铺该货号数据合计` FROM `cwl_qudaodiaobo_2` as a
        //     WHERE a.aid='{$this->authInfo["id"]}'
        //     GROUP BY 调出店铺编号,货号
        //     ) as aa left join customer as b on aa.调出店铺编号=b.CustomerCode 
        //     left join customer as c on aa.调入店铺编号=c.CustomerCode
        // ");

        // echo '<pre>';
        // print_r($select_qudaodiaobo); 
        // die;
        
        // 新
        $select_qudaodiaobo = $this->db_easyA->table('cwl_qudaodiaobo_2')
        ->field("原单编号,单据日期,审结日期,审结日期,调入店铺编号,调入店铺名称,调入店商品负责人,调出店铺编号,调出店铺名称,调出店商品负责人,
        调出价格类型,调入价格类型,货号,sum(数量) as 调出店铺该货号数据合计,create_time,aid,aname")
        ->where([
            ['aid', '=', $this->authInfo["id"]]
        ])->group('调出店铺编号,货号')->select()->toArray();
        // print_r($select_qudaodiaobo); 
        
        if (! empty($select_qudaodiaobo)) {
            //  调出不能有在途！！！
            $zaitu = $this->qudaodiaobo_zaitu($select_qudaodiaobo[0]['调出店商品负责人']);
            // 调空不能有在途！！！
            $kucun = $this->qudaodiaobo_kucun($select_qudaodiaobo[0]['调出店商品负责人']);
            // 单店单品上市天数必须大于7！！！
            $elt7day = $this->qudaodiaobo_elt7day($select_qudaodiaobo[0]['调出店商品负责人']);

            $store_in_map = "";
            // 组合调入店铺的条件
            foreach ($select_qudaodiaobo as $key => $val) {
                $store_in_map .= "'" . $val['调入店铺名称']. "'"  . ','; 
            }
    
            $store_in_map = substr($store_in_map, 0, -1);

            // 查询调入店铺7天内是否有情况过
            $store_in_data = $this->db_sqlsrv->query("
                SELECT
                    T.CustomItem17 商品专员,
                    T.CustomerName 店铺名称,
                    T.GoodsNo 货号,
                    T.[单据类型],
                    T.BillId 调出单号,
                    T.Quantity 调出数量,
                    T.[库存数量],
                    T.[清空时间]
                FROM
                (
                SELECT
                    EC.CustomerName,
                    EC.CustomItem17,
                    ECS.StockId,
                    ECS.BillId,
                    CASE WHEN ECS.BillType='ErpCustOutbound' THEN '店铺调出单' WHEN ECS.BillType='ErpCustReceipt' THEN '店铺收货单' WHEN ECS.BillType='ErpRetail' THEN '零售核销单' ELSE '其他' END AS 单据类型,
                    ECS.GoodsId,
                    EG.GoodsNo,
                    ECS.Quantity,
                    SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime) AS 库存数量,
                    ECS.CreateTime,
                    CASE WHEN SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime)<=0 AND ECS.BillType= 'ErpCustOutbound' AND ECS.Quantity<=-2 THEN '调出清空' END AS 清空操作,
                    CASE WHEN SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime)<=0 AND ECS.BillType= 'ErpCustOutbound' AND ECS.Quantity<=-2
                                                    THEN ECS.CreateTime END AS 清空时间
                    FROM ErpCustomer EC
                    LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId=ECS.CustomerId
                    LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                WHERE EC.CustomerName in ({$store_in_map})
                    AND EG.TimeCategoryName1=2023
                    ) T
                    WHERE T.[清空时间] > GETDATE()-7
            ");

            // 组合调入店铺 清空时间
            foreach ($select_qudaodiaobo as $key => $val) {
                $select_qudaodiaobo[$key]['清空时间'] = '';
                foreach ($store_in_data as $key1 => $val1) {
                    if ($val1['店铺名称'] == $val['调入店铺名称'] && $val1['货号'] == $val['货号']) {
                        $select_qudaodiaobo[$key]['清空时间'] = date('Y-m-d H:i:s', strtotime($val1['清空时间']));
                    }
                }
            }

            $wrongData = [];
            foreach ($select_qudaodiaobo as $key => $val) {
                $end1 = false;
                $end2 = false;
                $end3 = false;

                // 0 调入店铺7天内清空过
                if (!empty($val['清空时间'])) {
                    // $select_qudaodiaobo[$key]['信息反馈'] = "【调入店铺7天内清空过】"; 
                    $select_qudaodiaobo[$key]['信息反馈'] = "调入店7天内清空过"; 
                    $wrongData[] = $select_qudaodiaobo[$key];
                    continue;
                }

                // 1 调出不能有在途
                foreach ($zaitu as $key2 => $val2) {
                    if ($val['调出店铺名称'] == $val2['店铺名称'] && $val['货号'] == $val2['货号']) {
                        $select_qudaodiaobo[$key]['调出店在途量'] = $val2['在途数量']; 
                        // $select_qudaodiaobo[$key]['信息反馈'] = "【调出不能有在途】 在途数量：" . $val2['在途数量']; 
                        $select_qudaodiaobo[$key]['信息反馈'] = "调出店有在途"; 
                        $wrongData[] = $select_qudaodiaobo[$key];
                        $end1 = true;
                        break;
                    }
                }
                if ($end1 == true) continue;

                // 3 单店单品上市天数<=7
                foreach ($elt7day as $key4 => $val4) {
                    if ($val['调出店铺名称'] == $val4['店铺名称'] && $val['货号'] == $val4['货号']) {
                        $select_qudaodiaobo[$key]['信息反馈']  = "调出店上市不足7天"; 
                        // $select_qudaodiaobo[$key]['信息反馈'] = "上市天数不足8天"; 
                        $wrongData[] = $select_qudaodiaobo[$key];
                        $end3 = true;
                        break;
                    }
                }
                if ($end3 == true) continue;

                // 2 调空 7天内别调入
                foreach ($kucun as $key3 => $val3) {
                    if ($val['调出店铺名称'] == $val3['CustomerName'] && $val['货号'] == $val3['GoodsNo']) {
                        // 未完成
                        if (empty($val3['是否完成'])) {
                            // 调空并且有在途
                            if ($val3['actual_quantity'] - $val3['调入数量'] - $val['调出店铺该货号数据合计'] <= 0) {   
                                // 是否有在途
                                foreach ($zaitu as $key3_1 => $val3_1) {
                                    if ($val['调出店铺名称'] == $val3_1['店铺名称'] && $val['货号'] == $val3_1['货号']) {
                                        $select_qudaodiaobo[$key]['在途数量'] =  $val3_1['在途数量']; 
                                        $select_qudaodiaobo[$key]['店铺库存'] = $val3['actual_quantity'];
                                        $select_qudaodiaobo[$key]['未完成调拨量'] = $val3['调入数量'];
                                        $select_qudaodiaobo[$key]['信息反馈'] = "调出店有在途"; 
                                        // $select_qudaodiaobo[$key]['信息反馈'] = "调出店有在途"; 
                                        $wrongData[] = $select_qudaodiaobo[$key];
                                        $end1 = true;
                                        break 2;
                                    }
                                }
                            }
                        } elseif ($val3['actual_quantity'] - $val['调出店铺该货号数据合计'] <= 0) {
                            // 是否有在途
                            foreach ($zaitu as $key3_1 => $val3_2) {
                                if ($val['调出店铺名称'] == $val3_2['店铺名称'] && $val['货号'] == $val3_2['货号']) {
                                    $select_qudaodiaobo[$key]['在途数量'] =  $val3_2['在途数量']; 
                                    $select_qudaodiaobo[$key]['店铺库存'] = $val3['actual_quantity'];
                                    $select_qudaodiaobo[$key]['未完成调拨量'] = 0;
                                    // $select_qudaodiaobo[$key]['信息反馈'] = "【调空在途1】调入已完成 店铺库存：{$val3['actual_quantity']} 调出总数：{$val['调出店铺该货号数据合计']}";
                                    $select_qudaodiaobo[$key]['信息反馈'] = "调出店有在途"; 
                                    // $select_qudaodiaobo[$key]['信息反馈'] = "调出店有在途"; 
                                    $wrongData[] = $select_qudaodiaobo[$key];
                                    $end1 = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if ($end2 == true) continue; 
            }
            return $wrongData;
        } else {
            return [];
        }
    }

    // 上传excel 调拨
    public function uploadExcel_diaobo() {
        if (request()->isAjax()) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $new_name = "调拨申请_". $this->authInfo['name'] . '_' . rand(100, 999) . time() . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '原单编号',
                    'B' => '单据日期',
                    'C' => '审结日期',
                    'D' => '调出店铺编号',
                    'E' => '调入店铺编号',
                    'F' => '调出价格类型',
                    'G' => '调入价格类型',
                    'H' => '货号',
                    'I' => '颜色编号',
                    'J' => '规格',
                    'K' => '尺码',
                    'L' => '数量',
                    'M' => '规格',
                    'N' => '备注',
                ];

                // 店铺信息
                $select_customer = $this->db_easyA->table('customer')->field('CustomerName,CustomerCode,CustomItem17')->select()->toArray();
                // dump($select_customer);
                //读取数据
                $data = $this->readExcel2($info, $read_column);

                foreach ($data as $key => $val) {
                    if ( empty($val['调出店铺编号']) || empty($val['货号']) ) {
                        unset($data[$key]);
                    } else {
                        $data[$key]['aname'] = $this->authInfo['name'];
                        $data[$key]['aid'] = $this->authInfo['id'];
                        $data[$key]['create_time'] = $this->create_time;
                    }

                    foreach ($select_customer as $key2 => $val2) {
                        if ($val['调出店铺编号'] == $val2['CustomerCode']) {
                            $data[$key]['调出店铺名称'] = $val2['CustomerName'];
                            $data[$key]['调出店商品负责人'] = $val2['CustomItem17'];
                            break;
                        }
                    }

                    foreach ($select_customer as $key3 => $val3) {
                        if ($val['调入店铺编号'] == $val3['CustomerCode']) {
                            $data[$key]['调入店铺名称'] = $val3['CustomerName'];
                            $data[$key]['调入店商品负责人'] = $val3['CustomItem17'];
                            break;
                        }
                    }
                }
        
                // $this->db_easyA->startTrans();
                $this->db_easyA->table('cwl_qudaodiaobo_2')->where([
                    ['aid', '=', $this->authInfo['id']]
                 ])->delete();
                // $insertAll_qudaodiaobo = $this->db_easyA->table('cwl_qudaodiaobo_2')->insertAll($data);
                $chunk_list = array_chunk($data, 1000);
                foreach($chunk_list as $key => $val) {
                    $this->db_easyA->table('cwl_qudaodiaobo_2')->insertAll($val);
                }

                return json(['code' => 0, 'msg' => '上传成功']);
            } 
        }
    }

    public function redExcel_test() {
        $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/9851683357737.xlsx';   //文件保存路径
        $read_column = [
            'A' => '原单编号',
            'B' => '手工单号',
            'C' => '单据日期',
            'D' => '审结日期',
            'E' => '仓库编号',
            'F' => '店铺编号',
            'G' => '订货类型',
            'H' => '订单编号',
            'I' => '货号',
            'J' => '颜色编号',
            'K' => '规格',
            'L' => '尺码',
            'M' => '数量',
            'N' => '订货价格',
            'O' => '是否完成',
            'P' => '备注',
        ];

        if (! cache('test_date')) {
            $data = $this->readExcel1($save_path, $read_column);
            cache('test_date', $data, 3600);
        } else {
            $data = cache('test_date'); 
        }

        // $data = $this->readExcel1($save_path, $read_column);
        foreach ($data as $key => $val) {
            if ( empty($val['店铺编号']) || empty($val['货号']) ) {
                unset($data[$key]);
            } else {
                $data[$key]['aname'] = $this->authInfo['name'];
                $data[$key]['aid'] = $this->authInfo['id'];
                $data[$key]['create_time'] = $this->create_time;
            }
        }

        

        // 7天清空库存数据 
        $day7 = $this->day7();
        $del1 = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete();
        $del2 = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_2')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete(); 


        // 批量插入切割 
        $chunk_list = array_chunk($data, 1000);
        foreach($chunk_list as $key => $val) {
            $this->db_easyA->table('cwl_chuhuozhilingdan_2')->insertAll($val);
        }
        
    }
    
    // 上传excel 店铺补货
    public function uploadExcel_buhuo() {
        if (request()->isAjax()) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $new_name = "补货申请_". $this->authInfo['name'] . '_' . rand(100, 999) . time() . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);
            // dump($info);die;
            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '原单编号',
                    'B' => '手工单号',
                    'C' => '单据日期',
                    'D' => '审结日期',
                    'E' => '仓库编号',
                    'F' => '店铺编号',
                    'G' => '订货类型',
                    'H' => '订单编号',
                    'I' => '货号',
                    'J' => '颜色编号',
                    'K' => '规格',
                    'L' => '尺码',
                    'M' => '数量',
                    'N' => '订货价格',
                    'O' => '是否完成',
                    'P' => '备注',
                ];
                //读取数据
                $data = $this->readExcel1($info, $read_column);
                // echo '<pre>';
                // print_r($data);die;

                // 店铺信息
                $select_customer = $this->db_easyA->table('customer')->field('CustomerName,CustomerCode,CustomItem17')->select()->toArray();
                // $data = array_filter($data);
                foreach ($data as $key => $val) {
                    if ( empty($val['店铺编号']) || empty($val['货号']) ) {
                        unset($data[$key]);
                    } else {
                        $data[$key]['aname'] = $this->authInfo['name'];
                        $data[$key]['aid'] = $this->authInfo['id'];
                        $data[$key]['create_time'] = $this->create_time;
                    }
                    // a.店铺编号 = b.CustomerCode 
                    foreach ($select_customer as $key2 => $val2) {
                        if ($val['店铺编号'] == $val2['CustomerCode']) {
                            $data[$key]['店铺名称'] = $val2['CustomerName'];
                            $data[$key]['商品负责人'] = $val2['CustomItem17'];
                            break;
                        }
                    }
                }

        
                // // 7天清空库存数据 
                // $day7 = $this->day7();


                // $this->db_easyA->startTrans();
                $del1 = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
                    ['aid', '=', $this->authInfo['id']]
                ])->delete();
                $del2 = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_2')->where([
                    ['aid', '=', $this->authInfo['id']]
                ])->delete();    

                // $insertAll_chuhuozhilingdan = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->insertAll($data);

                // 批量插入切割
                $chunk_list = array_chunk($data, 1000);
                foreach($chunk_list as $key => $val) {
                    $this->db_easyA->table('cwl_chuhuozhilingdan_2')->insertAll($val);
                }



                // $update_chuhuozhilingdan = $this->db_easyA->execute("
                //     UPDATE cwl_chuhuozhilingdan_2 AS a
                //     LEFT JOIN customer AS b ON a.店铺编号 = b.CustomerCode 
                //     SET 店铺名称 = b.CustomerName,
                //     商品负责人 = b.CustomItem17
                //     WHERE
                //         a.aid = '{$this->authInfo["id"]}'
                // ");

                // 7天清空库存数据 
                $find_fuzheren = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->field('商品负责人')->where([
                    ['aid', '=', $this->authInfo['id']]
                ])->find();
                // echo $find_fuzheren['商品负责人']; die;
                $day7 = $this->day7($find_fuzheren['商品负责人']);

                $insertAll_7dayclean = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_2')->insertAll($day7);
                $update_chuhuozhilingdan_join_7dayclean = $this->db_easyA->execute("
                    UPDATE cwl_chuhuozhilingdan_7dayclean_2 AS a
                    LEFT JOIN cwl_chuhuozhilingdan_2 AS b ON a.店铺名称 = b.店铺名称 
                    AND a.货号 = b.货号 
                    AND b.aid = '{$this->authInfo["id"]}'
                    SET b.清空时间 = a.清空时间,
                        b.调出数量 = a.调出数量,
                        b.库存数量 = a.库存数量 
                    WHERE
                        a.aid = '{$this->authInfo["id"]}'
                ");
                return json(['code' => 0, 'msg' => '上传成功']);
            } 
        }
    }

}
