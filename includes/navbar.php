<?php
/**
 * Barra de Navegação Responsiva (Mobile-First)
 * Adapta opções de acordo com o perfil do usuário (Admin, Técnico, Visitante)
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/auth.php';

$usuario = get_logged_user();
$edicaoAtiva = get_active_edicao($pdo);

// Buscar todas as edições para o seletor rápido
$stmtEdicoes = $pdo->query("SELECT id, nome, ano, status FROM edicoes ORDER BY ano DESC, id DESC");
$todasEdicoes = $stmtEdicoes->fetchAll();

// Obter contagem de comunicados urgentes para badge
$stmtAvisos = $pdo->prepare("SELECT COUNT(*) FROM comunicados WHERE edicao_id = :edicao_id AND tipo = 'urgente'");
$stmtAvisos->execute([':edicao_id' => $edicaoAtiva['id']]);
$totalAvisosUrgentes = (int)$stmtAvisos->fetchColumn();

$currentUri = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>

<nav class="sticky top-0 z-40 bg-slate-900 border-b border-slate-800 text-white shadow-lg backdrop-blur-md bg-opacity-95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo e Nome do Sistema -->
            <div class="flex items-center gap-3">
                <a href="index.php" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition transform">
                        <i class="fa-solid fa-trophy text-lg text-amber-300"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-white via-slate-100 to-blue-200 bg-clip-text text-transparent">
                            Interclasse
                        </span>
                        <span class="hidden sm:inline-block text-[11px] font-semibold text-blue-400 bg-blue-950/80 px-2 py-0.5 rounded-full ml-1 border border-blue-800/50">
                            ETEC
                        </span>
                    </div>
                </a>

                <!-- Seletor de Edição Ativa (Garante isolamento de edições) -->
                <div class="relative ml-2 sm:ml-4">
                    <button type="button" id="edicao-dropdown-btn" class="flex items-center gap-1.5 text-xs font-medium text-slate-300 bg-slate-800/80 hover:bg-slate-700/80 hover:text-white px-2.5 py-1.5 rounded-lg border border-slate-700 transition">
                        <i class="fa-solid fa-calendar-days text-blue-400"></i>
                        <span class="truncate max-w-[110px] sm:max-w-[160px] font-semibold">
                            <?= htmlspecialchars($edicaoAtiva['nome'] ?? 'Edição') ?>
                        </span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>
                    <!-- Dropdown de Edições -->
                    <div id="edicao-dropdown-menu" class="hidden absolute left-0 mt-2 w-64 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl py-2 z-50 text-xs text-slate-200 divide-y divide-slate-700">
                        <div class="px-3 py-1.5 font-bold uppercase tracking-wider text-[10px] text-slate-400">
                            Selecionar Edição
                        </div>
                        <div class="py-1">
                            <?php foreach ($todasEdicoes as $ed): ?>
                                <a href="actions/edicoes_action.php?action=selecionar&id=<?= $ed['id'] ?>" 
                                   class="flex items-center justify-between px-3 py-2 hover:bg-slate-700 transition <?= $ed['id'] == $edicaoAtiva['id'] ? 'text-blue-400 font-bold bg-slate-700/50' : 'text-slate-300' ?>">
                                    <span class="truncate"><?= htmlspecialchars($ed['nome']) ?></span>
                                    <?php if ($ed['id'] == $edicaoAtiva['id']): ?>
                                        <i class="fa-solid fa-check text-blue-400"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if (is_admin()): ?>
                            <div class="p-2">
                                <a href="views/edicoes.php" class="block text-center py-1.5 px-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                                    <i class="fa-solid fa-plus mr-1"></i> Gerenciar Edições
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Links Principais Desktop -->
            <div class="hidden md:flex items-center gap-1 lg:gap-2">
                <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentUri === 'index.php' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-house-chimney text-xs mr-1.5 opacity-80"></i>Início
                </a>

                <a href="classificacao.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentUri === 'classificacao.php' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-table-list text-xs mr-1.5 opacity-80"></i>Classificação
                </a>

                <a href="jogos.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= in_array($currentUri, ['jogos.php', 'partidas.php']) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-gamepad text-xs mr-1.5 opacity-80"></i>Jogos
                </a>

                <a href="estatisticas.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentUri === 'estatisticas.php' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-column text-xs mr-1.5 opacity-80"></i>Estatísticas
                </a>

                <a href="turmas.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= in_array($currentUri, ['turmas.php', 'turma_detalhes.php']) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-users text-xs mr-1.5 opacity-80"></i>Turmas
                </a>

                <a href="comunicados.php" class="relative px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentUri === 'comunicados.php' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i class="fa-solid fa-bullhorn text-xs mr-1.5 opacity-80"></i>Avisos
                    <?php if ($totalAvisosUrgentes > 0): ?>
                        <span class="absolute top-1.5 right-1 w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                        <span class="absolute top-1.5 right-1 w-2 h-2 bg-rose-500 rounded-full"></span>
                    <?php endif; ?>
                </a>

                <!-- Histórico e Logs de Auditoria (Obrigatório nos requisitos) -->
                <a href="historico_logs.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $currentUri === 'historico_logs.php' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>" title="Auditoria e Histórico">
                    <i class="fa-solid fa-clock-rotate-left text-xs mr-1.5 opacity-80"></i>Histórico
                </a>
            </div>

            <!-- Área de Usuário / Login Desktop -->
            <div class="hidden md:flex items-center gap-3">
                <?php if ($usuario): ?>
                    <!-- Perfil Logado -->
                    <div class="flex items-center gap-2.5 pl-3 border-l border-slate-700">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs ring-2 ring-blue-400/30">
                            <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                        </div>
                        <div class="text-left text-xs">
                            <p class="font-bold text-slate-200 truncate max-w-[120px] leading-tight">
                                <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?>
                            </p>
                            <span class="inline-block uppercase tracking-wider text-[9px] font-extrabold px-1.5 py-0.5 rounded <?= match($usuario['perfil']) {
                                'admin' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                                'tecnico' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                                default => 'bg-slate-700 text-slate-300'
                            } ?>">
                                <?= htmlspecialchars($usuario['perfil']) ?>
                            </span>
                        </div>
                        <a href="actions/auth_action.php?action=logout" class="ml-2 text-slate-400 hover:text-rose-400 transition p-1.5 rounded-lg hover:bg-slate-800" title="Sair do Sistema">
                            <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Botão de Login -->
                    <a href="login.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md hover:shadow-blue-500/20">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Entrar</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Botão Menu Hambúrguer Mobile -->
            <div class="flex items-center md:hidden">
                <button type="button" id="mobile-menu-btn" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition" aria-label="Abrir Menu">
                    <i class="fa-solid fa-bars text-xl" id="mobile-menu-icon"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Drawer Mobile -->
    <div id="mobile-menu" class="hidden md:hidden bg-slate-900 border-b border-slate-800 px-4 pt-2 pb-6 space-y-2">
        <div class="py-2 border-b border-slate-800 mb-2">
            <span class="text-xs uppercase font-bold text-slate-500 tracking-wider">Navegação</span>
        </div>
        <a href="index.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= $currentUri === 'index.php' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-house-chimney w-5 mr-2 text-slate-400"></i>Início
        </a>
        <a href="classificacao.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= $currentUri === 'classificacao.php' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-table-list w-5 mr-2 text-slate-400"></i>Classificação
        </a>
        <a href="jogos.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= in_array($currentUri, ['jogos.php', 'partidas.php']) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-gamepad w-5 mr-2 text-slate-400"></i>Partidas & Cronograma
        </a>
        <a href="estatisticas.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= $currentUri === 'estatisticas.php' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-chart-column w-5 mr-2 text-slate-400"></i>Estatísticas & Artilharia
        </a>
        <a href="turmas.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= in_array($currentUri, ['turmas.php', 'turma_detalhes.php']) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-users w-5 mr-2 text-slate-400"></i>Turmas & Alunos
        </a>
        <a href="comunicados.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= $currentUri === 'comunicados.php' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-bullhorn w-5 mr-2 text-slate-400"></i>Mural de Comunicados
        </a>
        <a href="historico_logs.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold transition <?= $currentUri === 'historico_logs.php' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-clock-rotate-left w-5 mr-2 text-slate-400"></i>Histórico & Auditoria
        </a>

        <?php if (is_admin()): ?>
            <div class="py-2 border-b border-slate-800 pt-3">
                <span class="text-xs uppercase font-bold text-amber-500 tracking-wider">Administração</span>
            </div>
            <a href="views/modalidades.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-800">
                <i class="fa-solid fa-medal w-5 mr-2 text-amber-400"></i>Modalidades Esportivas
            </a>
            <a href="views/admin_usuarios.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-800">
                <i class="fa-solid fa-user-shield w-5 mr-2 text-amber-400"></i>Gerenciar Usuários
            </a>
            <a href="views/edicoes.php" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-800">
                <i class="fa-solid fa-calendar-plus w-5 mr-2 text-amber-400"></i>Gerenciar Edições
            </a>
        <?php endif; ?>

        <!-- Perfil Mobile -->
        <div class="pt-4 border-t border-slate-800">
            <?php if ($usuario): ?>
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-800/80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                            <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-white"><?= htmlspecialchars($usuario['nome']) ?></div>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars($usuario['email']) ?></div>
                        </div>
                    </div>
                    <a href="actions/auth_action.php?action=logout" class="py-2 px-3 bg-rose-600/20 text-rose-300 hover:bg-rose-600 hover:text-white rounded-lg text-xs font-bold transition">
                        Sair
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="block text-center w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-lg">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i>Entrar no Sistema
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Toggle menu mobile
const mobileBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
const mobileIcon = document.getElementById('mobile-menu-icon');

if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
        if (mobileMenu.classList.contains('hidden')) {
            mobileIcon.className = 'fa-solid fa-bars text-xl';
        } else {
            mobileIcon.className = 'fa-solid fa-xmark text-xl';
        }
    });
}

// Toggle dropdown edições
const edicaoBtn = document.getElementById('edicao-dropdown-btn');
const edicaoMenu = document.getElementById('edicao-dropdown-menu');

if (edicaoBtn && edicaoMenu) {
    edicaoBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        edicaoMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        edicaoMenu.classList.add('hidden');
    });
}
</script>
