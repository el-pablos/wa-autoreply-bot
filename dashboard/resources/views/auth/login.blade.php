<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — WA Bot Monitor</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --accent: #25d366; --bg: #0d1117; --surface: #161b22; --border: #30363d; --text: #e6edf3; --danger: #f85149; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .login-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 2rem; width: 100%; max-width: 380px; }
    .logo { text-align: center; font-size: 2.5rem; margin-bottom: .5rem; }
    h1 { text-align: center; font-size: 1.25rem; font-weight: 700; margin-bottom: .25rem; }
    .sub { text-align: center; font-size: .825rem; color: #8b949e; margin-bottom: 1.75rem; }
    label { display: block; font-size: .825rem; font-weight: 500; color: #8b949e; margin-bottom: .35rem; }
    input[type="password"] { width: 100%; padding: .65rem .875rem; background: #21262d; border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: .875rem; font-family: inherit; transition: border-color .15s; }
    input[type="password"]:focus { outline: none; border-color: var(--accent); }
    button { width: 100%; margin-top: 1rem; padding: .7rem; background: var(--accent); color: #000; border: none; border-radius: 8px; font-weight: 600; font-size: .9rem; cursor: pointer; transition: opacity .15s; }
    button:hover { opacity: .85; }
    .error { background: rgba(248,81,73,.1); border: 1px solid rgba(248,81,73,.3); color: var(--danger); padding: .65rem .875rem; border-radius: 8px; font-size: .825rem; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo">🤖</div>
    <h1>WA Bot Monitor</h1>
    <p class="sub">Masukkan password untuk lanjut</p>
    @if($errors->has('password'))
      <div class="error">❌ {{ $errors->first('password') }}</div>
    @endif
    <form action="{{ route('login.post') }}" method="POST">
      @csrf
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" autofocus>
      </div>
      <button type="submit">Masuk →</button>
    </form>
  </div>
</body>
</html>
