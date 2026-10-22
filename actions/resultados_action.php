<?php
/**
 * Controller de Resultados e Placar das Partidas
 * 
 * Regras CORE:
 * - Vitória vale 3 pontos, empate 1, derrota 0
 * - Classificação recalculada automaticamente após cada resultado registrado
 * - Transação PDO para garantir integridade e evitar perda de dados
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_admin('../jogos.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'lancar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../jogos.php');
            exit;
        }

        $id = (int)($_POST['partida_id'] ?? 0);
        $placar_mandante = isset($_POST['placar_mandante']) && $_POST['placar_mandante'] !== '' ? (int)$_POST['placar_mandante'] : null;
        $placar_visitante = isset($_POST['placar_visitante']) && $_POST['placar_visitante'] !== '' ? (int)$_POST['placar_visitante'] : null;
        $status = $_POST['status'] ?? 'encerrado';
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Buscar dados da partida
        $stmtPartida = $pdo->prepare("
            SELECT p.*, tm.sigla AS m_sigla, tv.sigla AS v_sigla 
            FROM partidas p
            JOIN turmas tm ON tm.id = p.turma_mandante_id
            JOIN turmas tv ON tv.id = p.turma_visitante_id
            WHERE p.id = :id
        ");
        $stmtPartida->execute([':id' => $id]);
        $partida = $stmtPartida->fetch();

        if (!$partida) {
            set_flash_message('error', 'Partida não encontrada.');
            header('Location: ../jogos.php');
            exit;
        }

        // Validação de placares negativos
        if (($placar_mandante !== null && $placar_mandante < 0) || ($placar_visitante !== null && $placar_visitante < 0)) {
            set_flash_message('error', 'O placar não pode conter valores negativos.');
            header("Location: ../partida_detalhes.php?id={$id}");
            exit;
        }

        try {
            // Transação PDO para integridade
            $pdo->beginTransaction();

            $stmtUpdate = $pdo->prepare("
                UPDATE partidas 
                SET placar_mandante = :placar_m, placar_visitante = :placar_v, status = :status, observacoes = :obs
                WHERE id = :id
            ");

            $stmtUpdate->execute([
                ':placar_m' => $placar_mandante,
                ':placar_v' => $placar_visitante,
                ':status'   => $status,
                ':obs'      => $observacoes,
                ':id'       => $id
            ]);

            log_auditoria($pdo, 'ATUALIZAR_RESULTADO', 'partidas', $id, [
                'partida'  => "{$partida['m_sigla']} vs {$partida['v_sigla']}",
                'placar'   => "{$placar_mandante} x {$placar_visitante}",
                'status'   => $status
            ]);

            $pdo->commit();

            set_flash_message('success', "Placar atualizado ({$partida['m_sigla']} {$placar_mandante} x {$placar_visitante} {$partida['v_sigla']})! A classificação foi atualizada automaticamente.");
            header("Location: ../partida_detalhes.php?id={$id}");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Erro ao salvar resultado: ' . $e->getMessage());
            header("Location: ../partida_detalhes.php?id={$id}");
            exit;
        }
        break;

    default:
        header('Location: ../jogos.php');
        exit;
}
