<?php

/**
 * Mandatory password change page — seragam dengan auth/login.php.
 *
 * Dipicu setelah login bila user.must_change_password=1 ATAU
 * kebijakan admin mengharuskan semua user buatan admin wajib ganti password.
 *
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @license    FMS Signature
 */

$changePasswordUrl = (string) ($changePasswordUrl ?? site_url('api/v1/profile/force-change-password'));
$dashboardUrl     = (string) ($dashboardUrl ?? site_url(ROUTE_ADMIN . '/dashboard'));
$csrfToken       = csrf_hash();
?>

<!-- Auth page theme toggle — single pill button, auto-syncs with backend system -->
<div id="fms-auth-theme-switcher" class="fms-auth-theme-switcher" role="group" aria-label="Tema">
  <button type="button" class="fms-auth-theme-switcher__btn" id="switcher-light-theme" title="Dark mode" aria-label="Switch to dark mode">
    <i class="ti ti-moon" aria-hidden="true"></i>
  </button>
  <button type="button" class="fms-auth-theme-switcher__btn d-none" id="switcher-dark-theme" title="Light mode" aria-label="Switch to light mode">
    <i class="ti ti-sun" aria-hidden="true"></i>
  </button>
</div>

<style>
  .fms-auth-theme-switcher {
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1080;
    display: flex;
    border: 1px solid rgba(var(--primary-rgb), 0.25);
    border-radius: 999px;
    overflow: hidden;
    background-color: var(--custom-white);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
  }

  [data-theme-mode="dark"] .fms-auth-theme-switcher {
    background-color: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.18);
  }

  .fms-auth-theme-switcher__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    margin: 0;
    padding: 0;
    border: none;
    background-color: transparent;
    color: var(--text-muted);
    cursor: pointer;
    transition: color 0.2s ease, background-color 0.2s ease;
  }

  .fms-auth-theme-switcher__btn:hover {
    color: rgb(var(--primary-rgb));
    background-color: rgba(var(--primary-rgb), 0.08);
  }

  [data-theme-mode="dark"] .fms-auth-theme-switcher__btn {
    color: rgba(255, 255, 255, 0.5);
  }

  [data-theme-mode="dark"] .fms-auth-theme-switcher__btn:hover {
    color: #ffffff;
    background-color: rgba(255, 255, 255, 0.1);
  }

  .fms-auth-theme-switcher__btn:first-child { border-radius: 999px 0 0 999px; }
  .fms-auth-theme-switcher__btn:last-child  { border-radius: 0 999px 999px 0; }

  .fms-auth-theme-switcher .ti { font-size: 1rem; line-height: 1; }

  .mandatory-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background-color: rgba(var(--warning-rgb), 0.1);
    border: 1px solid rgba(var(--warning-rgb), 0.3);
    border-radius: 999px;
    color: rgb(var(--warning-rgb));
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }
</style>

<script>
  (function () {
    var lightBtn = document.getElementById('switcher-light-theme');
    var darkBtn  = document.getElementById('switcher-dark-theme');
    if (!lightBtn || !darkBtn) return;

    function isDark() {
      return document.documentElement.getAttribute('data-theme-mode') === 'dark';
    }

    function syncToggle() {
      if (isDark()) {
        lightBtn.classList.remove('d-none');
        darkBtn.classList.add('d-none');
      } else {
        lightBtn.classList.add('d-none');
        darkBtn.classList.remove('d-none');
      }
    }

    function toggleTheme() {
      var next = isDark() ? 'light' : 'dark';
      if (window.FMSTheme && typeof window.FMSTheme.setPreference === 'function') {
        window.FMSTheme.setPreference(next);
      } else {
        document.documentElement.setAttribute('data-theme-mode', next);
      }
      syncToggle();
      if (typeof switcherClick === 'function') switcherClick();
    }

    lightBtn.addEventListener('click', toggleTheme);
    darkBtn.addEventListener('click', toggleTheme);

    syncToggle();
  }());
</script>

