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

      $sql = "select 店铺名称,季节,年份,单据日期,修改后风格,一级分类,二级分类,分类,数量,销售金额,零售价金额 from ea_lyp_ww_cussale14day where 年份='2023' and 季节 like '%秋%' or 季节 like '%冬%';";
      $data = Db::connect("mysql")->Query($sql);
      return json($data);
    }

    // cwl改进版
    public function ea_lyp_ww_cussale14day_pro() {
      ini_set('memory_limit','1024M');
      if (empty(cache('ea_lyp_ww_cussale14day_pro'))) {
        $sql = "
          SELECT
            *
          FROM
            ea_lyp_ww_cussale14day_pro 
          WHERE
            1
        ";
        $data = Db::connect("mysql")->Query($sql);

        cache('ea_lyp_ww_cussale14day_pro', $data, 86400);
      } 
      $res = cache('ea_lyp_ww_cussale14day_pro');
      return json($res);
    }

    // cwl改进版 14天分2周  跑数用
    public function ea_lyp_ww_cussale14day_handle() {
      ini_set('memory_limit','1024M');

      $date = date('Y-m-d');
      $本周开始 = date("Y-m-d", strtotime('-7 day', strtotime($date))); 
      $本周结束 = date("Y-m-d", strtotime('-1 day', strtotime($date)));
      $上周开始 = date("Y-m-d", strtotime('-14 day', strtotime($date))); 
      $上周结束 = date("Y-m-d", strtotime('-8 day', strtotime($date)));

      // die;
      $sql_本周 = "
        SELECT
          店铺名称,季节,年份,
          '本周' as 单据日期,
          '{$本周结束}/{$上周开始}' as 时间范围,
          修改后风格,一级分类,二级分类,分类,
          sum(数量) as 数量,
          sum(销售金额) as 销售金额,
          sum(零售价金额) as 零售价金额 
        FROM
          ea_lyp_ww_cussale14day 
        WHERE 1
          AND (
            年份 = '2023' 
            AND 季节 in ('初秋', '深秋', '秋季') 
            OR 季节 in ('初冬', '深冬', '冬季')
          )
          AND 单据日期 between '{$本周开始}' and '{$本周结束}'
        GROUP BY 店铺名称, 季节,年份,修改后风格,一级分类,二级分类,分类
      ";

      $sql_上周 = "
        SELECT
          店铺名称,季节,年份,
          '上周' as 单据日期,
          '{$本周结束}/{$上周开始}' as 时间范围,
          修改后风格,一级分类,二级分类,分类,
          sum(数量) as 数量,
          sum(销售金额) as 销售金额,
          sum(零售价金额) as 零售价金额 
        FROM
          ea_lyp_ww_cussale14day 
        WHERE 1
          AND (
            年份 = '2023' 
            AND 季节 in ('初秋', '深秋', '秋季') 
            OR 季节 in ('初冬', '深冬', '冬季')
          )
          AND 单据日期 between '{$上周开始}' and '{$上周结束}'
        GROUP BY 店铺名称, 季节,年份,修改后风格,一级分类,二级分类,分类
      ";
      
      $select_本周 = Db::connect("mysql")->Query($sql_本周);
      $select_上周 = Db::connect("mysql")->Query($sql_上周);

      if ($select_本周 && $select_上周) {
        Db::connect("mysql")->execute('TRUNCATE ea_lyp_ww_cussale14day_pro;');

        $select_chunk = array_chunk($select_本周, 500);
        $select_chunk2 = array_chunk($select_上周, 500);

        foreach($select_chunk as $key => $val) {
          Db::connect("mysql")->table('ea_lyp_ww_cussale14day_pro')->insertAll($val);
        }

        foreach($select_chunk2 as $key2 => $val2) {
          Db::connect("mysql")->table('ea_lyp_ww_cussale14day_pro')->insertAll($val2);
        }

        cache('ea_lyp_ww_cussale14day_pro', array_merge($select_本周, $select_上周), 86400);

        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => 'ea_lyp_ww_cussale14day_pro 更新成功！'
        ]);
      } 
      
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
