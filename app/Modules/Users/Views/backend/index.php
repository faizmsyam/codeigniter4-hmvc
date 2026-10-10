<?php
$permissions = array_map('strval', $backendPermissions ?? []);
$can = static fn (string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
$baseUrl = site_url('api/v1/users');
?>
<div class="card custom-card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h4 class="mb-0">Manajemen Pengguna</h4>
      <small class="text-muted">Kelola data pengguna, status akun, dan kata sandi</small>
    </div>

    <div class="w-100 w-sm-auto">
      <div class="d-flex flex-column flex-sm-row gap-2">
        <button type="button" class="btn btn-secondary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnRefresh">
          <i class="ri-refresh-line label-btn-icon me-2"></i> Refresh
        </button>

        <?php if ($can('users.create')): ?>
        <button class="btn btn-primary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnAddUser">
          <i class="ri-add-line label-btn-icon me-2"></i> Tambah Pengguna
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card-body pb-0">
    <div class="row g-3" id="usersSummaryRow"></div>
  </div>

  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="w-100 w-sm-auto">
      <div class="d-flex flex-column flex-sm-row gap-2">
        <div class="w-100">
          <input type="search" class="form-control" id="userSearch" placeholder="Cari username, email, nama...">
        </div>
        <div class="w-100">
          <select class="form-control form-select" id="userStatusFilter">
            <option value="">Semua status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
            <option value="banned">Diblokir</option>
            <option value="deleted">Dihapus</option>
          </select>
        </div>
      </div>
    </div>
    <div class="w-100 w-sm-auto">
      <div class="d-flex align-items-center gap-2">
        <label for="usersPerPage" class="text-muted small text-nowrap mb-0">Baris:</label>
        <select id="usersPerPage" class="form-select form-select-sm" style="width:75px">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
  </div>

  <div class="card-body p-0">
    <div id="userTableError" class="alert alert-danger d-none m-3"></div>

    <div class="table-responsive">
      <table class="table table-hover text-nowrap align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Pengguna</th>
            <th>Nama Lengkap</th>
            <th>Email</th>
            <th style="width:120px" class="text-center">Status</th>
            <th style="width:100px" class="text-center">Verifikasi</th>
            <th style="width:140px">Dibuat</th>
            <th style="width:200px" class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody id="userTableBody">
          <tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>
        </tbody>
      </table>
    </div>

    <div class="px-3 py-2 border-top d-none" id="userPagination"></div>
  </div>
</div>

<!-- Modal Tambah / Ubah Pengguna -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" aria-labelledby="userModalLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Tambah Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="userForm" autocomplete="off">
        <input type="hidden" id="userUuid">
        <div class="modal-body">
          <div id="userFormError" class="alert alert-danger py-2 mb-3 d-none" data-fms-form-error></div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="userName">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userName" name="username" maxlength="50" placeholder="Contoh: faizmsyam" required>
            <div class="invalid-feedback" id="userNameErr"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="userFullName">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="userFullName" name="full_name" maxlength="100" placeholder="Nama lengkap" required>
            <div class="invalid-feedback" id="userFullNameErr"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="userEmail">Email</label>
            <input type="email" class="form-control" id="userEmail" name="email" maxlength="100" placeholder="nama@domain.com">
            <div class="invalid-feedback" id="userEmailErr"></div>
          </div>

          <div class="mb-3" id="userPasswordGroup">
            <label class="form-label fw-semibold" for="userPassword">Kata Sandi <span class="text-danger">*</span></label>
            <input type="hidden" class="form-control" id="userPassword" name="password" placeholder="Minimal 8 karakter" value="App12345">
            <p class="mb-0">Default : <strong>App12345</strong></p>
            <div class="invalid-feedback" id="userPasswordErr"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="userStatusSelect">Status</label>
            <select class="form-select" id="userStatusSelect" name="status">
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
              <option value="banned">Diblokir</option>
            </select>
            <div class="invalid-feedback" id="userStatusSelectErr"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="userSaveBtn">
            <span id="userSaveBtnText">Simpan</span>
            <span id="userSaveBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Kelompok Pengguna (Assign Groups) -->
<div class="modal fade" id="userGroupsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-group-line me-2"></i>Kelompok Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="userGroupsForm" autocomplete="off">
        <input type="hidden" id="userGroupsUuid">
        <div class="modal-body">
          <p class="small text-muted mb-3">Pilih kelompok hak akses untuk pengguna: <strong id="userGroupsUsername"></strong></p>
          <div id="userGroupsLoading" class="text-center py-3 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>Memuat daftar kelompok...
          </div>
          <div id="userGroupsList" class="d-flex flex-column gap-2 d-none"></div>
          <div id="userGroupsError" class="alert alert-danger py-2 px-3 small d-none mt-2"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btnUserGroupsSubmit">
            <span id="userGroupsBtnText">Simpan Kelompok</span>
            <span id="userGroupsBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="userPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reset Kata Sandi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="userPasswordForm" autocomplete="off">
        <input type="hidden" id="resetPasswordUserUuid">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold" for="newPassword">Kata Sandi Baru</label>
            <p class="small text-muted mb-0">Reset kata sandi untuk <strong id="resetPasswordUsername"></strong></p>
            <p class="small text-muted mb-0">Sandi baru akan di reset ke default : <strong>App12345</strong></p>
            <input type="hidden" class="form-control" id="newPassword" minlength="5" maxlength="5" value="App12345">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning btn-sm" id="btnResetPasswordSubmit">Reset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Sesi Masuk -->
<div class="modal fade" id="userSessionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-device-line me-2"></i>Sesi Masuk <strong id="userSessionsUsername"></strong></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="userSessionsUuid">
        <div id="userSessionsList"></div>
        <p id="userSessionsEmpty" class="text-muted small text-center py-2 d-none">Tidak ada sesi tercatat.</p>
        <div id="userSessionsError" class="alert alert-danger py-2 px-3 small d-none mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
        <?php if ($can('users.revoke_sessions')): ?>
        <button type="button" class="btn btn-danger btn-sm" id="btnRevokeAllSessions">Keluarkan Semua</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  const CAN_CREATE        = <?php echo $can('users.create')        ? 'true' : 'false'; ?>;
  const CAN_UPDATE        = <?php echo $can('users.update')        ? 'true' : 'false'; ?>;
  const CAN_DELETE        = <?php echo $can('users.delete')        ? 'true' : 'false'; ?>;
  const CAN_RESTORE       = <?php echo $can('users.restore')       ? 'true' : 'false'; ?>;
  const CAN_RESET_PWD     = <?php echo $can('users.reset_password') ? 'true' : 'false'; ?>;
  const CAN_CHANGE_STATUS = <?php echo $can('users.change_status') ? 'true' : 'false'; ?>;
  const CAN_ASSIGN_GROUPS = <?php echo $can('users.assign_groups') ? 'true' : 'false'; ?>;
  const CAN_UNLOCK        = <?php echo $can('users.unlock')        ? 'true' : 'false'; ?>;
  const CAN_VERIFY_EMAIL  = <?php echo $can('users.verify_email')  ? 'true' : 'false'; ?>;
  const CAN_UNVERIFY      = <?php echo $can('users.unverify_email') ? 'true' : 'false'; ?>;
  const CAN_READ_SESSIONS = <?php echo $can('users.read_sessions') ? 'true' : 'false'; ?>;
  const CAN_REVOKE_SESS   = <?php echo $can('users.revoke_sessions') ? 'true' : 'false'; ?>;

  const BASE_URL = <?php echo json_encode($baseUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

  let currentPage = 1;
  let currentPerPage = 10;
  let currentSearch = ``;
  let currentStatus = ``;
  let searchTimer = null;

  const tableBody = document.getElementById(`userTableBody`);
  const tableError = document.getElementById(`userTableError`);
  const summaryRow = document.getElementById(`usersSummaryRow`);
  const searchInput = document.getElementById(`userSearch`);
  const statusFilter = document.getElementById(`userStatusFilter`);
  const perPageSelect = document.getElementById(`usersPerPage`);
  const paginEl = document.getElementById(`userPagination`);

  const userModal         = new bootstrap.Modal(document.getElementById(`userModal`));
  const userPasswordModal = new bootstrap.Modal(document.getElementById(`userPasswordModal`));
  const userGroupsModal   = new bootstrap.Modal(document.getElementById(`userGroupsModal`));
  const userSessionsModal = new bootstrap.Modal(document.getElementById(`userSessionsModal`));

  const userForm = document.getElementById(`userForm`);
  const userPasswordForm = document.getElementById(`userPasswordForm`);

  function esc(str) {
    return String(str ?? ``).replace(/&/g, `&amp;`).replace(/</g, `&lt;`).replace(/>/g, `&gt;`).replace(/"/g, `&quot;`);
  }

  function formatDate(str) {
    if (!str) return `-`;
    const d = new Date(str);
    return `${String(d.getDate()).padStart(2, `0`)} ${d.toLocaleString(`id`, { month: `short` })} ${d.getFullYear()}`;
  }

  function statusBadge(status, uuid, username) {
    const map = {
      active:   [`success`,   `Aktif`],
      inactive: [`secondary`, `Nonaktif`],
      banned:   [`danger`,    `Diblokir`],
      deleted:  [`dark`,      `Dihapus`],
    };
    const [color, label] = map[status] ?? [`secondary`, esc(status)];

    if (!CAN_CHANGE_STATUS || status === `deleted`) {
      return `<span class="badge bg-${color}-transparent">${label}</span>`;
    }

    const isActive = status === `active`;
    return `
      <div class="form-check form-switch d-inline-block">
        <input class="form-check-input js-toggle-user-status" type="checkbox" role="switch"
               data-uuid="${esc(uuid)}" data-username="${esc(username)}" data-status="${esc(status)}"
               ${isActive ? `checked` : ``} aria-label="Ubah status ${esc(username)}">
        <label class="form-check-label"><span class="badge bg-${color}-transparent">${label}</span></label>
      </div>
    `;
  }

  function verifiedBadge(verified) {
    return verified
      ? `<span class="badge bg-success-transparent"><i class="ri-checkbox-circle-line me-1"></i>Ya</span>`
      : `<span class="badge bg-secondary-transparent">Belum</span>`;
  }

  function renderSummary(summary) {
    if (!summary) return;
    summaryRow.innerHTML =
      summaryCard(`Total Pengguna`, summary.total ?? 0, `primary`) +
      summaryCard(`Aktif`, summary.active ?? 0, `success`) +
      summaryCard(`Nonaktif`, summary.inactive ?? 0, `secondary`) +
      summaryCard(`Diblokir`, summary.banned ?? 0, `danger`);
  }

  function summaryCard(label, value, variant) {
    return `
      <div class="col-6 col-md-3">
        <div class="card custom-card bg-${variant}-transparent border-0 shadow-none mb-0">
          <div class="card-body py-3">
            <div class="text-center">
              <span class="d-block mb-1 fs-12">${label}</span>
              <h5 class="fw-semibold mb-0">${value}</h5>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function rowMarkup(u) {
    const actions = [];

    if (CAN_UPDATE && u.status !== `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-success btn-glare btn-sm ms-1 js-user-edit" data-uuid="${esc(u.uuid)}" title="Ubah"><i class="ri-pencil-line"></i></button>`);
    }

    if (CAN_RESET_PWD && u.status !== `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-warning btn-glare btn-sm ms-1 js-user-pwd" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Reset Password"><i class="ri-key-line"></i></button>`);
    }

    if (CAN_DELETE && u.status !== `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-danger btn-glare btn-sm ms-1 js-user-delete" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Hapus"><i class="ri-delete-bin-line"></i></button>`);
    }

    if (CAN_RESTORE && u.status === `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-info btn-glare btn-sm ms-1 js-user-restore" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Pulihkan"><i class="ri-restart-line"></i> Pulihkan</button>`);
    }

    if (CAN_ASSIGN_GROUPS && u.status !== `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-secondary btn-glare btn-sm ms-1 js-user-groups" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Atur Kelompok"><i class="ri-group-line"></i></button>`);
    }

    const isLocked = u.status === `locked` || (u.locked_until && new Date(u.locked_until.replace(` `, `T`)).getTime() > Date.now());
    if (CAN_UNLOCK && u.status !== `deleted`) {
      actions.push(isLocked
        ? `<button type="button" class="btn btn-outline-primary btn-glare btn-sm ms-1 js-user-unlock" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Buka Kunci Akun"><i class="ri-lock-unlock-line"></i></button>`
        : `<button type="button" class="btn btn-outline-warning btn-glare btn-sm ms-1 js-user-lock" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Kunci Akun"><i class="ri-lock-line"></i></button>`);
    }

    if (CAN_VERIFY_EMAIL && u.status !== `deleted` && !u.is_email_verified) {
      actions.push(`<button type="button" class="btn btn-outline-success btn-glare btn-sm ms-1 js-user-verify" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Verifikasi Email"><i class="ri-mail-check-line"></i></button>`);
    }

    if (CAN_UNVERIFY && u.status !== `deleted` && u.is_email_verified) {
      actions.push(`<button type="button" class="btn btn-outline-secondary btn-glare btn-sm ms-1 js-user-unverify" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Batalkan Verifikasi Email"><i class="ri-mail-close-line"></i></button>`);
    }

    if (CAN_READ_SESSIONS && u.status !== `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-info btn-glare btn-sm ms-1 js-user-sessions" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Lihat Sesi Masuk"><i class="ri-device-line"></i></button>`);
    }

    if (CAN_REVOKE_SESS && u.status !== `deleted`) {
      actions.push(`<button type="button" class="btn btn-outline-danger btn-glare btn-sm ms-1 js-user-revoke-sessions" data-uuid="${esc(u.uuid)}" data-username="${esc(u.username)}" title="Keluarkan dari Perangkat"><i class="ri-logout-box-line"></i></button>`);
    }

    const avatarInitial = (u.full_name || u.username || `U`).charAt(0).toUpperCase();

    return `
      <tr data-uuid="${esc(u.uuid)}">
        <td>
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm avatar-rounded bg-primary-transparent me-2 text-primary fw-bold">
              ${avatarInitial}
            </div>
            <div>
              <div class="fw-medium">${esc(u.username)}</div>
              <small class="text-muted">ID #${esc(u.id)}</small>
            </div>
          </div>
        </td>
        <td>${esc(u.full_name || `-`)}</td>
        <td class="text-muted small">${esc(u.email)}</td>
        <td class="text-center">${statusBadge(u.status, u.uuid, u.username)}</td>
        <td class="text-center">${verifiedBadge(u.is_email_verified)}</td>
        <td class="text-muted small">${formatDate(u.created_at)}</td>
        <td class="text-end text-nowrap">${actions.join(` `) || `-`}</td>
      </tr>
    `;
  }

  function loadUsers(page) {
    currentPage = page || currentPage;
    paginEl.classList.add(`d-none`);
    tableBody.innerHTML = ``;
    tableError.classList.add(`d-none`);

    const url = `${BASE_URL}?page=${currentPage}&per_page=${currentPerPage}&search=${encodeURIComponent(currentSearch)}&status=${encodeURIComponent(currentStatus)}`;

    FMS.get(url).then(function(res) {
      const rows = res.items || res.data || [];
      const summary = res.summary || null;
      const pg = res.pagination || {};
      const total = pg.total || 0;
      const from = total === 0 ? 0 : (currentPage - 1) * currentPerPage + 1;
      const to = Math.min(currentPage * currentPerPage, total);

      renderSummary(summary);

      if (rows.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><i class="ri-user-unfollow-line me-2"></i>Tidak ada pengguna ditemukan.</td></tr>`;
      } else {
        tableBody.innerHTML = rows.map(rowMarkup).join(``);
      }

      FMS.pagination({
        target: paginEl,
        total: total,
        perPage: currentPerPage,
        page: currentPage,
        onChange: function(nextPage) { loadUsers(nextPage); }
      });
      paginEl.classList.remove(`d-none`);
    }).catch(function(err) {
      tableError.textContent = err.message || `Gagal memuat data pengguna.`;
      tableError.classList.remove(`d-none`);
      tableBody.innerHTML = ``;
    });
  }

  function openSessionsModal(uuid, username) {
    const list = document.getElementById(`userSessionsList`);
    document.getElementById(`userSessionsUsername`).textContent = username;
    document.getElementById(`userSessionsUuid`).value = uuid;
    list.innerHTML = `<div class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat sesi...</div>`;
    document.getElementById(`userSessionsEmpty`).classList.add(`d-none`);
    document.getElementById(`userSessionsError`).classList.add(`d-none`);
    userSessionsModal.show();

    FMS.get(`${BASE_URL}/${uuid}/sessions`).then(function(res) {
      const sessions = res.sessions || [];
      const list = document.getElementById(`userSessionsList`);
      if (sessions.length === 0) {
        document.getElementById(`userSessionsEmpty`).classList.remove(`d-none`);
        return;
      }
      list.innerHTML = sessions.map(function(s) {
        const active = !s.revoked_at;
        return `<div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-2">
          <div>
            <div class="fw-medium">${esc(s.device_label || `Perangkat`)}</div>
            <small class="text-muted">Aktif terakhir: ${esc(s.last_activity_at || s.created_at || `-`)}</small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-${active ? `success` : `secondary`}-transparent">${active ? `Aktif` : `Dicabut`}</span>
            ${active && CAN_REVOKE_SESS ? `<button type="button" class="btn btn-outline-danger btn-sm js-revoke-session" data-session-uuid="${esc(s.session_uuid || ``)}" title="Keluarkan sesi ini"><i class="ri-logout-box-line"></i></button>` : ``}
          </div>
        </div>`;
      }).join(``);
    }).catch(function(err) {
      const errBox = document.getElementById(`userSessionsError`);
      errBox.textContent = err.message || `Gagal memuat sesi.`;
      errBox.classList.remove(`d-none`);
    });
  }

  /* Search & Filter */
  searchInput.addEventListener(`input`, function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
      currentSearch = searchInput.value.trim();
      loadUsers(1);
    }, 300);
  });

  statusFilter.addEventListener(`change`, function() {
    currentStatus = statusFilter.value;
    loadUsers(1);
  });

  perPageSelect.addEventListener(`change`, function() {
    currentPerPage = parseInt(perPageSelect.value, 10);
    loadUsers(1);
  });

  document.getElementById(`btnRefresh`).addEventListener(`click`, function() {
    loadUsers(currentPage);
  });

  /* Open Add Modal */
  const btnAddUser = document.getElementById(`btnAddUser`);
  if (btnAddUser) {
    btnAddUser.addEventListener(`click`, function() {
      userForm.reset();
      document.getElementById(`userUuid`).value = ``;
      document.getElementById(`userModalLabel`).textContent = `Tambah Pengguna`;
      document.getElementById(`userPasswordGroup`).classList.remove(`d-none`);
      document.getElementById(`userPassword`).required = true;
      FMS.form.clearErrors(userForm);
      userModal.show();
    });
  }

  /* Save User (Create / Update) */
  userForm.addEventListener(`submit`, function(e) {
    e.preventDefault();
    const uuid = document.getElementById(`userUuid`).value;
    const payload = FMS.form.values(userForm);

    /* Password hanya dikirim ketika membuat user baru. */
    if (uuid) delete payload.password;

    const btnSave = document.getElementById(`userSaveBtn`);
    const btnText = document.getElementById(`userSaveBtnText`);
    const btnSpinner = document.getElementById(`userSaveBtnSpinner`);
    btnSave.disabled = true;
    btnText.textContent = `Menyimpan...`;
    btnSpinner.classList.remove(`d-none`);

    const req = uuid
      ? FMS.patch(`${BASE_URL}/${uuid}`, payload)
      : FMS.post(BASE_URL, payload);

    req.then(function() {
      userModal.hide();
      FMS.toast(`Data pengguna berhasil disimpan.`, true);
      loadUsers(currentPage);
    }).catch(function(err) {
      FMS.form.clearErrors(userForm);
      if (err && err.errors && Object.keys(err.errors).length) {
        FMS.form.errors(userForm, err.errors);
      } else {
        FMS.toast(err.message || `Gagal menyimpan pengguna.`, false);
      }
    }).finally(function() {
      btnSave.disabled = false;
      btnText.textContent = `Simpan`;
      btnSpinner.classList.add(`d-none`);
    });
  });

  /* Table row clicks */
  tableBody.addEventListener(`click`, function(e) {
    /* Edit User */
    const btnEdit = e.target.closest(`.js-user-edit`);
    if (btnEdit) {
      const uuid = btnEdit.dataset.uuid;
      FMS.get(`${BASE_URL}/${uuid}`).then(function(res) {
        const u = res.user || {};
        userForm.reset();
        document.getElementById(`userUuid`).value = u.uuid || ``;
        document.getElementById(`userName`).value = u.username || ``;
        document.getElementById(`userEmail`).value = u.email || ``;
        document.getElementById(`userFullName`).value = u.full_name || ``;
        document.getElementById(`userStatusSelect`).value = u.status || `active`;
        document.getElementById(`userPasswordGroup`).classList.add(`d-none`);
        document.getElementById(`userPassword`).required = false;
        document.getElementById(`userModalLabel`).textContent = `Ubah Pengguna`;
        FMS.form.clearErrors(userForm);
        userModal.show();
      }).catch(function(err) {
        FMS.toast(err.message || `Gagal memuat detail pengguna.`, false);
      });
      return;
    }

    /* Reset Password */
    const btnPwd = e.target.closest(`.js-user-pwd`);
    if (btnPwd) {
      userPasswordForm.reset();
      document.getElementById(`resetPasswordUserUuid`).value = btnPwd.dataset.uuid;
      document.getElementById(`resetPasswordUsername`).textContent = btnPwd.dataset.username;
      userPasswordModal.show();
      return;
    }

    /* Toggle Status */
    const toggleStatus = e.target.closest(`.js-toggle-user-status`);
    if (toggleStatus) {
      const uuid = toggleStatus.dataset.uuid;
      const currentStatusVal = toggleStatus.dataset.status;
      const newStatusVal = currentStatusVal === `active` ? `inactive` : `active`;

      toggleStatus.disabled = true;
      FMS.patch(`${BASE_URL}/${uuid}/status`, { status: newStatusVal }).then(function() {
        FMS.toast(`Status berhasil diubah.`, true);
        loadUsers(currentPage);
      }).catch(function(err) {
        toggleStatus.checked = !toggleStatus.checked;
        FMS.toast(err.message || `Gagal mengubah status.`, false);
      }).finally(function() {
        toggleStatus.disabled = false;
      });
      return;
    }

    /* Delete User */
    const btnDel = e.target.closest(`.js-user-delete`);
    if (btnDel) {
      const uuid = btnDel.dataset.uuid;
      const uname = btnDel.dataset.username;
      FMS.confirm({
        title: `Hapus Pengguna`,
        message: `Hapus akun "${uname}"?`,
        description: `Data pengguna akan dinonaktifkan/dihapus secara aman.`,
        confirmLabel: `Hapus`,
        loadingLabel: `Menghapus...`,
        variant: `danger`,
        action: function () {
          return FMS.del(`${BASE_URL}/${uuid}`).then(function() {
            FMS.toast(`Pengguna berhasil dihapus.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Restore User */
    const btnRestore = e.target.closest(`.js-user-restore`);
    if (btnRestore) {
      const uuid = btnRestore.dataset.uuid;
      const uname = btnRestore.dataset.username;
      FMS.confirm({
        title: `Pulihkan Pengguna`,
        message: `Pulihkan akun "${uname}"?`,
        confirmLabel: `Pulihkan`,
        loadingLabel: `Memulihkan...`,
        variant: `success`,
        action: function () {
          return FMS.post(`${BASE_URL}/${uuid}/restore`, {}).then(function() {
            FMS.toast(`Akun berhasil dipulihkan.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Lock Akun */
    const btnLock = e.target.closest(`.js-user-lock`);
    if (btnLock) {
      const uname = btnLock.dataset.username;
      FMS.confirm({
        title: `Kunci Akun`,
        message: `Kunci akun "${uname}"?`,
        confirmLabel: `Kunci`,
        loadingLabel: `Mengunci...`,
        variant: `warning`,
        action: function () {
          return FMS.patch(`${BASE_URL}/${btnLock.dataset.uuid}/status`, { status: `locked` }).then(function() {
            FMS.toast(`Akun ${uname} berhasil dikunci.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Unlock Akun */
    const btnUnlock = e.target.closest(`.js-user-unlock`);
    if (btnUnlock) {
      const uname = btnUnlock.dataset.username;
      FMS.confirm({
        title: `Buka Kunci Akun`,
        message: `Buka kunci akun "${uname}"?`,
        confirmLabel: `Buka Kunci`,
        loadingLabel: `Membuka...`,
        variant: `primary`,
        action: function () {
          return FMS.post(`${BASE_URL}/${btnUnlock.dataset.uuid}/unlock`, {}).then(function() {
            FMS.toast(`Akun ${uname} berhasil dibuka.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Verifikasi Email */
    const btnVerify = e.target.closest(`.js-user-verify`);
    if (btnVerify) {
      const uname = btnVerify.dataset.username;
      FMS.confirm({
        title: `Verifikasi Email`,
        message: `Verifikasi email untuk "${uname}"?`,
        confirmLabel: `Verifikasi`,
        loadingLabel: `Memverifikasi...`,
        variant: `success`,
        action: function () {
          return FMS.post(`${BASE_URL}/${btnVerify.dataset.uuid}/verify-email`, {}).then(function() {
            FMS.toast(`Email ${uname} diverifikasi.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Batal Verifikasi Email */
    const btnUnverify = e.target.closest(`.js-user-unverify`);
    if (btnUnverify) {
      const uname = btnUnverify.dataset.username;
      FMS.confirm({
        title: `Batalkan Verifikasi`,
        message: `Batalkan verifikasi email "${uname}"?`,
        confirmLabel: `Batalkan`,
        loadingLabel: `Membatalkan...`,
        variant: `warning`,
        action: function () {
          return FMS.post(`${BASE_URL}/${btnUnverify.dataset.uuid}/unverify-email`, {}).then(function() {
            FMS.toast(`Verifikasi email ${uname} dibatalkan.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Keluarkan dari Perangkat */
    const btnRevoke = e.target.closest(`.js-user-revoke-sessions`);
    if (btnRevoke) {
      const uname = btnRevoke.dataset.username;
      FMS.confirm({
        title: `Keluarkan dari Perangkat`,
        message: `Keluarkan "${uname}" dari semua perangkat?`,
        confirmLabel: `Keluarkan`,
        loadingLabel: `Mengeluarkan...`,
        variant: `danger`,
        action: function () {
          return FMS.del(`${BASE_URL}/${btnRevoke.dataset.uuid}/sessions`).then(function() {
            FMS.toast(`Sesi ${uname} dicabut.`, true);
            loadUsers(currentPage);
          });
        }
      });
      return;
    }

    /* Lihat Sesi Masuk */
    const btnSessions = e.target.closest(`.js-user-sessions`);
    if (btnSessions) {
      openSessionsModal(btnSessions.dataset.uuid, btnSessions.dataset.username);
      return;
    }

    /* Assign Groups */
    const btnGroups = e.target.closest(`.js-user-groups`);
    if (btnGroups) {
      const uuid  = btnGroups.dataset.uuid;
      const uname = btnGroups.dataset.username;
      const loading = document.getElementById(`userGroupsLoading`);
      const list    = document.getElementById(`userGroupsList`);
      const errBox  = document.getElementById(`userGroupsError`);

      document.getElementById(`userGroupsUuid`).value = uuid;
      document.getElementById(`userGroupsUsername`).textContent = uname;
      loading.classList.remove(`d-none`);
      list.classList.add(`d-none`);
      errBox.classList.add(`d-none`);
      list.innerHTML = ``;
      userGroupsModal.show();

      FMS.get(`${BASE_URL}/${uuid}/groups`).then(function(res) {
        const groups   = res.groups || [];
        const assigned = new Set((res.assigned_group_ids || []).map(Number));
        loading.classList.add(`d-none`);
        list.classList.remove(`d-none`);

        if (groups.length === 0) {
          list.innerHTML = `<p class="text-muted small text-center py-2">Belum ada kelompok tersedia.</p>`;
          return;
        }

        list.innerHTML = groups.map(function(g) {
          const checked = assigned.has(Number(g.id)) ? `checked` : ``;
          return `
            <div class="form-check border rounded px-3 py-2">
              <input class="form-check-input" type="checkbox" value="${g.id}" id="grp_${g.id}" ${checked}>
              <label class="form-check-label w-100" for="grp_${g.id}">
                <span class="fw-medium">${esc(g.name)}</span>
                ${g.description ? `<small class="d-block text-muted">${esc(g.description)}</small>` : ``}
              </label>
            </div>`;
        }).join(``);
      }).catch(function(err) {
        loading.classList.add(`d-none`);
        errBox.textContent = err.message || `Gagal memuat kelompok.`;
        errBox.classList.remove(`d-none`);
      });
    }
  });

  /* Reset Password Submit */
  userPasswordForm.addEventListener(`submit`, function(e) {
    e.preventDefault();
    const uuid = document.getElementById(`resetPasswordUserUuid`).value;
    const pwd = document.getElementById(`newPassword`).value;
    const btn = document.getElementById(`btnResetPasswordSubmit`);

    btn.disabled = true;
    FMS.patch(`${BASE_URL}/${uuid}/reset-password`, payload).then(function() {
      resetPasswordModal.hide();
      FMS.toast(`Password ${u.username} berhasil direset.`, true);
    }).catch(function(err) {
      const errBox = document.getElementById(`resetPasswordError`);
      var detailLines = [];
      if (err && err.errors && typeof err.errors === 'object') {
        Object.keys(err.errors).forEach(function(field) {
          var msgs = err.errors[field];
          if (Array.isArray(msgs)) {
            msgs.forEach(function(m) { detailLines.push(field + ': ' + m); });
          } else {
            detailLines.push(field + ': ' + msgs);
          }
        });
      }
      errBox.textContent = detailLines.length > 0
        ? detailLines.join('\n')
        : (err.message || `Gagal mereset password.`);
      errBox.classList.remove(`d-none`);
    }).finally(function() {
      btn.disabled = false;
    });
  });

  /* Assign Groups Submit */
  document.getElementById(`userGroupsForm`).addEventListener(`submit`, function(e) {
    e.preventDefault();
    const uuid = document.getElementById(`userGroupsUuid`).value;
    const checked = Array.from(document.querySelectorAll(`#userGroupsList input[type="checkbox"]:checked`))
      .map(function(cb) { return parseInt(cb.value, 10); })
      .filter(function(id) { return !isNaN(id); });

    const btn      = document.getElementById(`btnUserGroupsSubmit`);
    const btnText  = document.getElementById(`userGroupsBtnText`);
    const spinner  = document.getElementById(`userGroupsBtnSpinner`);

    btn.disabled = true;
    btnText.textContent = `Menyimpan...`;
    spinner.classList.remove(`d-none`);

    FMS.put(`${BASE_URL}/${uuid}/groups`, { group_ids: checked }).then(function() {
      userGroupsModal.hide();
      FMS.toast(`Kelompok pengguna berhasil disimpan.`, true);
      loadUsers(currentPage);
    }).catch(function(err) {
      const errBox = document.getElementById(`userGroupsError`);
      errBox.textContent = err.message || `Gagal menyimpan kelompok.`;
      errBox.classList.remove(`d-none`);
    }).finally(function() {
      btn.disabled = false;
      btnText.textContent = `Simpan Kelompok`;
      spinner.classList.add(`d-none`);
    });
  });

  /* Revoke one session from session modal */
  document.getElementById(`userSessionsList`).addEventListener(`click`, function(e) {
    const button = e.target.closest(`.js-revoke-session`);
    if (!button) return;

    const uuid = document.getElementById(`userSessionsUuid`).value;
    const sessionUuid = button.dataset.sessionUuid;
    if (!uuid || !sessionUuid) return;

    button.disabled = true;
    FMS.del(`${BASE_URL}/${uuid}/sessions`, { session_uuid: sessionUuid }).then(function() {
      userSessionsModal.hide();
      FMS.toast(`Sesi perangkat berhasil dicabut.`, true);
      loadUsers(currentPage);
    }).catch(function(err) {
      FMS.toast(err.message || `Gagal mencabut sesi perangkat.`, false);
      button.disabled = false;
    });
  });

  /* Revoke All Sessions (dari modal sesi) */
  const btnRevokeAll = document.getElementById(`btnRevokeAllSessions`);
  if (btnRevokeAll) {
    btnRevokeAll.addEventListener(`click`, function() {
      const uuid = document.getElementById(`userSessionsUuid`).value;
      if (!uuid) return;
      btnRevokeAll.disabled = true;
      FMS.del(`${BASE_URL}/${uuid}/sessions`).then(function() {
        FMS.toast(`Semua sesi berhasil dicabut.`, true);
        userSessionsModal.hide();
        loadUsers(currentPage);
      }).catch(function(err) {
        FMS.toast(err.message || `Gagal mencabut sesi.`, false);
      }).finally(function() { btnRevokeAll.disabled = false; });
    });
  }

  /* Initial Load */
  loadUsers(1);
});
</script>
