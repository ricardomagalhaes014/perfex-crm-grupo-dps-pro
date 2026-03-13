<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('leads_imo_inject_ui')) {
    function leads_imo_inject_ui()
    {
        $CI = &get_instance();

        // Nunca injeta nada em chamadas AJAX (ex.: /admin/leads/table)
        if ($CI->input->is_ajax_request()) {
            return;
        }

        // Só na página principal de leads: /admin/leads
        if ($CI->uri->segment(2) !== 'leads') {
            return;
        }
        ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // DIV onde ficam "+ Novo Lead" e "Importar Leads"
    var buttonsDiv = document.querySelector('._buttons');
    if (!buttonsDiv) return;

    // Evita criar o botão 2x
    if (document.getElementById('lead-source-btn')) {
        return;
    }

    // Botão principal: "Fonte de lead"
    var fonteBtn = document.createElement('a');
    fonteBtn.className = 'btn btn-default mleft5';
    fonteBtn.id = 'lead-source-btn';
    fonteBtn.href = 'javascript:void(0)';
    fonteBtn.innerText = 'Fonte de lead';
    buttonsDiv.appendChild(fonteBtn);

    // Abrir modal
    fonteBtn.addEventListener('click', function() {
        $('#leadSourceReportModal').modal('show');
        loadLeadSourceReport();
    });

    // Clicks dentro do modal
    document.addEventListener('click', function(e){
        if (e.target && e.target.id === 'lead-source-load-btn') {
            loadLeadSourceReport();
        }
        if (e.target && e.target.id === 'lead-source-export-btn') {
            exportLeadSourceExcel();
        }
    });

    function buildUrl(base) {
        var group = document.querySelector('#lead_source_group').value;
        var from  = document.querySelector('#lead_source_from').value;
        var to    = document.querySelector('#lead_source_to').value;

        var url = base + '?group=' + encodeURIComponent(group);

        if (from) {
            url += '&from=' + encodeURIComponent(from);
        }
        if (to) {
            url += '&to=' + encodeURIComponent(to);
        }

        return url;
    }

    function loadLeadSourceReport() {
        var tbody    = document.querySelector('#leadSourceReportTable tbody');
        var theadRow = document.querySelector('#leadSourceReportTable thead tr');

        tbody.innerHTML    = '<tr><td colspan="20">A carregar...</td></tr>';
        theadRow.innerHTML = '<th>Comercial</th>';

        var url = buildUrl(admin_url + 'leads_imo/source_report');

        fetch(url)
          .then(function(r) { return r.json(); })
          .then(function(data) {
              // Cabeçalho: Comercial + todos os status + Total
              theadRow.innerHTML = '<th>Comercial</th>';
              data.statuses.forEach(function(s){
                  theadRow.innerHTML += '<th>'+ s.name +'</th>';
              });
              theadRow.innerHTML += '<th>Total</th>';

              // Corpo
              tbody.innerHTML = '';
              if (!data.rows.length) {
                  tbody.innerHTML = '<tr><td colspan="20">Nenhum dado para esta combinação de filtros.</td></tr>';
                  return;
              }

              data.rows.forEach(function(r){
                  var rowHtml = '<tr><td>'+ r.staff_name +'</td>';

                  data.statuses.forEach(function(s){
                      var val = (r.counts && typeof r.counts[s.id] !== "undefined")
                          ? r.counts[s.id]
                          : 0;
                      rowHtml += '<td>'+ val +'</td>';
                  });

                  rowHtml += '<td><b>'+ r.total +'</b></td>';
                  rowHtml += '</tr>';
                  tbody.innerHTML += rowHtml;
              });
          })
          .catch(function(){
              tbody.innerHTML = '<tr><td colspan="20">Erro ao carregar relatório.</td></tr>';
          });
    }

    function exportLeadSourceExcel() {
        var url = buildUrl(admin_url + 'leads_imo/export_excel');
        // Abre o download do CSV/Excel
        window.location.href = url;
    }

});
</script>

<!-- Modal / Popup -->
<div class="modal fade" id="leadSourceReportModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Fonte de lead — Comerciais e status</h4>
      </div>

      <div class="modal-body">

        <!-- Filtros: Fonte + Datas -->
        <div class="row mbot15">
          <div class="col-md-3">
            <label>Fonte da lead</label>
            <select id="lead_source_group" class="form-control">
              <option value="imo">IMO (Google + Meta)</option>
              <option value="expansao_imo">EXPANSAO IMO</option>
              <option value="media">MEDIA</option>
              <option value="dental">DENTARIA</option>
            </select>
          </div>

          <div class="col-md-3">
            <label>Data início</label>
            <input type="date" id="lead_source_from" class="form-control">
          </div>

          <div class="col-md-3">
            <label>Data fim</label>
            <input type="date" id="lead_source_to" class="form-control">
          </div>

          <div class="col-md-3" style="padding-top:25px;">
            <button class="btn btn-primary" id="lead-source-load-btn">Carregar</button>
            <button class="btn btn-default mleft5" id="lead-source-export-btn">Exportar Excel</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped" id="leadSourceReportTable">
            <thead>
              <tr>
                <th>Comercial</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>

        <p class="text-muted">
          Use as datas para ver a movimentação das leads em determinado período.
          O botão "Exportar Excel" baixa o mesmo relatório em CSV para abrir no Excel/Sheets.
        </p>

      </div>
    </div>
  </div>
</div>

<?php
    }
}
