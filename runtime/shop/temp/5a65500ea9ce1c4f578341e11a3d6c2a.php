<?php /*a:2:{s:62:"C:\phpstudy_pro\WEB\easyadmin\app\shop\view\index\welcome.html";i:1677550867;s:63:"C:\phpstudy_pro\WEB\easyadmin\app\shop\view\layout\default.html";i:1677585498;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo sysconfig('site','site_name'); ?></title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <link rel="stylesheet" href="/static/admin/css/public.css?v=<?php echo htmlentities($version); ?>" media="all">
    <script>
        window.CONFIG = {
            ADMIN: "<?php echo htmlentities((isset($adminModuleName) && ($adminModuleName !== '')?$adminModuleName:'admin')); ?>",
            CONTROLLER_JS_PATH: "<?php echo htmlentities((isset($thisControllerJsPath) && ($thisControllerJsPath !== '')?$thisControllerJsPath:'')); ?>",
            ACTION: "<?php echo htmlentities((isset($thisAction) && ($thisAction !== '')?$thisAction:'')); ?>",
            AUTOLOAD_JS: "<?php echo htmlentities((isset($autoloadJs) && ($autoloadJs !== '')?$autoloadJs:'false')); ?>",
            IS_SUPER_ADMIN: "<?php echo htmlentities((isset($isSuperAdmin) && ($isSuperAdmin !== '')?$isSuperAdmin:'false')); ?>",
            VERSION: "<?php echo htmlentities((isset($version) && ($version !== '')?$version:'1.0.0')); ?>",
            CSRF_TOKEN: "<?php echo token(); ?>",
            ADMIN_ID: "<?php echo $adminId; ?>",
        };
    </script>
    <script src="/static/plugs/layui-v2.5.6/layui.all.js?v=<?php echo htmlentities($version); ?>" charset="utf-8"></script>
    <script src="/static/plugs/require-2.3.6/require.js?v=<?php echo htmlentities($version); ?>" charset="utf-8"></script>
    <script src="/static/config-admin.js?v=<?php echo htmlentities($version); ?>" charset="utf-8"></script>
</head>
<body>
<link rel="stylesheet" href="/static/admin/css/welcome.css?v=<?php echo time(); ?>" media="all">
<form class="layui-form" action="">
<div class="layuimini-container">
    <div class="layuimini-main">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md8">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md6">
                        <div class="layui-card">
                            <div class="layui-card-header"><i class="fa fa-warning icon"></i>????????????</div>
                            <div class="layui-card-body">
                                <div class="welcome-module">
                                    <div class="layui-row layui-col-space10">
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-blue">??????</span>
                                                        <h5>????????????</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins">1234</h1>
                                                        <small>????????????????????????</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-cyan">??????</span>
                                                        <h5>????????????</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins">1234</h1>
                                                        <small>????????????????????????</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-orange">??????</span>
                                                        <h5>????????????</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins">1234</h1>
                                                        <small>????????????????????????</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-green">??????</span>
                                                        <h5>????????????</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins">1234</h1>
                                                        <small>????????????????????????</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-md6">
                        <div class="layui-card">
                            <div class="layui-card-header"><i class="fa fa-credit-card icon icon-blue"></i>????????????</div>
                            <div class="layui-card-body">
                                <div class="welcome-module">
                                    <div class="layui-row layui-col-space10 layuimini-qiuck">

                                        <?php foreach($quicks as $vo): ?>
                                        <div class="layui-col-xs3 layuimini-qiuck-module">
                                            <a layuimini-content-href="<?php echo url($vo['href']); ?>" data-title="<?php echo htmlentities($vo['title']); ?>">
                                                <i class="<?php echo $vo['icon']; ?>"></i>
                                                <cite><?php echo htmlentities($vo['title']); ?></cite>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-header"><i class="fa fa-line-chart icon"></i>????????????</div>
                            <div class="layui-card-body">
                                <div id="echarts-records" style="width: 100%;min-height:500px"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-col-md4">

                <div class="layui-card">
                    <div class="layui-card-header"><i class="fa fa-bullhorn icon icon-tip"></i>????????????</div>
                    <div class="layui-card-body layui-text">
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title">?????????????????????</div>
                            <div class="layuimini-notice-extra">2019-07-11 23:06</div>
                            <div class="layuimini-notice-content layui-hide">
                                ???????????????????????????<br>
                                ?????????????????????????????????????????????????????????????????????????????????<br>
                                ?????????tab???????????????????????????<br>
                                ???????????????????????????font-awesome???????????????????????????<br>
                                ???????????????????????????????????????????????????????????????????????????????????????????????????<br>
                                url??????hash?????????????????????????????????tab??????????????????<br>
                                ??????????????????????????????????????????????????????????????????????????????????????????<br>
                                ???????????????????????????<br>
                            </div>
                        </div>
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title">????????????404??????</div>
                            <div class="layuimini-notice-extra">2019-07-11 12:57</div>
                            <div class="layuimini-notice-content layui-hide">
                                ???????????????????????????<br>
                                ?????????????????????????????????????????????????????????????????????????????????<br>
                                ?????????tab???????????????????????????<br>
                                ???????????????????????????font-awesome???????????????????????????<br>
                                ???????????????????????????????????????????????????????????????????????????????????????????????????<br>
                                url??????hash?????????????????????????????????tab??????????????????<br>
                                ??????????????????????????????????????????????????????????????????????????????????????????<br>
                                ???????????????????????????<br>
                            </div>
                        </div>
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title">??????treetable???????????????????????????</div>
                            <div class="layuimini-notice-extra">2019-07-05 14:28</div>
                            <div class="layuimini-notice-content layui-hide">
                                ???????????????????????????<br>
                                ?????????????????????????????????????????????????????????????????????????????????<br>
                                ?????????tab???????????????????????????<br>
                                ???????????????????????????font-awesome???????????????????????????<br>
                                ???????????????????????????????????????????????????????????????????????????????????????????????????<br>
                                url??????hash?????????????????????????????????tab??????????????????<br>
                                ??????????????????????????????????????????????????????????????????????????????????????????<br>
                                ???????????????????????????<br>
                            </div>
                        </div>
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title">??????logo????????????</div>
                            <div class="layuimini-notice-extra">2019-07-04 11:02</div>
                            <div class="layuimini-notice-content layui-hide">
                                ???????????????????????????<br>
                                ?????????????????????????????????????????????????????????????????????????????????<br>
                                ?????????tab???????????????????????????<br>
                                ???????????????????????????font-awesome???????????????????????????<br>
                                ???????????????????????????????????????????????????????????????????????????????????????????????????<br>
                                url??????hash?????????????????????????????????tab??????????????????<br>
                                ??????????????????????????????????????????????????????????????????????????????????????????<br>
                                ???????????????????????????<br>
                            </div>
                        </div>
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title">????????????????????????tab????????????</div>
                            <div class="layuimini-notice-extra">2019-06-17 11:55</div>
                            <div class="layuimini-notice-content layui-hide">
                                ???????????????????????????<br>
                                ?????????????????????????????????????????????????????????????????????????????????<br>
                                ?????????tab???????????????????????????<br>
                                ???????????????????????????font-awesome???????????????????????????<br>
                                ???????????????????????????????????????????????????????????????????????????????????????????????????<br>
                                url??????hash?????????????????????????????????tab??????????????????<br>
                                ??????????????????????????????????????????????????????????????????????????????????????????<br>
                                ???????????????????????????<br>
                            </div>
                        </div>
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title">???????????????????????????????????????</div>
                            <div class="layuimini-notice-extra">2019-06-13 14:53</div>
                            <div class="layuimini-notice-content layui-hide">
                                ???????????????????????????<br>
                                ?????????????????????????????????????????????????????????????????????????????????<br>
                                ?????????tab???????????????????????????<br>
                                ???????????????????????????font-awesome???????????????????????????<br>
                                ???????????????????????????????????????????????????????????????????????????????????????????????????<br>
                                url??????hash?????????????????????????????????tab??????????????????<br>
                                ??????????????????????????????????????????????????????????????????????????????????????????<br>
                                ???????????????????????????<br>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card">
                    <div class="layui-card-header"><i class="fa fa-fire icon"></i>????????????</div>
                    <div class="layui-card-body layui-text">



                              <div class="layui-form-item">
                                <label class="layui-form-label">?????????</label>
                                <div class="layui-input-block">
                                  <input type="text" name="title" required  lay-verify="required" placeholder="???????????????" autocomplete="off" class="layui-input">
                                </div>
                              </div>
                              <div class="layui-form-item">
                                <label class="layui-form-label">?????????</label>
                                <div class="layui-input-inline">
                                  <input type="password" name="password" required lay-verify="required" placeholder="???????????????" autocomplete="off" class="layui-input">
                                </div>
                                <div class="layui-form-mid layui-word-aux">????????????</div>
                              </div>


                              <div class="layui-form-item layui-form-text">
                                <label class="layui-form-label">?????????</label>
                                <div class="layui-input-block">
                                  <textarea name="desc" placeholder="???????????????" class="layui-textarea"></textarea>
                                </div>
                              </div>
                              <div class="layui-form-item">
                                <div class="layui-input-block">
                                  <button class="layui-btn" lay-submit lay-filter="formDemo">????????????</button>
                                  <button type="reset" class="layui-btn layui-btn-primary">??????</button>
                                </div>
                              </div>


                    </div>
                </div>

                <div class="layui-card">
                    <div class="layui-card-header"><i class="fa fa-paper-plane-o icon"></i>????????????</div>

                </div>

            </div>
        </div>
    </div>
</div>
</form>
</body>
</html>