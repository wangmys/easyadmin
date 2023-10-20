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
 * Class Caigou
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="采购自动推报表")
 */
class Caigou extends AdminController
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

    // 自动推
    public function zdt() {
        $sql = "
            select * from cwl_cgzdt_caigoushouhuo
            where TOP = 'Y'
                AND 中类 = '牛仔长裤'
            order by 排名 ASC
        ";
        $select = $this->db_easyA->query($sql);
        // dump($select);
        return View('zdt', [
            'data' => $select
        ]);
    }
}
