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
            select 
                货号,简码,图片路径,零售价,成本价,分类,当天销量,累销量,总库存量,云仓在途量,订单未入量,近一周销量,近两周销量,上柜数,
                concat(round(售罄率 * 100, 1), '%') as 售罄率
            from cwl_cgzdt_caigoushouhuo
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

    // 自动推
    public function zdt1() {
        $input = input();
        if (!empty($input['中类'])) {
            $title = $input['中类'];
            $sql = "
                select 
                    货号,简码,图片路径,零售价,成本价,分类,当天销量,累销量,总库存量,云仓在途量,订单未入量,近一周销量,近两周销量,上柜数,中类,大类,更新日期,
                    concat(round(售罄率 * 100, 1), '%') as 售罄率
                from cwl_cgzdt_caigoushouhuo
                where TOP = 'Y'
                    AND 中类 = '{$input['中类']}'
                order by 排名 ASC
            ";
        } elseif (!empty($input['大类'])) {
            $title = $input['大类'];
            $sql = "
                select 
                    货号,简码,图片路径,零售价,成本价,分类,当天销量,累销量,总库存量,云仓在途量,订单未入量,近一周销量,近两周销量,上柜数,中类,大类,更新日期,
                    concat(round(售罄率 * 100, 1), '%') as 售罄率
                from cwl_cgzdt_caigoushouhuo
                where TOP = 'Y'
                    AND 大类 = '{$input['大类']}'
                order by 排名 ASC
            ";
        } else {
            die;
        }


        $select = $this->db_easyA->query($sql);
        // dump($select);die;
        $更新日期 = date('Y-m-d', strtotime('-1 day', strtotime($select[0]['更新日期'])));
        return View('zdt1', [
            'data' => $select,
            'title' => $title . " 【{$更新日期}】" . "表名：S119"
        ]);
    }
}
