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