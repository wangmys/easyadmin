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

    //每天导出尺码库存json数据给肖甜使用
    public function create_stock_size_json() {

        ini_set('memory_limit','500M');

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

        $data = Db::connect("mysql2")->Query($this->get_sql3($start_date, $end_date));
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
    protected function get_sql3($start_date, $end_date) {

        return "select 
        Date as '日期', 
        CONCAT(month(DATE), '月') as '月', 
        YunCang as '大区', 
        WenDai as '温带', 
        WenQu as '温区', 
        State as '省份', 
        CustomItem29 as '城市级别', 
        max(NUM) as '店数', 
        GoodsNo as '货号', 
        GoodsName as '货号名称', 
        TimeCategoryName2 as '二级时间分类', 
        TimeCategoryName as '时间分类', 
        CustomItem17 as '厚度', 
        CategoryName1 as '一级分类', 
        CategoryName2 as '二级分类', 
        CategoryName as '分类', 
        SupplyName as '供应商名称', 
        GoodsCode as '简码', 
        StyleCategoryName1 as '一级风格', 
        StyleCategoryName as '风格', 
        ColorDesc as '颜色说明', 
        CustomItem46 as '深浅色', 
        CustomItem47 as '色感', 
        CustomItem48 as '色系', 
        CustomItem11 as '版型', 
        CustomItem1 as '适龄段', 
        CustomItem45 as '时尚度', 
        sum(`StockQuantity00/28/37/44/100/160/S`) as '28库存量',
        sum(`StockQuantity29/38/46/105/165/M`) as '29库存量',
        sum(`StockQuantity30/39/48/110/170/L`) as '30库存量',
        sum(`StockQuantity31/40/50/115/175/XL`) as '31库存量',
        sum(`StockQuantity32/41/52/120/180/2XL`) as '32库存量',
        sum(`StockQuantity33/42/54/125/185/3XL`) as '33库存量',
        sum(`StockQuantity34/43/56/190/4XL`) as '34库存量',
        sum(`StockQuantity35/44/58/195/5XL`) as '35库存量',
        sum(`StockQuantity36/6XL`) as '36库存量',
        sum(`StockQuantity38/7XL`) as '38库存量',
        sum(`StockQuantity40/8XL`) as '40库存量',
        sum(`StockQuantity42/9XL`) as '42库存量',
        sum(`Sales00/28/37/44/100/160/S`) as '28销量',
        sum(`Sales29/38/46/105/165/M`) as '29销量',
        sum(`Sales30/39/48/110/170/L`) as '30销量',
        sum(`Sales31/40/50/115/175/XL`) as '31销量',
        sum(`Sales32/41/52/120/180/2XL`) as '32销量',
        sum(`Sales33/42/54/125/185/3XL`) as '33销量',
        sum(`Sales34/43/56/190/4XL`) as '34销量',
        sum(`Sales35/44/58/195/5XL`) as '35销量',
        sum(`Sales36/6XL`) as '36销量',
        sum(`Sales38/7XL`) as '38销量',
        sum(`Sales40/8XL`) as '40销量',
        sum(`Sales42/9XL`) as '42销量',
        sum(SalesVolume) as '销额', 
        sum(RetailAmount) as '零售金额' 
        from sp_customer_stock_sale_size 
        where (DATE BETWEEN '{$start_date}' and '{$end_date}')  
        and TimeCategoryName2 like '%夏%' 
        group by Date,YunCang, WenDai, WenQu, State, CustomItem29, GoodsNo, GoodsName,  TimeCategoryName,TimeCategoryName2,  
        CustomItem17, CategoryName1, CategoryName2, CategoryName, SupplyName, StyleCategoryName, StyleCategoryName1, ColorDesc, CustomItem46, 
        CustomItem47, CustomItem48, CustomItem11, CustomItem1, CustomItem45;";

    }

}
