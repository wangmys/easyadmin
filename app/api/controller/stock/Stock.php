<?php
declare (strict_types = 1);

namespace app\api\controller\stock;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;

class Stock extends BaseController
{
    //创建一周库存数据json返回给肖甜使用
    public function create_stock_json() {

        $start_date = input('start_date');
        $end_date = input('end_date');
        if (!$start_date || !$end_date) {
            return json(['开始日期或结束日期不能为空'], 400);
        }
        if ($start_date > $end_date) {
            return json(['开始日期不能大于结束日期'], 400);
        }
        if ( (strtotime($end_date)-strtotime($start_date))/(24*60*60) > 6 ) {
            return json(['仅限于查询一周数据'], 400);
        }

        $data = Db::connect("mysql2")->Query($this->get_sql2($start_date, $end_date));
        return json($data);

    }

    //按每周划分的基础数据sql （加入深浅色后）
    protected function get_sql2($start_date, $end_date) {

        return "select 
FROM_DAYS(TO_DAYS(DATE) - MOD(TO_DAYS(DATE) -2, 7)) as start_date, 
max(DATE) as '结束日期', 
CONCAT(month(DATE), '月') as '月', 
YunCang as '大区', 
WenDai as '温带', 
WenQu as '温区', 
State as '省份', 
Mathod as '渠道类型', 
max(NUM) as '店数', 
StyleCategoryName as '风格', 
TimeCategoryName1 as '一级时间分类', 
Season as '季节归集', 
TimeCategoryName2 as '二级时间分类', 
CategoryName1 as '一级分类', 
CategoryName2 as '二级分类', 
CategoryName as '分类', 
CustomItem46 as '深浅色', 
StyleCategoryName1 as '一级风格', 
sum(StockQuantity) as '库存量', 
sum(StockAmount) as '库存金额', 
sum(StockCost) as '库存成本额', 
sum(SaleQuantity) as '销量', 
sum(SalesVolume) as '销额', 
sum(RetailAmount) as '零售金额', 
sum(CostAmount) as '销售成本额'  
from sp_customer_stock_sale_threeyear 
where DATE BETWEEN '{$start_date}' and '{$end_date}' 
group by start_date,YunCang, WenDai, WenQu, State, Mathod,TimeCategoryName1,TimeCategoryName2, Season, StyleCategoryName, StyleCategoryName1, CategoryName1, CategoryName2, CategoryName, CustomItem46;";

    }

    //按每周划分的基础数据sql
    protected function get_sql($start_date, $end_date) {

        return "select 
FROM_DAYS(TO_DAYS(DATE) - MOD(TO_DAYS(DATE) -2, 7)) as start_date, 
max(DATE) as '结束日期', 
CONCAT(month(DATE), '月') as '月', 
YunCang as '大区', 
WenDai as '温带', 
WenQu as '温区', 
State as '省份', 
Mathod as '渠道类型', 
max(NUM) as '店数', 
StyleCategoryName as '风格', 
TimeCategoryName1 as '一级时间分类', 
Season as '季节归集', 
TimeCategoryName2 as '二级时间分类', 
CategoryName1 as '一级分类', 
CategoryName2 as '二级分类', 
CategoryName as '分类', 
StyleCategoryName1 as '一级风格', 
sum(StockQuantity) as '库存量', 
sum(StockCost) as '库存成本额', 
sum(SaleQuantity) as '销量', 
sum(SalesVolume) as '销额', 
sum(RetailAmount) as '零售金额', 
sum(CostAmount) as '销售成本额'  
from sp_customer_stock_sale_twoyear 
where DATE BETWEEN '{$start_date}' and '{$end_date}' 
group by start_date,YunCang, WenDai, WenQu, State, Mathod,TimeCategoryName1,TimeCategoryName2, Season, StyleCategoryName, StyleCategoryName1, CategoryName1, CategoryName2, CategoryName;";

    }

}
