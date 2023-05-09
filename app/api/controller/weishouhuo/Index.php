<?php
declare (strict_types = 1);

namespace app\api\controller\weishouhuo;
use app\admin\model\dress\YinliuStore;
use app\common\constants\AdminConstant;
use think\facade\Cache;
use think\cache\driver\Redis;
use think\facade\Db;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\Yinliu;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Customers;
use jianyan\excel\Excel;
use \think\Request;

class Index
{
    /**
     * -店铺未收调拨
     * @param Request $req
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function index(Request $req)
    {
        $sql = "SELECT 
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
            --AND ED.UpdateTime<CONVERT(VARCHAR,GETDATE()-3,120)
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
        ORDER BY ED.UpdateTime,EC.CustomerName";
        $is_true = $req->get('is_true');
         if(empty($res = Cache::get('weishouhuo_index'))  || $is_true){
            $res = Db::connect('sqlsrv')->query($sql);
            Cache::set('weishouhuo_index',$res);
         }

         // 设置标题头
        $header = [];
        if($res){
            $header = array_map(function($v){ return [$v,$v]; }, array_keys($res[0]));
        }
        $fileName = time();
        return Excel::exportData($res, $header, $fileName, 'xlsx');
    }

    /**
     * -店铺未收调拨发出
     * @param Request $req
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function index2(Request $req)
    {
        $sql = "SELECT
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
        --AND EI.UpdateTime<CONVERT(VARCHAR,GETDATE()-3,120)
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
        EI.UpdateTime";
         $is_true = $req->get('is_true');
         if(empty($res = Cache::get('weishouhuo_index2')) || $is_true){
            $res = Db::connect('sqlsrv')->query($sql);
            Cache::set('weishouhuo_index2',$res);
         }
         // 设置标题头
         $header = [];
         if($res){
              $header = array_map(function($v){ return [$v,$v]; }, array_keys($res[0]));
         }
         $fileName = '店铺未收调拨发出';
         return Excel::exportData($res, $header, $fileName, 'xlsx');
    }
}
