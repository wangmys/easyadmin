<?php

namespace app\api\service\kl;
use app\common\traits\Singleton;
use app\api\model\kl\ErpRetailModel;
use app\api\model\kl\ErpRetailGoodsModel;
use app\api\model\kl\ErpRetailGoodsDetailModel;
use app\api\model\kl\ErpRetailPayModel;
use app\api\model\kl\ErpCustomerStockModel;
use app\api\model\kl\ErpCustomerStockDetailModel;
use app\common\constants\AdminConstant;
use think\facade\Db;

class RetailService
{

    use Singleton;

    public function createRetail($params) {

        Db::startTrans();
        try {

            //零售核销单主表 处理
            ErpRetailModel::create($this->dealRetailParams($params));

        } catch (\Exception $e) {
            Db::rollback();
            log_error($e);
            abort(0, '保存失败');
        }

    }

    protected function dealRetailParams($params) {

        return [
            'RetailID' => '',
            'CustomerId' => '',
            'CustomerName' => '',
            'RetailDate' => '',
            'ClassName' => '',
            'BranchId' => '',
            'CodingCode' => '',
            'CodingCodeText' => '',
            'WorkflowId' => '',
            'CreateTime' => '',
            'CreateUserId' => '',
            'CreateUserName' => '',
            'UpdateTime' => '',
            'UpdateUserId' => '',
            'UpdateUserName' => '',
            'Version' => '',
            'IsPause' => '',
            'BillType' => '',
            'PrintNum' => '',
            'BillStatus' => '',
        ];

    }

}

