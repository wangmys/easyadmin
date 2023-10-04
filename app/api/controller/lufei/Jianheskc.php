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
 * @ControllerAnnotation(title="检核SKC")
 */
class Jianheskc extends BaseController
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

    // 数据源
    public function skc_data()
    {
        $year = date('Y', time());

        $sql = "
            select * from sp_customer_stock_skc_2 where 年份 in('2023')
        ";
		
        $select = $this->db_bi->query($sql);
        $count = count($select);

        // dump($select);die;

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_jianhe_stock_skc;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_jianhe_stock_skc')->strict(false)->insertAll($val);
            }

            $sql_update1 = "
                update cwl_jianhe_stock_skc
                    set 
                        修订季节 = right(二级时间分类, 1),
                        修订风格 = left(调整风格, 2),
                        合并 = concat(二级时间分类,调整风格,一级分类,二级分类,分类)
                    where 1
            ";
            $this->db_easyA->execute($sql_update1);

            $sql_update2 = "
                update cwl_jianhe_stock_skc as sk
                LEFT JOIN (
                    SELECT
                        分类,修订分类
                    FROM	cwl_jianhe_skc_biaozhun_1 where 分类 is not null group by 分类
                ) as b ON sk.分类 = b.分类
                set 
                    sk.修订分类 = b.修订分类
                where 
                    sk.修订分类 is null
            ";
            $this->db_easyA->execute($sql_update2);

            $sql_update_17_36 = "
                update cwl_jianhe_stock_skc as s
                left join customer as c on s.店铺名称 = c.CustomerName
                set
                    s.商品负责人 = CustomItem17,
                    s.温区 = c.CustomItem36
                where 
                    s.商品负责人 is null or s.温区 is null
            ";
            $this->db_easyA->execute($sql_update_17_36);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_jianhe_stock_skc 更新成功，数量：{$count}！"
            ]);
        }
    }

    public function test() {
        // sleep(10);
        $insert = $this->db_easyA->table('cwl_swoole_test')->where('id=1')->update([
            'num' => Db::raw('num+1'),
        ]);
    }

    public function test2() {
        phpinfo();
    }

}
