<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widget VENDAS — quanto cada comercial vendeu, por empreendimento.
 *
 * Uma barra por PESSOA, dividida pelas cores dos empreendimentos: vê-se de
 * relance quem vendeu quanto, e de quê. TODOS veem TODOS, de propósito — é o
 * quadro da equipa, não o de cada um. Pedido do dono (04/08/2026).
 *
 * DOIS QUADROS lado a lado, e a diferença entre eles é a pergunta a que
 * respondem:
 *
 *   CONCLUÍDAS            — negócio fechado. É o que já está feito.
 *   RESERVADAS+CONCLUÍDAS — tudo o que está de pé, incluindo o que ainda está
 *                           a caminho. É a carteira.
 *
 * As duas juntas dizem mais do que qualquer uma sozinha: muita carteira e
 * pouco fechado é trabalho por rematar; o contrário é carteira a esvaziar.
 *
 * Mostra o VALOR das vendas (o preço das fracções), não a comissão — a
 * comissão de cada um continua assunto privado e vive no simulador.
 *
 * O período é o mesmo nos dois: os últimos 3 meses por omissão, ou um mês à
 * escolha. O que for escolhido viaja no endereço, para o quadro poder ser
 * partilhado tal como se vê.
 */

$CI = &get_instance();
$p  = db_prefix();

$moeda = function_exists('get_base_currency') ? get_base_currency() : null;

/* ------------------------------------------------------------------ *
 * Período — 3 meses por omissão, mês à escolha se o pedirem
 * ------------------------------------------------------------------ */
$mes_pedido = trim((string) $CI->input->get('dps_vendas_mes'));
$por_mes    = (bool) preg_match('/^\d{4}-\d{2}$/', $mes_pedido);

/* Os meses com vendas, para o selector não oferecer meses vazios. */
$meses = array_column($CI->db->query(
    "SELECT DISTINCT DATE_FORMAT(v.data_venda, '%Y-%m') m
       FROM {$p}simulador_vendas v
      WHERE v.data_venda IS NOT NULL AND v.estado <> 'cancelado'
   ORDER BY m DESC LIMIT 18"
)->result_array(), 'm');

if ($por_mes && !in_array($mes_pedido, $meses, true)) {
    $por_mes = false;   // mês sem vendas nenhumas — volta aos 3 meses
}

if ($por_mes) {
    $de     = $mes_pedido . '-01';
    $ate    = date('Y-m-t', strtotime($de));
    $rotulo = date('m/Y', strtotime($de));
} else {
    $de     = date('Y-m-01', strtotime('-2 months'));
    $ate    = date('Y-m-t');
    $rotulo = 'últimos 3 meses · ' . date('m/Y', strtotime($de)) . ' a ' . date('m/Y');
}

/* ------------------------------------------------------------------ *
 * Os dois conjuntos de dados
 * ------------------------------------------------------------------ */
$por_comercial = function ($de, $ate, array $estados) use ($CI, $p) {
    $lista = "'" . implode("','", array_map([$CI->db, 'escape_str'], $estados)) . "'";

    return $CI->db->query(
        "SELECT v.staff_id,
                COALESCE(NULLIF(TRIM(CONCAT(s.firstname,' ',s.lastname)), ''), 'Sem comercial') AS quem,
                v.empreendimento AS emp,
                COUNT(*)     AS n,
                SUM(v.valor) AS total
           FROM {$p}simulador_vendas v
      LEFT JOIN {$p}staff s ON s.staffid = v.staff_id
          WHERE v.estado IN ({$lista})
            AND v.data_venda >= ? AND v.data_venda <= ?
       GROUP BY v.staff_id, v.empreendimento
       ORDER BY total DESC",
        [$de, $ate]
    )->result_array();
};

/*
 * O segundo quadro é tudo o que está de pé — ou seja, tudo menos cancelado.
 * Ser literal e ficar só por 'reservado' e 'concluido' deixava de fora as que
 * já têm CPCV assinado, que são as mais seguras de todas; ninguém entenderia
 * um total que salta por cima delas.
 */
$estados_fechadas = ['concluido'];
$estados_carteira = ['pedido', 'reservado', 'submetido', 'vendido', 'concluido'];

$linhas_fechadas = $por_comercial($de, $ate, $estados_fechadas);
$linhas_carteira = $por_comercial($de, $ate, $estados_carteira);

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

/**
 * Transforma as linhas (comercial × empreendimento) em barras empilhadas:
 * uma barra por comercial, um segmento por empreendimento.
 *
 * A ordem das pessoas é imposta de fora ($ordem) para os dois gráficos ficarem
 * alinhados linha a linha — se cada um ordenasse por si, a mesma pessoa estava
 * em alturas diferentes e a comparação lado a lado perdia-se.
 */
