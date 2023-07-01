<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\model;



use app\common\constants\MenuConstant;
use app\common\model\TimeModel;
use think\facade\Db;

class SystemMenu extends TimeModel
{

    protected $deleteTime = 'delete_time';

    public function getPidMenuList()
    {
        $list        = $this->field('id,pid,title')
            ->where([
                ['pid', '<>', MenuConstant::HOME_PID],
                ['status', '=', 1],
            ])
            ->select()
            ->toArray();
        $pidMenuList = $this->buildPidMenu(0, $list);
        $pidMenuList = array_merge([[
            'id'    => 0,
            'pid'   => 0,
            'title' => '顶级菜单',
        ]], $pidMenuList);
        return $pidMenuList;
    }

    protected function buildPidMenu($pid, $list, $level = 0)
    {
        $newList = [];
        foreach ($list as $vo) {
            if ($vo['pid'] == $pid) {
                $level++;
                foreach ($newList as $v) {
                    if ($vo['pid'] == $v['pid'] && isset($v['level'])) {
                        $level = $v['level'];
                        break;
                    }
                }
                $vo['level'] = $level;
                if ($level > 1) {
                    $repeatString = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $markString   = str_repeat("{$repeatString}├{$repeatString}", $level - 1);
                    $vo['title']  = $markString . $vo['title'];
                }
                $newList[] = $vo;
                $childList = $this->buildPidMenu($vo['id'], $list, $level);
                !empty($childList) && $newList = array_merge($newList, $childList);
            }

        }
        return $newList;
    }

    public function getPerformanceData($start,$end,$set_key = 1)
    {
        if(empty($start)){
            $start = date('Y-m-d',strtotime(date('Y-m-d').'-2day'));
        }
        if(empty($end)){
            $end = date('Y-m-d',strtotime(date('Y-m-d').'-1day'));
        }

        $sql = "
        SELECT 
         T1.State,
         T1.CustomerName,
         T.CustomerCode,
         T1.CustomItem19,
         T1.CustomItem18,
         T1.StoreArea,
         SUM(T.[quantity]) AS 有效件量,
         SUM(T.[count]) AS 有效单数,
         SUM(T.[sales_f]) 有效业绩,
         T1.[sales_all] AS 总业绩,
         SUM(T.[sales_f]) / (CASE WHEN SUM(T.[quantity]) = 0 THEN 1 ELSE SUM(T.[quantity]) END) AS 件单价,
         SUM(T.[sales_f]) / (CASE WHEN SUM(T.[count]) = 0 THEN 1 ELSE SUM(T.[count]) END) AS 客单价,
         SUM(T.[quantity]) / (CASE WHEN SUM(T.[count]) = 0 THEN 1 ELSE SUM(T.[count]) END) AS 连带率,
         T1.[sales_all] / T1.业绩贡献人数 AS 人效,
         CASE WHEN T1.[sales_all]=0 THEN NULL ELSE CONVERT(DECIMAL(10,2), (T1.sales_all - T1.cost_all) / T1.sales_all * 100) END AS profit_estimate -- 毛利率=（主营业务收入-主营业务成本）/主营业务收入×100%
        FROM 
        (
        SELECT  
            EC.CustomerId,
            EC.CustomItem19,
            EC.CustomItem18,
            EC.CustomerCode,
            EC.CustomerName,
            EC.CustomItem14,
            EC.StoreArea,
            EC.State,
            COUNT(DISTINCT ERG.SalesmanID) 业绩贡献人数,
         SUM(ERG.Quantity*ERG.DiscountPrice) AS sales_all,  -- 总业绩
         SUM(CASE WHEN EC.MathodId=4 THEN ERG.Quantity*EGPT.采购价 WHEN EC.MathodId=7 THEN ERG.Quantity*EGPT.分销价 END ) AS cost_all  -- 总成本
        FROM ErpRetail ER
        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
        LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
        LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
        LEFT JOIN (SELECT 
                EGPT.GoodsId,
                SUM(CASE WHEN EGPT.PriceName='采购价' THEN EGPT.UnitPrice ELSE NULL END) AS 采购价,
                SUM(CASE WHEN EGPT.PriceName='分销价' THEN EGPT.UnitPrice ELSE NULL END) AS 分销价
            FROM ErpGoodsPriceType EGPT
            GROUP BY EGPT.GoodsId) EGPT ON EGPT.GoodsId=ERG.GoodsId
        WHERE EC.ShutOut = 0
          AND ER.CodingCodeText='已审结'
          AND EC.MathodId=4
          -- AND EC.CustomerCode='Y0477'
          AND ER.RetailDate BETWEEN '{$start}'  AND '{$end}'
        GROUP BY  
            EC.State,
            EC.CustomerId,
            EC.CustomItem19,
            EC.CustomItem18,
            EC.CustomerCode,
            EC.CustomItem14,
            EC.StoreArea,
          EC.CustomerName
        ) T1 -- 此段是总业绩和销量
        LEFT JOIN 
        (
        SELECT  
          EC.CustomerId,
          EC.CustomerCode,
          EC.CustomerName,
          mathod.Mathod,
          ER.RetailID,
         SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END ) AS quantity,
         SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity*ERG.DiscountPrice ELSE 0 END ) AS sales_f,
          CASE WHEN SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END )>0 THEN 1 WHEN SUM(ERG.Quantity)<0 THEN -1 ELSE 0 END AS count
        FROM ErpRetail ER
        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
        LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
        LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
        LEFT JOIN ErpBaseCustomerMathod mathod ON EC.MathodId = mathod.MathodId
        WHERE EC.ShutOut = 0
          AND ER.CodingCodeText='已审结'
          -- AND EC.CustomerCode='Y0477'
          AND ER.RetailDate BETWEEN '{$start}'  AND '{$end}'
          AND ER.RetailID NOT IN (SELECT ER.RetailID FROM ErpRetail ER  LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE ERG.Status='退' AND ER.RetailDate BETWEEN '{$start}'  AND '{$end}' GROUP BY ER.RetailID )
          AND ERG.Status!='赠'
        GROUP BY 
          EC.CustomerId,
          EC.CustomerCode,
          EC.CustomerName,
          mathod.Mathod,
          ER.RetailID
        ) T ON T.CustomerCode=T1.CustomerCode   -- 此段是有效业绩和数量
        GROUP BY 
          T.CustomerCode,
          T1.State,
          T1.CustomerName,
          T.Mathod,
          T1.CustomItem19,
          T1.CustomItem18,
          T1.StoreArea,       
          T1.CustomerId,
          T1.[sales_all],
          T1.cost_all,
          T1.业绩贡献人数
        ORDER BY 
          T.Mathod,
          T1.[sales_all] desc
        ";
        // 执行查询
        $list = Db::connect('sqlsrv')->query($sql);
        if($set_key){
            $new_list = [];
            foreach ($list as $k => $v){
                $v['ranking'] = $k + 1;
                $new_list[$v['CustomerCode']] = $v;
            }
            return $new_list;
        }
        return $list;
    }
}