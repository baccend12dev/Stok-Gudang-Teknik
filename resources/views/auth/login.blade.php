<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login INV - TEKNIK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body, html {
            height: 100%; margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            background: #111;
        }
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('{{ asset("img/bg-login.jpg") }}') center/cover no-repeat;
            position: relative;
        }
        .login-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 20, 25, 0.85);
        }
        .login-card {
            background: #f4f4f4;
            width: 100%;
            max-width: 440px;
            padding: 40px;
            border-radius: 4px;
            position: relative;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        .logo-header {
            text-align: center;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .logo-header svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: #111;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .logo-header .brand {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #111;
        }
        .login-card h1 {
            font-size: 20px;
            font-weight: 500;
            color: #111;
            text-align: center;
            margin: 0 0 8px 0;
        }
        .login-card p.subtitle {
            font-size: 13px;
            color: #555;
            text-align: center;
            margin: 0 0 30px 0;
        }
        .form-group { margin-bottom: 20px; }
        .label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        label {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .forgot-link {
            font-size: 11px;
            font-weight: 600;
            color: #111;
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 14px;
        }
        .input-action {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px 12px 38px;
            border: 1px solid #d4d4d8;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            color: #111;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #555;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        .checkbox-group input {
            margin: 0 8px 0 0;
            width: 15px;
            height: 15px;
            cursor: pointer;
            accent-color: #111;
        }
        .checkbox-group label {
            text-transform: none;
            letter-spacing: 0;
            font-weight: 400;
            font-size: 13px;
            color: #444;
            cursor: pointer;
            margin: 0;
        }
        .btn-submit {
            width: 100%;
            background: #18181b;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
            letter-spacing: 0.5px;
        }
        .btn-submit:hover { background: #000; }
        
        hr {
            border: 0;
            border-top: 1px solid #e5e5e5;
            margin: 24px 0;
        }
        
        .status-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Roboto Mono', monospace;
            font-size: 10px;
            color: #555;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        .status-dot {
            width: 8px; height: 8px;
            background: #22c55e;
            border-radius: 50%;
        }
        .alert-box {
            background: #e9e9e9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 12px;
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }
        .alert-box i { color: #dc2626; font-size: 14px; margin-top: 2px; }
        .alert-text {
            font-size: 10px;
            color: #555;
            line-height: 1.5;
        }
        .alert-text b { color: #111; }
        .footer-text {
            text-align: center;
            font-family: 'Roboto Mono', monospace;
            font-size: 9px;
            color: #888;
            line-height: 1.6;
        }
        .error-message {
            color: #dc2626;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="logo-header">
            <!-- Custom Robotic Arm SVG mimicking the design -->
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 21V19C4 17.8954 4.89543 17 6 17H18C19.1046 17 20 17.8954 20 19V21"></path>
                <path d="M12 17V13"></path>
                <path d="M12 13L8 9"></path>
                <path d="M8 9L11 5"></path>
                <circle cx="11" cy="5" r="2"></circle>
                <path d="M6 7L8 9"></path>
            </svg>
            <span class="brand">INVENTARIS TEKNIK</span>
        </div>
        
        <p class="subtitle">Silakan masuk untuk mengelola inventaris gudang.</p>
        
        <form method="POST" action="{{ route('login.post') }}">
            {{ csrf_field() }}
            
            <div class="form-group">
                <div class="label-row">
                    <label for="email">EMAIL</label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-regular fa-user input-icon"></i>
                    <input type="text" id="email" name="email" class="form-control" placeholder="email@ottopharm.com" value="{{ old('email') }}" required autofocus>
                </div>
                @if ($errors->has('email'))
                    <span class="error-message">{{ $errors->first('email') }}</span>
                @elseif ($errors->any())
                    <span class="error-message">{{ $errors->first() }}</span>
                @endif
            </div>
            
            <div class="form-group">
                <div class="label-row">
                    <label for="password">KATA SANDI</label>
                </div>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon" style="font-size: 13px;"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <i class="fa-regular fa-eye input-action" id="togglePassword"></i>
                </div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya di perangkat ini</label>
            </div>
            
            <button type="submit" class="btn-submit">
                MASUK <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </button>
        </form>
        
        <hr>
        
        <div class="status-row">
            <div class="status-dot"></div>
            SISTEM MONITORING STOK PADA GUDANG TEKNIK
        </div>
        
        <div class="alert-box">
            <i class="fa-solid fa-shield-halved"></i>
            <div class="alert-text">
                <b>Peringatan:</b> Akses hanya untuk personel resmi. 
            </div>
        </div>
        
        <div class="footer-text">
            VERSI 4.2.0-INDUSTRIAL- | BUILD 2026.07.16<br>
            &copy; 2026 TEKNIK OTOPHARMA. ALL RIGHTS RESERVED.
        </div>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const password = document.getElementById('password');
        if (password.type === 'password') {
            password.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
</script>
</body>
</html>