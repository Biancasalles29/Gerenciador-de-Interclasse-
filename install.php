<?php
/**
 * Assistente de Instalação e Inicialização do Banco de Dados
 * Gerenciador de Interclasse
 */

define('APP_ROOT', __DIR__);
$configFile = __DIR__ . '/config/database.php';
$config = file_exists($configFile) ? require $configFile : [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'database' => 'interclasse_db',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4'
];

$step = $_GET['step'] ?? '';
$error = null;
$success = null;

// Executar instalação ao receber POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_instalacao'])) {
    $host = trim($_POST['host'] ?? '127.0.0.1');
    $port = trim($_POST['port'] ?? '3306');
    $database = trim($_POST['database'] ?? 'interclasse_db');
    $username = trim($_POST['username'] ?? 'root');
    $password = $_POST['password'] ?? '';

    try {
        // 1. Conectar ao servidor MySQL sem especificar banco
        $dsnServer = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdoInit = new PDO($dsnServer, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 2. Criar o banco de dados
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // 3. Conectar agora ao banco criado
        $dsnDb = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdoDb = new PDO($dsnDb, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 4. Executar script SQL
        $sqlScriptPath = __DIR__ . '/database.sql';
        if (!file_exists($sqlScriptPath)) {
            throw new Exception("Arquivo database.sql não encontrado na raiz.");
        }

        $sql = file_get_contents($sqlScriptPath);
        
        // Remove delimitadores e executa
        $pdoDb->exec($sql);

        // 5. Salvar configurações atualizadas
        $newConfigContent = "<?php\n/**\n * Configuração de Parâmetros de Conexão com o Banco de Dados\n */\n\nreturn " . var_export([
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $port,
            'database'  => $database,
            'username'  => $username,
            'password'  => $password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options'   => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        ], true) . ";\n";

        file_put_contents($configFile, $newConfigContent);

        $success = "Banco de dados '{$database}' configurado e populado com sucesso!";

    } catch (Exception $e) {
        $error = "Falha na instalação: " . $e->getMessage();
    }
}

// Testar conexão atual
$conexaoOk = false;
$tabelasExistentes = [];
try {
    $dsnTest = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $pdoTest = new PDO($dsnTest, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $conexaoOk = true;
    
    $stmtTables = $pdoTest->query("SHOW TABLES");
    $tabelasExistentes = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $conexaoOk = false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação & Banco de Dados - Interclasse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex items-center justify-center font-sans">
    
    <div class="max-w-2xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
        
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-6 sm:p-8 text-white text-center">
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl border border-white/20">
                <i class="fa-solid fa-database text-amber-300"></i>
            </div>
            <h1 class="text-2xl font-black">Instalador do Banco de Dados</h1>
            <p class="text-xs text-blue-100 mt-1">Gerenciador de Interclasse • MySQL + PDO</p>
        </div>

        <div class="p-6 sm:p-8 space-y-6">
            
            <?php if ($success): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 text-emerald-800 text-xs">
                    <i class="fa-solid fa-circle-check text-base text-emerald-600 mt-0.5"></i>
                    <div>
                        <strong class="font-bold text-sm block">Sucesso!</strong>
                        <?= htmlspecialchars($success) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3 text-rose-800 text-xs">
                    <i class="fa-solid fa-circle-xmark text-base text-rose-600 mt-0.5"></i>
                    <div>
                        <strong class="font-bold text-sm block">Erro detectado:</strong>
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Status da Conexão Atual -->
            <div class="p-4 rounded-2xl border <?= $conexaoOk ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200' ?>">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full <?= $conexaoOk ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                        <span class="font-bold text-xs sm:text-sm text-slate-800">
                            Status do Banco: <?= $conexaoOk ? 'Conectado e Operacional' : 'Banco não configurado' ?>
                        </span>
                    </div>
                    <?php if ($conexaoOk): ?>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                            <?= count($tabelasExistentes) ?> Tabelas Encontradas
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($conexaoOk && !empty($tabelasExistentes)): ?>
                    <div class="mt-3 pt-3 border-t border-slate-200/60 flex flex-wrap gap-1.5">
                        <?php foreach ($tabelasExistentes as $tb): ?>
                            <span class="text-[10px] font-mono bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-600">
                                <?= $tb ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Formulário de Instalação / Reinicialização -->
            <form method="POST" action="install.php" class="space-y-4">
                <input type="hidden" name="executar_instalacao" value="1">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Host MySQL</label>
                        <input type="text" name="host" value="<?= htmlspecialchars($config['host']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Porta</label>
                        <input type="text" name="port" value="<?= htmlspecialchars($config['port']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nome do Banco de Dados</label>
                    <input type="text" name="database" value="<?= htmlspecialchars($config['database']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Usuário MySQL</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($config['username']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Senha MySQL</label>
                        <input type="password" name="password" value="<?= htmlspecialchars($config['password']) ?>" placeholder="(Vazio no XAMPP padrão)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 leading-relaxed">
                    <i class="fa-solid fa-wand-magic-sparkles text-blue-600 mr-1"></i>
                    O instalador criará automaticamente o banco de dados, todas as 9 tabelas do sistema, perfis de Administrador e Técnicos, modalidades esportivas e partidas de demonstração.
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                    <button type="submit" class="w-full sm:flex-1 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-md">
                        <?= $conexaoOk ? '🔄 Recriar / Reinicializar Banco' : '🚀 Criar Banco e Importar Dados' ?>
                    </button>
                    <a href="index.php" class="w-full sm:w-auto py-3 px-5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm rounded-xl transition text-center">
                        Ir para o Site
                    </a>
                </div>
            </form>

        </div>
    </div>

</body>
</html>
