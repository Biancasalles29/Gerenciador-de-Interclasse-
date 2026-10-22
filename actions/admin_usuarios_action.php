<?php
/**
 * Controller Administrativo de Usuários
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

require_admin('../index.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'criar':
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'visitante';
        $telefone = trim($_POST['telefone'] ?? '');

        if (empty($nome) || empty($email) || empty($senha)) {
            set_flash_message('error', 'Nome, e-mail e senha são obrigatórios.');
            header('Location: ../views/admin_usuarios.php');
            exit;
        }

        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmtCheck->execute([':email' => $email]);
            if ($stmtCheck->fetch()) {
                set_flash_message('error', 'Este e-mail já está em uso.');
                header('Location: ../views/admin_usuarios.php');
                exit;
            }

            $senhaHash = hash_password($senha);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, perfil, telefone, ativo)
                VALUES (:nome, :email, :senha, :perfil, :telefone, 1)
            ");

            $stmt->execute([
                ':nome'     => $nome,
                ':email'    => $email,
                ':senha'    => $senhaHash,
                ':perfil'   => $perfil,
                ':telefone' => $telefone
            ]);

            $novoId = (int)$pdo->lastInsertId();
            log_auditoria($pdo, 'ADMIN_CRIAR_USUARIO', 'usuarios', $novoId, "Usuário {$nome} criado com perfil {$perfil}");

            set_flash_message('success', "Usuário '{$nome}' cadastrado com sucesso!");
            header('Location: ../views/admin_usuarios.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao salvar usuário: ' . $e->getMessage());
            header('Location: ../views/admin_usuarios.php');
            exit;
        }
        break;

    case 'editar':
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $perfil = $_POST['perfil'] ?? 'visitante';
        $telefone = trim($_POST['telefone'] ?? '');
        $senha = $_POST['senha'] ?? '';

        try {
            if (!empty($senha)) {
                $senhaHash = hash_password($senha);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nome = :nome, email = :email, perfil = :perfil, telefone = :telefone, senha = :senha
                    WHERE id = :id
                ");
                $params = [
                    ':nome'     => $nome,
                    ':email'    => $email,
                    ':perfil'   => $perfil,
                    ':telefone' => $telefone,
                    ':senha'    => $senhaHash,
                    ':id'       => $id
                ];
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nome = :nome, email = :email, perfil = :perfil, telefone = :telefone
                    WHERE id = :id
                ");
                $params = [
                    ':nome'     => $nome,
                    ':email'    => $email,
                    ':perfil'   => $perfil,
                    ':telefone' => $telefone,
                    ':id'       => $id
                ];
            }

            $stmt->execute($params);
            log_auditoria($pdo, 'ADMIN_ATUALIZAR_USUARIO', 'usuarios', $id, "Atualização cadastral do usuário #{$id} ({$nome})");

            set_flash_message('success', 'Dados do usuário atualizados com sucesso!');
            header('Location: ../views/admin_usuarios.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            header('Location: ../views/admin_usuarios.php');
            exit;
        }
        break;

    case 'toggle_status':
        $id = (int)($_GET['id'] ?? 0);
        if ($id === (int)$_SESSION['usuario_id']) {
            set_flash_message('error', 'Você não pode desativar seu próprio usuário.');
            header('Location: ../views/admin_usuarios.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_auditoria($pdo, 'ADMIN_TOGGLE_STATUS', 'usuarios', $id, "Alteração de status do usuário #{$id}");

            set_flash_message('success', 'Status do usuário alterado.');
            header('Location: ../views/admin_usuarios.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao alterar status: ' . $e->getMessage());
            header('Location: ../views/admin_usuarios.php');
            exit;
        }
        break;

    default:
        header('Location: ../views/admin_usuarios.php');
        exit;
}
