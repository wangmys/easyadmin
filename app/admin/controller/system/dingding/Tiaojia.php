<?php
namespace app\admin\controller\system\dingding;

use AlibabaCloud\SDK\Dingtalk\Vworkflow_1_0\Models\QuerySchemaByProcessCodeResponseBody\result\schemaContent\items\props\push;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * 报表
 * Class Baobiao
 * @package app\dingtalk
 */
class Tiaojia extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    
    /**
     * 构造函数
     * Dingtalk constructor.
     */
    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
    }

    public function list() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // 非系统管理员
            // if (! checkAdmin()) { 
            //     $aname = session('admin.name');       
            //     $aid = session('admin.id');   
            //     $mapSuper = " AND list.aid='{$aid}'";  
            // } else {
            //     $mapSuper = '';
            // }
            // if (!empty($input['更新日期'])) {
            //     $map1 = " AND `更新日期` = '{$input['更新日期']}'";                
            // } else {
            //     $today = date('Y-m-d');
            //     $map1 = " AND `更新日期` = '{$today}'";            
            // }
            $sql = "
                SELECT 
                   *
                FROM 
                    dd_baobiao
                WHERE 1
                ORDER BY `key` ASC, 钉群, 编号 ASC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    dd_baobiao
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);

            // $reads = $this->db_easyA->table('dd_customer_push_weather')->where([
            //     ['更新日期', '=', $input['更新日期'] ? $input['更新日期'] : date('Y-m-d')],
            //     ['已读', '=', 'Y'],
            // ])->count('*');

            // $noReads = $this->db_easyA->table('dd_customer_push_weather')->where([
            //     ['更新日期', '=', $input['更新日期'] ? $input['更新日期'] : date('Y-m-d')],
            //     ['已读', '=', 'N'],
            // ])->count('*');
            // print_r($count);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            // $time = time();
            // if ($time < strtotime(date('Y-m-d 20:30:00'))) {
            //     // echo '显示昨天';
            //     $today = date('Y-m-d', strtotime('-1 day', $time));
            // } else {
            //     // echo '显示今天';
            //     $today = date('Y-m-d');
            // }
            return View('list', [
                // 'today' => $today,
            ]);
        }        
    }

    public function upload2() {
        $slq_info2 = "
            SELECT 
                EG.GoodsNo as 货号,
                EG.GoodsId,
                EGPT.UnitPrice as 零售价,
                EGC.ColorDesc as 颜色,
                EGI.Img
            FROM ErpGoods AS EG
            LEFT JOIN ErpGoodsColor AS EGC ON EG.GoodsId = EGC.GoodsId
            LEFT JOIN ErpGoodsPriceType AS EGPT ON EG.GoodsId = EGPT.GoodsId AND EGPT.PriceId = 1
            LEFT JOIN ErpGoodsImg AS EGI ON EG.GoodsId = EGI.GoodsId
            where EG.GoodsNo in ('214504023','214504024','214504025','214504026','214504027','214504028')
        ";
    }

    // 更新调价模板店铺信息
    public function getCustomer() {
        $id = 6636;
        $sql_temp = "
            select 货号 from dd_tiaojia_temp
            where id = '{$id}'
        ";
        $select_temp = $this->db_easyA->query($sql_temp);

        $货号str = "";
        foreach ($select_temp as $key => $val) {
            if ($key + 1 == count($select_temp)) {
                $货号str .= "'" . $val['货号'] . "'";
            } else {
                $货号str .= "'" . $val['货号'] . "',";
            }
        }

        // $货号str;
        $sql_店铺可用库存 = "
                SELECT 
                    '{$id}' as id,
                    T.CustomerName AS 店铺名称,
                    T.货号,
                    SUM(T.店铺库存) as 店铺库存,
                    SUM(T.在途库存) as 在途库存,
                    SUM(T.店铺库存) + SUM(T.在途库存) as 店铺可用库存
                FROM
                ( -- 店铺库存
                        SELECT 
                                EC.CustomerName,
                                EG.GoodsNo AS 货号,
                                SUM(ECSD.Quantity) AS 店铺库存,
                                0 as 在途库存
                        FROM ErpCustomerStock ECS 
                        LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId = ECSD.StockId
                        LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
                        LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                        WHERE EC.MathodId IN (4)
                        AND EC.ShutOut=0
                        AND EG.GoodsNo in ({$货号str})
                        GROUP BY 
                                EG.GoodsNo,
                                EC.CustomerName
                        HAVING SUM(ECSD.Quantity)!=0
                
                        UNION ALL
                        
                        -- 在途库存 			
                        SELECT 
                            m.CustomerName,
                            m.货号,
                            0 AS 店铺库存,
                            SUM(m.Quantity) as 在途库存 
                        FROM												
                        (--仓库发货在途
                            SELECT  
                                    EC.CustomerName,
                                    EG.GoodsNo AS 货号,
                                    SUM(EDGD.Quantity) AS Quantity
                            FROM ErpDelivery ED 
                            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
                            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
                            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
                            WHERE ED.CodingCodeText='已审结'
                                    AND ED.IsCompleted=0
                                    AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
                                    AND EC.MathodId IN (4)
                                    AND EC.ShutOut=0
                                    AND EG.GoodsNo in ({$货号str})
                            GROUP BY  
                                    EG.GoodsNo,
                                    EC.CustomerName
                                    
                            UNION ALL
                
                            --店铺调拨在途
                            SELECT 
                                    EC.CustomerName,
                                    EG.GoodsNo AS 货号,
                                    SUM(EIGD.Quantity) AS Quantity
                            FROM ErpCustOutbound EI 
                            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
                            LEFT JOIN ErpCustOutboundGoodsDetail EIGD ON EIG.CustOutboundGoodsId=EIGD.CustOutboundGoodsId
                            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
                            WHERE EI.CodingCodeText='已审结'
                                    AND EI.IsCompleted=0
                                    AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                    AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                                    AND EC.MathodId IN (4)
                                    AND EC.ShutOut=0
                                    AND EG.GoodsNo in ({$货号str})
                            GROUP BY  
                                    EG.GoodsNo,
                                    EC.CustomerName
                        ) as m
                        GROUP BY 
                        m.CustomerName,
                        m.货号
                ) as t
                    GROUP BY 
                        T.CustomerName,
                        T.货号
        ";
        try {
            $select_店铺可用库存 = $this->db_sqlsrv->query($sql_店铺可用库存);
            if ($select_店铺可用库存) {
                // 删除历史数据
                $this->db_easyA->table('dd_tiaojia_customer_temp')->where(['id' => $id])->delete();
                // $this->db_easyA->execute('TRUNCATE dd_tiaojia_customer_temp;');
                $chunk_list = array_chunk($select_店铺可用库存, 500);
                // $this->db_easyA->startTrans();
    
                foreach($chunk_list as $key => $val) {
                    // 基础结果 
                    $insert = $this->db_easyA->table('dd_tiaojia_customer_temp')->strict(false)->insertAll($val);
                }
    
                $sql_分组货号 = "
                     select
                        id,货号
                    from dd_tiaojia_customer_temp
                    where id='{$id}'
                    group by 货号
                ";
                $select_分组货号 = $this->db_easyA->query($sql_分组货号);
                $分组货号str2 = "";
                foreach ($select_分组货号 as $key2 => $val2) {
                    if ($key2 + 1 == count($select_分组货号)) {
                        $分组货号str2 .= "'" . $val2['货号'] . "'";
                    } else {
                        $分组货号str2 .= "'" . $val2['货号'] . "',";
                    }
                }
                $sql_信息明细2 = "
                    SELECT 
                        '{$id}' as id,
                        EG.GoodsNo as 货号,
                        EG.GoodsId,
                        EGPT.UnitPrice as 零售价,
                        EGC.ColorDesc as 颜色,
                        EGI.Img
                    FROM ErpGoods AS EG
                    LEFT JOIN ErpGoodsColor AS EGC ON EG.GoodsId = EGC.GoodsId
                    LEFT JOIN ErpGoodsPriceType AS EGPT ON EG.GoodsId = EGPT.GoodsId AND EGPT.PriceId = 1
                    LEFT JOIN ErpGoodsImg AS EGI ON EG.GoodsId = EGI.GoodsId
                    where EG.GoodsNo in ($分组货号str2)
                ";
                $select_信息明细2 = $this->db_sqlsrv->query($sql_信息明细2);
                if ($select_信息明细2) {
                    $this->db_easyA->table('dd_tiaojia_goods_info')->where(['id' => $id])->delete();
                    $chunk_list2 = array_chunk($select_信息明细2, 500);
                    foreach($chunk_list2 as $key3 => $val3) {
                        // 基础结果 
                        $insert = $this->db_easyA->table('dd_tiaojia_goods_info')->strict(false)->insertAll($val3);
                    }

                    $sql_调价_零售价_颜色_图片 = "
                        update dd_tiaojia_customer_temp as c 
                        left join dd_tiaojia_goods_info as i on c.id = i.id and c.货号 = i.货号
                        left join dd_tiaojia_temp as t on c.id = t.id and c.货号 = t.货号  
                        SET
                            c.调价 = t.调价,
                            c.调价时间范围 = t.调价时间范围,
                            c.零售价 = i.零售价,
                            c.颜色 = i.颜色,
                            c.Img = i.Img
                        where 1
                    ";
                    $this->db_easyA->execute($sql_调价_零售价_颜色_图片);
                }
                // return true;
            } else {
                // return false;
            }
        } catch (\Throwable $th) {
            throw $th;
            // return false;
        }
        
    }
}
