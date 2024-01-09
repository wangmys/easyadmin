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
 * @ControllerAnnotation(title="金额售罄率 冬季")
 */
class JineshouqinglvWinter extends BaseController
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

    public function autoHandle() {
        $date = input('date') ?? date('Y-m-d');
        $this->handle1($date);
        $this->handle2($date);
        $this->handle3($date);
    }

    // 计算1
    protected function handle1($date) {
        $sql = "
            SELECT 
                t.*,
                ROUND((t.前一周销吊牌额 + t.前两周销吊牌额)  / 2, 1) AS 近两周销吊牌额均值,
                CONCAT(ROUND(t.累销吊牌额 / t.采购下单吊牌额 * 100, 1), '%') as 累销额售罄,
                CONCAT(ROUND(t.采购入库吊牌额 / t.采购下单吊牌额 * 100, 1), '%') as 入库达成率
            FROM (
                SELECT
                    m.更新日期,m.风格,m.一级分类,m.二级分类,
                    ROUND(c.采购下单吊牌额 / 10000, 1) as 采购下单吊牌额,
                    ROUND(SUM(IFNULL(采购入库吊牌额, 0)) / 10000, 1) as 采购入库吊牌额,
            
                    ROUND(SUM(IFNULL(昨天销吊牌额, 0)) / 10000, 1) as 昨天销吊牌额,
                    ROUND(SUM(IFNULL(累销吊牌额, 0)) / 10000, 1) as 累销吊牌额,
        
                    ROUND(SUM(IFNULL(合计库存吊牌额, 0)) / 10000, 1) as 合计库存吊牌额,
            
                    ROUND(SUM(IFNULL(前三周销吊牌额, 0)) / 10000, 1) as 前三周销吊牌额,
                    ROUND(SUM(IFNULL(前两周销吊牌额, 0)) / 10000, 1) as 前两周销吊牌额,
                    ROUND(SUM(IFNULL(前一周销吊牌额, 0)) / 10000, 1) as 前一周销吊牌额
                FROM
                    winter_report_tag_price as m
                LEFT JOIN cwl_winter_report_tag_price_config as c ON m.风格 = c.风格 and m.一级分类 = m.一级分类 and m.二级分类 = c.二级分类
                WHERE 1
                    AND 更新日期 = '{$date}'
                    AND m.二级分类 <> '合计'
                GROUP BY
                    m.风格, m.一级分类, m.二级分类
            ) AS t
            GROUP BY
                t.风格,t. 一级分类, t.二级分类
        ";

        $select = $this->db_bi->query($sql);
        if ($select) {
            // 删除历史数据
            $this->db_bi->table('cwl_winter_report_tag_price')->where(['更新日期' => $date])->delete();
            // $this->db_easyA->execute('TRUNCATE cwl_baokuan_7day;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                 $this->db_bi->table('cwl_winter_report_tag_price')->strict(false)->insertAll($val);
            }
        }
    }

    // 一级分类合计
    protected function handle2($date) {
        $date = input('date') ?? date('Y-m-d');
        $sql = "
            SELECT 
                t.*,
                CONCAT(ROUND(t.累销吊牌额 / t.采购下单吊牌额 * 100, 1), '%') as 累销额售罄,
                CONCAT(ROUND(t.采购入库吊牌额 / t.采购下单吊牌额 * 100, 1), '%') as 入库达成率
            FROM (
                SELECT
                    1 as `key`,
                    风格,一级分类,'合计' as 二级分类,
                    SUM(采购下单吊牌额) as 采购下单吊牌额,
                    SUM(采购入库吊牌额) as 采购入库吊牌额,
                    SUM(昨天销吊牌额) as 昨天销吊牌额,
                    SUM(累销吊牌额) as 累销吊牌额,
                    SUM(合计库存吊牌额) as 合计库存吊牌额,
                    SUM(前三周销吊牌额) as 前三周销吊牌额,
                    SUM(前两周销吊牌额) as 前两周销吊牌额,
                    SUM(前一周销吊牌额) as 前一周销吊牌额,
                    SUM(近两周销吊牌额均值) as 近两周销吊牌额均值,
                    更新日期
                FROM 
                    `cwl_winter_report_tag_price` 
                WHERE
                    更新日期 = '{$date}'
                GROUP BY
                    风格,一级分类
            ) AS t
        ";

        $select = $this->db_bi->query($sql);
        if ($select) {
            // 删除历史数据
            // $this->db_bi->table('cwl_winter_report_tag_price')->where(['更新日期' => $date])->delete();
            // $this->db_easyA->execute('TRUNCATE cwl_baokuan_7day;');
            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                 $this->db_bi->table('cwl_winter_report_tag_price')->strict(false)->insertAll($val);
            }
        }
    }

    // 风格合计
    protected function handle3($date) {
        $date = input('date') ?? date('Y-m-d');
        $sql = "
            SELECT 
                t.*,
                CONCAT(ROUND(t.累销吊牌额 / t.采购下单吊牌额 * 100, 1), '%') as 累销额售罄,
                CONCAT(ROUND(t.采购入库吊牌额 / t.采购下单吊牌额 * 100, 1), '%') as 入库达成率
            FROM (
                SELECT
                    2 as `key`,
                    风格, '合计' as 一级分类,'合计' as 二级分类,
                    SUM(采购下单吊牌额) as 采购下单吊牌额,
                    SUM(采购入库吊牌额) as 采购入库吊牌额,
                    SUM(昨天销吊牌额) as 昨天销吊牌额,
                    SUM(累销吊牌额) as 累销吊牌额,
                    SUM(合计库存吊牌额) as 合计库存吊牌额,
                    SUM(前三周销吊牌额) as 前三周销吊牌额,
                    SUM(前两周销吊牌额) as 前两周销吊牌额,
                    SUM(前一周销吊牌额) as 前一周销吊牌额,
                    SUM(近两周销吊牌额均值) as 近两周销吊牌额均值,
                    更新日期
                FROM 
                    `cwl_winter_report_tag_price` 
                WHERE 1
                    AND 更新日期 = '2024-01-08'
                    AND 二级分类 <> '合计'
                GROUP BY
                    风格
            ) AS t
        ";

        $select = $this->db_bi->query($sql);
        if ($select) {
            // 删除历史数据
            // $this->db_bi->table('cwl_winter_report_tag_price')->where(['更新日期' => $date])->delete();
            // $this->db_easyA->execute('TRUNCATE cwl_baokuan_7day;');
            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                    $this->db_bi->table('cwl_winter_report_tag_price')->strict(false)->insertAll($val);
            }
        }
    }

}
