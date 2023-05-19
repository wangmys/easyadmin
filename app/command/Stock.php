<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\api\model\store\Stock as StockM;
use app\api\model\store\StockSaleTwoyear;

class Stock extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('stock')
            ->setDescription('the stock command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit','500M');

        $start_date = '2023-04-23';
        for ($i=1; $i<=21; $i++) {
            $current_date = date("Y-m-d", (strtotime($start_date) + 24*60*60*$i) );
//            echo $current_date;die;
            $data = Db::connect("sqlsrv")->Query($this->get_sql3($current_date));
            if ($data) {
                foreach ($data as $k=>$v_data) {
                    StockSaleTwoyear::create($v_data);
                   print_r($v_data);die;
                }
            }
        }
        echo 'okk';die;
    }

    protected function execute_bak(Input $input, Output $output)
    {
        ini_set('memory_limit','500M');

        for ($i=1; $i<101; $i++) {
            $current_date = date("Y-m-d",strtotime("-{$i} day"));
            $data = Db::connect("sqlsrv")->Query($this->get_sql2($current_date));
            if ($data) {
                foreach ($data as $k=>$v_data) {
                    StockM::create($v_data);
//                    print_r($v_data);die;
                }
            }
        }
        echo 'okk';die;
    }


    //按每周划分的基础数据sql
    protected function get_sql3($current_date) {

        return "SELECT 
	T.Date,
	T.YunCang,
	T.WenDai,
	T.WenQu,
	T.State,
	T.Mathod,
	MAX(T.NUM) OVER (PARTITION BY T.YunCang,T.Mathod,T.WenDai,T.WenQu,T.State) NUM,
	-- COUNT(DISTINCT T.CustomerId) OVER (PARTITION BY T.YunCang,T.Mathod,T.WenDai,T.WenQu,T.State) NUM,
	T.TimeCategoryName1,
	T.Season,
	T.TimeCategoryName2,
	T.CategoryName1,
	T.CategoryName2,
	T.CategoryName,
	T.StyleCategoryName,
	T.StyleCategoryName1,
	T.CustomItem1,
	T.CustomItem45,
	T.StockQuantity,
	[StockQuantity00/28/37/44/100/160/S],
	T.[StockQuantity29/38/46/105/165/M],
	T.[StockQuantity30/39/48/110/170/L],
	T.[StockQuantity31/40/50/115/175/XL],
	T.[StockQuantity32/41/52/120/180/2XL],
	T.[StockQuantity33/42/54/125/185/3XL],
	T.[StockQuantity34/43/56/190/4XL],
	T.[StockQuantity35/44/58/195/5XL],
	T.[StockQuantity36/6XL],
	T.[StockQuantity38/7XL],
	T.[StockQuantity40/8XL],
	T.StockCost,
	T.SaleQuantity,
	T.[Sales00/28/37/44/100/160/S],
	T.[Sales29/38/46/105/165/M],
	T.[Sales30/39/48/110/170/L],
	T.[Sales31/40/50/115/175/XL],
	T.[Sales32/41/52/120/180/2XL],
	T.[Sales33/42/54/125/185/3XL],
	T.[Sales34/43/56/190/4XL],
	T.[Sales35/44/58/195/5XL],
	T.[Sales36/6XL],
	T.[Sales38/7XL],
	T.[Sales40/8XL],
	T.SalesVolume,
	T.RetailAmount,
	T.CostAmount
FROM 
(
SELECT 
	'{$current_date}' AS Date,
	CASE WHEN EC.CustomItem15='长沙云仓' OR EC.CustomItem15='南昌云仓' THEN '长江以南' 
			 WHEN EC.CustomItem15='武汉云仓' THEN '长江以北' 
			 WHEN EC.CustomItem15='广州云仓' THEN '两个' 
			 WHEN EC.CustomItem15='贵阳云仓' THEN '西南片区' ELSE EC.CustomItem15 END AS YunCang,
	CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END AS Mathod,
	EC.CustomItem30 AS WenDai,
	EC.CustomItem36 AS WenQu,
	EC.State AS State,
	COUNT(DISTINCT EC.CustomerId) NUM,
	-- EC.CustomerId,
	EG.TimeCategoryName1 AS TimeCategoryName1,
	CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
			 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
			 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
			 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
			 ELSE EG.TimeCategoryName2 
  END AS Season,
	EG.TimeCategoryName2 AS TimeCategoryName2,
	EG.CategoryName1 AS CategoryName1,
	EG.CategoryName2 AS CategoryName2,
	EG.CategoryName AS CategoryName,
	EG.StyleCategoryName AS StyleCategoryName,
	EG.StyleCategoryName1 AS StyleCategoryName1,
	EG.CustomItem1,
	EG.CustomItem45,
	SUM(ECSD.Quantity) AS StockQuantity,
	SUM(CASE WHEN EBGS.ViewOrder=1 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity00/28/37/44/100/160/S],
	SUM(CASE WHEN EBGS.ViewOrder=2 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity29/38/46/105/165/M],
	SUM(CASE WHEN EBGS.ViewOrder=3 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity30/39/48/110/170/L],
	SUM(CASE WHEN EBGS.ViewOrder=4 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity31/40/50/115/175/XL],
	SUM(CASE WHEN EBGS.ViewOrder=5 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity32/41/52/120/180/2XL],
	SUM(CASE WHEN EBGS.ViewOrder=6 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity33/42/54/125/185/3XL],
	SUM(CASE WHEN EBGS.ViewOrder=7 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity34/43/56/190/4XL],
	SUM(CASE WHEN EBGS.ViewOrder=8 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity35/44/58/195/5XL],
	SUM(CASE WHEN EBGS.ViewOrder=9 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity36/6XL],
	SUM(CASE WHEN EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity38/7XL],
	SUM(CASE WHEN EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity40/8XL],
	SUM(ECSD.Quantity*EGPT.[成本价]) AS StockCost,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity ELSE 0 END) AS SaleQuantity,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=1	 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales00/28/37/44/100/160/S],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=2  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales29/38/46/105/165/M],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=3  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales30/39/48/110/170/L],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=4  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales31/40/50/115/175/XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=5  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales32/41/52/120/180/2XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=6  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales33/42/54/125/185/3XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=7  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales34/43/56/190/4XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=8  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales35/44/58/195/5XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=9  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales36/6XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales38/7XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales40/8XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*ERG.DiscountPrice ELSE 0 END) AS SalesVolume,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*EGPT.[零售价] ELSE 0 END) AS RetailAmount,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*EGPT.[成本价] ELSE 0 END) AS CostAmount
