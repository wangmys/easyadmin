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
           var $manager = $("#manager").val();
           var $searchValue = $("#searchValue").val();
           var $month = $("#month").val();
           var $searchValue2 = JSON.parse($("#searchValue2").val());
           var $get = JSON.stringify({'month':$searchValue2});
           console.log($searchValue2)
            ea.table.render({
                init:{
                   table_elem: '#currentTable',
                   table_render_id: 'currentTableRenderId',
                   index_url: 'system.command.index/index',
                   export_url: 'system.command.index/index_export'
                },
                where:{filter:$get},
                height: 760,
                // toolbar:[
                //     'custom_export'
                // ],
                toolbar:[],
                limit:10000,
                limits:[10000],
                cols: [[
                    // {type: "checkbox"},
                    // {field: 'id', minWith: '10%', title: 'ID',search:false},
                    {field: '商品负责人', minWith: '10%', title: '商品负责人',search:'xmSelect',selectList:JSON.parse($manager)},
                    {field: '创建人', minWith: 134, title: '创建人',search:false},
                    {field: '店铺名称', minWith: 134, title: '店铺名称',search:false},
                    {field: '货号', minWith: 134, title: '货号',search:false},
                    {field: '单据类型', minWith: 134, title: '单据类型',search:false},
                    {field: '变动数量', minWith: 134, title: '变动数量',search:false},
                    {field: '库存数量', minWith: 134, title: '库存数量',search:false},
                    {field: '变动时间', minWith: 134, title: '单据时间',search:false},
                    {field: '清空操作', minWith: 134, title: '清空操作',search:false},
                    {field: 'month', minWith: 134, title: '月份',search:'xmSelect',selectList:JSON.parse($month),searchValue:$searchValue2,radio:true,clickClose:true},
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
           var cols = [{field: '商品负责人', minWith: '10%', title: '商品负责人',search:'xmSelect',selectList:JSON.parse($manager)}];
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