<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Procura no Moloni a factura já emitida para cada fracção e guarda o número.
 *
 * Antes, o número da factura era escrito à mão no quadro de comissões. Escrever
 * à mão um número que já existe noutro sistema é trabalho a dobrar e, mais cedo
 * ou mais tarde, um número trocado — e um número trocado num quadro de dinheiro
 * é pior do que um campo vazio.
 *
 * COMO SE FAZ A CORRESPONDÊNCIA
 *
 * As facturas do Moloni trazem, na linha, a fracção e o valor:
 *
 *     "CPCV Lote 1 torre 1 unidade AA"   preço 16745
 *
 * e no CRM a venda #15 é a unidade `1_AA` com a parcela do CPCV a 16.745,00 €.
 * Ou seja, há dois sinais independentes: a UNIDADE e o VALOR. Exige-se os dois.
 *
 * Só a unidade não chegava — os códigos repetem-se entre empreendimentos, e
 * uma "unidade S" existe no Gaia Douro e pode existir noutro lado. Só o valor
 * também não: duas fracções iguais dão comissões iguais. Juntos, enganam-se com
 * muito mais dificuldade, e quando não há certeza não se escreve nada: fica por
 * preencher e diz-se porquê.
 */
class Dps_moloni_model extends App_Model
{
    /** Quantas facturas se leem, no máximo. Páginas de 50. */
    const MAXIMO_FACTURAS = 400;

    private function t_vendas()
    {
        return db_prefix() . 'simulador_vendas';
    }

    private function config()
    {
        return [
            'dev_id'     => get_option('dps_painel_moloni_dev_id'),
            'secret'     => get_option('dps_painel_moloni_secret'),
            'email'      => get_option('dps_painel_moloni_email'),
            'password'   => get_option('dps_painel_moloni_password'),
            'company_id' => (int) get_option('dps_painel_moloni_company_id'),
        ];
    }

    /* ---------------------------------------------------------------- API */

    private function token()
    {
        $c = $this->config();

        foreach (['dev_id', 'secret', 'email', 'password'] as $k) {
            if (trim((string) $c[$k]) === '') {
                return null;
            }
        }

        $url = 'https://api.moloni.pt/v1/grant/?' . http_build_query([
            'grant_type'    => 'password',
            'client_id'     => $c['dev_id'],
            'client_secret' => $c['secret'],
            'username'      => $c['email'],
            'password'      => $c['password'],
        ]);

        $r = $this->pedir($url);

        return $r['access_token'] ?? null;
    }

    private function pedir($url, $dados = null)
    {
        $ch = curl_init($url);
        $opcoes = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
        ];
        if ($dados !== null) {
            $opcoes[CURLOPT_POST]       = true;
            $opcoes[CURLOPT_POSTFIELDS] = http_build_query($dados);
        }
        curl_setopt_array($ch, $opcoes);
        $corpo = curl_exec($ch);
        curl_close($ch);

