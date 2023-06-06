<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpBaseGoodsColorModel;
use think\facade\Db;

class BaseGoodsColorService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpBaseGoodsColorModel::where([['ColorId', '=', $params['ColorId']]])->field('ColorId')->find()) {
            json_fail(400, 'ColorId已存在');
        }

        $arr['CreateTime'] = date('Ymd H:i:s');
        $arr['UpdateTime'] = date('Ymd H:i:s');
        $arr['Version'] = time();

        $arr = array_merge($arr, $params);
        $arr = array_merge($arr, ErpBaseGoodsColorModel::INSERT);

        unset($arr['Version']);

        $sql = generate_sql($arr, 'ErpBaseGoodsColor');
        // echo $sql;die;
        Db::connect("sqlsrv2")->Query($sql);

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

            unset($new['ColorId']);
            ErpBaseGoodsColorModel::where([['ColorId', '=', $params['ColorId']]])->update($new);

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

            ErpBaseGoodsColorModel::where([['ColorId', '=', $params['ColorId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

