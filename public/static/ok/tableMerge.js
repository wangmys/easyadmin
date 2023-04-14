layui.define(["table"],
    function(e) {
        var o = layui.jquery;
        e("tableMerge", {
            render: function(n) {
                var e = o(n.elem).next().children(".layui-table-box"),
                    t = o(e.children(".layui-table-body").children("table").children("tbody").children("tr").toArray().reverse()),
                    l = o(e.children(".layui-table-fixed-l").children(".layui-table-body").children("table").children("tbody").children("tr").toArray().reverse()),
                    h = o(e.children(".layui-table-fixed-r").children(".layui-table-body").children("table").children("tbody").children("tr").toArray().reverse()),
                    c = {};
                function d(e, a) {
                    for (var r = c[a].mergeField, i = layui.table.cache[n.id].length, t = 0; t < r.length; t++) if (layui.table.cache[n.id][i - 2 - e][r[t]] !== layui.table.cache[n.id][i - 1 - e][r[t]]) return ! 0;
                    return ! 1
                }
                layui.each(n.cols,
                    function(i, e) {
                        layui.each(e,
                            function(e, a) {
                                if (a.merge && a.field) {
                                    var r = [a.field]; ! 0 !== a.merge && (r = "string" == typeof a.merge ? [a.merge] : a.merge),
                                        c[n.index + "-" + i + "-" + e] = {
                                            mergeField: r,
                                            rowspan: 1
                                        }
                                }
                            })
                    }),
                    t.each(function(e) {
                        for (var a in c) if (e === t.length - 1 || d(e, a)) {
                            var r = o(this).children('[data-key="' + a + '"]').outerHeight(),
                                i = 0;
                            0 === t.eq(e).data("index") && (i = 1),
                                o(this).children('[data-key="' + a + '"]').attr("rowspan", c[a].rowspan).css({
                                    position: "static",
                                    height: r * c[a].rowspan + i
                                }).children().css({
                                    height: "auto",
                                    "white-space": "normal",
                                    "max-height": r * c[a].rowspan + i - 10
                                }),
                                l.eq(e).children('[data-key="' + a + '"]').attr("rowspan", c[a].rowspan).css({
                                    position: "static",
                                    height: r * c[a].rowspan + i
                                }).children().css({
                                    height: "auto",
                                    "white-space": "normal",
                                    "max-height": r * c[a].rowspan + i - 10
                                }),
                                h.eq(e).children('[data-key="' + a + '"]').attr("rowspan", c[a].rowspan).css({
                                    position: "static",
                                    height: r * c[a].rowspan + i
                                }).children().css({
                                    height: "auto",
                                    "white-space": "normal",
                                    "max-height": r * c[a].rowspan + i - 10
                                }),
                                c[a].rowspan = 1
                        } else o(this).children('[data-key="' + a + '"]').remove(),
                            l.eq(e).children('[data-key="' + a + '"]').remove(),
                            h.eq(e).children('[data-key="' + a + '"]').remove(),
                            c[a].rowspan += 1
                    })
            }
        })
    });