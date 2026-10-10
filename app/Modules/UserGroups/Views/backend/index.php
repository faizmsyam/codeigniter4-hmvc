<?php
$permissions = array_map('strval', $backendPermissions ?? []);
$can         = static fn (string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
$baseUrl     = site_url('api/v1/user-groups');
?>
<div id="userGroupsList" data-list-url="<?php echo esc($baseUrl, 'attr'); ?>">
  <div class="row">
    <div class="col-lg-12">
      <div class="card custom-card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Manajemen User Group</h5>
            <span class="text-muted fs-13">Kelola kelompok pengguna, status, dan anggota grup.</span>
          </div>
          <div class="w-100 w-sm-auto">
            <div class="d-flex flex-column flex-sm-row gap-2">
              <button type="button" class="btn btn-secondary btn-glare btn-wave label-btn w-100 w-sm-auto" id="btnRefresh">
                <i class="ri-refresh-line label-btn-icon me-2"></i>Refresh
              </button>
              <?php if ($can('user_groups.create')): ?>
                <button type="button" class="btn btn-primary btn-glare btn-wave label-btn w-100 w-sm-auto" id="btnAddGroup">
                  <i class="ri-add-line label-btn-icon me-2"></i>Tambah User Group
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="card-header d-flex flex-column flex-sm-row justify-content-end gap-2">
          <div class="input-group" style="width:150px">
            <span class="input-group-text">Baris</span>
            <select id="groupsPerPage" class="form-select">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div class="form-group">
            <input type="search" class="form-control breadcrumb-input" id="groupSearch" placeholder="Cari nama user group...">
          </div>
          <div class="form-group">
            <select class="form-select" id="groupStatusFilter">
              <option value="">Semua status</option>
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
              <option value="deleted">Terhapus</option>
            </select>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover text-nowrap align-middle">
              <thead>
                <tr>
                  <th style="width:70px">No.</th>
                  <th>Nama User Group</th>
                  <th class="text-center">Anggota</th>
                  <th class="text-center">Status</th>
                  <th>Dibuat</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody id="groupTableBody">
                <tr><td colspan="6" class="text-center py-5 text-muted">Memuat data...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card-footer d-none" id="card-footer-groupPagination">
          <div id="groupPagination" class="small"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah / Ubah User Group (tanpa field status — inline only) -->
<div class="modal fade" id="groupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="groupModalTitle">Tambah User Group</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <form id="groupForm" novalidate>
          <input type="hidden" id="groupHash">
          <div class="mb-3">
            <label for="groupName" class="form-label">Nama User Group <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="groupName" maxlength="100" autocomplete="off" required>
            <div class="invalid-feedback" id="groupNameError">Nama user group wajib diisi.</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnSaveGroup">
          <span class="spinner-border spinner-border-sm d-none me-1" id="saveSpinner"></span>
          Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detail Anggota Grup -->
<div class="modal fade" id="groupMembersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="groupMembersModalTitle">Anggota Grup</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-0">
        <div id="groupMembersLoading" class="text-center py-4 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span>Memuat anggota...
        </div>
        <div class="table-responsive d-none" id="groupMembersTable">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>Email</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody id="groupMembersBody"></tbody>
          </table>
        </div>
        <div id="groupMembersEmpty" class="text-center py-4 text-muted d-none">
          <i class="ri-group-line fs-2 d-block mb-2"></i>Belum ada anggota di grup ini.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const BASE_URL    = <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES); ?>;
  const CAN_UPDATE  = <?php echo $can('user_groups.update')  ? 'true' : 'false'; ?>;
  const CAN_DELETE  = <?php echo $can('user_groups.delete')  ? 'true' : 'false'; ?>;
  const CAN_RESTORE = <?php echo $can('user_groups.restore') ? 'true' : 'false'; ?>;
  const CAN_READ    = <?php echo $can('user_groups.read')    ? 'true' : 'false'; ?>;

  const state = { page: 1, perPage: 10, search: '', status: '' };

  const groupModal        = new bootstrap.Modal(document.getElementById('groupModal'));
  const groupMembersModal = new bootstrap.Modal(document.getElementById('groupMembersModal'));

  function payloadData(response) {
    return response && response.data ? response.data : response;
  }

  function formatDate(value) {
    if (!value) return '-';
    const d = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(d.getTime())
      ? FMS.escape(value)
      : d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

  function inlineStatusSwitch(item) {
    if (!CAN_UPDATE || item.deleted_at) {
      const color = item.deleted_at ? 'danger' : (item.is_active ? 'success' : 'warning');
      const label = item.deleted_at ? 'Terhapus' : (item.is_active ? 'Aktif' : 'Nonaktif');
      return `<span class="badge bg-${color}-transparent">${label}</span>`;
    }
    const checked = item.is_active ? 'checked' : '';
    const label   = item.is_active ? 'Aktif' : 'Nonaktif';
    const color   = item.is_active ? 'success' : 'warning';
    return `<div class="form-check form-switch d-inline-flex align-items-center gap-2 js-status"
         data-hash="${FMS.escape(item.hash)}" data-active="${item.is_active ? '1' : '0'}">
      <input class="form-check-input" type="checkbox" role="switch" ${checked} tabindex="0"
             aria-label="Ubah status ${FMS.escape(item.name)}" style="pointer-events:none">
      <span class="badge bg-${color}-transparent">${label}</span>
    </div>`;
  }

  function actionButtons(item) {
    const h = FMS.escape(item.hash);
    const n = FMS.escape(item.name);

    if (item.deleted_at) {
      return CAN_RESTORE
        ? `<button type="button" class="btn btn-sm btn-outline-success js-restore" data-hash="${h}" title="Pulihkan"><i class="ri-refresh-line"></i> Pulihkan</button>`
        : '-';
    }

    let btns = '';

    if (CAN_READ) {
      btns += `<button type="button" class="btn btn-sm btn-outline-secondary me-1 js-members" data-hash="${h}" title="Lihat Anggota"><i class="ri-group-line"></i></button>`;
    }

    if (CAN_UPDATE) {
      btns += `<button type="button" class="btn btn-sm btn-outline-primary me-1 js-edit" data-hash="${h}" title="Ubah"><i class="ri-pencil-line"></i></button>`;
    }

    if (CAN_DELETE) {
      btns += `<button type="button" class="btn btn-sm btn-outline-danger js-delete" data-hash="${h}" data-name="${n}" title="Hapus"><i class="ri-delete-bin-line"></i></button>`;
    }

    return btns || '-';
  }

  function renderRows(items, pagination) {
    const body = document.getElementById('groupTableBody');
    if (!items.length) {
      body.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="ri-inbox-line fs-2 d-block mb-2"></i>Belum ada data user group.</td></tr>`;
      return;
    }

    body.innerHTML = items.map(function (item, index) {
      const num = ((pagination.current_page - 1) * pagination.per_page) + index + 1;
      return `<tr>
        <td>${num}</td>
        <td>
          <div class="fw-semibold">${FMS.escape(item.name)}</div>
          <small class="text-muted">ID #${Number(item.id)}</small>
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-link p-0 js-members" data-hash="${FMS.escape(item.hash)}" title="Lihat anggota">
            <span class="badge bg-primary-transparent">${Number(item.member_count || 0).toLocaleString('id-ID')}</span>
          </button>
        </td>
        <td class="text-center">${inlineStatusSwitch(item)}</td>
        <td>${formatDate(item.created_at)}</td>
        <td class="text-end text-nowrap">${actionButtons(item)}</td>
      </tr>`;
    }).join('');
  }

  function loadGroups() {
    document.getElementById('card-footer-groupPagination').classList.add('d-none');
    const query = new URLSearchParams({
      page: String(state.page),
      per_page: String(state.perPage),
      search: state.search,
      status: state.status,
    });

    document.getElementById('groupTableBody').innerHTML =
      `<tr><td colspan="6" class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>`;

    FMS.get(`${BASE_URL}?${query.toString()}`).then(function (response) {
      const data       = payloadData(response) || {};
      const items      = data.items || [];
      const pagination = data.pagination || { current_page: 1, per_page: state.perPage, total: 0, last_page: 1 };

      renderRows(items, pagination);

      FMS.pagination({
        target:   '#groupPagination',
        total:    pagination.total,
        perPage:  pagination.per_page,
        page:     pagination.current_page,
        onChange: function (page) { state.page = page; loadGroups(); },
      });
      document.getElementById('card-footer-groupPagination').classList.remove('d-none');
    }).catch(function (error) {
      document.getElementById('groupTableBody').innerHTML =
        `<tr><td colspan="6" class="text-center py-5 text-danger">${FMS.escape(error.message || 'Gagal memuat data.')}</td></tr>`;
      FMS.toast(error.message || 'Gagal memuat user group.', false);
    });
  }

  function openCreate() {
    document.getElementById('groupModalTitle').textContent = 'Tambah User Group';
    document.getElementById('groupHash').value = '';
    document.getElementById('groupName').value = '';
    document.getElementById('groupName').classList.remove('is-invalid');
    groupModal.show();
  }

  function openEdit(hash) {
    FMS.get(`${BASE_URL}/${encodeURIComponent(hash)}`).then(function (response) {
      const data  = payloadData(response) || {};
      const group = data.group || {};
      document.getElementById('groupModalTitle').textContent = 'Ubah User Group';
      document.getElementById('groupHash').value = group.hash || hash;
      document.getElementById('groupName').value = group.name || '';
      document.getElementById('groupName').classList.remove('is-invalid');
      groupModal.show();
    }).catch(function (error) { FMS.toast(error.message, false); });
  }

  function saveGroup() {
    const hash      = document.getElementById('groupHash').value;
    const nameInput = document.getElementById('groupName');
    const name      = nameInput.value.trim();

    if (!name) {
      nameInput.classList.add('is-invalid');
      nameInput.focus();
      return;
    }

    nameInput.classList.remove('is-invalid');

    const button  = document.getElementById('btnSaveGroup');
    const spinner = document.getElementById('saveSpinner');
    button.disabled = true;
    spinner.classList.remove('d-none');

    const payload = { name: name };
    const request = hash
      ? FMS.patch(`${BASE_URL}/${encodeURIComponent(hash)}`, payload)
      : FMS.post(BASE_URL, payload);

    request.then(function (response) {
      groupModal.hide();
      FMS.toast(response.message || 'User group berhasil disimpan.', true);
      loadGroups();
    }).catch(function (error) {
      const message = error.message || 'Gagal menyimpan user group.';
      document.getElementById('groupNameError').textContent = message;
      nameInput.classList.add('is-invalid');
      FMS.toast(message, false);
    }).finally(function () {
      button.disabled = false;
      spinner.classList.add('d-none');
    });
  }

  function openDelete(hash, name) {
    FMS.confirm({
      title: `Hapus User Group`,
      message: `Hapus user group "${name}"?`,
      description: `Data akan dipindahkan ke data terhapus dan dapat dipulihkan kembali.`,
      confirmLabel: `Hapus`,
      loadingLabel: `Menghapus...`,
      variant: `danger`,
      action: function () {
        return FMS.del(`${BASE_URL}/${encodeURIComponent(hash)}`).then(function (response) {
          FMS.toast(response.message || `User group berhasil dihapus.`, true);
          loadGroups();
        });
      }
    });
  }

  function changeStatus(hash, currentActive) {
    const nextStatus = currentActive ? 0 : 1;
    FMS.patch(`${BASE_URL}/${encodeURIComponent(hash)}/status`, { is_active: nextStatus }).then(function (response) {
      FMS.toast(response.message || 'Status berhasil diubah.', true);
      loadGroups();
    }).catch(function (error) { FMS.toast(error.message, false); });
  }

  function restoreGroup(hash) {
    FMS.post(`${BASE_URL}/${encodeURIComponent(hash)}/restore`, {}).then(function (response) {
      FMS.toast(response.message || 'User group berhasil dipulihkan.', true);
      loadGroups();
    }).catch(function (error) { FMS.toast(error.message, false); });
  }

  function openMembers(hash) {
    const titleEl   = document.getElementById('groupMembersModalTitle');
    const loadingEl = document.getElementById('groupMembersLoading');
    const tableEl   = document.getElementById('groupMembersTable');
    const bodyEl    = document.getElementById('groupMembersBody');
    const emptyEl   = document.getElementById('groupMembersEmpty');

    titleEl.textContent = 'Anggota Grup';
    loadingEl.classList.remove('d-none');
    tableEl.classList.add('d-none');
    emptyEl.classList.add('d-none');
    bodyEl.innerHTML = '';
    groupMembersModal.show();

    FMS.get(`${BASE_URL}/${encodeURIComponent(hash)}/members`).then(function (response) {
      const data    = payloadData(response) || {};
      const group   = data.group || {};
      const members = data.members || [];

      titleEl.textContent = `Anggota Grup: ${group.name || ''}`;
      loadingEl.classList.add('d-none');

      if (!members.length) {
        emptyEl.classList.remove('d-none');
        return;
      }

      bodyEl.innerHTML = members.map(function (m) {
        const activeLabel = m.is_active ? 'Aktif' : 'Nonaktif';
        const activeColor = m.is_active ? 'success' : 'warning';
        return `<tr>
          <td>${FMS.escape(m.full_name || '-')}</td>
          <td class="text-muted">${FMS.escape(m.username || '-')}</td>
          <td class="text-muted small">${FMS.escape(m.email || '-')}</td>
          <td class="text-center"><span class="badge bg-${activeColor}-transparent">${activeLabel}</span></td>
        </tr>`;
      }).join('');

      tableEl.classList.remove('d-none');
    }).catch(function (error) {
      loadingEl.classList.add('d-none');
      emptyEl.textContent = error.message || 'Gagal memuat anggota.';
      emptyEl.classList.remove('d-none');
    });
  }

  /* Event wiring */
  const btnAdd = document.getElementById('btnAddGroup');
  if (btnAdd) btnAdd.addEventListener('click', openCreate);

  document.getElementById('btnSaveGroup').addEventListener('click', saveGroup);
  document.getElementById('btnRefresh').addEventListener('click', loadGroups);

  document.getElementById('groupsPerPage').addEventListener('change', function () {
    state.perPage = Number(this.value);
    state.page = 1;
    loadGroups();
  });

  document.getElementById('groupStatusFilter').addEventListener('change', function () {
    state.status = this.value;
    state.page = 1;
    loadGroups();
  });

  document.getElementById('groupSearch').addEventListener('input', FMS.debounce(function (event) {
    state.search = event.target.value.trim();
    state.page = 1;
    loadGroups();
  }, 350));

  document.getElementById('groupTableBody').addEventListener('click', function (event) {
    const editBtn    = event.target.closest('.js-edit');
    const deleteBtn  = event.target.closest('.js-delete');
    const statusEl   = event.target.closest('.js-status');
    const restoreBtn = event.target.closest('.js-restore');
    const membersBtn = event.target.closest('.js-members');

    if (editBtn)    openEdit(editBtn.dataset.hash);
    if (deleteBtn)  openDelete(deleteBtn.dataset.hash, deleteBtn.dataset.name);
    if (statusEl)   changeStatus(statusEl.dataset.hash, statusEl.dataset.active === '1');
    if (restoreBtn) restoreGroup(restoreBtn.dataset.hash);
    if (membersBtn) openMembers(membersBtn.dataset.hash);
  });

  loadGroups();
});
</script>
