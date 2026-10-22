<?php
/**
 * Controller de Súmula e Estatísticas
 * Gerencia Artilharia (gols/pontos), Assistências, Cartões Amarelos e Vermelhos
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_admin('../jogos.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'adicionar_evento':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../jogos.php');
            exit;
        }

        $partida_id = (int)($_POST['partida_id'] ?? 0);
        $turma_id   = (int)($_POST['turma_id'] ?? 0);
        $aluno_id   = (int)($_POST['aluno_id'] ?? 0);
        $tipo       = $_POST['tipo'] ?? '';
        $minuto     = !empty($_POST['minuto']) ? (int)$_POST['minuto'] : null;
        $observacao = trim($_POST['observacao'] ?? '');

        $tiposValidos = ['gol_ponto', 'assistencia', 'cartao_amarelo', 'cartao_vermelho'];
        if (!in_array($tipo, $tiposValidos) || $partida_id <= 0 || $aluno_id <= 0 || $turma_id <= 0) {
            set_flash_message('error', 'Dados incompletos para registrar o lance na súmula.');
            header("Location: ../partida_detalhes.php?id={$partida_id}");
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Inserir evento na súmula
            $stmt = $pdo->prepare("
                INSERT INTO sumula_eventos (partida_id, turma_id, aluno_id, tipo, minuto, observacao)
                VALUES (:partida_id, :turma_id, :aluno_id, :tipo, :minuto, :observacao)
            ");

            $stmt->execute([
                ':partida_id' => $partida_id,
                ':turma_id'   => $turma_id,
                ':aluno_id'   => $aluno_id,
                ':tipo'       => $tipo,
                ':minuto'     => $minuto,
                ':observacao' => $observacao
            ]);

            // Se for gol_ponto, atualizar placar da partida automaticamente
            if ($tipo === 'gol_ponto') {
                $stmtPartida = $pdo->prepare("SELECT turma_mandante_id, turma_visitante_id FROM partidas WHERE id = :id");
                $stmtPartida->execute([':id' => $partida_id]);
                $partida = $stmtPartida->fetch();

                if ($partida) {
                    // Contar gols de cada turma
                    $stmtGolsM = $pdo->prepare("SELECT COUNT(*) FROM sumula_eventos WHERE partida_id = :id AND turma_id = :m AND tipo = 'gol_ponto'");
                    $stmtGolsM->execute([':id' => $partida_id, ':m' => $partida['turma_mandante_id']]);
                    $golsM = (int)$stmtGolsM->fetchColumn();

                    $stmtGolsV = $pdo->prepare("SELECT COUNT(*) FROM sumula_eventos WHERE partida_id = :id AND turma_id = :v AND tipo = 'gol_ponto'");
                    $stmtGolsV->execute([':id' => $partida_id, ':v' => $partida['turma_visitante_id']]);
                    $golsV = (int)$stmtGolsV->fetchColumn();

                    $stmtPlacar = $pdo->prepare("UPDATE partidas SET placar_mandante = :gm, placar_visitante = :gv WHERE id = :id");
                    $stmtPlacar->execute([':gm' => $golsM, ':gv' => $golsV, ':id' => $partida_id]);
                }
            }

            // Buscar nome do aluno para log legível
            $stmtAluno = $pdo->prepare("SELECT nome FROM alunos WHERE id = :id");
            $stmtAluno->execute([':id' => $aluno_id]);
            $alunoNome = $stmtAluno->fetchColumn() ?: 'Atleta';

            log_auditoria($pdo, 'REGISTRAR_SUMULA', 'sumula_eventos', $partida_id, [
                'evento' => $tipo,
                'atleta' => $alunoNome,
                'minuto' => $minuto
            ]);

            $pdo->commit();

            $nomeTipo = match($tipo) {
                'gol_ponto'       => 'Gol / Ponto',
                'assistencia'     => 'Assistência',
                'cartao_amarelo'  => 'Cartão Amarelo',
                'cartao_vermelho' => 'Cartão Vermelho',
                default           => 'Lance'
            };

            set_flash_message('success', "{$nomeTipo} registrado para {$alunoNome} com sucesso!");
            header("Location: ../partida_detalhes.php?id={$partida_id}");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Erro ao registrar evento na súmula: ' . $e->getMessage());
            header("Location: ../partida_detalhes.php?id={$partida_id}");
            exit;
        }
        break;

    case 'remover_evento':
        $evento_id = (int)($_POST['evento_id'] ?? $_GET['evento_id'] ?? 0);
        $partida_id = (int)($_POST['partida_id'] ?? $_GET['partida_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $stmtDel = $pdo->prepare("DELETE FROM sumula_eventos WHERE id = :id");
            $stmtDel->execute([':id' => $evento_id]);

            // Se ainda temos partida_id, recalcular gols
            if ($partida_id > 0) {
                $stmtPartida = $pdo->prepare("SELECT turma_mandante_id, turma_visitante_id FROM partidas WHERE id = :id");
                $stmtPartida->execute([':id' => $partida_id]);
                $partida = $stmtPartida->fetch();

                if ($partida) {
                    $stmtGolsM = $pdo->prepare("SELECT COUNT(*) FROM sumula_eventos WHERE partida_id = :id AND turma_id = :m AND tipo = 'gol_ponto'");
                    $stmtGolsM->execute([':id' => $partida_id, ':m' => $partida['turma_mandante_id']]);
                    $golsM = (int)$stmtGolsM->fetchColumn();

                    $stmtGolsV = $pdo->prepare("SELECT COUNT(*) FROM sumula_eventos WHERE partida_id = :id AND turma_id = :v AND tipo = 'gol_ponto'");
                    $stmtGolsV->execute([':id' => $partida_id, ':v' => $partida['turma_visitante_id']]);
                    $golsV = (int)$stmtGolsV->fetchColumn();

                    $stmtPlacar = $pdo->prepare("UPDATE partidas SET placar_mandante = :gm, placar_visitante = :gv WHERE id = :id");
                    $stmtPlacar->execute([':gm' => $golsM, ':gv' => $golsV, ':id' => $partida_id]);
                }
            }

            log_auditoria($pdo, 'REMOVER_SUMULA', 'sumula_eventos', $evento_id, "Remoção de lance da súmula #{$evento_id}");

            $pdo->commit();

            set_flash_message('success', 'Lance removido da súmula com sucesso.');
            header("Location: ../partida_detalhes.php?id={$partida_id}");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Erro ao remover lance da súmula: ' . $e->getMessage());
            header("Location: ../partida_detalhes.php?id={$partida_id}");
            exit;
        }
        break;

    default:
        header('Location: ../jogos.php');
        exit;
}
