<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpPuPreviewModel;
use app\api\model\kl\ErpPuPreviewGoodsModel;
use app\api\model\kl\ErpPuPreviewGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class PuPreviewService
{

    use Singleton;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpPuPreviewModel::where([['PuPreviewID', '=', $params['PuPreviewID']]])->field('PuPreviewID')->find()) {
            json_fail(400, 'PuPreviewID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['PuPreviewID'] = $params['PuPreviewID'];
            $arr['WarehouseId'] = $params['WarehouseId'];
            $arr['PreviewTaskID'] = $params['PreviewTaskID'];
            $arr['WorkerID'] = $params['WorkerID'];
            $arr['IsCancel'] = $params['IsCancel'] ?? 0;#0 or 1
            $arr['IsPosition'] = $params['IsPosition'] ?? 0;#0 or 1
            $arr['CreateTime'] = date('Ymd H:i:s');
            $arr['UpdateTime'] = date('Ymd H:i:s');
            $arr['Version'] = time();
            $new = array_merge($arr, ErpPuPreviewModel::INSERT);
            $new['PuPreviewDate'] = $now;
            $new['ReceiptNoticeId'] = $params['ReceiptNoticeId'] ?? '';

            if ($params['CodingCode'] == ErpPuPreviewModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpPuPreviewModel::CodingCode_TEXT[$params['CodingCode']];
            }

            ErpPuPreviewModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpPuPreviewGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addPuPreviewGoods($new['PuPreviewID'], $new['PuPreviewID'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addPuPreviewGoods($PuPreviewID, $PuPreviewGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['PuPreviewGoodsID'] = $PuPreviewGoodsID;
        $arr['PuPreviewID'] = $PuPreviewID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['SupplyId'] = $detail['SupplyId'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['ViewOrder'] = $detail['ViewOrder'] ?? 0;

//        Db::startTrans();
        try {
            ErpPuPreviewGoodsModel::create($arr);
            foreach ($detail['detail'] as $k => $v) {
                //ErpPuPreviewGoodsDetail 处理
                $this->addPuPreviewGoodsDetail($PuPreviewGoodsID, $v);
            }
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
        }

    }

    public function addPuPreviewGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['PuPreviewGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpPuPreviewGoodsDetailModel::create($arr);
        } catch (\Exception $e) {
            log_error($e);
            abort(0, $e->getMessage());
//            Db::rollback(); // 回滚事务
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
            if ($params['CodingCode'] == ErpPuPreviewModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCodeText'] = ErpPuPreviewModel::CodingCode_TEXT[$params['CodingCode']];
            } elseif ($params['CodingCode'] == ErpPuPreviewModel::CodingCode['NOTCOMMIT']) {
                $new['CodingCodeText'] = ErpPuPreviewModel::CodingCode_TEXT[$params['CodingCode']];
            }
            $new['UpdateTime'] = date('Ymd H:i:s');
            ErpPuPreviewModel::where([['PuPreviewID', '=', $params['PuPreviewID']]])->update($new);

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

            ErpPuPreviewModel::where([['PuPreviewID', '=', $params['PuPreviewID']]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

