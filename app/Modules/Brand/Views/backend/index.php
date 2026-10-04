<?php
$permissions = array_map('strval', $backendPermissions ?? []);
$can = static fn (string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
?>
<div class="row">
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-0">Pengaturan Brand</h4>
        <small class="text-muted">Kelola identitas brand aplikasi (nama, kontak, logo)</small>
      </div>
      <span class="badge bg-primary-transparent" id="brandUpdatedAt" style="display:none"></span>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-8">
    <div class="card custom-card">
      <div class="card-header">
        <div class="card-title">Data Brand</div>
      </div>
      <div class="card-body">
        <form id="brandForm" novalidate>
          <h6 class="fw-semibold mb-3"><i class="ri-building-4-line me-1"></i> Identitas Utama</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="brandName">Nama Brand <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="brandName" name="name" maxlength="100" autocomplete="organization" required>
              <div class="invalid-feedback" id="brandNameError">Nama brand wajib diisi.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brandTagline">Tagline</label>
              <input type="text" class="form-control" id="brandTagline" name="tagline" maxlength="190" placeholder="Contoh: Solusi digital terpercaya">
              <div class="invalid-feedback" id="brandTaglineError"></div>
            </div>
            <div class="col-12">
              <label class="form-label" for="brandDescription">Deskripsi</label>
              <textarea class="form-control" id="brandDescription" name="description" rows="3" maxlength="2000" placeholder="Deskripsi singkat organisasi atau aplikasi"></textarea>
              <div class="invalid-feedback" id="brandDescriptionError"></div>
            </div>
          </div>

          <hr class="my-4">
          <h6 class="fw-semibold mb-3"><i class="ri-contacts-line me-1"></i> Kontak dan Lokasi</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="brandEmail">Email</label>
              <input type="email" class="form-control" id="brandEmail" name="email" maxlength="190" autocomplete="email">
              <div class="invalid-feedback" id="brandEmailError">Email brand tidak valid.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brandPhone">Telepon</label>
              <input type="text" class="form-control" id="brandPhone" name="phone" maxlength="40" autocomplete="tel">
              <div class="invalid-feedback" id="brandPhoneError">Nomor telepon brand tidak valid.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brandWhatsapp">WhatsApp</label>
              <input type="text" class="form-control" id="brandWhatsapp" name="whatsapp" maxlength="40" placeholder="628123456789">
              <div class="invalid-feedback" id="brandWhatsappError"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brandWebsite">Website</label>
              <input type="url" class="form-control" id="brandWebsite" name="website_url" maxlength="255" placeholder="https://contoh.com">
              <div class="invalid-feedback" id="brandWebsiteError"></div>
            </div>
            <div class="col-12">
              <label class="form-label" for="brandAddress">Alamat</label>
              <textarea class="form-control" id="brandAddress" name="address" rows="3" maxlength="1000"></textarea>
              <div class="invalid-feedback" id="brandAddressError"></div>
            </div>
          </div>

          <hr class="my-4">
          <h6 class="fw-semibold mb-3"><i class="ri-share-line me-1"></i> Media Sosial</h6>
          <div class="row g-3">
            <?php foreach ([
              'facebook' => ['Facebook', 'ri-facebook-circle-line'],
              'instagram' => ['Instagram', 'ri-instagram-line'],
              'twitter' => ['X / Twitter', 'ri-twitter-x-line'],
              'youtube' => ['YouTube', 'ri-youtube-line'],
              'linkedin' => ['LinkedIn', 'ri-linkedin-box-line'],
              'tiktok' => ['TikTok', 'ri-tiktok-line'],
            ] as $socialKey => [$socialLabel, $socialIcon]): ?>
            <div class="col-md-6">
              <label class="form-label" for="brandSocial<?php echo ucfirst($socialKey) ?>"><i class="<?php echo $socialIcon ?> me-1"></i><?php echo $socialLabel ?></label>
              <input type="url" class="form-control" id="brandSocial<?php echo ucfirst($socialKey) ?>" name="social_<?php echo $socialKey ?>" maxlength="255" placeholder="https://">
              <div class="invalid-feedback" id="brandSocial<?php echo ucfirst($socialKey) ?>Error"></div>
            </div>
            <?php endforeach; ?>
          </div>

          <hr class="my-4">
          <h6 class="fw-semibold mb-3"><i class="ri-image-line me-1"></i> Logo dan Ikon</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="brandLogo">Logo Utama</label>
              <input type="file" class="form-control" id="brandLogo" name="logo" accept="image/jpeg,image/png,image/webp">
              <small class="text-muted">JPEG/PNG/WebP.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="brandLogoLight">Logo Light</label>
              <input type="file" class="form-control" id="brandLogoLight" name="logo_light" accept="image/jpeg,image/png,image/webp">
              <small class="text-muted">Versi untuk latar gelap.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="brandFavicon">Favicon</label>
              <input type="file" class="form-control" id="brandFavicon" name="favicon" accept="image/jpeg,image/png,image/webp">
              <small class="text-muted">Ikon tab browser.</small>
            </div>
          </div>

          <?php if ($can('brand.update')): ?>
          <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-light" id="brandResetBtn"><i class="ri-refresh-line me-1"></i>Muat Ulang</button>
            <button type="submit" class="btn btn-primary" id="brandSaveBtn">
              <span class="spinner-border spinner-border-sm d-none me-1" id="brandSaveSpinner"></span>
              Simpan Brand
            </button>
          </div>
          <?php endif; ?>
        </form>
        <div class="alert alert-info mt-3 d-none" id="brandMessage" role="status"></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card custom-card">
      <div class="card-header">
        <div class="card-title">Pratinjau Gambar</div>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="avatar avatar-lg avatar-rounded bg-light" style="padding: 0.5rem;" id="brandLogoPreviewWrap">
            <img src="" alt="Logo" id="brandLogoPreview" style="display:none;max-width:100%;max-height:64px;object-fit:contain">
            <i class="ri-image-line fs-3 text-muted" id="brandLogoPlaceholder"></i>
          </span>
          <div>
            <div class="fw-semibold">Logo</div>
            <small class="text-muted d-block" id="brandLogoName">Belum ada logo</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="avatar avatar-lg avatar-rounded bg-dark" style="padding: 0.5rem;" id="brandLogoLightPreviewWrap">
            <img src="" alt="Logo Light" id="brandLogoLightPreview" style="display:none;max-width:100%;max-height:64px;object-fit:contain">
            <i class="ri-image-line fs-3 text-muted" id="brandLogoLightPlaceholder"></i>
          </span>
          <div>
            <div class="fw-semibold">Logo Light</div>
            <small class="text-muted d-block" id="brandLogoLightName">Belum ada logo light</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="avatar avatar-md avatar-rounded bg-light" style="padding: 0.5rem;" id="brandFaviconPreviewWrap">
            <img src="" alt="Favicon" id="brandFaviconPreview" style="display:none;max-width:100%;max-height:32px;object-fit:contain">
            <i class="ri-image-line fs-4 text-muted" id="brandFaviconPlaceholder"></i>
          </span>
          <div>
            <div class="fw-semibold">Favicon</div>
            <small class="text-muted d-block" id="brandFaviconName">Belum ada favicon</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const DATA_URL   = <?php echo json_encode(site_url('api/v1/brand'), JSON_UNESCAPED_SLASHES); ?>;
  const UPDATE_URL = DATA_URL;
  const UPLOAD_URL = <?php echo json_encode(site_url('api/v1/brand/images'), JSON_UNESCAPED_SLASHES); ?>;
  const CAN_UPDATE = <?php echo $can('brand.update') ? 'true' : 'false'; ?>;

  const ALL_FIELDS = [
    'brandName', 'brandTagline', 'brandDescription',
    'brandEmail', 'brandPhone', 'brandWhatsapp', 'brandWebsite', 'brandAddress',
    'brandSocialFacebook', 'brandSocialInstagram', 'brandSocialTwitter',
    'brandSocialYoutube', 'brandSocialLinkedin', 'brandSocialTiktok'
  ];

  const FIELD_MAP = {
    name: 'brandName',
    tagline: 'brandTagline',
    description: 'brandDescription',
    email: 'brandEmail',
    phone: 'brandPhone',
    whatsapp: 'brandWhatsapp',
    website_url: 'brandWebsite',
    address: 'brandAddress',
    'social_links.facebook': 'brandSocialFacebook',
    'social_links.instagram': 'brandSocialInstagram',
    'social_links.twitter': 'brandSocialTwitter',
    'social_links.youtube': 'brandSocialYoutube',
    'social_links.linkedin': 'brandSocialLinkedin',
    'social_links.tiktok': 'brandSocialTiktok',
    social_facebook: 'brandSocialFacebook',
    social_instagram: 'brandSocialInstagram',
    social_twitter: 'brandSocialTwitter',
    social_youtube: 'brandSocialYoutube',
    social_linkedin: 'brandSocialLinkedin',
    social_tiktok: 'brandSocialTiktok',
    logo_path: 'brandLogo',
    logo_light_path: 'brandLogoLight',
    favicon_path: 'brandFavicon'
  };

  function payloadData(response) {
    return response && response.data ? response.data : response;
  }

  function normalizePayload(response) {
    const payload = payloadData(response);
    return payload && payload.brand ? payload : (payload || {});
  }

  function setPreview(imgId, placeholderId, nameId, imageUrl, fileName) {
    const img = document.getElementById(imgId);
    const placeholder = document.getElementById(placeholderId);
    if (imageUrl) {
      img.src = imageUrl;
      img.style.display = '';
      if (placeholder) placeholder.style.display = 'none';
    } else {
      img.removeAttribute('src');
      img.style.display = 'none';
      if (placeholder) placeholder.style.display = '';
    }
    if (nameId) {
      document.getElementById(nameId).textContent = fileName || (imageUrl ? 'Sudah terpasang' : 'Belum ada');
    }
  }

  function showMessage(text, isError) {
    const box = document.getElementById('brandMessage');
    box.classList.remove('d-none', 'alert-info', 'alert-danger');
    box.classList.add(isError ? 'alert-danger' : 'alert-info');
    box.textContent = text;
  }

  function fillForm(brand) {
    if (!brand) return;
    document.getElementById('brandName').value        = brand.name || '';
    document.getElementById('brandTagline').value     = brand.tagline || '';
    document.getElementById('brandDescription').value = brand.description || '';
    document.getElementById('brandEmail').value       = brand.email || '';
    document.getElementById('brandPhone').value       = brand.phone || '';
    document.getElementById('brandWhatsapp').value    = brand.whatsapp || '';
    document.getElementById('brandWebsite').value     = brand.website_url || '';
    document.getElementById('brandAddress').value     = brand.address || '';

    const socials = brand.social_links || {};
    document.getElementById('brandSocialFacebook').value  = socials.facebook || '';
    document.getElementById('brandSocialInstagram').value = socials.instagram || '';
    document.getElementById('brandSocialTwitter').value   = socials.twitter || '';
    document.getElementById('brandSocialYoutube').value   = socials.youtube || '';
    document.getElementById('brandSocialLinkedin').value  = socials.linkedin || '';
    document.getElementById('brandSocialTiktok').value    = socials.tiktok || '';

    setPreview('brandLogoPreview', 'brandLogoPlaceholder', 'brandLogoName', brand.logo_url || '', brand.logo_path || '');
    setPreview('brandLogoLightPreview', 'brandLogoLightPlaceholder', 'brandLogoLightName', brand.logo_light_url || '', brand.logo_light_path || '');
    setPreview('brandFaviconPreview', 'brandFaviconPlaceholder', 'brandFaviconName', brand.favicon_url || '', brand.favicon_path || '');

    const badge = document.getElementById('brandUpdatedAt');
    if (brand.updated_at) {
      badge.style.display = '';
      badge.textContent = 'Diperbarui: ' + brand.updated_at;
    }
  }

  function loadBrand() {
    FMS.get(DATA_URL).then(function (response) {
      const data = normalizePayload(response);
      if (data.brand) {
        fillForm(data.brand);
      }
    }).catch(function (error) {
      showMessage(error.message || 'Gagal memuat data brand.', true);
    });
  }

  function clearFieldErrors() {
    ALL_FIELDS.forEach(function (id) {
      const el = document.getElementById(id);
      if (el) el.classList.remove('is-invalid');
      const err = document.getElementById(id + 'Error');
      if (err) err.textContent = '';
    });
  }

  function showFieldErrors(errors) {
    Object.keys(errors || {}).forEach(function (field) {
      const inputId = FIELD_MAP[field] || field;
      const input = document.getElementById(inputId);
      if (!input) return;
      input.classList.add('is-invalid');
      const feedback = document.getElementById(inputId + 'Error');
      const msg = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
      if (feedback && msg) {
        feedback.textContent = String(msg);
      }
    });
  }

  document.getElementById('brandResetBtn')?.addEventListener('click', function () {
    clearFieldErrors();
    loadBrand();
  });

  ['brandLogo', 'brandLogoLight', 'brandFavicon'].forEach(function (inputId) {
    document.getElementById(inputId)?.addEventListener('change', function (event) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      const objectUrl = URL.createObjectURL(file);
      if (inputId === 'brandLogo') {
        setPreview('brandLogoPreview', 'brandLogoPlaceholder', 'brandLogoName', objectUrl, file.name);
      } else if (inputId === 'brandLogoLight') {
        setPreview('brandLogoLightPreview', 'brandLogoLightPlaceholder', 'brandLogoLightName', objectUrl, file.name);
      } else {
        setPreview('brandFaviconPreview', 'brandFaviconPlaceholder', 'brandFaviconName', objectUrl, file.name);
      }
    });
  });

  function formPayload() {
    return {
      name: document.getElementById('brandName').value.trim(),
      tagline: document.getElementById('brandTagline').value.trim(),
      description: document.getElementById('brandDescription').value.trim(),
      email: document.getElementById('brandEmail').value.trim(),
      phone: document.getElementById('brandPhone').value.trim(),
      whatsapp: document.getElementById('brandWhatsapp').value.trim(),
      website_url: document.getElementById('brandWebsite').value.trim(),
      address: document.getElementById('brandAddress').value.trim(),
      social_links: {
        facebook: document.getElementById('brandSocialFacebook').value.trim(),
        instagram: document.getElementById('brandSocialInstagram').value.trim(),
        twitter: document.getElementById('brandSocialTwitter').value.trim(),
        youtube: document.getElementById('brandSocialYoutube').value.trim(),
        linkedin: document.getElementById('brandSocialLinkedin').value.trim(),
        tiktok: document.getElementById('brandSocialTiktok').value.trim()
      }
    };
  }

  function uploadSelectedImages() {
    const selectedInputs = [
      ['brandLogo', 'logo', 'logo_path'],
      ['brandLogoLight', 'logo_light', 'logo_light_path'],
      ['brandFavicon', 'favicon', 'favicon_path']
    ].filter(function (entry) {
      const input = document.getElementById(entry[0]);
      return input && input.files && input.files[0];
    });

    if (!selectedInputs.length) return Promise.resolve({});

    const uploadData = new FormData();
    selectedInputs.forEach(function (entry) {
      uploadData.append(entry[1], document.getElementById(entry[0]).files[0]);
    });

    return FMS.ajax({
      url: UPLOAD_URL,
      method: 'POST',
      data: uploadData
    }).then(function (uploadResponse) {
      const uploadedPaths = uploadResponse && uploadResponse.paths ? uploadResponse.paths : {};
      const mappedPaths = {};
      selectedInputs.forEach(function (entry) {
        if (uploadedPaths[entry[2]]) mappedPaths[entry[2]] = uploadedPaths[entry[2]];
      });
      if (Object.keys(mappedPaths).length !== selectedInputs.length) {
        throw new Error('Respons upload gambar brand tidak lengkap.');
      }
      return mappedPaths;
    });
  }

  document.getElementById('brandForm').addEventListener('submit', function (event) {
    event.preventDefault();
    if (!CAN_UPDATE) return;
    clearFieldErrors();

    const name = document.getElementById('brandName').value.trim();
    if (!name) {
      document.getElementById('brandName').classList.add('is-invalid');
      document.getElementById('brandNameError').textContent = 'Nama brand wajib diisi.';
      document.getElementById('brandName').focus();
      return;
    }

    const spinner = document.getElementById('brandSaveSpinner');
    const saveBtn = document.getElementById('brandSaveBtn');
    if (spinner) spinner.classList.remove('d-none');
    if (saveBtn) saveBtn.disabled = true;

    /* Brand JSON murni: gambar diunggah dulu, lalu object_key masuk payload. */
    uploadSelectedImages().then(function (uploadedPaths) {
      return FMS.ajax({
        url: UPDATE_URL,
        method: 'POST',
        contentType: 'application/json',
        data: Object.assign(formPayload(), uploadedPaths)
      });
    }).then(function (data) {
      const payload = payloadData({ data: data }) || data || {};
      if (payload.brand) fillForm(payload.brand);
      showMessage('Data brand berhasil disimpan.', false);
      FMS.toast('Data brand berhasil disimpan.', true);
    }).catch(function (error) {
      showMessage(error.message || 'Gagal menyimpan brand.', true);
      showFieldErrors(error.errors || {});
      FMS.toast(error.message || 'Gagal menyimpan brand.', false);
    }).finally(function () {
      if (spinner) spinner.classList.add('d-none');
      if (saveBtn) saveBtn.disabled = false;
    });
  });

  loadBrand();
});
</script>
