<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpLypPuhuoTiGoodsModel;
//可以凌晨 00:01开始跑（预计半小时跑完）
class Puhuo_liangzhou extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo')
            ->setDescription('the Puhuo command');
    }

    protected function execute(Input $input, Output $output) {

        ini_set('memory_limit','2048M');
        $db = Db::connect("mysql");
        
        $data = $this->get_kl_data();
        if ($data) {
            
            //先清空旧数据再跑
            $db->Query("truncate table sp_lyp_puhuo_liangzhou;");
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                $insert = $db->table('sp_lyp_puhuo_liangzhou')->strict(false)->insertAll($val);
            }


            //2周销skc数 处理
            $data = $this->get_liangzhou_data();
            // print_r($data);die;
            $db->Query("truncate table sp_lyp_puhuo_liangzhou_skc;");
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                $insert = $db->table('sp_lyp_puhuo_liangzhou_skc')->strict(false)->insertAll($val);
            }

        }
        echo 'okk';die;
        
    }

    protected function get_kl_data() {

        $sql = "SELECT 
        T1.CustomItem15 AS yuncang,
        T1.CustomerName,
        T1.[GoodsNo],
        T1.[TimeCategoryName1],
        T1.[Season],
        T1.[CategoryName1],
        T1.[CategoryName2],
        T1.[StyleCategoryName],
        T2.[00/28/37/44/100/160/S],
        T2.[29/38/46/105/165/M],
        T2.[30/39/48/110/170/L],
        T2.[31/40/50/115/175/XL],
        T2.[32/41/52/120/180/2XL],
        T2.[33/42/54/125/185/3XL],
        T2.[34/43/56/190/4XL],
        T2.[35/44/58/195/5XL],
        T2.[36/6XL],
        T2.[38/7XL],
        T2.[40] AS _40,
        T2.amount,
        T2.money,
        T2.CustomerGrade,
        CONVERT(VARCHAR(10),T1.StockDate,23) AS listing_date,
        DATEDIFF(day, T1.StockDate, GETDATE()) AS listing_days
        
    FROM
    (
    SELECT 
        T.CustomItem15,
        T.CustomerName,
        T.[GoodsNo],
        T.[TimeCategoryName1],
        T.[Season],
        T.[CategoryName1],
        T.[CategoryName2],
        T.[StyleCategoryName],
        MIN(T.StockDate) AS StockDate
    FROM 
    (
    SELECT 
        EC.CustomItem15,
        EC.CustomerName,
        EG.GoodsNo AS GoodsNo,
        EG.TimeCategoryName1 AS TimeCategoryName1,
        --EG.TimeCategoryName2 AS 二级时间分类,
        
        CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
                 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
                 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
                 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
                 ELSE EG.TimeCategoryName2 
      END AS Season,
        
        EG.CategoryName1 AS CategoryName1,
        EG.CategoryName2 AS CategoryName2,
        EG.StyleCategoryName AS StyleCategoryName,
        MIN(ECS.StockDate) AS StockDate
    FROM ErpCustomerStock ECS
    LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
    LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
    WHERE ((EG.TimeCategoryName1=2022 AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%'))
                    OR (EG.TimeCategoryName1=2023 ))
        AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
        AND EC.ShutOut=0
        AND EC.MathodId IN (4,7)
    GROUP BY 
        EC.CustomItem15,
        EC.CustomerName,
        EG.GoodsNo,
        EG.TimeCategoryName1,
        EG.TimeCategoryName2,
        EG.CategoryName1,
        EG.CategoryName2,
        EG.StyleCategoryName
    
    UNION ALL
    SELECT 
        EC.CustomItem15,
        EC.CustomerName,
        EG.GoodsNo,
        EG.TimeCategoryName1 AS TimeCategoryName1,
        --EG.TimeCategoryName2 AS 二级时间分类,
        
        CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
                 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
                 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
                 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
                 ELSE EG.TimeCategoryName2 
      END AS Season,
        
        EG.CategoryName1 AS CategoryName1,
        EG.CategoryName2 AS CategoryName2,
        EG.StyleCategoryName AS StyleCategoryName,
        MIN(ES.CreateTime)
    FROM ErpCustomer EC
    LEFT JOIN ErpSorting ES ON EC.CustomerId=ES.CustomerId
    LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
    LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
    WHERE EC.ShutOut=0
        AND EC.MathodId IN (4,7)
        AND ((EG.TimeCategoryName1=2022 AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%'))
                    OR (EG.TimeCategoryName1=2023 ))
        AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
    GROUP BY 
        EC.CustomItem15,
        EC.CustomerName,
        EG.GoodsNo,
        EG.TimeCategoryName1,
        EG.TimeCategoryName2,
        EG.CategoryName1,
        EG.CategoryName2,
        EG.StyleCategoryName
        
    UNION ALL 
    SELECT
        EC.CustomItem15,
        EC.CustomerName,
        EG.GoodsNo,
        EG.TimeCategoryName1 AS TimeCategoryName1,
        --EG.TimeCategoryName2 AS 二级时间分类,
        
        CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
                 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
                 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
                 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
                 ELSE EG.TimeCategoryName2 
      END AS Season,
        
        EG.CategoryName1 AS CategoryName1,
        EG.CategoryName2 AS CategoryName2,
        EG.StyleCategoryName AS StyleCategoryName,
        MIN(EI.CreateTime)
    FROM ErpCustomer EC 
    JOIN ErpInstruction EI ON EC.CustomerId= EI.InItemId
    JOIN ErpInstructionGoods EIG ON EI.InstructionId= EIG.InstructionId
    JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId
    WHERE EC.MathodId IN (4,7)
        AND EC.ShutOut=0
        AND ((EG.TimeCategoryName1=2022 AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%'))
                    OR (EG.TimeCategoryName1=2023 ))
        AND EG.CategoryName1 IN ('内搭','外套','鞋履','下装')
        AND EI.Type=4
        AND EI.CodingCodeText='已审结'
        AND EI.OutBillId!=''
        AND EI.InBillId=''	
    GROUP BY
        EC.CustomItem15,
        EC.CustomerName,
        EG.GoodsNo,
        EG.TimeCategoryName1,
        EG.TimeCategoryName2,
        EG.CategoryName1,
        EG.CategoryName2,
        EG.StyleCategoryName
    ) T
    GROUP BY 
        T.CustomItem15,
        T.CustomerName,
        T.[GoodsNo],
        T.[TimeCategoryName1],
        T.[Season],
        T.[CategoryName1],
        T.[CategoryName2],
        T.[StyleCategoryName]
    ) T1
    LEFT JOIN 
    (
    SELECT 
        T.CustomItem15,
        T.CustomerName,
        T.GoodsNo,
        SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE NULL END ) AS  [00/28/37/44/100/160/S],
        SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE NULL END ) AS  [29/38/46/105/165/M],
        SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE NULL END ) AS  [30/39/48/110/170/L],
        SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE NULL END ) AS  [31/40/50/115/175/XL],
        SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE NULL END ) AS  [32/41/52/120/180/2XL],
        SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE NULL END ) AS  [33/42/54/125/185/3XL],
        SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE NULL END ) AS  [34/43/56/190/4XL],
        SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE NULL END ) AS  [35/44/58/195/5XL],
        SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE NULL END ) AS  [36/6XL],
        SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE NULL END ) AS  [38/7XL],
        SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE NULL END ) AS  [40],
        SUM(T.Quantity) AS amount,
        SUM(T.[money]) AS money,
        T.CustomerGrade
    FROM
    (
    SELECT 
        EC.CustomItem15,
        EC.CustomerGrade,
        EC.CustomerName,
        EG.GoodsNo,
        ERGD.SizeId,
        SUM(ERGD.Quantity) AS Quantity,
        SUM(ERGD.Quantity*ERG.DiscountPrice) AS money
    FROM ErpCustomer EC 
    LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
    LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
    LEFT JOIN ErpRetailGoodsDetail ERGD ON ERG.RetailGoodsID=ERGD.RetailGoodsID
    LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
    WHERE EC.ShutOut=0
        AND EC.MathodId IN (4,7)
        AND ER.CodingCodeText='已审结'
        AND ((EG.TimeCategoryName1=2022 AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%'))
                    OR (EG.TimeCategoryName1=2023 ))
        AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
        AND CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN  CONVERT(VARCHAR(10),GETDATE()-14,23)	AND CONVERT(VARCHAR(10),GETDATE()-1,23)
    GROUP BY
        EC.CustomItem15,
        EC.CustomerGrade,
        EC.CustomerName,
        EG.GoodsNo,
        ERGD.SizeId
    ) T
    LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId
    WHERE EBGS.IsEnable=1
    GROUP BY
        T.CustomItem15,
        T.CustomerName,
        T.GoodsNo,
        T.CustomerGrade
    ) T2 ON T1.CustomerName=T2.CustomerName AND T1.[GoodsNo]=T2.GoodsNo;";

        return Db::connect("sqlsrv")->Query($sql);

    }

    protected function get_liangzhou_data() {

        //剔除的货品
        $ti_goods = SpLypPuhuoTiGoodsModel::where([])->column('GoodsNo');
        $ti_goods = get_goods_str($ti_goods);
        $sql = "select yuncang,CustomerName,TimeCategoryName1, Season, CategoryName1, CategoryName2, StyleCategoryName, count(GoodsNo) as skc_num from sp_lyp_puhuo_liangzhou where amount>0 and GoodsNo not in ({$ti_goods}) 
        group by yuncang,CustomerName,TimeCategoryName1, Season, CategoryName1, CategoryName2, StyleCategoryName;";

        return Db::connect("mysql")->Query($sql);

    }

}
