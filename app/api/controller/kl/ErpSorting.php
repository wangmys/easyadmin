<?php
declare (strict_types = 1);

namespace app\api\controller\kl;
use app\api\constants\ApiConstant;
use app\BaseController;
use think\exception\ValidateException;
use think\Request;
use think\facade\Db;
use app\api\service\kl\SortingService;
use app\api\validate\SortingValidate;

class ErpSorting extends BaseController
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function create() {

        $params = $this->request->param();

        try {
            validate(SortingValidate::class)->scene('create')->check($params);
        } catch (ValidateException $exception) {
            return json(['code'=>400, 'msg'=>$exception->getError(), 'data'=>[]]);
        }

        $sortingService = new SortingService();
        $sortingService->createSorting($params);
        return json(['code'=>200, 'msg'=>'okk', 'data'=>[]]);

    }

}
