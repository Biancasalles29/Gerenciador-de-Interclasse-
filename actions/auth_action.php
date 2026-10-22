<?php
/**
 * Controller de Autenticação (Login, Logout e Registro)
 * Tratamento de senhas com bcrypt e logs de auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/logger.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../login.php');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            set_flash_message('error', 'Preencha o e-mail e a senha.');
            header('Location: ../login.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1");
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario && verify_password($senha, $usuario['senha'])) {
                // Login com sucesso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];
                $_SESSION['usuario_telefone'] = $usuario['telefone'];

                log_auditoria($pdo, 'LOGIN', 'usuarios', $usuario['id'], 'Usuário autenticou-se com sucesso');

                set_flash_message('success', "Bem-vindo(a) de volta, {$usuario['nome']}!");
                
                // Redirecionar
                $redirect = $_POST['redirect_to'] ?? '../index.php';
                header("Location: {$redirect}");
                exit;
            } else {
                // Tentativa inválida
                log_auditoria($pdo, 'LOGIN_FALHA', 'usuarios', null, "Tentativa de login falha para o e-mail: {$email}");
                set_flash_message('error', 'E-mail ou senha incorretos.');
                header('Location: ../login.php');
                exit;
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Erro interno ao realizar autenticação.');
            header('Location: ../login.php');
            exit;
        }
        break;

    case 'logout':
        if (is_logged_in()) {
            log_auditoria($pdo, 'LOGOUT', 'usuarios', $_SESSION['usuario_id'], 'Usuário encerrou a sessão');
        }
        // Limpar variáveis de sessão do usuário
        unset($_SESSION['usuario_id']);
        unset($_SESSION['usuario_nome']);
        unset($_SESSION['usuario_email']);
        unset($_SESSION['usuario_perfil']);
        unset($_SESSION['usuario_telefone']);

        set_flash_message('info', 'Você saiu da sua conta.');
        header('Location: ../login.php');
        exit;

    case 'registro':
        // Cadastro simples para técnicos e participantes
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../login.php');
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'visitante';
        $telefone = trim($_POST['telefone'] ?? '');

        // Apenas 'tecnico' ou 'visitante' no autoregistro (admin só pode ser criado por outro admin)
        if (!in_array($perfil, ['tecnico', 'visitante'])) {
            $perfil = 'visitante';
        }

        if (empty($nome) || empty($email) || empty($senha)) {
            set_flash_message('error', 'Preencha todos os campos obrigatórios.');
            header('Location: ../login.php#registro');
            exit;
        }

        if (strlen($senha) < 6) {
            set_flash_message('error', 'A senha deve conter no mínimo 6 caracteres.');
            header('Location: ../login.php#registro');
            exit;
        }

        try {
            // Checar duplicidade de e-mail
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                set_flash_message('error', 'Este e-mail já está cadastrado.');
                header('Location: ../login.php#registro');
                exit;
            }

            // Inserir com hash bcrypt seguro
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

            log_auditoria($pdo, 'REGISTRO_USUARIO', 'usuarios', $novoId, "Novo cadastro realizado com perfil: {$perfil}");

            // Autologin
            $_SESSION['usuario_id'] = $novoId;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_perfil'] = $perfil;
            $_SESSION['usuario_telefone'] = $telefone;

            set_flash_message('success', 'Cadastro realizado com sucesso! Seja bem-vindo(a).');
            header('Location: ../index.php');
            exit;

        } catch (PDOException $e) {
            set_flash_message('error', 'Erro ao processar cadastro no banco de dados.');
            header('Location: ../login.php#registro');
            exit;
        }
        break;

    default:
        header('Location: ../index.php');
        exit;
}