FROM ErpCustomer EC 
LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
LEFT JOIN ErpBaseGoodsSize EBGS ON ECSD.SizeId=EBGS.SizeId
LEFT JOIN ErpGoods EG ON ECS.GoodsId = EG.GoodsId
LEFT JOIN (SELECT 
							EGPT.GoodsId, 
							SUM(CASE WHEN EGPT.PriceName='零售价' THEN EGPT.UnitPrice ELSE NULL END) AS 零售价,
							SUM(CASE WHEN EGPT.PriceName='成本价' THEN EGPT.UnitPrice ELSE NULL END) AS 成本价
						FROM ErpGoodsPriceType EGPT
						GROUP BY EGPT.GoodsId ) EGPT ON EG.GoodsId=EGPT.GoodsId
LEFT JOIN (SELECT ERG.RetailID,ERG.GoodsId,AVG(ERG.DiscountPrice) AS DiscountPrice 
						FROM ErpRetail ER 
						LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
						WHERE CONVERT(VARCHAR(10),ER.RetailDate,23)='{$current_date}'
						GROUP BY ERG.RetailID,ERG.GoodsId) ERG ON ECS.BillId=ERG.RetailID AND ECS.GoodsId=ERG.GoodsId 
WHERE EC.MathodId IN (4,7)
	AND EC.RegionId!=55
	AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
	AND CONVERT(VARCHAR(10),ECS.StockDate,23) <= '{$current_date}'
-- 	AND EG.TimeCategoryName1=2020
-- 	AND EG.CategoryName='翻领羊毛衫'
-- 	AND TimeCategoryName2='冬季'
-- 	AND StyleCategoryName='基本款'
-- 	AND EC.CustomItem36='中三'
-- 	AND EC.State='江西省'
-- 	AND EC.CustomerName='德兴一店'
GROUP BY 
	EC.CustomItem15,
	EC.MathodId,
	EC.CustomItem30,
	EC.CustomItem36,
	EC.State,
	-- EC.CustomerId,
	EG.TimeCategoryName1,
	EG.TimeCategoryName2,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName,
	EG.StyleCategoryName,
	EG.StyleCategoryName1,
	EG.CustomItem1,
	EG.CustomItem45
