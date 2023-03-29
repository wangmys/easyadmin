<?php

namespace app\http\logic;

use app\admin\model\SystemAdmin;
use think\facade\Db;
use think\cache\driver\Redis;

/**
 * 添加历史数据
 * Class WorkMessage
 * @package app\http\logic
 */
class AddHistoryData
{
    public $worker;
    public $sqlsrv;
    public $redis;

    public function __construct()
    {
        // 初始化
        $this->redis = new Redis();
        // 初始化任务
        $this->setTaskQueue();
        // 初始化连接康雷数据库
        $this->sqlsrv = Db::connect("sqlsrv");
        // 初始化连接bi数据库
        $this->mysql2 = Db::connect("mysql2");
    }

    // 设置任务队列
    public function setTaskQueue()
    {
        $list = $this->redis->lrange("task_queue",0,-1);
        if($list){
            return false;
        }
        if(empty($this->redis->lrange("finish_task",0,-1))){
            // 计算天数
            $days = $this->daysbetweendates('2022-07-09','2022-11-30');
            // 循环添加任务
            foreach($days as $k=>$v){
                // 添加任务队列
                $this->redis->rpush("task_queue",$v);
            }
        }
        return $this->redis->lrange("task_queue",0,-1);
    }

    // 执行任务
    public function run(){

        // 插入结果
        $result = [];

        // 启动事务
        Db::startTrans();

            try {
                $datetime = '2022-01-01';
                // 取队列第一个值
                $d = $this->redis->lindex('task_queue',0);
                $res = 0;
                if($d !== false && $d !== null){
                    $datetime = date('Y-m-d',strtotime($datetime."+{$d}day"));
                    $sql = $this->setSql("'$datetime'");
                    // 查询数据
                    $data = Db::connect("sqlsrv")->Query($sql);
                    // 实例化
                    $table = Db::connect("mysql2")->table('sp_customer_stock_sale_year_copy');
                    // 执行插入
                    $res = $table->insertAll($data);
                    // 执行完毕从任务列表,弹出这个任务
                    $this->redis->lpop('task_queue');
                    // 添加任务完成记录
                    $this->redis->rpush('finish_task',$d);
                    // 提交事务
                    Db::commit();
                    echo '<pre>';
                    print_r($res);
                    $this->setData($res);
                    return $res;
                }
            }catch (\Exception $e){

                // 回滚事务
                Db::rollback();
                // 日志
                $this->setLog($e->getMessage());
                return false;
            }
        return false;
    }

    /**
     * 增加数据
     */
    public function run2()
    {
         // 插入结果
        $result = [];

        // 启动事务
        Db::startTrans();

            try {
                $datetime = '2022-07-09';
                // 取队列第一个值
                $d = $this->redis->lindex('task_queue',0);
                $res = 0;
                if($d !== false && $d !== null){
                    $datetime = date('Y-m-d',strtotime($datetime."+{$d}day"));
                    $sql = $this->getSql("'$datetime'");
                    // 查询数据
                    $data = Db::connect("sqlsrv")->Query($sql);
                    // 实例化
                    $table = Db::connect("mysql2")->table('sp_customer_stock_sale_autumn');
                    if(count($data) > 3000){
                        $list = $this->batch($data);
                        $total = 0;
                        foreach ($list as $k => $v){
                           $total += $table->insertAll($v);
                        }
                        $res = count($list).'=='.$total;
                    }else{
                         // 执行插入
                        $res = $table->insertAll($data);
                    }

                    // 执行完毕从任务列表,弹出这个任务
                    $this->redis->lpop('task_queue');
                    // 添加任务完成记录
                    $this->redis->rpush('finish_task',$d);
                    // 提交事务
                    Db::commit();
                    echo '<pre>';
                    print_r($res);
                    $this->setData($res);
                    return $res;
                }
            }catch (\Exception $e){

                // 回滚事务
                Db::rollback();
                // 日志
                $this->setLog($e->getMessage());
                return false;
            }
        return false;
    }

    /**
     * 数据分批
     */
    public function batch($data)
    {
        $arr = [];
        for ($i=0;$i<=4;$i++){
            $arr[$i] = [];
        }
        foreach ($data as $k=>$v){
            switch ($k%5){
                case 0:
                $arr[0][] = $v;
                break;
                case 1:
                $arr[1][] = $v;
                break;
                case 2:
                $arr[2][] = $v;
                break;
                case 3:
                $arr[3][] = $v;
                break;
                case 4:
                $arr[4][] = $v;
                break;
                default:
                $arr[5][] = $v;
                break;
            }
        }
        return $arr;
    }

    /**
     * 获取当前天数
     */
    public function getThisDay($date)
    {
        return date("z",strtotime($date))+1;
    }
    // 获取一年相差天数
    public function daysbetweendates($date1, $date2){
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        $days = ceil(($date2 - $date1)/86400);
        return range(0,$days);
    }

    // 写入数据文件
    public function setData($data = 0,$fielname = 'file')
    {
        file_put_contents("{$fielname}.txt",$data.'  '.date('Y/m/d H:i:s')."\r\n",FILE_APPEND);
    }

