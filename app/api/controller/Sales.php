<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\cache\driver\Redis;
use think\facade\Db;

class Sales
{
    public function index()
    {
        $sql = "SELECT 
            T.WenDai,
            T.WenQu,
            IFNULL(T.State,'合计') AS State,
            IFNULL(T.CategoryName1,'合计') AS CategoryName1,
            IFNULL(T.CategoryName2,'合计') AS CategoryName2,
            IFNULL(T.CategoryName,'合计') AS CategoryName,
            
            CONCAT(ROUND(SUM(T.StockCost) / (SELECT SUM(StockCost) 
                                                    FROM sp_customer_stock_sale_year 
                                                    WHERE Date>='2022-08-01' 
                                                    AND WenDai='中' 
                                                    AND CategoryName1='内搭'
                                                    AND TimeCategoryName1=2022
                                                    AND TimeCategoryName2='初秋')*100,2),'%') AS 销售占比,
                                                    
            CONCAT(ROUND(SUM(T.SaleQuantity) / (SELECT SUM(SaleQuantity) 
                                                         FROM sp_customer_stock_sale_year 
                                                         WHERE Date>='2022-08-01' 
                                                         AND WenDai='中' 
                                                         AND CategoryName1='内搭'
                                                         AND TimeCategoryName1=2022
                                                         AND TimeCategoryName2='初秋')*100,2),'%') AS 库存占比,
                                                         
            CONCAT(ROUND((SUM(T.StockCost) / (SELECT SUM(StockCost) FROM sp_customer_stock_sale_year WHERE Date>='2022-08-01' AND WenDai='中' AND CategoryName1='内搭' AND TimeCategoryName1=2022 AND TimeCategoryName2='初秋')*100) /
            (SUM(T.SaleQuantity) / (SELECT SUM(SaleQuantity) FROM sp_customer_stock_sale_year WHERE Date>='2022-08-01' AND WenDai='中' AND CategoryName1='内搭' AND TimeCategoryName1=2022 AND TimeCategoryName2='初秋')*100)*100,2),'%') AS 效率,
            
            CONCAT(ROUND(SUM(T.SalesVolume) / SUM(T.RetailAmount) * 100,2),'%')  AS 折扣,
            
            CONCAT(ROUND((SUM(T.SalesVolume) - SUM(T.CostAmount)) / SUM(T.SalesVolume) *100,2),'%') AS 毛利
        FROM `sp_customer_stock_sale_year` T
        WHERE T.Date>='2022-08-01'
            AND T.WenDai='中'
            AND T.CategoryName1='内搭'
            AND T.TimeCategoryName1=2022
            AND T.TimeCategoryName2='初秋'
        GROUP BY
            T.WenDai,
            T.WenQu,
            T.State,
            T.CategoryName1,
            T.CategoryName2,
            CategoryName
            WITH ROLLUP";
        $list = Db::connect("mysql2")->query($sql);
        echo '<pre>';
        print_r($list);
        die;


    }
}
