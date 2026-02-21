<?php
require_once __DIR__ . '/../config/config.php';

if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $user = db()->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_info'] = $user;
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .bg-circles {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.03);
            animation: float 8s infinite ease-in-out;
        }
        .circle:nth-child(1) { width: 400px; height: 400px; top: -100px; left: -100px; animation-delay: 0s; }
        .circle:nth-child(2) { width: 300px; height: 300px; bottom: -80px; right: -80px; animation-delay: 2s; }
        .circle:nth-child(3) { width: 200px; height: 200px; top: 50%; left: 70%; animation-delay: 4s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .logo-section {
            text-align: center;
            margin-bottom: 36px;
        }
        .logo-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(240,147,251,0.3);
        }
        .logo-section h1 {
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .logo-section p {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.4);
            font-size: 15px;
        }
        .form-group input {
            width: 100%;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 14px 16px 14px 44px;
            color: #fff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }
        .form-group input::placeholder { color: rgba(255,255,255,0.3); }
        .form-group input:focus {
            outline: none;
            border-color: #f093fb;
            background: rgba(255,255,255,0.12);
            box-shadow: 0 0 0 3px rgba(240,147,251,0.15);
        }
        .pw-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        .pw-toggle:hover { color: rgba(255,255,255,0.8); }
        .error-msg {
            background: rgba(245,87,108,0.15);
            border: 1px solid rgba(245,87,108,0.4);
            border-radius: 10px;
            padding: 12px 16px;
            color: #ff8099;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(245,87,108,0.3);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(245,87,108,0.45);
        }
        .btn-login:active { transform: translateY(0); }
        .hint {
            text-align: center;
            margin-top: 24px;
            color: rgba(255,255,255,0.35);
            font-size: 12px;
        }
        .hint span {
            color: rgba(255,255,255,0.6);
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="bg-circles">
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
</div>
<div class="login-wrapper">
    <div class="login-card">
        <div class="logo-section">
            <div class="logo-icon">🍽️</div>
            <h1><?= APP_NAME ?></h1>
            <p><?= APP_TAGLINE ?> · Admin Portal</p>
        </div>
        <?php if ($error): ?>
        <div class="error-msg"><i class="fa fa-circle-exclamation"></i> <?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Username / Email</label>
                <div class="input-wrap">
                    <i class="fa fa-user"></i>
                    <input type="text" name="username" placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fa fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" onclick="togglePw()"><i class="fa fa-eye" id="pwIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="fa fa-right-to-bracket"></i> &nbsp;Sign In to Dashboard</button>
        </form>
        <p class="hint">Default: <span>admin</span> / <span>admin123</span></p>
    </div>
</div>
<script>
function togglePw() {
    const p = document.getElementById('password');
    const i = document.getElementById('pwIcon');
    if (p.type === 'password') {
        p.type = 'text';
        i.className = 'fa fa-eye-slash';
    } else {
        p.type = 'password';
        i.className = 'fa fa-eye';
    }
}
</script>
</body>
</html>
