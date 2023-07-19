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
 * @ControllerAnnotation(title="基础表更新")
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

    // 门店业绩环比报表  http://www.easyadmin1.com/api/tableupdate/s113?date=2023-07-14
    public function s113($date = '')
    {
        // 编号
        $code = 'S113';
        echo $date = $date ? $date : date("Y-m-d", strtotime("-1 day")); 
        $sql = "
            SELECT
                IFNULL(经营属性, '总计') AS 性质,
                IFNULL(省份, '合计') AS 省份,
                店铺名称,
                concat(  round(  (SUM(今日流水)   / SUM( `环比流水` )   - 1) * 100 , 2),    '%') AS 今日环比,
                concat(  round(  (SUM( `本月累计流水` ) / SUM( `环比累计流水`) - 1 ) * 100 , 2),  '%') AS 月度环比,
                ROUND( SUM( 今日流水 ), 2 ) AS 今日流水,
                ROUND( SUM( `环比流水` ), 2 ) AS 环比流水,
                ROUND( SUM( `本月累计流水` ), 2 ) AS 本月累计流水,
                ROUND( SUM( `环比累计流水` ), 2 ) AS 环比累计流水,
                '{$date}' AS 更新日期 
            FROM
                cwl_dianpuyejihuanbi_handle 
            WHERE
                `use` = 1 
                AND 更新日期='{$date}'
            GROUP BY
                省份 
                WITH ROLLUP
        ";
        $list = $this->db_easyA->query($sql);

        if ($list) {
            // $insertData = $list;
            // foreach ($insertData as $key => $val) {
            //     $insertData[$key]['更新日期'] = $date;
            // }
            // 清空同一天
            $this->db_bi->table('sp_ww_s113')->where([
                ['更新日期', '=', $date]
            ])->delete();

            // 入库
            $this->db_bi->table('sp_ww_s113')->strict(false)->insertAll($list);
        }

        $sql2 = "
            SELECT
                IFNULL(经营属性, '总计') AS 经营属性,
                IFNULL(省份, '合计') AS 省份,
                IFNULL(店铺名称, '合计') AS 店铺名称,
                concat(  round(  (SUM(今日流水)   / SUM( `环比流水` )   - 1) * 100 , 2),    '%') AS 今日环比,
                concat(  round(  (SUM( `本月累计流水` ) / SUM( `环比累计流水`) - 1 ) * 100 , 2),  '%') AS 月度环比,
                ROUND( SUM( 今日流水 ), 2 ) AS 今日流水,
                ROUND( SUM( `环比流水` ), 2 ) AS 环比流水,
                ROUND( SUM( `本月累计流水` ), 2 ) AS 本月累计流水,
                ROUND( SUM( `环比累计流水` ), 2 ) AS 环比累计流水,
                '{$date}' AS 更新日期
            FROM
                cwl_dianpuyejihuanbi_handle 
            WHERE
                `use` = 1 
                AND 更新日期='{$date}'
            GROUP BY
                经营属性,省份,店铺名称 
                WITH ROLLUP
        ";
        $list2 = $this->db_easyA->query($sql2);
        if ($list2) {
            // $insertData = $list;
            // foreach ($insertData as $key => $val) {
            //     $insertData[$key]['更新日期'] = $date;
            // }
            // 清空同一天
            $this->db_bi->table('sp_ww_s113b')->where([
                ['更新日期', '=', $date]
            ])->delete();

            // 入库
            $this->db_bi->table('sp_ww_s113b')->strict(false)->insertAll($list2);
        }
    }

    public function s108A() {
        $sql3 = "
            SELECT
                IFNULL(SCL.`督导`,'总计') AS 督导,
                IFNULL(SCL.`省份`,'合计') AS 省份,
                CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
                CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
                SUM(SCM.`今日目标`) AS 今日目标,
                SUM(SCL.`今天流水`) AS 今天流水,
                SUM(SCM.`本月目标`) 本月目标,
                SUM(SCL.`本月流水`) 本月流水,
                SUM(SCL.`近七天日均`) AS 近七天日均,
                ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均,
                date_format(now(),'%Y-%m-%d %H:%i:%s') as 更新日期
                FROM sp_customer_liushui SCL
                LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
                where SCL.`经营模式`='直营'
                GROUP BY
                SCL.`督导`,
                SCL.`省份`
            WITH ROLLUP
        ";
        $select = $this->db_bi->query($sql3);

        if ($select) {
            $this->db_bi->execute('TRUNCATE sp_ww_s108a;');

            $select_chunk = array_chunk($select, 500);
    
            foreach($select_chunk as $key => $val) {
                $status = $this->db_bi->table('sp_ww_s108a')->strict(false)->insertAll($val);
            }
            // $this->db_bi->table('retail_2week_by_wangwei')->insertAll($select);
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'sp_ww_s108a 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'sp_ww_s108a 更新失败！'
            ]);   
        }
    }

    public function s110A() {
        $sql3 = "
            SELECT
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`,
            CONCAT(ROUND(SCL.`今天流水`/SCM.`今日目标`*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SCL.`本月流水`/SCM.`本月目标`*100,2),'%') AS 本月达成率,
            SCM.`今日目标`,
            SCL.`今天流水`,
            SCM.`本月目标`,
            SCL.`本月流水`,
            SCL.`近七天日均`,
            ROUND((SCM.`本月目标` - SCL.`本月流水`) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均,
            date_format(now(),'%Y-%m-%d %H:%i:%s') as 更新日期
            FROM sp_customer_liushui SCL
            LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            WHERE SCL.`经营模式`='直营'
            ORDER BY
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`
        ";
        $select = $this->db_bi->query($sql3);

        if ($select) {
            $this->db_bi->execute('TRUNCATE sp_ww_s110a;');

            $select_chunk = array_chunk($select, 500);
    
            foreach($select_chunk as $key => $val) {
                $status = $this->db_bi->table('sp_ww_s110a')->strict(false)->insertAll($val);
            }
            // $this->db_bi->table('retail_2week_by_wangwei')->insertAll($select);
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'sp_ww_s110a 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'sp_ww_s110a 更新失败！'
            ]);   
        }
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

    // 店铺预计库存到尺码齐码率
    public function customer_kc_sk_by_wangwei() {
        $sql = "
        --仓库可用库存数量到尺码齐码率
                SELECT 
                    EC.State 省份,
                    EC.CustomItem15,
                    EC.CustomItem17,
                    EC.CustomerName,
                    EG.TimeCategoryName1,
                    EG.TimeCategoryName2,
                    EG.CategoryName1 AS 一级分类,
                    EG.CategoryName2 AS 二级分类,
                    EG.CategoryName AS 分类,
                    EG.GoodsName,
                    EG.StyleCategoryName,
                    EG.GoodsNo,
                    EG.StyleCategoryName1,
                    EG.StyleCategoryName2,
                    EGPT.UnitPrice 零售价,
                    ISNULL(TT.Price,EGPT.UnitPrice) 当前零售价,
                    SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END) AS  [预计库存_00/28/37/44/100/160/S],
                    SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0 END) AS  [预计库存_29/38/46/105/165/M],
                    SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0 END) AS  [预计库存_30/39/48/110/170/L],
                    SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0 END) AS  [预计库存_31/40/50/115/175/XL],
                    SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0 END) AS  [预计库存_32/41/52/120/180/2XL],
                    SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0 END) AS  [预计库存_33/42/54/125/185/3XL],
                    SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0 END) AS  [预计库存_34/43/56/190/4XL],
                    SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0 END) AS  [预计库存_35/44/58/195/5XL],
                    SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0 END) AS  [预计库存_36/6XL],
                    SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END) AS [预计库存_38/7XL],
                    SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END) AS [预计库存_40],
                    SUM(T.Quantity) AS 预计库存Quantity,
                    CASE WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11111111111%' THEN 11 
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1111111111%' THEN 10 
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%111111111%' THEN 9
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11111111%' THEN 8
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1111111%' THEN 7
                        WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%111111%' THEN 6
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11111%' THEN 5
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1111%' THEN 4
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%111%' THEN 3
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11%' THEN 2
                            WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                                CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1%' THEN 1
                            ELSE 0
                        END AS 齐码情况
                FROM 
                (
                -- 店铺库存
                SELECT 
                    EC.CustomerId,
                    EC.CustomerName,
                    ECS.GoodsId,
                    ECSD.SizeId,
                    SUM(ECSD.Quantity) AS Quantity
                FROM ErpCustomerStock ECS 
                LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId = ECSD.StockId
                LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
                LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','初秋', '深秋','秋季', '初冬', '深冬', '冬季')
                AND EG.CategoryName1 = '鞋履'
                GROUP BY 
                    EC.CustomerId,
                    EC.CustomerName,
                    ECS.GoodsId,
                    ECSD.SizeId
                HAVING SUM(ECSD.Quantity)!=0

                UNION ALL 

                --仓库发货在途
                SELECT  
                    EC.CustomerId,
                    EC.CustomerName,
                    EDG.GoodsId,
                    EDGD.SizeId,
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
                    AND EC.MathodId IN (4,7)
                    AND EC.ShutOut=0
                    AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','初秋', '深秋','秋季', '初冬', '深冬', '冬季')
                    AND EG.CategoryName1 = '鞋履'
                GROUP BY  
                    EC.CustomerId,
                    EC.CustomerName,
                    EDG.GoodsId,
                    EDGD.SizeId
                    
                UNION ALL

                --店店调拨在途

                SELECT 
                    EC.CustomerId,
                    EC.CustomerName,
                    EIG.GoodsId,
                    EIGD.SizeId,
                    SUM(EIGD.Quantity) AS Quantity
                FROM ErpCustOutbound EI 
                LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
                LEFT JOIN ErpCustOutboundGoodsDetail EIGD ON EIG.CustOutboundGoodsId=EIGD.CustOutboundGoodsId
                LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
                LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
                WHERE EI.CodingCodeText='已审结'
                    AND EI.IsCompleted=0
                    AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                    AND EC.MathodId IN (4,7)
                    AND EC.ShutOut=0
                    AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','初秋', '深秋','秋季', '初冬', '深冬', '冬季')
                    AND EG.CategoryName1 = '鞋履'
                GROUP BY  
                    EC.CustomerId,
                    EC.CustomerName,
                    EIG.GoodsId,
                    EIGD.SizeId

                UNION ALL

                -- 已配未发
                SELECT  
                    EC.CustomerId,
                    EC.CustomerName,
                    ESG.GoodsId,
                    ESGD.SizeId,
                    SUM(ESGD.Quantity) AS Quantity
                FROM ErpSorting ES 
                LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
                LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID=ESGD.SortingGoodsID
                LEFT JOIN ErpCustomer EC ON EC.CustomerId=ES.CustomerId
                LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
                WHERE ES.IsCompleted=0
                    AND EC.MathodId IN (4,7)
                    AND EC.ShutOut=0
                    AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','初秋', '深秋','秋季', '初冬', '深冬', '冬季')
                    AND EG.CategoryName1 = '鞋履'
                GROUP BY	 
                    EC.CustomerId,
                    EC.CustomerName,
                    ESG.GoodsId,
                    ESGD.SizeId

                ) T
                LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId
                LEFT JOIN ErpGoods EG ON T.GoodsId=EG.GoodsId
                LEFT JOIN 
                (
                SELECT 
                                EC.CustomerId,
                                EC.CustomerCode,
                                EC.CustomerName,
                                EG.GoodsNo,
                                EG.GoodsId,
                                EPT.Price,
                                EPTT.BDate,
                                CONVERT(VARCHAR(10),EP.CreateTime,23) AS CreateTime,
                                Row_Number() OVER (partition by EPC.CustomerId,EPT.GoodsId ORDER BY EP.CreateTime desc) RN
                        FROM ErpPromotion EP
                        LEFT JOIN ErpPromotionCustomer EPC ON EP.PromotionId=EPC.PromotionId
                        LEFT JOIN ErpCustomer EC ON EPC.CustomerId=EC.CustomerId
                        LEFT JOIN ErpPromotionTypeEx1 EPT ON EP.PromotionId=EPT.PromotionId
                        LEFT JOIN ErpGoods EG ON EPT.GoodsId=EG.GoodsId
                        LEFT JOIN ErpPromotionTime  EPTT ON EP.PromotionId=EPTT.PromotionId 
                        WHERE  EP.PromotionTypeId=1
                            AND EP.IsDisable=0
                            AND EP.CodingCodeText='已审结'
                            AND EC.MathodId IN (7,4)
                            AND EC.ShutOut=0
                            AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏','春季','初春','正春','初秋', '深秋','秋季', '初冬', '深冬', '冬季')
                            AND EG.CategoryName1 = '鞋履'
                            AND CONVERT(VARCHAR,GETDATE(),23) BETWEEN EPTT.BDate AND EPTT.EDate
                            -- AND EG.GoodsNo='B12612015'
                ) TT ON T.CustomerId=TT.CustomerId  AND T.GoodsId=TT.GoodsId AND TT.RN= 1
                LEFT JOIN ErpGoodsPriceType EGPT ON T.GoodsId=EGPT.GoodsId
                LEFT JOIN ErpCustomer EC ON T.CustomerId = EC.CustomerId
                WHERE EGPT.PriceId=1
                -- AND TT.RN= 1
                GROUP BY 
                    EC.State,
                    EC.CustomItem15,
                    EC.CustomItem17,
                    EC.CustomerName,
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
                    EGPT.UnitPrice ,
                    TT.Price 
                ;
        ";
        $select = $this->db_sqlsrv->query($sql);

        if ($select) {
            $this->db_bi->execute('TRUNCATE customer_kc_sk_by_wangwei;');
            $this->db_bi->table('customer_kc_sk_by_wangwei')->insertAll($select);
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'customer_kc_sk_by_wangwei 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'customer_kc_sk_by_wangwei 更新失败！'
            ]);   
        }
    }

    public function retail_2week_by_wangwei() {
        $sql = "
            SELECT TOP
                    3000000
                    EC.State AS 省份,
                    EBC.Mathod AS 渠道属性,
                    EC.CustomItem15 AS 店铺云仓,
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
                    SUM ( ERG.Quantity * ERG.DiscountPrice ) / SUM (ERG.Quantity) AS 当前零售价,
                    SUM ( ERG.Quantity ) AS 销售数量,
                    SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
                FROM
                    ErpRetail AS ER
                    LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                    LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                    LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                    LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                WHERE
                    ER.CodingCodeText = '已审结'
                    AND ER.RetailDate >= DATEADD(DAY, -14, CAST(GETDATE() AS DATE))
                    AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                    -- AND EG.TimeCategoryName2 IN ( '初夏', '盛夏', '夏季' )
                    AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                    AND EC.CustomItem17 IS NOT NULL
                    AND EBC.Mathod IN ('直营', '加盟')
                    -- AND EG.TimeCategoryName1 IN ('2023')
                GROUP BY
                    EC.CustomItem17
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
                HAVING  SUM ( ERG.Quantity ) <> 0
        ";

        $select = $this->db_sqlsrv->query($sql);
        // dump($select);die;
        if ($select) {
            $this->db_bi->execute('TRUNCATE retail_2week_by_wangwei;');

            $select_chunk = array_chunk($select, 500);
    
            foreach($select_chunk as $key => $val) {
                $status = $this->db_bi->table('retail_2week_by_wangwei')->insertAll($val);
            }
            // $this->db_bi->table('retail_2week_by_wangwei')->insertAll($select);
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'retail_2week_by_wangwei 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'retail_2week_by_wangwei 更新失败！'
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
        $select_customer = $this->db_bi->table('customer')->where([
            ['Mathod', 'exp',  new Raw("IN ('直营', '加盟')")],
            ['Region', '<>', '闭店区']
        ])->select()->toArray();
        if (!$select_customer) {
            echo '没有数据更新';
            die;
        } 
        // $this->db_bi->getLastSql();
        if ($select_customer) {
            $this->db_easyA->table('customer')->where(1)->delete();

            $select_customer = array_chunk($select_customer, 500);
    
            foreach($select_customer as $key => $val) {
                $insert = $this->db_easyA->table('customer')->strict(false)->insertAll($val);
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
    }

    // 拉取 sjp_goods
    public function sjp_goods() {
        // 查询bi
        $select = $this->db_bi->table('sjp_goods')->where(1)->select()->toArray();
        if (!$select) {
            echo '没有数据更新';
            die;
        } 

        $handle = $this->db_easyA->table('sjp_goods')->where(1)->delete();

        $select = array_chunk($select, 1000);

        foreach($select as $key => $val) {
            $insert = $this->db_easyA->table('sjp_goods')->strict(false)->insertAll($val);
        }


        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => 'easyadmin2 sjp_goods 更新成功！'
        ]);
    }

    public function customer_first() {
        // 首单日期   
        $sql2 = "
            SELECT
                ER.CustomerName AS 店铺名称,
                (
                SELECT TOP
                    1 t1.RetailDate 
                FROM
                    ErpRetail t1
                    LEFT JOIN ErpCustomer AS t2 ON t1.CustomerId = t2.CustomerId 
                WHERE
                    t1.CustomerName = ER.CustomerName 
                    AND t2.CustomerCode = EC.CustomerCode 
                ORDER BY
                    t1.RetailDate ASC 
                ) AS 首单日期,
                EC.RegionId
                ,
                EC.CustomerCode 
            FROM
                ErpRetail AS ER
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId 
            WHERE
                ER.CodingCodeText = '已审结' 
                AND EC.ShutOut = 0 
                -- AND EC.RegionId <> 55 
                AND EBC.Mathod IN ( '直营', '加盟' )    
            GROUP BY
                ER.CustomerName,
                EC.RegionId,
                EC.CustomerCode
        ";
        // 首单日期
        $select_firstDate = $this->db_sqlsrv->query($sql2);

        // $handle = $this->db_easyA->table('customer_first')->where(1)->delete();
        $this->db_easyA->execute('TRUNCATE customer_first;');
        $this->db_bi->execute('TRUNCATE customer_regionid;');
        
        $insert_all = $this->db_easyA->table('customer_first')->strict(false)->insertAll($select_firstDate);
        $insert_all2 = $this->db_bi->table('customer_regionid')->strict(false)->insertAll($select_firstDate);
        if ($insert_all || $insert_all2) {
            // $this->db_bi->commit();    
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'easyadmin2 customer_first 更新成功！'
            ]);
        } else {
            // $this->db_bi->rollback();   
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'easyadmin2 customer_first 更新失败！'
            ]);
        }
    }

    // 原来 summer_report_2加了分类
    public function summer_report_3() {
        $sql = "
            -- s032
            --2022冬季数据
            WITH T1 AS 
            (
            --仓库库存 warehouse_stock
            SELECT 
                ROW_NUMBER() OVER (ORDER BY T1.ID) AS ID,
                T1.[云仓],
                T1.[风格],
                T1.[一级分类],
                T1.[二级分类],
                T1.[分类],
                T1.[仓库库存],
                T1.[仓库库存成本]
            FROM 
            (
            SELECT 
                ROW_NUMBER() OVER (ORDER BY T.ID) AS ID,
                CASE WHEN T.[云仓] IS NULL THEN '总计'  ELSE T.[云仓] END AS 云仓,
                CASE WHEN T.[风格] IS NULL THEN '合计'  ELSE T.[风格] END AS 风格,
                CASE WHEN T.[一级分类] IS NULL THEN '合计' ELSE T.[一级分类] END AS 一级分类,
                CASE WHEN T.[二级分类] IS NULL THEN '合计' ELSE T.[二级分类] END AS 二级分类,
                CASE WHEN T.[分类] IS NULL THEN '合计' ELSE T.[分类] END AS 分类,
                T.[仓库库存],
                T.[仓库库存成本]	
            FROM 
            (
            SELECT
                ROW_NUMBER() OVER (ORDER BY (SELECT 0)) AS ID,
                EW.WarehouseName AS 云仓,
                EG.StyleCategoryName AS 风格,
                eg.CategoryName1 AS 一级分类,
                eg.CategoryName2 AS 二级分类,
            -- 	cwl
                eg.CategoryName AS 分类,
                SUM ( Quantity ) AS 仓库库存,
                SUM ( Quantity * egc.ProductionCost ) AS 仓库库存成本 
            FROM ErpWarehouse EW 
            LEFT JOIN ErpGoods EG ON 1=1
            LEFT JOIN ErpWarehouseStock ews ON EW.WarehouseId=ews.WarehouseId AND EG.GoodsId=ews.GoodsId
            LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId 
            WHERE EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
            GROUP BY ROLLUP
                (EW.WarehouseName,
                eg.StyleCategoryName,
                eg.CategoryName1 ,
                eg.CategoryName2,
                eg.CategoryName)
                ) T
            WHERE T.[二级分类] IS NOT NULL 
                    OR T.[一级分类] IS NULL
            ) T1
            ),







            T2 AS 
            (
            --仓库可用库存  spring_warehouse_occupy_stock
                
            SELECT
                CASE WHEN WarehouseName IS NULL THEN '总计' ELSE WarehouseName END  AS 云仓,
                CASE WHEN StyleCategoryName IS NULL THEN '合计' ELSE StyleCategoryName END  AS 风格,
                CASE WHEN CategoryName1 IS NULL THEN '合计' ELSE CategoryName1 END AS 一级分类,
                CASE WHEN CategoryName2 IS NULL THEN '合计' ELSE CategoryName2 END AS 二级分类,
                CASE WHEN CategoryName IS NULL THEN '合计' ELSE CategoryName END AS 分类,
                SUM ( SumQuantity ) AS 仓库可用库存,
                SUM ( SumCost ) AS 仓库可用库存成本
            FROM
                (
                --仓库库存
            SELECT
                EW.WarehouseName ,
                EG.StyleCategoryName ,
                eg.CategoryName1 ,
                eg.CategoryName2 ,
                eg.CategoryName,
                SUM ( Quantity ) AS SumQuantity,
                SUM ( Quantity * egc.ProductionCost ) AS SumCost 
            FROM ErpWarehouse EW 
            LEFT JOIN ErpWarehouseStock ews ON EW.WarehouseId=ews.WarehouseId
            LEFT JOIN ErpGoods eg ON ews.GoodsId= eg.GoodsId
            LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId 
            WHERE
                EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
            GROUP BY 
                EW.WarehouseName,
                eg.StyleCategoryName,
                eg.CategoryName1 ,
                eg.CategoryName2,
                eg.CategoryName
            UNION ALL 
                --出货指令单占用库存
            SELECT
                EW.WarehouseName,
                EG.StyleCategoryName AS StyleCategoryName,
                eg.CategoryName1 ,
                eg.CategoryName2 ,
                eg.CategoryName,
                -SUM ( esg.Quantity ) AS SumQuantity,
                -SUM ( esg.Quantity* egc.ProductionCost ) AS SumCost 
                FROM ErpSorting es
                    LEFT JOIN ErpSortingGoods esg ON es.SortingID= esg.SortingID
                    LEFT JOIN ErpGoods eg ON esg.GoodsId= eg.GoodsId
                    LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId
                    LEFT JOIN ErpWarehouse EW ON ES.WarehouseId=EW.WarehouseId 
                WHERE	EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                    AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                    AND (es.CodingCode= 'StartNode1'
                                OR (es.CodingCode= 'EndNode2' AND es.IsCompleted= 0 )
                            )
                AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')		
                GROUP BY
                    EW.WarehouseName,
                eg.StyleCategoryName ,
                    eg.CategoryName1 ,
                    eg.CategoryName2,
                    eg.CategoryName
                UNION ALL
                --仓库出货单占用库存
                SELECT
                    EW.WarehouseName,
                EG.StyleCategoryName AS StyleCategoryName,
                    eg.CategoryName1 ,
                    eg.CategoryName2 ,
                    eg.CategoryName,
                    -SUM ( edg.Quantity ) AS SumQuantity,
                    -SUM ( edg.Quantity* egc.ProductionCost ) AS SumCost 
                FROM
                    ErpDelivery ed
                    LEFT JOIN ErpDeliveryGoods edg ON ed.DeliveryID= edg.DeliveryID
                    LEFT JOIN ErpGoods eg ON edg.GoodsId= eg.GoodsId
                    LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId 
                    LEFT JOIN ErpWarehouse EW ON ED.WarehouseId=EW.WarehouseId
                WHERE
                    ed.CodingCode= 'StartNode1' 
                    AND edg.SortingID IS NULL 
                    AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                    AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
                GROUP BY
                    EW.WarehouseName,
                    eg.StyleCategoryName ,
                    eg.CategoryName1 ,
                    eg.CategoryName2,
                    eg.CategoryName
                UNION ALL
                --采购退货指令单占用库存
                SELECT
                    EW.WarehouseName,
                    EG.StyleCategoryName AS StyleCategoryName,
                    eg.CategoryName1 ,
                    eg.CategoryName2 ,
                    eg.CategoryName,
                    -SUM ( eprng.Quantity ) AS SumQuantity,
                    -SUM ( eprng.Quantity* egc.ProductionCost ) AS SumCost 
                FROM
                    ErpPuReturnNotice eprn
                    LEFT JOIN ErpPuReturnNoticeGoods eprng ON eprn.PuReturnNoticeId= eprng.PuReturnNoticeId
                    LEFT JOIN ErpGoods eg ON eprng.GoodsId= eg.GoodsId
                    LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId 
                    LEFT JOIN ErpWarehouse EW ON eprn.WarehouseId=EW.WarehouseId
                WHERE
                    eprn.CodingCode= 'StartNode1' 
                    AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                    AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                    AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
                GROUP BY
                    EW.WarehouseName,
                    eg.StyleCategoryName ,
                    eg.CategoryName1 ,
                    eg.CategoryName2,
                    eg.CategoryName
                UNION ALL
                --采购退货单占用库存
                SELECT
                    EW.WarehouseName,
                    EG.StyleCategoryName AS StyleCategoryName,
                    eg.CategoryName1 ,
                    eg.CategoryName2 ,
                    eg.CategoryName,
                    -SUM ( epcrg.Quantity ) AS SumQuantity,
                    -SUM ( epcrg.Quantity* egc.ProductionCost ) AS SumCost 
                FROM
                    ErpPurchaseReturn epcr
                    LEFT JOIN ErpPurchaseReturnGoods epcrg ON epcr.PurchaseReturnId= epcrg.PurchaseReturnId
                    LEFT JOIN ErpGoods eg ON epcrg.GoodsId= eg.GoodsId
                    LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId 
                    LEFT JOIN ErpWarehouse EW ON epcr.WarehouseId=EW.WarehouseId
                WHERE
                    epcr.CodingCode= 'StartNode1' 
                    AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                    AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                    AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
                GROUP BY
                    EW.WarehouseName,
                    eg.StyleCategoryName ,
                    eg.CategoryName1 ,
                    eg.CategoryName2,
                    eg.CategoryName
                UNION ALL
                --仓库调拨占用库存
                SELECT
                    EW.WarehouseName,
                    EG.StyleCategoryName AS StyleCategoryName  ,
                    eg.CategoryName1 ,
                    eg.CategoryName2 ,
                    eg.CategoryName,
                    -SUM ( EIGD.Quantity ) AS SumQuantity,
                    -SUM ( EIGD.Quantity* egc.ProductionCost ) AS SumCost 
                FROM
                    ErpInstruction EI
                    LEFT JOIN ErpInstructionGoods EIG ON EI.InstructionId= EIG.InstructionId
                    LEFT JOIN ErpInstructionGoodsDetail EIGD ON EIG.InstructionGoodsId=EIGD.InstructionGoodsId
                    LEFT JOIN ErpGoods eg ON EIG.GoodsId= eg.GoodsId
                    LEFT JOIN ErpGoodsCost egc ON EIG.GoodsId= egc.GoodsId 
                    LEFT JOIN ErpWarehouse EW ON EI.OutItemId=EW.WarehouseId 
                WHERE EI.Type= 1
                    AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                    AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                    AND (EI.CodingCode= 'StartNode1' OR (EI.CodingCode= 'EndNode2' AND EI.IsCompleted=0 ))
                    AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
                GROUP BY
                    EW.WarehouseName,
                    eg.StyleCategoryName ,
                    eg.CategoryName1 ,
                    eg.CategoryName2,
                    eg.CategoryName
            ) AS A 
            GROUP BY ROLLUP
                (WarehouseName,
                StyleCategoryName,
                CategoryName1,
                CategoryName2,
                CategoryName)
            HAVING A.CategoryName2!='合计' OR A.CategoryName1 IS NULL

            ),



            T3 AS 
            (
            --收仓在途  spring_warehouse_intransit_stock

            SELECT 
                CASE WHEN EW.WarehouseName IS NULL THEN '总计' ELSE EW.WarehouseName END  AS 云仓,
                CASE WHEN eg.StyleCategoryName IS NULL THEN '合计' ELSE eg.StyleCategoryName END  AS 风格,
                CASE WHEN eg.CategoryName1 IS NULL THEN '合计' ELSE eg.CategoryName1 END AS 一级分类,
                CASE WHEN eg.CategoryName2 IS NULL THEN '合计' ELSE eg.CategoryName2 END AS 二级分类,
                CASE WHEN eg.CategoryName IS NULL THEN '合计' ELSE eg.CategoryName END AS 分类,
                SUM(ERG.Quantity) AS 收仓在途,
                SUM(ERG.Quantity*EGC.ProductionCost) AS 收仓在途成本
            FROM ErpCustomer EC 
            LEFT JOIN ErpReturn ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpReturnGoods  ERG ON ER.ReturnID=ERG.ReturnID
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            LEFT JOIN ErpGoodsCost	EGC	on EG.GoodsId=EGC.GoodsId 
            LEFT JOIN ErpWarehouse EW ON ER.WarehouseId=EW.WarehouseId 
            WHERE EC.MathodId IN (4,7) 
                    AND EC.ShutOut=0
                    AND ER.CodingCode='EndNode2'
                    AND (ER.IsCompleted IS NULL OR ER.IsCompleted=0)
                    AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                    AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')	
                    AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
            GROUP BY ROLLUP
                (EW.WarehouseName,
                eg.StyleCategoryName,
                eg.CategoryName1,
                eg.CategoryName2,
                eg.CategoryName)
            ),






            T4 AS 
            (
            --已配未发
            SELECT  
                CASE WHEN EC.CustomItem15 IS NULL THEN '总计' ELSE EC.CustomItem15 END  AS 云仓,
                CASE WHEN EG.StyleCategoryName IS NULL THEN '合计' ELSE EG.StyleCategoryName END  AS 风格,
                CASE WHEN EG.CategoryName1 IS NULL THEN '合计' ELSE EG.CategoryName1 END AS 一级分类,
                CASE WHEN EG.CategoryName2 IS NULL THEN '合计' ELSE EG.CategoryName2 END AS 二级分类,
                CASE WHEN EG.CategoryName IS NULL THEN '合计' ELSE EG.CategoryName END AS 分类,
                SUM(ESG.Quantity) AS 已配未发,
                SUM(ESG.Quantity*EGC.ProductionCost) AS 已配未发成本
            FROM ErpCustomer EC
            LEFT JOIN ErpSorting ES ON EC.CustomerId=ES.CustomerId
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            LEFT JOIN ErpGoodsCost	EGC	on ESG.GoodsId=EGC.GoodsId
            LEFT JOIN ErpWarehouse EW ON ES.WarehouseId=EW.WarehouseId 
            WHERE EC.MathodId IN (4,7) 
                AND EC.ShutOut=0
                AND ES.IsCompleted=0
                AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND EW.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044')
            GROUP BY ROLLUP
                (EC.CustomItem15,
                eg.StyleCategoryName,
                eg.CategoryName1,
                eg.CategoryName2,
                eg.CategoryName)
            ),



            T5 AS
            (
            --销量情况

            SELECT  
                CASE WHEN EC.CustomItem15 IS NULL THEN '总计' ELSE EC.CustomItem15 END  AS 云仓,
                CASE WHEN EG.StyleCategoryName IS NULL THEN '合计' ELSE EG.StyleCategoryName END  AS 风格,
                CASE WHEN EG.CategoryName1 IS NULL THEN '合计' ELSE EG.CategoryName1 END AS 一级分类,
                CASE WHEN EG.CategoryName2 IS NULL THEN '合计' ELSE EG.CategoryName2 END AS 二级分类,
                CASE WHEN EG.CategoryName IS NULL THEN '合计' ELSE EG.CategoryName END AS 分类,
                SUM(CASE WHEN (CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,GETDATE()-7,23) AND CONVERT(VARCHAR,GETDATE()-1,23)) THEN ERG.Quantity ELSE 0 END) AS 最后一周销,
                SUM(CASE WHEN (CONVERT(VARCHAR,ER.RetailDate,23)=CONVERT(VARCHAR,GETDATE()-1,23)) THEN ERG.Quantity ELSE 0 END) AS 昨天销,
                SUM(CASE WHEN (CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,GETDATE()-7,23) AND CONVERT(VARCHAR,GETDATE()-1,23)) THEN ERG.Quantity ELSE 0 END) AS 前一周销量,
                SUM(CASE WHEN (CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,GETDATE()-14,23) AND CONVERT(VARCHAR,GETDATE()-8,23)) THEN ERG.Quantity ELSE 0 END) AS 前两周销量,
                SUM(CASE WHEN (CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,GETDATE()-21,23) AND CONVERT(VARCHAR,GETDATE()-15,23)) THEN ERG.Quantity ELSE 0 END) AS 前三周销量,
                SUM(CASE WHEN (CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,GETDATE()-28,23) AND CONVERT(VARCHAR,GETDATE()-22,23)) THEN ERG.Quantity ELSE 0 END) AS 前四周销量,
                SUM(ERG.Quantity) AS 累计销售,
                SUM(ERG.Quantity*EGC.ProductionCost) AS 累销成本
            FROM ErpCustomer EC 
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            LEFT JOIN ErpGoodsCost	EGC	on ERG.GoodsId=EGC.GoodsId
            WHERE EC.MathodId IN (4,7) 
                AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND ER.CodingCode='EndNode2'
                AND EC.CustomItem15 IN ('广州云仓','南昌云仓','长沙云仓','武汉云仓','贵阳云仓')
            GROUP BY ROLLUP
                (EC.CustomItem15,
                eg.StyleCategoryName,
                eg.CategoryName1,
                eg.CategoryName2,
                eg.CategoryName)
            ),





            T6 AS 
            (
            --店铺在途库存
            SELECT 
                    ISNULL(CustomItem15,'总计') AS 云仓,
                    ISNULL(风格,'合计') AS 风格,
                    ISNULL(一级分类,'合计') AS 一级分类,
                    ISNULL(二级分类,'合计') AS 二级分类,
                    ISNULL(分类,'合计') AS 分类,
                    SUM(Quantity) AS 在途库存数量,
                    SUM(ProductionCost) AS 在途成本
            FROM 
            (SELECT 
                EC.CustomItem15,
                eg.StyleCategoryName  AS 风格,
                eg.CategoryName1 AS 一级分类,
                eg.CategoryName2 AS 二级分类,
                eg.CategoryName AS 分类,
                SUM(EDG.Quantity) AS Quantity,
                SUM(EDG.Quantity*EGC.ProductionCost) AS ProductionCost
            FROM ErpCustomer EC 
            LEFT JOIN ErpDelivery ED ON EC.CustomerId=ED.CustomerId
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            LEFT JOIN ErpGoods EG ON EDG.GoodsId= EG.GoodsId
            LEFT JOIN ErpGoodsCost	EGC	on EG.GoodsId=EGC.GoodsId
            WHERE EC.MathodId IN (4,7) 
                AND EC.ShutOut=0
                AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND ED.CodingCode='EndNode2'
                AND ED.IsCompleted=0
                AND ED.DeliveryID NOT IN (SELECT DeliveryId FROM ErpCustReceipt WHERE CodingCodeText='已审结' AND DeliveryId IS NOT NULL )	
            GROUP BY
                EC.CustomItem15,
                eg.StyleCategoryName,
                eg.CategoryName1,
                eg.CategoryName2,
                eg.CategoryName
                
            UNION ALL
            SELECT 
                EC.CustomItem15,
                eg.StyleCategoryName  AS 风格,
                eg.CategoryName1 AS 一级分类,
                eg.CategoryName2 AS 二级分类,
                eg.CategoryName AS 分类,
                SUM(EIG.Quantity) AS Quantity,
                SUM(EIG.Quantity*EGC.ProductionCost) AS ProductionCost
            FROM ErpCustomer EC 
            LEFT JOIN ErpCustOutbound EI ON EI.InCustomerId=EC.CustomerId
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId
            LEFT JOIN ErpGoodsCost	EGC	on EG.GoodsId=EGC.GoodsId
            WHERE EC.MathodId IN (4,7)
                AND EC.ShutOut=0
                AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
                AND EI.CodingCodeText='已审结'
                AND EI.IsCompleted=0
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )		
            GROUP BY
                EC.CustomItem15,
                eg.StyleCategoryName,
                eg.CategoryName1,
                eg.CategoryName2,
                eg.CategoryName
            ) AS A
            GROUP BY ROLLUP
                    (CustomItem15,
                    风格,
                    一级分类,
                    二级分类,
                    分类)
            ),





            T7 AS
            (
            --店铺库存数  spring_customer_stock
            SELECT
                ROW_NUMBER() OVER (ORDER BY T2.ID) AS ID,
                T2.[云仓],
                T2.[风格],
                T2.[一级分类],
                T2.[二级分类],
                T2.[分类],
                T2.[店库存数量],
                T2.[店铺库存成本]
            FROM
            (
            SELECT
                ROW_NUMBER() OVER (ORDER BY (select 0)) AS ID,
                ISNULL(EC.CustomItem15,'总计') AS 云仓,
                ISNULL(eg.StyleCategoryName,'合计') AS 风格,
                ISNULL(eg.CategoryName1,'合计') AS 一级分类,
                ISNULL(eg.CategoryName2,'合计') AS 二级分类,
                ISNULL(eg.CategoryName,'合计') AS 分类,
                SUM(ECS.Quantity) AS 店库存数量,
                SUM(ECS.Quantity*EGC.ProductionCost) AS 店铺库存成本
            FROM ErpCustomer EC 
            JOIN ErpCustomerStock ECS ON EC.CustomerId=ECS.CustomerId
            JOIN ErpGoods EG ON ECS.GoodsId= EG.GoodsId
            JOIN ErpGoodsCost	EGC	on EG.GoodsId=EGC.GoodsId
            WHERE EC.MathodId IN (4,7) 
                AND EC.ShutOut=0
                AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
            GROUP BY ROLLUP
                (EC.CustomItem15,
                eg.StyleCategoryName,
                eg.CategoryName1,
                eg.CategoryName2,
                eg.CategoryName)
            ) T2
            WHERE T2.[分类]!='合计' OR T2.[二级分类]!='合计' OR T2.[一级分类]='合计'
            ),


            T8 AS
            (
            --仓仓调拨在途
            SELECT 
                CASE WHEN T.[云仓] IS NULL THEN '总计'  ELSE T.[云仓] END AS 云仓,
                CASE WHEN T.[风格] IS NULL THEN '合计'  ELSE T.[风格] END AS 风格,
                CASE WHEN T.[一级分类] IS NULL THEN '合计' ELSE T.[一级分类] END AS 一级分类,
                CASE WHEN T.[二级分类] IS NULL THEN '合计' ELSE T.[二级分类] END AS 二级分类,
                CASE WHEN T.[分类] IS NULL THEN '合计' ELSE T.[分类] END AS 分类,
                T.[仓库在途库存],
                T.[仓库在途库存成本]
            FROM 
            (
            SELECT
                EW.WarehouseName AS 云仓,
                EG.StyleCategoryName AS 风格,
                eg.CategoryName1 AS 一级分类,
                eg.CategoryName2 AS 二级分类,
                eg.CategoryName AS 分类,
                SUM ( Quantity ) AS 仓库在途库存,
                SUM ( Quantity * egc.ProductionCost ) AS 仓库在途库存成本 
            FROM ErpOutbound EO
            LEFT JOIN ErpOutboundGoods EOG ON EO.OutboundId=EOG.OutboundId
            LEFT JOIN ErpGoods eg ON EOG.GoodsId= eg.GoodsId
            LEFT JOIN ErpGoodsCost egc ON eg.GoodsId= egc.GoodsId 
            LEFT JOIN ErpWarehouse EW ON EO.InWarehouseId=EW.WarehouseId
            WHERE EO.CodingCode= 'EndNode2' 
                AND EO.IsCompleted=0
                AND EO.OutboundId  NOT IN (SELECT ER.OutboundId FROM ErpReceipt ER WHERE ER.Type=2 )
                AND EG.TimeCategoryName1= 2023 
                AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
            GROUP BY ROLLUP
                (EW.WarehouseName,
                eg.StyleCategoryName,
                eg.CategoryName1 ,
                eg.CategoryName2,
                eg.CategoryName)
                ) T
            WHERE T.[二级分类] IS NOT NULL 
                    OR T.[一级分类] IS NULL

            )


            --关联合并
            SELECT 
                T1.ID,
                T1.[云仓],
                T1.[风格],
                T1.[一级分类],
                T1.[二级分类],
            -- 	cwl
                T1.[分类],
                ISNULL(T1.[仓库库存],0) + ISNULL(T3.[收仓在途],0) + ISNULL(T8.[仓库在途库存],0) + ISNULL(T7.[店库存数量],0) + ISNULL(T6.[在途库存数量],0) + ISNULL(T5.[累计销售],0) AS 采购入库数,
                T1.[仓库库存],
                T2.[仓库可用库存],
                T1.[仓库库存成本],
                T3.[收仓在途],
                T3.[收仓在途成本],
                T4.[已配未发],
                T5.[最后一周销],
                T5.[昨天销],
                T5.[累计销售],
                CONVERT(DECIMAL(10,2),T5.[累销成本]/10000) AS 累销成本,
                T6.[在途库存数量],
                T7.[店库存数量],
                ISNULL(T1.[仓库库存],0) + ISNULL(T3.[收仓在途],0) + ISNULL(T8.[仓库在途库存],0) + ISNULL(T7.[店库存数量],0) + ISNULL(T6.[在途库存数量],0) AS 合计库存数 ,
                CONCAT(CONVERT(DECIMAL(10,2),(ISNULL(T1.[仓库库存成本],0) + ISNULL(T3.[收仓在途成本],0) + ISNULL(T8.[仓库在途库存成本],0) + ISNULL(T7.[店铺库存成本],0) + ISNULL(T6.[在途成本],0)) / 
                (SELECT ISNULL(T1.[仓库库存成本],0) + ISNULL(T3.[收仓在途成本],0) + ISNULL(T8.[仓库在途库存成本],0) + ISNULL(T7.[店铺库存成本],0) + ISNULL(T6.[在途成本],0) 
                        FROM T1 
                    LEFT JOIN T7 ON T1.[云仓]=T7.[云仓] AND T1.[风格]=T7.[风格] AND T1.[一级分类]=T7.[一级分类] AND T1.[二级分类]=T7.[二级分类]
                    LEFT JOIN T3 ON T1.[云仓]=T3.[云仓] AND T1.[风格]=T3.[风格] AND T1.[一级分类]=T3.[一级分类] AND T1.[二级分类]=T3.[二级分类]
                    LEFT JOIN T6 ON T1.[云仓]=T6.[云仓] AND T1.[风格]=T6.[风格] AND T1.[一级分类]=T6.[一级分类] AND T1.[二级分类]=T6.[二级分类]
                    LEFT JOIN T8 ON T1.[云仓]=T8.[云仓] AND T1.[风格]=T8.[风格] AND T1.[一级分类]=T8.[一级分类] AND T1.[二级分类]=T8.[二级分类] 
                    WHERE T7.[云仓]='总计' ) *100),'%') AS 合计库存数占比,
                CONVERT(DECIMAL(10,2),(ISNULL(T1.[仓库库存成本],0) + ISNULL(T3.[收仓在途成本],0) + ISNULL(T8.[仓库在途库存成本],0) + ISNULL(T7.[店铺库存成本],0) + ISNULL(T6.[在途成本],0))/10000) AS 合计库存成本 ,
                CASE WHEN ISNULL(T1.[仓库库存],0) + ISNULL(T3.[收仓在途],0) + ISNULL(T8.[仓库在途库存],0) + ISNULL(T7.[店库存数量],0) + ISNULL(T6.[在途库存数量],0) + ISNULL(T5.[累计销售],0)=0  THEN NULL 
                ELSE CONCAT(CONVERT(DECIMAL(10,1),ISNULL(T5.[累计销售],0) / ( ISNULL(T1.[仓库库存],0) + ISNULL(T3.[收仓在途],0) + ISNULL(T8.[仓库在途库存],0) + ISNULL(T7.[店库存数量],0) + ISNULL(T6.[在途库存数量],0) + ISNULL(T5.[累计销售],0) ) *100),'%') END   AS 数量售罄率,
                CASE WHEN ISNULL(T1.[仓库库存成本],0) + ISNULL(T3.[收仓在途成本],0) + ISNULL(T8.[仓库在途库存成本],0) + ISNULL(T7.[店铺库存成本],0) + ISNULL(T6.[在途成本],0) + ISNULL(T5.[累销成本],0)=0 THEN NULL
                ELSE CONCAT(CONVERT(DECIMAL(10,1),ISNULL(T5.[累销成本],0) / ( ISNULL(T1.[仓库库存成本],0) + ISNULL(T3.[收仓在途成本],0) + ISNULL(T8.[仓库在途库存成本],0) + ISNULL(T7.[店铺库存成本],0) + ISNULL(T6.[在途成本],0) + ISNULL(T5.[累销成本],0) )*100),'%') END AS 成本售罄率,
                T5.[前四周销量],
                T5.[前三周销量],
                T5.[前两周销量],
                T5.[前一周销量],
                CASE WHEN ISNULL(T5.[前一周销量],0) + ISNULL(T5.[前两周销量],0) + ISNULL(T5.[前三周销量],0)=0 /*OR T5.[前一周销量] IS NULL*/ THEN NULL ELSE ROUND((ISNULL(T1.[仓库库存],0) + ISNULL(T3.[收仓在途],0) + ISNULL(T7.[店库存数量],0) + ISNULL(T6.[在途库存数量],0))/((ISNULL(T5.[前一周销量],0) + ISNULL(T5.[前两周销量],0) + ISNULL(T5.[前三周销量],0))/3),1) END AS 周转周,
                CONVERT(VARCHAR,GETDATE(),23) AS 更新日期	
            FROM T1 
            LEFT JOIN T7 ON T1.[云仓]=T7.[云仓] AND T7.[风格]=T1.[风格] AND T7.[一级分类]=T1.[一级分类] AND T7.[二级分类]=T1.[二级分类] AND T7.[分类]=T1.[分类]
            LEFT JOIN T2 ON T1.[云仓]=T2.[云仓] AND T1.[风格]=T2.[风格] AND T1.[一级分类]=T2.[一级分类] AND T1.[二级分类]=T2.[二级分类] AND T1.[分类]=T2.[分类]
            LEFT JOIN T3 ON T1.[云仓]=T3.[云仓] AND T1.[风格]=T3.[风格] AND T1.[一级分类]=T3.[一级分类] AND T1.[二级分类]=T3.[二级分类] AND T1.[分类]=T3.[分类]
            LEFT JOIN T4 ON T1.[云仓]=T4.[云仓] AND T1.[风格]=T4.[风格] AND T1.[一级分类]=T4.[一级分类] AND T1.[二级分类]=T4.[二级分类] AND T1.[分类]=T4.[分类]
            LEFT JOIN T5 ON T1.[云仓]=T5.[云仓] AND T1.[风格]=T5.[风格] AND T1.[一级分类]=T5.[一级分类] AND T1.[二级分类]=T5.[二级分类] AND T1.[分类]=T5.[分类]
            LEFT JOIN T6 ON T1.[云仓]=T6.[云仓] AND T1.[风格]=T6.[风格] AND T1.[一级分类]=T6.[一级分类] AND T1.[二级分类]=T6.[二级分类] AND T1.[分类]=T6.[分类]
            LEFT JOIN T8 ON T1.[云仓]=T8.[云仓] AND T1.[风格]=T8.[风格] AND T1.[一级分类]=T8.[一级分类] AND T1.[二级分类]=T8.[二级分类] AND T1.[分类]=T8.[分类]
            ORDER BY T1.ID
        ";

        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            $this->db_bi->table('summer_report_3')->where([
                ['更新日期', '=', date('Y-m-d')]
            ])->delete();
            $select_chunk = array_chunk($select, 500);

            // echo '<pre>';
            // print_r($weishouhuo);
            $res_weishouhou = true;
    
            foreach($select_chunk as $key => $val) {
                $status = $this->db_bi->table('summer_report_3')->insertAll($val);
            }
            
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'summer_report_3 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'summer_report_3 更新失败！'
            ]);
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


    // 一年所有周 周销 sqlserver
    public function year7day() {
        $sql = "
                SELECT TOP
                100000 EBC.Mathod AS 渠道性质,
                DATEPART( week, ER.RetailDate ) AS 周次,
            -- 								EC.CustomItem17 AS 商品负责人,
            -- 	CONVERT ( VARCHAR ( 10 ), ER.RetailDate, 23 ) AS 销售年份,
            -- 	DATEPART( yy, ER.RetailDate ) AS 销售年份,
                EG.TimeCategoryName1 AS 商品年份,
                CONVERT(VARCHAR(10), DATEADD(day, -(DATEPART(dw, ER.RetailDate)-1), ER.RetailDate), 23) as 开始,
                CONVERT(VARCHAR(10), DATEADD(day, 7-(DATEPART(dw, ER.RetailDate)), ER.RetailDate), 23) as 结束,
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
                    '冬季' ELSE EG.TimeCategoryName2 
                END AS 季节归集,
                EG.StyleCategoryName AS 风格,
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 小类,
                
                SUM ( ERG.Quantity ) AS 销售数量,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额 
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId 
            WHERE
                ER.CodingCodeText = '已审结' 
                AND ER.RetailDate >= '2021-01-01' 
                AND ER.RetailDate < '2021-12-31' 
                AND EC.CustomItem17 IS NOT NULL 
                AND EBC.Mathod IN ( '直营', '加盟' ) 
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
            GROUP BY
            -- 	ER.RetailDate,
                DATEPART( yy, ER.RetailDate ),
                DATEPART( week, ER.RetailDate ),
                CONVERT(VARCHAR(10), DATEADD(day, -(DATEPART(dw, ER.RetailDate)-1), ER.RetailDate), 23),
                CONVERT(VARCHAR(10), DATEADD(day, 7-(DATEPART(dw, ER.RetailDate)), ER.RetailDate), 23),
                EBC.Mathod,
                EG.TimeCategoryName1,
                EG.TimeCategoryName2,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.StyleCategoryName
            ORDER BY
                DATEPART( week, ER.RetailDate ),
                EBC.Mathod,
                EG.StyleCategoryName,
                EG.CategoryName1,
                EG.CategoryName2        
        
        ";
    }

}
