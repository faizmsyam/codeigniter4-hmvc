var xi = Object.defineProperty;
var Te = (e) => {
  throw TypeError(e);
};
var Bi = (e, t, i) => t in e ? xi(e, t, { enumerable: !0, configurable: !0, writable: !0, value: i }) : e[t] = i;
var it = (e, t, i) => Bi(e, typeof t != "symbol" ? t + "" : t, i), ee = (e, t, i) => t.has(e) || Te("Cannot " + i);
var L = (e, t, i) => (ee(e, t, "read from private field"), i ? i.call(e) : t.get(e)), tt = (e, t, i) => t.has(e) ? Te("Cannot add the same private member more than once") : t instanceof WeakSet ? t.add(e) : t.set(e, i), vt = (e, t, i, n) => (ee(e, t, "write to private field"), n ? n.call(e, i) : t.set(e, i), i), K = (e, t, i) => (ee(e, t, "access private method"), i);
import { onMount as Fi, onDestroy as Hi } from "svelte";
const Vi = "5";
var qe;
typeof window < "u" && ((qe = window.__svelte ?? (window.__svelte = {})).v ?? (qe.v = /* @__PURE__ */ new Set())).add(Vi);
const Wi = 1, ji = 4, Gi = 8, zi = 16, Ki = 2, R = Symbol(), Yi = "http://www.w3.org/1999/xhtml", qi = "@attach", de = !1;
var $e = Array.isArray, $i = Array.prototype.indexOf, Xi = Object.defineProperty, jt = Object.getOwnPropertyDescriptor, Qi = Object.getOwnPropertyDescriptors, Zi = Object.prototype, Ji = Array.prototype, Xe = Object.getPrototypeOf;
function tn(e) {
  for (var t = 0; t < e.length; t++)
    e[t]();
}
function Qe() {
  var e, t, i = new Promise((n, s) => {
    e = n, t = s;
  });
  return { promise: i, resolve: e, reject: t };
}
const M = 2, ve = 4, Ze = 8, Je = 1 << 24, Ct = 16, gt = 32, Tt = 64, be = 128, q = 512, B = 1024, j = 2048, Q = 4096, Et = 8192, et = 16384, we = 32768, Pe = 65536, Oe = 1 << 17, ti = 1 << 18, Jt = 1 << 19, en = 1 << 20, pt = 32768, fe = 1 << 21, Ie = 1 << 22, nt = 1 << 23, ct = Symbol("$state"), nn = Symbol("legacy props"), sn = Symbol(""), bt = new class extends Error {
  constructor() {
    super(...arguments);
    it(this, "name", "StaleReactionError");
    it(this, "message", "The reaction that called `getAbortSignal()` was re-run or destroyed");
  }
}();
function rn() {
  throw new Error("https://svelte.dev/e/async_derived_orphan");
}
function on(e) {
  throw new Error("https://svelte.dev/e/effect_in_teardown");
}
function ln() {
  throw new Error("https://svelte.dev/e/effect_in_unowned_derived");
}
function an(e) {
  throw new Error("https://svelte.dev/e/effect_orphan");
}
function un() {
  throw new Error("https://svelte.dev/e/effect_update_depth_exceeded");
}
function cn(e) {
  throw new Error("https://svelte.dev/e/props_invalid_value");
}
function dn() {
  throw new Error("https://svelte.dev/e/state_descriptors_fixed");
}
function fn() {
  throw new Error("https://svelte.dev/e/state_prototype_fixed");
}
function hn() {
  throw new Error("https://svelte.dev/e/state_unsafe_mutation");
}
function pn() {
  console.warn("https://svelte.dev/e/select_multiple_invalid_value");
}
function ei(e) {
  return e === this.v;
}
function _n(e, t) {
  return e != e ? t == t : e !== t || e !== null && typeof e == "object" || typeof e == "function";
}
function Cn(e) {
  return !_n(e, this.v);
}
let gn = !1, Z = null;
function zt(e) {
  Z = e;
}
function mn(e, t = !1, i) {
  Z = {
    p: Z,
    i: !1,
    c: null,
    e: null,
    s: e,
    x: null,
    l: null
  };
}
function yn(e) {
  var t = (
    /** @type {ComponentContext} */
    Z
  ), i = t.e;
  if (i !== null) {
    t.e = null;
    for (var n of i)
      hi(n);
  }
  return e !== void 0 && (t.x = e), t.i = !0, Z = t.p, e ?? /** @type {T} */
  {};
}
function ii() {
  return !0;
}
let wt = [];
function vn() {
  var e = wt;
  wt = [], tn(e);
}
function te(e) {
  if (wt.length === 0) {
    var t = wt;
    queueMicrotask(() => {
      t === wt && vn();
    });
  }
  wt.push(e);
}
function bn(e) {
  var t = D;
  if (t === null)
    return w.f |= nt, e;
  if ((t.f & we) === 0) {
    if ((t.f & be) === 0)
      throw e;
    t.b.error(e);
  } else
    Kt(e, t);
}
function Kt(e, t) {
  for (; t !== null; ) {
    if ((t.f & be) !== 0)
      try {
        t.b.error(e);
        return;
      } catch (i) {
        e = i;
      }
    t = t.parent;
  }
  throw e;
}
const Ft = /* @__PURE__ */ new Set();
let N = null, P = null, $ = [], Ee = null, he = !1;
var Nt, Dt, at, ut, xt, At, Lt, k, pe, Rt, _e, ni, si;
const Zt = class Zt {
  constructor() {
    tt(this, k);
    it(this, "committed", !1);
    /**
     * The current values of any sources that are updated in this batch
     * They keys of this map are identical to `this.#previous`
     * @type {Map<Source, any>}
     */
    it(this, "current", /* @__PURE__ */ new Map());
    /**
     * The values of any sources that are updated in this batch _before_ those updates took place.
     * They keys of this map are identical to `this.#current`
     * @type {Map<Source, any>}
     */
    it(this, "previous", /* @__PURE__ */ new Map());
    /**
     * When the batch is committed (and the DOM is updated), we need to remove old branches
     * and append new ones by calling the functions added inside (if/each/key/etc) blocks
     * @type {Set<() => void>}
     */
    tt(this, Nt, /* @__PURE__ */ new Set());
    /**
     * If a fork is discarded, we need to destroy any effects that are no longer needed
     * @type {Set<(batch: Batch) => void>}
     */
    tt(this, Dt, /* @__PURE__ */ new Set());
    /**
     * The number of async effects that are currently in flight
     */
    tt(this, at, 0);
    /**
     * The number of async effects that are currently in flight, _not_ inside a pending boundary
     */
    tt(this, ut, 0);
    /**
     * A deferred that resolves when the batch is committed, used with `settled()`
     * TODO replace with Promise.withResolvers once supported widely enough
     * @type {{ promise: Promise<void>, resolve: (value?: any) => void, reject: (reason: unknown) => void } | null}
     */
    tt(this, xt, null);
    /**
     * Deferred effects (which run after async work has completed) that are DIRTY
     * @type {Set<Effect>}
     */
    tt(this, At, /* @__PURE__ */ new Set());
    /**
     * Deferred effects that are MAYBE_DIRTY
     * @type {Set<Effect>}
     */
    tt(this, Lt, /* @__PURE__ */ new Set());
    /**
     * A set of branches that still exist, but will be destroyed when this batch
     * is committed — we skip over these during `process`
     * @type {Set<Effect>}
     */
    it(this, "skipped_effects", /* @__PURE__ */ new Set());
    it(this, "is_fork", !1);
  }
  is_deferred() {
    return this.is_fork || L(this, ut) > 0;
  }
  /**
   *
   * @param {Effect[]} root_effects
   */
  process(t) {
    var n;
    $ = [], this.apply();
    var i = {
      parent: null,
      effect: null,
      effects: [],
      render_effects: []
    };
    for (const s of t)
      K(this, k, pe).call(this, s, i);
    this.is_fork || K(this, k, ni).call(this), this.is_deferred() ? (K(this, k, Rt).call(this, i.effects), K(this, k, Rt).call(this, i.render_effects)) : (N = null, Re(i.render_effects), Re(i.effects), (n = L(this, xt)) == null || n.resolve()), P = null;
  }
  /**
   * Associate a change to a given source with the current
   * batch, noting its previous and current values
   * @param {Source} source
   * @param {any} value
   */
  capture(t, i) {
    this.previous.has(t) || this.previous.set(t, i), (t.f & nt) === 0 && (this.current.set(t, t.v), P == null || P.set(t, t.v));
  }
  activate() {
    N = this, this.apply();
  }
  deactivate() {
    N === this && (N = null, P = null);
  }
  flush() {
    if (this.activate(), $.length > 0) {
      if (wn(), N !== null && N !== this)
        return;
    } else L(this, at) === 0 && this.process([]);
    this.deactivate();
  }
  discard() {
    for (const t of L(this, Dt)) t(this);
    L(this, Dt).clear();
  }
  /**
   *
   * @param {boolean} blocking
   */
  increment(t) {
    vt(this, at, L(this, at) + 1), t && vt(this, ut, L(this, ut) + 1);
  }
  /**
   *
   * @param {boolean} blocking
   */
  decrement(t) {
    vt(this, at, L(this, at) - 1), t && vt(this, ut, L(this, ut) - 1), this.revive();
  }
  revive() {
    for (const t of L(this, At))
      L(this, Lt).delete(t), H(t, j), St(t);
    for (const t of L(this, Lt))
      H(t, Q), St(t);
    this.flush();
  }
  /** @param {() => void} fn */
  oncommit(t) {
    L(this, Nt).add(t);
  }
  /** @param {(batch: Batch) => void} fn */
  ondiscard(t) {
    L(this, Dt).add(t);
  }
  settled() {
    return (L(this, xt) ?? vt(this, xt, Qe())).promise;
  }
  static ensure() {
    if (N === null) {
      const t = N = new Zt();
      Ft.add(N), Zt.enqueue(() => {
        N === t && t.flush();
      });
    }
    return N;
  }
  /** @param {() => void} task */
  static enqueue(t) {
    te(t);
  }
  apply() {
  }
};
Nt = new WeakMap(), Dt = new WeakMap(), at = new WeakMap(), ut = new WeakMap(), xt = new WeakMap(), At = new WeakMap(), Lt = new WeakMap(), k = new WeakSet(), /**
 * Traverse the effect tree, executing effects or stashing
 * them for later execution as appropriate
 * @param {Effect} root
 * @param {EffectTarget} target
 */
pe = function(t, i) {
  var c;
  t.f ^= B;
  for (var n = t.first; n !== null; ) {
    var s = n.f, r = (s & (gt | Tt)) !== 0, o = r && (s & B) !== 0, l = o || (s & Et) !== 0 || this.skipped_effects.has(n);
    if ((n.f & be) !== 0 && ((c = n.b) != null && c.is_pending()) && (i = {
      parent: i,
      effect: n,
      effects: [],
      render_effects: []
    }), !l && n.fn !== null) {
      r ? n.f ^= B : (s & ve) !== 0 ? i.effects.push(n) : Bt(n) && ((n.f & Ct) !== 0 && L(this, At).add(n), Ut(n));
      var u = n.first;
      if (u !== null) {
        n = u;
        continue;
      }
    }
    var a = n.parent;
    for (n = n.next; n === null && a !== null; )
      a === i.effect && (K(this, k, Rt).call(this, i.effects), K(this, k, Rt).call(this, i.render_effects), i = /** @type {EffectTarget} */
      i.parent), n = a.next, a = a.parent;
  }
}, /**
 * @param {Effect[]} effects
 */
Rt = function(t) {
  for (const i of t)
    (i.f & j) !== 0 ? L(this, At).add(i) : (i.f & Q) !== 0 && L(this, Lt).add(i), K(this, k, _e).call(this, i.deps), H(i, B);
}, /**
 * @param {Value[] | null} deps
 */
