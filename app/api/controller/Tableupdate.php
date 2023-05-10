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
}
