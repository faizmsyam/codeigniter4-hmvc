<div class="row authentication authentication-cover-main mx-0">
  <div class="col-xxl-9 col-xl-9">
    <div class="row justify-content-center align-items-center h-100">
      <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
        <div class="card custom-card border-0 shadow-none my-4">
          <div class="card-body p-5">
            <div>
              <h4 class="mb-1 fw-semibold">Hi, Welcome back!</h4>
              <p class="mb-4 text-muted fw-normal">Please enter your credentials</p>
            </div>
            <div class="row gy-3">
              <div class="col-xl-12">
                <label for="signin-email" class="form-label text-default">Email</label>
                <input type="email" name="email" class="form-control" id="signin-email" placeholder="Enter Email">
              </div>
              <div class="col-xl-12 mb-2">
                <label for="signin-password" class="form-label text-default d-block">Password</label>
                <div class="position-relative">
                  <input type="password" name="password" class="form-control" id="signin-password" placeholder="Enter Password">
                  <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('signin-password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                </div>
              </div>
            </div>
            <div class="d-grid mt-3">
              <a href="<?php echo site_url(ROUTE_ADMIN) ?>" class="btn btn-primary">Sign In</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xxl-3 col-xl-3 col-lg-12 d-xl-block d-none px-0">
    <div class="authentication-cover overflow-hidden">
      <div class="authentication-cover-logo">
        <a href="<?php echo site_url() ?>">
          <img src="<?php echo assets('img/media', 'logo.png') ?>" alt="" class="desktop-dark">
        </a>
      </div>
      <div class="authentication-cover-background">
        <img src="<?php echo assets('img/auth', 'background.png') ?>" alt="">
      </div>
      <div class="authentication-cover-content">
        <div class="p-5">
          <h3 class="fw-semibold lh-base">Welcome to Dashboard</h3>
          <p class="mb-0 text-muted fw-medium">Manage your website and content with ease using our powerful admin tools.</p>
        </div>
        <div>
          <img src="<?php echo assets('img/auth', 'media-in.png') ?>" alt="" class="img-fluid">
        </div>
      </div>
    </div>
  </div>
</div>