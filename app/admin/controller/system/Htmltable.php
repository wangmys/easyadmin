<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 */
class Htmltable extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    // 下水道
    public function s105() {
        $date = input('param.date') ? input('param.date') : date('Y-m-d', strtotime('+1day'));

        $sql = "
            SELECT
                店铺名称,
                concat(round(今日达成率 * 100, 1), '%') as 今日达成率,
                concat(round(本月达成率 * 100, 1), '%') as 本月达成率,
                昨日递增率 AS `22年日同比`,
                前年对比今年昨日递增率 AS `21年日同比`,
                累销递增率 AS `22年月累同比`,
                前年对比今年累销递增率 AS `21年月累同比`,
                昨天销量 as 今日流水,
                今日目标,
                本月业绩 as 本月流水,
                本月目标
            from xiashui_old_customer_state_detail_ww where 更新时间 = '{$date}'
        ";

        $select = $this->db_bi->query($sql);
        // dump($select);die;
        $更新日期 = date('Y-m-d', time());
        return View('s105', [
            'data' => $select,
            'title' => '下水道店业绩情况' . " 【{$更新日期}】" . "表名：S105"
        ]);
    }
}
