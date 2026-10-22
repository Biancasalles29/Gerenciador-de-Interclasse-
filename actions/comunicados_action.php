<?php
/**
 * Controller de Comunicados Oficiais e Notificações
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_tecnico_or_admin('../comunicados.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$edicaoAtiva = get_active_edicao($pdo);

switch ($action) {
    case 'criar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../comunicados.php');
            exit;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        $tipo = $_POST['tipo'] ?? 'geral';
        $partida_id = !empty($_POST['partida_id']) ? (int)$_POST['partida_id'] : null;

        if (!in_array($tipo, ['urgente', 'cronograma', 'geral'])) {
            $tipo = 'geral';
        }

        if (empty($titulo) || empty($conteudo)) {
            set_flash_message('error', 'O título e o conteúdo do comunicado são obrigatórios.');
            header('Location: ../comunicados.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO comunicados (edicao_id, usuario_id, titulo, conteudo, tipo, partida_id)
                VALUES (:edicao_id, :usuario_id, :titulo, :conteudo, :tipo, :partida_id)
            ");

            $stmt->execute([
                ':edicao_id'   => $edicaoAtiva['id'],
                ':usuario_id'  => $_SESSION['usuario_id'] ?? null,
                ':titulo'      => $titulo,
                ':conteudo'    => $conteudo,
                ':tipo'        => $tipo,
                ':partida_id'  => $partida_id
            ]);

            $id = (int)$pdo->lastInsertId();
            log_auditoria($pdo, 'PUBLICAR_COMUNICADO', 'comunicados', $id, "Novo comunicado: {$titulo} ({$tipo})");

            set_flash_message('success', 'Comunicado oficial publicado no Mural!');
            header('Location: ../comunicados.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao publicar comunicado: ' . $e->getMessage());
            header('Location: ../comunicados.php');
            exit;
        }
        break;

    case 'excluir':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        try {
            $stmtDel = $pdo->prepare("DELETE FROM comunicados WHERE id = :id");
            $stmtDel->execute([':id' => $id]);

            log_auditoria($pdo, 'EXCLUIR_COMUNICADO', 'comunicados', $id, "Exclusão do comunicado #{$id}");

            set_flash_message('success', 'Comunicado removido.');
            header('Location: ../comunicados.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao excluir comunicado: ' . $e->getMessage());
            header('Location: ../comunicados.php');
            exit;
        }
        break;

    default:
        header('Location: ../comunicados.php');
        exit;
}
