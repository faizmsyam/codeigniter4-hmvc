<?php

namespace App\Modules\Authentication\Views\auth;

$verificationStatus = (string) ($verificationStatus ?? 'invalid');
$isVerified = $verificationStatus === 'verified';
?>

<div class="container-fluid">
  <div class="row authentication mx-0">
    <div class="col-xxl-7 col-xl-7 col-lg-12">
      <div class="row justify-content-center align-items-center h-100">
        <div class="col-xxl-6 col-xl-7 col-lg-7 col-md-7 col-sm-8 col-12">
          <div class="p-4">
            <div class="mb-4 text-center">
              <span class="avatar avatar-xxl rounded-circle <?php echo $isVerified ? 'bg-success-transparent' : 'bg-danger-transparent'; ?>">
                <i class="<?php echo $isVerified ? 'ri-mail-check-line text-success' : 'ri-mail-close-line text-danger'; ?> fs-1"></i>
              </span>
            </div>
            <div class="text-center mb-4">
              <h4 class="mb-2 fw-semibold"><?php echo $isVerified ? 'Email berhasil diverifikasi' : 'Verifikasi email gagal'; ?></h4>
              <p class="text-muted">
                <?php echo $isVerified
                  ? 'Alamat email Anda telah aktif. Silakan masuk menggunakan akun Anda.'
                  : 'Link verifikasi tidak valid, kedaluwarsa, atau sudah pernah digunakan.'; ?>
              </p>
            </div>
            <div class="d-grid">
              <a href="<?php echo site_url('fms-auth/in'); ?>" class="btn btn-primary">Kembali ke Halaman Login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xxl-5 col-xl-5 col-lg-5 d-xl-block d-none px-0">
      <div class="authentication-cover">
        <div class="aunthentication-cover-content rounded">
          <div class="swiper keyboard-control">
            <div class="swiper-wrapper">
              <div class="swiper-slide"><div class="text-fixed-white text-center p-5 d-flex align-items-center justify-content-center"><div><div class="mb-4"><i class="ri-shield-check-line fs-1"></i></div><h5 class="fw-semibold">Keamanan Akun</h5><p class="fw-normal fs-14 op-7">Verifikasi email melindungi identitas dan akses akun Anda.</p></div></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