_e = function(t) {
  if (t !== null)
    for (const i of t)
      (i.f & M) === 0 || (i.f & pt) === 0 || (i.f ^= pt, K(this, k, _e).call(
        this,
        /** @type {Derived} */
        i.deps
      ));
}, ni = function() {
  if (L(this, ut) === 0) {
    for (const t of L(this, Nt)) t();
    L(this, Nt).clear();
  }
  L(this, at) === 0 && K(this, k, si).call(this);
}, si = function() {
  var r;
  if (Ft.size > 1) {
    this.previous.clear();
    var t = P, i = !0, n = {
      parent: null,
      effect: null,
      effects: [],
      render_effects: []
    };
    for (const o of Ft) {
      if (o === this) {
        i = !1;
        continue;
      }
      const l = [];
      for (const [a, c] of this.current) {
        if (o.current.has(a))
          if (i && c !== o.current.get(a))
            o.current.set(a, c);
          else
            continue;
        l.push(a);
      }
      if (l.length === 0)
        continue;
      const u = [...o.current.keys()].filter((a) => !this.current.has(a));
      if (u.length > 0) {
        var s = $;
        $ = [];
        const a = /* @__PURE__ */ new Set(), c = /* @__PURE__ */ new Map();
        for (const d of l)
          ri(d, u, a, c);
        if ($.length > 0) {
          N = o, o.apply();
          for (const d of $)
            K(r = o, k, pe).call(r, d, n);
          o.deactivate();
        }
        $ = s;
      }
    }
    N = null, P = t;
  }
  this.committed = !0, Ft.delete(this);
};
let Yt = Zt;
function wn() {
  var e = dt;
  he = !0;
  var t = null;
  try {
    var i = 0;
    for (Xt(!0); $.length > 0; ) {
      var n = Yt.ensure();
      if (i++ > 1e3) {
        var s, r;
        In();
      }
      n.process($), st.clear();
    }
  } finally {
    he = !1, Xt(e), Ee = null;
  }
}
function In() {
  try {
    un();
  } catch (e) {
    Kt(e, Ee);
  }
}
let Y = null;
function Re(e) {
  var t = e.length;
  if (t !== 0) {
    for (var i = 0; i < t; ) {
      var n = e[i++];
      if ((n.f & (et | Et)) === 0 && Bt(n) && (Y = /* @__PURE__ */ new Set(), Ut(n), n.deps === null && n.first === null && n.nodes === null && (n.teardown === null && n.ac === null ? mi(n) : n.fn = null), (Y == null ? void 0 : Y.size) > 0)) {
        st.clear();
        for (const s of Y) {
          if ((s.f & (et | Et)) !== 0) continue;
          const r = [s];
          let o = s.parent;
          for (; o !== null; )
            Y.has(o) && (Y.delete(o), r.push(o)), o = o.parent;
          for (let l = r.length - 1; l >= 0; l--) {
            const u = r[l];
            (u.f & (et | Et)) === 0 && Ut(u);
          }
        }
        Y.clear();
      }
    }
    Y = null;
  }
}
function ri(e, t, i, n) {
  if (!i.has(e) && (i.add(e), e.reactions !== null))
    for (const s of e.reactions) {
      const r = s.f;
      (r & M) !== 0 ? ri(
        /** @type {Derived} */
        s,
        t,
        i,
        n
      ) : (r & (Ie | Ct)) !== 0 && (r & j) === 0 && oi(s, t, n) && (H(s, j), St(
        /** @type {Effect} */
        s
      ));
    }
}
function oi(e, t, i) {
  const n = i.get(e);
  if (n !== void 0) return n;
  if (e.deps !== null)
    for (const s of e.deps) {
      if (t.includes(s))
        return !0;
      if ((s.f & M) !== 0 && oi(
        /** @type {Derived} */
        s,
        t,
        i
      ))
        return i.set(
          /** @type {Derived} */
          s,
          !0
        ), !0;
    }
  return i.set(e, !1), !1;
}
function St(e) {
  for (var t = Ee = e; t.parent !== null; ) {
    t = t.parent;
    var i = t.f;
    if (he && t === D && (i & Ct) !== 0 && (i & ti) === 0)
      return;
    if ((i & (Tt | gt)) !== 0) {
      if ((i & B) === 0) return;
      t.f ^= B;
    }
  }
  $.push(t);
}
function En(e, t, i, n) {
  const s = Ne;
  if (i.length === 0 && e.length === 0) {
    n(t.map(s));
    return;
  }
  var r = N, o = (
    /** @type {Effect} */
    D
  ), l = Nn();
  function u() {
    Promise.all(i.map((a) => /* @__PURE__ */ Dn(a))).then((a) => {
      l();
      try {
        n([...t.map(s), ...a]);
      } catch (c) {
        (o.f & et) === 0 && Kt(c, o);
      }
      r == null || r.deactivate(), qt();
    }).catch((a) => {
      Kt(a, o);
    });
  }
  e.length > 0 ? Promise.all(e).then(() => {
    l();
    try {
      return u();
    } finally {
      r == null || r.deactivate(), qt();
    }
  }) : u();
}
function Nn() {
  var e = D, t = w, i = Z, n = N;
  return function(r = !0) {
    rt(e), J(t), zt(i), r && (n == null || n.activate());
  };
}
function qt() {
  rt(null), J(null), zt(null);
}
// @__NO_SIDE_EFFECTS__
function Ne(e) {
  var t = M | j, i = w !== null && (w.f & M) !== 0 ? (
    /** @type {Derived} */
    w
  ) : null;
  return D !== null && (D.f |= Jt), {
    ctx: Z,
    deps: null,
    effects: null,
    equals: ei,
    f: t,
    fn: e,
    reactions: null,
    rv: 0,
    v: (
      /** @type {V} */
      R
    ),
    wv: 0,
    parent: i ?? D,
    ac: null
  };
}
// @__NO_SIDE_EFFECTS__
function Dn(e, t) {
  let i = (
    /** @type {Effect | null} */
    D
  );
  i === null && rn();
  var n = (
    /** @type {Boundary} */
    i.b
  ), s = (
    /** @type {Promise<V>} */
    /** @type {unknown} */
    void 0
  ), r = ci(
    /** @type {V} */
    R
  ), o = !w, l = /* @__PURE__ */ new Map();
  return Hn(() => {
    var f;
    var u = Qe();
    s = u.promise;
    try {
      Promise.resolve(e()).then(u.resolve, u.reject).then(() => {
        a === N && a.committed && a.deactivate(), qt();
      });
    } catch (h) {
      u.reject(h), qt();
    }
    var a = (
      /** @type {Batch} */
      N
    );
    if (o) {
      var c = !n.is_pending();
      n.update_pending_count(1), a.increment(c), (f = l.get(a)) == null || f.reject(bt), l.delete(a), l.set(a, u);
    }
    const d = (h, C = void 0) => {
      if (a.activate(), C)
        C !== bt && (r.f |= nt, ge(r, C));
      else {
        (r.f & nt) !== 0 && (r.f ^= nt), ge(r, h);
        for (const [b, v] of l) {
          if (l.delete(b), b === a) break;
          v.reject(bt);
        }
      }
      o && (n.update_pending_count(-1), a.decrement(c));
    };
    u.promise.then(d, (h) => d(null, h || "unknown"));
  }), fi(() => {
    for (const u of l.values())
      u.reject(bt);
  }), new Promise((u) => {
    function a(c) {
      function d() {
        c === s ? u(r) : a(s);
      }
      c.then(d, d);
    }
    a(s);
  });
}
// @__NO_SIDE_EFFECTS__
function An(e) {
  const t = /* @__PURE__ */ Ne(e);
  return t.equals = Cn, t;
}
function li(e) {
  var t = e.effects;
  if (t !== null) {
    e.effects = null;
    for (var i = 0; i < t.length; i += 1)
      _t(
        /** @type {Effect} */
        t[i]
      );
  }
}
function Ln(e) {
  for (var t = e.parent; t !== null; ) {
    if ((t.f & M) === 0)
      return (t.f & et) === 0 ? (
        /** @type {Effect} */
        t
      ) : null;
    t = t.parent;
  }
  return null;
}
function De(e) {
  var t, i = D;
  rt(Ln(e));
  try {
    e.f &= ~pt, li(e), t = wi(e);
  } finally {
    rt(i);
  }
  return t;
}
function ai(e) {
  var t = De(e);
  if (e.equals(t) || (N != null && N.is_fork || (e.v = t), e.wv = vi()), !yt)
    if (P !== null)
      ($t() || N != null && N.is_fork) && P.set(e, t);
    else {
      var i = (e.f & q) === 0 ? Q : B;
      H(e, i);
    }
}
let Ce = /* @__PURE__ */ new Set();
const st = /* @__PURE__ */ new Map();
let ui = !1;
function ci(e, t) {
  var i = {
    f: 0,
    // TODO ideally we could skip this altogether, but it causes type errors
    v: e,
    reactions: null,
    equals: ei,
    rv: 0,
    wv: 0
  };
  return i;
}
// @__NO_SIDE_EFFECTS__
function z(e, t) {
  const i = ci(e);
  return Gn(i), i;
}
function W(e, t, i = !1) {
  w !== null && // since we are untracking the function inside `$inspect.with` we need to add this check
  // to ensure we error if state is set inside an inspect effect
  (!X || (w.f & Oe) !== 0) && ii() && (w.f & (M | Ct | Ie | Oe)) !== 0 && !(F != null && F.includes(e)) && hn();
  let n = i ? It(t) : t;
  return ge(e, n);
}
function ge(e, t) {
  if (!e.equals(t)) {
    var i = e.v;
    yt ? st.set(e, t) : st.set(e, i), e.v = t;
    var n = Yt.ensure();
    n.capture(e, i), (e.f & M) !== 0 && ((e.f & j) !== 0 && De(
      /** @type {Derived} */
      e
    ), H(e, (e.f & q) !== 0 ? B : Q)), e.wv = vi(), di(e, j), D !== null && (D.f & B) !== 0 && (D.f & (gt | Tt)) === 0 && (G === null ? zn([e]) : G.push(e)), !n.is_fork && Ce.size > 0 && !ui && Sn();
  }
  return t;
}
function Sn() {
  ui = !1;
  var e = dt;
  Xt(!0);
  const t = Array.from(Ce);
  try {
    for (const i of t)
      (i.f & B) !== 0 && H(i, Q), Bt(i) && Ut(i);
  } finally {
    Xt(e);
  }
  Ce.clear();
}
function ie(e) {
  W(e, e.v + 1);
}
function di(e, t) {
  var i = e.reactions;
  if (i !== null)
    for (var n = i.length, s = 0; s < n; s++) {
      var r = i[s], o = r.f, l = (o & j) === 0;
      if (l && H(r, t), (o & M) !== 0) {
        var u = (
          /** @type {Derived} */
          r
        );
        P == null || P.delete(u), (o & pt) === 0 && (o & q && (r.f |= pt), di(u, Q));
      } else l && ((o & Ct) !== 0 && Y !== null && Y.add(
        /** @type {Effect} */
        r
      ), St(
        /** @type {Effect} */
        r
      ));
    }
}
function It(e) {
  if (typeof e != "object" || e === null || ct in e)
    return e;
  const t = Xe(e);
  if (t !== Zi && t !== Ji)
    return e;
  var i = /* @__PURE__ */ new Map(), n = $e(e), s = /* @__PURE__ */ z(0), r = ft, o = (l) => {
    if (ft === r)
      return l();
    var u = w, a = ft;
    J(null), Ue(r);
    var c = l();
    return J(u), Ue(a), c;
  };
  return n && i.set("length", /* @__PURE__ */ z(
    /** @type {any[]} */
    e.length
  )), new Proxy(
    /** @type {any} */
    e,
    {
      defineProperty(l, u, a) {
        (!("value" in a) || a.configurable === !1 || a.enumerable === !1 || a.writable === !1) && dn();
        var c = i.get(u);
        return c === void 0 ? c = o(() => {
          var d = /* @__PURE__ */ z(a.value);
          return i.set(u, d), d;
        }) : W(c, a.value, !0), !0;
      },
      deleteProperty(l, u) {
        var a = i.get(u);
        if (a === void 0) {
          if (u in l) {
            const c = o(() => /* @__PURE__ */ z(R));
            i.set(u, c), ie(s);
          }
        } else
          W(a, R), ie(s);
        return !0;
      },
      get(l, u, a) {
        var h;
        if (u === ct)
          return e;
        var c = i.get(u), d = u in l;
        if (c === void 0 && (!d || (h = jt(l, u)) != null && h.writable) && (c = o(() => {
          var C = It(d ? l[u] : R), b = /* @__PURE__ */ z(C);
          return b;
        }), i.set(u, c)), c !== void 0) {
          var f = I(c);
          return f === R ? void 0 : f;
        }
        return Reflect.get(l, u, a);
      },
      getOwnPropertyDescriptor(l, u) {
        var a = Reflect.getOwnPropertyDescriptor(l, u);
        if (a && "value" in a) {
          var c = i.get(u);
          c && (a.value = I(c));
        } else if (a === void 0) {
          var d = i.get(u), f = d == null ? void 0 : d.v;
          if (d !== void 0 && f !== R)
            return {
              enumerable: !0,
              configurable: !0,
              value: f,
              writable: !0
            };
        }
        return a;
      },
      has(l, u) {
        var f;
        if (u === ct)
          return !0;
        var a = i.get(u), c = a !== void 0 && a.v !== R || Reflect.has(l, u);
        if (a !== void 0 || D !== null && (!c || (f = jt(l, u)) != null && f.writable)) {
          a === void 0 && (a = o(() => {
            var h = c ? It(l[u]) : R, C = /* @__PURE__ */ z(h);
            return C;
          }), i.set(u, a));
          var d = I(a);
          if (d === R)
            return !1;
        }
        return c;
      },
      set(l, u, a, c) {
        var p;
        var d = i.get(u), f = u in l;
        if (n && u === "length")
          for (var h = a; h < /** @type {Source<number>} */
          d.v; h += 1) {
            var C = i.get(h + "");
            C !== void 0 ? W(C, R) : h in l && (C = o(() => /* @__PURE__ */ z(R)), i.set(h + "", C));
          }
        if (d === void 0)
          (!f || (p = jt(l, u)) != null && p.writable) && (d = o(() => /* @__PURE__ */ z(void 0)), W(d, It(a)), i.set(u, d));
        else {
          f = d.v !== R;
          var b = o(() => It(a));
          W(d, b);
        }
        var v = Reflect.getOwnPropertyDescriptor(l, u);
        if (v != null && v.set && v.set.call(c, a), !f) {
          if (n && typeof u == "string") {
            var g = (
              /** @type {Source<number>} */
              i.get("length")
            ), m = Number(u);
            Number.isInteger(m) && m >= g.v && W(g, m + 1);
          }
          ie(s);
        }
        return !0;
      },
      ownKeys(l) {
        I(s);
        var u = Reflect.ownKeys(l).filter((d) => {
          var f = i.get(d);
          return f === void 0 || f.v !== R;
        });
        for (var [a, c] of i)
          c.v !== R && !(a in l) && u.push(a);
        return u;
      },
      setPrototypeOf() {
        fn();
      }
    }
  );
}
function ke(e) {
  try {
    if (e !== null && typeof e == "object" && ct in e)
      return e[ct];
  } catch {
  }
  return e;
}
function Tn(e, t) {
  return Object.is(ke(e), ke(t));
}
var Pn, On, Rn;
// @__NO_SIDE_EFFECTS__
function kn(e) {
  return (
    /** @type {TemplateNode | null} */
    On.call(e)
  );
}
// @__NO_SIDE_EFFECTS__
function Mn(e) {
  return (
    /** @type {TemplateNode | null} */
    Rn.call(e)
  );
}
function Un(e, t) {
  if (t) {
    const i = document.body;
    e.autofocus = !0, te(() => {
      document.activeElement === i && e.focus();
    });
  }
}
function Ae(e) {
  var t = w, i = D;
  J(null), rt(null);
  try {
    return e();
  } finally {
    J(t), rt(i);
  }
}
function xn(e) {
  D === null && (w === null && an(), ln()), yt && on();
}
function Bn(e, t) {
  var i = t.last;
  i === null ? t.last = t.first = e : (i.next = e, e.prev = i, t.last = e);
}
function mt(e, t, i) {
  var n = D;
  n !== null && (n.f & Et) !== 0 && (e |= Et);
  var s = {
    ctx: Z,
    deps: null,
    nodes: null,
    f: e | j | q,
    first: null,
    fn: t,
    last: null,
    next: null,
    parent: n,
    b: n && n.b,
    prev: null,
    teardown: null,
    wv: 0,
    ac: null
  };
  if (i)
    try {
      Ut(s), s.f |= we;
    } catch (l) {
      throw _t(s), l;
    }
  else t !== null && St(s);
  var r = s;
  if (i && r.deps === null && r.teardown === null && r.nodes === null && r.first === r.last && // either `null`, or a singular child
  (r.f & Jt) === 0 && (r = r.first, (e & Ct) !== 0 && (e & Pe) !== 0 && r !== null && (r.f |= Pe)), r !== null && (r.parent = n, n !== null && Bn(r, n), w !== null && (w.f & M) !== 0 && (e & Tt) === 0)) {
    var o = (
      /** @type {Derived} */
      w
    );
    (o.effects ?? (o.effects = [])).push(r);
  }
  return s;
}
function $t() {
  return w !== null && !X;
}
function fi(e) {
  const t = mt(Ze, null, !1);
  return H(t, B), t.teardown = e, t;
}
function Fn(e) {
  xn();
  var t = (
    /** @type {Effect} */
    D.f
  ), i = !w && (t & gt) !== 0 && (t & we) === 0;
  if (i) {
    var n = (
      /** @type {ComponentContext} */
      Z
    );
    (n.e ?? (n.e = [])).push(e);
  } else
    return hi(e);
}
function hi(e) {
  return mt(ve | en, e, !1);
}
function Le(e) {
  return mt(ve, e, !1);
}
function Hn(e) {
  return mt(Ie | Jt, e, !0);
}
function Vn(e, t = 0) {
  return mt(Ze | t, e, !0);
}
function pi(e, t = 0) {
  var i = mt(Je | t, e, !0);
  return i;
}
function _i(e) {
  return mt(gt | Jt, e, !0);
}
function Ci(e) {
  var t = e.teardown;
  if (t !== null) {
    const i = yt, n = w;
    Me(!0), J(null);
    try {
      t.call(null);
    } finally {
      Me(i), J(n);
    }
  }
}
function gi(e, t = !1) {
  var i = e.first;
  for (e.first = e.last = null; i !== null; ) {
    const s = i.ac;
    s !== null && Ae(() => {
      s.abort(bt);
    });
    var n = i.next;
    (i.f & Tt) !== 0 ? i.parent = null : _t(i, t), i = n;
  }
}
function Wn(e) {
  for (var t = e.first; t !== null; ) {
    var i = t.next;
    (t.f & gt) === 0 && _t(t), t = i;
  }
}
function _t(e, t = !0) {
  var i = !1;
  (t || (e.f & ti) !== 0) && e.nodes !== null && e.nodes.end !== null && (jn(
    e.nodes.start,
    /** @type {TemplateNode} */
    e.nodes.end
  ), i = !0), gi(e, t && !i), Qt(e, 0), H(e, et);
  var n = e.nodes && e.nodes.t;
  if (n !== null)
    for (const r of n)
      r.stop();
  Ci(e);
  var s = e.parent;
  s !== null && s.first !== null && mi(e), e.next = e.prev = e.teardown = e.ctx = e.deps = e.fn = e.nodes = e.ac = null;
}
function jn(e, t) {
  for (; e !== null; ) {
    var i = e === t ? null : /* @__PURE__ */ Mn(e);
    e.remove(), e = i;
  }
}
function mi(e) {
  var t = e.parent, i = e.prev, n = e.next;
  i !== null && (i.next = n), n !== null && (n.prev = i), t !== null && (t.first === e && (t.first = n), t.last === e && (t.last = i));
}
let dt = !1;
function Xt(e) {
  dt = e;
}
let yt = !1;
function Me(e) {
  yt = e;
}
let w = null, X = !1;
function J(e) {
  w = e;
}
let D = null;
function rt(e) {
  D = e;
}
let F = null;
function Gn(e) {
  w !== null && (F === null ? F = [e] : F.push(e));
}
let x = null, V = 0, G = null;
function zn(e) {
  G = e;
}
let yi = 1, Mt = 0, ft = Mt;
function Ue(e) {
  ft = e;
}
function vi() {
  return ++yi;
}
function Bt(e) {
  var t = e.f;
  if ((t & j) !== 0)
    return !0;
  if (t & M && (e.f &= ~pt), (t & Q) !== 0) {
    var i = e.deps;
    if (i !== null)
      for (var n = i.length, s = 0; s < n; s++) {
        var r = i[s];
        if (Bt(
          /** @type {Derived} */
          r
        ) && ai(
          /** @type {Derived} */
          r
        ), r.wv > e.wv)
          return !0;
      }
    (t & q) !== 0 && // During time traveling we don't want to reset the status so that
    // traversal of the graph in the other batches still happens
    P === null && H(e, B);
  }
  return !1;
}
function bi(e, t, i = !0) {
  var n = e.reactions;
  if (n !== null && !(F != null && F.includes(e)))
    for (var s = 0; s < n.length; s++) {
      var r = n[s];
      (r.f & M) !== 0 ? bi(
        /** @type {Derived} */
        r,
        t,
        !1
      ) : t === r && (i ? H(r, j) : (r.f & B) !== 0 && H(r, Q), St(
        /** @type {Effect} */
        r
      ));
    }
}
function wi(e) {
  var C;
  var t = x, i = V, n = G, s = w, r = F, o = Z, l = X, u = ft, a = e.f;
  x = /** @type {null | Value[]} */
  null, V = 0, G = null, w = (a & (gt | Tt)) === 0 ? e : null, F = null, zt(e.ctx), X = !1, ft = ++Mt, e.ac !== null && (Ae(() => {
    e.ac.abort(bt);
  }), e.ac = null);
  try {
    e.f |= fe;
    var c = (
      /** @type {Function} */
      e.fn
    ), d = c(), f = e.deps;
    if (x !== null) {
      var h;
      if (Qt(e, V), f !== null && V > 0)
        for (f.length = V + x.length, h = 0; h < x.length; h++)
          f[V + h] = x[h];
      else
        e.deps = f = x;
      if ($t() && (e.f & q) !== 0)
        for (h = V; h < f.length; h++)
          ((C = f[h]).reactions ?? (C.reactions = [])).push(e);
    } else f !== null && V < f.length && (Qt(e, V), f.length = V);
    if (ii() && G !== null && !X && f !== null && (e.f & (M | Q | j)) === 0)
      for (h = 0; h < /** @type {Source[]} */
      G.length; h++)
        bi(
          G[h],
          /** @type {Effect} */
          e
        );
    return s !== null && s !== e && (Mt++, G !== null && (n === null ? n = G : n.push(.../** @type {Source[]} */
    G))), (e.f & nt) !== 0 && (e.f ^= nt), d;
  } catch (b) {
    return bn(b);
  } finally {
    e.f ^= fe, x = t, V = i, G = n, w = s, F = r, zt(o), X = l, ft = u;
  }
}
function Kn(e, t) {
  let i = t.reactions;
  if (i !== null) {
    var n = $i.call(i, e);
    if (n !== -1) {
      var s = i.length - 1;
      s === 0 ? i = t.reactions = null : (i[n] = i[s], i.pop());
    }
  }
  i === null && (t.f & M) !== 0 && // Destroying a child effect while updating a parent effect can cause a dependency to appear
  // to be unused, when in fact it is used by the currently-updating parent. Checking `new_deps`
  // allows us to skip the expensive work of disconnecting and immediately reconnecting it
  (x === null || !x.includes(t)) && (H(t, Q), (t.f & q) !== 0 && (t.f ^= q, t.f &= ~pt), li(
    /** @type {Derived} **/
    t
  ), Qt(
    /** @type {Derived} **/
    t,
    0
  ));
}
function Qt(e, t) {
  var i = e.deps;
  if (i !== null)
    for (var n = t; n < i.length; n++)
      Kn(e, i[n]);
}
function Ut(e) {
  var t = e.f;
  if ((t & et) === 0) {
    H(e, B);
    var i = D, n = dt;
    D = e, dt = !0;
    try {
      (t & (Ct | Je)) !== 0 ? Wn(e) : gi(e), Ci(e);
      var s = wi(e);
      e.teardown = typeof s == "function" ? s : null, e.wv = yi;
      var r;
      de && gn && (e.f & j) !== 0 && e.deps;
    } finally {
      dt = n, D = i;
    }
  }
}
function I(e) {
  var t = e.f, i = (t & M) !== 0;
  if (w !== null && !X) {
    var n = D !== null && (D.f & et) !== 0;
    if (!n && !(F != null && F.includes(e))) {
      var s = w.deps;
      if ((w.f & fe) !== 0)
        e.rv < Mt && (e.rv = Mt, x === null && s !== null && s[V] === e ? V++ : x === null ? x = [e] : x.includes(e) || x.push(e));
      else {
        (w.deps ?? (w.deps = [])).push(e);
        var r = e.reactions;
        r === null ? e.reactions = [w] : r.includes(w) || r.push(w);
      }
    }
  }
  if (yt) {
    if (st.has(e))
      return st.get(e);
    if (i) {
      var o = (
        /** @type {Derived} */
        e
      ), l = o.v;
      return ((o.f & B) === 0 && o.reactions !== null || Ei(o)) && (l = De(o)), st.set(o, l), l;
    }
  } else i && (!(P != null && P.has(e)) || N != null && N.is_fork && !$t()) && (o = /** @type {Derived} */
  e, Bt(o) && ai(o), dt && $t() && (o.f & q) === 0 && Ii(o));
  if (P != null && P.has(e))
    return P.get(e);
  if ((e.f & nt) !== 0)
    throw e.v;
  return e.v;
}
function Ii(e) {
  if (e.deps !== null) {
    e.f ^= q;
    for (const t of e.deps)
      (t.reactions ?? (t.reactions = [])).push(e), (t.f & M) !== 0 && (t.f & q) === 0 && Ii(
        /** @type {Derived} */
        t
      );
  }
}
function Ei(e) {
  if (e.v === R) return !0;
  if (e.deps === null) return !1;
  for (const t of e.deps)
    if (st.has(t) || (t.f & M) !== 0 && Ei(
      /** @type {Derived} */
      t
    ))
      return !0;
  return !1;
}
function Ni(e) {
  var t = X;
  try {
    return X = !0, e();
  } finally {
    X = t;
  }
}
const Yn = -7169;
function H(e, t) {
  e.f = e.f & Yn | t;
}
const qn = /* @__PURE__ */ new Set(), $n = /* @__PURE__ */ new Set();
function Xn(e, t, i, n = {}) {
  function s(r) {
    if (n.capture || Zn.call(t, r), !r.cancelBubble)
      return Ae(() => i == null ? void 0 : i.call(this, r));
  }
  return e.startsWith("pointer") || e.startsWith("touch") || e === "wheel" ? te(() => {
    t.addEventListener(e, s, n);
  }) : t.addEventListener(e, s, n), s;
}
function Qn(e) {
  for (var t = 0; t < e.length; t++)
    qn.add(e[t]);
  for (var i of $n)
    i(e);
}
let xe = null;
function Zn(e) {
  var v;
  var t = this, i = (
    /** @type {Node} */
    t.ownerDocument
  ), n = e.type, s = ((v = e.composedPath) == null ? void 0 : v.call(e)) || [], r = (
    /** @type {null | Element} */
    s[0] || e.target
  );
  xe = e;
  var o = 0, l = xe === e && e.__root;
  if (l) {
    var u = s.indexOf(l);
    if (u !== -1 && (t === document || t === /** @type {any} */
    window)) {
      e.__root = t;
      return;
    }
    var a = s.indexOf(t);
    if (a === -1)
      return;
    u <= a && (o = u);
  }
  if (r = /** @type {Element} */
  s[o] || e.target, r !== t) {
    Xi(e, "currentTarget", {
      configurable: !0,
      get() {
        return r || i;
      }
    });
    var c = w, d = D;
    J(null), rt(null);
    try {
      for (var f, h = []; r !== null; ) {
        var C = r.assignedSlot || r.parentNode || /** @type {any} */
        r.host || null;
        try {
          var b = r["__" + n];
          b != null && (!/** @type {any} */
          r.disabled || // DOM could've been updated already by the time this is reached, so we check this as well
          // -> the target could not have been disabled because it emits the event in the first place
          e.target === r) && b.call(r, e);
        } catch (g) {
          f ? h.push(g) : f = g;
        }
        if (e.cancelBubble || C === t || C === null)
          break;
        r = C;
      }
      if (f) {
        for (let g of h)
          queueMicrotask(() => {
            throw g;
          });
        throw f;
      }
    } finally {
      e.__root = t, delete e.currentTarget, J(c), rt(d);
    }
  }
}
function Jn(e) {
  var t = document.createElement("template");
  return t.innerHTML = e.replaceAll("<!>", "<!---->"), t.content;
}
function ts(e, t) {
  var i = (
    /** @type {Effect} */
    D
  );
  i.nodes === null && (i.nodes = { start: e, end: t, a: null, t: null });
}
// @__NO_SIDE_EFFECTS__
function es(e, t) {
  var i = (t & Ki) !== 0, n, s = !e.startsWith("<!>");
  return () => {
    n === void 0 && (n = Jn(s ? e : "<!>" + e), n = /** @type {TemplateNode} */
    /* @__PURE__ */ kn(n));
    var r = (
      /** @type {TemplateNode} */
      i || Pn ? document.importNode(n, !0) : n.cloneNode(!0)
    );
    return ts(r, r), r;
  };
}
function is(e, t) {
  e !== null && e.before(
    /** @type {Node} */
    t
  );
}
function ns(e) {
  return e.endsWith("capture") && e !== "gotpointercapture" && e !== "lostpointercapture";
}
const ss = [
  "beforeinput",
  "click",
  "change",
  "dblclick",
  "contextmenu",
  "focusin",
  "focusout",
  "input",
  "keydown",
  "keyup",
  "mousedown",
  "mousemove",
  "mouseout",
  "mouseover",
  "mouseup",
  "pointerdown",
  "pointermove",
  "pointerout",
  "pointerover",
  "pointerup",
  "touchend",
  "touchmove",
  "touchstart"
];
function rs(e) {
  return ss.includes(e);
}
const os = {
  // no `class: 'className'` because we handle that separately
  formnovalidate: "formNoValidate",
  ismap: "isMap",
  nomodule: "noModule",
  playsinline: "playsInline",
  readonly: "readOnly",
  defaultvalue: "defaultValue",
  defaultchecked: "defaultChecked",
  srcobject: "srcObject",
  novalidate: "noValidate",
  allowfullscreen: "allowFullscreen",
  disablepictureinpicture: "disablePictureInPicture",
  disableremoteplayback: "disableRemotePlayback"
};
function ls(e) {
  return e = e.toLowerCase(), os[e] ?? e;
}
function as(e, t) {
  var i = void 0, n;
  pi(() => {
    i !== (i = t()) && (n && (_t(n), n = null), i && (n = _i(() => {
      Le(() => (
        /** @type {(node: Element) => void} */
        i(e)
      ));
    })));
  });
}
function Di(e) {
  var t, i, n = "";
  if (typeof e == "string" || typeof e == "number") n += e;
  else if (typeof e == "object") if (Array.isArray(e)) {
    var s = e.length;
    for (t = 0; t < s; t++) e[t] && (i = Di(e[t])) && (n && (n += " "), n += i);
  } else for (i in e) e[i] && (n && (n += " "), n += i);
  return n;
}
function us() {
  for (var e, t, i = 0, n = "", s = arguments.length; i < s; i++) (e = arguments[i]) && (t = Di(e)) && (n && (n += " "), n += t);
  return n;
}
function cs(e) {
  return typeof e == "object" ? us(e) : e ?? "";
}
const Be = [...` 	
\r\f \v\uFEFF`];
function ds(e, t, i) {
  var n = e == null ? "" : "" + e;
  if (i) {
    for (var s in i)
      if (i[s])
        n = n ? n + " " + s : s;
      else if (n.length)
        for (var r = s.length, o = 0; (o = n.indexOf(s, o)) >= 0; ) {
          var l = o + r;
          (o === 0 || Be.includes(n[o - 1])) && (l === n.length || Be.includes(n[l])) ? n = (o === 0 ? "" : n.substring(0, o)) + n.substring(l + 1) : o = l;
        }
  }
  return n === "" ? null : n;
}
function Fe(e, t = !1) {
  var i = t ? " !important;" : ";", n = "";
  for (var s in e) {
    var r = e[s];
    r != null && r !== "" && (n += " " + s + ": " + r + i);
  }
  return n;
}
function ne(e) {
  return e[0] !== "-" || e[1] !== "-" ? e.toLowerCase() : e;
}
function fs(e, t) {
  if (t) {
    var i = "", n, s;
    if (Array.isArray(t) ? (n = t[0], s = t[1]) : n = t, e) {
      e = String(e).replaceAll(/\s*\/\*.*?\*\/\s*/g, "").trim();
      var r = !1, o = 0, l = !1, u = [];
      n && u.push(...Object.keys(n).map(ne)), s && u.push(...Object.keys(s).map(ne));
      var a = 0, c = -1;
      const b = e.length;
      for (var d = 0; d < b; d++) {
        var f = e[d];
        if (l ? f === "/" && e[d - 1] === "*" && (l = !1) : r ? r === f && (r = !1) : f === "/" && e[d + 1] === "*" ? l = !0 : f === '"' || f === "'" ? r = f : f === "(" ? o++ : f === ")" && o--, !l && r === !1 && o === 0) {
          if (f === ":" && c === -1)
            c = d;
          else if (f === ";" || d === b - 1) {
            if (c !== -1) {
              var h = ne(e.substring(a, c).trim());
              if (!u.includes(h)) {
                f !== ";" && d++;
                var C = e.substring(a, d).trim();
                i += " " + C + ";";
              }
            }
            a = d + 1, c = -1;
          }
        }
      }
    }
    return n && (i += Fe(n)), s && (i += Fe(s, !0)), i = i.trim(), i === "" ? null : i;
  }
  return e == null ? null : String(e);
}
function hs(e, t, i, n, s, r) {
  var o = e.__className;
  if (o !== i || o === void 0) {
    var l = ds(i, n, r);
    l == null ? e.removeAttribute("class") : t ? e.className = l : e.setAttribute("class", l), e.__className = i;
  } else if (r && s !== r)
    for (var u in r) {
      var a = !!r[u];
      (s == null || a !== !!s[u]) && e.classList.toggle(u, a);
    }
  return r;
}
function se(e, t = {}, i, n) {
  for (var s in i) {
    var r = i[s];
    t[s] !== r && (i[s] == null ? e.style.removeProperty(s) : e.style.setProperty(s, r, n));
  }
}
function ps(e, t, i, n) {
  var s = e.__style;
  if (s !== t) {
    var r = fs(t, n);
    r == null ? e.removeAttribute("style") : e.style.cssText = r, e.__style = t;
  } else n && (Array.isArray(n) ? (se(e, i == null ? void 0 : i[0], n[0]), se(e, i == null ? void 0 : i[1], n[1], "important")) : se(e, i, n));
  return n;
}
function me(e, t, i = !1) {
  if (e.multiple) {
    if (t == null)
      return;
    if (!$e(t))
      return pn();
    for (var n of e.options)
      n.selected = t.includes(He(n));
    return;
  }
  for (n of e.options) {
    var s = He(n);
    if (Tn(s, t)) {
      n.selected = !0;
      return;
    }
  }
  (!i || t !== void 0) && (e.selectedIndex = -1);
}
function _s(e) {
  var t = new MutationObserver(() => {
    me(e, e.__value);
  });
  t.observe(e, {
    // Listen to option element changes
    childList: !0,
    subtree: !0,
    // because of <optgroup>
    // Listen to option element value attribute changes
    // (doesn't get notified of select value changes,
    // because that property is not reflected as an attribute)
    attributes: !0,
    attributeFilter: ["value"]
  }), fi(() => {
    t.disconnect();
  });
}
function He(e) {
  return "__value" in e ? e.__value : e.value;
}
const Pt = Symbol("class"), Ot = Symbol("style"), Ai = Symbol("is custom element"), Li = Symbol("is html");
function Cs(e, t) {
  t ? e.hasAttribute("selected") || e.setAttribute("selected", "") : e.removeAttribute("selected");
}
function Ve(e, t, i, n) {
  var s = Si(e);
  s[t] !== (s[t] = i) && (t === "loading" && (e[sn] = i), i == null ? e.removeAttribute(t) : typeof i != "string" && Ti(e).includes(t) ? e[t] = i : e.setAttribute(t, i));
}
function gs(e, t, i, n, s = !1, r = !1) {
  var o = Si(e), l = o[Ai], u = !o[Li], a = t || {}, c = e.tagName === "OPTION";
  for (var d in t)
    d in i || (i[d] = null);
  i.class ? i.class = cs(i.class) : i[Pt] && (i.class = null), i[Ot] && (i.style ?? (i.style = null));
  var f = Ti(e);
  for (const p in i) {
    let y = i[p];
    if (c && p === "value" && y == null) {
      e.value = e.__value = "", a[p] = y;
      continue;
    }
    if (p === "class") {
      var h = e.namespaceURI === "http://www.w3.org/1999/xhtml";
      hs(e, h, y, n, t == null ? void 0 : t[Pt], i[Pt]), a[p] = y, a[Pt] = i[Pt];
      continue;
    }
    if (p === "style") {
      ps(e, y, t == null ? void 0 : t[Ot], i[Ot]), a[p] = y, a[Ot] = i[Ot];
      continue;
    }
    var C = a[p];
    if (!(y === C && !(y === void 0 && e.hasAttribute(p)))) {
      a[p] = y;
      var b = p[0] + p[1];
      if (b !== "$$")
        if (b === "on") {
          const T = {}, ot = "$$" + p;
          let O = p.slice(2);
          var v = rs(O);
          if (ns(O) && (O = O.slice(0, -7), T.capture = !0), !v && C) {
            if (y != null) continue;
            e.removeEventListener(O, a[ot], T), a[ot] = null;
          }
          if (y != null)
            if (v)
              e[`__${O}`] = y, Qn([O]);
            else {
              let Mi = function(Ui) {
                a[p].call(this, Ui);
              };
              a[ot] = Xn(O, e, Mi, T);
            }
          else v && (e[`__${O}`] = void 0);
        } else if (p === "style")
          Ve(e, p, y);
        else if (p === "autofocus")
          Un(
            /** @type {HTMLElement} */
            e,
            !!y
          );
        else if (!l && (p === "__value" || p === "value" && y != null))
          e.value = e.__value = y;
        else if (p === "selected" && c)
          Cs(
            /** @type {HTMLOptionElement} */
            e,
            y
          );
        else {
          var g = p;
          u || (g = ls(g));
          var m = g === "defaultValue" || g === "defaultChecked";
          if (y == null && !l && !m)
            if (o[p] = null, g === "value" || g === "checked") {
              let T = (
                /** @type {HTMLInputElement} */
                e
              );
              const ot = t === void 0;
              if (g === "value") {
                let O = T.defaultValue;
                T.removeAttribute(g), T.defaultValue = O, T.value = T.__value = ot ? O : null;
              } else {
                let O = T.defaultChecked;
                T.removeAttribute(g), T.defaultChecked = O, T.checked = ot ? O : !1;
              }
            } else
              e.removeAttribute(p);
          else m || f.includes(g) && (l || typeof y != "string") ? (e[g] = y, g in o && (o[g] = R)) : typeof y != "function" && Ve(e, g, y);
        }
    }
  }
  return a;
}
function ms(e, t, i = [], n = [], s = [], r, o = !1, l = !1) {
  En(s, i, n, (u) => {
    var a = void 0, c = {}, d = e.nodeName === "SELECT", f = !1;
    if (pi(() => {
      var C = t(...u.map(I)), b = gs(
        e,
        a,
        C,
        r,
        o,
        l
      );
      f && d && "value" in C && me(
        /** @type {HTMLSelectElement} */
        e,
        C.value
      );
      for (let g of Object.getOwnPropertySymbols(c))
        C[g] || _t(c[g]);
      for (let g of Object.getOwnPropertySymbols(C)) {
        var v = C[g];
        g.description === qi && (!a || v !== a[g]) && (c[g] && _t(c[g]), c[g] = _i(() => as(e, () => v))), b[g] = v;
      }
      a = b;
    }), d) {
      var h = (
        /** @type {HTMLSelectElement} */
        e
      );
      Le(() => {
        me(
          h,
          /** @type {Record<string | symbol, any>} */
          a.value,
          !0
        ), _s(h);
      });
    }
    f = !0;
  });
}
function Si(e) {
  return (
    /** @type {Record<string | symbol, unknown>} **/
    // @ts-expect-error
    e.__attributes ?? (e.__attributes = {
      [Ai]: e.nodeName.includes("-"),
      [Li]: e.namespaceURI === Yi
    })
  );
}
var We = /* @__PURE__ */ new Map();
function Ti(e) {
  var t = e.getAttribute("is") || e.nodeName, i = We.get(t);
  if (i) return i;
  We.set(t, i = []);
  for (var n, s = e, r = Element.prototype; r !== s; ) {
    n = Qi(s);
    for (var o in n)
      n[o].set && i.push(o);
    s = Xe(s);
  }
  return i;
}
function je(e, t) {
  return e === t || (e == null ? void 0 : e[ct]) === t;
}
function ys(e = {}, t, i, n) {
  return Le(() => {
    var s, r;
    return Vn(() => {
      s = r, r = [], Ni(() => {
        e !== i(...r) && (t(e, ...r), s && je(i(...s), e) && t(null, ...s));
      });
    }), () => {
      te(() => {
        r && je(i(...r), e) && t(null, ...r);
      });
    };
  }), e;
}
let Ht = !1;
function vs(e) {
  var t = Ht;
  try {
    return Ht = !1, [e(), Ht];
  } finally {
    Ht = t;
  }
}
function Vt(e, t, i, n) {
  var m;
  var s = (i & Gi) !== 0, r = (i & zi) !== 0, o = (
    /** @type {V} */
    n
  ), l = !0, u = () => (l && (l = !1, o = r ? Ni(
    /** @type {() => V} */
    n
  ) : (
    /** @type {V} */
    n
  )), o), a;
  if (s) {
    var c = ct in e || nn in e;
    a = ((m = jt(e, t)) == null ? void 0 : m.set) ?? (c && t in e ? (p) => e[t] = p : void 0);
  }
  var d, f = !1;
  s ? [d, f] = vs(() => (
    /** @type {V} */
    e[t]
  )) : d = /** @type {V} */
  e[t], d === void 0 && n !== void 0 && (d = u(), a && (cn(), a(d)));
  var h;
  if (h = () => {
    var p = (
      /** @type {V} */
      e[t]
    );
    return p === void 0 ? u() : (l = !0, p);
  }, (i & ji) === 0)
    return h;
  if (a) {
    var C = e.$$legacy;
    return (
      /** @type {() => V} */
      (function(p, y) {
        return arguments.length > 0 ? ((!y || C || f) && a(y ? h() : p), p) : h();
      })
    );
  }
  var b = !1, v = ((i & Wi) !== 0 ? Ne : An)(() => (b = !1, h()));
  s && I(v);
  var g = (
    /** @type {Effect} */
    D
  );
  return (
    /** @type {() => V} */
    (function(p, y) {
      if (arguments.length > 0) {
        const T = y ? I(v) : s ? It(p) : p;
        return W(v, T), b = !0, o !== void 0 && (o = T), p;
      }
      return yt && b || (g.f & et) !== 0 ? v.v : I(v);
    })
  );
}
const bs = [
  [
    "af",
    // Afghanistan
    "93",
    0,
    null,
    "0"
  ],
  [
    "ax",
    // Åland Islands
    "358",
    1,
    ["18", "4"],
    // (4 is a mobile range shared with FI)
    "0"
  ],
  [
    "al",
    // Albania
    "355",
    0,
    null,
    "0"
  ],
  [
    "dz",
    // Algeria
    "213",
    0,
    null,
    "0"
  ],
  [
    "as",
    // American Samoa
    "1",
    5,
    ["684"],
    "1"
  ],
  [
    "ad",
    // Andorra
    "376"
  ],
  [
    "ao",
    // Angola
    "244"
  ],
  [
    "ai",
    // Anguilla
    "1",
    6,
    ["264"],
    "1"
  ],
  [
    "ag",
    // Antigua and Barbuda
    "1",
    7,
    ["268"],
    "1"
  ],
  [
    "ar",
    // Argentina
    "54",
    0,
    null,
    "0"
  ],
  [
    "am",
    // Armenia
    "374",
    0,
    null,
    "0"
  ],
  [
    "aw",
    // Aruba
    "297"
  ],
  [
    "ac",
    // Ascension Island
    "247"
  ],
  [
    "au",
    // Australia
    "61",
    0,
    ["4"],
    // (mobile range shared with CX and CC)
    "0"
  ],
  [
    "at",
    // Austria
    "43",
    0,
    null,
    "0"
  ],
  [
    "az",
    // Azerbaijan
    "994",
    0,
    null,
    "0"
  ],
  [
    "bs",
    // Bahamas
    "1",
    8,
    ["242"],
    "1"
  ],
  [
    "bh",
    // Bahrain
    "973"
  ],
  [
    "bd",
    // Bangladesh
    "880",
    0,
    null,
    "0"
  ],
  [
    "bb",
    // Barbados
    "1",
    9,
    ["246"],
    "1"
  ],
  [
    "by",
    // Belarus
    "375",
    0,
    null,
    "8"
  ],
  [
    "be",
    // Belgium
    "32",
    0,
    null,
    "0"
  ],
  [
    "bz",
    // Belize
    "501"
  ],
  [
    "bj",
    // Benin
    "229"
  ],
  [
    "bm",
    // Bermuda
    "1",
    10,
    ["441"],
    "1"
  ],
  [
    "bt",
    // Bhutan
    "975"
  ],
  [
    "bo",
    // Bolivia
    "591",
    0,
    null,
    "0"
  ],
  [
    "ba",
    // Bosnia and Herzegovina
    "387",
    0,
    null,
    "0"
  ],
  [
    "bw",
    // Botswana
    "267"
  ],
  [
    "br",
    // Brazil
    "55",
    0,
    null,
    "0"
  ],
  [
    "io",
    // British Indian Ocean Territory
    "246"
  ],
  [
    "vg",
    // British Virgin Islands
    "1",
    11,
    ["284"],
    "1"
  ],
  [
    "bn",
    // Brunei
    "673"
  ],
  [
    "bg",
    // Bulgaria
    "359",
    0,
    null,
    "0"
  ],
  [
    "bf",
    // Burkina Faso
    "226"
  ],
  [
    "bi",
    // Burundi
    "257"
  ],
  [
    "kh",
    // Cambodia
    "855",
    0,
    null,
    "0"
  ],
  [
    "cm",
    // Cameroon
    "237"
  ],
  [
    "ca",
    // Canada
    "1",
    1,
    ["204", "226", "236", "249", "250", "257", "263", "289", "306", "343", "354", "365", "367", "368", "382", "403", "416", "418", "428", "431", "437", "438", "450", "468", "474", "506", "514", "519", "548", "579", "581", "584", "587", "604", "613", "639", "647", "672", "683", "705", "709", "742", "753", "778", "780", "782", "807", "819", "825", "867", "873", "879", "902", "905", "942"],
    "1"
  ],
  [
    "cv",
    // Cape Verde
    "238"
  ],
  [
    "bq",
    // Caribbean Netherlands
    "599",
    1,
    ["3", "4", "7"]
  ],
  [
    "ky",
    // Cayman Islands
    "1",
    12,
    ["345"],
    "1"
  ],
  [
    "cf",
    // Central African Republic
    "236"
  ],
  [
    "td",
    // Chad
    "235"
  ],
  [
    "cl",
    // Chile
    "56"
  ],
  [
    "cn",
    // China
    "86",
    0,
    null,
    "0"
  ],
  [
    "cx",
    // Christmas Island
    "61",
    2,
    ["4", "89164"],
    // (4 is a mobile range shared with AU and CC)
    "0"
  ],
  [
    "cc",
    // Cocos (Keeling) Islands
    "61",
    1,
    ["4", "89162"],
    // (4 is a mobile range shared with AU and CX)
    "0"
  ],
  [
    "co",
    // Colombia
    "57",
    0,
    null,
    "0"
  ],
  [
    "km",
    // Comoros
    "269"
  ],
  [
    "cg",
    // Congo (Brazzaville)
    "242"
  ],
  [
    "cd",
    // Congo (Kinshasa)
    "243",
    0,
    null,
    "0"
  ],
  [
    "ck",
    // Cook Islands
    "682"
  ],
  [
    "cr",
    // Costa Rica
    "506"
  ],
  [
    "ci",
    // Côte d'Ivoire
    "225"
  ],
  [
    "hr",
    // Croatia
    "385",
    0,
    null,
    "0"
  ],
  [
    "cu",
    // Cuba
    "53",
    0,
    null,
    "0"
  ],
  [
    "cw",
    // Curaçao
    "599",
    0
  ],
  [
    "cy",
    // Cyprus
    "357"
  ],
  [
    "cz",
    // Czech Republic
    "420"
  ],
  [
    "dk",
    // Denmark
    "45"
  ],
  [
    "dj",
    // Djibouti
    "253"
  ],
  [
    "dm",
    // Dominica
    "1",
    13,
    ["767"],
    "1"
  ],
  [
    "do",
    // Dominican Republic
    "1",
    2,
    ["809", "829", "849"],
    "1"
  ],
  [
    "ec",
    // Ecuador
    "593",
    0,
    null,
    "0"
  ],
  [
    "eg",
    // Egypt
    "20",
    0,
    null,
    "0"
  ],
  [
    "sv",
    // El Salvador
    "503"
  ],
  [
    "gq",
    // Equatorial Guinea
    "240"
  ],
  [
    "er",
    // Eritrea
    "291",
    0,
    null,
    "0"
  ],
  [
    "ee",
    // Estonia
    "372"
  ],
  [
    "sz",
    // Eswatini
    "268"
  ],
  [
    "et",
    // Ethiopia
    "251",
    0,
    null,
    "0"
  ],
  [
    "fk",
    // Falkland Islands (Malvinas)
    "500"
  ],
  [
    "fo",
    // Faroe Islands
    "298"
  ],
  [
    "fj",
    // Fiji
    "679"
  ],
  [
    "fi",
    // Finland
    "358",
    0,
    ["4"],
    // (mobile range shared with AX)
    "0"
  ],
  [
    "fr",
    // France
    "33",
    0,
    null,
    "0"
  ],
  [
    "gf",
    // French Guiana
    "594",
    0,
    null,
    "0"
  ],
  [
    "pf",
    // French Polynesia
    "689"
  ],
  [
    "ga",
    // Gabon
    "241"
  ],
  [
    "gm",
    // Gambia
    "220"
  ],
  [
    "ge",
    // Georgia
    "995",
    0,
    null,
    "0"
  ],
  [
    "de",
    // Germany
    "49",
    0,
    null,
    "0"
  ],
  [
    "gh",
    // Ghana
    "233",
    0,
    null,
    "0"
  ],
  [
    "gi",
    // Gibraltar
    "350"
  ],
  [
    "gr",
    // Greece
    "30"
  ],
  [
    "gl",
    // Greenland
    "299"
  ],
  [
    "gd",
    // Grenada
    "1",
    14,
    ["473"],
    "1"
  ],
  [
    "gp",
    // Guadeloupe
    "590",
    0,
    null,
    "0"
  ],
  [
    "gu",
    // Guam
    "1",
    15,
    ["671"],
    "1"
  ],
  [
    "gt",
    // Guatemala
    "502"
  ],
  [
    "gg",
    // Guernsey
    "44",
    1,
    ["1481", "7781", "7839", "7911"],
    "0"
  ],
  [
    "gn",
    // Guinea
    "224"
  ],
  [
    "gw",
    // Guinea-Bissau
    "245"
  ],
  [
    "gy",
    // Guyana
    "592"
  ],
  [
    "ht",
    // Haiti
    "509"
  ],
  [
    "hn",
    // Honduras
    "504"
  ],
  [
    "hk",
    // Hong Kong SAR China
    "852"
  ],
  [
    "hu",
    // Hungary
    "36",
    0,
    null,
    "06"
  ],
  [
    "is",
    // Iceland
    "354"
  ],
  [
    "in",
    // India
    "91",
    0,
    null,
    "0"
  ],
  [
    "id",
    // Indonesia
    "62",
    0,
    null,
    "0"
  ],
  [
    "ir",
    // Iran
    "98",
    0,
    null,
    "0"
  ],
  [
    "iq",
    // Iraq
    "964",
    0,
    null,
    "0"
  ],
  [
    "ie",
    // Ireland
    "353",
    0,
    null,
    "0"
  ],
  [
    "im",
    // Isle of Man
    "44",
    2,
    ["1624", "74576", "7524", "7624", "7924"],
    "0"
  ],
  [
    "il",
    // Israel
    "972",
    0,
    null,
    "0"
  ],
  [
    "it",
    // Italy
    "39",
    0,
    ["3"]
    // (mobile range shared with VA)
  ],
  [
    "jm",
    // Jamaica
    "1",
    4,
    ["658", "876"],
    "1"
  ],
  [
    "jp",
    // Japan
    "81",
    0,
    null,
    "0"
  ],
  [
    "je",
    // Jersey
    "44",
    3,
    ["1534", "7509", "7700", "7797", "7829", "7937"],
    "0"
  ],
  [
    "jo",
    // Jordan
    "962",
    0,
    null,
    "0"
  ],
  [
    "kz",
    // Kazakhstan
    "7",
    1,
    ["33", "7"],
    // (33 is shared with RU)
    "8"
  ],
  [
    "ke",
    // Kenya
    "254",
    0,
    null,
    "0"
  ],
  [
    "ki",
    // Kiribati
    "686",
    0,
    null,
    "0"
  ],
  [
    "xk",
    // Kosovo
    "383",
    0,
    null,
    "0"
  ],
  [
    "kw",
    // Kuwait
    "965"
  ],
  [
    "kg",
    // Kyrgyzstan
    "996",
    0,
    null,
    "0"
  ],
  [
    "la",
    // Laos
    "856",
    0,
    null,
    "0"
  ],
  [
    "lv",
    // Latvia
    "371"
  ],
  [
    "lb",
    // Lebanon
    "961",
    0,
    null,
    "0"
  ],
  [
    "ls",
    // Lesotho
    "266"
  ],
  [
    "lr",
    // Liberia
    "231",
    0,
    null,
    "0"
  ],
  [
    "ly",
    // Libya
    "218",
    0,
    null,
    "0"
  ],
  [
    "li",
    // Liechtenstein
    "423",
    0,
    null,
    "0"
  ],
  [
    "lt",
    // Lithuania
    "370",
    0,
    null,
    "0"
  ],
  [
    "lu",
    // Luxembourg
    "352"
  ],
  [
    "mo",
    // Macao SAR China
    "853"
  ],
  [
    "mg",
    // Madagascar
    "261",
    0,
    null,
    "0"
  ],
  [
    "mw",
    // Malawi
    "265",
    0,
    null,
    "0"
  ],
  [
    "my",
    // Malaysia
    "60",
    0,
    null,
    "0"
  ],
  [
    "mv",
    // Maldives
    "960"
  ],
  [
    "ml",
    // Mali
    "223"
  ],
  [
    "mt",
    // Malta
    "356"
  ],
  [
    "mh",
    // Marshall Islands
    "692",
    0,
    null,
    "1"
  ],
  [
    "mq",
    // Martinique
    "596",
    0,
    null,
    "0"
  ],
  [
    "mr",
    // Mauritania
    "222"
  ],
  [
    "mu",
    // Mauritius
    "230"
  ],
  [
    "yt",
    // Mayotte
    "262",
    1,
    ["269", "639"],
    "0"
  ],
  [
    "mx",
    // Mexico
    "52"
  ],
  [
    "fm",
    // Micronesia
    "691"
  ],
  [
    "md",
    // Moldova
    "373",
    0,
    null,
    "0"
  ],
  [
    "mc",
    // Monaco
    "377",
    0,
    null,
    "0"
  ],
  [
    "mn",
    // Mongolia
    "976",
    0,
    null,
    "0"
  ],
  [
    "me",
    // Montenegro
    "382",
    0,
    null,
    "0"
  ],
  [
    "ms",
    // Montserrat
    "1",
    16,
    ["664"],
    "1"
  ],
  [
    "ma",
    // Morocco
    "212",
    0,
    ["6", "7"],
    // (mobile ranges shared with EH)
    "0"
  ],
  [
    "mz",
    // Mozambique
    "258"
  ],
  [
    "mm",
    // Myanmar (Burma)
    "95",
    0,
    null,
    "0"
  ],
  [
    "na",
    // Namibia
    "264",
    0,
    null,
    "0"
  ],
  [
    "nr",
    // Nauru
    "674"
  ],
  [
    "np",
    // Nepal
    "977",
    0,
    null,
    "0"
  ],
  [
    "nl",
    // Netherlands
    "31",
    0,
    null,
    "0"
  ],
  [
    "nc",
    // New Caledonia
    "687"
  ],
  [
    "nz",
    // New Zealand
    "64",
    0,
    null,
    "0"
  ],
  [
    "ni",
    // Nicaragua
    "505"
  ],
  [
    "ne",
    // Niger
    "227"
  ],
  [
    "ng",
    // Nigeria
    "234",
    0,
    null,
    "0"
  ],
  [
    "nu",
    // Niue
    "683"
  ],
  [
    "nf",
    // Norfolk Island
    "672"
  ],
  [
    "kp",
    // North Korea
    "850",
    0,
    null,
    "0"
  ],
  [
    "mk",
    // North Macedonia
    "389",
    0,
    null,
    "0"
  ],
  [
    "mp",
    // Northern Mariana Islands
    "1",
    17,
    ["670"],
    "1"
  ],
  [
    "no",
    // Norway
    "47",
    0,
    ["4", "9"]
    // (mobile ranges shared with SJ)
  ],
  [
    "om",
    // Oman
    "968"
  ],
  [
    "pk",
    // Pakistan
    "92",
    0,
    null,
    "0"
  ],
  [
    "pw",
    // Palau
    "680"
  ],
  [
    "ps",
    // Palestinian Territories
    "970",
    0,
    null,
    "0"
  ],
  [
    "pa",
    // Panama
    "507"
  ],
  [
    "pg",
    // Papua New Guinea
    "675"
  ],
  [
    "py",
    // Paraguay
    "595",
    0,
    null,
    "0"
  ],
  [
    "pe",
    // Peru
    "51",
    0,
    null,
    "0"
  ],
  [
    "ph",
    // Philippines
    "63",
    0,
    null,
    "0"
  ],
  [
    "pl",
    // Poland
    "48"
  ],
  [
    "pt",
    // Portugal
    "351"
  ],
  [
    "pr",
    // Puerto Rico
    "1",
    3,
    ["787", "939"],
    "1"
  ],
  [
    "qa",
    // Qatar
    "974"
  ],
  [
    "re",
    // Réunion
    "262",
    0,
    null,
    "0"
  ],
  [
    "ro",
    // Romania
    "40",
    0,
    null,
    "0"
  ],
  [
    "ru",
    // Russia
    "7",
    0,
    ["33"],
    // (shared with KZ)
    "8"
  ],
  [
    "rw",
    // Rwanda
    "250",
    0,
    null,
    "0"
  ],
  [
    "ws",
    // Samoa
    "685"
  ],
  [
    "sm",
    // San Marino
    "378"
  ],
  [
    "st",
    // São Tomé & Príncipe
    "239"
  ],
  [
    "sa",
    // Saudi Arabia
    "966",
    0,
    null,
    "0"
  ],
  [
    "sn",
    // Senegal
    "221"
  ],
  [
    "rs",
    // Serbia
    "381",
    0,
    null,
    "0"
  ],
  [
    "sc",
    // Seychelles
    "248"
  ],
  [
    "sl",
    // Sierra Leone
    "232",
    0,
    null,
    "0"
  ],
  [
    "sg",
    // Singapore
    "65"
  ],
  [
    "sx",
    // Sint Maarten
    "1",
    21,
    ["721"],
    "1"
  ],
  [
    "sk",
    // Slovakia
    "421",
    0,
    null,
    "0"
  ],
  [
    "si",
    // Slovenia
    "386",
    0,
    null,
    "0"
  ],
  [
    "sb",
    // Solomon Islands
    "677"
  ],
  [
    "so",
    // Somalia
    "252",
    0,
    null,
    "0"
  ],
  [
    "za",
    // South Africa
    "27",
    0,
    null,
    "0"
  ],
  [
    "kr",
    // South Korea
    "82",
    0,
    null,
    "0"
  ],
  [
    "ss",
    // South Sudan
    "211",
    0,
    null,
    "0"
  ],
  [
    "es",
    // Spain
    "34"
  ],
  [
    "lk",
    // Sri Lanka
    "94",
    0,
    null,
    "0"
  ],
  [
    "bl",
    // St. Barthélemy
    "590",
    1,
    null,
    "0"
  ],
  [
    "sh",
    // St. Helena
    "290"
  ],
  [
    "kn",
    // St. Kitts & Nevis
    "1",
    18,
    ["869"],
    "1"
  ],
  [
    "lc",
    // St. Lucia
    "1",
    19,
    ["758"],
    "1"
  ],
  [
    "mf",
    // St. Martin
    "590",
    2,
    null,
    "0"
  ],
  [
    "pm",
    // St. Pierre & Miquelon
    "508",
    0,
    null,
    "0"
  ],
  [
    "vc",
    // St. Vincent & Grenadines
    "1",
    20,
    ["784"],
    "1"
  ],
  [
    "sd",
    // Sudan
    "249",
    0,
    null,
    "0"
  ],
  [
    "sr",
    // Suriname
    "597"
  ],
  [
    "sj",
    // Svalbard & Jan Mayen
    "47",
    1,
    ["4", "79", "9"]
    // (4 and 9 are mobile ranges shared with NO)
  ],
  [
    "se",
    // Sweden
    "46",
    0,
    null,
    "0"
  ],
  [
    "ch",
    // Switzerland
    "41",
    0,
    null,
    "0"
  ],
  [
    "sy",
    // Syria
    "963",
    0,
    null,
    "0"
  ],
  [
    "tw",
    // Taiwan
    "886",
    0,
    null,
    "0"
  ],
  [
    "tj",
    // Tajikistan
    "992"
  ],
  [
    "tz",
    // Tanzania
    "255",
    0,
    null,
    "0"
  ],
  [
    "th",
    // Thailand
    "66",
    0,
    null,
    "0"
  ],
  [
    "tl",
    // Timor-Leste
    "670"
  ],
  [
    "tg",
    // Togo
    "228"
  ],
  [
    "tk",
    // Tokelau
    "690"
  ],
  [
    "to",
    // Tonga
    "676"
  ],
  [
    "tt",
    // Trinidad & Tobago
    "1",
    22,
    ["868"],
    "1"
  ],
  [
    "tn",
    // Tunisia
    "216"
  ],
  [
    "tr",
    // Turkey
    "90",
    0,
    null,
    "0"
  ],
  [
    "tm",
    // Turkmenistan
    "993",
    0,
    null,
    "8"
  ],
  [
    "tc",
    // Turks & Caicos Islands
    "1",
    23,
    ["649"],
    "1"
  ],
  [
    "tv",
    // Tuvalu
    "688"
  ],
  [
    "vi",
    // U.S. Virgin Islands
    "1",
    24,
    ["340"],
    "1"
  ],
  [
    "ug",
    // Uganda
    "256",
    0,
    null,
    "0"
  ],
  [
    "ua",
    // Ukraine
    "380",
    0,
    null,
    "0"
  ],
  [
    "ae",
    // United Arab Emirates
    "971",
    0,
    null,
    "0"
  ],
  [
    "gb",
    // United Kingdom
    "44",
    0,
    null,
    "0"
  ],
  [
    "us",
    // United States
    "1",
    0,
    null,
    "1"
  ],
  [
    "uy",
    // Uruguay
    "598",
    0,
    null,
    "0"
  ],
  [
    "uz",
    // Uzbekistan
    "998"
  ],
  [
    "vu",
    // Vanuatu
    "678"
  ],
  [
    "va",
    // Vatican City
    "39",
    1,
    ["06698", "3"]
    // (3 is a mobile range shared with IT)
  ],
  [
    "ve",
    // Venezuela
    "58",
    0,
    null,
    "0"
  ],
  [
    "vn",
    // Vietnam
    "84",
    0,
    null,
    "0"
  ],
  [
    "wf",
    // Wallis & Futuna
    "681"
  ],
  [
    "eh",
    // Western Sahara
    "212",
    1,
    ["5288", "5289", "6", "7"],
    // (6 and 7 are mobile ranges shared with MA)
    "0"
  ],
  [
    "ye",
    // Yemen
    "967",
    0,
    null,
    "0"
  ],
  [
    "zm",
    // Zambia
    "260",
    0,
    null,
    "0"
  ],
  [
    "zw",
    // Zimbabwe
    "263",
    0,
    null,
    "0"
  ]
], ht = [];
for (const e of bs)
  ht.push({
    name: "",
    // populated in the plugin
    iso2: e[0],
    dialCode: e[1],
    priority: e[2] || 0,
    areaCodes: e[3] || null,
    nodeById: {},
    // populated by the plugin
    nationalPrefix: e[4] || null,
    normalisedName: "",
    // populated in the plugin
    initials: "",
    // populated in the plugin
    dialCodePlus: ""
    // populated in the plugin
  });
