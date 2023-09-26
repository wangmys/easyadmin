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

  public function exec() {

    $res = exec("cd /data/web/easyadmin2/easyadmin && php think puhuo_yuncangkeyong");
    sleep(10);
    $res = exec("cd /data/web/easyadmin2/easyadmin && php think puhuo_start1 1000");
    echo '临时使用，每次执行一次即可';die;

  }

}
