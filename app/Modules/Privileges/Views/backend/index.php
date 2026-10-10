<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
      <h4 class="mb-1">Hak Akses Pengguna</h4>
      <small class="text-muted">Pilih kelompok pengguna, lalu tentukan menu dan fitur yang boleh digunakan.</small>
    </div>
    <button id="savePrivileges" class="btn btn-primary btn-glare btn-wave label-btn" type="button">
      <i class="ri-save-line label-btn-icon me-2"></i>Simpan
    </button>
  </div>

  <div class="card-body">
    <div id="privilegesError" class="alert alert-danger d-none"></div>
    <div class="alert alert-info">
      <div class="fw-semibold mb-1">Cara kerja</div>
      <div><strong>Boleh dibuka</strong> menentukan menu tampil atau tidak. <strong>Hal yang boleh dilakukan</strong> menentukan tombol yang tampil di dalam menu, misalnya Tambah, Ubah, Hapus, atau Ekspor.</div>
    </div>
    <div class="row g-3 align-items-end mb-4">
      <div class="col-lg-4">
        <label class="form-label fw-semibold" for="privilegesGroup">Kelompok pengguna</label>
        <select id="privilegesGroup" class="form-select"></select>
      </div>
      <div class="col-lg-8">
        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
          <button id="allowAllMenus" class="btn btn-outline-secondary btn-sm" type="button">Bolehkan semua fitur</button>
          <button id="clearAllMenus" class="btn btn-outline-secondary btn-sm" type="button">Matikan semua fitur</button>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-nowrap" style="min-width:230px">Fitur</th>
            <th class="text-center text-nowrap" style="width:120px">Boleh dibuka</th>
            <th class="text-nowrap" style="min-width:420px">Hal yang boleh dilakukan</th>
          </tr>
        </thead>
        <tbody id="privilegesMatrix">
          <tr>
            <td colspan="3" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat privileges...</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div id="privilegesSystem" class="mt-4"></div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    var BASE_URL = <?php echo json_encode(site_url('api/v1/privileges'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var WILDCARD_KEY = '*';
    var ACTION_LABELS = {
      read: 'Lihat',
      view: 'Lihat',
      create: 'Tambah',
      update: 'Ubah',
      delete: 'Hapus',
      restore: 'Pulihkan',
      export: 'Unduh laporan',
      reset_password: 'Atur ulang kata sandi',
      verify_email: 'Verifikasi email',
      unverify_email: 'Batalkan verifikasi email',
      unlock: 'Buka akun terkunci',
      reorder: 'Ubah urutan',
      change_status: 'Ubah status',
      read_sessions: 'Lihat sesi masuk',
      revoke_sessions: 'Keluarkan dari perangkat',
      read_groups: 'Lihat kelompok',
      assign_groups: 'Atur kelompok',
      manage: 'Kelola semua',
      change_password: 'Ubah Password',
      update_avatar: 'Ubah Avatar',
    };
    var groups = [];
    var permissions = [];
    var menus = [];
    var menuPermissions = [];
    var selectedPermissionHashes = [];
    var lockedPermissionHashes = [];

    var groupSelect = document.getElementById('privilegesGroup');
    var matrixBody = document.getElementById('privilegesMatrix');
    var systemBox = document.getElementById('privilegesSystem');
    var errorBox = document.getElementById('privilegesError');
    var saveButton = document.getElementById('savePrivileges');

    function showError(message) {
      errorBox.textContent = message;
      errorBox.classList.remove('d-none');
    }

    function hideError() {
      errorBox.textContent = '';
      errorBox.classList.add('d-none');
    }

    function permissionKey(permission) {
      return String((permission && permission.permission_key) || '').toLowerCase();
    }

    function permissionByHash(hash) {
      return permissions.filter(function(permission) {
        return permission.hash === hash;
      })[0] || null;
    }

    function mappingsForMenu(menuHash) {
      return menuPermissions.filter(function(mapping) {
        return mapping.menu_id === menuHash;
      });
    }

    function isSelectedHash(hash) {
      return selectedPermissionHashes.indexOf(hash) !== -1;
    }

    function isLockedHash(hash) {
      return lockedPermissionHashes.indexOf(hash) !== -1;
    }

    function gatePermissionsForMenu(requiredMappings) {
      /* Izin dasar pembuka menu: is_system = 1 */
      return requiredMappings
        .map(function(mapping) {
          return permissionByHash(mapping.permission_id);
        })
        .filter(function(p) {
          return p && Number(p.is_system) === 1;
        });
    }

    function actionPermissionsForMenu(requiredMappings) {
      /* Hal yang boleh dilakukan: Tambah, Ubah, Hapus, lalu tombol khusus lain. */
      var priorities = { create: 1, update: 2, delete: 3 };

      return requiredMappings
        .map(function(mapping) {
          return permissionByHash(mapping.permission_id);
        })
        .filter(function(p) {
          return p && Number(p.is_system) === 0 && permissionKey(p) !== WILDCARD_KEY;
        })
        .sort(function(a, b) {
          var actionA = String(a.action_name || '').toLowerCase();
          var actionB = String(b.action_name || '').toLowerCase();
          var priorityA = priorities[actionA] || 4;
          var priorityB = priorities[actionB] || 4;
          if (priorityA !== priorityB) return priorityA - priorityB;

          return Number(a.id || 0) - Number(b.id || 0);
        });
    }

    function actionLabel(permission) {
      var action = String(permission.action_name || '').toLowerCase();
      if (ACTION_LABELS[action]) return ACTION_LABELS[action];

      return String(permission.action_name || permission.permission_key || '')
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function checkboxMarkup(hash, className, label, attributes) {
      return '<input class="form-check-input ' + className + '" type="checkbox"' +
        ' value="' + FMS.escape(hash) + '"' +
        ' aria-label="' + FMS.escape(label) + '"' +
        (isSelectedHash(hash) || isLockedHash(hash) ? ' checked' : '') +
        (isLockedHash(hash) ? ' disabled' : '') +
        (attributes || '') + '>';
    }

    function menuDepth(menu) {
      var depth = 0;
      var parentId = menu.id_parent;
      var visited = {};

      while (parentId && depth < 8 && !visited[String(parentId)]) {
        visited[String(parentId)] = true;
        depth += 1;
        var parent = menus.filter(function(candidate) {
          return String(candidate.id) === String(parentId);
        })[0];
        parentId = parent ? parent.id_parent : null;
      }

      return depth;
    }

    function renderMatrix() {
      matrixBody.innerHTML = menus.map(function(menu) {
        var depth = menuDepth(menu);
        var requiredMappings = mappingsForMenu(menu.hash);
        var requiredHashes = requiredMappings.map(function(mapping) {
          return mapping.permission_id;
        });
        var gatePerms = gatePermissionsForMenu(requiredMappings);
        var actionPermissions = actionPermissionsForMenu(requiredMappings);

        var gateHash = gatePerms.length ? gatePerms[0].hash : (requiredHashes.length ? requiredHashes[0] : '');
        var menuToggle = gateHash ?
          '<div class="form-check form-switch d-flex justify-content-center">' +
          checkboxMarkup(gateHash, 'js-menu-access', 'Buka ' + menu.name, ' role="switch" data-menu="' + FMS.escape(menu.hash) + '"') +
          '</div>' :
          '<span class="badge bg-light text-secondary">Kelompok menu</span>';

        var actions = actionPermissions.length ?
          '<div class="d-flex flex-wrap gap-3">' + actionPermissions.map(function(permission) {
            var id = 'permission-' + permission.hash + '-' + menu.hash;
            return '<div class="form-check">' +
              checkboxMarkup(permission.hash, 'js-action-permission', permission.permission_key, ' id="' + id + '" data-menu="' + FMS.escape(menu.hash) + '"') +
              '<label class="form-check-label" for="' + id + '">' + FMS.escape(actionLabel(permission)) + '</label>' +
              '</div>';
          }).join('') + '</div>' :
          '<span class="text-muted">Tidak ada pilihan tambahan.</span>';

        return '<tr><td><div class="fw-semibold d-flex align-items-center" style="padding-left:' + (depth * 22) + 'px">' +
          (menu.id_parent ? '<span class="text-muted me-2">&#8627;</span>' : '') +
          FMS.escape(menu.name) + '</div><small class="text-muted d-block" style="padding-left:' + (depth * 22) + 'px">' + FMS.escape(menu.url || '#') + '</small></td>' +
          '<td class="text-center">' + menuToggle + '</td><td>' + actions + '</td></tr>';
      }).join('') || '<tr><td colspan="3" class="text-center text-muted py-4">Belum ada menu.</td></tr>';

      renderSystemPermissions();
    }

    function renderSystemPermissions() {
      systemBox.innerHTML = '';
    }

    function loadGroupPermissions() {
      var groupHash = groupSelect.value;
      if (!groupHash) {
        selectedPermissionHashes = [];
        lockedPermissionHashes = [];
        renderMatrix();
        return;
      }

      matrixBody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat privileges...</td></tr>`;
      systemBox.innerHTML = '';

      FMS.get(BASE_URL + '/groups/' + groupHash + '/permissions')
        .then(function(data) {
          selectedPermissionHashes = (data.items || [])
            .filter(function(row) {
              return (row.effect || 'allow') === 'allow';
            })
            .map(function(row) {
              return row.permission_id;
            });
          lockedPermissionHashes = permissions
            .filter(function(permission) {
              return permissionKey(permission) === WILDCARD_KEY && selectedPermissionHashes.indexOf(permission.hash) !== -1;
            })
            .map(function(permission) {
              return permission.hash;
            });
          renderMatrix();
        })
        .catch(function(error) {
          matrixBody.innerHTML = '';
          showError(error.message);
        });
    }

    function loadOverview() {
      hideError();
      FMS.get(BASE_URL + '/overview')
        .then(function(data) {
          groups = data.groups || [];
          permissions = data.permissions || [];
          menus = data.menus || [];
          menuPermissions = data.menu_permissions || [];
          groupSelect.innerHTML = groups.map(function(group) {
            return '<option value="' + FMS.escape(group.hash) + '">' + FMS.escape(group.name) + '</option>';
          }).join('');
          loadGroupPermissions();
        })
        .catch(function(error) {
          matrixBody.innerHTML = '';
          showError(error.message);
        });
    }

    function setAllFeatures(checked) {
      Array.prototype.forEach.call(document.querySelectorAll('.js-menu-access, .js-action-permission'), function(toggle) {
        if (toggle.disabled) return;
        toggle.checked = checked;
      });
    }

    document.getElementById('allowAllMenus').addEventListener('click', function() {
      setAllFeatures(true);
    });
    document.getElementById('clearAllMenus').addEventListener('click', function() {
      setAllFeatures(false);
    });
    groupSelect.addEventListener('change', loadGroupPermissions);

    saveButton.addEventListener('click', function() {
      var groupHash = groupSelect.value;
      if (!groupHash) return;

      var hashes = lockedPermissionHashes.slice();
      Array.prototype.forEach.call(document.querySelectorAll('.js-menu-access, .js-action-permission'), function(box) {
        if (box.checked && hashes.indexOf(box.value) === -1) hashes.push(box.value);
      });

      var mappings = hashes.map(function(hash) {
        return {
          permission_id: hash,
          effect: 'allow'
        };
      });
      saveButton.disabled = true;
      var originalSaveHtml = saveButton.innerHTML;
      saveButton.innerHTML = '<span class="spinner-border spinner-border-sm label-btn-icon me-2"></span>Menyimpan...';
      FMS.put(BASE_URL + '/groups/' + groupHash + '/permissions', {
          permissions: mappings
        })
        .then(function() {
          FMS.toast('Privileges berhasil disimpan.', true);
          return loadGroupPermissions();
        })
        .catch(function(error) {
          FMS.toast(error.message, false);
        })
        .then(function() {
          saveButton.disabled = false;
          saveButton.innerHTML = originalSaveHtml;
        });
    });

    loadOverview();
  });
</script>