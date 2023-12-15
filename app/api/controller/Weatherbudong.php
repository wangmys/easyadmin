<?php
namespace app\api\controller;

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
 * 历史天气补丁
 */
class Weatherbudong extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_binew = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_binew = Db::connect('bi_new');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
    }

    // 更新天气url
    public function update_weather_url() {
        $sql  = "
            update
                `cus_weather_url` 
            set
                update_status = 0
            WHERE 1
                and weather_url like '%202311%'
        ";
        $this->db_tianqi->execute($sql);
    }

    // 更新天气1
    public function del_weather_data1() {
        $sql  = "
            delete from cus_weather_data 
            WHERE 
                weather_time BETWEEN '2023-12-01 00:00:00' 
                AND '2023-12-30 00:00:00'
        ";
        $this->db_tianqi->execute($sql);
    }

}
