define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('system.puhuo.Scconfig/saveConfig'),
        del_url:ea.url('/system.puhuo.Scconfig/delConfig')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.customer_level();
             this.fill_rate();
             this.dongxiao_rate();
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
        // 保存skc价格配置
        saveSkcConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="sign_id"]').val(res.data.sign_id)
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

        //店铺评分标准
        customer_level:function (){
            var customer_level_head = $("#customer_level_head").val();
            var customer_level_head = JSON.parse(customer_level_head);
            // console.log(customer_level_head);
            var html = '';
            var that = this;
            customer_level_head.forEach(function (i,value) {
                html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="" placeholder="请输入" class="layui-input"></td>'
           })
            $('.add_customer_level').on('click', function(){
               var element = $([
                   '<tr>',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="id[]" class="layui-hide" value="">',
                           '<input type="text" name="config_str[]" class="layui-hide" value="customer_level">',
                           '<input type="text" name="key_level[]" class="layui-hide" value="">',
                           '<button type="button" class="layui-btn layui-btn-normal get" id="">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)
            
                element.find('.get').on('click', function(){
                    var _url = url.save_url;

                    var key = element.find('input[name="key[]"]').val();
                    var score = element.find('input[name="score[]"]').val();
                    var config_str = element.find('input[name="config_str[]"]').val();
                    var key_level = element.find('input[name="key_level[]"]').val();

                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        key:key,
                        score:score,
                        config_str:config_str,
                        key_level:key_level,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig(element,_url,_data);
                })

                element.find('.del').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除该店铺等级',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                       $(_this).parents('tr').remove();
                       layer.closeAll()
                   })
               })
               $('#form-customer_level-config tbody').append(element);
           });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.puhuo_customer_level-select'), function (key, element) {

                $(element).find('.get').on('click', function(){
                    
                    var _url = url.save_url;
                    
                    var key = $(element).find('input[name="key[]"]').val();
                    var score = $(element).find('input[name="score[]"]').val();
                    var config_str = $(element).find('input[name="config_str[]"]').val();
                    var key_level = $(element).find('input[name="key_level[]"]').val();

                    var id = $(element).find('input[name="id[]"]').val();

                    var _data = {
                        key:key,
                        score:score,
                        config_str:config_str,
                        key_level:key_level,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

               $(element).find('.del').on('click', function(){
                   var _this = $(this);
                   var _id = $(element).find('input[name="id[]"]').val();
                   layer.confirm('是否删除该店铺等级',{},function () {
                        that.delConfig(_this, url.del_url, _id)
                   })
               })

           });
            
            ea.listen();
       }


    };
    return Controller;
});