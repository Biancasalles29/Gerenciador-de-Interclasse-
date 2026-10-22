<?php
/**
 * Controller de Turmas / Salas
 * Regras:
 * - Somente os técnicos e administradores podem cadastrar suas turmas
 * - Turmas são vinculadas à edição ativa
 * - Registro rigoroso de logs de auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_tecnico_or_admin('../turmas.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$edicaoAtiva = get_active_edicao($pdo);

switch ($action) {
    case 'criar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../turmas.php');
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        $sigla = trim($_POST['sigla'] ?? '');
        $curso = trim($_POST['curso'] ?? '');
        $periodo = $_POST['periodo'] ?? 'Manhã';
        $cor = $_POST['cor_uniforme'] ?? '#2563EB';

        // Definir técnico responsável
        if (is_admin()) {
            $tecnico_id = !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : $_SESSION['usuario_id'];
        } else {
            // Se for técnico, ele é obrigatoriamente o responsável pela sua sala
            $tecnico_id = (int)$_SESSION['usuario_id'];
        }

        if (empty($nome) || empty($sigla)) {
            set_flash_message('error', 'Nome da turma e sigla são campos obrigatórios.');
            header('Location: ../turmas.php');
            exit;
        }

        try {
            // Verificar duplicidade de sigla na mesma edição
            $stmtCheck = $pdo->prepare("SELECT id FROM turmas WHERE edicao_id = :edicao_id AND sigla = :sigla");
            $stmtCheck->execute([
                ':edicao_id' => $edicaoAtiva['id'],
                ':sigla'     => $sigla
            ]);

            if ($stmtCheck->fetch()) {
                set_flash_message('warning', "Já existe uma turma cadastrada com a sigla '{$sigla}' nesta edição.");
                header('Location: ../turmas.php');
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO turmas (edicao_id, tecnico_id, nome, sigla, curso, periodo, cor_uniforme)
                VALUES (:edicao_id, :tecnico_id, :nome, :sigla, :curso, :periodo, :cor)
            ");

            $stmt->execute([
                ':edicao_id'  => $edicaoAtiva['id'],
                ':tecnico_id' => $tecnico_id,
                ':nome'       => $nome,
                ':sigla'      => $sigla,
                ':curso'      => $curso,
                ':periodo'    => $periodo,
                ':cor'        => $cor
            ]);

            $turmaId = (int)$pdo->lastInsertId();

            log_auditoria($pdo, 'CRIAR_TURMA', 'turmas', $turmaId, [
                'nome'       => $nome,
                'sigla'      => $sigla,
                'tecnico_id' => $tecnico_id,
                'edicao_id'  => $edicaoAtiva['id']
            ]);

            set_flash_message('success', "Turma {$sigla} cadastrada com sucesso!");
            header("Location: ../turma_detalhes.php?id={$turmaId}");
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao salvar turma: ' . $e->getMessage());
            header('Location: ../turmas.php');
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
        $sigla = trim($_POST['sigla'] ?? '');
        $curso = trim($_POST['curso'] ?? '');
        $periodo = $_POST['periodo'] ?? 'Manhã';
        $cor = $_POST['cor_uniforme'] ?? '#2563EB';

        // Buscar turma existente
        $stmtFind = $pdo->prepare("SELECT * FROM turmas WHERE id = :id");
        $stmtFind->execute([':id' => $id]);
        $turma = $stmtFind->fetch();

        if (!$turma) {
            set_flash_message('error', 'Turma não encontrada.');
            header('Location: ../turmas.php');
            exit;
        }

        // Permissão: Admin ou o próprio técnico da sala
        if (!can_manage_turma($turma['tecnico_id'])) {
            set_flash_message('error', 'Você não tem permissão para editar esta turma.');
            header('Location: ../turmas.php');
            exit;
        }

        $tecnico_id = $turma['tecnico_id'];
        if (is_admin() && isset($_POST['tecnico_id'])) {
            $tecnico_id = !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE turmas 
                SET nome = :nome, sigla = :sigla, curso = :curso, periodo = :periodo, cor_uniforme = :cor, tecnico_id = :tecnico_id
                WHERE id = :id
            ");

            $stmt->execute([
                ':nome'       => $nome,
                ':sigla'      => $sigla,
                ':curso'      => $curso,
                ':periodo'    => $periodo,
                ':cor'        => $cor,
                ':tecnico_id' => $tecnico_id,
                ':id'         => $id
            ]);

            log_auditoria($pdo, 'ATUALIZAR_TURMA', 'turmas', $id, [
                'alterado_por' => $_SESSION['usuario_nome'],
                'nome_novo'    => $nome,
                'sigla_nova'   => $sigla
            ]);

            set_flash_message('success', "Turma {$sigla} atualizada com sucesso!");
            header("Location: ../turma_detalhes.php?id={$id}");
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao atualizar turma: ' . $e->getMessage());
            header("Location: ../turma_detalhes.php?id={$id}");
            exit;
        }
        break;

    case 'excluir':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        $stmtFind = $pdo->prepare("SELECT * FROM turmas WHERE id = :id");
        $stmtFind->execute([':id' => $id]);
        $turma = $stmtFind->fetch();

        if (!$turma) {
            set_flash_message('error', 'Turma não encontrada.');
            header('Location: ../turmas.php');
            exit;
        }

        if (!can_manage_turma($turma['tecnico_id'])) {
            set_flash_message('error', 'Você não tem permissão para excluir esta turma.');
            header('Location: ../turmas.php');
            exit;
        }

        try {
            // Verificar se turma já jogou alguma partida para prevenir perda acidental de dados
            $stmtPartidas = $pdo->prepare("SELECT COUNT(*) FROM partidas WHERE turma_mandante_id = :id OR turma_visitante_id = :id");
            $stmtPartidas->execute([':id' => $id]);
            if ($stmtPartidas->fetchColumn() > 0) {
                set_flash_message('error', 'Não é possível excluir esta turma pois ela já possui partidas associadas no cronograma. Cancele ou remova as partidas primeiro.');
                header("Location: ../turma_detalhes.php?id={$id}");
                exit;
            }

            $stmtDel = $pdo->prepare("DELETE FROM turmas WHERE id = :id");
            $stmtDel->execute([':id' => $id]);

            log_auditoria($pdo, 'EXCLUIR_TURMA', 'turmas', $id, "Exclusão da turma {$turma['sigla']} ({$turma['nome']})");

            set_flash_message('success', "Turma {$turma['sigla']} removida com sucesso.");
            header('Location: ../turmas.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao excluir turma: ' . $e->getMessage());
            header("Location: ../turma_detalhes.php?id={$id}");
            exit;
        }
        break;

    default:
        header('Location: ../turmas.php');
        exit;
}
