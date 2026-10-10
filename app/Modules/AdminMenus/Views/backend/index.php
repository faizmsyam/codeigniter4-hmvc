<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */
$permissions = array_map('strval', $backendPermissions ?? []);
$can = static fn(string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
?>
<div class="card custom-card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h4 class="mb-0">Manajemen Menu</h4>
      <small class="text-muted">Kelola menu navigasi backend secara hierarkis</small>
    </div>

    <div class="w-100 w-sm-auto">
      <div class="d-flex flex-column flex-sm-row gap-2">
        <button type="button" class="btn btn-secondary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnRefresh"><i class="ri-refresh-line label-btn-icon me-2"></i> Refresh</button>

        <?php if ($can('menus.create')): ?>
          <button class="btn btn-primary btn-wave btn-glare label-btn w-100 w-sm-auto" id="btnAddRoot"><i class="ri-add-line label-btn-icon me-2"></i>Tambah Menu</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card-body pb-0">
    <div class="row g-3" id="menuSummaryRow"></div>
  </div>

  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="w-100 w-sm-auto">
      <div class="d-flex flex-column flex-sm-row gap-2">
        <div class="w-100">
          <input type="search" class="form-control"
            id="menuSearch" placeholder="Cari nama atau URL...">
        </div>
        <div class="w-100">
          <select class="form-control form-select" id="menuStatusFilter">
            <option value="">Semua status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
    </div>
    <div class="w-100 w-sm-auto">
      <div class="d-flex flex-column flex-sm-row gap-2">
        <button type="button" class="btn btn-teal-light btn-border-start btn-glare w-100 w-sm-auto" id="btnExpandAll">
          <i class="ri-arrow-down-s-line me-1"></i>Expand
        </button>
        <button type="button" class="btn btn-teal-light btn-border-start btn-glare w-100 w-sm-auto" id="btnCollapseAll">
          <i class="ri-arrow-up-s-line me-1"></i>Collapse
        </button>
      </div>
    </div>
  </div>

  <div class="card-body p-0">
    <div id="menuTreeError" class="alert alert-danger d-none"></div>

    <div class="table-responsive">
      <table class="table table-hover text-nowrap align-middle mb-0">
        <thead>
          <tr>
            <th>Nama</th>
            <th>URL</th>
            <th style="width:140px" class="text-center">Urutan</th>
            <th style="width:100px" class="text-center">Status</th>
            <th style="width:200px" class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody id="menuTreeBody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ================= MODAL TAMBAH / EDIT MENU ================= -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true" aria-labelledby="menuModalLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="menuModalLabel">Tambah Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <form id="menuForm" autocomplete="off">
        <input type="hidden" id="menuId">
        <input type="hidden" id="menuParentId">

        <div class="modal-body">

          <div id="menuFormError" data-fms-form-error class="alert alert-danger py-2 mb-3 d-none"></div>

          <div id="menuParentInfo" class="alert alert-info py-2 mb-3 d-none">
            <i class="ri-corner-down-right-line me-1"></i>
            Sub menu dari: <strong id="menuParentName"></strong>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="menuName">
              Nama <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="menuName"
              maxlength="100" placeholder="Contoh: Dashboard" required>
            <div class="invalid-feedback" id="menuNameErr"></div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-8">
              <label class="form-label fw-semibold" for="menuUrl">URL</label>
              <input type="text" class="form-control" id="menuUrl"
                maxlength="100" placeholder="dashboard atau #">
              <div class="invalid-feedback" id="menuUrlErr"></div>
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold" for="menuPosition">Posisi</label>
              <input type="number" class="form-control" id="menuPosition" min="0" placeholder="1">
              <div class="invalid-feedback" id="menuPositionErr"></div>
            </div>
          </div>

          <!-- FIELD IKON + TOMBOL PILIH + LIVE PREVIEW -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="menuIcon">
              Ikon Menu
              <small class="text-muted fw-normal">(Phosphor Duotone lokal, SVG, atau icon class)</small>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light p-1 text-center" id="menuIconPreviewWrap" style="min-width: 44px; display: flex; align-items: center; justify-content: center;">
                <span id="menuIconPreview" style="font-size: 20px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px;">
                  <i class="ph-duotone ph-grid-four"></i>
                </span>
              </span>
              <input type="text" class="form-control" id="menuIcon" placeholder="ph-duotone ph-house atau paste SVG">
              <button class="btn btn-primary" type="button" id="btnPickIcon">
                <i class="ph-duotone ph-magnifying-glass me-1"></i> Pilih
              </button>
              <button class="btn btn-outline-secondary" type="button" id="btnClearIcon" title="Kosongkan">
                <i class="ri-close-line"></i>
              </button>
            </div>
            <div class="invalid-feedback d-block" id="menuIconErr"></div>
            <small class="text-muted d-block mt-1">
              Klik <strong>Pilih</strong> untuk membuka katalog 1.500+ Phosphor Duotone lokal, atau paste SVG/class lain. Kosongkan untuk icon default.
            </small>
          </div>

          <div class="d-flex gap-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="menuBlank">
              <label class="form-check-label" for="menuBlank">Buka tab baru</label>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="menuSaveBtn">
            <span id="menuSaveBtnText">Simpan</span>
            <span id="menuSaveBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- ================= MODAL PHOSPHOR ICON PICKER ================= -->
<div class="modal fade" id="phosphorIconModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header flex-column align-items-start pb-2">
        <div class="d-flex align-items-center justify-content-between w-100 mb-2">
          <h5 class="modal-title mb-0">
            <i class="ph-duotone ph-shapes text-primary me-2"></i>Pilih Ikon Phosphor Duotone
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <small class="text-muted">1.512 ikon duotone lokal &mdash; offline murni</small>
          <div class="btn-group btn-group-sm" role="group">
            <input type="radio" class="btn-check" name="iconMode" id="iconModeClass" value="class" checked autocomplete="off">
            <label class="btn btn-outline-primary" for="iconModeClass"><i class="ri-font-size me-1"></i>Class Font</label>
            <input type="radio" class="btn-check" name="iconMode" id="iconModeSvg" value="svg" autocomplete="off">
            <label class="btn btn-outline-primary" for="iconModeSvg"><i class="ri-code-s-slash-line me-1"></i>SVG Raw</label>
          </div>
          <small id="iconModeSvgHint" class="text-warning fst-italic d-none">Memuat data SVG...</small>
        </div>
      </div>
      <div class="modal-body p-3">
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="ph-duotone ph-magnifying-glass"></i>
            </span>
            <input type="search" class="form-control border-start-0" id="phosphorSearch"
              placeholder="Ketik untuk mencari icon... (contoh: house, user, chart, setting, mail, file, shield)" autofocus>
            <span class="input-group-text bg-light text-muted" id="phosphorCountBadge">0 icon</span>
          </div>
        </div>
        <div id="phosphorPickerLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 mb-0">Memuat katalog Phosphor Duotone...</p>
        </div>
        <div id="phosphorGrid" class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2" style="max-height: 420px; overflow-y: auto; display: none;"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <span class="text-muted small">
          Terpilih: <code id="phosphorSelectedName" class="text-primary fw-semibold">ph-duotone ph-house</code>
        </span>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODAL HAK TOMBOL ================= -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hak Tombol: <span id="actionMenuName"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="actionMenuId"><input type="hidden" id="actionPermissionId">
        <div class="row g-2 mb-3">
          <div class="col-md-4"><input id="actionName" class="form-control" placeholder="Contoh: Tambah ABC"></div>
          <div class="col-md-6"><input id="actionDescription" class="form-control" placeholder="Keterangan opsional"></div>
          <div class="col-md-2"><button id="actionSave" class="btn btn-primary w-100" type="button">Tambah</button></div>
        </div>
        <div id="actionList"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const CAN_CREATE = <?php echo $can('menus.create') ? 'true' : 'false'; ?>;
    const CAN_UPDATE = <?php echo $can('menus.update') ? 'true' : 'false'; ?>;
    const CAN_DELETE = <?php echo $can('menus.delete') ? 'true' : 'false'; ?>;
    const CAN_REORDER = <?php echo $can('menus.reorder') ? 'true' : 'false'; ?>;
    var BASE_URL = <?php echo json_encode(site_url('api/v1/menus'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var PHOSPHOR_JSON_URL = <?php echo json_encode(fmsAssets('js', 'phosphor-icons.json'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var PHOSPHOR_SVGS_URL = <?php echo json_encode(fmsAssets('js', 'phosphor-svgs.json'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    var actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
    var menuModal = new bootstrap.Modal(document.getElementById('menuModal'));
    var phosphorModalEl = document.getElementById('phosphorIconModal');
    var phosphorModal = phosphorModalEl ? new bootstrap.Modal(phosphorModalEl) : null;

    /* ================= LIVE PREVIEW LOGIC ================= */
    var menuIconInput = document.getElementById('menuIcon');
    var menuIconPreview = document.getElementById('menuIconPreview');

    function updateIconPreview(val) {
      if (!menuIconPreview) return;
      var str = (val || '').trim();
      if (!str) {
        menuIconPreview.innerHTML = '<i class="ph-duotone ph-grid-four text-muted"></i>';
        return;
      }
      if (str.toLowerCase().startsWith('<svg')) {
        menuIconPreview.innerHTML = str;
        var svg = menuIconPreview.querySelector('svg');
        if (svg) {
          svg.setAttribute('width', '22');
          svg.setAttribute('height', '22');
        }
      } else {
        menuIconPreview.innerHTML = '<i class="' + str.replace(/[^A-Za-z0-9_\- ]/g, '') + '"></i>';
      }
    }

    if (menuIconInput) {
      menuIconInput.addEventListener('input', function() {
        updateIconPreview(this.value);
      });
    }

    var btnClearIcon = document.getElementById('btnClearIcon');
    if (btnClearIcon) {
      btnClearIcon.addEventListener('click', function() {
        if (menuIconInput) {
          menuIconInput.value = '';
          updateIconPreview('');
        }
      });
    }

    /* ================= PHOSPHOR PICKER MODAL ================= */
    var phosphorIconsCache = null;
    var phosphorSvgsCache = null;
    var phosphorLoadingSvgs = false;
    var currentIconMode = "class";

    var btnPickIcon = document.getElementById("btnPickIcon");
    var phosphorGrid = document.getElementById("phosphorGrid");
    var phosphorSearch = document.getElementById("phosphorSearch");
    var phosphorPickerLoading = document.getElementById("phosphorPickerLoading");
    var phosphorCountBadge = document.getElementById("phosphorCountBadge");
    var phosphorSelectedName = document.getElementById("phosphorSelectedName");
    var iconModeSvgHint = document.getElementById("iconModeSvgHint");

    function loadPhosphorSvgs(callback) {
      if (phosphorSvgsCache) {
        if (callback) callback();
        return;
      }
      if (phosphorLoadingSvgs) return;
      phosphorLoadingSvgs = true;
      if (iconModeSvgHint) iconModeSvgHint.classList.remove("d-none");

      FMS.get(PHOSPHOR_SVGS_URL, null, {
          csrf: false
        })
        .then(function(svgMap) {
          phosphorSvgsCache = svgMap || {};
          phosphorLoadingSvgs = false;
          if (iconModeSvgHint) iconModeSvgHint.classList.add("d-none");
          if (callback) callback();
        })
        .catch(function(err) {
          phosphorLoadingSvgs = false;
          if (iconModeSvgHint) {
            iconModeSvgHint.textContent = "Gagal memuat SVG: " + ((err && err.message) || "");
            iconModeSvgHint.classList.remove("d-none");
          }
        });
    }

    var modeRadios = document.querySelectorAll("input[name=iconMode]");
    modeRadios.forEach(function(r) {
      r.addEventListener("change", function() {
        currentIconMode = this.value;
        if (currentIconMode === "svg" && !phosphorSvgsCache) {
          loadPhosphorSvgs(function() {
            if (phosphorSelectedName) {
              phosphorSelectedName.textContent = "Mode SVG Aktif (1.512 SVG siap)";
            }
          });
        }
        if (phosphorSelectedName) {
          phosphorSelectedName.textContent = currentIconMode === "svg" ?
            "Mode SVG: klik ikon untuk output tag <svg>" :
            "Mode Class: klik ikon untuk output ph-duotone";
        }
      });
    });

    function renderPhosphorGrid(filter) {
      if (!phosphorGrid || !phosphorIconsCache) return;
      var q = (filter || "").trim().toLowerCase();
      var list = q ? phosphorIconsCache.filter(function(name) {
        return name.toLowerCase().indexOf(q) !== -1;
      }) : phosphorIconsCache;

      if (phosphorCountBadge) {
        phosphorCountBadge.textContent = list.length + " icon";
      }

      var html = "";
      for (var i = 0; i < list.length; i++) {
        var name = list[i];
        var fullClass = "ph-duotone ph-" + name;
        html += "<div class=\"col\">" +
          "<button type=\"button\" class=\"btn btn-outline-light text-dark border w-100 p-2 d-flex flex-column align-items-center justify-content-center phosphor-item-btn\" data-icon-name=\"" + name + "\" data-icon-class=\"" + fullClass + "\" title=\"" + name + "\" style=\"height:76px;\">" +
          "<i class=\"" + fullClass + " fs-2 mb-1 text-primary\"></i>" +
          "<span class=\"text-truncate w-100 text-center\" style=\"font-size:11px;\">" + name + "</span>" +
          "</button>" +
          "</div>";
      }
      phosphorGrid.innerHTML = html || "<div class=\"col-12 text-center py-4 text-muted\">Tidak ada icon yang cocok</div>";

      var btns = phosphorGrid.querySelectorAll(".phosphor-item-btn");
      btns.forEach(function(b) {
        b.addEventListener("click", function() {
          var name = this.getAttribute("data-icon-name");
          var cls = this.getAttribute("data-icon-class");

          if (currentIconMode === "svg") {
            if (phosphorSvgsCache && phosphorSvgsCache[name]) {
              var svgCode = phosphorSvgsCache[name];
              if (menuIconInput) {
                menuIconInput.value = svgCode;
                updateIconPreview(svgCode);
              }
              if (phosphorModal) phosphorModal.hide();
            } else {
              loadPhosphorSvgs(function() {
                var fallbackSvg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 256 256\"></svg>";
                var svgCode = (phosphorSvgsCache && phosphorSvgsCache[name]) || fallbackSvg;
                if (menuIconInput) {
                  menuIconInput.value = svgCode;
                  updateIconPreview(svgCode);
                }
                if (phosphorModal) phosphorModal.hide();
              });
            }
          } else {
            if (menuIconInput) {
              menuIconInput.value = cls;
              updateIconPreview(cls);
            }
            if (phosphorModal) phosphorModal.hide();
          }
        });

        b.addEventListener("mouseenter", function() {
          var name = this.getAttribute("data-icon-name");
          var cls = this.getAttribute("data-icon-class");
          if (phosphorSelectedName) {
            phosphorSelectedName.textContent = currentIconMode === "svg" ? ("SVG: " + name + "-duotone.svg") : cls;
          }
        });
      });
    }

    if (btnPickIcon) {
      btnPickIcon.addEventListener("click", function() {
        if (!phosphorModal) return;
        phosphorModal.show();

        if (!phosphorSvgsCache && !phosphorLoadingSvgs) {
          loadPhosphorSvgs();
        }

        if (phosphorIconsCache) {
          if (phosphorPickerLoading) phosphorPickerLoading.style.display = "none";
          if (phosphorGrid) phosphorGrid.style.display = "flex";
          if (phosphorSearch) phosphorSearch.value = "";
          renderPhosphorGrid("");
          setTimeout(function() {
            if (phosphorSearch) phosphorSearch.focus();
          }, 150);
          return;
        }

        if (phosphorPickerLoading) phosphorPickerLoading.style.display = "block";
        if (phosphorGrid) phosphorGrid.style.display = "none";

        FMS.get(PHOSPHOR_JSON_URL, null, {
            csrf: false
          })
          .then(function(data) {
            phosphorIconsCache = Array.isArray(data) ? data : [];
            if (phosphorPickerLoading) phosphorPickerLoading.style.display = "none";
            if (phosphorGrid) phosphorGrid.style.display = "flex";
            renderPhosphorGrid("");
            setTimeout(function() {
              if (phosphorSearch) phosphorSearch.focus();
            }, 150);
          })
          .catch(function(err) {
            if (phosphorPickerLoading) {
              phosphorPickerLoading.innerHTML = "<span class=\"text-danger\">Gagal memuat daftar icon: " + ((err && err.message) || "") + "</span>";
            }
          });
      });
    }

    if (phosphorSearch) {
      phosphorSearch.addEventListener("input", function() {
        renderPhosphorGrid(this.value);
      });
    }

    /* ================= TREE & CRUD LOGIC ================= */
    function loadMenuActions(menuId) {
      FMS.get(BASE_URL + '/' + menuId + '/actions').then(function(data) {
        var items = data.items || [];
        document.getElementById('actionList').innerHTML = items.length ? items.map(function(item) {
          return '<div class="d-flex justify-content-between align-items-center border p-2 mb-2 rounded bg-light">' +
            '<div><strong>' + esc(item.action_name) + '</strong><br><small class="text-muted">' + esc(item.description || item.code) + '</small></div>' +
            '<button class="btn btn-sm btn-outline-danger btn-delete-action" data-id="' + item.id + '"><i class="ri-delete-bin-line"></i></button>' +
            '</div>';
        }).join('') : '<p class="text-muted text-center py-3 mb-0">Belum ada hak tombol khusus.</p>';
        document.querySelectorAll('.btn-delete-action').forEach(function(btn) {
          btn.onclick = function() {
            deleteMenuAction(this.dataset.id);
          };
        });
      });
    }

    function saveMenuAction() {
      var name = document.getElementById('actionName').value.trim();
      var desc = document.getElementById('actionDescription').value.trim();
      var menuId = document.getElementById('actionMenuId').value;
      if (!name) return FMS.toast('Nama tombol harus diisi', 'warning');

      FMS.post(BASE_URL + '/' + menuId + '/actions', {
        action_name: name,
        description: desc
      }).then(function(res) {
        document.getElementById('actionName').value = '';
        document.getElementById('actionDescription').value = '';
        loadMenuActions(menuId);
        FMS.toast('Hak tombol berhasil ditambahkan', 'success');
      });
    }

    function deleteMenuAction(id) {
      FMS.del(BASE_URL + '/' + document.getElementById('actionMenuId').value + '/actions/' + id).then(function(res) {
        loadMenuActions(document.getElementById('actionMenuId').value);
        FMS.toast('Hak tombol berhasil dihapus', 'success');
      });
    }

    document.getElementById('actionSave').onclick = saveMenuAction;

    var menuTreeBody = document.getElementById('menuTreeBody');
    var menuTreeError = document.getElementById('menuTreeError');
    var menuSearch = document.getElementById('menuSearch');
    var menuStatusFilter = document.getElementById('menuStatusFilter');
    var btnRefresh = document.getElementById('btnRefresh');
    var btnAddRoot = document.getElementById('btnAddRoot');
    var btnExpandAll = document.getElementById('btnExpandAll');
    var btnCollapseAll = document.getElementById('btnCollapseAll');

    var menuForm = document.getElementById('menuForm');
    var menuFormError = document.getElementById('menuFormError');
    var menuParentInfo = document.getElementById('menuParentInfo');
    var menuParentName = document.getElementById('menuParentName');
    var menuSaveBtn = document.getElementById('menuSaveBtn');
    var menuSaveBtnSpinner = document.getElementById('menuSaveBtnSpinner');

    var rawMenuRows = [];
    var collapsedNodes = Object.create(null);

    var fieldMap = {
      name: 'menuName',
      url: 'menuUrl',
      icon: 'menuIcon',
      position: 'menuPosition'
    };

    function clearTreeError() {
      menuTreeError.classList.add('d-none');
      menuTreeError.textContent = '';
    }

    function showTreeError(message) {
      menuTreeError.textContent = message;
      menuTreeError.classList.remove('d-none');
    }

    function clearFieldErrors() {
      menuFormError.classList.add('d-none');
      menuFormError.textContent = '';
      ['menuName', 'menuUrl', 'menuIcon', 'menuPosition'].forEach(function(id) {
        var input = document.getElementById(id);
        var feedback = document.getElementById(id + 'Err');
        if (input) input.classList.remove('is-invalid');
        if (feedback) feedback.textContent = '';
      });
    }

    function showValidationErrors(errors) {
      var unmapped = [];
      Object.keys(errors || {}).forEach(function(fieldName) {
        var inputId = fieldMap[fieldName];
        var input = inputId ? document.getElementById(inputId) : null;
        var feedback = inputId ? document.getElementById(inputId + 'Err') : null;
        var message = Array.isArray(errors[fieldName]) ? errors[fieldName][0] : errors[fieldName];
        if (input && feedback) {
          input.classList.add('is-invalid');
          feedback.textContent = message;
        } else if (message) {
          unmapped.push(message);
        }
      });
      if (unmapped.length > 0) {
        menuFormError.textContent = unmapped.join(' ');
        menuFormError.classList.remove('d-none');
      }
    }

    function setSaveLoading(loading) {
      menuSaveBtn.disabled = loading;
      menuSaveBtnSpinner.classList.toggle('d-none', !loading);
    }

    function value(id, nextValue) {
      var element = document.getElementById(id);
      if (!element) return '';
      if (arguments.length > 1) {
        element.value = nextValue;
      }
      return element.value;
    }

    function checked(id, nextChecked) {
      var element = document.getElementById(id);
      if (!element) return false;
      if (arguments.length > 1) {
        element.checked = Boolean(nextChecked);
      }
      return element.checked;
    }

    function buildTree(rows) {
      var byId = Object.create(null);
      var roots = [];
      rows.forEach(function(row) {
        byId[row.id] = Object.assign({}, row, {
          children: []
        });
      });
      rows.forEach(function(row) {
        var parentId = row.id_parent ? Number(row.id_parent) : null;
        if (parentId && byId[parentId]) {
          byId[parentId].children.push(byId[row.id]);
        } else {
          roots.push(byId[row.id]);
        }
      });
      sortByPosition(roots);
      return roots;
    }

    function sortByPosition(nodes) {
      nodes.sort(function(a, b) {
        var gap = Number(a.position) - Number(b.position);
        return gap !== 0 ? gap : Number(a.id) - Number(b.id);
      });
      nodes.forEach(function(node) {
        sortByPosition(node.children);
      });
    }

    function filterTree(nodes, keyword, status) {
      return nodes.filter(function(node) {
        var haystack = (node.name + ' ' + (node.url || '')).toLowerCase();
        var matchesSelf = (keyword === '' || haystack.indexOf(keyword) !== -1) &&
          (status === '' || String(node.is_active) === status);
        var filteredChildren = filterTree(node.children || [], keyword, status);
        if (matchesSelf || filteredChildren.length > 0) {
          node.children = filteredChildren;
          return true;
        }
        return false;
      });
    }

    function renderSummary(rows) {
      var total = rows.length;
      var active = rows.filter(function(row) {
        return Number(row.is_active) === 1;
      }).length;
      var inactive = total - active;
      var roots = rows.filter(function(row) {
        return !row.id_parent;
      }).length;

      var summaryContainer = document.getElementById('menuSummaryRow');
      if (!summaryContainer) return;
      summaryContainer.innerHTML = [
        '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-light text-center"><small class="text-muted d-block">Total Menu</small><strong class="fs-5">' + total + '</strong></div></div>',
        '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-light text-center"><small class="text-muted d-block">Menu Utama</small><strong class="fs-5 text-primary">' + roots + '</strong></div></div>',
        '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-light text-center"><small class="text-muted d-block">Aktif</small><strong class="fs-5 text-success">' + active + '</strong></div></div>',
        '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-light text-center"><small class="text-muted d-block">Nonaktif</small><strong class="fs-5 text-secondary">' + inactive + '</strong></div></div>'
      ].join('');
    }

    function renderTree() {
      clearTreeError();
      var keyword = (menuSearch ? menuSearch.value : '').trim().toLowerCase();
      var status = menuStatusFilter ? menuStatusFilter.value : '';
      var tree = buildTree(rawMenuRows);
      var visibleTree = (keyword !== '' || status !== '') ? filterTree(tree, keyword, status) : tree;
      renderSummary(rawMenuRows);

      if (visibleTree.length === 0) {
        menuTreeBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data menu yang cocok.</td></tr>';
        return;
      }

      var html = [];
      visibleTree.forEach(function(node) {
        renderNode(node, 0, html);
      });
      menuTreeBody.innerHTML = html.join('');
      bindTreeEvents();
    }

    function renderNode(node, depth, html) {
      var hasChildren = node.children && node.children.length > 0;
      var isCollapsed = Boolean(collapsedNodes[node.id]);
      var indent = depth * 24;
      var iconHtml = renderMenuIcon(node.icon);
      var toggleIcon = hasChildren ?
        (isCollapsed ? '<i class="ri-arrow-right-s-line me-1 tree-toggle" data-node-id="' + node.id + '" style="cursor:pointer"></i>' :
          '<i class="ri-arrow-down-s-line me-1 tree-toggle" data-node-id="' + node.id + '" style="cursor:pointer"></i>') :
        '<span class="d-inline-block" style="width: 14px;"></span>';

      var statusBadge = Number(node.is_active) === 1 ?
        '<span class="badge bg-success-transparent">Aktif</span>' :
        '<span class="badge bg-secondary-transparent">Nonaktif</span>';

      html.push('<tr data-node-id="' + node.id + '">');
      html.push('<td><div class="d-flex align-items-center" style="padding-left:' + indent + 'px">' +
        toggleIcon +
        '<span class="me-2 d-inline-flex align-items-center justify-content-center" style="width:24px;height:24px;font-size:18px;">' + iconHtml + '</span>' +
        '<div><strong>' + esc(node.name) + '</strong>' + (node.target_blank == 1 ? ' <i class="ri-external-link-line text-muted small" title="Tab baru"></i>' : '') + '</div>' +
        '</div></td>');
      html.push('<td><code class="text-muted">' + esc(node.url || '#') + '</code></td>');
      var reorderControls = '';
      if (CAN_REORDER) {
        reorderControls = '<div class="btn-group btn-group-sm" role="group">' +
          '<button type="button" class="btn btn-outline-light text-dark btn-move-up" data-id="' + node.id + '" data-parent="' + (node.id_parent || '') + '" title="Pindah ke Atas"><i class="ri-arrow-up-s-line"></i></button>' +
          '<input type="number" class="form-control form-control-sm text-center menu-position-input px-1" style="width:48px" value="' + (node.position || 0) + '" data-id="' + node.id + '" data-hash="' + (node.hash || node.id) + '">' +
          '<button type="button" class="btn btn-outline-light text-dark btn-move-down" data-id="' + node.id + '" data-parent="' + (node.id_parent || '') + '" title="Pindah ke Bawah"><i class="ri-arrow-down-s-line"></i></button>' +
          '</div>';
      } else {
        reorderControls = '<span class="badge bg-light text-dark">' + (node.position || 0) + '</span>';
      }
      html.push('<td class="text-center">' + reorderControls + '</td>');
      html.push('<td class="text-center">' + statusBadge + '</td>');
      html.push('<td class="text-end">' +
        '<div class="btn-list">' +
        '<button class="btn btn-sm btn-icon btn-teal-light btn-actions" data-id="' + node.id + '" data-hash="' + (node.hash || node.id) + '" data-name="' + esc(node.name) + '" title="Hak Tombol"><i class="ri-shield-keyhole-line"></i></button>' +
        (CAN_CREATE ? '<button class="btn btn-sm btn-icon btn-info-light btn-add-child" data-id="' + node.id + '" data-hash="' + (node.hash || node.id) + '" data-name="' + esc(node.name) + '" title="Tambah Sub Menu"><i class="ri-add-line"></i></button>' : '') +
        (CAN_UPDATE ? '<button class="btn btn-sm btn-icon btn-primary-light btn-edit" data-id="' + node.id + '" data-hash="' + (node.hash || node.id) + '" title="Edit"><i class="ri-edit-line"></i></button>' : '') +
        (CAN_DELETE ? '<button class="btn btn-sm btn-icon btn-danger-light btn-delete" data-id="' + node.id + '" data-hash="' + (node.hash || node.id) + '" data-name="' + esc(node.name) + '" title="Hapus"><i class="ri-delete-bin-line"></i></button>' : '') +
        '</div>' +
        '</td>');
      html.push('</tr>');

      if (hasChildren && !isCollapsed) {
        node.children.forEach(function(child) {
          renderNode(child, depth + 1, html);
        });
      }
    }

    function renderMenuIcon(iconString) {
      var str = (iconString || '').trim();
      if (!str) {
        return '<i class="ph-duotone ph-grid-four text-muted"></i>';
      }
      if (str.toLowerCase().startsWith('<svg')) {
        return str;
      }
      return '<i class="' + str.replace(/[^A-Za-z0-9_\- ]/g, '') + '"></i>';
    }

    function esc(s) {
      if (!s) return '';
      return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }


    function moveMenuNode(menuId, direction) {
      var target = rawMenuRows.find(function(r) {
        return String(r.id) === String(menuId);
      });
      if (!target) return;

      var parentId = target.id_parent ? Number(target.id_parent) : null;
      /* Ambil semua sibling dengan parent yang sama */
      var siblings = rawMenuRows.filter(function(r) {
        var p = r.id_parent ? Number(r.id_parent) : null;
        return p === parentId;
      });

      /* Urutkan siblings berdasarkan posisi saat ini */
      siblings.sort(function(a, b) {
        var gap = Number(a.position || 0) - Number(b.position || 0);
        return gap !== 0 ? gap : Number(a.id) - Number(b.id);
      });

      var currentIndex = siblings.findIndex(function(r) {
        return String(r.id) === String(menuId);
      });
      if (currentIndex === -1) return;

      var swapIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
      if (swapIndex < 0 || swapIndex >= siblings.length) {
        return FMS.toast(direction === 'up' ? 'Menu sudah di posisi teratas' : 'Menu sudah di posisi terbawah', 'info');
      }

      /* Tukar posisi di array */
      var temp = siblings[currentIndex];
      siblings[currentIndex] = siblings[swapIndex];
      siblings[swapIndex] = temp;

      /* Bentuk ordering payload baru 1..N */
      var orderingPayload = siblings.map(function(item, idx) {
        return {
          id: item.id,
          menu_id: item.id,
          position: idx + 1
        };
      });

      FMS.patch(BASE_URL + '/reorder', {
        ordering: orderingPayload
      }).then(function(res) {
        FMS.toast('Urutan menu berhasil disimpan', 'success');
        loadData();
      }).catch(function(err) {
        FMS.toast((err && err.message) ? err.message : 'Gagal menyimpan urutan menu', 'danger');
      });
    }

    function bindTreeEvents() {
      document.querySelectorAll('.tree-toggle').forEach(function(el) {
        el.onclick = function() {
          var id = this.getAttribute('data-node-id');
          collapsedNodes[id] = !collapsedNodes[id];
          renderTree();
        };
      });

      document.querySelectorAll('.btn-actions').forEach(function(el) {
        el.onclick = function() {
          var ref = this.getAttribute('data-id');
          var name = this.getAttribute('data-name');
          document.getElementById('actionMenuId').value = ref;
          document.getElementById('actionMenuName').textContent = name;
          loadMenuActions(ref);
          actionModal.show();
        };
      });

      document.querySelectorAll('.btn-add-child').forEach(function(el) {
        el.onclick = function() {
          openAddModal(this.getAttribute('data-id'), this.getAttribute('data-name'));
        };
      });

      document.querySelectorAll('.btn-edit').forEach(function(el) {
        el.onclick = function() {
          openEditModal(this.getAttribute('data-id'), this.getAttribute('data-id'));
        };
      });

      document.querySelectorAll('.btn-delete').forEach(function(el) {
        el.onclick = function() {
          openDeleteModal(this.getAttribute('data-id'), this.getAttribute('data-name'));
        };
      });

      document.querySelectorAll('.btn-move-up').forEach(function(el) {
        el.onclick = function() {
          moveMenuNode(this.getAttribute('data-id'), 'up');
        };
      });

      document.querySelectorAll('.btn-move-down').forEach(function(el) {
        el.onclick = function() {
          moveMenuNode(this.getAttribute('data-id'), 'down');
        };
      });

      document.querySelectorAll('.menu-position-input').forEach(function(el) {
        el.onchange = function() {
          var id = this.getAttribute('data-id');
          var nextPos = Number(this.value);
          FMS.patch(BASE_URL + '/' + id, {
            position: nextPos
          }).then(function(res) {
            FMS.toast('Posisi diperbarui', 'success');
            loadData();
          });
        };
      });
    }

    function loadData() {
      FMS.get(BASE_URL, {
        all: 1
      }).then(function(res) {
        var data = res && res.items !== undefined ? res : (res && res.data ? res.data : {});
        if (data && Array.isArray(data.items)) {
          rawMenuRows = data.items;
          renderTree();
        } else {
          showTreeError('Gagal memuat data menu');
        }
      }).catch(function(err) {
        showTreeError(err.message || 'Gagal terhubung ke server');
      });
    }

    function openAddModal(parentId, parentName) {
      clearFieldErrors();
      document.getElementById('menuModalLabel').textContent = parentId ? 'Tambah Sub Menu' : 'Tambah Menu Utama';
      value('menuId', '');
      value('menuParentId', parentId || '');
      value('menuName', '');
      value('menuUrl', '');
      value('menuPosition', '1');
      value('menuIcon', '');
      checked('menuBlank', false);
      updateIconPreview('');

      if (parentId && parentName) {
        menuParentName.textContent = parentName;
        menuParentInfo.classList.remove('d-none');
      } else {
        menuParentInfo.classList.add('d-none');
      }
      menuModal.show();
    }

    function openEditModal(menuId, menuRef) {
      clearFieldErrors();
      var target = rawMenuRows.find(function(m) {
        return String(m.id) === String(menuId);
      });
      var ref = menuRef || (target && (target.hash || target.id)) || menuId;
      if (!target) return;

      document.getElementById('menuModalLabel').textContent = 'Edit Menu: ' + target.name;
      value('menuId', target.id);
      value('menuParentId', target.id_parent || '');
      value('menuName', target.name || '');
      value('menuUrl', target.url || '');
      value('menuPosition', target.position || 0);
      value('menuIcon', target.icon || '');
      checked('menuBlank', Number(target.target_blank));
      updateIconPreview(target.icon || '');

      menuParentInfo.classList.add('d-none');
      menuModal.show();
    }

    function openDeleteModal(menuIdentifier, menuNameStr) {
      FMS.confirm({
        title: `Hapus Menu`,
        message: `Hapus menu "${menuNameStr}"?`,
        description: `Menu yang punya sub menu tidak bisa dihapus.`,
        confirmLabel: `Hapus`,
        loadingLabel: `Menghapus...`,
        variant: `danger`,
        action: function() {
          return FMS.del(`${BASE_URL}/${menuIdentifier}`).then(function(res) {
            FMS.toast(`Menu berhasil dihapus`, `success`);
            loadData();
          });
        }
      });
    }

    menuForm.onsubmit = function(e) {
      e.preventDefault();
      clearFieldErrors();
      setSaveLoading(true);

      var isEdit = Boolean(value('menuId'));
      var url = isEdit ? (BASE_URL + '/' + value('menuId')) : BASE_URL;
      var method = isEdit ? 'PUT' : 'POST';

      var payload = {
        id_parent: value('menuParentId') ? Number(value('menuParentId')) : null,
        name: value('menuName').trim(),
        url: value('menuUrl').trim() || null,
        position: value('menuPosition') === '' ? 1 : Number(value('menuPosition')),
        icon: value('menuIcon').trim() || null,
        target_blank: checked('menuBlank') ? 1 : 0
      };

      var request = isEdit ? FMS.patch(url, payload) : FMS.post(url, payload);
      request.then(function(res) {
        setSaveLoading(false);
        menuModal.hide();
        FMS.toast(isEdit ? 'Menu berhasil diperbarui' : 'Menu berhasil ditambahkan', 'success');
        loadData();
      }).catch(function(err) {
        setSaveLoading(false);
        if (err && err.errors && Object.keys(err.errors).length > 0) {
          showValidationErrors(err.errors);
        } else {
          menuFormError.textContent = (err && err.message) ? err.message : 'Gagal menyimpan menu';
          menuFormError.classList.remove('d-none');
        }
      });
    };

    if (btnAddRoot) btnAddRoot.onclick = function() {
      openAddModal(null, null);
    };
    if (btnRefresh) btnRefresh.onclick = loadData;
    if (menuSearch) menuSearch.oninput = renderTree;
    if (menuStatusFilter) menuStatusFilter.onchange = renderTree;

    if (btnExpandAll) {
      btnExpandAll.onclick = function() {
        collapsedNodes = Object.create(null);
        renderTree();
      };
    }
    if (btnCollapseAll) {
      btnCollapseAll.onclick = function() {
        rawMenuRows.forEach(function(r) {
          collapsedNodes[r.id] = true;
        });
        renderTree();
      };
    }

    loadData();
  });
</script>