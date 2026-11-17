<?php
/**
 * Konfigurasi Database dan Helper Sistem Rekam Medis Klinik
 * Menggunakan PHP PDO dengan Prepared Statements
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'rekam_medis';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:10px;margin:30px auto;max-width:600px;'>
        <h3 style='margin-top:0'>Gagal Menghubungkan ke Database MySQL</h3>
        <p><strong>Pesan Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p>Pastikan MySQL di XAMPP sudah berjalan dan database <code>rekam_medis</code> sudah diimpor dari file <code>database/rekam_medis.sql</code>.</p>
    </div>");
}

/**
 * Base URL dinamis helper
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Tentukan root path project rekam-medis-klinik
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Potong hingga folder rekam-medis-klinik
    $pos = strpos($scriptDir, '/rekam-medis-klinik');
    if ($pos !== false) {
        $root = substr($scriptDir, 0, $pos + strlen('/rekam-medis-klinik'));
    } else {
        $root = $scriptDir;
    }
    
    $root = rtrim($root, '/');
    $path = ltrim($path, '/');
    return $protocol . $host . $root . ($path ? '/' . $path : '');
}

/**
 * Cek sesi login pengguna
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . base_url('login.php?error=unauthorized'));
        exit;
    }
}

/**
 * Dapatkan informasi user yang sedang login
 */
function current_user($pdo) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, username, nama_lengkap, role, email, foto FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Flash message helper
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitasi string output
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia
 */
function format_date_id($dateStr, $withTime = false) {
    if (empty($dateStr) || $dateStr === '0000-00-00' || $dateStr === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($dateStr);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('j', $timestamp);
    $m = $bulan[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);
    
    if ($withTime) {
        return "{$d} {$m} {$y}, " . date('H:i', $timestamp) . " WIB";
    }
    return "{$d} {$m} {$y}";
}

/**
 * Format mata uang Rupiah
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Auto-generate Nomor Rekam Medis Baru (Format: RM-YYYY-XXXX)
 */
function generate_nomor_rm($pdo) {
    $currentYear = date('Y');
    $prefix = "RM-{$currentYear}-";
    $stmt = $pdo->prepare("SELECT nomor_rm FROM pasien WHERE nomor_rm LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastRM = $stmt->fetchColumn();

    if ($lastRM) {
        $lastNum = (int)substr($lastRM, strlen($prefix));
        $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNum = '0001';
    }
    return $prefix . $nextNum;
}

/**
 * Auto-generate Nomor Antrean Kunjungan Harian (Format: A-001, A-002, ...)
 */
function generate_nomor_antrean($pdo, $tanggal = null) {
    if (!$tanggal) $tanggal = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT nomor_antrean FROM pendaftaran WHERE tanggal_kunjungan = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$tanggal]);
    $lastAntrean = $stmt->fetchColumn();

    if ($lastAntrean && preg_match('/A-(\d+)/', $lastAntrean, $matches)) {
        $num = (int)$matches[1] + 1;
    } else {
        $num = 1;
    }
    return 'A-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

/**
 * Dapatkan informasi klinik untuk header/surat/cetak
 */
function get_clinic_info($pdo) {
    static $clinic = null;
    if ($clinic === null) {
        $stmt = $pdo->query("SELECT * FROM pengaturan_klinik WHERE id = 1 LIMIT 1");
        $clinic = $stmt->fetch();
        if (!$clinic) {
            $clinic = [
                'nama_klinik' => 'Klinik Pratama Sehat Medika',
                'slogan' => 'Pelayanan Kesehatan Terpercaya & Modern',
                'alamat_klinik' => 'Jl. Kesehatan No. 45, Jakarta Selatan',
                'no_telepon' => '(021) 7890-1234',
                'email_klinik' => 'info@sehatmedika.co.id',
                'website' => 'www.sehatmedika.co.id',
                'pimpinan_klinik' => 'dr. H. Hendra Wijaya, Sp.PD',
                'jam_operasional' => 'Senin - Sabtu: 08.00 - 21.00 WIB'
            ];
        }
    }
    return $clinic;
}
