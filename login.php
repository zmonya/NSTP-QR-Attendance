<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login · QR Attendance System</title>
    
    <!-- Google Fonts - Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* GLOBAL STYLES — DEEPER, RICHER LIGHT BLUE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(145deg, #5f9db2 0%, #3e7a8c 100%);
            /* deeper ocean blue — still light but with gravity */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
        }

        /* MAIN WRAPPER – TWO PANEL, ELEVATED SHADOW */
        .split-wrapper {
            display: flex;
            width: 100%;
            max-width: 1120px;
            background: #ffffff;
            border-radius: 40px;
            box-shadow: 0 25px 50px -8px rgba(21, 66, 80, 0.25);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(2px);
        }

        /* === LEFT PANEL – BRIGHT, CLEAN, LOGO === */
        .left-panel {
            flex: 1.1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            position: relative;
        }

        .left-content {
            text-align: center;
            max-width: 360px;
        }

        .brand-icon-big {
            width: 150px;
            height: 150px;
            background: linear-gradient(150deg, #e1f0f5, #cbe1ea);
            border-radius: 40% 60% 40% 60% / 50% 40% 60% 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.8rem;
            border: 1px solid rgba(47, 112, 130, 0.25);
            box-shadow: 0 16px 32px rgba(46, 99, 112, 0.12);
            animation: floatIcon 3.8s infinite ease-in-out;
        }

        .brand-icon-big i {
            font-size: 5rem;
            color: #25697c;      /* deeper teal, richer */
            opacity: 0.95;
            filter: drop-shadow(0 6px 10px rgba(30, 90, 105, 0.15));
        }

        @keyframes floatIcon {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
            100% { transform: translateY(0px); }
        }

        .left-panel h2 {
            font-size: 2.3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #184955;        /* deeper blue‑gray */
            margin-bottom: 0.5rem;
        }

        .left-tagline {
            font-size: 0.95rem;
            font-weight: 500;
            color: #2c6a7a;
            background: #def0f5;
            padding: 0.6rem 1.8rem;
            border-radius: 60px;
            display: inline-block;
            border: 1px solid #aacdd6;
            backdrop-filter: blur(2px);
            margin-top: 0.6rem;
        }

        .left-panel p {
            color: #2f6c7c;
            font-size: 0.95rem;
            margin-top: 1.3rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .left-panel p i {
            color: #1d6b80;
        }

        /* === RIGHT PANEL – DEEPER LIGHT BLUE, FORM CENTERED === */
        .right-panel {
            flex: 1.3;
            background: linear-gradient(165deg, #b8dbe7, #9fc7d5);
            /* deeper, more present light blue – not pale, still airy but with character */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3.5rem 2.5rem;
            position: relative;
            backdrop-filter: blur(4px);
        }

        .right-panel h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #0c404e;        /* deeper teal, strong contrast */
            letter-spacing: -0.01em;
            margin-bottom: 0.2rem;
            text-align: center;
        }

        .right-sub {
            color: #1a5668;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 2rem;
            text-align: center;
            border-bottom: 1.5px dashed #5c97a5;
            padding-bottom: 1rem;
            width: 100%;
            max-width: 280px;
        }

        /* FORM – absolute center */
        .login-form {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* INPUTS – crisp, with deeper border */
        .input-group {
            width: 100%;
            margin-bottom: 1.5rem;
            border-radius: 50px;
            overflow: hidden;
            border: 1.8px solid #7faebb;
            background: white;
            transition: all 0.2s ease;
            box-shadow: 0 6px 14px rgba(52, 104, 118, 0.08);
        }

        .input-group:focus-within {
            border-color: #1e6a7f;
            box-shadow: 0 8px 18px rgba(30, 106, 127, 0.15);
            background: white;
        }

        .input-group-text {
            background: white;
            border: none;
            color: #1f677b;        /* deeper icon color */
            padding: 0.95rem 1.2rem 0.95rem 1.5rem;
            font-size: 1rem;
            border-radius: 50px 0 0 50px;
        }

        .form-control {
            border: none;
            padding: 0.95rem 1rem 0.95rem 0.2rem;
            font-size: 0.98rem;
            font-weight: 400;
            color: #0c4452;
            background: white;
            border-radius: 0 50px 50px 0;
        }

        .form-control::placeholder {
            color: #699aa8;
            font-weight: 300;
            font-size: 0.9rem;
        }

        .form-control:focus {
            box-shadow: none;
            background: white;
            outline: none;
        }

        /* PASSWORD TOGGLE */
        .password-toggle {
            background: white;
            border: none;
            color: #568e9c;
            padding: 0 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0 50px 50px 0;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #0f5f72;
            background: #ecf6fa;
        }

        /* FORGOT LINK – deeper but subtle */
        .form-helper {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            margin-top: -0.6rem;
            margin-bottom: 1.2rem;
        }

        .form-helper a {
            color: #1a5565;
            font-size: 0.78rem;
            text-decoration: none;
            font-weight: 600;
            padding: 0.3rem 1.2rem;
            border-radius: 40px;
            background: rgba(255,255,255,0.6);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: 1px solid #8ab3bf;
        }

        .form-helper a:hover {
            background: white;
            border-color: #1e6a7f;
            color: #0a414f;
        }

        /* BUTTON – deeper blue, confident */
        .btn-login {
            background: #256f84;   /* deeper, richer blue */
            border: none;
            padding: 0.95rem 1.8rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: white;
            border-radius: 60px;
            width: 70%;
            transition: all 0.25s ease;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid rgba(255,255,255,0.25);
            box-shadow: 0 10px 20px rgba(28, 94, 109, 0.25);
            margin-top: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .btn-login:hover {
            background: #1a5a6b;
            transform: translateY(-3px);
            box-shadow: 0 16px 28px rgba(22, 78, 92, 0.3);
        }

        .btn-login i {
            font-size: 0.9rem;
        }

        /* REGISTER LINK – deeper accents */
        .register-link {
            margin-top: 2.2rem;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .register-link a {
            color: #0c4553;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            background: rgba(255,255,255,0.6);
            padding: 0.7rem 2rem;
            border-radius: 60px;
            border: 1px solid #80aeb9;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.2s;
            backdrop-filter: blur(2px);
        }

        .register-link a:hover {
            background: white;
            border-color: #256f84;
            color: #093b46;
        }

        /* ALERTS – refined */
        .alert {
            border-radius: 30px;
            padding: 0.8rem 1.3rem;
            margin-bottom: 1.5rem;
            border: none;
            font-size: 0.82rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(6px);
            border-left: 6px solid;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 8px 18px rgba(19, 73, 88, 0.08);
        }

        .alert-danger {
            color: #8e444c;
            border-left-color: #b14d56;
            background: #ffefed;
        }

        .alert-success {
            color: #266155;
            border-left-color: #2f8475;
            background: #e0f3ef;
        }

        .close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 1.2rem;
            opacity: 0.5;
            color: inherit;
        }

        .close:hover {
            opacity: 1;
        }

        /* FOOTER CREDIT – deeper */
        .footer-credit {
            margin-top: 2rem;
            font-size: 0.72rem;
            color: #1e5665;
            display: flex;
            justify-content: center;
            gap: 0.6rem;
            letter-spacing: 0.02em;
            font-weight: 500;
        }

        /* MOBILE */
        @media (max-width: 820px) {
            .split-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            .left-panel, .right-panel {
                padding: 2.5rem 1.8rem;
            }
            .btn-login {
                width: 80%;
            }
        }

        /* autofill */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 100px white inset;
            -webkit-text-fill-color: #0c4452;
        }

        .brand-icon-big i {
            transition: transform 0.2s;
        }
        .brand-icon-big:hover i {
            transform: rotate(6deg) scale(1.03);
        }

    </style>
</head>
<body>

    <!-- TWO PANEL LAYOUT – DEEPER BLUE ATMOSPHERE -->
    <div class="split-wrapper">
        
        <!-- LEFT PANEL – CLEAN, LOGO, FLOATING -->
        <div class="left-panel">
            <div class="left-content">
                <div class="brand-icon-big">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h2>QR Attendance</h2>
                
                
            
            </div>
        </div>

        <!-- RIGHT PANEL – DEEPER LIGHT BLUE, FORM PERFECTLY CENTERED -->
        <div class="right-panel">
            <h3>Welcome</h3>
            <div class="right-sub">
                <i class="fas fa-arrow-right-to-bracket" style="margin-right:6px;"></i> access dashboard
            </div>

            <!-- ALERT MESSAGES -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- LOGIN FORM – FULLY CENTERED -->
            <form class="login-form" action="endpoint/login-user.php" method="POST">
                <!-- Username / Email -->
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="text" name="username" class="form-control" placeholder="Username or email" required autofocus>
                </div>

                <!-- Password + toggle -->
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                
                <!-- forgot -->
                <div class="form-helper">
                    <a href="forgot-password.php">
                        <i class="fas fa-key"></i> Forgot?
                    </a>
                </div>

                <!-- SIGN IN BUTTON – DEEPER TONE -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-arrow-right-to-bracket"></i> Sign In
                </button>
            </form>

            <!-- REGISTER LINK -->
            <div class="register-link">
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create account
                </a>
            </div>

            <!-- subtle credit -->
            <div class="footer-credit">
                <i class="fas fa-qrcode"></i> QR Attendance v2.1
                <span style="color:#679aa5;">•</span>
            
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // auto dismiss alerts
            setTimeout(function() {
                $('.alert').alert('close');
            }, 4000);

            // password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                toggleIcon.classList.toggle('fa-eye');
                toggleIcon.classList.toggle('fa-eye-slash');
                
                this.style.color = type === 'text' ? '#1a6a7f' : '#568e9c';
            });

            // loading state on submit
            $('form').on('submit', function() {
                const btn = $(this).find('button[type="submit"]');
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Signing in...');
                btn.prop('disabled', true);
            });

            // border focus effect (consistency)
            $('.form-control').on('focus', function() {
                $(this).closest('.input-group').css('border-color', '#1e6a7f');
            }).on('blur', function() {
                $(this).closest('.input-group').css('border-color', '#7faebb');
            });
        });
    </script>
</body>
</html>