define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('system.skc.config/saveConfig'),
        save_skc_config_url:ea.url('system.skc.config/saveSkcConfig'),
        del_url:ea.url('/system.skc.config/delConfig'),
        index_url:ea.url('/system.skc.config/index')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.win_num();
             this.skc_config();
        },
        // 保存配置
        saveConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="sign_id[]"]').val(res.data.sign_id)
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

        //窗数陈列标准
        win_num:function (){
            var win_num_head = $("#win_num_head").val();
            var win_num_head = JSON.parse(win_num_head);
            var html = '';
            var that = this;
            win_num_head.forEach(function (i,value) {
                html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="" placeholder="请输入" class="layui-input"></td>'
           })
            $('.add_win_num').on('click', function(){
               var element = $([
                   '<tr>',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="sign_id[]" class="layui-hide" value="">',
                           '<button type="button" class="layui-btn layui-btn-normal get" sign_id="">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)
            
                element.find('.get').on('click', function(){
                    var _url = url.save_url;
                    var area_range = element.find('input[name="area_range[]"]').val();
                    var win_num = element.find('input[name="win_num[]"]').val();
                    var skc_fl = element.find('input[name="skc_fl[]"]').val();
                    var skc_yl = element.find('input[name="skc_yl[]"]').val();
                    var skc_xxdc = element.find('input[name="skc_xxdc[]"]').val();
                    var skc_num = element.find('input[name="skc_num[]"]').val();
                    var sign_id = element.find('input[name="sign_id[]"]').val();
                    var _data = {
                        area_range:area_range,
                        win_num:win_num,
                        skc_fl:skc_fl,
                        skc_yl:skc_yl,
                        skc_xxdc:skc_xxdc,
                        skc_num:skc_num,
                        sign_id:sign_id
                    }
                    // 保存配置
                    that.saveConfig(element,_url,_data);
                })

                element.find('.del').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除该组合',{},function () {
                        var sign_id = element.find('input[name="sign_id[]"]').val();
                        if (sign_id != '') {
                            that.delConfig(_this, url.del_url, sign_id)
                        }
                       $(_this).parents('tr').remove();
                       layer.closeAll()
                   })
               })
               $('#form-win_num-config tbody').append(element);
           });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.skc_win_num-select'), function (key, element) {

                $(element).find('.get').on('click', function(){
                    var _url = url.save_url;
                    var area_range = $(element).find('input[name="area_range[]"]').val();
                    var win_num = $(element).find('input[name="win_num[]"]').val();
                    var skc_fl = $(element).find('input[name="skc_fl[]"]').val();
                    var skc_yl = $(element).find('input[name="skc_yl[]"]').val();
                    var skc_xxdc = $(element).find('input[name="skc_xxdc[]"]').val();
                    var skc_num = $(element).find('input[name="skc_num[]"]').val();
                    var sign_id = $(element).find('input[name="sign_id[]"]').val();
                    // console.log(area_range, win_num, skc_fl, skc_yl, skc_xxdc, skc_num, sign_id);
                    var _data = {
                        area_range:area_range,
                        win_num:win_num,
                        skc_fl:skc_fl,
                        skc_yl:skc_yl,
                        skc_xxdc:skc_xxdc,
                        skc_num:skc_num,
                        sign_id:sign_id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

               $(element).find('.del').on('click', function(){
                   var _this = $(this);
                   var _id = $(element).find('input[name="sign_id[]"]').val();
                   layer.confirm('是否删除该组合',{},function () {
                        that.delConfig(_this, url.del_url, _id)
                   })
               })

           });
            
            ea.listen();
       },

        //SKC价格配置
        skc_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.skc_config-select'), function (key, element) {

                var gender = $(element).find('.skc_sz_nostore')[0];
                var data = JSON.parse($(element).attr('lay-data'));
                // console.log(data);
                var data = data.skc_sz_nostore;
                var genderSelect = xmSelect.render({
                    el: gender,
                    filterable: true,
                    data: function(){
                        return data
                    }
                })


                $(element).find('.get_skc_config').on('click', function(){
                    var _url = url.save_skc_config_url;
                    var dt_price = $(element).find('input[name="dt_price"]').val();
                    var dc_price = $(element).find('input[name="dc_price"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
                    var skc_sz_nostore = $(element).find('.skc_sz_nostore .label-content').attr('title');

                    var _data = {
                        dt_price:dt_price,
                        dc_price:dc_price,
                        sign_id:sign_id,
                        skc_sz_nostore:skc_sz_nostore
                    }
                    // console.log(_data);
                    // 保存配置
                    that.saveSkcConfig($(element), _url, _data);
                }.bind(genderSelect))

            });
                
            ea.listen();
        }


    };
    return Controller;
});