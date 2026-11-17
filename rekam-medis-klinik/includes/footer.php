<?php
/**
 * Footer Layout Component
 * Menutup layout wrapper, menyisipkan modal konfirmasi hapus universal, dan memuat script JS
 */
?>
    </main> <!-- End Main Content Body -->

    <!-- Footer Bar -->
    <footer class="app-footer">
      <div>
        <strong>&copy; <?= date('Y'); ?> <?= e($clinic['nama_klinik']); ?></strong>. Seluruh hak cipta dilindungi.
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="d-inline-flex align-items-center gap-1 text-success">
          <span class="badge rounded-pill bg-success p-1" style="width:8px;height:8px;"></span>
          <span style="font-size:0.75rem;">Sistem Online</span>
        </span>
        <span class="text-secondary-subtle">|</span>
        <span class="text-muted" style="font-size:0.75rem;">Versi 2.4.0 (PHP Native & PDO)</span>
      </div>
    </footer>
  </div> <!-- End app-main -->
</div> <!-- End app-wrapper -->

<!-- Universal Confirmation Modal (Hapus Data) -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content modal-content-custom text-center p-3">
      <div class="text-danger mb-2 mt-2">
        <div class="d-inline-flex p-3 rounded-circle bg-danger-subtle text-danger">
          <i data-lucide="alert-triangle" style="width:36px;height:36px;"></i>
        </div>
      </div>
      <h5 class="fw-bold mt-2 mb-1" id="deleteModalTitle">Konfirmasi Hapus</h5>
      <p class="text-muted small mb-4" id="deleteModalMessage">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
      <div class="d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
        <a href="#" id="deleteConfirmBtn" class="btn btn-danger btn-sm px-3 d-flex align-items-center gap-1">
          <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
          <span>Ya, Hapus</span>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Vanilla JavaScript -->
<script src="<?= base_url('assets/js/script.js'); ?>"></script>
<script src="<?= base_url('assets/js/app.js'); ?>"></script>

<script>
  // Pastikan ikon Lucide dirender setelah semua DOM dan modal siap
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
</script>

</body>
</html>
