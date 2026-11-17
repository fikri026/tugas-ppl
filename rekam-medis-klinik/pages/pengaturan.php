<?php
/**
 * Halaman Pengaturan Sistem Rekam Medis Klinik
 * Fitur: Profil Klinik, Pengaturan Akun Pengguna, dan Ubah Password dengan password_hash()
 */

$page_title = 'Pengaturan Sistem';
$active_menu = 'pengaturan';

require_once __DIR__ . '/../includes/header.php';

// 1. UPDATE PROFIL KLINIK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_klinik') {
    $nama_klinik = trim($_POST['nama_klinik'] ?? '');
    $slogan = trim($_POST['slogan'] ?? '');
    $alamat_klinik = trim($_POST['alamat_klinik'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $email_klinik = trim($_POST['email_klinik'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $pimpinan_klinik = trim($_POST['pimpinan_klinik'] ?? '');
    $jam_operasional = trim($_POST['jam_operasional'] ?? '');

    if (empty($nama_klinik) || empty($alamat_klinik) || empty($no_telepon) || empty($pimpinan_klinik)) {
        set_flash('danger', 'Seluruh kolom profil klinik bertanda bintang (*) wajib diisi!');
    } else {
        $stmt = $pdo->prepare("
            UPDATE pengaturan_klinik 
            SET nama_klinik = ?, slogan = ?, alamat_klinik = ?, no_telepon = ?, email_klinik = ?, website = ?, pimpinan_klinik = ?, jam_operasional = ?
            WHERE id = 1
        ");
        $stmt->execute([$nama_klinik, $slogan, $alamat_klinik, $no_telepon, $email_klinik, $website, $pimpinan_klinik, $jam_operasional]);
        set_flash('success', 'Profil dan identitas klinik berhasil diperbarui!');
        header('Location: ' . base_url('pages/pengaturan.php'));
        exit;
    }
}

// 2. UPDATE PROFIL AKUN LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profil') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_id = (int)$_SESSION['user_id'];

    if (empty($nama_lengkap) || empty($username)) {
        set_flash('danger', 'Nama lengkap dan username tidak boleh kosong!');
    } else {
        // Cek username unik untuk user lain
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $user_id]);
        if ($check->fetch()) {
            set_flash('danger', 'Username tersebut sudah dipakai pengguna lain!');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = ?, username = ?, email = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $username, $email, $user_id]);
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['username'] = $username;
            set_flash('success', 'Informasi akun pengguna berhasil diperbarui!');
            header('Location: ' . base_url('pages/pengaturan.php'));
            exit;
        }
    }
}

// 3. UBAH PASSWORD DENGAN VALIDASI & HASHING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_password') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    $user_id = (int)$_SESSION['user_id'];

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        set_flash('danger', 'Seluruh kolom ubah password wajib diisi!');
    } elseif (strlen($password_baru) < 6) {
        set_flash('danger', 'Password baru minimal harus terdiri dari 6 karakter!');
    } elseif ($password_baru !== $konfirmasi_password) {
        set_flash('danger', 'Konfirmasi password baru tidak cocok!');
    } else {
        // Verifikasi password lama
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $curr = $stmt->fetch();

        if ($curr && password_verify($password_lama, $curr['password'])) {
            $new_hash = password_hash($password_baru, PASSWORD_BCRYPT);
            $stmt_u = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_u->execute([$new_hash, $user_id]);
            set_flash('success', 'Password akun Anda berhasil diubah dengan aman!');
            header('Location: ' . base_url('pages/pengaturan.php'));
            exit;
        } else {
            set_flash('danger', 'Password lama yang Anda masukkan salah!');
        }
    }
}

// Reload info terbaru
$clinic = get_clinic_info($pdo);
$me = current_user($pdo);
?>

<!-- Header Halaman -->
<div class="page-header">
  <div>
    <h2 class="page-title">Pengaturan Sistem & Profil</h2>
    <p class="page-subtitle">Konfigurasi identitas klinik, informasi kontak, profil pengguna, dan keamanan akun.</p>
  </div>
</div>

