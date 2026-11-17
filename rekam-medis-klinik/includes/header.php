<?php
/**
 * Header Layout Template
 * Mengatur sesi login, CSS dependencies, Google Fonts, dan Lucide Icons
 */

require_once __DIR__ . '/../config/database.php';

// Pastikan user sudah login
check_login();

$user_active = current_user($pdo);
$clinic = get_clinic_info($pdo);
$flash = get_flash();

if (!isset($page_title)) {
    $page_title = 'Sistem Rekam Medis Klinik';
}
if (!isset($active_menu)) {
    $active_menu = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title); ?> - <?= e($clinic['nama_klinik']); ?></title>
  
  <!-- Favicon Medical Icon SVG -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231d4ed8'><path d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14h-2v-3H8v-2h3V8h2v3h3v2h-3z'/></svg>">

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom Medical Theme CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">

  <!-- Chart.js (untuk visualisasi grafik di dashboard dan laporan) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<div class="app-wrapper">
  <!-- Mobile Backdrop -->
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- Sidebar -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Main Area -->
  <div class="app-main">
    <!-- Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Flash Notifications Container -->
    <div class="content-body pb-0">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
          <?php if ($flash['type'] === 'success'): ?>
            <i data-lucide="check-circle" style="width:20px;height:20px;"></i>
          <?php elseif ($flash['type'] === 'danger'): ?>
            <i data-lucide="alert-circle" style="width:20px;height:20px;"></i>
          <?php elseif ($flash['type'] === 'warning'): ?>
            <i data-lucide="alert-triangle" style="width:20px;height:20px;"></i>
          <?php else: ?>
            <i data-lucide="info" style="width:20px;height:20px;"></i>
          <?php endif; ?>
          <div class="flex-grow-1">
            <?= $flash['message']; ?>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
    </div>

    <!-- Main Dynamic Content -->
    <main class="content-body">
