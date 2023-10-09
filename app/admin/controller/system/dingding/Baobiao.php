<?php
namespace app\admin\controller\system\dingding;

use AlibabaCloud\SDK\Dingtalk\Vworkflow_1_0\Models\QuerySchemaByProcessCodeResponseBody\result\schemaContent\items\props\push;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * 报表
 * Class Baobiao
 * @package app\dingtalk
 */
class Baobiao extends BaseController
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

            // 非系统管理员
            // if (! checkAdmin()) { 
            //     $aname = session('admin.name');       
            //     $aid = session('admin.id');   
            //     $mapSuper = " AND list.aid='{$aid}'";  
            // } else {
            //     $mapSuper = '';
            // }
            // if (!empty($input['更新日期'])) {
            //     $map1 = " AND `更新日期` = '{$input['更新日期']}'";                
            // } else {
            //     $today = date('Y-m-d');
            //     $map1 = " AND `更新日期` = '{$today}'";            
            // }
            $sql = "
                SELECT 
                   *
                FROM 
                    dd_baobiao
                WHERE 1
                ORDER BY `key` ASC, 钉群, 编号 ASC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    dd_baobiao
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);

            // $reads = $this->db_easyA->table('dd_customer_push_weather')->where([
            //     ['更新日期', '=', $input['更新日期'] ? $input['更新日期'] : date('Y-m-d')],
            //     ['已读', '=', 'Y'],
            // ])->count('*');

            // $noReads = $this->db_easyA->table('dd_customer_push_weather')->where([
            //     ['更新日期', '=', $input['更新日期'] ? $input['更新日期'] : date('Y-m-d')],
            //     ['已读', '=', 'N'],
            // ])->count('*');
            // print_r($count);
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

    public function updateStatusHandle() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $编号 = $input['编号'];
            $状态 = $input['状态'];
            $find = $this->db_easyA->table('dd_baobiao')->where([
                ['编号', '=', $编号]
            ])->find();

            if ($find && ($状态 == '开' || $状态 == '关')) {
                $res = $this->db_easyA->table('dd_baobiao')->where([
                    ['编号', '=', $编号]
                ])->save([
                    '状态' => $状态
                ]);
                if ($res) {
                    return json(['status' => 1, 'msg' => '设置成功']);
                } else {
                    return json(['status' => 2, 'msg' => '设置失败']);
                }
            } else {
                return json(['status' => 3, 'msg' => '参数异常']);
            }
        }
    }

    public function test() {
        echo '中文111';
    }
}
