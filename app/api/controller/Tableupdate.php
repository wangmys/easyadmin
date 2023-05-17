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

    // 更新周销
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
                DATEPART( yy, GETDATE() ) AS 年份,
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
                --AND ER.CustomerName = '九江六店'
                --AND EG.GoodsNo= 'B32503009'
            GROUP BY
                EC.CustomItem17
                ,ER.CustomerName
                ,EG.GoodsNo
                ,EC.State
                ,EC.CustomItem15
                ,EBC.Mathod
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
                ,ERG.Quantity
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
            ['groupby聚合', '=', '否']
        ])->find();
        // echo $this->db_easyA->getLastSql();
        // dump($find_retail);die;
        // echo count($select);
        if ($find_retail['groupby聚合'] == '否') {
            $select = $this->db_easyA->query("
                SELECT
                    商品负责人,
                    省份,
                    渠道属性,
                    店铺云仓,
                    店铺名称,
                    年份,
                    季节归集,
                    二级时间分类,
                    大类,
                    中类,
                    小类,
                    领型,
                    风格,
                    商品代码,
                    SUM(销售数量) AS 销售数量, 
                    SUM(销售金额) AS  销售金额,
                    '是' AS groupby聚合
                FROM
                    cwl_retail 
                    GROUP BY 商品负责人,店铺名称,商品代码
            ");

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
            }

        }
    }
}