const ws = {
  ad: "Andorra",
  ae: "United Arab Emirates",
  af: "Afghanistan",
  ag: "Antigua & Barbuda",
  ai: "Anguilla",
  al: "Albania",
  am: "Armenia",
  ao: "Angola",
  ar: "Argentina",
  as: "American Samoa",
  at: "Austria",
  au: "Australia",
  aw: "Aruba",
  ax: "Åland Islands",
  az: "Azerbaijan",
  ba: "Bosnia & Herzegovina",
  bb: "Barbados",
  bd: "Bangladesh",
  be: "Belgium",
  bf: "Burkina Faso",
  bg: "Bulgaria",
  bh: "Bahrain",
  bi: "Burundi",
  bj: "Benin",
  bl: "St. Barthélemy",
  bm: "Bermuda",
  bn: "Brunei",
  bo: "Bolivia",
  bq: "Caribbean Netherlands",
  br: "Brazil",
  bs: "Bahamas",
  bt: "Bhutan",
  bw: "Botswana",
  by: "Belarus",
  bz: "Belize",
  ca: "Canada",
  cc: "Cocos (Keeling) Islands",
  cd: "Congo - Kinshasa",
  cf: "Central African Republic",
  cg: "Congo - Brazzaville",
  ch: "Switzerland",
  ci: "Côte d’Ivoire",
  ck: "Cook Islands",
  cl: "Chile",
  cm: "Cameroon",
  cn: "China",
  co: "Colombia",
  cr: "Costa Rica",
  cu: "Cuba",
  cv: "Cape Verde",
  cw: "Curaçao",
  cx: "Christmas Island",
  cy: "Cyprus",
  cz: "Czechia",
  de: "Germany",
  dj: "Djibouti",
  dk: "Denmark",
  dm: "Dominica",
  do: "Dominican Republic",
  dz: "Algeria",
  ec: "Ecuador",
  ee: "Estonia",
  eg: "Egypt",
  eh: "Western Sahara",
  er: "Eritrea",
  es: "Spain",
  et: "Ethiopia",
  fi: "Finland",
  fj: "Fiji",
  fk: "Falkland Islands",
  fm: "Micronesia",
  fo: "Faroe Islands",
  fr: "France",
  ga: "Gabon",
  gb: "United Kingdom",
  gd: "Grenada",
  ge: "Georgia",
  gf: "French Guiana",
  gg: "Guernsey",
  gh: "Ghana",
  gi: "Gibraltar",
  gl: "Greenland",
  gm: "Gambia",
  gn: "Guinea",
  gp: "Guadeloupe",
  gq: "Equatorial Guinea",
  gr: "Greece",
  gt: "Guatemala",
  gu: "Guam",
  gw: "Guinea-Bissau",
  gy: "Guyana",
  hk: "Hong Kong SAR China",
  hn: "Honduras",
  hr: "Croatia",
  ht: "Haiti",
  hu: "Hungary",
  id: "Indonesia",
  ie: "Ireland",
  il: "Israel",
  im: "Isle of Man",
  in: "India",
  io: "British Indian Ocean Territory",
  iq: "Iraq",
  ir: "Iran",
  is: "Iceland",
  it: "Italy",
  je: "Jersey",
  jm: "Jamaica",
  jo: "Jordan",
  jp: "Japan",
  ke: "Kenya",
  kg: "Kyrgyzstan",
  kh: "Cambodia",
  ki: "Kiribati",
  km: "Comoros",
  kn: "St. Kitts & Nevis",
  kp: "North Korea",
  kr: "South Korea",
  kw: "Kuwait",
  ky: "Cayman Islands",
  kz: "Kazakhstan",
  la: "Laos",
  lb: "Lebanon",
  lc: "St. Lucia",
  li: "Liechtenstein",
  lk: "Sri Lanka",
  lr: "Liberia",
  ls: "Lesotho",
  lt: "Lithuania",
  lu: "Luxembourg",
  lv: "Latvia",
  ly: "Libya",
  ma: "Morocco",
  mc: "Monaco",
  md: "Moldova",
  me: "Montenegro",
  mf: "St. Martin",
  mg: "Madagascar",
  mh: "Marshall Islands",
  mk: "North Macedonia",
  ml: "Mali",
  mm: "Myanmar (Burma)",
  mn: "Mongolia",
  mo: "Macao SAR China",
  mp: "Northern Mariana Islands",
  mq: "Martinique",
  mr: "Mauritania",
  ms: "Montserrat",
  mt: "Malta",
  mu: "Mauritius",
  mv: "Maldives",
  mw: "Malawi",
  mx: "Mexico",
  my: "Malaysia",
  mz: "Mozambique",
  na: "Namibia",
  nc: "New Caledonia",
  ne: "Niger",
  nf: "Norfolk Island",
  ng: "Nigeria",
  ni: "Nicaragua",
  nl: "Netherlands",
  no: "Norway",
  np: "Nepal",
  nr: "Nauru",
  nu: "Niue",
  nz: "New Zealand",
  om: "Oman",
  pa: "Panama",
  pe: "Peru",
  pf: "French Polynesia",
  pg: "Papua New Guinea",
  ph: "Philippines",
  pk: "Pakistan",
  pl: "Poland",
  pm: "St. Pierre & Miquelon",
  pr: "Puerto Rico",
  ps: "Palestinian Territories",
  pt: "Portugal",
  pw: "Palau",
  py: "Paraguay",
  qa: "Qatar",
  re: "Réunion",
  ro: "Romania",
  rs: "Serbia",
  ru: "Russia",
  rw: "Rwanda",
  sa: "Saudi Arabia",
  sb: "Solomon Islands",
  sc: "Seychelles",
  sd: "Sudan",
  se: "Sweden",
  sg: "Singapore",
  sh: "St. Helena",
  si: "Slovenia",
  sj: "Svalbard & Jan Mayen",
  sk: "Slovakia",
  sl: "Sierra Leone",
  sm: "San Marino",
  sn: "Senegal",
  so: "Somalia",
  sr: "Suriname",
  ss: "South Sudan",
  st: "São Tomé & Príncipe",
  sv: "El Salvador",
  sx: "Sint Maarten",
  sy: "Syria",
  sz: "Eswatini",
  tc: "Turks & Caicos Islands",
  td: "Chad",
  tg: "Togo",
  th: "Thailand",
  tj: "Tajikistan",
  tk: "Tokelau",
  tl: "Timor-Leste",
  tm: "Turkmenistan",
  tn: "Tunisia",
  to: "Tonga",
  tr: "Turkey",
  tt: "Trinidad & Tobago",
  tv: "Tuvalu",
  tw: "Taiwan",
  tz: "Tanzania",
  ua: "Ukraine",
  ug: "Uganda",
  us: "United States",
  uy: "Uruguay",
  uz: "Uzbekistan",
  va: "Vatican City",
  vc: "St. Vincent & Grenadines",
  ve: "Venezuela",
  vg: "British Virgin Islands",
  vi: "U.S. Virgin Islands",
  vn: "Vietnam",
  vu: "Vanuatu",
  wf: "Wallis & Futuna",
  ws: "Samoa",
  ye: "Yemen",
  yt: "Mayotte",
  za: "South Africa",
  zm: "Zambia",
  zw: "Zimbabwe"
}, Is = {
  selectedCountryAriaLabel: "Change country, selected ${countryName} (${dialCode})",
  noCountrySelected: "Select country",
  countryListAriaLabel: "List of countries",
  searchPlaceholder: "Search",
  clearSearchAriaLabel: "Clear search",
  zeroSearchResults: "No results found",
  oneSearchResult: "1 result found",
  multipleSearchResults: "${count} results found",
  // additional countries (not supported by country-list library)
  ac: "Ascension Island",
  xk: "Kosovo"
}, Pi = { ...ws, ...Is }, Wt = {
  OPEN_COUNTRY_DROPDOWN: "open:countrydropdown",
  CLOSE_COUNTRY_DROPDOWN: "close:countrydropdown",
  COUNTRY_CHANGE: "countrychange",
  INPUT: "input"
  // used for synthetic input trigger
}, E = {
  HIDE: "iti__hide",
  V_HIDE: "iti__v-hide",
  ARROW_UP: "iti__arrow--up",
  GLOBE: "iti__globe",
  FLAG: "iti__flag",
  COUNTRY_ITEM: "iti__country",
  HIGHLIGHT: "iti__highlight"
}, U = {
  ARROW_UP: "ArrowUp",
  ARROW_DOWN: "ArrowDown",
  SPACE: " ",
  ENTER: "Enter",
  ESC: "Escape",
  TAB: "Tab"
}, Ge = {
  PASTE: "insertFromPaste",
  DELETE_FWD: "deleteContentForward"
}, lt = {
  ALPHA_UNICODE: new RegExp("\\p{L}", "u"),
  // any kind of letter from any language
  NON_PLUS_NUMERIC: /[^+0-9]/,
  // chars that are NOT + or digit
  NON_PLUS_NUMERIC_GLOBAL: /[^+0-9]/g,
  // chars that are NOT + or digit (global)
  HIDDEN_SEARCH_CHAR: /^[a-zA-ZÀ-ÿа-яА-Я ]$/
  // single acceptable hidden-search char
}, Es = {
  HIDDEN_SEARCH_RESET_MS: 1e3
}, ze = {
  UNKNOWN_NUMBER_TYPE: -99,
  UNKNOWN_VALIDATION_ERROR: -99
}, re = {
  SANE_SELECTED_WITH_DIAL_WIDTH: 78,
  // px width fallback when separateDialCode enabled
  SANE_SELECTED_NO_DIAL_WIDTH: 42,
  // px width fallback when no separate dial code
  INPUT_PADDING_EXTRA_LEFT: 6
  // px gap between selected country container and input text
}, Oi = {
  NANP: "1"
  // North American Numbering Plan
}, oe = {
  DIAL_CODE: "44",
  // +44 United Kingdom
  MOBILE_PREFIX: "7",
  // UK mobile numbers start with 7 after national trunk (0) or core section
  MOBILE_CORE_LENGTH: 10
  // core number length (excluding dial code / national prefix) for mobiles
}, Ns = {
  ISO2: "us"
}, ye = {
  AGGRESSIVE: "aggressive",
  POLITE: "polite"
}, le = {
  AUTO: "auto"
}, Ke = {
  COUNTRY_CODE: "countryCode",
  DIAL_CODE: "dialCode"
}, A = {
  EXPANDED: "aria-expanded",
  LABEL: "aria-label",
  SELECTED: "aria-selected",
  ACTIVE_DESCENDANT: "aria-activedescendant",
  HASPOPUP: "aria-haspopup",
  CONTROLS: "aria-controls",
  HIDDEN: "aria-hidden",
  AUTOCOMPLETE: "aria-autocomplete",
  MODAL: "aria-modal"
}, ae = (e) => typeof window < "u" && typeof window.matchMedia == "function" && window.matchMedia(e).matches, Ds = () => {
  if (typeof navigator < "u" && typeof window < "u") {
    const e = /Android.+Mobile|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    ), t = ae("(max-width: 500px)"), i = ae("(max-height: 600px)"), n = ae("(pointer: coarse)");
    return e || t || n && i;
  }
  return !1;
}, Ri = {
  // Allow alphanumeric "phonewords" (e.g. +1 800 FLOWERS) as valid numbers
  allowPhonewords: !1,
  //* Whether or not to allow the dropdown.
  allowDropdown: !0,
  //* Add a placeholder in the input with an example number for the selected country.
  autoPlaceholder: ye.POLITE,
  //* Modify the parentClass.
  containerClass: "",
  //* The order of the countries in the dropdown. Defaults to alphabetical.
  countryOrder: null,
  //* Add a country search input at the top of the dropdown.
  countrySearch: !0,
  //* Modify the auto placeholder.
  customPlaceholder: null,
  //* Append menu to specified element.
  dropdownContainer: null,
  //* Don't display these countries.
  excludeCountries: [],
  //* Fix the dropdown width to the input width (rather than being as wide as the longest country name).
  fixDropdownWidth: !0,
  //* Format the number as the user types
  formatAsYouType: !0,
  //* Format the input value during initialisation and on setNumber.
  formatOnDisplay: !0,
  //* geoIp lookup function.
  geoIpLookup: null,
  //* Inject a hidden input with the name returned from this function, and on submit, populate it with the result of getNumber.
  hiddenInput: null,
  //* Internationalise the plugin text e.g. search input placeholder, country names.
  i18n: {},
  //* Initial country.
  initialCountry: "",
  //* A function to load the utils script.
  loadUtils: null,
  //* National vs international formatting for numbers e.g. placeholders and displaying existing numbers.
  nationalMode: !0,
  //* Display only these countries.
  onlyCountries: [],
  //* Number type to use for placeholders.
  placeholderNumberType: "MOBILE",
  //* Show flags - for both the selected country, and in the country dropdown
  showFlags: !0,
  //* Display the international dial code next to the selected flag.
  separateDialCode: !1,
  //* Only allow certain chars e.g. a plus followed by numeric digits, and cap at max valid length.
  strictMode: !1,
  //* Use full screen popup instead of dropdown for country list.
  useFullscreenPopup: Ds(),
  //* The number type to enforce during validation.
  validationNumberTypes: ["MOBILE"]
}, As = (e, t) => {
  e.useFullscreenPopup && (e.fixDropdownWidth = !1), e.onlyCountries.length === 1 && (e.initialCountry = e.onlyCountries[0]), e.separateDialCode && (e.nationalMode = !1), e.allowDropdown && !e.showFlags && !e.separateDialCode && (e.nationalMode = !1), e.useFullscreenPopup && !e.dropdownContainer && (e.dropdownContainer = document.body), e.i18n = { ...t, ...e.i18n };
}, Gt = (e) => e.replace(/\D/g, ""), ki = (e = "") => e.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(), Ls = (e, t) => {
  const i = ki(t), n = [], s = [], r = [], o = [], l = [], u = [];
  for (const c of e)
    c.iso2 === i ? n.push(c) : c.normalisedName.startsWith(i) ? s.push(c) : c.normalisedName.includes(i) ? r.push(c) : i === c.dialCode || i === c.dialCodePlus ? o.push(c) : c.dialCodePlus.includes(i) ? l.push(c) : c.initials.includes(i) && u.push(c);
  const a = (c, d) => c.priority - d.priority;
  return [
    ...n.sort(a),
    ...s.sort(a),
    ...r.sort(a),
    ...o.sort(a),
    ...l.sort(a),
    ...u.sort(a)
  ];
}, Ss = (e, t) => {
  const i = t.toLowerCase();
  for (const n of e)
    if (n.name.toLowerCase().startsWith(i))
      return n;
  return null;
}, ue = (e) => Object.keys(e).filter((t) => !!e[t]).join(" "), S = (e, t, i) => {
  const n = document.createElement(e);
  return t && Object.entries(t).forEach(
    ([s, r]) => n.setAttribute(s, r)
  ), i && i.appendChild(n), n;
}, Ts = () => `
  <svg class="iti__search-icon-svg" width="14" height="14" viewBox="0 0 24 24" focusable="false" ${A.HIDDEN}="true">
    <circle cx="11" cy="11" r="7" />
    <line x1="21" y1="21" x2="16.65" y2="16.65" />
  </svg>`, Ps = (e) => {
  const t = `iti-${e}-clear-mask`;
  return `
    <svg class="iti__search-clear-svg" width="12" height="12" viewBox="0 0 16 16" ${A.HIDDEN}="true" focusable="false">
      <mask id="${t}" maskUnits="userSpaceOnUse">
        <rect width="16" height="16" fill="white" />
        <path d="M5.2 5.2 L10.8 10.8 M10.8 5.2 L5.2 10.8" stroke="black" stroke-linecap="round" class="iti__search-clear-x" />
      </mask>
      <circle cx="8" cy="8" r="8" class="iti__search-clear-bg" mask="url(#${t})" />
    </svg>`;
};
class Os {
  constructor(t, i, n) {
    this.highlightedItem = null, this.selectedItem = null, t.dataset.intlTelInputId = n.toString(), this.telInput = t, this.options = i, this.id = n, this.hadInitialPlaceholder = !!t.getAttribute("placeholder"), this.isRTL = !!this.telInput.closest("[dir=rtl]"), this.options.separateDialCode && (this.originalPaddingLeft = this.telInput.style.paddingLeft);
  }
  //* Generate all of the markup for the plugin: the selected country overlay, and the dropdown.
  generateMarkup(t) {
    this.countries = t, this._prepareTelInput();
    const i = this._createWrapperAndInsert();
    this._maybeBuildCountryContainer(i), i.appendChild(this.telInput), this._maybeUpdateInputPaddingAndReveal(), this._maybeBuildHiddenInputs(i);
  }
  _prepareTelInput() {
    var t;
    this.telInput.classList.add("iti__tel-input"), !this.telInput.hasAttribute("autocomplete") && !((t = this.telInput.form) != null && t.hasAttribute("autocomplete")) && this.telInput.setAttribute("autocomplete", "off");
  }
  _createWrapperAndInsert() {
    const { allowDropdown: t, showFlags: i, containerClass: n, useFullscreenPopup: s } = this.options, r = ue({
      iti: !0,
      "iti--allow-dropdown": t,
      "iti--show-flags": i,
      "iti--inline-dropdown": !s,
      [n]: !!n
    }), o = S("div", { class: r });
    return this.isRTL && o.setAttribute("dir", "ltr"), this.telInput.before(o), o;
  }
  _maybeBuildCountryContainer(t) {
    const { allowDropdown: i, separateDialCode: n, showFlags: s } = this.options;
    if (i || s || n) {
      this.countryContainer = S(
        "div",
        // visibly hidden until we measure it's width to set the input padding correctly
        { class: `iti__country-container ${E.V_HIDE}` },
        t
      ), i ? (this.selectedCountry = S(
        "button",
        {
          type: "button",
          class: "iti__selected-country",
          [A.EXPANDED]: "false",
          [A.LABEL]: this.options.i18n.noCountrySelected,
          [A.HASPOPUP]: "dialog",
          [A.CONTROLS]: `iti-${this.id}__dropdown-content`
        },
        this.countryContainer
      ), this.telInput.disabled && this.selectedCountry.setAttribute("disabled", "true")) : this.selectedCountry = S(
        "div",
        { class: "iti__selected-country" },
        this.countryContainer
      );
      const r = S(
        "div",
        { class: "iti__selected-country-primary" },
        this.selectedCountry
      );
      this.selectedCountryInner = S(
        "div",
        { class: E.FLAG },
        r
      ), i && (this.dropdownArrow = S(
        "div",
        { class: "iti__arrow", [A.HIDDEN]: "true" },
        r
      )), n && (this.selectedDialCode = S(
        "div",
        { class: "iti__selected-dial-code" },
        this.selectedCountry
      )), i && this._buildDropdownContent();
    }
  }
  _buildDropdownContent() {
    const {
      fixDropdownWidth: t,
      useFullscreenPopup: i,
      countrySearch: n,
      i18n: s,
      dropdownContainer: r,
      containerClass: o
    } = this.options, l = t ? "" : "iti--flexible-dropdown-width";
    if (this.dropdownContent = S("div", {
      id: `iti-${this.id}__dropdown-content`,
      class: `iti__dropdown-content ${E.HIDE} ${l}`,
      role: "dialog",
      [A.MODAL]: "true"
    }), this.isRTL && this.dropdownContent.setAttribute("dir", "rtl"), n && this._buildSearchUI(), this.countryList = S(
      "ul",
      {
        class: "iti__country-list",
        id: `iti-${this.id}__country-listbox`,
        role: "listbox",
        [A.LABEL]: s.countryListAriaLabel
      },
      this.dropdownContent
    ), this._appendListItems(), n && this.updateSearchResultsA11yText(), r) {
      const u = ue({
        iti: !0,
        "iti--container": !0,
        "iti--fullscreen-popup": i,
        "iti--inline-dropdown": !i,
        [o]: !!o
      });
      this.dropdown = S("div", { class: u }), this.dropdown.appendChild(this.dropdownContent);
    } else
      this.countryContainer.appendChild(this.dropdownContent);
  }
  _buildSearchUI() {
    const { i18n: t } = this.options, i = S(
      "div",
      { class: "iti__search-input-wrapper" },
      this.dropdownContent
    );
    this.searchIcon = S(
      "span",
      {
        class: "iti__search-icon",
        [A.HIDDEN]: "true"
      },
      i
    ), this.searchIcon.innerHTML = Ts(), this.searchInput = S(
      "input",
      {
        id: `iti-${this.id}__search-input`,
        // Chrome says inputs need either a name or an id
        type: "search",
        class: "iti__search-input",
        placeholder: t.searchPlaceholder,
        // role=combobox + aria-autocomplete=list + aria-activedescendant allows maintaining focus on the search input while allowing users to navigate search results with up/down keyboard keys
        role: "combobox",
        [A.EXPANDED]: "true",
        [A.LABEL]: t.searchPlaceholder,
        [A.CONTROLS]: `iti-${this.id}__country-listbox`,
        [A.AUTOCOMPLETE]: "list",
        autocomplete: "off"
      },
      i
    ), this.searchClearButton = S(
      "button",
      {
        type: "button",
        class: `iti__search-clear ${E.HIDE}`,
        [A.LABEL]: t.clearSearchAriaLabel,
        tabindex: "-1"
      },
      i
    ), this.searchClearButton.innerHTML = Ps(this.id), this.searchResultsA11yText = S(
      "span",
      { class: "iti__a11y-text" },
      this.dropdownContent
    ), this.searchNoResults = S(
      "div",
      {
        class: `iti__no-results ${E.HIDE}`,
        [A.HIDDEN]: "true"
        // all a11y messaging happens in this.searchResultsA11yText
      },
      this.dropdownContent
    ), this.searchNoResults.textContent = t.zeroSearchResults;
  }
  _maybeUpdateInputPaddingAndReveal() {
    this.countryContainer && (this.updateInputPadding(), this.countryContainer.classList.remove(E.V_HIDE));
  }
  _maybeBuildHiddenInputs(t) {
    var n, s;
    const { hiddenInput: i } = this.options;
    if (i) {
      const r = this.telInput.getAttribute("name") || "", o = i(r);
      if (o.phone) {
        const l = (n = this.telInput.form) == null ? void 0 : n.querySelector(
          `input[name="${o.phone}"]`
        );
        l ? this.hiddenInput = l : (this.hiddenInput = S("input", {
          type: "hidden",
          name: o.phone
        }), t.appendChild(this.hiddenInput));
      }
      if (o.country) {
        const l = (s = this.telInput.form) == null ? void 0 : s.querySelector(
          `input[name="${o.country}"]`
        );
        l ? this.hiddenInputCountry = l : (this.hiddenInputCountry = S("input", {
          type: "hidden",
          name: o.country
        }), t.appendChild(this.hiddenInputCountry));
      }
    }
  }
  //* For each country: add a country list item <li> to the countryList <ul> container.
  _appendListItems() {
    const t = document.createDocumentFragment();
    for (let i = 0; i < this.countries.length; i++) {
      const n = this.countries[i], s = ue({
        [E.COUNTRY_ITEM]: !0
      }), r = S("li", {
        id: `iti-${this.id}__item-${n.iso2}`,
        class: s,
        tabindex: "-1",
        role: "option",
        [A.SELECTED]: "false"
      });
      r.dataset.dialCode = n.dialCode, r.dataset.countryCode = n.iso2, n.nodeById[this.id] = r, this.options.showFlags && S("div", { class: `${E.FLAG} iti__${n.iso2}` }, r);
      const o = S("span", { class: "iti__country-name" }, r);
      o.textContent = n.name;
      const l = S("span", { class: "iti__dial-code" }, r);
      this.isRTL && l.setAttribute("dir", "ltr"), l.textContent = `+${n.dialCode}`, t.appendChild(r);
    }
    this.countryList.appendChild(t);
  }
  //* Update the input padding to make space for the selected country/dial code.
  updateInputPadding() {
    if (this.selectedCountry) {
      const t = this.options.separateDialCode ? re.SANE_SELECTED_WITH_DIAL_WIDTH : re.SANE_SELECTED_NO_DIAL_WIDTH, n = (this.selectedCountry.offsetWidth || this._getHiddenSelectedCountryWidth() || t) + re.INPUT_PADDING_EXTRA_LEFT;
      this.telInput.style.paddingLeft = `${n}px`;
    }
  }
  //* When input is in a hidden container during init, we cannot calculate the selected country width.
  //* Fix: clone the markup, make it invisible, add it to the end of the DOM, and then measure it's width.
  //* To get the right styling to apply, all we need is a shallow clone of the container,
  //* and then to inject a deep clone of the selectedCountry element.
  _getHiddenSelectedCountryWidth() {
    if (this.telInput.parentNode) {
      let t;
      try {
        t = window.top.document.body;
      } catch {
        t = document.body;
      }
      const i = this.telInput.parentNode.cloneNode(
        !1
      );
      i.style.visibility = "hidden", t.appendChild(i);
      const n = this.countryContainer.cloneNode();
      i.appendChild(n);
      const s = this.selectedCountry.cloneNode(
        !0
      );
      n.appendChild(s);
      const r = s.offsetWidth;
      return t.removeChild(i), r;
    }
    return 0;
  }
  //* Update search results text (for a11y).
  updateSearchResultsA11yText() {
    const { i18n: t } = this.options, i = this.countryList.childElementCount;
    let n;
    i === 0 ? n = t.zeroSearchResults : t.searchResultsText ? n = t.searchResultsText(i) : i === 1 ? n = t.oneSearchResult : n = t.multipleSearchResults.replace(
      "${count}",
      i.toString()
    ), this.searchResultsA11yText.textContent = n;
  }
  //* Check if an element is visible within it's container, else scroll until it is.
  scrollTo(t) {
    const i = this.countryList, n = document.documentElement.scrollTop, s = i.offsetHeight, r = i.getBoundingClientRect().top + n, o = r + s, l = t.offsetHeight, u = t.getBoundingClientRect().top + n, a = u + l, c = u - r + i.scrollTop;
    if (u < r)
      i.scrollTop = c;
    else if (a > o) {
      const d = s - l;
      i.scrollTop = c - d;
    }
  }
  //* Remove highlighting from the previous list item and highlight the new one.
  highlightListItem(t, i) {
    const n = this.highlightedItem;
    if (n && n.classList.remove(E.HIGHLIGHT), this.highlightedItem = t, this.highlightedItem && (this.highlightedItem.classList.add(E.HIGHLIGHT), this.options.countrySearch)) {
      const s = this.highlightedItem.getAttribute("id") || "";
      this.searchInput.setAttribute(A.ACTIVE_DESCENDANT, s);
    }
    i && this.highlightedItem.focus();
  }
  updateSelectedItem(t) {
    if (this.selectedItem && this.selectedItem.dataset.countryCode !== t && (this.selectedItem.setAttribute(A.SELECTED, "false"), this.selectedItem = null), t && !this.selectedItem) {
      const i = this.countryList.querySelector(
        `[data-country-code="${t}"]`
      );
      i && (i.setAttribute(A.SELECTED, "true"), this.selectedItem = i);
    }
  }
  //* Country search: Filter the country list to the given array of countries.
  filterCountries(t) {
    this.countryList.innerHTML = "";
    let i = !0;
    for (const n of t) {
      const s = n.nodeById[this.id];
      s && (this.countryList.appendChild(s), i && (this.highlightListItem(s, !1), i = !1));
    }
    i ? (this.highlightListItem(null, !1), this.searchNoResults && this.searchNoResults.classList.remove(E.HIDE)) : this.searchNoResults && this.searchNoResults.classList.add(E.HIDE), this.countryList.scrollTop = 0, this.updateSearchResultsA11yText();
  }
  destroy() {
    this.telInput.iti = void 0, delete this.telInput.dataset.intlTelInputId, this.options.separateDialCode && (this.telInput.style.paddingLeft = this.originalPaddingLeft);
    const t = this.telInput.parentNode;
    t.before(this.telInput), t.remove(), this.telInput = null, this.countryContainer = null, this.selectedCountry = null, this.selectedCountryInner = null, this.selectedDialCode = null, this.dropdownArrow = null, this.dropdownContent = null, this.searchInput = null, this.searchIcon = null, this.searchClearButton = null, this.searchNoResults = null, this.searchResultsA11yText = null, this.countryList = null, this.dropdown = null, this.hiddenInput = null, this.hiddenInputCountry = null, this.highlightedItem = null, this.selectedItem = null;
    for (const i of this.countries)
      delete i.nodeById[this.id];
    this.countries = null;
  }
}
const Rs = (e) => {
  const { onlyCountries: t, excludeCountries: i } = e;
  if (t.length) {
    const n = t.map(
      (s) => s.toLowerCase()
    );
    return ht.filter(
      (s) => n.includes(s.iso2)
    );
  } else if (i.length) {
    const n = i.map(
      (s) => s.toLowerCase()
    );
    return ht.filter(
      (s) => !n.includes(s.iso2)
    );
  }
  return ht;
}, ks = (e, t) => {
  for (const i of e) {
    const n = i.iso2.toLowerCase();
    t.i18n[n] && (i.name = t.i18n[n]);
  }
}, Ms = (e) => {
  const t = /* @__PURE__ */ new Set();
  let i = 0;
  const n = {}, s = (o, l) => {
    if (!o || !l)
      return;
    l.length > i && (i = l.length), n.hasOwnProperty(l) || (n[l] = []);
    const u = n[l];
    u.includes(o) || u.push(o);
  }, r = [...e].sort((o, l) => o.priority - l.priority);
  for (const o of r) {
    t.has(o.dialCode) || t.add(o.dialCode);
    for (let l = 1; l < o.dialCode.length; l++) {
      const u = o.dialCode.substring(0, l);
      s(o.iso2, u);
    }
    if (s(o.iso2, o.dialCode), o.areaCodes) {
      const l = n[o.dialCode][0];
      for (const u of o.areaCodes) {
        for (let a = 1; a < u.length; a++) {
          const c = u.substring(0, a), d = o.dialCode + c;
          s(l, d), s(o.iso2, d);
        }
        s(o.iso2, o.dialCode + u);
      }
    }
  }
  return { dialCodes: t, dialCodeMaxLen: i, dialCodeToIso2Map: n };
}, Us = (e, t) => {
  t.countryOrder && (t.countryOrder = t.countryOrder.map(
    (i) => i.toLowerCase()
  )), e.sort((i, n) => {
    const { countryOrder: s } = t;
    if (s) {
      const r = s.indexOf(i.iso2), o = s.indexOf(n.iso2), l = r > -1, u = o > -1;
      if (l || u)
        return l && u ? r - o : l ? -1 : 1;
    }
    return i.name.localeCompare(n.name);
  });
}, xs = (e) => {
  for (const t of e)
    t.normalisedName = ki(t.name), t.initials = t.normalisedName.split(/[^a-z]/).map((i) => i[0]).join(""), t.dialCodePlus = `+${t.dialCode}`;
}, Bs = (e, t, i, n) => {
  let s = e;
  if (i && t) {
    t = `+${n.dialCode}`;
    const r = s[t.length] === " " || s[t.length] === "-" ? t.length + 1 : t.length;
    s = s.substring(r);
  }
  return s;
}, Fs = (e, t, i, n, s) => {
  const r = i ? i.formatNumberAsYouType(e, n.iso2) : e, { dialCode: o } = n;
  return s && t.charAt(0) !== "+" && r.includes(`+${o}`) ? (r.split(`+${o}`)[1] || "").trim() : r;
}, Hs = (e, t, i, n) => {
  if (i === 0 && !n)
    return 0;
  let s = 0;
  for (let r = 0; r < t.length; r++) {
    if (/[+0-9]/.test(t[r]) && s++, s === e && !n)
      return r + 1;
    if (n && s === e + 1)
      return r;
  }
  return t.length;
}, Vs = [
  "800",
  "822",
  "833",
  "844",
  "855",
  "866",
  "877",
  "880",
  "881",
  "882",
  "883",
  "884",
  "885",
  "886",
  "887",
  "888",
  "889"
], Ye = (e) => {
  const t = Gt(e);
  if (t.startsWith(Oi.NANP) && t.length >= 4) {
    const i = t.substring(1, 4);
    return Vs.includes(i);
  }
  return !1;
};
for (const e of ht)
  e.name = Pi[e.iso2];
