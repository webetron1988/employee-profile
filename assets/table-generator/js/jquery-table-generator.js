/**
 * Table Tag Generator
 *
 * Copyright (c) 2014 Shunsuke Kusakabe
 * Licensed under the MIT.
 *
 */
jQuery(function (a) {
    function b(a) {
        return new RegExp(a, "i").test(navigator.userAgent);
    }
    function c(b, c) {
        n.find("table").empty();
        var d, e, f;
        for (d = 0; b > d; d++) {
            for (f = a("<tr />"), e = 0; c > e; e++) a("<td />").appendTo(f);
            f.appendTo(n.find("table"));
        }
        $('.perfect-scrollbar').perfectScrollbar('update')
    }
    function d(b, d) {
        var f;
        return 0 > d ? ((f = a(this).val()), 
        a(this).val(++f), c(q.val(), r.val()), 
        g(e())) : d > 0 && ((f = a(this).val()), 
        --f, 
        f > 0 && (a(this).val(f), 
        c(q.val(), 
        r.val()), 
        g(e()))), 
        i(), 
        !1;
    }
    function e() {
        if (window.localStorage) {
            var a = n.html(),
                b = localStorage.getItem("index"),
                c = localStorage.getItem("undo"),
                d = { row: q.val(), col: r.val() };
            return (
                b == c
                    ? (b++, localStorage.setItem("data" + b, a), localStorage.setItem("o" + b, JSON.stringify(d)), localStorage.setItem("index", b), localStorage.setItem("undo", b))
                    : b > c && (c++, localStorage.setItem("data" + c, a), localStorage.setItem("o" + c, JSON.stringify(d)), localStorage.setItem("index", c), localStorage.setItem("undo", c)),
                f(),
                a
            );
        }
    }
    function f() {
        if (window.localStorage) {
            var b = Number(localStorage.getItem("index")),
                c = Number(localStorage.getItem("undo"));
            b == c
                ? 0 === c
                    ? (a("#undo").attr("disabled", "disabled"), a("#redo").attr("disabled", "disabled"))
                    : (a("#undo").removeAttr("disabled"), a("#redo").attr("disabled", "disabled"))
                : 0 === c
                ? (a("#undo").attr("disabled", "disabled"), a("#redo").removeAttr("disabled"))
                : (a("#undo").removeAttr("disabled"), a("#redo").removeAttr("disabled"));
        }
    }
    function g(c) {
        var d;
        (d = c ? c : n.html()),
            (d = d
                .replace(/<\/table>/g, "\n</table>")
                .replace(/&lt;/g, "<")
                .replace(/&gt;/g, ">")
                .replace(/ui-selectable/, "")
                .replace(/ui-selecte(d\s|d)/g, "")
                .replace(/ui-selecte(e\s|e)/g, "")
                .replace(/TTG-merged/g, "")
                .replace(/ class=\"\"/g, "")
                .replace(/ class=\" \"/g, "")
                .replace(/ style=\".*?\"/g, "")
                .replace(/<tb.*?>/g, "\n	<tbody>")
                .replace(/<\/tbo.*?>/g, "\n	</tbody>")
                .replace(/<tr/g, "\n		<tr")
                .replace(/<\/tr/g, "\n		</tr")
                .replace(/<td/g, "\n			<td")
                .replace(/<th/g, "\n			<th")),
            b("msie|trident") && (d = d.replace(/ class=>/g, ">").replace(/^\n/g, "")),
            a("[name=source]")
                .attr("rows", d.match(/\n/g).length + 2)
                .val("")
                .val(d);
    }
    function h() {
        a(".ui-selected." + u).each(function () {
            var b = Number(a(this).attr("rowspan")),
                c = Number(a(this).attr("colspan"));
            isNaN(c) && (c = 1), a(this).removeAttr("rowspan"), a(this).removeAttr("colspan");
            var d;
            for (e = 1; c > e; e++) a(this).get(0).tagName.match(/td/i) ? (d = a("<td>")) : a(this).get(0).tagName.match(/th/i) && (d = a("<th>")), d.addClass("ui-selected"), d.insertAfter(a(this));
            console.log();
            var e,
                f,
                g,
                h = a(this).parent("tr"),
                i = a("tr").index(h),
                j = i + 1,
                k = (Number(r.val()), a(this).parent().children().index(a(this)) - 1);
            for (e = j; j + b - 1 > e; e++)
                for (g = a("tr:eq(" + e + ")").children(), f = 0; c > f; f++)
                    a(this).get(0).tagName.match(/td/i) ? (d = a("<td>")) : a(this).get(0).tagName.match(/th/i) && (d = a("<th>")), d.addClass("ui-selected1"), -1 == k ? d.insertBefore(g[0]) : d.insertAfter(g[k] ? g[k] : g[g.length - 1]);
            a(this).removeClass(u);
        });
    }
    function i() {
        n.find("input").remove(), a("#stripe-label").remove();
    }
    function j(b) {
        (b = b || ".default"),
            clearTimeout(l),
            a("#generator .message p").hide(),
            a(b).show(),
            a(b).hasClass("alert") &&
                (l = setTimeout(function () {
                    a(b).fadeOut("normal", function () {
                        a(".default").fadeIn();
                    });
                }, 5e3));
    }
    function k(a, b) {
        var c = b.keyCode ? b.keyCode : b.charCode ? b.charCode : b.which;
        if (9 == c && !b.shiftKey && !b.ctrlKey && !b.altKey) {
            var d = a.scrollTop;
            if (a.setSelectionRange) {
                var e = a.selectionStart,
                    f = a.selectionEnd;
                (a.value = a.value.substring(0, e) + "	" + a.value.substr(f)), a.setSelectionRange(e + 1, e + 1), a.focus();
            } else a.createTextRange ? ((document.selection.createRange().text = "	"), (b.returnValue = !1)) : alert("Please contact the admin and tell xe that the tab functionality does not work in your browser.");
            return (a.scrollTop = d), b.preventDefault && b.preventDefault(), !1;
        }
        return !0;
    }
    var l,
        m = "#tag-wrapper",
        n = a(m),
        o = n.find("table"),
        p = a("#ttg-header"),
        q = p.find("[name=row]"),
        r = p.find("[name=col]"),
        s = p.find("[name=row], [name=col]"),
        t = p.find(".button"),
        u = "TTG-merged";
    if (
        (c(q.val(), r.val()),
        g(),
        a("#class, #chars").show(),
        a("#output-chars, #output-class").hide(),
        a.cookie("checked", ""),
        t.not("#undo, #redo").removeAttr("disabled"),
        n.mousedown(function (a) {
            a.metaKey = !1;
        }),
        n.find("table").selectable(),
        s
            .mouseup(function () {
                c(q.val(), r.val()), g(e());
            })
            .mousewheel(d),
        s.keyup(function () {
            var b = 1 * a(this).val();
            return /[0-9]/.test(b) === !1 ? void j("#ent-num") : 1 > b ? void j("#ent-natural-num") : (c(q.val(), r.val()), void g(e()));
        }),
        window.localStorage)
    )
        try {
            localStorage.clear(),
                localStorage.setItem("index", 0),
                localStorage.setItem("undo", 0),
                localStorage.setItem("o0", JSON.stringify({ row: a("[name=row]").val(), col: a("[name=col]").val() })),
                localStorage.setItem("data0", n.html()),
                a("#undo").attr("disabled", "disabled"),
                a("#redo").attr("disabled", "disabled");
        } catch (v) {
            "NS_ERROR_FILE_CORRUPTED" == v.name && a("#undo, #redo, #initialize").hide();
        }
    else a("#undo, #redo, #initialize").hide();
    a("#output-chars").click(function () {
        j(),
            t.not("#undo, #redo").removeAttr("disabled"),
            a("#chars").show(),
            a(this).hide(),
            s.mousewheel(d),
            n.find("td, th").each(function () {
                (content = a(this).children("textarea").val()), a(this).children("textarea").remove(), a(this).text(content);
            }),
            g(e());
    }),
        a("#output-class").click(function () {
            j(),
                t.not("#undo, #redo").removeAttr("disabled"),
                a("#class").show(),
                a(this).hide(),
                s.mousewheel(d),
                n.find("td, th").each(function () {
                    var b = a(this).children("input").val();
                    a(this).children("input").remove(), a(this).addClass(b).removeClass("class");
                });
            var b = a(".table-class").val();
            o.removeAttr("class"), b && o.addClass(b);
            var c = [];
            a(".tr-class").each(function (b) {
                c[b] = a(this).val();
            }),
                n.find("tr").each(function (b) {
                    a(this).removeAttr("class"), void 0 !== c[b] && a(this).addClass(c[b]);
                }),
                i(),
                g(e());
        }),
        a("#chars").click(function () {
            j("#ent-chars"),
                a(".ui-selected").removeClass("ui-selected"),
                t.not("#output-chars").attr("disabled", "disabled"),
                a(this).hide(),
                a("#output-chars").show(),
                s.unbind("mousewheel"),
                n.find("td, th").each(function () {
                    var b = a(this).text();
                    a(this).html('<textarea rows="1" name="textarea" class="form-control border-0 min-w-140px" placeholder="Enter Text" style="resize: none;">' + b + "</textarea>");
                    // <div class="add-text-box p-2 m-2"></div>
                }),
                a("textarea").click(function () {
                    a(this).focus();
                }),
                b("webkit") && n.find("td:has( input ), th:has( input )").css("padding", "1px 3px 2px");
        }),
        a(document).on(
            {
                focus: function () {
                    var b = a(this).parents("tr");
                    b.find("textarea").attr("rows", "1"), b.prevAll().find("textarea").attr("rows", "1"), b.nextAll().find("textarea").attr("rows", "1");
                },
                blur: function () {
                    o.find("textarea").attr("rows", "1");
                },
            },
            "[name=textarea]"
        ),
        a("#class").click(function () {
            j("#ent-class-names"),
                t.not("#output-class").attr("disabled", "disabled"),
                a(this).hide(),
                a("#output-class").show(),
                s.unbind("mousewheel"),
                n.find("td, th").each(function () {
                    a(this).removeClass("ui-selectee ui-selected " + u);
                    var b = a(this).attr("class");
                    void 0 === b && (b = ""), a('<input type="text" class="form-control form-control-sm my-1 min-w-100px" placeholder="Class Name" value="' + b + '" size="5">').appendTo(this), a(this).children().addClass("class");
                });
            var c = -27;
            b("webkit") && ((c = -25), n.find("td:has( input ), th:has( input )").css("padding", "3px"));
            var d = o.find("tr:first").position().left,
                e = o.attr("class");
            void 0 !== e &&
                (e = e
                    .replace(/ui-selecte(d\s|d)/g, "")
                    .replace(/ui-selecte(e\s|e)/g, "")
                    .replace(/ui-selectable/g, "")),
                e || (e = ""),
                a("<input />")
                    .val(e)
                    .attr({ type: "text", size: "5", placeholder: "table", class: "table-class" })
                    .css({ position: "absolute", top: c + "px", left: d + 4 + "px" })
                    .appendTo(m),
                a("<label />")
                    .attr("id", "stripe-label")
                    .css({ position: "absolute", top: c + 5 + "px", left: d - 65 + "px" })
                    .appendTo(m),
                a("<input />").attr({ type: "checkbox", id: "stripe" }).appendTo("#stripe-label").after("<span>stripe</span>"),
                n.find("tr").each(function () {
                    c = b("firefox") ? a(this).position().top + 3 : b("webkit") ? a(this).position().top + 4 : b("meie|trident") ? a(this).position().top + 6 : a(this).position().top + 3;
                    var d = a(this).position().left,
                        e = a(this).attr("class");
                    void 0 !== e && (e = e.replace(/ui-selecte(d\s|d)/g, "").replace(/ui-selecte(e\s|e)/g, "")),
                        e || (e = ""),
                        a("<input />")
                            .val(e)
                            .attr({ type: "text", size: "5", placeholder: "tr", class: "tr-class" })
                            .css({ position: "absolute", top: c + "px", left: d - 65 + "px" })
                            .appendTo(m);
                }),
                a("input").click(function () {
                    a(this).focus();
                }),
                a("#stripe").removeAttr("checked");
            var f = a.cookie("checked");
            1 == f && a("#stripe").attr("checked", "checked");
            var g = [];
            a("#stripe-label").click(function () {
                a("#stripe").is(":checked")
                    ? (a.cookie("checked", 1),
                      a(".tr-class").each(function (b) {
                          g[b] = a(this).val();
                          var c = a(this).val();
                          a(this).val(b % 2 === 0 ? "even" + ("" !== c ? " " + c : "") : "odd" + ("" !== c ? " " + c : ""));
                      }))
                    : (a.cookie("checked", ""),
                      a(".tr-class").each(function (b) {
                          (g[b] = a(this).val()), void 0 !== g[b] ? ((g[b] = g[b].replace(/eve(n\s|n)/g, "").replace(/od(d\s|d)/g, "")), a(this).val(g[b])) : a(this).val("");
                      }));
            });
        }),
        a(window).resize(function () {
            var c = -30;
            b("webkit") && (c = -33);
            var d = o.find("tr:first").position().left;
            a(".table-class").css({ position: "absolute", top: c + "px", left: d + 4 + "px" }),
                a("#stripe-label").css({ position: "absolute", top: c + 5 + "px", left: d - 69 + "px" }),
                (c = []),
                (d = 0),
                n.find("tr").each(function (e) {
                    b("firefox") ? (c[e] = a(this).position().top + 3) : b("webkit") && (c[e] = a(this).position().top + 4), (d = a(this).position().left);
                }),
                a(".tr-class").each(function (b) {
                    a(this).css({ position: "absolute", top: c[b] + "px", left: d - 75 + "px" });
                });
                $('.perfect-scrollbar').perfectScrollbar('update')
        }),
        a("#merge").click(function () {
            var b = null,
                c = null,
                d = n.find("td.ui-selected, th.ui-selected");
            if (0 === d.length) return void j("#select-cells");
            d.each(function () {
                a(this).get(0).tagName.match(/td/i) ? (b = !0) : a(this).get(0).tagName.match(/th/i) && (c = !0);
            });
            var f = "";
            if (b === !0 && c === !0) return j("#cant-merge"), !1;
            b === !0 && null === c ? (f = "td") : null === b && c === !0 && (f = "th"),
                a(f + ".ui-selected").each(function () {
                    return a(this).hasClass(u) ? (a(".ui-selected").removeClass("ui-selected"), j("#cant-remerge"), !1) : void 0;
                });
            var h = q.val(),
                i = r.val(),
                k = a(f + ".ui-selected")
                    .parent("tr")
                    .addClass("ui-selected").length,
                l = a(f + ".ui-selected").length / k;
            if (h == k && i == l) o.html("<tr><" + f + "></" + f + "></tr>");
            else {
                h != k && i == l
                    ? k > 1 && (a("tr.ui-selected:not( tr.ui-selected:first )").remove(), (k = 1))
                    : h == k &&
                      i != l &&
                      l > 1 &&
                      (n.find("tr.ui-selected").each(function () {
                          a(this)
                              .children(f + ".ui-selected:not( .ui-selected:first )")
                              .remove();
                      }),
                      (l = 1));
                var m,
                    p = a("tr").index(a("tr.ui-selected:first")),
                    s = a(f).index(a(f + ".ui-selected:first"));
                (m = i > s ? s : s - i * p),
                    a("tr.ui-selected").each(function (b) {
                        0 === b
                            ? l > 1 &&
                              a(this)
                                  .children(f + ".ui-selected:not(.ui-selected:first)")
                                  .remove()
                            : a(this)
                                  .children(f + ".ui-selected")
                                  .remove();
                    }),
                    l > 1 &&
                        a(f + ".ui-selected:first")
                            .attr("colspan", l)
                            .addClass(u),
                    k > 1 &&
                        a(f + ".ui-selected:first")
                            .attr("rowspan", k)
                            .addClass(u);
                var t = a("tr:empty").length;
                t > 0 &&
                    (n.find("[rowspan]").each(function () {
                        var b = a(this).attr("rowspan") - t;
                        a(this).attr("rowspan", b), 1 == a(this).attr("rowspan") && a(this).removeAttr("rowspan");
                    }),
                    n.find("tr:empty").remove(),
                    (h -= t)),
                    a("." + u).each(function () {
                        void 0 === a(this).attr("rowspan") && void 0 === a(this).attr("colspan") && a(this).removeClass(u);
                        // var setColor = "#333333"
                        // a(this).css({"background-color":setColor})
                    });
            }
            g(e());
            $('.perfect-scrollbar').perfectScrollbar('update')
        }),
        a("#replace").click(function () {
            var b = n.find("td.ui-selected, th.ui-selected");
            return 0 === b.length
                ? void j("#select-cells")
                : (b.each(function () {
                      var b = a(this).text(),
                          c = a(this).attr("colspan"),
                          d = a(this).attr("rowspan"),
                          e = a(this).attr("class"),
                          f = a("td" == a(this).get(0).tagName.toLowerCase() ? "<th>" : "<td>");
                      a(this).replaceWith(function () {
                          return f.text(b), c && f.attr("colspan", c), d && f.attr("rowspan", d), e && f.addClass(e).removeClass("ui-selected"), f;
                      });
                  }),
                  void g(e()));
        }),
        a("#split").click(function () {
            $('.perfect-scrollbar').perfectScrollbar('update')
            var a = n.find("td.ui-selected, th.ui-selected");
            return 0 === a.length ? void j("#select-cells") : (h(), void g(e()));
        }),
        a("#undo").click(function () {
            if (window.localStorage) {
                var a = localStorage.getItem("undo");
                a--;
                var b = JSON.parse(localStorage.getItem("o" + a));
                q.val(b.row), r.val(b.col);
                var c = localStorage.getItem("data" + a);
                n.empty().html(c), localStorage.setItem("undo", a), g(c), f(), i(), n.find("table").selectable();
                $('.perfect-scrollbar').perfectScrollbar('update')
            }
        }),
        a("#redo").click(function () {
            if (window.localStorage) {
                var a = (localStorage.getItem("index"), localStorage.getItem("undo"));
                a++;
                var b = JSON.parse(localStorage.getItem("o" + a));
                q.val(b.row), r.val(b.col);
                var c = localStorage.getItem("data" + a);
                n.empty().html(c), localStorage.setItem("undo", a), g(c), f(), i(), n.find("table").selectable();
                $('.perfect-scrollbar').perfectScrollbar('update')
            }
        }),
        a("#initialize").click(function () {
            c(q.val(), r.val()),
                o.removeAttr("class"),
                g(),
                window.localStorage &&
                    (localStorage.clear(), localStorage.setItem("index", 0), localStorage.setItem("undo", 0), localStorage.setItem("o0", JSON.stringify({ row: q.val(), col: r.val() })), localStorage.setItem("data0", n.html())),
                i(),
                f();
                $('.perfect-scrollbar').perfectScrollbar('update')
        }),
        a("#insert").click(function () {
            $('.perfect-scrollbar').perfectScrollbar('update')
            var b = a("[name=source]", "#generator").val();
            a("#content").insertAtCaret(b), tb_remove();
            $('.perfect-scrollbar').perfectScrollbar('update')
        });
    var w;
    (w = a("#css").text().match(/\n/g) ? a("#css").text().match(/\n/g).length + 2 : a("[name = style]").attr("rows")),
        a("[name = style]").attr("rows", w).val(a("#css").text()),
        a("[name = style]")
            .change(function () {
                a("#css").text(a(this).val());
            })
            .keyup(function () {
                a("#css").text(a(this).val());
            }),
        a("textarea", "#style").keydown(function (b) {
            var c,
                d = a(this).val();
            (c = d.match(/\n/g) ? d.match(/\n/g).length + 2 : a(this).attr("rows")), a(this).attr("rows", c), k(this, b);
        }),
        a.fn.extend({
            insertAtCaret: function (a) {
                var b = this.get(0);
                b.focus();
                var c = b.value,
                    d = b.selectionStart,
                    e = d + a.length;
                (b.value = c.substr(0, d) + a + c.substr(d)), b.setSelectionRange(e, e);
            },
        });
    var x = encodeURIComponent(document.title),
        y = encodeURIComponent(location.href),
        z = "https://twitter.com/intent/tweet?hashtags=TableTagGenerator";
    (z += "&text=" + x), (z += "&url=" + y), (z += "&via=shunk");
    var A = "https://facebook.com/sharer/sharer.php?u=" + y,
        B = "https://plus.google.com/share?url=" + y;
    a("#tweet").attr("href", z), a("#like").attr("href", A), a("#gplus").attr("href", B);
});
$('.perfect-scrollbar').perfectScrollbar('update')

$("#merge").fadeOut();
$( "table" ).selectable({
    selected: function(event, ui) {
        // Count the number of selected items
        var selectedCount = $(".ui-selected", this).length;

        // Show or hide the div based on the selected count
        if (selectedCount > 2) {
            $("#merge").fadeIn();
        } else {
            $("#merge").fadeOut();
        }

    },
    selecting: function(event, ui) {
        // Get the column index of the selected cell
        selectedColumnIndex = $(ui.selecting).index();
    }
  });
  
  $("#italic").click(function() {
    if (selectedColumnIndex !== null) {
        // Clear previously selected columns
        //$("table th, table td").removeClass('font-italic');

        // Highlight all cells in the selected column
        $('table tr').each(function() {
            $(this).children().eq(selectedColumnIndex).addClass('font-italic');
        });
    }
});