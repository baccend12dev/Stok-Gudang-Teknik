<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login Inventori HR - PT OTTO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap + Font --}}
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

    {{-- Google Font Inter --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top left, #2563eb 0, #0f172a 45%, #020617 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 24px;
        }

        .auth-card {
            background: rgba(248, 250, 252, 0.96);
            border-radius: 18px;
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.45),
                0 0 0 1px rgba(148, 163, 184, 0.2);
            padding: 32px 28px;
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(59,130,246,0.18), transparent 60%);
            pointer-events: none;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .brand-badge i { font-size: 14px; }

        .auth-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .auth-subtitle {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .auth-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control-modern {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            box-shadow: none;
            font-size: 14px;
            padding: 10px 14px;
            height: 42px;
            transition: all 0.15s ease;
            width: 100%;
            background: #fff;
            color: #0f172a;
            font-weight: 500;
        }
        .form-control-modern:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        /* Password Toggle */
        .password-group {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            z-index: 2;
            padding: 5px;
        }
        .toggle-password:hover {
            color: #2563eb;
        }

        .btn-login {
            width: 100%;
            border-radius: 10px;
            background: linear-gradient(to right, #2563ebff, #1d4ed8);
            border: none;
            color: #fff !important;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 16px;
            margin-top: 10px;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background: linear-gradient(to right, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 12px 25px -5px rgba(30, 64, 175, 0.5);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        .error-box {
            margin-top: 16px;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-box i { font-size: 16px; }

        .footer-text {
            margin-top: 24px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="brand-badge">
                <i class="fa fa-cubes"></i>
                <span>INVENTORI HR · PT. OTTO</span>
            </div>

            <h1 class="auth-title">Sign In</h1>
            <p class="auth-subtitle">Silahkan masuk untuk melanjutkan.</p>

            <form class="auth-form" method="POST" action="{{ route('login.post') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email"
                           name="email"
                           class="form-control-modern"
                           value="{{ old('email') }}"
                           placeholder="Masukkan alamat email"
                           required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="password-group">
                        <input type="password"
                               name="password"
                               id="password-field"
                               class="form-control-modern"
                               placeholder="Masukkan password"
                               required>
                        <i class="fa fa-eye toggle-password" id="toggle-password" title="Tampilkan Password"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fa fa-sign-in"></i> SIGN IN
                </button>

                @if ($errors->any())
                    <div class="error-box">
                        <i class="fa fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
            </form>

            <div class="footer-text">
                &copy; {{ date('Y') }} HR · PT. OTTO PHARMACEUTICAL
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            // Logic Toggle Password (Mata)
            $('#toggle-password').click(function() {
                var input = $('#password-field');
                var icon = $(this);

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    icon.attr('title', 'Sembunyikan Password');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    icon.attr('title', 'Tampilkan Password');
                }
            });
        });
    </script>
</body>
</html>