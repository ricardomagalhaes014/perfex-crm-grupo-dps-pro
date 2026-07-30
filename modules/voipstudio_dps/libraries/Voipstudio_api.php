<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cliente minimalista da API REST do VoIPstudio (l7api.com/v1.1/voipstudio)
 *
 * Autenticação com contas que têm 2FA:
 *  - O token guardado é usado até a API responder 401 (não expira por relógio:
 *    era a expiração de 10 min que obrigava a re-login constante e, com 2FA
 *    ligado, cada re-login disparava um email com código — daí a chuva de
 *    emails "Two-factor Authentication").
 *  - Quando a API pede 2FA, o módulo NÃO insiste: marca "código pendente",
 *    entra em pausa (cooldown) e o admin introduz o código recebido por email
 *    nas Definições do módulo. Só aí se volta a falar com a API.
 */
class Voipstudio_api
{
    const BASE = 'https://l7api.com/v1.1/voipstudio';

    /** Pausa entre tentativas de login falhadas — trava a chuva de emails. */
    const COOLDOWN_SEGUNDOS = 1800;

    protected function http($method, $path, $body = null, $token = null)
    {
        $ch = curl_init(self::BASE . $path);
        $headers = ['Content-Type: application/json'];
        if ($token) {
            // A API exige Basic com "email:token" — só o token dá
            // "Failed to parse Authorization header credentials" (HTTP 400).
            $headers[] = 'Authorization: Basic '
                . base64_encode(get_option('voipstudio_dps_email') . ':' . $token);
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            return ['code' => 0, 'body' => null, 'error' => $err];
        }
        return ['code' => $code, 'body' => json_decode($resp, true), 'error' => null];
    }

    /** A resposta do /login está a pedir o código 2FA? */
    protected function pede_2fa($r)
    {
        $texto = strtolower((string) json_encode($r['body'] ?? []));

        return strpos($texto, 'two-factor') !== false
            || strpos($texto, 'two_factor') !== false
            || strpos($texto, '2fa') !== false
            || strpos($texto, 'otp') !== false
            || strpos($texto, 'verification code') !== false;
    }

    /** Há um código 2FA por confirmar? (usado pelas Definições e pelo cron) */
    public function aguarda_2fa()
    {
        return get_option('voipstudio_dps_2fa_pendente') === '1';
    }

    protected function em_cooldown()
    {
        $ultimo = (int) get_option('voipstudio_dps_login_tentativa');

        return $ultimo && (time() - $ultimo) < self::COOLDOWN_SEGUNDOS;
    }

    /**
     * Devolve um user_token válido.
     *
     * $force só é usado depois de um 401 real — nunca por relógio. Se a conta
     * pedir 2FA ou estivermos em cooldown, lança Exception SEM tentar /login,
     * para não disparar mais emails de código.
     */
    public function token($force = false)
    {
        $tok = get_option('voipstudio_dps_token');

        if (!$force && $tok) {
            return $tok;
        }

        if ($this->aguarda_2fa()) {
            throw new Exception('VoIPstudio pediu o código 2FA — introduza nas Definições do módulo o código que recebeu por email.');
        }

        if ($this->em_cooldown()) {
            throw new Exception('Login VoIPstudio em pausa (última tentativa falhou). Confirme as credenciais/código nas Definições.');
        }

        $email = get_option('voipstudio_dps_email');
        $pass  = get_option('voipstudio_dps_password');
        if (!$email || !$pass) {
            throw new Exception('Credenciais VoIPstudio não configuradas (Definições do módulo).');
        }

        update_option('voipstudio_dps_login_tentativa', time());

        $r = $this->http('POST', '/login', ['email' => $email, 'password' => $pass, 'remember_me' => true]);

        if ($r['code'] === 200 && !empty($r['body']['user_token'])) {
            $this->guardar_token($r['body']['user_token']);

            return $r['body']['user_token'];
        }

        if ($this->pede_2fa($r)) {
            // Um email com código acabou de sair — fica pendente até o admin
            // o introduzir; entretanto ninguém volta a bater no /login.
            update_option('voipstudio_dps_2fa_pendente', '1');
            throw new Exception('O VoIPstudio enviou um código 2FA para o email da conta — introduza-o nas Definições do módulo (campo "Código 2FA").');
        }

        $msg = isset($r['body']['message']) ? $r['body']['message'] : ('HTTP ' . $r['code']);
        throw new Exception('Login VoIPstudio falhou: ' . $msg);
    }

    /**
     * Completa o login com o código 2FA recebido por email.
     * O nome do campo varia entre versões da API, por isso enviam-se os
     * sinónimos habituais — os desconhecidos são ignorados pelo servidor.
     */
    public function login_com_codigo($codigo)
    {
        $email  = get_option('voipstudio_dps_email');
        $pass   = get_option('voipstudio_dps_password');
        $codigo = trim((string) $codigo);

        if (!$email || !$pass) {
            throw new Exception('Credenciais VoIPstudio não configuradas.');
        }
        if ($codigo === '') {
            throw new Exception('Introduza o código recebido por email.');
        }

        $r = $this->http('POST', '/login', [
            'email'           => $email,
            'password'        => $pass,
            'remember_me'     => true,
            'code'            => $codigo,
            'otp'             => $codigo,
            'two_factor_code' => $codigo,
        ]);

        if ($r['code'] === 200 && !empty($r['body']['user_token'])) {
            $this->guardar_token($r['body']['user_token']);

            return true;
        }

        $msg = isset($r['body']['message']) ? $r['body']['message'] : ('HTTP ' . $r['code']);
        throw new Exception('O código não foi aceite: ' . $msg . ' — use o código do email MAIS RECENTE.');
    }

    protected function guardar_token($tok)
    {
        update_option('voipstudio_dps_token', $tok);
        update_option('voipstudio_dps_token_time', time());
        update_option('voipstudio_dps_2fa_pendente', '0');
        update_option('voipstudio_dps_login_tentativa', '0');
    }

    /** Pedido autenticado com UMA renovação em 401 (guardada por cooldown). */
    public function request($method, $path, $body = null)
    {
        $r = $this->http($method, $path, $body, $this->token());
        if ($r['code'] === 401) {
            update_option('voipstudio_dps_token', '');
            $r = $this->http($method, $path, $body, $this->token(true));
        }

        return $r;
    }

    /** Click-to-call: liga primeiro ao dispositivo da conta, depois ao destino. */
    public function call($to_e164, $caller_id = null)
    {
        $body = ['to' => $to_e164];
        if ($caller_id) {
            $body['caller_id'] = $caller_id;
        }

        return $this->request('POST', '/calls', $body);
    }

    /** Registos de chamadas (CDR). */
    public function cdrs($page = 1, $limit = 100)
    {
        return $this->request('GET', '/cdrs?page=' . (int) $page . '&limit=' . (int) $limit . '&sort=-calldate');
    }
}
