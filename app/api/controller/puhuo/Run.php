<?php
declare (strict_types = 1);

namespace app\api\controller\puhuo;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;
use app\admin\model\wwdata\LypWwCusstockModel;
use app\admin\model\wwdata\LypWwCussale14dayModel;

class Run extends BaseController
{

  public function puhuo() {

    if (env('ENV_SIGN') == 'local') {

      $res = exec("cd D:/wwwroot/suoge/sg_easyadmin/easyadmin && php think puhuo_start2_merge");
      
    } elseif (env('ENV_SIGN') == 'product') {

      $res = exec("cd /data/web/easyadmin2/easyadmin && php think puhuo_start2_merge");

    }
    var_dump($res);die;

  }

  public function test() {

    $res = exec("echo 999");
    sleep(25);
    $res2 = exec("echo 888");
    print_r([$res, $res2]);die;

  }

}
