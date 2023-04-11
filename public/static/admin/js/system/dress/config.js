define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table;

    var Controller = {
        index: function () {
             var that = this;
             // 请求省,字段数据
             url = ea.url('/system.dress.config/index');
             $.get({
                url:url,
                data:{}
            },function (res) {
                 field_data = res.field;
                 $('.add').on('click', function(){
                    that.add_tr(field_data)
                 });

                 // 获取渲染后端渲染的数据,重新绑定事件
                 $.each($('.php-select'), function (key, element) {
                     console.log($(element))
                    that.bind($(element),field_data);
                });


                 ea.listen();
                 $('#app-form').removeClass("layui-hide");
             });
        },
        // 添加行
        add_tr:function (field_data) {
            var element = $([
                '<tr>',
                    '<td data-edit="text"><input type="text" name="input" lay-verify="required" placeholder="请输入标题" class="layui-input"></td>',
                    '<td class="field"></td>',
                    '<td class="handler">',
                        '<button type="button" class="layui-btn layui-btn-normal get">保存</button>',
                        '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                    '</td>',
                '</tr>',
            ].join(''))
            var field = field_data;
            var gender = element.find('.field')[0];
            var genderSelect = xmSelect.render({
                el: gender,
                data: function(){
                    return field
                }
            })

            element.find('.get').on('click', function(){
                 var _url = ea.url('/system.dress.config/saveConfig');
                 var _field = this.getValue('valueStr');
                 var _name = element.find('input[name=input]').val();
                 $.post({
                    url:_url,
                    data:{
                        field:_field,
                        name:_name
                    }
                },function (res) {
                    layer.confirm('请求成功')
                 });
            }.bind(genderSelect))

            element.find('.del').on('click', function(){
                var _this = $(this);
                layer.confirm('是否删除该组合',{},function () {
                    _this.parents('tr').remove();
                    layer.closeAll()
                })
            })

            $('#form-create tbody').append(element);
        },
        bind:function (element,field) {
          var gender = element.find('.field')[0];

          var data = JSON.parse(element.attr('lay-data'));
          var genderSelect = xmSelect.render({
              el: gender,
              data: function(){
                  return data
              }
          })

          element.find('.get').on('click', function(){
             var _url = ea.url('/system.dress.config/saveConfig');
             var _field = this.getValue('valueStr');
             var _name = element.find('input[name=input]').val();
             $.post({
                url:_url,
                data:{
                    field:_field,
                    name:_name
                }
            },function (res) {
                layer.confirm('请求成功')
             });
          }.bind(genderSelect))

          element.find('.del').on('click', function(){
            var _this = $(this);
            layer.confirm('是否删除该组合',{},function () {
                _this.parents('tr').remove();
                layer.closeAll()
            })
        })
        },
        index_copy:function (){
             // 请求省,字段数据
             url = ea.url('/system.dress.dress/config');
             $.get({
                url:url,
                data:{}
            },function (res) {
                $('.add').on('click', function(){
                    var element = $([
                        '<tr>',
                            '<td data-edit="text"><input type="text" name="input1" lay-verify="required" placeholder="请输入内容" class="layui-input"></td>',
                            '<td class="province"></td>',
                            '<td class="field"></td>',
                            '<td class="stock"><input type="text" name="input1" lay-verify="required" placeholder="请输入内容" class="layui-input"></td>',
                            '<td class="handler">',
                                '<button type="button" class="layui-btn layui-btn-normal get">保存</button>',
                                '<button type="button" class="layui-btn layui-btn-danger del">删除</button>',
                            '</td>',
                        '</tr>',
                    ].join(''))

                    // 字段组合
                    var field = element.find('.field')[0];
                    var field = xmSelect.render({
                        el: field,
                        data: function(){
                            return res.field
                        }
                    })

                    // 省
                    var province = element.find('.province')[0];
                    xmSelect.render({
                        el: province,
                        radio: true,
                        clickClose: true,
                        model: { label: { type: 'text' } },
                        data: function(){
                            return res.provinceList
                        },
                        on: function(data){

                        }.bind(field),
                    })

                    element.find('.get').on('click', function(){
                        console.log(this)
                        alert('valueStr: ' + this.getValue('valueStr'));
                    }.bind(field))

                    element.find('.del').on('click', function(){
                        $(this).parents('tr').remove();
                    })

                    $('#form-create tbody').append(element);
                });

                ea.listen();
             });
        }
    };
    return Controller;
});