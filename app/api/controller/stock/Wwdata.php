<?php
declare (strict_types = 1);

namespace app\api\controller\stock;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;
use app\admin\model\wwdata\LypWwDataModel;
use app\admin\model\wwdata\LypWwDiaoboModel;

class Wwdata extends BaseController
{

    public function sjp_customer_stock_json() {

        $bi_db = Db::connect("mysql2");
        $data = $bi_db->Query("select cs.店铺名称, cs.季节, g.一级时间分类, CASE 
        WHEN g.二级分类='短T' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=50 THEN '引流款' 
        WHEN right(g.二级分类, 2)='长裤' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=100 THEN '引流款' 
        WHEN right(g.二级分类, 2)='短裤' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=70 THEN '引流款' 
        WHEN right(g.二级分类, 1)='衬' and (cs.当前零售价/cs.零售价)<1 and cs.当前零售价<=80 THEN '引流款' ELSE g.风格 END AS 修改后风格, g.一级分类, g.二级分类, g.分类, sum(cs.合计) as 库存数量合计, count(g.货号) as SKC数 from sjp_customer_stock cs 
 left join sjp_goods g on cs.货号=g.货号 
 where cs.季节 like '%夏%' 
 group by g.分类;");
        // print_r($data);die;
        return json($data);

    }

}
