define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('/system.dress.sysconfig/saveConfig'),
        del_url:ea.url('/system.dress.sysconfig/delConfig'),
        index_url:ea.url('/system.dress.sysconfig/index')
    }

    var Controller = {
        index: function () {
            // 调用表头页js
             this.fetch_index();
             // 调用库存预警页js
             // this.waring_stock();
             ea.listen();
        },
        // 添加行
        add_tr:function (field_data) {
            var that = this;
            var element = $([
                '<tr>',
                    '<td><input type="text" name="name" lay-verify="required" placeholder="请输入标题" class="layui-input"></td>',
                    '<td class="field"></td>',
                    '<td class="handler">',
                        '<button type="button" class="layui-btn layui-btn-normal get">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
            var field = field_data;
            // 获取新数据
            that.getNewData(field);
            console.log(field)
            var gender = element.find('.field')[0];
            var genderSelect = xmSelect.render({
                el: gender,
                data: function(){
                    return field
                }
            })

            element.find('.get').on('click', function(){
                 var _url = url.save_url;
                 var _field = this.getValue('valueStr');
                 var _name = element.find('input[name=name]').val();
                 var _id = element.attr('lay-id');
                 var _data = {
                     field:_field,
                     name:_name,
                     id:_id
                 }
                 // 保存配置
                 that.saveConfig(element,_url,_data);

            }.bind(genderSelect))

            element.find('.del').on('click', function(){
                var _this = $(this);
                var _id = element.attr('lay-id');
                if(_id){
                var _url = url.del_url;
                    layer.confirm('是否删除该组合',{},function () {
                        // 删除
                        that.delConfig(_this,_url,_id)
                    })
                }else{
                    _this.parents('tr').remove();
                    layer.closeAll()
                }
            })

            $('#form-create tbody').append(element);
        },
        bind:function (element,field) {
          var gender = element.find('.field')[0];
          var that = this;
          var data = JSON.parse(element.attr('lay-data'));
          var _id = element.attr('lay-id');
          var genderSelect = xmSelect.render({
              el: gender,
              data: function(){
                  return data
              }
          })

          element.find('.get').on('click', function(){
             var _url = url.save_url;
             var _field = this.getValue('valueStr');
             var _name = element.find('input[name=name]').val();
             var _data = {
                    field:_field,
                    name:_name,
                    id:_id
                }
             // 保存配置
             that.saveConfig(element,_url,_data);
          }.bind(genderSelect))

          element.find('.del').on('click', function(){
            var _this = $(this);
            if(_id){
                var _url = url.del_url;
                layer.confirm('是否删除该组合',{},function () {
                    // 删除
                    that.delConfig(_this,_url,_id)
                })
            }else{
                _this.parents('tr').remove();
                layer.closeAll()
            }
        })
        },
        // 保存配置
        saveConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                element.attr('lay-id',res.data.id);
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
        // 预警库存
        waring_stock:function (){
             var head_field = $("#head_field").val();
             var head = JSON.parse(head_field);
             var province_str = $("#province_list").val();
             var province_list= JSON.parse(province_str);
             var html = '<td class="province"></td>';
             var that = this;
             head.forEach(function (i,value) {
                 if(i !== '省份'){
                    html += '<td class="field_'+ value +'_'+ i +'"><input type="text" name="' + i + '[]" lay-verify="required" value="0" placeholder="请输入数值" class="layui-input"></td>'
                 }else{
                    html += '<td class="province"></td>';
                 }
            })
             $('.addstock').on('click', function(){
                var element = $([
                    '<tr>',
                        ,html,
                        '<td class="handler">',
                            '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                        '</td>',
                    '</tr>',
                ].join(''))
                 console.log(html)
                // 获取新数据
                that.getNewData(province_list);
                var _province_list = province_list;
                // 字段组合
                var _province = element.find('.province')[0];
                var province = xmSelect.render({
                    el: _province,
                    data: function(){
                        return _province_list
                    },
                    name:'省份[]'
                })
                element.find('.del').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除该组合',{},function () {
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })
                $('#form-create-config tbody').append(element);
            });

             // 获取渲染后端渲染的数据,重新绑定事件
             $.each($('.province-select'), function (key, element) {
                var ele = $(element).find('.province')[0];
                var _province_data = JSON.parse($(element).attr('lay-data'));
                var province = xmSelect.render({
                    el: ele,
                    data: function(){
                        return _province_data
                    },
                    name:'省份[]'
                })

                $(element).find('.del').on('click', function(){
                    var _this = $(this);
                    layer.confirm('是否删除该组合',{},function () {
                        $(_this).parents('tr').remove();
                        layer.closeAll()
                    })
                })
            });

            ea.listen();
        },
        fetch_index:function (){
             var that = this;
             // 请求省,字段数据
             $.get({
                url:url.index_url,
                data:{}
            },function (res) {
                 field_data = res.field;
                 $('.add').on('click', function(){
                    that.add_tr(field_data);
                 });

                 // 获取渲染后端渲染的数据,重新绑定事件
                 $.each($('.php-select'), function (key, element) {
                    that.bind($(element),field_data);
                });
                 $('#app-form').removeClass("layui-hide");
             });
        },
        // 获取新城市数据
        getNewData:function (data) {
            if(!data) return data;
            var new_data = [];
            data.forEach(function (value,i) {
               value.selected = false;
               new_data[i] = value;
            })
            console.log(new_data)
            return new_data;
        }
    };
    return Controller;
});