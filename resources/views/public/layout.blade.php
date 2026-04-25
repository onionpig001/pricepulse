<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PricePulse — Live SaaS Pricing')</title>
    <meta name="description" content="@yield('description', 'Up-to-date SaaS pricing, compared and machine-readable. Updated weekly from vendor pricing pages.')">
    @stack('head')
    <style>
        :root { --bg:#0b0d10; --fg:#e8eaed; --muted:#8a8f98; --accent:#6ca4ff; --card:#141821; --border:#222834; }
        * { box-sizing:border-box; }
        body { background:var(--bg); color:var(--fg); font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","Inter",system-ui,sans-serif; margin:0; line-height:1.55; }
        a { color:var(--accent); text-decoration:none; }
        a:hover { text-decoration:underline; }
        .container { max-width:1100px; margin:0 auto; padding:28px 22px; }
        header.top { display:flex; justify-content:space-between; align-items:baseline; padding-bottom:18px; border-bottom:1px solid var(--border); margin-bottom:28px; }
        header.top h1 { margin:0; font-size:22px; letter-spacing:-0.01em; }
        header.top h1 a { color:var(--fg); }
        header.top nav a { margin-left:18px; color:var(--muted); font-size:14px; }
        h2 { font-size:20px; margin-top:36px; margin-bottom:14px; letter-spacing:-0.01em; }
        h3 { font-size:16px; margin-top:22px; margin-bottom:8px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:18px 20px; margin-bottom:14px; }
        .muted { color:var(--muted); font-size:13px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:14px; }
        table { width:100%; border-collapse:collapse; margin:12px 0; font-size:14px; }
        th, td { padding:10px 12px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
        th { color:var(--muted); font-weight:500; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; }
        .tier-price { font-size:22px; font-weight:600; }
        .tier-price .unit { font-size:13px; color:var(--muted); font-weight:400; margin-left:4px; }
        .badge { display:inline-block; font-size:11px; padding:2px 8px; border-radius:100px; background:#1e2630; color:var(--muted); margin-right:6px; }
        .badge.change { background:#2a1a1a; color:#ffb4a2; }
        .badge.free { background:#1a2e22; color:#7ad49a; }
        ul.features { padding-left:18px; margin:8px 0; }
        ul.features li { margin:4px 0; font-size:13px; color:#cfd3d9; }
        footer { margin-top:60px; padding-top:22px; border-top:1px solid var(--border); color:var(--muted); font-size:13px; }
        .cta { display:inline-block; margin-top:10px; padding:8px 14px; border:1px solid var(--border); border-radius:6px; font-size:13px; }
        .cta:hover { border-color:var(--accent); text-decoration:none; }
        .article { max-width:720px; font-size:15.5px; }
        .article h2 { font-size:18px; margin-top:28px; padding-top:8px; border-top:1px solid var(--border); }
        .article h3 { font-size:16px; }
        .article p { margin:10px 0; color:#cfd3d9; }
        .article ul { padding-left:22px; }
        .article li { margin:6px 0; color:#cfd3d9; }
        .article table { margin:14px 0; }
        .article code { background:#1e2630; padding:1px 6px; border-radius:4px; font-size:13px; }
    </style>
</head>
<body>
<div class="container">
    <header class="top">
        <h1><a href="/">PricePulse</a> <span class="muted" style="font-size:13px">live SaaS pricing</span></h1>
        <nav>
            <a href="/">Home</a>
            <a href="/llms.txt">llms.txt</a>
        </nav>
    </header>
    @yield('content')
    <footer>
        <p>Pricing data is mirrored from each vendor's public pricing page and may lag behind live changes. Always verify against the source before making purchasing decisions. <a href="/llms.txt">llms.txt</a></p>
    </footer>
</div>
</body>
</html>
