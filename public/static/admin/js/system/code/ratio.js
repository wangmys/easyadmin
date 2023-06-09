define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.code.ratio/index',
        list_url: 'system.code.ratio/list',
        index3_url: 'system.code.ratio/index3',
        export_url: 'system.code.ratio/index_export'
    };
    var table = layui.table

    var Controller = {
        index: function () {
            var $get = JSON.parse($("#where").val());
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.code.ratio/index',
                   export_url: 'system.command.index/index_export'
                },
                where:$get,
                height: 760,
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [[
                    // {field: '风格', width: 115, title: '风格',search:false},
                    // {field: '一级分类', width: 115, title: '一级分类',search:false},
                    // {field: '二级分类', width: 115, title: '二级分类',search:false},
                    // {field: '领型', width: 115, title: '领型',search:false},
                    // {field: '近三天折率', width: 115, title: '近三天折率',search:false},
                    // {field: '货品等级', width: 115, title: '货品等级',search:false},
                    // {field: '上柜数', width: 115, title: '上柜数',search:false},
                    // {field: '图片', widthh: 80, title: '图片', search: false, templet: ea.table.image},
                    {field: '尺码情况', width: 115, title: '尺码情况',search:false},
                    {field: '货号', width: 115, title: '货号',search:false},
                    {field: '单码售罄比', width: 115, title: '单码售罄比',search:false},
                    {field: '当前库存', width: 115, title: '当前库存',search:false},
                    {field: '总库存', width: 115, title: '总库存',search:false},
                    {field: '累销尺码比', width: 115, title: '累销尺码比',search:false},
                    {field: '单码售罄', width: 115, title: '单码售罄',search:false},
                    {field: '周转', width: 115, title: '周转',search:false},
                    {field: '当前总库存量', width: 115, title: '当前总库存量',search:false},
                    {field: '未入量', width: 115, title: '未入量',search:false},
                    {field: '累销', width: 115, title: '累销',search:false},
                    {field: '周销', width: 115, title: '周销',search:false},
                    {field: '店铺库存', width: 115, title: '店铺库存',search:false},
                    {field: '云仓库存', width: 115, title: '云仓库存',search:false},
                    {field: '云仓在途库存', width: 115, title: '云仓在途库存',search:false},
                    {field: '当前单店均深', width: 115, title: '当前单店均深',search:false}
                ]]
            });
            ea.listen();
        },
        index3: function () {
            var $get = JSON.parse($("#where").val());
            var $cols = JSON.parse($("#cols").val());
            console.log($cols)
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.code.ratio/index3',
                   export_url: 'system.command.index/index_export'
                },
                where:$get,
                height: 760,
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [$cols],
                done: function (res, curr, count) {
                   console.log(45)
                }
            });
            ea.listen();
        },
        list: function () {
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.code.ratio/list',
                   export_url: 'system.command.index/index_export'
                },
                where:{filter:[]},
                height: 760,
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [[
                    {field: '全国排名', width: 115, title: '全国排名',search:false},
                    {field: '货号', width: 115, title: '货号',search:false},
                    {field: '风格', width: 115, title: '风格',search:true},
                    {field: '一级分类', width: 115, title: '一级分类',search:true},
                    {field: '二级分类', width: 115, title: '二级分类',search:true},
                    {field: '领型', width: 115, title: '领型',search:true},
                    {field: '近三天折率', width: 115, title: '近三天折率',search:false},
                    {field: '货品等级', width: 115, title: '货品等级',search:false},
                    {field: '上柜家数', width: 115, title: '上柜家数',search:false},
                    {field: '上市天数', width: 115, title: '上市天数',search:false},
                    {field: '日均销', width: 115, title: '日均销',search:false},
                    {field: '图片', width: 80, title: '图片', search: false, templet: ea.table.image},
                    {
                        width: 120,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '查看详情',
                                url: init.index_url,
                                method: 'open',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                                extend:"data-full='true' data-title = '尺码情况' ",
                                field:'货号'
                            }],
                        ],
                        fixed: 'right'
                    }
                ]]
            });

            ea.listen();
        },
        alllist: function () {
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.code.ratio/alllist',
                   export_url: 'system.code.index/index_export'
                },
                where:{filter:[]},
                height: 760,
                toolbar:[],
                limit:20,
                limits:[15,20,50,100,200,1000],
                cols: [[
                    {field: '全国排名', width: 115, title: '全国排名',search:false},
                    {field: '货号', width: 115, title: '货号',search:false},
                    {field: '风格', width: 115, title: '风格',search:true},
                    {field: '一级分类', width: 115, title: '一级分类',search:true},
                    {field: '二级分类', width: 115, title: '二级分类',search:true},
                    {field: '领型', width: 115, title: '领型',search:true},
                    {field: '近三天折率', width: 115, title: '近三天折率',search:false},
                    {field: '货品等级', width: 115, title: '货品等级',search:false},
                    {field: '上柜家数', width: 115, title: '上柜家数',search:false},
                    {field: '上市天数', width: 115, title: '上市天数',search:false},
                    {field: '日均销', width: 115, title: '日均销',search:false},
                    {field: '图片', width: 80, title: '图片', search: false, templet: ea.table.image},
                    {field: '字段', width: 80, title: '字段', search: false},
                    {field: '合计', width: 115, title: '合计', search: false},
                    {field: '库存_00/28/37/44/100/160/S', width: 160, title: '库存_00/28/37/44/100/160/S', search: false},
                    {field: '库存_29/38/46/105/165/M', width: 160, title: '库存_29/38/46/105/165/M', search: false},
                    {field: '库存_30/39/48/110/170/L', width: 160, title: '库存_30/39/48/110/170/L', search: false},
                    {field: '库存_31/40/50/115/175/XL', width: 160, title: '库存_31/40/50/115/175/XL', search: false},
                    {field: '库存_32/41/52/120/180/2XL', width: 160, title: '库存_32/41/52/120/180/2XL', search: false},
                    {field: '库存_33/42/54/125/185/3XL', width: 160, title: '库存_33/42/54/125/185/3XL', search: false},
                    {field: '库存_34/43/56/190/4XL', width: 160, title: '库存_34/43/56/190/4XL', search: false},
                    {field: '库存_35/44/58/195/5XL', width: 160, title: '库存_35/44/58/195/5XL', search: false},
                    {field: '库存_36/6XL', width: 110, title: '库存_36/6XL', search: false},
                    {field: '库存_38/7XL', width: 110, title: '库存_38/7XL', search: false},
                    {field: '库存_40/8XL', width: 110, title: '库存_40/8XL', search: false},
                    // {
                    //     width: 120,
                    //     title: '操作',
                    //     templet: ea.table.tool,
                    //     operat: [
                    //         [{
                    //             text: '查看详情',
                    //             url: init.index_url,
                    //             method: 'open',
                    //             auth: '',
                    //             class: 'layui-btn layui-btn-normal layui-btn-xs',
                    //             extend:"data-full='true' data-title = '尺码情况' ",
                    //             field:'货号'
                    //         }],
                    //     ],
                    //     fixed: 'right'
                    // }
                ]]
            });

            ea.listen();
        }
    };
    return Controller;
});