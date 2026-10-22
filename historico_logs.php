<?php
/**
 * Página Oficial de Históricos & Auditoria do Sistema
 * 
 * Regra CORE:
 * "O sistema deve ter uma página de históricos e manter sempre os logs de qualquer
 * alteração feita por qualquer usuário, para auditorias futuras."
 */

$pageTitle = 'Histórico & Auditoria';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Filtros
$filtroAcao = trim($_GET['acao'] ?? '');
$filtroUsuario = trim($_GET['usuario'] ?? '');
$filtroBusca = trim($_GET['busca'] ?? '');
$filtroData = trim($_GET['data'] ?? '');

// Montar query de logs com filtros
$sql = "SELECT * FROM logs_auditoria WHERE 1=1";
$params = [];

if (!empty($filtroAcao)) {
    $sql .= " AND acao = :acao";
    $params[':acao'] = $filtroAcao;
}

if (!empty($filtroUsuario)) {
    $sql .= " AND usuario_nome LIKE :usuario";
    $params[':usuario'] = "%{$filtroUsuario}%";
}

if (!empty($filtroData)) {
    $sql .= " AND DATE(data_hora) = :data";
    $params[':data'] = $filtroData;
}

if (!empty($filtroBusca)) {
    $sql .= " AND (detalhes LIKE :busca OR tabela_afetada LIKE :busca)";
    $params[':busca'] = "%{$filtroBusca}%";
}

$sql .= " ORDER BY data_hora DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Buscar ações distintas para o select de filtro
$stmtAcoes = $pdo->query("SELECT DISTINCT acao FROM logs_auditoria ORDER BY acao ASC");
$acoesDistintas = $stmtAcoes->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 mb-1">
                <span>Transparência e Conformidade</span>
                <span>•</span>
                <span>Auditoria Contínua</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                <span>Histórico de Auditoria</span>
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Registro imutável de todas as modificações, acessos e alterações realizadas no sistema.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Auditoria Ativa</span>
            </span>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6">
        <form method="GET" action="historico_logs.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            
            <!-- Filtro de Ação -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Tipo de Ação</label>
                <select name="acao" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas as Ações</option>
                    <?php foreach ($acoesDistintas as $ac): ?>
                        <option value="<?= htmlspecialchars($ac) ?>" <?= $filtroAcao === $ac ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ac) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro Usuário -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Usuário / Responsável</label>
                <input type="text" name="usuario" value="<?= htmlspecialchars($filtroUsuario) ?>" placeholder="Nome do usuário..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Filtro Data -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Data da Operação</label>
                <input type="date" name="data" value="<?= htmlspecialchars($filtroData) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Botões -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition text-center shadow-sm">
                    <i class="fa-solid fa-filter mr-1"></i> Filtrar
                </button>
                <a href="historico_logs.php" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs rounded-xl transition text-center">
                    Limpar
                </a>
            </div>

        </form>
    </div>

    <!-- Tabela de Auditoria Responsiva -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        
        <div class="p-4 bg-slate-50/60 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">
                Últimos registros auditados (Exibindo <?= count($logs) ?>)
            </span>
            <span class="text-[11px] text-slate-400">
                Ordenado cronologicamente (Mais recente primeiro)
            </span>
        </div>

        <?php if (!empty($logs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-200">
                            <th class="py-3 px-3 text-center w-12">ID</th>
                            <th class="py-3 px-4">Data & Horário</th>
                            <th class="py-3 px-4">Usuário</th>
                            <th class="py-3 px-3">Ação Executada</th>
                            <th class="py-3 px-3">Tabela / Registro</th>
                            <th class="py-3 px-4">Detalhes da Alteração</th>
                            <th class="py-3 px-3 text-right">IP de Origem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-3 text-center text-slate-400 font-mono">
                                    #<?= $log['id'] ?>
                                </td>
                                
                                <td class="py-3 px-4 whitespace-nowrap text-slate-600 font-medium">
                                    <?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 block truncate max-w-[140px]">
                                        <?= htmlspecialchars($log['usuario_nome'] ?? 'Visitante') ?>
                                    </span>
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider <?= match(true) {
                                        str_contains($log['acao'], 'LOGIN') => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                                        str_contains($log['acao'], 'CRONOGRAMA') => 'bg-amber-100 text-amber-800 border border-amber-300',
                                        str_contains($log['acao'], 'EXCLUIR') => 'bg-rose-100 text-rose-800 border border-rose-300',
                                        str_contains($log['acao'], 'CRIAR') => 'bg-blue-100 text-blue-800 border border-blue-300',
                                        str_contains($log['acao'], 'ATUALIZAR') => 'bg-indigo-100 text-indigo-800 border border-indigo-300',
                                        default => 'bg-slate-100 text-slate-700 border border-slate-300'
                                    } ?>">
                                        <?= htmlspecialchars($log['acao']) ?>
                                    </span>
                                </td>

                                <td class="py-3 px-3 text-slate-600 font-mono text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($log['tabela_afetada'] ?? '-') ?>
                                    <?= $log['registro_id'] ? "(#{$log['registro_id']})" : '' ?>
                                </td>

                                <td class="py-3 px-4 text-slate-700 max-w-sm break-words">
                                    <?php 
                                    $det = $log['detalhes'];
                                    // Tentar decodificar se for JSON para visualização amigável
                                    $json = json_decode($det, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                        echo "<pre class='font-mono text-[10px] text-slate-600 bg-slate-50 p-1.5 rounded border border-slate-200 overflow-x-auto max-h-24'>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                                    } else {
                                        echo htmlspecialchars($det ?? '-');
                                    }
                                    ?>
                                </td>

                                <td class="py-3 px-3 text-right font-mono text-slate-400 text-[11px] whitespace-nowrap">
                                    <?= htmlspecialchars($log['ip_origem'] ?? '127.0.0.1') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 px-4">
                <i class="fa-solid fa-list-check text-4xl text-slate-300 mb-2 block"></i>
                <h4 class="font-bold text-slate-700 text-sm">Nenhum log encontrado para este filtro</h4>
                <p class="text-xs text-slate-500 mt-1">Todas as ações dos usuários são registradas aqui automaticamente.</p>
            </div>
        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