<div class="row authentication authentication-cover-main mx-0">
  <div class="col-xxl-9 col-xl-9">
    <div class="row justify-content-center align-items-center h-100">
      <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
        <div class="card custom-card border-0 shadow-none my-4">
          <div class="card-body p-5">
            <!-- Mandatory badge -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="mandatory-badge mb-0">
                <i class="ti ti-lock" aria-hidden="true"></i>
                Ganti Password Wajib
              </div>
              <a href="<?php echo site_url('fms-auth/out'); ?>" class="btn btn-sm btn-outline-light text-default d-inline-flex align-items-center gap-1">
                <i class="ti ti-logout"></i> Logout
              </a>
            </div>

            <div>
              <h4 class="mb-1 fw-semibold">Password Harus Diubah</h4>
              <p class="mb-3 text-muted fw-normal">
                Password Anda saat ini tidak memenuhi kebijakan keamanan. Silakan buat password baru untuk melanjutkan.
              </p>
            </div>

            <div
              id="change-pw-alert"
              class="alert d-none"
              role="alert"
              aria-live="polite"></div>

            <form
              id="change-pw-form"
              autocomplete="off"
              novalidate
              data-api-url="<?php echo esc($changePasswordUrl); ?>"
              data-redirect-url="<?php echo esc($dashboardUrl); ?>">
              <input type="hidden" name="<?php echo csrf_token(); ?>" value="<?php echo esc($csrfToken); ?>">

              <div class="row gy-3">
                <div class="col-xl-12">
                  <label for="new_password" class="form-label text-default">Password Baru</label>
                  <div class="position-relative">
                    <input
                      type="password"
                      name="new_password"
                      class="form-control"
                      id="new_password"
                      placeholder="Masukkan password baru"
                      autocomplete="new-password"
                      maxlength="128"
                      required>
                    <a href="javascript:void(0);" class="show-password-button text-muted"
                       onclick="createpassword('new_password', this)"
                       id="btn-toggle-new">
                      <i class="ri-eye-off-line align-middle"></i>
                    </a>
                  </div>
                  <div class="form-text fs-11">Minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.</div>
                </div>

                <div class="col-xl-12 mb-2">
                  <label for="confirm_password" class="form-label text-default">Konfirmasi Password Baru</label>
                  <div class="position-relative">
                    <input
                      type="password"
                      name="confirm_password"
                      class="form-control"
                      id="confirm_password"
                      placeholder="Ulangi password baru"
                      autocomplete="new-password"
                      maxlength="128"
                      required>
                    <a href="javascript:void(0);" class="show-password-button text-muted"
                       onclick="createpassword('confirm_password', this)"
                       id="btn-toggle-confirm">
                      <i class="ri-eye-off-line align-middle"></i>
                    </a>
                  </div>
                </div>

                <!-- Password strength indicator -->
                <div class="col-xl-12">
                  <div class="password-strength-meter mb-2" id="strength-meter" style="display:none">
                    <div class="d-flex gap-1">
                      <div class="strength-bar flex-fill" id="strength-bar-1" style="height:4px;background:#e2e8f0;border-radius:2px;"></div>
                      <div class="strength-bar flex-fill" id="strength-bar-2" style="height:4px;background:#e2e8f0;border-radius:2px;"></div>
                      <div class="strength-bar flex-fill" id="strength-bar-3" style="height:4px;background:#e2e8f0;border-radius:2px;"></div>
                      <div class="strength-bar flex-fill" id="strength-bar-4" style="height:4px;background:#e2e8f0;border-radius:2px;"></div>
                    </div>
                    <small class="text-muted" id="strength-label"></small>
                  </div>
                </div>
              </div>

              <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary" id="change-pw-submit">
                  <i class="ti ti-key me-1"></i>
                  Simpan &amp; Lanjutkan
                </button>
              </div>

              <div class="text-center mt-3">
                <small class="text-muted">
                  <i class="ti ti-info-circle me-1"></i>
                  Anda tidak bisa melewati halaman ini sebelum password diperbarui.
                </small>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xxl-3 col-xl-3 col-lg-12 d-xl-block d-none px-0">
    <div class="authentication-cover overflow-hidden">
      <div class="authentication-cover-logo">
        <a href="<?php echo site_url() ?>">
          <img src="<?php echo esc((string) (($appBrand['logo_light_url'] ?? '') ?: ($appBrand['logo_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))); ?>" alt="logo" class="desktop-dark">
        </a>
      </div>
      <div class="authentication-cover-background">
        <img src="<?php echo fmsAssets('img/auth', 'background.png') ?>" alt="">
      </div>
      <div class="authentication-cover-content">
        <div class="p-5">
          <h3 class="fw-semibold lh-base">Keamanan Akun</h3>
          <p class="mb-0 text-muted fw-medium">
            Password baru Anda akan langsung aktif setelah disimpan.<br>
            Gunakan kombinasi yang kuat dan mudah diingat.
          </p>
        </div>
        <div>
          <img src="<?php echo fmsAssets('img/auth', 'media-in.png') ?>" alt="" class="img-fluid">
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo base_url('assets/fms/js/change-password.js?v=' . time()); ?>"></script>
