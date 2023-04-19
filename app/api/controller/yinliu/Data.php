<?php
declare (strict_types = 1);

namespace app\api\controller\yinliu;
use app\api\constants\ApiConstant;
use app\BaseController;
use app\api\service\bi\yinliu\YinliuDataService;
use app\api\service\bi\fatory\CreateFactory;

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
     *  拉取配饰问题数据并储存
     */
    public function pullYinliuData()
    {
        $Date = $this->Date;
        $model = $this->service;
        $code = $model->save($Date);
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }

    /**
     *  生成问题统计
     */
    public function createYinliuTotal()
    {
        $Date = $this->Date;
        $model = $this->service;
        $code = $model->create($Date);
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }

    /**
     *  更新周一问题状态
     */
    public function updateYinliuState()
    {
        $Date = $this->Date;
        $model = $this->service;
        // 检测周一任务完成度
        $code = $model->checkMondayComplete($Date);
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
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

    /**
     * 拉取引流款问题数据
     */
    public function pullDressData()
    {
        $model = CreateFactory::createService('yinliu');
        $Date = $this->Date;
        // 拉取引流款库存不达标数据
        $code = $model->pullYinliuData($Date);
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }

    /**
     * 执行引流款问题数据任务
     */
    public function executeDeessTask()
    {
        $Date = $this->Date;
        $model = CreateFactory::createService('yinliu');
        // 拉取引流款库存不达标数据
        $model->pullYinliuData($Date);
        $code = $model->updateMondayTaskStatue($Date);
        return json([
            'code' => $code,
            'msg' => $model->getError($code)
        ]);
    }
}
