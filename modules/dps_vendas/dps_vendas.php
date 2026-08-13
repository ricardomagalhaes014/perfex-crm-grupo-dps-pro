<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Vendas & Comissões
Description: Processo de venda (documentos, workflow de estados) sobre a tabela de vendas do simulador, com regras de comissão por empreendimento e Arquivo de documentos por empreendimento.
Version: 1.6.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_VENDAS_MODULE_NAME', 'dps_vendas');

/*
 * Estados de lead que este módulo mexe. Ver a nota em Dps_vendas::marcar_pago:
 * a lead só chega a Concretizado quando o pagamento é confirmado.
 */
/*
 * ATENÇÃO aos nomes: já existe mais abaixo DPS_VENDAS_ESTADO_CONTRATO, e essa
 * guarda o NOME do estado ('PARA CONTRATO') porque é por nome que se procura o
 * id em tblleads_status. Estas duas guardam IDS. Ter as duas com o mesmo nome
 * fazia a primeira ganhar e a segunda ficar com o valor errado em silêncio —
 * e o quadro de reserva deixava de abrir, porque procurava um estado chamado
 * "10". Foi um engano meu de hoje (03/08/2026).
 */
defined('DPS_VENDAS_ESTADO_CONTRATO_ID') || define('DPS_VENDAS_ESTADO_CONTRATO_ID', 10);
defined('DPS_VENDAS_ESTADO_CONCRETIZADO_ID') || define('DPS_VENDAS_ESTADO_CONCRETIZADO_ID', 13);
define('DPS_VENDAS_VERSION', '1.8.0');
define('DPS_VENDAS_UPLOAD_PATH', 'uploads/dps_vendas/');
define('DPS_ARQUIVO_UPLOAD_PATH', 'uploads/dps_arquivo/');

register_activation_hook(DPS_VENDAS_MODULE_NAME, 'dps_vendas_activate');

// O helper é usado nas vistas (cores de estado, nomes legíveis), por isso tem
// de estar disponível antes de qualquer uma delas ser renderizada.
get_instance()->load->helper(DPS_VENDAS_MODULE_NAME . '/dps_vendas');

hooks()->add_action('admin_init', 'dps_vendas_ensure_schema');
hooks()->add_action('admin_init', 'dps_vendas_menu');
hooks()->add_action('admin_init', 'dps_vendas_permissions');

function dps_vendas_activate()
{
    require_once __DIR__ . '/install.php';
}

/**
 * Migração automática de esquema, sem obrigar a desactivar/reactivar o módulo.
 *
 * Corre no máximo uma vez por versão: quando a opção guardada já iguala a
 * versão actual, sai imediatamente (custo nulo nos pedidos seguintes). Serve
 * para acrescentar as colunas do circuito CPCV/pagamento e alargar o `tipo`
 * dos documentos depois de um deploy só de ficheiros.
 */
