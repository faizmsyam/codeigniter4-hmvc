<?php
/**
 * Auth page theme toggle — seragam dengan backend (switcher offcanvas).
 *
 * Pakai radio button Light/Dark persis sama backend.
 * custom-switcher.min.js handle localStorage + data-theme-mode.
 *
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @license    FMS Signature
 */
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
      /* Toggle: current state dibalik */
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

    /* Sync initial state after DOM + FMSTheme.apply() from head.php */
    syncToggle();
  }());
</script>

<div class="row authentication authentication-cover-main mx-0">
  <div class="col-xxl-9 col-xl-9">
    <div class="row justify-content-center align-items-center h-100">
      <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
        <div class="card custom-card border-0 shadow-none my-4">
          <div class="card-body p-5">
            <div>
              <h4 class="mb-1 fw-semibold">Selamat datang kembali</h4>
              <p class="mb-4 text-muted fw-normal">Masuk menggunakan username atau email Anda.</p>
            </div>

            <div
              id="signin-alert"
              class="alert d-none"
              role="alert"
              aria-live="polite"></div>

            <?php if (session()->getFlashdata('logout_success')): ?>
              <div class="alert alert-success" role="alert">
                <?php echo esc((string) session()->getFlashdata('logout_success')) ?>
              </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('login_success')): ?>
              <div class="alert alert-success" role="alert">
                <?php echo esc((string) session()->getFlashdata('login_success')) ?>
              </div>
            <?php endif; ?>

            <form id="signin-form" autocomplete="on" novalidate>
              <?php echo csrf_field() ?>
              <div class="row gy-3">
                <div class="col-xl-12">
                  <label for="signin-identifier" class="form-label text-default">Username atau email</label>
                  <input
                    type="text"
                    name="identifier"
                    class="form-control"
                    id="signin-identifier"
                    value="<?php echo esc((string) old('identifier')) ?>"
                    placeholder="Masukkan username atau email"
                    autocomplete="username"
                    maxlength="191"
                    required>
                </div>
                <div class="col-xl-12 mb-2">
                  <label for="signin-password" class="form-label text-default d-block">Password</label>
                  <div class="position-relative">
                    <input
                      type="password"
                      name="password"
                      class="form-control"
                      id="signin-password"
                      placeholder="Masukkan password"
                      autocomplete="current-password"
                      maxlength="4096"
                      required>
                    <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('signin-password',this)" id="button-addon2">
                      <i class="ri-eye-off-line align-middle"></i>
                    </a>
                  </div>
                </div>
              </div>
              <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary" id="signin-submit">Masuk</button>
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
          <img src="<?php echo esc((string) (($appBrand['logo_light_url'] ?? '') ?: ($appBrand['logo_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))) ?>" alt="<?php echo esc((string) ($appBrand['name'] ?? 'FMS')) ?>" class="desktop-dark">
        </a>
      </div>
      <div class="authentication-cover-background">
        <img src="<?php echo fmsAssets('img/auth', 'background.png') ?>" alt="">
      </div>
      <div class="authentication-cover-content">
        <div class="p-5">
          <h3 class="fw-semibold lh-base"><?php echo esc((string) ($appBrand['name'] ?? 'FMS Admin Panel')) ?></h3>
          <p class="mb-0 text-muted fw-medium"><?php echo esc((string) (($appBrand['tagline'] ?? '') ?: 'Menu dan akses disesuaikan otomatis berdasarkan user group Anda.')) ?></p>
        </div>
        <div>
          <img src="<?php echo fmsAssets('img/auth', 'media-in.png') ?>" alt="" class="img-fluid">
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('signin-form');
    var alertBox = document.getElementById('signin-alert');
    var submitButton = document.getElementById('signin-submit');
    var identifierInput = document.getElementById('signin-identifier');
    var passwordInput = document.getElementById('signin-password');

    var loginUrl = <?php echo json_encode(site_url('api/v1/auth/login')) ?>;
    var dashboardUrl = <?php echo json_encode(site_url(ROUTE_ADMIN . '/dashboard')) ?>;

    function showAlert(message, level) {
      var variant = level === 'success' ? 'alert-success' : 'alert-danger';
      alertBox.className = 'alert ' + variant;
      alertBox.textContent = message;
    }

    function hideAlert() {
      alertBox.className = 'alert d-none';
      alertBox.textContent = '';
    }

    form.addEventListener('submit', function(event) {
      event.preventDefault();
      hideAlert();

      var identifier = (identifierInput.value || '').trim();
      var password = passwordInput.value || '';

      if (identifier === '' || password === '') {
        showAlert('Username/email dan password wajib diisi.', 'danger');
        return;
      }

      submitButton.disabled = true;

      FMS.ajax({
        url: loginUrl,
        method: 'POST',
        data: {
          identifier: identifier,
          password: password,
          device_label: navigator.userAgent ? navigator.userAgent.slice(0, 191) : 'web-admin'
        }
      }).then(function(payload) {
        var accessToken = payload && payload.access_token ? String(payload.access_token) : '';
        var tokenType = payload && payload.token_type ? String(payload.token_type) : 'Bearer';
        var redirectUrl = payload && payload.redirect_url ? String(payload.redirect_url) : dashboardUrl;

        try {
          sessionStorage.setItem('fms_access_token', accessToken);
          sessionStorage.setItem('fms_token_type', tokenType);
        } catch (storageError) {
          /* Token tetap tersimpan di httpOnly refresh cookie, lanjut redirect. */
        }

        showAlert('Login berhasil. Mengalihkan...', 'success');
        window.location.href = redirectUrl;
      }).catch(function(error) {
        var message = (error && error.message) ? String(error.message) : 'Login gagal. Coba lagi.';
        var status = error && typeof error.status !== 'undefined' ? Number(error.status) : 0;
        if (status === 429) {
          message = message || 'Terlalu banyak percobaan login. Coba lagi setelah 15 menit.';
        }
        showAlert(message, 'danger');
        submitButton.disabled = false;
        if (passwordInput) {
          passwordInput.value = '';
          passwordInput.focus();
        }
      });
    });
  });
</script>