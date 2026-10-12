<div class="row g-3" id="fmsStorageUsersRoot" data-api-url="<?= esc(site_url('api/v1/storage'), 'attr') ?>">
  <div class="col-12">
    <div class="card custom-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Storage Users</h5><small class="text-muted">User terpisah dari FMS Admin, hak akses diatur per role</small>
        </div>
        <button id="fmsStorageUserCreate" class="btn btn-primary btn-sm"><i class="ph-duotone ph-user-plus me-1"></i>Tambah User</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Username</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Role</th>
                <th>Login Terakhir</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="fmsStorageUsers">
              <tr>
                <td colspan="6" class="text-center text-muted">Memuat...</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="fmsStorageUsersPagination" class="mt-3"></div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card custom-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Storage Roles</h5><small class="text-muted">Role berisi daftar permission</small>
        </div>
        <button id="fmsStorageRoleCreate" class="btn btn-primary btn-sm"><i class="ph-duotone ph-shield-plus me-1"></i>Tambah Role</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Role</th>
                <th>Permission</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="fmsStorageRoles">
              <tr>
                <td colspan="3" class="text-center text-muted">Memuat...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fmsStorageUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User Storage</h5><button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="fmsStorageUserForm">
          <div class="mb-3"><label class="form-label" for="fmsStorageUserUsername">Username</label><input class="form-control" id="fmsStorageUserUsername" name="username" required></div>
          <div class="mb-3"><label class="form-label" for="fmsStorageUserFullName">Nama</label><input class="form-control" id="fmsStorageUserFullName" name="full_name"></div>
          <div class="mb-3"><label class="form-label" for="fmsStorageUserEmail">Email</label><input class="form-control" id="fmsStorageUserEmail" name="email" type="email"></div>
          <div class="mb-3"><label class="form-label" for="fmsStorageUserPassword">Password</label><input class="form-control" id="fmsStorageUserPassword" name="password" type="password"><small class="text-muted">Minimal 12 karakter</small></div>
          <div class="mb-3"><label class="form-label" for="fmsStorageUserStatus">Status</label><select class="form-select" id="fmsStorageUserStatus" name="status"><option value="active">Aktif</option><option value="suspended">Suspend</option></select></div>
          <div class="mb-2"><label class="form-label">Role</label><div id="fmsStorageUserRoles" class="d-flex flex-column gap-1"></div></div>
        </form>
      </div>
      <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="fmsStorageUserSave">Simpan</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="fmsStorageRoleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Role Storage</h5><button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="fmsStorageRoleForm">
          <div class="mb-3"><label class="form-label" for="fmsStorageRoleName">Nama Role</label><input class="form-control" id="fmsStorageRoleName" name="name" required placeholder="contoh: uploader"></div>
          <div class="mb-2"><label class="form-label">Permission</label>
            <div id="fmsStorageRolePermissions" class="d-flex flex-column gap-1"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="fmsStorageRoleSave">Simpan</button></div>
    </div>
  </div>
</div>