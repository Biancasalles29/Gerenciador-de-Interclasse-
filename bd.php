<?php
/**
 * Conexão com o Banco de Dados via PDO
 * Gerenciador de Interclasse
 * 
 * Regras:
 * - Uso obrigatório de PDO com prepared statements
 * - Tratamento seguro de exceções sem expor credenciais
 * - Suporte primário a MySQL (padrão) com fallback automático para SQLite
 *   caso o serviço MySQL local não esteja em execução (permitindo emulação imediata).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// Carregar configurações
$configFile = APP_ROOT . '/config/database.php';
$config = file_exists($configFile) ? require $configFile : [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'database' => 'interclasse_db',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4'
];

$pdo = null;
$dbDriverUsed = 'mysql';

// 1. Tentar conectar ao MySQL
try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset'] ?? 'utf8mb4'
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    $dbDriverUsed = 'mysql';

} catch (PDOException $e) {
    // 2. Fallback de Emulação: Conectar via SQLite PDO para rodar imediatamente sem servidor MySQL
    try {
        $sqlitePath = APP_ROOT . '/database.sqlite';
        $isFirstRun = !file_exists($sqlitePath) || filesize($sqlitePath) < 100;

        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $dbDriverUsed = 'sqlite';

        // Mapear funções compatíveis do MySQL para SQLite
        $pdo->sqliteCreateFunction('NOW', function() {
            return date('Y-m-d H:i:s');
        });

        // Se for primeira execução no SQLite, inicializa o banco com o schema e seeds
        if ($isFirstRun) {
            inicializar_sqlite($pdo);
        }

    } catch (Exception $sqliteEx) {
        die("Erro ao inicializar conexão com o banco de dados: " . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Inicializa a base de dados SQLite com as tabelas e dados de teste equivalentes ao MySQL
 */
