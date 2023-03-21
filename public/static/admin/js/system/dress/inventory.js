define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.inventory/index',
        question_index: '/admin/system.dress.inventory/question',
        list_index: '/admin/system.dress.inventory/index',
        finish_rate: '/admin/system.dress.inventory/finish_rate',
    };

    function isRed(d,obj){
        if(d[obj.field] === null){
            d[obj.field] = 0;
        }
        if(parseInt(d[obj.field]) < parseInt(d.config[obj.field])){
            str = '';
            var key = obj.field + '_';
            if(d._data[key]){
                // str = ' / ' + d._data[obj.field + '_'];
            }
            //得到当前行数据，并拼接成自定义模板
            return '<span style="width: 100%;display: block;background: rgba(255,0,0,.2)">'+ d[obj.field] + str +'</span>'
        }
        return '';
    }

    var Controller = {

        index: function () {
            var $get = $("#where").val();
            // 比较表达式
            defaultOp = 'lt';
            ea.table.render({
                url: init.list_index,
                search:false,
                where:{filter:$get},
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    {type: "checkbox"},
                    {field: '省份', minWith: 134, title: '省份',search: true},
                    {field: '店铺名称', minWith: 134, title: '店铺名称',search: true},
                    {field: '商品负责人', minWith: 134, title: '商品负责人',search: true},
                    {field: '背包', minWith: 134, title: '背包',templet: function(d){
                        return isRed(d,this);
                      },searchValue:this.minWith,search:defaultOp,search: false},
                    {field: '挎包', minWith: 134, title: '挎包',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '领带', minWith: 134, title: '领带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '帽子', minWith: 134, title: '帽子',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '内裤', minWith: 134, title: '内裤',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '皮带', minWith: 134, title: '皮带',templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '袜子', title: '袜子', minWith: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '手包', title: '手包', minWith: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                    {field: '胸包', title: '胸包', minWith: 134,templet: function(d){
                        return isRed(d,this);
                      },search:defaultOp,search: false},
                ]]
            });

            ea.listen();
        },
        question: function () {
            var $get = $("#where").val();
            ea.table.render({
                url: init.question_index,
                search:false,
                where:{filter:$get},
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    {type: "checkbox"},
                    {field: '商品负责人', minWith: 134, title: '商品负责人'},
                    {field: '背包', minWith: 134, title: '背包'},
                    {field: '挎包', minWith: 134, title: '挎包'},
                    {field: '领带', minWith: 134, title: '领带'},
                    {field: '帽子', minWith: 134, title: '帽子'},
                    {field: '内裤', minWith: 134, title: '内裤'},
                    {field: '皮带', minWith: 134, title: '皮带'},
                    {field: '袜子', title: '袜子', minWith: 134},
                    {field: '手包', title: '手包', minWith: 134},
                    {field: '胸包', title: '胸包', minWith: 134},
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
                                field:'商品负责人',
                                extend:"data-width = '1500px' data-height = '900px' data-title = '配置库存详情' "
                            }],
                        ],
                        fixed: 'right'
                    }
                ]]
            });

            ea.listen();
        },
        finish_rate: function () {
            var $get = $("#where").val();
            ea.table.render({
                url: init.finish_rate,
                where:JSON.parse($get),
                search:false,
                height: 760,
                limit: 1000,
                toolbar:[],
                limits:[100,200,500,1000],
                cols: [[
                    {type: "checkbox"},
                    {field: '商品负责人', minWith: 134, title: '商品负责人'},
                    {field: '背包', minWith: 134, title: '背包'},
                    {field: '挎包', minWith: 134, title: '挎包'},
                    {field: '领带', minWith: 134, title: '领带'},
                    {field: '帽子', minWith: 134, title: '帽子'},
                    {field: '内裤', minWith: 134, title: '内裤'},
                    {field: '皮带', minWith: 134, title: '皮带'},
                    {field: '袜子', title: '袜子', minWith: 134},
                    {field: '手包', title: '手包', minWith: 134},
                    {field: '胸包', title: '胸包', minWith: 134},
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
                                field:'商品负责人',
                                extend:"data-width = '1500px' data-height = '900px' data-title = '配置库存详情' "
                            }],
                        ],
                        fixed: 'right'
                    }
                ]]
            });

            ea.listen();
        }
    };
    return Controller;
});