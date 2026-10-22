<?php
/**
 * Gerenciamento de Usuários e Permissões (Admin)
 */

$pageTitle = 'Controle de Usuários';
require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('../index.php');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$stmt = $pdo->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM turmas WHERE tecnico_id = u.id) AS total_turmas_tecnico
    FROM usuarios u
    ORDER BY u.perfil ASC, u.nome ASC
");
$usuarios = $stmt->fetchAll();
?>

<main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-amber-600 mb-1">
                <span>Painel de Controle</span>
                <span>•</span>
                <span>Permissões & Acesso</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Usuários & Perfis
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Administradores, Técnicos de Sala e Visitantes cadastrados.
            </p>
        </div>

        <button type="button" onclick="abrirModalNovoUsuario()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
            <i class="fa-solid fa-user-plus"></i>
            <span>Novo Usuário</span>
        </button>
    </div>

    <!-- Tabela de Usuários -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                        <th class="py-3 px-4">Usuário</th>
                        <th class="py-3 px-3">E-mail</th>
                        <th class="py-3 px-3 text-center">Perfil</th>
                        <th class="py-3 px-3">Telefone</th>
                        <th class="py-3 px-3 text-center">Salas Vinculadas</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($usuarios as $u): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs">
                                    <?= strtoupper(substr($u['nome'], 0, 1)) ?>
                                </div>
                                <span><?= htmlspecialchars($u['nome']) ?></span>
                            </td>
                            <td class="py-3.5 px-3 text-slate-600 font-mono text-xs">
                                <?= htmlspecialchars($u['email']) ?>
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider <?= match($u['perfil']) {
                                    'admin' => 'bg-amber-100 text-amber-800 border border-amber-300',
                                    'tecnico' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                                    default => 'bg-slate-100 text-slate-600'
                                } ?>">
                                    <?= htmlspecialchars($u['perfil']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-slate-500">
                                <?= htmlspecialchars($u['telefone'] ?: '-') ?>
                            </td>
                            <td class="py-3.5 px-3 text-center text-slate-700 font-semibold">
                                <?= $u['total_turmas_tecnico'] ?> sala(s)
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span class="w-2.5 h-2.5 rounded-full inline-block <?= $u['ativo'] ? 'bg-emerald-500' : 'bg-rose-500' ?>" title="<?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>"></span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <button type="button" onclick="abrirModalEditarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)" class="text-slate-400 hover:text-blue-600 transition p-1" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                    <a href="../actions/admin_usuarios_action.php?action=toggle_status&id=<?= $u['id'] ?>" data-confirm="Deseja alterar o status (ativo/inativo) deste usuário?" data-title="Alterar Status" class="text-slate-400 hover:text-amber-600 transition p-1" title="Ativar/Desativar">
                                        <i class="fa-solid fa-power-off"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal: Novo Usuário -->
<div id="modal-novo-user" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-slate-900">Cadastrar Usuário</h3>
            <button type="button" onclick="fecharModalNovoUsuario()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="../actions/admin_usuarios_action.php?action=criar" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo *</label>
                <input type="text" name="nome" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">E-mail *</label>
                <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Perfil</label>
                    <select name="perfil" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                        <option value="tecnico">Técnico de Sala</option>
                        <option value="admin">Administrador</option>
                        <option value="visitante">Visitante</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Telefone</label>
                    <input type="text" name="telefone" placeholder="(11) 99999-8888" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Senha Inicial (Bcrypt) *</label>
                <input type="password" name="senha" required minlength="6" placeholder="Mínimo 6 dígitos" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalNovoUsuario()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                    Criar Usuário
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Usuário -->
<div id="modal-editar-user" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-lg font-bold text-slate-900">Editar Usuário</h3>
            <button type="button" onclick="fecharModalEditarUsuario()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="../actions/admin_usuarios_action.php?action=editar" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="edit-user-id">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo *</label>
                <input type="text" name="nome" id="edit-user-nome" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">E-mail *</label>
                <input type="email" name="email" id="edit-user-email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Perfil</label>
                    <select name="perfil" id="edit-user-perfil" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                        <option value="tecnico">Técnico de Sala</option>
                        <option value="admin">Administrador</option>
                        <option value="visitante">Visitante</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Telefone</label>
                    <input type="text" name="telefone" id="edit-user-telefone" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nova Senha (Deixe em branco para manter a atual)</label>
                <input type="password" name="senha" placeholder="Opcional" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalEditarUsuario()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
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
function abrirModalNovoUsuario() {
    document.getElementById('modal-novo-user').classList.remove('hidden');
}
function fecharModalNovoUsuario() {
    document.getElementById('modal-novo-user').classList.add('hidden');
}

function abrirModalEditarUsuario(u) {
    document.getElementById('edit-user-id').value = u.id;
    document.getElementById('edit-user-nome').value = u.nome;
    document.getElementById('edit-user-email').value = u.email;
    document.getElementById('edit-user-perfil').value = u.perfil;
    document.getElementById('edit-user-telefone').value = u.telefone || '';
    document.getElementById('modal-editar-user').classList.remove('hidden');
}
function fecharModalEditarUsuario() {
    document.getElementById('modal-editar-user').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
