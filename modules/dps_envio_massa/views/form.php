<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">

                <!-- Cabeçalho -->
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #f0f0f0;">
                    <div style="background:linear-gradient(135deg,#f97316,#ea580c);border-radius:12px;width:48px;height:48px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-paper-plane" style="color:#fff;font-size:20px;"></i>
                    </div>
                    <div>
                        <h3 style="margin:0;font-size:1.3rem;font-weight:700;color:#1a1a1a;">Envio em Massa</h3>
                        <p style="margin:0;color:#888;font-size:0.9rem;">Envie documentos e emails para os contactos das suas tarefas</p>
                    </div>
                </div>

                <!-- Alertas -->
                <?php echo $this->load->view('alerts', [], true); ?>

                <form method="POST" action="<?= admin_url('dps_envio_massa/enviar'); ?>" enctype="multipart/form-data" id="form-envio-massa">
                    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

                    <div class="row">

                        <!-- COLUNA ESQUERDA: Configurações -->
                        <div class="col-md-5">

                            <!-- 1. Estado da Tarefa -->
                            <div class="form-group">
                                <label style="font-weight:700;color:#333;font-size:0.95rem;">
                                    <i class="fa fa-filter" style="color:#f97316;margin-right:6px;"></i>
                                    Estado da Tarefa
                                </label>
                                <select name="task_status" id="task_status" class="form-control selectpicker" data-live-search="false" required>
                                    <option value="">-- Seleccione um estado --</option>
                                    <?php foreach ($task_statuses as $id => $nome): ?>
                                        <option value="<?= $id; ?>"><?= htmlspecialchars($nome); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    <?= $is_admin ? 'Como administrador, vê as tarefas de todos os comerciais.' : 'Vê apenas as suas tarefas.'; ?>
                                </small>
                            </div>

                            <!-- 2. Upload do Documento -->
                            <div class="form-group">
                                <label style="font-weight:700;color:#333;font-size:0.95rem;">
                                    <i class="fa fa-paperclip" style="color:#f97316;margin-right:6px;"></i>
                                    Documento (opcional)
                                </label>
                                <div style="border:2px dashed #e0e0e0;border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:border-color .2s;" id="drop-zone">
                                    <i class="fa fa-cloud-upload" style="font-size:28px;color:#ccc;margin-bottom:8px;display:block;"></i>
                                    <p style="margin:0;color:#888;font-size:0.9rem;">Arraste um ficheiro ou clique para seleccionar</p>
                                    <p style="margin:4px 0 0;color:#bbb;font-size:0.8rem;">PDF, Word, Excel, imagens (máx. 10MB)</p>
                                    <input type="file" name="documento" id="documento" style="display:none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                </div>
                                <div id="file-preview" style="display:none;margin-top:8px;padding:8px 12px;background:#f8f9fa;border-radius:6px;display:flex;align-items:center;gap:8px;">
                                    <i class="fa fa-file" style="color:#f97316;"></i>
                                    <span id="file-name" style="font-size:0.9rem;color:#333;"></span>
                                    <button type="button" onclick="clearFile()" style="margin-left:auto;background:none;border:none;color:#999;cursor:pointer;font-size:16px;">&times;</button>
                                </div>
                            </div>

                            <!-- 3. Assunto -->
                            <div class="form-group">
                                <label style="font-weight:700;color:#333;font-size:0.95rem;">
                                    <i class="fa fa-tag" style="color:#f97316;margin-right:6px;"></i>
                                    Assunto do Email <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="assunto" id="assunto" class="form-control" placeholder="Ex: Documentação importante para o seu processo" required>
                            </div>

                            <!-- 4. Corpo do Email -->
                            <div class="form-group">
                                <label style="font-weight:700;color:#333;font-size:0.95rem;">
                                    <i class="fa fa-envelope" style="color:#f97316;margin-right:6px;"></i>
                                    Mensagem <span class="text-danger">*</span>
                                </label>
                                <textarea name="corpo" id="corpo" class="form-control" rows="8" placeholder="Escreva aqui a mensagem a enviar aos destinatários..." required style="resize:vertical;"></textarea>
                            </div>

                            <!-- Campo oculto com emails seleccionados -->
                            <input type="hidden" name="emails_selecionados" id="emails_selecionados">

                        </div>

                        <!-- COLUNA DIREITA: Destinatários -->
                        <div class="col-md-7">
                            <div style="background:#f8f9fa;border-radius:10px;padding:20px;min-height:400px;">

                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                                    <h5 style="margin:0;font-weight:700;color:#333;">
                                        <i class="fa fa-users" style="color:#f97316;margin-right:6px;"></i>
                                        Destinatários
                                    </h5>
                                    <div id="contador-badge" style="display:none;background:#f97316;color:#fff;border-radius:20px;padding:3px 12px;font-size:0.85rem;font-weight:700;">
                                        <span id="contador-sel">0</span> seleccionados
                                    </div>
                                </div>

                                <!-- Estado inicial -->
                                <div id="estado-inicial" style="text-align:center;padding:60px 20px;color:#bbb;">
                                    <i class="fa fa-filter" style="font-size:40px;margin-bottom:12px;display:block;"></i>
                                    <p>Seleccione um estado de tarefa para ver os destinatários</p>
                                </div>

                                <!-- Loading -->
                                <div id="estado-loading" style="display:none;text-align:center;padding:60px 20px;color:#888;">
                                    <i class="fa fa-spinner fa-spin" style="font-size:30px;margin-bottom:12px;display:block;color:#f97316;"></i>
                                    <p>A carregar destinatários...</p>
                                </div>

                                <!-- Sem resultados -->
                                <div id="estado-vazio" style="display:none;text-align:center;padding:60px 20px;color:#bbb;">
                                    <i class="fa fa-inbox" style="font-size:40px;margin-bottom:12px;display:block;"></i>
                                    <p>Nenhum destinatário encontrado com email válido para este estado.</p>
                                </div>

                                <!-- Lista de destinatários -->
                                <div id="lista-destinatarios" style="display:none;">
                                    <!-- Barra de acções -->
                                    <div style="display:flex;gap:8px;margin-bottom:12px;align-items:center;">
                                        <button type="button" onclick="selectAll()" class="btn btn-xs btn-default">
                                            <i class="fa fa-check-square-o"></i> Seleccionar todos
                                        </button>
                                        <button type="button" onclick="deselectAll()" class="btn btn-xs btn-default">
                                            <i class="fa fa-square-o"></i> Desseleccionar todos
                                        </button>
                                        <span id="total-badge" style="margin-left:auto;color:#888;font-size:0.85rem;"></span>
                                    </div>

                                    <!-- Pesquisa rápida -->
                                    <div style="margin-bottom:10px;">
                                        <input type="text" id="pesquisa-dest" class="form-control input-sm" placeholder="&#xf002; Pesquisar por nome ou email..." style="font-family:FontAwesome,sans-serif;">
                                    </div>

                                    <!-- Tabela -->
                                    <div style="max-height:380px;overflow-y:auto;border-radius:8px;border:1px solid #e8e8e8;background:#fff;">
                                        <table class="table table-hover" style="margin:0;font-size:0.88rem;">
                                            <thead style="position:sticky;top:0;background:#f5f5f5;z-index:1;">
                                                <tr>
                                                    <th style="width:36px;padding:10px 12px;">
                                                        <input type="checkbox" id="check-all" onchange="toggleAll(this)">
                                                    </th>
                                                    <th style="padding:10px 12px;">Nome</th>
                                                    <th style="padding:10px 12px;">Email</th>
                                                    <th style="padding:10px 12px;">Comercial</th>
                                                    <th style="padding:10px 12px;">Tarefa</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-destinatarios"></tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- Botão de Envio -->
                            <div style="margin-top:20px;text-align:right;">
                                <button type="submit" id="btn-enviar" class="btn btn-primary btn-lg" disabled style="background:linear-gradient(135deg,#f97316,#ea580c);border:none;border-radius:8px;padding:12px 32px;font-weight:700;font-size:1rem;">
                                    <i class="fa fa-paper-plane" style="margin-right:8px;"></i>
                                    Enviar Email em Massa
                                </button>
                                <p style="margin-top:8px;color:#888;font-size:0.82rem;">
                                    <i class="fa fa-info-circle"></i>
                                    Seleccione pelo menos um destinatário para activar o envio.
                                </p>
                            </div>

                        </div>
                    </div><!-- /row -->

                </form>

            </div>
        </div>
    </div>
