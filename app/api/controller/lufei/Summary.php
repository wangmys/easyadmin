<?php
namespace app\api\controller\lufei;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;
use think\App;

use app\admin\controller\system\dress\Dress;

/**
 * @ControllerAnnotation(title="问题汇总表")
 */
class Summary extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    protected $目标月份 = "";

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');

        $目标月份 = date('Y-m');

        if (date('Y-m-d') == date('Y-m-01')) {
            $目标月份 = date('Y-m', strtotime('-1 month'));
        }
        $this->目标月份 = $目标月份;
    }

    public function seasionHandle($seasion = "夏季,秋季") {
        $seasionStr = "";
        $seasion = explode(',', $seasion);
        foreach ($seasion as $key => $val) {
            if ($key + 1 == count($seasion)) {
                if ($val == '春季') {
                    $seasionStr .= "'初春','正春','春季'";
                } elseif ($val == '夏季') {
                    $seasionStr .= "'初夏','盛夏','夏季'";
                } elseif ($val == '秋季') {
                    $seasionStr .= "'初秋','深秋','秋季'";
                } elseif ($val == '冬季') {
                    $seasionStr .= "'初冬','深冬','冬季'";
                }
            } else {
                if ($val == '春季') {
                    $seasionStr .= "'初春','正春','春季',";
                } elseif ($val == '夏季') {
                    $seasionStr .= "'初夏','盛夏','夏季',";
                } elseif ($val == '秋季') {
                    $seasionStr .= "'初秋','深秋','秋季',";
                } elseif ($val == '冬季') {
                    $seasionStr .= "'初冬','深冬','冬季',";
                }
            }
        }

        return $seasionStr;
    }

    // 几秒
    public function getData0() {
        $目标月份 = date('Y-m');

        if (date('Y-m-d') == date('Y-m-01')) {
            $目标月份 = date('Y-m', strtotime('-1 month'));
        }
        $sql = "
            select 商品专员,省份,经营模式,店铺名称,目标月份 from cwl_customitem17_yeji where 目标月份='{$目标月份}'
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->table('cwl_summary')->where([
                ['目标月份' , '=', $目标月份]
            ])->delete();
            // $this->db_easyA->execute('TRUNCATE cwl_customitem17_yeji;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_summary')->strict(false)->insertAll($val);
            }
        }
    }

    // 业绩表现情况 几十秒
    public function getData1()
    {
         // 每月1号
        if (date('Y-m-d') == date('Y-m-01')) {
            $开始= date("Y-m-01", strtotime('-1month')); 
            $目标月份 = date('Y-m', strtotime('-1 month'));
        } else {
            $开始= date("Y-m-01"); 
            $目标月份 = date('Y-m');
        }
        $昨天 = date('Y-m-d', strtotime('-1 day')); 
        $今天 = date('Y-m-d'); 

        $sql_首单日期 = "
            update cwl_summary as s
            left join customer_pro as p on s.店铺名称=p.CustomerName
            set
                s.首单日期=p.首单日期
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_首单日期);

        $到结束剩余天数 = $this->getDaysDiff(strtotime($开始), strtotime($今天));
    
        $sql_更新若干 = "
            update cwl_summary as s
            left join cwl_customitem17_yeji as y on s.店铺名称=y.店铺名称 and y.目标月份='{$目标月份}'
            set
                s.本月目标=y.本月目标,
                s.当前流水=y.实际累计流水,
                s.当前流水 = y.实际累计流水,
                s.目标达成率 = y.目标达成率,
                s.日均流水 = y.实际累计流水 / {$到结束剩余天数},
                s.剩余日均流水 = y.`100%缺口_日均额`
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_更新若干);

        $sql_环比 = "
            update cwl_summary as s
            left join cwl_dianpuyejihuanbi_handle as h on s.店铺名称=h.店铺名称
            set
                s.环比=h.今日环比
            where 
                h.更新日期='{$昨天}'
                AND s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_环比);

        $sql_同日 = "
            select 店铺名称,昨日递增率 from old_customer_state_detail_ww where 更新时间='{$昨天}'
        ";
        $select_同日 = $this->db_bi->query($sql_同日);
        foreach($select_同日 as $key => $val) {
            $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val['店铺名称']])->update(['同比' => $val['昨日递增率']]);
        }
    }

    // 近三天季节销售占比 1-2几分钟
    public function getData2() {
        $sql_data = "
            SELECT
                EC.State AS 省份,
                ER.CustomerName AS 店铺名称,
                EC.CustomItem17 AS 商品负责人,
                CASE
                    EG.TimeCategoryName2
                    WHEN '初春' THEN '春季'
                    WHEN '正春' THEN '春季'
                    WHEN '春季' THEN '春季'
                    WHEN '初秋' THEN '秋季'
                    WHEN '深秋' THEN '秋季'
                    WHEN '秋季' THEN '秋季'
                    WHEN '初夏' THEN '夏季'
                    WHEN '盛夏' THEN '夏季'
                    WHEN '夏季' THEN '夏季'
                    WHEN '冬季' THEN '冬季'
                    WHEN '初冬' THEN '冬季'
                    WHEN '深冬' THEN '冬季'
                END AS 季节归集,
                EG.CategoryName1 AS 一级分类,
                CASE
                    WHEN EG.CategoryName1 = '内搭' OR EG.CategoryName1 = '外套' THEN '上装' ELSE EG.CategoryName1       
                END AS 一级分类修订,
                EG.TimeCategoryName1 AS 年份,
                SUM(ERG.Quantity) AS 销售数量,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期,   
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.RetailDate >= DATEADD(DAY, -3, CAST(GETDATE() AS DATE)) 
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE)) 
                AND ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0
                AND EC.RegionId NOT IN ('40', '55', '84', '85',  '97')
                AND EG.CategoryName1 IN ('内搭', '外套','下装','鞋履')
                AND EBC.Mathod IN ('直营', '加盟')
            GROUP BY 
                ER.CustomerName,
                EC.State,
                EC.CustomItem17,
                EG.CategoryName1,
                EG.TimeCategoryName2,
                EG.TimeCategoryName1,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd')	
            ORDER BY ER.CustomerName,EC.State ASC, FORMAT(ER.RetailDate, 'yyyy-MM-dd') ASC         
        ";
        $select = $this->db_sqlsrv->query($sql_data);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_summary_retail_1;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_summary_retail_1')->strict(false)->insertAll($val);
            }

            $sql_三天销售占比 = "
                select
                    t.店铺名称,sum(t.上装春占比) as 上装春占比,sum(t.上装夏占比) as 上装夏占比,sum(t.上装秋占比) as 上装秋占比,sum(t.上装冬占比) as 上装冬占比,
                    sum(t.下装占比) as 下装占比, 
                    sum(t.鞋履占比) as 鞋履占比,
                    date_format(now(),'%Y-%m-%d') as 更新日期 
                From
                (
                    SELECT 
                        t1.*,
                        CASE WHEN t1.季节归集='春季' AND t1.一级分类修订 = '上装' THEN t1.销售金额 / t1.店铺总销售金额  END AS 上装春占比,
                        CASE WHEN t1.季节归集='夏季' AND t1.一级分类修订 = '上装' THEN t1.销售金额 / t1.店铺总销售金额  END AS 上装夏占比,
                        CASE WHEN t1.季节归集='秋季' AND t1.一级分类修订 = '上装' THEN t1.销售金额 / t1.店铺总销售金额  END AS 上装秋占比,
                        CASE WHEN t1.季节归集='冬季' AND t1.一级分类修订 = '上装' THEN t1.销售金额 / t1.店铺总销售金额  END AS 上装冬占比,
                        CASE WHEN t1.一级分类修订 = '下装' THEN t1.销售金额 / t1.店铺总销售金额  END AS 下装占比,
                        CASE WHEN t1.一级分类修订 = '鞋履' THEN t1.销售金额 / t1.店铺总销售金额  END AS 鞋履占比
                    FROM 
                    (
                        SELECT
                            m1.省份,m1.店铺名称,m1.季节归集,m1.一级分类修订,
                            sum(m1.销售数量) as 销售数量,
                            sum(m1.销售金额) as 销售金额,
                            (select sum(销售金额) from cwl_summary_retail_1 where 省份=m1.省份 AND 店铺名称=m1.店铺名称) as 店铺总销售金额 
                        FROM
                            cwl_summary_retail_1 as m1 
                        WHERE
                            m1.一级分类修订 IN ( '上装', '下装', '鞋履' ) 
                        GROUP BY
                            m1.店铺名称,m1.季节归集,m1.一级分类修订
                    ) AS t1
                ) AS t
                group by t.店铺名称
            ";
            $select_三天销售占比 = $this->db_easyA->query($sql_三天销售占比);
            if ($select_三天销售占比) {
                $this->db_easyA->execute('TRUNCATE cwl_summary_retail_2;');
                $chunk_list2 = array_chunk($select_三天销售占比, 500);
                foreach($chunk_list2 as $key2 => $val2) {
                   $this->db_easyA->table('cwl_summary_retail_2')->strict(false)->insertAll($val2);
                }
            }
        }
    }

    // 几秒
    public function getData3() {
        $sql_更新销售占比 = "
            update cwl_summary as s 
            left join cwl_summary_retail_2 as r on s.店铺名称 = r.店铺名称
            set
                s.上装春占比 = r.上装春占比,
                s.上装夏占比 = r.上装夏占比,
                s.上装秋占比 = r.上装秋占比,
                s.上装冬占比 = r.上装冬占比,
                s.下装占比 = r.下装占比,
                s.鞋履占比 = r.下装占比
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_更新销售占比);

        $sql_天气上新提醒 = "
            select
                    w.店铺名称,
                    w.`秋季SKC`,w.`冬季SKC`,
                    c.`秋季SKC` as `配置秋季SKC`,
                    c.`冬季SKC` as `配置冬季SKC`,
                    w.提醒
            from
                cwl_weathertips_customer as w 
            left join cwl_weathertips_config as c on c.id = 1
            where
                    w.提醒 is not null 
        ";
        $select_天气上新提醒 = $this->db_easyA->query($sql_天气上新提醒);
        foreach ($select_天气上新提醒 as $key => $val) {
            if ($val['提醒'] == '上秋') {
                if ($val['秋季SKC'] < $val['配置秋季SKC']) {
                    $select_天气上新提醒[$key]['提醒'] = '上秋';     
                } else {
                    $select_天气上新提醒[$key]['提醒'] = NULL;
                }
            } elseif ($val['提醒'] == '上冬') {
                if ($val['冬季SKC'] < $val['配置冬季SKC']) {
                    $select_天气上新提醒[$key]['提醒'] = '上冬';     
                } else {
                    $select_天气上新提醒[$key]['提醒'] = NULL;
                }
            } elseif($val['提醒'] == '上秋冬') {
                if ($val['秋季SKC'] < $val['配置秋季SKC'] && $val['冬季SKC'] < $val['配置冬季SKC']) {
                    $select_天气上新提醒[$key]['提醒'] = '上秋冬';     
                } elseif ($val['秋季SKC'] < $val['配置秋季SKC']) {
                    $select_天气上新提醒[$key]['提醒'] = '上秋'; 
                } elseif ($val['冬季SKC'] < $val['配置冬季SKC']) {
                    $select_天气上新提醒[$key]['提醒'] = '上冬'; 
                } else {
                    $select_天气上新提醒[$key]['提醒'] = NULL;
                }
            }
        }

        // 更新上新提醒
        foreach ($select_天气上新提醒 as $key2 => $val2) {
            if ($val2['提醒']) {            
                // $目标月份 = $this->目标月份;
                $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val2['店铺名称'], '目标月份' => $this->目标月份])->update(['上新提醒' => $val2['提醒']]);
            } 
        }

        // 引流
        // $res = http_get('http://www.easyadmin1.com/admin/system.dress.dresscwl/index_api');
        $res = http_get('http://im.babiboy.com/admin/system.dress.dresscwl/index_api');
        // 配饰
        // $res = http_get('http://www.easyadmin1.com/admin/system.dress.indexcwl/list_api');
        $res = http_get('http://im.babiboy.com/admin/system.dress.indexcwl/list_api');

        $sql_大小码缺码提醒 = "
            update cwl_summary as s left join
            (
                select
                    t.店铺名称,
                    t.`未上柜提醒00/28/37/44/100/160/S` + `未上柜提醒29/38/46/105/165/M` + `未上柜提醒34/43/56/190/4XL` + `未上柜提醒35/44/58/195/5XL` + `未上柜提醒36/6XL` + `未上柜提醒38/7XL` + `未上柜提醒_40` AS 缺码
                from
                (
                    SELECT 
                        店铺名称,
                        sum(case when `未上柜提醒00/28/37/44/100/160/S` is not null then 1 else 0  end) as `未上柜提醒00/28/37/44/100/160/S`,
                        sum(case when `未上柜提醒29/38/46/105/165/M` is not null then 1  else 0 end) as `未上柜提醒29/38/46/105/165/M`,
                        sum(case when `未上柜提醒34/43/56/190/4XL` is not null then 1  else 0 end) as `未上柜提醒34/43/56/190/4XL`,
                        sum(case when `未上柜提醒35/44/58/195/5XL` is not null then 1  else 0 end) as `未上柜提醒35/44/58/195/5XL`,
                        sum(case when `未上柜提醒36/6XL` is not null then 1 else 0 end) as `未上柜提醒36/6XL`,
                        sum(case when `未上柜提醒38/7XL` is not null then 1 else 0 end) as `未上柜提醒38/7XL`,
                        sum(case when `未上柜提醒_40` is not null then 1 else 0 end) as `未上柜提醒_40`
                    FROM `cwl_daxiao_handle` group by 店铺名称
                ) as t
            ) as m on s.店铺名称 = m.店铺名称
            set
                s.大小码缺少提醒 = case when m.缺码>0 then '缺' end
            WHERE 
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_大小码缺码提醒);

        $slq_售空SKC数 = "
            SELECT
                店铺名称,
                sum(1) as 数量
            from cwl_skauto_res 
            where 1
                AND 在途库存 + 已配未发 <= 0
                AND 售空提醒 = '售空'
            group by 店铺名称
        ";
        $select_售空SKC数 = $this->db_easyA->query($slq_售空SKC数);
        if ($select_售空SKC数) {
            foreach($select_售空SKC数 as $key3 => $val3) {
                $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val3['店铺名称'], '目标月份' => $this->目标月份])->update(['售空SKC数' => $val3['数量']]); 
            }
        }

        $slq_即将售空SKC数 = "
            SELECT
                店铺名称,
                sum(1) as 数量
            from cwl_skauto_res 
            where 1
                AND 在途库存 + 已配未发 <= 0
                AND 售空提醒 = '即将售空'
            group by 店铺名称
        ";
        $select_即将售空SKC数 = $this->db_easyA->query($slq_即将售空SKC数);
        if ($select_即将售空SKC数) {
            foreach($select_即将售空SKC数 as $key4 => $val4) {
                $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val4['店铺名称'], '目标月份' => $this->目标月份])->update(['即将售空SKC数' => $val4['数量']]); 
            }
        }

        $sql_不动销 = "
            SELECT
                店铺简称 as 店铺名称,
                `5-10天`,
                `10-15天`,
                `20-30天`,
                `30天以上`,
                `考核标准`,
                `考核标准占比`,
                需要调整SKC数 
            FROM
                cwl_budongxiao_result_sys 
            WHERE
                考核结果 = '不合格'
        ";
        $select_不动销 = $this->db_easyA->query($sql_不动销);
        if ($select_不动销) {
            foreach ($select_不动销 as $key5 => $val5) {
                // $val5[$val5['考核标准']]
                $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val5['店铺名称'], '目标月份' => $this->目标月份])->update(['不动销占比' => $val5['考核标准占比'], '不动销SKC数' => $val5['需要调整SKC数']]); 
            }
        }

        $date = date('Y-m-d');
        $sql_断码率 = "
            UPDATE cwl_summary AS s
            LEFT JOIN cwl_duanmalv_table1_1 as t on s.店铺名称=t.店铺名称 and t.更新日期='{$date}'
            SET
                s.`断码率整体齐码率`=t.`齐码率-整体`,
                s.`断码率TOP考核齐码率`=t.`齐码率-TOP考核`
            WHERE
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_断码率);

        $sql_单款超量SKC数 = "
            UPDATE cwl_summary AS s
            LEFT JOIN (
                SELECT
                    店铺名称,
                    count(*) as 数量 
                FROM
                    `cwl_chaoliang_sk` 
                WHERE 
                    `提醒备注`='请注意'
                GROUP BY 店铺名称
            ) as t on s.店铺名称=t.店铺名称
            SET
                s.`单款超量SKC数`=t.数量
            WHERE
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_单款超量SKC数);
    }

    // 传入开始结束时间戳
    public function getDaysDiff($beginDate, $endDate) {
        $days = round( ($endDate - $beginDate) / 3600 / 24);
        return $days;
    }

    // 新品大类监控情况
    public function getData4() {    
        $date = date('Y-m-d', strtotime('-1day', time()));
        $dataEnd = input('date') ? input('date') : date('Y-m-d');
        // 1.销售占比
        $sql_销售占比数据源 = "
            SELECT
                ER.CustomerName AS 店铺名称,
                EBC.Mathod AS 经营模式,
                EC.CustomItem36 AS 温区,
                SUM ( ERG.Quantity ) AS 数量,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
                    CASE
                        WHEN EG.TimeCategoryName2 IN ( '初春', '正春', '春季' ) THEN
                        '春季' 
                        WHEN EG.TimeCategoryName2 IN ( '初夏', '盛夏', '夏季' ) THEN
                        '夏季' 
                        WHEN EG.TimeCategoryName2 IN ( '初秋', '深秋', '秋季' ) THEN
                        '秋季' 
                        WHEN EG.TimeCategoryName2 IN ( '初冬', '深冬', '冬季' ) THEN
                        '冬季' 
                    END AS 季节归集,
                    EG.TimeCategoryName2 AS 季节,
                    EG.CategoryName1 AS 一级分类,
                    EG.StyleCategoryName AS 风格,
                    FORMAT ( ER.RetailDate, 'yyyy-MM-dd' ) AS 销售日期 
                FROM
                    ErpRetail AS ER
                    LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                    LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                    LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                    LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId 
                WHERE
                    ER.CodingCodeText = '已审结' 
                    AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                    AND EBC.Mathod IN ( '直营', '加盟' ) 
                    AND ER.RetailDate BETWEEN '{$date}' AND '{$dataEnd}' 
                    AND EG.TimeCategoryName1 IN ('2023', '2024', '2025')
                GROUP BY
                    ER.CustomerName,
                    EBC.Mathod,
                    EC.CustomItem36,
                    EG.CategoryName1,
                    EG.StyleCategoryName,
                    EG.TimeCategoryName2,
                    FORMAT ( ER.RetailDate, 'yyyy-MM-dd' ) 
                ORDER BY
            ER.CustomerName
        ";
        $select_销售占比数据源 = $this->db_sqlsrv->query($sql_销售占比数据源);
        if ($select_销售占比数据源) {
            $this->db_easyA->execute('TRUNCATE cwl_summary_retail_pro;');
            $chunk_list = array_chunk($select_销售占比数据源, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_summary_retail_pro')->strict(false)->insertAll($val);
            }
        }

        // 2.
        $sql_销售占比外套 = "
            update cwl_summary as s
            left join (
                select 
                    m1.店铺名称,
                    sum(m1.数量) as 数量,
                    sum(m1.销售金额) as 销售金额,
                    m1.一级分类,
                    m2.总销售金额
                from cwl_summary_retail_pro  as m1
                left join (
                    select 
                        店铺名称, sum(数量) as 总数量, sum(销售金额) as 总销售金额
                    from cwl_summary_retail_pro 
                    where 1
                    group by
                        店铺名称
                ) as m2 on m1.店铺名称 = m2.店铺名称
                where 1
                    and m1.一级分类 = '外套'
                group by
                    m1.店铺名称,m1.一级分类
            ) as t on s.店铺名称 = t.店铺名称
            set
                s.销售贡献比_外套 = t.销售金额 / t.总销售金额
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_销售占比外套);

        $sql_销售占比内搭 = "
            update cwl_summary as s
            left join (
                select 
                    m1.店铺名称,
                    sum(m1.数量) as 数量,
                    sum(m1.销售金额) as 销售金额,
                    m1.一级分类,
                    m2.总销售金额
                from cwl_summary_retail_pro  as m1
                left join (
                    select 
                        店铺名称, sum(数量) as 总数量, sum(销售金额) as 总销售金额
                    from cwl_summary_retail_pro 
                    where 1
                    group by
                        店铺名称
                ) as m2 on m1.店铺名称 = m2.店铺名称
                where 1
                    and m1.一级分类 = '内搭'
                group by
                    m1.店铺名称,m1.一级分类
            ) as t on s.店铺名称 = t.店铺名称
            set
                s.销售贡献比_内搭 = t.销售金额 / t.总销售金额
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_销售占比内搭);

        $sql_销售占比下装 = "
            update cwl_summary as s
            left join (
                select 
                    m1.店铺名称,
                    sum(m1.数量) as 数量,
                    sum(m1.销售金额) as 销售金额,
                    m1.一级分类,
                    m2.总销售金额
                from cwl_summary_retail_pro  as m1
                left join (
                    select 
                        店铺名称, sum(数量) as 总数量, sum(销售金额) as 总销售金额
                    from cwl_summary_retail_pro 
                    where 1
                    group by
                        店铺名称
                ) as m2 on m1.店铺名称 = m2.店铺名称
                where 1
                    and m1.一级分类 = '下装'
                group by
                    m1.店铺名称,m1.一级分类
            ) as t on s.店铺名称 = t.店铺名称
            set
                s.销售贡献比_下装 = t.销售金额 / t.总销售金额
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_销售占比下装);

        $sql_销售占比鞋履 = "
            update cwl_summary as s
            left join (
                select 
                    m1.店铺名称,
                    sum(m1.数量) as 数量,
                    sum(m1.销售金额) as 销售金额,
                    m1.一级分类,
                    m2.总销售金额
                from cwl_summary_retail_pro  as m1
                left join (
                    select 
                        店铺名称, sum(数量) as 总数量, sum(销售金额) as 总销售金额
                    from cwl_summary_retail_pro 
                    where 1
                    group by
                        店铺名称
                ) as m2 on m1.店铺名称 = m2.店铺名称
                where 1
                    and m1.一级分类 = '鞋履'
                group by
                    m1.店铺名称,m1.一级分类
            ) as t on s.店铺名称 = t.店铺名称
            set
                s.销售贡献比_鞋履 = t.销售金额 / t.总销售金额
            where
                s.目标月份 = '{$this->目标月份}'
        ";
        $this->db_easyA->execute($sql_销售占比鞋履);
    }



}
