<?php
$permissions = array_map('strval', $backendPermissions ?? []);
$can = static fn (string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
?>
<div class="row">
  <div class="col-lg-12">
    <div class="box mb-3">
      <h3 class="fw-bold mb-1">Uploads</h3>
      <p class="text-muted mb-0">
        Upload gambar JPEG, PNG, atau WebP yang dikonversi menjadi WebP statis.
      </p>
    </div>
    <div class="box">
      <form id="fmsUploadForm" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="fmsUploadImage">Gambar</label>
            <input type="file" class="form-control" id="fmsUploadImage" name="image" accept="image/jpeg,image/png,image/webp" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="fmsUploadFolder">Folder</label>
            <input type="text" class="form-control" id="fmsUploadFolder" name="folder" value="fms_images" pattern="fms_[a-z0-9_-]+" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="fmsUploadOwnerType">Jenis Pemilik</label>
            <input type="text" class="form-control" id="fmsUploadOwnerType" name="owner_type" maxlength="64" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="fmsUploadOwnerIdentifier">ID Pemilik</label>
            <input type="number" min="1" class="form-control" id="fmsUploadOwnerIdentifier" name="owner_id" required>
          </div>
        </div>
        <?php if ($can('uploads.create')): ?>
        <div class="d-flex justify-content-end mt-3">
          <button type="submit" class="btn btn-primary">Upload Gambar</button>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
