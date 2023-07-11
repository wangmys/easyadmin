<?php
declare (strict_types = 1);

namespace app\api\controller\stock;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;

class Diaobo extends BaseController
{

    //调拨前后json导出
    public function create_diaobo_json() {
        
        ini_set('memory_limit','500M');
        
        $start_date = input('start_date');
        $end_date = input('end_date');
        if (!$start_date || !$end_date) {
            return json(['code'=>400, 'msg'=>'开始日期或结束日期不能为空', 'data'=>[]]);
        }
        if ($start_date > $end_date) {
            return json(['code'=>400, 'msg'=>'开始日期不能大于结束日期', 'data'=>[]]);
        }
        $data = Db::connect("sqlsrv")->Query($this->get_diaobo_sql($start_date, $end_date));
        return json($data);

    }

    protected function get_diaobo_sql($start_date, $end_date) {

        return "-- 调拨前后数据查询
        SELECT 
        EC.State,
        EC.CustomerName,
        EG.TimeCategoryName1,
        EG.TimeCategoryName2,
        EG.CategoryName1,
        EG.CategoryName2,
        EG.CategoryName,
        EG.StyleCategoryName,
        EG.GoodsNo,
        EGPT.UnitPrice,
        ISNULL(T.Price,EGPT.UnitPrice) 当前零售价,
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=1 THEN ECSD.Quantity END ) 	[调入数量_00/28/37/44/100/160/S],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=2 THEN ECSD.Quantity END ) 	[调入数量_29/38/46/105/165/M],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=3 THEN ECSD.Quantity END ) 	[调入数量_30/39/48/110/170/L],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=4 THEN ECSD.Quantity END ) 	[调入数量_31/40/50/115/175/XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=5 THEN ECSD.Quantity END ) 	[调入数量_32/41/52/120/180/2XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=6 THEN ECSD.Quantity END ) 	[调入数量_33/42/54/125/185/3XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=7 THEN ECSD.Quantity END ) 	[调入数量_34/43/56/190/4XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=8 THEN ECSD.Quantity END ) 	[调入数量_35/44/58/195/5XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=9 THEN ECSD.Quantity END ) 	[调入数量_36/6XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=10 THEN ECSD.Quantity END ) [调入数量_38/7XL],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' AND EBGS.ViewOrder=11 THEN ECSD.Quantity END ) [调入数量_40],
        SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' THEN ECSD.Quantity END ) 调入总数量,
        
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=1 THEN ECSD.Quantity END)  [调入前库存数量_00/28/37/44/100/160/S],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=2 THEN ECSD.Quantity END)  [调入前库存数量_29/38/46/105/165/M],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=3 THEN ECSD.Quantity END)  [调入前库存数量_30/39/48/110/170/L],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=4 THEN ECSD.Quantity END)  [调入前库存数量_31/40/50/115/175/XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=5 THEN ECSD.Quantity END)  [调入前库存数量_32/41/52/120/180/2XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=6 THEN ECSD.Quantity END)  [调入前库存数量_33/42/54/125/185/3XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=7 THEN ECSD.Quantity END)  [调入前库存数量_34/43/56/190/4XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=8 THEN ECSD.Quantity END)  [调入前库存数量_35/44/58/195/5XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=9 THEN ECSD.Quantity END)  [调入前库存数量_36/6XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=10 THEN ECSD.Quantity END) [调入前库存数量_38/7XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' AND EBGS.ViewOrder=11 THEN ECSD.Quantity END) [调入前库存数量_40],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' THEN ECSD.Quantity END) 调入前库存总数量,
        
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=1 THEN ECSD.Quantity END) [调入后库存数量_00/28/37/44/100/160/S]	,
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=2 THEN ECSD.Quantity END) [调入后库存数量_29/38/46/105/165/M],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=3 THEN ECSD.Quantity END) [调入后库存数量_30/39/48/110/170/L],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=4 THEN ECSD.Quantity END) [调入后库存数量_31/40/50/115/175/XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=5 THEN ECSD.Quantity END) [调入后库存数量_32/41/52/120/180/2XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=6 THEN ECSD.Quantity END) [调入后库存数量_33/42/54/125/185/3XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=7 THEN ECSD.Quantity END) [调入后库存数量_34/43/56/190/4XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=8 THEN ECSD.Quantity END) [调入后库存数量_35/44/58/195/5XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=9 THEN ECSD.Quantity END) [调入后库存数量_36/6XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=10 THEN ECSD.Quantity END) [调入后库存数量_38/7XL],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') AND EBGS.ViewOrder=11 THEN ECSD.Quantity END) [调入后库存数量_40],
        SUM(CASE WHEN CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}' OR (ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}') THEN ECSD.Quantity END) 调入后库存总数量	,
        DATEDIFF(DAY,MIN(ECS.StockDate),GETDATE()) 上市天数,
        DATEDIFF(DAY, MIN(CASE WHEN ECS.BillType='ErpRetail' THEN ECS.StockDate END), GETDATE()) 最早销售时间,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=1 THEN ECSD.Quantity END ) [调拨前两周销量_00/28/37/44/100/160/S],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=2 THEN ECSD.Quantity END ) 	[调拨前两周销量_29/38/46/105/165/M],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=3 THEN ECSD.Quantity END ) 	[调拨前两周销量_30/39/48/110/170/L],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=4 THEN ECSD.Quantity END ) 	[调拨前两周销量_31/40/50/115/175/XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=5 THEN ECSD.Quantity END ) 	[调拨前两周销量_32/41/52/120/180/2XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=6 THEN ECSD.Quantity END ) 	[调拨前两周销量_33/42/54/125/185/3XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=7 THEN ECSD.Quantity END ) 	[调拨前两周销量_34/43/56/190/4XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=8 THEN ECSD.Quantity END ) 	[调拨前两周销量_35/44/58/195/5XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=9 THEN ECSD.Quantity END ) 	[调拨前两周销量_36/6XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=10 THEN ECSD.Quantity END ) [调拨前两周销量_38/7XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=11 THEN ECSD.Quantity END ) [调拨前两周销量_40],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') THEN ECSD.Quantity END ) 调拨前两周总销量,
        /*
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=1 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=2 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=3 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=4 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=5 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=6 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=7 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=8 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=9 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=10 THEN ECSD.Quantity END ) 累销数量,
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND EBGS.ViewOrder=11 THEN ECSD.Quantity END ) 累销数量,*/
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' THEN ECSD.Quantity END ) 累销总数量,
        
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=1 THEN ECSD.Quantity END ) [调拨后两周销量_00/28/37/44/100/160/S],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=2 THEN ECSD.Quantity END ) 	[调拨后两周销量_29/38/46/105/165/M],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=3 THEN ECSD.Quantity END ) 	[调拨后两周销量_30/39/48/110/170/L],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=4 THEN ECSD.Quantity END ) 	[调拨后两周销量_31/40/50/115/175/XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=5 THEN ECSD.Quantity END ) 	[调拨后两周销量_32/41/52/120/180/2XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=6 THEN ECSD.Quantity END ) 	[调拨后两周销量_33/42/54/125/185/3XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=7 THEN ECSD.Quantity END ) 	[调拨后两周销量_34/43/56/190/4XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=8 THEN ECSD.Quantity END ) 	[调拨后两周销量_35/44/58/195/5XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=9 THEN ECSD.Quantity END ) 	[调拨后两周销量_36/6XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=10 THEN ECSD.Quantity END ) [调拨后两周销量_38/7XL],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') AND EBGS.ViewOrder=11 THEN ECSD.Quantity END ) [调拨后两周销量_40],
        -SUM(CASE WHEN ECS.BillType = 'ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN DATEADD(DAY, 1, '{$end_date}') AND DATEADD(DAY, 14, '{$end_date}') THEN ECSD.Quantity END ) 调拨后两周总销量
        -- COUNT(EC.CustomerName ) OVER (PARTITION BY EC.State)
        FROM ErpCustomer EC 
        LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
        LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
        LEFT JOIN ErpCustReceipt ECR ON ECS.BillId=ECR.ReceiptID
        LEFT JOIN ErpCustReceiptGoods ECRG ON ECS.BillId =ECRG.ReceiptID AND ECS.GoodsId=ECRG.GoodsId
        LEFT JOIN ErpCustOutbound ECO ON ECRG.CustOutboundId=ECO.CustOutboundId
        LEFT JOIN ErpGoods EG ON  ECS.GoodsId = EG.GoodsId
        LEFT JOIN ErpBaseGoodsSize EBGS ON ECSD.SizeId=EBGS.SizeId
        LEFT JOIN ErpGoodsPriceType EGPT ON EG.GoodsId=EGPT.GoodsId
        LEFT JOIN (
                                SELECT 
                                    A.CustomerId,A.GoodsId,A.Price
                                FROM 
                                        (
                                        SELECT 
                                                EPC.CustomerId,
                                                EPT.GoodsId,
                                                EPT.Price,
                                                Row_Number() OVER (partition by EPC.CustomerId,EPT.GoodsId ORDER BY EP.CreateTime desc) RN
                                        FROM ErpPromotion EP
                                        LEFT JOIN ErpPromotionCustomer EPC ON EP.PromotionId=EPC.PromotionId
                                        LEFT JOIN ErpCustomer EC ON EPC.CustomerId=EC.CustomerId
                                        LEFT JOIN ErpPromotionTypeEx1 EPT ON EP.PromotionId=EPT.PromotionId
                                        LEFT JOIN ErpPromotionTime EPTT ON EP.PromotionId=EPTT.PromotionId
                                        LEFT JOIN ErpGoods EG ON EPT.GoodsId = EG.GoodsId
                                        WHERE EP.PromotionTypeId=1
                                            AND EP.IsDisable=0
                                            AND EP.CodingCodeText='已审结'
                                            AND EC.MathodId IN (4,7)
                                            AND EC.ShutOut=0
                                            -- AND EG.TimeCategoryName1=2023
                                            -- AND EG.TimeCategoryName2 IN ('夏季','初夏','盛夏')
                                            AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                                            AND CONVERT(VARCHAR(10),GETDATE()-1,120) BETWEEN EPTT.BDate AND EPTT.EDate			
                                            ) A
                                WHERE A.RN=1 AND A.Price!=0) T 
                                ON EC.CustomerId=T.CustomerId AND EG.GoodsId=T.GoodsId
        WHERE EC.ShutOut=0
            AND EC.MathodId IN (4,7)
            AND EGPT.PriceId=1
        GROUP BY 
        EC.State,
        EC.CustomerName,
        EG.TimeCategoryName1,
        EG.TimeCategoryName2,
        EG.CategoryName1,
        EG.CategoryName2,
        EG.CategoryName,
        EG.StyleCategoryName,
        EG.GoodsNo,
        EGPT.UnitPrice,
        T.Price
        HAVING SUM(CASE WHEN ECS.BillType = 'ErpCustReceipt' AND ECR.Type=2 AND (ECO.ManualNo IS NOT NULL AND ECO.ManualNo!='') AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN '{$start_date}' AND '{$end_date}' THEN ECSD.Quantity END ) > 0
        ;";

    }

}