let Ws = 0;
const js = new Set(ht.map((e) => e.iso2)), ce = (e) => js.has(e);
class Se {
  constructor(t, i = {}) {
    this.id = Ws++, this.options = { ...Ri, ...i }, As(this.options, Pi), this.ui = new Os(t, this.options, this.id), this.isAndroid = Se._getIsAndroid(), this.promise = this._createInitPromises(), this.countries = Rs(this.options);
    const { dialCodes: n, dialCodeMaxLen: s, dialCodeToIso2Map: r } = Ms(
      this.countries
    );
    this.dialCodes = n, this.dialCodeMaxLen = s, this.dialCodeToIso2Map = r, this.countryByIso2 = new Map(this.countries.map((o) => [o.iso2, o])), this._init();
  }
  static _getIsAndroid() {
    return typeof navigator < "u" ? /Android/i.test(navigator.userAgent) : !1;
  }
  _updateNumeralSet(t) {
    /[\u0660-\u0669]/.test(t) ? this.userNumeralSet = "arabic-indic" : /[\u06F0-\u06F9]/.test(t) ? this.userNumeralSet = "persian" : this.userNumeralSet = "ascii";
  }
  _mapAsciiToUserNumerals(t) {
    if (this.userNumeralSet || this._updateNumeralSet(this.ui.telInput.value), this.userNumeralSet === "ascii")
      return t;
    const i = this.userNumeralSet === "arabic-indic" ? 1632 : 1776;
    return t.replace(/[0-9]/g, (n) => String.fromCharCode(i + Number(n)));
  }
  // Normalize Eastern Arabic (U+0660-0669) and Persian/Extended Arabic-Indic (U+06F0-06F9) numerals to ASCII 0-9
  _normaliseNumerals(t) {
    if (!t)
      return "";
    if (this._updateNumeralSet(t), this.userNumeralSet === "ascii")
      return t;
    const i = this.userNumeralSet === "arabic-indic" ? 1632 : 1776, n = this.userNumeralSet === "arabic-indic" ? /[\u0660-\u0669]/g : /[\u06F0-\u06F9]/g;
    return t.replace(n, (s) => String.fromCharCode(48 + (s.charCodeAt(0) - i)));
  }
  _getTelInputValue() {
    const t = this.ui.telInput.value.trim();
    return this._normaliseNumerals(t);
  }
  _setTelInputValue(t) {
    this.ui.telInput.value = this._mapAsciiToUserNumerals(t);
  }
  _createInitPromises() {
    const t = new Promise((n, s) => {
      this.resolveAutoCountryPromise = n, this.rejectAutoCountryPromise = s;
    }), i = new Promise((n, s) => {
      this.resolveUtilsScriptPromise = n, this.rejectUtilsScriptPromise = s;
    });
    return Promise.all([t, i]);
  }
  //* Can't be private as it's called from intlTelInput convenience wrapper.
  _init() {
    this.selectedCountryData = {}, this.abortController = new AbortController(), this._processCountryData(), this.ui.generateMarkup(this.countries), this._setInitialState(), this._initListeners(), this._initRequests();
  }
  //********************
  //*  PRIVATE METHODS
  //********************
  //* Prepare all of the country data, including onlyCountries, excludeCountries, countryOrder options.
  _processCountryData() {
    ks(this.countries, this.options), Us(this.countries, this.options), xs(this.countries);
  }
  //* Set the initial state of the input value and the selected country by:
  //* 1. Extracting a dial code from the given number
  //* 2. Using explicit initialCountry
  _setInitialState(t = !1) {
    const i = this.ui.telInput.getAttribute("value"), n = this._normaliseNumerals(i), s = this._getTelInputValue(), o = n && n.startsWith("+") && (!s || !s.startsWith("+")) ? n : s, l = this._getDialCode(o), u = Ye(o), { initialCountry: a, geoIpLookup: c } = this.options, d = a === le.AUTO && c;
    if (l && !u)
      this._updateCountryFromNumber(o);
    else if (!d || t) {
      const f = a ? a.toLowerCase() : "";
      ce(f) ? this._setCountry(f) : l && u ? this._setCountry(Ns.ISO2) : this._setCountry("");
    }
    o && this._updateValFromNumber(o);
  }
  //* Initialise the main event listeners: input keyup, and click selected country.
  _initListeners() {
    this._initTelInputListeners(), this.options.allowDropdown && this._initDropdownListeners(), (this.ui.hiddenInput || this.ui.hiddenInputCountry) && this.ui.telInput.form && this._initHiddenInputListener();
  }
  //* Update hidden input on form submit.
  _initHiddenInputListener() {
    var i;
    const t = () => {
      this.ui.hiddenInput && (this.ui.hiddenInput.value = this.getNumber()), this.ui.hiddenInputCountry && (this.ui.hiddenInputCountry.value = this.selectedCountryData.iso2 || "");
    };
    (i = this.ui.telInput.form) == null || i.addEventListener("submit", t, {
      signal: this.abortController.signal
    });
  }
  //* initialise the dropdown listeners.
  _initDropdownListeners() {
    const t = this.abortController.signal, i = (o) => {
      this.ui.dropdownContent.classList.contains(E.HIDE) ? this.ui.telInput.focus() : o.preventDefault();
    }, n = this.ui.telInput.closest("label");
    n && n.addEventListener("click", i, { signal: t });
    const s = () => {
      this.ui.dropdownContent.classList.contains(
        E.HIDE
      ) && !this.ui.telInput.disabled && !this.ui.telInput.readOnly && this._openDropdown();
    };
    this.ui.selectedCountry.addEventListener(
      "click",
      s,
      {
        signal: t
      }
    );
    const r = (o) => {
      this.ui.dropdownContent.classList.contains(
        E.HIDE
      ) && [U.ARROW_UP, U.ARROW_DOWN, U.SPACE, U.ENTER].includes(o.key) && (o.preventDefault(), o.stopPropagation(), this._openDropdown()), o.key === U.TAB && this._closeDropdown();
    };
    this.ui.countryContainer.addEventListener(
      "keydown",
      r,
      { signal: t }
    );
  }
  //* Init many requests: utils script / geo ip lookup.
  _initRequests() {
    const { loadUtils: t, initialCountry: i, geoIpLookup: n } = this.options;
    if (t && !_.utils) {
      const r = () => {
        var o;
        (o = _.attachUtils(t)) == null || o.catch(() => {
        });
      };
      if (_.documentReady())
        r();
      else {
        const o = () => {
          r();
        };
        window.addEventListener("load", o, {
          signal: this.abortController.signal
        });
      }
    } else
      this.resolveUtilsScriptPromise();
    i === le.AUTO && n && !this.selectedCountryData.iso2 ? this._loadAutoCountry() : this.resolveAutoCountryPromise();
  }
  //* Perform the geo ip lookup.
  _loadAutoCountry() {
    _.autoCountry ? this.handleAutoCountry() : _.startedLoadingAutoCountry || (_.startedLoadingAutoCountry = !0, typeof this.options.geoIpLookup == "function" && this.options.geoIpLookup(
      (t = "") => {
        const i = t.toLowerCase();
        ce(i) ? (_.autoCountry = i, setTimeout(() => kt("handleAutoCountry"))) : (this._setInitialState(!0), kt("rejectAutoCountryPromise"));
      },
      () => {
        this._setInitialState(!0), kt("rejectAutoCountryPromise");
      }
    ));
  }
  _openDropdownWithPlus() {
    this._openDropdown(), this.ui.searchInput.value = "+", this._filterCountriesByQuery("");
  }
  //* Initialize the tel input listeners.
  _initTelInputListeners() {
    this._bindInputListener(), this._maybeBindKeydownListener(), this._maybeBindPasteListener();
  }
  _bindInputListener() {
    const {
      strictMode: t,
      formatAsYouType: i,
      separateDialCode: n,
      allowDropdown: s,
      countrySearch: r
    } = this.options;
    let o = !1;
    lt.ALPHA_UNICODE.test(this._getTelInputValue()) && (o = !0);
    const l = (u) => {
      const a = this._getTelInputValue();
      if (this.isAndroid && (u == null ? void 0 : u.data) === "+" && n && s && r) {
        const C = this.ui.telInput.selectionStart || 0, b = a.substring(0, C - 1), v = a.substring(C);
        this._setTelInputValue(b + v), this._openDropdownWithPlus();
        return;
      }
      this._updateCountryFromNumber(a) && this._triggerCountryChange();
      const c = (u == null ? void 0 : u.data) && lt.NON_PLUS_NUMERIC.test(u.data), d = (u == null ? void 0 : u.inputType) === Ge.PASTE && a;
      c || d && !t ? o = !0 : lt.NON_PLUS_NUMERIC.test(a) || (o = !1);
      const f = (u == null ? void 0 : u.detail) && u.detail.isSetNumber, h = this.userNumeralSet === "ascii";
      if (i && !o && !f && h) {
        const C = this.ui.telInput.selectionStart || 0, v = a.substring(
          0,
          C
        ).replace(
          lt.NON_PLUS_NUMERIC_GLOBAL,
          ""
        ).length, g = (u == null ? void 0 : u.inputType) === Ge.DELETE_FWD, m = this._getFullNumber(), p = Fs(
          m,
          a,
          _.utils,
          this.selectedCountryData,
          this.options.separateDialCode
        ), y = Hs(
          v,
          p,
          C,
          g
        );
        this._setTelInputValue(p), this.ui.telInput.setSelectionRange(y, y);
      }
    };
    this.ui.telInput.addEventListener(
      "input",
      l,
      {
        signal: this.abortController.signal
      }
    );
  }
  _maybeBindKeydownListener() {
    const { strictMode: t, separateDialCode: i, allowDropdown: n, countrySearch: s } = this.options;
    if (t || i) {
      const r = (o) => {
        if (o.key && o.key.length === 1 && !o.altKey && !o.ctrlKey && !o.metaKey) {
          if (i && n && s && o.key === "+") {
            o.preventDefault(), this._openDropdownWithPlus();
            return;
          }
          if (t) {
            const l = this._getTelInputValue(), a = !l.startsWith("+") && this.ui.telInput.selectionStart === 0 && o.key === "+", c = this._normaliseNumerals(o.key), d = /^[0-9]$/.test(c), f = i ? d : a || d, h = this.ui.telInput, C = h.selectionStart, b = h.selectionEnd, v = l.slice(0, C), g = l.slice(b), m = v + o.key + g, p = this._getFullNumber(m), y = _.utils.getCoreNumber(
              p,
              this.selectedCountryData.iso2
            ), T = this.maxCoreNumberLength && y.length > this.maxCoreNumberLength, O = this._getNewCountryFromNumber(p) !== null;
            (!f || T && !O && !a) && o.preventDefault();
          }
        }
      };
      this.ui.telInput.addEventListener("keydown", r, {
        signal: this.abortController.signal
      });
    }
  }
  _maybeBindPasteListener() {
    if (this.options.strictMode) {
      const t = (i) => {
        i.preventDefault();
        const n = this.ui.telInput, s = n.selectionStart, r = n.selectionEnd, o = this._getTelInputValue(), l = o.slice(0, s), u = o.slice(r), a = this.selectedCountryData.iso2, c = i.clipboardData.getData("text"), d = this._normaliseNumerals(c), f = s === 0 && r > 0, h = !o.startsWith("+") || f, C = d.replace(lt.NON_PLUS_NUMERIC_GLOBAL, ""), b = C.startsWith("+"), v = C.replace(/\+/g, ""), g = b && h ? `+${v}` : v;
        let m = l + g + u;
        if (m.length > 5) {
          let y = _.utils.getCoreNumber(m, a);
          for (; y.length === 0 && m.length > 0; )
            m = m.slice(0, -1), y = _.utils.getCoreNumber(m, a);
          if (!y)
            return;
          if (this.maxCoreNumberLength && y.length > this.maxCoreNumberLength)
            if (n.selectionEnd === o.length) {
              const T = y.length - this.maxCoreNumberLength;
              m = m.slice(0, m.length - T);
            } else
              return;
        }
        this._setTelInputValue(m);
        const p = s + g.length;
        n.setSelectionRange(p, p), n.dispatchEvent(new InputEvent("input", { bubbles: !0 }));
      };
      this.ui.telInput.addEventListener("paste", t, {
        signal: this.abortController.signal
      });
    }
  }
  //* Adhere to the input's maxlength attr.
  _cap(t) {
    const i = Number(this.ui.telInput.getAttribute("maxlength"));
    return i && t.length > i ? t.substring(0, i) : t;
  }
  //* Trigger a custom event on the input (typed via ItiEventMap).
  _trigger(t, i = {}) {
    const n = new CustomEvent(t, {
      bubbles: !0,
      cancelable: !0,
      detail: i
    });
    this.ui.telInput.dispatchEvent(n);
  }
  //* Open the dropdown.
  _openDropdown() {
    const { fixDropdownWidth: t, countrySearch: i } = this.options;
    if (this.dropdownAbortController = new AbortController(), t && (this.ui.dropdownContent.style.width = `${this.ui.telInput.offsetWidth}px`), this.ui.dropdownContent.classList.remove(E.HIDE), this.ui.selectedCountry.setAttribute(A.EXPANDED, "true"), this._setDropdownPosition(), i) {
      const n = this.ui.countryList.firstElementChild;
      n && (this.ui.highlightListItem(n, !1), this.ui.countryList.scrollTop = 0), this.ui.searchInput.focus();
    }
    this._bindDropdownListeners(), this.ui.dropdownArrow.classList.add(E.ARROW_UP), this._trigger(Wt.OPEN_COUNTRY_DROPDOWN);
  }
  //* Set the dropdown position
  _setDropdownPosition() {
    if (this.options.dropdownContainer && this.options.dropdownContainer.appendChild(this.ui.dropdown), !this.options.useFullscreenPopup) {
      const t = this.ui.telInput.getBoundingClientRect(), i = this.ui.telInput.offsetHeight;
      if (this.options.dropdownContainer) {
        this.ui.dropdown.style.top = `${t.top + i}px`, this.ui.dropdown.style.left = `${t.left}px`;
        const n = () => this._closeDropdown();
        window.addEventListener("scroll", n, {
          signal: this.dropdownAbortController.signal
        });
      }
    }
  }
  //* We only bind dropdown listeners when the dropdown is open.
  _bindDropdownListeners() {
    const t = this.dropdownAbortController.signal;
    this._bindDropdownMouseoverListener(t), this._bindDropdownCountryClickListener(t), this._bindDropdownClickOffListener(t), this._bindDropdownKeydownListener(t), this.options.countrySearch && this._bindDropdownSearchListeners(t);
  }
  //* When mouse over a list item, just highlight that one
  //* we add the class "highlight", so if they hit "enter" we know which one to select.
  _bindDropdownMouseoverListener(t) {
    const i = (n) => {
      var r;
      const s = (r = n.target) == null ? void 0 : r.closest(
        `.${E.COUNTRY_ITEM}`
      );
      s && this.ui.highlightListItem(s, !1);
    };
    this.ui.countryList.addEventListener(
      "mouseover",
      i,
      {
        signal: t
      }
    );
  }
  //* Listen for country selection.
  _bindDropdownCountryClickListener(t) {
    const i = (n) => {
      var r;
      const s = (r = n.target) == null ? void 0 : r.closest(
        `.${E.COUNTRY_ITEM}`
      );
      s && this._selectListItem(s);
    };
    this.ui.countryList.addEventListener("click", i, {
      signal: t
    });
  }
  //* Click off to close (except when this initial opening click is bubbling up).
  //* We cannot just stopPropagation as it may be needed to close another instance.
  _bindDropdownClickOffListener(t) {
    const i = (n) => {
      !!n.target.closest(
        `#iti-${this.id}__dropdown-content`
      ) || this._closeDropdown();
    };
    setTimeout(() => {
      document.documentElement.addEventListener(
        "click",
        i,
        { signal: t }
      );
    }, 0);
  }
  //* Listen for up/down scrolling, enter to select, or escape to close.
  //* Use keydown as keypress doesn't fire for non-char keys and we want to catch if they
  //* just hit down and hold it to scroll down (no keyup event).
  //* Listen on the document because that's where key events are triggered if no input has focus.
  _bindDropdownKeydownListener(t) {
    let i = "", n = null;
    const s = (r) => {
      [
        U.ARROW_UP,
        U.ARROW_DOWN,
        U.ENTER,
        U.ESC
      ].includes(r.key) && (r.preventDefault(), r.stopPropagation(), r.key === U.ARROW_UP || r.key === U.ARROW_DOWN ? this._handleUpDownKey(r.key) : r.key === U.ENTER ? this._handleEnterKey() : r.key === U.ESC && (this._closeDropdown(), this.ui.selectedCountry.focus())), !this.options.countrySearch && lt.HIDDEN_SEARCH_CHAR.test(r.key) && (r.stopPropagation(), n && clearTimeout(n), i += r.key.toLowerCase(), this._searchForCountry(i), n = setTimeout(() => {
        i = "";
      }, Es.HIDDEN_SEARCH_RESET_MS));
    };
    document.addEventListener("keydown", s, { signal: t });
  }
  //* Search input listeners when countrySearch enabled.
  _bindDropdownSearchListeners(t) {
    const i = () => {
      const o = this.ui.searchInput.value.trim();
      this._filterCountriesByQuery(o), this.ui.searchInput.value ? this.ui.searchClearButton.classList.remove(E.HIDE) : this.ui.searchClearButton.classList.add(E.HIDE);
    };
    let n = null;
    const s = () => {
      n && clearTimeout(n), n = setTimeout(() => {
        i(), n = null;
      }, 100);
    };
    this.ui.searchInput.addEventListener("input", s, {
      signal: t
    });
    const r = () => {
      this.ui.searchInput.value = "", this.ui.searchInput.focus(), i();
    };
    this.ui.searchClearButton.addEventListener("click", r, {
      signal: t
    });
  }
  //* Hidden search (countrySearch disabled): Find the first list item whose name starts with the query string.
  _searchForCountry(t) {
    const i = Ss(this.countries, t);
    if (i) {
      const n = i.nodeById[this.id];
      this.ui.highlightListItem(n, !1), this.ui.scrollTo(n);
    }
  }
  //* Country search: Filter the countries according to the search query.
  _filterCountriesByQuery(t) {
    let i;
    t === "" ? i = this.countries : i = Ls(this.countries, t), this.ui.filterCountries(i);
  }
  //* Highlight the next/prev item in the list (and ensure it is visible).
  _handleUpDownKey(t) {
    var n, s;
    let i = t === U.ARROW_UP ? (n = this.ui.highlightedItem) == null ? void 0 : n.previousElementSibling : (s = this.ui.highlightedItem) == null ? void 0 : s.nextElementSibling;
    !i && this.ui.countryList.childElementCount > 1 && (i = t === U.ARROW_UP ? this.ui.countryList.lastElementChild : this.ui.countryList.firstElementChild), i && (this.ui.scrollTo(i), this.ui.highlightListItem(i, !1));
  }
  //* Select the currently highlighted item.
  _handleEnterKey() {
    this.ui.highlightedItem && this._selectListItem(this.ui.highlightedItem);
  }
  //* Update the input's value to the given val (format first if possible)
  //* NOTE: this is called from _setInitialState, handleUtils and setNumber.
  _updateValFromNumber(t) {
    let i = t;
    if (this.options.formatOnDisplay && _.utils && this.selectedCountryData) {
      const n = this.options.nationalMode || !i.startsWith("+") && !this.options.separateDialCode, { NATIONAL: s, INTERNATIONAL: r } = _.utils.numberFormat, o = n ? s : r;
      i = _.utils.formatNumber(
        i,
        this.selectedCountryData.iso2,
        o
      );
    }
    i = this._beforeSetNumber(i), this._setTelInputValue(i);
  }
  //* Check if need to select a new country based on the given number
  //* Note: called from _setInitialState, keyup handler, setNumber.
  _updateCountryFromNumber(t) {
    const i = this._getNewCountryFromNumber(t);
    return i !== null ? this._setCountry(i) : !1;
  }
  // if there is a selected country, and the number doesn't start with a dial code, then add it
  _ensureHasDialCode(t) {
    const { dialCode: i, nationalPrefix: n } = this.selectedCountryData;
    if (t.startsWith("+") || !i)
      return t;
    const o = n && t.startsWith(n) && !this.options.separateDialCode ? t.substring(1) : t;
    return `+${i}${o}`;
  }
  // Get the country ISO2 code from the given number
  // BUT ONLY IF ITS CHANGED FROM THE CURRENTLY SELECTED COUNTRY
  // NOTE: consider refactoring this to be more clear
  _getNewCountryFromNumber(t) {
    const i = t.indexOf("+");
    let n = i ? t.substring(i) : t;
    const s = this.selectedCountryData.iso2, r = this.selectedCountryData.dialCode;
    n = this._ensureHasDialCode(n);
    const o = this._getDialCode(n, !0), l = Gt(n);
    if (o) {
      const u = Gt(o), a = this.dialCodeToIso2Map[u];
      if (a.length === 1)
        return a[0] === s ? null : a[0];
      if (!s && this.defaultCountry && a.includes(this.defaultCountry))
        return this.defaultCountry;
      if (r === Oi.NANP && Ye(l))
        return null;
      const { areaCodes: d, priority: f } = this.selectedCountryData;
      if (d) {
        const g = d.map(
          (m) => `${r}${m}`
        );
        for (const m of g)
          if (l.startsWith(m))
            return null;
      }
      const C = d && !(f === 0) && l.length > u.length, b = s && a.includes(s) && !C, v = s === a[0];
      if (!b && !v)
        return a[0];
    } else if (n.startsWith("+") && l.length) {
      const u = this.selectedCountryData.dialCode || "";
      return u && u.startsWith(l) ? null : "";
    } else if ((!n || n === "+") && !s)
      return this.defaultCountry;
    return null;
  }
  //* Update the selected country, dial code (if separateDialCode), placeholder, title, and selected list item.
  //* Note: called from _setInitialState, _updateCountryFromNumber, _selectListItem, setCountry.
  _setCountry(t) {
    const { separateDialCode: i, showFlags: n, i18n: s, allowDropdown: r } = this.options, o = this.selectedCountryData.iso2 || "";
    if (r && this.ui.updateSelectedItem(t), this.selectedCountryData = t ? this.countryByIso2.get(t) : {}, this.selectedCountryData.iso2 && (this.defaultCountry = this.selectedCountryData.iso2), this.ui.selectedCountry) {
      const l = t && n ? `${E.FLAG} iti__${t}` : `${E.FLAG} ${E.GLOBE}`;
      let u, a;
      if (t) {
        const { name: c, dialCode: d } = this.selectedCountryData;
        a = c, u = s.selectedCountryAriaLabel.replace("${countryName}", c).replace("${dialCode}", `+${d}`);
      } else
        a = s.noCountrySelected, u = s.noCountrySelected;
      this.ui.selectedCountryInner.className = l, this.ui.selectedCountry.setAttribute("title", a), this.ui.selectedCountry.setAttribute(A.LABEL, u);
    }
    if (i) {
      const l = this.selectedCountryData.dialCode ? `+${this.selectedCountryData.dialCode}` : "";
      this.ui.selectedDialCode.textContent = l, this.ui.updateInputPadding();
    }
    return this._updatePlaceholder(), this._updateMaxLength(), o !== t;
  }
  //* Update the maximum valid number length for the currently selected country.
  _updateMaxLength() {
    const { strictMode: t, placeholderNumberType: i, validationNumberTypes: n } = this.options, { iso2: s } = this.selectedCountryData;
    if (t && _.utils)
      if (s) {
        const r = _.utils.numberType[i];
        let o = _.utils.getExampleNumber(
          s,
          !1,
          r,
          !0
        ), l = o;
        for (; _.utils.isPossibleNumber(
          o,
          s,
          n
        ); )
          l = o, o += "0";
        const u = _.utils.getCoreNumber(l, s);
        this.maxCoreNumberLength = u.length, s === "by" && (this.maxCoreNumberLength = u.length + 1);
      } else
        this.maxCoreNumberLength = null;
  }
  //* Update the input placeholder to an example number from the currently selected country.
  _updatePlaceholder() {
    const {
      autoPlaceholder: t,
      placeholderNumberType: i,
      nationalMode: n,
      customPlaceholder: s
    } = this.options, r = t === ye.AGGRESSIVE || !this.ui.hadInitialPlaceholder && t === ye.POLITE;
    if (_.utils && r) {
      const o = _.utils.numberType[i];
      let l = this.selectedCountryData.iso2 ? _.utils.getExampleNumber(
        this.selectedCountryData.iso2,
        n,
        o
      ) : "";
      l = this._beforeSetNumber(l), typeof s == "function" && (l = s(l, this.selectedCountryData)), this.ui.telInput.setAttribute("placeholder", l);
    }
  }
  //* Called when the user selects a list item from the dropdown.
  _selectListItem(t) {
    const i = t.dataset[Ke.COUNTRY_CODE], n = this._setCountry(i);
    this._closeDropdown();
    const s = t.dataset[Ke.DIAL_CODE];
    if (this._updateDialCode(s), this.options.formatOnDisplay) {
      const r = this._getTelInputValue();
      this._updateValFromNumber(r);
    }
    this.ui.telInput.focus(), n && this._triggerCountryChange();
  }
  //* Close the dropdown and unbind any listeners.
  _closeDropdown() {
    this.ui.dropdownContent.classList.contains(E.HIDE) || (this.ui.dropdownContent.classList.add(E.HIDE), this.ui.selectedCountry.setAttribute(A.EXPANDED, "false"), this.options.countrySearch && (this.ui.searchInput.removeAttribute(A.ACTIVE_DESCENDANT), this.ui.highlightedItem && (this.ui.highlightedItem.classList.remove(E.HIGHLIGHT), this.ui.highlightedItem = null)), this.ui.dropdownArrow.classList.remove(E.ARROW_UP), this.dropdownAbortController.abort(), this.dropdownAbortController = null, this.options.dropdownContainer && this.ui.dropdown.remove(), this._trigger(Wt.CLOSE_COUNTRY_DROPDOWN));
  }
  //* Replace any existing dial code with the new one
  //* Note: called from _selectListItem and setCountry
  _updateDialCode(t) {
    const i = this._getTelInputValue(), n = `+${t}`;
    let s;
    if (i.startsWith("+")) {
      const r = this._getDialCode(i);
      r ? s = i.replace(r, n) : s = n, this._setTelInputValue(s);
    }
  }
  //* Try and extract a valid international dial code from a full telephone number.
  //* Note: returns the raw string inc plus character and any whitespace/dots etc.
  _getDialCode(t, i) {
    let n = "";
    if (t.startsWith("+")) {
      let s = "", r = !1;
      for (let o = 0; o < t.length; o++) {
        const l = t.charAt(o);
        if (/[0-9]/.test(l)) {
          if (s += l, !!!this.dialCodeToIso2Map[s])
            break;
          if (this.dialCodes.has(s)) {
            if (n = t.substring(0, o + 1), r = !0, !i)
              break;
          } else i && r && (n = t.substring(0, o + 1));
          if (s.length === this.dialCodeMaxLen)
            break;
        }
      }
    }
    return n;
  }
  //* Get the input val, adding the dial code if separateDialCode is enabled.
  _getFullNumber(t) {
    const i = t ? this._normaliseNumerals(t) : this._getTelInputValue(), { dialCode: n } = this.selectedCountryData;
    let s;
    const r = Gt(i);
    return this.options.separateDialCode && !i.startsWith("+") && n && r ? s = `+${n}` : s = "", s + i;
  }
  //* Remove the dial code if separateDialCode is enabled also cap the length if the input has a maxlength attribute
  _beforeSetNumber(t) {
    const i = this._getDialCode(t), n = Bs(
      t,
      i,
      this.options.separateDialCode,
      this.selectedCountryData
    );
    return this._cap(n);
  }
  //* Trigger the 'countrychange' event.
  _triggerCountryChange() {
    this._trigger(Wt.COUNTRY_CHANGE);
  }
  //**************************
  //*  SECRET PUBLIC METHODS
  //**************************
  //* This is called when the geoip call returns.
  handleAutoCountry() {
    this.options.initialCountry === le.AUTO && _.autoCountry && (this.defaultCountry = _.autoCountry, this.selectedCountryData.iso2 || this.ui.selectedCountryInner.classList.contains(E.GLOBE) || this.setCountry(this.defaultCountry), this.resolveAutoCountryPromise());
  }
  //* This is called when the utils request completes.
  handleUtils() {
    if (_.utils) {
      const t = this._getTelInputValue();
      t && this._updateValFromNumber(t), this.selectedCountryData.iso2 && (this._updatePlaceholder(), this._updateMaxLength());
    }
    this.resolveUtilsScriptPromise();
  }
  //********************
  //*  PUBLIC METHODS
  //********************
  //* Remove plugin.
  destroy() {
    this.ui.telInput && (this.options.allowDropdown && this._closeDropdown(), this.abortController.abort(), this.abortController = null, this.ui.destroy(), _.instances instanceof Map ? _.instances.delete(this.id) : delete _.instances[this.id]);
  }
  //* Get the extension from the current number.
  getExtension() {
    return _.utils ? _.utils.getExtension(
      this._getFullNumber(),
      this.selectedCountryData.iso2
    ) : "";
  }
  //* Format the number to the given format.
  getNumber(t) {
    if (_.utils) {
      const { iso2: i } = this.selectedCountryData, n = this._getFullNumber(), s = _.utils.formatNumber(
        n,
        i,
        t
      );
      return this._mapAsciiToUserNumerals(s);
    }
    return "";
  }
  //* Get the type of the entered number e.g. landline/mobile.
  getNumberType() {
    return _.utils ? _.utils.getNumberType(
      this._getFullNumber(),
      this.selectedCountryData.iso2
    ) : ze.UNKNOWN_NUMBER_TYPE;
  }
  //* Get the country data for the currently selected country.
  getSelectedCountryData() {
    return this.selectedCountryData;
  }
  //* Get the validation error.
  getValidationError() {
    if (_.utils) {
      const { iso2: t } = this.selectedCountryData;
      return _.utils.getValidationError(this._getFullNumber(), t);
    }
    return ze.UNKNOWN_VALIDATION_ERROR;
  }
  //* Validate the input val using number length only
  isValidNumber() {
    const { dialCode: t, iso2: i } = this.selectedCountryData;
    if (t === oe.DIAL_CODE && _.utils) {
      const n = this._getFullNumber(), s = _.utils.getCoreNumber(n, i);
      if (s[0] === oe.MOBILE_PREFIX && s.length !== oe.MOBILE_CORE_LENGTH)
        return !1;
    }
    return this._validateNumber(!1);
  }
  //* Validate the input val with precise validation
  isValidNumberPrecise() {
    return this._validateNumber(!0);
  }
  _utilsIsPossibleNumber(t) {
    return _.utils ? _.utils.isPossibleNumber(
      t,
      this.selectedCountryData.iso2,
      this.options.validationNumberTypes
    ) : null;
  }
  //* Shared internal validation logic to handle alpha character extension rules.
  _validateNumber(t) {
    if (!_.utils)
      return null;
    if (!this.selectedCountryData.iso2)
      return !1;
    const i = (o) => t ? this._utilsIsValidNumber(o) : this._utilsIsPossibleNumber(o), n = this._getFullNumber(), s = n.search(lt.ALPHA_UNICODE);
    if (s > -1 && !this.options.allowPhonewords) {
      const o = n.substring(0, s), l = i(o), u = i(n);
      return l && u;
    }
    return i(n);
  }
  _utilsIsValidNumber(t) {
    return _.utils ? _.utils.isValidNumber(
      t,
      this.selectedCountryData.iso2,
      this.options.validationNumberTypes
    ) : null;
  }
  //* Update the selected country, and update the input val accordingly.
  setCountry(t) {
    const i = t == null ? void 0 : t.toLowerCase();
    if (!ce(i))
      throw new Error(`Invalid country code: '${i}'`);
    const n = this.selectedCountryData.iso2;
    if (t && i !== n || !t && n) {
      if (this._setCountry(i), this._updateDialCode(this.selectedCountryData.dialCode), this.options.formatOnDisplay) {
        const r = this._getTelInputValue();
        this._updateValFromNumber(r);
      }
      this._triggerCountryChange();
    }
  }
  //* Set the input value and update the country.
  setNumber(t) {
    const i = this._normaliseNumerals(t), n = this._updateCountryFromNumber(i);
    this._updateValFromNumber(i), n && this._triggerCountryChange(), this._trigger(Wt.INPUT, { isSetNumber: !0 });
  }
  //* Set the placeholder number typ
  setPlaceholderNumberType(t) {
    this.options.placeholderNumberType = t, this._updatePlaceholder();
  }
  setDisabled(t) {
    this.ui.telInput.disabled = t, t ? this.ui.selectedCountry.setAttribute("disabled", "true") : this.ui.selectedCountry.removeAttribute("disabled");
  }
}
const Gs = (e) => {
  if (!_.utils && !_.startedLoadingUtilsScript) {
    let t;
    if (typeof e == "function")
      try {
        t = Promise.resolve(e());
      } catch (i) {
        return Promise.reject(i);
      }
    else
      return Promise.reject(
        new TypeError(
          `The argument passed to attachUtils must be a function that returns a promise for the utilities module, not ${typeof e}`
        )
      );
    return _.startedLoadingUtilsScript = !0, t.then((i) => {
      const n = i == null ? void 0 : i.default;
      if (!n || typeof n != "object")
        throw new TypeError(
          "The loader function passed to attachUtils did not resolve to a module object with utils as its default export."
        );
      return _.utils = n, kt("handleUtils"), !0;
    }).catch((i) => {
      throw kt("rejectUtilsScriptPromise", i), i;
    });
  }
  return null;
}, kt = (e, ...t) => {
  Object.values(_.instances).forEach((i) => {
    const n = i[e];
    typeof n == "function" && n.apply(i, t);
  });
}, _ = Object.assign(
  (e, t) => {
    const i = new Se(e, t);
    return _.instances[i.id] = i, e.iti = i, i;
  },
  {
    defaults: Ri,
    //* Using a static var like this allows us to mock it in the tests.
    documentReady: () => document.readyState === "complete",
    //* Get the country data object.
    getCountryData: () => ht,
    //* A getter for the plugin instance.
    getInstance: (e) => {
      const t = e.dataset.intlTelInputId;
      return t ? _.instances[t] : null;
    },
    //* A map from instance ID to instance object.
    instances: {},
    attachUtils: Gs,
    startedLoadingUtilsScript: !1,
    startedLoadingAutoCountry: !1,
    version: "25.14.1"
  }
);
var zs = /* @__PURE__ */ es("<input/>");
function $s(e, t) {
  mn(t, !0);
  let i = Vt(t, "disabled", 3, !1), n = Vt(t, "inputProps", 19, () => ({})), s = Vt(t, "options", 19, () => ({})), r = Vt(t, "value", 3, ""), o = /* @__PURE__ */ z(void 0), l = /* @__PURE__ */ z(void 0), u = /* @__PURE__ */ z(null), a = /* @__PURE__ */ z(!1);
  const c = () => I(l) ? s().strictMode ? I(l).isValidNumberPrecise() : I(l).isValidNumber() : null, d = () => {
    var p, y;
    const m = c();
    I(u) !== m && (W(u, m, !0), (p = t.onChangeValidity) == null || p.call(t, !!m), (y = t.onChangeErrorCode) == null || y.call(t, m ? null : I(l).getValidationError()));
  }, f = () => {
    var m, p;
    (p = t.onChangeNumber) == null || p.call(t, ((m = I(l)) == null ? void 0 : m.getNumber()) ?? ""), d();
  }, h = () => {
    var m, p;
    (p = t.onChangeCountry) == null || p.call(t, ((m = I(l)) == null ? void 0 : m.getSelectedCountryData().iso2) ?? ""), f();
  };
  Fi(() => {
    I(o) && (W(l, _(I(o), s()), !0), I(o).addEventListener("countrychange", h), r() && I(l).setNumber(r()), i() && I(l).setDisabled(i()), W(u, c(), !0), W(a, !0));
  }), Hi(() => {
    var m;
    I(o) && I(o).removeEventListener("countrychange", h), (m = I(l)) == null || m.destroy();
  }), Fn(() => {
    I(a) && I(l) && I(l).setDisabled(i());
  });
  function C() {
    return I(l);
  }
  function b() {
    return I(o);
  }
  var v = { getInstance: C, getInput: b }, g = zs();
  return ms(g, () => ({ type: "tel", oninput: f, ...n() }), void 0, void 0, void 0, void 0, !0), ys(g, (m) => W(o, m), () => I(o)), is(e, g), yn(v);
}
export {
  $s as default
};
