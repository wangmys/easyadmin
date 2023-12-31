define(["jquery", "easy-admin2"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        update_url: 'system.puhuozdkphmd/update',
        add_url: 'system.puhuozdkphmd/add',
        delete_url: 'system.puhuozdkphmd/delete',
        list_index: '/admin/system.puhuozdkphmd/index',
        url_import: '/admin/system.puhuozdkphmd/import_excel'
    };

    var table = layui.table,
        treetable = layui.treetable,
        upload=layui.upload,
        form = layui.form
    var Controller = {

        index: function () {

            $('body').on('click', '.excel_tpl', function (obj) {
                location.href = 'http://im.babiboy.com/static/m/images/货品等级模板.xlsx';

            })

            url = ea.url('/system.puhuozdkphmd/getWeatherField');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {
                var Yuncang_list = res.Yuncang_list;
                var Mathod_list = res.Mathod_list;

                var cols = [
                    {type: "checkbox",fixed:'left'},
                    {field: 'Yuncang', width: 70, title: '云仓',fixed:'left',search: 'select',selectList:Yuncang_list,laySearch:true},
                    {field: 'Mathod', width: 70, title: '经营模式',fixed:'left',search: 'select',selectList:Mathod_list,laySearch:true},
                    {field: 'B', width: 80, title: 'B级',search: false},
                    {field: 'A1', width: 80, title: 'A1级',search: false},
                    {field: 'A2', width: 80, title: 'A2级',search: false},
                    {field: 'A3', width: 80, title: 'A3级',search: false},
                    {field: 'N', width: 80, title: 'N级',search: false},
                    {field: 'H3', width: 80, title: 'H3级',search: false},
                    {field: 'H6', width: 80, title: 'H6级',search: false},
                    {field: 'K1', width: 80, title: 'K1级',search: false},
                    {field: 'K2', width: 80, title: 'K2级',search: false},
                    {field: 'X1', width: 80, title: 'X1级',search: false},
                    {field: 'X2', width: 80, title: 'X2级',search: false},
                    {field: 'X3', width: 80, title: 'X3级',search: false},
                ];

                cols.push({
                    width: 120,
                    title: '操作',
                    templet: ea.table.tool,
                    operat: [
                        [{
                            text: '编辑',
                            url: init.update_url,
                            method: 'open',
                            auth: '',
                            class: 'layui-btn layui-btn-normal layui-btn-xs',
                            field:'id'
                        }],
                        // 'delete'
                    ],
                    fixed: 'right'
                })

                ea.table.render({
                    init: init,//如果使用toolbar['add','delete']等这些默认配置，这句必须要加上！！
                    url: init.list_index,
                    search:true,
                    height: 800,
                    limit: 1000,
                    toolbar:[
                        'add','delete', [{
                            text: '模板下载',
                            url: 'http://im.babiboy.com/static/m/images/puhuo_tpl.xlsx',
                            method: 'none',
                            auth: 'add',
                            class: 'layui-btn layui-btn-normal layui-btn-sm excel_tpl',
                            // icon: 'fa fa-plus ',
                            // extend: 'data-full="true"',
                        }],
                        [{
                            text: '导入',
                            url: init.add_url,
                            method: 'none',
                            auth: 'add',
                            class: 'layui-btn layui-btn-normal layui-btn-sm upload_excel',
                            // icon: 'fa fa-plus ',
                            // extend: 'data-import="true"',
                        }]
                    ],
                    // defaultToolbar: false, //这里在右边显示
                    limits:[1000,2000,3000],
                    cols: [cols],
                    done:function (res, curr, count) {


                    }
                });

                //excel转换
                //指定允许上传的文件类型
                upload.render({
                    elem: '.upload_excel'
                    , url: init.url_import //此处配置你自己的上传接口即可
                    , accept: 'file' //普通文件
                    , before: function (obj) {
                        layer.load();
                    }
                    , error: function () {
                        //上传成功结束加载效果
                        setTimeout(function () {
                            layui.layer.closeAll();
                        }, 2000);
                    }
                    , done: function (res) {
                        layer.closeAll('loading');
                        if (res.code != 1) {
                            // $("#msg").css('color', 'red');
                            // $("#msg").html('上传失败');
                            // $("#msg").show();
                            layer.msg(res.msg, {time: 2000, icon: 2})
                        } else {
                            // $("#msg").show();
                            layer.msg('上传成功', {time: 2000, icon: 1});
                            layui.table.reload('currentTableRenderId')
                            // table.reload('#currentTable')
                        }
                    }
                });

                ea.listen();


            },function (res) {
                alert('失败')
            })
        },
        update: function () {
            ea.listen();
        },
        add: function () {
            ea.listen();
        }
    };
    return Controller;
});