<?php
/**
 * Controller de Modalidades Esportivas
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_admin('../views/modalidades.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$edicaoAtiva = get_active_edicao($pdo);

switch ($action) {
    case 'criar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/modalidades.php');
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Geral');
        $icone = trim($_POST['icone'] ?? 'fa-futbol');
        $permite_empate = isset($_POST['permite_empate']) ? 1 : 0;
        $tempo = !empty($_POST['tempo_partida_minutos']) ? (int)$_POST['tempo_partida_minutos'] : 30;

        if (empty($nome)) {
            set_flash_message('error', 'O nome da modalidade é obrigatório.');
            header('Location: ../views/modalidades.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO modalidades (edicao_id, nome, categoria, icone, permite_empate, tempo_partida_minutos)
                VALUES (:edicao_id, :nome, :categoria, :icone, :permite_empate, :tempo)
            ");

            $stmt->execute([
                ':edicao_id'       => $edicaoAtiva['id'],
                ':nome'            => $nome,
                ':categoria'       => $categoria,
                ':icone'           => $icone,
                ':permite_empate'  => $permite_empate,
                ':tempo'           => $tempo
            ]);

            $id = (int)$pdo->lastInsertId();
            log_auditoria($pdo, 'CRIAR_MODALIDADE', 'modalidades', $id, "Modalidade {$nome} criada na edição {$edicaoAtiva['nome']}");

            set_flash_message('success', "Modalidade '{$nome}' cadastrada com sucesso!");
            header('Location: ../views/modalidades.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao salvar modalidade: ' . $e->getMessage());
            header('Location: ../views/modalidades.php');
            exit;
        }
        break;

    case 'editar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../views/modalidades.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Geral');
        $icone = trim($_POST['icone'] ?? 'fa-futbol');
        $permite_empate = isset($_POST['permite_empate']) ? 1 : 0;
        $tempo = !empty($_POST['tempo_partida_minutos']) ? (int)$_POST['tempo_partida_minutos'] : 30;

        try {
            $stmt = $pdo->prepare("
                UPDATE modalidades 
                SET nome = :nome, categoria = :categoria, icone = :icone, permite_empate = :permite_empate, tempo_partida_minutos = :tempo
                WHERE id = :id
            ");

            $stmt->execute([
                ':nome'           => $nome,
                ':categoria'      => $categoria,
                ':icone'          => $icone,
                ':permite_empate' => $permite_empate,
                ':tempo'          => $tempo,
                ':id'             => $id
            ]);

            log_auditoria($pdo, 'ATUALIZAR_MODALIDADE', 'modalidades', $id, "Modalidade #{$id} alterada para {$nome}");

            set_flash_message('success', "Modalidade '{$nome}' atualizada com sucesso!");
            header('Location: ../views/modalidades.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao atualizar modalidade: ' . $e->getMessage());
            header('Location: ../views/modalidades.php');
            exit;
        }
        break;

    case 'excluir':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        try {
            $stmtDel = $pdo->prepare("DELETE FROM modalidades WHERE id = :id");
            $stmtDel->execute([':id' => $id]);

            log_auditoria($pdo, 'EXCLUIR_MODALIDADE', 'modalidades', $id, "Exclusão da modalidade #{$id}");

            set_flash_message('success', 'Modalidade excluída com sucesso.');
            header('Location: ../views/modalidades.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao excluir modalidade: ' . $e->getMessage());
            header('Location: ../views/modalidades.php');
            exit;
        }
        break;

    default:
        header('Location: ../views/modalidades.php');
        exit;
}