<div class="row g-4">
  <!-- Kolom Kiri: Profil Klinik -->
  <div class="col-12 col-xl-7">
    <div class="card-custom h-100">
      <div class="card-header-custom">
        <h3 class="card-header-title">
          <i data-lucide="building-2" class="text-primary" style="width:20px;height:20px;"></i>
          <span>Profil & Legalitas Klinik</span>
        </h3>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Kop Resmi Surat</span>
      </div>
      <div class="card-body-custom p-4">
        <form action="" method="POST">
          <input type="hidden" name="action" value="update_klinik">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label form-label-custom">Nama Resmi Klinik *</label>
              <input type="text" name="nama_klinik" class="form-control form-control-custom" value="<?= e($clinic['nama_klinik']); ?>" required>
            </div>
            <div class="col-md-5">
              <label class="form-label form-label-custom">Penanggung Jawab / Kepala *</label>
              <input type="text" name="pimpinan_klinik" class="form-control form-control-custom" value="<?= e($clinic['pimpinan_klinik']); ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Slogan / Visi Pelayanan</label>
              <input type="text" name="slogan" class="form-control form-control-custom" value="<?= e($clinic['slogan']); ?>">
            </div>

            <div class="col-12">
              <label class="form-label form-label-custom">Alamat Lengkap Klinik *</label>
              <textarea name="alamat_klinik" class="form-control form-control-custom" rows="2" required><?= e($clinic['alamat_klinik']); ?></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">No. Telepon / Hotline *</label>
              <input type="text" name="no_telepon" class="form-control form-control-custom" value="<?= e($clinic['no_telepon']); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Email Resmi Klinik</label>
              <input type="email" name="email_klinik" class="form-control form-control-custom" value="<?= e($clinic['email_klinik']); ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label form-label-custom">Website Resmi</label>
              <input type="text" name="website" class="form-control form-control-custom" value="<?= e($clinic['website']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-custom">Jam Operasional Layanan</label>
              <input type="text" name="jam_operasional" class="form-control form-control-custom" value="<?= e($clinic['jam_operasional']); ?>">
            </div>

            <div class="col-12 pt-3 border-top d-flex justify-content-end">
              <button type="submit" class="btn btn-primary-custom">
                <i data-lucide="save" style="width:16px;height:16px;"></i>
                <span>Simpan Profil Klinik</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Kolom Kanan: Pengaturan Akun & Ubah Password -->
  <div class="col-12 col-xl-5">
    <!-- Card Akun Pengguna -->
    <div class="card-custom mb-4">
      <div class="card-header-custom">
        <h3 class="card-header-title">
          <i data-lucide="user-cog" class="text-success" style="width:20px;height:20px;"></i>
          <span>Profil Akun Saya</span>
        </h3>
        <span class="badge bg-success-subtle text-success border border-success-subtle text-capitalize">
          <?= e($me['role']); ?>
        </span>
      </div>
      <div class="card-body-custom p-4">
        <form action="" method="POST">
          <input type="hidden" name="action" value="update_profil">
          <div class="mb-3">
            <label class="form-label form-label-custom">Nama Lengkap & Gelar *</label>
            <input type="text" name="nama_lengkap" class="form-control form-control-custom" value="<?= e($me['nama_lengkap']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label form-label-custom">Username Login *</label>
            <input type="text" name="username" class="form-control form-control-custom font-monospace" value="<?= e($me['username']); ?>" required>
          </div>
          <div class="mb-4">
            <label class="form-label form-label-custom">Alamat Email</label>
            <input type="email" name="email" class="form-control form-control-custom" value="<?= e($me['email']); ?>">
          </div>
          <button type="submit" class="btn btn-primary-custom w-100 justify-content-center">
            <i data-lucide="check" style="width:16px;height:16px;"></i>
            <span>Simpan Informasi Akun</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Card Ubah Password -->
    <div class="card-custom" id="ubahPassword">
      <div class="card-header-custom">
        <h3 class="card-header-title">
          <i data-lucide="key" class="text-danger" style="width:20px;height:20px;"></i>
          <span>Ubah Kata Sandi (Password)</span>
        </h3>
      </div>
      <div class="card-body-custom p-4">
        <form action="" method="POST">
          <input type="hidden" name="action" value="ubah_password">
          <div class="mb-3">
            <label class="form-label form-label-custom">Password Saat Ini (Lama) *</label>
            <input type="password" name="password_lama" class="form-control form-control-custom" placeholder="Ketikkan password lama" required>
          </div>
          <div class="mb-3">
            <label class="form-label form-label-custom">Password Baru *</label>
            <input type="password" name="password_baru" class="form-control form-control-custom" placeholder="Minimal 6 karakter" required>
          </div>
          <div class="mb-4">
            <label class="form-label form-label-custom">Ulangi Password Baru *</label>
            <input type="password" name="konfirmasi_password" class="form-control form-control-custom" placeholder="Ketik ulang password baru" required>
          </div>
          <button type="submit" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
            <i data-lucide="shield" style="width:16px;height:16px;"></i>
            <span>Perbarui Password Akun</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
