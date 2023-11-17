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
                    // {field: '上柜家数', width: 115, title: '上柜家数',search:false},
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

            // 自定义模块
            layui.config({
                base: '/static/ok/layuiadmin/modules/'
            }).extend({
                tableMerge: 'tableMerge'
            });

            layui.use(['form', 'table','tableMerge'], function () {
                tableMerge = layui.tableMerge;
                // 风格
                var Style = JSON.parse($("#Style").val());
                var CategoryName1 = JSON.parse($("#CategoryName1").val());
                var CategoryName2 = JSON.parse($("#CategoryName2").val());
                var Collar = JSON.parse($("#Collar").val());
                console.log(Collar)

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
                    limit:40,
                    size:'sm',
                    limits:[40,50,100,200,1000],
                    cols: [
                        [
                        {field: '全国排名', width: 60, title: '排名',search:false,fixed:'left'},
                        {field: '货号', width: 90, title: '货号',search: 'xmSelect',fixed:'left'},
                        {field: '风格', width: 60, title: '风格',search: 'xmSelect',selectList:Style,fixed:'left'},
                        {field: '一级分类', width: 60, title: '大类',fieldAlias:'cate',search:'select',selectList:CategoryName1,fixed:'left',hide:true},
                        {field: '二级分类', width: 80, title: '中类',fieldAlias:'cate2',search:'select',selectList:CategoryName2,fixed:'left',hide:true},
                        {field: '领型', width: 70, title: '领型',fieldAlias:'collar',search:'select',selectList:Collar,fixed:'left'},
                        {field: '近三天折率', width: 60, title: '折率',search:false},
                        {field: '货品等级', width: 60, title: '等级',search:false},
                        {field: '上柜家数', width: 60, title: '上柜数',search:false},
                        {field: '总库存', title: '总库存 >=',search:true,hide: true},
                        {field: 'showType', title: '展示方式',search:'select',hide: true,selectList:{1: '全部展示',2: '部分展示'},searchValue:'1'},
                        {field: 'isDanger', title: '是否偏码', search: 'select',hide: true,selectList:{1: '展示偏码'}},
                        // {field: '上市天数', width: 80, title: '上市天数',search:false},
                        // {field: '日均销', width: 80, title: '日均销',search:false},
                        {field: '图片', width: 110, title: '图片', search: false, templet: ea.table.image,imageHeight:30,merge: true},
                        {field: '字段', width: 95, title: '字段', search: false},
                        {field: '合计', width: 60, title: '合计', search: false},
                        {field: '库存_00/28/37/44/90/160/S', width: 70, title: '28/37/44/S', search: false},
                        {field: '库存_29/38/46/105/165/M', width: 70, title: '29/38/46/M', search: false},
                        {field: '库存_30/39/48/110/170/L', width: 70, title: '30/39/48/L', search: false},
                        {field: '库存_31/40/50/115/175/XL', width: 80, title: '31/40/50/XL', search: false},
                        {field: '库存_32/41/52/120/180/2XL', width: 80, title: '32/41/52/2XL', search: false},
                        {field: '库存_33/42/54/125/185/3XL', width: 80, title: '33/42/54/3XL', search: false},
                        {field: '库存_34/43/56/190/4XL', width: 80, title: '34/43/56/4XL', search: false},
                        {field: '库存_35/44/58/195/5XL', width: 80, title: '35/44/58/5XL', search: false},
                        {field: '库存_36/6XL', width: 60, title: '36/6XL', search: false},
                        {field: '库存_38/7XL', width: 60, title: '38/7XL', search: false},
                        {field: '库存_40/8XL', width: 60, title: '40/8XL', search: false}
                    ]
                    ],done: function(res){
                        tableMerge.render(this);
                        if(res.showType==2){
                            $('.layui-table tr:nth-child(8n) td').css({
                                'border-bottom':'1px solid red',
                                'box-sizing':'border-box',
                            })
                        }else{
                            $('.layui-table tr:nth-child(14n) td').css({
                                'border-bottom':'1px solid red',
                                'box-sizing':'border-box',
                            })
                        }
                    }
                });
            });

            ea.listen();
        },
        warehouseList: function () {

            // 自定义模块
            layui.config({
                base: '/static/ok/layuiadmin/modules/'
            }).extend({
                tableMerge: 'tableMerge'
            });

            layui.use(['form', 'table','tableMerge'], function () {
                tableMerge = layui.tableMerge;
                // 风格
                var Style = JSON.parse($("#Style").val());
                var CategoryName1 = JSON.parse($("#CategoryName1").val());
                var CategoryName2 = JSON.parse($("#CategoryName2").val());
                var Collar = JSON.parse($("#Collar").val());
                ea.table.render({
                    init:{
                       table_elem: '#currentTable',
                       table_render_id: 'currentTableRenderId',
                       index_url: 'system.code.ratio/warehouseList',
                       export_url: 'system.code.index/index_export'
                    },
                    where:{filter:[]},
                    height: 700,
                    toolbar:[
                        [
                            {
                                text: '广州云仓',
                                method: 'none',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-sm guangzhou',
                                event:'guangzhou'
                            },
                            {
                                text: '南昌云仓',
                                method: 'none',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-sm nanchang',
                                event:'nanchang'
                            },
                            {
                                text: '武汉云仓',
                                method: 'none',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-sm wuhan',
                                event:'wuhan'
                            },
                            {
                                text: '长沙云仓',
                                method: 'none',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-sm changsha',
                                event:'changsha'
                            },
                            {
                                text: '贵阳云仓',
                                method: 'none',
                                auth: '',
                                class: 'layui-btn layui-btn-normal layui-btn-sm guiyang',
                                event:'guiyang'
                            }
                        ]
                    ],
                    limit:40,
                    limits:[40,50,100,200,1000],
                    size:'sm',
                    cols: [
                        [
                            {field: '全国排名', width: 60, title: '排名',search:false,rowspan: 2,fixed:'left'},
                            {field: '货号', width: 100, title: '货号',search:true,rowspan: 2,fixed:'left'},
                            {field: '风格', width: 70, title: '风格',search:'select',selectList:Style,rowspan: 2,fixed:'left'},
                            {field: '一级分类', width: 70, title: '大类',fieldAlias:'cate',search:'select',selectList:CategoryName1,rowspan: 2,fixed:'left'},
                            {field: '二级分类', width: 80, title: '中类',fieldAlias:'cate2',search:'select',selectList:CategoryName2,rowspan: 2,fixed:'left'},
                            {field: '领型', width: 70, title: '领型',fieldAlias:'collar',search:'select',selectList:Collar,rowspan: 2,fixed:'left'},
                            {field: '近三天折率', width: 70, title: '折率',search:false,rowspan: 2},
                            {field: '货品等级', width: 65, title: '等级',search:false,rowspan: 2},
                            // {field: '上柜家数', width: 70, title: '上柜数',search:false,rowspan: 2},
                            // {field: '上市天数', width: 110, title: '上市天数',search:false,rowspan: 2},
                            // {field: '日均销', width: 110, title: '日均销',search:false,rowspan: 2},
                            {field: 'showType', title: '展示方式',search:'select',hide: true,selectList:{1: '全部展示',2: '部分展示'},searchValue:'1'},
                            {field: '图片', width: 120, title: '图片', search: false,rowspan: 2, templet: ea.table.image,imageHeight:30,merge: true},
                            {title: '广州云仓',colspan: 13},
                            {title: '南昌云仓',colspan: 13},
                            {title: '武汉云仓',colspan: 13},
                            {title: '长沙云仓',colspan: 13},
                            {title: '贵阳云仓',colspan: 13}
                        ],
                        [
                            {field: '广州_字段', width: 110, title: '字段', search: false},
                            {field: '广州_总计', width: 60, title: '总计', search: false},
                            {field: '广州_00/28/37/44/100/160/S', width: 80, title: '28/37/44/S', search: false},
                            {field: '广州_29/38/46/105/165/M', width: 80, title: '29/38/46/M', search: false},
                            {field: '广州_30/39/48/110/170/L', width: 80, title: '30/39/48/L', search: false},
                            {field: '广州_31/40/50/115/175/XL', width: 80, title: '31/40/50/XL', search: false},
                            {field: '广州_32/41/52/120/180/2XL', width: 80, title: '32/41/52/2XL', search: false},
                            {field: '广州_33/42/54/125/185/3XL', width: 80, title: '33/42/54/3XL', search: false},
                            {field: '广州_34/43/56/190/4XL', width: 80, title: '34/43/56/4XL', search: false},
                            {field: '广州_35/44/58/195/5XL', width: 80, title: '35/44/58/5XL', search: false},
                            {field: '广州_36/6XL', width: 60, title: '36/6XL', search: false},
                            {field: '广州_38/7XL', width: 60, title: '38/7XL', search: false},
                            {field: '广州_40/8XL', width: 60, title: '40/8XL', search: false},
                            {field: '南昌_字段', width: 110, title: '字段', search: false},
                            {field: '南昌_总计', width: 60, title: '总计', search: false},
                            {field: '南昌_00/28/37/44/100/160/S', width: 80, title: '28/37/44/S', search: false},
                            {field: '南昌_29/38/46/105/165/M', width: 80, title: '29/38/46/M', search: false},
                            {field: '南昌_30/39/48/110/170/L', width: 80, title: '30/39/48/L', search: false},
                            {field: '南昌_31/40/50/115/175/XL', width: 80, title: '31/40/50/XL', search: false},
                            {field: '南昌_32/41/52/120/180/2XL', width: 80, title: '32/41/52/2XL', search: false},
                            {field: '南昌_33/42/54/125/185/3XL', width: 80, title: '33/42/54/3XL', search: false},
                            {field: '南昌_34/43/56/190/4XL', width: 80, title: '34/43/56/4XL', search: false},
                            {field: '南昌_35/44/58/195/5XL', width: 80, title: '35/44/58/5XL', search: false},
                            {field: '南昌_36/6XL', width: 60, title: '36/6XL', search: false},
                            {field: '南昌_38/7XL', width: 60, title: '38/7XL', search: false},
                            {field: '南昌_40/8XL', width: 60, title: '40/8XL', search: false},
                            {field: '武汉_字段', width: 110, title: '字段', search: false},
                            {field: '武汉_总计', width: 60, title: '总计', search: false},
                            {field: '武汉_00/28/37/44/100/160/S', width: 80, title: '28/37/44/S', search: false},
                            {field: '武汉_29/38/46/105/165/M', width: 80, title: '29/38/46/M', search: false},
                            {field: '武汉_30/39/48/110/170/L', width: 80, title: '30/39/48/L', search: false},
                            {field: '武汉_31/40/50/115/175/XL', width: 80, title: '31/40/50/XL', search: false},
                            {field: '武汉_32/41/52/120/180/2XL', width: 80, title: '32/41/52/2XL', search: false},
                            {field: '武汉_33/42/54/125/185/3XL', width: 80, title: '33/42/54/3XL', search: false},
                            {field: '武汉_34/43/56/190/4XL', width: 80, title: '34/43/56/4XL', search: false},
                            {field: '武汉_35/44/58/195/5XL', width: 80, title: '35/44/58/5XL', search: false},
                            {field: '武汉_36/6XL', width: 60, title: '36/6XL', search: false},
                            {field: '武汉_38/7XL', width: 60, title: '38/7XL', search: false},
                            {field: '武汉_40/8XL', width: 60, title: '40/8XL', search: false},
                            {field: '长沙_字段', width: 110, title: '字段', search: false},
                            {field: '长沙_总计', width: 60, title: '总计', search: false},
                            {field: '长沙_00/28/37/44/100/160/S', width: 80, title: '28/37/44/S', search: false},
                            {field: '长沙_29/38/46/105/165/M', width: 80, title: '29/38/46/M', search: false},
                            {field: '长沙_30/39/48/110/170/L', width: 80, title: '30/39/48/L', search: false},
                            {field: '长沙_31/40/50/115/175/XL', width: 80, title: '31/40/50/XL', search: false},
                            {field: '长沙_32/41/52/120/180/2XL', width: 80, title: '32/41/52/2XL', search: false},
                            {field: '长沙_33/42/54/125/185/3XL', width: 80, title: '33/42/54/3XL', search: false},
                            {field: '长沙_34/43/56/190/4XL', width: 80, title: '34/43/56/4XL', search: false},
                            {field: '长沙_35/44/58/195/5XL', width: 80, title: '35/44/58/5XL', search: false},
                            {field: '长沙_36/6XL', width: 60, title: '36/6XL', search: false},
                            {field: '长沙_38/7XL', width: 60, title: '38/7XL', search: false},
                            {field: '长沙_40/8XL', width: 60, title: '40/8XL', search: false},
                            {field: '贵阳_字段', width: 110, title: '字段', search: false},
                            {field: '贵阳_总计', width: 60, title: '总计', search: false},
                            {field: '贵阳_00/28/37/44/100/160/S', width: 80, title: '28/37/44/S', search: false},
                            {field: '贵阳_29/38/46/105/165/M', width: 80, title: '29/38/46/M', search: false},
                            {field: '贵阳_30/39/48/110/170/L', width: 80, title: '30/39/48/L', search: false},
                            {field: '贵阳_31/40/50/115/175/XL', width: 80, title: '31/40/50/XL', search: false},
                            {field: '贵阳_32/41/52/120/180/2XL', width: 80, title: '32/41/52/2XL', search: false},
                            {field: '贵阳_33/42/54/125/185/3XL', width: 80, title: '33/42/54/3XL', search: false},
                            {field: '贵阳_34/43/56/190/4XL', width: 80, title: '34/43/56/4XL', search: false},
                            {field: '贵阳_35/44/58/195/5XL', width: 80, title: '35/44/58/5XL', search: false},
                            {field: '贵阳_36/6XL', width: 60, title: '36/6XL', search: false},
                            {field: '贵阳_38/7XL', width: 60, title: '38/7XL', search: false},
                            {field: '贵阳_40/8XL', width: 60, title: '40/8XL', search: false}
                        ]
                    ],done: function(res){
                        tableMerge.render(this);

                        $('.guangzhou').click(function() {
                            $('.layui-table-main').animate({'scrollLeft': 265}, 400);
                        });
                        $('.nanchang').click(function() {
                            $('.layui-table-main').animate({'scrollLeft':1262}, 400);
                        });

                        $('.wuhan').click(function() {
                            $('.layui-table-main').animate({'scrollLeft': 2265}, 400);
                        });
                        $('.changsha').click(function() {
                            $('.layui-table-main').animate({'scrollLeft': 3268}, 400);
                        });
                        $('.guiyang').click(function() {
                            $('.layui-table-main').animate({'scrollLeft': 5000}, 400);
                        });

                        if(res.showType==2){
                            $('.layui-table tr:nth-child(7n) td').css({
                                'border-bottom':'1px solid red',
                                'box-sizing':'border-box',
                            })
                        }else{
                            $('.layui-table tr:nth-child(13n) td').css({
                                'border-bottom':'1px solid red',
                                'box-sizing':'border-box',
                            })
                        }

                    }
                });
            })
            ea.listen();
        }
    };
    return Controller;
});