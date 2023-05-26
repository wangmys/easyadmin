<?php


namespace app\api\service\bi\command;

use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use think\App;
use think\facade\Db;
use app\admin\model\command\CommandErrorTotal;
use app\admin\model\command\CommandLog;
use app\admin\model\command\CommandErrorLog;

/**
 * 引流配饰数据拉取服务
 * Class CommandService
 * @package app\api\service\bi\command
 */
class CommandService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];

    protected $code = 0;
    protected $msg = '';

    public function __construct()
    {
        $this->totalModel = new CommandErrorTotal;
        $this->logModel = new CommandLog;
        $this->errorLogModel = new CommandErrorLog;
    }

    /**
     * 拉取指令日志
     */
    public function pullData()
    {
        // 查询sql
        $getSql = $this->getLastSql();
        // 执行查询商品专员指令日志
        $log = Db::connect("sqlsrv")->query($getSql);
        // 定义错误指令统计结果集
        $result = [];
        // 定义错误指令记录结果集
        $result_log = [];
        // 循环判断
        foreach ($log as $k => $v){
            $log[$k]['is_error']??$log[$k]['is_error'] = 0;
            // 是否符合异常指令判断第一层
            if($v['单据类型'] == '店铺调出单' && $v['清空操作']=='调出清空'){
                // 异常指令判断第二层(下一条指令是否为[店铺收货单],且库存数量大于0)
                $item = $log[$k+1]??[];
                if(!empty($item) && $item['单据类型']=='店铺收货单' && $item['库存数量'] > 0 ){
                    // 月份
                    $month = date('Y-m',strtotime($v['变动时间']));
                    // 年份
                    $year = date('Y',strtotime($v['变动时间']));
                    if(empty($result[$v['商品负责人']][$month])) $result[$v['商品负责人']][$month] = 0;
                    // 二层判断全部通过,计入错误指令结果集
                    $result[$v['商品负责人']][$month] += 1;
                    $v['year'] = $year;
                    $v['month'] = date('m',strtotime($v['变动时间']));
                    $result_log[] = $v;
                    $item['type'] = 1;
                    $item['year'] = $year;
                    $item['month'] = date('m',strtotime($v['变动时间']));
                    $result_log[] = $item;
                    $log[$k+1]['is_error'] = 1;
                }else{
                    continue;
                }
            }else{
                continue;
            }
            $log[$k]['is_error']??$log[$k]['is_error'] = 0;
        }
        $result_process = $this->processData($result);
        // 提交事务
        Db::startTrans();
        try{
            // 保存指令日志
//            $this->logModel->insertAll($log);
             // 删除错误指令记录
            $this->errorLogModel->where('id','>',0)->delete();
            // 删除错误指令统计
            $this->totalModel->where('id','>',0)->delete();

            // 保存错误指令记录
            $this->errorLogModel->saveAll($result_log);
            // 加工错误指令统计数据(重组)
            $result_process = $this->processData($result);
            // 保存错误指令统计
            $this->totalModel->insertAll($result_process);

            // 提交事务
            Db::commit();
        }catch (\Exception $e){
            // 回滚
            Db::rollback();
            $this->msg = $e->getMessage();
            return ApiConstant::ERROR_CODE;
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 数据加工
     */
    public function processData($result)
    {
        // 新数组
        $new_data = [];
        foreach ($result as $key => $val){
            foreach ($val as $k=>$v){
                $date = explode('-',$k);
                $new_data[] = [
                    '商品负责人' => $key,
                    'date_str' => $k,
                    'num' => $v,
                    'year' => $date[0],
                    'month' => $date[1],
                    'create_time' => time()
                ];
            }
        }
        return $new_data;
    }

    /**
     * 获取执行sql
     * @param string $date
     * @param string $op
     */
    public function getLastSql($date = '',$op = 'eq')
    {
        // 时间
        $date = date('Y-m-d');
        $start = date('Y-01-01');
        $sql = "SELECT
            A.*
            ,
        CASE

                WHEN A.[单据类型] = '店铺收货单' THEN
                NULL ELSE ROW_NUMBER ( ) OVER ( PARTITION BY A.店铺名称, A.货号, A.清空操作 ORDER BY A.清空时间 )
            END 清空次数,
        CASE

            WHEN A.[单据类型] = '店铺调出单' THEN
            NULL ELSE ROW_NUMBER ( ) OVER ( PARTITION BY A.店铺名称, A.货号, A.清空操作 ORDER BY A.清空时间 )
            END 七天内收货次数
        FROM
            (
            SELECT
                TT.CustomItem17 AS 商品负责人,
                TT.CustomerName AS 店铺名称,
                TT.GoodsNo AS 货号,
                TT.[单据类型],
                TT.Quantity AS 变动数量,
                TT.[库存数量],
                TT.CreateTime AS 变动时间,
                TT.[清空操作],
                TT.[清空时间],
                TT.[清空货号],
                COUNT ( 1 ) OVER ( PARTITION BY TT.CustomerName, TT.GoodsNo ) 次数,
                TT.[收货来源]
            FROM
                (
                SELECT
                    T.CustomerName,
                    T.CustomItem17,
                    T.GoodsNo,
                    T.[单据类型],
                    T.Quantity,
                    T.[库存数量],
                    T.CreateTime,
                    T.[清空操作],
                CASE

                        WHEN T.[清空操作] = '调出清空' THEN
                        T.CreateTime
                        WHEN MAX ( T.[清空操作] ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo ) = '调出清空'
                        AND T.CreateTime> MAX ( T.[清空时间] ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo ) THEN
                            MAX ( T.[清空时间] ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo )
                            WHEN MAX ( T.[清空操作] ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo ) = '调出清空' THEN
                            MIN ( T.[清空时间] ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo )
                        END AS 清空时间,
                    CASE

                            WHEN MAX ( T.[清空操作] ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo ) = '调出清空' THEN
                            MIN ( T.清空货号 ) OVER ( PARTITION BY T.CustomerName, T.GoodsNo )
                        END AS 清空货号,
                        T.[收货来源]
                    FROM
                        (
                        SELECT
                            EC.CustomerName,
                            EC.CustomItem17,
                            ECS.BillId,
                        CASE

                                WHEN ECS.BillType= 'ErpCustOutbound' THEN
                                '店铺调出单'
                                WHEN ECS.BillType= 'ErpCustReceipt' THEN
                                '店铺收货单'
                                WHEN ECS.BillType= 'ErpRetail' THEN
                                '零售核销单' ELSE '其他'
                            END AS 单据类型,
                            ECS.GoodsId,
                            EG.GoodsNo,
                            ECS.Quantity,
                            SUM ( ECS.Quantity ) OVER ( PARTITION BY EC.CustomerId, ECS.GoodsId ORDER BY ECS.CreateTime ) AS 库存数量,
                            ECS.CreateTime,
                        CASE

                                WHEN SUM ( ECS.Quantity ) OVER ( PARTITION BY EC.CustomerId, ECS.GoodsId ORDER BY ECS.CreateTime ) <= 0
                                AND ECS.BillType= 'ErpCustOutbound'
                                AND ECS.Quantity<=- 2 THEN
                                    '调出清空'
                                END AS 清空操作,
                            CASE

                                    WHEN SUM ( ECS.Quantity ) OVER ( PARTITION BY EC.CustomerId, ECS.GoodsId ORDER BY ECS.CreateTime ) <= 0
                                    AND ECS.BillType= 'ErpCustOutbound'
                                    AND ECS.Quantity<=- 2 THEN
                                        ECS.CreateTime
                                    END AS 清空时间,
                                CASE

                                        WHEN SUM ( ECS.Quantity ) OVER ( PARTITION BY EC.CustomerId, ECS.GoodsId ORDER BY ECS.CreateTime ) <= 0
                                        AND ECS.BillType= 'ErpCustOutbound'
                                        AND ECS.Quantity<=- 2 THEN
                                            EG.GoodsNo
                                        END AS 清空货号,
                                    CASE

                                            WHEN ECR.Type= 1 THEN
                                            '仓库发出'
                                            WHEN ECR.Type= 2 THEN
                                            '店铺调拨'
                                        END 收货来源
        FROM
            ErpCustomer EC
            LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId= ECS.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId= EG.GoodsId
            LEFT JOIN ErpCustReceipt ECR ON ECS.BillId= ECR.ReceiptID
        WHERE
            EG.TimeCategoryName1= 2023
            ) T
            ) TT
        WHERE
            TT.[清空时间] IS NOT NULL
            AND TT.CreateTime>= TT.[清空时间]
            AND TT.CreateTime<= DATEADD( DAY, 7, TT.[清空时间] )
            AND TT.[单据类型] NOT IN ( '零售核销单', '其他' )
            ) A
        WHERE
            A.[次数] >= 2 and A.[变动时间] >='{$start}' and A.[变动时间] <= '{$date}'
            AND CONCAT ( A.[单据类型], A.[库存数量] ) != '店铺收货单0'
        ORDER BY
            A.[商品负责人],
            A.[店铺名称],
            A.[清空货号],
            A.[变动时间]";

        return $sql;
    }

    /**
     * 获取错误提示
     */
    public function getError($code = 0)
    {
        return !empty($this->msg)?$this->msg:ApiConstant::ERROR_CODE_LIST[$code];
    }
}