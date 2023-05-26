<?php
declare (strict_types = 1);

namespace app\api\controller\kl;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\Request;
use think\facade\Db;
use app\api\service\kl\RetailService;

class ErpRetail extends BaseController
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function create() {

        $params = $this->request->param();
        $retailService = new RetailService();
        $retailService->createRetail($params);
        print_r($params);exit;

    }

}
