<?php
// Endereco do backend Python. Pode ser sobrescrito por variavel de ambiente.
define('API_BASE', getenv('PIT_API_BASE') ?: 'http://127.0.0.1:8010');

/**
 * Chamada HTTP simples ao backend (sem dependencia externa).
 * Retorna array: ['status' => int, 'body' => array|null, 'erro' => string|null]
 */
function api_call($metodo, $rota, $payload = null)
{
    $url = API_BASE . $rota;
    $opts = [
        'http' => [
            'method'        => $metodo,
            'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'ignore_errors' => true,
            'timeout'       => 10,
        ],
    ];
    if ($payload !== null) {
        $opts['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    $corpo = @file_get_contents($url, false, stream_context_create($opts));
    if ($corpo === false) {
        return ['status' => 0, 'body' => null, 'erro' => 'backend indisponivel em ' . API_BASE];
    }
    // PHP 8.4+ expoe http_get_last_response_headers(); antes disso,
    // o unico caminho e a variavel magica $http_response_header.
    $cabecalhos = function_exists('http_get_last_response_headers')
        ? (http_get_last_response_headers() ?: [])
        : ($http_response_header ?? []);

    $status = 0;
    if (isset($cabecalhos[0]) && preg_match('{HTTP/\S+\s(\d{3})}', $cabecalhos[0], $m)) {
        $status = (int) $m[1];
    }
    return ['status' => $status, 'body' => json_decode($corpo, true), 'erro' => null];
}

function e($txt)
{
    return htmlspecialchars((string) $txt, ENT_QUOTES, 'UTF-8');
}
