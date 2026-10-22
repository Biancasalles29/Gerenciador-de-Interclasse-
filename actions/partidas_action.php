<?php
/**
 * Controller de Partidas e Cronograma
 * 
 * Regras CORE implementadas:
 * 1. Todas as partidas devem possuir horário, local e data
 * 2. As equipes devem estar vinculadas a uma turma
 * 3. Uma equipe pode jogar somente UMA partida por horário (validação de choque)
 * 4. Alterações no cronograma DEVEM ser comunicadas automaticamente dentro do site
 * 5. Registro completo em logs de auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_admin('../jogos.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$edicaoAtiva = get_active_edicao($pdo);

switch ($action) {
    case 'criar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../jogos.php');
            exit;
        }

        $modalidade_id      = (int)($_POST['modalidade_id'] ?? 0);
        $turma_mandante_id  = (int)($_POST['turma_mandante_id'] ?? 0);
        $turma_visitante_id = (int)($_POST['turma_visitante_id'] ?? 0);
        $data_jogo          = trim($_POST['data_jogo'] ?? '');
        $hora_jogo          = trim($_POST['hora_jogo'] ?? '');
        $local_jogo         = trim($_POST['local_jogo'] ?? '');
        $fase               = trim($_POST['fase'] ?? 'Fase de Grupos');
        $observacoes        = trim($_POST['observacoes'] ?? '');

        // Validação 1: Campos obrigatórios
        if (empty($modalidade_id) || empty($turma_mandante_id) || empty($turma_visitante_id) || empty($data_jogo) || empty($hora_jogo) || empty($local_jogo)) {
            set_flash_message('error', 'Preencha todos os campos obrigatórios (modalidade, turmas, data, horário e local).');
            header('Location: ../jogos.php');
            exit;
        }

        // Validação 2: Equipes distintas
        if ($turma_mandante_id === $turma_visitante_id) {
            set_flash_message('error', 'A turma mandante e visitante não podem ser a mesma.');
            header('Location: ../jogos.php');
            exit;
        }

        // Padronizar formato de hora para HH:MM:00
        if (strlen($hora_jogo) === 5) {
            $hora_jogo .= ':00';
        }

        try {
            // REGRA CORE 3: Uma equipe pode jogar somente uma partida por horário
            $stmtConflito = $pdo->prepare("
                SELECT p.id, t1.sigla AS mandante_sigla, t2.sigla AS visitante_sigla 
                FROM partidas p
                JOIN turmas t1 ON t1.id = p.turma_mandante_id
                JOIN turmas t2 ON t2.id = p.turma_visitante_id
                WHERE p.edicao_id = :edicao_id 
                  AND p.data_jogo = :data_jogo 
                  AND p.hora_jogo = :hora_jogo 
                  AND p.status != 'cancelado'
                  AND (
                    p.turma_mandante_id IN (:t1, :t2) OR 
                    p.turma_visitante_id IN (:t1, :t2)
                  )
                LIMIT 1
            ");

            $stmtConflito->execute([
                ':edicao_id'  => $edicaoAtiva['id'],
                ':data_jogo'  => $data_jogo,
                ':hora_jogo'  => $hora_jogo,
                ':t1'         => $turma_mandante_id,
                ':t2'         => $turma_visitante_id
            ]);

            $conflito = $stmtConflito->fetch();
            if ($conflito) {
                set_flash_message('error', "Choque de Horário: Uma das equipes já possui o jogo (#{$conflito['id']}: {$conflito['mandante_sigla']} x {$conflito['visitante_sigla']}) agendado para o mesmo dia ({$data_jogo}) às {$hora_jogo}.");
                header('Location: ../jogos.php');
                exit;
            }

            // Inserir partida
            $stmt = $pdo->prepare("
                INSERT INTO partidas (edicao_id, modalidade_id, turma_mandante_id, turma_visitante_id, data_jogo, hora_jogo, local_jogo, fase, status, observacoes)
                VALUES (:edicao_id, :modalidade_id, :mandante, :visitante, :data_jogo, :hora_jogo, :local_jogo, :fase, 'agendado', :observacoes)
            ");

            $stmt->execute([
                ':edicao_id'      => $edicaoAtiva['id'],
                ':modalidade_id'  => $modalidade_id,
                ':mandante'       => $turma_mandante_id,
                ':visitante'      => $turma_visitante_id,
                ':data_jogo'      => $data_jogo,
                ':hora_jogo'      => $hora_jogo,
                ':local_jogo'     => $local_jogo,
                ':fase'           => $fase,
                ':observacoes'    => $observacoes
            ]);

            $partidaId = (int)$pdo->lastInsertId();

            log_auditoria($pdo, 'AGENDAR_PARTIDA', 'partidas', $partidaId, [
                'mandante_id'   => $turma_mandante_id,
                'visitante_id'  => $turma_visitante_id,
                'data'          => $data_jogo,
                'hora'          => $hora_jogo,
                'local'         => $local_jogo
            ]);

            set_flash_message('success', 'Partida agendada com sucesso!');
            header('Location: ../jogos.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao agendar partida: ' . $e->getMessage());
            header('Location: ../jogos.php');
            exit;
        }
        break;

    case 'editar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../jogos.php');
            exit;
        }

        $id                 = (int)($_POST['id'] ?? 0);
        $modalidade_id      = (int)($_POST['modalidade_id'] ?? 0);
        $turma_mandante_id  = (int)($_POST['turma_mandante_id'] ?? 0);
        $turma_visitante_id = (int)($_POST['turma_visitante_id'] ?? 0);
        $data_jogo          = trim($_POST['data_jogo'] ?? '');
        $hora_jogo          = trim($_POST['hora_jogo'] ?? '');
        $local_jogo         = trim($_POST['local_jogo'] ?? '');
        $fase               = trim($_POST['fase'] ?? 'Fase de Grupos');
        $status             = $_POST['status'] ?? 'agendado';
        $observacoes        = trim($_POST['observacoes'] ?? '');

        if (strlen($hora_jogo) === 5) {
            $hora_jogo .= ':00';
        }

        // Buscar dados atuais da partida
        $stmtAtual = $pdo->prepare("
            SELECT p.*, tm.sigla AS m_sigla, tv.sigla AS v_sigla, m.nome AS modalidade_nome
            FROM partidas p
            JOIN turmas tm ON tm.id = p.turma_mandante_id
            JOIN turmas tv ON tv.id = p.turma_visitante_id
            JOIN modalidades m ON m.id = p.modalidade_id
            WHERE p.id = :id
        ");
        $stmtAtual->execute([':id' => $id]);
        $partidaOriginal = $stmtAtual->fetch();

        if (!$partidaOriginal) {
            set_flash_message('error', 'Partida não encontrada.');
            header('Location: ../jogos.php');
            exit;
        }

        // REGRA CORE 3: Choque de Horário (exceto a própria partida)
        $stmtConflito = $pdo->prepare("
            SELECT p.id, t1.sigla AS mandante_sigla, t2.sigla AS visitante_sigla 
            FROM partidas p
            JOIN turmas t1 ON t1.id = p.turma_mandante_id
            JOIN turmas t2 ON t2.id = p.turma_visitante_id
            WHERE p.edicao_id = :edicao_id 
              AND p.data_jogo = :data_jogo 
              AND p.hora_jogo = :hora_jogo 
              AND p.id != :partida_id
              AND p.status != 'cancelado'
              AND (
                p.turma_mandante_id IN (:t1, :t2) OR 
                p.turma_visitante_id IN (:t1, :t2)
              )
            LIMIT 1
        ");

        $stmtConflito->execute([
            ':edicao_id'   => $edicaoAtiva['id'],
            ':data_jogo'   => $data_jogo,
            ':hora_jogo'   => $hora_jogo,
            ':partida_id'  => $id,
            ':t1'          => $turma_mandante_id,
            ':t2'          => $turma_visitante_id
        ]);

        $conflito = $stmtConflito->fetch();
        if ($conflito) {
            set_flash_message('error', "Choque de Horário: Uma das equipes já possui outro jogo agendado para o mesmo dia e horário.");
            header('Location: ../jogos.php');
            exit;
        }

        try {
            // Verificar se houve alteração no cronograma (data, hora ou local)
            $mudouData  = ($partidaOriginal['data_jogo'] !== $data_jogo);
            $mudouHora  = (substr($partidaOriginal['hora_jogo'], 0, 5) !== substr($hora_jogo, 0, 5));
            $mudouLocal = ($partidaOriginal['local_jogo'] !== $local_jogo);

            $stmtUpdate = $pdo->prepare("
                UPDATE partidas 
                SET modalidade_id = :modalidade_id, turma_mandante_id = :mandante, turma_visitante_id = :visitante,
                    data_jogo = :data_jogo, hora_jogo = :hora_jogo, local_jogo = :local_jogo, fase = :fase,
                    status = :status, observacoes = :observacoes
                WHERE id = :id
            ");

            $stmtUpdate->execute([
                ':modalidade_id' => $modalidade_id,
                ':mandante'      => $turma_mandante_id,
                ':visitante'     => $turma_visitante_id,
                ':data_jogo'     => $data_jogo,
                ':hora_jogo'     => $hora_jogo,
                ':local_jogo'    => $local_jogo,
                ':fase'          => $fase,
                ':status'        => $status,
                ':observacoes'   => $observacoes,
                ':id'            => $id
            ]);

            // REGRA CORE 4: Alterações no cronograma DEVEM ser comunicadas dentro do site
            if ($mudouData || $mudouHora || $mudouLocal) {
                $dataFormatada = date('d/m/Y', strtotime($data_jogo));
                $horaFormatada = substr($hora_jogo, 0, 5);

                $tituloComunicado = "Alteração de Cronograma: {$partidaOriginal['m_sigla']} vs {$partidaOriginal['v_sigla']}";
                $conteudoComunicado = "Aviso Oficial: Houve alteração no cronograma da partida de {$partidaOriginal['modalidade_nome']}. ";
                $conteudoComunicado .= "Novo agendamento: Data: {$dataFormatada} às {$horaFormatada} no local: {$local_jogo}.";

                $stmtAviso = $pdo->prepare("
                    INSERT INTO comunicados (edicao_id, usuario_id, titulo, conteudo, tipo, partida_id)
                    VALUES (:edicao_id, :usuario_id, :titulo, :conteudo, 'cronograma', :partida_id)
                ");

                $stmtAviso->execute([
                    ':edicao_id'   => $edicaoAtiva['id'],
                    ':usuario_id'  => $_SESSION['usuario_id'] ?? null,
                    ':titulo'      => $tituloComunicado,
                    ':conteudo'    => $conteudoComunicado,
                    ':partida_id'  => $id
                ]);

                log_auditoria($pdo, 'ALTERAR_CRONOGRAMA', 'partidas', $id, [
                    'partida'   => "{$partidaOriginal['m_sigla']} x {$partidaOriginal['v_sigla']}",
                    'anterior'  => ['data' => $partidaOriginal['data_jogo'], 'hora' => $partidaOriginal['hora_jogo'], 'local' => $partidaOriginal['local_jogo']],
                    'novo'      => ['data' => $data_jogo, 'hora' => $hora_jogo, 'local' => $local_jogo]
                ]);

                set_flash_message('success', 'Partida atualizada e alteração comunicada publicamente no Mural de Avisos!');
            } else {
                log_auditoria($pdo, 'ATUALIZAR_PARTIDA', 'partidas', $id, "Atualização de dados gerais da partida #{$id}");
                set_flash_message('success', 'Partida atualizada com sucesso!');
            }

            header('Location: ../jogos.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao salvar alterações da partida: ' . $e->getMessage());
            header('Location: ../jogos.php');
            exit;
        }
        break;

    case 'excluir':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        try {
            $stmtDel = $pdo->prepare("DELETE FROM partidas WHERE id = :id");
            $stmtDel->execute([':id' => $id]);

            log_auditoria($pdo, 'EXCLUIR_PARTIDA', 'partidas', $id, "Exclusão da partida #{$id}");

            set_flash_message('success', 'Partida excluída com sucesso.');
            header('Location: ../jogos.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao excluir partida: ' . $e->getMessage());
            header('Location: ../jogos.php');
            exit;
        }
        break;

    default:
        header('Location: ../jogos.php');
        exit;
}
