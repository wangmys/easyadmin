<?php
declare (strict_types = 1);

namespace app\api\controller\stock;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;
use app\admin\model\wwdata\LypWwDataModel;
use app\admin\model\wwdata\LypWwDiaoboModel;

class Diaobo extends BaseController
{

    public function create_ww_json() {

        ini_set('memory_limit','1024M');
        $table = input('table');
        $data = LypWwDataModel::table($table)->where([])->withoutField('id',false)->select();
        $data = $data ? $data->toArray() : [];
        return json($data);

    }

    //调拨前后json导出
    public function create_diaobo_json() {
        
        ini_set('memory_limit','2048M');
        
        // $start_date = input('start_date');
        // $end_date = input('end_date');
        // if (!$start_date || !$end_date) {
        //     return json(['code'=>400, 'msg'=>'开始日期或结束日期不能为空', 'data'=>[]]);
        // }
        // if ($start_date > $end_date) {
        //     return json(['code'=>400, 'msg'=>'开始日期不能大于结束日期', 'data'=>[]]);
        // }
        // $data = Db::connect("sqlsrv")->Query($this->get_diaobo_sql($start_date, $end_date));
        $data = LypWwDiaoboModel::where([])->select();
        $data = $data ? $data->toArray() : [];
        return json($data);

    }

