<?php
/**
 * Controller de Edições do Torneio
 * Regra: Dados de uma edição de interclasse não podem interferir em outra
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'selecionar':
        // Qualquer usuário pode alternar a visualização da edição desejada
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT id, nome FROM edicoes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $edicao = $stmt->fetch();

            if ($edicao) {
                $_SESSION['edicao_ativa_id'] = $edicao['id'];
                set_flash_message('info', "Edição ativa alterada para: {$edicao['nome']}");
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
        header("Location: {$referer}");
        exit;

    case 'criar':
        require_admin('../views/edicoes.php');

        $nome = trim($_POST['nome'] ?? '');
        $ano = !empty($_POST['ano']) ? (int)$_POST['ano'] : (int)date('Y');
        $status = $_POST['status'] ?? 'em_andamento';
        $tornar_ativa = isset($_POST['ativa']) ? 1 : 0;

        if (empty($nome)) {
            set_flash_message('error', 'O nome da edição é obrigatório.');
            header('Location: ../views/edicoes.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            if ($tornar_ativa) {
                $pdo->exec("UPDATE edicoes SET ativa = 0");
            }

            $stmt = $pdo->prepare("
                INSERT INTO edicoes (nome, ano, status, ativa)
                VALUES (:nome, :ano, :status, :ativa)
            ");

            $stmt->execute([
                ':nome'   => $nome,
                ':ano'    => $ano,
                ':status' => $status,
                ':ativa'  => $tornar_ativa
            ]);

            $novoId = (int)$pdo->lastInsertId();

            if ($tornar_ativa) {
                $_SESSION['edicao_ativa_id'] = $novoId;
            }

            log_auditoria($pdo, 'CRIAR_EDICAO', 'edicoes', $novoId, "Nova edição cadastrada: {$nome} ({$ano})");

            $pdo->commit();

            set_flash_message('success', "Edição '{$nome}' criada com sucesso!");
            header('Location: ../views/edicoes.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Erro ao criar edição: ' . $e->getMessage());
            header('Location: ../views/edicoes.php');
            exit;
        }
        break;

    case 'ativar':
        require_admin('../views/edicoes.php');

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $pdo->exec("UPDATE edicoes SET ativa = 0");

            $stmt = $pdo->prepare("UPDATE edicoes SET ativa = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $_SESSION['edicao_ativa_id'] = $id;

            log_auditoria($pdo, 'DEFINIR_EDICAO_ATIVA', 'edicoes', $id, "Definida edição #{$id} como padrão");

            $pdo->commit();

            set_flash_message('success', 'Edição definida como ativa padrão do sistema.');
            header('Location: ../views/edicoes.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Erro ao ativar edição: ' . $e->getMessage());
            header('Location: ../views/edicoes.php');
            exit;
        }
        break;

    default:
        header('Location: ../index.php');
        exit;
}
