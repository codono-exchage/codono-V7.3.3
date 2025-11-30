/*! BitMEX-Frontend - v1.1.0 - 2017-03-27 */
"use strict";
function debounce(a, b, c) {
    var d;
    return function () {
        var e = this, f = arguments, g = function () {
            d = null, c || a.apply(e, f)
        }, h = c && !d;
        clearTimeout(d), d = setTimeout(g, b), h && a.apply(e, f)
    }
}
function calculateNumBars(a, b) {
    for (var c = SYMBOL_STORAGE[Object.keys(SYMBOL_STORAGE)[0]].intraday_multipliers.map(Number), d = c[0],
             e = 1; e < c.length; e++)c[e] < b && (d = c[e]);
    return a / d
}
function resStringToMinutes(a) {
    if (!isNaN(Number(a)))return Number(a);
    var b = a.slice(0, -1);
    return RESOLUTIONS[a[a.length - 1]] * parseInt(b, 10)
}
function periodLengthSeconds(a, b) {
    return 24 * ("D" === a ? b : "M" === a ? 31 * b : "W" === a ? 7 * b : b * a / 1440) * 60 * 60
}
function newBar(a) {
    return {time: Math.floor(Date.now() / a) * a, close: null, open: null, high: null, low: null, volume: 0}
}
function diffBar(a, b) {
    a.volume += b.size, a.open || (a.open = b.price), a.high = Math.max(a.high, b.price), a.low ? a.low = Math.min(a.low, b.price) : a.low = b.price, a.close = b.price
}
function inherit(a, b) {
    var c = function () {
    };
    c.prototype = b.prototype, a.prototype = new c, a.prototype.constructor = a, a.prototype.superclass = b
}
var Zepto = function () {
    function a(a) {
        return null == a ? String(a) : S[T.call(a)] || "object"
    }

    function b(b) {
        return "function" == a(b)
    }

    function c(a) {
        return null != a && a == a.window
    }

    function d(a) {
        return null != a && a.nodeType == a.DOCUMENT_NODE
    }

    function e(b) {
        return "object" == a(b)
    }

    function f(a) {
        return e(a) && !c(a) && Object.getPrototypeOf(a) == Object.prototype
    }

    function g(a) {
        return "number" == typeof a.length
    }

    function h(a) {
        return D.call(a, function (a) {
            return null != a
        })
    }

    function i(a) {
        return a.length > 0 ? x.fn.concat.apply([], a) : a
    }

    function j(a) {
        return a.replace(/::/g, "/").replace(/([A-Z]+)([A-Z][a-z])/g, "$1_$2").replace(/([a-z\d])([A-Z])/g, "$1_$2").replace(/_/g, "-").toLowerCase()
    }

    function k(a) {
        return a in G ? G[a] : G[a] = new RegExp("(^|\\s)" + a + "(\\s|$)")
    }

    function l(a, b) {
        return "number" != typeof b || H[j(a)] ? b : b + "px"
    }

    function m(a) {
        var b, c;
        return F[a] || (b = E.createElement(a), E.body.appendChild(b), c = getComputedStyle(b, "").getPropertyValue("display"), b.parentNode.removeChild(b), "none" == c && (c = "block"), F[a] = c), F[a]
    }

    function n(a) {
        return "children" in a ? C.call(a.children) : x.map(a.childNodes, function (a) {
            if (1 == a.nodeType)return a
        })
    }

    function o(a, b, c) {
        for (w in b)c && (f(b[w]) || X(b[w])) ? (f(b[w]) && !f(a[w]) && (a[w] = {}), X(b[w]) && !X(a[w]) && (a[w] = []), o(a[w], b[w], c)) : b[w] !== v && (a[w] = b[w])
    }

    function p(a, b) {
        return null == b ? x(a) : x(a).filter(b)
    }

    function q(a, c, d, e) {
        return b(c) ? c.call(a, d, e) : c
    }

    function r(a, b, c) {
        null == c ? a.removeAttribute(b) : a.setAttribute(b, c)
    }

    function s(a, b) {
        var c = a.className, d = c && c.baseVal !== v;
        if (b === v)return d ? c.baseVal : c;
        d ? c.baseVal = b : a.className = b
    }

    function t(a) {
        var b;
        try {
            return a ? "true" == a || "false" != a && ("null" == a ? null : /^0/.test(a) || isNaN(b = Number(a)) ? /^[\[\{]/.test(a) ? x.parseJSON(a) : a : b) : a
        } catch (b) {
            return a
        }
    }

    function u(a, b) {
        b(a);
        for (var c in a.childNodes)u(a.childNodes[c], b)
    }

    var v, w, x, y, z, A, B = [], C = B.slice, D = B.filter, E = window.document, F = {}, G = {},
        H = {"column-count": 1, columns: 1, "font-weight": 1, "line-height": 1, opacity: 1, "z-index": 1, zoom: 1},
        I = /^\s*<(\w+|!)[^>]*>/, J = /^<(\w+)\s*\/?>(?:<\/\1>|)$/, K = /^(?:body|html)$/i,
        L = ["val", "css", "html", "text", "data", "width", "height", "offset"],
        M = ["after", "prepend", "before", "append"], N = E.createElement("table"), O = E.createElement("tr"),
        P = {tr: E.createElement("tbody"), tbody: N, thead: N, tfoot: N, td: O, th: O, "*": E.createElement("div")},
        Q = /complete|loaded|interactive/, R = /^[\w-]*$/, S = {}, T = S.toString, U = {}, V = E.createElement("div"),
        W = {
            tabindex: "tabIndex",
            readonly: "readOnly",
            for: "htmlFor",
            class: "className",
            maxlength: "maxLength",
            cellspacing: "cellSpacing",
            cellpadding: "cellPadding",
            rowspan: "rowSpan",
            colspan: "colSpan",
            usemap: "useMap",
            frameborder: "frameBorder",
            contenteditable: "contentEditable"
        }, X = Array.isArray || function (a) {
                return a instanceof Array
            };
    return U.matches = function (a, b) {
        if (!b || !a || 1 !== a.nodeType)return !1;
        var c = a.webkitMatchesSelector || a.mozMatchesSelector || a.oMatchesSelector || a.matchesSelector;
        if (c)return c.call(a, b);
        var d, e = a.parentNode, f = !e;
        return f && (e = V).appendChild(a), d = ~U.qsa(e, b).indexOf(a), f && V.removeChild(a), d
    }, z = function (a) {
        return a.replace(/-+(.)?/g, function (a, b) {
            return b ? b.toUpperCase() : ""
        })
    }, A = function (a) {
        return D.call(a, function (b, c) {
            return a.indexOf(b) == c
        })
    }, U.fragment = function (a, b, c) {
        var d, e, g;
        return J.test(a) && (d = x(E.createElement(RegExp.$1))), d || (a.replace && (a = a.replace(/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi, "<$1></$2>")), b === v && (b = I.test(a) && RegExp.$1), b in P || (b = "*"), g = P[b], g.innerHTML = "" + a, d = x.each(C.call(g.childNodes), function () {
            g.removeChild(this)
        })), f(c) && (e = x(d), x.each(c, function (a, b) {
            L.indexOf(a) > -1 ? e[a](b) : e.attr(a, b)
        })), d
    }, U.Z = function (a, b) {
        return a = a || [], a.__proto__ = x.fn, a.selector = b || "", a
    }, U.isZ = function (a) {
        return a instanceof U.Z
    }, U.init = function (a, c) {
        var d;
        if (!a)return U.Z();
        if ("string" == typeof a)if (a = a.trim(), "<" == a[0] && I.test(a)) d = U.fragment(a, RegExp.$1, c), a = null; else {
            if (c !== v)return x(c).find(a);
            d = U.qsa(E, a)
        } else {
            if (b(a))return x(E).ready(a);
            if (U.isZ(a))return a;
            if (X(a)) d = h(a); else if (e(a)) d = [a], a = null; else if (I.test(a)) d = U.fragment(a.trim(), RegExp.$1, c), a = null; else {
                if (c !== v)return x(c).find(a);
                d = U.qsa(E, a)
            }
        }
        return U.Z(d, a)
    }, x = function (a, b) {
        return U.init(a, b)
    }, x.extend = function (a) {
        var b, c = C.call(arguments, 1);
        return "boolean" == typeof a && (b = a, a = c.shift()), c.forEach(function (c) {
            o(a, c, b)
        }), a
    }, U.qsa = function (a, b) {
        var c, e = "#" == b[0], f = !e && "." == b[0], g = e || f ? b.slice(1) : b, h = R.test(g);
        return d(a) && h && e ? (c = a.getElementById(g)) ? [c] : [] : 1 !== a.nodeType && 9 !== a.nodeType ? [] : C.call(h && !e ? f ? a.getElementsByClassName(g) : a.getElementsByTagName(b) : a.querySelectorAll(b))
    }, x.contains = function (a, b) {
        return a !== b && a.contains(b)
    }, x.type = a, x.isFunction = b, x.isWindow = c, x.isArray = X, x.isPlainObject = f, x.isEmptyObject = function (a) {
        var b;
        for (b in a)return !1;
        return !0
    }, x.inArray = function (a, b, c) {
        return B.indexOf.call(b, a, c)
    }, x.camelCase = z, x.trim = function (a) {
        return null == a ? "" : String.prototype.trim.call(a)
    }, x.uuid = 0, x.support = {}, x.expr = {}, x.map = function (a, b) {
        var c, d, e, f = [];
        if (g(a))for (d = 0; d < a.length; d++)null != (c = b(a[d], d)) && f.push(c); else for (e in a)null != (c = b(a[e], e)) && f.push(c);
        return i(f)
    }, x.each = function (a, b) {
        var c, d;
        if (g(a)) {
            for (c = 0; c < a.length; c++)if (b.call(a[c], c, a[c]) === !1)return a
        } else for (d in a)if (b.call(a[d], d, a[d]) === !1)return a;
        return a
    }, x.grep = function (a, b) {
        return D.call(a, b)
    }, window.JSON && (x.parseJSON = JSON.parse), x.each("Boolean Number String Function Array Date RegExp Object Error".split(" "), function (a, b) {
        S["[object " + b + "]"] = b.toLowerCase()
    }), x.fn = {
        forEach: B.forEach,
        reduce: B.reduce,
        push: B.push,
        sort: B.sort,
        indexOf: B.indexOf,
        concat: B.concat,
        map: function (a) {
            return x(x.map(this, function (b, c) {
                return a.call(b, c, b)
            }))
        },
        slice: function () {
            return x(C.apply(this, arguments))
        },
        ready: function (a) {
            return Q.test(E.readyState) && E.body ? a(x) : E.addEventListener("DOMContentLoaded", function () {
                a(x)
            }, !1), this
        },
        get: function (a) {
            return a === v ? C.call(this) : this[a >= 0 ? a : a + this.length]
        },
        toArray: function () {
            return this.get()
        },
        size: function () {
            return this.length
        },
        remove: function () {
            return this.each(function () {
                null != this.parentNode && this.parentNode.removeChild(this)
            })
        },
        each: function (a) {
            return B.every.call(this, function (b, c) {
                return a.call(b, c, b) !== !1
            }), this
        },
        filter: function (a) {
            return b(a) ? this.not(this.not(a)) : x(D.call(this, function (b) {
                return U.matches(b, a)
            }))
        },
        add: function (a, b) {
            return x(A(this.concat(x(a, b))))
        },
        is: function (a) {
            return this.length > 0 && U.matches(this[0], a)
        },
        not: function (a) {
            var c = [];
            if (b(a) && a.call !== v) this.each(function (b) {
                a.call(this, b) || c.push(this)
            }); else {
                var d = "string" == typeof a ? this.filter(a) : g(a) && b(a.item) ? C.call(a) : x(a);
                this.forEach(function (a) {
                    d.indexOf(a) < 0 && c.push(a)
                })
            }
            return x(c)
        },
        has: function (a) {
            return this.filter(function () {
                return e(a) ? x.contains(this, a) : x(this).find(a).size()
            })
        },
        eq: function (a) {
            return a === -1 ? this.slice(a) : this.slice(a, +a + 1)
        },
        first: function () {
            var a = this[0];
            return a && !e(a) ? a : x(a)
        },
        last: function () {
            var a = this[this.length - 1];
            return a && !e(a) ? a : x(a)
        },
        find: function (a) {
            var b = this;
            return "object" == typeof a ? x(a).filter(function () {
                var a = this;
                return B.some.call(b, function (b) {
                    return x.contains(b, a)
                })
            }) : 1 == this.length ? x(U.qsa(this[0], a)) : this.map(function () {
                return U.qsa(this, a)
            })
        },
        closest: function (a, b) {
            var c = this[0], e = !1;
            for ("object" == typeof a && (e = x(a)); c && !(e ? e.indexOf(c) >= 0 : U.matches(c, a));)c = c !== b && !d(c) && c.parentNode;
            return x(c)
        },
        parents: function (a) {
            for (var b = [], c = this; c.length > 0;)c = x.map(c, function (a) {
                if ((a = a.parentNode) && !d(a) && b.indexOf(a) < 0)return b.push(a), a
            });
            return p(b, a)
        },
        parent: function (a) {
            return p(A(this.pluck("parentNode")), a)
        },
        children: function (a) {
            return p(this.map(function () {
                return n(this)
            }), a)
        },
        contents: function () {
            return this.map(function () {
                return C.call(this.childNodes)
            })
        },
        siblings: function (a) {
            return p(this.map(function (a, b) {
                return D.call(n(b.parentNode), function (a) {
                    return a !== b
                })
            }), a)
        },
        empty: function () {
            return this.each(function () {
                this.innerHTML = ""
            })
        },
        pluck: function (a) {
            return x.map(this, function (b) {
                return b[a]
            })
        },
        show: function () {
            return this.each(function () {
                "none" == this.style.display && (this.style.display = ""), "none" == getComputedStyle(this, "").getPropertyValue("display") && (this.style.display = m(this.nodeName))
            })
        },
        replaceWith: function (a) {
            return this.before(a).remove()
        },
        wrap: function (a) {
            var c = b(a);
            if (this[0] && !c)var d = x(a).get(0), e = d.parentNode || this.length > 1;
            return this.each(function (b) {
                x(this).wrapAll(c ? a.call(this, b) : e ? d.cloneNode(!0) : d)
            })
        },
        wrapAll: function (a) {
            if (this[0]) {
                x(this[0]).before(a = x(a));
                for (var b; (b = a.children()).length;)a = b.first();
                x(a).append(this)
            }
            return this
        },
        wrapInner: function (a) {
            var c = b(a);
            return this.each(function (b) {
                var d = x(this), e = d.contents(), f = c ? a.call(this, b) : a;
                e.length ? e.wrapAll(f) : d.append(f)
            })
        },
        unwrap: function () {
            return this.parent().each(function () {
                x(this).replaceWith(x(this).children())
            }), this
        },
        clone: function () {
            return this.map(function () {
                return this.cloneNode(!0)
            })
        },
        hide: function () {
            return this.css("display", "none")
        },
        toggle: function (a) {
            return this.each(function () {
                var b = x(this);
                (a === v ? "none" == b.css("display") : a) ? b.show() : b.hide()
            })
        },
        prev: function (a) {
            return x(this.pluck("previousElementSibling")).filter(a || "*")
        },
        next: function (a) {
            return x(this.pluck("nextElementSibling")).filter(a || "*")
        },
        html: function (a) {
            return 0 === arguments.length ? this.length > 0 ? this[0].innerHTML : null : this.each(function (b) {
                var c = this.innerHTML;
                x(this).empty().append(q(this, a, b, c))
            })
        },
        text: function (a) {
            return 0 === arguments.length ? this.length > 0 ? this[0].textContent : null : this.each(function () {
                this.textContent = a === v ? "" : "" + a
            })
        },
        attr: function (a, b) {
            var c;
            return "string" == typeof a && b === v ? 0 == this.length || 1 !== this[0].nodeType ? v : "value" == a && "INPUT" == this[0].nodeName ? this.val() : !(c = this[0].getAttribute(a)) && a in this[0] ? this[0][a] : c : this.each(function (c) {
                if (1 === this.nodeType)if (e(a))for (w in a)r(this, w, a[w]); else r(this, a, q(this, b, c, this.getAttribute(a)))
            })
        },
        removeAttr: function (a) {
            return this.each(function () {
                1 === this.nodeType && r(this, a)
            })
        },
        prop: function (a, b) {
            return a = W[a] || a, b === v ? this[0] && this[0][a] : this.each(function (c) {
                this[a] = q(this, b, c, this[a])
            })
        },
        data: function (a, b) {
            var c = this.attr("data-" + a.replace(/([A-Z])/g, "-$1").toLowerCase(), b);
            return null !== c ? t(c) : v
        },
        val: function (a) {
            return 0 === arguments.length ? this[0] && (this[0].multiple ? x(this[0]).find("option").filter(function () {
                    return this.selected
                }).pluck("value") : this[0].value) : this.each(function (b) {
                this.value = q(this, a, b, this.value)
            })
        },
        offset: function (a) {
            if (a)return this.each(function (b) {
                var c = x(this), d = q(this, a, b, c.offset()), e = c.offsetParent().offset(),
                    f = {top: d.top - e.top, left: d.left - e.left};
                "static" == c.css("position") && (f.position = "relative"), c.css(f)
            });
            if (0 == this.length)return null;
            var b = this[0].getBoundingClientRect();
            return {
                left: b.left + window.pageXOffset,
                top: b.top + window.pageYOffset,
                width: Math.round(b.width),
                height: Math.round(b.height)
            }
        },
        css: function (b, c) {
            if (arguments.length < 2) {
                var d = this[0], e = getComputedStyle(d, "");
                if (!d)return;
                if ("string" == typeof b)return d.style[z(b)] || e.getPropertyValue(b);
                if (X(b)) {
                    var f = {};
                    return x.each(X(b) ? b : [b], function (a, b) {
                        f[b] = d.style[z(b)] || e.getPropertyValue(b)
                    }), f
                }
            }
            var g = "";
            if ("string" == a(b)) c || 0 === c ? g = j(b) + ":" + l(b, c) : this.each(function () {
                this.style.removeProperty(j(b))
            }); else for (w in b)b[w] || 0 === b[w] ? g += j(w) + ":" + l(w, b[w]) + ";" : this.each(function () {
                this.style.removeProperty(j(w))
            });
            return this.each(function () {
                this.style.cssText += ";" + g
            })
        },
        index: function (a) {
            return a ? this.indexOf(x(a)[0]) : this.parent().children().indexOf(this[0])
        },
        hasClass: function (a) {
            return !!a && B.some.call(this, function (a) {
                    return this.test(s(a))
                }, k(a))
        },
        addClass: function (a) {
            return a ? this.each(function (b) {
                y = [];
                var c = s(this);
                q(this, a, b, c).split(/\s+/g).forEach(function (a) {
                    x(this).hasClass(a) || y.push(a)
                }, this), y.length && s(this, c + (c ? " " : "") + y.join(" "))
            }) : this
        },
        removeClass: function (a) {
            return this.each(function (b) {
                if (a === v)return s(this, "");
                y = s(this), q(this, a, b, y).split(/\s+/g).forEach(function (a) {
                    y = y.replace(k(a), " ")
                }), s(this, y.trim())
            })
        },
        toggleClass: function (a, b) {
            return a ? this.each(function (c) {
                var d = x(this);
                q(this, a, c, s(this)).split(/\s+/g).forEach(function (a) {
                    (b === v ? !d.hasClass(a) : b) ? d.addClass(a) : d.removeClass(a)
                })
            }) : this
        },
        scrollTop: function (a) {
            if (this.length) {
                var b = "scrollTop" in this[0];
                return a === v ? b ? this[0].scrollTop : this[0].pageYOffset : this.each(b ? function () {
                    this.scrollTop = a
                } : function () {
                    this.scrollTo(this.scrollX, a)
                })
            }
        },
        scrollLeft: function (a) {
            if (this.length) {
                var b = "scrollLeft" in this[0];
                return a === v ? b ? this[0].scrollLeft : this[0].pageXOffset : this.each(b ? function () {
                    this.scrollLeft = a
                } : function () {
                    this.scrollTo(a, this.scrollY)
                })
            }
        },
        position: function () {
            if (this.length) {
                var a = this[0], b = this.offsetParent(), c = this.offset(),
                    d = K.test(b[0].nodeName) ? {top: 0, left: 0} : b.offset();
                return c.top -= parseFloat(x(a).css("margin-top")) || 0, c.left -= parseFloat(x(a).css("margin-left")) || 0, d.top += parseFloat(x(b[0]).css("border-top-width")) || 0, d.left += parseFloat(x(b[0]).css("border-left-width")) || 0, {
                    top: c.top - d.top,
                    left: c.left - d.left
                }
            }
        },
        offsetParent: function () {
            return this.map(function () {
                for (var a = this.offsetParent || E.body; a && !K.test(a.nodeName) && "static" == x(a).css("position");)a = a.offsetParent;
                return a
            })
        }
    }, x.fn.detach = x.fn.remove, ["width", "height"].forEach(function (a) {
        var b = a.replace(/./, function (a) {
            return a[0].toUpperCase()
        });
        x.fn[a] = function (e) {
            var f, g = this[0];
            return e === v ? c(g) ? g["inner" + b] : d(g) ? g.documentElement["scroll" + b] : (f = this.offset()) && f[a] : this.each(function (b) {
                g = x(this), g.css(a, q(this, e, b, g[a]()))
            })
        }
    }), M.forEach(function (b, c) {
        var d = c % 2;
        x.fn[b] = function () {
            var b, e, f = x.map(arguments, function (c) {
                return b = a(c), "object" == b || "array" == b || null == c ? c : U.fragment(c)
            }), g = this.length > 1;
            return f.length < 1 ? this : this.each(function (a, b) {
                e = d ? b : b.parentNode, b = 0 == c ? b.nextSibling : 1 == c ? b.firstChild : 2 == c ? b : null, f.forEach(function (a) {
                    if (g) a = a.cloneNode(!0); else if (!e)return x(a).remove();
                    u(e.insertBefore(a, b), function (a) {
                        null == a.nodeName || "SCRIPT" !== a.nodeName.toUpperCase() || a.type && "text/javascript" !== a.type || a.src || window.eval.call(window, a.innerHTML)
                    })
                })
            })
        }, x.fn[d ? b + "To" : "insert" + (c ? "Before" : "After")] = function (a) {
            return x(a)[b](this), this
        }
    }), U.Z.prototype = x.fn, U.uniq = A, U.deserializeValue = t, x.zepto = U, x
}();
window.$ = window.Zepto = Zepto, function (a) {
    function b(a) {
        return a._zid || (a._zid = m++)
    }

    function c(a, c, f, g) {
        if (c = d(c), c.ns)var h = e(c.ns);
        return (q[b(a)] || []).filter(function (a) {
            return a && (!c.e || a.e == c.e) && (!c.ns || h.test(a.ns)) && (!f || b(a.fn) === b(f)) && (!g || a.sel == g)
        })
    }

    function d(a) {
        var b = ("" + a).split(".");
        return {e: b[0], ns: b.slice(1).sort().join(" ")}
    }

    function e(a) {
        return new RegExp("(?:^| )" + a.replace(" ", " .* ?") + "(?: |$)")
    }

    function f(a, b) {
        return a.del && !s && a.e in t || !!b
    }

    function g(a) {
        return u[a] || s && t[a] || a
    }

    function h(c, e, h, i, k, m, n) {
        var o = b(c), p = q[o] || (q[o] = []);
        e.split(/\s/).forEach(function (b) {
            if ("ready" == b)return a(document).ready(h);
            var e = d(b);
            e.fn = h, e.sel = k, e.e in u && (h = function (b) {
                var c = b.relatedTarget;
                if (!c || c !== this && !a.contains(this, c))return e.fn.apply(this, arguments)
            }), e.del = m;
            var o = m || h;
            e.proxy = function (a) {
                if (a = j(a), !a.isImmediatePropagationStopped()) {
                    a.data = i;
                    var b = o.apply(c, a._args == l ? [a] : [a].concat(a._args));
                    return b === !1 && (a.preventDefault(), a.stopPropagation()), b
                }
            }, e.i = p.length, p.push(e), "addEventListener" in c && c.addEventListener(g(e.e), e.proxy, f(e, n))
        })
    }

    function i(a, d, e, h, i) {
        var j = b(a);
        (d || "").split(/\s/).forEach(function (b) {
            c(a, b, e, h).forEach(function (b) {
                delete q[j][b.i], "removeEventListener" in a && a.removeEventListener(g(b.e), b.proxy, f(b, i))
            })
        })
    }

    function j(b, c) {
        return !c && b.isDefaultPrevented || (c || (c = b), a.each(y, function (a, d) {
            var e = c[a];
            b[a] = function () {
                return this[d] = v, e && e.apply(c, arguments)
            }, b[d] = w
        }), (c.defaultPrevented !== l ? c.defaultPrevented : "returnValue" in c ? c.returnValue === !1 : c.getPreventDefault && c.getPreventDefault()) && (b.isDefaultPrevented = v)), b
    }

    function k(a) {
        var b, c = {originalEvent: a};
        for (b in a)x.test(b) || a[b] === l || (c[b] = a[b]);
        return j(c, a)
    }

    var l, m = 1, n = Array.prototype.slice, o = a.isFunction, p = function (a) {
            return "string" == typeof a
        }, q = {}, r = {}, s = "onfocusin" in window, t = {focus: "focusin", blur: "focusout"},
        u = {mouseenter: "mouseover", mouseleave: "mouseout"};
    r.click = r.mousedown = r.mouseup = r.mousemove = "MouseEvents", a.event = {
        add: h,
        remove: i
    }, a.proxy = function (c, d) {
        if (o(c)) {
            var e = function () {
                return c.apply(d, arguments)
            };
            return e._zid = b(c), e
        }
        if (p(d))return a.proxy(c[d], c);
        throw new TypeError("expected function")
    }, a.fn.bind = function (a, b, c) {
        return this.on(a, b, c)
    }, a.fn.unbind = function (a, b) {
        return this.off(a, b)
    }, a.fn.one = function (a, b, c, d) {
        return this.on(a, b, c, d, 1)
    };
    var v = function () {
        return !0
    }, w = function () {
        return !1
    }, x = /^([A-Z]|returnValue$|layer[XY]$)/, y = {
        preventDefault: "isDefaultPrevented",
        stopImmediatePropagation: "isImmediatePropagationStopped",
        stopPropagation: "isPropagationStopped"
    };
    a.fn.delegate = function (a, b, c) {
        return this.on(b, a, c)
    }, a.fn.undelegate = function (a, b, c) {
        return this.off(b, a, c)
    }, a.fn.live = function (b, c) {
        return a(document.body).delegate(this.selector, b, c), this
    }, a.fn.die = function (b, c) {
        return a(document.body).undelegate(this.selector, b, c), this
    }, a.fn.on = function (b, c, d, e, f) {
        var g, j, m = this;
        return b && !p(b) ? (a.each(b, function (a, b) {
            m.on(a, c, d, b, f)
        }), m) : (p(c) || o(e) || e === !1 || (e = d, d = c, c = l), (o(d) || d === !1) && (e = d, d = l), e === !1 && (e = w), m.each(function (l, m) {
            f && (g = function (a) {
                return i(m, a.type, e), e.apply(this, arguments)
            }), c && (j = function (b) {
                var d, f = a(b.target).closest(c, m).get(0);
                if (f && f !== m)return d = a.extend(k(b), {
                    currentTarget: f,
                    liveFired: m
                }), (g || e).apply(f, [d].concat(n.call(arguments, 1)))
            }), h(m, b, e, d, c, j || g)
        }))
    }, a.fn.off = function (b, c, d) {
        var e = this;
        return b && !p(b) ? (a.each(b, function (a, b) {
            e.off(a, c, b)
        }), e) : (p(c) || o(d) || d === !1 || (d = c, c = l), d === !1 && (d = w), e.each(function () {
            i(this, b, d, c)
        }))
    }, a.fn.trigger = function (b, c) {
        return b = p(b) || a.isPlainObject(b) ? a.Event(b) : j(b), b._args = c, this.each(function () {
            "dispatchEvent" in this && this.dispatchEvent(b), a(this).triggerHandler(b, c)
        })
    }, a.fn.triggerHandler = function (b, d) {
        var e, f;
        return this.each(function (g, h) {
            e = k(p(b) ? a.Event(b) : b), e._args = d, e.target = h, a.each(c(h, b.type || b), function (a, b) {
                if (f = b.proxy(e), e.isImmediatePropagationStopped())return !1
            })
        }), f
    }, "focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select keydown keypress keyup error".split(" ").forEach(function (b) {
        a.fn[b] = function (a) {
            return a ? this.bind(b, a) : this.trigger(b)
        }
    }), ["focus", "blur"].forEach(function (b) {
        a.fn[b] = function (a) {
            return a ? this.bind(b, a) : this.each(function () {
                try {
                    this[b]()
                } catch (a) {
                }
            }), this
        }
    }), a.Event = function (a, b) {
        p(a) || (b = a, a = b.type);
        var c = document.createEvent(r[a] || "Events"), d = !0;
        if (b)for (var e in b)"bubbles" == e ? d = !!b[e] : c[e] = b[e];
        return c.initEvent(a, d, !0), j(c)
    }
}(Zepto), function (a) {
    a.fn.serializeArray = function () {
        var b, c = [];
        return a([].slice.call(this.get(0).elements)).each(function () {
            b = a(this);
            var d = b.attr("type");
            "fieldset" != this.nodeName.toLowerCase() && !this.disabled && "submit" != d && "reset" != d && "button" != d && ("radio" != d && "checkbox" != d || this.checked) && c.push({
                name: b.attr("name"),
                value: b.val()
            })
        }), c
    }, a.fn.serialize = function () {
        var a = [];
        return this.serializeArray().forEach(function (b) {
            a.push(encodeURIComponent(b.name) + "=" + encodeURIComponent(b.value))
        }), a.join("&")
    }, a.fn.submit = function (b) {
        if (b) this.bind("submit", b); else if (this.length) {
            var c = a.Event("submit");
            this.eq(0).trigger(c), c.isDefaultPrevented() || this.get(0).submit()
        }
        return this
    }
}(Zepto), function (a) {
    "__proto__" in {} || a.extend(a.zepto, {
        Z: function (b, c) {
            return b = b || [], a.extend(b, a.fn), b.selector = c || "", b.__Z = !0, b
        }, isZ: function (b) {
            return "array" === a.type(b) && "__Z" in b
        }
    });
    try {
        getComputedStyle(void 0)
    } catch (a) {
        var b = getComputedStyle;
        window.getComputedStyle = function (a) {
            try {
                return b(a)
            } catch (a) {
                return null
            }
        }
    }
}(Zepto), function (a) {
    function b(b, d) {
        var i = b[h], j = i && e[i];
        if (void 0 === d)return j || c(b);
        if (j) {
            if (d in j)return j[d];
            var k = g(d);
            if (k in j)return j[k]
        }
        return f.call(a(b), d)
    }

    function c(b, c, f) {
        var i = b[h] || (b[h] = ++a.uuid), j = e[i] || (e[i] = d(b));
        return void 0 !== c && (j[g(c)] = f), j
    }

    function d(b) {
        var c = {};
        return a.each(b.attributes || i, function (b, d) {
            0 == d.name.indexOf("data-") && (c[g(d.name.replace("data-", ""))] = a.zepto.deserializeValue(d.value))
        }), c
    }

    var e = {}, f = a.fn.data, g = a.camelCase, h = a.expando = "Zepto" + +new Date, i = [];
    a.fn.data = function (d, e) {
        return void 0 === e ? a.isPlainObject(d) ? this.each(function (b, e) {
            a.each(d, function (a, b) {
                c(e, a, b)
            })
        }) : 0 in this ? b(this[0], d) : void 0 : this.each(function () {
            c(this, d, e)
        })
    }, a.fn.removeData = function (b) {
        return "string" == typeof b && (b = b.split(/\s+/)), this.each(function () {
            var c = this[h], d = c && e[c];
            d && a.each(b || d, function (a) {
                delete d[b ? g(this) : a]
            })
        })
    }, ["remove", "empty"].forEach(function (b) {
        var c = a.fn[b];
        a.fn[b] = function () {
            var a = this.find("*");
            return "remove" === b && (a = a.add(this)), a.removeData(), c.call(this)
        }
    })
}(Zepto), function (a) {
    function b(b, c, d) {
        var e = a.Event(c);
        return a(b).trigger(e, d), !e.isDefaultPrevented()
    }

    function c(a, c, d, e) {
        if (a.global)return b(c || s, d, e)
    }

    function d(b) {
        b.global && 0 == a.active++ && c(b, null, "ajaxStart")
    }

    function e(b) {
        b.global && !--a.active && c(b, null, "ajaxStop")
    }

    function f(a, b) {
        var d = b.context;
        if (b.beforeSend.call(d, a, b) === !1 || c(b, d, "ajaxBeforeSend", [a, b]) === !1)return !1;
        c(b, d, "ajaxSend", [a, b])
    }

    function g(a, b, d, e) {
        var f = d.context;
        d.success.call(f, a, "success", b), e && e.resolveWith(f, [a, "success", b]), c(d, f, "ajaxSuccess", [b, d, a]), i("success", b, d)
    }

    function h(a, b, d, e, f) {
        var g = e.context;
        e.error.call(g, d, b, a), f && f.rejectWith(g, [d, b, a]), c(e, g, "ajaxError", [d, e, a || b]), i(b, d, e)
    }

    function i(a, b, d) {
        var f = d.context;
        d.complete.call(f, b, a), c(d, f, "ajaxComplete", [b, d]), e(d)
    }

    function j() {
    }

    function k(a) {
        return a && (a = a.split(";", 2)[0]), a && (a == w ? "html" : a == v ? "json" : t.test(a) ? "script" : u.test(a) && "xml") || "text"
    }

    function l(a, b) {
        return "" == b ? a : (a + "&" + b).replace(/[&?]{1,2}/, "?")
    }

    function m(b) {
        b.processData && b.data && "string" != a.type(b.data) && (b.data = a.param(b.data, b.traditional)), !b.data || b.type && "GET" != b.type.toUpperCase() || (b.url = l(b.url, b.data), b.data = void 0)
    }

    function n(b, c, d, e) {
        return a.isFunction(c) && (e = d, d = c, c = void 0), a.isFunction(d) || (e = d, d = void 0), {
            url: b,
            data: c,
            success: d,
            dataType: e
        }
    }

    function o(b, c, d, e) {
        var f, g = a.isArray(c), h = a.isPlainObject(c);
        a.each(c, function (c, i) {
            f = a.type(i), e && (c = d ? e : e + "[" + (h || "object" == f || "array" == f ? c : "") + "]"), !e && g ? b.add(i.name, i.value) : "array" == f || !d && "object" == f ? o(b, i, d, c) : b.add(c, i)
        })
    }

    var p, q, r = 0, s = window.document, t = /^(?:text|application)\/javascript/i, u = /^(?:text|application)\/xml/i,
        v = "application/json", w = "text/html", x = /^\s*$/;
    a.active = 0, a.ajaxJSONP = function (b, c) {
        if (!("type" in b))return a.ajax(b);
        var d, e, i = b.jsonpCallback, j = (a.isFunction(i) ? i() : i) || "jsonp" + ++r, k = s.createElement("script"),
            l = window[j], m = function (b) {
                a(k).triggerHandler("error", b || "abort")
            }, n = {abort: m};
        return c && c.promise(n), a(k).on("load error", function (f, i) {
            clearTimeout(e), a(k).off().remove(), "error" != f.type && d ? g(d[0], n, b, c) : h(null, i || "error", n, b, c), window[j] = l, d && a.isFunction(l) && l(d[0]), l = d = void 0
        }), f(n, b) === !1 ? (m("abort"), n) : (window[j] = function () {
            d = arguments
        }, k.src = b.url.replace(/\?(.+)=\?/, "?$1=" + j), s.head.appendChild(k), b.timeout > 0 && (e = setTimeout(function () {
            m("timeout")
        }, b.timeout)), n)
    }, a.ajaxSettings = {
        type: "GET",
        beforeSend: j,
        success: j,
        error: j,
        complete: j,
        context: null,
        global: !0,
        xhr: function () {
            return new window.XMLHttpRequest
        },
        accepts: {
            script: "text/javascript, application/javascript, application/x-javascript",
            json: v,
            xml: "application/xml, text/xml",
            html: w,
            text: "text/plain"
        },
        crossDomain: !1,
        timeout: 0,
        processData: !0,
        cache: !0
    }, a.ajax = function (b) {
        var c = a.extend({}, b || {}), e = a.Deferred && a.Deferred();
        for (p in a.ajaxSettings)void 0 === c[p] && (c[p] = a.ajaxSettings[p]);
        d(c), c.crossDomain || (c.crossDomain = /^([\w-]+:)?\/\/([^\/]+)/.test(c.url) && RegExp.$2 != window.location.host), c.url || (c.url = window.location.toString()), m(c);
        var i = c.dataType, n = /\?.+=\?/.test(c.url);
        if (n && (i = "jsonp"), c.cache !== !1 && (b && b.cache === !0 || "script" != i && "jsonp" != i) || (c.url = l(c.url, "_=" + Date.now())), "jsonp" == i)return n || (c.url = l(c.url, c.jsonp ? c.jsonp + "=?" : c.jsonp === !1 ? "" : "callback=?")), a.ajaxJSONP(c, e);
        var o, r = c.accepts[i], s = {}, t = function (a, b) {
            s[a.toLowerCase()] = [a, b]
        }, u = /^([\w-]+:)\/\//.test(c.url) ? RegExp.$1 : window.location.protocol, v = c.xhr(), w = v.setRequestHeader;
        if (e && e.promise(v), c.crossDomain || t("X-Requested-With", "XMLHttpRequest"), t("Accept", r || "*/*"), (r = c.mimeType || r) && (r.indexOf(",") > -1 && (r = r.split(",", 2)[0]), v.overrideMimeType && v.overrideMimeType(r)), (c.contentType || c.contentType !== !1 && c.data && "GET" != c.type.toUpperCase()) && t("Content-Type", c.contentType || "application/x-www-form-urlencoded"), c.headers)for (q in c.headers)t(q, c.headers[q]);
        if (v.setRequestHeader = t, v.onreadystatechange = function () {
                if (4 == v.readyState) {
                    v.onreadystatechange = j, clearTimeout(o);
                    var b, d = !1;
                    if (v.status >= 200 && v.status < 300 || 304 == v.status || 0 == v.status && "file:" == u) {
                        i = i || k(c.mimeType || v.getResponseHeader("content-type")), b = v.responseText;
                        try {
                            "script" == i ? (0, eval)(b) : "xml" == i ? b = v.responseXML : "json" == i && (b = x.test(b) ? null : a.parseJSON(b))
                        } catch (a) {
                            d = a
                        }
                        d ? h(d, "parsererror", v, c, e) : g(b, v, c, e)
                    } else h(v.statusText || null, v.status ? "error" : "abort", v, c, e)
                }
            }, f(v, c) === !1)return v.abort(), h(null, "abort", v, c, e), v;
        if (c.xhrFields)for (q in c.xhrFields)v[q] = c.xhrFields[q];
        var y = !("async" in c) || c.async;
        v.open(c.type, c.url, y, c.username, c.password);
        for (q in s)w.apply(v, s[q]);
        return c.timeout > 0 && (o = setTimeout(function () {
            v.onreadystatechange = j, v.abort(), h(null, "timeout", v, c, e)
        }, c.timeout)), v.send(c.data ? c.data : null), v
    }, a.get = function () {
        return a.ajax(n.apply(null, arguments))
    }, a.post = function () {
        var b = n.apply(null, arguments);
        return b.type = "POST", a.ajax(b)
    }, a.getJSON = function () {
        var b = n.apply(null, arguments);
        return b.dataType = "json", a.ajax(b)
    }, a.fn.load = function (b, c, d) {
        if (!this.length)return this;
        var e, f = this, g = b.split(/\s/), h = n(b, c, d), i = h.success;
        return g.length > 1 && (h.url = g[0], e = g[1]), h.success = function (b) {
            f.html(e ? a("<div>").html(b.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, "")).find(e) : b), i && i.apply(f, arguments)
        }, a.ajax(h), this
    };
    var y = encodeURIComponent;
    a.param = function (a, b) {
        var c = [];
        return c.add = function (a, b) {
            this.push(y(a) + "=" + y(b))
        }, o(c, a, b), c.join("&").replace(/%20/g, "+")
    }
}(Zepto), "undefined" != typeof module && (module.exports = Zepto), function () {
    window.TVSettings = {
        fullscreen: !0,
        library_path: "/trade/",
        drawings_access: {type: "black", tools: [{name: "Regression Trend"}]},
        enabled_features: ["minimalistic_logo", "narrow_chart_enabled", "dont_show_boolean_study_arguments", "clear_bars_on_series_error", "hide_loading_screen_on_series_error"],
        disabled_features: ["google_analytics", "14851", "header_saveload"],
        charts_storage_url: "http://saveload.tradingview.com",
        client_id: "bitmex.com",
        overrides: {
            "paneProperties.topMargin": 10,
            "paneProperties.bottomMargin": 25,
            "paneProperties.legendProperties.showStudyArguments": !1,
            "paneProperties.legendProperties.showStudyTitles": !0,
            "paneProperties.legendProperties.showStudyValues": !0,
            "paneProperties.legendProperties.showSeriesTitle": !1,
            "paneProperties.legendProperties.showSeriesOHLC": !0,
            "scalesProperties.showLeftScale": !1,
            "scalesProperties.showRightScale": !0,
            "scalesProperties.scaleSeriesOnly": !1,
            "scalesProperties.showSymbolLabels": !0,
            "mainSeriesProperties.priceAxisProperties.autoScale": !0,
            "mainSeriesProperties.priceAxisProperties.autoScaleDisabled": !1,
            "mainSeriesProperties.priceAxisProperties.percentage": !1,
            "mainSeriesProperties.priceAxisProperties.percentageDisabled": !1,
            "mainSeriesProperties.priceAxisProperties.log": !1,
            "mainSeriesProperties.priceAxisProperties.logDisabled": !1,
            "mainSeriesProperties.showLastValue": !0,
            "mainSeriesProperties.visible": !0,
            "mainSeriesProperties.showPriceLine": !0
        },
        interval: "60",
        allow_symbol_change: !0,
        container_id: "tv_chart_container",
        time_frames: [{text: "1y", resolution: "1d"}, {text: "1m", resolution: "360"}, {
            text: "5d",
            resolution: "60"
        }, {text: "1d", resolution: "5"}, {text: "5h", resolution: "5"}]
    }
}(), function () {
    function a(a) {
        var b = "#3c3f3f", c = "#6b767b", d = "#e5603b", e = "#56bc76", f = "#eee", g = "#8dd2a2", h = "#ee977f",
            i = "#5DC8EA", j = "#1709C4", k = "#f8f8f8";
        return "light" === a && (b = "#fff", c = "#ddd", i = "#8B008B", f = "#222", g = "#205536", h = "#5C1A10", k = "#666", j = "#33FF88"), {
            toolbar_bg: "transparent",
            overrides: {
                "dataWindowProperties.visible": !1,
                "dataWindowProperties.font": "Open Sans, Verdana",
                "dataWindowProperties.fontSize": 8,
                "paneProperties.background": b,
                "paneProperties.vertGridProperties.color": c,
                "paneProperties.horzGridProperties.color": c,
                "paneProperties.crossHairProperties.color": f,
                "scalesProperties.lineColor": f,
                "scalesProperties.textColor": f,
                "symbolWatermarkProperties.color": k,
                "symbolWatermarkProperties.transparency": 85,
                "mainSeriesProperties.candleStyle.upColor": e,
                "mainSeriesProperties.candleStyle.downColor": d,
                "mainSeriesProperties.candleStyle.drawWick": !0,
                "mainSeriesProperties.candleStyle.drawBorder": !0,
                "mainSeriesProperties.candleStyle.borderColor": "#378658",
                "mainSeriesProperties.candleStyle.borderUpColor": g,
                "mainSeriesProperties.candleStyle.borderDownColor": h,
                "mainSeriesProperties.candleStyle.wickColor": "#737375",
                "mainSeriesProperties.candleStyle.wickUpColor": "#388d53",
                "mainSeriesProperties.candleStyle.wickDownColor": "#d04f4f",
                "mainSeriesProperties.candleStyle.barColorsOnPrevClose": !1,
                "mainSeriesProperties.hollowCandleStyle.upColor": e,
                "mainSeriesProperties.hollowCandleStyle.downColor": d,
                "mainSeriesProperties.hollowCandleStyle.drawWick": !0,
                "mainSeriesProperties.hollowCandleStyle.drawBorder": !0,
                "mainSeriesProperties.hollowCandleStyle.borderColor": "#378658",
                "mainSeriesProperties.hollowCandleStyle.borderUpColor": g,
                "mainSeriesProperties.hollowCandleStyle.borderDownColor": h,
                "mainSeriesProperties.hollowCandleStyle.wickColor": "#737375",
                "mainSeriesProperties.hollowCandleStyle.wickUpColor": "#388d53",
                "mainSeriesProperties.hollowCandleStyle.wickDownColor": "#d04f4f",
                "mainSeriesProperties.haStyle.upColor": e,
                "mainSeriesProperties.haStyle.downColor": d,
                "mainSeriesProperties.haStyle.drawWick": !0,
                "mainSeriesProperties.haStyle.drawBorder": !0,
                "mainSeriesProperties.haStyle.borderColor": "#378658",
                "mainSeriesProperties.haStyle.borderUpColor": g,
                "mainSeriesProperties.haStyle.borderDownColor": h,
                "mainSeriesProperties.haStyle.wickColor": "#737375",
                "mainSeriesProperties.haStyle.wickUpColor": "#388d53",
                "mainSeriesProperties.haStyle.wickDownColor": "#d04f4f",
                "mainSeriesProperties.haStyle.barColorsOnPrevClose": !1,
                "mainSeriesProperties.barStyle.upColor": e,
                "mainSeriesProperties.barStyle.downColor": d,
                "mainSeriesProperties.barStyle.barColorsOnPrevClose": !1,
                "mainSeriesProperties.barStyle.dontDrawOpen": !1,
                "mainSeriesProperties.lineStyle.color": "#618fb0",
                "mainSeriesProperties.lineStyle.linewidth": 1,
                "mainSeriesProperties.lineStyle.priceSource": "close",
                "mainSeriesProperties.areaStyle.color1": e,
                "mainSeriesProperties.areaStyle.color2": d,
                "mainSeriesProperties.areaStyle.linecolor": "#618fb0",
                "mainSeriesProperties.areaStyle.linewidth": 1,
                "mainSeriesProperties.areaStyle.priceSource": "close",
                "mainSeriesProperties.areaStyle.transparency": 50,
                "mainSeriesProperties.renkoStyle.upColor": e,
                "mainSeriesProperties.renkoStyle.downColor": d,
                "mainSeriesProperties.pbStyle.upColor": e,
                "mainSeriesProperties.pbStyle.downColor": d,
                "mainSeriesProperties.kagiStyle.upColor": e,
                "mainSeriesProperties.kagiStyle.downColor": d,
                "mainSeriesProperties.pnfStyle.upColor": e,
                "mainSeriesProperties.pnfStyle.downColor": d
            },
            studies_overrides: {
                "volume.volume.color.0": d,
                "volume.volume.color.1": e,
                "volume.volume.transparency": 60,
                "volume.volume ma.color": "#4ab0ce",
                "volume.volume ma.transparency": 80,
                "volume.volume ma.linewidth": 5,
                "volume.show ma": !1,
                "volume.options.showStudyArguments": !1,
                "bollinger bands.median.color": j,
                "bollinger bands.upper.linewidth": 7,
                "compare.plot.color": i
            }
        }
    }

    window.TVColors = {light: a("light"), dark: a("dark")}
}(), function () {
    window.TVEmbedSettings = {
        disabled_features: ["header_symbol_search", "edit_buttons_in_legend", "header_fullscreen_button"],
        overrides: {"paneProperties.topMargin": 15, "paneProperties.bottomMargin": 30}
    }
}(), function () {
    function a(a) {
        if (b) {
            for (var c = new Array(arguments.length - 1), d = 1; d < arguments.length; d++)c[d - 1] = arguments[d];
            "setItem" === a && "string" != typeof c[1] && (c[1] = JSON.stringify(c[1]));
            try {
                var e = b[a].apply(b, c);
                if ("getItem" === a)try {
                    e = JSON.parse(e)
                } catch (a) {
                }
                return e
            } catch (a) {
                return ""
            }
        }
    }

    var b = window.localStorage;
    window.betterLS = {};
    var c = {get: "getItem", set: "setItem", remove: "removeItem"};
    ["getItem", "setItem", "clear", "removeItem"].concat(Object.keys(c)).forEach(function (b) {
        var d = c[b] || b;
        window.betterLS[b] = a.bind(null, d)
    })
}(), function () {
    function a() {
        function a() {
            if (A)return clearTimeout(b);
            window.postMessage({event: "chartReady"}, "*"), b = setTimeout(a, 100)
        }

        s("widget is ready."), u = t.chart(), v = t.activeChart()._chartWidget._model.m_model;
        var b;
        a(), c(), E || (E = !0, t.onContextMenu(m)), t.onSymbolChange(function (a) {
            D = a.name, window.postMessage({event: "symbolChange", data: a}, "*")
        }), t.onIntervalChange(function (a) {
            window.postMessage({event: "intervalChange", data: a}, "*")
        })
    }

    function b(a) {
        var b = a.data;
        if (b.cmd)return x = x.filter(function (a) {
            return a.data.cmd !== b.cmd
        }), !t || z ? (s("Queuing %j", b), void x.push({data: b})) : (s("Executing %j", b), "chartReadyAck" === b.cmd ? d() : "changeSymbol" === b.cmd ? e(b.data) : "changeReferenceSymbol" === b.cmd ? n(b.data) : "updatePosition" === b.cmd ? h(b.data) : "updateOrders" === b.cmd ? i(b.data) : "createExecution" === b.cmd ? void b.data : "draw" === b.cmd ? j(b.draw) : "showNoticeDialog" === b.cmd ? k(b.data) : "showConfirmDialog" === b.cmd ? l(b.data) : "reloadBins" === b.cmd ? p() : void 0)
    }

    function c() {
        z = !1;
        var a = x.slice();
        s("Emptying cmd queue. Contents: " + JSON.stringify(a, null, 2)), x = [], a.forEach(b)
    }

    function d() {
        A = !0
    }

    function e(a) {
        if (D !== a) {
            var b = setTimeout(function () {
                z = !1, D !== a && k({title: "Sorry...", body: "This instrument failed to load. Please try another."})
            }, 1e4);
            z = !0;
            var d = t.symbolInterval();
            s("setting symbol", a), o(null), t.setSymbol(a, d.interval, function () {
                clearTimeout(b), c()
            })
        }
    }

    function f(a) {
        if (!(window.top && window.top.innerWidth < 996))return u.createPositionLine().setLineLength(88).setText(a.text).setQuantity(a.qty).setPrice(a.price).setBodyBorderColor(a.color).setBodyTextColor(a.color).setLineColor(a.color).setBodyBackgroundColor(C)
    }

    function g(a) {
        return u.createOrderLine().onMove(function () {
            var b = this._line._points[0].price;
            this.setText("Amending..."), window.postMessage({event: "orderAmend", data: {price: b, id: a.id}}, "*")
        }).onCancel(function () {
            this.setText("Cancelling..."), window.postMessage({event: "orderCancel", data: a.id}, "*")
        }).setLineLength(52).setText(a.text).setQuantity(a.qty).setPrice(a.price).setTooltip(a.tooltip).setBodyBorderColor(a.color).setBodyTextColor(a.color).setLineColor(a.color).setQuantityBackgroundColor(a.color).setQuantityBorderColor(a.color).setCancelButtonBorderColor(a.color).setCancelButtonIconColor(a.color).setCancelButtonBackgroundColor(C).setBodyBackgroundColor(C)
    }

    function h(a) {
        if (a && (I = a), w && w.remove(), G.disablePositions || !a.qty)return void(w = null);
        w = f(a)
    }

    function i(a) {
        if (G.disableOrders)return void(H = a);
        var b = a.map(function (a) {
            return a.id
        });
        a.forEach(function (a) {
            y[a.id] ? r(y[a.id].order, a) || (y[a.id].line.remove(), y[a.id].line = g(a), y[a.id].order = a) : y[a.id] = {
                order: a,
                line: g(a)
            }
        }), Object.keys(y).forEach(function (a) {
            b.indexOf(a) === -1 && (y[a].line.remove(), delete y[a])
        })
    }

    function j(a) {
        s("creating shape", a), u.createShape(a.point, a.options)
    }

    function k(a) {
        t.showNoticeDialog ? t.showNoticeDialog(a) : console.error(a.title + " " + a.body)
    }

    function l(a) {
        t.showConfirmDialog(a)
    }

    function m(a, b) {
        return [{
            text: G.disableOrders ? "Enable Order Display" : "Disable Order Display",
            position: "top",
            click: function (a) {
                G.disableOrders ? (q("disableOrders", !1), i(H)) : (H = Object.keys(y).map(function (a) {
                    return y[a].order
                }), i([]), q("disableOrders", !0))
            }
        }, {
            text: G.disablePositions ? "Enable Position Display" : "Disable Position Display",
            position: "top",
            click: function (a) {
                q("disablePositions", !G.disablePositions), h(I)
            }
        }, {
            text: G.disableIndexOverlay ? "Enable Index Overlay" : "Disable Index Overlay",
            position: "top",
            click: function (a) {
                var b = !G.disableIndexOverlay;
                q("disableIndexOverlay", b), b ? o(null) : n(J)
            }
        }, {
            text: "Reset Chart", position: "top", click: function (a) {
                try {
                    Object.keys(window.localStorage).forEach(function (a) {
                        0 === a.indexOf("tradingview") && window.localStorage.removeItem(a)
                    }), window.location.reload()
                } catch (a) {
                }
            }
        }, {text: "-", position: "top"}, {text: "-Change Symbol..."}, {text: "-Reset Chart"}]
    }

    function n(a) {
        if (J !== a && (J = a, !G.disableIndexOverlay && a)) {
            if (o(a))return void s("Compare study for reference symbol %s already found, not adding a new one.", a);
            var b = v.m_mainSeries.priceScale().properties(), c = b.log._value, d = b.percentage._value;
            u.createStudy("Compare", !1, !1, ["open", a]), s("Created compare study for reference symbol %s.", a), b.log.setValue(c), b.percentage.setValue(d)
        }
    }

    function o(a) {
        a || (J = null);
        var b = u.createStudyTemplate({saveInterval: !1}), c = b.panes[0].sources.filter(function (a) {
            return "study_Compare" === a.type
        }), d = !1;
        return c.forEach(function (b) {
            if (!d && b.state.inputs.symbol === a)return void(d = !0);
            s("Removing compare study entity %s (reference symbol %s).", b.id, b.state.inputs.symbol), u.removeEntity(b.id)
        }), d
    }

    function p() {
        "resetData" in u ? u.resetData() : u._chartWidget.connect()
    }

    function q(a, b) {
        G[a] = b, window.betterLS.setItem(F, G)
    }

    function r(a, b) {
        return JSON.stringify(a) === JSON.stringify(b)
    }

    function s() {
        window.TV_DEBUG && console.log.apply(console, arguments)
    }

    window.addEventListener("message", b, !1), window.TV_DEBUG = !1;
    var t, u, v, w, x = [], y = {}, z = !0, A = !1, B = /theme=light/i.test(window.location.search),
        C = B ? "rgba(255,255,255,1)" : "rgba(51,51,51,1)", D = document.body.getAttribute("data-symbol") || null,
        E = !1, F = "bitmex-tv-options", G = window.betterLS.getItem(F);
    G && "string" != typeof G || (G = {});
    var H, I, J, K = setInterval(function () {
        (t = window.TVWidget) && (s("found widget, adding onChartReady"), s("current ready status: ", t._ready), clearInterval(K), t.onChartReady(a))
    }, 16)
}(), function (a) {
    var b = function () {
        var a = function (a) {
            var b = -a.getTimezoneOffset();
            return null !== b ? b : 0
        }, c = function (a, b, c) {
            var d = new Date;
            return void 0 !== a && d.setFullYear(a), d.setMonth(b), d.setDate(c), d
        }, d = function (b) {
            return a(c(b, 0, 2))
        }, e = function (b) {
            return a(c(b, 5, 2))
        }, f = function (b) {
            var c = b.getMonth() > 7, f = c ? e(b.getFullYear()) : d(b.getFullYear()), g = a(b), h = f < 0, i = f - g;
            return h || c ? 0 !== i : i < 0
        }, g = function () {
            var a = d(), b = e(), c = a - b;
            return c < 0 ? a + ",1" : c > 0 ? b + ",1,s" : a + ",0"
        };
        return {
            determine: function () {
                var a = g();
                return new b.TimeZone(b.olson.timezones[a])
            }, date_is_dst: f, dst_start_for: function (a) {
                var b = new Date(2010, 6, 15, 1, 0, 0, 0);
                return {
                    "America/Denver": new Date(2011, 2, 13, 3, 0, 0, 0),
                    "America/Mazatlan": new Date(2011, 3, 3, 3, 0, 0, 0),
                    "America/Chicago": new Date(2011, 2, 13, 3, 0, 0, 0),
                    "America/Mexico_City": new Date(2011, 3, 3, 3, 0, 0, 0),
                    "America/Asuncion": new Date(2012, 9, 7, 3, 0, 0, 0),
                    "America/Santiago": new Date(2012, 9, 3, 3, 0, 0, 0),
                    "America/Campo_Grande": new Date(2012, 9, 21, 5, 0, 0, 0),
                    "America/Montevideo": new Date(2011, 9, 2, 3, 0, 0, 0),
                    "America/Sao_Paulo": new Date(2011, 9, 16, 5, 0, 0, 0),
                    "America/Los_Angeles": new Date(2011, 2, 13, 8, 0, 0, 0),
                    "America/Santa_Isabel": new Date(2011, 3, 5, 8, 0, 0, 0),
                    "America/Havana": new Date(2012, 2, 10, 2, 0, 0, 0),
                    "America/New_York": new Date(2012, 2, 10, 7, 0, 0, 0),
                    "Europe/Helsinki": new Date(2013, 2, 31, 5, 0, 0, 0),
                    "Pacific/Auckland": new Date(2011, 8, 26, 7, 0, 0, 0),
                    "America/Halifax": new Date(2011, 2, 13, 6, 0, 0, 0),
                    "America/Goose_Bay": new Date(2011, 2, 13, 2, 1, 0, 0),
                    "America/Miquelon": new Date(2011, 2, 13, 5, 0, 0, 0),
                    "America/Godthab": new Date(2011, 2, 27, 1, 0, 0, 0),
                    "Europe/Moscow": b,
                    "Asia/Amman": new Date(2013, 2, 29, 1, 0, 0, 0),
                    "Asia/Beirut": new Date(2013, 2, 31, 2, 0, 0, 0),
                    "Asia/Damascus": new Date(2013, 3, 6, 2, 0, 0, 0),
                    "Asia/Jerusalem": new Date(2013, 2, 29, 5, 0, 0, 0),
                    "Asia/Yekaterinburg": b,
                    "Asia/Omsk": b,
                    "Asia/Krasnoyarsk": b,
                    "Asia/Irkutsk": b,
                    "Asia/Yakutsk": b,
                    "Asia/Vladivostok": b,
                    "Asia/Baku": new Date(2013, 2, 31, 4, 0, 0),
                    "Asia/Yerevan": new Date(2013, 2, 31, 3, 0, 0),
                    "Asia/Kamchatka": b,
                    "Asia/Gaza": new Date(2010, 2, 27, 4, 0, 0),
                    "Africa/Cairo": new Date(2010, 4, 1, 3, 0, 0),
                    "Europe/Minsk": b,
                    "Pacific/Apia": new Date(2010, 10, 1, 1, 0, 0, 0),
                    "Pacific/Fiji": new Date(2010, 11, 1, 0, 0, 0),
                    "Australia/Perth": new Date(2008, 10, 1, 1, 0, 0, 0)
                }[a]
            }
        }
    }();
    b.TimeZone = function (a) {
        var c = {
            "America/Denver": ["America/Denver", "America/Mazatlan"],
            "America/Chicago": ["America/Chicago", "America/Mexico_City"],
            "America/Santiago": ["America/Santiago", "America/Asuncion", "America/Campo_Grande"],
            "America/Montevideo": ["America/Montevideo", "America/Sao_Paulo"],
            "Asia/Beirut": ["Asia/Amman", "Asia/Jerusalem", "Asia/Beirut", "Europe/Helsinki", "Asia/Damascus"],
            "Pacific/Auckland": ["Pacific/Auckland", "Pacific/Fiji"],
            "America/Los_Angeles": ["America/Los_Angeles", "America/Santa_Isabel"],
            "America/New_York": ["America/Havana", "America/New_York"],
            "America/Halifax": ["America/Goose_Bay", "America/Halifax"],
            "America/Godthab": ["America/Miquelon", "America/Godthab"],
            "Asia/Dubai": ["Europe/Moscow"],
            "Asia/Dhaka": ["Asia/Yekaterinburg"],
            "Asia/Jakarta": ["Asia/Omsk"],
            "Asia/Shanghai": ["Asia/Krasnoyarsk", "Australia/Perth"],
            "Asia/Tokyo": ["Asia/Irkutsk"],
            "Australia/Brisbane": ["Asia/Yakutsk"],
            "Pacific/Noumea": ["Asia/Vladivostok"],
            "Pacific/Tarawa": ["Asia/Kamchatka", "Pacific/Fiji"],
            "Pacific/Tongatapu": ["Pacific/Apia"],
            "Asia/Baghdad": ["Europe/Minsk"],
            "Asia/Baku": ["Asia/Yerevan", "Asia/Baku"],
            "Africa/Johannesburg": ["Asia/Gaza", "Africa/Cairo"]
        }, d = a;
        return function () {
            return void 0 !== c[d]
        }() && function () {
            for (var a = c[d], e = a.length, f = 0,
                     g = a[0]; f < e; f += 1)if (g = a[f], b.date_is_dst(b.dst_start_for(g)))return void(d = g)
        }(), {
            name: function () {
                return d
            }
        }
    }, b.olson = {}, b.olson.timezones = {
        "-720,0": "Pacific/Majuro",
        "-660,0": "Pacific/Pago_Pago",
        "-600,1": "America/Adak",
        "-600,0": "Pacific/Honolulu",
        "-570,0": "Pacific/Marquesas",
        "-540,0": "Pacific/Gambier",
        "-540,1": "America/Anchorage",
        "-480,1": "America/Los_Angeles",
        "-480,0": "Pacific/Pitcairn",
        "-420,0": "America/Phoenix",
        "-420,1": "America/Denver",
        "-360,0": "America/Guatemala",
        "-360,1": "America/Chicago",
        "-360,1,s": "Pacific/Easter",
        "-300,0": "America/Bogota",
        "-300,1": "America/New_York",
        "-270,0": "America/Caracas",
        "-240,1": "America/Halifax",
        "-240,0": "America/Santo_Domingo",
        "-240,1,s": "America/Santiago",
        "-210,1": "America/St_Johns",
        "-180,1": "America/Godthab",
        "-180,0": "America/Argentina/Buenos_Aires",
        "-180,1,s": "America/Montevideo",
        "-120,0": "America/Noronha",
        "-120,1": "America/Noronha",
        "-60,1": "Atlantic/Azores",
        "-60,0": "Atlantic/Cape_Verde",
        "0,0": "UTC",
        "0,1": "Europe/London",
        "60,1": "Europe/Berlin",
        "60,0": "Africa/Lagos",
        "60,1,s": "Africa/Windhoek",
        "120,1": "Asia/Beirut",
        "120,0": "Africa/Johannesburg",
        "180,0": "Asia/Baghdad",
        "180,1": "Europe/Moscow",
        "210,1": "Asia/Tehran",
        "240,0": "Asia/Dubai",
        "240,1": "Asia/Baku",
        "270,0": "Asia/Kabul",
        "300,1": "Asia/Yekaterinburg",
        "300,0": "Asia/Karachi",
        "330,0": "Asia/Kolkata",
        "345,0": "Asia/Kathmandu",
        "360,0": "Asia/Dhaka",
        "360,1": "Asia/Omsk",
        "390,0": "Asia/Rangoon",
        "420,1": "Asia/Krasnoyarsk",
        "420,0": "Asia/Jakarta",
        "480,0": "Asia/Shanghai",
        "480,1": "Asia/Irkutsk",
        "525,0": "Australia/Eucla",
        "525,1,s": "Australia/Eucla",
        "540,1": "Asia/Yakutsk",
        "540,0": "Asia/Tokyo",
        "570,0": "Australia/Darwin",
        "570,1,s": "Australia/Adelaide",
        "600,0": "Australia/Brisbane",
        "600,1": "Asia/Vladivostok",
        "600,1,s": "Australia/Sydney",
        "630,1,s": "Australia/Lord_Howe",
        "660,1": "Asia/Kamchatka",
        "660,0": "Pacific/Noumea",
        "690,0": "Pacific/Norfolk",
        "720,1,s": "Pacific/Auckland",
        "720,0": "Pacific/Tarawa",
        "765,1,s": "Pacific/Chatham",
        "780,0": "Pacific/Tongatapu",
        "780,1,s": "Pacific/Apia",
        "840,0": "Pacific/Kiritimati"
    }, "undefined" != typeof exports ? exports.jstz = b : a.jstz = b
}(this);
var Datafeeds = window.Datafeeds = {}, $ = window.$, CONFIGURATION_GLOBAL = {max_bars: 8640}, SYMBOL_STORAGE = {};
Datafeeds.UDFCompatibleDatafeed = function (a, b) {
    this._datafeedURL = a, this._configuration = void 0, this._symbolSearch = null, this._symbolsStorage = null, window === window.top ? this._dataUpdater = new Datafeeds.DataPulseUpdater(this, b || 1e4) : this._dataUpdater = new Datafeeds.WebsocketUpdater(this), this._enableLogging = !1, this._initializationFinished = !1, this._callbacks = {}, this._initialize()
}, Datafeeds.UDFCompatibleDatafeed.prototype.defaultConfiguration = function () {
    return {
        supports_search: !1,
        supports_group_request: !0,
        supported_resolutions: ["1", "5", "15", "30", "60", "1D", "1W", "1M"],
        supports_marks: !1
    }
}, Datafeeds.UDFCompatibleDatafeed.prototype.on = function (a, b) {
    return this._callbacks.hasOwnProperty(a) || (this._callbacks[a] = []), this._callbacks[a].push(b), this
}, Datafeeds.UDFCompatibleDatafeed.prototype._fireEvent = function (a, b) {
    if (this._callbacks.hasOwnProperty(a)) {
        for (var c = this._callbacks[a], d = 0; d < c.length; ++d)c[d](b);
        this._callbacks[a] = []
    }
}, Datafeeds.UDFCompatibleDatafeed.prototype.onInitialized = function () {
    this._initializationFinished = !0, this._fireEvent("initialized")
}, Datafeeds.UDFCompatibleDatafeed.prototype._logMessage = function (a) {
    if (this._enableLogging) {
        var b = new Date;
        console.log(b.toLocaleTimeString() + "." + b.getMilliseconds() + "> " + a)
    }
}, Datafeeds.UDFCompatibleDatafeed.prototype._send = function (a, b) {
    var c = a;
    if (b)for (var d = 0; d < Object.keys(b).length; ++d) {
        var e = Object.keys(b)[d], f = encodeURIComponent(b[e]);
        c += (0 === d ? "?" : "&") + e + "=" + f
    }
    this._logMessage("New request: " + c);
    var g = {
        done: function (a) {
            return this._done = a, this
        }, fail: function (a) {
            return this._error = a, this
        }
    };
    return $.ajax({
        url: c, success: function () {
            g._done.apply(this, arguments)
        }, error: function () {
            g._error.apply(this, arguments)
        }
    }), g
}, Datafeeds.UDFCompatibleDatafeed.prototype._initialize = function () {
    var a = this;
    this._send(this._datafeedURL + "/config/?market="+$("#market_name").val()).done(function (b) {
        var c = JSON.parse(b);
        a._setupWithConfiguration(c)
    }).fail(function (b) {
        a._setupWithConfiguration(a.defaultConfiguration())
    })
}, Datafeeds.UDFCompatibleDatafeed.prototype.onReady = function (a) {
    if (this._configuration) {
        var b = this;
        setTimeout(function () {
            a(b._configuration)
        }, 0)
    } else {
        var b = this;
        this.on("configuration_ready", function () {
            a(b._configuration)
        })
    }
}, Datafeeds.UDFCompatibleDatafeed.prototype._setupWithConfiguration = function (a) {
    this._configuration = a, CONFIGURATION_GLOBAL = a, a.exchanges || (a.exchanges = []);
    var b = a.supported_resolutions || a.supportedResolutions;
    a.supported_resolutions = b;
    var c = a.symbols_types || a.symbolsTypes;
    if (a.symbols_types = c, !a.supports_search && !a.supports_group_request)throw"Unsupported datafeed configuration. Must either support search, or support group request";
    a.supports_search || (this._symbolSearch = new Datafeeds.SymbolSearchComponent(this)), a.supports_group_request ? this._symbolsStorage = new Datafeeds.SymbolsStorage(this) : this.onInitialized(), this._fireEvent("configuration_ready"), this._logMessage("Initialized with " + JSON.stringify(a))
}, Datafeeds.UDFCompatibleDatafeed.prototype.getMarks = function (a, b, c, d, e) {
    this._configuration.supports_marks && this._send(this._datafeedURL + "/symbols", {
        symbol: a.ticker.toUpperCase(),
        from: b,
        to: c,
        resolution: e
    }).done(function (a) {
        d(JSON.parse(a))
    }).fail(function () {
        d([])
    })
};
var LAST_SEARCH = {query: null, data: null};
Datafeeds.UDFCompatibleDatafeed.prototype.searchSymbolsByName = debounce(function (a, b, c, d) {
    if (!this._configuration)return void d([]);
    if (this._configuration.supports_search) {
        var e = a.toUpperCase().replace(/\s/g, "");
        if (LAST_SEARCH.query === e)return d(LAST_SEARCH.data);
        this._send(this._datafeedURL + "/search", {limit: 30, query: e, type: c, exchange: b}).done(function (a) {
            for (var b = JSON.parse(a), c = 0; c < b.length; ++c)b[c].params || (b[c].params = []);
            void 0 === b.s || "error" != b.s ? (LAST_SEARCH.query = e, LAST_SEARCH.data = b, d(b)) : d([])
        }).fail(function (a) {
            d([])
        })
    } else {
        if (!this._symbolSearch)throw new Error("Datafeed error: inconsistent configuration (symbol search)");
        var f = {ticker: a, exchange: b, type: c, onResultReadyCallback: d};
        if (this._initializationFinished) this._symbolSearch.searchSymbolsByName(f, 30); else {
            var g = this;
            this.on("initialized", function () {
                g._symbolSearch.searchSymbolsByName(f, 30)
            })
        }
    }
}, 500), Datafeeds.UDFCompatibleDatafeed.prototype._symbolResolveURL = "/symbols", Datafeeds.UDFCompatibleDatafeed.prototype.resolveSymbol = function (a, b, c) {
    function d(a) {
        var c = a;
        e.postProcessSymbolInfo && (c = e.postProcessSymbolInfo(c)), e._logMessage("Symbol resolved: " + (Date.now() - f)), b(c)
    }

    var e = this;
    if (!this._initializationFinished)return void this.on("initialized", function () {
        e.resolveSymbol(a, b, c)
    });
    var f = Date.now();
    e._logMessage("Resolve requested"), this._configuration.supports_group_request ? this._initializationFinished ? this._symbolsStorage.resolveSymbol(a, d, c) : this.on("initialized", function () {
        e._symbolsStorage.resolveSymbol(a, d, c)
    }) : this._send(this._datafeedURL + this._symbolResolveURL, {market: a ? a : ""}).done(function (a) {
        var b = JSON.parse(a);
        SYMBOL_STORAGE[b.symbol] = b, b.s && "ok" != b.s ? c("unknown_symbol") : d(b)
    }).fail(function (a) {
        e._logMessage("Error resolving symbol: " + JSON.stringify([a])), c("unknown_symbol")
    })
}, Datafeeds.UDFCompatibleDatafeed.prototype._historyURL = "/history", Datafeeds.UDFCompatibleDatafeed.prototype.getBars = function (a, b, c, d, e, f) {
    if (c > 0 && String(c).length > 10)throw new Error("Got a JS time instead of Unix one.");
    this._send(this._datafeedURL + this._historyURL, {
        market: a.ticker,
        resolution: b,
        from: c,
        to: d
    }).done(function (b) {
        var c = JSON.parse(b);
        if ("ok" !== c.s)return void(f && f(c.s));
        for (var d = [], g = c.t.length, h = void 0 !== c.v, i = void 0 !== c.o, j = 0; j < g; ++j) {
            var k = {time: 1e3 * c.t[j], close: c.c[j]};
            i ? (k.open = c.o[j], k.high = c.h[j], k.low = c.l[j]) : k.open = k.high = k.low = k.close, h && (k.volume = c.v[j]), d.push(k)
        }
        e(d), window.postMessage({event: "gotBars", symbol: a.name}, "*"), 0 === d.length && f("no data")
    }).fail(function (a, b, c) {
        console.warn("getBars(): HTTP error " + a.response), f && f("network error: " + a.response)
    })
}, Datafeeds.UDFCompatibleDatafeed.prototype.subscribeBars = function (a, b, c, d) {
    this._dataUpdater.subscribeDataListener(a, b, c, d)
}, Datafeeds.UDFCompatibleDatafeed.prototype.unsubscribeBars = function (a) {
    this._dataUpdater.unsubscribeDataListener(a)
};
var RESOLUTIONS = {H: 60, D: 1440, M: 43200};
Datafeeds.UDFCompatibleDatafeed.prototype.calculateHistoryDepth = function (a, b, c) {
    var d = CONFIGURATION_GLOBAL.max_bars,
        e = (CONFIGURATION_GLOBAL.intraday_multipliers, "string" == typeof a ? resStringToMinutes(a) : a),
        f = resStringToMinutes(1 + b);
    if (calculateNumBars(resStringToMinutes(c + b), e) > d) {
        var g = d / f;
        return g < 1 && "M" === b && (b = "D", f = resStringToMinutes(1 + b)), window.TV_DEBUG && console.warn("TradingView asked for too many bars. Scaling it back to " + g + b + " of " + e + "M data."), {
            resolutionBack: b,
            intervalBack: g
        }
    }
    return {resolutionBack: b, intervalBack: c}
}, Datafeeds.SymbolsStorage = function (a) {
    this._datafeed = a, this._exchangesList = ["NYSE", "FOREX", "AMEX"], this._exchangesWaitingForData = {}, this._exchangesDataCache = {}, this._symbolsInfo = {}, this._symbolsList = [], this._requestFullSymbolsList()
}, Datafeeds.SymbolsStorage.prototype._requestFullSymbolsList = function () {
    for (var a = this, b = (this._datafeed, 0); b < this._exchangesList.length; ++b) {
        var c = this._exchangesList[b];
        this._exchangesDataCache.hasOwnProperty(c) || (this._exchangesDataCache[c] = !0, this._exchangesWaitingForData[c] = "waiting_for_data", this._datafeed._send(this._datafeed._datafeedURL + "/symbols", {group: c}).done(function (b) {
            return function (c) {
                a._onExchangeDataReceived(b, JSON.parse(c)), a._onAnyExchangeResponseReceived(b)
            }
        }(c)).fail(function (b) {
            return function (c) {
                a._onAnyExchangeResponseReceived(b)
            }
        }(c)))
    }
}, Datafeeds.SymbolsStorage.prototype._onExchangeDataReceived = function (a, b) {
    function c(a, b, c) {
        return a[b] instanceof Array ? a[b][c] : a[b]
    }

    try {
        for (var d = 0; d < b.symbol.length; ++d) {
            var e = b.symbol[d], f = c(b, "exchange_listed", d), g = c(b, "exchange_traded", d), h = g + ":" + e,
                i = c(b, "has_intraday", d), j = void 0 !== b.ticker, k = {
                    name: e,
                    base_name: [f + ":" + e],
                    description: c(b, "description", d),
                    full_name: h,
                    legs: [h],
                    has_intraday: i,
                    has_no_volume: c(b, "has_no_volume", d),
                    listed_exchange: f,
                    exchange: g,
                    minmov: c(b, "minmovement", d) || c(b, "minmov", d),
                    minmove2: c(b, "minmove2", d) || c(b, "minmov2", d),
                    fractional: c(b, "fractional", d),
                    pointvalue: c(b, "pointvalue", d),
                    pricescale: c(b, "pricescale", d),
                    type: c(b, "type", d),
                    session: c(b, "session_regular", d),
                    ticker: j ? c(b, "ticker", d) : e,
                    timezone: c(b, "timezone", d),
                    supported_resolutions: c(b, "supported_resolutions", d) || this._datafeed.defaultConfiguration().supported_resolutions,
                    force_session_rebuild: c(b, "force_session_rebuild", d) || !1,
                    has_daily: c(b, "has_daily", d) || !0,
                    intraday_multipliers: c(b, "intraday_multipliers", d) || ["1", "5", "15", "30", "60"],
                    has_fractional_volume: c(b, "has_fractional_volume", d) || !1,
                    has_weekly_and_monthly: c(b, "has_weekly_and_monthly", d) || !1,
                    has_empty_bars: c(b, "has_empty_bars", d) || !1,
                    volume_precision: c(b, "volume_precision", d) || 0
                };
            this._symbolsInfo[k.ticker] = this._symbolsInfo[e] = this._symbolsInfo[h] = k, this._symbolsList.push(e)
        }
    } catch (b) {
        throw new Error("API error when processing exchange `" + a + "` symbol #" + d + ": " + b)
    }
}, Datafeeds.SymbolsStorage.prototype._onAnyExchangeResponseReceived = function (a) {
    delete this._exchangesWaitingForData[a], 0 === Object.keys(this._exchangesWaitingForData).length && (this._symbolsList.sort(), this._datafeed._logMessage("All exchanges data ready"), this._datafeed.onInitialized())
}, Datafeeds.SymbolsStorage.prototype.resolveSymbol = function (a, b, c) {
    this._symbolsInfo.hasOwnProperty(a) ? b(this._symbolsInfo[a]) : c("invalid symbol")
}, Datafeeds.SymbolSearchComponent = function (a) {
    this._datafeed = a
}, Datafeeds.SymbolSearchComponent.prototype.searchSymbolsByName = function (a, b) {
    if (!this._datafeed._symbolsStorage)throw new Error("Cannot use local symbol search when no groups information is available");
    for (var c = this._datafeed._symbolsStorage, d = [], e = !a.ticker || 0 === a.ticker.length,
             f = 0; f < c._symbolsList.length; ++f) {
        var g = c._symbolsList[f], h = c._symbolsInfo[g];
        if (!(a.type && a.type.length > 0 && h.type != a.type) && !(a.exchange && a.exchange.length > 0 && h.exchange != a.exchange) && ((e || 0 === h.name.indexOf(a.ticker)) && d.push({
                symbol: h.name,
                full_name: h.full_name,
                description: h.description,
                exchange: h.exchange,
                params: [],
                type: h.type,
                ticker: h.name
            }), d.length >= b))break
    }
    a.onResultReadyCallback(d)
}, Datafeeds.DataPulseUpdater = function (a, b) {
    this._datafeed = a, this._subscribers = {}, this._requestsPending = 0;
    var c = this, d = function () {
        if (!(c._requestsPending > 0))for (var a in c._subscribers) {
            var b = c._subscribers[a], d = b.resolution, e = Math.round(Date.now() / 1e3),
                f = e - periodLengthSeconds(d, 10);
            c._requestsPending++, function (b) {
                c._datafeed.getBars(b.symbolInfo, d, f, e, function (d) {
                    if (c._requestsPending--, c._subscribers.hasOwnProperty(a) && 0 !== d.length) {
                        var e = d[d.length - 1];
                        if (isNaN(b.lastBarTime) || !(e.time < b.lastBarTime)) {
                            var f = b.listeners;
                            if (!isNaN(b.lastBarTime) && e.time > b.lastBarTime) {
                                if (d.length < 2)throw new Error("Not enough bars in history for proper pulse update. Need at least 2.");
                                for (var g = d[d.length - 2], h = 0; h < f.length; ++h)f[h](g)
                            }
                            b.lastBarTime = e.time;
                            for (var h = 0; h < f.length; ++h)f[h](e)
                        }
                    }
                }, function () {
                    c._requestsPending--
                })
            }(b)
        }
    };
    void 0 !== b && b > 0 && setInterval(d, b)
}, Datafeeds.DataPulseUpdater.prototype.unsubscribeDataListener = function (a) {
    this._datafeed._logMessage("Unsubscribing " + a), delete this._subscribers[a]
}, Datafeeds.DataPulseUpdater.prototype.subscribeDataListener = function (a, b, c, d) {
    this._datafeed._logMessage("Subscribing " + d);
    a.name;
    this._subscribers.hasOwnProperty(d) || (this._subscribers[d] = {
        symbolInfo: a,
        resolution: b,
        lastBarTime: NaN,
        listeners: []
    }), this._subscribers[d].listeners.push(c)
}, Datafeeds.WebsocketUpdater = function (a) {
    this._datafeed = a, this._subscribers = {}
}, Datafeeds.WebsocketUpdater.prototype.subscribeDataListener = function (a, b, c, d) {
    var e = periodLengthSeconds(b, 1), f = 1e3 * e,
        g = {timeout: null, windowListener: null, symbol: a.name, cb: c, period: f};
    this._subscribers[d] = g, this._datafeed._logMessage("Subscribing " + d), g.bar = newBar(f), window.postMessage({
        event: "addStream",
        symbol: a.name
    }, "*");
    var h = function (b) {
        var d = b.data;
        if ("addTrades" === d.cmd && d.symbol === a.name) {
            var e = d.data;
            if (e && e.length) {
                for (var f = 0; f < e.length; f++)diffBar(g.bar, e[f]);
                c(g.bar)
            }
        }
    };
    window.addEventListener("message", h, !1), g.windowListener = h, this.setNewBarTimeout(g)
}, Datafeeds.WebsocketUpdater.prototype.setNewBarTimeout = function (a) {
    var b = Date.now(), c = Math.ceil(b / a.period) * a.period, d = c - b;
    a.timeout = setTimeout(function () {
        this.setNewBarTimeout(a);
        var b = a.bar;
        if (null !== b.close) {
            var c = b.close, d = a.bar = newBar(a.period);
            d.time === b.time && (d.time += a.period), d.open = d.high = d.low = d.close = c, a.cb(d)
        }
    }.bind(this), d)
}, Datafeeds.WebsocketUpdater.prototype.unsubscribeDataListener = function (a) {
    this._datafeed._logMessage("Unsubscribing " + a);
    var b = this._subscribers[a];
    clearTimeout(b && b.timeout), window.postMessage({
        event: "endStream",
        symbol: b.symbol
    }, "*"), window.removeEventListener("message", b && b.windowListener, !1), delete this._subscribers[a]
}, function () {
    function a(a) {
        "hideSymbolSearch enabledStudies enabledDrawings disabledDrawings disabledStudies disableLogo hideSideToolbar".split(" ").map(function (b) {
            a[b] && console.warn("Feature `" + b + "` is obsolete. Please see the doc for details.")
        })
    }

    if (!window.TradingView) {
        var b = {
            mobile: {
                disabledFeatures: "left_toolbar header_widget timeframes_toolbar edit_buttons_in_legend context_menus control_bar border_around_the_chart".split(" "),
                enabledFeatures: ["narrow_chart_enabled"]
            }
        }, c = {
            BARS: 0, CANDLES: 1, LINE: 2, AREA: 3, HEIKEN_ASHI: 8, HOLLOW_CANDLES: 9, version: function () {
                return "1.6 dev (internal id f4b00b4a @ 2016-04-08 03:06:16.622908)"
            }, gEl: function (a) {
                return document.getElementById(a)
            }, gId: function () {
                return "tradingview_" + (1048576 * (1 + Math.random()) | 0).toString(16).substring(1)
            }, onready: function (a) {
                window.addEventListener ? window.addEventListener("DOMContentLoaded", a, !1) : window.attachEvent("onload", a)
            }, css: function (a) {
                var b = document.getElementsByTagName("head")[0], c = document.createElement("style");
                c.type = "text/css", c.styleSheet ? c.styleSheet.cssText = a : (a = document.createTextNode(a), c.appendChild(a)), b.appendChild(c)
            }, bindEvent: function (a, b, c) {
                a.addEventListener ? a.addEventListener(b, c, !1) : a.attachEvent && a.attachEvent("on" + b, c)
            }, unbindEvent: function (a, b, c) {
                a.removeEventListener ? a.removeEventListener(b, c, !1) : a.detachEvent && a.detachEvent("on" + b, c)
            }, widget: function (d) {
                if (this.id = c.gId(), !d.datafeed)throw"Datafeed is not defined";
                var e = {
                    width: 800,
                    height: 500,
                    symbol: "AA",
                    interval: "D",
                    timezone: "",
                    container: "",
                    path: "",
                    locale: "en",
                    toolbar_bg: void 0,
                    hideSymbolSearch: !1,
                    hideSideToolbar: !1,
                    enabledStudies: [],
                    enabledDrawings: [],
                    disabledDrawings: [],
                    disabledStudies: [],
                    drawingsAccess: void 0,
                    studiesAccess: void 0,
                    widgetbar: {datawindow: !1, details: !1, watchlist: !1, watchlist_settings: {default_symbols: []}},
                    overrides: {"mainSeriesProperties.showCountdown": !1},
                    studiesOverrides: {},
                    fullscreen: !1,
                    autosize: !1,
                    disabledFeatures: [],
                    enabledFeatures: [],
                    indicators_file_name: null,
                    custom_css_url: null,
                    auto_save_delay: null,
                    debug: !1,
                    time_frames: [{text: "5y", resolution: "W"}, {text: "1y", resolution: "W"}, {
                        text: "6m",
                        resolution: "120"
                    }, {text: "3m", resolution: "60"}, {text: "1m", resolution: "30"}, {
                        text: "5d",
                        resolution: "5"
                    }, {text: "1d", resolution: "1"}],
                    client_id: "0",
                    user_id: "0",
                    charts_storage_url: void 0,
                    charts_storage_api_version: "1.0",
                    logo: {},
                    favorites: {intervals: [], chartTypes: []},
                    rss_news_feed: null,
                    theme: d.theme
                }, f = {
                    width: d.width,
                    height: d.height,
                    symbol: d.symbol,
                    interval: d.interval,
                    timezone: d.timezone,
                    container: d.container_id,
                    path: d.library_path,
                    locale: d.locale,
                    toolbar_bg: d.toolbar_bg,
                    hideSymbolSearch: d.hide_symbol_search || d.hideSymbolSearch,
                    hideSideToolbar: d.hide_side_toolbar,
                    enabledStudies: d.enabled_studies,
                    disabledStudies: d.disabled_studies,
                    enabledDrawings: d.enabled_drawings,
                    disabledDrawings: d.disabled_drawings,
                    drawingsAccess: d.drawings_access,
                    studiesAccess: d.studies_access,
                    widgetbar: d.widgetbar,
                    overrides: d.overrides,
                    studiesOverrides: d.studies_overrides,
                    savedData: d.saved_data || d.savedData,
                    snapshotUrl: d.snapshot_url,
                    uid: this.id,
                    datafeed: d.datafeed,
                    tradingController: d.trading_controller,
                    disableLogo: d.disable_logo || d.disableLogo,
                    logo: d.logo,
                    autosize: d.autosize,
                    fullscreen: d.fullscreen,
                    disabledFeatures: d.disabled_features,
                    enabledFeatures: d.enabled_features,
                    indicators_file_name: d.indicators_file_name,
                    custom_css_url: d.custom_css_url,
                    auto_save_delay: d.auto_save_delay,
                    debug: d.debug,
                    client_id: d.client_id,
                    user_id: d.user_id,
                    charts_storage_url: d.charts_storage_url,
                    charts_storage_api_version: d.charts_storage_api_version,
                    favorites: d.favorites,
                    numeric_formatting: d.numeric_formatting,
                    rss_news_feed: d.rss_news_feed,
                    studyCountLimit: d.study_count_limit,
                    symbolSearchRequestDelay: d.symbol_search_request_delay
                };
                a(f), this.options = $.extend(!0, e, f), this.options.time_frames = d.time_frames || e.time_frames, d.preset && (d = d.preset, b[d] ? (d = b[d], this.options.disabledFeatures = 0 < this.options.disabledFeatures.length ? this.options.disabledFeatures.concat(d.disabledFeatures) : d.disabledFeatures, this.options.enabledFeatures = 0 < this.options.enabledFeatures.length ? this.options.enabledFeatures.concat(d.enabledFeatures) : d.enabledFeatures) : console.warn("Unknown preset: `" + d + "`")), this._ready_handlers = [], this.create()
            }
        };
        c.widget.prototype = {
            _innerWindow: function () {
                return c.gEl(this.id).contentWindow
            }, _autoResizeChart: function () {
                this.options.fullscreen && $(c.gEl(this.id)).css("height", $(window).height() + "px")
            }, create: function () {
                function a(a) {
                    f.load(JSON.parse(a.content), a)
                }

                function b() {
                    c.gEl(f.id).contentWindow.TradingView.GlobalEventsStorage.subscribe("chart_load_requested", a)
                }

                var d, e = this.render(), f = this;
                if (this.options.container) {
                    var g = c.gEl(this.options.container);
                    g.innerHTML = e
                } else document.write(e);
                (this.options.autosize || this.options.fullscreen) && (g = $(c.gEl(this.id)), g.css("width", "100%"), this.options.fullscreen || g.css("height", "100%")), this._autoResizeChart(), this._onWindowResize = function (a) {
                    f._autoResizeChart()
                }, window.addEventListener("resize", this._onWindowResize), this.unsubscribeFromLoadRequestEvent = function () {
                    c.gEl(f.id).contentWindow.TradingView.GlobalEventsStorage.unsubscribe("chart_load_requested", a)
                }, d = c.gEl(this.id);
                var h = null;
                h = function () {
                    /*
                    c.unbindEvent(d, "load", h), d.contentWindow.widgetReady(function () {
                        var a;
                        for (f._ready = !0, a = f._ready_handlers.length; a--;)f._ready_handlers[a].call(f);
                        d.contentWindow._initializationFinished();
                        var e = c.gEl(f.id).contentWindow;
                        if (e.TradingView.GlobalEventsStorage) b(); else {
                            var g = null;
                            g = function () {
                                b(), c.unbindEvent(e, "load", g)
                            }, c.bindEvent(e, "load", g)
                        }
                    })*/
                }, c.bindEvent(d, "load", h)
            }, render: function () {
                window[this.options.uid] = {
                    datafeed: this.options.datafeed,
                    tradingController: this.options.tradingController,
                    overrides: this.options.overrides,
                    studiesOverrides: this.options.studiesOverrides,
                    disabledFeatures: this.options.disabledFeatures,
                    enabledFeatures: this.options.enabledFeatures,
                    enabledDrawings: this.options.enabledDrawings,
                    disabledDrawings: this.options.disabledDrawings,
                    favorites: this.options.favorites,
                    logo: this.options.logo,
                    numeric_formatting: this.options.numeric_formatting,
                    rss_news_feed: this.options.rss_news_feed
                }, this.options.savedData && (window[this.options.uid].chartContent = {json: this.options.savedData})
                ;var a = (this.options.path || "") + "tradingview/?market="+$("#market_name").val()+"&tv=chart&version=" + encodeURIComponent(c.version()) + "#localserver=1&symbol=" + encodeURIComponent(this.options.symbol) + "&interval=" + encodeURIComponent(this.options.interval) + (this.options.toolbar_bg ? "&toolbarbg=" + this.options.toolbar_bg.replace("#", "") : "") + "&hideSymbolSearch=" + this.options.hideSymbolSearch + "&hideSideToolbar=" + this.options.hideSideToolbar + "&enabledStudies=" + encodeURIComponent(JSON.stringify(this.options.enabledStudies)) + "&disabledStudies=" + encodeURIComponent(JSON.stringify(this.options.disabledStudies)) + (this.options.studiesAccess ? "&studiesAccess=" + encodeURIComponent(JSON.stringify(this.options.studiesAccess)) : "") + "&widgetbar=" + encodeURIComponent(JSON.stringify(this.options.widgetbar)) + (this.options.drawingsAccess ? "&drawingsAccess=" + encodeURIComponent(JSON.stringify(this.options.drawingsAccess)) : "") + "&timeFrames=" + encodeURIComponent(JSON.stringify(this.options.time_frames)) + (this.options.hasOwnProperty("disableLogo") ? "&disableLogo=" + encodeURIComponent(this.options.disableLogo) : "") + "&locale=" + encodeURIComponent(this.options.locale) + "&uid=" + encodeURIComponent(this.options.uid) + "&clientId=" + encodeURIComponent(this.options.client_id) + "&userId=" + encodeURIComponent(this.options.user_id) + (this.options.charts_storage_url ? "&chartsStorageUrl=" + encodeURIComponent(this.options.charts_storage_url) : "") + (this.options.charts_storage_api_version ? "&chartsStorageVer=" + encodeURIComponent(this.options.charts_storage_api_version) : "") + (this.options.indicators_file_name ? "&indicatorsFile=" + encodeURIComponent(this.options.indicators_file_name) : "") + (this.options.custom_css_url ? "&customCSS=" + encodeURIComponent(this.options.custom_css_url) : "") + (this.options.auto_save_delay ? "&autoSaveDelay=" + encodeURIComponent(this.options.auto_save_delay) : "") + "&debug=" + this.options.debug + (this.options.snapshotUrl ? "&snapshotUrl=" + encodeURIComponent(this.options.snapshotUrl) : "") + (this.options.timezone ? "&timezone=" + encodeURIComponent(this.options.timezone) : "") + (this.options.studyCountLimit ? "&studyCountLimit=" + encodeURIComponent(this.options.studyCountLimit) : "") + (this.options.symbolSearchRequestDelay ? "&ssreqdelay=" + encodeURIComponent(this.options.symbolSearchRequestDelay) : "") + (this.options.theme ? "&theme=" + this.options.theme : "");
                return '<iframe id="' + this.id + '" name="' + this.id + '"  src="' + a + '"' + (this.options.autosize || this.options.fullscreen ? "" : ' width="' + this.options.width + '" height="' + this.options.height + '"') + ' frameborder="0" allowTransparency="true" scrolling="no" allowfullscreen style="display:block;"></iframe>'
            }, onChartReady: function (a) {
                this._ready ? a.call(this) : this._ready_handlers.push(a)
            }, setSymbol: function (a, b, c) {
                this._innerWindow().changeSymbol(a, b + "", c)
            }, layout: function () {
                return this._innerWindow().layout()
            }, setLayout: function (a) {
                return this._innerWindow().setLayout(a)
            }, chartsCount: function (a) {
                return this._innerWindow().chartsCount(a)
            }, chart: function (a) {
                return this._innerWindow().chart(a)
            }, activeChart: function () {
                return this._innerWindow().activeChart()
            }, _widgetResizeTimer: null, createButton: function (a) {
                var b = this._innerWindow().$, c = this;
                a = a || {};
                var d = a.align || "left";
                return a = this._innerWindow().headerWidget, d = "left" == d ? a._$left : a._$right, a = a.createGroup({single: !0}).appendTo(d), a = b('<div class="button"></div>').appendTo(a), this._widgetResizeTimer && clearTimeout(this._widgetResizeTimer), this._widgetResizeTimer = setTimeout(function () {
                    c._innerWindow().resizeWindow(), clearTimeout(c._widgetResizeTimer)
                }, 5), a
            }, symbolInterval: function (a) {
                var b = this._innerWindow().getSymbolInterval();
                return a && a(b), b
            }, onSymbolChange: function (a) {
                this._innerWindow().setCallback("onSymbolChange", a)
            }, onIntervalChange: function (a) {
                this._innerWindow().setCallback("onIntervalChange", a)
            }, onTick: function (a) {
                this._innerWindow().setCallback("onTick", a)
            }, remove: function () {
                window.removeEventListener("resize", this._onWindowResize), delete window[this.options.uid];
                var a = c.gEl(this.id);
                a.contentWindow.destroyChart(), this.unsubscribeFromLoadRequestEvent(), a.parentNode.removeChild(a)
            }, getVisibleRange: function (a) {
                var b = this.activeChart().getVisibleRange();
                return a && a(b), b
            }, onAutoSaveNeeded: function (a) {
                this._innerWindow().setCallback("onAutoSaveNeeded", a)
            }, onMarkClick: function (a) {
                this._innerWindow().setCallback("onMarkClick", a)
            }, onBarMarkClicked: function (a) {
                this._innerWindow().setCallback("onMarkClick", a)
            }, onTimescaleMarkClicked: function (a) {
                this._innerWindow().setCallback("onTimescaleMarkClick", a)
            }, subscribe: function (a, b) {
                this._innerWindow().setCallback(a, b)
            }, onScreenshotReady: function (a) {
                this._innerWindow().setCallback("onScreenshotReady", a)
            }, onContextMenu: function (a) {
                this._innerWindow().TradingView.GlobalEventsStorage.subscribe("onContextMenu", function (b) {
                    b.callback(a(b.unixtime, b.price))
                })
            }, onShortcut: function (a, b) {
                this._innerWindow().createShortcutAction(a, b)
            }, onGrayedObjectClicked: function (a) {
                this._innerWindow().TradingView.GlobalEventsStorage.subscribe("onGrayedObjectClicked", a)
            }, closePopupsAndDialogs: function () {
                this._innerWindow().closePopupsAndDialogs()
            }, applyOverrides: function (a) {
                this.options = $.extend(!0, this.options, {overrides: a}), this._innerWindow().applyOverrides(a)
            }, createStudyTemplate: function (a, b) {
                var c = this.activeChart().createStudyTemplate(a);
                return b && b(c), c
            }, addCustomCSSFile: function (a) {
                this._innerWindow().addCustomCSSFile(a)
            }, save: function (a) {
                this._innerWindow().saveChart(a)
            }, load: function (a, b) {
                this._innerWindow().loadChart({json: a, extendedData: b})
            }, setLanguage: function (a) {
                this.remove(), this.options.locale = a, this.create()
            }, isFloatingTradingPanelVisible: function () {
                return this._innerWindow().isFloatingTradingPanelVisible()
            }, toggleFloatingTradingPanel: function () {
                this._innerWindow().toggleFloatingTradingPanel()
            }, isBottomTradingPanelVisible: function () {
                return this._innerWindow().isBottomTradingPanelVisible()
            }, toggleBottomTradingPanel: function () {
                this._innerWindow().toggleBottomTradingPanel()
            }, showSampleOrderDialog: function (a) {
                return this._innerWindow().showSampleOrderDialog(a)
            }, showSampleClosePositionDialog: function (a) {
                return this._innerWindow().showSampleClosePositionDialog(a)
            }, showSampleReversePositionDialog: function (a) {
                return this._innerWindow().showSampleReversePositionDialog(a)
            }, mainSeriesPriceFormatter: function () {
                return this._innerWindow().mainSeriesPriceFormatter()
            }
        }, "applyStudyTemplate setChartType clearMarks refreshMarks setVisibleRange createOrderLine createPositionLine createExecutionShape createVerticalLine createMultipointShape createShape removeEntity createStudy removeAllShapes removeAllStudies executeActionById executeAction".split(" ").forEach(function (a) {
            c.widget.prototype[a] = function () {
                return console.warn("Method `" + a + "` is obsolete. Please use `chart()." + a + "` instead."), this.activeChart()[a].apply(this.activeChart(), arguments)
            }
        }), window.TradingView && jQuery ? jQuery.extend(window.TradingView, c) : window.TradingView = c
    }
}(), function () {
    function a() {
        try {
            return window.self !== window.top && "/app/chart" !== window.parent.location.pathname
        } catch (a) {
            return !0
        }
    }

    function b(a) {
        for (var b = {}, c = a.substr(1).split("&"), d = 0; d < c.length; d++) {
            var e = c[d].split("=");
            b[decodeURIComponent(e[0])] = decodeURIComponent(e[1] || "")
        }
        return b
    }

    function c(a) {
        a = a.replace("-", "_");
        for (var b = 0; b < m.length; b++)if (a === m[b])return m[b];
        var c = a.split("_")[0];
        for (b = 0; b < m.length; b++)if (c === m[b].split("_")[0])return m[b];
        return "en"
    }

    function d(a) {
        a.onAutoSaveNeeded(function () {
            a.save(function (a) {
                var b = a.charts[0];
                l.set(k, {charts: [{panes: b.panes, timeScale: b.timeScale}]})
            })
        })
    }

    function e(a) {
        var b = l.get(k);
        if (b && b.charts) {
            try {
                b.charts[0].panes[0].sources[0].state.symbol = b.charts[0].panes[0].sources[0].state.shortName = a
            } catch (a) {
                return console.error("Couldn't set override symbol.", a), null
            }
            return b
        }
    }

    function f(a) {
        var b = "light" === j ? "dark" : "light";
        try {
            var c = window.TVColors[j].overrides, d = window.TVColors[b].overrides, e = /(?:color|background)/i;
            for (var f in c)if (e.test(f))try {
                if (g(a, f) !== d[f])continue;
                h(a, f, c[f])
            } catch (a) {
            }
        } catch (a) {
            console.error("Unable to reset chart properties.", a)
        }
        return a || {}
    }

    function g(a, b) {
        for (var c = b.split("."), d = 0; d < c.length; d++)a = a[c[d]];
        return a
    }

    function h(a, b, c) {
        for (var d = b.split("."), e = 0; e < d.length - 1; e++)a = a[d[e]];
        a[d[d.length - 1]] = c
    }

    var i = function (a) {
        var c = b(a);
        return c.theme || (c.theme = "light"), c.locale || (c.locale = "en"), c
    }(window.location.search), j = i.theme || "light", k = "tradingview.savedChartData", l = window.betterLS;
    window.initTV = function () {
        var b = window.jstz.determine().name(), g = i.symbol || document.body.getAttribute("data-symbol");
        window.TVDataFeed || (window.TVDataFeed = new Datafeeds.UDFCompatibleDatafeed("/trade", 6e4));
        var h = l.get("tradingview.lastTheme");
        if (h && h !== j)try {
            var m = l.get("tradingview.chartproperties");statics
            m = f(m), l.set("tradingview.chartproperties", m)
        } catch (a) {
            console.error("Unable to prepare saved tradingView data.", a)
        }
        l.set("tradingview.lastTheme", j);
        try {
            var n = k + "." + j, o = l.get(n);
            o && (l.set(k, o), l.remove(n))
        } catch (a) {
        }
        var p = {
            user_id: i.userID || "anonymous",
            symbol: g,
            timezone: b || "UTC",
            locale: c(i.locale),
            theme: j,
            datafeed: window.TVDataFeed
        };
        $.extend(!0, p, a() ? window.TVEmbedSettings : {}, window.TVSettings, window.TVColors[j]), p.savedData = e(g);
        for (var q = ["enabled_features", "disabled_features", "time_frames"], r = 0; r < q.length; r++) {
            var s = q[r];
            p[s] = [].concat(a() && window.TVEmbedSettings[s] || []).concat(window.TVSettings[s] || []).concat(window.TVColors[j][s] || [])
        }
        var t = new TradingView.widget(p);
        return t.onChartReady(function () {
            if (d(t), $(".loading-indicator").remove(), a()) {
                $($("iframe")[0].contentDocument).find(".pane-legend-minbtn svg.expand").not(".closed").click();
                try {
                    t.createButton().addClass("iconed fullscreen apply-common-tooltip").css("margin-left", 0).attr("title", "Open in New Window").on("click", function (a) {
                        var b = t.symbolInterval().symbol;
                        window.open("/app/chart?symbol=" + b, "_blank")
                    }).append($("<i></i>"))
                } catch (a) {
                    console.error(a)
                }
            }
        }), t
    };
    var m = ["zh", "zh_TW", "nl_NL", "en", "fr", "de_DE", "el", "it", "ja", "ko", "fa_IR", "pt", "ru", "es", "th_TH", "tr", "vi"];
    window.TVWidget = window.initTV()
}();