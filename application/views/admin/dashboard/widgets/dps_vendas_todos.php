<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widget VENDAS — o que a casa vendeu, por empreendimento.
 *
 * TODOS veem TUDO, de propósito: é o quadro da equipa, não o de cada um. O
 * comercial que vê o Boavista a puxar e o Aura parado sabe onde vale a pena
 * empurrar hoje. Pedido do dono (04/08/2026).
 *
 * Mostra o VALOR das vendas (o preço das fracções), não a comissão — a
 * comissão de cada um continua a ser assunto privado e vive no simulador de
 * comissões.
 *
 * Dois períodos: os últimos 3 meses (o que está a acontecer) e um mês à
 * escolha (para comparar). O mês escolhido viaja no endereço, para o quadro
 * poder ser partilhado tal como se vê.
 */

$CI = &get_instance();
$p  = db_prefix();

$moeda = function_exists('get_base_currency') ? get_base_currency() : null;

/*
 * Vendas canceladas ficam de fora: uma venda cancelada não é uma venda, e
 * mantê-la no gráfico dava um total que ninguém reconhecia.
 */
$fora_canceladas = "v.estado <> 'cancelado'";

/* ------------------------------------------------------------------ *
 * Mês à escolha — vindo do endereço, validado
 * ------------------------------------------------------------------ */
$mes_pedido = (string) $CI->input->get('dps_vendas_mes');

if (!preg_match('/^\d{4}-\d{2}$/', $mes_pedido)) {
    $mes_pedido = date('Y-m');
}

/* Os 12 meses com vendas, para o selector não oferecer meses vazios. */
$meses = $CI->db->query(
    "SELECT DISTINCT DATE_FORMAT(v.data_venda, '%Y-%m') m
       FROM {$p}simulador_vendas v
      WHERE v.data_venda IS NOT NULL AND {$fora_canceladas}
   ORDER BY m DESC LIMIT 18"
)->result_array();

$meses = array_column($meses, 'm');

if (!in_array($mes_pedido, $meses, true) && $meses) {
    $mes_pedido = $meses[0];
}

/* ------------------------------------------------------------------ *
 * Os dois conjuntos de dados
 * ------------------------------------------------------------------ */
$por_emp = function ($de, $ate) use ($CI, $p, $fora_canceladas) {
    return $CI->db->query(
        "SELECT v.empreendimento AS emp, COUNT(*) AS n, SUM(v.valor) AS total
           FROM {$p}simulador_vendas v
          WHERE {$fora_canceladas}
            AND v.data_venda >= ? AND v.data_venda <= ?
       GROUP BY v.empreendimento
       ORDER BY total DESC",
        [$de, $ate]
    )->result_array();
};

$tres_de  = date('Y-m-01', strtotime('-2 months'));
$tres_ate = date('Y-m-t');
$trimestre = $por_emp($tres_de, $tres_ate);

$mes_de  = $mes_pedido . '-01';
$mes_ate = date('Y-m-t', strtotime($mes_de));
$do_mes  = $por_emp($mes_de, $mes_ate);

/*
 * Uma cor por empreendimento, sempre a mesma nos dois gráficos — se o Aura
 * mudasse de cor entre um quadro e o outro, comparar dava trabalho em vez de
 * ser imediato.
 */
$paleta = [
    'Boavista Towers' => '#2f6fb0',
    'Gaia Douro'      => '#c97a06',
    'Aura Residence'  => '#3f8f6b',
    'Belo Horizonte'  => '#9a4f8f',
    'Raízes Fanzeres' => '#b8563f',
    'Lake Towers'     => '#4a8fa8',
];
$reserva = ['#6b7280', '#8a6d3b', '#5b6e5b', '#7a5c73', '#7d6b57'];

$cor = function ($nome) use ($paleta, &$reserva) {
    static $atribuidas = [];

    foreach ($paleta as $chave => $hex) {
        if (mb_stripos($nome, mb_substr($chave, 0, 6)) !== false) {
            return $hex;
        }
    }
    if (!isset($atribuidas[$nome])) {
        $atribuidas[$nome] = array_shift($reserva) ?: '#6b7280';
    }

    return $atribuidas[$nome];
};

$monta = function ($linhas) use ($cor) {
    $out = ['labels' => [], 'valores' => [], 'cores' => [], 'contagens' => [], 'total' => 0.0];

    foreach ($linhas as $l) {
        $nome = trim((string) $l['emp']) ?: 'Sem empreendimento';
        $out['labels'][]    = $nome;
        $out['valores'][]   = round((float) $l['total'], 2);
        $out['contagens'][] = (int) $l['n'];
        $out['cores'][]     = $cor($nome);
        $out['total']      += (float) $l['total'];
    }

    return $out;
};

$g3  = $monta($trimestre);
$gm  = $monta($do_mes);

$uid = 'dpsv' . substr(md5($mes_pedido . count($g3['labels'])), 0, 6);
?>

