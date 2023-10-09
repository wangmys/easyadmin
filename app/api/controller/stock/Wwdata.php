<?php
declare (strict_types = 1);

namespace app\api\controller\stock;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;
use app\admin\model\wwdata\LypWwCusstockModel;
use app\admin\model\wwdata\LypWwCussale14dayModel;

class Wwdata extends BaseController
{

    public function ea_lyp_ww_cusstock() {

        ini_set('memory_limit','500M');
        $data = LypWwCusstockModel::where([])->withoutField(['id', 'create_time'], false)->select();
        $data = $data ? $data->toArray() : [];
        return json($data);

    }

    public function ea_lyp_ww_cussale14day() {

      ini_set('memory_limit','1024M');
      // $data = LypWwCussale14dayModel::where([['年份', '=', '2023'], ['']])->withoutField(['id', 'create_time'], false)->select();
      // $data = $data ? $data->toArray() : [];
      $sql = "select 店铺名称,季节,年份,单据日期,修改后风格,一级分类,二级分类,分类,数量,销售金额,零售价金额 from ea_lyp_ww_cussale14day where 年份='2023' and 季节 like '%秋%' or 季节 like '%冬%';";
      $data = Db::connect("mysql")->Query($sql);
      return json($data);

    }

    public function sjp_leixiao() {

      ini_set('memory_limit','1024M');

      $sql = "select lx.店铺名称,g.`二级时间分类` as 季节,g.`一级时间分类` as 年份, g.`风格`, g.`一级分类`, g.`二级分类`, g.`分类`, lx.合计, lx.金额, g.零售价 from sjp_leixiao lx 
      left join sjp_goods g on lx.货号=g.货号 
      where g.`一级时间分类`='2023' and (g.`二级时间分类` like '%秋%' or g.`二级时间分类` like '%冬%');";
      $data = Db::connect("mysql2")->Query($sql);
      return json($data);

    }


}
