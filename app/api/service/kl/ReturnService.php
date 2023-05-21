<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpReturnModel;
use app\api\model\kl\ErpReturnGoodsModel;
use app\api\model\kl\ErpReturnGoodsDetailModel;
use app\api\model\kl\ErpGoodsModel;
use app\api\model\kl\ErpBarCodeModel;
use app\api\model\kl\ErpCustomerStockModel;
use app\api\model\kl\ErpCustomerStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class ReturnService
{

    use Singleton;

    protected $is_commit = 0;

    /**
     * 创建
     * @param $params
     * @return void
     */
    public function create($params) {

        if (ErpReturnModel::where([['ReturnID', '=', $params['ReturnID']]])->field('ReturnID')->find()) {
            json_fail(400, 'ReturnID单号已存在');
        }

        Db::startTrans();
        try {

            $now = date('Ymd');
            $arr['ReturnID'] = $params['ReturnID'];#NOT NULL
            $arr['CustomerId'] = $params['CustomerId'];#NOT NULL
            $arr['CustomerName'] = $params['CustomerName'];#NOT NULL
            $arr['ReturnNoticeID'] = $params['ReturnNoticeID'] ?? '';
            $arr['WarehouseId'] = $params['WarehouseId'] ?? '';
            $arr['WarehouseName'] = $params['WarehouseName'] ?? '';
            $arr['CreateTime'] = date('Ymd H:i:s');#NOT NULL
            $arr['UpdateTime'] = date('Ymd H:i:s');#NOT NULL
            $arr['Version'] = time();
            $new = array_merge($arr, ErpReturnModel::INSERT);
            $new['ReturnDate'] = $now;#NOT NULL
            $new['Remark'] = $params['Remark'] ?? '';
            $new['ManualNo'] = $params['ManualNo'] ?? '';
            $new['IsDefectiveGoods'] = $params['IsDefectiveGoods'] ?? '';
            $new['IsUnsalable'] = $params['IsUnsalable'] ?? 0;
            $new['StateId'] = $params['StateId'] ?? '';
            $new['State'] = $params['State'] ?? '';
            $new['CityId'] = $params['CityId'] ?? '';
            $new['City'] = $params['City'] ?? '';
            $new['DistrictId'] = $params['DistrictId'] ?? '';
            $new['District'] = $params['District'] ?? '';
            $new['StreetId'] = $params['StreetId'] ?? '';
            $new['Street'] = $params['Street'] ?? '';
            $new['Address'] = $params['Address'] ?? '';
            $new['Contact'] = $params['Contact'] ?? '';
            $new['Tel'] = $params['Tel'] ?? '';
            $new['CompletedTime'] = $params['CompletedTime'] ?? '';
            $new['BillSource'] = $params['BillSource'] ?? '';
            $new['SalesItemId'] = $params['SalesItemId'] ?? '';
            $new['SalesmanID'] = $params['SalesmanID'] ?? '';
            $new['BillType'] = $params['BillType'] ?? 0;
            $new['BusinessManId'] = $params['BusinessManId'] ?? '';

            if ($params['CodingCode'] == ErpReturnModel::CodingCode['HADCOMMIT']) {//已审结
                $new['CodingCode'] = $params['CodingCode'];
                $new['CodingCodeText'] = ErpReturnModel::CodingCode_TEXT[$params['CodingCode']];
                $this->is_commit = 1;
            }

            ErpReturnModel::create($new);

            $goods = $params['Goods'] ?? [];
            //ErpReturnGoods 处理
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $this->addReturnGoods($new['ReturnID'], $new['ReturnID'] . make_order_number($k, $k), $v, $params);
                }
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addReturnGoods($ReturnID, $ReturnGoodsID, $detail, $params) {

        $goodsId = ErpGoodsModel::where('GoodsNo', $detail['GoodsNo'])->field('GoodsId')->find();

        $arr['ReturnGoodsID'] = $ReturnGoodsID;
        $arr['ReturnID'] = $ReturnID;
        $arr['GoodsId'] = $goodsId['GoodsId'];
        $arr['UnitPrice'] = $detail['UnitPrice'];
        $arr['Price'] = $detail['Price'];
        $arr['Quantity'] = $detail['Quantity'];
        $arr['Discount'] = round($detail['Price'] / $detail['UnitPrice'], 2);
        $arr['Remark'] = $detail['Remark'] ?? '';
        $arr['ReturnNoticeID'] = $params['ReturnNoticeID'] ?? '';
        $arr['InstructionId'] = $params['InstructionId'] ?? '';
        $arr['CostPrice'] = $detail['CostPrice'] ?? '';

        //库存处理
        $CustomerStockData = [
            'StockId' => $ReturnGoodsID,
            'CustomerId' => $params['CustomerId'],
            'CustomerName' => $params['CustomerName'],
            'StockDate' => date('Ymd'),
            'BillType' => 'ErpReturn',
            'BillId' => $params['ReturnID'],
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
            ErpReturnGoodsModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                ErpCustomerStockModel::create($CustomerStockData);
            }
            foreach ($detail['detail'] as $k => $v) {
                //ErpReturnGoodsDetail 处理
                $this->addReturnGoodsDetail($ReturnGoodsID, $v);
            }
        } catch (\Exception $e) {
//            Db::rollback(); // 回滚事务
            log_error($e);
            abort(0, $e->getMessage());
        }

    }

    public function addReturnGoodsDetail($detailid, $detail) {

        //根据barcode获取ColorId,SizeId,GoodsId
        $barCodeInfo = ErpBarCodeModel::where([['BarCode', '=', $detail['Barcode']]])->field('ColorId,SizeId')->find();
        $arr['ReturnGoodsID'] = $detailid;
        $arr['ColorId'] = $barCodeInfo ? $barCodeInfo['ColorId'] : '';
        $arr['SizeId'] = $barCodeInfo ? $barCodeInfo['SizeId'] : '';
        $arr['Quantity'] = $detail['Quantity'];
        $arr['SpecId'] = 1;
//        Db::startTrans();
        try {
            ErpReturnGoodsDetailModel::create($arr);
            if ($this->is_commit == 1) {//已审结的 新增仓库库存记录
                unset($arr['ReturnGoodsID']);
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
        if ($params['CodingCode'] == ErpReturnModel::CodingCode['HADCOMMIT']) {//已审结
            $new['CodingCodeText'] = ErpReturnModel::CodingCode_TEXT[$params['CodingCode']];
        } elseif ($params['CodingCode'] == ErpReturnModel::CodingCode['NOTCOMMIT']) {
            $new['CodingCodeText'] = ErpReturnModel::CodingCode_TEXT[$params['CodingCode']];
        }
        $new['UpdateTime'] = date('Ymd H:i:s');

        Db::startTrans();
        try {

            ErpReturnModel::where([['ReturnID', '=', $params['ReturnID']]])->update($new);

            //库存记录处理 当状态变为未提交时 要删除对应库存记录
            if ($params['CodingCode'] == ErpReturnModel::CodingCode['NOTCOMMIT']) {
                $ReturnGoodsID = ErpReturnGoodsModel::where([['ReturnID', '=', $params['ReturnID']]])->column('ReturnGoodsID');
                ErpCustomerStockModel::where([['StockId', 'in', $ReturnGoodsID]])->delete();
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '更新失败');
        }

    }

    /**
     * 删除
     * @param $params
     * @return void
     */
    public function delete($params) {

        $ReturnGoodsID = ErpReturnGoodsModel::where([['ReturnID', '=', $params['ReturnID']]])->column('ReturnGoodsID');

        Db::startTrans();
        try {

            ErpReturnModel::where([['ReturnID', '=', $params['ReturnID']]])->delete();
            ErpReturnGoodsModel::where([['ReturnID', '=', $params['ReturnID']]])->delete();
            ErpReturnGoodsDetailModel::where([['ReturnGoodsID', 'in', $ReturnGoodsID]])->delete();

            //清理库存记录
            ErpCustomerStockModel::where([['StockId', 'in', $ReturnGoodsID]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '删除失败');
        }

    }

}

