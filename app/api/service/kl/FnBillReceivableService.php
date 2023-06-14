<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpFnBillReceivableModel;
use app\api\model\kl\ErpFnBillReceivableGoodsModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class FnBillReceivableService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpFnBillReceivableModel::where([['BillReceivableID', '=', $params['BillReceivableID']]])->field('BillReceivableID')->find()) {
            json_fail(400, 'BillReceivableID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['BillReceivableID'] = $params['BillReceivableID'];
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpFnBillReceivableModel::INSERT);
            $new['BillReceivableDate'] = $now;
            $new['BillType'] = $params['BillType'];
            $new['Summary'] = $params['Summary'];
            $new['AccountID'] = $params['AccountID'];
            $new['BillID'] = $params['BillID'];
            $new['Quantity'] = $params['Quantity'];
            $new['Amount'] = $params['Amount'];
            $new['Remark'] = $params['Remark'] ?? '';
            $new['ManualNo'] = $params['ManualNo'] ?? '';
            $new['BillDate'] = $now;
            $new['CustomerId'] = $params['CustomerId'] ?? '';
            $new['RoundingAmount'] = $params['RoundingAmount'] ?? null;
            $new['PrintNum'] = $params['PrintNum'] ?? 0;
            $new['SalesItemId'] = $params['SalesItemId'] ?? null;

            if ($params['CodingCode'] == ErpFnBillReceivableModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpFnBillReceivableModel::CodingCode_TEXT[$params['CodingCode']];
            }

            ErpFnBillReceivableModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpFnBillReceivableGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addFnBillReceivableGoods($new['BillReceivableID'], $new['BillReceivableID'] . make_order_number($k, $k), $v);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            //事务回滚失败，执行删除操作处理多余数据
            $this->delete($params);
            abort(0, $e->getMessage());
        }

    }

    public function addFnBillReceivableGoods($BillReceivableID, $BillReceivableGoodsID, $detail) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['BillReceivableGoodsID'] = $BillReceivableGoodsID;
        $arr['BillReceivableID'] = $BillReceivableID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Amount'] = $detail['Amount'];
        $arr['Remark'] = $detail['Remark'] ?? '';

        try {
            ErpFnBillReceivableGoodsModel::create($arr);
        } catch (\Exception $e) {
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

//        Db::startTrans();
        try {

            $new['CodingCode'] = $params['CodingCode'];
            if ($params['CodingCode'] == ErpFnBillReceivableModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpFnBillReceivableModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpFnBillReceivableModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpFnBillReceivableModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            ErpFnBillReceivableModel::where([['BillReceivableID', '=', $params['BillReceivableID']]])->update($new);

        } catch (\Exception $e) {
//            Db::rollback();
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

            ErpFnBillReceivableModel::where([['BillReceivableID', '=', $params['BillReceivableID']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

