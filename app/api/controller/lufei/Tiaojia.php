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
 * @ControllerAnnotation(title="调价")
 */
class Tiaojia extends BaseController
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

    // 数据源加工1
    // 基本款SKC：短T<=50,   短衬<=80,      短裤<=70,    长裤<=100,      鞋履 <=80
    // 引流款不管打不打折全算调价，基本款折率<1并且低于以上标准的才算调价
    public function sjp_customer_stock_cwl_1() {
        $sql = "
            SELECT
                scs.*,
                ROUND(scs.`当前零售价` / scs.零售价, 2) as 折率,
                sg.一级分类,
                sg.二级分类, 
                sg.分类,
                sg.风格,
                c.State as 省份
            FROM
                sjp_customer_stock AS scs
            RIGHT JOIN sjp_goods AS sg ON sg.货号=scs.`货号`
            RIGHT JOIN customer AS c ON c.CustomerName = scs.店铺名称
            WHERE 
                scs.货号 is not null
        ";

        $select = $this->db_bi->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_bi->execute('TRUNCATE sjp_customer_stock_cwl;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('sjp_customer_stock_cwl')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "sjp_customer_stock_cwl_1 更新成功，数量：{$count}！"
            ]);
        }
    }   

    // 数据源加工2
    // 基本款SKC：短T<=50,   短衬<=80,      短裤<=70,    长裤<=100,      鞋履 <=80
    // 引流款不管打不打折全算调价，基本款折率<1并且低于以上标准的才算调价
    public function sjp_customer_stock_cwl_2() {
        $sql = "
            update sjp_customer_stock_cwl set 是否调价款 = '是' where 风格='引流款' AND 是否调价款 is null
        ";

        $update = $this->db_bi->execute($sql);
        if ($update) {
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "sjp_customer_stock_cwl_2 更新成功，数量：{$update}！"
            ]);
        } else {
            return json([
                'status' => 2,
                'msg' => 'success',
                'content' => "sjp_customer_stock_cwl_2 更新失败，数量：{$update}！"
            ]);           
        }
    }  
}