        return json_decode((string) $corpo, true);
    }

    /**
     * Lê as facturas e devolve, por fracção, o número do documento.
     *
     * Chave: 'cpcv|1_aa' ou 'escritura|2_bn'. O valor vai junto para se poder
     * confirmar antes de escrever.
     */
    public function faturas_por_fraccao()
    {
        $tok = $this->token();
        if (!$tok) {
            return ['ok' => false, 'erro' => 'Faltam as credenciais do Moloni nas definições do Painel.'];
        }

        $empresa = $this->config()['company_id'];
        $indice  = [];
        $lidas   = 0;

        for ($pagina = 0; $lidas < self::MAXIMO_FACTURAS; $pagina++) {
            $docs = $this->pedir(
                'https://api.moloni.pt/v1/invoices/getAll/?access_token=' . urlencode($tok),
                ['company_id' => $empresa, 'offset' => $pagina * 50, 'qty' => 50]
            );

            if (!is_array($docs) || !$docs) {
                break;
            }

            foreach ($docs as $d) {
                $lidas++;
                $id = (int) ($d['document_id'] ?? 0);
                if (!$id) {
                    continue;
                }

                /*
                 * O getAll não traz as linhas — é preciso abrir cada factura.
                 * É um pedido por documento, daí o tecto: 400 facturas é mais
                 * de um ano de emissão e chega bem para o que se procura.
                 */
                $um = $this->pedir(
                    'https://api.moloni.pt/v1/invoices/getOne/?access_token=' . urlencode($tok),
                    ['company_id' => $empresa, 'document_id' => $id]
                );

                $numero = trim(($um['document_set_name'] ?? '') . ' ' . ($um['number'] ?? ''));

                foreach ((array) ($um['products'] ?? []) as $linha) {
                    $chave = $this->chave_da_linha((string) ($linha['name'] ?? ''));
                    if (!$chave) {
                        continue;
                    }

                    // A primeira que aparece é a mais recente: o getAll vem
                    // por data decrescente e não se sobrepõe.
                    if (!isset($indice[$chave])) {
                        $indice[$chave] = [
                            'numero' => $numero,
                            'valor'  => round((float) ($linha['price'] ?? 0), 2),
                            'nome'   => (string) ($linha['name'] ?? ''),
                            'data'   => substr((string) ($um['date'] ?? ''), 0, 10),
                        ];
                    }
                }
            }

            if (count($docs) < 50) {
                break;
            }
        }

        return ['ok' => true, 'indice' => $indice, 'facturas_lidas' => $lidas];
    }

    /**
     * "CPCV Lote 1 torre 1 unidade AA" -> "cpcv|1_aa"
     *
     * Devolve null quando a linha não identifica fracção nenhuma — uma factura
     * de outra coisa qualquer não deve casar com venda nenhuma.
     */
    private function chave_da_linha($nome)
    {
        $n = mb_strtolower($nome, 'UTF-8');

        if (!preg_match('/torre\s*(\w+).*?unidade\s*([a-z0-9]+)/u', $n, $m)) {
            return null;
        }

        $parcela = (strpos($n, 'escritura') !== false) ? 'escritura' : 'cpcv';

        return $parcela . '|' . $m[1] . '_' . $m[2];
    }

    /**
     * Quanto é que a DPS factura ao promotor por esta parcela desta venda.
     *
     * Vem da tabela de recebimento do Painel do Negócio, que é onde se define
     * quanto se recebe por empreendimento e como se reparte entre CPCV e
     * escritura. Não se duplica a regra aqui: dois sítios a dizer percentagens
     * diferentes para o mesmo empreendimento seria pior do que não as ter.
     *
     * Devolve null quando o empreendimento não tem taxa definida — sem taxa não
     * há valor esperado, logo não há segundo sinal e não se escreve nada.
     */
    private function valor_facturavel($venda, $parcela)
    {
        $linha = $this->db
            ->where('LOWER(TRIM(empreendimento))', mb_strtolower(trim((string) $venda['empreendimento']), 'UTF-8'))
            ->get(db_prefix() . 'dps_painel_recebimento')->row_array();

        if (!$linha || (float) $linha['taxa_recebida'] <= 0) {
            return null;
        }

        $total = round((float) $venda['valor'] * (float) $linha['taxa_recebida'] / 100, 2);

        $cp = isset($linha['cpcv_pct']) ? (float) $linha['cpcv_pct'] : null;
        $es = isset($linha['escritura_pct']) ? (float) $linha['escritura_pct'] : null;

        // Sem repartição definida, recebe-se tudo no CPCV.
        if (($cp === null || $cp <= 0) && ($es === null || $es <= 0)) {
            $cp = 100.0;
            $es = 0.0;
        }

        $no_cpcv = round($total * (float) $cp / 100, 2);

        return $parcela === 'escritura' ? round($total - $no_cpcv, 2) : $no_cpcv;
    }

    /* ------------------------------------------------------- Sincronizar */

    /**
     * Compara o índice do Moloni com as vendas e grava os números que batem
     * certo nos dois sinais. Em modo de ensaio não escreve nada.
     */
    public function sincronizar($aplicar = false)
    {
        $r = $this->faturas_por_fraccao();
        if (empty($r['ok'])) {
            return $r;
        }

        $indice = $r['indice'];

        $vendas = $this->db
            ->select('id, empreendimento, unidade, valor, taxa, cliente,
                      fatura_moloni_cpcv, fatura_moloni_escritura')
            ->where_in('estado', ['vendido', 'concluido'])
            ->get($this->t_vendas())->result_array();

        $achados = [];
        $sem_par = [];

        foreach ($vendas as $v) {
            $unidade = mb_strtolower(trim((string) $v['unidade']), 'UTF-8');
            if ($unidade === '') {
                continue;
            }

            foreach (['cpcv', 'escritura'] as $parcela) {
                $coluna = 'fatura_moloni_' . $parcela;
                if (trim((string) ($v[$coluna] ?? '')) !== '') {
                    continue;                       // já preenchido à mão: não se mexe
                }

                $f = $indice[$parcela . '|' . $unidade] ?? null;
                if (!$f) {
                    continue;
                }

                /*
                 * Segundo sinal: o valor da linha da factura.
                 *
                 * A base é o que a DPS COBRA AO PROMOTOR — a taxa recebida do
                 * empreendimento sobre o preço da venda —, e não a comissão que
                 * a DPS paga ao comercial. São coisas diferentes e no Gaia Douro
                 * a segunda é metade da primeira: comparar com a errada rejeitava
                 * todas as facturas certas. Verificado a 31/07/2026 contra as
                 * facturas M 93, M 94 e M 95.
                 */
                $esperado = $this->valor_facturavel($v, $parcela);

                if ($esperado === null || abs($esperado - $f['valor']) > 0.01) {
                    $sem_par[] = [
                        'venda'    => (int) $v['id'],
                        'unidade'  => $v['unidade'],
                        'parcela'  => $parcela,
                        'motivo'   => 'a unidade bate mas o valor não: Moloni ' .
                                      number_format($f['valor'], 2, ',', '.') . ' € contra ' .
                                      ($esperado === null ? 'sem parcela calculada' : number_format($esperado, 2, ',', '.') . ' €'),
                        'factura'  => $f['numero'],
                    ];
                    continue;
                }

                $achados[] = [
                    'venda'   => (int) $v['id'],
                    'unidade' => $v['unidade'],
                    'cliente' => $v['cliente'],
                    'parcela' => $parcela,
                    'factura' => $f['numero'],
                    'valor'   => $f['valor'],
                    'data'    => $f['data'],
                ];

                if ($aplicar) {
                    $this->db->where('id', (int) $v['id'])->update($this->t_vendas(), [$coluna => $f['numero']]);
                }
            }
        }

        return [
            'ok'             => true,
            'facturas_lidas' => $r['facturas_lidas'],
            'fraccoes'       => count($indice),
            'achados'        => $achados,
            'duvidas'        => $sem_par,
            'aplicado'       => (bool) $aplicar,
        ];
    }
}
