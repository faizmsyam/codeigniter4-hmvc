<?php
$permissions = array_map('strval', $backendPermissions ?? []);
$can = static fn(string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
?>
<div id="activityLogsList" data-list-url="<?php echo esc(site_url('api/v1/activity-logs'), 'attr'); ?>" data-export-url="<?php echo esc(site_url('api/v1/activity-logs/export'), 'attr'); ?>">
  <div class="row">
    <div class="col-lg-12">
      <div class="card custom-card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-1">Riwayat Aktivitas</h5>
            <span class="text-muted fs-13">Jejak perubahan dan tindakan pengguna di dalam sistem.</span>
          </div>
          <div class="w-100 w-sm-auto">
            <div class="d-flex flex-column flex-sm-row gap-2">
              <button type="button" class="btn btn-secondary btn-glare btn-wave label-btn w-100 w-sm-auto" id="activityLogsRefresh"><i class="ri-refresh-line label-btn-icon me-2"></i>Refresh</button>
              <?php if ($can('activity_logs.export')): ?>
                <button type="button" class="btn btn-success btn-glare btn-wave label-btn w-100 w-sm-auto" id="activityLogsExport"><i class="ri-file-excel-2-line label-btn-icon me-2"></i>Export CSV</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="card-header d-flex flex-column flex-sm-row justify-content-end gap-2">
          <div class="input-group" style="width:150px">
            <span class="input-group-text">Baris</span>
            <select id="activityLogsLimit" class="form-select">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div class="form-group">
            <input type="text" class="form-control breadcrumb-input" id="activityLogsDateRange" placeholder="Search By Date Range">
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover text-nowrap align-middle">
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>Kegiatan</th>
                  <th>Modul</th>
                  <th>Pengguna</th>
                  <th>IP / Device</th>
                  <th>Status</th>
                  <th>Detail</th>
                </tr>
              </thead>
              <tbody id="activityLogsRows"></tbody>
            </table>
          </div>
        </div>
        <div class="card-footer d-none" id="card-footer-activityLogsPager">
          <div id="activityLogsPager" class="small"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('activityLogsList');
    var rows = document.getElementById('activityLogsRows');
    var pager = document.getElementById('activityLogsPager');

    function toLocalYMD(d) {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    const today = new Date();
    const start = new Date(today);
    start.setDate(today.getDate() - 30);
    const startDate = toLocalYMD(start);
    const endDateFormatted = toLocalYMD(today);

    const rangeInput = document.getElementById(`activityLogsDateRange`);
    let dateRange = [startDate, endDateFormatted];

    flatpickr(`#activityLogsDateRange`, {
      mode: `range`,
      dateFormat: `Y-m-d`,
      defaultDate: [startDate, endDateFormatted],
      onReady: function(selectedDates, dateStr, instance) {
        updateInputDisplay(selectedDates.length ? selectedDates : [startDate, endDateFormatted], instance);
        syncDateRange(selectedDates.length ? selectedDates : [start, today]);
      },
      onChange: function(selectedDates, dateStr, instance) {
        updateInputDisplay(selectedDates, instance);
        syncDateRange(selectedDates || []);
        if (selectedDates.length === 2) {
          load(1);
        }
      }
    });

    function syncDateRange(selectedDates) {
      dateRange = (selectedDates || []).filter(Boolean).map(function(date) {
        if (date instanceof Date) {
          return toLocalYMD(date);
        }
        return String(date).split(`T`)[0];
      });
    }

    function updateInputDisplay(dates, instance) {
      if (dates.length === 2) {
        const startDateFormatted = formatDate(dates[0]);
        const endDateFormatted = formatDate(dates[1]);
        instance.input.value = `${startDateFormatted} to ${endDateFormatted}`;
      } else {
        instance.input.value = '';
      }
    }

    function formatDate(dateString) {
      const date = new Date(dateString);
      const day = String(date.getDate()).padStart(2, `0`);
      const month = date.toLocaleString(`default`, {
        month: `short`
      });
      const year = date.getFullYear();
      return `${day}, ${month} ${year}`;
    }

    function esc(value) {
      return FMS.escape(value == null ? `` : String(value));
    }

    function humanize(value) {
      return String(value || `-`).replace(/[._-]+/g, ` `).replace(/\b\w/g, function(letter) {
        return letter.toUpperCase();
      });
    }

    function statusBadge(statusCode) {
      if (statusCode === null || statusCode === undefined || statusCode === ``) {
        return `<span class="badge bg-secondary-transparent">OK</span>`;
      }
      var code = Number(statusCode || 0);
      var color = code >= 200 && code < 400 ? `success` : (code >= 400 ? `danger` : `secondary`);
      return `<span class="badge bg-${color}-transparent">${esc(statusCode || `-`)}</span>`;
    }

    function changeDetailMarkup(item) {
      if (!item.has_changes) return `<span class="text-muted">-</span>`;

      return `<button type="button" class="btn btn-outline-primary btn-sm js-activity-detail" data-uuid="${esc(item.uuid)}"><i class="ri-eye-line me-1"></i>Detail</button>`;
    }

    function renderPayload(payload) {
      var value = payload && typeof payload === `object` ? payload : {};
      return esc(JSON.stringify(value, null, 2));
    }

    function openDetail(item) {
      var modal = document.createElement(`div`);
      modal.className = `modal fade`;
      modal.tabIndex = -1;
      modal.innerHTML = `<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="ri-file-list-3-line me-2"></i>Detail Perubahan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><div class="small fw-semibold text-danger mb-2">Before</div><pre class="bg-danger-transparent border rounded p-3 mb-0 small" style="min-height:160px;max-height:420px;overflow:auto;white-space:pre-wrap;">${renderPayload(item.before)}</pre></div>
              <div class="col-md-6"><div class="small fw-semibold text-success mb-2">After</div><pre class="bg-success-transparent border rounded p-3 mb-0 small" style="min-height:160px;max-height:420px;overflow:auto;white-space:pre-wrap;">${renderPayload(item.after)}</pre></div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div>
      </div>`;
      document.body.appendChild(modal);
      var instance = new bootstrap.Modal(modal);
      modal.addEventListener(`hidden.bs.modal`, function() {
        instance.dispose();
        modal.remove();
      });
      instance.show();
    }

    function rowMarkup(item) {
      var description = item.description ?
        `<div class="text-muted fs-12 mt-1 text-wrap" style="max-width:360px">${esc(item.description)}</div>` :
        ``;
      var entity = item.entity_type ?
        `<span class="badge bg-light text-default border">${esc(humanize(item.entity_type))}</span>${item.entity_id ? `<div class="text-muted fs-12 mt-1">#${esc(item.entity_id)}</div>` : ``}` :
        `<span class="text-muted">-</span>`;

      var actorName = item.actor_name || item.actor_username || (item.actor_user_id ? `Pengguna #${item.actor_user_id}` : `Sistem`);
      var actorSubtext = item.actor_name && item.actor_username ? `@${item.actor_username}` : (item.actor_user_id ? `ID #${item.actor_user_id}` : `Aktivitas otomatis`);
      var ipLabel = item.ip_address || `-`;
      var deviceLabel = item.device_label || item.user_agent || `-`;

      return `<tr>
        <td class="text-nowrap"><div class="fw-medium">${esc(item.created_at || `-`)}</div><div class="text-muted fs-12">${esc(item.http_method || ``)}</div></td>
        <td><div class="fw-semibold text-default">${esc(humanize(item.event))}</div>${description}</td>
        <td><span class="badge bg-primary-transparent">${esc(humanize(item.module))}</span></td>
        <td><div class="d-flex align-items-center gap-2"><span class="avatar avatar-xs rounded-circle bg-light text-default"><i class="ri-user-line"></i></span><div><div class="fw-medium">${esc(actorName)}</div><div class="text-muted fs-12">${esc(actorSubtext)}</div></div></div></td>
        <td><div class="fw-medium text-nowrap">${esc(ipLabel)}</div><div class="text-muted fs-12 text-wrap" style="max-width:220px">${esc(deviceLabel)}</div></td>
        <td>${statusBadge(item.status_code)}</td>
        <td>${changeDetailMarkup(item)}</td>
      </tr>`;
    }
    var page = 1;
    var limitSelect = document.getElementById('activityLogsLimit');
    var perPage = Number(limitSelect.value || 25);
    limitSelect.addEventListener('change', function() {
      perPage = Number(limitSelect.value || 25);
      load(1);
    });

    rows.addEventListener(`click`, function(event) {
      var trigger = event.target.closest(`.js-activity-detail`);
      if (!trigger) return;

      var originalHtml = trigger.innerHTML;
      trigger.disabled = true;
      trigger.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Memuat`;
      FMS.get(`${root.dataset.listUrl}/${encodeURIComponent(trigger.dataset.uuid)}`)
        .then(function(body) {
          openDetail(body && body.data ? body.data : body);
        })
        .catch(function(error) {
          FMS.toast(error.message || `Gagal memuat detail aktivitas.`, false);
        })
        .finally(function() {
          trigger.disabled = false;
          trigger.innerHTML = originalHtml;
        });
    });

    function load(targetPage) {
      page = targetPage || page;
      rows.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat aktivitas...</td></tr>`;
      document.getElementById('card-footer-activityLogsPager').classList.add('d-none');
      var params = {
        page: String(page),
        per_page: String(perPage)
      };
      if (dateRange.length === 2) {
        params.date_from = dateRange[0];
        params.date_to = dateRange[1];
      }
      FMS.get(root.dataset.listUrl, params)
        .then(function(body) {
          var data = (body && body.items !== undefined) ? body : (body.data || {});
          var items = data.items || [];
          rows.innerHTML = items.length ?
            items.map(function(item) {
              return rowMarkup(item);
            }).join(``) :
            `<tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-history-line fs-24 d-block mb-2"></i>Belum ada activity log.</td></tr>`;
          var result = FMS.pagination({
            target: pager,
            total: Number(data.total || 0),
            page: Number(data.page || page),
            perPage: Number(data.per_page || perPage),
            onChange: function(nextPage) {
              load(nextPage);
            }
          });
          document.getElementById('card-footer-activityLogsPager').classList.remove('d-none');
        })
        .catch(function(error) {
          rows.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4"><i class="ri-error-warning-line me-1"></i>${esc(error.message)}</td></tr>`;
        });
    }
    document.getElementById('activityLogsRefresh').addEventListener('click', function() {
      load(page);
    });
    const exportButton = document.getElementById(`activityLogsExport`);
    if (exportButton) {
      exportButton.addEventListener(`click`, function() {
        if (dateRange.length !== 2) {
          FMS.toast(`Pilih dulu rentang tanggal, contoh hari ini sampai 30 hari ke depan.`, false);
          if (rangeInput) rangeInput.focus();

          return;
        }
        const exportParams = {
          date_from: dateRange[0],
          date_to: dateRange[1]
        };
        FMS.ajax({
            url: root.dataset.exportUrl,
            method: 'GET',
            data: exportParams,
            headers: {
              'Accept': 'text/csv'
            }
          })
          .then(function(response) {
            var csvBlob = new Blob([response], {
              type: 'text/csv;charset=utf-8'
            });
            var downloadUrl = URL.createObjectURL(csvBlob);
            var anchor = document.createElement('a');
            anchor.href = downloadUrl;
            anchor.download = 'activity-logs-export.csv';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            URL.revokeObjectURL(downloadUrl);
            FMS.toast('File CSV berhasil diunduh.', true);
          })
          .catch(function(error) {
            FMS.toast(error.message || 'Ekspor gagal.', false);
          });
      });
    }
    load(1);
  });
</script>