// sportvatar-headless — serves the static headless Sportvatar site from R2 and proxies the dynamic paths to Laravel.
//
// Architecture: docs/HEADLESS.md (repo) / flovatar-kb notes/headless-architecture.md (same design as flovatar-headless).
// Objects: R2 bucket sportvatar-web under SITE_PREFIX (testnet: "testnet/site/"), written by `php artisan headless:build`
// and by the per-item regeneration job.  Same-origin proxy to ORIGIN for /api/*, wallet login (/login*, /logout,
// /csrf-token), list searches, template data, image endpoints and the pages that are not static (packs/claims, Sportbit
// item pages, meme maker, leaderboard, admin), so the Laravel session cookie is first-party on the frontend host
// (no CORS credentials, no third-party cookies).
//
// Bindings / vars (wrangler.toml):
//   SITE        R2 bucket binding -> sportvatar-web   (optional: without it objects are fetched from BUCKET_URL)
//   BUCKET_URL  public URL of the bucket (R2 custom domain / r2.dev), only needed without the SITE binding
//   SITE_PREFIX "testnet/site/" (testnet) | "site/" (prod)
//   ORIGIN      Laravel origin, e.g. https://testnet.sportvatar.com
//   NOINDEX     "true" on testnet (robots.txt Disallow + X-Robots-Tag)

const TYPES = {
  html: "text/html; charset=utf-8", css: "text/css; charset=utf-8", js: "application/javascript; charset=utf-8",
  mjs: "application/javascript; charset=utf-8", json: "application/json", xml: "application/xml; charset=utf-8",
  svg: "image/svg+xml", png: "image/png", jpg: "image/jpeg", jpeg: "image/jpeg", gif: "image/gif", webp: "image/webp",
  ico: "image/x-icon", woff: "font/woff", woff2: "font/woff2", ttf: "font/ttf", otf: "font/otf",
  eot: "application/vnd.ms-fontobject", mp4: "video/mp4", webm: "video/webm", mp3: "audio/mpeg", wav: "audio/wav",
  pdf: "application/pdf", txt: "text/plain; charset=utf-8", webmanifest: "application/manifest+json",
  glb: "model/gltf-binary", gltf: "model/gltf+json", map: "application/json",
};

// Full browser UA for the Worker's own subrequests: the zone WAF blocks "bot-like" agents on HTML.
const FETCH_UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 sportvatar-edge";

const HTML_CACHE = "public, max-age=0, s-maxage=60, stale-while-revalidate=300";
const ASSET_CACHE = "public, max-age=86400";
const IMMUTABLE = "public, max-age=31536000, immutable";

// Paths answered by Laravel (same origin through this Worker).
const PROXY = [
  /^\/api\//, /^\/login(\/|$)/, /^\/logout\/?$/, /^\/csrf-token\/?$/,
  /^\/search\/?$/, /^\/(locker-room|leaderboard|transactions|builder)\/search\/?$/,
  /^\/templates\//, /^\/storage\//,
  /^\/sportvatars\/\d+\/0x[a-f0-9]{16}\/.+/,                  // downloads (svg/png), twitter
  /^\/components\//,                                             // Sportbit item pages: server-rendered (100k+ on mainnet)
  /^\/((it|es)\/)?pack(\/|$)/,                                   // paid packs + free-pack claims (session)
  /^\/((it|es)\/)?meme-maker\/?$/, /^\/leaderboard\/?$/, /^\/mosaic\/?$/,
  /^\/subscribe\/?$/, /^\/moonpay\//, /^\/treasure-hunting/, /^\/twitter\//, /^\/graffle\/?$/,
  /^\/admin(\/|$)/, /^\/sanctum\//, /^\/livewire\//, /^\/filament\//, /^\/css\/filament\//, /^\/js\/filament\//, /^\/fonts\/filament\//,
  /^\/transactions\/0x[a-f0-9]{16}\/?$/, /^\/collection\/api(\/|$)/,
];

// Anonymous, user-agnostic GETs that may be cached at the edge (honours the origin's Cache-Control).
// (Laravel answers them with public, s-maxage=60 when anonymous and private, no-store otherwise; see App\Support\PublicCache.)
const EDGE_CACHEABLE = [
  /^\/api\/v2\/(?!auth\/)/, /^\/api\/image\//, /^\/templates\//, /^\/components\/\d+\/0x[a-f0-9]{16}\/?$/, /^\/collection\/api(\/|$)/,
  /^\/((it|es)\/)?meme-maker\/?$/, /^\/leaderboard\/?$/, /^\/mosaic\/?$/,
];

