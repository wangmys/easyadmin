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

    public function getDate0() {
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

    public function getDate()
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

    // 传入开始结束时间戳
    public function getDaysDiff($beginDate, $endDate) {
        $days = round( ($endDate - $beginDate) / 3600 / 24);
        return $days;
    }

}
