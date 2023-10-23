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
        ini_set('memory_limit','1024M');
        // echo 111;
        $res = system("wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?中类=保暖内衣 /data/web/cwl/demo23.jpg", $result);

        print $result;//输出命令的结果状态码
        print $res;//输出命令输出的最后一行
    }

}