function dps_vendas_ensure_schema()
{
    if (get_option('dps_vendas_schema_version') === DPS_VENDAS_VERSION) {
        return;
    }

    $CI     = &get_instance();
    $vendas = db_prefix() . 'simulador_vendas';
    $docs   = db_prefix() . 'vendas_docs';

    if ($CI->db->table_exists($vendas)) {
        $existing = array_map(function ($f) {
            return $f->name;
        }, $CI->db->field_data($vendas));

        $novas = [
            'cpcv_assinado'    => 'TINYINT(1) NOT NULL DEFAULT 0',
            'cpcv_assinado_em' => 'DATETIME NULL DEFAULT NULL',
            'pago'             => 'TINYINT(1) NOT NULL DEFAULT 0',
            'pago_em'          => 'DATETIME NULL DEFAULT NULL',
            // Circuito do pagamento da comissão AO COMERCIAL: ele anexa o
            // recibo, a direção marca PAGO com a data em que pagou.
            'comissao_recibo_doc'  => 'INT NULL DEFAULT NULL',
            'comissao_pago_dps'    => 'TINYINT(1) NOT NULL DEFAULT 0',
            'comissao_pago_dps_em' => 'DATE NULL DEFAULT NULL',
            /*
             * v1.5.0 — a comissão passa a ser paga em DUAS parcelas (CPCV e
             * Escritura), cada uma com o seu mês previsto de recebimento.
             *
             * O mês fica em VARCHAR(7) ('2026-11') e não numa DATE porque o
             * que a direção sabe é o mês, não o dia: forçar um dia obrigava a
             * inventar um (o dia 1) e depois já ninguém sabia se era real.
             * Mês VAZIO = pago na hora, entra na previsão como "sem data".
             */
            /*
             * v1.7.0 — RECEBIMENTO DA DPS.
             *
             * Marca posta à mão pela direção quando o promotor paga. Antes o
             * Painel do Negócio adivinhava pelo mês previsto da regra: se o mês
             * já tinha chegado, assumia que o dinheiro tinha entrado. Era um
             * palpite, e um palpite otimista — punha em caixa dinheiro que
             * podia estar atrasado.
             *
             * Regra do dono (29/07/2026): "só recebemos em caixa quando tem
             * esse visto que eu coloco".
             *
             * A DATA é o que permite o filtro por mês no painel, por isso é
             * gravada mesmo (não se deduz do datetime da marca).
             */
            'recebido_dps'     => 'TINYINT(1) NOT NULL DEFAULT 0',
            'recebido_dps_em'  => 'DATE NULL DEFAULT NULL',
            'recebido_dps_por' => 'INT NULL DEFAULT NULL',
            /*
             * v1.8.0 — DADOS PARA O CPCV (só usados no Aura, por enquanto).
             *
             * O contrato-promessa do Meixomil identifica o comprador com NIF,
             * n.º e validade do Cartão de Cidadão, naturalidade, nacionalidade,
             * freguesia e concelho. Nada disto vinha da reserva e tinha de ser
             * copiado à mão do CC fotografado — trabalho repetido e com erros.
             *
             * As colunas existem para todos os empreendimentos porque a tabela
             * é uma só; quem as PEDE no formulário é apenas o Aura.
             */
            'cliente_nif'          => 'VARCHAR(30) NULL DEFAULT NULL',
            'cliente_cc'           => 'VARCHAR(40) NULL DEFAULT NULL',
            'cliente_cc_validade'  => 'DATE NULL DEFAULT NULL',
            'cliente_naturalidade' => 'VARCHAR(120) NULL DEFAULT NULL',
            'cliente_nacionalidade'=> 'VARCHAR(60) NULL DEFAULT NULL',
            'cliente_freguesia'    => 'VARCHAR(120) NULL DEFAULT NULL',
            'cliente_concelho'     => 'VARCHAR(120) NULL DEFAULT NULL',
            // Código postal do cliente: é preciso para o CPCV e para a
            // escritura, e sem ele o promotor tem de andar a pedi-lo.
            'cliente_codigo_postal'  => 'VARCHAR(20) NULL DEFAULT NULL',
            'cpcv_mes_previsto'      => "VARCHAR(7) NULL DEFAULT NULL",
            'escritura_mes_previsto' => "VARCHAR(7) NULL DEFAULT NULL",
            'cpcv_pago'              => 'TINYINT(1) NOT NULL DEFAULT 0',
            'cpcv_pago_em'           => 'DATE NULL DEFAULT NULL',
            'escritura_paga'         => 'TINYINT(1) NOT NULL DEFAULT 0',
            'escritura_paga_em'      => 'DATE NULL DEFAULT NULL',
        ];

        foreach ($novas as $coluna => $definicao) {
            if (!in_array($coluna, $existing, true)) {
                $CI->db->query("ALTER TABLE `{$vendas}` ADD `{$coluna}` {$definicao}");
            }
        }
    }

    if ($CI->db->table_exists($docs)) {
        $CI->db->query("ALTER TABLE `{$docs}` MODIFY `tipo` VARCHAR(40) NOT NULL DEFAULT 'outro'");
    }

    // ---- Arquivo (v1.3.0): pastas por empreendimento + documentos ----
    $pastas = db_prefix() . 'dps_arquivo_pastas';
    $adocs  = db_prefix() . 'dps_arquivo_docs';

    if (!$CI->db->table_exists($pastas)) {
        $CI->db->query("CREATE TABLE `{$pastas}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nome` VARCHAR(150) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `nome` (`nome`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Semear com os empreendimentos actuais; o admin pode criar mais.
        foreach (['Boavista Towers', 'Raízes Fanzeres', 'Belo Horizonte', 'Douro Mar', 'Aura Residence', 'Lake Towers'] as $nome_emp) {
            $CI->db->insert($pastas, ['nome' => $nome_emp, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }

    if (!$CI->db->table_exists($adocs)) {
        $CI->db->query("CREATE TABLE `{$adocs}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `pasta_id` INT(11) NOT NULL,
            `nome` VARCHAR(191) NOT NULL,
            `filename` VARCHAR(191) NOT NULL,
            `tamanho` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `staff_id` INT(11) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `pasta_id` (`pasta_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // O table_exists() lá de cima deixou a lista de tabelas em cache SEM as
    // recém-criadas — e o menu (que corre já a seguir, no mesmo pedido)
    // consulta-a. Sem esta limpeza, o Arquivo aparecia sem filhos no primeiro
    // pedido após o deploy: precisamente o de quem vai verificar se saiu.
    $CI->db->data_cache = [];

    update_option('dps_vendas_schema_version', DPS_VENDAS_VERSION);
}

/**
 * Menu lateral com dois submenus: as vendas e as comissões a receber.
 * As Regras de Comissão ficam num terceiro item, só para quem pode geri-las.
 */
function dps_vendas_menu()
{
    $CI = &get_instance();

    // "Simulador" — atalho que leva a identidade do utilizador logado para o
    // simulador (dpsimobiliario.pt). Assim entra sem password e o simulador
    // sabe já quem está a reservar / a enviar propostas. Ver Dps_vendas::simulador().
    $CI->app_menu->add_sidebar_menu_item('dps_simulador', [
        'slug'     => 'dps_simulador',
        'name'     => 'Simulador',
        'href'     => admin_url('dps_vendas/simulador'),
        'icon'     => 'fa fa-building-o',
        'position' => 25,
    ]);

    $CI->app_menu->add_sidebar_menu_item('dps_vendas', [
        'slug'     => 'dps_vendas',
        'name'     => 'Vendas & Comissões',
        'icon'     => 'fa fa-handshake-o',
        'position' => 26,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_vendas', [
        'slug'     => 'dps_vendas_lista',
        'name'     => 'Vendas',
        'href'     => admin_url('dps_vendas'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_vendas', [
        'slug'     => 'dps_vendas_comissoes',
        'name'     => 'Comissões',
        'href'     => admin_url('dps_vendas/comissoes'),
        'position' => 2,
    ]);

    // "Arquivo" — documentos por empreendimento: o admin carrega, todos
    // descarregam. As pastas vêm da BD (o admin cria-as na própria página).
    $CI->app_menu->add_sidebar_menu_item('dps_arquivo', [
        'slug'     => 'dps_arquivo',
        'name'     => 'Arquivo',
        'icon'     => 'fa fa-folder-open',
        // Sem href o Perfex punha '#' e o clique caía no dashboard geral.
        'href'     => admin_url('dps_vendas/arquivo'),
        'position' => 27,
    ]);

    $tabela_pastas = db_prefix() . 'dps_arquivo_pastas';
    if ($CI->db->table_exists($tabela_pastas)) {
        $pos = 1;
        foreach ($CI->db->order_by('nome')->get($tabela_pastas)->result_array() as $pasta_menu) {
            $CI->app_menu->add_sidebar_children_item('dps_arquivo', [
                'slug'     => 'dps_arquivo_' . (int) $pasta_menu['id'],
                'name'     => $pasta_menu['nome'],
                'href'     => admin_url('dps_vendas/arquivo/' . (int) $pasta_menu['id']),
                'position' => $pos++,
            ]);
        }
    }

    if (is_admin() || staff_can('gerir_regras', 'dps_vendas')) {
        $CI->app_menu->add_sidebar_children_item('dps_vendas', [
            'slug'     => 'dps_vendas_regras',
            'name'     => 'Regras de Comissão',
            'href'     => admin_url('dps_vendas/regras'),
            'position' => 3,
        ]);
    }
}

/**
 * Capacidades do módulo.
 *
 * Por omissão um comercial vê e cria as suas vendas. Marcar como "Recebido"
 * (que liberta a comissão) e descarregar documentos de identificação são
 * acções sensíveis e ficam atrás de capacidades próprias — na prática, só
 * admin, a menos que sejam atribuídas explicitamente.
 */
function dps_vendas_permissions()
{
    $capabilities = [
        'view'          => 'Ver todas as vendas (não apenas as suas)',
        'create'        => 'Criar vendas',
        'edit'          => 'Editar vendas',
        'delete'        => 'Eliminar vendas',
        'marcar_recebido' => 'Marcar venda como Recebida (liberta a comissão)',
        'download_docs' => 'Descarregar documentos de identificação',
        'gerir_regras'  => 'Gerir regras de comissão',
    ];

    register_staff_capabilities('dps_vendas', $capabilities, 'DPS Vendas & Comissões');
}

/**
 * Botões "Proposta" e "Disponíveis" da coluna Funções (tabela de leads).
 *
 * Agem DIRETAMENTE, sem abrir a ficha do cliente (era o que obrigava o
 * comercial a dois passos extra):
 *   - Proposta    -> abre o simulador já com a lead identificada
 *   - Disponíveis -> escolhe-se o empreendimento e envia na hora
 */
hooks()->add_action('app_admin_footer', 'dps_vendas_js_abrir_lead');
function dps_vendas_js_abrir_lead()
{
    $CI  = &get_instance();
    $uri = $CI->uri->uri_string();

    if (strpos($uri, 'leads') === false) {
        return;
    }
    ?>
    <script>
    (function () {
      'use strict';

      var BASE = '<?php echo admin_url(); ?>';
      var CSRF_NOME = '<?php echo $CI->security->get_csrf_token_name(); ?>';

      function csrf() {
        return (typeof csrfData !== 'undefined' && csrfData && csrfData.hash)
          ? csrfData.hash
          : (document.querySelector('input[name="' + CSRF_NOME + '"]') || {}).value || '';
      }

      function aviso(tipo, msg) {
        if (typeof alert_float === 'function') { alert_float(tipo, msg); }
        else { alert(msg); }
      }

      function contexto(leadId, depois) {
        $.get(BASE + 'dps_propostas/contexto_lead/' + leadId, function (r) {
          try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
          if (!r || !r.success) { aviso('danger', (r && r.message) || 'Não consegui carregar a lead.'); return; }
          depois(r);
        }).fail(function () { aviso('danger', 'Erro de comunicação com o CRM.'); });
      }

      /* ---------- janela para escolher o empreendimento ---------- */
      function escolherEmp(ctx, titulo, apenasComProposta, aoEscolher) {
        var lista = (ctx.emps || []).filter(function (e) {
          return !apenasComProposta || e.tem_proposta;
        });

        if (!lista.length) { aviso('warning', 'Não há empreendimentos configurados.'); return; }

        var ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;inset:0;background:rgba(8,21,40,.65);z-index:2147483000;'
          + 'display:flex;align-items:center;justify-content:center;padding:20px;';

        var cx = document.createElement('div');
        cx.style.cssText = 'background:#fff;border-radius:12px;padding:22px 24px;max-width:380px;width:100%;'
          + 'box-shadow:0 20px 60px rgba(0,0,0,.3);font-family:inherit;';

        var opcoes = lista.map(function (e) {
          return '<option value="' + e.k + '">' + $('<span>').text(e.nome).html() + '</option>';
        }).join('');

        cx.innerHTML =
            '<div style="font-weight:700;font-size:1.05rem;margin-bottom:4px;">' + titulo + '</div>'
          + '<div style="color:#5a6675;font-size:.86rem;margin-bottom:16px;">'
          +   $('<span>').text(ctx.nome || ('Lead #' + ctx.lead_id)).html() + '</div>'
          + '<select class="form-control" id="dps-emp-escolha" style="margin-bottom:16px;">' + opcoes + '</select>'
          + '<div style="display:flex;gap:8px;">'
          +   '<button type="button" class="btn btn-info" id="dps-emp-ok" style="flex:1;">Continuar</button>'
          +   '<button type="button" class="btn btn-default" id="dps-emp-no">Cancelar</button>'
          + '</div>';

        ov.appendChild(cx);
        document.body.appendChild(ov);

        function fechar() { if (ov.parentNode) { ov.parentNode.removeChild(ov); } }
        cx.querySelector('#dps-emp-no').onclick = fechar;
        ov.addEventListener('click', function (ev) { if (ev.target === ov) { fechar(); } });
        cx.querySelector('#dps-emp-ok').onclick = function () {
          var emp = cx.querySelector('#dps-emp-escolha').value;
          fechar();
          aoEscolher(emp);
        };
      }

      /* ---------- Proposta: abre o simulador com a lead identificada ---------- */
      function abrirSimulador(ctx, emp) {
        var url = 'https://dpsimobiliario.pt/simuladorportugal/'
          + '?lead_id='  + ctx.lead_id
          + '&staff_id=' + ctx.staff_id
          + '&token='    + encodeURIComponent(ctx.token || '')
          + '&empreendimento=' + encodeURIComponent(emp || '')
          + '&nome='     + encodeURIComponent(ctx.nome || '')
          + '&telefone=' + encodeURIComponent(ctx.telefone || '')
          + '&_cb='      + Date.now();
        window.open(url, '_blank');
      }

      /* ---------- Canal: WhatsApp ou email ----------
       * Mesma pergunta que já se faz nas propostas. Antes as disponibilidades
       * iam sempre por WhatsApp: sem telefone, ou com a instância em baixo, não
       * havia alternativa nenhuma.
       */
      function escolherCanal(ctx, aoEscolher) {
        var ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:99999;'
          + 'display:flex;align-items:center;justify-content:center;padding:20px;';

        var cx = document.createElement('div');
        cx.style.cssText = 'background:#fff;border-radius:14px;padding:26px 28px;max-width:420px;width:100%;'
          + 'box-shadow:0 18px 50px rgba(0,0,0,.3);font-family:inherit;';

        var temTel = !!(ctx.telefone && String(ctx.telefone).replace(/[^0-9]/g, ''));
        var temMail = !!(ctx.email && String(ctx.email).indexOf('@') > 0);

        cx.innerHTML = '<div style="font-weight:700;font-size:1.05rem;margin-bottom:6px;">Como quer enviar?</div>'
          + '<div style="color:#5a6675;font-size:.86rem;margin-bottom:18px;">'
          +   $('<span>').text(ctx.nome || ('Lead #' + ctx.lead_id)).html() + '</div>'
          + '<button type="button" class="btn" id="dps-canal-wa" style="width:100%;background:#25D366;color:#fff;'
          +   'font-weight:700;padding:12px;margin-bottom:10px;"' + (temTel ? '' : ' disabled')
          +   '>💬 WhatsApp' + (temTel ? '' : ' — sem telefone') + '</button>'
          + '<button type="button" class="btn" id="dps-canal-mail" style="width:100%;background:#1d6fb8;color:#fff;'
          +   'font-weight:700;padding:12px;margin-bottom:14px;"' + (temMail ? '' : ' disabled')
          +   '>✉️ Email' + (temMail ? '' : ' — sem email') + '</button>'
          + '<button type="button" class="btn btn-default" id="dps-canal-no" style="width:100%;">Cancelar</button>';

        ov.appendChild(cx);
        document.body.appendChild(ov);

        function fechar() { if (ov.parentNode) { ov.parentNode.removeChild(ov); } }
        cx.querySelector('#dps-canal-no').onclick = fechar;
        ov.addEventListener('click', function (ev) { if (ev.target === ov) { fechar(); } });
        cx.querySelector('#dps-canal-wa').onclick = function () { fechar(); aoEscolher('whatsapp'); };
        cx.querySelector('#dps-canal-mail').onclick = function () { fechar(); aoEscolher('email'); };
      }

      /* ---------- Disponíveis: envia sem sair da tabela ---------- */
      function enviarDisponiveis(ctx, emp, canal) {
        aviso('info', 'A enviar as unidades disponíveis por ' + (canal === 'email' ? 'email' : 'WhatsApp') + '…');

        var dados = { lead_id: ctx.lead_id, empreendimento: emp, canal: canal };
        dados[CSRF_NOME] = csrf();

        $.post(BASE + 'dps_propostas/enviar_info', dados, function (r) {
          try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
          aviso(r && r.success ? 'success' : 'danger',
                (r && r.message) ? r.message : 'Não foi possível enviar.');
        }).fail(function () { aviso('danger', 'Erro de comunicação com o CRM.'); });
      }

      /*
       * Mantém-se o nome dps_abrirLead porque é o que está nos onclick das
       * linhas da tabela — mudou o COMPORTAMENTO, não a chamada.
       */
      window.dpsAbrirLead = function (id, alvo) {
        contexto(id, function (ctx) {
          if (alvo === 'proposta') {
            escolherEmp(ctx, 'Gerar proposta', true, function (emp) { abrirSimulador(ctx, emp); });
          } else {
            escolherEmp(ctx, 'Enviar unidades disponíveis', false, function (emp) {
              escolherCanal(ctx, function (canal) { enviarDisponiveis(ctx, emp, canal); });
            });
          }
        });
      };
    })();
    </script>
    <?php
}


/**
 * Atualiza a página quando o simulador avisa que uma proposta foi enviada.
 *
 * O envio muda o estado da lead no servidor, mas a página do CRM não sabe —
 * ficava com o estado antigo até se fazer refresh à mão. O simulador manda
 * um aviso (postMessage) e aqui recarrega-se só o que é preciso.
 */
hooks()->add_action('app_admin_footer', 'dps_vendas_js_proposta_enviada');
function dps_vendas_js_proposta_enviada()
{
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }
    ?>
    <script>
    (function () {
      'use strict';

      window.addEventListener('message', function (ev) {
        // Só se aceita o aviso vindo do simulador — nada mais.
        if (ev.origin !== 'https://dpsimobiliario.pt') { return; }

        var d = ev.data;
        if (!d || d.dps !== 'proposta-enviada') { return; }

        if (typeof alert_float === 'function') {
          alert_float('success', 'Proposta enviada — a lead passou a "PROPOSTAS ENVIADAS".');
        }

        // 1) Tabelas com carregamento por AJAX: recarregar sem perder página
        //    nem filtros. Não se fixa um selector — o id da tabela de leads
        //    mudou já mais do que uma vez entre versões do Perfex.
        try {
          if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('table').each(function () {
              if (jQuery.fn.DataTable.isDataTable(this)) {
                var api = jQuery(this).DataTable();
                // ajax.reload só existe em tabelas serverside
                if (api.ajax && typeof api.ajax.reload === 'function' && api.ajax.url()) {
                  api.ajax.reload(null, false);
                }
              }
            });
          }
        } catch (e) {}

        // 2) Ficha da lead aberta: recarregar para o estado e o histórico ficarem certos.
        try {
          if (d.lead_id && typeof init_lead === 'function') {
            var modal = document.querySelector('.modal.in');
            if (modal) { init_lead(parseInt(d.lead_id, 10)); }
          }
        } catch (e) {}

        // 3) Listas de propostas: são páginas normais, recarrega-se a página.
        try {
          if (/dps_propostas/.test(location.pathname)) {
            setTimeout(function () { location.reload(); }, 900);
          }
        } catch (e) {}
      });
    })();
    </script>
    <?php
}

/*
 * CPCV gerado e por assinar: avisar quem tem de agir.
 *
 * 72 horas depois de o contrato ser gerado, se ainda não houver validação de
 * assinatura, o comercial leva um WhatsApp. Às 96 horas, a direcção.
 *
 * A contagem é feita a partir da PRIMEIRA geração (cpcv_gerado_em) e pára
 * assim que a assinatura for validada (cpcv_assinado). Cada aviso é enviado
 * UMA vez — a data de envio fica gravada, senão o cron repetia-o de cinco em
 * cinco minutos até alguém desistir de ler as mensagens.
 *
 * Vendas canceladas não são avisadas: não há contrato para assinar.
 */
hooks()->add_action('after_cron_run', 'dps_vendas_cron_cpcv_por_assinar');

if (!function_exists('dps_vendas_cron_cpcv_por_assinar')) {
    function dps_vendas_cron_cpcv_por_assinar()
    {
        $CI = &get_instance();
        $t  = db_prefix() . 'simulador_vendas';

        $avisos = [
            // coluna de controlo => [horas, a quem, texto]
            'cpcv_aviso_72h' => [72, 'comercial'],
            'cpcv_aviso_96h' => [96, 'direcao'],
        ];

        foreach ($avisos as $coluna => $cfg) {
            list($horas, $quem) = $cfg;

            $vendas = $CI->db
                ->select('id, empreendimento, unidade, cliente, staff_id, cpcv_gerado_em')
                ->from($t)
                ->where('cpcv_gerado_em IS NOT NULL')
                ->where('cpcv_gerado_em <=', date('Y-m-d H:i:s', strtotime('-' . $horas . ' hours')))
                ->where('(cpcv_assinado IS NULL OR cpcv_assinado = 0)')
                ->where('estado !=', 'cancelado')
                ->where($coluna . ' IS NULL')
                ->get()->result_array();

            foreach ($vendas as $v) {
                $dias = round((time() - strtotime($v['cpcv_gerado_em'])) / 3600);

                $texto = 'CPCV por assinar há ' . $dias . ' horas — '
                    . $v['empreendimento'] . ', fracção ' . $v['unidade']
                    . ' (' . $v['cliente'] . ').';

                if ($quem === 'comercial') {
                    dps_vendas_notificar(
                        (int) $v['staff_id'],
                        $texto . ' Confirme com o cliente e valide a assinatura no CRM.',
                        'dps_vendas/view/' . (int) $v['id']
                    );
                } else {
                    dps_vendas_notificar_admins(
                        $texto . ' O comercial já foi avisado às 72h.',
                        'dps_vendas/view/' . (int) $v['id']
                    );
                }

                $CI->db->where('id', (int) $v['id'])
                       ->update($t, [$coluna => date('Y-m-d H:i:s')]);

                log_activity('Venda #' . (int) $v['id'] . ' — aviso de CPCV por assinar ('
                    . $horas . 'h) enviado a ' . $quem);
            }
        }
    }
}

/*
 * Vendas concluídas que ainda não têm ficha de cliente.
 *
 * A passagem já acontece no momento em que a venda é concluída. Isto é a rede
 * por baixo: apanha as que ficaram para trás (as fechadas antes desta
 * funcionalidade existir) e as que falharem por o Perfex estar ocupado ou a
 * base de dados recusar naquele instante.
 *
 * É idempotente — corre de 5 em 5 minutos e não cria ninguém a dobrar. Quando
 * não há nada por passar, sai sem tocar em nada.
 */
hooks()->add_action('after_cron_run', 'dps_vendas_cron_clientes');

if (!function_exists('dps_vendas_cron_clientes')) {
    function dps_vendas_cron_clientes()
    {
        $CI = &get_instance();
        $CI->load->model('dps_vendas/dps_vendas_model');

        $por_passar = $CI->db->where('estado', 'concluido')
                             ->where('client_id IS NULL')
                             ->count_all_results(db_prefix() . 'simulador_vendas');

        if ($por_passar === 0) {
            return;
        }

        $r = $CI->dps_vendas_model->sincronizar_clientes();

        if ($r['criados'] > 0 || $r['falhados']) {
            log_activity('Passagem automática a cliente: ' . $r['criados'] . ' criado(s)'
                . ($r['falhados'] ? ', sem dados suficientes: ' . implode(', ', $r['falhados']) : ''));
        }
    }
}

/* =====================================================================
 * Lead em "PARA CONTRATO" -> abre o quadro de reserva
 *
 * Quem põe uma lead em PARA CONTRATO está a dizer que aquilo fechou. O que
 * faltava era o passo seguinte: escolher a unidade e a venda aparecer no mapa.
 * Ficava na cabeça do comercial, e por isso ficava por fazer.
 *
 * O gancho é do lado do servidor (lead_status_changed) de propósito: apanha a
 * mudança venha ela do quadro kanban, da ficha da lead ou de uma alteração em
 * massa. O JS no rodapé é só o mensageiro que leva o comercial ao quadro.
 * ================================================================== */

/** Nome do estado de lead que dispara a reserva. */
defined('DPS_VENDAS_ESTADO_CONTRATO') || define('DPS_VENDAS_ESTADO_CONTRATO', 'PARA CONTRATO');

hooks()->add_action('lead_status_changed', 'dps_vendas_lead_para_contrato');
hooks()->add_action('app_admin_footer', 'dps_vendas_js_reserva_lead');

/**
 * Id do estado "PARA CONTRATO", lido da base de dados.
 *
 * Não se fixa o 10 no código: os estados de lead são editáveis no CRM e um
 * número à mão transforma-se em silêncio no dia em que alguém mexer neles.
 */
function dps_vendas_estado_contrato_id()
{
    static $id = null;

    if ($id === null) {
        $CI  = &get_instance();
        $row = $CI->db->select('id')
            ->where('name', DPS_VENDAS_ESTADO_CONTRATO)
            ->get(db_prefix() . 'leads_status')
            ->row();
        $id = $row ? (int) $row->id : 0;
    }

    return $id;
}

/**
 * Marca a lead para o quadro de reserva quando ela entra em PARA CONTRATO.
 *
 * ATENÇÃO ao formato: o Perfex dispara este mesmo gancho com dados diferentes
 * conforme o caminho. Da ficha da lead vêm IDS de estado; do arrastar no
 * kanban vêm NOMES (Leads_model::update_lead_status passa
 * $this->get_status(...)->name). Comparar só ids fazia a reserva nunca abrir a
 * quem trabalha no kanban — que são quase todos.
 */
function dps_vendas_lead_para_contrato($dados)
{
    $novo = $dados['new_status'] ?? null;

    if ($novo === null || $novo === '') {
        return;
    }

    $entrou = is_numeric($novo)
        ? ((int) $novo === dps_vendas_estado_contrato_id())
        : (trim((string) $novo) === DPS_VENDAS_ESTADO_CONTRATO);

    if (!$entrou) {
        return;
    }

    $lead_id = (int) ($dados['lead_id'] ?? 0);
    if (!$lead_id) {
        return;
    }

    // Validade curta: se o comercial mudar o estado e for almoçar, não é a
    // meio da tarde, numa página qualquer, que o quadro lhe salta à frente.
    get_instance()->session->set_userdata('dps_vendas_reserva_lead', [
        'lead_id' => $lead_id,
        'ate'     => time() + 120,
    ]);
}

/**
 * Devolve (e consome) a lead à espera de reserva.
 */
function dps_vendas_reserva_pendente()
{
    $CI  = &get_instance();
    $mem = $CI->session->userdata('dps_vendas_reserva_lead');

    $CI->session->unset_userdata('dps_vendas_reserva_lead');

    if (!is_array($mem) || empty($mem['lead_id']) || ($mem['ate'] ?? 0) < time()) {
        return 0;
    }

    return (int) $mem['lead_id'];
}

/**
 * O mensageiro: depois de mudar o estado, leva o comercial ao quadro.
 *
 * Não se abre uma janela nova — os bloqueadores de pop-ups matam qualquer
 * window.open que não venha directamente de um clique, e o quadro simplesmente
 * não aparecia. Vai-se para lá na mesma página, com um botão de voltar.
 */
function dps_vendas_js_reserva_lead()
{
    if (!is_staff_member()) {
        return;
    }

    $CI = &get_instance();

    // Só nas páginas de leads: em mais lado nenhum isto faz sentido.
    if (strpos((string) uri_string(), 'leads') === false) {
        return;
    }

    $pendente = dps_vendas_reserva_pendente();
    $destino  = admin_url('dps_vendas/reserva/');
    $consulta = admin_url('dps_vendas/reserva_pendente');
    ?>
    <script>
    (function () {
        var DESTINO  = <?php echo json_encode($destino); ?>;
        var CONSULTA = <?php echo json_encode($consulta); ?>;
        var JA       = <?php echo (int) $pendente; ?>;

        function abrir(id) {
            if (id > 0) { window.location.href = DESTINO + id; }
        }

        // Caminho da ficha da lead: a página recarrega e a sessão já traz a lead.
        if (JA > 0) { abrir(JA); return; }

        /*
         * Caminho do kanban: nada recarrega, por isso perguntamos ao servidor
         * logo a seguir a um pedido que possa ter mudado o estado. Só nesses
         * dois endereços — não se anda a bater à porta a cada AJAX da página.
         */
        if (typeof jQuery === 'undefined') { return; }

        jQuery(document).ajaxComplete(function (e, xhr, opcoes) {
            var url = (opcoes && opcoes.url) || '';
            if (url.indexOf('leads/update_lead_status') === -1
             && url.indexOf('leads/lead/') === -1) { return; }

            jQuery.getJSON(CONSULTA).done(function (r) {
                abrir(parseInt(r && r.lead_id, 10) || 0);
            });
        });
    })();
    </script>
    <?php
}
