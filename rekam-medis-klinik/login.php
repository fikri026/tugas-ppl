<?php
/**
 * Halaman Autentikasi Login Sistem Rekam Medis
 * Menggunakan PHP Session, PDO Prepared Statements, dan password_verify
 */

require_once __DIR__ . '/config/database.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . base_url('pages/dashboard.php'));
    exit;
}

$clinic = get_clinic_info($pdo);
$error = '';
$success = '';

if (isset($_GET['logout'])) {
    $success = 'Anda telah berhasil keluar dari sistem.';
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'Silakan login terlebih dahulu untuk mengakses sistem.';
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            // Flash greeting
            set_flash('success', "Selamat datang kembali, <strong>" . e($user['nama_lengkap']) . "</strong>!");
            header('Location: ' . base_url('pages/dashboard.php'));
            exit;
        } else {
            $error = 'Username atau password yang Anda masukkan salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= e($clinic['nama_klinik']); ?></title>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      margin: 0;
    }
    .login-container {
      max-width: 460px;
      width: 100%;
    }
    .login-card {
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
      padding: 2.25rem;
    }
    .brand-icon-box {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      background: linear-gradient(135deg, #1d4ed8, #059669);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.25rem;
      box-shadow: 0 10px 15px -3px rgba(29, 78, 216, 0.3);
    }
    .form-control {
      padding: 0.75rem 1rem;
      border-radius: 10px;
      border: 1px solid #cbd5e1;
      font-size: 0.95rem;
      transition: all 0.2s;
    }
    .form-control:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }
    .btn-login {
      background: linear-gradient(135deg, #1d4ed8, #2563eb);
      color: #fff;
      font-weight: 600;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      border: none;
      transition: all 0.2s;
      box-shadow: 0 4px 6px -1px rgba(29, 78, 216, 0.2);
    }
    .btn-login:hover {
      background: linear-gradient(135deg, #1e40af, #1d4ed8);
      color: #fff;
      transform: translateY(-1px);
      box-shadow: 0 6px 12px -2px rgba(29, 78, 216, 0.3);
    }
    .demo-account-badge {
      cursor: pointer;
      font-size: 0.78rem;
      transition: all 0.15s;
    }
    .demo-account-badge:hover {
      opacity: 0.85;
      transform: scale(0.98);
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-card">
    <!-- Brand Header -->
    <div class="text-center mb-4">
      <div class="brand-icon-box">
        <i data-lucide="cross" style="width:32px;height:32px;"></i>
      </div>
      <h4 class="fw-bold text-dark mb-1"><?= e($clinic['nama_klinik']); ?></h4>
      <p class="text-muted small mb-0">Sistem Informasi Rekam Medis Elektronik</p>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 small rounded-3" role="alert">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <div><?= e($error); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small rounded-3" role="alert">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <div><?= e($success); ?></div>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="" method="POST" novalidate>
      <div class="mb-3">
        <label for="username" class="form-label small fw-semibold text-secondary">Username</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px;">
            <i data-lucide="user" style="width:18px;height:18px;"></i>
          </span>
          <input type="text" name="username" id="username" class="form-control border-start-0" placeholder="Masukkan username" value="<?= isset($_POST['username']) ? e($_POST['username']) : 'admin'; ?>" required autofocus>
        </div>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px;">
            <i data-lucide="lock" style="width:18px;height:18px;"></i>
          </span>
          <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" placeholder="Masukkan password" value="admin123" required>
          <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn" style="border-radius: 0 10px 10px 0;">
            <i data-lucide="eye" id="eyeIcon" style="width:18px;height:18px;"></i>
          </button>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="rememberMe" checked>
          <label class="form-check-label small text-muted" for="rememberMe">Ingat sesi saya</label>
        </div>
        <span class="text-primary small fw-medium" title="Hubungi IT Administrator">Bantuan Login</span>
      </div>

      <button type="submit" class="btn btn-login w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
        <i data-lucide="log-in" style="width:18px;height:18px;"></i>
        <span>Masuk ke Dashboard</span>
      </button>
    </form>

    <!-- Quick Demo Accounts Helper -->
    <div class="bg-light p-3 rounded-3 border mt-4">
      <div class="small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
        <i data-lucide="sparkles" class="text-primary" style="width:16px;height:16px;"></i>
        <span>Akun Demo Cepat (Klik untuk isi otomatis):</span>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="badge bg-primary text-white border-0 py-2 px-3 demo-account-badge rounded-pill" onclick="fillLogin('admin', 'admin123')">
          Admin (admin / admin123)
        </button>
        <button type="button" class="badge bg-success text-white border-0 py-2 px-3 demo-account-badge rounded-pill" onclick="fillLogin('dr_budi', 'admin123')">
          Dokter (dr_budi / admin123)
        </button>
        <button type="button" class="badge bg-secondary text-white border-0 py-2 px-3 demo-account-badge rounded-pill" onclick="fillLogin('petugas1', 'admin123')">
          Petugas (petugas1 / admin123)
        </button>
      </div>
    </div>

    <!-- Clinic info footer note -->
    <div class="text-center mt-4">
      <span class="text-muted small d-block"><?= e($clinic['alamat_klinik']); ?></span>
      <span class="text-muted small">Hotline: <?= e($clinic['no_telepon']); ?></span>
    </div>
  </div>
</div>

<script>
  lucide.createIcons();

  // Show/Hide Password
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  toggleBtn.addEventListener('click', function() {
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      eyeIcon.setAttribute('data-lucide', 'eye-off');
    } else {
      passwordInput.type = 'password';
      eyeIcon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
  });

  // Auto-fill Demo Login
  function fillLogin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
  }
</script>

</body>
</html>
