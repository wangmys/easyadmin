<?php
declare (strict_types = 1);

namespace app\api\controller\stock;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;
use app\admin\model\wwdata\LypWwCusstockModel;

class Wwdata extends BaseController
{

    public function ea_lyp_ww_cusstock() {

        ini_set('memory_limit','500M');
        $data = LypWwCusstockModel::where([])->withoutField(['id', 'create_time'], false)->select();
        $data = $data ? $data->toArray() : [];
        return json($data);

    }

}
