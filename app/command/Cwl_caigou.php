<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;


// 断码sk cwl_duanmalv_sk
class Cwl_caigou extends Command
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';

    protected function configure() {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        // 指令配置
        $this->setName('Cwl_caigou')
            ->setDescription('the Cwl_caigou command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit','512M');

        $sql = "select * from cwl_cgzdt_config";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            foreach ($select as $key => $val) {
                // $path = "/data/web/cwl/cgzdt_{$val['值']}.jpg";

                $path = "/data/web/easyadmin2/easyadmin/public/img/".date('Ymd').'/'. "cgzdt_{$val['值']}.jpg";

                // echo "wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}";die;
                // wkhtmltoimage --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?中类=羽绒服 /data/web/cwl/cgzdt_test1.jpg

                $res = system("wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}", $result);
                // print $result;//输出命令的结果状态码
                // print $res;//输出命令输出的最后一行
            }
        }
    }

}
