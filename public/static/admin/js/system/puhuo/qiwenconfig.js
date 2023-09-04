define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('system.puhuo.Qiwenconfig/saveColdtohot'),
        save_url_hottocold:ea.url('system.puhuo.Qiwenconfig/saveHottocold'),
        del_url:ea.url('system.puhuo.Qiwenconfig/delColdtohot'),
        del_url_hottocold:ea.url('/system.puhuo.Qiwenconfig/delHottocold')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.coldtohot();
             this.hottocold();
        },
        // 保存配置
        saveConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                console.log(res);
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

        //店铺评分标准(冷到热)
        coldtohot:function (){
            var qiwen_head = $("#qiwen_head").val();
            var qiwen_head = JSON.parse(qiwen_head);
            // console.log(qiwen_head);
            var html = '';
            var that = this;
            qiwen_head.forEach(function (i,value) {
                html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="" placeholder="请输入" class="layui-input"></td>'
           })
            $('.add_coldtohot').on('click', function(){
               var element = $([
                   '<tr>',
                       ,html,
                       '<td class="handler">',
                           '<input type="text" name="id[]" class="layui-hide" value="">',
                           '<input type="text" name="qiwen_sort[]" class="layui-hide" value="">',
                           '<input type="text" name="remark[]" class="layui-hide" value="冷--热">',
                           '<button type="button" class="layui-btn layui-btn-normal get_coldtohot" id="">保存</button>',
                           '<button type="button" class="layui-btn layui-btn-danger del_coldtohot">删除</button>',
                       '</td>',
                   '</tr>',
               ].join(''))
                // console.log(html)
            
                element.find('.get_coldtohot').on('click', function(){
                    var _url = url.save_url;

                    var yuncang = element.find('input[name="yuncang[]"]').val();
                    var province = element.find('input[name="province[]"]').val();
                    var wenqu = element.find('input[name="wenqu[]"]').val();
                    var qiwen_score = element.find('input[name="qiwen_score[]"]').val();
                    var qiwen_sort = element.find('input[name="qiwen_sort[]"]').val();
                    var remark = element.find('input[name="remark[]"]').val();

                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        yuncang:yuncang,
                        province:province,
                        wenqu:wenqu,
                        qiwen_score:qiwen_score,
                        qiwen_sort:qiwen_sort,
                        remark:remark,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig(element,_url,_data);
                })

                element.find('.del_coldtohot').on('click', function(){
                   var _this = $(this);
                   layer.confirm('是否删除该气温评分',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url, id)
                        }
                       $(_this).parents('tr').remove();
                       layer.closeAll()
                   })
               })
               $('#form-coldtohot-config tbody').append(element);
           });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.puhuo_coldtohot-select'), function (key, element) {

                $(element).find('.get_coldtohot').on('click', function(){
                    
                    var _url = url.save_url;
                    
                    var yuncang = $(element).find('input[name="yuncang[]"]').val();
                    var province = $(element).find('input[name="province[]"]').val();
                    var wenqu = $(element).find('input[name="wenqu[]"]').val();
                    var qiwen_score = $(element).find('input[name="qiwen_score[]"]').val();
                    var qiwen_sort = $(element).find('input[name="qiwen_sort[]"]').val();
                    var remark = $(element).find('input[name="remark[]"]').val();

                    var id = $(element).find('input[name="id[]"]').val();

                    var _data = {
                        yuncang:yuncang,
                        province:province,
                        wenqu:wenqu,
                        qiwen_score:qiwen_score,
                        qiwen_sort:qiwen_sort,
                        remark:remark,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

               $(element).find('.del_coldtohot').on('click', function(){
                   var _this = $(this);
                   var _id = $(element).find('input[name="id[]"]').val();
                   layer.confirm('是否删除该气温评分',{},function () {
                        that.delConfig(_this, url.del_url, _id)
                   })
               })

           });
            
            ea.listen();
       },



       //店铺评分标准(热到冷)
       hottocold:function (){
            var qiwen_head_hottocold = $("#qiwen_head_hottocold").val();
            var qiwen_head_hottocold = JSON.parse(qiwen_head_hottocold);
            // console.log(qiwen_head_hottocold);
            var html = '';
            var that = this;
            qiwen_head_hottocold.forEach(function (i,value) {
                html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="" placeholder="请输入" class="layui-input"></td>'
            })
            $('.add_hottocold').on('click', function(){
            var element = $([
                '<tr>',
                    ,html,
                    '<td class="handler">',
                        '<input type="text" name="id[]" class="layui-hide" value="">',
                        '<input type="text" name="qiwen_sort[]" class="layui-hide" value="">',
                        '<input type="text" name="remark[]" class="layui-hide" value="热--冷">',
                        '<button type="button" class="layui-btn layui-btn-normal get_hottocold" id="">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del_hottocold">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
                // console.log(html)
            
                element.find('.get_hottocold').on('click', function(){
                    var _url = url.save_url_hottocold;

                    var yuncang = element.find('input[name="yuncang[]"]').val();
                    var province = element.find('input[name="province[]"]').val();
                    var wenqu = element.find('input[name="wenqu[]"]').val();
                    var qiwen_score = element.find('input[name="qiwen_score[]"]').val();
                    var qiwen_sort = element.find('input[name="qiwen_sort[]"]').val();
                    var remark = element.find('input[name="remark[]"]').val();

                    var id = element.find('input[name="id[]"]').val();
                    var _data = {
                        yuncang:yuncang,
                        province:province,
                        wenqu:wenqu,
                        qiwen_score:qiwen_score,
                        qiwen_sort:qiwen_sort,
                        remark:remark,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig(element,_url,_data);
                })

                element.find('.del_hottocold').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除该气温评分',{},function () {
                        var id = element.find('input[name="id[]"]').val();
                        if (id != '') {
                            that.delConfig(_this, url.del_url_hottocold, id)
                        }
                    $(_this).parents('tr').remove();
                    layer.closeAll()
                })
            })
            $('#form-hottocold-config tbody').append(element);
            });

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.puhuo_hottocold-select'), function (key, element) {

                $(element).find('.get_hottocold').on('click', function(){
                    
                    var _url = url.save_url_hottocold;
                    
                    var yuncang = $(element).find('input[name="yuncang[]"]').val();
                    var province = $(element).find('input[name="province[]"]').val();
                    var wenqu = $(element).find('input[name="wenqu[]"]').val();
                    var qiwen_score = $(element).find('input[name="qiwen_score[]"]').val();
                    var qiwen_sort = $(element).find('input[name="qiwen_sort[]"]').val();
                    var remark = $(element).find('input[name="remark[]"]').val();

                    var id = $(element).find('input[name="id[]"]').val();

                    var _data = {
                        yuncang:yuncang,
                        province:province,
                        wenqu:wenqu,
                        qiwen_score:qiwen_score,
                        qiwen_sort:qiwen_sort,
                        remark:remark,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

            $(element).find('.del_hottocold').on('click', function(){
                var _this = $(this);
                var _id = $(element).find('input[name="id[]"]').val();
                layer.confirm('是否删除该气温评分',{},function () {
                        that.delConfig(_this, url.del_url_hottocold, _id)
                })
            })

            });
                
                ea.listen();
        }

        


    };
    return Controller;
});