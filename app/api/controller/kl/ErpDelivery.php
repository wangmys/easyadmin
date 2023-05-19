<?php
declare (strict_types = 1);

namespace app\api\controller\kl;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\exception\ValidateException;
use think\Request;
use think\facade\Db;
use app\api\service\kl\DeliveryService;
use app\api\validate\DeliveryValidate;

class ErpDelivery extends BaseController
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 创建 仓库出货单
     * @return \think\response\Json
     */
    public function create() {

        $params = $this->request->param();

        try {
            validate(DeliveryValidate::class)->scene('create')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        $deliveryService = new DeliveryService();
        $deliveryService->createdelivery($params);
        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

    /**
     * 更新 仓库出货单
     * @return \think\response\Json
     */
    public function update() {

        $params = $this->request->param();

        try {
            validate(DeliveryValidate::class)->scene('update')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        $deliveryService = new DeliveryService();
        $deliveryService->updatedelivery($params);
        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

    /**
     * 删除 仓库出货单
     * @return \think\response\Json
     */
    public function delete() {

        $params = $this->request->param();

        try {
            validate(DeliveryValidate::class)->scene('delete')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        $deliveryService = new DeliveryService();
        $deliveryService->deletedelivery($params);
        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

}