$monta = function ($linhas, ?array $ordem = null) use ($cor) {
    $por_pessoa = [];
    $emps       = [];
    $total      = 0.0;

    foreach ($linhas as $l) {
        $quem = trim((string) $l['quem']) ?: 'Sem comercial';
        $emp  = trim((string) $l['emp']) ?: 'Sem empreendimento';
        $val  = (float) $l['total'];

        $por_pessoa[$quem]['total']      = ($por_pessoa[$quem]['total'] ?? 0) + $val;
        $por_pessoa[$quem]['n']          = ($por_pessoa[$quem]['n'] ?? 0) + (int) $l['n'];
        $por_pessoa[$quem]['emps'][$emp] = ($por_pessoa[$quem]['emps'][$emp] ?? 0) + $val;
        $emps[$emp]                      = true;
        $total                          += $val;
    }

    if ($ordem === null) {
        // Quem vendeu mais fica em cima — é a leitura que toda a gente procura.
        uasort($por_pessoa, fn ($a, $b) => $b['total'] <=> $a['total']);
        $pessoas = array_keys($por_pessoa);
    } else {
        $pessoas = $ordem;
    }

    $emps = array_keys($emps);
    sort($emps);

    // Um conjunto de dados por empreendimento, com o valor de cada pessoa.
    $series = [];
    foreach ($emps as $emp) {
        $valores = [];
        foreach ($pessoas as $quem) {
            $valores[] = round($por_pessoa[$quem]['emps'][$emp] ?? 0, 2);
        }
        $series[] = ['label' => $emp, 'cor' => $cor($emp), 'valores' => $valores];
    }

    return [
        'pessoas' => $pessoas,
        'dados'   => $por_pessoa,
        'series'  => $series,
        'total'   => $total,
    ];
};

/*
 * A ordem manda-a a carteira, não as concluídas: é o conjunto maior, logo
 * ninguém desaparece da lista por ainda não ter fechado nada no período.
 */
$carteira = $monta($linhas_carteira);
$fechadas = $monta($linhas_fechadas, $carteira['pessoas']);

$altura = max(200, 46 * count($carteira['pessoas']) + 60);
$uid    = 'dpsv' . substr(md5($de . $ate . count($carteira['pessoas'])), 0, 6);
?>

