<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpWarehouseModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class WarehouseService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpWarehouseModel::where([['WarehouseId', '=', $params['WarehouseId']]])->field('WarehouseId')->find()) {
            json_fail(400, 'WarehouseId单号已存在');
        }

        Db::startTrans();
        try {

            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();

            $arr = array_merge($arr, $params);
            $arr = array_merge($arr, ErpWarehouseModel::INSERT);

            if (!$arr['RegionId']) {
                $arr['RegionId'] = null;
            }

            ErpWarehouseModel::create($arr);

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
            unset($new['WarehouseId']);
            if (!$params['RegionId']) {
                $new['RegionId'] = null;
            }
            ErpWarehouseModel::where([['WarehouseId', '=', $params['WarehouseId']]])->update($new);

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

            ErpWarehouseModel::where([['WarehouseId', '=', $params['WarehouseId']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

