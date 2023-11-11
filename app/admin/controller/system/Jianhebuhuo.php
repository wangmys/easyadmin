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
 * Class Jianhebuhuo
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="检核补货3.0")
 */
class Jianhebuhuo extends AdminController
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

    /** 补货
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

    /**
     * @NodeAnotation(title="补货历史记录")
     */
    public function history() {
        if (request()->isAjax()) {

            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (!empty($input['更新时间_开始'])) {
                $map1 = " AND 更新时间 >= '{$input['更新时间_开始']} 00:00:00'";
            } else {
                $map1 = "";
            }

            if (!empty($input['更新时间_结束'])) {
                $map2 = " AND 更新时间 <= '{$input['更新时间_结束']} 23:59:59'";
            } else {
                $map2 = "";
            }

            $sql = "
                SELECT 
                    *    
                FROM cwl_chuhuozhilingdan_history
                WHERE 1
                    AND aid = '{$this->authInfo['id']}'
                    {$map1}
                    {$map2}
                ORDER BY 
                    更新时间 DESC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_chuhuozhilingdan_history
                WHERE  1
                    AND aid = '{$this->authInfo['id']}'
                    {$map1}
                    {$map2}
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            $更新时间_开始 = date("Y-m-d");
            $更新时间_结束 = date("Y-m-d");
            return View('history',[
                'date_start' => $更新时间_开始,
                'date_end' => $更新时间_结束,
            ]);
        }
    }  

    /**
     * @NodeAnotation(title="补货检验表2.0")
     * 仓库给店铺补货 7天内调空
     * 使用出货指令单的excel
     */
    public function chuhuozhiling() {      
        if (request()->isAjax()) {
        // if (1) {    
            // // 筛选条件  7天调空
            // $select_chuhuozhiling_clean = $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
            //     ['aid', '=', $this->authInfo['id']],
            //     ['清空时间', 'exp', new Raw('IS NOT NULL')]
            // ])->order('create_time DESC')
            // ->select();
            
            // 7天调空 或者 有未完成调拨且库存-调拨未完成<=0
            $select_chuhuozhiling_clean = $this->db_easyA->query("
                SELECT * FROM cwl_chuhuozhilingdan_3
                WHERE 
                    aid = '{$this->authInfo['id']}'
                    AND (清空时间 IS NOT NULL OR 调拨未完成数 IS NOT NULL)
                ORDER BY create_time DESC    
            ");    

            // if ($select_chuhuozhiling_clean) {
            //     $time = date('Y-m-d H:i:s');
            //     // $select = array_chunk($data, 500);
            //     foreach($select_chuhuozhiling_clean as $key => $val) {
            //         // 一条一条插入，因为字段数量不一样
            //         $val['更新时间'] = $time;
            //         $insert = $this->db_easyA->table('cwl_chuhuozhilingdan_history')->strict(false)->insert($val);
            //     }
            //     // print_r($data);
            // }

            return json(["code" => "0", "msg" => "", "data" => $select_chuhuozhiling_clean, "count" => count($select_chuhuozhiling_clean), 'create_time' => $this->create_time]);
        } else {
            $select_chuhuozhiling_clean = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->field('create_time')
            ->order('create_time DESC')
            ->find();
            return View('chuhuozhiling',[
                'create_time' => $select_chuhuozhiling_clean ? $select_chuhuozhiling_clean['create_time'] : '无记录'
            ]);
        }
    }  


    // 康雷在途 给区域调拨用   调出负责人$diaochufuzheren
    public function qudaodiaobo_zaitu_new($diaochufuzheren = "", $CustomerName = '', $GoodsNo = '') {
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
                AND EC.CustomerName = '{$CustomerName}' AND EG.GoodsNo = '{$GoodsNo}'
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
                AND EC.CustomItem17 = '{$diaochufuzheren}' 
                                AND EC.CustomerName = '{$CustomerName}'
                                AND EG.GoodsNo = '{$GoodsNo}'
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

    // 已备未发
    public function qudaodiaobo_weifa($diaochufuzheren = "", $CustomerName = '', $GoodsNo = '') {
        $sql = "
            SELECT 
                EC.State AS 省份,
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerCode,
                EC.CustomerName as 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EC.MathodId,
                EG.GoodsNo as 货号,
                SUM(ESG.Quantity) 已配未发
            FROM ErpCustomer EC
            LEFT JOIN ErpSorting ES ON EC.CustomerId=ES.CustomerId
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            WHERE	
                -- EG.CategoryName1 IN ('内搭','外套','下装','鞋履','配饰')
                EC.ShutOut=0  
                AND EC.MathodId IN (4,7)
                AND ES.IsCompleted=0
                AND EG.GoodsNo='{$GoodsNo}'
                AND EC.CustomerName='{$CustomerName}'
                -- AND EC.CustomerCode='Y0535'
            GROUP BY 
                EC.State,
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerCode,
                EC.CustomerName,
                EG.CategoryName1,
                EC.MathodId,
            EG.GoodsNo
        ";
        // 在途 调出店铺是不能有在途的，这样没意义
        $zaitu = $this->db_sqlsrv->query($sql);
        return $zaitu;
    }

    // 分拣未完成  a(a存在给b分拣未完成记录)->b(当前操作店铺b给发调拨)->c
    public function qudaodiaobo_fenjian_weiwancheng($CustomerCode = '', $GoodsNo = '') {
        $sql = "
            SELECT TOP 100
                EIA.InstructionApplyId AS 指定单号,
                EC_in.CustomerName AS 调入店名称,
                EC_in.CustomerCode AS 调入店编号,
                EC_out.CustomerName AS 调出店名称,
                EC_out.CustomerCode AS 调出店编号,
                EG.GoodsNo,
                EIA.IsCompleted,
                EIA.InstructionApplyDate
            FROM
                ErpInstructionApply AS EIA
            LEFT JOIN ErpCustomer AS EC_in ON EIA.InItemId = EC_in.CustomerId
            LEFT JOIN ErpCustomer AS EC_out ON EIA.OutItemId = EC_out.CustomerId
            LEFT JOIN ErpInstructionApplyGoods AS EIAG ON EIA.InstructionApplyId = EIAG.InstructionApplyId
            LEFT JOIN ErpGoods AS EG ON EIAG.GoodsId = EG.GoodsId
            WHERE 
            -- 	EIA.InItemId = 'C991000274'
                EC_in.CustomerCode='{$CustomerCode}'
                AND IsCompleted = 0
            AND EG.GoodsNo = '{$GoodsNo}'
        ";
        // 分拣未完成
        $fenjian_weiwancheng = $this->db_sqlsrv->query($sql);
        return $fenjian_weiwancheng;
    }

    // 康雷在途 调出店铺 调拨未完成计算 actual_quantity
    public function qudaodiaobo_weiwancheng($diaochufuzheren = "", $CustomerName = '', $GoodsNo = '') {
        // 调拨未完成数量
        $sql3 = "
            SELECT
                EC.CustomItem17,
                EC.CustomerName ,
                EIA.InstructionApplyId,
                EG.GoodsNo ,
                SUM ( EIAG.Quantity ) AS 调拨未完成数
            FROM
                ErpCustomer EC 
                LEFT JOIN ErpInstructionApply EIA ON EC.CustomerId = EIA.OutItemId
                LEFT JOIN ErpInstructionApplyGoods EIAG ON EIA.InstructionApplyId= EIAG.InstructionApplyId 
                LEFT JOIN ErpGoods EG ON EG.GoodsId = EIAG.GoodsId
            WHERE
                EC.ShutOut= 0 
                AND EC.CustomerName = '{$CustomerName}' 
                AND EG.GoodsNo = '{$GoodsNo}' 
                AND EG.TimeCategoryName1 IN ('2020', '2021', '2022', '2023','2024') 
                AND EIA.CodingCodeText='已审结'
                AND EIA.IsCompleted=0
            GROUP BY
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo,
                EIA.InstructionApplyId
        ";

        $kucun = $this->db_sqlsrv->query($sql3);
        return $kucun;
    }

    // 康雷库存 给区域调拨用   调出负责人$diaochufuzheren
    public function qudaodiaobo_kucun_new($diaochufuzheren = "", $CustomerName = '', $GoodsNo = '') {
        $year = date('Y', time());
        // 可查是否完成，未完成数量
        $sql3 = "
            SELECT
                EC.CustomItem17,
                EC.CustomerName ,
                EG.GoodsNo ,
                SUM ( ECS.Quantity ) AS actual_quantity,
                EIA.IsCompleted AS '是否完成',
                EIAG.Quantity AS '调出数量' 
            FROM
                ErpCustomerStock ECS
                LEFT JOIN ErpCustomer EC ON ECS.CustomerId= EC.CustomerId
                LEFT JOIN ErpGoods EG ON ECS.GoodsId= EG.GoodsId
                LEFT JOIN ErpInstructionApplyGoods EIAG ON ECS.GoodsId= EIAG.GoodsId
                LEFT JOIN ErpInstructionApply EIA ON EIA.InstructionApplyId= EIAG.InstructionApplyId 
            WHERE
                EC.ShutOut= 0 
                -- AND EC.CustomItem17 = '{$diaochufuzheren}' 
                                AND EC.CustomerName = '{$CustomerName}' 
                                AND EG.GoodsNo = '{$GoodsNo}' 
                AND EG.TimeCategoryName1= {$year} 
                                AND EIA.CodingCodeText='已审结'
                                AND EIA.IsCompleted=0
            GROUP BY
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo,
                EIA.IsCompleted,
                EIAG.Quantity 
            HAVING
            SUM ( ECS.Quantity ) !=0
        ";

        $kucun = $this->db_sqlsrv->query($sql3);
        return $kucun;
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
                AND EC.CustomItem17 = '{$diaochufuzheren}' 
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
    public function qudaodiaobo_kucun($diaochufuzheren = "", $CustomerName = '', $GoodsNo = '') {

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
                AND EG.GoodsNo = '{$GoodsNo}'
                AND EC.CustomerName = '{$CustomerName}'
                AND EG.TimeCategoryName1 in ('2020','2021','2022','2023','2024')
            GROUP BY 
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo 
            --HAVING SUM(ECS.Quantity)!=0
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
                AND EC.CustomItem17 = '{$diaochufuzheren}' 
                AND EG.TimeCategoryName1= in ('2020','2021','2022','2023','2024')
            GROUP BY
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo,
                EIA.IsCompleted,
                EIAG.Quantity 
            HAVING
            SUM ( ECS.Quantity ) !=0
        ";

        $kucun = $this->db_sqlsrv->query($sql);
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
        $year = date("Y", time());
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
            WHERE EC.CustomItem17 IN ({$shangpingfuzheren})
            -- AND EG.TimeCategoryName1={$year}
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


    public function buhuo_test() {
        $need_num = 1;
        $weiwancheng = $this->qudaodiaobo_weiwancheng('黎亿炎', '凤凰二店', 'B12502005');
        $kucun = $this->qudaodiaobo_kucun('黎亿炎', '凤凰二店', 'B12502005');


        dump($weiwancheng);
        dump($kucun);
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
                // $read_column = [
                //     'A' => '原单编号',
                //     'B' => '手工单号',
                //     'C' => '单据日期',
                //     'D' => '审结日期',
                //     'E' => '仓库编号',
                //     'F' => '店铺编号',
                //     'G' => '订货类型',
                //     'H' => '订单编号',
                //     'I' => '货号',
                //     'J' => '颜色编号',
                //     'K' => '规格',
                //     'L' => '尺码',
                //     'M' => '数量',
                //     'N' => '订货价格',
                //     'O' => '是否完成',
                //     'P' => '备注',
                // ];

                // 新系统
                $read_column = [
                    'A' => '原单编号',
                    'B' => '仓库编号',
                    'C' => '店铺编号',
                    'F' => '货号',
                    'G' => '尺码',
                    'I' => '数量',
                    'K' => '备注',
                ];
                //读取数据
                $data = $this->readExcel1($info, $read_column);

                // echo '<pre>';
                // print_r($data);die;

                // 店铺信息
                $select_customer = $this->db_easyA->table('customer')->field('CustomerName,CustomerCode,CustomItem17')->select()->toArray();
                // $data = array_filter($data);
                foreach ($data as $key => $val) {
                    // if ( empty($val['店铺编号']) || empty($val['货号']) ) {
                    //     unset($data[$key]);
                    // } else {
                    //     $data[$key]['aname'] = $this->authInfo['name'];
                    //     $data[$key]['aid'] = $this->authInfo['id'];
                    //     $data[$key]['create_time'] = $this->create_time;
                    // }
                    $data[$key]['aname'] = $this->authInfo['name'];
                    $data[$key]['aid'] = $this->authInfo['id'];
                    $data[$key]['create_time'] = $this->create_time;
                    // a.店铺编号 = b.CustomerCode 
                    foreach ($select_customer as $key2 => $val2) {
                        if ($val['店铺编号'] == $val2['CustomerCode']) {
                            $data[$key]['店铺名称'] = $val2['CustomerName'];
                            $data[$key]['商品负责人'] = $val2['CustomItem17'];
                            break;
                        } 
                        if ($key2 == count($select_customer) -1) {
                            return json(['code' => -1, 'msg' => '调出店铺号不存在:' . $val['店铺编号']]);
                        }
                    }
                }

                // $this->db_easyA->startTrans();
                $del1 = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->where([
                    ['aid', '=', $this->authInfo['id']]
                ])->delete();
                $del2 = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_3')->where([
                    ['aid', '=', $this->authInfo['id']]
                ])->delete();    

                // 同步康雷最新在途
                $sql = "
                SELECT
                    TOP 20000
                    EC.CustomItem17,
                    EC.CustomerName ,
                    EIA.InstructionApplyId,
                    EG.GoodsNo ,
                    SUM ( EIAG.Quantity ) AS 调拨未完成数,
                    '{$this->authInfo['id']}' as aid,
                    '{$this->authInfo['name']}' as aname
                    FROM
                    ErpCustomer EC 
                    LEFT JOIN ErpInstructionApply EIA ON EC.CustomerId = EIA.OutItemId
                    LEFT JOIN ErpInstructionApplyGoods EIAG ON EIA.InstructionApplyId= EIAG.InstructionApplyId 
                    LEFT JOIN ErpGoods EG ON EG.GoodsId = EIAG.GoodsId
                    WHERE
                    EC.ShutOut= 0 
                    AND EG.TimeCategoryName1 IN ('2020', '2021', '2022', '2023', '2024') 
                    AND EIA.CodingCodeText='已审结'
                    AND EIA.IsCompleted=0
                    GROUP BY
                    EC.CustomItem17,
                    EC.CustomerName,
                    EG.GoodsNo,
                    EIA.InstructionApplyId
            ";
            $select_zaitu = $this->db_sqlsrv->query($sql);


            // 先别删
            $del3 = $this->db_easyA->table('cwl_chuhuozhilingdan_zaitu')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->delete(); 
            // // 康雷在途入库
            $this->db_easyA->table('cwl_chuhuozhilingdan_zaitu')->insertAll($select_zaitu);

            // 批量切割插入
            $chunk_list = array_chunk($data, 1000);
            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_chuhuozhilingdan_3')->insertAll($val);
            }

            // 7天清空库存数据 
            $select_fuzheren = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->field('商品负责人')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->group('商品负责人')->select();
            
            
            // $select_fuzheren = arrToStr($select_fuzheren);
            $fuzheren_str = '';
            foreach ($select_fuzheren as $key => $val) {
                $fuzheren_str .= "'" . $val['商品负责人'] . "',";
            }
            // 删除最后的逗号 '余惠华','刘琳娜','周奇志','张洋涛','易丽平','曹太阳','林冠豪','许文贤','陈栋云','黎亿炎'
            $fuzheren_str = mb_substr($fuzheren_str, 0, -1, "UTF-8");
            $day7 = $this->day7($fuzheren_str);

            $insertAll_7dayclean = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_3')->insertAll($day7);
            $update_chuhuozhilingdan_join_7dayclean = $this->db_easyA->execute("
                UPDATE cwl_chuhuozhilingdan_7dayclean_3 AS a
                LEFT JOIN cwl_chuhuozhilingdan_3 AS b ON a.店铺名称 = b.店铺名称 
                AND a.货号 = b.货号 
                AND b.aid = '{$this->authInfo["id"]}'
                SET b.清空时间 = a.清空时间,
                    b.调出数量 = a.调出数量,
                    b.库存数量 = a.库存数量 
                WHERE
                    a.aid = '{$this->authInfo["id"]}'
            ");

            // echo 222;die;

            // 未完成
            $select_chuhuozhiling_weiwancheng = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->where([
                ['aid', '=', $this->authInfo['id']],
                ['清空时间', 'exp', new Raw('IS NULL')]
            ])->order('create_time DESC')
            ->select()->toArray();

            $select_chuhuozhiling_weiwancheng = $this->db_easyA->query("
                SELECT
                    a.店铺名称 ,
                    a.商品负责人,
                    a.货号,
                    zt.*
                FROM
                    `cwl_chuhuozhilingdan_3` as a right join cwl_chuhuozhilingdan_zaitu as zt
                    on a.店铺名称=zt.CustomerName and a.`商品负责人`= zt.CustomItem17 and a.货号=zt.GoodsNo and a.aid = zt.aid
                WHERE
                    a.`aid` = '{$this->authInfo['id']}' 
                    AND ( `清空时间` IS NULL ) 
                ORDER BY
                    `create_time` DESC
            ");

            // echo $this->db_easyA->getLastSql();die;

            // dump($select_chuhuozhiling_weiwancheng);    
            if ($select_chuhuozhiling_weiwancheng) {
                foreach ($select_chuhuozhiling_weiwancheng as $key => $val) {
                    // 在途未完成 - 库存 <= 0
                    // $zaitu_kucun_0 = $this->buhuo_weiwancheng_handle($val['商品负责人'], $val['店铺名称'], $val['货号']);
                    // if ($zaitu_kucun_0) {
                    //     $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
                    //         ['商品负责人', '=', $val['商品负责人']],
                    //         ['店铺名称', '=', $val['店铺名称']],
                    //         ['货号', '=', $val['货号']],
                    //         ['aid', '=', $this->authInfo['id']],
                    //     ])->update([
                    //         '调拨未完成数' => $zaitu_kucun_0['调拨未完成数'],
                    //         '库存数量' => $zaitu_kucun_0['库存数量']
                    //     ]);
                    // }

                    $kucun = $this->qudaodiaobo_kucun($val['商品负责人'], $val['店铺名称'], $val['货号']);
            
                    // 库存 - 调拨未完成
                    if ($kucun[0]['actual_quantity'] - $val['调拨未完成数'] <= 0) {
                        $data['调拨未完成数'] = $val['调拨未完成数'];
                        $data['库存数量'] = $kucun[0]['actual_quantity']; 
                        $this->db_easyA->table('cwl_chuhuozhilingdan_3')->where([
                            ['商品负责人', '=', $val['商品负责人']],
                            ['店铺名称', '=', $val['店铺名称']],
                            ['货号', '=', $val['货号']],
                            ['aid', '=', $this->authInfo['id']],
                        ])->update([
                            '调拨未完成数' => $val['调拨未完成数'],
                            '库存数量' => $data['库存数量'] 
                        ]);
                    }
                }
            }

            $this->db_easyA->table('cwl_shopbuhuo_log')->insert([
                'option' => '店铺补货',
                'aid' => $this->authInfo['id'],
                'aname' => $this->authInfo['name'],
                'create_time' => date("Y-m-d H:i:s"),
            ]);

            $select_chuhuozhiling_clean = $this->db_easyA->query("
                SELECT * FROM cwl_chuhuozhilingdan_3
                WHERE 
                    aid = '{$this->authInfo['id']}'
                    AND (清空时间 IS NOT NULL OR 调拨未完成数 IS NOT NULL)
                ORDER BY create_time DESC    
            ");    

            if ($select_chuhuozhiling_clean) {
                $time = date('Y-m-d H:i:s');
                // $select = array_chunk($data, 500);
                foreach($select_chuhuozhiling_clean as $key => $val) {
                    // 一条一条插入，因为字段数量不一样
                    $val['更新时间'] = $time;
                    $insert = $this->db_easyA->table('cwl_chuhuozhilingdan_history')->strict(false)->insert($val);
                }
                // print_r($data);
            }
            return json(['code' => 0, 'msg' => '上传成功']);

            }
        }
    }

    // 补货测试
    public function redExcel_test_buhuo() {
        // $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/补货申请_黎亿炎_ccccccccccccc.xlsx';   //文件保存路径
        $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/出货指令单test.xlsx';   //文件保存路径
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

        // if (! cache('test_date')) {
        //     $data = $this->readExcel1($save_path, $read_column);
        //     cache('test_date', $data, 3600);
        // } else {
        //     $data = cache('test_date'); 
        // }
        $data = $this->readExcel1($save_path, $read_column);
        echo '<pre>';
        print_r($data);

        die;

        // 店铺信息
        $select_customer = $this->db_easyA->table('customer')->field('CustomerName,CustomerCode,CustomItem17')->select()->toArray();
        // $data = array_filter($data);
        foreach ($data as $key => $val) {
            // if ( empty($val['店铺编号']) || empty($val['货号']) ) {
            //     unset($data[$key]);
            // } else {
            //     $data[$key]['aname'] = $this->authInfo['name'];
            //     $data[$key]['aid'] = $this->authInfo['id'];
            //     $data[$key]['create_time'] = $this->create_time;
            // }
            $data[$key]['aname'] = $this->authInfo['name'];
            $data[$key]['aid'] = $this->authInfo['id'];
            $data[$key]['create_time'] = $this->create_time;
            // a.店铺编号 = b.CustomerCode 
            foreach ($select_customer as $key2 => $val2) {
                if ($val['店铺编号'] == $val2['CustomerCode']) {
                    $data[$key]['店铺名称'] = $val2['CustomerName'];
                    $data[$key]['商品负责人'] = $val2['CustomItem17'];
                    break;
                } 
                if ($key2 == count($select_customer) -1) {
                    return json(['code' => -1, 'msg' => '调出店铺号不存在:' . $val['店铺编号']]);
                }
            }
        }

        // $this->db_easyA->startTrans();
        $del1 = $this->db_easyA->table('cwl_chuhuozhilingdan_test')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete();
        $del2 = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_test')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete();  

        // 同步康雷最新在途
        $sql = "
            SELECT
                TOP 20000
                EC.CustomItem17,
                EC.CustomerName ,
                EIA.InstructionApplyId,
                EG.GoodsNo ,
                SUM ( EIAG.Quantity ) AS 调拨未完成数,
                '{$this->authInfo['id']}' as aid,
                '{$this->authInfo['name']}' as aname
                FROM
                ErpCustomer EC 
                LEFT JOIN ErpInstructionApply EIA ON EC.CustomerId = EIA.OutItemId
                LEFT JOIN ErpInstructionApplyGoods EIAG ON EIA.InstructionApplyId= EIAG.InstructionApplyId 
                LEFT JOIN ErpGoods EG ON EG.GoodsId = EIAG.GoodsId
                WHERE
                EC.ShutOut= 0 
                AND EG.TimeCategoryName1 IN ('2020', '2021', '2022', '2023', '2024') 
                AND EIA.CodingCodeText='已审结'
                AND EIA.IsCompleted=0
                GROUP BY
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo,
                EIA.InstructionApplyId
        ";
        $select_zaitu = $this->db_sqlsrv->query($sql);


        // 先别删
        // $del3 = $this->db_easyA->table('cwl_chuhuozhilingdan_zaitu')->where([
        //     ['aid', '=', $this->authInfo['id']]
        // ])->delete(); 
        // // 康雷在途入库
        // $this->db_easyA->table('cwl_chuhuozhilingdan_zaitu')->insertAll($select_zaitu);

        // 批量切割插入
        $chunk_list = array_chunk($data, 1000);
        foreach($chunk_list as $key => $val) {
            $this->db_easyA->table('cwl_chuhuozhilingdan_test')->insertAll($val);
        }

        // 7天清空库存数据 
        $select_fuzheren = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->field('商品负责人')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->group('商品负责人')->select();
        
        
        // $select_fuzheren = arrToStr($select_fuzheren);
        $fuzheren_str = '';
        foreach ($select_fuzheren as $key => $val) {
            $fuzheren_str .= "'" . $val['商品负责人'] . "',";
        }
        // 删除最后的逗号 '余惠华','刘琳娜','周奇志','张洋涛','易丽平','曹太阳','林冠豪','许文贤','陈栋云','黎亿炎'
        $fuzheren_str = mb_substr($fuzheren_str, 0, -1, "UTF-8");
        $day7 = $this->day7($fuzheren_str);

        $insertAll_7dayclean = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_test')->insertAll($day7);
        $update_chuhuozhilingdan_join_7dayclean = $this->db_easyA->execute("
            UPDATE cwl_chuhuozhilingdan_7dayclean_test AS a
            LEFT JOIN cwl_chuhuozhilingdan_test AS b ON a.店铺名称 = b.店铺名称 
            AND a.货号 = b.货号 
            AND b.aid = '{$this->authInfo["id"]}'
            SET b.清空时间 = a.清空时间,
                b.调出数量 = a.调出数量,
                b.库存数量 = a.库存数量 
            WHERE
                a.aid = '{$this->authInfo["id"]}'
        ");

        // echo 222;die;

        // 未完成
        // $select_chuhuozhiling_weiwancheng = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->where([
        //     ['aid', '=', $this->authInfo['id']],
        //     ['清空时间', 'exp', new Raw('IS NULL')]
        // ])->order('create_time DESC')
        // ->select()->toArray();

        $select_chuhuozhiling_weiwancheng = $this->db_easyA->query("
            SELECT
                a.店铺名称 ,
                a.商品负责人,
                a.货号,
                zt.*
            FROM
                `cwl_chuhuozhilingdan_test` as a right join cwl_chuhuozhilingdan_zaitu as zt
                on a.店铺名称=zt.CustomerName and a.`商品负责人`= zt.CustomItem17 and a.货号=zt.GoodsNo and a.aid = zt.aid
            WHERE
                a.`aid` = '{$this->authInfo['id']}' 
                AND ( `清空时间` IS NULL ) 
            ORDER BY
                `create_time` DESC
        ");

        // echo $this->db_easyA->getLastSql();die;

        // dump($select_chuhuozhiling_weiwancheng);    
        if ($select_chuhuozhiling_weiwancheng) {
            foreach ($select_chuhuozhiling_weiwancheng as $key => $val) {
                // 在途未完成 - 库存 <= 0
                // $zaitu_kucun_0 = $this->buhuo_weiwancheng_handle($val['商品负责人'], $val['店铺名称'], $val['货号']);
                // if ($zaitu_kucun_0) {
                //     $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
                //         ['商品负责人', '=', $val['商品负责人']],
                //         ['店铺名称', '=', $val['店铺名称']],
                //         ['货号', '=', $val['货号']],
                //         ['aid', '=', $this->authInfo['id']],
                //     ])->update([
                //         '调拨未完成数' => $zaitu_kucun_0['调拨未完成数'],
                //         '库存数量' => $zaitu_kucun_0['库存数量']
                //     ]);
                // }

                $kucun = $this->qudaodiaobo_kucun($val['商品负责人'], $val['店铺名称'], $val['货号']);
            
                // 库存 - 调拨未完成
                if ($kucun[0]['actual_quantity'] - $val['调拨未完成数'] <= 0) {
                    $data['调拨未完成数'] = $val['调拨未完成数'];
                    $data['库存数量'] = $kucun[0]['actual_quantity']; 
                    $this->db_easyA->table('cwl_chuhuozhilingdan_test')->where([
                        ['商品负责人', '=', $val['商品负责人']],
                        ['店铺名称', '=', $val['店铺名称']],
                        ['货号', '=', $val['货号']],
                        ['aid', '=', $this->authInfo['id']],
                    ])->update([
                        '调拨未完成数' => $val['调拨未完成数'],
                        '库存数量' => $data['库存数量'] 
                    ]);
                }
            }
        }

        $this->db_easyA->table('cwl_shopbuhuo_log')->insert([
            'option' => '店铺补货',
            'aid' => $this->authInfo['id'],
            'aname' => $this->authInfo['name'],
            'create_time' => date("Y-m-d H:i:s"),
        ]);
        return json(['code' => 0, 'msg' => '上传成功']);
    } 

    // 补货测试
    public function redExcel_test_buhuo_new() {
        // $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/补货申请_黎亿炎_ccccccccccccc.xlsx';   //文件保存路径
        $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/补货.xlsx';   //文件保存路径
        $read_column = [
            'A' => '原单编号',
            'B' => '仓库编号',
            'C' => '店铺编号',
            'F' => '货号',
            'G' => '尺码',
            'I' => '数量',
            'K' => '备注',
        ];

        // if (! cache('test_date')) {
        //     $data = $this->readExcel1($save_path, $read_column);
        //     cache('test_date', $data, 3600);
        // } else {
        //     $data = cache('test_date'); 
        // }
        $data = $this->readExcel1($save_path, $read_column);
        // echo '<pre>';
        // print_r($data);

        // die;

        // 店铺信息
        $select_customer = $this->db_easyA->table('customer')->field('CustomerName,CustomerCode,CustomItem17')->select()->toArray();
        // $data = array_filter($data);
        foreach ($data as $key => $val) {
            // if ( empty($val['店铺编号']) || empty($val['货号']) ) {
            //     unset($data[$key]);
            // } else {
            //     $data[$key]['aname'] = $this->authInfo['name'];
            //     $data[$key]['aid'] = $this->authInfo['id'];
            //     $data[$key]['create_time'] = $this->create_time;
            // }
            $data[$key]['aname'] = $this->authInfo['name'];
            $data[$key]['aid'] = $this->authInfo['id'];
            $data[$key]['create_time'] = $this->create_time;
            // a.店铺编号 = b.CustomerCode 
            foreach ($select_customer as $key2 => $val2) {
                if ($val['店铺编号'] == $val2['CustomerCode']) {
                    $data[$key]['店铺名称'] = $val2['CustomerName'];
                    $data[$key]['商品负责人'] = $val2['CustomItem17'];
                    break;
                } 
                if ($key2 == count($select_customer) -1) {
                    return json(['code' => -1, 'msg' => '调出店铺号不存在:' . $val['店铺编号']]);
                }
            }
        }

        // $this->db_easyA->startTrans();
        $del1 = $this->db_easyA->table('cwl_chuhuozhilingdan_test')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete();
        $del2 = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_test')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete();  

        
        // 同步康雷最新在途
        echo $sql = "
            SELECT
                TOP 20000
                EC.CustomItem17,
                EC.CustomerName ,
                EIA.InstructionApplyId,
                EG.GoodsNo ,
                SUM ( EIAG.Quantity ) AS 调拨未完成数,
                '{$this->authInfo['id']}' as aid,
                '{$this->authInfo['name']}' as aname
                FROM
                ErpCustomer EC 
                LEFT JOIN ErpInstructionApply EIA ON EC.CustomerId = EIA.OutItemId
                LEFT JOIN ErpInstructionApplyGoods EIAG ON EIA.InstructionApplyId= EIAG.InstructionApplyId 
                LEFT JOIN ErpGoods EG ON EG.GoodsId = EIAG.GoodsId
                WHERE
                EC.ShutOut= 0 
                AND EG.TimeCategoryName1 IN ('2020', '2021', '2022', '2023', '2024') 
                AND EIA.CodingCodeText='已审结'
                AND EIA.IsCompleted=0
                GROUP BY
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo,
                EIA.InstructionApplyId
        ";
        $select_zaitu = $this->db_sqlsrv->query($sql);
        die;    

        // 先别删
        // $del3 = $this->db_easyA->table('cwl_chuhuozhilingdan_zaitu')->where([
        //     ['aid', '=', $this->authInfo['id']]
        // ])->delete(); 
        // // 康雷在途入库
        // $this->db_easyA->table('cwl_chuhuozhilingdan_zaitu')->insertAll($select_zaitu);

        // 批量切割插入
        $chunk_list = array_chunk($data, 1000);
        foreach($chunk_list as $key => $val) {
            $this->db_easyA->table('cwl_chuhuozhilingdan_test')->insertAll($val);
        }

        // 7天清空库存数据 
        $select_fuzheren = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->field('商品负责人')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->group('商品负责人')->select();
        
        
        // $select_fuzheren = arrToStr($select_fuzheren);
        $fuzheren_str = '';
        foreach ($select_fuzheren as $key => $val) {
            $fuzheren_str .= "'" . $val['商品负责人'] . "',";
        }
        // 删除最后的逗号 '余惠华','刘琳娜','周奇志','张洋涛','易丽平','曹太阳','林冠豪','许文贤','陈栋云','黎亿炎'
        $fuzheren_str = mb_substr($fuzheren_str, 0, -1, "UTF-8");
        $day7 = $this->day7($fuzheren_str);

        $insertAll_7dayclean = $this->db_easyA->table('cwl_chuhuozhilingdan_7dayclean_test')->insertAll($day7);
        $update_chuhuozhilingdan_join_7dayclean = $this->db_easyA->execute("
            UPDATE cwl_chuhuozhilingdan_7dayclean_test AS a
            LEFT JOIN cwl_chuhuozhilingdan_test AS b ON a.店铺名称 = b.店铺名称 
            AND a.货号 = b.货号 
            AND b.aid = '{$this->authInfo["id"]}'
            SET b.清空时间 = a.清空时间,
                b.调出数量 = a.调出数量,
                b.库存数量 = a.库存数量 
            WHERE
                a.aid = '{$this->authInfo["id"]}'
        ");

        // echo 222;die;

        // 未完成
        // $select_chuhuozhiling_weiwancheng = $this->db_easyA->table('cwl_chuhuozhilingdan_3')->where([
        //     ['aid', '=', $this->authInfo['id']],
        //     ['清空时间', 'exp', new Raw('IS NULL')]
        // ])->order('create_time DESC')
        // ->select()->toArray();

        $select_chuhuozhiling_weiwancheng = $this->db_easyA->query("
            SELECT
                a.店铺名称 ,
                a.商品负责人,
                a.货号,
                zt.*
            FROM
                `cwl_chuhuozhilingdan_test` as a right join cwl_chuhuozhilingdan_zaitu as zt
                on a.店铺名称=zt.CustomerName and a.`商品负责人`= zt.CustomItem17 and a.货号=zt.GoodsNo and a.aid = zt.aid
            WHERE
                a.`aid` = '{$this->authInfo['id']}' 
                AND ( `清空时间` IS NULL ) 
            ORDER BY
                `create_time` DESC
        ");

        // echo $this->db_easyA->getLastSql();die;

        // dump($select_chuhuozhiling_weiwancheng);    
        if ($select_chuhuozhiling_weiwancheng) {
            foreach ($select_chuhuozhiling_weiwancheng as $key => $val) {
                // 在途未完成 - 库存 <= 0
                // $zaitu_kucun_0 = $this->buhuo_weiwancheng_handle($val['商品负责人'], $val['店铺名称'], $val['货号']);
                // if ($zaitu_kucun_0) {
                //     $this->db_easyA->table('cwl_chuhuozhilingdan_2')->where([
                //         ['商品负责人', '=', $val['商品负责人']],
                //         ['店铺名称', '=', $val['店铺名称']],
                //         ['货号', '=', $val['货号']],
                //         ['aid', '=', $this->authInfo['id']],
                //     ])->update([
                //         '调拨未完成数' => $zaitu_kucun_0['调拨未完成数'],
                //         '库存数量' => $zaitu_kucun_0['库存数量']
                //     ]);
                // }

                $kucun = $this->qudaodiaobo_kucun($val['商品负责人'], $val['店铺名称'], $val['货号']);
            
                // 库存 - 调拨未完成
                if ($kucun[0]['actual_quantity'] - $val['调拨未完成数'] <= 0) {
                    $data['调拨未完成数'] = $val['调拨未完成数'];
                    $data['库存数量'] = $kucun[0]['actual_quantity']; 
                    $this->db_easyA->table('cwl_chuhuozhilingdan_test')->where([
                        ['商品负责人', '=', $val['商品负责人']],
                        ['店铺名称', '=', $val['店铺名称']],
                        ['货号', '=', $val['货号']],
                        ['aid', '=', $this->authInfo['id']],
                    ])->update([
                        '调拨未完成数' => $val['调拨未完成数'],
                        '库存数量' => $data['库存数量'] 
                    ]);
                }
            }
        }

        $this->db_easyA->table('cwl_shopbuhuo_log')->insert([
            'option' => '店铺补货',
            'aid' => $this->authInfo['id'],
            'aname' => $this->authInfo['name'],
            'create_time' => date("Y-m-d H:i:s"),
        ]);
        return json(['code' => 0, 'msg' => '上传成功']);
    } 

}
