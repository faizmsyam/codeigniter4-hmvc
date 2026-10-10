<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */
$permissions = array_map('strval', $backendPermissions ?? []);
$can = static fn (string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
$apiBaseUrl = site_url('api/v1/settings');
?>
<div class="row">
  <div class="col-12">
    <div class="card custom-card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Konfigurasi &amp; Keamanan Sistem</h4>
          <small class="text-muted">Kelola kebijakan autentikasi sistem, API key, dan basic auth client</small>
        </div>
        <div class="w-100 w-sm-auto">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-transparent border border-danger-subtle px-2 py-1 fs-12">
              <i class="ri-shield-keyhole-line me-1"></i>Super Administrator
            </span>
            <button type="button" class="btn btn-secondary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnRefreshAll">
              <i class="ri-refresh-line label-btn-icon me-2"></i> Refresh
            </button>
          </div>
        </div>
      </div>

      <div class="card-header border-bottom">
        <ul class="nav nav-tabs card-header-tabs nav-tabs-header mb-0" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab" aria-controls="tab-overview" aria-selected="true">
              <i class="ph-duotone ph-shield-check fs-16"></i>
              <span>Ikhtisar Sistem</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-auth-btn" data-bs-toggle="tab" data-bs-target="#tab-auth" type="button" role="tab" aria-controls="tab-auth" aria-selected="false">
              <i class="ph-duotone ph-lock-key fs-16"></i>
              <span>Kebijakan Autentikasi</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-email-btn" data-bs-toggle="tab" data-bs-target="#tab-email" type="button" role="tab" aria-controls="tab-email" aria-selected="false">
              <i class="ph-duotone ph-envelope-simple fs-16"></i>
              <span>Email / SMTP</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-api-keys-btn" data-bs-toggle="tab" data-bs-target="#tab-api-keys" type="button" role="tab" aria-controls="tab-api-keys" aria-selected="false">
              <i class="ph-duotone ph-key fs-16"></i>
              <span>API Keys</span>
              <span class="badge bg-primary-transparent rounded-pill fs-11 ms-1" id="badgeTotalApiKeys">0</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-basic-auth-btn" data-bs-toggle="tab" data-bs-target="#tab-basic-auth" type="button" role="tab" aria-controls="tab-basic-auth" aria-selected="false">
              <i class="ph-duotone ph-user-switch fs-16"></i>
              <span>Basic Auth Clients</span>
              <span class="badge bg-info-transparent rounded-pill fs-11 ms-1" id="badgeTotalBasicClients">0</span>
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body">
        <div class="tab-content">

          <!-- ════════ TAB 1: IKHTISAR KEAMANAN SISTEM ════════ -->
          <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn">
            <div class="row g-3 mb-4" id="overviewSummaryRow">
              <div class="col-sm-6 col-xl-3">
                <div class="card border mb-0">
                  <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="text-muted fs-13">Environment</span>
                      <span class="avatar avatar-sm bg-primary-transparent rounded">
                        <i class="ph-duotone ph-cpu fs-18"></i>
                      </span>
                    </div>
                    <h5 class="fw-bold mb-1 text-uppercase text-primary" id="ovEnv">-</h5>
                    <span class="text-muted fs-12">Lingkungan server aktif</span>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card border mb-0">
                  <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="text-muted fs-13">API Keys Aktif</span>
                      <span class="avatar avatar-sm bg-success-transparent rounded">
                        <i class="ph-duotone ph-key fs-18"></i>
                      </span>
                    </div>
                    <h5 class="fw-bold mb-1 text-success" id="ovApiActive">-</h5>
                    <span class="text-muted fs-12"><span id="ovApiTotal">0</span> total / <span id="ovApiRevoked">0</span> dicabut</span>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card border mb-0">
                  <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="text-muted fs-13">Basic Auth Clients</span>
                      <span class="avatar avatar-sm bg-info-transparent rounded">
                        <i class="ph-duotone ph-user-switch fs-18"></i>
                      </span>
                    </div>
                    <h5 class="fw-bold mb-1 text-info" id="ovBasicActive">-</h5>
                    <span class="text-muted fs-12"><span id="ovBasicLocked">0</span> terkunci / <span id="ovBasicRevoked">0</span> dicabut</span>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card border mb-0">
                  <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="text-muted fs-13">Pendaftaran Publik</span>
                      <span class="avatar avatar-sm bg-warning-transparent rounded">
                        <i class="ph-duotone ph-user-plus fs-18"></i>
                      </span>
                    </div>
                    <h5 class="fw-bold mb-1" id="ovPublicReg">-</h5>
                    <span class="text-muted fs-12" id="ovEmailVerif">-</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dokumentasi Integrasi & Header Keamanan -->
            <div class="card border shadow-none mb-0">
              <div class="card-header bg-light-subtle">
                <h6 class="mb-0 fw-semibold"><i class="ri-book-open-line me-1"></i> Panduan Kredensial &amp; Header Autentikasi API</h6>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <h6 class="fw-semibold text-primary mb-2"><i class="ri-key-2-line me-1"></i> 1. Autentikasi API Key</h6>
                    <p class="text-muted fs-13 mb-2">Gunakan salah satu header HTTP di bawah pada setiap permintaan ke endpoint <code>/api/v1/*</code>:</p>
                    <pre class="bg-dark text-light p-2 rounded fs-12 mb-2"><code>X-API-Key: fms_&lt;key_id&gt;_&lt;secret&gt;</code></pre>
                    <p class="text-muted fs-12 mb-0">Atau via Header Authorization: <code>Authorization: ApiKey fms_&lt;key_id&gt;_&lt;secret&gt;</code></p>
                  </div>
                  <div class="col-lg-6">
                    <h6 class="fw-semibold text-info mb-2"><i class="ri-shield-user-line me-1"></i> 2. Autentikasi Basic Client</h6>
                    <p class="text-muted fs-13 mb-2">Kirim kredensial Basic Auth base64(username:password):</p>
                    <pre class="bg-dark text-light p-2 rounded fs-12 mb-2"><code>Authorization: Basic &lt;base64(username:password)&gt;</code></pre>
                    <p class="text-muted fs-12 mb-0">Otomatis terkunci 15 menit jika 5 kali gagal berturut-turut.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ════════ TAB 2: KEBIJAKAN AUTENTIKASI ════════ -->
          <div class="tab-pane fade" id="tab-auth" role="tabpanel" aria-labelledby="tab-auth-btn">
            <div class="row justify-content-center">
              <div class="col-12 col-xl-9">
                <form id="authSettingsForm" novalidate>
                  <div class="border rounded p-3 p-md-4 mb-3">
                    <h6 class="fw-semibold text-primary mb-3">
                      <i class="ri-user-add-line me-1"></i> Kebijakan Pendaftaran &amp; Verifikasi Akun
                    </h6>
                    <div class="mb-3">
                      <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                        <label class="form-check-label fw-semibold" for="setPublicReg">
                          Izinkan Pendaftaran Publik (Self-Registration)
                          <div class="text-muted fw-normal fs-12">Jika dinonaktifkan, pendaftaran user baru hanya dapat dilakukan oleh Administrator melalui panel admin.</div>
                        </label>
                        <input class="form-check-input ms-3 fs-18" type="checkbox" role="switch" id="setPublicReg">
                      </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                      <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                        <label class="form-check-label fw-semibold" for="setPublicEmailVerif">
                          Wajib Verifikasi Email (Pendaftaran Publik)
                          <div class="text-muted fw-normal fs-12">User yang mendaftar sendiri wajib memverifikasi email sebelum dapat masuk ke sistem.</div>
                        </label>
                        <input class="form-check-input ms-3 fs-18" type="checkbox" role="switch" id="setPublicEmailVerif">
                      </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-0">
                      <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                        <label class="form-check-label fw-semibold" for="setAdminEmailVerif">
                          Wajib Verifikasi Email (User Buatan Admin)
                          <div class="text-muted fw-normal fs-12">User yang dibuat langsung oleh Administrator wajib melakukan verifikasi email sebelum aktif.</div>
                        </label>
                        <input class="form-check-input ms-3 fs-18" type="checkbox" role="switch" id="setAdminEmailVerif">
                      </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-0">
                      <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                        <label class="form-check-label fw-semibold" for="setAdminMustChangePassword">
                          Wajib Ganti Password (User Buatan Admin)
                          <div class="text-muted fw-normal fs-12">User yang dibuat oleh Administrator wajib mengganti password default sebelum dapat mengakses sistem.</div>
                        </label>
                        <input class="form-check-input ms-3 fs-18" type="checkbox" role="switch" id="setAdminMustChangePassword">
                      </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                      <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                        <label class="form-check-label fw-semibold" for="setLoginRateLimit">
                          Aktifkan Rate Limit Login
                          <div class="text-muted fw-normal fs-12">Membatasi percobaan login gagal. Super Administrator selalu dikecualikan agar tidak pernah terkunci.</div>
                        </label>
                        <input class="form-check-input ms-3 fs-18" type="checkbox" role="switch" id="setLoginRateLimit">
                      </div>
                    </div>
                    <div class="row g-3" id="loginRateLimitOptions">
                      <div class="col-md-4">
                        <label for="setLoginMaxFailures" class="form-label fw-semibold">Maksimal Gagal Login</label>
                        <input type="number" class="form-control" id="setLoginMaxFailures" min="1" max="100" value="5">
                        <div class="form-text fs-12">Jumlah kegagalan sebelum akun dikunci.</div>
                      </div>
                      <div class="col-md-4">
                        <label for="setLoginFailureWindow" class="form-label fw-semibold">Jendela Percobaan</label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="setLoginFailureWindow" min="1" max="86400" value="900">
                          <span class="input-group-text">Detik</span>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <label for="setLoginLockout" class="form-label fw-semibold">Durasi Penguncian</label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="setLoginLockout" min="1" max="86400" value="900">
                          <span class="input-group-text">Detik</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="border rounded p-3 p-md-4 mb-4">
                    <h6 class="fw-semibold text-primary mb-3">
                      <i class="ri-timer-line me-1"></i> Durasi Token &amp; Pembatasan Frekuensi
                    </h6>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="setTtl" class="form-label fw-semibold">Masa Berlaku Token Verifikasi (Menit) <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="setTtl" min="5" max="100800" required>
                          <span class="input-group-text">Menit</span>
                        </div>
                        <div class="form-text fs-12">Default: 1440 menit (24 jam). Minimal 5 menit.</div>
                        <div class="invalid-feedback" id="setTtlError"></div>
                      </div>
                      <div class="col-md-6">
                        <label for="setCooldown" class="form-label fw-semibold">Jeda Kirim Ulang Email Verifikasi (Detik) <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="setCooldown" min="0" max="3600" required>
                          <span class="input-group-text">Detik</span>
                        </div>
                        <div class="form-text fs-12">Mencegah spam email verifikasi. Default: 120 detik (2 menit).</div>
                        <div class="invalid-feedback" id="setCooldownError"></div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="text-muted fs-12">
                      <span id="authSettingsVersion">Versi: 1</span> &bull; Terakhir diperbarui: <span id="authSettingsUpdatedAt">-</span>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-light" id="btnResetAuthSettings">
                        <i class="ri-refresh-line me-1"></i>Reset Form
                      </button>
                      <button type="submit" class="btn btn-primary btn-wave btn-glare label-btn" id="btnSaveAuthSettings">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="authSaveSpinner"></span>
                        <i class="ri-save-line label-btn-icon me-1"></i>Simpan Perubahan
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- ════════ TAB 3: EMAIL / SMTP ════════ -->
          <div class="tab-pane fade" id="tab-email" role="tabpanel" aria-labelledby="tab-email-btn">
            <div class="row justify-content-center">
              <div class="col-12 col-xl-9">
                <form id="emailSettingsForm" novalidate>
                  <div class="border rounded p-3 p-md-4 mb-3">
                    <h6 class="fw-semibold text-primary mb-3">
                      <i class="ri-mail-settings-line me-1"></i> Konfigurasi Pengiriman Email
                    </h6>
                    <div class="mb-3">
                      <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                        <label class="form-check-label fw-semibold" for="setEmailEnabled">
                          Aktifkan Pengiriman Email
                          <div class="text-muted fw-normal fs-12">Jika dinonaktifkan, semua pengiriman email (verifikasi, reset password, notifikasi) akan dihentikan.</div>
                        </label>
                        <input class="form-check-input ms-3 fs-18" type="checkbox" role="switch" id="setEmailEnabled">
                      </div>
                    </div>
                    <hr class="my-3">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="setEmailProtocol" class="form-label fw-semibold">Protokol</label>
                        <select class="form-control form-select" id="setEmailProtocol">
                          <option value="smtp">SMTP</option>
                          <option value="mail">PHP mail()</option>
                          <option value="sendmail">Sendmail</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="setSmtpCrypto" class="form-label fw-semibold">Enkripsi SMTP</label>
                        <select class="form-control form-select" id="setSmtpCrypto">
                          <option value="tls">TLS / STARTTLS (port 587)</option>
                          <option value="ssl">SSL / Implicit TLS (port 465)</option>
                          <option value="">Tanpa Enkripsi</option>
                        </select>
                      </div>
                      <div class="col-md-8">
                        <label for="setSmtpHost" class="form-label fw-semibold">Host SMTP</label>
                        <input type="text" class="form-control" id="setSmtpHost" placeholder="smtp.gmail.com">
                      </div>
                      <div class="col-md-4">
                        <label for="setSmtpPort" class="form-label fw-semibold">Port</label>
                        <input type="number" class="form-control" id="setSmtpPort" min="1" max="65535" value="587">
                      </div>
                      <div class="col-md-6">
                        <label for="setSmtpUser" class="form-label fw-semibold">Username SMTP</label>
                        <input type="text" class="form-control" id="setSmtpUser" placeholder="user@domain.com">
                      </div>
                      <div class="col-md-6">
                        <label for="setSmtpPassword" class="form-label fw-semibold">Password SMTP</label>
                        <input type="password" class="form-control" id="setSmtpPassword" placeholder="Biarkan kosong untuk tidak mengubah" autocomplete="new-password">
                        <div class="form-text fs-12" id="smtpPasswordHint">Password terenkripsi. Biarkan kosong bila tidak ingin mengubah.</div>
                      </div>
                      <div class="col-md-6">
                        <label for="setFromEmail" class="form-label fw-semibold">Email Pengirim</label>
                        <input type="email" class="form-control" id="setFromEmail" placeholder="no-reply@domain.com">
                      </div>
                      <div class="col-md-6">
                        <label for="setFromName" class="form-label fw-semibold">Nama Pengirim</label>
                        <input type="text" class="form-control" id="setFromName" placeholder="Nama Aplikasi">
                      </div>
                      <div class="col-md-6">
                        <label for="setReplyTo" class="form-label fw-semibold">Reply-To <span class="text-muted">(opsional)</span></label>
                        <input type="email" class="form-control" id="setReplyTo" placeholder="support@domain.com">
                      </div>
                      <div class="col-md-6">
                        <label for="setEmailTimeout" class="form-label fw-semibold">Timeout <span class="text-muted">(detik)</span></label>
                        <input type="number" class="form-control" id="setEmailTimeout" min="1" max="120" value="10">
                      </div>
                    </div>
                  </div>

                  <div class="border rounded p-3 p-md-4 mb-3">
                    <h6 class="fw-semibold text-info mb-3"><i class="ri-send-plane-line me-1"></i> Uji Koneksi</h6>
                    <div class="row g-3 align-items-end">
                      <div class="col-md-8">
                        <label for="setTestRecipient" class="form-label fw-semibold">Kirim Email Tes Ke</label>
                        <input type="email" class="form-control" id="setTestRecipient" placeholder="tes@domain.com">
                      </div>
                      <div class="col-md-4">
                        <button type="button" class="btn btn-outline-info w-100" id="btnTestEmail">
                          <span class="spinner-border spinner-border-sm me-1 d-none" id="emailTestSpinner"></span>
                          <i class="ri-flight-takeoff-line me-1"></i>Kirim Tes
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="text-muted fs-12">
                      <span id="emailSettingsVersion">Versi: 1</span> &bull; Terakhir diperbarui: <span id="emailSettingsUpdatedAt">-</span>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-light" id="btnResetEmailSettings">
                        <i class="ri-refresh-line me-1"></i>Reset Form
                      </button>
                      <button type="submit" class="btn btn-primary btn-wave btn-glare label-btn" id="btnSaveEmailSettings">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="emailSaveSpinner"></span>
                        <i class="ri-save-line label-btn-icon me-1"></i>Simpan Perubahan
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- ════════ TAB 4: API KEYS ════════ -->
          <div class="tab-pane fade" id="tab-api-keys" role="tabpanel" aria-labelledby="tab-api-keys-btn">
            <div class="row g-3 mb-3" id="apiKeysSummaryRow"></div>

            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 px-0 pt-0 border-0 mb-3">
              <div class="w-100 w-sm-auto">
                <div class="d-flex flex-column flex-sm-row gap-2">
                  <div style="min-width: 240px;">
                    <input type="search" class="form-control" id="searchApiKey" placeholder="Cari label atau key ID...">
                  </div>
                  <div>
                    <select class="form-control form-select" id="filterApiEnv">
                      <option value="all">Semua Environment</option>
                      <option value="production">Production</option>
                      <option value="staging">Staging</option>
                      <option value="development">Development</option>
                    </select>
                  </div>
                  <div>
                    <select class="form-control form-select" id="filterApiStatus">
                      <option value="">Semua Status</option>
                      <option value="active">Aktif</option>
                      <option value="revoked">Dicabut (Revoked)</option>
                      <option value="expired">Kedaluwarsa</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="w-100 w-sm-auto">
                <div class="d-flex align-items-center gap-2">
                  <div class="d-flex align-items-center gap-2 me-2">
                    <label for="apiKeysPerPage" class="text-muted small text-nowrap mb-0">Baris:</label>
                    <select id="apiKeysPerPage" class="form-select form-select-sm" style="width:75px">
                      <option value="10" selected>10</option>
                      <option value="25">25</option>
                      <option value="50">50</option>
                      <option value="100">100</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-primary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnOpenCreateApiKey">
                    <i class="ri-add-line label-btn-icon me-2"></i>Buat API Key
                  </button>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover text-nowrap align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Label &amp; Key ID</th>
                    <th>Hak Akses (Scopes)</th>
                    <th>Environment</th>
                    <th class="text-center" style="width: 110px;">Status</th>
                    <th>Kedaluwarsa</th>
                    <th>Terakhir Dipakai</th>
                    <th class="text-end" style="width: 170px;">Aksi</th>
                  </tr>
                </thead>
                <tbody id="tbodyApiKeys">
                  <tr><td colspan="8" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>
                </tbody>
              </table>
            </div>

            <div class="card-footer d-none px-0 pt-3 border-0" id="card-footer-apiKeysPager">
              <div id="paginationApiKeys" class="small"></div>
            </div>
          </div>

          <!-- ════════ TAB 4: BASIC AUTH CLIENTS ════════ -->
          <div class="tab-pane fade" id="tab-basic-auth" role="tabpanel" aria-labelledby="tab-basic-auth-btn">
            <div class="row g-3 mb-3" id="basicSummaryRow"></div>

            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 px-0 pt-0 border-0 mb-3">
              <div class="w-100 w-sm-auto">
                <div class="d-flex flex-column flex-sm-row gap-2">
                  <div style="min-width: 240px;">
                    <input type="search" class="form-control" id="searchBasic" placeholder="Cari username atau label...">
                  </div>
                  <div>
                    <select class="form-control form-select" id="filterBasicEnv">
                      <option value="all">Semua Environment</option>
                      <option value="production">Production</option>
                      <option value="staging">Staging</option>
                      <option value="development">Development</option>
                    </select>
                  </div>
                  <div>
                    <select class="form-control form-select" id="filterBasicStatus">
                      <option value="">Semua Status</option>
                      <option value="active">Aktif</option>
                      <option value="locked">Terkunci</option>
                      <option value="revoked">Dicabut (Revoked)</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="w-100 w-sm-auto">
                <div class="d-flex align-items-center gap-2">
                  <div class="d-flex align-items-center gap-2 me-2">
                    <label for="basicPerPage" class="text-muted small text-nowrap mb-0">Baris:</label>
                    <select id="basicPerPage" class="form-select form-select-sm" style="width:75px">
                      <option value="10" selected>10</option>
                      <option value="25">25</option>
                      <option value="50">50</option>
                      <option value="100">100</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-primary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnOpenCreateBasic">
                    <i class="ri-add-line label-btn-icon me-2"></i>Buat Basic Client
                  </button>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover text-nowrap align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;">No.</th>
                    <th>Username &amp; Label</th>
                    <th>Hak Akses (Scopes)</th>
                    <th>Environment</th>
                    <th class="text-center" style="width: 120px;">Status</th>
                    <th>Kedaluwarsa</th>
                    <th>Terakhir Dipakai</th>
                    <th class="text-end" style="width: 190px;">Aksi</th>
                  </tr>
                </thead>
                <tbody id="tbodyBasicClients">
                  <tr><td colspan="8" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>
                </tbody>
              </table>
            </div>

            <div class="card-footer d-none px-0 pt-3 border-0" id="card-footer-basicPager">
              <div id="paginationBasicClients" class="small"></div>
            </div>
          </div>

        </div><!-- /.tab-content -->
      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL DIALOGS
══════════════════════════════════════════════════════════════ -->

<!-- Modal: Buat API Key Baru -->
<div class="modal fade" id="modalCreateApiKey" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-key-line me-1 text-primary"></i>Buat API Key Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="formCreateApiKey" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <label for="createApiLabel" class="form-label">Nama / Label Aplikasi <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="createApiLabel" maxlength="100" autocomplete="off" placeholder="Contoh: Mobile Apps Gateway" required>
            <div class="invalid-feedback" id="createApiLabelError">Label wajib diisi.</div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="createApiEnv" class="form-label">Environment</label>
              <select class="form-control form-select" id="createApiEnv">
                <option value="development">Development</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
              </select>
            </div>
            <div class="col-6">
              <label for="createApiExpires" class="form-label">Masa Berlaku</label>
              <input type="datetime-local" class="form-control" id="createApiExpires">
              <div class="form-text fs-11 text-muted">Kosongkan jika permanen.</div>
            </div>
          </div>
          <div class="mb-3">
            <label for="createApiScopes" class="form-label">Hak Akses (Scopes)</label>
            <input type="text" class="form-control" id="createApiScopes" value="*" placeholder="* atau users.read, brand.read">
            <div class="form-text fs-11 text-muted">Gunakan <code>*</code> untuk akses penuh atau pisahkan dengan koma.</div>
          </div>
          <div class="mb-0">
            <label for="createApiAllowlist" class="form-label">IP Allowlist (Opsional)</label>
            <textarea class="form-control" id="createApiAllowlist" rows="2" placeholder="Satu IP / CIDR per baris, contoh:&#10;192.168.1.100&#10;10.0.0.0/8"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-wave btn-glare label-btn" id="btnSubmitCreateApiKey">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerCreateApi"></span>
            <i class="ri-add-line label-btn-icon me-1"></i>Buat API Key
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Ubah API Key -->
<div class="modal fade" id="modalEditApiKey" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-edit-line me-1 text-primary"></i>Ubah API Key</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="formEditApiKey" novalidate>
        <div class="modal-body">
          <input type="hidden" id="editApiKeyId">
          <div class="mb-3">
            <label class="form-label">Key ID</label>
            <input type="text" class="form-control font-monospace bg-light" id="editApiKeyIdDisplay" readonly>
          </div>
          <div class="mb-3">
            <label for="editApiLabel" class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editApiLabel" maxlength="100" autocomplete="off" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="editApiEnv" class="form-label">Environment</label>
              <select class="form-control form-select" id="editApiEnv">
                <option value="development">Development</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
              </select>
            </div>
            <div class="col-6">
              <label for="editApiExpires" class="form-label">Masa Berlaku</label>
              <input type="datetime-local" class="form-control" id="editApiExpires">
            </div>
          </div>
          <div class="mb-3">
            <label for="editApiScopes" class="form-label">Hak Akses (Scopes)</label>
            <input type="text" class="form-control" id="editApiScopes">
          </div>
          <div class="mb-0">
            <label for="editApiAllowlist" class="form-label">IP Allowlist</label>
            <textarea class="form-control" id="editApiAllowlist" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-wave btn-glare label-btn" id="btnSubmitEditApiKey">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerEditApi"></span>
            <i class="ri-save-line label-btn-icon me-1"></i>Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Tampilkan Secret API Key Baru (Sekali Saja) -->
<div class="modal fade" id="modalShowNewApiKey" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-key-2-line me-1 text-success"></i>Kredensial API Key Berhasil Dibuat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
          <i class="ri-error-warning-line fs-20"></i>
          <span class="fs-13">Secret token ini <strong>HANYA DITAMPILKAN SEKALI</strong>. Salin dan simpan di tempat yang aman sekarang!</span>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Token Kunci Lengkap (X-API-Key)</label>
          <div class="input-group">
            <input type="text" class="form-control font-monospace fs-12" id="displayNewApiKey" readonly>
            <button type="button" class="btn btn-outline-secondary" id="btnCopyApiKey" title="Salin ke clipboard">
              <i class="ri-file-copy-line"></i>
            </button>
          </div>
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold">Contoh Perintah cURL</label>
          <pre class="bg-dark text-light p-2 rounded fs-12 mb-0"><code id="curlApiKeySnippet"></code></pre>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Saya Sudah Menyimpan Secret</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Buat Basic Auth Client Baru -->
<div class="modal fade" id="modalCreateBasic" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-user-add-line me-1 text-primary"></i>Buat Basic Auth Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="formCreateBasic" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <label for="createBasicUsername" class="form-label">Username Klien <span class="text-danger">*</span></label>
            <input type="text" class="form-control font-monospace" id="createBasicUsername" maxlength="100" autocomplete="off" placeholder="contoh: external_service_01" required>
            <div class="invalid-feedback" id="createBasicUsernameError">Username wajib diisi (alfanumerik, garis bawah, strip).</div>
          </div>
          <div class="mb-3">
            <label for="createBasicLabel" class="form-label">Label Deskripsi</label>
            <input type="text" class="form-control" id="createBasicLabel" maxlength="100" placeholder="Contoh: Integrasi Pembayaran Bank">
          </div>
          <div class="mb-3">
            <label for="createBasicPassword" class="form-label">Password Klien <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="createBasicPassword" minlength="8" placeholder="Minimal 8 karakter" required>
            <div class="invalid-feedback" id="createBasicPasswordError">Password minimal 8 karakter.</div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="createBasicEnv" class="form-label">Environment</label>
              <select class="form-control form-select" id="createBasicEnv">
                <option value="development">Development</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
              </select>
            </div>
            <div class="col-6">
              <label for="createBasicExpires" class="form-label">Masa Berlaku</label>
              <input type="datetime-local" class="form-control" id="createBasicExpires">
            </div>
          </div>
          <div class="mb-0">
            <label for="createBasicScopes" class="form-label">Hak Akses (Scopes)</label>
            <input type="text" class="form-control" id="createBasicScopes" value="*" placeholder="* atau brand.read, users.read">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-wave btn-glare label-btn" id="btnSubmitCreateBasic">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerCreateBasic"></span>
            <i class="ri-add-line label-btn-icon me-1"></i>Buat Klien
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Ubah Basic Auth Client -->
<div class="modal fade" id="modalEditBasic" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-edit-line me-1 text-primary"></i>Ubah Basic Auth Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="formEditBasic" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control font-monospace bg-light" id="editBasicUsernameDisplay" readonly>
          </div>
          <div class="mb-3">
            <label for="editBasicLabel" class="form-label">Label Deskripsi</label>
            <input type="text" class="form-control" id="editBasicLabel" maxlength="100">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="editBasicEnv" class="form-label">Environment</label>
              <select class="form-control form-select" id="editBasicEnv">
                <option value="development">Development</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
              </select>
            </div>
            <div class="col-6">
              <label for="editBasicExpires" class="form-label">Masa Berlaku</label>
              <input type="datetime-local" class="form-control" id="editBasicExpires">
            </div>
          </div>
          <div class="mb-0">
            <label for="editBasicScopes" class="form-label">Hak Akses (Scopes)</label>
            <input type="text" class="form-control" id="editBasicScopes">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-wave btn-glare label-btn" id="btnSubmitEditBasic">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerEditBasic"></span>
            <i class="ri-save-line label-btn-icon me-1"></i>Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Reset Password Basic Client -->
<div class="modal fade" id="modalResetBasicPassword" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-key-line me-1 text-warning"></i>Reset Password Klien</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="formResetBasicPassword" novalidate>
        <div class="modal-body">
          <input type="hidden" id="resetBasicUsername">
          <p class="text-muted fs-13 mb-3">
            Mereset password untuk klien: <strong class="text-dark font-monospace" id="resetBasicUsernameDisplay">-</strong>
          </p>
          <div class="mb-0">
            <label for="resetBasicNewPassword" class="form-label">Password Baru <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="resetBasicNewPassword" minlength="8" placeholder="Minimal 8 karakter" required>
            <div class="invalid-feedback">Password minimal 8 karakter.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning btn-wave btn-glare label-btn" id="btnSubmitResetBasic">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerResetBasic"></span>
            <i class="ri-refresh-line label-btn-icon me-1"></i>Reset Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Tampilkan Password Baru Basic Client (Sekali Saja) -->
<div class="modal fade" id="modalShowBasicPassword" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-shield-check-line me-1 text-success"></i>Kredensial Basic Auth Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
          <i class="ri-error-warning-line fs-20"></i>
          <span class="fs-13">Password ditampilkan <strong>HANYA SEKALI</strong>. Salin sekarang!</span>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Username</label>
          <input type="text" class="form-control font-monospace" id="displayBasicUsername" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Password</label>
          <div class="input-group">
            <input type="text" class="form-control font-monospace" id="displayBasicPassword" readonly>
            <button type="button" class="btn btn-outline-secondary" id="btnCopyBasicPassword" title="Salin password">
              <i class="ri-file-copy-line"></i>
            </button>
          </div>
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold">Header Authorization HTTP</label>
          <pre class="bg-dark text-light p-2 rounded fs-12 mb-0"><code id="basicAuthHeaderSnippet"></code></pre>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Saya Sudah Menyimpan Password</button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     CLIENT JAVASCRIPT LOGIC
══════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  /* ── 1. Konstanta URL & Keadaan ── */
  const API_BASE   = <?php echo json_encode($apiBaseUrl); ?>;
  const OVERVIEW_URL = API_BASE + "/overview";
  const AUTH_URL     = API_BASE + "/auth";
  const EMAIL_URL    = API_BASE + "/email";
  const APIKEYS_URL  = API_BASE + "/api-keys";
  const BASIC_URL    = API_BASE + "/basic-auth";
  const ORIGIN       = window.location.origin;

  const state = {
    apiKeys: { page: 1, perPage: 10, search: "", status: "", environment: "all" },
    basic:   { page: 1, perPage: 10, search: "", status: "", environment: "all" }
  };

  /* ── 2. Inisialisasi Modal Bootstrap ── */
  const modalCreateApiKey       = new bootstrap.Modal(document.getElementById("modalCreateApiKey"));
  const modalEditApiKey         = new bootstrap.Modal(document.getElementById("modalEditApiKey"));
  const modalShowNewApiKey      = new bootstrap.Modal(document.getElementById("modalShowNewApiKey"));
  const modalCreateBasic        = new bootstrap.Modal(document.getElementById("modalCreateBasic"));
  const modalEditBasic          = new bootstrap.Modal(document.getElementById("modalEditBasic"));
  const modalResetBasicPassword = new bootstrap.Modal(document.getElementById("modalResetBasicPassword"));
  const modalShowBasicPassword  = new bootstrap.Modal(document.getElementById("modalShowBasicPassword"));

  /* ── 3. Helper Functions ── */
  function esc(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function formatDate(str) {
    if (!str) return "-";
    return String(str).replace("T", " ").substring(0, 19);
  }

  function copyToClipboard(text, btnElement) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function () {
      if (btnElement) {
        const orig = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="ri-check-line me-1"></i>Tersalin!';
        btnElement.classList.replace("btn-outline-secondary", "btn-success");
        setTimeout(function () {
          btnElement.innerHTML = orig;
          btnElement.classList.replace("btn-success", "btn-outline-secondary");
        }, 2000);
      }
      FMS.toast("Berhasil disalin ke clipboard.", true);
    }).catch(function () {
      FMS.toast("Gagal menyalin ke clipboard.", false);
    });
  }

  function renderMetricCard(title, count, subtitle, iconClass, colorClass) {
    return `
      <div class="col-sm-6 col-xl-3">
        <div class="card border mb-0">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted fs-13">${esc(title)}</span>
              <span class="avatar avatar-sm bg-${colorClass}-transparent rounded">
                <i class="${iconClass} fs-18"></i>
              </span>
            </div>
            <h5 class="fw-bold mb-1 text-${colorClass}">${esc(count)}</h5>
            <span class="text-muted fs-12">${esc(subtitle)}</span>
          </div>
        </div>
      </div>
    `;
  }

  /* ── 4. OVERVIEW ── */
  function loadOverview() {
    FMS.get(OVERVIEW_URL).then(function (body) {
      const data = (body && body.data !== undefined) ? body.data : (body || {});

      const envText = (data.environment || "-").toUpperCase();
      document.getElementById("ovEnv").textContent = envText;

      const apis = data.api_keys || {};
      const apiActive = Number(apis.active || 0);
      const apiTotal  = Number(apis.total || 0);
      const apiRev    = Number(apis.revoked || 0);
      document.getElementById("ovApiActive").textContent  = apiActive.toLocaleString("id-ID");
      document.getElementById("ovApiTotal").textContent   = apiTotal.toLocaleString("id-ID");
      document.getElementById("ovApiRevoked").textContent = apiRev.toLocaleString("id-ID");
      document.getElementById("badgeTotalApiKeys").textContent = String(apiTotal);

      const basic = data.basic_auth || {};
      const basicActive = Number(basic.active || 0);
      const basicTotal  = Number(basic.total || 0);
      const basicLocked = Number(basic.locked || 0);
      const basicRev    = Number(basic.revoked || 0);
      document.getElementById("ovBasicActive").textContent  = basicActive.toLocaleString("id-ID");
      document.getElementById("ovBasicLocked").textContent  = basicLocked.toLocaleString("id-ID");
      document.getElementById("ovBasicRevoked").textContent = basicRev.toLocaleString("id-ID");
      document.getElementById("badgeTotalBasicClients").textContent = String(basicTotal);

      const auth = data.auth_settings || {};
      const regEnabled = Boolean(Number(auth.public_registration_enabled));
      const emailRequired = Boolean(Number(auth.public_email_verification_required));

      const ovPublicRegEl = document.getElementById("ovPublicReg");
      ovPublicRegEl.textContent = regEnabled ? "Diizinkan" : "Dinonaktifkan";
      ovPublicRegEl.className = "fw-bold mb-1 " + (regEnabled ? "text-success" : "text-muted");

      const ovEmailVerifEl = document.getElementById("ovEmailVerif");
      if (emailRequired && regEnabled) {
        const ttlMin = Number(auth.verification_ttl_minutes || 1440);
        const coolSec = Number(auth.resend_cooldown_seconds || 120);
        const ttlText = ttlMin >= 60 ? (ttlMin / 60).toFixed(0) + " jam" : ttlMin + " menit";
        const coolText = coolSec >= 60 ? (coolSec / 60).toFixed(0) + " menit" : coolSec + " detik";
        ovEmailVerifEl.textContent = "Verifikasi wajib — TTL " + ttlText + " / jeda kirim " + coolText;
      } else if (emailRequired) {
        ovEmailVerifEl.textContent = "Wajib verifikasi email (pendaftaran dimatikan)";
      } else {
        ovEmailVerifEl.textContent = "Tanpa verifikasi email";
      }
    }).catch(function (err) {
      console.error("Gagal memuat overview:", err);
    });
  }

  /* ── 5. AUTH SETTINGS ── */
  function syncLoginRateLimitOptions() {
    const enabled = document.getElementById("setLoginRateLimit").checked;
    document.querySelectorAll("#loginRateLimitOptions input").forEach(function (input) {
      input.disabled = !enabled;
    });
  }

  function loadAuthSettings() {
    FMS.get(AUTH_URL).then(function (body) {
      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const s = data.auth_settings || {};

      document.getElementById("setPublicReg").checked        = Boolean(Number(s.public_registration_enabled));
      document.getElementById("setPublicEmailVerif").checked  = Boolean(Number(s.public_email_verification_required));
      document.getElementById("setAdminEmailVerif").checked   = Boolean(Number(s.admin_created_email_verification_required));
      document.getElementById("setAdminMustChangePassword").checked = Boolean(Number(s.admin_must_change_password));
      document.getElementById("setLoginRateLimit").checked = Number(s.login_rate_limit_enabled ?? 1) === 1;
      document.getElementById("setLoginMaxFailures").value = Number(s.login_max_failures) || 5;
      document.getElementById("setLoginFailureWindow").value = Number(s.login_failure_window_seconds) || 900;
      document.getElementById("setLoginLockout").value = Number(s.login_lockout_seconds) || 900;
      syncLoginRateLimitOptions();
      document.getElementById("setTtl").value          = Number(s.verification_ttl_minutes) || 1440;
      document.getElementById("setCooldown").value     = Number(s.resend_cooldown_seconds) || 120;
      document.getElementById("authSettingsVersion").textContent = "Versi: " + (s.version || 1);
      document.getElementById("authSettingsUpdatedAt").textContent = formatDate(s.updated_at);
    }).catch(function (err) {
      FMS.toast(err.message || "Gagal memuat kebijakan autentikasi.", false);
    });
  }

  document.getElementById("authSettingsForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner = document.getElementById("authSaveSpinner");
    const btn     = document.getElementById("btnSaveAuthSettings");

    spinner.classList.remove("d-none");
    btn.disabled = true;

    const payload = {
      public_registration_enabled:               document.getElementById("setPublicReg").checked ? 1 : 0,
      public_email_verification_required:        document.getElementById("setPublicEmailVerif").checked ? 1 : 0,
      admin_created_email_verification_required:  document.getElementById("setAdminEmailVerif").checked ? 1 : 0,
      admin_must_change_password:                document.getElementById("setAdminMustChangePassword").checked ? 1 : 0,
      login_rate_limit_enabled:                  document.getElementById("setLoginRateLimit").checked ? 1 : 0,
      login_max_failures:                        parseInt(document.getElementById("setLoginMaxFailures").value, 10),
      login_failure_window_seconds:              parseInt(document.getElementById("setLoginFailureWindow").value, 10),
      login_lockout_seconds:                     parseInt(document.getElementById("setLoginLockout").value, 10),
      verification_ttl_minutes: parseInt(document.getElementById("setTtl").value, 10),
      resend_cooldown_seconds:  parseInt(document.getElementById("setCooldown").value, 10)
    };

    FMS.put(AUTH_URL, payload).then(function () {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast("Kebijakan autentikasi berhasil disimpan.", true);
      loadAuthSettings();
      loadOverview();
    }).catch(function (err) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast(err.message || "Gagal menyimpan kebijakan autentikasi.", false);
    });
  });

  document.getElementById("btnResetAuthSettings").addEventListener("click", function () {
    loadAuthSettings();
  });

  document.getElementById("setLoginRateLimit").addEventListener("change", syncLoginRateLimitOptions);
  syncLoginRateLimitOptions();

  /* ── 6. EMAIL / SMTP SETTINGS ── */
  function loadEmailSettings() {
    FMS.get(EMAIL_URL).then(function (body) {
      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const s = data.email_settings || {};
      document.getElementById("setEmailEnabled").checked = Boolean(Number(s.enabled));
      document.getElementById("setEmailProtocol").value = s.protocol || "smtp";
      document.getElementById("setSmtpHost").value = s.smtp_host || "";
      document.getElementById("setSmtpPort").value = Number(s.smtp_port) || 587;
      document.getElementById("setSmtpUser").value = s.smtp_user || "";
      document.getElementById("setSmtpPassword").value = "";
      document.getElementById("setSmtpCrypto").value = s.smtp_crypto || "tls";
      document.getElementById("setFromEmail").value = s.from_email || "";
      document.getElementById("setFromName").value = s.from_name || "";
      document.getElementById("setReplyTo").value = s.reply_to || "";
      document.getElementById("setEmailTimeout").value = Number(s.timeout_seconds) || 10;
      document.getElementById("smtpPasswordHint").textContent = s.smtp_password_configured
        ? "Password SMTP sudah tersimpan terenkripsi. Biarkan kosong untuk tidak mengubah."
        : "Password SMTP belum dikonfigurasi.";
      document.getElementById("emailSettingsVersion").textContent = "Versi: " + (s.version || 1);
      document.getElementById("emailSettingsUpdatedAt").textContent = formatDate(s.updated_at);
    }).catch(function (err) {
      FMS.toast(err.message || "Gagal memuat konfigurasi email.", false);
    });
  }

  document.getElementById("emailSettingsForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner = document.getElementById("emailSaveSpinner");
    const btn = document.getElementById("btnSaveEmailSettings");
    const payload = {
      enabled: document.getElementById("setEmailEnabled").checked ? 1 : 0,
      protocol: document.getElementById("setEmailProtocol").value,
      smtp_host: document.getElementById("setSmtpHost").value.trim(),
      smtp_port: parseInt(document.getElementById("setSmtpPort").value, 10),
      smtp_user: document.getElementById("setSmtpUser").value.trim(),
      smtp_password: document.getElementById("setSmtpPassword").value,
      smtp_crypto: document.getElementById("setSmtpCrypto").value,
      from_email: document.getElementById("setFromEmail").value.trim(),
      from_name: document.getElementById("setFromName").value.trim(),
      reply_to: document.getElementById("setReplyTo").value.trim(),
      timeout_seconds: parseInt(document.getElementById("setEmailTimeout").value, 10)
    };
    spinner.classList.remove("d-none");
    btn.disabled = true;
    FMS.put(EMAIL_URL, payload).then(function () {
      FMS.toast("Konfigurasi email berhasil disimpan.", true);
      loadEmailSettings();
    }).catch(function (err) {
      FMS.toast(err.message || "Gagal menyimpan konfigurasi email.", false);
    }).finally(function () {
      spinner.classList.add("d-none");
      btn.disabled = false;
    });
  });

  document.getElementById("btnResetEmailSettings").addEventListener("click", loadEmailSettings);
  document.getElementById("btnTestEmail").addEventListener("click", function () {
    const recipient = document.getElementById("setTestRecipient").value.trim();
    if (!recipient) {
      FMS.toast("Email tujuan tes wajib diisi.", false);
      return;
    }
    const spinner = document.getElementById("emailTestSpinner");
    const btn = document.getElementById("btnTestEmail");
    spinner.classList.remove("d-none");
    btn.disabled = true;
    FMS.post(EMAIL_URL + "/test", { recipient_email: recipient }).then(function () {
      FMS.toast("Email tes berhasil dikirim.", true);
    }).catch(function (err) {
      FMS.toast(err.message || "Email tes gagal dikirim.", false);
    }).finally(function () {
      spinner.classList.add("d-none");
      btn.disabled = false;
    });
  });

  /* ── 7. API KEYS ── */
  function renderApiKeysSummary(items) {
    const total   = items.length;
    const active  = items.filter(function (i) { return i.status === "active"; }).length;
    const revoked = items.filter(function (i) { return i.status === "revoked"; }).length;
    const expired = items.filter(function (i) { return i.status === "expired"; }).length;

    document.getElementById("apiKeysSummaryRow").innerHTML =
      renderMetricCard("Total Ditampilkan", total, "Data di halaman ini", "ph-duotone ph-list-numbers", "primary") +
      renderMetricCard("Kunci Aktif", active, "Dapat digunakan", "ph-duotone ph-check-circle", "success") +
      renderMetricCard("Kunci Dicabut", revoked, "Tidak aktif permanen", "ph-duotone ph-prohibit", "danger") +
      renderMetricCard("Kedaluwarsa", expired, "Waktu berlaku habis", "ph-duotone ph-clock", "warning");
  }

  function renderApiKeys(items, pagination) {
    const tbody = document.getElementById("tbodyApiKeys");
    const footer = document.getElementById("card-footer-apiKeysPager");

    if (!items || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="ri-key-line fs-2 d-block mb-1"></i>Belum ada data API Key.</td></tr>';
      renderApiKeysSummary([]);
      footer.classList.add("d-none");
      return;
    }

    renderApiKeysSummary(items);

    tbody.innerHTML = items.map(function (item, idx) {
      const num = ((pagination.current_page - 1) * pagination.per_page) + idx + 1;
      let statusBadge = "";
      if (item.status === "active") {
        statusBadge = '<span class="badge bg-success-transparent">Aktif</span>';
      } else if (item.status === "revoked") {
        statusBadge = '<span class="badge bg-danger-transparent">Dicabut</span>';
      } else {
        statusBadge = '<span class="badge bg-warning-transparent">Expired</span>';
      }

      const scopesBadges = (item.scopes || []).map(function (s) {
        return `<span class="badge bg-secondary-transparent me-1">${esc(s)}</span>`;
      }).join("") || '<span class="text-muted">-</span>';

      let actionBtns = "";
      if (item.status === "revoked") {
        actionBtns += `<button type="button" class="btn btn-outline-success btn-glare btn-sm ms-1 js-api-activate" data-id="${esc(item.key_id)}" title="Aktifkan Kembali"><i class="ri-check-line"></i></button>`;
      } else {
        actionBtns += `<button type="button" class="btn btn-outline-warning btn-glare btn-sm ms-1 js-api-roll" data-id="${esc(item.key_id)}" title="Regenerate Secret"><i class="ri-refresh-line"></i></button>`;
        actionBtns += `<button type="button" class="btn btn-outline-secondary btn-glare btn-sm ms-1 js-api-revoke" data-id="${esc(item.key_id)}" title="Cabut Akses"><i class="ri-forbid-line"></i></button>`;
      }
      actionBtns += `<button type="button" class="btn btn-outline-primary btn-glare btn-sm ms-1 js-api-edit" data-id="${esc(item.key_id)}" title="Ubah"><i class="ri-pencil-line"></i></button>`;
      actionBtns += `<button type="button" class="btn btn-outline-danger btn-glare btn-sm ms-1 js-api-del" data-id="${esc(item.key_id)}" data-label="${esc(item.label)}" title="Hapus"><i class="ri-delete-bin-line"></i></button>`;

      return `
        <tr>
          <td class="text-muted small">${num}</td>
          <td>
            <div class="fw-semibold">${esc(item.label)}</div>
            <code class="fs-12 text-muted">${esc(item.key_id)}</code>
          </td>
          <td>${scopesBadges}</td>
          <td><span class="badge bg-primary-transparent">${esc(item.environment)}</span></td>
          <td class="text-center">${statusBadge}</td>
          <td class="small">${item.expires_at ? formatDate(item.expires_at) : '<span class="text-muted">Permanen</span>'}</td>
          <td class="small">${formatDate(item.last_used_at)}</td>
          <td class="text-end text-nowrap">${actionBtns}</td>
        </tr>
      `;
    }).join("");

    footer.classList.remove("d-none");
    FMS.pagination({
      target: "#paginationApiKeys",
      total: pagination.total,
      perPage: pagination.per_page,
      page: pagination.current_page,
      onChange: function (page) {
        state.apiKeys.page = page;
        loadApiKeys();
      }
    });
  }

  function loadApiKeys() {
    const query = new URLSearchParams({
      page: String(state.apiKeys.page),
      per_page: String(state.apiKeys.perPage),
      search: state.apiKeys.search,
      status: state.apiKeys.status,
      environment: state.apiKeys.environment
    });

    FMS.get(APIKEYS_URL + "?" + query.toString()).then(function (body) {
      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const items = data.items || [];
      const pagination = data.pagination || { current_page: 1, per_page: state.apiKeys.perPage, total: 0 };
      renderApiKeys(items, pagination);
    }).catch(function (err) {
      document.getElementById("tbodyApiKeys").innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">${esc(err.message || "Gagal memuat API Key.")}</td></tr>`;
      document.getElementById("card-footer-apiKeysPager").classList.add("d-none");
    });
  }

  /* ── 7. BASIC AUTH CLIENTS ── */
  function renderBasicSummary(items) {
    const total   = items.length;
    const active  = items.filter(function (i) { return i.status === "active"; }).length;
    const locked  = items.filter(function (i) { return i.status === "locked"; }).length;
    const revoked = items.filter(function (i) { return i.status === "revoked"; }).length;

    document.getElementById("basicSummaryRow").innerHTML =
      renderMetricCard("Total Ditampilkan", total, "Data di halaman ini", "ph-duotone ph-list-numbers", "primary") +
      renderMetricCard("Klien Aktif", active, "Bisa autentikasi", "ph-duotone ph-check-circle", "success") +
      renderMetricCard("Klien Terkunci", locked, "Salah sandi berulang", "ph-duotone ph-lock", "warning") +
      renderMetricCard("Klien Dicabut", revoked, "Dinonaktifkan", "ph-duotone ph-prohibit", "danger");
  }

  function renderBasicClients(items, pagination) {
    const tbody = document.getElementById("tbodyBasicClients");
    const footer = document.getElementById("card-footer-basicPager");

    if (!items || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="ri-user-switch-line fs-2 d-block mb-1"></i>Belum ada data Basic Client.</td></tr>';
      renderBasicSummary([]);
      footer.classList.add("d-none");
      return;
    }

    renderBasicSummary(items);

    tbody.innerHTML = items.map(function (item, idx) {
      const num = ((pagination.current_page - 1) * pagination.per_page) + idx + 1;
      let statusBadge = "";
      if (item.status === "active") {
        statusBadge = '<span class="badge bg-success-transparent">Aktif</span>';
      } else if (item.status === "locked") {
        statusBadge = `<span class="badge bg-danger-transparent"><i class="ri-lock-line me-1"></i>Terkunci (${item.failed_attempts}x)</span>`;
      } else if (item.status === "revoked") {
        statusBadge = '<span class="badge bg-secondary-transparent">Dicabut</span>';
      } else {
        statusBadge = '<span class="badge bg-warning-transparent">Expired</span>';
      }

      const scopesBadges = (item.scopes || []).map(function (s) {
        return `<span class="badge bg-secondary-transparent me-1">${esc(s)}</span>`;
      }).join("") || '<span class="text-muted">-</span>';

      let actionBtns = "";
      if (item.status === "locked") {
        actionBtns += `<button type="button" class="btn btn-outline-warning btn-glare btn-sm ms-1 js-basic-unlock" data-user="${esc(item.username)}" title="Buka Kunci Akun"><i class="ri-lock-unlock-line"></i></button>`;
      }
      if (item.status === "revoked") {
        actionBtns += `<button type="button" class="btn btn-outline-success btn-glare btn-sm ms-1 js-basic-activate" data-user="${esc(item.username)}" title="Aktifkan Kembali"><i class="ri-check-line"></i></button>`;
      } else {
        actionBtns += `<button type="button" class="btn btn-outline-secondary btn-glare btn-sm ms-1 js-basic-revoke" data-user="${esc(item.username)}" title="Cabut Akses"><i class="ri-forbid-line"></i></button>`;
      }
      actionBtns += `<button type="button" class="btn btn-outline-danger btn-glare btn-sm ms-1 js-basic-reset-pw" data-user="${esc(item.username)}" title="Reset Password"><i class="ri-key-line"></i></button>`;
      actionBtns += `<button type="button" class="btn btn-outline-primary btn-glare btn-sm ms-1 js-basic-edit" data-user="${esc(item.username)}" title="Ubah"><i class="ri-pencil-line"></i></button>`;
      actionBtns += `<button type="button" class="btn btn-outline-danger btn-glare btn-sm ms-1 js-basic-del" data-user="${esc(item.username)}" data-label="${esc(item.label || item.username)}" title="Hapus"><i class="ri-delete-bin-line"></i></button>`;

      return `
        <tr>
          <td class="text-muted small">${num}</td>
          <td>
            <div class="fw-semibold font-monospace">${esc(item.username)}</div>
            <small class="text-muted">${esc(item.label || "-")}</small>
          </td>
          <td>${scopesBadges}</td>
          <td><span class="badge bg-primary-transparent">${esc(item.environment)}</span></td>
          <td class="text-center">${statusBadge}</td>
          <td class="small">${item.expires_at ? formatDate(item.expires_at) : '<span class="text-muted">Permanen</span>'}</td>
          <td class="small">${formatDate(item.last_used_at)}</td>
          <td class="text-end text-nowrap">${actionBtns}</td>
        </tr>
      `;
    }).join("");

    footer.classList.remove("d-none");
    FMS.pagination({
      target: "#paginationBasicClients",
      total: pagination.total,
      perPage: pagination.per_page,
      page: pagination.current_page,
      onChange: function (page) {
        state.basic.page = page;
        loadBasicClients();
      }
    });
  }

  function loadBasicClients() {
    const query = new URLSearchParams({
      page: String(state.basic.page),
      per_page: String(state.basic.perPage),
      search: state.basic.search,
      status: state.basic.status,
      environment: state.basic.environment
    });

    FMS.get(BASIC_URL + "?" + query.toString()).then(function (body) {
      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const items = data.items || [];
      const pagination = data.pagination || { current_page: 1, per_page: state.basic.perPage, total: 0 };
      renderBasicClients(items, pagination);
    }).catch(function (err) {
      document.getElementById("tbodyBasicClients").innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">${esc(err.message || "Gagal memuat Basic Client.")}</td></tr>`;
      document.getElementById("card-footer-basicPager").classList.add("d-none");
    });
  }

  /* ── 8. Delegasi Aksi API Key (Roll, Revoke, Activate, Edit, Del) ── */
  document.getElementById("tbodyApiKeys").addEventListener("click", function (e) {
    const rollBtn = e.target.closest(".js-api-roll");
    const revBtn  = e.target.closest(".js-api-revoke");
    const actBtn  = e.target.closest(".js-api-activate");
    const editBtn = e.target.closest(".js-api-edit");
    const delBtn  = e.target.closest(".js-api-del");

    if (rollBtn) {
      const keyId = rollBtn.getAttribute("data-id");
      FMS.confirm({
        title: "Regenerate Secret API Key",
        message: `Regenerate secret untuk API Key "${keyId}"? Secret lama akan langsung tidak berlaku!`,
        confirmLabel: "Ya, Regenerate",
        loadingLabel: "Memproses...",
        variant: "warning",
        action: function () {
          return FMS.post(APIKEYS_URL + "/" + encodeURIComponent(keyId) + "/roll").then(function (body) {
            const data = (body && body.data !== undefined) ? body.data : (body || {});
            const presented = data.presented_key || "";
            document.getElementById("displayNewApiKey").value = presented;
            document.getElementById("curlApiKeySnippet").textContent =
              `curl -H "X-API-Key: ${presented}" \\\n  ${ORIGIN}/api/v1/brand`;
            modalShowNewApiKey.show();
            loadApiKeys();
            loadOverview();
          });
        }
      });
      return;
    }

    if (revBtn) {
      const keyId = revBtn.getAttribute("data-id");
      FMS.confirm({
        title: "Cabut API Key",
        message: `Cabut izin (revoke) API Key "${keyId}"? Token ini tidak dapat digunakan lagi.`,
        confirmLabel: "Ya, Cabut",
        loadingLabel: "Mencabut...",
        variant: "danger",
        action: function () {
          return FMS.post(APIKEYS_URL + "/" + encodeURIComponent(keyId) + "/revoke").then(function () {
            FMS.toast("API Key berhasil dicabut.", true);
            loadApiKeys();
            loadOverview();
          });
        }
      });
      return;
    }

    if (actBtn) {
      const keyId = actBtn.getAttribute("data-id");
      FMS.confirm({
        title: "Aktifkan Kembali API Key",
        message: `Aktifkan kembali API Key "${keyId}"?`,
        confirmLabel: "Ya, Aktifkan",
        loadingLabel: "Mengaktifkan...",
        variant: "success",
        action: function () {
          return FMS.post(APIKEYS_URL + "/" + encodeURIComponent(keyId) + "/activate").then(function () {
            FMS.toast("API Key berhasil diaktifkan kembali.", true);
            loadApiKeys();
            loadOverview();
          });
        }
      });
      return;
    }

    if (editBtn) {
      const keyId = editBtn.getAttribute("data-id");
      FMS.get(APIKEYS_URL + "/" + encodeURIComponent(keyId)).then(function (body) {
        const data = (body && body.data !== undefined) ? body.data : (body || {});
        const k = data.key || {};
        document.getElementById("editApiKeyId").value = k.key_id || keyId;
        document.getElementById("editApiKeyIdDisplay").value = k.key_id || keyId;
        document.getElementById("editApiLabel").value = k.label || "";
        document.getElementById("editApiEnv").value = k.environment || "development";
        document.getElementById("editApiScopes").value = (k.scopes || []).join(", ");
        document.getElementById("editApiAllowlist").value = (k.ip_allowlist || []).join("\n");
        document.getElementById("editApiExpires").value = k.expires_at ? k.expires_at.substring(0, 16) : "";
        modalEditApiKey.show();
      }).catch(function (err) {
        FMS.toast(err.message || "Gagal mengambil detail API Key.", false);
      });
      return;
    }

    if (delBtn) {
      const keyId = delBtn.getAttribute("data-id");
      const label = delBtn.getAttribute("data-label") || keyId;
      FMS.confirm({
        title: "Hapus Permanen API Key",
        message: `Hapus permanen API Key "${label}" (${keyId})? Tindakan ini tidak dapat dibatalkan.`,
        confirmLabel: "Ya, Hapus Permanen",
        loadingLabel: "Menghapus...",
        variant: "danger",
        action: function () {
          return FMS.del(APIKEYS_URL + "/" + encodeURIComponent(keyId)).then(function () {
            FMS.toast("API Key berhasil dihapus permanen.", true);
            loadApiKeys();
            loadOverview();
          });
        }
      });
      return;
    }
  });

  /* ── 9. Delegasi Aksi Basic Client (Unlock, Revoke, Activate, Reset, Edit, Del) ── */
  document.getElementById("tbodyBasicClients").addEventListener("click", function (e) {
    const unlockBtn  = e.target.closest(".js-basic-unlock");
    const revBtn     = e.target.closest(".js-basic-revoke");
    const actBtn     = e.target.closest(".js-basic-activate");
    const resetPwBtn = e.target.closest(".js-basic-reset-pw");
    const editBtn    = e.target.closest(".js-basic-edit");
    const delBtn     = e.target.closest(".js-basic-del");

    if (unlockBtn) {
      const username = unlockBtn.getAttribute("data-user");
      FMS.confirm({
        title: "Buka Kunci Basic Client",
        message: `Buka kunci akun "${username}" dan reset percobaan login?`,
        confirmLabel: "Buka Kunci",
        loadingLabel: "Membuka...",
        variant: "warning",
        action: function () {
          return FMS.post(BASIC_URL + "/" + encodeURIComponent(username) + "/unlock").then(function () {
            FMS.toast("Akun klien berhasil dibuka kuncinya.", true);
            loadBasicClients();
            loadOverview();
          });
        }
      });
      return;
    }

    if (revBtn) {
      const username = revBtn.getAttribute("data-user");
      FMS.confirm({
        title: "Cabut Basic Client",
        message: `Cabut izin akun "${username}"? Klien ini tidak dapat melakukan autentikasi lagi.`,
        confirmLabel: "Ya, Cabut",
        loadingLabel: "Mencabut...",
        variant: "danger",
        action: function () {
          return FMS.post(BASIC_URL + "/" + encodeURIComponent(username) + "/revoke").then(function () {
            FMS.toast("Klien berhasil dicabut.", true);
            loadBasicClients();
            loadOverview();
          });
        }
      });
      return;
    }

    if (actBtn) {
      const username = actBtn.getAttribute("data-user");
      FMS.confirm({
        title: "Aktifkan Kembali Basic Client",
        message: `Aktifkan kembali akun klien "${username}"?`,
        confirmLabel: "Ya, Aktifkan",
        loadingLabel: "Mengaktifkan...",
        variant: "success",
        action: function () {
          return FMS.post(BASIC_URL + "/" + encodeURIComponent(username) + "/activate").then(function () {
            FMS.toast("Klien berhasil diaktifkan kembali.", true);
            loadBasicClients();
            loadOverview();
          });
        }
      });
      return;
    }

    if (resetPwBtn) {
      const username = resetPwBtn.getAttribute("data-user");
      document.getElementById("resetBasicUsername").value = username;
      document.getElementById("resetBasicUsernameDisplay").textContent = username;
      document.getElementById("resetBasicNewPassword").value = "";
      modalResetBasicPassword.show();
      return;
    }

    if (editBtn) {
      const username = editBtn.getAttribute("data-user");
      FMS.get(BASIC_URL + "/" + encodeURIComponent(username)).then(function (body) {
        const data = (body && body.data !== undefined) ? body.data : (body || {});
        const c = data.client || {};
        document.getElementById("editBasicUsernameDisplay").value = c.username || username;
        document.getElementById("editBasicLabel").value = c.label || "";
        document.getElementById("editBasicEnv").value = c.environment || "development";
        document.getElementById("editBasicScopes").value = (c.scopes || []).join(", ");
        document.getElementById("editBasicExpires").value = c.expires_at ? c.expires_at.substring(0, 16) : "";
        modalEditBasic.show();
      }).catch(function (err) {
        FMS.toast(err.message || "Gagal mengambil data Basic Client.", false);
      });
      return;
    }

    if (delBtn) {
      const username = delBtn.getAttribute("data-user");
      const label    = delBtn.getAttribute("data-label") || username;
      FMS.confirm({
        title: "Hapus Permanen Basic Client",
        message: `Hapus permanen akun "${label}" (${username})? Tindakan ini tidak dapat dibatalkan.`,
        confirmLabel: "Ya, Hapus Permanen",
        loadingLabel: "Menghapus...",
        variant: "danger",
        action: function () {
          return FMS.del(BASIC_URL + "/" + encodeURIComponent(username)).then(function () {
            FMS.toast("Basic Client berhasil dihapus permanen.", true);
            loadBasicClients();
            loadOverview();
          });
        }
      });
      return;
    }
  });

  /* ── 10. Handler Form Create / Edit API Key ── */
  document.getElementById("btnOpenCreateApiKey").addEventListener("click", function () {
    document.getElementById("formCreateApiKey").reset();
    document.getElementById("createApiEnv").value = "development";
    document.getElementById("createApiScopes").value = "*";
    document.getElementById("createApiLabel").classList.remove("is-invalid");
    modalCreateApiKey.show();
  });

  document.getElementById("formCreateApiKey").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner = document.getElementById("spinnerCreateApi");
    const btn     = document.getElementById("btnSubmitCreateApiKey");

    const labelInput = document.getElementById("createApiLabel");
    if (!labelInput.value.trim()) {
      labelInput.classList.add("is-invalid");
      return;
    }
    labelInput.classList.remove("is-invalid");

    spinner.classList.remove("d-none");
    btn.disabled = true;

    const scopesRaw = document.getElementById("createApiScopes").value.trim() || "*";
    const scopes = scopesRaw === "*" ? "*" : scopesRaw.split(",").map(function (s) { return s.trim(); }).filter(Boolean);

    const payload = {
      label:        labelInput.value.trim(),
      environment:  document.getElementById("createApiEnv").value,
      expires_at:   document.getElementById("createApiExpires").value || null,
      scopes:       scopes,
      ip_allowlist: document.getElementById("createApiAllowlist").value.trim() || null
    };

    FMS.post(APIKEYS_URL, payload).then(function (body) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      modalCreateApiKey.hide();

      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const presentedKey = data.presented_key || "";

      document.getElementById("displayNewApiKey").value = presentedKey;
      document.getElementById("curlApiKeySnippet").textContent =
        `curl -H "X-API-Key: ${presentedKey}" \\\n  ${ORIGIN}/api/v1/brand`;

      modalShowNewApiKey.show();
      loadApiKeys();
      loadOverview();
    }).catch(function (err) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast(err.message || "Gagal membuat API Key.", false);
    });
  });

  document.getElementById("formEditApiKey").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner = document.getElementById("spinnerEditApi");
    const btn     = document.getElementById("btnSubmitEditApiKey");
    const keyId   = document.getElementById("editApiKeyId").value;

    spinner.classList.remove("d-none");
    btn.disabled = true;

    const scopesRaw = document.getElementById("editApiScopes").value.trim() || "*";
    const scopes = scopesRaw === "*" ? "*" : scopesRaw.split(",").map(function (s) { return s.trim(); }).filter(Boolean);

    const payload = {
      label:        document.getElementById("editApiLabel").value.trim(),
      environment:  document.getElementById("editApiEnv").value,
      expires_at:   document.getElementById("editApiExpires").value || null,
      scopes:       scopes,
      ip_allowlist: document.getElementById("editApiAllowlist").value.trim() || null
    };

    FMS.put(APIKEYS_URL + "/" + encodeURIComponent(keyId), payload).then(function () {
      spinner.classList.add("d-none");
      btn.disabled = false;
      modalEditApiKey.hide();
      FMS.toast("API Key berhasil diperbarui.", true);
      loadApiKeys();
    }).catch(function (err) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast(err.message || "Gagal memperbarui API Key.", false);
    });
  });

  document.getElementById("btnCopyApiKey").addEventListener("click", function () {
    copyToClipboard(document.getElementById("displayNewApiKey").value, this);
  });

  /* ── 11. Handler Form Create / Edit Basic Client ── */
  document.getElementById("btnOpenCreateBasic").addEventListener("click", function () {
    document.getElementById("formCreateBasic").reset();
    document.getElementById("createBasicEnv").value = "development";
    document.getElementById("createBasicScopes").value = "*";
    document.getElementById("createBasicUsername").classList.remove("is-invalid");
    document.getElementById("createBasicPassword").classList.remove("is-invalid");
    modalCreateBasic.show();
  });

  document.getElementById("formCreateBasic").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner = document.getElementById("spinnerCreateBasic");
    const btn     = document.getElementById("btnSubmitCreateBasic");

    const usernameInput = document.getElementById("createBasicUsername");
    const passwordInput = document.getElementById("createBasicPassword");

    if (!usernameInput.value.trim()) {
      usernameInput.classList.add("is-invalid");
      return;
    }
    usernameInput.classList.remove("is-invalid");

    if (!passwordInput.value || passwordInput.value.length < 8) {
      passwordInput.classList.add("is-invalid");
      return;
    }
    passwordInput.classList.remove("is-invalid");

    spinner.classList.remove("d-none");
    btn.disabled = true;

    const scopesRaw = document.getElementById("createBasicScopes").value.trim() || "*";
    const scopes = scopesRaw === "*" ? "*" : scopesRaw.split(",").map(function (s) { return s.trim(); }).filter(Boolean);

    const payload = {
      username:    usernameInput.value.trim(),
      label:       document.getElementById("createBasicLabel").value.trim(),
      password:    passwordInput.value,
      environment: document.getElementById("createBasicEnv").value,
      expires_at:  document.getElementById("createBasicExpires").value || null,
      scopes:      scopes
    };

    FMS.post(BASIC_URL, payload).then(function (body) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      modalCreateBasic.hide();

      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const client = data.client || {};
      const plainPw = data.plain_password || payload.password;
      const uname = client.username || payload.username;

      document.getElementById("displayBasicUsername").value = uname;
      document.getElementById("displayBasicPassword").value = plainPw;

      const b64 = btoa(uname + ":" + plainPw);
      document.getElementById("basicAuthHeaderSnippet").textContent =
        `Authorization: Basic ${b64}\n\n` +
        `# cURL Contoh:\ncurl -u "${uname}:${plainPw}" \\\n  ${ORIGIN}/api/v1/brand`;

      modalShowBasicPassword.show();
      loadBasicClients();
      loadOverview();
    }).catch(function (err) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast(err.message || "Gagal membuat Basic Client.", false);
    });
  });

  document.getElementById("formEditBasic").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner = document.getElementById("spinnerEditBasic");
    const btn     = document.getElementById("btnSubmitEditBasic");
    const username = document.getElementById("editBasicUsernameDisplay").value;

    spinner.classList.remove("d-none");
    btn.disabled = true;

    const scopesRaw = document.getElementById("editBasicScopes").value.trim() || "*";
    const scopes = scopesRaw === "*" ? "*" : scopesRaw.split(",").map(function (s) { return s.trim(); }).filter(Boolean);

    const payload = {
      label:       document.getElementById("editBasicLabel").value.trim(),
      environment: document.getElementById("editBasicEnv").value,
      expires_at:  document.getElementById("editBasicExpires").value || null,
      scopes:      scopes
    };

    FMS.put(BASIC_URL + "/" + encodeURIComponent(username), payload).then(function () {
      spinner.classList.add("d-none");
      btn.disabled = false;
      modalEditBasic.hide();
      FMS.toast("Basic Client berhasil diperbarui.", true);
      loadBasicClients();
    }).catch(function (err) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast(err.message || "Gagal memperbarui Basic Client.", false);
    });
  });

  document.getElementById("formResetBasicPassword").addEventListener("submit", function (e) {
    e.preventDefault();
    const spinner  = document.getElementById("spinnerResetBasic");
    const btn      = document.getElementById("btnSubmitResetBasic");
    const username = document.getElementById("resetBasicUsername").value;
    const customPw = document.getElementById("resetBasicNewPassword").value.trim();

    if (!customPw || customPw.length < 8) {
      document.getElementById("resetBasicNewPassword").classList.add("is-invalid");
      return;
    }
    document.getElementById("resetBasicNewPassword").classList.remove("is-invalid");

    spinner.classList.remove("d-none");
    btn.disabled = true;

    FMS.post(BASIC_URL + "/" + encodeURIComponent(username) + "/reset-password", { password: customPw }).then(function (body) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      modalResetBasicPassword.hide();

      const data = (body && body.data !== undefined) ? body.data : (body || {});
      const plainPw = data.plain_password || customPw;

      document.getElementById("displayBasicUsername").value = username;
      document.getElementById("displayBasicPassword").value = plainPw;

      const b64 = btoa(username + ":" + plainPw);
      document.getElementById("basicAuthHeaderSnippet").textContent =
        `Authorization: Basic ${b64}\n\n` +
        `# cURL Contoh:\ncurl -u "${username}:${plainPw}" \\\n  ${ORIGIN}/api/v1/brand`;

      modalShowBasicPassword.show();
      loadBasicClients();
    }).catch(function (err) {
      spinner.classList.add("d-none");
      btn.disabled = false;
      FMS.toast(err.message || "Gagal mereset password.", false);
    });
  });

  document.getElementById("btnCopyBasicPassword").addEventListener("click", function () {
    copyToClipboard(document.getElementById("displayBasicPassword").value, this);
  });

  /* ── 12. Search & Filter Input Listeners ── */
  document.getElementById("searchApiKey").addEventListener("input", FMS.debounce(function (e) {
    state.apiKeys.search = e.target.value.trim();
    state.apiKeys.page = 1;
    loadApiKeys();
  }, 350));

  document.getElementById("filterApiEnv").addEventListener("change", function (e) {
    state.apiKeys.environment = e.target.value;
    state.apiKeys.page = 1;
    loadApiKeys();
  });

  document.getElementById("filterApiStatus").addEventListener("change", function (e) {
    state.apiKeys.status = e.target.value;
    state.apiKeys.page = 1;
    loadApiKeys();
  });

  document.getElementById("apiKeysPerPage").addEventListener("change", function (e) {
    state.apiKeys.perPage = parseInt(e.target.value, 10) || 10;
    state.apiKeys.page = 1;
    loadApiKeys();
  });

  document.getElementById("searchBasic").addEventListener("input", FMS.debounce(function (e) {
    state.basic.search = e.target.value.trim();
    state.basic.page = 1;
    loadBasicClients();
  }, 350));

  document.getElementById("filterBasicEnv").addEventListener("change", function (e) {
    state.basic.environment = e.target.value;
    state.basic.page = 1;
    loadBasicClients();
  });

  document.getElementById("filterBasicStatus").addEventListener("change", function (e) {
    state.basic.status = e.target.value;
    state.basic.page = 1;
    loadBasicClients();
  });

  document.getElementById("basicPerPage").addEventListener("change", function (e) {
    state.basic.perPage = parseInt(e.target.value, 10) || 10;
    state.basic.page = 1;
    loadBasicClients();
  });

  /* ── 13. Refresh All Button ── */
  document.getElementById("btnRefreshAll").addEventListener("click", function () {
    loadOverview();
    loadAuthSettings();
    loadEmailSettings();
    loadApiKeys();
    loadBasicClients();
    FMS.toast("Data konfigurasi berhasil disegarkan.", true);
  });

  /* ── 14. Tab Lazy Loading ── */
  document.getElementById("tab-overview-btn").addEventListener("shown.bs.tab", function () {
    loadOverview();
  });

  document.getElementById("tab-auth-btn").addEventListener("shown.bs.tab", function () {
    loadAuthSettings();
  });

  document.getElementById("tab-email-btn").addEventListener("shown.bs.tab", function () {
    loadEmailSettings();
  });

  document.getElementById("tab-api-keys-btn").addEventListener("shown.bs.tab", function () {
    loadApiKeys();
  });

  document.getElementById("tab-basic-auth-btn").addEventListener("shown.bs.tab", function () {
    loadBasicClients();
  });

  /* ── 15. Inisialisasi Pertama Kali ── */
  loadOverview();
  loadAuthSettings();
  loadApiKeys();
  loadBasicClients();
});
</script>
