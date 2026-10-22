<?php
/**
 * Gerenciamento de Mensagens Flash (TOAST)
 * Permite enviar mensagens de sucesso, erro, alerta ou informação entre requisições
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Define uma mensagem flash para ser exibida na próxima requisição
 * @param string $tipo 'success' | 'error' | 'warning' | 'info'
 * @param string $mensagem Texto da notificação
 * @param string|null $titulo Título opcional
 */
function set_flash_message(string $tipo, string $mensagem, ?string $titulo = null): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $tipo,
        'message' => $mensagem,
        'title'   => $titulo ?? match($tipo) {
            'success' => 'Sucesso!',
            'error'   => 'Erro!',
            'warning' => 'Atenção!',
            'info'    => 'Informação',
            default   => 'Notificação'
        }
    ];
}

/**
 * Retorna e limpa as mensagens flash pendentes
 * @return array
 */
function get_flash_messages(): array {
    if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
        return [];
    }
    $messages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
    return $messages;
}
