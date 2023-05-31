<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\api\model\store\StockSaleSize;

class Stock_size extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('stock')
            ->setDescription('the stock command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit','1000M');

        $start_date = date('Y-m-d', time()-24*60*60*21);//'2023-01-02';////填入 开始日期 的前一天
        $end_date = date('Y-m-d', time()-24*60*60);//'2023-01-21';//

		//生成json文件
		$data = Db::connect("mysql2")->Query($this->get_sql($start_date, $end_date));
		// print_r($data);die;
		@file_put_contents(app()->getRootPath().'/public'.'/img/20day_json.txt', json_encode($data));

        echo 'okk';die;
    }

    protected function get_sql($start_date, $end_date) {

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
