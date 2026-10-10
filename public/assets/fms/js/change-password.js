(function () {
  'use strict';

  // ── DOM refs ────────────────────────────────────────────────────────────────
  var form          = document.getElementById('change-pw-form');
  var alertBox      = document.getElementById('change-pw-alert');
  var submitButton  = document.getElementById('change-pw-submit');
  var newPassInput  = document.getElementById('new_password');
  var confirmInput  = document.getElementById('confirm_password');
  var strengthMeter = document.getElementById('strength-meter');
  var strengthLabel = document.getElementById('strength-label');

  // ── Config (injected by controller via data-* attributes) ──────────────────
  var apiUrl     = form ? form.dataset.apiUrl     : '';
  var redirectUrl = form ? form.dataset.redirectUrl : '';

  // ── Helpers ───────────────────────────────────────────────────────────────
  function showAlert(message, level) {
    var variant = level === 'success' ? 'alert-success' : 'alert-danger';
    alertBox.className = 'alert ' + variant;
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
  }

  function hideAlert() {
    alertBox.className = 'alert d-none';
    alertBox.textContent = '';
  }

  function checkStrength(password) {
    var score = 0;
    if (password.length >= 8)  score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    return Math.min(score, 4);
  }

  function updateStrengthUI(password) {
    if (!strengthMeter) return;
    var strength = checkStrength(password);
    var levels = [
      { bars: 1, color: '#ef4444', label: 'Sangat lemah' },
      { bars: 1, color: '#f97316', label: 'Lemah' },
      { bars: 2, color: '#eab308', label: 'Sedang' },
      { bars: 3, color: '#22c55e', label: 'Kuat' },
      { bars: 4, color: '#10b981', label: 'Sangat kuat' },
    ];
    var lvl = levels[strength] || levels[0];
    strengthMeter.style.display = 'block';
    for (var i = 1; i <= 4; i++) {
      var bar = document.getElementById('strength-bar-' + i);
      if (bar) {
        bar.style.backgroundColor = i <= strength ? lvl.color : '#e2e8f0';
      }
    }
    strengthLabel.textContent = lvl.label;
    strengthLabel.style.color = lvl.color;
  }

  // ── Password input → strength meter ────────────────────────────────────────
  if (newPassInput) {
    newPassInput.addEventListener('input', function () {
      updateStrengthUI(this.value);
    });
  }

  // ── Submit ─────────────────────────────────────────────────────────────────
  if (!form) return;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    hideAlert();

    var newPass     = newPassInput  ? newPassInput.value  : '';
    var confirmPass = confirmInput ? confirmInput.value : '';

    if (newPass === '' || confirmPass === '') {
      showAlert('Semua kolom wajib diisi.', 'danger');
      return;
    }
    if (newPass !== confirmPass) {
      showAlert('Konfirmasi password tidak cocok.', 'danger');
      return;
    }

    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

    var accessToken = '';
    try { accessToken = sessionStorage.getItem('fms_access_token') || ''; } catch (e) {}

    var http = new XMLHttpRequest();
    http.open('POST', apiUrl, true);
    http.setRequestHeader('Content-Type', 'application/json');
    http.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (accessToken) {
      http.setRequestHeader('Authorization', 'Bearer ' + accessToken);
    }

    http.onreadystatechange = function () {
      if (http.readyState !== 4) return;

      submitButton.disabled = false;
      submitButton.innerHTML = '<i class="ti ti-key me-1"></i> Simpan &amp; Lanjutkan';

      var body = null;
      try { body = JSON.parse(http.responseText); } catch (e) { body = { message: http.responseText }; }

      if (http.status >= 400 || (body && body.status === false)) {
        showAlert(body && body.message ? body.message : 'Gagal (' + http.status + ')', 'danger');
        return;
      }

      var redirectTo = (body && body.data && body.data.redirect_url)
        ? String(body.data.redirect_url)
        : redirectUrl;
      showAlert('Password berhasil diperbarui. Mengalihkan...', 'success');
      submitButton.textContent = 'Berhasil!';
      submitButton.classList.remove('btn-primary');
      submitButton.classList.add('btn-success');
      setTimeout(function () { window.location.href = redirectTo; }, 1200);
    };

    http.onerror = function () {
      submitButton.disabled = false;
      submitButton.innerHTML = '<i class="ti ti-key me-1"></i> Simpan &amp; Lanjutkan';
      showAlert('Tidak dapat terhubung ke server.', 'danger');
    };

    http.send(JSON.stringify({ new_password: newPass, confirm_password: confirmPass }));
  });

})();
