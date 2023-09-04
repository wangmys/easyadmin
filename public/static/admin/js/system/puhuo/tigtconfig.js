define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('system.puhuo.Tigtconfig/saveConfig'),
        del_url:ea.url('/system.puhuo.Tigtconfig/delConfig')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.ti_goods_type();
        },
        // 保存配置
        saveConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="id[]"]').val(res.data.id)
                ea.msg.success(res.msg);
             });
        },
        // 删除配置
        delConfig:function (_this,_url,_id) {
            ea.request.get({
                url:_url,
                data:{id:_id}
            },function (res) {
                if(res.code == 1){
                    _this.parents('tr').remove();
                    layer.closeAll()
                }
                ea.msg.success(res.msg);
            })
        },

        //剔除 货品等级配置
        ti_goods_type:function (){
            var ti_goods_type_head = $("#ti_goods_type_head").val();
            var ti_goods_type_head = JSON.parse(ti_goods_type_head);
            // console.log(ti_goods_type_head);
            var html = '';
            var that = this;
            ti_goods_type_head.forEach(function (i,value) {
                html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="" placeholder="请输入" class="layui-input"></td>'
           })
            $('.add_ti_goods_type').on('click', function(){
               var element = $([
                   '<tr>',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="id[]" class="layui-hide" value="">',
                           '<button type="button" class="layui-btn layui-btn-normal get" id="">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)
            
                element.find('.get').on('click', function(){
                    var _url = url.save_url;

                    var GoodsLevel = element.find('input[name="GoodsLevel[]"]').val();

                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        GoodsLevel:GoodsLevel,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig(element,_url,_data);
                })

                element.find('.del').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除该货品等级',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                       $(_this).parents('tr').remove();
                       layer.closeAll()
                   })
               })
               $('#form-ti_goods_type-config tbody').append(element);
           });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.puhuo_ti_goods_type-select'), function (key, element) {

                $(element).find('.get').on('click', function(){
                    
                    var _url = url.save_url;
                    
                    var GoodsLevel = $(element).find('input[name="GoodsLevel[]"]').val();

                    var id = $(element).find('input[name="id[]"]').val();

                    var _data = {
                        GoodsLevel:GoodsLevel,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

               $(element).find('.del').on('click', function(){
                   var _this = $(this);
                   var _id = $(element).find('input[name="id[]"]').val();
                   layer.confirm('是否删除该货品等级',{},function () {
                        that.delConfig(_this, url.del_url, _id)
                   })
               })

           });
            
            ea.listen();
       }


    };
    return Controller;
});