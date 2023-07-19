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
 * @ControllerAnnotation(title="天气提醒跑数")
 */
class Weathertips extends BaseController
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

    public function seasionHandle($seasion = "春季") {
        $seasionStr = "";
        if ($seasion == '春季') {
            $seasionStr = "'初春','正春','春季'";
        } elseif ($seasion == '夏季') {
            $seasionStr = "'初夏','盛夏','夏季'";
        } elseif ($seasion == '秋季') {
            $seasionStr = "'初秋','深秋','秋季'";
        } elseif ($seasion == '冬季') {
            $seasionStr = "'初冬','深冬','冬季'";
        }
        return $seasionStr;
    }

    public function customer()
    {
        $sql = "
            select 
                c.CustomItem15 as 云仓,
                c.State as 省份,
                c.CustomItem17 as 商品负责人,
                c.CustomerName as 店铺名称,
                c.CustomerGrade as 店铺等级,
                c.customerCode as 店铺编码,
                c.Region as 区域修订,
                cf.RegionId,
                c.Mathod as 经营属性,
                c.CustomItem36 AS 温带,
                cf.`首单日期` as 开业日期
            From customer as c
            LEFT JOIN customer_first as cf on c.CustomerName = cf.`店铺名称` and c.customerCode = cf.CustomerCode 
            WHERE 
                c.CustomItem36 IS NOT NULL
            ORDER BY 
                c.State, c.Mathod
        ";
		
        $select = $this->db_easyA->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_customer;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_weathertips_customer')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_customer first 更新成功，数量：{$count}！"
            ]);

        }
    }

    // 店铺库存
    public function customerStock() {
        
    }
}
