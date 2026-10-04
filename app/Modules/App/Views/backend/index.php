<!-- Start:: Welcome Banner -->
<div class="row">
  <div class="col-xl-12">
    <div class="card custom-card overflow-hidden">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div>
            <h4 class="fw-semibold mb-1">
              Selamat Datang, <?php echo esc((string) (session()->get('fms_user_name') ?? session()->get('name') ?? 'Administrator')) ?>!
            </h4>
            <p class="text-muted mb-0">
              <?php echo esc((string) (($appBrand['tagline'] ?? '') ?: 'Platform Administrasi Terintegrasi')) ?> —
              <?php echo esc((string) (($appBrand['name'] ?? '') ?: 'FMS App Starter')) ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- End:: Welcome Banner -->