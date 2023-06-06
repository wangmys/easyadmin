<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpBaseGoodsSizeModel;
use think\facade\Db;

class BaseGoodsSizeService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpBaseGoodsSizeModel::where([['SizeId', '=', $params['SizeId']]])->field('SizeId')->find()) {
            json_fail(400, 'SizeId已存在');
        }

        $arr['CreateTime'] = date('Ymd H:i:s');
        $arr['UpdateTime'] = date('Ymd H:i:s');

        $arr = array_merge($arr, $params);
        $arr = array_merge($arr, ErpBaseGoodsSizeModel::INSERT);

        $sql = generate_sql($arr, 'ErpBaseGoodsSize');
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

            unset($new['SizeId']);
            ErpBaseGoodsSizeModel::where([['SizeId', '=', $params['SizeId']]])->update($new);

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

            ErpBaseGoodsSizeModel::where([['SizeId', '=', $params['SizeId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