</div>

<script>
var todosDestinatarios = [];

// ---------------------------------------------------------------
// Upload de ficheiro
// ---------------------------------------------------------------
document.getElementById('drop-zone').addEventListener('click', function() {
    document.getElementById('documento').click();
});
document.getElementById('drop-zone').addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.borderColor = '#f97316';
});
document.getElementById('drop-zone').addEventListener('dragleave', function() {
    this.style.borderColor = '#e0e0e0';
});
document.getElementById('drop-zone').addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.borderColor = '#e0e0e0';
    var files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('documento').files = files;
        showFilePreview(files[0].name);
    }
});
document.getElementById('documento').addEventListener('change', function() {
    if (this.files.length > 0) {
        showFilePreview(this.files[0].name);
    }
});
function showFilePreview(name) {
    document.getElementById('file-name').textContent = name;
    document.getElementById('file-preview').style.display = 'flex';
    document.getElementById('drop-zone').style.display = 'none';
}
function clearFile() {
    document.getElementById('documento').value = '';
    document.getElementById('file-preview').style.display = 'none';
    document.getElementById('drop-zone').style.display = 'block';
}

// ---------------------------------------------------------------
// Carregar destinatários via AJAX
// ---------------------------------------------------------------
document.getElementById('task_status').addEventListener('change', function() {
    var status = this.value;
    if (!status) return;

    mostrarEstado('loading');

    $.ajax({
        url: '<?= admin_url('dps_envio_massa/get_destinatarios'); ?>',
        method: 'POST',
        data: {
            task_status: status,
            '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(resp) {
            if (!resp.success) {
                mostrarEstado('vazio');
                return;
            }
            todosDestinatarios = resp.destinatarios;
            if (todosDestinatarios.length === 0) {
                mostrarEstado('vazio');
                return;
            }
            renderDestinatarios(todosDestinatarios);
            mostrarEstado('lista');
            document.getElementById('total-badge').textContent = resp.total + ' destinatário(s) encontrado(s)';
        },
        error: function() {
            mostrarEstado('vazio');
        }
    });
});