HAVING SUM(ECSD.Quantity) !=0 
	OR -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity ELSE 0 END) !=0
--ORDER BY SUM(ECSD.Quantity)
) AS T
ORDER BY 
	T.YunCang,
	T.WenDai,
	T.WenQu,
	T.State,
	T.Mathod
;";

    }

    protected function get_sql2($current_date) {

        return "SELECT 
	T.Date,
	T.YunCang,
	T.WenDai,
	T.WenQu,
	T.State,
	T.Mathod,
	MAX(T.NUM) OVER (PARTITION BY T.YunCang,T.Mathod,T.WenDai,T.WenQu,T.State) NUM,
-- 	T.CustomerId,
-- 	T.CustomerName,
	-- COUNT(DISTINCT T.CustomerId) OVER (PARTITION BY T.YunCang,T.Mathod,T.WenDai,T.WenQu,T.State) NUM,
	T.TimeCategoryName1,
	T.Season,
	T.TimeCategoryName2,
	T.CategoryName1,
	T.CategoryName2,
	T.CategoryName,
	T.StyleCategoryName,
	T.StyleCategoryName1,
	T.CustomItem1,
	T.CustomItem45,
	T.GoodsNo,
	T.GoodsName,
	T.CustomItem17, -- 厚度
	T.CustomItem46 , -- 
	T.CustomItem47 ,
	T.CustomItem48 ,
	T.StockQuantity,
	T.SupplyName,
	[StockQuantity00/28/37/44/100/160/S],
	T.[StockQuantity29/38/46/105/165/M],
	T.[StockQuantity30/39/48/110/170/L],
	T.[StockQuantity31/40/50/115/175/XL],
	T.[StockQuantity32/41/52/120/180/2XL],
	T.[StockQuantity33/42/54/125/185/3XL],
	T.[StockQuantity34/43/56/190/4XL],
	T.[StockQuantity35/44/58/195/5XL],
	T.[StockQuantity36/6XL],
	T.[StockQuantity38/7XL],
	T.[StockQuantity40/8XL],
	T.StockCost,
	T.SaleQuantity,
	T.[Sales00/28/37/44/100/160/S],
	T.[Sales29/38/46/105/165/M],
	T.[Sales30/39/48/110/170/L],
	T.[Sales31/40/50/115/175/XL],
	T.[Sales32/41/52/120/180/2XL],
	T.[Sales33/42/54/125/185/3XL],
	T.[Sales34/43/56/190/4XL],
	T.[Sales35/44/58/195/5XL],
	T.[Sales36/6XL],
	T.[Sales38/7XL],
	T.[Sales40/8XL],
	T.SalesVolume,
	T.RetailAmount,
	T.CostAmount
FROM 
(
SELECT 
	'{$current_date}' AS Date,
	CASE WHEN EC.CustomItem15='长沙云仓' OR EC.CustomItem15='南昌云仓' THEN '长江以南' 
			 WHEN EC.CustomItem15='武汉云仓' THEN '长江以北' 
			 WHEN EC.CustomItem15='广州云仓' THEN '两广' 
			 WHEN EC.CustomItem15='贵阳云仓' THEN '西南片区' ELSE EC.CustomItem15 END AS YunCang,
	CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END AS Mathod,
	EC.CustomItem30 AS WenDai,
	EC.CustomItem36 AS WenQu,
	EC.State AS State,
	COUNT(DISTINCT EC.CustomerId) NUM,
-- 	EC.CustomerId,
-- 	EC.CustomerName,
	EG.TimeCategoryName1 AS TimeCategoryName1,
	CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
			 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
			 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
			 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
			 ELSE EG.TimeCategoryName2 
  END AS Season,
	EG.TimeCategoryName2 AS TimeCategoryName2,
	EG.CategoryName1 AS CategoryName1,
	EG.CategoryName2 AS CategoryName2,
	EG.CategoryName AS CategoryName,
	EG.StyleCategoryName AS StyleCategoryName,
	EG.StyleCategoryName1 AS StyleCategoryName1,
	EG.CustomItem1,
	EG.CustomItem45,
	EG.GoodsNo,
	EG.GoodsName,
	EG.CustomItem17,
	EG.CustomItem46 ,
	EG.CustomItem47 ,
	EG.CustomItem48 ,
	ES.SupplyName,
	SUM(ECSD.Quantity) AS StockQuantity,
	SUM(CASE WHEN EBGS.ViewOrder=1 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity00/28/37/44/100/160/S],
	SUM(CASE WHEN EBGS.ViewOrder=2 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity29/38/46/105/165/M],
	SUM(CASE WHEN EBGS.ViewOrder=3 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity30/39/48/110/170/L],
	SUM(CASE WHEN EBGS.ViewOrder=4 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity31/40/50/115/175/XL],
	SUM(CASE WHEN EBGS.ViewOrder=5 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity32/41/52/120/180/2XL],
	SUM(CASE WHEN EBGS.ViewOrder=6 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity33/42/54/125/185/3XL],
	SUM(CASE WHEN EBGS.ViewOrder=7 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity34/43/56/190/4XL],
	SUM(CASE WHEN EBGS.ViewOrder=8 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity35/44/58/195/5XL],
	SUM(CASE WHEN EBGS.ViewOrder=9 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity36/6XL],
	SUM(CASE WHEN EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity38/7XL],
	SUM(CASE WHEN EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity40/8XL],
	SUM(ECSD.Quantity*EGPT.[成本价]) AS StockCost,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity ELSE 0 END) AS SaleQuantity,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=1	 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales00/28/37/44/100/160/S],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=2  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales29/38/46/105/165/M],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=3  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales30/39/48/110/170/L],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=4  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales31/40/50/115/175/XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=5  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales32/41/52/120/180/2XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=6  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales33/42/54/125/185/3XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=7  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales34/43/56/190/4XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=8  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales35/44/58/195/5XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=9  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales36/6XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales38/7XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales40/8XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*ERG.DiscountPrice ELSE 0 END) AS SalesVolume,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*EGPT.[零售价] ELSE 0 END) AS RetailAmount,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*EGPT.[成本价] ELSE 0 END) AS CostAmount
