<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('leads_imo_inject_ui')) {
    function leads_imo_inject_ui()
    {
        $CI = &get_instance();

        if ($CI->input->is_ajax_request()) {
            return;
        }

        if ($CI->uri->segment(2) !== 'leads') {
            return;
        }

        // Carregar mapa de statuses via PHP para injectar no JS
        $CI->db->order_by('statusorder', 'asc');
        $statuses = $CI->db->get(db_prefix() . 'leads_status')->result_array();
        $statusMapJson = json_encode(
            array_column($statuses, 'id', 'name')  // { "Novos": 1, "Quente": 2, ... }
        );
        ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    /* ------------------------------------------------------------------ */
    /*  Mapa nome → id dos statuses (injectado via PHP)                    */
    /* ------------------------------------------------------------------ */
    var STATUS_MAP = <?= $statusMapJson; ?>; // { "Novos": 1, ... }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    function getSourceGroup(prefix) {
        var el = document.getElementById((prefix || '') + 'lead_source_group');
        return el ? el.value : 'expansao_imo';
    }

    function getDateFrom(prefix) {
        var el = document.getElementById((prefix || '') + 'lead_source_from');
        return el ? el.value : '';
    }

    function getDateTo(prefix) {
        var el = document.getElementById((prefix || '') + 'lead_source_to');
        return el ? el.value : '';
    }

    /* ------------------------------------------------------------------ */
    /*  Botão "Fonte de lead" — relatório comerciais × statuses            */
    /* ------------------------------------------------------------------ */

    var buttonsDiv = document.querySelector('._buttons');
    if (buttonsDiv && !document.getElementById('lead-source-btn')) {
        var fonteBtn = document.createElement('a');
        fonteBtn.className  = 'btn btn-default mleft5';
        fonteBtn.id         = 'lead-source-btn';
        fonteBtn.href       = 'javascript:void(0)';
        fonteBtn.innerText  = 'Fonte de lead';
        buttonsDiv.appendChild(fonteBtn);

        fonteBtn.addEventListener('click', function () {
            $('#leadSourceReportModal').modal('show');
            loadLeadSourceReport();
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Delegação global para botões dos modais                            */
    /* ------------------------------------------------------------------ */
    document.addEventListener('click', function (e) {
        var id = e.target && e.target.id;
        if (id === 'lead-source-load-btn')  loadLeadSourceReport();
        if (id === 'lead-source-export-btn') exportLeadSourceExcel();
        if (id === 'status-dist-load-btn')  loadStatusDistribution();
    });

    /* ------------------------------------------------------------------ */
    /*  Relatório comerciais × statuses (modal "Fonte de lead")            */
    /* ------------------------------------------------------------------ */
    function loadLeadSourceReport() {
        var tbody    = document.querySelector('#leadSourceReportTable tbody');
        var theadRow = document.querySelector('#leadSourceReportTable thead tr');

        tbody.innerHTML    = '<tr><td colspan="20">A carregar...</td></tr>';
        theadRow.innerHTML = '<th>Comercial</th>';

        var url = admin_url + 'leads_imo/source_report'
            + '?group=' + encodeURIComponent(getSourceGroup('src_'))
            + '&from='  + encodeURIComponent(getDateFrom('src_'))
            + '&to='    + encodeURIComponent(getDateTo('src_'));

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                theadRow.innerHTML = '<th>Comercial</th>';
                data.statuses.forEach(function (s) {
                    theadRow.innerHTML += '<th>' + s.name + '</th>';
                });
                theadRow.innerHTML += '<th>Total</th>';

                tbody.innerHTML = '';
                if (!data.rows.length) {
                    tbody.innerHTML = '<tr><td colspan="20">Nenhum dado para esta combinação de filtros.</td></tr>';
                    return;
                }
                data.rows.forEach(function (r) {
                    var html = '<tr><td>' + r.staff_name + '</td>';
                    data.statuses.forEach(function (s) {
                        html += '<td>' + ((r.counts && r.counts[s.id] !== undefined) ? r.counts[s.id] : 0) + '</td>';
                    });
                    html += '<td><b>' + r.total + '</b></td></tr>';
                    tbody.innerHTML += html;
                });
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="20">Erro ao carregar relatório.</td></tr>';
            });
    }

    function exportLeadSourceExcel() {
        window.location.href = admin_url + 'leads_imo/export_excel'
            + '?group=' + encodeURIComponent(getSourceGroup('src_'))
            + '&from='  + encodeURIComponent(getDateFrom('src_'))
            + '&to='    + encodeURIComponent(getDateTo('src_'));
    }

    /* ------------------------------------------------------------------ */
    /*  Clique nos botões de status — distribuição por comercial           */
    /* ------------------------------------------------------------------ */
    var overview = document.querySelector('.leads-overview');
    if (overview) {
        // MutationObserver: apanha os botões após o Vue os renderizar
        var clickAttached = false;
        var mo = new MutationObserver(function () {
            overview.querySelectorAll('button:not([data-imo-tagged])').forEach(function (btn) {
                // Span com color = nome do status
                var nameSpan = btn.querySelector('span[style*="color"]');
                if (!nameSpan) return;

                var statusName = nameSpan.textContent.trim();
                var statusId   = STATUS_MAP[statusName];
                if (!statusId) return;

                btn.setAttribute('data-imo-tagged',  '1');
                btn.setAttribute('data-status-id',   statusId);
                btn.setAttribute('data-status-name', statusName);
            });

            if (!clickAttached) {
                clickAttached = true;
                overview.addEventListener('click', function (e) {
                    var btn = e.target.closest('button[data-status-id]');
                    if (!btn) return;

                    var statusId   = btn.getAttribute('data-status-id');
                    var statusName = btn.getAttribute('data-status-name');

                    var titleEl = document.getElementById('statusDistTitle');
                    if (titleEl) titleEl.textContent = 'Distribuição — ' + statusName;

                    window._imoStatusId = statusId;

                    $('#statusDistModal').modal('show');
                    loadStatusDistribution();
                });
            }
        });
        mo.observe(overview, { childList: true, subtree: true });
    }

    /* ------------------------------------------------------------------ */
    /*  Distribuição por comercial para estado seleccionado                */
    /* ------------------------------------------------------------------ */
    function loadStatusDistribution() {
        var tbody = document.querySelector('#statusDistTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="3">A carregar...</td></tr>';

        var statusId = window._imoStatusId || 0;

        var url = admin_url + 'leads_imo/status_distribution'
            + '?status_id=' + encodeURIComponent(statusId)
            + '&group='     + encodeURIComponent(getSourceGroup('dist_'))
            + '&from='      + encodeURIComponent(getDateFrom('dist_'))
            + '&to='        + encodeURIComponent(getDateTo('dist_'));

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                tbody.innerHTML = '';
                if (!data.rows || !data.rows.length) {
                    tbody.innerHTML = '<tr><td colspan="3">Sem leads neste estado para a fonte seleccionada.</td></tr>';
                    return;
                }

                var total = 0;
                data.rows.forEach(function (r, i) {
                    total += r.total;
                    tbody.innerHTML += '<tr>'
                        + '<td>' + (i + 1) + '</td>'
                        + '<td>' + r.staff_name + '</td>'
                        + '<td><b>' + r.total + '</b></td>'
                        + '</tr>';
                });
                tbody.innerHTML += '<tr class="active">'
                    + '<td colspan="2"><b>Total</b></td>'
                    + '<td><b>' + total + '</b></td>'
                    + '</tr>';
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="3">Erro ao carregar.</td></tr>';
            });
    }

});
</script>

