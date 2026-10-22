<?php
/**
 * Gerenciamento de Modalidades Esportivas (Admin)
 */

$pageTitle = 'Gerenciar Modalidades';
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('../index.php');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];

$stmt = $pdo->prepare("SELECT * FROM modalidades WHERE edicao_id = :id ORDER BY nome ASC");
$stmt->execute([':id' => $edicaoId]);
$modalidades = $stmt->fetchAll();
?>

<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-amber-600 mb-1">
                <span>Painel Administrativo</span>
                <span>•</span>
                <span>Configuração de Esportes</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Modalidades Esportivas
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Esportes cadastrados para a edição <?= htmlspecialchars($edicaoAtiva['nome']) ?>.
            </p>
        </div>

        <button type="button" onclick="abrirModalNovaModalidade()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Nova Modalidade</span>
        </button>
    </div>

    <!-- Tabela de Modalidades -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (!empty($modalidades)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">Modalidade</th>
                            <th class="py-3 px-3">Categoria</th>
                            <th class="py-3 px-3 text-center">Permite Empate?</th>
                            <th class="py-3 px-3 text-center">Duração</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($modalidades as $mod): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                        <i class="fa-solid <?= htmlspecialchars($mod['icone'] ?? 'fa-trophy') ?>"></i>
                                    </div>
                                    <span><?= htmlspecialchars($mod['nome']) ?></span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600">
                                    <?= htmlspecialchars($mod['categoria'] ?? 'Geral') ?>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $mod['permite_empate'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= $mod['permite_empate'] ? 'Sim (1 ponto)' : 'Não (Mata-mata / Prorrogação)' ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center text-slate-600 font-mono">
                                    <?= $mod['tempo_partida_minutos'] ?? 30 ?> min
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <button type="button" onclick="abrirModalEditarModalidade(<?= htmlspecialchars(json_encode($mod)) ?>)" class="text-slate-400 hover:text-blue-600 transition p-1" title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a href="../actions/modalidades_action.php?action=excluir&id=<?= $mod['id'] ?>" data-confirm="Deseja excluir esta modalidade esportiva e todos os jogos vinculados a ela?" data-title="Excluir Modalidade" class="text-slate-400 hover:text-rose-600 transition p-1" title="Excluir">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 px-4">
                <i class="fa-solid fa-trophy text-4xl text-slate-300 mb-2 block"></i>
                <h4 class="font-bold text-slate-700 text-sm">Nenhuma modalidade cadastrada</h4>
                <p class="text-xs text-slate-500 mt-1">Cadastre esportes como Futsal, Vôlei, Basquete, Queimada, etc.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<!-- Modal: Nova Modalidade -->
<div id="modal-nova-mod" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-slate-900">Cadastrar Modalidade</h3>
            <button type="button" onclick="fecharModalNovaModalidade()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="../actions/modalidades_action.php?action=criar" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nome da Modalidade *</label>
                <input type="text" name="nome" placeholder="Ex: Futsal Masculino, Basquete 3x3" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Categoria</label>
                    <input type="text" name="categoria" value="Geral" placeholder="Ex: Masculino, Feminino, Misto" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Ícone FontAwesome</label>
                    <select name="icone" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                        <option value="fa-futbol">Futebol / Futsal (fa-futbol)</option>
                        <option value="fa-volleyball">Vôlei (fa-volleyball)</option>
                        <option value="fa-basketball">Basquete (fa-basketball)</option>
                        <option value="fa-fire">Queimada (fa-fire)</option>
                        <option value="fa-hand-back-fist">Handebol (fa-hand-back-fist)</option>
                        <option value="fa-chess">Xadrez (fa-chess)</option>
                        <option value="fa-table-tennis-paddle-ball">Tênis de Mesa (fa-table-tennis)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 items-center pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tempo Total (Minutos)</label>
                    <input type="number" name="tempo_partida_minutos" value="30" min="5" max="120" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
                <div class="pt-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="permite_empate" value="1" checked class="w-4 h-4 rounded text-blue-600">
                        <span class="text-xs font-semibold text-slate-700">Permite Empate?</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalNovaModalidade()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                    Salvar Modalidade
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Modalidade -->
<div id="modal-editar-mod" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-slate-900">Editar Modalidade</h3>
            <button type="button" onclick="fecharModalEditarModalidade()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="../actions/modalidades_action.php?action=editar" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="edit-mod-id">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nome da Modalidade *</label>
                <input type="text" name="nome" id="edit-mod-nome" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Categoria</label>
                    <input type="text" name="categoria" id="edit-mod-categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Ícone</label>
                    <input type="text" name="icone" id="edit-mod-icone" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 items-center pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tempo Total (Minutos)</label>
                    <input type="number" name="tempo_partida_minutos" id="edit-mod-tempo" min="5" max="120" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
                <div class="pt-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="permite_empate" id="edit-mod-empate" value="1" class="w-4 h-4 rounded text-blue-600">
                        <span class="text-xs font-semibold text-slate-700">Permite Empate?</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalEditarModalidade()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNovaModalidade() {
    document.getElementById('modal-nova-mod').classList.remove('hidden');
}
function fecharModalNovaModalidade() {
    document.getElementById('modal-nova-mod').classList.add('hidden');
}

function abrirModalEditarModalidade(m) {
    document.getElementById('edit-mod-id').value = m.id;
    document.getElementById('edit-mod-nome').value = m.nome;
    document.getElementById('edit-mod-categoria').value = m.categoria;
    document.getElementById('edit-mod-icone').value = m.icone;
    document.getElementById('edit-mod-tempo').value = m.tempo_partida_minutos;
    document.getElementById('edit-mod-empate').checked = (parseInt(m.permite_empate) === 1);
    document.getElementById('modal-editar-mod').classList.remove('hidden');
}
function fecharModalEditarModalidade() {
    document.getElementById('modal-editar-mod').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
