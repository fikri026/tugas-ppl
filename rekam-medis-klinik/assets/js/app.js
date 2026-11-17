/**
 * Sistem Rekam Medis Klinik - app.js
 * Logika interaksi form dinamis, modal handler, resep obat
 */

// Helper hitung usia dari tanggal lahir
function calculateAge(birthDateString) {
  if (!birthDateString) return '-';
  const birthDate = new Date(birthDateString);
  const diff = Date.now() - birthDate.getTime();
  const ageDate = new Date(diff);
  return Math.abs(ageDate.getUTCFullYear() - 1970) + ' tahun';
}

// Handler modal edit pasien
function openEditPasienModal(data) {
  document.getElementById('edit_id').value = data.id || '';
  document.getElementById('edit_nomor_rm').value = data.nomor_rm || '';
  document.getElementById('edit_nik').value = data.nik || '';
  document.getElementById('edit_nama_pasien').value = data.nama_pasien || '';
  document.getElementById('edit_jenis_kelamin').value = data.jenis_kelamin || 'Laki-laki';
  document.getElementById('edit_tempat_lahir').value = data.tempat_lahir || '';
  document.getElementById('edit_tanggal_lahir').value = data.tanggal_lahir || '';
  document.getElementById('edit_no_telepon').value = data.no_telepon || '';
  document.getElementById('edit_gol_darah').value = data.gol_darah || '-';
  document.getElementById('edit_alergi').value = data.alergi || 'Tidak Ada';
  document.getElementById('edit_alamat').value = data.alamat || '';

  const modal = new bootstrap.Modal(document.getElementById('modalEditPasien'));
  modal.show();
}

// Handler modal detail pasien
function openDetailPasienModal(data) {
  document.getElementById('detail_nama').textContent = data.nama_pasien || '-';
  document.getElementById('detail_rm').textContent = data.nomor_rm || '-';
  document.getElementById('detail_nik').textContent = data.nik || '-';
  document.getElementById('detail_gender').textContent = data.jenis_kelamin || '-';
  document.getElementById('detail_ttl').textContent = (data.tempat_lahir || '-') + ', ' + (data.tanggal_lahir || '-');
  document.getElementById('detail_umur').textContent = calculateAge(data.tanggal_lahir);
  document.getElementById('detail_telepon').textContent = data.no_telepon || '-';
  document.getElementById('detail_goldar').textContent = data.gol_darah || '-';
  document.getElementById('detail_alergi').textContent = data.alergi || 'Tidak Ada';
  document.getElementById('detail_alamat').textContent = data.alamat || '-';

  const modal = new bootstrap.Modal(document.getElementById('modalDetailPasien'));
  modal.show();
}

// Handler modal edit dokter
function openEditDokterModal(data) {
  document.getElementById('edit_dokter_id').value = data.id || '';
  document.getElementById('edit_dokter_nip').value = data.nip || '';
  document.getElementById('edit_dokter_nama').value = data.nama_dokter || '';
  document.getElementById('edit_dokter_spesialisasi').value = data.spesialisasi || '';
  document.getElementById('edit_dokter_telepon').value = data.no_telepon || '';
  document.getElementById('edit_dokter_email').value = data.email || '';
  document.getElementById('edit_dokter_jadwal').value = data.jadwal_praktek || '';
  document.getElementById('edit_dokter_status').value = data.status || 'Aktif';

  const modal = new bootstrap.Modal(document.getElementById('modalEditDokter'));
  modal.show();
}

// Handler modal edit obat
function openEditObatModal(data) {
  document.getElementById('edit_obat_id').value = data.id || '';
  document.getElementById('edit_obat_kode').value = data.kode_obat || '';
  document.getElementById('edit_obat_nama').value = data.nama_obat || '';
  document.getElementById('edit_obat_kategori').value = data.kategori || '';
  document.getElementById('edit_obat_jenis').value = data.jenis_obat || 'Tablet';
  document.getElementById('edit_obat_stok').value = data.stok || '0';
  document.getElementById('edit_obat_satuan').value = data.satuan || 'Strip';
  document.getElementById('edit_obat_harga').value = data.harga || '0';
  document.getElementById('edit_obat_deskripsi').value = data.deskripsi || '';

  const modal = new bootstrap.Modal(document.getElementById('modalEditObat'));
  modal.show();
}

// Handler konfirmasi hapus umum
function confirmDelete(url, title, message) {
  document.getElementById('deleteConfirmBtn').setAttribute('href', url);
  if (title) document.getElementById('deleteModalTitle').textContent = title;
  if (message) document.getElementById('deleteModalMessage').textContent = message;

  const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
  modal.show();
}

// Handler dinamis resep obat: tambah baris obat
function addResepRow() {
  const container = document.getElementById('resepItemsContainer');
  if (!container) return;

  const rowCount = container.children.length;
  const newRow = document.createElement('div');
  newRow.className = 'row g-2 align-items-center mb-3 resep-item-row p-2 bg-light rounded';
  newRow.id = `resep_row_${rowCount}`;

  // Clone select obat dari template
  const templateSelect = document.getElementById('templateObatSelect');
  const selectHtml = templateSelect ? templateSelect.innerHTML : '';

  newRow.innerHTML = `
    <div class="col-md-4">
      <label class="form-label small fw-semibold text-muted mb-1">Pilih Obat</label>
      <select name="obat_id[]" class="form-select form-select-sm select-obat-item" required>
        ${selectHtml}
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold text-muted mb-1">Dosis</label>
      <input type="text" name="dosis[]" class="form-control form-control-sm" placeholder="Contoh: 500 mg" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold text-muted mb-1">Aturan Pakai</label>
      <input type="text" name="aturan_pakai[]" class="form-control form-control-sm" placeholder="3x sehari 1 tablet" required>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold text-muted mb-1">Jumlah</label>
      <input type="number" name="jumlah[]" class="form-control form-control-sm" value="1" min="1" required>
    </div>
    <div class="col-md-1 text-center">
      <label class="form-label small d-block mb-1">&nbsp;</label>
      <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeResepRow('resep_row_${rowCount}')" title="Hapus Baris">
        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
      </button>
    </div>
  `;

  container.appendChild(newRow);
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
}

function removeResepRow(rowId) {
  const row = document.getElementById(rowId);
  if (row) {
    row.remove();
  }
}