<div class="col-md-12">
  <div class="panel_s">
    <div class="panel-body">

      <div class="row mbot15">
        <div class="col-md-6">
          <h4 class="no-margin" style="letter-spacing:.04em;">
            <i class="fa fa-building-o"></i> VENDAS
            <small class="text-muted">— a casa toda, por empreendimento</small>
          </h4>
        </div>
        <div class="col-md-6 text-right">
          <h4 class="no-margin text-success">
            <?php echo app_format_money($g3['total'], $moeda); ?>
            <small class="text-muted" style="font-size:12px;">últimos 3 meses</small>
          </h4>
        </div>
      </div>

      <div class="row">

        <!-- Últimos 3 meses -->
        <div class="col-md-7">
          <p class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;">
            Últimos 3 meses
            <span style="text-transform:none;letter-spacing:0;">
              (<?php echo date('m/Y', strtotime($tres_de)); ?> a <?php echo date('m/Y'); ?>)
            </span>
          </p>

          <?php if (empty($g3['labels'])) { ?>
            <p class="text-muted">Sem vendas neste período.</p>
          <?php } else { ?>
            <div style="height:250px;"><canvas id="<?php echo $uid; ?>_tri"></canvas></div>
          <?php } ?>
        </div>

        <!-- Mês à escolha -->
        <div class="col-md-5">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;">Mês</span>
            <select class="form-control input-sm" style="width:auto;"
                    onchange="var u=new URL(window.location.href);u.searchParams.set('dps_vendas_mes',this.value);window.location=u.toString();">
              <?php foreach ($meses as $m) { ?>
                <option value="<?php echo $m; ?>" <?php echo $m === $mes_pedido ? 'selected' : ''; ?>>
                  <?php echo date('m/Y', strtotime($m . '-01')); ?>
                </option>
              <?php } ?>
            </select>
            <strong class="text-success" style="margin-left:auto;">
              <?php echo app_format_money($gm['total'], $moeda); ?>
            </strong>
          </div>

          <?php if (empty($gm['labels'])) { ?>
            <p class="text-muted">Sem vendas neste mês.</p>
          <?php } else { ?>
            <div style="height:250px;"><canvas id="<?php echo $uid; ?>_mes"></canvas></div>
          <?php } ?>
        </div>

      </div>

      <?php if (!empty($g3['labels'])) { ?>
      <div class="table-responsive mtop15">
        <table class="table table-condensed" style="font-size:13px;">
          <thead>
            <tr>
              <th>Empreendimento</th>
              <th class="text-right">Vendas</th>
              <th class="text-right">Valor (3 meses)</th>
              <th class="text-right"><?php echo date('m/Y', strtotime($mes_pedido . '-01')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $mes_por_nome = array_combine($gm['labels'], $gm['valores']) ?: [];
            foreach ($g3['labels'] as $k => $nome) { ?>
              <tr>
                <td>
                  <span style="display:inline-block;width:10px;height:10px;border-radius:2px;
                               background:<?php echo $g3['cores'][$k]; ?>;margin-right:7px;"></span>
                  <?php echo html_escape($nome); ?>
                </td>
                <td class="text-right"><?php echo (int) $g3['contagens'][$k]; ?></td>
                <td class="text-right"><strong><?php echo app_format_money($g3['valores'][$k], $moeda); ?></strong></td>
                <td class="text-right text-muted">
                  <?php echo isset($mes_por_nome[$nome]) ? app_format_money($mes_por_nome[$nome], $moeda) : '—'; ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <?php } ?>

    </div>
  </div>
</div>

<script>
(function () {
    /*
     * O Chart.js já vem com o Perfex, mas o dashboard nem sempre o tem
     * carregado quando este bloco corre. Espera-se por ele em vez de falhar
     * em silêncio — um gráfico que não aparece lê-se como avaria.
     */
    function quandoHouverChart(fn, tentativas) {
        if (typeof Chart !== 'undefined') { return fn(); }
        if ((tentativas || 0) > 40) { return; }
        setTimeout(function () { quandoHouverChart(fn, (tentativas || 0) + 1); }, 150);
    }

    var euros = function (v) {
        return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 0 }).format(v) + ' €';
    };

    quandoHouverChart(function () {
        var tri = document.getElementById('<?php echo $uid; ?>_tri');
        if (tri) {
            new Chart(tri.getContext('2d'), {
                type: 'horizontalBar',
                data: {
                    labels: <?php echo json_encode($g3['labels'], JSON_UNESCAPED_UNICODE); ?>,
                    datasets: [{
                        data: <?php echo json_encode($g3['valores']); ?>,
                        backgroundColor: <?php echo json_encode($g3['cores']); ?>,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    legend: { display: false },
                    tooltips: { callbacks: { label: function (t) { return euros(t.xLabel); } } },
                    scales: {
                        xAxes: [{ ticks: { beginAtZero: true, callback: euros } }],
                        yAxes: [{ gridLines: { display: false } }]
                    }
                }
            });
        }

        var mes = document.getElementById('<?php echo $uid; ?>_mes');
        if (mes) {
            new Chart(mes.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($gm['labels'], JSON_UNESCAPED_UNICODE); ?>,
                    datasets: [{
                        data: <?php echo json_encode($gm['valores']); ?>,
                        backgroundColor: <?php echo json_encode($gm['cores']); ?>,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11 } },
                    tooltips: { callbacks: { label: function (t, d) {
                        return d.labels[t.index] + ': ' + euros(d.datasets[0].data[t.index]);
                    } } }
                }
            });
        }
    });
})();
</script>
