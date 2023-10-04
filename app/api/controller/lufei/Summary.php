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

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
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

    // 业绩表现情况
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
        ";
        $this->db_easyA->execute($sql_更新若干);

        $sql_环比 = "
            update cwl_summary as s
            left join cwl_dianpuyejihuanbi_handle as h on s.店铺名称=h.店铺名称
            set
                s.环比=h.今日环比
            where 
                h.更新日期='{$昨天}'
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

    // 近三天季节销售占比
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
                1
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
                $this->db_easyA->table('cwl_summary')->where(['店铺名称' => $val2['店铺名称']])->update(['上新提醒' => $val2['提醒']]);
            } 
        }

        // 引流
        $res = http_get('http://www.easyadmin1.com/admin/system.dress.dress/index_api');
        $res = http_get('http://im.babiboy.com/admin/system.dress.dress/index_api');
        // 配饰
        $res = http_get('http://www.easyadmin1.com/admin/system.dress.index/list_api.html');
        $res = http_get('http://im.babiboy.com/admin/system.dress.index/list_api.html');
    }

    // 传入开始结束时间戳
    public function getDaysDiff($beginDate, $endDate) {
        $days = round( ($endDate - $beginDate) / 3600 / 24);
        return $days;
    }

     /**
     * 引流
     */
    public function yinliu()
    {
        // $Dress = new Dress(App);
        // dump(Dress->index_api());

        // die;
        $url = 'http://www.easyadmin1.com/admin/system.dress.dress/index_api';
        $res = http_get($url);
        // $res = http_get('http://www.easyadmin1.com/api/Tableupdate/receipt_receiptNotice');
        $res =json_decode($res, true);
        
    }

}