    // 写入错误日志
    public function setLog($msg)
    {
        return file_put_contents('log.txt', var_export($msg,true).'  '.date('Y/m/d H:i:s')."\r\n",FILE_APPEND);
    }

    public function getSql($datetime)
    {
        $sql = "SELECT 
	{$datetime} AS Date,
	CASE WHEN EC.CustomItem15='长沙云仓' OR EC.CustomItem15='南昌云仓' THEN '长沙南昌云仓集' ELSE EC.CustomItem15 END AS YunCang,
	EC.CustomItem30 AS WenDai,
	EC.CustomItem36 AS WenQu,
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
	EG.GoodsNo,
	SUM(ECS.Quantity) AS StockQuantity,
	SUM(ECS.Quantity*EGPT.[成本价]) AS StockCost,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECS.Quantity ELSE 0 END) AS SaleQuantity,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECS.Quantity*ERG.DiscountPrice ELSE 0 END) AS SalesVolume,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECS.Quantity*EGPT.[零售价] ELSE 0 END) AS RetailAmount,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECS.Quantity*EGPT.[成本价] ELSE 0 END) AS CostAmount
FROM ErpCustomer EC 
LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
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
						WHERE CONVERT(VARCHAR(10),ER.RetailDate,23)={$datetime}
						GROUP BY ERG.RetailID,ERG.GoodsId) ERG ON ECS.BillId=ERG.RetailID AND ECS.GoodsId=ERG.GoodsId 
WHERE EC.MathodId IN (4,7)
	AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
	AND EG.TimeCategoryName1=2022
	AND EG.TimeCategoryName2 LIKE '%秋%'
	AND CONVERT(VARCHAR(10),ECS.StockDate,23) <= {$datetime}
	AND EC.CustomerName NOT LIKE '%常熟%'
GROUP BY 
	EC.CustomItem15,
	EC.CustomItem30,
	EC.CustomItem36,
	EG.TimeCategoryName1,
	EG.TimeCategoryName2,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName,
	EG.StyleCategoryName,
	EG.StyleCategoryName1,
	EG.GoodsNo
HAVING SUM(ECS.Quantity) !=0 
	OR -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECS.Quantity ELSE 0 END) !=0
;";
        return $sql;
    }

    // 设置Sql语句
    public function setSql($datetime)
    {
        $sql = "SELECT 
	{$datetime} AS Date,
	EC.State AS State,
	EC.CustomItem30 AS WenDai,
	EC.CustomItem36 AS WenQu,
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
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity ELSE 0 END) AS SaleQuantity,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=1	 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales00/28/37/44/100/160/S],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=2  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales29/38/46/105/165/M],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=3  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales30/39/48/110/170/L],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=4  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales31/40/50/115/175/XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=5  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales32/41/52/120/180/2XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=6  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales33/42/54/125/185/3XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=7  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales34/43/56/190/4XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=8  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales35/44/58/195/5XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=9  THEN ECSD.Quantity ELSE NULL END ) AS  [Sales36/6XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=10 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales38/7XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} AND EBGS.ViewOrder=11 THEN ECSD.Quantity ELSE NULL END ) AS  [Sales40/8XL],
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity*ERG.DiscountPrice ELSE 0 END) AS SalesVolume,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity*EGPT.[零售价] ELSE 0 END) AS RetailAmount,
	-SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity*EGPT.[成本价] ELSE 0 END) AS CostAmount
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
						WHERE CONVERT(VARCHAR(10),ER.RetailDate,23)={$datetime}
						GROUP BY ERG.RetailID,ERG.GoodsId) ERG ON ECS.BillId=ERG.RetailID AND ECS.GoodsId=ERG.GoodsId 
WHERE EC.MathodId IN (4,7)
	AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
	AND CONVERT(VARCHAR(10),ECS.StockDate,23) <= {$datetime}
GROUP BY 
	EC.State,
	EC.CustomItem30,
	EC.CustomItem36,
	EG.TimeCategoryName1,
	EG.TimeCategoryName2,
	EG.CategoryName1,
	EG.CategoryName2,
	EG.CategoryName,
	EG.StyleCategoryName,
	EG.StyleCategoryName1
HAVING SUM(ECSD.Quantity) !=0 
	OR -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23)={$datetime} THEN ECSD.Quantity ELSE 0 END) !=0
";
        return $sql;
    }

    // 产科任务完成情况
    public function showTaskInfo()
    {
        $task = $this->redis->lrange("task_queue",0,-1);
        $finish_task = $this->redis->lrange("finish_task",0,-1);
        echo '<pre>';
        print_r($task);
        print_r($finish_task);
        die;
    }

    // 清空任务列表
    public function clearTask()
    {
        $this->redis->del(['task_queue','finish_task']);
        $task = $this->redis->lrange("task_queue",0,-1);
        $finish_task = $this->redis->lrange("finish_task",0,-1);
        echo '<pre>';
        print_r($task);
        print_r($finish_task);
        die;
    }

}