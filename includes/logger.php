<?php
/**
 * Sistema de Auditoria e Histórico de Logs
 * Requisito CORE: Manter sempre os logs de qualquer alteração feita por qualquer usuário
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registra uma ação no histórico de auditoria do sistema
 * 
 * @param PDO $pdo Instância da conexão PDO
 * @param string $acao Nome da ação executada (ex: LOGIN, CRIAR_TURMA, ATUALIZAR_RESULTADO)
 * @param string|null $tabela Nome da tabela afetada
 * @param int|null $registro_id ID do registro afetado
 * @param string|array|null $detalhes Texto explicativo ou array com alterações
 * @return bool
 */
function log_auditoria(PDO $pdo, string $acao, ?string $tabela = null, ?int $registro_id = null, mixed $detalhes = null): bool {
    try {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $usuario_nome = $_SESSION['usuario_nome'] ?? 'Anônimo / Visitante';

        // Formatar detalhes se for array
        if (is_array($detalhes)) {
            $detalhes = json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Obter IP de forma confiável
        $ip = $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '127.0.0.1';
        
        // Tratar múltiplos IPs no X-Forwarded-For
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO logs_auditoria (usuario_id, usuario_nome, acao, tabela_afetada, registro_id, detalhes, ip_origem, data_hora)
            VALUES (:usuario_id, :usuario_nome, :acao, :tabela, :registro_id, :detalhes, :ip, NOW())
        ");

        return $stmt->execute([
            ':usuario_id'   => $usuario_id,
            ':usuario_nome' => $usuario_nome,
            ':acao'         => strtoupper($acao),
            ':tabela'       => $tabela,
            ':registro_id'  => $registro_id,
            ':detalhes'     => $detalhes,
            ':ip'           => substr($ip, 0, 45)
        ]);
    } catch (Exception $e) {
        // Falha no log não deve interromper fluxo crítico se a auditoria falhar, mas registramos no erro do PHP
        error_log("Falha ao gravar log de auditoria: " . $e->getMessage());
        return false;
    }
}