<!-- ================================================================ -->
<!-- Modal: Fonte de lead — Comerciais × Statuses                      -->
<!-- ================================================================ -->
<div class="modal fade" id="leadSourceReportModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Fonte de lead — Comerciais e estados</h4>
      </div>
      <div class="modal-body">
        <div class="row mbot15">
          <div class="col-md-3">
            <label>Fonte da lead</label>
            <select id="src_lead_source_group" class="form-control">
              <option value="expansao_imo" selected>IMO Portugal</option>
              <option value="imo">IMO Brasil</option>
              <option value="imo_dubai">IMO Dubai</option>
              <option value="bsx">BSX</option>
              <option value="contacto_pessoal">Contacto Pessoal</option>
            </select>
          </div>
          <div class="col-md-3">
            <label>Data início</label>
            <input type="date" id="src_lead_source_from" class="form-control">
          </div>
          <div class="col-md-3">
            <label>Data fim</label>
            <input type="date" id="src_lead_source_to" class="form-control">
          </div>
          <div class="col-md-3" style="padding-top:25px;">
            <button class="btn btn-primary" id="lead-source-load-btn">Carregar</button>
            <button class="btn btn-default mleft5" id="lead-source-export-btn">Exportar Excel</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped" id="leadSourceReportTable">
            <thead><tr><th>Comercial</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <p class="text-muted">
          Movimentação de leads por comercial no período seleccionado.
        </p>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================ -->
<!-- Modal: Distribuição por comercial para um estado específico       -->
<!-- ================================================================ -->
<div class="modal fade" id="statusDistModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="statusDistTitle">Distribuição por comercial</h4>
      </div>
      <div class="modal-body">
        <div class="row mbot15">
          <div class="col-md-4">
            <label>Fonte da lead</label>
            <select id="dist_lead_source_group" class="form-control">
              <option value="expansao_imo" selected>IMO Portugal</option>
              <option value="imo">IMO Brasil</option>
              <option value="imo_dubai">IMO Dubai</option>
              <option value="bsx">BSX</option>
              <option value="contacto_pessoal">Contacto Pessoal</option>
            </select>
          </div>
          <div class="col-md-3">
            <label>Data início</label>
            <input type="date" id="dist_lead_source_from" class="form-control">
          </div>
          <div class="col-md-3">
            <label>Data fim</label>
            <input type="date" id="dist_lead_source_to" class="form-control">
          </div>
          <div class="col-md-2" style="padding-top:25px;">
            <button class="btn btn-primary btn-sm" id="status-dist-load-btn">Filtrar</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover" id="statusDistTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Comercial</th>
                <th>Leads</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
    }
}
