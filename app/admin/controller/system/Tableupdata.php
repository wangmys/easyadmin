<?php
namespace app\admin\controller\system;

use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;
use app\api\controller\lufei\updatatable\sp_customer_stock_skc_2 as Sk2;

/**
 * 报表自动更新
 * Class Stockskc2
 * @package app\dingtalk
 */
class Tableupdata extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    
    /**
     * 构造函数
     * Dingtalk constructor.
     */
    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
    }

    public function list() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            $sql = "
                SELECT 
                   *
                FROM 
                    cwl_table_update
                WHERE 1

                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql_sp_customer_stock_skc_2_count = "
                SELECT 
                    count(*) as total
                FROM 
                    sp_customer_stock_skc_2
                WHERE 1
            ";
            $总数_sql_sp_customer_stock_skc_2 = $this->db_bi->query($sql_sp_customer_stock_skc_2_count);
            foreach ($select as $key=>$val) {
                if ($val['表名'] == 'sp_customer_stock_skc_2') {
                    $select[$key]['实时数据'] = $总数_sql_sp_customer_stock_skc_2[0]['total'];
                }
            }

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                cwl_table_update
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            // $time = time();
            // if ($time < strtotime(date('Y-m-d 20:30:00'))) {
            //     // echo '显示昨天';
            //     $today = date('Y-m-d', strtotime('-1 day', $time));
            // } else {
            //     // echo '显示今天';
            //     $today = date('Y-m-d');
            // }
            return View('list', [
                // 'today' => $today,
            ]);
        }        
    }

    public function updataHandle() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            // return json(['status' => 1, 'msg' => '执行成功']);
            if (!empty($input['表名'])) {
                if ($input['表名'] == 'sp_customer_stock_skc_2') {
                    $sk2 = new Sk2;
                    $res = $sk2->doris();
                    if ($res) {
                        $this->db_easyA->table('cwl_table_update')->where(['表名' => $input['表名']])->update(['跑数完成时间' => date('Y-m-d H:i:s')]);
                        return json(['status' => 1, 'msg' => '执行成功']);
                    } else {
                        return json(['status' => 2, 'msg' => '执行失败，请稍后再试']);
                    }
                } else {
                    return json(['status' => 3, 'msg' => '表名不存在']);
                }
                
            }
        }
    }

    public function test() {
        $sk2 = new Sk2;
        $sk2->doris();
    }
}
