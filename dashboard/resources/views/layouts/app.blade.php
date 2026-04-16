<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'WA Bot Monitor') — Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:          #0d1117;
      --surface:     #161b22;
      --surface2:    #21262d;
      --border:      #30363d;
      --accent:      #25d366;
      --accent-dim:  #1a9e4a;
      --text:        #e6edf3;
      --text-muted:  #8b949e;
      --danger:      #f85149;
      --warning:     #d29922;
      --info:        #58a6ff;
      --radius:      8px;
      --sidebar-w:   240px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar {
      position: fixed; top: 0; left: -100%; width: var(--sidebar-w);
      height: 100vh; background: var(--surface); border-right: 1px solid var(--border);
      display: flex; flex-direction: column; z-index: 999;
      transition: left .25s ease; padding: 1.5rem 0;
    }
    .sidebar.open { left: 0; }
    @media (min-width: 768px) { .sidebar { left: 0; } }

    .sidebar-logo {
      padding: 0 1.25rem 1.5rem;
      font-size: 1.1rem; font-weight: 700; color: var(--accent);
      display: flex; align-items: center; gap: .5rem;
      border-bottom: 1px solid var(--border);
    }
    .sidebar-logo span { font-size: 1.4rem; }

    nav a {
      display: flex; align-items: center; gap: .75rem;
      padding: .65rem 1.25rem; text-decoration: none;
      color: var(--text-muted); font-size: .875rem; font-weight: 500;
      border-left: 3px solid transparent; transition: all .15s;
    }
    nav a:hover, nav a.active {
      background: var(--surface2); color: var(--text);
      border-left-color: var(--accent);
    }
    nav a .icon { font-size: 1.1rem; width: 20px; text-align: center; }

    .sidebar-footer {
      margin-top: auto; padding: 1rem 1.25rem;
      border-top: 1px solid var(--border);
    }

    /* ── Main ── */
    .main-wrap { margin-left: 0; transition: margin .25s ease; min-height: 100vh; }
    @media (min-width: 768px) { .main-wrap { margin-left: var(--sidebar-w); } }

    .topbar {
      background: var(--surface); border-bottom: 1px solid var(--border);
      padding: .875rem 1.25rem; display: flex; align-items: center; gap: 1rem;
      position: sticky; top: 0; z-index: 100;
    }
    .topbar h1 { font-size: 1rem; font-weight: 600; flex: 1; }
    .hamburger {
      background: none; border: none; color: var(--text); cursor: pointer;
      font-size: 1.25rem; padding: .25rem; display: block;
    }
    @media (min-width: 768px) { .hamburger { display: none; } }

    /* Bot status badge */
    .status-badge {
      display: inline-flex; align-items: center; gap: .35rem;
      padding: .2rem .6rem; border-radius: 20px; font-size: .75rem; font-weight: 600;
    }
    .status-badge.online  { background: rgba(37,211,102,.15); color: var(--accent); }
    .status-badge.offline { background: rgba(248,81,73,.15);  color: var(--danger); }
    .status-badge.connecting { background: rgba(210,153,34,.15); color: var(--warning); }
    .status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .status-badge.online .status-dot { animation: pulse 2s infinite; }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: .4; }
    }

    .content { padding: 1.25rem; }
    @media (min-width: 768px) { .content { padding: 1.75rem; } }

    /* ── Cards & helpers ── */
    .card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.25rem;
    }
    .stat-grid {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: .875rem;
    }
    @media (min-width: 640px)  { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .stat-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1rem; text-align: center;
    }
    .stat-card .num { font-size: 1.8rem; font-weight: 700; color: var(--accent); }
    .stat-card .lbl { font-size: .75rem; color: var(--text-muted); margin-top: .25rem; }

    .btn {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .5rem 1rem; border-radius: var(--radius);
      font-size: .875rem; font-weight: 500; cursor: pointer;
      text-decoration: none; border: 1px solid transparent; transition: all .15s;
    }
    .btn-primary  { background: var(--accent);     color: #000; border-color: var(--accent); }
    .btn-primary:hover  { background: var(--accent-dim); border-color: var(--accent-dim); }
    .btn-danger   { background: var(--danger);     color: #fff; border-color: var(--danger); }
    .btn-danger:hover   { opacity: .85; }
    .btn-ghost    { background: transparent; color: var(--text-muted); border-color: var(--border); }
    .btn-ghost:hover    { background: var(--surface2); color: var(--text); }
    .btn-sm       { padding: .3rem .65rem; font-size: .8rem; }

    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-size: .825rem; font-weight: 500; color: var(--text-muted); margin-bottom: .35rem; }
    .form-control {
      width: 100%; padding: .55rem .75rem;
      background: var(--surface2); border: 1px solid var(--border);
      border-radius: var(--radius); color: var(--text); font-family: inherit;
      font-size: .875rem; transition: border-color .15s;
    }
    .form-control:focus { outline: none; border-color: var(--accent); }
    .form-control.is-invalid { border-color: var(--danger); }

    .alert {
      padding: .75rem 1rem; border-radius: var(--radius); font-size: .875rem; margin-bottom: 1rem;
    }
    .alert-success { background: rgba(37,211,102,.1); border: 1px solid rgba(37,211,102,.3); color: var(--accent); }
    .alert-danger  { background: rgba(248,81,73,.1);  border: 1px solid rgba(248,81,73,.3);  color: var(--danger); }

    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    th, td { padding: .75rem .875rem; text-align: left; border-bottom: 1px solid var(--border); }
    th { background: var(--surface2); font-weight: 600; font-size: .8rem; text-transform: uppercase; color: var(--text-muted); }
    tr:hover td { background: var(--surface2); }

    .badge {
      display: inline-block; padding: .15rem .55rem;
      border-radius: 20px; font-size: .75rem; font-weight: 600;
    }
    .badge-success { background: rgba(37,211,102,.15); color: var(--accent); }
    .badge-danger  { background: rgba(248,81,73,.15);  color: var(--danger); }
    .badge-info    { background: rgba(88,166,255,.15); color: var(--info); }

    .page-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.25rem; }

    .overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.5); z-index: 998;
    }
    .overlay.show { display: block; }
    @media (min-width: 768px) { .overlay { display: none !important; } }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
  </style>
  @stack('styles')
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span>💬</span> WA Bot Monitor
  </div>
  <nav>
    <a href="{{ route('dashboard') }}"        class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">
      <span class="icon">📊</span> Dashboard
    </a>
    <a href="{{ route('allowlist.index') }}"  class="{{ request()->routeIs('allowlist*') ? 'active' : '' }}">
      <span class="icon">📋</span> Allow-List
    </a>
    <a href="{{ route('logs.index') }}"       class="{{ request()->routeIs('logs*') ? 'active' : '' }}">
      <span class="icon">📝</span> Log Pesan
    </a>
    <a href="{{ route('approved.index') }}"   class="{{ request()->routeIs('approved*') ? 'active' : '' }}">
      <span class="icon">✅</span> Approved Session
    </a>
    <a href="{{ route('settings.index') }}"   class="{{ request()->routeIs('settings*') ? 'active' : '' }}">
      <span class="icon">⚙️</span> Pengaturan
    </a>
  </nav>
  <div class="sidebar-footer">
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="btn btn-ghost" style="width:100%; justify-content:center;">
        🚪 Logout
      </button>
    </form>
  </div>
</aside>

<div class="main-wrap">
  <header class="topbar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <h1>@yield('page-title', 'Dashboard')</h1>
    @yield('topbar-right')
  </header>
  <main class="content">
    @if(session('success'))
      <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">❌ {{ session('error') }}</div>
    @endif
    @yield('content')
  </main>
</div>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
  }
</script>
@stack('scripts')
</body>
</html>
