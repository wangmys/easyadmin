<?php

// This file is auto-generated, don't edit it. Thanks.

namespace AlibabaCloud\SDK\Dingtalk\Vtrajectory_1_0\Models;

use AlibabaCloud\SDK\Dingtalk\Vtrajectory_1_0\Models\QueryAppActiveUsersResponseBody\list_;
use AlibabaCloud\Tea\Model;

class QueryAppActiveUsersResponseBody extends Model
{
    /**
     * @description 是否存在更多数据需要获取
     *
     * @var bool
     */
    public $hasMore;

    /**
     * @description 数据集合
     *
     * @var list_[]
     */
    public $list;

    /**
     * @description 下一次获取开始位置
     *
     * @var int
     */
    public $nextToken;

    /**
     * @description 总数
     *
     * @var int
     */
    public $totalCount;
    protected $_name = [
        'hasMore'    => 'hasMore',
        'list'       => 'list',
        'nextToken'  => 'nextToken',
        'totalCount' => 'totalCount',
    ];

    public function validate()
    {
    }

    public function toMap()
    {
        $res = [];
        if (null !== $this->hasMore) {
            $res['hasMore'] = $this->hasMore;
        }
        if (null !== $this->list) {
            $res['list'] = [];
            if (null !== $this->list && \is_array($this->list)) {
                $n = 0;
                foreach ($this->list as $item) {
                    $res['list'][$n++] = null !== $item ? $item->toMap() : $item;
                }
            }
        }
        if (null !== $this->nextToken) {
            $res['nextToken'] = $this->nextToken;
        }
        if (null !== $this->totalCount) {
            $res['totalCount'] = $this->totalCount;
        }

        return $res;
    }

    /**
     * @param array $map
     *
     * @return QueryAppActiveUsersResponseBody
     */
    public static function fromMap($map = [])
    {
        $model = new self();
        if (isset($map['hasMore'])) {
            $model->hasMore = $map['hasMore'];
        }
        if (isset($map['list'])) {
            if (!empty($map['list'])) {
                $model->list = [];
                $n           = 0;
                foreach ($map['list'] as $item) {
                    $model->list[$n++] = null !== $item ? list_::fromMap($item) : $item;
                }
            }
        }
        if (isset($map['nextToken'])) {
            $model->nextToken = $map['nextToken'];
        }
        if (isset($map['totalCount'])) {
            $model->totalCount = $map['totalCount'];
        }

        return $model;
    }
}
