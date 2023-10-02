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

/**
 * @ControllerAnnotation(title="专员业绩")
 */
class Customitem17 extends BaseController
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

    public function getCustomer1()
    {
        $目标月份 = date('Y-m');
        if (date('Y-m-d') == date('Y-m-01')) {
            $目标月份 = date('Y-m', strtotime('-1 month'));
        }
        // 待会删除
        // $目标月份 = date('Y-m', strtotime('-1 month'));
        // 待会删除 END

        $sql = "
            select 
                CustomerName as 店铺名称,
                State as 省份,
                Mathod as 经营模式,
                CustomItem17 as 商品专员,
                '{$目标月份}' as 目标月份,    
                m.本月目标
            from customer_pro as c
            left join sp_customer_mubiao_ww as m on c.CustomerName=m.店铺名称
            group by 商品专员,经营模式,店铺名称
        ";
		
        $select = $this->db_easyA->query($sql);


        if ($select) {
            // 删除历史数据
            $this->db_easyA->table('cwl_customitem17_yeji')->where(['目标月份' => $目标月份])->delete();
            // $this->db_easyA->execute('TRUNCATE cwl_customitem17_yeji;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_customitem17_yeji')->strict(false)->insertAll($val);
            }
        }
    }

    // 老店业绩同比
    public function getCustomer2()
    {
        $截止日期 = date('Y-m-d', strtotime('-1 day'));
        $目标月份 = date('Y-m');
        if (date('Y-m-d') == date('Y-m-01')) {
            $目标月份 = date('Y-m', strtotime('-1 month'));
        }

        // 待会删除
        // $目标月份 = date('Y-m', strtotime('-1 month'));
        // $截止日期 = date('Y-m-d', strtotime('-2 day'));
        // 待会删除 END


        $sql = "
            select 
                店铺名称,
                累销递增率
            from old_customer_state_detail_ww
            where 更新时间 = '$截止日期'
                and 店铺名称 not in ('合计')
        ";
		
        $select = $this->db_bi->query($sql);


        if ($select) {
            foreach($select as $key => $val) {
                $this->db_easyA->table('cwl_customitem17_yeji')->where([
                    ['目标月份', '=', $目标月份],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->update([
                    '老店业绩同比' => $val['累销递增率']
                ]);
            }
        }
    }

    // 传入开始结束时间戳
    public function getDaysDiff($beginDate, $endDate) {
        $days = round( ($endDate - $beginDate) / 3600 / 24);
        return $days;
    }

    // 近七天，哪7天
    public function getBefore7() {
        // $今天 = date('Y-m-01');
        $今天 = date('Y-m-d');
        $本月 = strtotime(date('Y-m-01'));
        $day1 = date('Y-m-d', strtotime('-1 day', strtotime($今天)));
        // echo '<br>';
        $day2 = date('Y-m-d', strtotime('-2 day', strtotime($今天)));
        // echo '<br>';
        $day3 = date('Y-m-d', strtotime('-3 day', strtotime($今天)));
        // echo '<br>';
        $day4 = date('Y-m-d', strtotime('-4 day', strtotime($今天)));
        // echo '<br>';
        $day5 = date('Y-m-d', strtotime('-5 day', strtotime($今天)));
        // echo '<br>';
        $day6 = date('Y-m-d', strtotime('-6 day', strtotime($今天)));
        // echo '<br>';
        $day7 = date('Y-m-d', strtotime('-7 day', strtotime($今天)));

        // 每月1号
        if ($今天 == date('Y-m-01')) {
            // $今天 = date('Y-m-d');
            $本月 = strtotime(date('Y-m-01', strtotime('-1 month')));
            $day1 = date('Y-m-d', strtotime('-1 day', strtotime($今天)));
            // echo '<br>';
            $day2 = date('Y-m-d', strtotime('-2 day', strtotime($今天)));
            // echo '<br>';
            $day3 = date('Y-m-d', strtotime('-3 day', strtotime($今天)));
            // echo '<br>';
            $day4 = date('Y-m-d', strtotime('-4 day', strtotime($今天)));
            // echo '<br>';
            $day5 = date('Y-m-d', strtotime('-5 day', strtotime($今天)));
            // echo '<br>';
            $day6 = date('Y-m-d', strtotime('-6 day', strtotime($今天)));
            // echo '<br>';
            $day7 = date('Y-m-d', strtotime('-7 day', strtotime($今天)));
        }

        // 待会删除
        // $本月 = strtotime(date('Y-m-01', strtotime('-1 month')));
        // $day1 = date('Y-m-d', strtotime('-2 day', strtotime($今天)));
        // $day2 = date('Y-m-d', strtotime('-3 day', strtotime($今天)));
        // $day3 = date('Y-m-d', strtotime('-4 day', strtotime($今天)));
        // $day4 = date('Y-m-d', strtotime('-5 day', strtotime($今天)));
        // $day5 = date('Y-m-d', strtotime('-6 day', strtotime($今天)));
        // $day6 = date('Y-m-d', strtotime('-7 day', strtotime($今天)));
        // $day7 = date('Y-m-d', strtotime('-8 day', strtotime($今天)));
        // 待会删除 END
        // die;

        $res_data = [];
        if (strtotime($day1) >= $本月) {
            array_push($res_data, $day1);
        }
        if (strtotime($day2) >= $本月) {
            array_push($res_data, $day2);
        }
        if (strtotime($day3) >= $本月) {
            array_push($res_data, $day3);
        }
        if (strtotime($day4) >= $本月) {
            array_push($res_data, $day4);
        }
        if (strtotime($day5) >= $本月) {
            array_push($res_data, $day5);
        }
        if (strtotime($day6) >= $本月) {
            array_push($res_data, $day6);
        }
        if (strtotime($day7) >= $本月) {
            array_push($res_data, $day7);
        }

        // dump($res_data);die;
        // echo arrToStr($res_data);
        return $res_data;
    }

    public function getRetail()
    {
        $开始= date('Y-m-01');
        $结束= date('Y-m-d');
        $截止日期 = date('Y-m-d', strtotime('-1 day'));
        $本月最后一天 = date('Y-m-t'); 
        $到结束剩余天数 = $this->getDaysDiff(strtotime($截止日期), strtotime($本月最后一天));

        $目标月份 = date('Y-m');


        // 每月1号
        if (date('Y-m-d') == date('Y-m-01')) {
            $开始= date("Y-m-01", strtotime('-1month')); 
            $目标月份 = date('Y-m', strtotime('-1 month'));
            $本月最后一天 = date('Y-m-t', strtotime('-1month')); 
            $到结束剩余天数 = $this->getDaysDiff(strtotime($截止日期), strtotime($本月最后一天));
        }

        // 待会删除 数据调整用
        // $目标月份 = date('Y-m', strtotime('-1 month'));
        // $开始= date("Y-m-01", strtotime('-1month')); 
        // $结束= date('Y-m-d', strtotime('-1 day'));
        // $截止日期 = date('Y-m-d', strtotime('-2 day'));
        // $本月最后一天 = date('Y-m-t', strtotime('-1month')); 
        // $到结束剩余天数 = $this->getDaysDiff(strtotime($截止日期), strtotime($本月最后一天));

        // 待会删除 数据调整用 END


        // echo $开始;
        // echo '<br>';
        // echo $结束;
        // echo '<br>';
        // echo $截止日期;
        // echo '<br>';
        // echo $本月最后一天;
        // echo '<br>';
        // echo $到结束剩余天数;
        // echo '<br>';
        // echo $目标月份;


        // die;

        $落后分母 = date('d', strtotime('-1 day')) / date('t'); 
        // die;
        $sql = "
            select 
                *
            from ww_dianpuyejihuanbi_data
            where 日期>='{$开始}' and 日期< '{$结束}'
        ";
		
        $select = $this->db_bi->query($sql);
        // echo '<pre>';
        // print_r($select);die;
        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_customitem17_retail')->where(1)->delete();
            $this->db_easyA->execute("delete from cwl_customitem17_retail where 日期>='{$开始}' and 日期<'{$结束}'");
            // $this->db_easyA->execute('TRUNCATE cwl_customitem17_retail;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_customitem17_retail')->strict(false)->insertAll($val);
            }

            $sql_商品专员 = "
            update cwl_customitem17_retail as r
                left join customer_pro as c on r.店铺名称 = c.CustomerName
                set
                    r.商品专员 = c.CustomItem17
            ";
            $this->db_easyA->execute($sql_商品专员);
        }

        $sql_实际累计流水 = "
            update cwl_customitem17_yeji as y 
            left join (
                select 店铺名称,sum(销售金额) as 销售金额 from cwl_customitem17_retail where 日期>='{$开始}' AND 日期<'{$结束}' GROUP BY 店铺名称
            ) as r on y.店铺名称 = r.店铺名称
            set
                y.实际累计流水 = r.销售金额,
                y.累计流水截止日期 = '{$截止日期}'
            where  
                y.目标月份 = '{$目标月份}'
        ";
     
        $this->db_easyA->execute($sql_实际累计流水);
        
                
        $sql_目标达成率 = "
            update cwl_customitem17_yeji
                set
                    目标达成率 = 实际累计流水 / 本月目标,
                    `100%缺口额` = case
                                    when 本月目标 - 实际累计流水 > 0 then 本月目标 - 实际累计流水 else NULL 
                                   end,
                    `85%缺口额` = case
                                    when (本月目标 * 0.85) - 实际累计流水 > 0 then (本月目标 * 0.85) - 实际累计流水 else NULL
                                  end
                where  
                    目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_目标达成率);

        // die;
        $sql_缺口日均 = "
            update cwl_customitem17_yeji
                set
                    `100%缺口_日均额` = case
                                          when `100%缺口额` > 0 then `100%缺口额` / $到结束剩余天数 else null  
                                       end,
                    `85%缺口_日均额` = case
                                          when `85%缺口额` > 0 then  `85%缺口额` / $到结束剩余天数 else null 
                                       end
                where  
                    目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_缺口日均);
        
        // 近七天日均销
        $str_近七天日期 = '';
        $getBefore7 = $this->getBefore7();
        // dump($getBefore7);        
        // die;
        if($getBefore7) {
            $str_近七天日期 = arrToStr($getBefore7);
        }
        $sql_近七天日均销 = " 
            update cwl_customitem17_yeji as y 
            left join (
                SELECT
                    店铺名称,
                    AVG(销售金额) as 近七天日均销 
                FROM
                    cwl_customitem17_retail
                WHERE 
                    日期 in ({$str_近七天日期})
                GROUP BY 店铺名称
            ) as r on y.店铺名称 = r.店铺名称
            set
                y.近七天日均销 = r.近七天日均销
            where  
                目标月份 = '{$目标月份}'
        ";
        
        $this->db_easyA->execute($sql_近七天日均销);
        
        $sql_落后 = "
            update cwl_customitem17_yeji
            set
                `100%进度落后` = 
                    case
                        when 本月目标 is not null then 目标达成率 - {$落后分母} else NULL 
                    end,
                `85%进度落后` = 
                    case
                        when 本月目标 is not null > 0 then 实际累计流水 / (本月目标*0.85) - {$落后分母} else NULL
                    end
            where  
                目标月份 = '{$目标月份}'
        ";  
        $this->db_easyA->execute($sql_落后);      
    }

    // 专员表
    public function updateZhuanyuan() {
        $目标月份 = date('Y-m');
        $截止日期 = date('Y-m-d', strtotime('-1 day'));
        $本月最后一天 = date('Y-m-t'); 
        $到结束剩余天数 = $this->getDaysDiff(strtotime($截止日期), strtotime($本月最后一天));

        if (date('Y-m-d') == date('Y-m-01')) {
            $目标月份 = date('Y-m', strtotime('-1 month'));
        }

        // 每月1号
        if (date('Y-m-d') == date('Y-m-01')) {
            $本月最后一天 = date('Y-m-t', strtotime('-1month')); 
            $到结束剩余天数 = $this->getDaysDiff(strtotime($截止日期), strtotime($本月最后一天));
        }

        $sql = "
            select 商品专员,目标月份 from cwl_customitem17_yeji where 目标月份 = '{$目标月份}' group by 商品专员
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            // 删除历史数据
            $this->db_easyA->table('cwl_customitem17_zhuanyuan')->where([
                ['目标月份' , '=', $目标月份]
            ])->delete();
            // $this->db_easyA->execute('TRUNCATE cwl_customitem17_yeji;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_customitem17_zhuanyuan')->strict(false)->insertAll($val);
            }
        }

        $sql_目标_直营 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
            SELECT
                商品专员,
                sum(本月目标) AS 本月目标 
            FROM
                cwl_customitem17_yeji 
            WHERE 1
                AND 经营模式 in ('直营') 
                AND 本月目标 IS NOT NULL 
                AND 目标月份 = '{$目标月份}'
            GROUP BY
                商品专员
            ) as t on z.商品专员=t.商品专员
            set z.`目标_直营` = t.本月目标
            where 
                z.目标月份 = '{$目标月份}'
        ";

        $sql_目标_加盟 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
            SELECT
                商品专员,
                sum(本月目标) AS 本月目标 
            FROM
                cwl_customitem17_yeji 
            WHERE 1
                AND 经营模式 in ('加盟') 
                AND 本月目标 IS NOT NULL 
                AND 目标月份 = '{$目标月份}'
            GROUP BY
                商品专员
            ) as t on z.商品专员=t.商品专员
            set z.`目标_加盟` = t.本月目标
            where 
            z.目标月份 = '{$目标月份}'
        ";

        $sql_目标_合计 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
            SELECT
                商品专员,
                sum(本月目标) AS 本月目标 
            FROM
                cwl_customitem17_yeji 
            WHERE 1
                AND 经营模式 in ('直营','加盟') 
                AND 本月目标 IS NOT NULL 
                AND 目标月份 = '{$目标月份}'
            GROUP BY
                商品专员
            ) as t on z.商品专员=t.商品专员
            set z.`目标_合计` = t.本月目标
            where 
            z.目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_目标_直营);
        $this->db_easyA->execute($sql_目标_加盟);
        $this->db_easyA->execute($sql_目标_合计);

        $sql_累计流水_直营 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
            SELECT
                商品专员,
                累计流水截止日期,
                sum(实际累计流水) AS 实际累计流水 
            FROM
                cwl_customitem17_yeji 
            WHERE 1
                AND 经营模式 in ('直营') 
                AND 目标月份 = '{$目标月份}'
            GROUP BY
                商品专员
            ) as t on z.商品专员=t.商品专员
            set 
                z.`累计流水_直营` = t.实际累计流水,
                z.累计流水截止日期 = t.累计流水截止日期
            where
                z.目标月份 = '{$目标月份}'
        ";

        $sql_累计流水_加盟 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
            SELECT
                商品专员,
                sum(实际累计流水) AS 实际累计流水 
            FROM
                cwl_customitem17_yeji 
            WHERE 1
                AND 经营模式 in ('加盟') 
                AND 目标月份 = '{$目标月份}'
            GROUP BY
                商品专员
            ) as t on z.商品专员=t.商品专员
            set z.`累计流水_加盟` = t.实际累计流水
            where
                z.目标月份 = '{$目标月份}'
        ";

        $sql_累计流水_合计 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
            SELECT
                商品专员,
                sum(实际累计流水) AS 实际累计流水 
            FROM
                cwl_customitem17_yeji 
            WHERE 1
                AND 经营模式 in ('加盟', '直营') 
                AND 目标月份 = '{$目标月份}'
            GROUP BY
                商品专员
            ) as t on z.商品专员=t.商品专员
            set z.`累计流水_合计` = t.实际累计流水
            where
                z.目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_累计流水_直营);
        $this->db_easyA->execute($sql_累计流水_加盟);
        $this->db_easyA->execute($sql_累计流水_合计);

        $sql_达成率 = "
            update cwl_customitem17_zhuanyuan
            set 
                `达成率_直营` = `累计流水_直营` / `目标_直营`,
                `达成率_加盟` = `累计流水_加盟` / `目标_加盟`,
                `达成率_合计` = `累计流水_合计` / `目标_合计`
            where
                目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_达成率);

        $sql_100日均需销额 = "
            update cwl_customitem17_zhuanyuan
            set 
                `100%日均需销额_直营` = (`目标_直营` - `累计流水_直营`) / {$到结束剩余天数},
                `100%日均需销额_加盟` = (`目标_加盟` - `累计流水_加盟`) / {$到结束剩余天数},
                `100%日均需销额_合计` = (`目标_合计` - `累计流水_合计`) / {$到结束剩余天数}
            where
                目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_100日均需销额);

        $sql_85日均需销额 = "
            update cwl_customitem17_zhuanyuan
            set 
                `85%日均需销额_直营` = (`目标_直营` * 0.85  - `累计流水_直营`) / {$到结束剩余天数},
                `85%日均需销额_加盟` = (`目标_加盟` * 0.85 - `累计流水_加盟`)  / {$到结束剩余天数},
                `85%日均需销额_合计` = (`目标_合计` * 0.85 - `累计流水_合计`)  / {$到结束剩余天数}
            where
                目标月份 = '{$目标月份}'
        ";
        $this->db_easyA->execute($sql_85日均需销额);

        $str_近七天日期 = '';
        $getBefore7 = $this->getBefore7();
        if($getBefore7) {
            $str_近七天日期 = arrToStr($getBefore7);
        }
        $sql_近七天日均销_直营 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 
           
                select 
                    m.商品专员, 
                    m.销售金额 / 7 as 近七天日均销
                from (
                    SELECT
                        商品专员,
                        sum(销售金额) as 销售金额  
                    FROM
                        cwl_customitem17_retail
                    WHERE
                        日期 IN ( {$str_近七天日期} ) 
                        AND 经营属性 = '直营'
                    GROUP BY
                        商品专员
                ) as m

            ) as t on z.商品专员=t.商品专员
            set 
                z.`近七天日均销额_直营` = t.近七天日均销
            where
                目标月份 = '{$目标月份}'
        ";
        
        $sql_近七天日均销_加盟 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 

                select 
                    m.商品专员, 
                    m.销售金额 / 7 as 近七天日均销
                from (
                    SELECT
                        商品专员,
                        sum(销售金额) as 销售金额  
                    FROM
                        cwl_customitem17_retail
                    WHERE
                        日期 IN ( {$str_近七天日期} ) 
                        AND 经营属性 = '加盟'
                    GROUP BY
                        商品专员
                ) as m

            ) as t on z.商品专员=t.商品专员
            set 
                z.`近七天日均销额_加盟` = t.近七天日均销
            where
                目标月份 = '{$目标月份}'
        ";
        
        $sql_近七天日均销_合计 = "
            update cwl_customitem17_zhuanyuan as z 
            left join ( 

                select 
                    m.商品专员, 
                    m.销售金额 / 7 as 近七天日均销
                from (
                    SELECT
                        商品专员,
                        sum(销售金额) as 销售金额  
                    FROM
                        cwl_customitem17_retail
                    WHERE
                        日期 IN ( {$str_近七天日期} ) 
                    GROUP BY
                        商品专员
                ) as m

            ) as t on z.商品专员=t.商品专员
            set 
                z.`近七天日均销额_合计` = t.近七天日均销
            where
                目标月份 = '{$目标月份}'
        ";

        $this->db_easyA->execute($sql_近七天日均销_直营);
        $this->db_easyA->execute($sql_近七天日均销_加盟);
        $this->db_easyA->execute($sql_近七天日均销_合计);
    }

    public function test() {
        echo $截止日期 = date('Y-m-d', strtotime('-1 day'));
        echo '<br>';
        echo $本月最后一天 = date('Y-m-t'); 
        echo '<br>';
        echo $到结束剩余天数 = $this->getDaysDiff(strtotime($截止日期), strtotime($本月最后一天));


        $str_近七天日期 = '';
        $getBefore7 = $this->getBefore7();
        if($getBefore7) {
            $str_近七天日期 = arrToStr($getBefore7);
        }
        echo '<br>';
        $str_近七天日期 = "'2023-09-26','2023-09-25','2023-09-24','2023-09-23','2023-09-22','2023-09-21','2023-09-20'";

        echo $sql_近七天日均销 = " 
            SELECT
                店铺名称,
                销售金额
            FROM
                cwl_customitem17_retail
            WHERE 
                日期 in ({$str_近七天日期})
                AND 商品专员 = '刘琳娜'
            GROUP BY 店铺名称
        ";
    }
}
