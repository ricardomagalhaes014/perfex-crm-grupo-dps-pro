<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_sofia_ia_model extends App_Model
{
    /**
     * Quantos caracteres de conhecimento seguem em cada pergunta.
     *
     * Não é o limite do modelo — é uma decisão de custo. Cada pergunta paga o
     * contexto que leva, e a partir de certo ponto mais trechos não melhoram a
     * resposta, só a conta.
     */
    const ORCAMENTO_CONTEXTO = 24000;

    /** Conhecimento marcado como "incluir sempre", que vai em todas as perguntas. */
    const ORCAMENTO_BASE = 12000;

    public function __construct()
    {
        parent::__construct();
    }

    /* ------------------------------------------------------------------ */
    /* Base de conhecimento                                                */
    /* ------------------------------------------------------------------ */

    public function get_conhecimentos($apenas_ativos = false)
    {
        if ($apenas_ativos) {
            $this->db->where('ativo', 1);
        }
        $this->db->order_by('sempre_incluir', 'desc');
        $this->db->order_by('titulo', 'asc');

        return $this->db->get(db_prefix() . 'dps_sofia_conhecimento')->result_array();
    }

    public function get_conhecimento($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get(db_prefix() . 'dps_sofia_conhecimento')->row_array();
    }

    public function guardar_conhecimento($dados, $id = null)
    {
        $registo = [
            'titulo'         => trim($dados['titulo']),
            'categoria'      => isset($dados['categoria']) ? $dados['categoria'] : null,
            'conteudo'       => dps_sofia_ia_forcar_utf8($dados['conteudo']),
            'sempre_incluir' => !empty($dados['sempre_incluir']) ? 1 : 0,
            'ativo'          => isset($dados['ativo']) ? (int) !empty($dados['ativo']) : 1,
        ];

        if (isset($dados['fonte'])) {
            $registo['fonte'] = $dados['fonte'];
        }
        if (isset($dados['ficheiro'])) {
            $registo['ficheiro'] = $dados['ficheiro'];
        }

        if ($id) {
            $registo['dateupdated'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update(db_prefix() . 'dps_sofia_conhecimento', $registo);
        } else {
            $registo['fonte']      = isset($registo['fonte']) ? $registo['fonte'] : 'manual';
            $registo['criado_por'] = get_staff_user_id();
            $registo['dateadded']  = date('Y-m-d H:i:s');
            $this->db->insert(db_prefix() . 'dps_sofia_conhecimento', $registo);
            $id = $this->db->insert_id();
        }

        $this->reindexar($id, $registo['conteudo']);

        return (int) $id;
    }

    public function apagar_conhecimento($id)
    {
        $id = (int) $id;

        $ficha = $this->get_conhecimento($id);
        if (!$ficha) {
            return false;
        }

        // O ficheiro original vai atrás da ficha; não serve para mais nada.
        if (!empty($ficha['ficheiro'])) {
            $caminho = FCPATH . DPS_SOFIA_IA_UPLOAD_PATH . $ficha['ficheiro'];
            if (is_file($caminho)) {
                @unlink($caminho);
            }
        }

        $this->db->where('conhecimento_id', $id);
        $this->db->delete(db_prefix() . 'dps_sofia_trechos');

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'dps_sofia_conhecimento');

        return true;
    }

    /**
     * Reescreve os trechos de uma ficha. Apaga e volta a criar em vez de
     * comparar: o texto muda por inteiro quando o admin edita, e acertar
     * diferenças aqui seria trabalho sem retorno.
     */
    public function reindexar($conhecimento_id, $conteudo)
    {
        $conhecimento_id = (int) $conhecimento_id;

        $this->db->where('conhecimento_id', $conhecimento_id);
        $this->db->delete(db_prefix() . 'dps_sofia_trechos');

        $ordem = 0;
        foreach (dps_sofia_ia_partir_em_trechos($conteudo) as $trecho) {
            $this->db->insert(db_prefix() . 'dps_sofia_trechos', [
                'conhecimento_id' => $conhecimento_id,
                'ordem'           => $ordem++,
                'texto'           => $trecho,
                'texto_norm'      => dps_sofia_ia_normalizar($trecho),
            ]);
        }

        return $ordem;
    }

    /**
     * Reconstrói o índice todo. Serve depois de uma importação em massa ou se
     * alguma coisa correr mal com os trechos.
     */
    public function reindexar_tudo()
    {
        $total = 0;
        foreach ($this->get_conhecimentos() as $ficha) {
            $total += $this->reindexar($ficha['id'], $ficha['conteudo']);
        }

        return $total;
    }

    /* ------------------------------------------------------------------ */
    /* Procura                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * O conhecimento que segue em todas as perguntas, independentemente do que
     * foi perguntado — preços em vigor, regras de comissão, o essencial.
     */
    public function get_conhecimento_base()
    {
        $this->db->where('ativo', 1);
        $this->db->where('sempre_incluir', 1);
        $this->db->order_by('titulo', 'asc');
        $fichas = $this->db->get(db_prefix() . 'dps_sofia_conhecimento')->result_array();

        $blocos = [];
        $usado  = 0;

        foreach ($fichas as $ficha) {
            $texto = "### " . $ficha['titulo'] . "\n" . $ficha['conteudo'];
            if ($usado + mb_strlen($texto) > self::ORCAMENTO_BASE) {
                break;
            }
            $blocos[] = $texto;
            $usado   += mb_strlen($texto);
        }

        return $blocos;
    }

    /**
     * Trechos relevantes para uma pergunta.
     *
     * Duas fases: o SQL reduz a tabela aos trechos que contêm pelo menos um dos
     * termos, e a pontuação fina faz-se em PHP. Pontuar tudo em SQL obrigaria a
     * uma expressão por termo, e trazer a base inteira para PHP deixaria de
     * caber em memória à medida que os PDFs se acumulam.
     */
    public function procurar_trechos($pergunta, $incluir_sempre = false)
    {
        $termos = dps_sofia_ia_termos($pergunta);

        if (empty($termos)) {
            return ['trechos' => [], 'itens' => [], 'fontes' => []];
        }

        $this->db->select('t.texto, t.texto_norm, c.id as conhecimento_id, c.titulo, c.categoria');
        $this->db->from(db_prefix() . 'dps_sofia_trechos t');
        $this->db->join(db_prefix() . 'dps_sofia_conhecimento c', 'c.id = t.conhecimento_id');
        $this->db->where('c.ativo', 1);

        /*
         * Com IA, o conhecimento permanente já segue à parte em todas as
         * perguntas e procurá-lo outra vez era mandar o mesmo texto duas vezes.
         * No modo sem IA não há "à parte" nenhum — só existe o que a procura
         * encontrar — por isso aí tem de entrar também.
         */
        if (!$incluir_sempre) {
            $this->db->where('c.sempre_incluir', 0);
        }

        $this->db->group_start();
        foreach ($termos as $termo) {
            $this->db->or_like('t.texto_norm', $termo);
        }
        $this->db->group_end();

        $this->db->limit(400);
        $candidatos = $this->db->get()->result_array();

        if (empty($candidatos)) {
            return ['trechos' => [], 'itens' => [], 'fontes' => []];
        }

        foreach ($candidatos as $indice => $candidato) {
            $pontos = 0;
            foreach ($termos as $termo) {
                $ocorrencias = substr_count($candidato['texto_norm'], $termo);
                if ($ocorrencias === 0) {
                    continue;
                }
                /*
                 * Um termo longo distingue mais do que um curto, e repetir a
                 * mesma palavra vinte vezes não torna o trecho vinte vezes mais
                 * relevante — daí o tecto de 3.
                 */
                $pontos += min($ocorrencias, 3) * mb_strlen($termo);
            }
            $candidatos[$indice]['pontos'] = $pontos;
        }

        usort($candidatos, function ($a, $b) {
            return $b['pontos'] - $a['pontos'];
        });

        $trechos = [];
        $itens   = [];
        $fontes  = [];
        $usado   = 0;

        foreach ($candidatos as $candidato) {
            if ($candidato['pontos'] <= 0) {
                continue;
            }
            $bloco = '### ' . $candidato['titulo'] . "\n" . $candidato['texto'];
            if ($usado + mb_strlen($bloco) > self::ORCAMENTO_CONTEXTO) {
                break;
            }
            $trechos[] = $bloco;
            $usado    += mb_strlen($bloco);

            // Versão em partes, para o modo sem IA poder mostrar cada achado
            // com o seu título em vez de um bloco de texto colado.
            $itens[] = [
                'titulo' => $candidato['titulo'],
                'texto'  => $candidato['texto'],
                'pontos' => $candidato['pontos'],
            ];

            $fontes[$candidato['conhecimento_id']] = $candidato['titulo'];
        }

        return ['trechos' => $trechos, 'itens' => $itens, 'fontes' => $fontes];
    }

    /* ------------------------------------------------------------------ */
    /* Dados ao vivo (disponibilidade do simulador)                        */
    /* ------------------------------------------------------------------ */

    /**
     * Estado actual das fracções, do simulador.
     *
     * Um PDF com a tabela de preços não sabe o que se vendeu ontem — e é
     * exactamente isso que um comercial pergunta ("o que há disponível?").
     * Por isso a disponibilidade não vem da base de conhecimento: vem daqui,
     * a cada pergunta.
     *
     * Guardado em ficheiro durante 10 minutos. Sem isso, cada pergunta de cada
     * comercial era mais um pedido ao dpsimobiliario.pt, e a resposta ficava
     * presa à latência desse site. Se o pedido falhar, usa-se a cópia antiga:
     * disponibilidade de há uma hora é muito melhor do que nenhuma.
     */
    public function estados_do_simulador()
    {
        $cache = FCPATH . DPS_SOFIA_IA_UPLOAD_PATH . 'estados_cache.json';

        if (is_readable($cache) && (time() - filemtime($cache)) < 600) {
            $guardado = json_decode((string) file_get_contents($cache), true);
            if (is_array($guardado)) {
                return $guardado;
            }
        }

        $ch = curl_init('https://dpsimobiliario.pt/simuladorportugal/save_states.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $bruto = curl_exec($ch);
        curl_close($ch);

        $dados = is_string($bruto) ? json_decode($bruto, true) : null;

        if (is_array($dados) && !empty($dados)) {
            if (!is_dir(dirname($cache))) {
                @mkdir(dirname($cache), 0755, true);
            }
            @file_put_contents($cache, $bruto);

            return $dados;
        }

        // Falhou: melhor a cópia velha do que nada.
        if (is_readable($cache)) {
            $guardado = json_decode((string) file_get_contents($cache), true);
            if (is_array($guardado)) {
                log_activity('Sofia IA: simulador não respondeu; a usar disponibilidade em cache.');

                return $guardado;
            }
        }

        log_activity('Sofia IA: não consegui obter a disponibilidade do simulador.');

        return [];
    }

    private function catalogo_unidades()
    {
        // O catálogo (tipologia, área, preço) é mantido no dps_propostas. Ler
        // de lá em vez de copiar evita ficar com uma tabela de preços velha.
        $ficheiro = FCPATH . 'modules/dps_propostas/units.json';

        if (!is_readable($ficheiro)) {
            return [];
        }

        $dados = json_decode((string) file_get_contents($ficheiro), true);

        return is_array($dados) ? $dados : [];
    }

    /**
     * Resumo do que está disponível agora, para ir com a pergunta.
     *
     * Vai sempre um resumo curto (contagens e preço mínimo por tipologia).
     * A lista fracção a fracção só entra quando a pergunta nomeia um
     * empreendimento — são 249 fracções só no Boavista, e mandá-las em todas
     * as perguntas era pagar contexto que ninguém leu.
     */
    public function dados_ao_vivo($pergunta)
    {
        $estados  = $this->estados_do_simulador();
        $catalogo = $this->catalogo_unidades();

        if (empty($estados) || empty($catalogo)) {
            return '';
        }

        $pergunta_norm = dps_sofia_ia_normalizar($pergunta);
        $linhas        = ['## Disponibilidade AGORA (dados ao vivo do simulador)'];

        if (!empty($estados['updated'])) {
            $linhas[] = 'Actualizado em: ' . $estados['updated'];
        }

        foreach (dps_sofia_ia_empreendimentos() as $chave => $emp) {
            if (empty($catalogo[$chave]) || empty($estados[$emp['states_key']])) {
                continue;
            }

            $unidades = $catalogo[$chave];
            $situacao = $estados[$emp['states_key']];

            $por_tipologia = [];
            $disponiveis   = [];

            foreach ($unidades as $codigo => $unidade) {
                $estado = isset($situacao[$codigo]) ? $situacao[$codigo] : null;
                if ($estado !== 'Disponível') {
                    continue;
                }

                $tipologia = !empty($unidade['tipologia']) ? $unidade['tipologia'] : 'n/d';
                $preco     = isset($unidade['preco']) ? (float) $unidade['preco'] : 0;

                if (!isset($por_tipologia[$tipologia])) {
                    $por_tipologia[$tipologia] = ['total' => 0, 'min' => null];
                }
                $por_tipologia[$tipologia]['total']++;
                if ($preco > 0 && ($por_tipologia[$tipologia]['min'] === null || $preco < $por_tipologia[$tipologia]['min'])) {
                    $por_tipologia[$tipologia]['min'] = $preco;
                }

                $disponiveis[$codigo] = $unidade;
            }

            if (empty($por_tipologia)) {
                $linhas[] = "\n### " . $emp['nome'] . "\nSem fracções disponíveis de momento.";
                continue;
            }

            ksort($por_tipologia);

            $resumo = [];
            foreach ($por_tipologia as $tipologia => $info) {
                $resumo[] = $tipologia . ': ' . $info['total']
                          . ($info['min'] ? ' (desde ' . number_format($info['min'], 0, ',', ' ') . ' €)' : '');
            }

            $linhas[] = "\n### " . $emp['nome'] . ' — ' . count($disponiveis) . ' disponíveis'
                      . "\n" . implode(' | ', $resumo);

            // Detalhe só para o empreendimento que a pergunta nomeia.
            $nome_norm = dps_sofia_ia_normalizar($emp['nome']);
            $primeira  = explode(' ', $nome_norm)[0];

            if ($primeira !== '' && strpos($pergunta_norm, $primeira) !== false) {
                $detalhe = [];
                foreach (array_slice($disponiveis, 0, 80, true) as $codigo => $unidade) {
                    $detalhe[] = sprintf(
                        '%s: %s, %s m2, %s €%s',
                        $codigo,
                        $unidade['tipologia'] ?? 'n/d',
                        $unidade['area'] ?? 'n/d',
                        isset($unidade['preco']) ? number_format((float) $unidade['preco'], 0, ',', ' ') : 'n/d',
                        !empty($unidade['piso']) ? ', piso ' . $unidade['piso'] : ''
                    );
                }
                $linhas[] = "Fracções disponíveis:\n" . implode("\n", $detalhe)
                          . (count($disponiveis) > 80 ? "\n(mostradas as primeiras 80 de " . count($disponiveis) . ')' : '');
            }
        }

        return count($linhas) > 1 ? implode("\n", $linhas) : '';
    }

    /* ------------------------------------------------------------------ */
    /* Conversas                                                           */
    /* ------------------------------------------------------------------ */

    public function get_conversa_atual($staff_id, $criar = true)
    {
        $staff_id = (int) $staff_id;

        $this->db->where('staff_id', $staff_id);
        $this->db->order_by('dateupdated', 'desc');
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $conversa = $this->db->get(db_prefix() . 'dps_sofia_conversas')->row_array();

        if ($conversa || !$criar) {
            return $conversa;
        }

        $this->db->insert(db_prefix() . 'dps_sofia_conversas', [
            'staff_id'    => $staff_id,
            'dateadded'   => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);

        return [
            'id'       => $this->db->insert_id(),
            'staff_id' => $staff_id,
            'titulo'   => null,
        ];
    }

    public function nova_conversa($staff_id)
    {
        $this->db->insert(db_prefix() . 'dps_sofia_conversas', [
            'staff_id'    => (int) $staff_id,
            'dateadded'   => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insert_id();
    }

    public function get_mensagens($conversa_id, $limite = 50)
    {
        $this->db->where('conversa_id', (int) $conversa_id);
        $this->db->order_by('id', 'asc');
        $this->db->limit($limite);

        return $this->db->get(db_prefix() . 'dps_sofia_mensagens')->result_array();
    }

    public function get_mensagem($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get(db_prefix() . 'dps_sofia_mensagens')->row_array();
    }

    private function guardar_mensagem($conversa_id, $papel, $texto, $extra = [])
    {
        $registo = array_merge([
            'conversa_id' => (int) $conversa_id,
            'papel'       => $papel,
            'texto'       => $texto,
            'dateadded'   => date('Y-m-d H:i:s'),
        ], $extra);

        $this->db->insert(db_prefix() . 'dps_sofia_mensagens', $registo);

        /*
         * O id tem de ser lido AQUI, antes do UPDATE seguinte: em MySQL o
         * insert_id fica a zero depois de uma escrita que não seja um INSERT,
         * e o botão "esta resposta está errada" ficaria sem mensagem para
         * apontar.
         */
        $id = (int) $this->db->insert_id();

        $this->db->where('id', (int) $conversa_id);
        $this->db->update(db_prefix() . 'dps_sofia_conversas', ['dateupdated' => date('Y-m-d H:i:s')]);

        return $id;
    }

    /**
     * Trava de segurança por comercial. Cada pergunta custa dinheiro; um script
     * enganado ou um browser em ciclo esvaziaria a conta da API sem ninguém dar
     * por isso até chegar a factura.
     */
    public function excedeu_limite($staff_id)
    {
        $limite = (int) get_option('dps_sofia_ia_limite_hora');
        if ($limite <= 0) {
            return false;
        }

        $this->db->select('COUNT(m.id) as total');
        $this->db->from(db_prefix() . 'dps_sofia_mensagens m');
        $this->db->join(db_prefix() . 'dps_sofia_conversas c', 'c.id = m.conversa_id');
        $this->db->where('c.staff_id', (int) $staff_id);
        $this->db->where('m.papel', 'comercial');
        $this->db->where('m.dateadded >=', date('Y-m-d H:i:s', strtotime('-1 hour')));

        $linha = $this->db->get()->row_array();

        return (int) $linha['total'] >= $limite;
    }

    /* ------------------------------------------------------------------ */
    /* A pergunta                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Devolve ['ok' => bool, 'erro' => string, 'resposta' => string,
     *          'mensagem_id' => int, 'fontes' => array, 'sem_resposta' => bool].
     */
    public function perguntar($pergunta, $conversa_id, $staff_id)
    {
        $pergunta = trim((string) $pergunta);

        if ($pergunta === '') {
            return ['ok' => false, 'erro' => 'Escreva a pergunta.'];
        }

        if ($this->excedeu_limite($staff_id)) {
            return ['ok' => false, 'erro' => 'Já fez muitas perguntas nesta hora. Tente daqui a pouco.'];
        }

        $local = $this->fornecedor() === 'local';

        if (!$local && $this->chave_ativa() === '') {
            return [
                'ok'   => false,
                'erro' => 'A Sofia ainda não tem chave de API configurada. Um administrador tem de a escrever em Sofia IA → Definições.',
            ];
        }

        $conversa_id = (int) $conversa_id;
        $this->guardar_mensagem($conversa_id, 'comercial', $pergunta);

        if ($local) {
            return $this->responder_sem_ia($pergunta, $conversa_id, $staff_id);
        }

        $encontrado = $this->procurar_trechos($pergunta);
        $base       = $this->get_conhecimento_base();

        $prefixo = get_option('dps_sofia_ia_persona') ?: dps_sofia_ia_persona_por_omissao();
        $prefixo .= "\n\n"
            . "Quando o conhecimento abaixo não chegar para responder com segurança, começa a resposta "
            . 'exactamente com ' . DPS_SOFIA_IA_MARCA_SEM_RESPOSTA . ' e escreve a seguir uma frase curta a '
            . "dizer ao comercial que vais pedir a resposta à administração. Não inventes para preencher.";

        if (!empty($base)) {
            $prefixo .= "\n\n## Conhecimento permanente\n\n" . implode("\n\n", $base);
        }

        /*
         * A disponibilidade ao vivo vai à frente do conhecimento estático de
         * propósito, e com instrução explícita de prevalecer: os PDFs de preços
         * têm meses e listam fracções já vendidas. Sem esta regra, a Sofia
         * escolhia a tabela do dossier e mandava o comercial oferecer um T2 que
         * já não existe — o pior erro possível, porque acontece à frente do
         * cliente.
         */
        $ao_vivo = $this->dados_ao_vivo($pergunta);

        $contexto = '';
        if ($ao_vivo !== '') {
            $contexto .= $ao_vivo
                . "\n\nNOTA: para disponibilidade, tipologias em venda e preços, vale o que está "
                . "acima (ao vivo). Se um documento mais abaixo disser outra coisa, o de cima é que "
                . "está certo — os documentos podem listar fracções já vendidas.\n\n";
        }

        $contexto .= empty($encontrado['trechos'])
            ? "## Conhecimento relacionado com esta pergunta\n\n(não foi encontrado nada na base sobre este assunto)"
            : "## Conhecimento relacionado com esta pergunta\n\n" . implode("\n\n", $encontrado['trechos']);

        $historico = $this->historico_para_modelo($conversa_id);

        $resultado = $this->chamar_modelo($prefixo, $contexto, $historico);

        if (!$resultado['ok']) {
            return $resultado;
        }

        $texto        = $resultado['texto'];
        $sem_resposta = strpos($texto, DPS_SOFIA_IA_MARCA_SEM_RESPOSTA) !== false;

        // O marcador é para o código, não para quem lê.
        $texto = trim(str_replace(DPS_SOFIA_IA_MARCA_SEM_RESPOSTA, '', $texto));
        if ($texto === '') {
            $texto = 'Não encontro isso no conhecimento que tenho. Vou pedir a resposta à administração.';
        }

        $mensagem_id = $this->guardar_mensagem($conversa_id, 'sofia', $texto, [
            'fontes'         => $sem_resposta ? null : implode(' | ', $encontrado['fontes']),
            'sem_resposta'   => $sem_resposta ? 1 : 0,
            'tokens_entrada' => $resultado['tokens_entrada'],
            'tokens_saida'   => $resultado['tokens_saida'],
        ]);

        if ($sem_resposta) {
            $this->registar_pendente('sem_resposta', [
                'conversa_id' => $conversa_id,
                'mensagem_id' => $mensagem_id,
                'staff_id'    => $staff_id,
                'pergunta'    => $pergunta,
            ]);
        }

        return [
            'ok'           => true,
            'resposta'     => $texto,
            'mensagem_id'  => $mensagem_id,
            'fontes'       => array_values($encontrado['fontes']),
            'sem_resposta' => $sem_resposta,
        ];
    }

    /**
     * Modo sem IA: procura interna, sem chamada a serviço nenhum.
     *
     * Aqui a Sofia não redige — mostra. Encontra os trechos da base de
     * conhecimento que batem com as palavras da pergunta e devolve-os como
     * estão, com o título de onde vieram. Não interpreta a pergunta, não
     * resume, não junta duas fichas numa resposta e não percebe sinónimos:
     * quem escrever "está caro" não encontra a ficha que fala em "preço".
     *
     * Vale a pena mesmo assim porque o circuito importante mantém-se inteiro:
     * quando não encontra nada, a pergunta segue para a administração
     * exactamente como no modo com IA, e o "esta resposta está errada"
     * continua a funcionar. E o trabalho de construir a base de conhecimento
     * não se perde — no dia em que houver chave de API, é a mesma base que
     * passa a alimentar as respostas escritas.
     */
    private function responder_sem_ia($pergunta, $conversa_id, $staff_id)
    {
        // Aqui o conhecimento permanente entra na procura como qualquer outro:
        // sem modelo a redigir, não há mais nenhum sítio por onde ele apareça.
        $encontrado = $this->procurar_trechos($pergunta, true);
        $itens      = array_slice($encontrado['itens'], 0, 3);

        /*
         * Disponibilidade também aqui. Sem IA a Sofia não redige, mas mostrar a
         * lista actualizada do simulador responde a "o que há disponível?"
         * melhor do que qualquer PDF — e é a pergunta mais frequente.
         */
        $ao_vivo = '';
        if (preg_match('/\b(disponiv|disponív|tipologia|t[0-4]\b|fracc|fraç|preco|preço|quanto custa|stock)/iu', $pergunta)) {
            $ao_vivo = $this->dados_ao_vivo($pergunta);
        }

        $sem_resposta = empty($itens) && $ao_vivo === '';

        if ($sem_resposta) {
            $texto = 'Não encontrei nada sobre isso na base de conhecimento. '
                   . 'Vou pedir a resposta à administração.';
        } else {
            $partes = [];

            if ($ao_vivo !== '') {
                $partes[] = $ao_vivo;
            }

            if (!empty($itens)) {
                $partes[] = 'Encontrei isto na base de conhecimento:';
                foreach ($itens as $item) {
                    $partes[] = '**' . $item['titulo'] . "**\n" . trim($item['texto']);
                }
            }

            $texto = implode("\n\n", $partes);
        }

        $mensagem_id = $this->guardar_mensagem($conversa_id, 'sofia', $texto, [
            'fontes'       => $sem_resposta ? null : implode(' | ', $encontrado['fontes']),
            'sem_resposta' => $sem_resposta ? 1 : 0,
        ]);

        if ($sem_resposta) {
            $this->registar_pendente('sem_resposta', [
                'conversa_id' => $conversa_id,
                'mensagem_id' => $mensagem_id,
                'staff_id'    => $staff_id,
                'pergunta'    => $pergunta,
            ]);
        }

        return [
            'ok'           => true,
            'resposta'     => $texto,
            'mensagem_id'  => $mensagem_id,
            'fontes'       => array_values($encontrado['fontes']),
            'sem_resposta' => $sem_resposta,
        ];
    }

    /**
     * As últimas trocas da conversa, para a Sofia perceber um "e o T3?" que só
     * faz sentido à luz da pergunta anterior.
     */
    private function historico_para_modelo($conversa_id, $quantas = 6)
    {
        $this->db->where('conversa_id', (int) $conversa_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit($quantas);
        $mensagens = array_reverse($this->db->get(db_prefix() . 'dps_sofia_mensagens')->result_array());

        $historico = [];
        foreach ($mensagens as $mensagem) {
            $historico[] = [
                'role'    => $mensagem['papel'] === 'comercial' ? 'user' : 'assistant',
                'content' => $mensagem['texto'],
            ];
        }

        /*
         * A API exige que a conversa comece por uma mensagem do utilizador. Se
         * a janela apanhar uma resposta da Sofia à cabeça, corta-se.
         */
        while (!empty($historico) && $historico[0]['role'] !== 'user') {
            array_shift($historico);
        }

        return $historico;
    }

    /* ------------------------------------------------------------------ */
    /* Chamada ao modelo                                                   */
    /* ------------------------------------------------------------------ */

    public function fornecedor()
    {
        $escolhido = (string) get_option('dps_sofia_ia_fornecedor');

        return in_array($escolhido, ['claude', 'openai', 'local'], true) ? $escolhido : 'claude';
    }

    public function chave_ativa()
    {
        if ($this->fornecedor() === 'local') {
            return '';
        }

        return trim((string) get_option(
            $this->fornecedor() === 'openai' ? 'dps_sofia_ia_api_key_openai' : 'dps_sofia_ia_api_key_claude'
        ));
    }

    /**
     * Se a Sofia consegue responder já. No modo sem IA está sempre pronta —
     * não depende de nada externo.
     */
    public function esta_pronta()
    {
        return $this->fornecedor() === 'local' || $this->chave_ativa() !== '';
    }

    private function chamar_modelo($prefixo, $contexto, $historico)
    {
        if ($this->fornecedor() === 'openai') {
            return $this->chamar_openai($prefixo, $contexto, $historico);
        }

        return $this->chamar_claude($prefixo, $contexto, $historico);
    }

    /**
     * Claude, via HTTP directo.
     *
     * Não se usa o SDK oficial de PHP de propósito: o servidor é alojamento
     * partilhado sem composer e o deploy é por FTP, por isso uma dependência
     * nova seria uma pasta vendor a viajar à mão. Todo o CRM já fala com APIs
     * assim (ElevenLabs, Evolution, Moloni).
     */
    private function chamar_claude($prefixo, $contexto, $historico)
    {
        $modelo = get_option('dps_sofia_ia_modelo') ?: 'claude-opus-5';

        $corpo = [
            'model'      => $modelo,
            'max_tokens' => 1500,
            /*
             * O prefixo (persona + conhecimento permanente) é igual em todas as
             * perguntas, por isso leva marca de cache: a partir da segunda
             * pergunta é cobrado a uma fracção do preço. O contexto da pergunta
             * vem depois, sem marca, porque muda sempre.
             */
            'system' => [
                [
                    'type'          => 'text',
                    'text'          => $prefixo,
                    'cache_control' => ['type' => 'ephemeral'],
                ],
                [
                    'type' => 'text',
                    'text' => $contexto,
                ],
            ],
            // Responder a partir de texto dado não precisa de raciocínio longo.
            'thinking'      => ['type' => 'adaptive'],
            'output_config' => ['effort' => 'low'],
            'messages'      => $historico,
        ];

        $resposta = $this->http(
            'https://api.anthropic.com/v1/messages',
            $corpo,
            [
                'x-api-key: ' . $this->chave_ativa(),
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ]
        );

        if (!$resposta['ok']) {
            return $resposta;
        }

        $dados = $resposta['dados'];

        if (isset($dados['error'])) {
            return ['ok' => false, 'erro' => 'A API respondeu com erro: ' . $dados['error']['message']];
        }

        /*
         * Uma recusa vem com HTTP 200 e content vazio. Ler content[0] sem
         * verificar o stop_reason dava um erro de PHP em vez de uma mensagem.
         */
        if (isset($dados['stop_reason']) && $dados['stop_reason'] === 'refusal') {
            return [
                'ok'   => false,
                'erro' => 'O modelo recusou responder a esta pergunta. Reformule ou fale com a administração.',
            ];
        }

        $texto = '';
        foreach (isset($dados['content']) ? $dados['content'] : [] as $bloco) {
            if (isset($bloco['type']) && $bloco['type'] === 'text') {
                $texto .= $bloco['text'];
            }
        }

        if (trim($texto) === '') {
            return ['ok' => false, 'erro' => 'A Sofia não devolveu resposta. Tente novamente.'];
        }

        return [
            'ok'             => true,
            'texto'          => trim($texto),
            'tokens_entrada' => isset($dados['usage']['input_tokens']) ? (int) $dados['usage']['input_tokens'] : null,
            'tokens_saida'   => isset($dados['usage']['output_tokens']) ? (int) $dados['usage']['output_tokens'] : null,
        ];
    }

    private function chamar_openai($prefixo, $contexto, $historico)
    {
        $modelo = get_option('dps_sofia_ia_modelo_openai') ?: 'gpt-4o';

        $mensagens = [
            ['role' => 'system', 'content' => $prefixo],
            ['role' => 'system', 'content' => $contexto],
        ];

        foreach ($historico as $mensagem) {
            $mensagens[] = $mensagem;
        }

        $resposta = $this->http(
            'https://api.openai.com/v1/chat/completions',
            [
                'model'      => $modelo,
                'max_tokens' => 1500,
                'messages'   => $mensagens,
            ],
            [
                'Authorization: Bearer ' . $this->chave_ativa(),
                'Content-Type: application/json',
            ]
        );

        if (!$resposta['ok']) {
            return $resposta;
        }

        $dados = $resposta['dados'];

        if (isset($dados['error'])) {
            return ['ok' => false, 'erro' => 'A API respondeu com erro: ' . $dados['error']['message']];
        }

        $texto = isset($dados['choices'][0]['message']['content']) ? $dados['choices'][0]['message']['content'] : '';

        if (trim($texto) === '') {
            return ['ok' => false, 'erro' => 'A Sofia não devolveu resposta. Tente novamente.'];
        }

        return [
            'ok'             => true,
            'texto'          => trim($texto),
            'tokens_entrada' => isset($dados['usage']['prompt_tokens']) ? (int) $dados['usage']['prompt_tokens'] : null,
            'tokens_saida'   => isset($dados['usage']['completion_tokens']) ? (int) $dados['usage']['completion_tokens'] : null,
        ];
    }

    private function http($url, $corpo, $cabecalhos)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => $cabecalhos,
            CURLOPT_TIMEOUT        => 90,
        ]);

        $bruto  = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro   = curl_error($ch);
        curl_close($ch);

        if ($bruto === false) {
            log_activity('Sofia IA: falha de rede — ' . $erro);

            return ['ok' => false, 'erro' => 'Não consegui falar com o serviço de IA. Tente novamente.'];
        }

        $dados = json_decode($bruto, true);

        if (!is_array($dados)) {
            log_activity('Sofia IA: resposta ilegível (HTTP ' . $codigo . ') — ' . substr($bruto, 0, 300));

            return ['ok' => false, 'erro' => 'O serviço de IA devolveu uma resposta que não consegui ler.'];
        }

        /*
         * Guarda-se o erro no registo de actividade porque a mensagem que o
         * comercial vê é deliberadamente vaga — a resposta da API pode conter
         * detalhes de conta que não têm de aparecer no ecrã.
         */
        if ($codigo >= 400) {
            log_activity('Sofia IA: HTTP ' . $codigo . ' — ' . substr($bruto, 0, 500));
        }

        return ['ok' => true, 'dados' => $dados];
    }

    /* ------------------------------------------------------------------ */
    /* Por responder (sem resposta + reportes)                             */
    /* ------------------------------------------------------------------ */

    public function registar_pendente($tipo, $dados)
    {
        $registo = [
            'tipo'           => $tipo,
            'conversa_id'    => isset($dados['conversa_id']) ? (int) $dados['conversa_id'] : null,
            'mensagem_id'    => isset($dados['mensagem_id']) ? (int) $dados['mensagem_id'] : null,
            'staff_id'       => (int) $dados['staff_id'],
            'pergunta'       => $dados['pergunta'],
            'resposta_sofia' => isset($dados['resposta_sofia']) ? $dados['resposta_sofia'] : null,
            'nota'           => isset($dados['nota']) ? $dados['nota'] : null,
            'estado'         => 'aberta',
            'dateadded'      => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'dps_sofia_pendentes', $registo);
        $id = (int) $this->db->insert_id();

        $quem = get_staff_full_name($registo['staff_id']);

        $this->notificar_gestores(
            $tipo === 'reporte'
                ? 'Sofia IA: ' . $quem . ' reportou uma resposta errada'
                : 'Sofia IA: ' . $quem . ' fez uma pergunta que a Sofia não soube responder',
            'dps_sofia_ia/pendentes'
        );

        return $id;
    }

    /**
     * O comercial diz que a resposta está errada.
     *
     * Uma resposta errada que ninguém corrige fica na base a ser repetida a
     * toda a equipa, por isso este caminho existe mesmo quando a Sofia respondeu
     * com confiança.
     */
    public function reportar_resposta($mensagem_id, $nota, $staff_id)
    {
        $mensagem = $this->get_mensagem($mensagem_id);

        if (!$mensagem || $mensagem['papel'] !== 'sofia') {
            return false;
        }

        // Só se reporta o que é nosso — a conversa de outro comercial não.
        $this->db->where('id', (int) $mensagem['conversa_id']);
        $conversa = $this->db->get(db_prefix() . 'dps_sofia_conversas')->row_array();

        if (!$conversa || (int) $conversa['staff_id'] !== (int) $staff_id) {
            return false;
        }

        // A pergunta é a mensagem do comercial imediatamente anterior.
        $this->db->where('conversa_id', (int) $mensagem['conversa_id']);
        $this->db->where('papel', 'comercial');
        $this->db->where('id <', (int) $mensagem_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $anterior = $this->db->get(db_prefix() . 'dps_sofia_mensagens')->row_array();

        return $this->registar_pendente('reporte', [
            'conversa_id'    => $mensagem['conversa_id'],
            'mensagem_id'    => $mensagem_id,
            'staff_id'       => $staff_id,
            'pergunta'       => $anterior ? $anterior['texto'] : '(pergunta não encontrada)',
            'resposta_sofia' => $mensagem['texto'],
            'nota'           => trim((string) $nota),
        ]);
    }

    public function get_pendentes($estado = 'aberta')
    {
        $this->db->select('p.*, CONCAT(s.firstname, " ", s.lastname) as comercial');
        $this->db->from(db_prefix() . 'dps_sofia_pendentes p');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = p.staff_id', 'left');

        if ($estado) {
            $this->db->where('p.estado', $estado);
        }

        $this->db->order_by('p.id', 'desc');
        $this->db->limit(200);

        return $this->db->get()->result_array();
    }

    public function get_pendente($id)
    {
        $this->db->select('p.*, CONCAT(s.firstname, " ", s.lastname) as comercial');
        $this->db->from(db_prefix() . 'dps_sofia_pendentes p');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = p.staff_id', 'left');
        $this->db->where('p.id', (int) $id);

        return $this->db->get()->row_array();
    }

    /**
     * O admin responde: a resposta vira ficha de conhecimento e o comercial é
     * avisado. É aqui que a Sofia aprende — sem este passo o circuito seria
     * apenas uma caixa de reclamações.
     */
    public function responder_pendente($id, $dados)
    {
        $pendente = $this->get_pendente($id);

        if (!$pendente || $pendente['estado'] !== 'aberta') {
            return false;
        }

        $resposta = trim((string) $dados['resposta']);
        if ($resposta === '') {
            return false;
        }

        $titulo = trim((string) $dados['titulo']);
        if ($titulo === '') {
            $titulo = mb_substr($pendente['pergunta'], 0, 120);
        }

        /*
         * A ficha guarda a pergunta e a resposta. Guardar só a resposta tirava
         * ao índice as palavras pelas quais o comercial procurou — que estão na
         * pergunta, não na resposta.
         */
        $conhecimento_id = $this->guardar_conhecimento([
            'titulo'         => $titulo,
            'categoria'      => isset($dados['categoria']) ? $dados['categoria'] : null,
            'conteudo'       => "Pergunta: " . $pendente['pergunta'] . "\n\nResposta: " . $resposta,
            'fonte'          => 'resposta_admin',
            'sempre_incluir' => !empty($dados['sempre_incluir']),
            'ativo'          => 1,
        ]);

        $this->db->where('id', (int) $id);
        $this->db->update(db_prefix() . 'dps_sofia_pendentes', [
            'estado'          => 'respondida',
            'resposta'        => $resposta,
            'conhecimento_id' => $conhecimento_id,
            'respondido_por'  => get_staff_user_id(),
            'respondido_em'   => date('Y-m-d H:i:s'),
        ]);

        // O comercial que perguntou fica a saber que já há resposta.
        add_notification([
            'description' => 'Sofia IA: já há resposta para a sua pergunta — ' . mb_substr($pendente['pergunta'], 0, 80),
            'touserid'    => (int) $pendente['staff_id'],
            'fromcompany' => 1,
            'fromuserid'  => null,
            'link'        => 'dps_sofia_ia',
        ]);
        pusher_trigger_notification([(int) $pendente['staff_id']]);

        return $conhecimento_id;
    }

    public function ignorar_pendente($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->where('estado', 'aberta');
        $this->db->update(db_prefix() . 'dps_sofia_pendentes', [
            'estado'         => 'ignorada',
            'respondido_por' => get_staff_user_id(),
            'respondido_em'  => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Quem recebe os avisos. A opção vazia significa "todos os administradores"
     * — assim o módulo funciona logo depois de instalado, sem configuração.
     */
    private function notificar_gestores($texto, $link)
    {
        $configurados = array_filter(array_map('intval', explode(',', (string) get_option('dps_sofia_ia_notificar_staff'))));

        if (empty($configurados)) {
            $this->db->select('staffid');
            $this->db->where('admin', 1);
            $this->db->where('active', 1);
            foreach ($this->db->get(db_prefix() . 'staff')->result_array() as $admin) {
                $configurados[] = (int) $admin['staffid'];
            }
        }

        foreach ($configurados as $staff_id) {
            add_notification([
                'description' => $texto,
                'touserid'    => $staff_id,
                'fromcompany' => 1,
                'fromuserid'  => null,
                'link'        => $link,
            ]);
        }

        if (!empty($configurados)) {
            pusher_trigger_notification($configurados);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Importar da Sofia das chamadas (ElevenLabs)                         */
    /* ------------------------------------------------------------------ */

    /**
     * Traz o que a Sofia das chamadas já sabe: as instruções do agente e os
     * documentos que lhe foram associados na ElevenLabs.
     *
     * A chave e o agente são os que o módulo dps_sofia_calls já tem guardados,
     * para não haver duas configurações da mesma conta a divergir.
     */
    /**
     * A chave da ElevenLabs a usar na importação.
     *
     * Tem opção própria porque a conta onde o conhecimento foi carregado pode
     * não ser a mesma que faz as chamadas. Só quando esta está vazia é que se
     * usa a do módulo Sofia Calls — que era a única hipótese antes e levava a
     * importação a olhar para a conta errada, sem erro nenhum: ligava-se,
     * encontrava um espaço de trabalho vazio, e dizia que não havia nada.
     */
    private function chave_elevenlabs()
    {
        $propria = trim((string) get_option('dps_sofia_ia_elevenlabs_key'));

        return $propria !== '' ? $propria : trim((string) get_option('sofia_calls_elevenlabs_api_key'));
    }

    public function importar_da_elevenlabs()
    {
        $chave = $this->chave_elevenlabs();

        if ($chave === '') {
            return [
                'ok'   => false,
                'erro' => 'Não há chave da ElevenLabs. Escreva-a em Sofia IA → Definições '
                        . '(ou configure o módulo Sofia Calls).',
            ];
        }

        $importadas = 0;
        $falhadas   = [];

        /*
         * Importa-se a base de conhecimento do ESPAÇO DE TRABALHO inteiro, e
         * não só os documentos ligados a um agente. Foi aí que o conhecimento
         * foi carregado, e há documentos que não estão presos a agente nenhum —
         * lê-los pelo agente deixava-os de fora sem dar sinal disso.
         */
        $pagina = 'https://api.elevenlabs.io/v1/convai/knowledge-base?page_size=100';

        while ($pagina) {
            $lista = $this->get_json_elevenlabs($pagina, $chave);

            if (!is_array($lista)) {
                return ['ok' => false, 'erro' => 'Não consegui ler a base de conhecimento na ElevenLabs. Verifique a chave.'];
            }

            $documentos = isset($lista['documents']) ? $lista['documents'] : [];

            foreach ($documentos as $documento) {
                if (empty($documento['id'])) {
                    continue;
                }

                $texto = $this->texto_documento_elevenlabs($documento['id'], $chave);
                $nome  = !empty($documento['name']) ? $documento['name'] : ('Documento ' . $documento['id']);

                if (trim((string) $texto) === '') {
                    // Documentos do tipo URL/PDF externo nem sempre expõem
                    // texto. Fica registado em vez de desaparecer em silêncio.
                    $falhadas[] = $nome;
                    continue;
                }

                $this->guardar_conhecimento_elevenlabs($documento['id'], $nome, $texto);
                $importadas++;
            }

            $pagina = !empty($lista['has_more']) && !empty($lista['next_cursor'])
                ? 'https://api.elevenlabs.io/v1/convai/knowledge-base?page_size=100&cursor=' . urlencode($lista['next_cursor'])
                : null;
        }

        // As instruções do agente, se houver agente configurado.
        $agente = trim((string) get_option('dps_sofia_ia_elevenlabs_agente'))
               ?: trim((string) get_option('sofia_calls_default_agent_id'));

        if ($agente !== '') {
            $agente_dados = $this->get_json_elevenlabs('https://api.elevenlabs.io/v1/convai/agents/' . $agente, $chave);
            $prompt = isset($agente_dados['conversation_config']['agent']['prompt']['prompt'])
                ? $agente_dados['conversation_config']['agent']['prompt']['prompt']
                : '';

            if (trim($prompt) !== '') {
                $this->guardar_conhecimento_elevenlabs('agente:' . $agente, 'Instruções da Sofia (agente de chamadas)', $prompt);
                $importadas++;
            }
        }

        if ($importadas === 0) {
            return [
                'ok'   => false,
                'erro' => 'Liguei-me à ElevenLabs mas não trouxe nada legível. '
                        . (empty($falhadas) ? 'A base de conhecimento está vazia nesta conta.'
                                            : 'Documentos sem texto acessível: ' . implode(', ', $falhadas)),
            ];
        }

        return ['ok' => true, 'importadas' => $importadas, 'falhadas' => $falhadas];
    }

    /**
     * A ElevenLabs expõe o conteúdo dos documentos em mais do que um sítio
     * consoante a versão. Tenta-se o mais específico primeiro e cai-se para os
     * campos do próprio objecto — assim uma mudança de API não deixa a
     * importação a zero sem explicação.
     */
    private function texto_documento_elevenlabs($documento_id, $chave)
    {
        $conteudo = $this->get_json_elevenlabs(
            'https://api.elevenlabs.io/v1/convai/knowledge-base/' . $documento_id . '/content',
            $chave,
            true
        );

        if (is_string($conteudo) && trim($conteudo) !== '') {
            return $conteudo;
        }

        $documento = $this->get_json_elevenlabs('https://api.elevenlabs.io/v1/convai/knowledge-base/' . $documento_id, $chave);

        if (!is_array($documento)) {
            return '';
        }

        foreach (['extracted_inner_html', 'text', 'content'] as $campo) {
            if (!empty($documento[$campo]) && is_string($documento[$campo])) {
                return trim(strip_tags($documento[$campo]));
            }
        }

        return '';
    }

    private function get_json_elevenlabs($url, $chave, $devolver_bruto = false)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $chave],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $bruto  = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($bruto === false || $codigo >= 400) {
            return null;
        }

        if ($devolver_bruto) {
            $json = json_decode($bruto, true);

            return is_array($json) ? null : $bruto;
        }

        return json_decode($bruto, true);
    }

    /**
     * Importar duas vezes tem de actualizar a ficha, não criar uma cópia — caso
     * contrário a base enche-se de versões antigas do mesmo documento e a Sofia
     * passa a ver preços contraditórios.
     *
     * A correspondência é pelo ID do documento na ElevenLabs, não pelo título.
     * Pelo título não servia: naquela conta há três documentos chamados
     * "Belo Horizonte Residences - Manual Operador Virtual" com tamanhos
     * diferentes, e casar por nome fazia o último apagar os outros dois — uma
     * perda de conteúdo silenciosa, que só se notaria quando a Sofia desse uma
     * resposta incompleta.
     */
    private function guardar_conhecimento_elevenlabs($documento_id, $titulo, $conteudo)
    {
        $referencia = 'elevenlabs:' . $documento_id;

        $this->db->where('fonte_id', $referencia);
        $this->db->limit(1);
        $existente = $this->db->get(db_prefix() . 'dps_sofia_conhecimento')->row_array();

        $id = $this->guardar_conhecimento([
            'titulo'         => $titulo,
            'categoria'      => 'empresa',
            'conteudo'       => dps_sofia_ia_forcar_utf8($conteudo),
            'fonte'          => 'elevenlabs',
            'sempre_incluir' => $existente ? $existente['sempre_incluir'] : 0,
            'ativo'          => 1,
        ], $existente ? $existente['id'] : null);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'dps_sofia_conhecimento', ['fonte_id' => $referencia]);

        return $id;
    }
}
