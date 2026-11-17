/**
 * Sistem Rekam Medis Klinik - script.js
 * Inisialisasi ikon Lucide, jam digital, sidebar mobile, pencarian tabel
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Inisialisasi Lucide Icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // 2. Jam Digital & Tanggal Live
  function updateLiveClock() {
    const clockEl = document.getElementById('liveClock');
    if (!clockEl) return;
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;
  }
  setInterval(updateLiveClock, 1000);
  updateLiveClock();

  // 3. Mobile Sidebar Toggle & Backdrop
  const toggleBtn = document.getElementById('sidebarToggleBtn');
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      if (backdrop) backdrop.classList.toggle('show');
    });
  }

  if (backdrop && sidebar) {
    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // 4. Inisialisasi Tooltip & Popover Bootstrap
  if (typeof bootstrap !== 'undefined') {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  // 5. Auto dismiss alert setelah 4 detik
  const autoAlerts = document.querySelectorAll('.alert-dismissible');
  autoAlerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getInstance(alert);
      if (bsAlert) {
        bsAlert.close();
      }
    }, 4500);
  });
});

/**
 * Filter tabel realtime di sisi client
 */
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const filter = input.value.toLowerCase();
  const table = document.getElementById(tableId);
  if (!table) return;
  const trs = table.getElementsByTagName('tr');

  for (let i = 1; i < trs.length; i++) {
    const tr = trs[i];
    // Abaikan jika baris header atau baris tanpa td
    if (!tr.getElementsByTagName('td').length) continue;
    
    const text = tr.textContent || tr.innerText;
    if (text.toLowerCase().indexOf(filter) > -1) {
      tr.style.display = '';
    } else {
      tr.style.display = 'none';
    }
  }
}

/**
 * Helper cetak halaman / dokumen
 */
function printDocument() {
  window.print();
}
