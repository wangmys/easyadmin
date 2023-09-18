<?php
namespace app\api\controller;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * @ControllerAnnotation(title="报表跑数")
 *
 */
class Table extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_wechat = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_wechat = Db::connect('wechat');
    }

    // 目标
    public function mubiao_data()
    {
        $date = input('date') ? input('date') : date('Y-m-d', time());

        $sql = "
            select 
            R.name as 店铺名称,
            SUM(CASE WHEN concat(N.start , '-01') = DATE_ADD(curdate(),interval -day(curdate())+1 day)
                    THEN R.s1 + R.s2 + R.s3 + R.s4 + R.s5 + R.s6 + R.s7 + R.s8 + R.s9 + R.s10 + R.s11 + R.s12 + R.s13 + R.s14 + R.s15 + R.s16 + R.s17 + R.s18 + R.s19 + R.s20 + R.s21 + R.s22 + R.s23 + R.s24 + R.s25 + R.s26 + R.s27 + R.s28 + R.s29 + R.s30 + R.s31 END) AS 本月目标,
            /*SUM(CASE WHEN concat(N.start , '-01') = date_add(curdate()-1-day(curdate()-1)+1,interval -1 month)
                    THEN R.s1 + R.s2 + R.s3 + R.s4 + R.s5 + R.s6 + R.s7 + R.s8 + R.s9 + R.s10 + R.s11 + R.s12 + R.s13 + R.s14 + R.s15 + R.s16 + R.s17 + R.s18 + R.s19 + R.s20 + R.s21 + R.s22 + R.s23 + R.s24 + R.s25 + R.s26 + R.s27 + R.s28 + R.s29 + R.s30 + R.s31 END) AS 上月目标,*/
                    CASE WHEN day(NOW())=1 THEN R.s1
                            WHEN day(NOW())=2 THEN R.s2
                            WHEN day(NOW())=3 THEN R.s3
                            WHEN day(NOW())=4 THEN R.s4
                            WHEN day(NOW())=5 THEN R.s5
                            WHEN day(NOW())=6 THEN R.s6
                            WHEN day(NOW())=7 THEN R.s7
                            WHEN day(NOW())=8 THEN R.s8
                            WHEN day(NOW())=9 THEN R.s9
                            WHEN day(NOW())=10 THEN R.s10
                            WHEN day(NOW())=11 THEN R.s11
                            WHEN day(NOW())=12 THEN R.s12
                            WHEN day(NOW())=13 THEN R.s13
                            WHEN day(NOW())=14 THEN R.s14
                            WHEN day(NOW())=15 THEN R.s15
                            WHEN day(NOW())=16 THEN R.s16
                            WHEN day(NOW())=17 THEN R.s17
                            WHEN day(NOW())=18 THEN R.s18
                            WHEN day(NOW())=19 THEN R.s19
                            WHEN day(NOW())=20 THEN R.s20
                            WHEN day(NOW())=21 THEN R.s21
                            WHEN day(NOW())=22 THEN R.s22
                            WHEN day(NOW())=23 THEN R.s23
                            WHEN day(NOW())=24 THEN R.s24
                            WHEN day(NOW())=25 THEN R.s25
                            WHEN day(NOW())=26 THEN R.s26
                            WHEN day(NOW())=27 THEN R.s27
                            WHEN day(NOW())=28 THEN R.s28
                            WHEN day(NOW())=29 THEN R.s29
                            WHEN day(NOW())=30 THEN R.s30
                            WHEN day(NOW())=31 THEN R.s31
                            END AS 今日目标,
            date_format(now(),'%Y-%m-%d') AS 更新日期
            from wechat.shopdaytasknewtz R
            left join wechat.shopstask N on R.pid=N.id
            WHERE (concat(N.start , '-01') = DATE_ADD(DATE_ADD(curdate(), interval -1 DAY),interval -day(DATE_ADD(curdate(), interval -1 DAY))+1 day)
                -- OR concat(N.start , '-01') = date_add(curdate()-1-day(curdate()-1)+1,interval -1 month)
            )
                AND R.name IS NOT NULL
            GROUP BY R.name
        
        ";
        // 查wechat
        $select_data = $this->db_wechat->query($sql);
        $count = count($select_data);
        if ($select_data) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('sp_customer_mubiao_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_easyA->execute('TRUNCATE sp_customer_mubiao_ww;');

            $select_chunk = array_chunk($select_data, 500);
    
            foreach($select_chunk as $key => $val) {
                $insertAll = $this->db_bi->table('sp_customer_mubiao_ww')->strict(false)->insertAll($val);
            }
            
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "sp_customer_mubiao_ww 更新成功，{$count}！"
            ]);
        }
    }


    public function liushui_data()
    {
        $sql = "
            SELECT 
                CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END AS  经营模式,
                EC.State 省份,
                SUBSTRING(EC.CustomerName, 1, charindex('店',EC.CustomerName) ) 店铺名称,
                EC.CustomerCode 店铺编号,
                ISNULL(EC.CustomItem18,0) 督导,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)=CONVERT(VARCHAR(10),GETDATE(),23) THEN ERG.Quantity*ERG.DiscountPrice END) AS 今天流水,
                SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23)=CONVERT(VARCHAR(7),GETDATE(),23) THEN ERG.Quantity*ERG.DiscountPrice END) 本月流水,
                SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)>= CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity*ERG.DiscountPrice END) AS 近七天流水,
                CASE WHEN CONVERT(VARCHAR(10),MIN(ER.RetailDate),23)<= CONVERT(VARCHAR(10),GETDATE()-6,23)  AND SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)>= CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity*ERG.DiscountPrice END) >0 
                                                        THEN SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)>= CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity*ERG.DiscountPrice END)/7
                        WHEN SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)>= CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity*ERG.DiscountPrice END) >0 
                            THEN  SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23)>= CONVERT(VARCHAR(10),GETDATE()-6,23) THEN ERG.Quantity*ERG.DiscountPrice END)/DATEDIFF(DAY, CONVERT(VARCHAR(10),MIN(ER.RetailDate),23), CONVERT(VARCHAR(10),GETDATE()+1,23))
                        END AS 近七天日均,
                    DATEDIFF(DAY, CONVERT(VARCHAR(10),MIN(ER.RetailDate),23), CONVERT(VARCHAR(10),GETDATE()+1,23)) 最大可除天数,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM ErpCustomer EC 
            LEFT JOIN ErpRetail ER ON EC.CustomerId= ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.MathodId IN (4,7)
                AND ER.CodingCodeText='已审结'
                AND ER.RetailDate > GETDATE()-32
            GROUP BY 
                EC.MathodId,
                EC.State,
                EC.CustomerName,
                EC.CustomerCode,
                EC.CustomItem18
            HAVING SUM(CASE WHEN CONVERT(VARCHAR(7),ER.RetailDate,23)=CONVERT(VARCHAR(7),GETDATE(),23) THEN ERG.Quantity*ERG.DiscountPrice END) IS NOT NULL
            ;
        ";
        // 查康雷
        $select_data = $this->db_sqlsrv->query($sql);
        $count = count($select_data);
        if ($select_data) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('sp_customer_liushui_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_easyA->execute('TRUNCATE sp_customer_liushui_ww;');

            $select_chunk = array_chunk($select_data, 500);
    
            foreach($select_chunk as $key => $val) {
                $this->db_bi->table('sp_customer_liushui_ww')->strict(false)->insertAll($val);
            }
            
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "sp_customer_liushui_ww 更新成功，{$count}！"
            ]);
        }
    }

    public function oldCustomer_data()
    {
        // 1
        $sql_customer_first_date_ww = "
            SELECT
                T.性质,
                T.CustomerName,
                MIN(T.first_date) AS first_date,
                T.Region,
                T.State,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
            (
            SELECT
                A.性质,
                A.CustomerId,
                A.CustomerName,
                MIN(A.日期) AS first_date,
                A.Region,
                A.State
            FROM
            (
            SELECT
            CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
            EC.CustomerId ,
            EC.CustomerName ,
            EBCR.Region,
            EC.State,
            CONVERT(VARCHAR,ER.RetailDate,23) AS 日期,
            SUM(ERG.Quantity*ERG.DiscountPrice) 销售
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND ER.RetailDate<CONVERT(VARCHAR,(select dateAdd(DD,-364,getdate())),23)
            GROUP BY
                EC.CustomerId ,
                EC.CustomerName,
                EBCR.Region,
                EC.MathodId,
                EC.State,
                CONVERT(VARCHAR,ER.RetailDate,23)
            ) AS A
            WHERE A.销售>0
            GROUP BY
                A.性质,
                A.CustomerId,
                A.CustomerName,
                A.Region,
                A.State
            
            UNION ALL
            
            --粤一区
            SELECT
                A.性质,
                A.CustomerId,
                A.店铺名称,
                MIN(A.日期) AS first_date,
                A.Region,
                A.State
            FROM
            (
            SELECT
            CASE WHEN EC.MathodId=7 THEN '直营' ELSE '直营' END AS 性质 ,
            EC.CustomerId ,
            LEFT(EC.CustomerName,4) AS 店铺名称,
            '两广区' AS Region,
            EC.State,
            CONVERT(VARCHAR,ER.RetailDate,23) AS 日期,
            SUM(ERG.Quantity*ERG.DiscountPrice) 销售
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            JOIN (SELECT CustomerName FROM ErpCustomer WHERE ShutOut=0 AND RegionId=93) A ON LEFT(EC.CustomerName,4)=A.CustomerName
            WHERE EC.ShutOut=1			--店铺营业中
                AND EC.RegionId =55				--属于闭店区
                AND EC.CustomerName  NOT LIKE '%内购%'
                --AND ShopNature=0       --店铺性质实体店
                AND ER.RetailDate<CONVERT(VARCHAR,(select dateAdd(DD,-364,getdate())),23)
            GROUP BY
                EC.CustomerId ,
                EC.CustomerName,
                EC.MathodId,
                EC.State,
                CONVERT(VARCHAR,ER.RetailDate,23)
            ) AS A
            WHERE A.销售>0
            GROUP BY
                A.性质,
                A.CustomerId,
                A.店铺名称,
                A.Region,
                A.State
            ) T
            GROUP BY
                T.性质,
                T.CustomerName,
                T.Region,
                T.State
            ORDER BY first_date;
        ";

        // 2
        $sql_customer_lastyear_day_ww = "
            SELECT
                    T.性质 ,
                    T.区域 ,
                    T.店铺名称,
                    SUM(T.去年同日) AS 去年同日,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
            (
            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 去年同日
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23)= CONVERT(VARCHAR,(select dateAdd(DD,-365,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName

            UNION ALL

            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '直营' END AS 性质 ,
                    '两广区' AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    LEFT(EC.CustomerName,4) AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 去年同日
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            JOIN (SELECT CustomerName FROM ErpCustomer WHERE ShutOut=0 AND RegionId=93) A ON LEFT(EC.CustomerName,4)=A.CustomerName
            WHERE EC.ShutOut=1			--店铺营业中
                AND EC.RegionId =55				--属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND CONVERT(VARCHAR,ER.RetailDate,23)=CONVERT(VARCHAR,(select dateAdd(DD,-365,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName
            ) T
            GROUP BY
                    T.性质 ,
                    T.区域 ,
                    T.店铺名称
            ;
        ";

        // 3
        $sql_customer_lastyear_month_ww = "
            SELECT
                    T.性质 ,
                    T.区域 ,
                    T.店铺名称,
                    SUM(T.去年同月) AS 去年同月,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
            (
            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 去年同月
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN  CONVERT(VARCHAR,(SELECT DATEADD(mm, DATEDIFF(mm,0,getdate())-12, 0)),23) AND CONVERT(VARCHAR,(select dateAdd(DD,-365,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName

            UNION ALL

            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '直营' END AS 性质 ,
                    '两广区' AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    LEFT(EC.CustomerName,4) AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 去年同月
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            JOIN (SELECT CustomerName FROM ErpCustomer WHERE ShutOut=0 AND RegionId=93) A ON LEFT(EC.CustomerName,4)=A.CustomerName
            WHERE EC.ShutOut=1			--店铺营业中
                AND EC.RegionId =55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN  CONVERT(VARCHAR,(SELECT DATEADD(mm, DATEDIFF(mm,0,getdate())-12, 0)),23) AND CONVERT(VARCHAR,(select dateAdd(DD,-365,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName
            ) T
            GROUP BY
                    T.性质 ,
                    T.区域 ,
                    T.店铺名称
            ;
        ";

        // 4
        $sql_customer_month_ww = "
            SELECT
                T.性质,
                T.区域,
                T.店铺编号,
                T.店铺名称,
                SUM(本月业绩) AS 本月业绩,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
            (SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    CONVERT(VARCHAR,ER.RetailDate,23) AS 日期,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 本月业绩
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN  CONVERT(VARCHAR,(SELECT DATEADD(mm, DATEDIFF(mm,0,getdate()), 0)),23) AND CONVERT(VARCHAR,getdate(),23) --零售核销单时间
                --AND EC.State='广东省'
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName,
                CONVERT(VARCHAR,ER.RetailDate,23)
            ) T
            INNER JOIN
            (SELECT
            A.CustomerName,
            MIN(RTIME) AS 首营业时间
            FROM
            (
            SELECT
                LEFT(EC.CustomerName,4) AS CustomerName,
                CONVERT(VARCHAR, DATEADD(YEAR , 1 , ER.RetailDate),23) AS RTIME,
                SUM(ERG.Quantity*ERG.DiscountPrice) AS 销售
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            GROUP BY
                LEFT(EC.CustomerName,4),
                CONVERT(VARCHAR, DATEADD(YEAR , 1 , ER.RetailDate),23)
            HAVING SUM(ERG.Quantity*ERG.DiscountPrice)>0
            ) AS A
            WHERE A.销售>0
            GROUP BY A.CustomerName) B
            ON B.CustomerName=LEFT(T.店铺名称,4) --AND CONVERT(VARCHAR,ER.RetailDate,23)=A.RTIME
            WHERE (T.日期>B.首营业时间 OR T.日期=B.首营业时间)
            GROUP BY
                T.性质,
                T.区域,
                T.店铺编号,
                T.店铺名称
            ;        
        ";

        // 5
        $sql_customer_yesterday_ww = "
            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 昨天销量,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23)=CONVERT(VARCHAR,GETDATE(),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName
            ;
        ";

        // 6
        $sql_customer_region_b_ww = "
            SELECT
            CASE WHEN EC.MathodId=7 THEN '加盟' WHEN EC.MathodId=4 THEN '直营' ELSE NULL END AS 经营模式,
            EBCR.Region AS 区域,
            CASE WHEN EC.MathodId=7 THEN '赖炽明' ELSE  EC.CustomItem18 END AS 督导,
            EC.CustomerName AS 店铺,
            CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            WHERE EC.ShutOut=0
            AND EC.MathodId IN (4,7)
            ORDER BY
            EC.MathodId,
            EBCR.Region ,
            EC.CustomItem18 ,
            EC.CustomerName
            ;
        ";

        // 7
        $sql_customer_year_before_last_day_ww = "
            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 前年同日
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23)=CONVERT(VARCHAR,(select dateAdd(DD,-730,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName

            UNION ALL

            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '直营' END AS 性质 ,
                    CASE WHEN EBCR.Region='闭店区' THEN  '粤一区' END AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    LEFT(EC.CustomerName,4) AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 前年同日
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            JOIN (SELECT CustomerName FROM ErpCustomer WHERE ShutOut=0 AND RegionId=93) A ON LEFT(EC.CustomerName,4)=A.CustomerName
            WHERE EC.ShutOut=1			--店铺营业中
                AND EC.RegionId =55				--属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND CONVERT(VARCHAR,ER.RetailDate,23)=CONVERT(VARCHAR,(select dateAdd(DD,-730,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName
            ;
        ";

        // 8
        $sql_customer_year_before_last_month_ww = "
            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 前年同月
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                --AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN CONVERT(VARCHAR,(SELECT DATEADD(mm, DATEDIFF(mm,0,getdate())-24, 0)),23) AND CONVERT(VARCHAR,(select dateAdd(DD,-730,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName

            UNION ALL

            SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '直营' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    LEFT(EC.CustomerName,4) AS 店铺名称,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 前年同月
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            JOIN (SELECT CustomerName FROM ErpCustomer WHERE ShutOut=0 AND RegionId=93) A ON LEFT(EC.CustomerName,4)=A.CustomerName
            WHERE EC.ShutOut=1			--店铺营业中
                AND EC.RegionId =55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                --AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN  CONVERT(VARCHAR,(SELECT DATEADD(mm, DATEDIFF(mm,0,getdate())-24, 0)),23) AND CONVERT(VARCHAR,(select dateAdd(DD,-730,getdate())),23)
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName
            ;
        
        ";

        // 9
        $sql_customer_month_qiannian_ww = "
            SELECT
                T.性质,
                T.区域,
                T.店铺编号,
                T.店铺名称,
                SUM(本月业绩) AS 前年同期本月业绩,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
            (SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    CONVERT(VARCHAR,ER.RetailDate,23) AS 日期,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 本月业绩
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23) BETWEEN  CONVERT(VARCHAR,(SELECT DATEADD(mm, DATEDIFF(mm,0,getdate()), 0)),23) AND CONVERT(VARCHAR,getdate(),23) --零售核销单时间
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName,
                CONVERT(VARCHAR,ER.RetailDate,23)
            ) T
            INNER JOIN
            (SELECT
            A.CustomerName,
            MIN(RTIME) AS 首营业时间
            FROM
            (
            SELECT
                LEFT(EC.CustomerName,4) AS CustomerName,
                CONVERT(VARCHAR, DATEADD(YEAR , 2 , ER.RetailDate),23) AS RTIME,
                SUM(ERG.Quantity*ERG.DiscountPrice) AS 销售
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            GROUP BY
                LEFT(EC.CustomerName,4),
                CONVERT(VARCHAR, DATEADD(YEAR , 2 , ER.RetailDate),23)
            HAVING SUM(ERG.Quantity*ERG.DiscountPrice)>0
            ) AS A
            WHERE A.销售>0
            GROUP BY A.CustomerName) B
            ON B.CustomerName=LEFT(T.店铺名称,4) --AND CONVERT(VARCHAR,ER.RetailDate,23)=A.RTIME
            WHERE (T.日期>B.首营业时间 OR T.日期=B.首营业时间)
            GROUP BY
                T.性质,
                T.区域,
                T.店铺编号,
                T.店铺名称
            ;
        ";

        // 10
        $sql_customer_day_qiannian_ww = "
            SELECT
                T.性质,
                T.区域,
                T.店铺编号,
                T.店铺名称,
                SUM(本月业绩) AS 前年同期今天,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
            (SELECT
                    CASE WHEN EC.MathodId=4 THEN '直营' ELSE '加盟' END AS 性质 ,
                    EBCR.Region AS 区域 ,
                    EC.CustomerId AS 店铺编号,
                    EC.CustomerName AS 店铺名称,
                    CONVERT(VARCHAR,ER.RetailDate,23) AS 日期,
                    SUM(ERG.Quantity*ERG.DiscountPrice) AS 本月业绩
            FROM ErpCustomer EC
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            WHERE EC.ShutOut=0			--店铺营业中
                AND EC.MathodId IN (4,7) 		--加盟店和直营店
                AND EC.RegionId !=55				--不属于闭店区
                AND ER.CodingCodeText='已审结'
                AND EC.CustomerName  NOT LIKE '%内购%'
                AND ShopNature=0
                AND CONVERT(VARCHAR,ER.RetailDate,23) = CONVERT(VARCHAR,getdate(),23) --零售核销单时间
            GROUP BY
                EC.MathodId,
                EBCR.Region,
                EC.CustomerId,
                EC.CustomerName,
                CONVERT(VARCHAR,ER.RetailDate,23)
            ) T
            INNER JOIN
            (SELECT
            A.CustomerName,
            MIN(RTIME) AS 首营业时间
            FROM
            (
            SELECT
                LEFT(EC.CustomerName,4) AS CustomerName,
                CONVERT(VARCHAR, DATEADD(YEAR , 2 , ER.RetailDate),23) AS RTIME,
                SUM(ERG.Quantity*ERG.DiscountPrice) AS 销售
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            GROUP BY
                LEFT(EC.CustomerName,4),
                CONVERT(VARCHAR, DATEADD(YEAR , 2 , ER.RetailDate),23)
            HAVING SUM(ERG.Quantity*ERG.DiscountPrice)>0
            ) AS A
            WHERE A.销售>0
            GROUP BY A.CustomerName) B
            ON B.CustomerName=LEFT(T.店铺名称,4) --AND CONVERT(VARCHAR,ER.RetailDate,23)=A.RTIME
            WHERE (T.日期>B.首营业时间 OR T.日期=B.首营业时间)
            GROUP BY
                T.性质,
                T.区域,
                T.店铺编号,
                T.店铺名称
            ;
        ";

        // 1
        $select_customer_first_date_ww = $this->db_sqlsrv->query($sql_customer_first_date_ww);
        // // 2
        $select_customer_lastyear_day_ww = $this->db_sqlsrv->query($sql_customer_lastyear_day_ww);
        // // 3
        $select_customer_lastyear_month_ww = $this->db_sqlsrv->query($sql_customer_lastyear_month_ww);
        // // // 4
        $select_customer_month_ww = $this->db_sqlsrv->query($sql_customer_month_ww);
        // // // 5
        $select_customer_yesterday_ww = $this->db_sqlsrv->query($sql_customer_yesterday_ww);
        // // // 6
        $select_customer_region_b_ww = $this->db_sqlsrv->query($sql_customer_region_b_ww);
        // // // 7
        $select_customer_year_before_last_day_ww = $this->db_sqlsrv->query($sql_customer_year_before_last_day_ww);
        // // // 8
        $select_customer_year_before_last_month_ww = $this->db_sqlsrv->query($sql_customer_year_before_last_month_ww);
        // // // 9
        $select_customer_month_qiannian_ww = $this->db_sqlsrv->query($sql_customer_month_qiannian_ww);
        // // 10
        $select_customer_day_qiannian_ww = $this->db_sqlsrv->query($sql_customer_day_qiannian_ww);


        // die;
        // 1
        if ($select_customer_first_date_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_first_date_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_first_date_ww;');

            $select_chunk1 = array_chunk($select_customer_first_date_ww, 500);
    
            foreach($select_chunk1 as $key1 => $val1) {
                $this->db_bi->table('customer_first_date_ww')->strict(false)->insertAll($val1);
            }
        }

        // 2
        if ($select_customer_lastyear_day_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_lastyear_day_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_lastyear_day_ww;');

            $select_chunk2 = array_chunk($select_customer_lastyear_day_ww, 500);
    
            foreach($select_chunk2 as $key2 => $val2) {
                $this->db_bi->table('customer_lastyear_day_ww')->strict(false)->insertAll($val2);
            }
        }

        // 3
        if ($select_customer_lastyear_month_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_lastyear_month_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_lastyear_month_ww;');

            $select_chunk3 = array_chunk($select_customer_lastyear_month_ww, 500);
    
            foreach($select_chunk3 as $key3 => $val3) {
                $this->db_bi->table('customer_lastyear_month_ww')->strict(false)->insertAll($val3);
            }
        }

        // 4
        if ($select_customer_month_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_month_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_month_ww;');

            $select_chunk4 = array_chunk($select_customer_month_ww, 500);
    
            foreach($select_chunk4 as $key4 => $val4) {
                $this->db_bi->table('customer_month_ww')->strict(false)->insertAll($val4);
            }
        }

        // 5
        if ($select_customer_yesterday_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_yesterday_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_yesterday_ww;');

            $select_chunk5 = array_chunk($select_customer_yesterday_ww, 500);
    
            foreach($select_chunk5 as $key5 => $val5) {
                $this->db_bi->table('customer_yesterday_ww')->strict(false)->insertAll($val5);
            }
        }

        // 6
        if ($select_customer_region_b_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_region_b_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_region_b_ww;');

            $select_chunk6 = array_chunk($select_customer_region_b_ww, 500);
    
            foreach($select_chunk6 as $key6 => $val6) {
                $this->db_bi->table('customer_region_b_ww')->strict(false)->insertAll($val6);
            }
        }

        // 7
        if ($select_customer_year_before_last_day_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_year_before_last_day_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_year_before_last_day_ww;');

            $select_chunk7 = array_chunk($select_customer_year_before_last_day_ww, 500);
    
            foreach($select_chunk7 as $key7 => $val7) {
                $this->db_bi->table('customer_year_before_last_day_ww')->strict(false)->insertAll($val7);
            }
        }

        // 8
        if ($select_customer_year_before_last_month_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_year_before_last_month_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_year_before_last_month_ww;');

            $select_chunk8 = array_chunk($select_customer_year_before_last_month_ww, 500);
    
            foreach($select_chunk8 as $key8 => $val8) {
                $this->db_bi->table('customer_year_before_last_month_ww')->strict(false)->insertAll($val8);
            }
        }

        // 9
        if ($select_customer_month_qiannian_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_month_qiannian_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_month_qiannian_ww;');

            $select_chunk9 = array_chunk($select_customer_month_qiannian_ww, 500);
    
            foreach($select_chunk9 as $key9 => $val9) {
                $this->db_bi->table('customer_month_qiannian_ww')->strict(false)->insertAll($val9);
            }
        }

        // 10
        if ($select_customer_day_qiannian_ww) {
            // dump($select_data); die;
            // 删 easyadmin2
            // $this->db_bi->table('customer_day_qiannian_ww')->where([
            //     ['更新日期', '=', date('Y-m-d')]
            // ])->delete();
            $this->db_bi->execute('TRUNCATE customer_day_qiannian_ww;');

            $select_chunk10 = array_chunk($select_customer_day_qiannian_ww, 500);
    
            foreach($select_chunk10 as $key10 => $val10) {
                $this->db_bi->table('customer_day_qiannian_ww')->strict(false)->insertAll($val10);
            }
        }

        // 视图层
        // old_customer_state_detail_ww
        $sql_old_customer_state_detail_ww = "
            SELECT 
                CASE WHEN A.`店铺名称`='合计'  AND A.`省份`='合计'  THEN CONCAT(A.`经营模式`,'合计') ELSE A.`经营模式` END AS 经营模式 ,
            CASE WHEN A.`店铺名称`='合计'  AND A.`省份`!='合计'  THEN CONCAT(A.`省份`,'合计') 
                        ELSE A.`省份` END  AS `省份`,
                A.店铺名称 ,
                CASE WHEN A.店铺名称 = '合计' THEN '合计' ELSE A.`首单日期` END AS 首单日期,
                A.`前年同日`,
                A.去年同日,
                A. 昨天销量,
                A.`前年同期今天`,
                CONCAT(ROUND( ((A.`前年同期今天`/A.`前年同日` -1 )*100),2),'%') AS 前年对比今年昨日递增率 ,
                CONCAT(ROUND( ((A.`昨天销量`/A.`去年同日` -1 )*100),2),'%') AS 昨日递增率 ,
                A.`前年同月`,
                A.去年同月,
                A.本月业绩,
                A.`前年同期本月业绩`,
                CONCAT(ROUND( ((A.`前年同期本月业绩`/A.`前年同月` -1 )*100),2),'%') AS 前年对比今年累销递增率 ,
                CONCAT(ROUND( ((A.`本月业绩`/A.`去年同月` -1 )*100),2),'%') AS 累销递增率 ,
                A.`前年累销递增金额差`,
                A.累销递增金额差,
                DATE_SUB(curdate(),INTERVAL -1 DAY) AS 更新时间
        FROM
        (SELECT 
                IFNULL(CFD.`性质`,'总计') AS 经营模式 ,
                IFNULL(CFD.State,'合计') AS 省份,
                IFNULL(CFD.CustomerName,'合计') AS 店铺名称 ,
                CFD.first_date AS 首单日期,
                SUM(QNBR.`前年同期今天`) AS 前年同期今天,
                SUM(CYBLD.`前年同日`) AS 前年同日,
                SUM(CLD.`去年同日`) AS 去年同日,
                SUM(CY.`昨天销量`) AS 昨天销量,
                SUM(CYBLM.`前年同月`) AS 前年同月,
                SUM(CLM.`去年同月`) 去年同月,
                SUM(CM.`本月业绩`) 本月业绩,
                SUM(QNBY.`前年同期本月业绩`) AS 前年同期本月业绩,
                SUM(QNBY.`前年同期本月业绩`) - SUM(CYBLM.`前年同月`) AS 前年累销递增金额差,
                SUM(CM.`本月业绩`) - SUM(CLM.`去年同月`) AS 累销递增金额差
        FROM customer_first_date_ww CFD
        LEFT JOIN customer_region_b_ww CR ON CFD.CustomerName=CR.`店铺`
        LEFT JOIN customer_lastyear_day_ww CLD ON CFD.CustomerName=CLD.`店铺名称`
        LEFT JOIN customer_yesterday_ww CY ON CFD.CustomerName= CY.`店铺名称`
        LEFT JOIN customer_lastyear_month_ww CLM ON CFD.CustomerName=CLM.`店铺名称`
        LEFT JOIN customer_month_ww CM ON CFD.CustomerName=CM.`店铺名称`
        LEFT JOIN customer_year_before_last_day_ww CYBLD ON CFD.CustomerName=CYBLD.`店铺名称`
        LEFT JOIN customer_year_before_last_month_ww CYBLM ON CFD.CustomerName=CYBLM.`店铺名称`
        LEFT JOIN customer_month_qiannian_ww QNBY ON CFD.CustomerName=QNBY.`店铺名称`
        LEFT JOIN customer_day_qiannian_ww QNBR ON CFD.CustomerName=QNBR.`店铺名称`
        WHERE CFD.CustomerName NOT LIKE '%停用%'
        GROUP BY  CFD.`性质` ,CFD.State,CFD.CustomerName
        WITH ROLLUP
        ) AS A
        ;
        ";

        $select_old_customer_state_detail_ww = $this->db_bi->query($sql_old_customer_state_detail_ww);
        if ($select_old_customer_state_detail_ww) {
            // dump($select_data); die;
            // 保留历史
            $this->db_bi->table('old_customer_state_detail_ww')->where([
                ['更新时间', '=', date('Y-m-d', strtotime('+1 day', time()))]
            ])->delete();

            $select_chunk11 = array_chunk($select_old_customer_state_detail_ww, 500);
    
            foreach($select_chunk11 as $key11 => $val11) {
                $this->db_bi->table('old_customer_state_detail_ww')->strict(false)->insertAll($val11);
            }
        }

        // old_customer_state_ww
        $sql_old_customer_state_ww = "
            SELECT 
                A.`店铺数`,
                A.两年以上老店数,
                A.`经营模式`,
                A.`省份`,
                A.`前年同日`,
                A.`去年同日`,
                A.`昨天销量`,
                A.`前年同期今天`,
                CONCAT(ROUND( ((A.`前年同期今天`/A.`前年同日` -1 )*100),2),'%') AS 前年对比今年昨日递增率 ,
                CONCAT(ROUND( ((A.`昨天销量`/A.`去年同日` -1 )*100),2),'%') AS 昨日递增率 ,
                A.`前年同月`,
                A.`去年同月`,
                A.`本月业绩`,
                A.`前年同期本月业绩`,
                CONCAT(ROUND( ((A.`前年同期本月业绩`/A.`前年同月` -1 )*100),2),'%') AS 前年对比今年累销递增率 ,
                CONCAT(ROUND( ((A.`本月业绩`/A.`去年同月` -1 )*100),2),'%') AS 累销递增率 ,
                A.`前年累销递增金额差`,
                A.`累销递增金额差`,
                DATE_SUB(curdate(),INTERVAL -1 DAY) AS 更新时间
            FROM 
            (SELECT 
                    COUNT(DISTINCT CustomerName) AS 店铺数,
                    COUNT(DISTINCT CASE WHEN CFD.first_date<= DATE_ADD(CURDATE(),INTERVAL -731 DAY) THEN CFD.CustomerName END) 两年以上老店数,
                    IFNULL(CFD.`性质`,'总计') AS 经营模式 ,
                    IFNULL(CFD.State,'合计') AS 省份,
                    SUM(QNBR.`前年同期今天`) AS 前年同期今天,
                    SUM(CYBLD.`前年同日`) AS 前年同日,
                    SUM(CLD.`去年同日`) AS 去年同日,
                    SUM(CY.`昨天销量`) AS 昨天销量,
                    SUM(CYBLM.`前年同月`) AS 前年同月,
                    SUM(CLM.`去年同月`) 去年同月,
                    SUM(CM.`本月业绩`) 本月业绩,
                    SUM(QNBY.`前年同期本月业绩`) AS 前年同期本月业绩,
                    SUM(QNBY.`前年同期本月业绩`) - SUM(CYBLM.`前年同月`) AS 前年累销递增金额差,
                    SUM(CM.`本月业绩`) - SUM(CLM.`去年同月`) AS 累销递增金额差
            FROM customer_first_date_ww CFD
            LEFT JOIN customer_region_b_ww CR ON CFD.CustomerName=CR.`店铺`
            LEFT JOIN customer_lastyear_day_ww CLD ON CFD.CustomerName=CLD.`店铺名称`
            LEFT JOIN customer_yesterday_ww CY ON CFD.CustomerName= CY.`店铺名称`
            LEFT JOIN customer_lastyear_month_ww CLM ON CFD.CustomerName=CLM.`店铺名称`
            LEFT JOIN customer_month_ww CM ON CFD.CustomerName=CM.`店铺名称`
            LEFT JOIN customer_year_before_last_day_ww CYBLD ON CFD.CustomerName=CYBLD.`店铺名称`
            LEFT JOIN customer_year_before_last_month_ww CYBLM ON CFD.CustomerName=CYBLM.`店铺名称`
            LEFT JOIN customer_month_qiannian_ww QNBY ON CFD.CustomerName=QNBY.`店铺名称`
            LEFT JOIN customer_day_qiannian_ww QNBR ON CFD.CustomerName=QNBR.`店铺名称`
            WHERE CFD.CustomerName NOT LIKE '%停用%'
            GROUP BY 
                CFD.`性质`,
                CFD.State
            WITH ROLLUP	) AS A
            ;
        ";

        $select_old_customer_state_ww = $this->db_bi->query($sql_old_customer_state_ww);
        if ($select_old_customer_state_ww) {
            // dump($select_data); die;
            // 保留历史
            $this->db_bi->table('old_customer_state_ww')->where([
                ['更新时间', '=', date('Y-m-d', strtotime('+1 day', time()))]
            ])->delete();

            $select_chunk12 = array_chunk($select_old_customer_state_ww, 500);
    
            foreach($select_chunk12 as $key12 => $val12) {
                $this->db_bi->table('old_customer_state_ww')->strict(false)->insertAll($val12);
            }
        }

        // old_customer_state_2_ww
        $sql_old_customer_state_2_ww = "
            SELECT 
                A.`店铺数`,
                A.两年以上老店数,
                A.`省份`,
                A.`前年同日`,
                A.`去年同日`,
                A.`昨天销量`,
                A.`前年同期今天`,
                CONCAT(ROUND( ((A.`前年同期今天`/A.`前年同日` -1 )*100),2),'%') AS 前年对比今年昨日递增率 ,
                CONCAT(ROUND( ((A.`昨天销量`/A.`去年同日` -1 )*100),2),'%') AS 昨日递增率 ,
                A.`前年同月`,
                A.`去年同月`,
                A.`本月业绩`,
                A.`前年同期本月业绩`,
                CONCAT(ROUND( ((A.`前年同期本月业绩`/A.`前年同月` -1 )*100),2),'%') AS 前年对比今年累销递增率 ,
                CONCAT(ROUND( ((A.`本月业绩`/A.`去年同月` -1 )*100),2),'%') AS 累销递增率 ,
                A.`前年累销递增金额差`,
                A.`累销递增金额差`,
                DATE_SUB(curdate(),INTERVAL -1 DAY) AS 更新时间
            FROM 
            (SELECT 
                    COUNT(DISTINCT CustomerName) AS 店铺数,
                    COUNT(DISTINCT CASE WHEN CFD.first_date<= DATE_ADD(CURDATE(),INTERVAL -731 DAY) THEN CFD.CustomerName END) 两年以上老店数,
                    IFNULL(CFD.State,'合计') AS 省份,
                    SUM(QNBR.`前年同期今天`) AS 前年同期今天,
                    SUM(CYBLD.`前年同日`) AS 前年同日,
                    SUM(CLD.`去年同日`) AS 去年同日,
                    SUM(CY.`昨天销量`) AS 昨天销量,
                    SUM(CYBLM.`前年同月`) AS 前年同月,
                    SUM(CLM.`去年同月`) 去年同月,
                    SUM(CM.`本月业绩`) 本月业绩,
                    SUM(QNBY.`前年同期本月业绩`) AS 前年同期本月业绩,
                    SUM(QNBY.`前年同期本月业绩`) - SUM(CYBLM.`前年同月`) AS 前年累销递增金额差,
                    SUM(CM.`本月业绩`) - SUM(CLM.`去年同月`) AS 累销递增金额差
            FROM customer_first_date_ww CFD
            LEFT JOIN customer_region_b_ww CR ON CFD.CustomerName=CR.`店铺`
            LEFT JOIN customer_lastyear_day_ww CLD ON CFD.CustomerName=CLD.`店铺名称`
            LEFT JOIN customer_yesterday_ww CY ON CFD.CustomerName= CY.`店铺名称`
            LEFT JOIN customer_lastyear_month_ww CLM ON CFD.CustomerName=CLM.`店铺名称`
            LEFT JOIN customer_month_ww CM ON CFD.CustomerName=CM.`店铺名称`
            LEFT JOIN customer_year_before_last_day_ww CYBLD ON CFD.CustomerName=CYBLD.`店铺名称`
            LEFT JOIN customer_year_before_last_month_ww CYBLM ON CFD.CustomerName=CYBLM.`店铺名称`
            LEFT JOIN customer_month_qiannian_ww QNBY ON CFD.CustomerName=QNBY.`店铺名称`
            LEFT JOIN customer_day_qiannian_ww QNBR ON CFD.CustomerName=QNBR.`店铺名称`
            WHERE CFD.CustomerName NOT LIKE '%停用%'
            GROUP BY 
                CFD.State
            WITH ROLLUP	) AS A
            ;
        ";

        $select_old_customer_state_2_ww = $this->db_bi->query($sql_old_customer_state_2_ww);
        if ($select_old_customer_state_2_ww) {
            // dump($select_data); die;
            // 保留历史
            $this->db_bi->table('old_customer_state_2_ww')->where([
                ['更新时间', '=', date('Y-m-d', strtotime('+1 day', time()))]
            ])->delete();

            $select_chunk13 = array_chunk($select_old_customer_state_2_ww, 500);
    
            foreach($select_chunk13 as $key13 => $val13) {
                $this->db_bi->table('old_customer_state_2_ww')->strict(false)->insertAll($val13);
            }
        }
    }

    public function test2() {
        echo date('Y-m-d', strtotime('+1 day', time()));
    }

}
