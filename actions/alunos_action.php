<?php
/**
 * Controller de Alunos / Atletas
 * Regras:
 * - Técnicos cadastram os alunos de suas devidas salas
 * - Proteção estrita de dados pessoais (RG/RA, contatos)
 * - Registro em auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_tecnico_or_admin('../turmas.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'criar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../turmas.php');
            exit;
        }

        $turma_id = (int)($_POST['turma_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $numero_camisa = !empty($_POST['numero_camisa']) ? (int)$_POST['numero_camisa'] : null;
        $posicao = trim($_POST['posicao'] ?? '');
        $rg_ra = trim($_POST['rg_ra'] ?? '');
        $contato_emergencia = trim($_POST['contato_emergencia'] ?? '');

        if ($turma_id <= 0 || empty($nome)) {
            set_flash_message('error', 'O nome do atleta e a turma são obrigatórios.');
            header("Location: ../turma_detalhes.php?id={$turma_id}");
            exit;
        }

        // Buscar dados da turma para validar técnico responsável
        $stmtTurma = $pdo->prepare("SELECT * FROM turmas WHERE id = :id");
        $stmtTurma->execute([':id' => $turma_id]);
        $turma = $stmtTurma->fetch();

        if (!$turma) {
            set_flash_message('error', 'Turma inexistente.');
            header('Location: ../turmas.php');
            exit;
        }

        if (!can_manage_turma($turma['tecnico_id'])) {
            set_flash_message('error', 'Você só pode cadastrar alunos na sua própria turma.');
            header("Location: ../turma_detalhes.php?id={$turma_id}");
            exit;
        }

        try {
            // Se informou número de camisa, verificar se já existe na mesma turma
            if ($numero_camisa !== null) {
                $stmtCamisa = $pdo->prepare("SELECT id, nome FROM alunos WHERE turma_id = :turma_id AND numero_camisa = :num AND ativo = 1");
                $stmtCamisa->execute([':turma_id' => $turma_id, ':num' => $numero_camisa]);
                $camisaExistente = $stmtCamisa->fetch();
                if ($camisaExistente) {
                    set_flash_message('warning', "A camisa nº {$numero_camisa} já está atribuída ao aluno {$camisaExistente['nome']}.");
                    header("Location: ../turma_detalhes.php?id={$turma_id}");
                    exit;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO alunos (turma_id, nome, numero_camisa, posicao, rg_ra, contato_emergencia, ativo)
                VALUES (:turma_id, :nome, :numero_camisa, :posicao, :rg_ra, :contato_emergencia, 1)
            ");

            $stmt->execute([
                ':turma_id'           => $turma_id,
                ':nome'               => $nome,
                ':numero_camisa'      => $numero_camisa,
                ':posicao'            => $posicao,
                ':rg_ra'              => $rg_ra,
                ':contato_emergencia' => $contato_emergencia
            ]);

            $alunoId = (int)$pdo->lastInsertId();

            log_auditoria($pdo, 'CADASTRAR_ALUNO', 'alunos', $alunoId, [
                'nome'     => $nome,
                'camisa'   => $numero_camisa,
                'turma'    => $turma['sigla'],
                'turma_id' => $turma_id
            ]);

            set_flash_message('success', "Atleta {$nome} cadastrado(a) com sucesso na turma {$turma['sigla']}!");
            header("Location: ../turma_detalhes.php?id={$turma_id}");
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao cadastrar atleta: ' . $e->getMessage());
            header("Location: ../turma_detalhes.php?id={$turma_id}");
            exit;
        }
        break;

    case 'editar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../turmas.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $numero_camisa = !empty($_POST['numero_camisa']) ? (int)$_POST['numero_camisa'] : null;
        $posicao = trim($_POST['posicao'] ?? '');
        $rg_ra = trim($_POST['rg_ra'] ?? '');
        $contato_emergencia = trim($_POST['contato_emergencia'] ?? '');

        // Buscar aluno e turma vinculada
        $stmtAluno = $pdo->prepare("
            SELECT a.*, t.tecnico_id, t.sigla 
            FROM alunos a 
            JOIN turmas t ON t.id = a.turma_id 
            WHERE a.id = :id
        ");
        $stmtAluno->execute([':id' => $id]);
        $aluno = $stmtAluno->fetch();

        if (!$aluno) {
            set_flash_message('error', 'Atleta não encontrado.');
            header('Location: ../turmas.php');
            exit;
        }

        if (!can_manage_turma($aluno['tecnico_id'])) {
            set_flash_message('error', 'Você não tem permissão para editar os dados deste aluno.');
            header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
            exit;
        }

        try {
            // Verificar colisão de número de camisa
            if ($numero_camisa !== null) {
                $stmtCamisa = $pdo->prepare("
                    SELECT id, nome FROM alunos 
                    WHERE turma_id = :turma_id AND numero_camisa = :num AND id != :id AND ativo = 1
                ");
                $stmtCamisa->execute([
                    ':turma_id' => $aluno['turma_id'],
                    ':num'      => $numero_camisa,
                    ':id'       => $id
                ]);
                $camisaExistente = $stmtCamisa->fetch();
                if ($camisaExistente) {
                    set_flash_message('warning', "A camisa nº {$numero_camisa} já pertence a outro aluno ({$camisaExistente['nome']}).");
                    header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
                    exit;
                }
            }

            $stmt = $pdo->prepare("
                UPDATE alunos 
                SET nome = :nome, numero_camisa = :numero_camisa, posicao = :posicao, rg_ra = :rg_ra, contato_emergencia = :contato_emergencia
                WHERE id = :id
            ");

            $stmt->execute([
                ':nome'               => $nome,
                ':numero_camisa'      => $numero_camisa,
                ':posicao'            => $posicao,
                ':rg_ra'              => $rg_ra,
                ':contato_emergencia' => $contato_emergencia,
                ':id'                 => $id
            ]);

            log_auditoria($pdo, 'ATUALIZAR_ALUNO', 'alunos', $id, [
                'aluno'    => $nome,
                'turma_id' => $aluno['turma_id']
            ]);

            set_flash_message('success', "Dados do atleta {$nome} atualizados com sucesso!");
            header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao atualizar atleta: ' . $e->getMessage());
            header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
            exit;
        }
        break;

    case 'excluir':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        $stmtAluno = $pdo->prepare("
            SELECT a.*, t.tecnico_id 
            FROM alunos a 
            JOIN turmas t ON t.id = a.turma_id 
            WHERE a.id = :id
        ");
        $stmtAluno->execute([':id' => $id]);
        $aluno = $stmtAluno->fetch();

        if (!$aluno) {
            set_flash_message('error', 'Atleta não encontrado.');
            header('Location: ../turmas.php');
            exit;
        }

        if (!can_manage_turma($aluno['tecnico_id'])) {
            set_flash_message('error', 'Você não tem permissão para excluir este atleta.');
            header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
            exit;
        }

        try {
            $stmtDel = $pdo->prepare("DELETE FROM alunos WHERE id = :id");
            $stmtDel->execute([':id' => $id]);

            log_auditoria($pdo, 'EXCLUIR_ALUNO', 'alunos', $id, "Remoção do atleta {$aluno['nome']} da turma ID {$aluno['turma_id']}");

            set_flash_message('success', "Atleta {$aluno['nome']} removido com sucesso.");
            header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao excluir atleta: ' . $e->getMessage());
            header("Location: ../turma_detalhes.php?id={$aluno['turma_id']}");
            exit;
        }
        break;

    default:
        header('Location: ../turmas.php');
        exit;
}
