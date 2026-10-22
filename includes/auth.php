<?php
/**
 * Autenticação e Controle de Acesso (RBAC)
 * Requisitos:
 * - Senhas com hash bcrypt
 * - Níveis de acesso: admin, tecnico, visitante
 * - Proteção estrita de dados pessoais de participantes
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/logger.php';

/**
 * Cria um hash seguro usando bcrypt
 * @param string $senha
 * @return string
 */
function hash_password(string $senha): string {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verifica se a senha corresponde ao hash bcrypt
 * @param string $senha
 * @param string $hash
 * @return bool
 */
function verify_password(string $senha, string $hash): bool {
    return password_verify($senha, $hash);
}

/**
 * Verifica se o usuário atual está autenticado
 * @return bool
 */
function is_logged_in(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Retorna os dados do usuário autenticado ou null
 * @return array|null
 */
function get_logged_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['usuario_id'],
        'nome'     => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email'    => $_SESSION['usuario_email'] ?? '',
        'perfil'   => $_SESSION['usuario_perfil'] ?? 'visitante',
        'telefone' => $_SESSION['usuario_telefone'] ?? ''
    ];
}

/**
 * Verifica se o usuário autenticado é Administrador
 * @return bool
 */
function is_admin(): bool {
    return is_logged_in() && ($_SESSION['usuario_perfil'] ?? '') === 'admin';
}

/**
 * Verifica se o usuário autenticado é Técnico
 * @return bool
 */
function is_tecnico(): bool {
    return is_logged_in() && ($_SESSION['usuario_perfil'] ?? '') === 'tecnico';
}

/**
 * Verifica se o usuário tem permissão para gerenciar uma turma específica
 * Somente o técnico responsável pela sala ou o Administrador
 * @param int|null $turma_tecnico_id ID do técnico responsável pela sala
 * @return bool
 */
function can_manage_turma(?int $turma_tecnico_id): bool {
    if (!is_logged_in()) {
        return false;
    }
    if (is_admin()) {
        return true;
    }
    if (is_tecnico() && $turma_tecnico_id !== null) {
        return (int)$_SESSION['usuario_id'] === (int)$turma_tecnico_id;
    }
    return false;
}

/**
 * Proteção de dados sensíveis dos participantes (alunos)
 * Regra: Dados pessoais devem ser acessíveis somente aos usuários que têm permissão
 * @param int|null $turma_tecnico_id ID do técnico da turma do aluno
 * @return bool
 */
function can_view_sensitive_data(?int $turma_tecnico_id): bool {
    return can_manage_turma($turma_tecnico_id);
}

/**
 * Mascara dados confidenciais quando o usuário não tiver permissão
 * @param string|null $valor
 * @param bool $pode_visualizar
 * @return string
 */
function mask_sensitive_data(?string $valor, bool $pode_visualizar): string {
    if (empty($valor)) {
        return '-';
    }
    if ($pode_visualizar) {
        return htmlspecialchars($valor);
    }
    return '•••••••• (Restrito)';
}

/**
 * Exige login para continuar, redirecionando se necessário
 * @param string $redirectUrl
 */
function require_login(string $redirectUrl = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Você precisa estar conectado para acessar esta página.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Exige perfil Administrador para continuar
 */
function require_admin(string $redirectUrl = 'index.php'): void {
    require_login();
    if (!is_admin()) {
        set_flash_message('error', 'Acesso negado: funcionalidade restrita a Administradores.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Exige perfil Técnico ou Administrador para continuar
 */
function require_tecnico_or_admin(string $redirectUrl = 'index.php'): void {
    require_login();
    if (!is_admin() && !is_tecnico()) {
        set_flash_message('error', 'Acesso negado: apenas Técnicos de Salas e Administradores podem realizar esta operação.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Obtém os dados da Edição ativa do Interclasse
 * Permite alternar edições sem que os dados de uma interfiram em outra
 * @param PDO $pdo
 * @return array
 */
function get_active_edicao(PDO $pdo): array {
    // 1. Se houver edição selecionada na sessão, verificar se ela existe
    if (isset($_SESSION['edicao_ativa_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM edicoes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['edicao_ativa_id']]);
        $edicao = $stmt->fetch();
        if ($edicao) {
            return $edicao;
        }
    }

    // 2. Se não houver, busca a edição marcada como ativa = 1
    $stmt = $pdo->query("SELECT * FROM edicoes WHERE ativa = 1 LIMIT 1");
    $edicao = $stmt->fetch();
    if ($edicao) {
        $_SESSION['edicao_ativa_id'] = $edicao['id'];
        return $edicao;
    }

    // 3. Fallback para a edição mais recente cadastrada
    $stmt = $pdo->query("SELECT * FROM edicoes ORDER BY ano DESC, id DESC LIMIT 1");
    $edicao = $stmt->fetch();
    if ($edicao) {
        $_SESSION['edicao_ativa_id'] = $edicao['id'];
        return $edicao;
    }

    // Caso extremo: nenhuma edição cadastrada
    return [
        'id'     => 1,
        'nome'   => 'Interclasse Geral',
        'ano'    => (int)date('Y'),
        'status' => 'em_andamento',
        'ativa'  => 1
    ];
}
