<?php
declare (strict_types = 1);

namespace app\api\controller\yinliu;
use app\api\constants\ApiConstant;
use app\BaseController;
use app\api\service\bi\yinliu\YinliuDataService;

class Data extends BaseController
{
    /**
     * 服务
     * @var YinliuDataService|null
     */
    protected $service = null;
    // 日期
    protected $Date = '';

    public function index()
    {
        return '您好！这是一个[api]示例应用';
    }

    public function __construct()
    {
        // 初始化日期
        $this->Date = date('Y-m-d');
        $this->service = new YinliuDataService;
    }

    /**
     *  拉取问题数据并储存
     */
    public function pullYinliuData()
    {
        $Date = $this->Date;
        $code = $this->service->save($Date);
        return json([
            'code' => $code,
            'msg' => $this->service->getError($code)
        ]);
    }

    /**
     *  生成问题统计
     */
    public function createYinliuTotal()
    {
        $Date = $this->Date;
        $code = $this->service->create($Date);
        return json([
            'code' => $code,
            'msg' => $this->service->getError($code)
        ]);
    }

    /**
     *  生成问题统计
     */
    public function updateYinliuState()
    {
        $Date = $this->Date;
        $code = $this->service->checkMondayComplete($Date);
        return json([
            'code' => $code,
            'msg' => $this->service->getError($code)
        ]);
    }

    /**
     * 执行指定任务
     * @return \think\response\Json
     */
    public function run()
    {
        // 拉取问题数据
        $this->pullYinliuData();
        // 生成问题统计
        $this->createYinliuTotal();
        // 更新周一问题状态
        return $this->updateYinliuState();
    }
}
