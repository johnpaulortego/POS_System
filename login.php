<?php
session_start();
require 'db.php';

// Check if a "Remember Me" cookie exists to pre-fill the username
$remembered_user = isset($_COOKIE['remember_purr']) ? $_COOKIE['remember_purr'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $hashMatch = password_verify($_POST['password'], $user['password']);
        $plainMatch = ($user['password'] === $_POST['password']); 
        
        if ($hashMatch || $plainMatch) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Handle "Remember Me" logic
            if (isset($_POST['remember_me'])) {
                setcookie('remember_purr', $_POST['username'], time() + (86400 * 30), "/"); // Save for 30 days
            } else {
                setcookie('remember_purr', '', time() - 3600, "/"); // Clear cookie
            }

            header("Location: " . ($user['role'] == 'admin' ? "admin.php" : "pos.php"));
            exit;
        }
    }
    $error = "Invalid credentials!"; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Purr'Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --orange: #F28B50;
            --light-orange: #FFF5F0;
            --white: #FFFFFF;
            --text-main: #2D2D2D;
            --text-muted: #8B8B8B;
            --border: #D0D0D0;
            --bg-gradient-1: #F28B50;
            --bg-gradient-2: #e07a42;
            --input-bg: #FFFFFF;
        }

        [data-theme="dark"] {
            --white: #2A2A2A;
            --text-main: #FFFFFF;
            --text-muted: #AAAAAA;
            --border: #444444;
            --bg-gradient-1: #1A1A1A;
            --bg-gradient-2: #2A2A2A;
            --input-bg: #333333;
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; }
        body { margin: 0; background: linear-gradient(135deg, #F28B50 0%, #e07a42 60%, #c9622e 100%); height: 100vh; display: flex; justify-content: center; align-items: center; color: var(--text-main); transition: background 0.3s ease; }

        .login-box { 
            background: var(--white); padding: 60px 45px; border-radius: 35px; 
            box-shadow: 0 25px 70px rgba(242, 139, 80, 0.12); width: 100%; max-width: 420px; 
            text-align: center; border: 1px solid var(--border); transition: background 0.3s ease;
            position: relative;
        }

        .theme-toggle-login { position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-muted); transition: color 0.2s; }
        .theme-toggle-login:hover { color: var(--orange); }

        .logo { font-size: 34px; font-weight: 800; margin-bottom: 8px; color: var(--text-main); }
        .logo span { color: var(--orange); }
        .subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 45px; font-weight: 500; }

        /* --- FLOATING INPUTS --- */
        .input-group { position: relative; margin-bottom: 25px; }
        .input-group input { 
            width: 100%; padding: 16px 20px; border: 1.5px solid var(--border); 
            border-radius: 16px; outline: none; font-size: 15px; font-weight: 600;
            background: var(--input-bg); transition: 0.3s; color: var(--text-main);
        }
        .input-group label { 
            position: absolute; left: 18px; top: 17px; color: var(--text-muted); 
            font-size: 15px; font-weight: 500; transition: all 0.2s ease; 
            background: var(--white); padding: 0 6px; pointer-events: none; 
        }
        .input-group input:focus { border-color: var(--orange); box-shadow: 0 0 0 4px var(--light-orange); }
        .input-group input:focus ~ label, .input-group input:not(:placeholder-shown) ~ label { 
            top: -10px; left: 14px; font-size: 12px; font-weight: 800; color: var(--orange); text-transform: uppercase;
        }

        /* --- REMEMBER ME ROW --- */
        .options-row { display: flex; align-items: center; justify-content: flex-start; margin-bottom: 25px; padding-left: 5px; }
        .remember-me { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: var(--text-muted); font-weight: 600; }
        .remember-me input { display: none; }
        .custom-checkbox { 
            width: 20px; height: 20px; border: 2px solid var(--border); 
            border-radius: 6px; position: relative; transition: 0.2s; 
        }
        .remember-me input:checked + .custom-checkbox { background: var(--orange); border-color: var(--orange); }
        .remember-me input:checked + .custom-checkbox::after { 
            content: ''; position: absolute; left: 6px; top: 2px; width: 4px; height: 8px; 
            border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); 
        }

        .toggle-password { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); }

        button { 
            background: var(--orange); color: white; border: none; width: 100%; padding: 18px; 
            border-radius: 18px; cursor: pointer; font-weight: 800; font-size: 16px; transition: 0.3s;
        }
        button:hover { background: #e07a42; transform: translateY(-3px); box-shadow: 0 12px 24px rgba(242, 139, 80, 0.25); }

        .error-msg { background: #FFF0F0; color: #EB5757; padding: 14px; border-radius: 16px; font-size: 13px; font-weight: 700; margin-bottom: 30px; }
        .loader { width: 18px; height: 18px; border: 3px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; display: none; animation: spin 0.8s linear infinite; margin-left: 12px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;overflow:hidden;z-index:0;">
        <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,0.08);"></div>
        <div style="position:absolute;bottom:-60px;left:-60px;width:250px;height:250px;border-radius:50%;background:rgba(255,255,255,0.06);"></div>
    </div>
    <div class="login-box" style="position:relative;z-index:1;">
        <div class="theme-toggle-login" onclick="toggleTheme()">
            <i data-lucide="moon" id="themeIcon" style="width: 24px; height: 24px;"></i>
        </div>
        <div class="logo">Purr'<span>Coffee</span></div>
        <p class="subtitle">Enter your credentials to sign in.</p>

        <?php if(isset($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST">
            <div class="input-group">
                <input type="text" name="username" value="<?= htmlspecialchars($remembered_user) ?>" placeholder=" " required autocomplete="off">
                <label>Username</label>
            </div>

            <div class="input-group">
                <input type="password" id="pass" name="password" placeholder=" " required>
                <label>Password</label>
                <span class="toggle-password" onclick="toggle()">
                    <i data-lucide="eye" id="eyeIcon" style="width: 20px;"></i>
                </span>
            </div>

            <div class="options-row">
                <label class="remember-me">
                    <input type="checkbox" name="remember_me" <?= $remembered_user ? 'checked' : '' ?>>
                    <div class="custom-checkbox"></div>
                    Remember me
                </label>
            </div>

            <button type="submit" id="btn">
                <span id="txt">Sign In</span>
                <div class="loader" id="ld"></div>
            </button>
        </form>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();
        function toggle() { 
            const p = document.getElementById('pass'); 
            const icon = document.getElementById('eyeIcon');
            p.type = p.type === 'password' ? 'text' : 'password';
            icon.setAttribute('data-lucide', p.type === 'password' ? 'eye' : 'eye-off');
            lucide.createIcons();
        }
        document.getElementById('loginForm').onsubmit = () => {
            document.getElementById('btn').disabled = true;
            document.getElementById('ld').style.display = 'inline-block';
            document.getElementById('txt').innerText = 'Signing in...';
        };
    </script>
</body>
</html>