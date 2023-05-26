<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpCustStockAdjustModel;
use app\api\model\kl\ErpCustStockAdjustGoodsModel;
use app\api\model\kl\ErpCustStockAdjustGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpCustomerStockModel;
use app\api\model\kl\ErpCustomerStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class CustStockAdjustService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpCustStockAdjustModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->field('StockAdjustID')->find()) {
            json_fail(400, 'StockAdjustID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['StockAdjustID'] = $params['StockAdjustID'];#NOT NULL
            $arr['CustomerId'] = $params['CustomerId'];#NOT NULL
            $arr['CreateTime'] = date('Ymd H:i:s');#NOT NULL
            $arr['UpdateTime'] = date('Ymd H:i:s');#NOT NULL
            $arr['Version'] = time();
            $new = array_merge($arr, ErpCustStockAdjustModel::INSERT);
            $new['AdjustDate'] = $now;#NOT NULL
            $new['ManualNo'] = $params['ManualNo'] ?? '';
            $new['Remark'] = $params['Remark'] ?? '';
//            $new['StockAdjustAttributesId'] = $params['StockAdjustAttributesId'] ?? '';
            $new['BillSource'] = $params['BillSource'] ?? '';
            $new['SalesmanID'] = $params['SalesmanID'] ?? '';

            if ($params['StockAdjustAttributesId']) {
                $new['StockAdjustAttributesId'] = $params['StockAdjustAttributesId'];
            }

            if ($params['CodingCode'] == ErpCustStockAdjustModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpCustStockAdjustModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpCustStockAdjustModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpCustStockAdjustGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addStockAdjustGoods($new['StockAdjustID'], $new['StockAdjustID'] . make_order_number($k, $k), $v, $params);
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

    public function addStockAdjustGoods($StockAdjustID, $StockAdjustGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['StockAdjustGoodsID'] = $StockAdjustGoodsID;
        $arr['StockAdjustID'] = $StockAdjustID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice'] ?? '';

        //库存处理
        $CustomerStockData = [
            'StockId' => $StockAdjustGoodsID,
            'CustomerId' => $params['CustomerId'],
            'CustomerName' => $params['CustomerName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpCustStockAdjust',
            'BillId' => $params['StockAdjustID'],
            'GoodsId' => $goodsId['GoodsId'],
            'Quantity' => $detail['Quantity'],
            'CreateTime' => date('Ymd H:i:s'),
            'UpdateTime' => date('Ymd H:i:s'),
            'Remark' => $detail['Remark'] ?? '',
            'Version' => time(),
        ];
        $CustomerStockData = array_merge($CustomerStockData, ErpCustomerStockModel::INSERT);

//        Db::startTrans();
        try {
            ErpCustStockAdjustGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpCustomerStockModel::create($CustomerStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpCustStockAdjustGoodsDetail 处理
                $this->addStockAdjustGoodsDetail($StockAdjustGoodsID, $v);
            }
        } catch (\Exception $e) {
//            Db::rollback(); // 回滚事务
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addStockAdjustGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['StockAdjustGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpCustStockAdjustGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['StockAdjustGoodsID']);
                $arr['StockId'] = $detailid;
                ErpCustomerStockDetailModel::create($arr);
            }
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

        $new['CodingCode'] = $params['CodingCode'];
        if ($params['CodingCode'] == ErpCustStockAdjustModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpCustStockAdjustModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpCustStockAdjustModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpCustStockAdjustModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpCustStockAdjustModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpCustStockAdjustModel::CodingCode['NOTCOMMIT']) {
                $StockAdjustGoodsID = ErpCustStockAdjustGoodsModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->column('StockAdjustGoodsID');
                ErpCustomerStockModel::where([['StockId', 'in', $StockAdjustGoodsID]])->delete();
            }

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

        $StockAdjustGoodsID = ErpCustStockAdjustGoodsModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->column('StockAdjustGoodsID');

        Db::startTrans();
        try {

            ErpCustStockAdjustModel::where([['StockAdjustID', '=', $params['StockAdjustID']]])->delete();

            //清理库存记录
            ErpCustomerStockModel::where([['StockId', 'in', $StockAdjustGoodsID]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

}

