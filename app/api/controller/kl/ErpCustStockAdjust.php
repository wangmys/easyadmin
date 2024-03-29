<?php
declare (strict_types = 1);

namespace app\api\controller\kl;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\exception\ValidateException;
use think\Request;
use think\facade\Db;
use app\api\service\kl\CustStockAdjustService;
use app\api\validate\CustStockAdjustValidate;

class ErpCustStockAdjust extends BaseController
{
    protected $request;
    protected $service;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new CustStockAdjustService();
    }

    /**
     * 创建
     * @return \think\response\Json
     */
    public function create() {

        $params = $this->request->param();

        try {
            validate(CustStockAdjustValidate::class)->scene('create')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        try {
            $this->service->create($params);
        } catch (\Exception $e) {
            return json(['code'=>500, 'msg'=>$e->getMessage(), 'data'=>[]]);
        }

        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

    /**
     * 更新
     * @return \think\response\Json
     */
    public function update() {

        $params = $this->request->param();

        try {
            validate(CustStockAdjustValidate::class)->scene('update')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        try {
            $this->service->update($params);
        } catch (\Exception $e) {
            return json(['code'=>500, 'msg'=>$e->getMessage(), 'data'=>[]]);
        }
        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

    /**
     * 删除
     * @return \think\response\Json
     */
    public function delete() {

        $params = $this->request->param();

        try {
            validate(CustStockAdjustValidate::class)->scene('delete')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        try {
            $this->service->delete($params);
        } catch (\Exception $e) {
            return json(['code'=>500, 'msg'=>$e->getMessage(), 'data'=>[]]);
        }
        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

}