function inicializar_sqlite(PDO $pdo): void {
    $schema = "
    CREATE TABLE IF NOT EXISTS edicoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        ano INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'em_andamento',
        ativa INTEGER NOT NULL DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        perfil TEXT NOT NULL DEFAULT 'visitante',
        telefone TEXT,
        ativo INTEGER NOT NULL DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS modalidades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        edicao_id INTEGER NOT NULL,
        nome TEXT NOT NULL,
        icone TEXT DEFAULT 'fa-futbol',
        categoria TEXT DEFAULT 'Geral',
        permite_empate INTEGER NOT NULL DEFAULT 1,
        tempo_partida_minutos INTEGER DEFAULT 30,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS turmas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        edicao_id INTEGER NOT NULL,
        tecnico_id INTEGER,
        nome TEXT NOT NULL,
        sigla TEXT NOT NULL,
        curso TEXT,
        periodo TEXT DEFAULT 'Manhã',
        cor_uniforme TEXT DEFAULT '#2563EB',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS alunos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        turma_id INTEGER NOT NULL,
        nome TEXT NOT NULL,
        numero_camisa INTEGER,
        posicao TEXT,
        rg_ra TEXT,
        contato_emergencia TEXT,
        ativo INTEGER NOT NULL DEFAULT 1,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS partidas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        edicao_id INTEGER NOT NULL,
        modalidade_id INTEGER NOT NULL,
        turma_mandante_id INTEGER NOT NULL,
        turma_visitante_id INTEGER NOT NULL,
        data_jogo DATE NOT NULL,
        hora_jogo TIME NOT NULL,
        local_jogo TEXT NOT NULL,
        fase TEXT DEFAULT 'Fase de Grupos',
        placar_mandante INTEGER DEFAULT NULL,
        placar_visitante INTEGER DEFAULT NULL,
        status TEXT DEFAULT 'agendado',
        observacoes TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS sumula_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        partida_id INTEGER NOT NULL,
        turma_id INTEGER NOT NULL,
        aluno_id INTEGER NOT NULL,
        tipo TEXT NOT NULL,
        minuto INTEGER,
        observacao TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS comunicados (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        edicao_id INTEGER NOT NULL,
        usuario_id INTEGER,
        titulo TEXT NOT NULL,
        conteudo TEXT NOT NULL,
        tipo TEXT DEFAULT 'geral',
        partida_id INTEGER,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS logs_auditoria (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER,
        usuario_nome TEXT,
        acao TEXT NOT NULL,
        tabela_afetada TEXT,
        registro_id INTEGER,
        detalhes TEXT,
        ip_origem TEXT,
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $pdo->exec($schema);

    // Inserir Edição
    $pdo->exec("INSERT INTO edicoes (id, nome, ano, status, ativa) VALUES (1, 'Interclasse ETEC 2026 - Oficial', 2026, 'em_andamento', 1);");

    // Hashes bcrypt para senhas de teste
    $senhaAdmin = password_hash('admin123', PASSWORD_BCRYPT);
    $senhaTecnico = password_hash('tecnico123', PASSWORD_BCRYPT);

    $stmtUser = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha, perfil, telefone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtUser->execute([1, 'Administrador do Sistema', 'admin@etec.sp.gov.br', $senhaAdmin, 'admin', '(11) 98888-0001']);
    $stmtUser->execute([2, 'Prof. Carlos Eduardo (Técnico INFO)', 'tecnico.info@etec.sp.gov.br', $senhaTecnico, 'tecnico', '(11) 97777-0002']);
    $stmtUser->execute([3, 'Profª. Renata Silva (Técnica ADM)', 'tecnico.adm@etec.sp.gov.br', $senhaTecnico, 'tecnico', '(11) 97777-0003']);
    $stmtUser->execute([4, 'Prof. Marcos Rocha (Técnico EDIF)', 'tecnico.edif@etec.sp.gov.br', $senhaTecnico, 'tecnico', '(11) 97777-0004']);
    $stmtUser->execute([5, 'Aluno Torcedor / Visitante', 'visitante@etec.sp.gov.br', $senhaTecnico, 'visitante', '(11) 96666-0005']);

    // Modalidades
    $pdo->exec("
        INSERT INTO modalidades (id, edicao_id, nome, icone, categoria, permite_empate, tempo_partida_minutos) VALUES
        (1, 1, 'Futsal Masculino', 'fa-futbol', 'Geral', 1, 30),
        (2, 1, 'Futsal Feminino', 'fa-futbol', 'Geral', 1, 30),
        (3, 1, 'Voleibol Misto', 'fa-volleyball', 'Geral', 0, 40),
        (4, 1, 'Basquete 3x3', 'fa-basketball', 'Geral', 0, 20),
        (5, 1, 'Handebol Masculino', 'fa-hand-back-fist', 'Geral', 1, 30),
        (6, 1, 'Queimada Mista', 'fa-fire', 'Geral', 0, 25);
    ");

    // Turmas
    $pdo->exec("
        INSERT INTO turmas (id, edicao_id, tecnico_id, nome, sigla, curso, periodo, cor_uniforme) VALUES
        (1, 1, 2, '3º Ensino Médio - Informática', '3º INFO', 'Técnico em Desenvolvimento de Sistemas', 'Manhã', '#2563EB'),
        (2, 1, 3, '3º Ensino Médio - Administração', '3º ADM', 'Técnico em Administração', 'Manhã', '#DC2626'),
        (3, 1, 4, '2º Ensino Médio - Edificações', '2º EDIF', 'Técnico em Edificações', 'Tarde', '#16A34A'),
        (4, 1, 2, '1º Ensino Médio - Química', '1º QUÍM', 'Técnico em Química', 'Manhã', '#CA8A04');
    ");

    // Alunos
    $pdo->exec("
        INSERT INTO alunos (id, turma_id, nome, numero_camisa, posicao, rg_ra, contato_emergencia) VALUES
        (1, 1, 'Gabriel Salles (Capitão)', 10, 'Pivô / Meia', 'RA-1234567-SP', '(11) 98111-2233 - Mãe (Ana)'),
        (2, 1, 'Lucas Martins', 7, 'Ala Direita', 'RA-1234568-SP', '(11) 98111-2234 - Pai (João)'),
        (3, 1, 'Matheus Oliveira (Goleiro)', 1, 'Goleiro', 'RA-1234569-SP', '(11) 98111-2235 - Mãe (Clara)'),
        (4, 1, 'Pedro Henrique', 5, 'Fixo', 'RA-1234570-SP', '(11) 98111-2236 - Pai (Marcos)'),
        (5, 1, 'Enzo Ferrari', 9, 'Ala Esquerda', 'RA-1234571-SP', '(11) 98111-2237 - Irmão (Rafael)'),
        (6, 2, 'Bruno Souza (Capitão)', 10, 'Pivô', 'RA-2234567-SP', '(11) 97222-3344 - Mãe (Lúcia)'),
        (7, 2, 'Felipe Santos', 8, 'Meia', 'RA-2234568-SP', '(11) 97222-3345 - Pai (Rogério)'),
        (8, 2, 'Guilherme Lima (Goleiro)', 12, 'Goleiro', 'RA-2234569-SP', '(11) 97222-3346 - Avó (Maria)'),
        (9, 2, 'Leonardo Alves', 4, 'Fixo', 'RA-2234570-SP', '(11) 97222-3347 - Mãe (Silvia)'),
        (10, 3, 'Thiago Mendes (Capitão)', 11, 'Pivô', 'RA-3234567-SP', '(11) 96333-4455 - Mãe (Patrícia)'),
        (11, 3, 'Rodrigo Costa', 7, 'Ala Direita', 'RA-3234568-SP', '(11) 96333-4456 - Pai (Antônio)'),
        (12, 3, 'Danilo Pereira', 1, 'Goleiro', 'RA-3234569-SP', '(11) 96333-4457 - Mãe (Elza)'),
        (13, 4, 'Arthur Ramos', 9, 'Atacante', 'RA-4234567-SP', '(11) 95444-5566 - Mãe (Renata)'),
        (14, 4, 'Vinícius Silva', 10, 'Meia', 'RA-4234568-SP', '(11) 95444-5567 - Pai (Carlos)');
    ");

    // Partidas
    $pdo->exec("
        INSERT INTO partidas (id, edicao_id, modalidade_id, turma_mandante_id, turma_visitante_id, data_jogo, hora_jogo, local_jogo, fase, placar_mandante, placar_visitante, status, observacoes) VALUES
        (1, 1, 1, 1, 2, '2026-10-20', '09:00:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', 3, 1, 'encerrado', 'Grande jogo de abertura com arquibancada lotada.'),
        (2, 1, 1, 3, 4, '2026-10-20', '10:00:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', 2, 2, 'encerrado', 'Empate emocionante nos segundos finais.'),
        (3, 1, 1, 1, 3, '2026-10-24', '09:30:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', NULL, NULL, 'agendado', 'Partida decisiva pela liderança do grupo.'),
        (4, 1, 1, 2, 4, '2026-10-24', '10:30:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', NULL, NULL, 'agendado', 'Disputa de recuperação no torneio.');
    ");

    // Súmula
    $pdo->exec("
        INSERT INTO sumula_eventos (partida_id, turma_id, aluno_id, tipo, minuto, observacao) VALUES
        (1, 1, 1, 'gol_ponto', 8, 'Gol de bico no ângulo esquerdo'),
        (1, 1, 2, 'assistencia', 8, 'Passe rasteiro em profundidade'),
        (1, 1, 1, 'gol_ponto', 18, 'Gol de pênalti com segurança'),
        (1, 2, 6, 'gol_ponto', 22, 'Finalização rápida na saída do goleiro'),
        (1, 1, 5, 'gol_ponto', 28, 'Contra-ataque mortal'),
        (1, 1, 1, 'assistencia', 28, 'Belo passe de calcanhar'),
        (1, 2, 9, 'cartao_amarelo', 15, 'Falta tática para interromper contra-ataque'),
        (1, 1, 4, 'cartao_amarelo', 26, 'Reclamação com a arbitragem'),
        (2, 3, 10, 'gol_ponto', 5, 'Chute forte de fora da área'),
        (2, 4, 13, 'gol_ponto', 12, 'Gol de cabeça após lateral'),
        (2, 4, 14, 'assistencia', 12, 'Cruzamento preciso'),
        (2, 3, 11, 'gol_ponto', 25, 'Rebote do goleiro'),
        (2, 4, 13, 'gol_ponto', 29, 'Gol de empate no último minuto'),
        (2, 3, 11, 'cartao_amarelo', 19, 'Carrinho atrasado');
    ");

    // Comunicados
    $pdo->exec("
        INSERT INTO comunicados (edicao_id, usuario_id, titulo, conteudo, tipo, partida_id) VALUES
        (1, 1, 'Abertura Oficial dos Jogos Escolares 2026', 'Sejam todos bem-vindos ao Interclasse ETEC 2026! O espírito esportivo e o respeito mútuo devem prevalecer.', 'geral', NULL),
        (1, 1, 'Aviso de Cronograma: Partida 3 remanejada para Quadra A', 'Informamos que a partida entre 3º INFO e 2º EDIF será realizada no Ginásio Principal - Quadra A às 09:30 do dia 24/10/2026.', 'cronograma', 3);
    ");

    // Logs
    $pdo->exec("
        INSERT INTO logs_auditoria (usuario_id, usuario_nome, acao, tabela_afetada, registro_id, detalhes, ip_origem) VALUES
        (1, 'Administrador do Sistema', 'CRIAR_EDICAO', 'edicoes', 1, 'Criação da edição Interclasse ETEC 2026', '127.0.0.1'),
        (1, 'Administrador do Sistema', 'CADASTRAR_TURMAS', 'turmas', 1, 'Cadastro das 4 turmas participantes do torneio', '127.0.0.1'),
        (1, 'Administrador do Sistema', 'FINALIZAR_PARTIDA', 'partidas', 1, 'Encerramento da partida 1: 3º INFO 3 x 1 3º ADM', '127.0.0.1');
    ");
}

/**
 * Retorna a instância PDO ativa
 * @return PDO
 */
function get_pdo(): PDO {
    global $pdo;
    return $pdo;
}
