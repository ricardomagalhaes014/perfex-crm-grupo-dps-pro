<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widget VENDAS — quanto cada comercial vendeu, por empreendimento.
 *
 * Uma barra por PESSOA, dividida pelas cores dos empreendimentos: vê-se de
 * relance quem vendeu quanto, e de quê. TODOS veem TODOS, de propósito — é o
 * quadro da equipa, não o de cada um. Pedido do dono (04/08/2026).
 *
 * TRÊS QUADROS lado a lado, e a diferença entre eles é a pergunta a que
 * respondem:
 *
 *   CONCLUÍDAS            — negócio fechado. É o que já está feito. O Belo
 *                           Horizonte conta sempre aqui, ver mais abaixo.
 *   RESERVADAS+CONCLUÍDAS — tudo o que está de pé, incluindo o que ainda está
 *                           a caminho. É a carteira.
 *   O ANO, CONCLUÍDAS     — o acumulado de 2026. Não obedece ao selector de
 *                           período: é sempre o ano inteiro.
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
$por_comercial = function ($de, $ate, $condicao) use ($CI, $p) {
    return $CI->db->query(
        "SELECT v.staff_id,
                COALESCE(NULLIF(TRIM(CONCAT(s.firstname,' ',s.lastname)), ''), 'Sem comercial') AS quem,
                v.empreendimento AS emp,
                COUNT(*)     AS n,
                SUM(v.valor) AS total
           FROM {$p}simulador_vendas v
      LEFT JOIN {$p}staff s ON s.staffid = v.staff_id
          WHERE {$condicao}
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
$carteira_sql = "v.estado <> 'cancelado'";

/*
 * BELO HORIZONTE conta como concluído aqui, seja qual for o estado da venda.
 *
 * É uma regra só deste gráfico, decidida pelo dono (04/08/2026): o circuito do
 * Belo Horizonte não passa pelos mesmos estados dos outros empreendimentos, e
 * sem esta excepção o Cláudio e o Breno apareciam a zero num quadro em que
 * têm negócio fechado. NÃO se mexe no estado das vendas nem em mais nenhum
 * sítio — quem quiser saber o estado real continua a vê-lo no mapa de vendas.
 */
$fechadas_sql = "(v.estado = 'concluido'
                  OR (v.empreendimento LIKE '%Belo Horizonte%' AND v.estado <> 'cancelado'))";

$linhas_fechadas = $por_comercial($de, $ate, $fechadas_sql);
$linhas_carteira = $por_comercial($de, $ate, $carteira_sql);

/*
 * O terceiro quadro ignora o selector de período de propósito: é o agregado do
 * ANO, e é a única leitura que não muda quando alguém mexe no filtro. Serve
 * para responder a "quanto é que já fizemos este ano", pergunta que não tem
 * nada a ver com os últimos 3 meses.
 */
$ano             = date('Y');
$linhas_ano      = $por_comercial($ano . '-01-01', $ano . '-12-31', $fechadas_sql);

/* ------------------------------------------------------------------ *
 * A TABELA tem período PRÓPRIO, e abre no mês corrente
 * ------------------------------------------------------------------ *
 * Os gráficos servem para ver a forma das coisas ao longo do trimestre; a
 * tabela serve para conferir nomes e números, e quem confere quer o mês em
 * que está. Partilhar um filtro só obrigava sempre um dos dois a estar no
 * período errado.
 */
$tab_pedido = trim((string) $CI->input->get('dps_vendas_tabela'));

if ($tab_pedido === '') {
    $tab_pedido = 'mes:' . date('Y-m');   // por omissão, o mês corrente
}

if (preg_match('/^mes:(\d{4}-\d{2})$/', $tab_pedido, $mt)) {
    $tab_de     = $mt[1] . '-01';
    $tab_ate    = date('Y-m-t', strtotime($tab_de));
    $tab_rotulo = date('m/Y', strtotime($tab_de));
} elseif (preg_match('/^ano:(\d{4})$/', $tab_pedido, $mt)) {
    $tab_de     = $mt[1] . '-01-01';
    $tab_ate    = $mt[1] . '-12-31';
    $tab_rotulo = $mt[1];
} elseif ($tab_pedido === 'tudo') {
    $tab_de     = '1970-01-01';
    $tab_ate    = '2999-12-31';
    $tab_rotulo = 'desde sempre';
} else {
    $tab_pedido = '3m';
    $tab_de     = date('Y-m-01', strtotime('-2 months'));
    $tab_ate    = date('Y-m-t');
    $tab_rotulo = 'últimos 3 meses';
}

$tab_linhas_fech = $por_comercial($tab_de, $tab_ate, $fechadas_sql);
$tab_linhas_cart = $por_comercial($tab_de, $tab_ate, $carteira_sql);

/* Anos e meses com vendas, para o selector não oferecer períodos vazios. */
$tab_meses = array_column($CI->db->query(
    "SELECT DISTINCT DATE_FORMAT(v.data_venda,'%Y-%m') m
       FROM {$p}simulador_vendas v
      WHERE v.data_venda IS NOT NULL AND v.estado <> 'cancelado'
   ORDER BY m DESC LIMIT 24"
)->result_array(), 'm');

$tab_anos = array_column($CI->db->query(
    "SELECT DISTINCT YEAR(v.data_venda) a
       FROM {$p}simulador_vendas v
      WHERE v.data_venda IS NOT NULL AND v.estado <> 'cancelado'
   ORDER BY a DESC"
)->result_array(), 'a');

/* O mês corrente entra na lista mesmo sem vendas — senão o que abre por
   omissão não estava lá para se voltar a ele. */
if (!in_array(date('Y-m'), $tab_meses, true)) {
    array_unshift($tab_meses, date('Y-m'));
}

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

/*
 * O do ano ordena-se por si e tem escala própria: cobre um período diferente,
 * com gente que pode não aparecer nos outros dois. Forçá-lo à mesma régua dos
 * outros esmagava-os a todos, porque o ano é sempre maior que o trimestre.
 */
$anual = $monta($linhas_ano);

$tab_cart = $monta($tab_linhas_cart);
$tab_fech = $monta($tab_linhas_fech, $tab_cart['pessoas']);

$altura = max(200, 46 * max(count($carteira['pessoas']), count($anual['pessoas'])) + 60);
$uid    = 'dpsv' . substr(md5($de . $ate . count($carteira['pessoas'])), 0, 6);
?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="DPS — Vendas da equipa">
 <div class="row">
  <div class="col-md-12">
   <div class="panel_s">
    <div class="panel-body">
      <div class="widget-dragger"></div>

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
        <div class="col-md-4">
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
        <div class="col-md-4">
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

        <!-- O ANO INTEIRO, CONCLUÍDAS -->
        <div class="col-md-4">
          <p class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px;">
            <?php echo $ano; ?> — concluídas
            — <strong class="text-success"><?php echo app_format_money($anual['total'], $moeda); ?></strong>
          </p>

          <?php if (empty($anual['pessoas']) || $anual['total'] <= 0) { ?>
            <p class="text-muted">Sem vendas concluídas em <?php echo $ano; ?>.</p>
          <?php } else { ?>
            <div style="height:<?php echo $altura; ?>px;">
              <canvas id="<?php echo $uid; ?>_ano"></canvas>
            </div>
          <?php } ?>
        </div>

      </div>

      <div class="row mtop15" style="border-top:1px solid rgba(0,0,0,.06);padding-top:12px;">
        <div class="col-md-7">
          <p class="text-muted no-margin" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;">
            Detalhe — <span style="text-transform:none;letter-spacing:0;"><?php echo html_escape($tab_rotulo); ?></span>
          </p>
        </div>
        <div class="col-md-5 text-right">
          <select class="form-control input-sm" style="width:auto;display:inline-block;"
                  onchange="var u=new URL(window.location.href);
                            u.searchParams.set('dps_vendas_tabela',this.value);
                            window.location=u.toString();">
            <optgroup label="Mês">
              <?php foreach ($tab_meses as $m) { $vv = 'mes:' . $m; ?>
                <option value="<?php echo $vv; ?>" <?php echo $tab_pedido === $vv ? 'selected' : ''; ?>><?php echo date('m/Y', strtotime($m . '-01')); ?></option>
              <?php } ?>
            </optgroup>
            <optgroup label="Ano">
              <?php foreach ($tab_anos as $a) { $vv = 'ano:' . $a; ?>
                <option value="<?php echo $vv; ?>" <?php echo $tab_pedido === $vv ? 'selected' : ''; ?>><?php echo $a; ?></option>
              <?php } ?>
            </optgroup>
            <optgroup label="Outros">
              <option value="3m" <?php echo $tab_pedido === '3m' ? 'selected' : ''; ?>>Últimos 3 meses</option>
              <option value="tudo" <?php echo $tab_pedido === 'tudo' ? 'selected' : ''; ?>>Tudo</option>
            </optgroup>
          </select>
        </div>
      </div>

      <?php if (empty($tab_cart['pessoas'])) { ?>
        <p class="text-muted mtop10">Sem vendas em <?php echo html_escape($tab_rotulo); ?>.</p>
      <?php } else { ?>
      <div class="table-responsive">
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
            <?php $pos = 0; foreach ($tab_cart['pessoas'] as $quem) { $pos++; ?>
              <tr>
                <td class="text-muted" style="font-variant-numeric:tabular-nums;"><?php echo $pos; ?>.</td>
                <td>
                  <?php echo html_escape($quem); ?>
                  <br>
                  <?php
                  /* As fracções de cada um, em pequenino — diz de que é feito o total. */
                  $partes = $tab_cart['dados'][$quem]['emps'] ?? [];
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
                  <?php echo (int) ($tab_fech['dados'][$quem]['n'] ?? 0); ?>
                </td>
                <td class="text-right">
                  <?php echo isset($tab_fech['dados'][$quem])
                      ? '<strong>' . app_format_money($tab_fech['dados'][$quem]['total'], $moeda) . '</strong>'
                      : '<span class="text-muted">—</span>'; ?>
                </td>
                <td class="text-right text-muted" style="font-variant-numeric:tabular-nums;">
                  <?php echo (int) ($tab_cart['dados'][$quem]['n'] ?? 0); ?>
                </td>
                <td class="text-right"><strong><?php echo app_format_money($tab_cart['dados'][$quem]['total'] ?? 0, $moeda); ?></strong></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <?php } ?>

    </div>
   </div>
  </div>
 </div>

<?php /* O <script> TEM de ficar dentro da div raiz: o renderizador de
   widgets (render_dashboard_widgets) so aproveita o primeiro elemento
   HTML do ficheiro e descarta tudo o que venha depois. */ ?>
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
        barras('<?php echo $uid; ?>_ano',
               <?php echo json_encode($anual['pessoas'], JSON_UNESCAPED_UNICODE); ?>,
               <?php echo json_encode($anual['series'], JSON_UNESCAPED_UNICODE); ?>);
    });
})();
</script>
</div>
