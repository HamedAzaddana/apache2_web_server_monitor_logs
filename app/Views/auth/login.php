<?php
/**
 * Login View
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - <?= APP_TITLE ?> Monitor</title>
    <link href="<?= url('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('css/all.min.css') ?>" rel="stylesheet">
    <style>
        :root { --primary: #059669; --bg: #f0fdf4; }
        body { background-color: var(--bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: white; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); padding: 2rem; width: 100%; max-width: 400px; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header i { font-size: 3rem; color: var(--primary); }
        .btn-login { background-color: var(--primary); border-color: var(--primary); padding: 0.75rem; }
        .btn-login:hover { background-color: #047857; }
        .btn-login:disabled { opacity: 0.65; cursor: not-allowed; }
        .spinner-border-sm { width: 1rem; height: 1rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-chart-line"></i>
            <h3 class="mt-3" style="color: var(--primary)"><?= APP_TITLE ?> Monitor</h3>
            <p class="text-muted">Please login to continue</p>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= url('login') ?>" id="loginForm">
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100" id="loginBtn">
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Logging in...
                </span>
            </button>
        </form>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var btn = document.getElementById('loginBtn');
            var btnText = btn.querySelector('.btn-text');
            var btnLoading = btn.querySelector('.btn-loading');

            // Show loading state
            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
        });
    </script>
</body>
</html>
