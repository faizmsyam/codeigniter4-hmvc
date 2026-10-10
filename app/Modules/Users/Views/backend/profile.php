<?php
/**
 * View Profil Pengguna Backend — Tabbed Interface (data-driven)
 *
 * Semua data dimuat dari GET /api/v1/profile. Tidak ada query DB di view ini.
 */

$profileApiUrl     = (string) ($profileApiUrl ?? '');
$switchUrl         = (string) ($switchGroupUrl ?? '');
$updUrl            = (string) ($updateUrl ?? '');
$avatarUpUrl       = (string) ($avatarUrlUpload ?? '');
$changePwUrl       = (string) ($changePasswordUrl ?? '');
$activityLogUrl       = (string) ($activityLogsUrl ?? '');
$activityLogDetailUrl = (string) ($activityLogDetailUrl ?? $activityLogUrl);
?>

<div class="row" id="profilePage" data-api-url="<?php echo esc($profileApiUrl, 'attr'); ?>">
  <div class="col-xl-12">
    <div class="card custom-card">
      <div class="card-body pb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
              <span class="avatar avatar-xxl avatar-rounded bg-primary-transparent border border-2 border-primary">
                <img src="" alt="Avatar" id="profileAvatarImg" style="object-fit:cover;width:100%;height:100%;border-radius:50%;display:none;">
                <span class="fs-24 fw-bold text-primary" id="profileAvatarPlaceholder"><i class="ph-duotone ph-user"></i></span>
              </span>
              <label for="avatarInput" class="position-absolute bottom-0 end-0 btn btn-sm btn-icon btn-primary rounded-circle mb-0 d-none" style="cursor:pointer;" title="Ubah foto avatar" id="profileAvatarTrigger">
                <i class="ti ti-camera fs-14"></i>
              </label>
              <input type="file" id="avatarInput" accept="image/png,image/jpeg,image/webp" class="d-none">
            </div>
            <div>
              <h5 class="fw-semibold mb-1 d-flex align-items-center gap-2">
                <span id="profileDisplayName">Memuat profil…</span>
                <span class="badge bg-secondary-transparent fs-11 d-none" id="profileStatusBadge"></span>
              </h5>
              <p class="text-muted mb-1 fs-13">
                <i class="ti ti-at text-primary me-1"></i><span id="profileUsername">-</span>
                <span class="mx-2 text-muted">&bull;</span>
                <i class="ti ti-mail text-primary me-1"></i><span id="profileEmail">-</span>
                <span id="emailVerifiedBadge" class="badge bg-success-transparent text-success ms-1 d-none">
                  <i class="ti ti-circle-fill fs-8 me-1"></i>Terverifikasi
                </span>
              </p>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary-transparent fs-12">
                  <i class="ti ti-shield-check me-1"></i>
                  Peran Aktif: <strong class="ms-1" id="profileActiveGroupName">-</strong>
                </span>
                <span class="badge bg-info-transparent fs-12 d-none" id="profileGroupCountBadge">
                  <i class="ti ti-users-group me-1"></i><span id="profileGroupCount">0</span> Peran Terdaftar
                </span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted fs-12">
              <i class="ti ti-calendar me-1"></i>Bergabung: <span id="profileJoinedAt">-</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="card custom-card">
      <div class="card-header border-bottom">
        <ul class="nav nav-tabs card-header-tabs nav-tabs-header mb-0" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="tab-profile-info-btn" data-bs-toggle="tab" data-bs-target="#tab-profile-info" type="button" role="tab" aria-controls="tab-profile-info" aria-selected="true">
              <i class="ph-duotone ph-user fs-16"></i><span>Informasi Profil</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-profile-roles-btn" data-bs-toggle="tab" data-bs-target="#tab-profile-roles" type="button" role="tab" aria-controls="tab-profile-roles" aria-selected="false">
              <i class="ph-duotone ph-users-three fs-16"></i><span>Kelompok & Ganti Role</span>
              <span class="badge bg-primary rounded-pill fs-10 ms-1 d-none" id="switchableGroupCountBadge">0</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-profile-permissions-btn" data-bs-toggle="tab" data-bs-target="#tab-profile-permissions" type="button" role="tab" aria-controls="tab-profile-permissions" aria-selected="false">
              <i class="ph-duotone ph-shield-check fs-16"></i><span>Hak Akses Aktif</span>
              <span class="badge bg-secondary-transparent rounded-pill fs-10 ms-1" id="permissionCountBadge">0</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-profile-activity-btn" data-bs-toggle="tab" data-bs-target="#tab-profile-activity" type="button" role="tab" aria-controls="tab-profile-activity" aria-selected="false">
              <i class="ph-duotone ph-clock-counter-clockwise fs-16"></i><span>Aktivitas Saya</span>
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body">
        <div class="tab-content">
          <!-- TAB 1 -->
          <div class="tab-pane fade show active" id="tab-profile-info" role="tabpanel" aria-labelledby="tab-profile-info-btn">
            <div class="row gy-4">
              <div class="col-xl-7">
                <div class="border rounded p-3 mb-3">
                  <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2 text-primary">
                    <i class="ti ti-id fs-18"></i> Ubah Data Profil
                  </h6>
                  <form id="profileForm">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="prof_username" class="form-label fs-13 text-muted">Username</label>
                        <input type="text" class="form-control" id="prof_username" readonly disabled>
                        <div class="form-text fs-11">Username digunakan untuk masuk dan tidak dapat diubah sendiri.</div>
                      </div>
                      <div class="col-md-6">
                        <label for="prof_fullName" class="form-label fs-13 text-muted">Nama Lengkap</label>
                        <input type="text" class="form-control" id="prof_fullName" readonly disabled>
                        <div class="form-text fs-11">Nama Lengkap tidak dapat diubah sendiri.</div>
                      </div>
                      <div class="col-md-6">
                        <label for="prof_email" class="form-label fs-13 text-muted">Alamat Email</label>
                        <input type="email" class="form-control" id="prof_email" name="email" required>
                        <div class="form-text fs-11" id="profEmailHint">Email yang sudah terverifikasi tidak dapat diubah.</div>
                      </div>
                      <div class="col-md-6">
                        <label for="prof_phone" class="form-label fs-13 text-muted">Nomor Telepon</label>
                        <input type="text" class="form-control" id="prof_phone" name="phone" placeholder="Contoh: 08123456789">
                      </div>
                      <div class="col-12 text-end pt-2 d-none" id="profileSaveWrapper">
                        <button type="submit" class="btn btn-primary px-4" id="btnSaveProfile">
                          <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <div class="col-xl-5">
                <form id="changePasswordForm">
                  <div class="border rounded p-3 mb-3">
                    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2 text-primary">
                      <i class="ti ti-lock fs-18"></i> Keamanan Kata Sandi
                    </h6>
                    
                    <div>
                      <div class="mb-3">
                        <label for="current_password" class="form-label fs-13 text-muted">Kata Sandi Saat Ini</label>
                        <div class="input-group">
                          <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                          <button class="btn btn-outline-light border text-muted" type="button" data-toggle-password="current_password"><i class="ti ti-eye"></i></button>
                        </div>
                      </div>
                      <div class="mb-3">
                        <label for="new_password" class="form-label fs-13 text-muted">Kata Sandi Baru</label>
                        <div class="input-group">
                          <input type="password" class="form-control" id="new_password" name="new_password" required autocomplete="new-password">
                          <button class="btn btn-outline-light border text-muted" type="button" data-toggle-password="new_password"><i class="ti ti-eye"></i></button>
                        </div>
                        <div class="form-text fs-11">Minimal 12 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.</div>
                      </div>
                      <div class="mb-3">
                        <label for="confirm_password" class="form-label fs-13 text-muted">Konfirmasi Kata Sandi Baru</label>
                        <div class="input-group">
                          <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                          <button class="btn btn-outline-light border text-muted" type="button" data-toggle-password="confirm_password"><i class="ti ti-eye"></i></button>
                        </div>
                      </div>
                      <div class="text-end pt-2">
                        <button type="submit" class="btn btn-outline-primary px-3" id="btnChangePassword">
                          <i class="ti ti-key me-1"></i> Perbarui Kata Sandi
                        </button>
                      </div>
                    </div>
                  </div>
                </form>

                <div class="border rounded p-3 bg-light">
                  <div class="fs-12 text-muted mb-1">
                    <i class="ti ti-clock me-1"></i> Terakhir Masuk:
                    <span class="fw-semibold ms-1" id="profileLastLogin">-</span>
                  </div>
                  <div class="fs-12 text-muted">
                    <i class="ti ti-shield me-1"></i> Sesi Akses:
                    <span class="fw-semibold ms-1">Terlindungi (RBAC v2.5)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2 -->
          <div class="tab-pane fade" id="tab-profile-roles" role="tabpanel" aria-labelledby="tab-profile-roles-btn">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <h6 class="fw-semibold mb-1">Kelompok Peran Pengguna</h6>
                <p class="text-muted fs-13 mb-0">Pilih salah satu peran yang terdaftar untuk beralih konteks kerja dan menu backend Anda.</p>
              </div>
              <span class="badge bg-primary-transparent fs-13 px-3 py-2">
                <i class="ti ti-check me-1"></i> Peran Aktif Sekarang: <strong id="activeGroupLabel">-</strong>
              </span>
            </div>
            <div id="switchableGroupsContainer"></div>
            <div class="alert alert-info border-0 d-none" id="switchableGroupsEmpty">
              <i class="ti ti-info-circle me-2"></i> Tidak ada peran alternatif yang tersedia pada sesi ini.
            </div>
          </div>

          <!-- TAB 3 -->
          <div class="tab-pane fade" id="tab-profile-permissions" role="tabpanel" aria-labelledby="tab-profile-permissions-btn">
            <div class="mb-3">
              <h6 class="fw-semibold mb-1">Daftar Hak Akses (Permissions)</h6>
              <p class="text-muted fs-13 mb-0">Seluruh hak akses yang saat ini berlaku pada sesi Anda di bawah kelompok <strong id="activeGroupLabel2">-</strong>.</p>
            </div>
            <div class="alert alert-light border text-muted fs-13 d-none" id="permissionsEmpty">
              <i class="ti ti-info-circle me-1 text-primary"></i> Belum ada hak akses khusus yang tercatat pada sesi ini.
            </div>
            <div class="alert alert-success border-0 mb-4 d-none" id="permissionsSuperAdmin">
              <div class="d-flex align-items-center gap-2">
                <i class="ti ti-award fs-20"></i>
                <div>
                  <h6 class="fw-semibold mb-0">Akses Super Administrator Aktif</h6>
                  <div class="fs-12">Akun ini memiliki hak akses penuh (<code>*</code>) ke seluruh modul, menu, dan fitur backend.</div>
                </div>
              </div>
            </div>
            <div class="row g-3" id="permissionsContainer"></div>
          </div>

          <!-- TAB 4 -->
          <div class="tab-pane fade" id="tab-profile-activity" role="tabpanel" aria-labelledby="tab-profile-activity-btn">
            <div class="card custom-card" id="profileActivityLogs" data-list-url="<?php echo esc($activityLogUrl, 'attr'); ?>" data-detail-url="<?php echo esc($activityLogDetailUrl, 'attr'); ?>">
              <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <h6 class="fw-semibold mb-1">Aktivitas Saya</h6>
                  <p class="text-muted fs-13 mb-0">Riwayat aktivitas akun Anda pada sesi ini. Halaman Control Panel tetap dapat melihat seluruh aktivitas pengguna.</p>
                </div>
                <button type="button" class="btn btn-secondary btn-glare btn-wave label-btn" id="profileActivityRefresh"><i class="ri-refresh-line label-btn-icon me-2"></i>Refresh</button>
              </div>
              <div class="card-header border-bottom flex-wrap gap-2">
                <div class="input-group" style="max-width: 320px;">
                  <span class="input-group-text"><i class="ri-search-line"></i></span>
                  <input type="search" class="form-control" id="profileActivitySearch" placeholder="Cari aktivitas...">
                </div>
                <select class="form-select" id="profileActivityLimit" style="width: auto;">
                  <option value="10">10 baris</option>
                  <option value="25" selected>25 baris</option>
                  <option value="50">50 baris</option>
                </select>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="text-nowrap">Waktu</th>
                        <th>Aktivitas</th>
                        <th>Modul</th>
                        <th>Status</th>
                        <th>Detail</th>
                      </tr>
                    </thead>
                    <tbody id="profileActivityRows">
                      <tr><td colspan="5" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat aktivitas...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer d-none" id="card-footer-profile-activity">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div id="profileActivityPager" class="mb-0"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  const switchUrl         = <?php echo json_encode($switchUrl); ?>;
  const updateUrl         = <?php echo json_encode($updUrl); ?>;
  const avatarUrl         = <?php echo json_encode($avatarUpUrl); ?>;
  const changePasswordUrl = <?php echo json_encode($changePwUrl); ?>;
  const profileApiUrl     = <?php echo json_encode($profileApiUrl); ?>;
  const profilePage       = document.getElementById('profilePage');
  let profileModel        = {};

  function escapeProfile(value) {
    return FMS.escape(value == null ? `` : String(value));
  }

  function humanizeProfile(value) {
    return String(value || `-`).replace(/[._-]+/g, ` `).replace(/\b\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }

  function statusBadgeClass(statusValue) {
    return {
      active:  `bg-success-transparent`,
      pending: `bg-warning-transparent`,
      locked:  `bg-danger-transparent`
    }[statusValue] || `bg-secondary-transparent`;
  }

  function statusLabel(statusValue) {
    return {
      active:  `Aktif`,
      pending: `Menunggu`,
      locked:  `Terkunci`
    }[statusValue] || (statusValue ? statusValue.charAt(0).toUpperCase() + statusValue.slice(1) : `-`);
  }

  const profileTimezoneLabels = {
    "Asia/Jakarta": "WIB",
    "Asia/Pontianak": "WIB",
    "Asia/Makassar": "WITA",
    "Asia/Ujung_Pandang": "WITA",
    "Asia/Jayapura": "WIT",
    "Asia/Manokwari": "WIT"
  };
  const profileFallbackTimezone = "Asia/Jakarta";
  let profileTimezone = profileFallbackTimezone;
  try {
    const detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (Object.prototype.hasOwnProperty.call(profileTimezoneLabels, detectedTimezone)) {
      profileTimezone = detectedTimezone;
    }
  } catch (error) {
    profileTimezone = profileFallbackTimezone;
  }
  const profileTimezoneLabel = profileTimezoneLabels[profileTimezone];

  function formatDateValue(dateValue, withTime) {
    if (!dateValue) return `-`;
    const normalizedDate = String(dateValue).trim().replace(` `, `T`);
    const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalizedDate);
    const parsed = new Date(hasTimezone ? normalizedDate : `${normalizedDate}+07:00`);
    if (isNaN(parsed.getTime())) return `-`;
    const dateFormatter = new Intl.DateTimeFormat(`id-ID`, {
      timeZone: profileTimezone,
      day: `2-digit`,
      month: `short`,
      year: `numeric`
    });
    const formattedDate = dateFormatter.format(parsed);
    if (!withTime) return formattedDate;
    const timeFormatter = new Intl.DateTimeFormat(`id-ID`, {
      timeZone: profileTimezone,
      hour: `2-digit`,
      minute: `2-digit`,
      hourCycle: `h23`
    });
    return `${formattedDate}, ${timeFormatter.format(parsed)} ${profileTimezoneLabel}`;
  }

  function renderProfile(body) {
    profileModel = (body && body.user) ? body : {};
    const user = body.user || {};
    const can = body.can || {};
    const displayName = user.full_name || user.username || `Pengguna`;
    const initial = displayName.slice(0, 2).toUpperCase();
    const avatarUrlFromApi = body.avatar_url || ``;

    document.getElementById(`profileDisplayName`).textContent = displayName;
    document.getElementById(`profileUsername`).textContent = user.username || `-`;
    document.getElementById(`profileEmail`).textContent = user.email || `-`;
    const emailVerifiedBadge = document.getElementById(`emailVerifiedBadge`);
    if (emailVerifiedBadge) {
      emailVerifiedBadge.classList.toggle(`d-none`, user.is_email_verified !== true);
      emailVerifiedBadge.title = user.is_email_verified === true && user.email_verified_at
        ? `Diverifikasi: ${formatDateValue(user.email_verified_at, true)}`
        : ``;
    }
    document.getElementById(`prof_username`).value = user.username || ``;
    document.getElementById(`prof_fullName`).value = user.full_name || ``;
    document.getElementById(`prof_email`).value = user.email || ``;
    document.getElementById(`prof_phone`).value = user.phone || ``;
    document.getElementById(`profileLastLogin`).textContent = formatDateValue(user.last_login_at, true);
    document.getElementById(`profileJoinedAt`).textContent = formatDateValue(user.created_at, false);
    document.getElementById(`profileActiveGroupName`).textContent = body.active_group_name || `Default`;
    document.getElementById(`activeGroupLabel`).textContent = body.active_group_name || `Default`;
    document.getElementById(`activeGroupLabel2`).textContent = body.active_group_name || `Default`;

    const statusBadge = document.getElementById(`profileStatusBadge`);
    statusBadge.textContent = statusLabel(user.status);
    statusBadge.className = `badge fs-11 ` + statusBadgeClass(user.status);
    statusBadge.classList.remove(`d-none`);

    const avatarImg = document.getElementById(`profileAvatarImg`);
    const avatarPlaceholder = document.getElementById(`profileAvatarPlaceholder`);
    if (avatarUrlFromApi !== ``) {
      avatarImg.src = avatarUrlFromApi;
      avatarImg.style.display = `block`;
      avatarPlaceholder.classList.add(`d-none`);
    } else {
      avatarImg.style.display = `none`;
      avatarPlaceholder.textContent = initial;
      avatarPlaceholder.classList.remove(`d-none`);
    }

    document.getElementById(`profileAvatarTrigger`).classList.toggle(`d-none`, can.update_avatar !== true);
    document.getElementById(`avatarInput`).disabled = can.update_avatar !== true;

    document.getElementById(`prof_email`).disabled = can.update !== true || user.is_email_verified === true;
    document.getElementById(`prof_email`).readOnly = user.is_email_verified === true;
    const emailHint = document.getElementById(`profEmailHint`);
    if (emailHint) {
      emailHint.textContent = user.is_email_verified === true && user.email_verified_at
        ? `Email terverifikasi pada ${formatDateValue(user.email_verified_at, true)} — tidak dapat diubah.`
        : `Email belum terverifikasi dan masih dapat diubah.`;
    }
    document.getElementById(`profileSaveWrapper`).classList.toggle(`d-none`, can.update !== true);
    document.getElementById(`changePasswordForm`).classList.toggle(`d-none`, can.change_password !== true);

    /* Grup terdaftar + badge */
    const groups = body.groups || [];
    const groupCountBadge = document.getElementById(`profileGroupCountBadge`);
    if (groups.length > 1) {
      groupCountBadge.classList.remove(`d-none`);
      document.getElementById(`profileGroupCount`).textContent = groups.length;
    }

    /* Daftar role yang dapat dipilih */
    const switchableGroups = body.switchable_groups || [];
    const switchableBadge = document.getElementById(`switchableGroupCountBadge`);
    const switchableContainer = document.getElementById(`switchableGroupsContainer`);
    const switchableEmpty = document.getElementById(`switchableGroupsEmpty`);

    switchableBadge.textContent = switchableGroups.length;
    switchableBadge.classList.toggle(`d-none`, switchableGroups.length === 0);

    if (switchableGroups.length === 0) {
      switchableContainer.innerHTML = ``;
      switchableEmpty.classList.remove(`d-none`);
    } else {
      switchableEmpty.classList.add(`d-none`);
      switchableContainer.innerHTML = `<div class="row g-3">` + switchableGroups.map(function(group) {
        const groupId = Number(group.id || 0);
        const groupName = group.name || `Kelompok`;
        const groupDescription = group.description || `Kelompok hak akses standar sistem.`;

        return `<div class="col-md-6 col-xl-4">
          <div class="card custom-card border h-100 mb-0">
            <div class="card-body d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex align-items-start justify-content-between mb-2">
                  <span class="avatar avatar-md avatar-rounded bg-light text-primary"><i class="ti ti-shield fs-18"></i></span>
                  <span class="badge bg-light text-muted fs-11">Tersedia</span>
                </div>
                <h6 class="fw-semibold mb-1">${escapeProfile(groupName)}</h6>
                <p class="text-muted fs-12 mb-3">${escapeProfile(groupDescription)}</p>
              </div>
              <div>
                <button type="button" class="btn btn-sm btn-primary w-100 btn-switch-group" data-group-id="${groupId}" data-group-name="${escapeProfile(groupName)}">
                  <i class="ti ti-switch-horizontal me-1"></i> Beralih ke Peran Ini
                </button>
              </div>
            </div>
          </div>
        </div>`;
      }).join(``) + `</div>`;
    }

    /* Daftar permissions */
    const permissions = body.active_permissions || [];
    document.getElementById(`permissionCountBadge`).textContent = permissions.length;

    const permissionsContainer = document.getElementById(`permissionsContainer`);
    const permissionsEmpty = document.getElementById(`permissionsEmpty`);
    const permissionsSuperAdmin = document.getElementById(`permissionsSuperAdmin`);

    permissionsEmpty.classList.toggle(`d-none`, permissions.length > 0);
    permissionsSuperAdmin.classList.toggle(`d-none`, permissions.indexOf(`*`) === -1);
    permissionsContainer.innerHTML = ``;

    if (permissions.length > 0 && permissions.indexOf(`*`) === -1) {
      const grouped = {};
      permissions.forEach(function(permissionCode) {
        const parts = String(permissionCode).split(`.`);
        const moduleKey = parts.length > 1 ? parts[0].toUpperCase() : `LAINNYA`;
        if (!grouped[moduleKey]) grouped[moduleKey] = [];
        grouped[moduleKey].push(permissionCode);
      });

      Object.keys(grouped).sort().forEach(function(moduleKey) {
        const modulePermissions = grouped[moduleKey];
        permissionsContainer.insertAdjacentHTML(`beforeend`, `
          <div class="col-md-6 col-xl-4 permission-group-card">
            <div class="card custom-card border h-100 mb-0">
              <div class="card-header border-bottom py-2 bg-light">
                <span class="fw-semibold fs-12 text-primary"><i class="ti ti-folder me-1"></i> MODUL ${escapeProfile(moduleKey)}</span>
                <span class="badge bg-primary-transparent float-end fs-10">${modulePermissions.length}</span>
              </div>
              <div class="card-body p-2">
                <div class="d-flex flex-wrap gap-1">
                  ${modulePermissions.map(function(permissionItem) {
                    return `<span class="badge bg-light text-muted fs-11 permission-badge font-monospace"><i class="ti ti-key text-success me-1"></i>${escapeProfile(permissionItem)}</span>`;
                  }).join(``)}
                </div>
              </div>
            </div>
          </div>`);
      });
    }

    bindSwitchGroupButtons();
    bindAvatarInput();
  }

  function loadProfile() {
    if (!profileApiUrl) return;

    FMS.get(profileApiUrl)
      .then(function(body) {
        renderProfile(body);
      })
      .catch(function(error) {
        document.getElementById(`profileDisplayName`).textContent = `Gagal memuat profil`;
        FMS.toast(error.message || `Gagal memuat data profil.`, false);
      });
  }

  /* Toggle Password Visibility */
  document.querySelectorAll(`[data-toggle-password]`).forEach(function(btn) {
    btn.addEventListener(`click`, function() {
      const targetId = this.getAttribute(`data-toggle-password`);
      const input = document.getElementById(targetId);
      if (!input) return;
      const icon = this.querySelector(`i`);
      if (input.type === `password`) {
        input.type = `text`;
        if (icon) {
          icon.classList.remove(`ti-eye`);
          icon.classList.add(`ti-eye-off`);
        }
      } else {
        input.type = `password`;
        if (icon) {
          icon.classList.remove(`ti-eye-off`);
          icon.classList.add(`ti-eye`);
        }
      }
    });
  });

  /* Simpan Profil */
  const profileForm = document.getElementById(`profileForm`);
  if (profileForm) {
    profileForm.addEventListener(`submit`, function(e) {
      e.preventDefault();
      const btn = document.getElementById(`btnSaveProfile`);
      if (btn) btn.disabled = true;

      const profilePayload = {
        email: String(document.getElementById(`prof_email`).value || ``).trim(),
        phone: String(document.getElementById(`prof_phone`).value || ``).trim()
      };

      /* Jangan kirim email jika field terkunci (sudah terverifikasi). */
      if (document.getElementById(`prof_email`).readOnly) {
        delete profilePayload.email;
      }

      FMS.ajax({
        url: updateUrl,
        method: `PATCH`,
        data: profilePayload
      })
      .then(function(res) {
        if (btn) btn.disabled = false;
        FMS.toast(res.message || `Profil berhasil diperbarui.`, true);
      })
      .catch(function(err) {
        if (btn) btn.disabled = false;
        FMS.toast(err.message || `Gagal menyimpan profil.`, false);
      });
    });
  }

  /* Ganti Password */
  const changePasswordForm = document.getElementById(`changePasswordForm`);
  if (changePasswordForm) {
    changePasswordForm.addEventListener(`submit`, function(e) {
      e.preventDefault();
      const btn = document.getElementById(`btnChangePassword`);
      if (btn) btn.disabled = true;

      const passwordPayload = {
        current_password: String(document.getElementById(`current_password`).value || ``),
        new_password: String(document.getElementById(`new_password`).value || ``),
        confirm_password: String(document.getElementById(`confirm_password`).value || ``)
      };

      FMS.ajax({
        url: changePasswordUrl,
        method: `POST`,
        data: passwordPayload
      })
      .then(function(res) {
        if (btn) btn.disabled = false;
        changePasswordForm.reset();
        FMS.toast(res.message || `Kata sandi berhasil diperbarui.`, true);
      })
      .catch(function(err) {
        if (btn) btn.disabled = false;
        FMS.toast(err.message || `Gagal memperbarui kata sandi.`, false);
      });
    });
  }

  function bindSwitchGroupButtons() {
    document.querySelectorAll(`.btn-switch-group`).forEach(function(button) {
      if (button.dataset.bound === `1`) return;
      button.dataset.bound = `1`;
      button.addEventListener(`click`, function() {
        const grpId = this.getAttribute(`data-group-id`);
        const grpName = this.getAttribute(`data-group-name`) || `peran terpilih`;
        const currentBtn = this;

        FMS.confirm({
          title: `Ganti Peran`,
          message: `Beralih ke peran "${grpName}"? Menu dan hak akses akan langsung disesuaikan.`,
          confirmLabel: `Ganti Peran`,
          loadingLabel: `Mengganti...`,
          variant: `primary`,
          action: function () {
            currentBtn.disabled = true;
            return FMS.ajax({
              url: switchUrl,
              method: `POST`,
              data: { group_id: Number(grpId) }
            }).then(function(res) {
              FMS.toast(res.message || `Berhasil beralih peran.`, true);
              setTimeout(function() {
                window.location.reload();
              }, 800);
            }).catch(function(err) {
              currentBtn.disabled = false;
              throw err;
            });
          }
        });
      });
    });
  }

  function bindAvatarInput() {
    const avatarInput = document.getElementById(`avatarInput`);
    if (!avatarInput || avatarInput.dataset.bound === `1`) return;
    avatarInput.dataset.bound = `1`;

    avatarInput.addEventListener(`change`, function() {
      if (!this.files || !this.files[0]) return;
      const file = this.files[0];

      if (file.size > 2 * 1024 * 1024) {
        FMS.toast(`Ukuran berkas avatar maksimal 2MB.`, false);
        this.value = ``;
        return;
      }

      const fd = new FormData();
      fd.append(`avatar`, file);

      FMS.ajax({
        url: avatarUrl,
        method: `POST`,
        data: fd
      })
      .then(function(res) {
        FMS.toast(res.message || `Avatar berhasil diperbarui.`, true);
        setTimeout(function() {
          window.location.reload();
        }, 700);
      })
      .catch(function(err) {
        FMS.toast(err.message || `Gagal memperbarui avatar.`, false);
      });
    });
  }

  /* Aktivitas Saya */
  const activityRoot    = document.getElementById(`profileActivityLogs`);
  const activityRows    = document.getElementById(`profileActivityRows`);
  const activityPager   = document.getElementById(`profileActivityPager`);
  const activityFooter  = document.getElementById(`card-footer-profile-activity`);
  const activitySearch  = document.getElementById(`profileActivitySearch`);
  const activityLimit   = document.getElementById(`profileActivityLimit`);
  const activityRefresh = document.getElementById(`profileActivityRefresh`);
  let activityLoaded    = false;
  let activityPage      = 1;
  let activityPerPage   = Number((activityLimit && activityLimit.value) || 25);
  let activitySearchTask = null;

  function activityStatusBadge(statusCode) {
    if (statusCode === null || statusCode === undefined || statusCode === ``) {
      return `<span class="badge bg-secondary-transparent">OK</span>`;
    }
    const code = Number(statusCode || 0);
    const color = code >= 200 && code < 400 ? `success` : (code >= 400 ? `danger` : `secondary`);
    return `<span class="badge bg-${color}-transparent">${escapeProfile(statusCode || `-`)}</span>`;
  }

  function activityChangeMarkup(item) {
    if (!item.has_changes) return `<span class="text-muted">-</span>`;

    const detailUrl = `${activityRoot.dataset.detailUrl}/${encodeURIComponent(item.uuid)}`;
    return `<button type="button" class="btn btn-outline-primary btn-sm js-profile-activity-detail" data-detail-url="${escapeProfile(detailUrl)}"><i class="ri-eye-line me-1"></i>Detail</button>`;
  }

  function activityRowMarkup(item) {
    const description = item.description ?
      `<div class="text-muted fs-12 mt-1 text-wrap" style="max-width:360px">${escapeProfile(item.description)}</div>` : ``;
    const entity = item.entity_type ?
      `<span class="badge bg-light text-default border">${escapeProfile(humanizeProfile(item.entity_type))}</span>${item.entity_id ? `<div class="text-muted fs-12 mt-1">#${escapeProfile(item.entity_id)}</div>` : ``}` :
      `<span class="text-muted">-</span>`;

    return `<tr>
      <td class="text-nowrap"><div class="fw-medium">${escapeProfile(item.created_at || `-`)}</div><div class="text-muted fs-12">${escapeProfile(item.http_method || ``)}</div></td>
      <td><div class="fw-semibold text-default">${escapeProfile(humanizeProfile(item.event))}</div>${description}</td>
      <td><span class="badge bg-primary-transparent">${escapeProfile(humanizeProfile(item.module))}</span></td>
      <td>${activityStatusBadge(item.status_code)}</td>
      <td>${activityChangeMarkup(item)}</td>
    </tr>`;
  }

  function renderActivityPayload(payload) {
    const value = payload && typeof payload === `object` ? payload : {};
    return escapeProfile(JSON.stringify(value, null, 2));
  }

  if (activityRows) {
    activityRows.addEventListener(`click`, function(event) {
      const trigger = event.target.closest(`.js-profile-activity-detail`);
      if (!trigger) return;

      const originalHtml = trigger.innerHTML;
      trigger.disabled = true;
      trigger.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Memuat`;

      FMS.get(trigger.dataset.detailUrl).then(function(body) {
        const item = body && body.data ? body.data : body;
        const modal = document.createElement(`div`);
        modal.className = `modal fade`;
        modal.tabIndex = -1;
        modal.innerHTML = `<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="ri-file-list-3-line me-2"></i>Detail Perubahan</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="small fw-semibold text-danger mb-2">Before</div>
                  <pre class="bg-danger-transparent border rounded p-3 mb-0 small" style="min-height:160px;max-height:420px;overflow:auto;white-space:pre-wrap;">${renderActivityPayload(item.before)}</pre>
                </div>
                <div class="col-md-6">
                  <div class="small fw-semibold text-success mb-2">After</div>
                  <pre class="bg-success-transparent border rounded p-3 mb-0 small" style="min-height:160px;max-height:420px;overflow:auto;white-space:pre-wrap;">${renderActivityPayload(item.after)}</pre>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>`;
        document.body.appendChild(modal);
        const instance = new bootstrap.Modal(modal);
        modal.addEventListener(`hidden.bs.modal`, function() {
          instance.dispose();
          modal.remove();
        });
        instance.show();
      }).catch(function(error) {
        FMS.toast(error.message || `Gagal memuat detail aktivitas.`, false);
      }).finally(function() {
        trigger.disabled = false;
        trigger.innerHTML = originalHtml;
      });
    });
  }

  function loadProfileActivity(targetPage) {
    if (!activityRoot || !activityRows || activityRoot.dataset.listUrl === ``) return;
    activityPage = targetPage || activityPage;
    activityRows.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat aktivitas...</td></tr>`;
    if (activityFooter) activityFooter.classList.add(`d-none`);

    const params = {
      page: String(activityPage),
      per_page: String(activityPerPage)
    };
    const keyword = activitySearch ? activitySearch.value.trim() : ``;
    if (keyword !== ``) params.search_keyword = keyword;

    FMS.get(activityRoot.dataset.listUrl, params)
      .then(function(body) {
        const data = (body && body.items !== undefined) ? body : (body.data || {});
        const items = data.items || [];
        activityRows.innerHTML = items.length ?
          items.map(function(item) {
            return activityRowMarkup(item);
          }).join(``) :
          `<tr><td colspan="5" class="text-center text-muted py-5"><i class="ri-history-line fs-24 d-block mb-2"></i>Belum ada aktivitas untuk akun ini.</td></tr>`;

        FMS.pagination({
          target: activityPager,
          total: Number(data.total || 0),
          page: Number(data.page || activityPage),
          perPage: Number(data.per_page || activityPerPage),
          onChange: function(nextPage) {
            loadProfileActivity(nextPage);
          }
        });
        if (activityFooter) activityFooter.classList.remove(`d-none`);
      })
      .catch(function(error) {
        activityRows.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4"><i class="ri-error-warning-line me-1"></i>${escapeProfile(error.message)}</td></tr>`;
      });
  }

  const activityTabButton = document.getElementById(`tab-profile-activity-btn`);
  if (activityTabButton) {
    activityTabButton.addEventListener(`shown.bs.tab`, function() {
      if (activityLoaded) return;
      activityLoaded = true;
      loadProfileActivity(1);
    });
  }

  if (activityLimit) {
    activityLimit.addEventListener(`change`, function() {
      activityPerPage = Number(activityLimit.value || 25);
      loadProfileActivity(1);
    });
  }

  if (activitySearch) {
    activitySearch.addEventListener(`input`, function() {
      if (activitySearchTask) clearTimeout(activitySearchTask);
      activitySearchTask = setTimeout(function() {
        loadProfileActivity(1);
      }, 400);
    });
  }

  if (activityRefresh) {
    activityRefresh.addEventListener(`click`, function() {
      loadProfileActivity(activityPage);
    });
  }

  if (activityTabButton && activityTabButton.classList.contains(`active`)) {
    activityLoaded = true;
    loadProfileActivity(1);
  }

  loadProfile();
});
</script>
