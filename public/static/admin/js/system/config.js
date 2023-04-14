define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table;

    var Controller = {
        index: function () {

            var app = new Vue({
                el: '#app',
                data: {
                    upload_type: upload_type
                }
            });
            form.on("radio(upload_type)", function (data) {
                    app.upload_type = this.value;
                });


             // 请求省,字段数据
             url = ea.url('/system.dress.config/index');
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
                            // var changeItem = data.change[0];
                            // if(data.isAdd && changeItem.value == 3){
                            //     this.update({ disabled: true })
                            // }else{
                            //     this.update({ disabled: false })
                            // }
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
             });

            ea.listen();
        }
    };
    return Controller;
});