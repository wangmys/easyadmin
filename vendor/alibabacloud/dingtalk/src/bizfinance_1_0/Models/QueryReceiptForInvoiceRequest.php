<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Dingtalk\Vbizfinance_1_0\Models;

use AlibabaCloud\Tea\Model;

class QueryReceiptForInvoiceRequest extends Model
{
    /**
     * @description 发票状态筛选列表 applied 已生成、unapplied 未生成 、 ignore 已忽略
     *
     * @var string[]
     */
    public $applyStatusList;

    /**
     * @var int
     */
    public $endTime;

    /**
     * @description 分页参数，从1 开始
     *
     * @var int
     */
    public $pageNumber;

    /**
     * @description 分页参数，每页查询个数
     *
     * @var int
     */
    public $pageSize;

    /**
     * @description 单据状态筛选条件列表，审批中、已通过 RUNNGIN、COMPLETED
     *
     * @var string[]
     */
    public $receiptStatusList;

    /**
     * @description 开始时间
     *
     * @var int
     */
    public $startTime;

    /**
     * @description 单据标题
     *
     * @var string
     */
    public $title;
    protected $_name = [
        'applyStatusList'   => 'applyStatusList',
        'endTime'           => 'endTime',
        'pageNumber'        => 'pageNumber',
        'pageSize'          => 'pageSize',
        'receiptStatusList' => 'receiptStatusList',
        'startTime'         => 'startTime',
        'title'             => 'title',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->applyStatusList) {
            $res['applyStatusList'] = $this->applyStatusList;
        }
        if (null !== $this->endTime) {
            $res['endTime'] = $this->endTime;
        }
        if (null !== $this->pageNumber) {
            $res['pageNumber'] = $this->pageNumber;
        }
        if (null !== $this->pageSize) {
            $res['pageSize'] = $this->pageSize;
        }
        if (null !== $this->receiptStatusList) {
            $res['receiptStatusList'] = $this->receiptStatusList;
        }
        if (null !== $this->startTime) {
            $res['startTime'] = $this->startTime;
        }
        if (null !== $this->title) {
            $res['title'] = $this->title;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryReceiptForInvoiceRequest
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['applyStatusList'])) {
            if (!empty($map['applyStatusList'])) {
                $model->applyStatusList = $map['applyStatusList'];
            }
        }
        if (isset($map['endTime'])) {
            $model->endTime = $map['endTime'];
        }
        if (isset($map['pageNumber'])) {
            $model->pageNumber = $map['pageNumber'];
        }
        if (isset($map['pageSize'])) {
            $model->pageSize = $map['pageSize'];
        }
        if (isset($map['receiptStatusList'])) {
            if (!empty($map['receiptStatusList'])) {
                $model->receiptStatusList = $map['receiptStatusList'];
            }
        }
        if (isset($map['startTime'])) {
            $model->startTime = $map['startTime'];
        }
        if (isset($map['title'])) {
            $model->title = $map['title'];
        }

        return $model;
    }
}