FROM ErpCustomer EC 
LEFT JOIN ff21120220830.dbo.ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
LEFT JOIN ff21120220830.dbo.ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
LEFT JOIN ErpBaseGoodsSize EBGS ON ECSD.SizeId=EBGS.SizeId
LEFT JOIN ErpGoods EG ON ECS.GoodsId = EG.GoodsId
LEFT JOIN (SELECT 
							EGPT.GoodsId, 
							SUM(CASE WHEN EGPT.PriceName='零售价' THEN EGPT.UnitPrice ELSE NULL END) AS 零售价,
							SUM(CASE WHEN EGPT.PriceName='成本价' THEN EGPT.UnitPrice ELSE NULL END) AS 成本价
						FROM ErpGoodsPriceType EGPT
						GROUP BY EGPT.GoodsId ) EGPT ON EG.GoodsId=EGPT.GoodsId
LEFT JOIN (SELECT ERG.RetailID,ERG.GoodsId,AVG(ERG.DiscountPrice) AS DiscountPrice 
						FROM ErpRetail ER 
						LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
						WHERE CONVERT(VARCHAR(10),ER.RetailDate,23)='{$current_date}'
						GROUP BY ERG.RetailID,ERG.GoodsId) ERG ON ECS.BillId=ERG.RetailID AND ECS.GoodsId=ERG.GoodsId
LEFT JOIN ErpSupply ES ON EG.SupplyId=ES.SupplyId 
WHERE EC.MathodId IN (4,7)
	AND EC.RegionId!=55
	AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
	AND CONVERT(VARCHAR(10),ECS.StockDate,23) <= '{$current_date}'
-- 	AND EG.TimeCategoryName1=2020
-- 	AND EG.CategoryName='翻领羊毛衫'
-- 	AND TimeCategoryName2='冬季'
-- 	AND StyleCategoryName='基本款'
-- 	AND EC.CustomItem36='中三'
-- 	AND EC.State='江西省'
-- 	AND EC.CustomerName='德兴一店'
GROUP BY 
	EC.CustomItem15,
	EC.MathodId,
	EC.CustomItem30,
	EC.CustomItem36,
	EC.State,
-- 	EC.CustomerId,
-- 	EC.CustomerName,
	EG.TimeCategoryName1,
	EG.TimeCategoryName2,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName,
	EG.StyleCategoryName,
	EG.StyleCategoryName1,
	EG.CustomItem1,
	EG.CustomItem45,
	EG.GoodsNo,
	EG.GoodsName,
	EG.CustomItem17,
	EG.CustomItem46 ,
	EG.CustomItem47 ,
	EG.CustomItem48 ,
	ES.SupplyName
HAVING SUM(ECSD.Quantity) !=0 
	OR -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity ELSE 0 END) !=0