<div class="col-md-12">
  <div class="panel_s">
    <div class="panel-body">

      <div class="row mbot15">
        <div class="col-md-7">
          <h4 class="no-margin" style="letter-spacing:.04em;">
            <i class="fa fa-trophy"></i> VENDAS
            <small class="text-muted">— por comercial, com as cores dos empreendimentos</small>
          </h4>
        </div>
        <div class="col-md-5 text-right">
          <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;margin-right:6px;">Período</span>
          <select class="form-control input-sm" style="width:auto;display:inline-block;"
                  onchange="var u=new URL(window.location.href);
                            if(this.value){u.searchParams.set('dps_vendas_mes',this.value);}
                            else{u.searchParams.delete('dps_vendas_mes');}
                            window.location=u.toString();">
            <option value="" <?php echo $por_mes ? '' : 'selected'; ?>>Últimos 3 meses</option>
            <?php foreach ($meses as $m) { ?>
              <option value="<?php echo $m; ?>" <?php echo ($por_mes && $m === $mes_pedido) ? 'selected' : ''; ?>>
                <?php echo date('m/Y', strtotime($m . '-01')); ?>
              </option>
            <?php } ?>
          </select>
        </div>
      </div>

      <p class="text-muted" style="font-size:12px;margin-bottom:12px;">
        <?php echo html_escape($rotulo); ?>
      </p>

      <div class="row">

        <!-- CONCLUÍDAS -->
        <div class="col-md-6">
          <p class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px;">
            Concluídas
            — <strong class="text-success"><?php echo app_format_money($fechadas['total'], $moeda); ?></strong>
          </p>

          <?php if (empty($fechadas['pessoas']) || $fechadas['total'] <= 0) { ?>
            <p class="text-muted">Sem vendas concluídas neste período.</p>
          <?php } else { ?>
            <div style="height:<?php echo $altura; ?>px;">
              <canvas id="<?php echo $uid; ?>_fec"></canvas>
            </div>
          <?php } ?>
        </div>

        <!-- RESERVADAS + CONCLUÍDAS -->
        <div class="col-md-6">
          <p class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px;">
            Reservadas + concluídas
            — <strong class="text-success"><?php echo app_format_money($carteira['total'], $moeda); ?></strong>
          </p>

          <?php if (empty($carteira['pessoas'])) { ?>
            <p class="text-muted">Sem vendas neste período.</p>
          <?php } else { ?>
            <div style="height:<?php echo $altura; ?>px;">
              <canvas id="<?php echo $uid; ?>_car"></canvas>
            </div>
          <?php } ?>
        </div>

      </div>

      <?php if (!empty($carteira['pessoas'])) { ?>
      <div class="table-responsive mtop15">
        <table class="table table-condensed" style="font-size:13px;">
          <thead>
            <tr>
              <th style="width:34px;"></th>
              <th>Comercial</th>
              <th class="text-right">Concluídas</th>
              <th class="text-right">Valor concluído</th>
              <th class="text-right">Total</th>
              <th class="text-right">Valor total</th>
            </tr>
          </thead>
          <tbody>
            <?php $pos = 0; foreach ($carteira['pessoas'] as $quem) { $pos++; ?>
              <tr>
                <td class="text-muted" style="font-variant-numeric:tabular-nums;"><?php echo $pos; ?>.</td>
                <td>
                  <?php echo html_escape($quem); ?>
                  <br>
                  <?php
                  /* As fracções de cada um, em pequenino — diz de que é feito o total. */
                  $partes = $carteira['dados'][$quem]['emps'] ?? [];
                  arsort($partes);
                  foreach ($partes as $emp => $v) { ?>
                    <span class="text-muted" style="font-size:11px;margin-right:8px;white-space:nowrap;">
                      <span style="display:inline-block;width:8px;height:8px;border-radius:2px;
                                   background:<?php echo $cor($emp); ?>;margin-right:4px;"></span><?php
                      echo html_escape($emp); ?>
                    </span>
                  <?php } ?>
                </td>
                <td class="text-right" style="font-variant-numeric:tabular-nums;">
                  <?php echo (int) ($fechadas['dados'][$quem]['n'] ?? 0); ?>
                </td>
                <td class="text-right">
                  <?php echo isset($fechadas['dados'][$quem])
                      ? '<strong>' . app_format_money($fechadas['dados'][$quem]['total'], $moeda) . '</strong>'
                      : '<span class="text-muted">—</span>'; ?>
                </td>
                <td class="text-right text-muted" style="font-variant-numeric:tabular-nums;">
                  <?php echo (int) ($carteira['dados'][$quem]['n'] ?? 0); ?>
                </td>
                <td class="text-right"><strong><?php echo app_format_money($carteira['dados'][$quem]['total'] ?? 0, $moeda); ?></strong></td>
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
     * O Chart.js vem com o Perfex, mas o dashboard nem sempre o tem carregado
     * quando este bloco corre. Espera-se por ele em vez de falhar em silêncio —
     * um gráfico que não aparece lê-se como avaria.
     */
    function quandoHouverChart(fn, n) {
        if (typeof Chart !== 'undefined') { return fn(); }
        if ((n || 0) > 40) { return; }
        setTimeout(function () { quandoHouverChart(fn, (n || 0) + 1); }, 150);
    }

    var euros = function (v) {
        return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 0 }).format(v) + ' €';
    };

    /* Barras EMPILHADAS: uma por comercial, um pedaço por empreendimento. */
    function barras(id, pessoas, series, tecto) {
        var el = document.getElementById(id);
        if (!el || !pessoas.length) { return; }

        new Chart(el.getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: pessoas,
                datasets: series.map(function (s) {
                    return { label: s.label, data: s.valores, backgroundColor: s.cor, borderWidth: 0 };
                })
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11 } },
                tooltips: {
                    callbacks: {
                        label: function (t, d) {
                            var v = d.datasets[t.datasetIndex].data[t.index];
                            if (!v) { return null; }   // não mostra empreendimentos a zero
                            return d.datasets[t.datasetIndex].label + ': ' + euros(v);
                        }
                    }
                },
                scales: {
                    /*
                     * A mesma escala nos dois gráficos. Sem isto, uma barra de
                     * 300k ao lado de uma de 3M ficava do mesmo tamanho e o par
                     * enganava em vez de comparar.
                     */
                    xAxes: [{ stacked: true, ticks: { beginAtZero: true, max: tecto, callback: euros } }],
                    yAxes: [{ stacked: true, gridLines: { display: false } }]
                }
            }
        });
    }

    var pessoas  = <?php echo json_encode($carteira['pessoas'], JSON_UNESCAPED_UNICODE); ?>;
    var sFechado = <?php echo json_encode($fechadas['series'], JSON_UNESCAPED_UNICODE); ?>;
    var sTotal   = <?php echo json_encode($carteira['series'], JSON_UNESCAPED_UNICODE); ?>;

    /* Tecto comum: o maior total individual da carteira, com uma folga. */
    var tecto = 0;
    pessoas.forEach(function (_, i) {
        var soma = 0;
        sTotal.forEach(function (s) { soma += (s.valores[i] || 0); });
        if (soma > tecto) { tecto = soma; }
    });
    tecto = tecto ? Math.ceil(tecto * 1.05) : undefined;

    quandoHouverChart(function () {
        barras('<?php echo $uid; ?>_fec', pessoas, sFechado, tecto);
        barras('<?php echo $uid; ?>_car', pessoas, sTotal,   tecto);
    });
})();
</script>