    protected function get_diaobo_sql($start_date, $end_date) {

        return "WITH T1 AS 
        (
        SELECT 
            ECR.CustomerId,
            ECRG.GoodsId,
            SUM(CASE WHEN EBGS.ViewOrder=1  THEN ECRGD.Quantity END ) 	[调入数量_00/28/37/44/100/160/S],
          SUM(CASE WHEN EBGS.ViewOrder=2  THEN ECRGD.Quantity END ) 	[调入数量_29/38/46/105/165/M],
          SUM(CASE WHEN EBGS.ViewOrder=3  THEN ECRGD.Quantity END ) 	[调入数量_30/39/48/110/170/L],
          SUM(CASE WHEN EBGS.ViewOrder=4  THEN ECRGD.Quantity END ) 	[调入数量_31/40/50/115/175/XL],
          SUM(CASE WHEN EBGS.ViewOrder=5  THEN ECRGD.Quantity END ) 	[调入数量_32/41/52/120/180/2XL],
          SUM(CASE WHEN EBGS.ViewOrder=6  THEN ECRGD.Quantity END ) 	[调入数量_33/42/54/125/185/3XL],
          SUM(CASE WHEN EBGS.ViewOrder=7  THEN ECRGD.Quantity END ) 	[调入数量_34/43/56/190/4XL],
          SUM(CASE WHEN EBGS.ViewOrder=8  THEN ECRGD.Quantity END ) 	[调入数量_35/44/58/195/5XL],
          SUM(CASE WHEN EBGS.ViewOrder=9  THEN ECRGD.Quantity END ) 	[调入数量_36/6XL],
          SUM(CASE WHEN EBGS.ViewOrder=10 THEN ECRGD.Quantity END ) 	[调入数量_38/7XL],
          SUM(CASE WHEN EBGS.ViewOrder=11 THEN ECRGD.Quantity END ) 	[调入数量_40],
            SUM(ECRGD.Quantity) AS 调入总数量
        FROM ErpCustReceipt ECR
        LEFT JOIN ErpCustReceiptGoods ECRG  ON ECR.ReceiptID=ECRG.ReceiptID
        LEFT JOIN ErpCustReceiptGoodsDetail ECRGD ON ECRG.ReceiptGoodsID=ECRGD.ReceiptGoodsID
        LEFT JOIN ErpBaseGoodsSize EBGS ON ECRGD.SizeId=EBGS.SizeId
        LEFT JOIN ErpCustOutbound ECO ON ECRG.CustOutboundId=ECO.CustOutboundId
        WHERE CONVERT(VARCHAR(10),ECR.ReceiptDate,23)  BETWEEN '{$start_date}' AND '{$end_date}'
            AND ECR.Type=2
            AND ECO.ManualNo IS NOT NULL 
            AND ECO.ManualNo!=''
        GROUP BY
            ECR.CustomerId,
            ECRG.GoodsId
        ),
        
        T2 AS 
        (
        SELECT 
            ECS.CustomerId,
            ECS.GoodsId,
            SUM(CASE WHEN EBGS.ViewOrder=1  THEN ECSD.Quantity END ) 	[调入前库存_00/28/37/44/100/160/S],
          SUM(CASE WHEN EBGS.ViewOrder=2  THEN ECSD.Quantity END ) 	[调入前库存_29/38/46/105/165/M],
          SUM(CASE WHEN EBGS.ViewOrder=3  THEN ECSD.Quantity END ) 	[调入前库存_30/39/48/110/170/L],
          SUM(CASE WHEN EBGS.ViewOrder=4  THEN ECSD.Quantity END ) 	[调入前库存_31/40/50/115/175/XL],
          SUM(CASE WHEN EBGS.ViewOrder=5  THEN ECSD.Quantity END ) 	[调入前库存_32/41/52/120/180/2XL],
          SUM(CASE WHEN EBGS.ViewOrder=6  THEN ECSD.Quantity END ) 	[调入前库存_33/42/54/125/185/3XL],
          SUM(CASE WHEN EBGS.ViewOrder=7  THEN ECSD.Quantity END ) 	[调入前库存_34/43/56/190/4XL],
          SUM(CASE WHEN EBGS.ViewOrder=8  THEN ECSD.Quantity END ) 	[调入前库存_35/44/58/195/5XL],
          SUM(CASE WHEN EBGS.ViewOrder=9  THEN ECSD.Quantity END ) 	[调入前库存_36/6XL],
          SUM(CASE WHEN EBGS.ViewOrder=10 THEN ECSD.Quantity END ) 	[调入前库存_38/7XL],
          SUM(CASE WHEN EBGS.ViewOrder=11 THEN ECSD.Quantity END ) 	[调入前库存_40],
            SUM(ECSD.Quantity) AS 调入前库存,
            DATEDIFF(DAY,MIN(ECS.StockDate),GETDATE()) 上市天数
        FROM ErpCustomerStock ECS
        LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
        LEFT JOIN ErpBaseGoodsSize EBGS ON ECSD.SizeId=EBGS.SizeId
        JOIN T1 ON T1.CustomerId = ECS.CustomerId AND T1.GoodsId= ECS.GoodsId
        WHERE CONVERT(VARCHAR(10),ECS.StockDate,23) < '{$start_date}'
        GROUP BY 
            ECS.CustomerId,
            ECS.GoodsId
        HAVING SUM(ECSD.Quantity)!=0
        ),
        
        T3 AS 
        (
        SELECT 
            A.CustomerId,
            A.GoodsId,
            SUM(A.[调入前库存_00/28/37/44/100/160/S]) [调入后库存_00/28/37/44/100/160/S],
          SUM(A.[调入前库存_29/38/46/105/165/M]) 		[调入后库存_29/38/46/105/165/M],
          SUM(A.[调入前库存_30/39/48/110/170/L]) 		[调入后库存_30/39/48/110/170/L],
          SUM(A.[调入前库存_31/40/50/115/175/XL])		[调入后库存_31/40/50/115/175/XL],
          SUM(A.[调入前库存_32/41/52/120/180/2XL])	[调入后库存_32/41/52/120/180/2XL],
          SUM(A.[调入前库存_33/42/54/125/185/3XL])	[调入后库存_33/42/54/125/185/3XL],
          SUM(A.[调入前库存_34/43/56/190/4XL])			[调入后库存_34/43/56/190/4XL],
          SUM(A.[调入前库存_35/44/58/195/5XL])			[调入后库存_35/44/58/195/5XL],
          SUM(A.[调入前库存_36/6XL])								[调入后库存_36/6XL],
          SUM(A.[调入前库存_38/7XL])								[调入后库存_38/7XL],
          SUM(A.[调入前库存_40])										[调入后库存_40],
            SUM(A.调入前库存)													调入后库存
        FROM 
        (
        SELECT 
            T2.CustomerId,
            T2.GoodsId,
            T2.[调入前库存_00/28/37/44/100/160/S],
          T2.[调入前库存_29/38/46/105/165/M],
          T2.[调入前库存_30/39/48/110/170/L],
          T2.[调入前库存_31/40/50/115/175/XL],
          T2.[调入前库存_32/41/52/120/180/2XL],
          T2.[调入前库存_33/42/54/125/185/3XL],
          T2.[调入前库存_34/43/56/190/4XL],
          T2.[调入前库存_35/44/58/195/5XL],
          T2.[调入前库存_36/6XL],
          T2.[调入前库存_38/7XL],
          T2.[调入前库存_40],
            T2.调入前库存
        FROM T2
        UNION ALL 
        SELECT 
            T1.CustomerId,
            T1.GoodsId,
            T1.[调入数量_00/28/37/44/100/160/S],
          T1.[调入数量_29/38/46/105/165/M],
          T1.[调入数量_30/39/48/110/170/L],
          T1.[调入数量_31/40/50/115/175/XL],
          T1.[调入数量_32/41/52/120/180/2XL],
          T1.[调入数量_33/42/54/125/185/3XL],
          T1.[调入数量_34/43/56/190/4XL],
          T1.[调入数量_35/44/58/195/5XL],
          T1.[调入数量_36/6XL],
          T1.[调入数量_38/7XL],
          T1.[调入数量_40],
            T1.调入总数量
        FROM T1
        ) A
        GROUP BY 
            A.CustomerId,
            A.GoodsId
        ),
        
        
        T4 AS 
        (
        SELECT 
            ER.CustomerId,
            ERG.GoodsId,
            SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=1 THEN ERGD.Quantity END ) 	[调拨前两周销量_00/28/37/44/100/160/S],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=2 THEN ERGD.Quantity END ) 	[调拨前两周销量_29/38/46/105/165/M],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=3 THEN ERGD.Quantity END ) 	[调拨前两周销量_30/39/48/110/170/L],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=4 THEN ERGD.Quantity END ) 	[调拨前两周销量_31/40/50/115/175/XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=5 THEN ERGD.Quantity END ) 	[调拨前两周销量_32/41/52/120/180/2XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=6 THEN ERGD.Quantity END ) 	[调拨前两周销量_33/42/54/125/185/3XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=7 THEN ERGD.Quantity END ) 	[调拨前两周销量_34/43/56/190/4XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=8 THEN ERGD.Quantity END ) 	[调拨前两周销量_35/44/58/195/5XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=9 THEN ERGD.Quantity END ) 	[调拨前两周销量_36/6XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=10 THEN ERGD.Quantity END ) 	[调拨前两周销量_38/7XL],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') AND EBGS.ViewOrder=11 THEN ERGD.Quantity END ) 	[调拨前两周销量_40],
          SUM(CASE WHEN  CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -14, '{$start_date}') AND DATEADD(DAY, -1, '{$start_date}') THEN ERGD.Quantity END ) 调拨前两周总销量,
          
          SUM(CASE WHEN  EBGS.ViewOrder=1 THEN ERGD.Quantity END ) 	[累销数量_00/28/37/44/100/160/S],
          SUM(CASE WHEN  EBGS.ViewOrder=2 THEN ERGD.Quantity END ) 	[累销数量_29/38/46/105/165/M],
          SUM(CASE WHEN  EBGS.ViewOrder=3 THEN ERGD.Quantity END ) 	[累销数量_30/39/48/110/170/L],
          SUM(CASE WHEN  EBGS.ViewOrder=4 THEN ERGD.Quantity END ) 	[累销数量_31/40/50/115/175/XL],
          SUM(CASE WHEN  EBGS.ViewOrder=5 THEN ERGD.Quantity END ) 	[累销数量_32/41/52/120/180/2XL],
          SUM(CASE WHEN  EBGS.ViewOrder=6 THEN ERGD.Quantity END ) 	[累销数量_33/42/54/125/185/3XL],
          SUM(CASE WHEN  EBGS.ViewOrder=7 THEN ERGD.Quantity END ) 	[累销数量_34/43/56/190/4XL],
          SUM(CASE WHEN  EBGS.ViewOrder=8 THEN ERGD.Quantity END ) 	[累销数量_35/44/58/195/5XL],
          SUM(CASE WHEN  EBGS.ViewOrder=9 THEN ERGD.Quantity END ) 	[累销数量_36/6XL],
          SUM(CASE WHEN  EBGS.ViewOrder=10 THEN ERGD.Quantity END ) [累销数量_38/7XL],
          SUM(CASE WHEN  EBGS.ViewOrder=11 THEN ERGD.Quantity END ) [累销数量_40],
          SUM( ERGD.Quantity ) 累销总数量,
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=1 THEN ERGD.Quantity END ) 	[调拨后两周销量_00/28/37/44/100/160/S],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=2 THEN ERGD.Quantity END ) 	[调拨后两周销量_29/38/46/105/165/M],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=3 THEN ERGD.Quantity END ) 	[调拨后两周销量_30/39/48/110/170/L],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=4 THEN ERGD.Quantity END ) 	[调拨后两周销量_31/40/50/115/175/XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=5 THEN ERGD.Quantity END ) 	[调拨后两周销量_32/41/52/120/180/2XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=6 THEN ERGD.Quantity END ) 	[调拨后两周销量_33/42/54/125/185/3XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=7 THEN ERGD.Quantity END ) 	[调拨后两周销量_34/43/56/190/4XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=8 THEN ERGD.Quantity END ) 	[调拨后两周销量_35/44/58/195/5XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=9 THEN ERGD.Quantity END ) 	[调拨后两周销量_36/6XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=10 THEN ERGD.Quantity END ) 	[调拨后两周销量_38/7XL],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') AND EBGS.ViewOrder=11 THEN ERGD.Quantity END ) 	[调拨后两周销量_40],
          SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN DATEADD(DAY, -13, '{$end_date}') AND DATEADD(DAY, 0, '{$end_date}') THEN ERGD.Quantity END ) 调拨后两周总销量,
            DATEDIFF(DAY, MIN(ER.RetailDate), GETDATE()) 最早销售时间
        FROM ErpRetail ER 
        LEFT JOIN ErpRetailGoods ERG ON ER.RetailID = ERG.RetailID
        LEFT JOIN ErpRetailGoodsDetail ERGD ON ERG.RetailGoodsID=ERGD.RetailGoodsID
        LEFT JOIN ErpBaseGoodsSize EBGS ON ERGD.SizeId=EBGS.SizeId
        JOIN T1 ON ER.CustomerId=T1.CustomerId AND ERG.GoodsId=T1.GoodsId
        GROUP BY
            ER.CustomerId,
            ERG.GoodsId
        )
        
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
            T1.[调入数量_00/28/37/44/100/160/S],
          T1.[调入数量_29/38/46/105/165/M],
          T1.[调入数量_30/39/48/110/170/L],
          T1.[调入数量_31/40/50/115/175/XL],
          T1.[调入数量_32/41/52/120/180/2XL],
          T1.[调入数量_33/42/54/125/185/3XL],
          T1.[调入数量_34/43/56/190/4XL],
          T1.[调入数量_35/44/58/195/5XL],
          T1.[调入数量_36/6XL],
          T1.[调入数量_38/7XL],
          T1.[调入数量_40],
            T1.调入总数量,
            T2.[调入前库存_00/28/37/44/100/160/S],
          T2.[调入前库存_29/38/46/105/165/M],
          T2.[调入前库存_30/39/48/110/170/L],
          T2.[调入前库存_31/40/50/115/175/XL],
          T2.[调入前库存_32/41/52/120/180/2XL],
          T2.[调入前库存_33/42/54/125/185/3XL],
          T2.[调入前库存_34/43/56/190/4XL],
          T2.[调入前库存_35/44/58/195/5XL],
          T2.[调入前库存_36/6XL],
          T2.[调入前库存_38/7XL],
          T2.[调入前库存_40],
            T2.调入前库存,
            T3.[调入后库存_00/28/37/44/100/160/S],
          T3.[调入后库存_29/38/46/105/165/M],
          T3.[调入后库存_30/39/48/110/170/L],
          T3.[调入后库存_31/40/50/115/175/XL],
          T3.[调入后库存_32/41/52/120/180/2XL],
          T3.[调入后库存_33/42/54/125/185/3XL],
          T3.[调入后库存_34/43/56/190/4XL],
          T3.[调入后库存_35/44/58/195/5XL],
          T3.[调入后库存_36/6XL],
          T3.[调入后库存_38/7XL],
          T3.[调入后库存_40],
            T3.调入后库存,
            T2.[上市天数],
            T4.[最早销售时间],
            T4.[调拨前两周销量_00/28/37/44/100/160/S],
          T4.[调拨前两周销量_29/38/46/105/165/M],
          T4.[调拨前两周销量_30/39/48/110/170/L],
          T4.[调拨前两周销量_31/40/50/115/175/XL],
          T4.[调拨前两周销量_32/41/52/120/180/2XL],
          T4.[调拨前两周销量_33/42/54/125/185/3XL],
          T4.[调拨前两周销量_34/43/56/190/4XL],
          T4.[调拨前两周销量_35/44/58/195/5XL],
          T4.[调拨前两周销量_36/6XL],
          T4.[调拨前两周销量_38/7XL],
          T4.[调拨前两周销量_40],
          T4.调拨前两周总销量,
          
          T4.[累销数量_00/28/37/44/100/160/S],
          T4.[累销数量_29/38/46/105/165/M],
          T4.[累销数量_30/39/48/110/170/L],
          T4.[累销数量_31/40/50/115/175/XL],
          T4.[累销数量_32/41/52/120/180/2XL],
          T4.[累销数量_33/42/54/125/185/3XL],
          T4.[累销数量_34/43/56/190/4XL],
          T4.[累销数量_35/44/58/195/5XL],
          T4.[累销数量_36/6XL],
          T4.[累销数量_38/7XL],
          T4.[累销数量_40],
          T4.累销总数量,
          T4.[调拨后两周销量_00/28/37/44/100/160/S],
          T4.[调拨后两周销量_29/38/46/105/165/M],
          T4.[调拨后两周销量_30/39/48/110/170/L],
          T4.[调拨后两周销量_31/40/50/115/175/XL],
          T4.[调拨后两周销量_32/41/52/120/180/2XL],
          T4.[调拨后两周销量_33/42/54/125/185/3XL],
          T4.[调拨后两周销量_34/43/56/190/4XL],
          T4.[调拨后两周销量_35/44/58/195/5XL],
          T4.[调拨后两周销量_36/6XL],
          T4.[调拨后两周销量_38/7XL],
          T4.[调拨后两周销量_40],
          T4.调拨后两周总销量
        FROM T1 
        LEFT JOIN T2 ON T1.CustomerId=T2.CustomerId AND T1.GoodsId=T2.GoodsId 
        LEFT JOIN T3 ON T1.CustomerId=T3.CustomerId AND T1.GoodsId=T3.GoodsId 
        LEFT JOIN T4 ON T1.CustomerId=T4.CustomerId AND T1.GoodsId=T4.GoodsId 
        LEFT JOIN ErpCustomer EC ON T1.CustomerId=EC.CustomerId
        LEFT JOIN ErpGoods EG ON T1.GoodsId=EG.GoodsId
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
        ;
        ";

    }

    protected function get_ww_sql() {

        return "SELECT 
        CONVERT(VARCHAR(10),ER.RetailDate,23) 日期,
        EC.State 省份,
        EC.CustomerName 店铺名称,
        EM.Mathod 经营模式,
        ERG.Status 销售方式,
        EG.CategoryName1 一级分类,
        SUM(ERG.Quantity) 数量,
        SUM(ERG.Quantity * ERG.DiscountPrice) 金额
    FROM ErpCustomer EC
    LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
    LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
    LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
    LEFT JOIN ErpBaseCustomerMathod EM ON EC.MathodId=EM.MathodId
    WHERE ER.RetailDate>DATEADD(month, DATEDIFF(month, 0, GETDATE())-2, 0)
        AND ERG.Status IN ('售','促')
        AND ER.CodingCodeText='已审结'
    GROUP BY 
        CONVERT(VARCHAR(10),ER.RetailDate,23),
        EC.State,
        EC.CustomerName,
        EM.Mathod,
        ERG.Status,
        EG.CategoryName1
    ORDER BY 
     CONVERT(VARCHAR(10),ER.RetailDate,23),
     EC.State,
     EM.Mathod,
     ERG.Status,
     EG.CategoryName1
    ;";

    }

}
