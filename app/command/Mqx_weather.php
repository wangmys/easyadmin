<?php
declare (strict_types=1);

namespace app\command;

use app\common\service\command\MqxWeatherService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\facade\Db;


// 断码sk cwl_duanmalv_sk
class Mqx_weather extends Command
{


    protected function configure()
    {

        // 指令配置
        $this->setName('mqx_weather')
            ->addArgument('date', Argument::OPTIONAL)
            ->setDescription('the mqx_weather command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit', '512M');
        try {
//            (new MqxWeatherService())->test();
            (new MqxWeatherService())->update_weather();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

        }
        $output->writeln('ok');

    }

}
