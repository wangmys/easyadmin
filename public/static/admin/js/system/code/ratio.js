define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.command.index/index',
        list_url: 'system.command.index/list',
        export_url: 'system.command.index/index_export'
    };
    var table = layui.table

    var Controller = {
        index: function () {
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.code.ratio/index',
                   export_url: 'system.command.index/index_export'
                },
                where:{filter:[]},
                height: 760,
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [[
                    {field: '图片', minWidth: 80, title: '图片', search: false, templet: ea.table.image},
                    {field: '尺码情况', minWith: 134, title: '尺码情况',search:false},
                    {field: '货号', minWith: 134, title: '货号',search:false},
                    {field: '总库存', minWith: 134, title: '总库存',search:false},
                    {field: '累销尺码比', minWith: 134, title: '累销尺码比',search:false},
                    {field: '单码售罄', minWith: 134, title: '单码售罄',search:false},
                    {field: '周转', minWith: 134, title: '周转',search:false},
                    {field: '当前总库存量', minWith: 134, title: '当前总库存量',search:false},
                    {field: '未入量', minWith: 134, title: '未入量'},
                    {field: '累销', minWith: 134, title: '累销'},
                    {field: '周销', minWith: 134, title: '周销'},
                    {field: '店铺库存', minWith: 134, title: '店铺库存'},
                    {field: '云仓库存', minWith: 134, title: '云仓库存'},
                    {field: '云仓在途库存', minWith: 134, title: '云仓在途库存'},
                    {field: '当前单店均深', minWith: 134, title: '当前单店均深'}
                ]]
            });
            ea.listen();
        }
    };
    return Controller;
});