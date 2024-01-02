define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.command.index/index',
        list_url: 'system.command.index/list',
        export_url: 'system.command.index/index_export'
    };
    var table = layui.table
    var laydate=layui.laydate
    var Controller = {
        index: function () {
           var $manager = JSON.parse($("#manager").val());
           var $dateY = JSON.parse($("#dateY").val());

            xmSelect.render({
                el: '#more',
                icon: 'show',
                // tips: '请选择省份',
                name: '商品负责人',
                toolbar: {
                    show: false,
                    list: ['ALL', 'CLEAR']
                },
                height: '320px',
                direction: 'auto',
                empty: '呀, 没有数据呢',
                filterable: true,
                theme: {
                    color: '#0081ff',
                },
                data: $manager
            });
            //年月范围
            laydate.render({
                elem: '#test8'
                ,type: 'month'
                // ,range: true
                ,value:$dateY
            });

            $('body').on('click', '#search', function (obj) {
                var data = {
                    '商品负责人': myform.商品负责人.value,
                    '时间': myform.时间.value,

                }
                console.log(data)
                tableList.reload({
                    where: data,
                });
                return false;
            })

         let tableList = ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.command.index/index',
                   export_url: 'system.command.index/index_export'
                },
                height: 760,
                // toolbar:[
                //     'custom_export'
                // ],
                search:false,
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [[
                    // {type: "checkbox"},
                    // {field: 'id', minWith: '10%', title: 'ID',search:false},
                    // {field: '商品负责人', minWith: '10%',hide:true, title: '商品负责人',},
                    {field: '商品负责人', minWith: 134, title: '创建人'},
                    {field: '创建人', minWith: 134, title: '原始创建人',search:false},
                    {field: '店铺名称', minWith: 134, title: '店铺名称',search:false},
                    {field: '货号', minWith: 134, title: '货号',search:false},
                    {field: '单据类型', minWith: 134, title: '单据类型',search:false},
                    {field: '变动数量', minWith: 134, title: '变动数量',search:false},
                    {field: '库存数量', minWith: 134, title: '库存数量',search:false},
                    {field: '变动时间', minWith: 134, title: '单据时间',search:false},
                    {field: '清空操作', minWith: 134, title: '清空操作',search:false},
                    // {field: 'month', minWith: 134, title: '月份',search:'xmSelect',selectList:JSON.parse($month),searchValue:$searchValue2,radio:true,clickClose:true},
                    {field: 'month', minWith: 134, title: '日期',search: 'range'},
                    // {field: '清空货号', title: '清空货号', minWith: 134,search:false},
                ]],
                done:function (res, curr, count) {
                    var that = this.elem.next();
                    res.data.forEach(function (item,index) {
                        if(item.type === 1) that.find("[data-index=" + index + "]").addClass('isRed');
                    })
                }
            });
            ea.listen();
        },
        total: function () {
           var $get = $("#where").val();
           var $manager = $("#manager").val();
           var field = JSON.parse($("#field").val());
           var cols = [{field: '商品负责人', minWith: '10%', title: '创建人',search:'xmSelect',selectList:JSON.parse($manager)}];
           // 完善动态字段列表
           for (index in field) {
                var val = field[index];
                cols.push({
                   field: ''+val+'',
                   title:''+val+'',
                   minWith: 134,
                   search:false
               });
            }
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.command.index/total',
                   export_url: 'system.command.index/total_export'
                },
                where:{filter:$get},
                height: 760,
                // toolbar:[
                //     'custom_export'
                // ],
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [cols]
            });
            ea.listen();
        },
        templet:function (_data,_this) {
            var key = "_"+_this.field;
            if(_data[_this.field] === null){
                _data[_this.field] = 0;
            }
            if(_data[key] === true){
                //得到当前行数据，并拼接成自定义模板
                return '<div style="width: 100%;height:100%;display: inline-block;background: rgba(255,0,0,.2)">'+ _data[_this.field] +'</div>'
            }
            return _data[_this.field];
        }
    };
    return Controller;
});