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
use think\App;

use app\admin\controller\system\dress\Dress;

/**
 * @ControllerAnnotation(title="三年趋势")
 */
class Threeyear extends BaseController
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

    protected $目标月份 = "";

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');

    }

    public function customerNum() {
        $sql_店铺数 = "
            select 
                t.YEAR as 年,
                t.WEEK as 周,
                sum(t.max_num) as 店铺数
            from (
                SELECT YEAR,
                    WEEK,
                    concat( YunCang, WenDai, WenQu, State, Mathod ) AS 云仓温带温区省份性质,
                    max( NUM ) AS max_num
                    ,
                    concat( YEAR, WEEK ) AS year_week 
                FROM
                    `sp_customer_stock_sale_threeyear2_week` 
                WHERE 1
            -- 		AND `Year` = '2023' 
            -- 		AND `Month` in (1,2,3,4,5,6,7,8,9,10,11,12)
                GROUP BY
                    `云仓温带温区省份性质`,
                    `year_week`
            ) as t
            group by t.YEAR,t.WEEK
        ";
        $select = $this->db_easyA->query($sql_店铺数);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE sp_customer_stock_sale_threeyear2_customer;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('sp_customer_stock_sale_threeyear2_customer')->strict(false)->insertAll($val);
            }
        }
    }


}
