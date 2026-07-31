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
     * Promotor de cada empreendimento, como está escrito no Moloni.
     *
     * Não se adivinha do nome: o Gaia Douro está facturado a "DM Towers SA"
     * (Douro Mar) e o Boavista a "BOAVISTA TOWER, S.A". Sem este mapa, a
     * correspondência ia pela unidade e havia unidades com o mesmo código em
     * empreendimentos diferentes.
     *
     * Ordem da verificação, tal como a direção a descreve: primeiro o CLIENTE,
     * depois a FRACÇÃO, depois se a factura tem nota de crédito a anulá-la.
     *
     * Guarda-se em options para se poder corrigir sem mexer no código; estes
     * são os valores de arranque.
     */
    private function promotor($empreendimento)
    {
        $guardado = json_decode((string) get_option('dps_moloni_promotores'), true);

        $mapa = is_array($guardado) && $guardado ? $guardado : [
            'gaia douro'      => 'DM TOWERS',
            'boavista towers' => 'BOAVISTA TOWER',
            'lake towers'     => 'LAKE-TOWERS',
        ];

        $chave = mb_strtolower(trim((string) $empreendimento), 'UTF-8');

        return $mapa[$chave] ?? null;
    }

    /**
     * Documentos anulados por nota de crédito.
     *
     * Há facturas emitidas por engano e depois anuladas. A nota de crédito
     * aponta para a factura que anula em `associated_documents`, e é isso que
     * se lê. Sem esta leitura, a fracção M10 tinha duas facturas (M 84 a
     * 15.495 € e M 92 a 15.395 €) e escolhia-se à sorte — a M 84 está anulada
     * pela nota de crédito M 28.
     */
    private function anuladas($tok, $empresa)
    {
        $fora = [];

        for ($pagina = 0; $pagina < 10; $pagina++) {
            $notas = $this->pedir(
                'https://api.moloni.pt/v1/creditNotes/getAll/?access_token=' . urlencode($tok),
                ['company_id' => $empresa, 'offset' => $pagina * 50, 'qty' => 50]
            );

            if (!is_array($notas) || !$notas) {
                break;
            }

            foreach ($notas as $n) {
                $um = $this->pedir(
                    'https://api.moloni.pt/v1/creditNotes/getOne/?access_token=' . urlencode($tok),
                    ['company_id' => $empresa, 'document_id' => (int) ($n['document_id'] ?? 0)]
                );

                foreach ((array) ($um['associated_documents'] ?? []) as $a) {
                    $id = (int) ($a['associated_id'] ?? 0);
                    if ($id) {
                        $fora[$id] = true;
                    }
                }
            }

            if (count($notas) < 50) {
                break;
            }
        }

        return $fora;
    }

    /**
     * As unidades que uma linha de factura pode estar a designar.
     *
     * Cada promotor escreve à sua maneira e o CRM guarda a unidade de forma
     * diferente conforme o empreendimento — daí devolver-se uma LISTA de
     * hipóteses em vez de uma só:
     *
     *   "CPCV Lote 1 torre 1 unidade AA"        -> 1AA, AA   (Gaia Douro: 1_AA)
     *   "CPCV Lote 1 bloco 1 unidade M10"       -> 1M10, M10 (Boavista: M10)
     *   'Fração: "DD" (T3), piso 11, Torre A4'  -> DD        (Lake Towers)
     *
     * Devolver as duas formas não afrouxa nada: a seguir exige-se também que o
     * valor bata certo, e é esse segundo sinal que impede enganos.
     */
    private function unidades_da_linha($nome)
    {
        $n = mb_strtolower($nome, 'UTF-8');
        $limpar = function ($s) {
            return strtoupper(preg_replace('/[^a-z0-9]/i', '', $s));
        };

        // "torre 1 unidade AA" / "bloco 1 unidade M10"
        if (preg_match('/(?:torre|bloco)\s*(\w+).*?unidade\s*([a-z0-9]+)/u', $n, $m)) {
            return [$limpar($m[1] . $m[2]), $limpar($m[2])];
        }

        // "unidade AA" sem torre nem bloco
        if (preg_match('/unidade\s*([a-z0-9]+)/u', $n, $m)) {
            return [$limpar($m[1])];
        }

        // 'Fração: "DD" (T3)' — as aspas são curvas no original.
        if (preg_match('/fra[cç][aã]o[:\s]*[«"\x{201C}\x{201D}]?\s*([a-z0-9]+)/u', $n, $m)) {
            return [$limpar($m[1])];
        }

        return [];
    }

    /**
     * Todas as linhas facturadas que designam uma fracção, já sem as anuladas.
     */
    public function linhas_facturadas()
    {
        $tok = $this->token();
        if (!$tok) {
            return ['ok' => false, 'erro' => 'Faltam as credenciais do Moloni nas definições do Painel.'];
        }

        $empresa  = $this->config()['company_id'];
        $anuladas = $this->anuladas($tok, $empresa);
        $linhas   = [];
        $lidas    = 0;

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

                // Anulada por nota de crédito: para todos os efeitos não existe.
                if (!$id || isset($anuladas[$id])) {
                    continue;
                }

                $um = $this->pedir(
                    'https://api.moloni.pt/v1/invoices/getOne/?access_token=' . urlencode($tok),
                    ['company_id' => $empresa, 'document_id' => $id]
                );

                // status 1 = fechada. Um rascunho não é uma factura emitida.
                if ((int) ($um['status'] ?? 0) !== 1) {
                    continue;
                }

                $numero = trim(($um['document_set_name'] ?? '') . ' ' . ($um['number'] ?? ''));

                foreach ((array) ($um['products'] ?? []) as $l) {
                    $unidades = $this->unidades_da_linha((string) ($l['name'] ?? ''));
                    if (!$unidades) {
                        continue;
                    }

                    $linhas[] = [
                        'numero'   => $numero,
                        'valor'    => round((float) ($l['price'] ?? 0), 2),
                        'parcela'  => stripos((string) $l['name'], 'escritura') !== false ? 'escritura' : 'cpcv',
                        'unidades' => $unidades,
                        'cliente'  => (string) ($um['entity_name'] ?? ''),
                        'data'     => substr((string) ($um['date'] ?? ''), 0, 10),
                        'nome'     => (string) ($l['name'] ?? ''),
                    ];
                }
            }

            if (count($docs) < 50) {
                break;
            }
        }

        return ['ok' => true, 'linhas' => $linhas, 'facturas_lidas' => $lidas, 'anuladas' => count($anuladas)];
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
     * Cruza as facturas com as vendas e grava os números que batem certo.
     *
     * Só escreve quando há UMA e uma só linha que case na unidade E no valor.
     * Duas candidatas, ou uma unidade certa com valor diferente, ficam por
     * confirmar à mão — num quadro de dinheiro, um número errado é pior do que
     * um campo vazio.
     */
    public function sincronizar($aplicar = false)
    {
        $r = $this->linhas_facturadas();
        if (empty($r['ok'])) {
            return $r;
        }

        $vendas = $this->db
            ->select('id, empreendimento, unidade, valor, cliente,
                      fatura_moloni_cpcv, fatura_moloni_escritura')
            ->where_in('estado', ['vendido', 'concluido'])
            ->get($this->t_vendas())->result_array();

        $achados      = [];
        $duvidas      = [];
        $sem_promotor = [];

        foreach ($vendas as $v) {
            $unidade = strtoupper(preg_replace('/[^a-z0-9]/i', '', (string) $v['unidade']));
            if ($unidade === '') {
                continue;
            }

            foreach (['cpcv', 'escritura'] as $parcela) {
                $coluna = 'fatura_moloni_' . $parcela;
                if (trim((string) ($v[$coluna] ?? '')) !== '') {
                    continue;                       // já preenchido: não se mexe
                }

                $esperado = $this->valor_facturavel($v, $parcela);
                if ($esperado === null || $esperado <= 0) {
                    continue;
                }

                /*
                 * 1.º o CLIENTE. Sem promotor conhecido não se procura nada:
                 * mais vale não preencher do que ir buscar a factura de outro
                 * empreendimento que por acaso tenha uma unidade com o mesmo
                 * código.
                 */
                $promotor = $this->promotor($v['empreendimento']);
                if ($promotor === null) {
                    $sem_promotor[$v['empreendimento']] = true;
                    continue;
                }

                // 2.º a FRACÇÃO, já dentro das facturas desse promotor.
                $mesma_unidade = [];
                foreach ($r['linhas'] as $l) {
                    if ($l['parcela'] !== $parcela) {
                        continue;
                    }
                    if (stripos($l['cliente'], $promotor) === false) {
                        continue;
                    }
                    if (in_array($unidade, $l['unidades'], true)) {
                        $mesma_unidade[] = $l;
                    }
                }
                if (!$mesma_unidade) {
                    continue;
                }

                $certas = array_values(array_filter($mesma_unidade, function ($l) use ($esperado) {
                    return abs($l['valor'] - $esperado) <= 0.01;
                }));

                if (count($certas) === 1) {
                    $f = $certas[0];
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
                    continue;
                }

                $duvidas[] = [
                    'venda'   => (int) $v['id'],
                    'unidade' => $v['unidade'],
                    'parcela' => $parcela,
                    'motivo'  => count($certas) > 1
                        ? count($certas) . ' facturas com o mesmo valor ('
                          . implode(', ', array_column($certas, 'numero')) . ')'
                        : 'a unidade bate mas nenhum valor coincide: esperado '
                          . number_format($esperado, 2, ',', '.') . ' €, no Moloni '
                          . implode(' / ', array_map(function ($l) {
                              return $l['numero'] . ' ' . number_format($l['valor'], 2, ',', '.') . ' €';
                          }, $mesma_unidade)),
                ];
            }
        }

        return [
            'ok'             => true,
            'facturas_lidas' => $r['facturas_lidas'],
            'anuladas'       => $r['anuladas'],
            'linhas'         => count($r['linhas']),
            'achados'        => $achados,
            'duvidas'        => $duvidas,
            'sem_promotor'   => array_keys($sem_promotor),
            'aplicado'       => (bool) $aplicar,
        ];
    }
}