function mostrarEstado(estado) {
    document.getElementById('estado-inicial').style.display  = 'none';
    document.getElementById('estado-loading').style.display  = 'none';
    document.getElementById('estado-vazio').style.display    = 'none';
    document.getElementById('lista-destinatarios').style.display = 'none';
    if (estado === 'loading') document.getElementById('estado-loading').style.display = 'block';
    else if (estado === 'vazio') document.getElementById('estado-vazio').style.display = 'block';
    else if (estado === 'lista') document.getElementById('lista-destinatarios').style.display = 'block';
    else document.getElementById('estado-inicial').style.display = 'block';
}

function renderDestinatarios(lista) {
    var tbody = document.getElementById('tbody-destinatarios');
    tbody.innerHTML = '';
    lista.forEach(function(d, i) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-email', d.email.toLowerCase());
        tr.setAttribute('data-nome', d.nome.toLowerCase());
        tr.innerHTML =
            '<td style="padding:8px 12px;"><input type="checkbox" class="check-dest" value="' + escHtml(d.email) + '" onchange="updateSeleccionados()"></td>' +
            '<td style="padding:8px 12px;font-weight:600;">' + escHtml(d.nome) + '</td>' +
            '<td style="padding:8px 12px;color:#555;">' + escHtml(d.email) + '</td>' +
            '<td style="padding:8px 12px;"><span style="background:#f0f0f0;border-radius:4px;padding:2px 8px;font-size:0.8rem;">' + escHtml(d.comercial) + '</span></td>' +
            '<td style="padding:8px 12px;color:#888;font-size:0.82rem;">' + escHtml(d.task_name) + '</td>';
        tbody.appendChild(tr);
    });
    updateSeleccionados();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ---------------------------------------------------------------
// Selecção de destinatários
// ---------------------------------------------------------------
function selectAll() {
    document.querySelectorAll('.check-dest:not([style*="display:none"])').forEach(function(cb) {
        var tr = cb.closest('tr');
        if (tr.style.display !== 'none') cb.checked = true;
    });
    document.getElementById('check-all').checked = true;
    updateSeleccionados();
}
function deselectAll() {
    document.querySelectorAll('.check-dest').forEach(function(cb) { cb.checked = false; });
    document.getElementById('check-all').checked = false;
    updateSeleccionados();
}
function toggleAll(cb) {
    document.querySelectorAll('.check-dest').forEach(function(c) {
        var tr = c.closest('tr');
        if (tr.style.display !== 'none') c.checked = cb.checked;
    });
    updateSeleccionados();
}
function updateSeleccionados() {
    var checked = document.querySelectorAll('.check-dest:checked');
    var emails  = Array.from(checked).map(function(cb) { return cb.value; });
    document.getElementById('emails_selecionados').value = emails.join(',');
    var n = emails.length;
    document.getElementById('contador-sel').textContent = n;
    document.getElementById('contador-badge').style.display = n > 0 ? 'inline-block' : 'none';
    document.getElementById('btn-enviar').disabled = n === 0;
}

// ---------------------------------------------------------------
// Pesquisa rápida
// ---------------------------------------------------------------
document.getElementById('pesquisa-dest').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#tbody-destinatarios tr').forEach(function(tr) {
        var email = tr.getAttribute('data-email') || '';
        var nome  = tr.getAttribute('data-nome') || '';
        tr.style.display = (email.includes(q) || nome.includes(q)) ? '' : 'none';
    });
});

// ---------------------------------------------------------------
// Confirmação antes de enviar
// ---------------------------------------------------------------
document.getElementById('form-envio-massa').addEventListener('submit', function(e) {
    var n = document.querySelectorAll('.check-dest:checked').length;
    if (n === 0) {
        e.preventDefault();
        alert('Seleccione pelo menos um destinatário.');
        return;
    }
    if (!confirm('Vai enviar um email para ' + n + ' destinatário(s). Confirma?')) {
        e.preventDefault();
    }
});
</script>