--ORDER BY SUM(ECSD.Quantity)
) AS T
ORDER BY 
	T.YunCang,
	T.WenDai,
	T.WenQu,
	T.State,
	T.Mathod
;";

    }

    protected function get_sql($current_date) {

        return "SELECT 
	T.Date,
	T.YunCang,
	T.WenDai,
	T.WenQu,
	T.State,
	T.Mathod,
	MAX(T.NUM) OVER (PARTITION BY T.YunCang,T.Mathod,T.WenDai,T.WenQu,T.State) NUM,
	-- COUNT(DISTINCT T.CustomerId) OVER (PARTITION BY T.YunCang,T.Mathod,T.WenDai,T.WenQu,T.State) NUM,
	T.TimeCategoryName1,
	T.Season,
	T.TimeCategoryName2,
	T.CategoryName1,
	T.CategoryName2,
	T.CategoryName,
	T.StyleCategoryName,
	T.StyleCategoryName1,
	T.CustomItem1,
	T.CustomItem45,
	T.GoodsNo,
	T.GoodsName,
	T.CustomItem46 ,
	T.CustomItem47 ,
	T.CustomItem48 ,
	T.StockQuantity,
	[StockQuantity00/28/37/44/100/160/S],
	T.[StockQuantity29/38/46/105/165/M],
	T.[StockQuantity30/39/48/110/170/L],
	T.[StockQuantity31/40/50/115/175/XL],
	T.[StockQuantity32/41/52/120/180/2XL],
	T.[StockQuantity33/42/54/125/185/3XL],
	T.[StockQuantity34/43/56/190/4XL],
	T.[StockQuantity35/44/58/195/5XL],
	T.[StockQuantity36/6XL],
	T.[StockQuantity38/7XL],
	T.[StockQuantity40/8XL],
	T.StockCost,
	T.SaleQuantity,
	T.[Sales00/28/37/44/100/160/S],
	T.[Sales29/38/46/105/165/M],
	T.[Sales30/39/48/110/170/L],
	T.[Sales31/40/50/115/175/XL],
	T.[Sales32/41/52/120/180/2XL],
	T.[Sales33/42/54/125/185/3XL],
	T.[Sales34/43/56/190/4XL],
	T.[Sales35/44/58/195/5XL],
	T.[Sales36/6XL],
	T.[Sales38/7XL],
	T.[Sales40/8XL],
	T.SalesVolume,
	T.RetailAmount,
	T.CostAmount
FROM 
(
SELECT 
	'{$current_date}' AS Date,
	CASE WHEN EC.CustomItem15='长沙云仓' OR EC.CustomItem15='南昌云仓' THEN '长江以南' 
			 WHEN EC.CustomItem15='武汉云仓' THEN '长江以北' 
			 WHEN EC.CustomItem15='广州云仓' THEN '两广' 
			 WHEN EC.CustomItem15='贵阳云仓' THEN '西南片区' ELSE EC.CustomItem15 END AS YunCang,
	CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END AS Mathod,
	EC.CustomItem30 AS WenDai,
	EC.CustomItem36 AS WenQu,
	EC.State AS State,
	COUNT(DISTINCT EC.CustomerId) NUM,
	-- EC.CustomerId,
	EG.TimeCategoryName1 AS TimeCategoryName1,
	CASE WHEN EG.TimeCategoryName2 LIKE '%春%' THEN '春季'
			 WHEN EG.TimeCategoryName2 LIKE '%夏%' THEN '夏季'
			 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
			 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
			 ELSE EG.TimeCategoryName2 
  END AS Season,
	EG.TimeCategoryName2 AS TimeCategoryName2,
	EG.CategoryName1 AS CategoryName1,
	EG.CategoryName2 AS CategoryName2,
	EG.CategoryName AS CategoryName,
	EG.StyleCategoryName AS StyleCategoryName,
	EG.StyleCategoryName1 AS StyleCategoryName1,
	EG.CustomItem1,
	EG.CustomItem45,
	EG.GoodsNo,
	EG.GoodsName,
	EG.CustomItem46 ,
	EG.CustomItem47 ,
	EG.CustomItem48 ,
	SUM(ECSD.Quantity) AS StockQuantity,
	SUM(CASE WHEN EBGS.ViewOrder=1 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity00/28/37/44/100/160/S],
	SUM(CASE WHEN EBGS.ViewOrder=2 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity29/38/46/105/165/M],
	SUM(CASE WHEN EBGS.ViewOrder=3 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity30/39/48/110/170/L],
	SUM(CASE WHEN EBGS.ViewOrder=4 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity31/40/50/115/175/XL],
	SUM(CASE WHEN EBGS.ViewOrder=5 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity32/41/52/120/180/2XL],
	SUM(CASE WHEN EBGS.ViewOrder=6 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity33/42/54/125/185/3XL],
	SUM(CASE WHEN EBGS.ViewOrder=7 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity34/43/56/190/4XL],
	SUM(CASE WHEN EBGS.ViewOrder=8 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity35/44/58/195/5XL],
	SUM(CASE WHEN EBGS.ViewOrder=9 	THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity36/6XL],
	SUM(CASE WHEN EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity38/7XL],
	SUM(CASE WHEN EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [StockQuantity40/8XL],
	SUM(ECSD.Quantity*EGPT.[成本价]) AS StockCost,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity ELSE 0 END) AS SaleQuantity,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=1	 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales00/28/37/44/100/160/S],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=2  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales29/38/46/105/165/M],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=3  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales30/39/48/110/170/L],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=4  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales31/40/50/115/175/XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=5  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales32/41/52/120/180/2XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=6  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales33/42/54/125/185/3XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=7  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales34/43/56/190/4XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=8  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales35/44/58/195/5XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=9  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales36/6XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales38/7XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' AND EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales40/8XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*ERG.DiscountPrice ELSE 0 END) AS SalesVolume,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*EGPT.[零售价] ELSE 0 END) AS RetailAmount,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity*EGPT.[成本价] ELSE 0 END) AS CostAmount
