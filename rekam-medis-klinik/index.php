<?php
/**
 * Entry Point Website Sistem Informasi Rekam Medis
 * Mengarahkan ke dashboard jika telah login, atau ke halaman login jika belum.
 */

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . base_url('pages/dashboard.php'));
    exit;
} else {
    header('Location: ' . base_url('login.php'));
    exit;
}