const ITEM_OWNER_URL = /^\/(sportvatars)\/(\d+)\/(0x[a-f0-9]{16})\/?$/;
const ITEM_URL = /^\/(sportvatars)\/(\d+)\/?$/;
const LANGS = ["it", "es"];

function typeFor(key) {
  const name = key.split("/").pop();
  const ext = name.includes(".") ? name.split(".").pop().toLowerCase() : "";
  return TYPES[ext];
}

function cfg(env) {
  return {
    prefix: env.SITE_PREFIX || "testnet/site/",
    origin: (env.ORIGIN || "https://testnet.sportvatar.com").replace(/\/+$/, ""),
    bucketUrl: (env.BUCKET_URL || "").replace(/\/+$/, ""),
    noindex: String(env.NOINDEX || "") === "true",
  };
}

// ---------------------------------------------------------------- R2 access (binding or public bucket URL)

async function getObject(env, c, key, request) {
  if (env.SITE) {
    const opts = {};
    if (request && request.headers.get("if-none-match")) opts.onlyIf = { etagDoesNotMatch: request.headers.get("if-none-match").replace(/^W\//, "").replace(/"/g, "") };
    const obj = await env.SITE.get(c.prefix + key, opts);
    if (!obj) return null;
    return {
      body: "body" in obj ? obj.body : null, // no body = 304 (onlyIf failed)
      etag: obj.httpEtag,
      contentType: obj.httpMetadata && obj.httpMetadata.contentType,
      cacheControl: obj.httpMetadata && obj.httpMetadata.cacheControl,
      size: obj.size,
    };
  }
  if (!c.bucketUrl) return null;
  const r = await fetch(c.bucketUrl + "/" + c.prefix + key, {
    headers: { "User-Agent": FETCH_UA },
    cf: { cacheTtl: 30, cacheEverything: true },
  });
  if (r.status !== 200) return null;
  return {
    body: r.body,
    etag: r.headers.get("etag"),
    contentType: r.headers.get("content-type"),
    cacheControl: r.headers.get("cache-control"),
    size: Number(r.headers.get("content-length") || 0),
  };
}

function objectResponse(c, request, key, obj, status = 200, cacheOverride = null, typeKey = null) {
  const h = new Headers();
  const type = typeFor(typeKey || key) || obj.contentType || "application/octet-stream";
  h.set("Content-Type", type);
  let cache = cacheOverride;
  if (!cache) {
    if (type.startsWith("text/html")) cache = HTML_CACHE;
    else if (/@[a-f0-9]+$/.test(key)) cache = IMMUTABLE; // versioned bundle copy (js/app.js@{mix id})
    else if (/\.(js|css)$/.test(key)) cache = "public, max-age=300"; // unversioned bundle: may change on deploy
    else cache = obj.cacheControl && !/text\/html/.test(type) ? obj.cacheControl : ASSET_CACHE;
  }
  h.set("Cache-Control", cache);
  if (obj.etag) h.set("ETag", obj.etag);
  h.set("X-Content-Type-Options", "nosniff");
  h.set("X-Headless", "r2");
  if (c.noindex) h.set("X-Robots-Tag", "noindex, nofollow, noarchive");
  if (obj.body === null && status === 200) return new Response(null, { status: 304, headers: h });
  return new Response(request.method === "HEAD" ? null : obj.body, { status, headers: h });
}

// ---------------------------------------------------------------- origin proxy

function rewriteLocation(loc, c, frontendOrigin) {
  if (!loc) return loc;
  try {
    const u = new URL(loc, c.origin);
    if (u.origin === c.origin) {
      const m = u.pathname.match(ITEM_OWNER_URL); // origin redirects to owner URLs -> canonical item URL
      return frontendOrigin + (m ? `/${m[1]}/${m[2]}` : u.pathname) + u.search + u.hash;
    }
  } catch (e) { /* keep */ }
  return loc;
}

async function proxy(request, env, c, url, opts = {}) {
  const upstream = c.origin + url.pathname + url.search;
  const headers = new Headers(request.headers);
  for (const k of ["host", "cf-connecting-ip", "cf-ipcountry", "cf-ray", "cf-visitor", "x-real-ip", "content-length"]) headers.delete(k);
  headers.set("X-Forwarded-Host", url.host);
  headers.set("X-Forwarded-Proto", "https");
  headers.set("X-Headless-Proxy", "1");
  if (!headers.get("user-agent")) headers.set("User-Agent", FETCH_UA);
  const init = { method: request.method, headers, redirect: "manual" };
  if (!["GET", "HEAD"].includes(request.method)) init.body = request.body;
  const cacheable = request.method === "GET" && (opts.cacheable || EDGE_CACHEABLE.some((re) => re.test(url.pathname)));
  if (cacheable) init.cf = { cacheEverything: true };
  let r = await fetch(upstream, init);
  const out = new Headers(r.headers);
  if (out.has("location")) out.set("location", rewriteLocation(out.get("location"), c, url.origin));
  // Laravel cookies are host-only; drop a Domain attribute if one ever appears so they stick to this host
  const cookies = typeof r.headers.getSetCookie === "function" ? r.headers.getSetCookie() : [];
  if (cookies.length) {
    out.delete("set-cookie");
    for (const ck of cookies) out.append("set-cookie", ck.replace(/;\s*domain=[^;]*/i, ""));
  }
  // cacheEverything subrequests get the zone's Browser Cache TTL (4 h) written over Laravel's max-age=0: restore it, or
  // browsers keep marketplace/API JSON and pages for hours (edge TTL stays s-maxage=60). Immutable assets keep theirs.
  if (cacheable) {
    const cc = out.get("cache-control") || "";
    if (/s-maxage=/.test(cc) && !/immutable/.test(cc)) out.set("cache-control", cc.replace(/(^|,\s*)max-age=\d+/, "$1max-age=0"));
  }
  out.set("X-Headless", opts.fallback ? "origin-fallback" : "origin");
  if (c.noindex) out.set("X-Robots-Tag", "noindex, nofollow, noarchive");
  let resp = new Response(r.body, { status: r.status, statusText: r.statusText, headers: out });
  if ((out.get("content-type") || "").includes("text/html") && typeof HTMLRewriter !== "undefined") {
    resp = rewriteOriginHtml(resp, c, url.origin, url);
  }
  return resp;
}

// Server-rendered HTML coming through the proxy: canonical/og:url and absolute origin links -> this host.
function rewriteOriginHtml(resp, c, frontendOrigin, url) {
  const fix = (v) => (v && v.startsWith(c.origin) ? frontendOrigin + v.slice(c.origin.length) : v);
  // The classic layout prints a site-wide canonical/og:url (https://sportvatar.com/) on every page -> this page's own URL
  // (owner URLs -> canonical item URL; ?lang= kept because it selects the page language).
  const own = url ? (() => {
    const m = url.pathname.match(ITEM_OWNER_URL);
    const lang = url.searchParams.get("lang");
    return frontendOrigin + (m ? `/${m[1]}/${m[2]}` : url.pathname) + (LANGS.includes(lang || "") ? `?lang=${lang}` : "");
  })() : null;
  const siteWide = (v) => !v || /^https?:\/\/[^\/]+\/?$/.test(v);
  const canonical = (v) => (own && siteWide(v) ? own : fix(v));
  return new HTMLRewriter()
    .on('link[rel="canonical"]', { element(e) { e.setAttribute("href", canonical(e.getAttribute("href"))); } })
    .on('meta[property="og:url"]', { element(e) { e.setAttribute("content", canonical(e.getAttribute("content"))); } })
    .on("a[href]", { element(e) { const h = e.getAttribute("href"); if (h && h.startsWith(c.origin)) e.setAttribute("href", fix(h)); } })
    .transform(resp);
}

// Item page not built yet (minted seconds ago): render it at the origin (owner URL), cache briefly.
async function itemFallback(request, env, c, url, seg, id) {
  const r = await fetch(`${c.origin}/${seg}/${id}`, { redirect: "manual", headers: { "User-Agent": FETCH_UA } });
  const loc = r.headers.get("location");
  if (r.status >= 300 && r.status < 400 && loc) {
    const target = new URL(loc, c.origin);
    if (ITEM_OWNER_URL.test(target.pathname)) {
      const u = new URL(url);
      u.pathname = target.pathname;
      return proxy(new Request(u.toString(), request), env, c, u, { fallback: true });
    }
  }
  return null;
}

async function notFound(request, env, c) {
  const obj = await getObject(env, c, "404.html", null);
  if (obj) return objectResponse(c, request, "404.html", obj, 404, "public, max-age=0, s-maxage=60");
  return new Response("Not found", { status: 404, headers: { "Content-Type": "text/plain" } });
}

function robots(c, url) {
  const body = c.noindex
    ? "User-agent: *\nDisallow: /\n"
    : `User-agent: *\nAllow: /\nSitemap: ${url.origin}/sitemap.xml\n`;
  return new Response(body, { headers: { "Content-Type": "text/plain; charset=utf-8", "Cache-Control": "public, max-age=3600" } });
}

// ---------------------------------------------------------------- router

async function handle(request, env) {
  const c = cfg(env);
  const url = new URL(request.url);
  let path;
  try { path = decodeURIComponent(url.pathname); } catch { path = url.pathname; }
  if (path.includes("..") || path.includes("\0")) return new Response("Bad request", { status: 400 });

  // www.<host> -> apex (only reached if www is attached to this Worker; normally the origin already redirects www)
  if (url.hostname.startsWith("www.")) {
    return Response.redirect(`${url.protocol}//${url.hostname.slice(4)}${url.pathname}${url.search}`, 301);
  }
  if (path === "/robots.txt") return robots(c, url);
  // contract MetadataViews.ExternalURL = https://sportvatar.com/collectible/{id} (never a route) -> the Sportvatar page
  const collectible = path.match(/^\/collectible\/(\d+)\/?$/);
  if (collectible && ["GET", "HEAD"].includes(request.method)) {
    return Response.redirect(`${url.origin}/sportvatars/${collectible[1]}`, 301);
  }
  if (path === "/__headless/health") return new Response(JSON.stringify({ ok: true, prefix: c.prefix, origin: c.origin, r2: !!env.SITE }), { headers: { "Content-Type": "application/json", "Cache-Control": "no-store" } });

  // canonical item URLs: /sportvatars/{id}/{owner} -> /sportvatars/{id}
  const owner = path.match(ITEM_OWNER_URL);
  if (owner && ["GET", "HEAD"].includes(request.method)) {
    return Response.redirect(`${url.origin}/${owner[1]}/${owner[2]}${url.search}`, 301);
  }

  if (!["GET", "HEAD"].includes(request.method) || PROXY.some((re) => re.test(path))) {
    return proxy(request, env, c, url);
  }

  // ?lang=it|es on a non-localized page: static objects are English, the origin renders the language (cacheable per URL)
  if (LANGS.includes(url.searchParams.get("lang") || "")) {
    return proxy(request, env, c, url, { cacheable: true });
  }

  // shells: wallet comes from the URL at runtime (FIND/.find names are resolved by the origin)
  if (/^\/collection\/[^\/]+\/?$/.test(path) && !/^\/collection\/0x[a-f0-9]{16}\/?$/.test(path.toLowerCase())) {
    return proxy(request, env, c, url);
  }
  if (/^\/collection\/[^\/]+\/?$/.test(path)) {
    const obj = await getObject(env, c, "_shells/collection.html", request);
    if (obj) return objectResponse(c, request, "_shells/collection.html", obj);
    return proxy(request, env, c, url, { fallback: true });
  }
  if (path === "/collection" || path === "/collection/") return Response.redirect(`${url.origin}/`, 302);

  // static objects
  let p = path.replace(/^\/+/, "");
  // mix-versioned bundle: /js/app.js?id=X -> the copy of exactly that build (pages built before a deploy keep theirs)
  const vid = url.searchParams.get("id");
  if (vid && /^[a-f0-9]{8,64}$/.test(vid) && /^(js|css)\/[^\/]+\.(js|css)$/.test(p)) {
    const obj = await getObject(env, c, `${p}@${vid}`, request);
    if (obj) return objectResponse(c, request, `${p}@${vid}`, obj, 200, null, p);
  }
  const candidates = p === "" ? ["index.html"] : p.endsWith("/") ? [p + "index.html"] : typeFor(p) ? [p] : [p + "/index.html", p + ".html"];
  for (const key of candidates) {
    const obj = await getObject(env, c, key, request);
    if (obj) return objectResponse(c, request, key, obj);
  }

  const item = path.match(ITEM_URL);
  if (item) {
    const fb = await itemFallback(request, env, c, url, item[1], item[2]);
    if (fb && fb.status === 200) return fb;
    return notFound(request, env, c);
  }
  // assets not mirrored on R2: try the origin, otherwise 404
  if (typeFor(p)) {
    const r = await proxy(request, env, c, url, { fallback: true });
    if (r.status !== 404) return r;
  }
  return notFound(request, env, c);
}

export default {
  async fetch(request, env, ctx) {
    try {
      return await handle(request, env, ctx);
    } catch (e) {
      return new Response("Worker error: " + (e && e.message ? e.message : String(e)), { status: 502, headers: { "Content-Type": "text/plain", "Cache-Control": "no-store" } });
    }
  },
};