FROM ErpCustomer EC 
LEFT JOIN ff21120220830.dbo.ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
LEFT JOIN ff21120220830.dbo.ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
LEFT JOIN ErpBaseGoodsSize EBGS ON ECSD.SizeId=EBGS.SizeId
LEFT JOIN ErpGoods EG ON ECS.GoodsId = EG.GoodsId
LEFT JOIN (SELECT 
							EGPT.GoodsId, 
							SUM(CASE WHEN EGPT.PriceName='零售价' THEN EGPT.UnitPrice ELSE NULL END) AS 零售价,
							SUM(CASE WHEN EGPT.PriceName='成本价' THEN EGPT.UnitPrice ELSE NULL END) AS 成本价
						FROM ErpGoodsPriceType EGPT
						GROUP BY EGPT.GoodsId ) EGPT ON EG.GoodsId=EGPT.GoodsId
LEFT JOIN (SELECT ERG.RetailID,ERG.GoodsId,AVG(ERG.DiscountPrice) AS DiscountPrice 
						FROM ErpRetail ER 
						LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
						WHERE CONVERT(VARCHAR(10),ER.RetailDate,23)='{$current_date}'
						GROUP BY ERG.RetailID,ERG.GoodsId) ERG ON ECS.BillId=ERG.RetailID AND ECS.GoodsId=ERG.GoodsId 
WHERE EC.MathodId IN (4,7)
	AND EC.RegionId!=55
	AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
	AND CONVERT(VARCHAR(10),ECS.StockDate,23) <= '{$current_date}'
-- 	AND EG.TimeCategoryName1=2020
-- 	AND EG.CategoryName='翻领羊毛衫'
-- 	AND TimeCategoryName2='冬季'
-- 	AND StyleCategoryName='基本款'
-- 	AND EC.CustomItem36='中三'
-- 	AND EC.State='江西省'
-- 	AND EC.CustomerName='德兴一店'
GROUP BY 
	EC.CustomItem15,
	EC.MathodId,
	EC.CustomItem30,
	EC.CustomItem36,
	EC.State,
	-- EC.CustomerId,
	EG.TimeCategoryName1,
	EG.TimeCategoryName2,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName,
	EG.StyleCategoryName,
	EG.StyleCategoryName1,
	EG.CustomItem1,
	EG.CustomItem45,
	EG.GoodsNo,
	EG.GoodsName,
	EG.CustomItem46 ,
	EG.CustomItem47 ,
	EG.CustomItem48 
HAVING SUM(ECSD.Quantity) !=0 
	OR -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)='{$current_date}' THEN ECSD.Quantity ELSE 0 END) !=0
--ORDER BY SUM(ECSD.Quantity)
) AS T
ORDER BY 
	T.YunCang,
	T.WenDai,
	T.WenQu,
	T.State,
	T.Mathod
;";

    }

}