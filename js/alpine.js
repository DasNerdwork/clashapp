(() => {
    var tt = !1,
        rt = !1,
        V = [],
        nt = -1;
    function Vt(e) {
        Sn(e);
    }
    function Sn(e) {
        V.includes(e) || V.push(e), An();
    }
    function Ee(e) {
        let t = V.indexOf(e);
        t !== -1 && t > nt && V.splice(t, 1);
    }
    function An() {
        !rt && !tt && ((tt = !0), queueMicrotask(On));
    }
    function On() {
        (tt = !1), (rt = !0);
        for (let e = 0; e < V.length; e++) V[e](), (nt = e);
        (V.length = 0), (nt = -1), (rt = !1);
    }
    var T,
        k,
        $,
        ot,
        it = !0;
    function qt(e) {
        (it = !1), e(), (it = !0);
    }
    function Ut(e) {
        (T = e.reactive),
            ($ = e.release),
            (k = (t) =>
                e.effect(t, {
                    scheduler: (r) => {
                        it ? Vt(r) : r();
                    },
                })),
            (ot = e.raw);
    }
    function st(e) {
        k = e;
    }
    function Wt(e) {
        let t = () => {};
        return [
            (n) => {
                let i = k(n);
                return (
                    e._x_effects ||
                        ((e._x_effects = new Set()),
                        (e._x_runEffects = () => {
                            e._x_effects.forEach((o) => o());
                        })),
                    e._x_effects.add(i),
                    (t = () => {
                        i !== void 0 && (e._x_effects.delete(i), $(i));
                    }),
                    i
                );
            },
            () => {
                t();
            },
        ];
    }
    function q(e, t, r = {}) {
        e.dispatchEvent(new CustomEvent(t, { detail: r, bubbles: !0, composed: !0, cancelable: !0 }));
    }
    function O(e, t) {
        if (typeof ShadowRoot == "function" && e instanceof ShadowRoot) {
            Array.from(e.children).forEach((i) => O(i, t));
            return;
        }
        let r = !1;
        if ((t(e, () => (r = !0)), r)) return;
        let n = e.firstElementChild;
        for (; n; ) O(n, t, !1), (n = n.nextElementSibling);
    }
    function v(e, ...t) {
        console.warn(`Alpine Warning: ${e}`, ...t);
    }
    var Gt = !1;
    function Jt() {
        Gt && v("Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems."),
            (Gt = !0),
            document.body || v("Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?"),
            q(document, "alpine:init"),
            q(document, "alpine:initializing"),
            ce(),
            rr((t) => S(t, O)),
            Q((t) => ae(t)),
            Ae((t, r) => {
                le(t, r).forEach((n) => n());
            });
        let e = (t) => !U(t.parentElement, !0);
        Array.from(document.querySelectorAll(Zt().join(",")))
            .filter(e)
            .forEach((t) => {
                S(t);
            }),
            q(document, "alpine:initialized");
    }
    var at = [],
        Yt = [];
    function Xt() {
        return at.map((e) => e());
    }
    function Zt() {
        return at.concat(Yt).map((e) => e());
    }
    function ve(e) {
        at.push(e);
    }
    function Se(e) {
        Yt.push(e);
    }
    function U(e, t = !1) {
        return Z(e, (r) => {
            if ((t ? Zt() : Xt()).some((i) => r.matches(i))) return !0;
        });
    }
    function Z(e, t) {
        if (e) {
            if (t(e)) return e;
            if ((e._x_teleportBack && (e = e._x_teleportBack), !!e.parentElement)) return Z(e.parentElement, t);
        }
    }
    function Qt(e) {
        return Xt().some((t) => e.matches(t));
    }
    var er = [];
    function tr(e) {
        er.push(e);
    }
    function S(e, t = O, r = () => {}) {
        ir(() => {
            t(e, (n, i) => {
                r(n, i), er.forEach((o) => o(n, i)), le(n, n.attributes).forEach((o) => o()), n._x_ignore && i();
            });
        });
    }
    function ae(e) {
        O(e, (t) => {
            ct(t), nr(t);
        });
    }
    var or = [],
        sr = [],
        ar = [];
    function rr(e) {
        ar.push(e);
    }
    function Q(e, t) {
        typeof t == "function" ? (e._x_cleanups || (e._x_cleanups = []), e._x_cleanups.push(t)) : ((t = e), sr.push(t));
    }
    function Ae(e) {
        or.push(e);
    }
    function Ce(e, t, r) {
        e._x_attributeCleanups || (e._x_attributeCleanups = {}), e._x_attributeCleanups[t] || (e._x_attributeCleanups[t] = []), e._x_attributeCleanups[t].push(r);
    }
    function ct(e, t) {
        e._x_attributeCleanups &&
            Object.entries(e._x_attributeCleanups).forEach(([r, n]) => {
                (t === void 0 || t.includes(r)) && (n.forEach((i) => i()), delete e._x_attributeCleanups[r]);
            });
    }
    function nr(e) {
        if (e._x_cleanups) for (; e._x_cleanups.length; ) e._x_cleanups.pop()();
    }
    var ut = new MutationObserver(mt),
        ft = !1;
    function ce() {
        ut.observe(document, { subtree: !0, childList: !0, attributes: !0, attributeOldValue: !0 }), (ft = !0);
    }
    function dt() {
        Cn(), ut.disconnect(), (ft = !1);
    }
    var ue = [],
        lt = !1;
    function Cn() {
        (ue = ue.concat(ut.takeRecords())),
            ue.length &&
                !lt &&
                ((lt = !0),
                queueMicrotask(() => {
                    Tn(), (lt = !1);
                }));
    }
    function Tn() {
        mt(ue), (ue.length = 0);
    }
    function h(e) {
        if (!ft) return e();
        dt();
        let t = e();
        return ce(), t;
    }
    var pt = !1,
        Oe = [];
    function cr() {
        pt = !0;
    }
    function lr() {
        (pt = !1), mt(Oe), (Oe = []);
    }
    function mt(e) {
        if (pt) {
            Oe = Oe.concat(e);
            return;
        }
        let t = [],
            r = [],
            n = new Map(),
            i = new Map();
        for (let o = 0; o < e.length; o++)
            if (
                !e[o].target._x_ignoreMutationObserver &&
                (e[o].type === "childList" && (e[o].addedNodes.forEach((s) => s.nodeType === 1 && t.push(s)), e[o].removedNodes.forEach((s) => s.nodeType === 1 && r.push(s))), e[o].type === "attributes")
            ) {
                let s = e[o].target,
                    a = e[o].attributeName,
                    c = e[o].oldValue,
                    l = () => {
                        n.has(s) || n.set(s, []), n.get(s).push({ name: a, value: s.getAttribute(a) });
                    },
                    u = () => {
                        i.has(s) || i.set(s, []), i.get(s).push(a);
                    };
                s.hasAttribute(a) && c === null ? l() : s.hasAttribute(a) ? (u(), l()) : u();
            }
        i.forEach((o, s) => {
            ct(s, o);
        }),
            n.forEach((o, s) => {
                or.forEach((a) => a(s, o));
            });
        for (let o of r) t.includes(o) || (sr.forEach((s) => s(o)), ae(o));
        t.forEach((o) => {
            (o._x_ignoreSelf = !0), (o._x_ignore = !0);
        });
        for (let o of t) r.includes(o) || (o.isConnected && (delete o._x_ignoreSelf, delete o._x_ignore, ar.forEach((s) => s(o)), (o._x_ignore = !0), (o._x_ignoreSelf = !0)));
        t.forEach((o) => {
            delete o._x_ignoreSelf, delete o._x_ignore;
        }),
            (t = null),
            (r = null),
            (n = null),
            (i = null);
    }
    function Te(e) {
        return F(j(e));
    }
    function N(e, t, r) {
        return (
            (e._x_dataStack = [t, ...j(r || e)]),
            () => {
                e._x_dataStack = e._x_dataStack.filter((n) => n !== t);
            }
        );
    }
    function j(e) {
        return e._x_dataStack ? e._x_dataStack : typeof ShadowRoot == "function" && e instanceof ShadowRoot ? j(e.host) : e.parentNode ? j(e.parentNode) : [];
    }
    function F(e) {
        return new Proxy({ objects: e }, Rn);
    }
    var Rn = {
        ownKeys({ objects: e }) {
            return Array.from(new Set(e.flatMap((t) => Object.keys(t))));
        },
        has({ objects: e }, t) {
            return t == Symbol.unscopables ? !1 : e.some((r) => Object.prototype.hasOwnProperty.call(r, t));
        },
        get({ objects: e }, t, r) {
            return t == "toJSON" ? Mn : Reflect.get(e.find((n) => Object.prototype.hasOwnProperty.call(n, t)) || {}, t, r);
        },
        set({ objects: e }, t, r, n) {
            let i = e.find((s) => Object.prototype.hasOwnProperty.call(s, t)) || e[e.length - 1],
                o = Object.getOwnPropertyDescriptor(i, t);
            return o?.set && o?.get ? Reflect.set(i, t, r, n) : Reflect.set(i, t, r);
        },
    };
    function Mn() {
        return Reflect.ownKeys(this).reduce((t, r) => ((t[r] = Reflect.get(this, r)), t), {});
    }
    function Re(e) {
        let t = (n) => typeof n == "object" && !Array.isArray(n) && n !== null,
            r = (n, i = "") => {
                Object.entries(Object.getOwnPropertyDescriptors(n)).forEach(([o, { value: s, enumerable: a }]) => {
                    if (a === !1 || s === void 0) return;
                    let c = i === "" ? o : `${i}.${o}`;
                    typeof s == "object" && s !== null && s._x_interceptor ? (n[o] = s.initialize(e, c, o)) : t(s) && s !== n && !(s instanceof Element) && r(s, c);
                });
            };
        return r(e);
    }
    function Me(e, t = () => {}) {
        let r = {
            initialValue: void 0,
            _x_interceptor: !0,
            initialize(n, i, o) {
                return e(
                    this.initialValue,
                    () => Nn(n, i),
                    (s) => ht(n, i, s),
                    i,
                    o
                );
            },
        };
        return (
            t(r),
            (n) => {
                if (typeof n == "object" && n !== null && n._x_interceptor) {
                    let i = r.initialize.bind(r);
                    r.initialize = (o, s, a) => {
                        let c = n.initialize(o, s, a);
                        return (r.initialValue = c), i(o, s, a);
                    };
                } else r.initialValue = n;
                return r;
            }
        );
    }
    function Nn(e, t) {
        return t.split(".").reduce((r, n) => r[n], e);
    }
    function ht(e, t, r) {
        if ((typeof t == "string" && (t = t.split(".")), t.length === 1)) e[t[0]] = r;
        else {
            if (t.length === 0) throw error;
            return e[t[0]] || (e[t[0]] = {}), ht(e[t[0]], t.slice(1), r);
        }
    }
    var ur = {};
    function y(e, t) {
        ur[e] = t;
    }
    function fe(e, t) {
        return (
            Object.entries(ur).forEach(([r, n]) => {
                let i = null;
                function o() {
                    if (i) return i;
                    {
                        let [s, a] = _t(t);
                        return (i = { interceptor: Me, ...s }), Q(t, a), i;
                    }
                }
                Object.defineProperty(e, `$${r}`, {
                    get() {
                        return n(t, o());
                    },
                    enumerable: !1,
                });
            }),
            e
        );
    }
    function fr(e, t, r, ...n) {
        try {
            return r(...n);
        } catch (i) {
            ee(i, e, t);
        }
    }
    function ee(e, t, r = void 0) {
        Object.assign(e, { el: t, expression: r }),
            console.warn(
                `Alpine Expression Error: ${e.message}

${
    r
        ? 'Expression: "' +
          r +
          `"

`
        : ""
}`,
                t
            ),
            setTimeout(() => {
                throw e;
            }, 0);
    }
    var Ne = !0;
    function Ie(e) {
        let t = Ne;
        Ne = !1;
        let r = e();
        return (Ne = t), r;
    }
    function R(e, t, r = {}) {
        let n;
        return x(e, t)((i) => (n = i), r), n;
    }
    function x(...e) {
        return dr(...e);
    }
    var dr = xt;
    function pr(e) {
        dr = e;
    }
    function xt(e, t) {
        let r = {};
        fe(r, e);
        let n = [r, ...j(e)],
            i = typeof t == "function" ? Pn(n, t) : Dn(n, t, e);
        return fr.bind(null, e, t, i);
    }
    function Pn(e, t) {
        return (r = () => {}, { scope: n = {}, params: i = [] } = {}) => {
            let o = t.apply(F([n, ...e]), i);
            Pe(r, o);
        };
    }
    var gt = {};
    function In(e, t) {
        if (gt[e]) return gt[e];
        let r = Object.getPrototypeOf(async function () {}).constructor,
            n = /^[\n\s]*if.*\(.*\)/.test(e.trim()) || /^(let|const)\s/.test(e.trim()) ? `(async()=>{ ${e} })()` : e,
            o = (() => {
                try {
                    let s = new r(["__self", "scope"], `with (scope) { __self.result = ${n} }; __self.finished = true; return __self.result;`);
                    return Object.defineProperty(s, "name", { value: `[Alpine] ${e}` }), s;
                } catch (s) {
                    return ee(s, t, e), Promise.resolve();
                }
            })();
        return (gt[e] = o), o;
    }
    function Dn(e, t, r) {
        let n = In(t, r);
        return (i = () => {}, { scope: o = {}, params: s = [] } = {}) => {
            (n.result = void 0), (n.finished = !1);
            let a = F([o, ...e]);
            if (typeof n == "function") {
                let c = n(n, a).catch((l) => ee(l, r, t));
                n.finished
                    ? (Pe(i, n.result, a, s, r), (n.result = void 0))
                    : c
                          .then((l) => {
                              Pe(i, l, a, s, r);
                          })
                          .catch((l) => ee(l, r, t))
                          .finally(() => (n.result = void 0));
            }
        };
    }
    function Pe(e, t, r, n, i) {
        if (Ne && typeof t == "function") {
            let o = t.apply(r, n);
            o instanceof Promise ? o.then((s) => Pe(e, s, r, n)).catch((s) => ee(s, i, t)) : e(o);
        } else typeof t == "object" && t instanceof Promise ? t.then((o) => e(o)) : e(t);
    }
    var Et = "x-";
    function C(e = "") {
        return Et + e;
    }
    function mr(e) {
        Et = e;
    }
    var yt = {};
    function d(e, t) {
        return (
            (yt[e] = t),
            {
                before(r) {
                    if (!yt[r]) {
                        console.warn("Cannot find directive `${directive}`. `${name}` will use the default order of execution");
                        return;
                    }
                    let n = W.indexOf(r);
                    W.splice(n >= 0 ? n : W.indexOf("DEFAULT"), 0, e);
                },
            }
        );
    }
    function le(e, t, r) {
        if (((t = Array.from(t)), e._x_virtualDirectives)) {
            let o = Object.entries(e._x_virtualDirectives).map(([a, c]) => ({ name: a, value: c })),
                s = vt(o);
            (o = o.map((a) => (s.find((c) => c.name === a.name) ? { name: `x-bind:${a.name}`, value: `"${a.value}"` } : a))), (t = t.concat(o));
        }
        let n = {};
        return t
            .map(_r((o, s) => (n[o] = s)))
            .filter(xr)
            .map(Ln(n, r))
            .sort($n)
            .map((o) => kn(e, o));
    }
    function vt(e) {
        return Array.from(e)
            .map(_r())
            .filter((t) => !xr(t));
    }
    var bt = !1,
        de = new Map(),
        hr = Symbol();
    function ir(e) {
        bt = !0;
        let t = Symbol();
        (hr = t), de.set(t, []);
        let r = () => {
                for (; de.get(t).length; ) de.get(t).shift()();
                de.delete(t);
            },
            n = () => {
                (bt = !1), r();
            };
        e(r), n();
    }
    function _t(e) {
        let t = [],
            r = (a) => t.push(a),
            [n, i] = Wt(e);
        return t.push(i), [{ Alpine: B, effect: n, cleanup: r, evaluateLater: x.bind(x, e), evaluate: R.bind(R, e) }, () => t.forEach((a) => a())];
    }
    function kn(e, t) {
        let r = () => {},
            n = yt[t.type] || r,
            [i, o] = _t(e);
        Ce(e, t.original, o);
        let s = () => {
            e._x_ignore || e._x_ignoreSelf || (n.inline && n.inline(e, t, i), (n = n.bind(n, e, t, i)), bt ? de.get(hr).push(n) : n());
        };
        return (s.runCleanups = o), s;
    }
    var De = (e, t) => ({ name: r, value: n }) => (r.startsWith(e) && (r = r.replace(e, t)), { name: r, value: n }),
        ke = (e) => e;
    function _r(e = () => {}) {
        return ({ name: t, value: r }) => {
            let { name: n, value: i } = gr.reduce((o, s) => s(o), { name: t, value: r });
            return n !== t && e(n, t), { name: n, value: i };
        };
    }
    var gr = [];
    function te(e) {
        gr.push(e);
    }
    function xr({ name: e }) {
        return yr().test(e);
    }
    var yr = () => new RegExp(`^${Et}([^:^.]+)\\b`);
    function Ln(e, t) {
        return ({ name: r, value: n }) => {
            let i = r.match(yr()),
                o = r.match(/:([a-zA-Z0-9\-_:]+)/),
                s = r.match(/\.[^.\]]+(?=[^\]]*$)/g) || [],
                a = t || e[r] || r;
            return { type: i ? i[1] : null, value: o ? o[1] : null, modifiers: s.map((c) => c.replace(".", "")), expression: n, original: a };
        };
    }
    var wt = "DEFAULT",
        W = ["ignore", "ref", "data", "id", "anchor", "bind", "init", "for", "model", "modelable", "transition", "show", "if", wt, "teleport"];
    function $n(e, t) {
        let r = W.indexOf(e.type) === -1 ? wt : e.type,
            n = W.indexOf(t.type) === -1 ? wt : t.type;
        return W.indexOf(r) - W.indexOf(n);
    }
    var St = [],
        At = !1;
    function re(e = () => {}) {
        return (
            queueMicrotask(() => {
                At ||
                    setTimeout(() => {
                        Le();
                    });
            }),
            new Promise((t) => {
                St.push(() => {
                    e(), t();
                });
            })
        );
    }
    function Le() {
        for (At = !1; St.length; ) St.shift()();
    }
    function br() {
        At = !0;
    }
    function pe(e, t) {
        return Array.isArray(t) ? wr(e, t.join(" ")) : typeof t == "object" && t !== null ? jn(e, t) : typeof t == "function" ? pe(e, t()) : wr(e, t);
    }
    function wr(e, t) {
        let r = (o) => o.split(" ").filter(Boolean),
            n = (o) =>
                o
                    .split(" ")
                    .filter((s) => !e.classList.contains(s))
                    .filter(Boolean),
            i = (o) => (
                e.classList.add(...o),
                () => {
                    e.classList.remove(...o);
                }
            );
        return (t = t === !0 ? (t = "") : t || ""), i(n(t));
    }
    function jn(e, t) {
        let r = (a) => a.split(" ").filter(Boolean),
            n = Object.entries(t)
                .flatMap(([a, c]) => (c ? r(a) : !1))
                .filter(Boolean),
            i = Object.entries(t)
                .flatMap(([a, c]) => (c ? !1 : r(a)))
                .filter(Boolean),
            o = [],
            s = [];
        return (
            i.forEach((a) => {
                e.classList.contains(a) && (e.classList.remove(a), s.push(a));
            }),
            n.forEach((a) => {
                e.classList.contains(a) || (e.classList.add(a), o.push(a));
            }),
            () => {
                s.forEach((a) => e.classList.add(a)), o.forEach((a) => e.classList.remove(a));
            }
        );
    }
    function G(e, t) {
        return typeof t == "object" && t !== null ? Fn(e, t) : Bn(e, t);
    }
    function Fn(e, t) {
        let r = {};
        return (
            Object.entries(t).forEach(([n, i]) => {
                (r[n] = e.style[n]), n.startsWith("--") || (n = Kn(n)), e.style.setProperty(n, i);
            }),
            setTimeout(() => {
                e.style.length === 0 && e.removeAttribute("style");
            }),
            () => {
                G(e, r);
            }
        );
    }
    function Bn(e, t) {
        let r = e.getAttribute("style", t);
        return (
            e.setAttribute("style", t),
            () => {
                e.setAttribute("style", r || "");
            }
        );
    }
    function Kn(e) {
        return e.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
    }
    function me(e, t = () => {}) {
        let r = !1;
        return function () {
            r ? t.apply(this, arguments) : ((r = !0), e.apply(this, arguments));
        };
    }
    d("transition", (e, { value: t, modifiers: r, expression: n }, { evaluate: i }) => {
        typeof n == "function" && (n = i(n)), n !== !1 && (!n || typeof n == "boolean" ? Hn(e, r, t) : zn(e, n, t));
    });
    function zn(e, t, r) {
        Er(e, pe, ""),
            {
                enter: (i) => {
                    e._x_transition.enter.during = i;
                },
                "enter-start": (i) => {
                    e._x_transition.enter.start = i;
                },
                "enter-end": (i) => {
                    e._x_transition.enter.end = i;
                },
                leave: (i) => {
                    e._x_transition.leave.during = i;
                },
                "leave-start": (i) => {
                    e._x_transition.leave.start = i;
                },
                "leave-end": (i) => {
                    e._x_transition.leave.end = i;
                },
            }[r](t);
    }
    function Hn(e, t, r) {
        Er(e, G);
        let n = !t.includes("in") && !t.includes("out") && !r,
            i = n || t.includes("in") || ["enter"].includes(r),
            o = n || t.includes("out") || ["leave"].includes(r);
        t.includes("in") && !n && (t = t.filter((g, b) => b < t.indexOf("out"))), t.includes("out") && !n && (t = t.filter((g, b) => b > t.indexOf("out")));
        let s = !t.includes("opacity") && !t.includes("scale"),
            a = s || t.includes("opacity"),
            c = s || t.includes("scale"),
            l = a ? 0 : 1,
            u = c ? he(t, "scale", 95) / 100 : 1,
            p = he(t, "delay", 0) / 1e3,
            m = he(t, "origin", "center"),
            w = "opacity, transform",
            L = he(t, "duration", 150) / 1e3,
            we = he(t, "duration", 75) / 1e3,
            f = "cubic-bezier(0.4, 0.0, 0.2, 1)";
        i &&
            ((e._x_transition.enter.during = { transformOrigin: m, transitionDelay: `${p}s`, transitionProperty: w, transitionDuration: `${L}s`, transitionTimingFunction: f }),
            (e._x_transition.enter.start = { opacity: l, transform: `scale(${u})` }),
            (e._x_transition.enter.end = { opacity: 1, transform: "scale(1)" })),
            o &&
                ((e._x_transition.leave.during = { transformOrigin: m, transitionDelay: `${p}s`, transitionProperty: w, transitionDuration: `${we}s`, transitionTimingFunction: f }),
                (e._x_transition.leave.start = { opacity: 1, transform: "scale(1)" }),
                (e._x_transition.leave.end = { opacity: l, transform: `scale(${u})` }));
    }
    function Er(e, t, r = {}) {
        e._x_transition ||
            (e._x_transition = {
                enter: { during: r, start: r, end: r },
                leave: { during: r, start: r, end: r },
                in(n = () => {}, i = () => {}) {
                    $e(e, t, { during: this.enter.during, start: this.enter.start, end: this.enter.end }, n, i);
                },
                out(n = () => {}, i = () => {}) {
                    $e(e, t, { during: this.leave.during, start: this.leave.start, end: this.leave.end }, n, i);
                },
            });
    }
    window.Element.prototype._x_toggleAndCascadeWithTransitions = function (e, t, r, n) {
        let i = document.visibilityState === "visible" ? requestAnimationFrame : setTimeout,
            o = () => i(r);
        if (t) {
            e._x_transition && (e._x_transition.enter || e._x_transition.leave)
                ? e._x_transition.enter && (Object.entries(e._x_transition.enter.during).length || Object.entries(e._x_transition.enter.start).length || Object.entries(e._x_transition.enter.end).length)
                    ? e._x_transition.in(r)
                    : o()
                : e._x_transition
                ? e._x_transition.in(r)
                : o();
            return;
        }
        (e._x_hidePromise = e._x_transition
            ? new Promise((s, a) => {
                  e._x_transition.out(
                      () => {},
                      () => s(n)
                  ),
                      e._x_transitioning && e._x_transitioning.beforeCancel(() => a({ isFromCancelledTransition: !0 }));
              })
            : Promise.resolve(n)),
            queueMicrotask(() => {
                let s = vr(e);
                s
                    ? (s._x_hideChildren || (s._x_hideChildren = []), s._x_hideChildren.push(e))
                    : i(() => {
                          let a = (c) => {
                              let l = Promise.all([c._x_hidePromise, ...(c._x_hideChildren || []).map(a)]).then(([u]) => u());
                              return delete c._x_hidePromise, delete c._x_hideChildren, l;
                          };
                          a(e).catch((c) => {
                              if (!c.isFromCancelledTransition) throw c;
                          });
                      });
            });
    };
    function vr(e) {
        let t = e.parentNode;
        if (t) return t._x_hidePromise ? t : vr(t);
    }
    function $e(e, t, { during: r, start: n, end: i } = {}, o = () => {}, s = () => {}) {
        if ((e._x_transitioning && e._x_transitioning.cancel(), Object.keys(r).length === 0 && Object.keys(n).length === 0 && Object.keys(i).length === 0)) {
            o(), s();
            return;
        }
        let a, c, l;
        Vn(e, {
            start() {
                a = t(e, n);
            },
            during() {
                c = t(e, r);
            },
            before: o,
            end() {
                a(), (l = t(e, i));
            },
            after: s,
            cleanup() {
                c(), l();
            },
        });
    }
    function Vn(e, t) {
        let r,
            n,
            i,
            o = me(() => {
                h(() => {
                    (r = !0), n || t.before(), i || (t.end(), Le()), t.after(), e.isConnected && t.cleanup(), delete e._x_transitioning;
                });
            });
        (e._x_transitioning = {
            beforeCancels: [],
            beforeCancel(s) {
                this.beforeCancels.push(s);
            },
            cancel: me(function () {
                for (; this.beforeCancels.length; ) this.beforeCancels.shift()();
                o();
            }),
            finish: o,
        }),
            h(() => {
                t.start(), t.during();
            }),
            br(),
            requestAnimationFrame(() => {
                if (r) return;
                let s = Number(getComputedStyle(e).transitionDuration.replace(/,.*/, "").replace("s", "")) * 1e3,
                    a = Number(getComputedStyle(e).transitionDelay.replace(/,.*/, "").replace("s", "")) * 1e3;
                s === 0 && (s = Number(getComputedStyle(e).animationDuration.replace("s", "")) * 1e3),
                    h(() => {
                        t.before();
                    }),
                    (n = !0),
                    requestAnimationFrame(() => {
                        r ||
                            (h(() => {
                                t.end();
                            }),
                            Le(),
                            setTimeout(e._x_transitioning.finish, s + a),
                            (i = !0));
                    });
            });
    }
    function he(e, t, r) {
        if (e.indexOf(t) === -1) return r;
        let n = e[e.indexOf(t) + 1];
        if (!n || (t === "scale" && isNaN(n))) return r;
        if (t === "duration" || t === "delay") {
            let i = n.match(/([0-9]+)ms/);
            if (i) return i[1];
        }
        return t === "origin" && ["top", "right", "left", "center", "bottom"].includes(e[e.indexOf(t) + 2]) ? [n, e[e.indexOf(t) + 2]].join(" ") : n;
    }
    var P = !1;
    function I(e, t = () => {}) {
        return (...r) => (P ? t(...r) : e(...r));
    }
    function Sr(e) {
        return (...t) => P && e(...t);
    }
    var Ar = [];
    function Fe(e) {
        Ar.push(e);
    }
    function Or(e, t) {
        Ar.forEach((r) => r(e, t)),
            (P = !0),
            Tr(() => {
                S(t, (r, n) => {
                    n(r, () => {});
                });
            }),
            (P = !1);
    }
    var je = !1;
    function Cr(e, t) {
        t._x_dataStack || (t._x_dataStack = e._x_dataStack),
            (P = !0),
            (je = !0),
            Tr(() => {
                qn(t);
            }),
            (P = !1),
            (je = !1);
    }
    function qn(e) {
        let t = !1;
        S(e, (n, i) => {
            O(n, (o, s) => {
                if (t && Qt(o)) return s();
                (t = !0), i(o, s);
            });
        });
    }
    function Tr(e) {
        let t = k;
        st((r, n) => {
            let i = t(r);
            return $(i), () => {};
        }),
            e(),
            st(t);
    }
    function _e(e, t, r, n = []) {
        switch ((e._x_bindings || (e._x_bindings = T({})), (e._x_bindings[t] = r), (t = n.includes("camel") ? Qn(t) : t), t)) {
            case "value":
                Un(e, r);
                break;
            case "style":
                Gn(e, r);
                break;
            case "class":
                Wn(e, r);
                break;
            case "selected":
            case "checked":
                Jn(e, t, r);
                break;
            default:
                Mr(e, t, r);
                break;
        }
    }
    function Un(e, t) {
        if (e.type === "radio") e.attributes.value === void 0 && (e.value = t), window.fromModel && (typeof t == "boolean" ? (e.checked = ge(e.value) === t) : (e.checked = Rr(e.value, t)));
        else if (e.type === "checkbox")
            Number.isInteger(t) ? (e.value = t) : !Array.isArray(t) && typeof t != "boolean" && ![null, void 0].includes(t) ? (e.value = String(t)) : Array.isArray(t) ? (e.checked = t.some((r) => Rr(r, e.value))) : (e.checked = !!t);
        else if (e.tagName === "SELECT") Zn(e, t);
        else {
            if (e.value === t) return;
            e.value = t === void 0 ? "" : t;
        }
    }
    function Wn(e, t) {
        e._x_undoAddedClasses && e._x_undoAddedClasses(), (e._x_undoAddedClasses = pe(e, t));
    }
    function Gn(e, t) {
        e._x_undoAddedStyles && e._x_undoAddedStyles(), (e._x_undoAddedStyles = G(e, t));
    }
    function Jn(e, t, r) {
        Mr(e, t, r), Xn(e, t, r);
    }
    function Mr(e, t, r) {
        [null, void 0, !1].includes(r) && ei(t) ? e.removeAttribute(t) : (Nr(t) && (r = t), Yn(e, t, r));
    }
    function Yn(e, t, r) {
        e.getAttribute(t) != r && e.setAttribute(t, r);
    }
    function Xn(e, t, r) {
        e[t] !== r && (e[t] = r);
    }
    function Zn(e, t) {
        let r = [].concat(t).map((n) => n + "");
        Array.from(e.options).forEach((n) => {
            n.selected = r.includes(n.value);
        });
    }
    function Qn(e) {
        return e.toLowerCase().replace(/-(\w)/g, (t, r) => r.toUpperCase());
    }
    function Rr(e, t) {
        return e == t;
    }
    function ge(e) {
        return [1, "1", "true", "on", "yes", !0].includes(e) ? !0 : [0, "0", "false", "off", "no", !1].includes(e) ? !1 : e ? Boolean(e) : null;
    }
    function Nr(e) {
        return [
            "disabled",
            "checked",
            "required",
            "readonly",
            "hidden",
            "open",
            "selected",
            "autofocus",
            "itemscope",
            "multiple",
            "novalidate",
            "allowfullscreen",
            "allowpaymentrequest",
            "formnovalidate",
            "autoplay",
            "controls",
            "loop",
            "muted",
            "playsinline",
            "default",
            "ismap",
            "reversed",
            "async",
            "defer",
            "nomodule",
        ].includes(e);
    }
    function ei(e) {
        return !["aria-pressed", "aria-checked", "aria-expanded", "aria-selected"].includes(e);
    }
    function Pr(e, t, r) {
        return e._x_bindings && e._x_bindings[t] !== void 0 ? e._x_bindings[t] : Dr(e, t, r);
    }
    function Ir(e, t, r, n = !0) {
        if (e._x_bindings && e._x_bindings[t] !== void 0) return e._x_bindings[t];
        if (e._x_inlineBindings && e._x_inlineBindings[t] !== void 0) {
            let i = e._x_inlineBindings[t];
            return (i.extract = n), Ie(() => R(e, i.expression));
        }
        return Dr(e, t, r);
    }
    function Dr(e, t, r) {
        let n = e.getAttribute(t);
        return n === null ? (typeof r == "function" ? r() : r) : n === "" ? !0 : Nr(t) ? !![t, "true"].includes(n) : n;
    }
    function Be(e, t) {
        var r;
        return function () {
            var n = this,
                i = arguments,
                o = function () {
                    (r = null), e.apply(n, i);
                };
            clearTimeout(r), (r = setTimeout(o, t));
        };
    }
    function Ke(e, t) {
        let r;
        return function () {
            let n = this,
                i = arguments;
            r || (e.apply(n, i), (r = !0), setTimeout(() => (r = !1), t));
        };
    }
    function ze({ get: e, set: t }, { get: r, set: n }) {
        let i = !0,
            o,
            s = k(() => {
                let a = e(),
                    c = r();
                if (i) n(Ot(a)), (i = !1), (o = JSON.stringify(a));
                else {
                    let l = JSON.stringify(a);
                    l !== o ? (n(Ot(a)), (o = l)) : (t(Ot(c)), (o = JSON.stringify(c)));
                }
                JSON.stringify(r()), JSON.stringify(e());
            });
        return () => {
            $(s);
        };
    }
    function Ot(e) {
        return typeof e == "object" ? JSON.parse(JSON.stringify(e)) : e;
    }
    function kr(e) {
        (Array.isArray(e) ? e : [e]).forEach((r) => r(B));
    }
    var J = {},
        Lr = !1;
    function $r(e, t) {
        if ((Lr || ((J = T(J)), (Lr = !0)), t === void 0)) return J[e];
        (J[e] = t), typeof t == "object" && t !== null && t.hasOwnProperty("init") && typeof t.init == "function" && J[e].init(), Re(J[e]);
    }
    function jr() {
        return J;
    }
    var Fr = {};
    function Br(e, t) {
        let r = typeof t != "function" ? () => t : t;
        return e instanceof Element ? Ct(e, r()) : ((Fr[e] = r), () => {});
    }
    function Kr(e) {
        return (
            Object.entries(Fr).forEach(([t, r]) => {
                Object.defineProperty(e, t, {
                    get() {
                        return (...n) => r(...n);
                    },
                });
            }),
            e
        );
    }
    function Ct(e, t, r) {
        let n = [];
        for (; n.length; ) n.pop()();
        let i = Object.entries(t).map(([s, a]) => ({ name: s, value: a })),
            o = vt(i);
        return (
            (i = i.map((s) => (o.find((a) => a.name === s.name) ? { name: `x-bind:${s.name}`, value: `"${s.value}"` } : s))),
            le(e, i, r).map((s) => {
                n.push(s.runCleanups), s();
            }),
            () => {
                for (; n.length; ) n.pop()();
            }
        );
    }
    var zr = {};
    function Hr(e, t) {
        zr[e] = t;
    }
    function Vr(e, t) {
        return (
            Object.entries(zr).forEach(([r, n]) => {
                Object.defineProperty(e, r, {
                    get() {
                        return (...i) => n.bind(t)(...i);
                    },
                    enumerable: !1,
                });
            }),
            e
        );
    }
    var ti = {
            get reactive() {
                return T;
            },
            get release() {
                return $;
            },
            get effect() {
                return k;
            },
            get raw() {
                return ot;
            },
            version: "3.13.3",
            flushAndStopDeferringMutations: lr,
            dontAutoEvaluateFunctions: Ie,
            disableEffectScheduling: qt,
            startObservingMutations: ce,
            stopObservingMutations: dt,
            setReactivityEngine: Ut,
            onAttributeRemoved: Ce,
            onAttributesAdded: Ae,
            closestDataStack: j,
            skipDuringClone: I,
            onlyDuringClone: Sr,
            addRootSelector: ve,
            addInitSelector: Se,
            interceptClone: Fe,
            addScopeToNode: N,
            deferMutations: cr,
            mapAttributes: te,
            evaluateLater: x,
            interceptInit: tr,
            setEvaluator: pr,
            mergeProxies: F,
            extractProp: Ir,
            findClosest: Z,
            onElRemoved: Q,
            closestRoot: U,
            destroyTree: ae,
            interceptor: Me,
            transition: $e,
            setStyles: G,
            mutateDom: h,
            directive: d,
            entangle: ze,
            throttle: Ke,
            debounce: Be,
            evaluate: R,
            initTree: S,
            nextTick: re,
            prefixed: C,
            prefix: mr,
            plugin: kr,
            magic: y,
            store: $r,
            start: Jt,
            clone: Cr,
            cloneNode: Or,
            bound: Pr,
            $data: Te,
            walk: O,
            data: Hr,
            bind: Br,
        },
        B = ti;
    function Tt(e, t) {
        let r = Object.create(null),
            n = e.split(",");
        for (let i = 0; i < n.length; i++) r[n[i]] = !0;
        return t ? (i) => !!r[i.toLowerCase()] : (i) => !!r[i];
    }
    var ri = "itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly";
    var Ps = Tt(ri + ",async,autofocus,autoplay,controls,default,defer,disabled,hidden,loop,open,required,reversed,scoped,seamless,checked,muted,multiple,selected");
    var qr = Object.freeze({}),
        Is = Object.freeze([]);
    var ni = Object.prototype.hasOwnProperty,
        xe = (e, t) => ni.call(e, t),
        K = Array.isArray,
        ne = (e) => Ur(e) === "[object Map]";
    var ii = (e) => typeof e == "string",
        He = (e) => typeof e == "symbol",
        ye = (e) => e !== null && typeof e == "object";
    var oi = Object.prototype.toString,
        Ur = (e) => oi.call(e),
        Rt = (e) => Ur(e).slice(8, -1);
    var Ve = (e) => ii(e) && e !== "NaN" && e[0] !== "-" && "" + parseInt(e, 10) === e;
    var qe = (e) => {
            let t = Object.create(null);
            return (r) => t[r] || (t[r] = e(r));
        },
        si = /-(\w)/g,
        Ds = qe((e) => e.replace(si, (t, r) => (r ? r.toUpperCase() : ""))),
        ai = /\B([A-Z])/g,
        ks = qe((e) => e.replace(ai, "-$1").toLowerCase()),
        Mt = qe((e) => e.charAt(0).toUpperCase() + e.slice(1)),
        Ls = qe((e) => (e ? `on${Mt(e)}` : "")),
        Nt = (e, t) => e !== t && (e === e || t === t);
    var Pt = new WeakMap(),
        be = [],
        D,
        Y = Symbol("iterate"),
        It = Symbol("Map key iterate");
    function ci(e) {
        return e && e._isEffect === !0;
    }
    function Zr(e, t = qr) {
        ci(e) && (e = e.raw);
        let r = ui(e, t);
        return t.lazy || r(), r;
    }
    function Qr(e) {
        e.active && (en(e), e.options.onStop && e.options.onStop(), (e.active = !1));
    }
    var li = 0;
    function ui(e, t) {
        let r = function () {
            if (!r.active) return e();
            if (!be.includes(r)) {
                en(r);
                try {
                    return di(), be.push(r), (D = r), e();
                } finally {
                    be.pop(), tn(), (D = be[be.length - 1]);
                }
            }
        };
        return (r.id = li++), (r.allowRecurse = !!t.allowRecurse), (r._isEffect = !0), (r.active = !0), (r.raw = e), (r.deps = []), (r.options = t), r;
    }
    function en(e) {
        let { deps: t } = e;
        if (t.length) {
            for (let r = 0; r < t.length; r++) t[r].delete(e);
            t.length = 0;
        }
    }
    var ie = !0,
        kt = [];
    function fi() {
        kt.push(ie), (ie = !1);
    }
    function di() {
        kt.push(ie), (ie = !0);
    }
    function tn() {
        let e = kt.pop();
        ie = e === void 0 ? !0 : e;
    }
    function M(e, t, r) {
        if (!ie || D === void 0) return;
        let n = Pt.get(e);
        n || Pt.set(e, (n = new Map()));
        let i = n.get(r);
        i || n.set(r, (i = new Set())), i.has(D) || (i.add(D), D.deps.push(i), D.options.onTrack && D.options.onTrack({ effect: D, target: e, type: t, key: r }));
    }
    function H(e, t, r, n, i, o) {
        let s = Pt.get(e);
        if (!s) return;
        let a = new Set(),
            c = (u) => {
                u &&
                    u.forEach((p) => {
                        (p !== D || p.allowRecurse) && a.add(p);
                    });
            };
        if (t === "clear") s.forEach(c);
        else if (r === "length" && K(e))
            s.forEach((u, p) => {
                (p === "length" || p >= n) && c(u);
            });
        else
            switch ((r !== void 0 && c(s.get(r)), t)) {
                case "add":
                    K(e) ? Ve(r) && c(s.get("length")) : (c(s.get(Y)), ne(e) && c(s.get(It)));
                    break;
                case "delete":
                    K(e) || (c(s.get(Y)), ne(e) && c(s.get(It)));
                    break;
                case "set":
                    ne(e) && c(s.get(Y));
                    break;
            }
        let l = (u) => {
            u.options.onTrigger && u.options.onTrigger({ effect: u, target: e, key: r, type: t, newValue: n, oldValue: i, oldTarget: o }), u.options.scheduler ? u.options.scheduler(u) : u();
        };
        a.forEach(l);
    }
    var pi = Tt("__proto__,__v_isRef,__isVue"),
        rn = new Set(
            Object.getOwnPropertyNames(Symbol)
                .map((e) => Symbol[e])
                .filter(He)
        ),
        mi = nn();
    var hi = nn(!0);
    var Wr = _i();
    function _i() {
        let e = {};
        return (
            ["includes", "indexOf", "lastIndexOf"].forEach((t) => {
                e[t] = function (...r) {
                    let n = _(this);
                    for (let o = 0, s = this.length; o < s; o++) M(n, "get", o + "");
                    let i = n[t](...r);
                    return i === -1 || i === !1 ? n[t](...r.map(_)) : i;
                };
            }),
            ["push", "pop", "shift", "unshift", "splice"].forEach((t) => {
                e[t] = function (...r) {
                    fi();
                    let n = _(this)[t].apply(this, r);
                    return tn(), n;
                };
            }),
            e
        );
    }
    function nn(e = !1, t = !1) {
        return function (n, i, o) {
            if (i === "__v_isReactive") return !e;
            if (i === "__v_isReadonly") return e;
            if (i === "__v_raw" && o === (e ? (t ? Pi : cn) : t ? Ni : an).get(n)) return n;
            let s = K(n);
            if (!e && s && xe(Wr, i)) return Reflect.get(Wr, i, o);
            let a = Reflect.get(n, i, o);
            return (He(i) ? rn.has(i) : pi(i)) || (e || M(n, "get", i), t) ? a : Dt(a) ? (!s || !Ve(i) ? a.value : a) : ye(a) ? (e ? ln(a) : Ze(a)) : a;
        };
    }
    var gi = xi();
    function xi(e = !1) {
        return function (r, n, i, o) {
            let s = r[n];
            if (!e && ((i = _(i)), (s = _(s)), !K(r) && Dt(s) && !Dt(i))) return (s.value = i), !0;
            let a = K(r) && Ve(n) ? Number(n) < r.length : xe(r, n),
                c = Reflect.set(r, n, i, o);
            return r === _(o) && (a ? Nt(i, s) && H(r, "set", n, i, s) : H(r, "add", n, i)), c;
        };
    }
    function yi(e, t) {
        let r = xe(e, t),
            n = e[t],
            i = Reflect.deleteProperty(e, t);
        return i && r && H(e, "delete", t, void 0, n), i;
    }
    function bi(e, t) {
        let r = Reflect.has(e, t);
        return (!He(t) || !rn.has(t)) && M(e, "has", t), r;
    }
    function wi(e) {
        return M(e, "iterate", K(e) ? "length" : Y), Reflect.ownKeys(e);
    }
    var Ei = { get: mi, set: gi, deleteProperty: yi, has: bi, ownKeys: wi },
        vi = {
            get: hi,
            set(e, t) {
                return console.warn(`Set operation on key "${String(t)}" failed: target is readonly.`, e), !0;
            },
            deleteProperty(e, t) {
                return console.warn(`Delete operation on key "${String(t)}" failed: target is readonly.`, e), !0;
            },
        };
    var Lt = (e) => (ye(e) ? Ze(e) : e),
        $t = (e) => (ye(e) ? ln(e) : e),
        jt = (e) => e,
        Xe = (e) => Reflect.getPrototypeOf(e);
    function Ue(e, t, r = !1, n = !1) {
        e = e.__v_raw;
        let i = _(e),
            o = _(t);
        t !== o && !r && M(i, "get", t), !r && M(i, "get", o);
        let { has: s } = Xe(i),
            a = n ? jt : r ? $t : Lt;
        if (s.call(i, t)) return a(e.get(t));
        if (s.call(i, o)) return a(e.get(o));
        e !== i && e.get(t);
    }
    function We(e, t = !1) {
        let r = this.__v_raw,
            n = _(r),
            i = _(e);
        return e !== i && !t && M(n, "has", e), !t && M(n, "has", i), e === i ? r.has(e) : r.has(e) || r.has(i);
    }
    function Ge(e, t = !1) {
        return (e = e.__v_raw), !t && M(_(e), "iterate", Y), Reflect.get(e, "size", e);
    }
    function Gr(e) {
        e = _(e);
        let t = _(this);
        return Xe(t).has.call(t, e) || (t.add(e), H(t, "add", e, e)), this;
    }
    function Jr(e, t) {
        t = _(t);
        let r = _(this),
            { has: n, get: i } = Xe(r),
            o = n.call(r, e);
        o ? sn(r, n, e) : ((e = _(e)), (o = n.call(r, e)));
        let s = i.call(r, e);
        return r.set(e, t), o ? Nt(t, s) && H(r, "set", e, t, s) : H(r, "add", e, t), this;
    }
    function Yr(e) {
        let t = _(this),
            { has: r, get: n } = Xe(t),
            i = r.call(t, e);
        i ? sn(t, r, e) : ((e = _(e)), (i = r.call(t, e)));
        let o = n ? n.call(t, e) : void 0,
            s = t.delete(e);
        return i && H(t, "delete", e, void 0, o), s;
    }
    function Xr() {
        let e = _(this),
            t = e.size !== 0,
            r = ne(e) ? new Map(e) : new Set(e),
            n = e.clear();
        return t && H(e, "clear", void 0, void 0, r), n;
    }
    function Je(e, t) {
        return function (n, i) {
            let o = this,
                s = o.__v_raw,
                a = _(s),
                c = t ? jt : e ? $t : Lt;
            return !e && M(a, "iterate", Y), s.forEach((l, u) => n.call(i, c(l), c(u), o));
        };
    }
    function Ye(e, t, r) {
        return function (...n) {
            let i = this.__v_raw,
                o = _(i),
                s = ne(o),
                a = e === "entries" || (e === Symbol.iterator && s),
                c = e === "keys" && s,
                l = i[e](...n),
                u = r ? jt : t ? $t : Lt;
            return (
                !t && M(o, "iterate", c ? It : Y),
                {
                    next() {
                        let { value: p, done: m } = l.next();
                        return m ? { value: p, done: m } : { value: a ? [u(p[0]), u(p[1])] : u(p), done: m };
                    },
                    [Symbol.iterator]() {
                        return this;
                    },
                }
            );
        };
    }
    function z(e) {
        return function (...t) {
            {
                let r = t[0] ? `on key "${t[0]}" ` : "";
                console.warn(`${Mt(e)} operation ${r}failed: target is readonly.`, _(this));
            }
            return e === "delete" ? !1 : this;
        };
    }
    function Si() {
        let e = {
                get(o) {
                    return Ue(this, o);
                },
                get size() {
                    return Ge(this);
                },
                has: We,
                add: Gr,
                set: Jr,
                delete: Yr,
                clear: Xr,
                forEach: Je(!1, !1),
            },
            t = {
                get(o) {
                    return Ue(this, o, !1, !0);
                },
                get size() {
                    return Ge(this);
                },
                has: We,
                add: Gr,
                set: Jr,
                delete: Yr,
                clear: Xr,
                forEach: Je(!1, !0),
            },
            r = {
                get(o) {
                    return Ue(this, o, !0);
                },
                get size() {
                    return Ge(this, !0);
                },
                has(o) {
                    return We.call(this, o, !0);
                },
                add: z("add"),
                set: z("set"),
                delete: z("delete"),
                clear: z("clear"),
                forEach: Je(!0, !1),
            },
            n = {
                get(o) {
                    return Ue(this, o, !0, !0);
                },
                get size() {
                    return Ge(this, !0);
                },
                has(o) {
                    return We.call(this, o, !0);
                },
                add: z("add"),
                set: z("set"),
                delete: z("delete"),
                clear: z("clear"),
                forEach: Je(!0, !0),
            };
        return (
            ["keys", "values", "entries", Symbol.iterator].forEach((o) => {
                (e[o] = Ye(o, !1, !1)), (r[o] = Ye(o, !0, !1)), (t[o] = Ye(o, !1, !0)), (n[o] = Ye(o, !0, !0));
            }),
            [e, r, t, n]
        );
    }
    var [Ai, Oi, Ci, Ti] = Si();
    function on(e, t) {
        let r = t ? (e ? Ti : Ci) : e ? Oi : Ai;
        return (n, i, o) => (i === "__v_isReactive" ? !e : i === "__v_isReadonly" ? e : i === "__v_raw" ? n : Reflect.get(xe(r, i) && i in n ? r : n, i, o));
    }
    var Ri = { get: on(!1, !1) };
    var Mi = { get: on(!0, !1) };
    function sn(e, t, r) {
        let n = _(r);
        if (n !== r && t.call(e, n)) {
            let i = Rt(e);
            console.warn(
                `Reactive ${i} contains both the raw and reactive versions of the same object${
                    i === "Map" ? " as keys" : ""
                }, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`
            );
        }
    }
    var an = new WeakMap(),
        Ni = new WeakMap(),
        cn = new WeakMap(),
        Pi = new WeakMap();
    function Ii(e) {
        switch (e) {
            case "Object":
            case "Array":
                return 1;
            case "Map":
            case "Set":
            case "WeakMap":
            case "WeakSet":
                return 2;
            default:
                return 0;
        }
    }
    function Di(e) {
        return e.__v_skip || !Object.isExtensible(e) ? 0 : Ii(Rt(e));
    }
    function Ze(e) {
        return e && e.__v_isReadonly ? e : un(e, !1, Ei, Ri, an);
    }
    function ln(e) {
        return un(e, !0, vi, Mi, cn);
    }
    function un(e, t, r, n, i) {
        if (!ye(e)) return console.warn(`value cannot be made reactive: ${String(e)}`), e;
        if (e.__v_raw && !(t && e.__v_isReactive)) return e;
        let o = i.get(e);
        if (o) return o;
        let s = Di(e);
        if (s === 0) return e;
        let a = new Proxy(e, s === 2 ? n : r);
        return i.set(e, a), a;
    }
    function _(e) {
        return (e && _(e.__v_raw)) || e;
    }
    function Dt(e) {
        return Boolean(e && e.__v_isRef === !0);
    }
    y("nextTick", () => re);
    y("dispatch", (e) => q.bind(q, e));
    y("watch", (e, { evaluateLater: t, effect: r }) => (n, i) => {
        let o = t(n),
            s = !0,
            a,
            c = r(() =>
                o((l) => {
                    JSON.stringify(l),
                        s
                            ? (a = l)
                            : queueMicrotask(() => {
                                  i(l, a), (a = l);
                              }),
                        (s = !1);
                })
            );
        e._x_effects.delete(c);
    });
    y("store", jr);
    y("data", (e) => Te(e));
    y("root", (e) => U(e));
    y("refs", (e) => (e._x_refs_proxy || (e._x_refs_proxy = F(ki(e))), e._x_refs_proxy));
    function ki(e) {
        let t = [],
            r = e;
        for (; r; ) r._x_refs && t.push(r._x_refs), (r = r.parentNode);
        return t;
    }
    var Ft = {};
    function Bt(e) {
        return Ft[e] || (Ft[e] = 0), ++Ft[e];
    }
    function fn(e, t) {
        return Z(e, (r) => {
            if (r._x_ids && r._x_ids[t]) return !0;
        });
    }
    function dn(e, t) {
        e._x_ids || (e._x_ids = {}), e._x_ids[t] || (e._x_ids[t] = Bt(t));
    }
    y("id", (e) => (t, r = null) => {
        let n = fn(e, t),
            i = n ? n._x_ids[t] : Bt(t);
        return r ? `${t}-${i}-${r}` : `${t}-${i}`;
    });
    y("el", (e) => e);
    pn("Focus", "focus", "focus");
    pn("Persist", "persist", "persist");
    function pn(e, t, r) {
        y(t, (n) => v(`You can't use [$${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${r}`, n));
    }
    d("modelable", (e, { expression: t }, { effect: r, evaluateLater: n, cleanup: i }) => {
        let o = n(t),
            s = () => {
                let u;
                return o((p) => (u = p)), u;
            },
            a = n(`${t} = __placeholder`),
            c = (u) => a(() => {}, { scope: { __placeholder: u } }),
            l = s();
        c(l),
            queueMicrotask(() => {
                if (!e._x_model) return;
                e._x_removeModelListeners.default();
                let u = e._x_model.get,
                    p = e._x_model.set,
                    m = ze(
                        {
                            get() {
                                return u();
                            },
                            set(w) {
                                p(w);
                            },
                        },
                        {
                            get() {
                                return s();
                            },
                            set(w) {
                                c(w);
                            },
                        }
                    );
                i(m);
            });
    });
    d("teleport", (e, { modifiers: t, expression: r }, { cleanup: n }) => {
        e.tagName.toLowerCase() !== "template" && v("x-teleport can only be used on a <template> tag", e);
        let i = mn(r),
            o = e.content.cloneNode(!0).firstElementChild;
        (e._x_teleport = o),
            (o._x_teleportBack = e),
            e.setAttribute("data-teleport-template", !0),
            o.setAttribute("data-teleport-target", !0),
            e._x_forwardEvents &&
                e._x_forwardEvents.forEach((a) => {
                    o.addEventListener(a, (c) => {
                        c.stopPropagation(), e.dispatchEvent(new c.constructor(c.type, c));
                    });
                }),
            N(o, {}, e);
        let s = (a, c, l) => {
            l.includes("prepend") ? c.parentNode.insertBefore(a, c) : l.includes("append") ? c.parentNode.insertBefore(a, c.nextSibling) : c.appendChild(a);
        };
        h(() => {
            s(o, i, t), S(o), (o._x_ignore = !0);
        }),
            (e._x_teleportPutBack = () => {
                let a = mn(r);
                h(() => {
                    s(e._x_teleport, a, t);
                });
            }),
            n(() => o.remove());
    });
    var Li = document.createElement("div");
    function mn(e) {
        let t = I(
            () => document.querySelector(e),
            () => Li
        )();
        return t || v(`Cannot find x-teleport element for selector: "${e}"`), t;
    }
    var hn = () => {};
    hn.inline = (e, { modifiers: t }, { cleanup: r }) => {
        t.includes("self") ? (e._x_ignoreSelf = !0) : (e._x_ignore = !0),
            r(() => {
                t.includes("self") ? delete e._x_ignoreSelf : delete e._x_ignore;
            });
    };
    d("ignore", hn);
    d(
        "effect",
        I((e, { expression: t }, { effect: r }) => {
            r(x(e, t));
        })
    );
    function oe(e, t, r, n) {
        let i = e,
            o = (c) => n(c),
            s = {},
            a = (c, l) => (u) => l(c, u);
        if (
            (r.includes("dot") && (t = $i(t)),
            r.includes("camel") && (t = ji(t)),
            r.includes("passive") && (s.passive = !0),
            r.includes("capture") && (s.capture = !0),
            r.includes("window") && (i = window),
            r.includes("document") && (i = document),
            r.includes("debounce"))
        ) {
            let c = r[r.indexOf("debounce") + 1] || "invalid-wait",
                l = Qe(c.split("ms")[0]) ? Number(c.split("ms")[0]) : 250;
            o = Be(o, l);
        }
        if (r.includes("throttle")) {
            let c = r[r.indexOf("throttle") + 1] || "invalid-wait",
                l = Qe(c.split("ms")[0]) ? Number(c.split("ms")[0]) : 250;
            o = Ke(o, l);
        }
        return (
            r.includes("prevent") &&
                (o = a(o, (c, l) => {
                    l.preventDefault(), c(l);
                })),
            r.includes("stop") &&
                (o = a(o, (c, l) => {
                    l.stopPropagation(), c(l);
                })),
            r.includes("self") &&
                (o = a(o, (c, l) => {
                    l.target === e && c(l);
                })),
            (r.includes("away") || r.includes("outside")) &&
                ((i = document),
                (o = a(o, (c, l) => {
                    e.contains(l.target) || (l.target.isConnected !== !1 && ((e.offsetWidth < 1 && e.offsetHeight < 1) || (e._x_isShown !== !1 && c(l))));
                }))),
            r.includes("once") &&
                (o = a(o, (c, l) => {
                    c(l), i.removeEventListener(t, o, s);
                })),
            (o = a(o, (c, l) => {
                (Bi(t) && Ki(l, r)) || c(l);
            })),
            i.addEventListener(t, o, s),
            () => {
                i.removeEventListener(t, o, s);
            }
        );
    }
    function $i(e) {
        return e.replace(/-/g, ".");
    }
    function ji(e) {
        return e.toLowerCase().replace(/-(\w)/g, (t, r) => r.toUpperCase());
    }
    function Qe(e) {
        return !Array.isArray(e) && !isNaN(e);
    }
    function Fi(e) {
        return [" ", "_"].includes(e)
            ? e
            : e
                  .replace(/([a-z])([A-Z])/g, "$1-$2")
                  .replace(/[_\s]/, "-")
                  .toLowerCase();
    }
    function Bi(e) {
        return ["keydown", "keyup"].includes(e);
    }
    function Ki(e, t) {
        let r = t.filter((o) => !["window", "document", "prevent", "stop", "once", "capture"].includes(o));
        if (r.includes("debounce")) {
            let o = r.indexOf("debounce");
            r.splice(o, Qe((r[o + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1);
        }
        if (r.includes("throttle")) {
            let o = r.indexOf("throttle");
            r.splice(o, Qe((r[o + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1);
        }
        if (r.length === 0 || (r.length === 1 && _n(e.key).includes(r[0]))) return !1;
        let i = ["ctrl", "shift", "alt", "meta", "cmd", "super"].filter((o) => r.includes(o));
        return (r = r.filter((o) => !i.includes(o))), !(i.length > 0 && i.filter((s) => ((s === "cmd" || s === "super") && (s = "meta"), e[`${s}Key`])).length === i.length && _n(e.key).includes(r[0]));
    }
    function _n(e) {
        if (!e) return [];
        e = Fi(e);
        let t = { ctrl: "control", slash: "/", space: " ", spacebar: " ", cmd: "meta", esc: "escape", up: "arrow-up", down: "arrow-down", left: "arrow-left", right: "arrow-right", period: ".", equal: "=", minus: "-", underscore: "_" };
        return (
            (t[e] = e),
            Object.keys(t)
                .map((r) => {
                    if (t[r] === e) return r;
                })
                .filter((r) => r)
        );
    }
    d("model", (e, { modifiers: t, expression: r }, { effect: n, cleanup: i }) => {
        let o = e;
        t.includes("parent") && (o = e.parentNode);
        let s = x(o, r),
            a;
        typeof r == "string" ? (a = x(o, `${r} = __placeholder`)) : typeof r == "function" && typeof r() == "string" ? (a = x(o, `${r()} = __placeholder`)) : (a = () => {});
        let c = () => {
                let m;
                return s((w) => (m = w)), gn(m) ? m.get() : m;
            },
            l = (m) => {
                let w;
                s((L) => (w = L)), gn(w) ? w.set(m) : a(() => {}, { scope: { __placeholder: m } });
            };
        typeof r == "string" &&
            e.type === "radio" &&
            h(() => {
                e.hasAttribute("name") || e.setAttribute("name", r);
            });
        var u = e.tagName.toLowerCase() === "select" || ["checkbox", "radio"].includes(e.type) || t.includes("lazy") ? "change" : "input";
        let p = P
            ? () => {}
            : oe(e, u, t, (m) => {
                  l(zi(e, t, m, c()));
              });
        if (
            (t.includes("fill") && ([null, ""].includes(c()) || (e.type === "checkbox" && Array.isArray(c()))) && e.dispatchEvent(new Event(u, {})),
            e._x_removeModelListeners || (e._x_removeModelListeners = {}),
            (e._x_removeModelListeners.default = p),
            i(() => e._x_removeModelListeners.default()),
            e.form)
        ) {
            let m = oe(e.form, "reset", [], (w) => {
                re(() => e._x_model && e._x_model.set(e.value));
            });
            i(() => m());
        }
        (e._x_model = {
            get() {
                return c();
            },
            set(m) {
                l(m);
            },
        }),
            (e._x_forceModelUpdate = (m) => {
                m === void 0 && typeof r == "string" && r.match(/\./) && (m = ""), (window.fromModel = !0), h(() => _e(e, "value", m)), delete window.fromModel;
            }),
            n(() => {
                let m = c();
                (t.includes("unintrusive") && document.activeElement.isSameNode(e)) || e._x_forceModelUpdate(m);
            });
    });
    function zi(e, t, r, n) {
        return h(() => {
            if (r instanceof CustomEvent && r.detail !== void 0) return r.detail !== null && r.detail !== void 0 ? r.detail : r.target.value;
            if (e.type === "checkbox")
                if (Array.isArray(n)) {
                    let i = null;
                    return t.includes("number") ? (i = Kt(r.target.value)) : t.includes("boolean") ? (i = ge(r.target.value)) : (i = r.target.value), r.target.checked ? n.concat([i]) : n.filter((o) => !Hi(o, i));
                } else return r.target.checked;
            else
                return e.tagName.toLowerCase() === "select" && e.multiple
                    ? t.includes("number")
                        ? Array.from(r.target.selectedOptions).map((i) => {
                              let o = i.value || i.text;
                              return Kt(o);
                          })
                        : t.includes("boolean")
                        ? Array.from(r.target.selectedOptions).map((i) => {
                              let o = i.value || i.text;
                              return ge(o);
                          })
                        : Array.from(r.target.selectedOptions).map((i) => i.value || i.text)
                    : t.includes("number")
                    ? Kt(r.target.value)
                    : t.includes("boolean")
                    ? ge(r.target.value)
                    : t.includes("trim")
                    ? r.target.value.trim()
                    : r.target.value;
        });
    }
    function Kt(e) {
        let t = e ? parseFloat(e) : null;
        return Vi(t) ? t : e;
    }
    function Hi(e, t) {
        return e == t;
    }
    function Vi(e) {
        return !Array.isArray(e) && !isNaN(e);
    }
    function gn(e) {
        return e !== null && typeof e == "object" && typeof e.get == "function" && typeof e.set == "function";
    }
    d("cloak", (e) => queueMicrotask(() => h(() => e.removeAttribute(C("cloak")))));
    Se(() => `[${C("init")}]`);
    d(
        "init",
        I((e, { expression: t }, { evaluate: r }) => (typeof t == "string" ? !!t.trim() && r(t, {}, !1) : r(t, {}, !1)))
    );
    d("text", (e, { expression: t }, { effect: r, evaluateLater: n }) => {
        let i = n(t);
        r(() => {
            i((o) => {
                h(() => {
                    e.textContent = o;
                });
            });
        });
    });
    d("html", (e, { expression: t }, { effect: r, evaluateLater: n }) => {
        let i = n(t);
        r(() => {
            i((o) => {
                h(() => {
                    (e.innerHTML = o), (e._x_ignoreSelf = !0), S(e), delete e._x_ignoreSelf;
                });
            });
        });
    });
    te(De(":", ke(C("bind:"))));
    var xn = (e, { value: t, modifiers: r, expression: n, original: i }, { effect: o }) => {
        if (!t) {
            let a = {};
            Kr(a),
                x(e, n)(
                    (l) => {
                        Ct(e, l, i);
                    },
                    { scope: a }
                );
            return;
        }
        if (t === "key") return qi(e, n);
        if (e._x_inlineBindings && e._x_inlineBindings[t] && e._x_inlineBindings[t].extract) return;
        let s = x(e, n);
        o(() =>
            s((a) => {
                a === void 0 && typeof n == "string" && n.match(/\./) && (a = ""), h(() => _e(e, t, a, r));
            })
        );
    };
    xn.inline = (e, { value: t, modifiers: r, expression: n }) => {
        t && (e._x_inlineBindings || (e._x_inlineBindings = {}), (e._x_inlineBindings[t] = { expression: n, extract: !1 }));
    };
    d("bind", xn);
    function qi(e, t) {
        e._x_keyExpression = t;
    }
    ve(() => `[${C("data")}]`);
    d("data", (e, { expression: t }, { cleanup: r }) => {
        if (Ui(e)) return;
        t = t === "" ? "{}" : t;
        let n = {};
        fe(n, e);
        let i = {};
        Vr(i, n);
        let o = R(e, t, { scope: i });
        (o === void 0 || o === !0) && (o = {}), fe(o, e);
        let s = T(o);
        Re(s);
        let a = N(e, s);
        s.init && R(e, s.init),
            r(() => {
                s.destroy && R(e, s.destroy), a();
            });
    });
    Fe((e, t) => {
        e._x_dataStack && ((t._x_dataStack = e._x_dataStack), t.setAttribute("data-has-alpine-state", !0));
    });
    function Ui(e) {
        return P ? (je ? !0 : e.hasAttribute("data-has-alpine-state")) : !1;
    }
    d("show", (e, { modifiers: t, expression: r }, { effect: n }) => {
        let i = x(e, r);
        e._x_doHide ||
            (e._x_doHide = () => {
                h(() => {
                    e.style.setProperty("display", "none", t.includes("important") ? "important" : void 0);
                });
            }),
            e._x_doShow ||
                (e._x_doShow = () => {
                    h(() => {
                        e.style.length === 1 && e.style.display === "none" ? e.removeAttribute("style") : e.style.removeProperty("display");
                    });
                });
        let o = () => {
                e._x_doHide(), (e._x_isShown = !1);
            },
            s = () => {
                e._x_doShow(), (e._x_isShown = !0);
            },
            a = () => setTimeout(s),
            c = me(
                (p) => (p ? s() : o()),
                (p) => {
                    typeof e._x_toggleAndCascadeWithTransitions == "function" ? e._x_toggleAndCascadeWithTransitions(e, p, s, o) : p ? a() : o();
                }
            ),
            l,
            u = !0;
        n(() =>
            i((p) => {
                (!u && p === l) || (t.includes("immediate") && (p ? a() : o()), c(p), (l = p), (u = !1));
            })
        );
    });
    d("for", (e, { expression: t }, { effect: r, cleanup: n }) => {
        let i = Gi(t),
            o = x(e, i.items),
            s = x(e, e._x_keyExpression || "index");
        (e._x_prevKeys = []),
            (e._x_lookup = {}),
            r(() => Wi(e, i, o, s)),
            n(() => {
                Object.values(e._x_lookup).forEach((a) => a.remove()), delete e._x_prevKeys, delete e._x_lookup;
            });
    });
    function Wi(e, t, r, n) {
        let i = (s) => typeof s == "object" && !Array.isArray(s),
            o = e;
        r((s) => {
            Ji(s) && s >= 0 && (s = Array.from(Array(s).keys(), (f) => f + 1)), s === void 0 && (s = []);
            let a = e._x_lookup,
                c = e._x_prevKeys,
                l = [],
                u = [];
            if (i(s))
                s = Object.entries(s).map(([f, g]) => {
                    let b = yn(t, g, f, s);
                    n((E) => u.push(E), { scope: { index: f, ...b } }), l.push(b);
                });
            else
                for (let f = 0; f < s.length; f++) {
                    let g = yn(t, s[f], f, s);
                    n((b) => u.push(b), { scope: { index: f, ...g } }), l.push(g);
                }
            let p = [],
                m = [],
                w = [],
                L = [];
            for (let f = 0; f < c.length; f++) {
                let g = c[f];
                u.indexOf(g) === -1 && w.push(g);
            }
            c = c.filter((f) => !w.includes(f));
            let we = "template";
            for (let f = 0; f < u.length; f++) {
                let g = u[f],
                    b = c.indexOf(g);
                if (b === -1) c.splice(f, 0, g), p.push([we, f]);
                else if (b !== f) {
                    let E = c.splice(f, 1)[0],
                        A = c.splice(b - 1, 1)[0];
                    c.splice(f, 0, A), c.splice(b, 0, E), m.push([E, A]);
                } else L.push(g);
                we = g;
            }
            for (let f = 0; f < w.length; f++) {
                let g = w[f];
                a[g]._x_effects && a[g]._x_effects.forEach(Ee), a[g].remove(), (a[g] = null), delete a[g];
            }
            for (let f = 0; f < m.length; f++) {
                let [g, b] = m[f],
                    E = a[g],
                    A = a[b],
                    X = document.createElement("div");
                h(() => {
                    A || v('x-for ":key" is undefined or invalid', o), A.after(X), E.after(A), A._x_currentIfEl && A.after(A._x_currentIfEl), X.before(E), E._x_currentIfEl && E.after(E._x_currentIfEl), X.remove();
                }),
                    A._x_refreshXForScope(l[u.indexOf(b)]);
            }
            for (let f = 0; f < p.length; f++) {
                let [g, b] = p[f],
                    E = g === "template" ? o : a[g];
                E._x_currentIfEl && (E = E._x_currentIfEl);
                let A = l[b],
                    X = u[b],
                    se = document.importNode(o.content, !0).firstElementChild,
                    Ht = T(A);
                N(se, Ht, o),
                    (se._x_refreshXForScope = (wn) => {
                        Object.entries(wn).forEach(([En, vn]) => {
                            Ht[En] = vn;
                        });
                    }),
                    h(() => {
                        E.after(se), S(se);
                    }),
                    typeof X == "object" && v("x-for key cannot be an object, it must be a string or an integer", o),
                    (a[X] = se);
            }
            for (let f = 0; f < L.length; f++) a[L[f]]._x_refreshXForScope(l[u.indexOf(L[f])]);
            o._x_prevKeys = u;
        });
    }
    function Gi(e) {
        let t = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/,
            r = /^\s*\(|\)\s*$/g,
            n = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,
            i = e.match(n);
        if (!i) return;
        let o = {};
        o.items = i[2].trim();
        let s = i[1].replace(r, "").trim(),
            a = s.match(t);
        return a ? ((o.item = s.replace(t, "").trim()), (o.index = a[1].trim()), a[2] && (o.collection = a[2].trim())) : (o.item = s), o;
    }
    function yn(e, t, r, n) {
        let i = {};
        return (
            /^\[.*\]$/.test(e.item) && Array.isArray(t)
                ? e.item
                      .replace("[", "")
                      .replace("]", "")
                      .split(",")
                      .map((s) => s.trim())
                      .forEach((s, a) => {
                          i[s] = t[a];
                      })
                : /^\{.*\}$/.test(e.item) && !Array.isArray(t) && typeof t == "object"
                ? e.item
                      .replace("{", "")
                      .replace("}", "")
                      .split(",")
                      .map((s) => s.trim())
                      .forEach((s) => {
                          i[s] = t[s];
                      })
                : (i[e.item] = t),
            e.index && (i[e.index] = r),
            e.collection && (i[e.collection] = n),
            i
        );
    }
    function Ji(e) {
        return !Array.isArray(e) && !isNaN(e);
    }
    function bn() {}
    bn.inline = (e, { expression: t }, { cleanup: r }) => {
        let n = U(e);
        n._x_refs || (n._x_refs = {}), (n._x_refs[t] = e), r(() => delete n._x_refs[t]);
    };
    d("ref", bn);
    d("if", (e, { expression: t }, { effect: r, cleanup: n }) => {
        e.tagName.toLowerCase() !== "template" && v("x-if can only be used on a <template> tag", e);
        let i = x(e, t),
            o = () => {
                if (e._x_currentIfEl) return e._x_currentIfEl;
                let a = e.content.cloneNode(!0).firstElementChild;
                return (
                    N(a, {}, e),
                    h(() => {
                        e.after(a), S(a);
                    }),
                    (e._x_currentIfEl = a),
                    (e._x_undoIf = () => {
                        O(a, (c) => {
                            c._x_effects && c._x_effects.forEach(Ee);
                        }),
                            a.remove(),
                            delete e._x_currentIfEl;
                    }),
                    a
                );
            },
            s = () => {
                e._x_undoIf && (e._x_undoIf(), delete e._x_undoIf);
            };
        r(() =>
            i((a) => {
                a ? o() : s();
            })
        ),
            n(() => e._x_undoIf && e._x_undoIf());
    });
    d("id", (e, { expression: t }, { evaluate: r }) => {
        r(t).forEach((i) => dn(e, i));
    });
    te(De("@", ke(C("on:"))));
    d(
        "on",
        I((e, { value: t, modifiers: r, expression: n }, { cleanup: i }) => {
            let o = n ? x(e, n) : () => {};
            e.tagName.toLowerCase() === "template" && (e._x_forwardEvents || (e._x_forwardEvents = []), e._x_forwardEvents.includes(t) || e._x_forwardEvents.push(t));
            let s = oe(e, t, r, (a) => {
                o(() => {}, { scope: { $event: a }, params: [a] });
            });
            i(() => s());
        })
    );
    et("Collapse", "collapse", "collapse");
    et("Intersect", "intersect", "intersect");
    et("Focus", "trap", "focus");
    et("Mask", "mask", "mask");
    function et(e, t, r) {
        d(t, (n) => v(`You can't use [x-${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${r}`, n));
    }
    B.setEvaluator(xt);
    B.setReactivityEngine({ reactive: Ze, effect: Zr, release: Qr, raw: _ });
    var zt = B;
    window.Alpine = zt;
    queueMicrotask(() => {
        zt.start();
    });
})();
