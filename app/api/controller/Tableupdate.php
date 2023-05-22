<?php
namespace app\api\controller;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="不动销")
 */
class Tableupdate extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }


    // 更新 sp_custoemr_weishouhou
    public function update_weishouhou() {
        // echo 111; die;
        // 删除所有基础计算结果
        // $this->db_easyA->startTrans();
        // $this->db_bi->startTrans();
        // $del_weishouhou = $this->db_easyA->table('sp_custoemr_weishouhou')->where(1)->delete();
        // $handle = $this->db_easyA->table('sp_custoemr_weishouhou')->where(1)->delete();
        // if ($handle) {
        //     $handle = $this->db_easyA->table('sp_custoemr_weishouhou')->where(1)->delete();
        // } else {
        //     $handle =  true;
        // }
        

        $select_weishouhuo = $this->db_sqlsrv->query("   
            SELECT 
                EC.State AS 省份,
                EC.CustomItem17 AS 商品负责人,
                EC.CustomerName AS 店铺名称,
                CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END AS 经营模式,
                EC.CustomerCode AS 店铺编号,
                EW.WarehouseName AS 出货仓库,
                EW.WarehouseCode AS 出货仓库编号,
                ED.DeliveryID AS 单号,
                ED.UpdateTime AS 发货单审批时间,
                DATEDIFF(DAY, ED.UpdateTime, GETDATE()) AS 未收天数,
                SUM(EDG.Quantity) AS 数量
            FROM ErpDelivery ED 
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
            LEFT JOIN ErpWarehouse EW ON ED.WarehouseId=EW.WarehouseId
            WHERE ED.CodingCodeText='已审结'
                AND ED.IsCompleted=0
                AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
                AND EC.ShutOut=0
                AND EC.MathodId IN (4,7)
            GROUP BY 
                EC.State,
                EC.CustomItem17,
                EC.MathodId,
                EC.CustomerName,
                EC.CustomerCode,
                EW.WarehouseName,
                EW.WarehouseCode,
                ED.DeliveryID,
                ED.UpdateTime
            ORDER BY ED.UpdateTime,EC.CustomerName
        ");

        if (!$select_weishouhuo) {
            echo '没有数据更新';
            die;
        }

        // 删除 
        $this->db_bi->table('sp_custoemr_weishouhou')->where(1)->delete();
        $select_weishouhuo = array_chunk($select_weishouhuo, 500);

        // echo '<pre>';
        // print_r($weishouhuo);
        $res_weishouhou = true;

        foreach($select_weishouhuo as $key => $val) {
            $insert = $this->db_bi->table('sp_custoemr_weishouhou')->insertAll($val);
            
            if (! $insert) {
                $res_weishouhou = false;
                break;
            }

            // print_r($res_weishouhou);
        }

        if ($res_weishouhou) {
            // $this->db_easyA->commit();    
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'sp_custoemr_weishouhou 更新成功！'
            ]);
        } else {
            // $this->db_bi->rollback();   
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'sp_custoemr_weishouhou 更新失败！'
            ]);
        }

    }

    // 更新 sp_custoemr_weishouhou_diaobo
    public function update_weishouhou_diaobo() {
        // echo 111; die;
        // 删除所有基础计算结果
        // $this->db_easyA->startTrans();
        // $handle = $this->db_easyA->table('sp_custoemr_weishouhou_diaobo')->where(1)->find();
        // $handle = $this->db_bi->table('sp_custoemr_weishouhou_diaobo')->where(1)->find();
        // if ($handle) {
        //     $handle = $this->db_bi->table('sp_custoemr_weishouhou_diaobo')->where(1)->delete();
        // } else {
        //     $handle =  true;
        // }
        
        $select_weishouhuo_diaobo = $this->db_sqlsrv->query("   
            SELECT
                EC.State AS 省份,
                EC.CustomItem17 AS 商品负责人,
                EC.CustomerName AS 调入店铺,
                CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END AS 经营模式,
                EC.CustomerCode AS 店铺编号,
                ECC.CustomerName AS 调出店铺,
                ECC.CustomerCode AS 调出店铺编号,
                EI.CustOutboundId AS 单号,
                EI.UpdateTime AS 调出单审批时间,
                DATEDIFF(DAY, EI.UpdateTime, GETDATE()) AS 发出天数,
                SUM(EIG.Quantity) AS 数量
            FROM ErpCustOutbound EI 
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
            LEFT JOIN ErpCustomer ECC ON EI.CustomerId=ECC.CustomerId
            WHERE EI.CodingCodeText='已审结'
                AND EI.IsCompleted=0
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                AND EC.ShutOut=0
                AND EC.MathodId IN (4,7)
            GROUP BY 
                EC.State,
                EC.CustomItem17,
                EC.CustomerName,
                EC.MathodId,
                EC.CustomerCode,
                ECC.CustomerName,
                ECC.CustomerCode,
                EI.CustOutboundId,
                EI.UpdateTime;
        ");
        if (!$select_weishouhuo_diaobo) {
            echo '没有数据更新';
            die;
        }

        $handle = $this->db_bi->table('sp_custoemr_weishouhou_diaobo')->where(1)->delete();

        $select_weishouhuo_diaobo = array_chunk($select_weishouhuo_diaobo, 500);

        // echo '<pre>';
        // print_r($select_weishouhuo_diaobo);
        // die;
        $res_weishouhou_diaobo = true;

        foreach($select_weishouhuo_diaobo as $key => $val) {
            $insert = $this->db_bi->table('sp_custoemr_weishouhou_diaobo')->insertAll($val);
            if (! $insert) {
                $res_weishouhou_diaobo = false;
                break;
            }
        }


        if ($res_weishouhou_diaobo) {
            // $this->db_bi->commit();    
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'sp_custoemr_weishouhou_diaobo 更新成功！'
            ]);
        } else {
            // $this->db_bi->rollback();   
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'sp_custoemr_weishouhou_diaobo 更新失败！'
            ]);
        }

    }


    // 更新 customer
    public function update_customer() {
        // 查询bi
        $select_customer = $this->db_bi->table('customer')->where(1)->select()->toArray();
        if (!$select_customer) {
            echo '没有数据更新';
            die;
        } 

        $handle = $this->db_easyA->table('customer')->where(1)->delete();

        $select_customer = array_chunk($select_customer, 500);

        foreach($select_customer as $key => $val) {
            $insert = $this->db_easyA->table('customer')->insertAll($val);
        }


        if ($select_customer) {
            // $this->db_bi->commit();    
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'easyadmin2 customer 更新成功！'
            ]);
        } else {
            // $this->db_bi->rollback();   
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'easyadmin2 customer 更新失败！'
            ]);
        }
    }

    // 更新周销 断码率专用 初步加工康雷表 groub by合并插入自己的retail表里
    public function retail_first() {
        // 康雷查询周销
        $select = $this->db_sqlsrv->query("   
            SELECT TOP
                200000 EC.CustomItem17 AS 商品负责人,
                EC.State AS 省份,
                EBC.Mathod AS 渠道属性,
                EC.CustomItem15 AS 店铺云仓,
                ER.CustomerName AS 店铺名称,
            --  DATEPART( yy, ER.RetailDate ) AS 年份,
            --  DATEPART( yy, GETDATE() ) AS 年份,
                EG.TimeCategoryName1 as 年份,
            CASE
                    EG.TimeCategoryName2
                    WHEN '初春' THEN
                    '春季'
                    WHEN '正春' THEN
                    '春季'
                    WHEN '春季' THEN
                    '春季'
                    WHEN '初秋' THEN
                    '秋季'
                    WHEN '深秋' THEN
                    '秋季'
                    WHEN '秋季' THEN
                    '秋季'
                    WHEN '初夏' THEN
                    '夏季'
                    WHEN '盛夏' THEN
                    '夏季'
                    WHEN '夏季' THEN
                    '夏季'
                    WHEN '冬季' THEN
                    '冬季'
                    WHEN '初冬' THEN
                    '冬季'
                    WHEN '深冬' THEN
                    '冬季'
                END AS 季节归集,
                EG.TimeCategoryName2 AS 二级时间分类,
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 小类,
                SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo  AS 商品代码,
                SUM ( ERG.Quantity ) AS 销售数量,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.CodingCodeText = '已审结'
                AND ER.RetailDate >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))

                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                AND EG.TimeCategoryName2 IN ( '初夏', '盛夏', '夏季' )
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023')
                --AND ER.CustomerName = '九江六店'
                --AND EG.GoodsNo= 'B32503009'
            GROUP BY
                EC.CustomItem17
                ,ER.CustomerName
                ,EG.GoodsNo
                ,EC.State
                ,EC.CustomItem15
                ,EBC.Mathod
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
        ");
        // echo count($select);
        if ($select) {
            // 删除
            $this->db_easyA->table('cwl_retail')->where(1)->delete();

            $chunk_list = array_chunk($select, 1000);
            $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_retail')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            if ($status) {
                $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => 'cwl_retail first 更新成功！'
                ]);
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_retail first 更新失败！'
                ]);
            }

        }
    }

    // 更新周销
    public function retail_second() {
        // 康雷查询周销
        $find_retail =$this->db_easyA->table('cwl_retail')->where([
            ['排名', 'exp', new Raw('IS NULL')]
        ])->find();
        // echo $this->db_easyA->getLastSql();
        // dump($find_retail);die;
        // echo count($select);

        // 需要进行排名
        if ($find_retail) {
            $select = $this->db_easyA->query("
                SELECT
                    a.商品负责人,
                    a.省份,
                    a.渠道属性,
                    a.店铺云仓,
                    a.店铺名称,
                    a.年份,
                    a.季节归集,
                    a.二级时间分类,
                    a.大类,
                    a.小类,
                    a.领型,
                    a.风格,
                    a.商品代码,
                    a.销售数量,
                    a.销售金额, 
                (
                    @rank :=
                IF
                ( @GROUP = a.中类, @rank + 1, 1 )) AS 排名
                ,
                ( @GROUP := a.中类 ) AS 中类
            FROM
                (
                SELECT
                    *
                FROM
                    cwl_retail 
                WHERE
                    1
            -- 		省份='江西省'
            -- 		店铺名称 = '九江六店' 
                ORDER BY
                    店铺名称 ASC,风格 ASC,季节归集 ASC,中类 ASC, 排名 ASC,
                    销售数量 DESC 
                ) a,
                ( SELECT @rank := 0, @GROUP := '' ) AS b
            ");

            if ($select) {
                // dump($select[0]);
                // dump($select[1]);
                // dump($select[2]);
                // dump($select[3]);
                // die;
                // 删除
                $this->db_easyA->table('cwl_retail')->where(1)->delete();

                $chunk_list = array_chunk($select, 1000);
                $this->db_easyA->startTrans();

                $status = true;
                foreach($chunk_list as $key => $val) {
                    // 基础结果 
                    $insert = $this->db_easyA->table('cwl_retail')->strict(false)->insertAll($val);
                    if (! $insert) {
                        $status = false;
                        break;
                    }
                }

                if ($status) {
                    $this->db_easyA->commit();
                    return json([
                        'status' => 1,
                        'msg' => 'success',
                        'content' => 'cwl_retail second 更新成功！'
                    ]);
                } else {
                    $this->db_easyA->rollback();
                    return json([
                        'status' => 0,
                        'msg' => 'error',
                        'content' => 'cwl_retail second 更新失败！'
                    ]);
                }
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_retail 排名执行失败！'
                ]);
            }

        }
    }

    // 采购顶推报表 receipt收货 receiptNotice采集入库
    public function receipt_receiptNotice() {
        // 采购收货
        $sql1 = "
            SELECT
                EW.WarehouseName AS 云仓,
                EG.TimeCategoryName1 AS 年份,
            CASE
                    EG.TimeCategoryName2 
                    WHEN '初春' THEN
                    '春季' 
                    WHEN '正春' THEN
                    '春季' 
                    WHEN '春季' THEN
                    '春季' 
                    WHEN '初秋' THEN
                    '秋季' 
                    WHEN '深秋' THEN
                    '秋季' 
                    WHEN '秋季' THEN
                    '秋季' 
                    WHEN '初夏' THEN
                    '夏季' 
                    WHEN '盛夏' THEN
                    '夏季' 
                    WHEN '夏季' THEN
                    '夏季' 
                    WHEN '冬季' THEN
                    '冬季' 
                    WHEN '初冬' THEN
                    '冬季' 
                    WHEN '深冬' THEN
                    '冬季' 
                END AS 季节,
                EG.TimeCategoryName2 AS 二级时间,
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.GoodsName AS 货号名称,
                EG.CategoryName AS 分类,
                SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo AS 货号,
                EGC.ColorDesc AS 颜色,
                SUM(ERG.Quantity) AS 数量,
                ES.SupplyName AS 供应商 
            FROM
                ErpReceipt AS ER
                LEFT JOIN ErpWarehouse AS EW ON ER.WarehouseId = EW.WarehouseId
                LEFT JOIN ErpReceiptGoods AS ERG ON ER.ReceiptId = ERG.ReceiptId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                LEFT JOIN erpGoodsColor AS EGC ON ERG.GoodsId = EGC.GoodsId
                LEFT JOIN ErpSupply AS ES ON ER.SupplyId = ES.SupplyId 
            WHERE
                ER.CodingCodeText = '已审结' 
                AND ER.ReceiptDate = DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) ) 
                AND ER.Type= 1 
                AND ES.SupplyName <> '南昌岳歌服饰' 
                AND EG.TimeCategoryName1 IN ( '2023' ) 
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EW.WarehouseName IN ( '过账虚拟仓', '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓' ) 
            GROUP BY
                EW.WarehouseName
                ,ES.SupplyName
                ,EG.GoodsNo
                ,EG.GoodsName 
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
                ,EGC.ColorDesc
        ";

        // 采购入库
        $sql2 = "
            SELECT
                EW.WarehouseName AS 云仓,
                EG.TimeCategoryName1 AS 年份,
            CASE
                    EG.TimeCategoryName2 
                    WHEN '初春' THEN
                    '春季' 
                    WHEN '正春' THEN
                    '春季' 
                    WHEN '春季' THEN
                    '春季' 
                    WHEN '初秋' THEN
                    '秋季' 
                    WHEN '深秋' THEN
                    '秋季' 
                    WHEN '秋季' THEN
                    '秋季' 
                    WHEN '初夏' THEN
                    '夏季' 
                    WHEN '盛夏' THEN
                    '夏季' 
                    WHEN '夏季' THEN
                    '夏季' 
                    WHEN '冬季' THEN
                    '冬季' 
                    WHEN '初冬' THEN
                    '冬季' 
                    WHEN '深冬' THEN
                    '冬季' 
                END AS 季节,
                EG.TimeCategoryName2 AS 二级时间,
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.GoodsName AS 货号名称,
                EG.CategoryName AS 分类,
                SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo AS 货号,
                EGC.ColorDesc AS 颜色,
                SUM(ERNG.Quantity) AS 数量,
                ES.SupplyName AS 供应商 
            FROM
                ErpReceiptNotice AS ERN
                LEFT JOIN ErpWarehouse AS EW ON ERN.WarehouseId = EW.WarehouseId
                LEFT JOIN ErpReceiptNoticeGoods AS ERNG ON ERN.ReceiptNoticeId = ERNG.ReceiptNoticeId
                LEFT JOIN erpGoods AS EG ON ERNG.GoodsId = EG.GoodsId
                LEFT JOIN erpGoodsColor AS EGC ON ERNG.GoodsId = EGC.GoodsId
                LEFT JOIN ErpSupply AS ES ON ERN.SupplyId = ES.SupplyId 
            WHERE
                ERN.CodingCodeText = '已审结' 
                AND ERN.ReceiptNoticeDate = DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) ) 
                AND ERN.IsCompleted IS NULL
                AND ES.SupplyName <> '南昌岳歌服饰' 
                AND EG.TimeCategoryName1 IN ( '2023' ) 
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EW.WarehouseName IN ( '过账虚拟仓', '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓' ) 
            GROUP BY
                EW.WarehouseName
                ,ES.SupplyName
                ,EG.GoodsNo
                ,EG.GoodsName 
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
                ,EGC.ColorDesc
            ";

        $select_receipt = $this->db_sqlsrv->query($sql1);
        $select_receiptNotice = $this->db_sqlsrv->query($sql2);
        
        // 删除旧数据
        $this->db_easyA->table('cwl_ErpReceipt')->where(1)->delete();
        $this->db_easyA->table('cwl_ErpReceiptNotice')->where(1)->delete();
        
        $this->db_easyA->startTrans();
        // 采购收货
        $insert_receipt = $this->db_easyA->table('cwl_ErpReceipt')->strict(false)->insertAll($select_receipt);
        // 采集入库
        $insert_receiptNotice = $this->db_easyA->table('cwl_ErpReceiptNotice')->strict(false)->insertAll($select_receiptNotice);

        if ($insert_receipt && $insert_receiptNotice) {
            $this->db_easyA->commit();
            $this->receipt_receiptNotice_report1();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => '采购定推表 更新成功！'
            ]);
        } else {
            $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => '采购定推表 更新失败！'
            ]);
        }
    }

    public function receipt_receiptNotice_report1() {
        $sql_receipt = "
            SELECT
                ER.云仓,
                ER.年份,
                ER.季节,
                ER.二级时间,
                ER.大类,
                ER.中类,
                ER.货号名称,
                ER.分类,
                ER.领型,
                ER.风格,
                ER.货号,
                ER.颜色,
                ER.供应商,
                '0' AS 发货总量,
                SUM(ER.数量) AS 入库总量
            FROM
                cwl_ErpReceipt AS ER
            GROUP BY
                ER.风格,ER.供应商,ER.中类,ER.领型
        ";
        $sql_receiptNotic = "
            SELECT
                ERN.云仓,
                ERN.年份,
                ERN.季节,
                ERN.二级时间,
                ERN.大类,
                ERN.中类,
                ERN.货号名称,
                ERN.分类,
                ERN.领型,
                ERN.风格,
                ERN.货号,
                ERN.颜色,
                ERN.供应商,
                SUM(ERN.数量) AS 发货总量,
                '0' AS 入库总量
            FROM
                cwl_ErpReceiptNotice AS ERN
            GROUP BY
                ERN.风格,ERN.供应商,ERN.中类,ERN.领型
        ";

        $select_receipt = $this->db_easyA->query($sql_receipt);
        $select_receiptNotic = $this->db_easyA->query($sql_receiptNotic);

        $mergeData = []; 
        $mergeData = array_merge($select_receipt, $select_receiptNotic);
        // $mergeData = $select_receiptNotic;

        // echo '<pre>';
        // print_r($mergeData);
        // 删除旧数据
        $this->db_easyA->table('cwl_ErpReceipt_report1')->where(1)->delete();

        $report1 = $this->db_easyA->table('cwl_ErpReceipt_report1')->strict(false)->insertAll($mergeData);
       
        return $report1;
    }

    // 采购定推表1 采购定推表2 sql
    public function receipt_receiptNotice_report1_create($seasion = '夏季') {
        $sql1 = "
            SELECT
                供应商,
                SUM(发货总量) AS 发货总量,
                SUM(入库总量) AS 入库总量,
                风格,
                中类,
                领型 
            FROM
                `cwl_ErpReceipt_report1`
                WHERE 季节='{$seasion}'
            GROUP BY 	
                风格,
                供应商,
                中类,
                领型 
        ";
        $sql2 = "
            SELECT
                IFNULL(风格, '总计') AS 风格,
                IFNULL(中类, '大类合计') AS 中类,
                IFNULL(领型,'中类合计') AS 领型,
                SUM(发货总量) AS 发货总量,
                SUM(入库总量) AS 入库总量
            FROM
                `cwl_ErpReceipt_report1`
                WHERE 季节='夏季'
            GROUP BY 	
                风格,
                大类,
                中类,
                领型 
            WITH ROLLUP
        ";

        $select_report1 = $this->db_easyA->query($sql1);
        $select_report2 = $this->db_easyA->query($sql2);

        dump($select_report1);
        dump($select_report2);

    }

    public function dianpuyejihuanbi_date_handle() {
        $date = input('date');

        // die;
        $res = $this->dianpuyejihuanbi_date($date);
        // dump(json_decode($res->toArray(), true));
        dump($res);
    }

    // 店铺业绩环比数据源入库        
    public function dianpuyejihuanbi_date($date = '') {
        if (empty($date)) return [
            'status' => 0,
            'msg' => 'error',
            'content' => '店铺业绩环比数据源 更新失败, 日期时间不能为空！'
        ];
        $sql = "
            declare @retail_date DATE
            set @retail_date = '{$date}'

            SELECT
                SUBSTRING(EC.State, 1, 2)  AS 省份,
                ER.CustomerName AS 店铺名称,
                EBC.Mathod AS 经营属性,
                @retail_date AS 日期,   
            CASE
                DATEPART( weekday, @retail_date )
                WHEN 1 THEN
                '星期日'
                WHEN 2 THEN
                '星期一'
                WHEN 3 THEN
                '星期二'
                WHEN 4 THEN
                '星期三'
                WHEN 5 THEN
                '星期四'
                WHEN 6 THEN
                '星期五'
                WHEN 7 THEN
                '星期六'
                END AS 星期,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
            FROM
                ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            WHERE
                ER.RetailDate >= @retail_date 
                AND ER.RetailDate < DATEADD( DAY, +1, @retail_date ) 
                AND ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0
                AND EC.RegionId <> 55
                AND EBC.Mathod IN ('直营', '加盟')
            GROUP BY 
                ER.CustomerName,
                EC.State,
                EBC.Mathod	
            ORDER BY EC.State ASC
        ";
        // 查康雷
        $select_data = $this->db_sqlsrv->query($sql);
        if ($select_data) {
            // dump($select_data);
            // 删 easyadmin2
            $this->db_easyA->table('cwl_dianpuyejihuanbi')->where([
                ['日期', '=', $date]
            ])->delete();

            $this->db_easyA->startTrans();
            $insertAll = $this->db_easyA->table('cwl_dianpuyejihuanbi')->strict(false)->insertAll($select_data);
            if ($insertAll) {
                $this->db_easyA->commit();
                return [
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "店铺业绩环比数据源 更新成功，{$date}！"
                ];
            } else {
                $this->db_easyA->rollback();
                return [
                    'status' => 0,
                    'msg' => 'error',
                    'content' => "店铺业绩环比数据源 更新失败，{$date}！"
                ];
            }   
        }
    }

    // 上月环比数据整理   cwl_dianpuyejihuanbi_2   
    public function dianpuyejihuanbi_2_handle($date = '') {
        $date_str = "2023-04-01";
        $date = date('Y-m', strtotime($date_str));
        $sql0 = "set @date_str = '{$date_str}';";

        $this->db_easyA->query($sql0);
        $sql = "
            SELECT
                a.省份,a.店铺名称,a.经营属性,DATE_FORMAT( @date_str, '%Y-%m') AS 日期,
                (
                SELECT
                    ROUND(SUM(w1.销售金额) / count(w1.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w1
                WHERE
                    w1.日期 >= @date_str
                    AND w1.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w1.星期='星期一'
                    AND w1.`店铺名称`=a.店铺名称
                ) as 星期一,
                (
                SELECT
                    ROUND(SUM(w2.销售金额) / count(w2.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w2
                WHERE
                    w2.日期 >= @date_str
                    AND w2.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w2.星期='星期二'
                    AND w2.`店铺名称`=a.店铺名称
                ) as 星期二,
                (
                SELECT
                    ROUND(SUM(w3.销售金额) / count(w3.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w3
                WHERE
                    w3.日期 >= @date_str
                    AND w3.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w3.星期='星期三'
                    AND w3.`店铺名称`=a.店铺名称
                ) as 星期三,
                (
                SELECT
                    ROUND(SUM(w4.销售金额) / count(w4.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w4
                WHERE
                    w4.日期 >= @date_str
                    AND w4.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w4.星期='星期四'
                    AND w4.`店铺名称`=a.店铺名称
                ) as 星期四,
                (
                SELECT
                    ROUND(SUM(w5.销售金额) / count(w5.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w5
                WHERE
                    w5.日期 >= @date_str
                    AND w5.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w5.星期='星期五'
                    AND w5.`店铺名称`=a.店铺名称
                ) as 星期五,
                (
                SELECT
                    ROUND(SUM(w6.销售金额) / count(w6.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w6
                WHERE
                    w6.日期 >= @date_str
                    AND w6.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w6.星期='星期六'
                    AND w6.`店铺名称`=a.店铺名称
                ) as 星期六,
                (
                SELECT
                    ROUND(SUM(w7.销售金额) / count(w7.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi AS w7
                WHERE
                    w7.日期 >= @date_str
                    AND w7.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w7.星期='星期日'
                    AND w7.`店铺名称`=a.店铺名称
                ) as 星期日	
            FROM
                cwl_dianpuyejihuanbi AS a
            WHERE
                日期 >= @date_str
                AND 日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
            GROUP BY `店铺名称`
            ORDER BY 省份
        "; 

        // 查easyadmin2
        $select_data = $this->db_easyA->query($sql);

        // 首单日期   
        $sql2 = "
            SELECT
                ER.CustomerName AS 店铺名称,
                (
                    SELECT TOP 1 RetailDate FROM
                        ErpRetail  
                    WHERE CustomerName = ER.CustomerName
                ) AS 首单日期
            FROM
                ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            WHERE
                ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0	
                AND EC.RegionId <> 55
                AND EBC.Mathod IN ('直营', '加盟')
            GROUP BY ER.CustomerName
        ";
        // 首单日期
        $select_firstDate = $this->db_sqlsrv->query($sql2);

        foreach ($select_data as $key => $val) {
            foreach ($select_firstDate as $key2 => $val2) {
                if ($val2['店铺名称'] == $val['店铺名称']) {
                    $select_data[$key]['首单日期'] = $val2['首单日期'];
                    break;
                }

                if ($key2 == count($select_firstDate) -1) {
                    $select_data[$key]['首单日期'] = '';
                }
            }
        }

        if ($select_data) {
            // dump($select_data);
            // 删 easyadmin2
            $this->db_easyA->table('cwl_dianpuyejihuanbi_2')->where([
                ['日期', '=', $date]
            ])->delete();

            $this->db_easyA->startTrans();
            $insertAll = $this->db_easyA->table('cwl_dianpuyejihuanbi_2')->strict(false)->insertAll($select_data);
            if ($insertAll) {
                $this->db_easyA->commit();

                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "店铺业绩环比 上月环比数据整理 更新成功，{$date}！"
                ]);
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => "店铺业绩环比 上月环比数据整理  更新失败，{$date}！"
                ]);
            }   
        }
    }

    public function testDay() {
        // 今天是星期几
        $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
        // 上月开始
        $last_month  = date("Y-m-01", strtotime('-1month')); 
        // 上月今天
        $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
        // 本月开始
        $current_month  = date("Y-m-01", time()); 
        // 本月今天
        $today_date = date("Y-m-d", time());

        $first = '2023-04-05';
        // $first = '2023-04-27';
        if (strtotime($first) <= strtotime($last_month)) {
            echo '首单<=' . $last_month; 
        } elseif (strtotime($first) > strtotime($last_month) && strtotime($first) <= strtotime($last_month_today)) {
            echo '首单>' . $last_month; 
            echo '<br>';
            echo '首单<=' . $last_month_today; 
            echo '<br>';
            echo '结果:' . $first . '-' . $last_month_today;
            echo '<br>';
            echo date("Y-m-", time()) . date('d', strtotime($first));
        } else {
            echo '其他情况';
            
        }
    }

    // 上月环比数据整理  cwl_dianpuyejihuanbi_2 二次加工  
    public function dianpuyejihuanbi_2_handle2() {
        // 今天是星期几
        $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
        // 上月开始
        $last_month  = date("Y-m-01", strtotime('-1month')); 
        // 上月今天
        $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
        // 本月开始
        $current_month  = date("Y-m-01", time()); 
        // 本月今天
        $today_date = date("Y-m-d", time());

        $select_dianpuyejihuanbi_2 = $this->db_easyA->table('cwl_dianpuyejihuanbi_2')->where([
            ['handle', 'exp', new Raw('IS NULL')],
            // ['店铺名称', '=', '彝良一店']
        ])
        // ->limit(1)
        ->select()->toArray();
        // dump($select_dianpuyejihuanbi_2);die;    
        foreach ($select_dianpuyejihuanbi_2 as $key => $val) {
            // $updateData = [];
            $updateData = $val;
            // 今日流水
            $find_dianpuyejihuanbi = $this->db_easyA->table('cwl_dianpuyejihuanbi')->where([
                ['日期', '=', date('Y-m-d', time())],
                ['店铺名称', '=', $val['店铺名称']]
            ])->find();
 
            // 今日流水
            if ($find_dianpuyejihuanbi) {
                $updateData['今日流水'] = $find_dianpuyejihuanbi['销售金额'];
            } else {
                $updateData['今日流水'] = '';
            }

            // 今日环比： (今天的店铺流水 / 上个月周N平均值) -1
            if ($updateData['今日流水'] && $val[$today]) {
                $updateData['今日环比'] = ($updateData['今日流水'] / $val[$today]) - 1;
            } else {
                $updateData['今日环比'] = '';
            }

            // 环比流水: 上个月周n平均值
            $updateData['环比流水'] = $val[$today];

            // 本月累计流水：新店（5月5-22） 环比累计流水 ：新店（4月5-4月22）
            // 首单在上个月1号前
            if (strtotime($val['首单日期']) <= strtotime($last_month)) {
                // 本月累计流水
                $benyueliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $current_month],
                    ['日期', '<=', $today_date],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                // dump($select_benyueliushui);
                $updateData['本月累计流水'] = $benyueliushui['销售金额'];

                // 环比累计流水   
                $huanbiliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $last_month],
                    ['日期', '<=', $last_month_today],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                $updateData['环比累计流水'] = $huanbiliushui['销售金额'];
                // 月度环比： (本月累计流水 /环比累计流水 )- 1
                $updateData['月度环比'] = round(($updateData['本月累计流水'] / $updateData['环比累计流水']) - 1, 2);
                $updateData['use'] = 1;
            // 第5-22天 
            } elseif (strtotime($val['首单日期']) > strtotime($last_month) && strtotime($val['首单日期']) <= strtotime($last_month_today)) {
                $current_month_start = date("Y-m-", time()) . date('d', strtotime($val['首单日期']));
                // 本月累计流水
                $benyueliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $current_month_start],
                    ['日期', '<=', $today_date],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                // dump($select_benyueliushui);
                $updateData['本月累计流水'] = $benyueliushui['销售金额'];

                // 环比累计流水   
                $huanbiliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $val['首单日期']],
                    ['日期', '<=', $last_month_today],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                $updateData['环比累计流水'] = $huanbiliushui['销售金额'];
                // 月度环比： (本月累计流水 /环比累计流水 )- 1
                $updateData['月度环比'] = round(($updateData['本月累计流水'] / $updateData['环比累计流水']) - 1, 2);
                $updateData['use'] = 1;
            } else {
                // echo '其他情况';
            }    

            $updateData['handle'] = 1;

            // dump($updateData);die;
            $this->db_easyA->table('cwl_dianpuyejihuanbi_2')->where([
                ['店铺名称', '=', $updateData['店铺名称']]
            ])->update($updateData);
        }

        // dump($select_dianpuyejihuanbi_2);
        echo 'ok';
    }
}
