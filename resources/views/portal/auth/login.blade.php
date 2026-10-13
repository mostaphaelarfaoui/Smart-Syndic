<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — Smart Syndic</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: radial-gradient(circle at 50% 30%, #0d3833 0%, #090d16 70%);
            position: relative;
            overflow: hidden;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 2;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--brand-primary) 0%, #14b8a6 100%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 8px 20px var(--brand-primary-glow);
        }
        .login-brand h1 {
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--text-primary);
        }
        .login-brand p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 0.35rem;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="login-brand-icon">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h1>Smart Syndic</h1>
            <p>بوابة إدارة وتسيير الإقامات السكنية</p>
        </div>

        @if(session('success'))
            <div style="background:var(--color-success-light); border:1px solid rgba(16,185,129,0.3); color:var(--color-success); padding:0.85rem 1rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; font-size:0.88rem;">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background:var(--color-danger-light); border:1px solid rgba(244,63,94,0.3); color:var(--color-danger); padding:0.85rem 1rem; border-radius:var(--radius-md); font-weight:700; margin-bottom:1.5rem; font-size:0.88rem;">
                ⚠ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('portal.login.submit') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">البريد الإلكتروني</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', 'syndic@alfirdaws.ma') }}"
                    required
                    autocomplete="email"
                    autofocus
                    class="form-control"
                    placeholder="name@example.com"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">كلمة المرور</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    value="password"
                    required
                    autocomplete="current-password"
                    class="form-control"
                    placeholder="••••••••"
                >
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-size:0.85rem; color:var(--text-secondary);">
                    <input type="checkbox" name="remember" value="1" checked style="accent-color:var(--brand-primary);">
                    <span>تذكر بيانات الدخول</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; font-size:1rem;">
                تسجيل الدخول للمنصة ➔
            </button>
        </form>
    </div>
</div>

</body>
</html>
