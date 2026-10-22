<?php
/**
 * Gerenciamento de Edições do Torneio (Admin)
 * Regra: Dados de uma edição não podem interferir em outra
 */

$pageTitle = 'Gerenciar Edições';
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('../index.php');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$stmt = $pdo->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM turmas WHERE edicao_id = e.id) AS total_turmas,
           (SELECT COUNT(*) FROM partidas WHERE edicao_id = e.id) AS total_partidas
    FROM edicoes e
    ORDER BY e.ano DESC, e.id DESC
");
$edicoes = $stmt->fetchAll();
?>

<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-amber-600 mb-1">
                <span>Configuração Geral</span>
                <span>•</span>
                <span>Isolamento de Dados</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Edições do Interclasse
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Crie e alterne entre edições anuais. Os dados de cada edição são 100% isolados.
            </p>
        </div>

        <button type="button" onclick="abrirModalNovaEdicao()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Nova Edição</span>
        </button>
    </div>

    <!-- Lista de Edições -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($edicoes as $ed): 
            $isAtiva = ($ed['ativa'] == 1);
            $isSelected = ($ed['id'] == $edicaoAtiva['id']);
        ?>
            <div class="bg-white rounded-2xl border p-5 shadow-sm flex flex-col justify-between <?= $isSelected ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200' ?>">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-black px-2.5 py-0.5 rounded-full <?= $isAtiva ? 'bg-emerald-100 text-emerald-700 border border-emerald-300' : 'bg-slate-100 text-slate-600' ?>">
                            <?= $isAtiva ? 'Edição Padrão Ativa' : 'Edição Arquivada' ?>
                        </span>

                        <span class="text-xs font-bold text-slate-400">Ano: <?= $ed['ano'] ?></span>
                    </div>

                    <h2 class="text-lg font-black text-slate-900 mb-1"><?= htmlspecialchars($ed['nome']) ?></h2>
                    <span class="text-xs text-slate-500 block mb-4">Status: <?= ucfirst($ed['status']) ?></span>

                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100 mb-4">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Turmas</span>
                            <span class="font-bold text-slate-800 text-sm"><?= $ed['total_turmas'] ?> salas</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Jogos</span>
                            <span class="font-bold text-slate-800 text-sm"><?= $ed['total_partidas'] ?> partidas</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <a href="../actions/edicoes_action.php?action=selecionar&id=<?= $ed['id'] ?>" class="py-1.5 px-3 rounded-lg text-xs font-bold transition <?= $isSelected ? 'bg-blue-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' ?>">
                        <?= $isSelected ? '✓ Em Visualização' : 'Visualizar Esta Edição' ?>
                    </a>

                    <?php if (!$isAtiva): ?>
                        <a href="../actions/edicoes_action.php?action=ativar&id=<?= $ed['id'] ?>" data-confirm="Deseja definir esta edição como a padrão ativa para todos os usuários?" data-title="Definir Ativa" class="py-1.5 px-3 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 text-xs font-bold rounded-lg transition">
                            Tornar Padrão
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>

<!-- Modal: Nova Edição -->
<div id="modal-nova-edicao" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-slate-900">Cadastrar Nova Edição</h3>
            <button type="button" onclick="fecharModalNovaEdicao()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="../actions/edicoes_action.php?action=criar" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nome da Edição *</label>
                <input type="text" name="nome" placeholder="Ex: Interclasse ETEC 2027" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Ano Letivo *</label>
                    <input type="number" name="ano" value="<?= (int)date('Y') + 1 ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                        <option value="em_andamento">Em Andamento</option>
                        <option value="planejamento">Planejamento</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="ativa" value="1" class="w-4 h-4 rounded text-blue-600">
                    <span class="text-xs font-semibold text-slate-700">Definir como edição padrão ativa imediatamente</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalNovaEdicao()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                    Criar Edição
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNovaEdicao() {
    document.getElementById('modal-nova-edicao').classList.remove('hidden');
}
function fecharModalNovaEdicao() {
    document.getElementById('modal-nova-edicao').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
