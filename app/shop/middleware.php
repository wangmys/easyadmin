<?php
// 这是系统自动生成的middleware定义文件
return [

    // Session初始化
    \think\middleware\SessionInit::class,

    // 系统操作日志
    \app\admin\middleware\SystemLog::class,

    // Csrf安全校验
    \app\admin\middleware\CsrfMiddleware::class,
];
