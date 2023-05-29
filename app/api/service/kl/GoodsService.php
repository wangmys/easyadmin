<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class GoodsService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpGoodsModel::where([['GoodsId', '=', $params['GoodsId']]])->field('GoodsId')->find()) {
            json_fail(400, 'GoodsId单号已存在');
        }

        $arr['CreateTime'] = date('Ymd H:i:s');
        $arr['UpdateTime'] = date('Ymd H:i:s');
        $arr['Version'] = time();

        $arr = array_merge($arr, $params);
        $arr = array_merge($arr, ErpGoodsModel::INSERT);

        unset($arr['Version']);
        $sql = $this->generate_sql($arr);
        // echo $sql;die;
        Db::connect("sqlsrv2")->Query($sql);

    }

    protected function generate_sql($arr) {

        $sql_str = 'set identity_insert ErpGoods ON; insert into [ErpGoods] (';
        $key = '';
        $value = ' VALUES (';
        foreach ($arr as $k_arr => $v_arr) {
            $key .= '['.$k_arr.'],';
            $value .= "'".$v_arr."',";
        }
        $key = substr($key, 0, -1);
        $value = substr($value, 0, -1);
        $sql_str = $sql_str.$key.')'.$value.');';
        // echo $sql_str;die;
        return $sql_str;

    }

    /**
     * 更新
     * @param $params
     * @return void
     */
    public function update($params) {

       Db::startTrans();
        try {
            $new['UpdateTime'] = date('Ymd H:i:s');
            $new = array_merge($new, $params);
            unset($new['GoodsId']);
            ErpGoodsModel::where([['GoodsId', '=', $params['GoodsId']]])->update($new);

            Db::commit();

        } catch (\Exception $e) {
           Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function delete($params) {

        Db::startTrans();
        try {

            ErpGoodsModel::where([['GoodsId', '=', $params['GoodsId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

