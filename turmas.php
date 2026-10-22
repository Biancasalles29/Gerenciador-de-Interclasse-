<?php
/**
 * Listagem e Cadastro de Turmas / Salas
 * 
 * Regras CORE:
 * - Somente os técnicos e administradores podem cadastrar turmas
 * - Demais pessoas têm acesso de visualização
 * - Turmas vinculadas à edição ativa
 */

$pageTitle = 'Turmas & Salas';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];

// Buscar turmas da edição ativa com contagem de alunos e nome do técnico
$stmtTurmas = $pdo->prepare("
    SELECT t.*, u.nome AS tecnico_nome, u.email AS tecnico_email,
           (SELECT COUNT(*) FROM alunos WHERE turma_id = t.id AND ativo = 1) AS total_alunos
    FROM turmas t
    LEFT JOIN usuarios u ON u.id = t.tecnico_id
    WHERE t.edicao_id = :edicao_id
    ORDER BY t.sigla ASC
");
$stmtTurmas->execute([':edicao_id' => $edicaoId]);
$turmas = $stmtTurmas->fetchAll();

// Se for admin, buscar lista de técnicos para atribuição
$tecnicosDisponiveis = [];
if (is_admin()) {
    $stmtTec = $pdo->query("SELECT id, nome, email FROM usuarios WHERE perfil IN ('tecnico', 'admin') AND ativo = 1 ORDER BY nome ASC");
    $tecnicosDisponiveis = $stmtTec->fetchAll();
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 mb-1">
                <span><?= htmlspecialchars($edicaoAtiva['nome']) ?></span>
                <span>•</span>
                <span>Salas Participantes</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Turmas & Salas
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Conheça as equipes, técnicos responsáveis e atletas inscritos no torneio.
            </p>
        </div>

        <?php if (is_tecnico() || is_admin()): ?>
            <div>
                <button type="button" onclick="abrirModalNovaTurma()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Cadastrar Minha Turma</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Grid de Turmas Responsivo -->
    <?php if (!empty($turmas)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($turmas as $t): 
                $podeGerenciar = can_manage_turma($t['tecnico_id']);
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between overflow-hidden">
                    
                    <div>
                        <!-- Faixa colorida do uniforme no topo -->
                        <div class="h-2.5 w-full" style="background-color: <?= htmlspecialchars($t['cor_uniforme']) ?>;"></div>

                        <div class="p-5 sm:p-6">
                            
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-md" style="background-color: <?= htmlspecialchars($t['cor_uniforme']) ?>;">
                                        <?= substr($t['sigla'], 0, 3) ?>
                                    </div>
                                    <div>
                                        <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">
                                            <?= htmlspecialchars($t['sigla']) ?>
                                        </h3>
                                        <span class="text-xs text-slate-500 font-medium">
                                            <?= htmlspecialchars($t['periodo'] ?? 'Manhã') ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if ($podeGerenciar): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Sua Sala
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-xs text-slate-600 mb-4 font-normal line-clamp-1">
                                <?= htmlspecialchars($t['nome']) ?>
                            </p>

                            <?php if (!empty($t['curso'])): ?>
                                <div class="mb-4 text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-100 flex items-center gap-2">
                                    <i class="fa-solid fa-graduation-cap text-slate-400"></i>
                                    <span class="truncate"><?= htmlspecialchars($t['curso']) ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Informações do Técnico -->
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Técnico(a)</span>
                                    <span class="font-bold text-slate-800 truncate block max-w-[160px]">
                                        <?= htmlspecialchars($t['tecnico_nome'] ?? 'A definir') ?>
                                    </span>
                                </div>

                                <div class="text-right">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Atletas</span>
                                    <span class="font-extrabold text-blue-600 block">
                                        <?= $t['total_alunos'] ?> inscritos
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Botão Acessar Detalhes -->
                    <div class="p-4 bg-slate-50/80 border-t border-slate-100">
                        <a href="turma_detalhes.php?id=<?= $t['id'] ?>" class="block w-full py-2 px-4 bg-white hover:bg-blue-600 hover:text-white text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition text-center shadow-sm">
                            Ver Elenco & Atletas <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i class="fa-solid fa-users-slash text-4xl text-slate-300 mb-3 block"></i>
            <h3 class="text-base font-bold text-slate-700">Nenhuma turma cadastrada para esta edição</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
                Os técnicos de cada sala podem cadastrar suas turmas e em seguida inscrever seus alunos.
            </p>
            <?php if (is_tecnico() || is_admin()): ?>
                <button type="button" onclick="abrirModalNovaTurma()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Cadastrar Primeira Turma</span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<!-- Modal: Cadastrar Nova Turma -->
<?php if (is_tecnico() || is_admin()): ?>
    <div id="modal-nova-turma" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-users-rectangle text-blue-600"></i>
                    <span>Cadastrar Turma</span>
                </h3>
                <button type="button" onclick="fecharModalNovaTurma()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/turmas_action.php?action=criar" method="POST" class="space-y-4">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo da Turma *</label>
                    <input type="text" name="nome" placeholder="Ex: 3º Ensino Médio - Informática A" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Sigla / Nome Curto *</label>
                        <input type="text" name="sigla" placeholder="Ex: 3º INFO A" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Período</label>
                        <select name="periodo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                            <option value="Integral">Integral</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Curso Técnico / Habilitação</label>
                    <input type="text" name="curso" placeholder="Ex: Desenvolvimento de Sistemas" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Cor do Uniforme</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="cor_uniforme" value="#2563EB" class="w-10 h-9 p-0.5 border border-slate-200 rounded-lg cursor-pointer bg-slate-50">
                            <span class="text-[11px] text-slate-500 font-mono">Destaque</span>
                        </div>
                    </div>

                    <?php if (is_admin()): ?>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Técnico Responsável</label>
                            <select name="tecnico_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                                <option value="">Eu mesmo</option>
                                <?php foreach ($tecnicosDisponiveis as $tec): ?>
                                    <option value="<?= $tec['id'] ?>"><?= htmlspecialchars($tec['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-3 bg-blue-50 rounded-xl text-[11px] text-blue-700 leading-relaxed border border-blue-200">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Após cadastrar a turma, você poderá inscrever os alunos/atletas e gerenciar suas numerações e posições.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharModalNovaTurma()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Cadastrar Turma
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalNovaTurma() {
        document.getElementById('modal-nova-turma').classList.remove('hidden');
    }
    function fecharModalNovaTurma() {
        document.getElementById('modal-nova-turma').classList.add('hidden');
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
