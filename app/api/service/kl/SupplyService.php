<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpSupplyModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class SupplyService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpSupplyModel::where([['SupplyId', '=', $params['SupplyId']]])->field('SupplyId')->find()) {
            json_fail(400, 'SupplyId单号已存在');
        }

        Db::startTrans();
        try {

            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();

            $arr = array_merge($arr, $params);
            $arr = array_merge($arr, ErpSupplyModel::INSERT);

            ErpSupplyModel::create($arr);

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

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
            ErpSupplyModel::where([['SupplyId', '=', $params['SupplyId']]])->update($new);

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

            ErpSupplyModel::where([['SupplyId', '=', $params['SupplyId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

