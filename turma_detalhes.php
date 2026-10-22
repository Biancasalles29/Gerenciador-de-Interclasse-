<?php
/**
 * Perfil da Turma e Gestão de Atletas
 * 
 * Regras CORE:
 * - Técnicos gerenciam seus alunos
 * - Proteção estrita de dados pessoais (RG/RA, emergência)
 * - Restrição de acesso aos dados sensíveis
 */

$pageTitle = 'Detalhes da Turma';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// Buscar detalhes da turma
$stmtTurma = $pdo->prepare("
    SELECT t.*, u.nome AS tecnico_nome, u.email AS tecnico_email, u.telefone AS tecnico_telefone
    FROM turmas t
    LEFT JOIN usuarios u ON u.id = t.tecnico_id
    WHERE t.id = :id
");
$stmtTurma->execute([':id' => $id]);
$turma = $stmtTurma->fetch();

if (!$turma) {
    set_flash_message('error', 'Turma não encontrada.');
    header('Location: turmas.php');
    exit;
}

$podeGerenciar = can_manage_turma($turma['tecnico_id']);
$podeVerDadosSensiveis = can_view_sensitive_data($turma['tecnico_id']);

// Buscar atletas inscritos
$stmtAlunos = $pdo->prepare("SELECT * FROM alunos WHERE turma_id = :id AND ativo = 1 ORDER BY numero_camisa ASC, nome ASC");
$stmtAlunos->execute([':id' => $id]);
$alunos = $stmtAlunos->fetchAll();

// Buscar histórico de partidas da turma
$stmtJogos = $pdo->prepare("
    SELECT p.*, 
           tm.sigla AS m_sigla, tm.cor_uniforme AS m_cor,
           tv.sigla AS v_sigla, tv.cor_uniforme AS v_cor,
           m.nome AS modalidade_nome, m.icone AS modalidade_icone
    FROM partidas p
    JOIN turmas tm ON tm.id = p.turma_mandante_id
    JOIN turmas tv ON tv.id = p.turma_visitante_id
    JOIN modalidades m ON m.id = p.modalidade_id
    WHERE (p.turma_mandante_id = :id OR p.turma_visitante_id = :id)
    ORDER BY p.data_jogo DESC, p.hora_jogo DESC
");
$stmtJogos->execute([':id' => $id]);
$jogos = $stmtJogos->fetchAll();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Voltar -->
    <div class="mb-4">
        <a href="turmas.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-blue-600 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Voltar para Lista de Turmas</span>
        </a>
    </div>

    <!-- Banner da Turma -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="h-4 w-full" style="background-color: <?= htmlspecialchars($turma['cor_uniforme']) ?>;"></div>
        
        <div class="p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center text-white font-black text-2xl sm:text-3xl shadow-lg flex-shrink-0" style="background-color: <?= htmlspecialchars($turma['cor_uniforme']) ?>;">
                    <?= substr($turma['sigla'], 0, 3) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400"><?= htmlspecialchars($turma['periodo'] ?? 'Manhã') ?></span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-blue-600 font-semibold"><?= htmlspecialchars($turma['curso'] ?? 'Ensino Médio') ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        <?= htmlspecialchars($turma['sigla']) ?>
                    </h1>
                    <p class="text-slate-500 text-xs sm:text-sm mt-0.5">
                        <?= htmlspecialchars($turma['nome']) ?>
                    </p>
                </div>
            </div>

            <!-- Técnico Responsável & Ações -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
                <div class="text-left md:text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Técnico Responsável</span>
                    <span class="font-bold text-sm text-slate-800 flex items-center gap-1.5 md:justify-end">
                        <i class="fa-solid fa-user-tie text-blue-600"></i>
                        <?= htmlspecialchars($turma['tecnico_nome'] ?? 'Não informado') ?>
                    </span>
                    <?php if ($podeGerenciar && !empty($turma['tecnico_telefone'])): ?>
                        <span class="text-[11px] text-slate-500 block"><?= htmlspecialchars($turma['tecnico_telefone']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($podeGerenciar): ?>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="abrirModalAluno()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center gap-1.5">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Inscrever Atleta</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Aviso de Proteção de Dados LGPD / Escolar -->
    <div class="mb-6 p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 flex items-start sm:items-center justify-between gap-3 text-xs text-blue-900">
        <div class="flex items-start sm:items-center gap-2.5">
            <i class="fa-solid fa-shield-halved text-base text-blue-600 flex-shrink-0 mt-0.5 sm:mt-0"></i>
            <div>
                <strong>Privacidade e Proteção de Dados dos Participantes:</strong>
                <span class="text-blue-800">
                    <?php if ($podeVerDadosSensiveis): ?>
                        Você possui permissão de Técnico/Administrador para visualizar RG/RA e contatos de emergência deste elenco.
                    <?php else: ?>
                        Dados sensíveis de estudantes (documentos e telefones de emergência) são protegidos e visíveis somente para o técnico da sala e a coordenação.
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Lista de Atletas / Elenco -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-id-card text-blue-600"></i>
                <span>Atletas Inscritos (<?= count($alunos) ?>)</span>
            </h3>
        </div>

        <?php if (!empty($alunos)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase font-black text-[10px] tracking-wider border-b border-slate-100">
                            <th class="py-3 px-3 text-center w-12">Nº</th>
                            <th class="py-3 px-4">Nome do Atleta</th>
                            <th class="py-3 px-3">Posição</th>
                            <th class="py-3 px-4">RG / RA Escolar</th>
                            <th class="py-3 px-4">Contato de Emergência</th>
                            <?php if ($podeGerenciar): ?>
                                <th class="py-3 px-4 text-right">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($alunos as $al): ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-3 text-center font-black text-slate-800">
                                    <span class="inline-block w-7 py-1 rounded bg-slate-100 text-xs">
                                        <?= $al['numero_camisa'] ? "#{$al['numero_camisa']}" : '-' ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-extrabold text-slate-900">
                                    <?= htmlspecialchars($al['nome']) ?>
                                </td>
                                <td class="py-3 px-3 text-slate-600 font-medium">
                                    <?= htmlspecialchars($al['posicao'] ?: 'Jogador') ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-500">
                                    <?= mask_sensitive_data($al['rg_ra'], $podeVerDadosSensiveis) ?>
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-500">
                                    <?= mask_sensitive_data($al['contato_emergencia'], $podeVerDadosSensiveis) ?>
                                </td>

                                <?php if ($podeGerenciar): ?>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <button type="button" onclick="abrirModalEditarAluno(<?= htmlspecialchars(json_encode($al)) ?>)" class="text-slate-400 hover:text-blue-600 transition p-1" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <a href="actions/alunos_action.php?action=excluir&id=<?= $al['id'] ?>" data-confirm="Deseja remover este atleta da turma?" data-title="Remover Atleta" class="text-slate-400 hover:text-rose-600 transition p-1" title="Excluir">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 px-4">
                <i class="fa-solid fa-user-xmark text-4xl text-slate-300 mb-2 block"></i>
                <h4 class="font-bold text-slate-700 text-sm">Nenhum atleta inscrito nesta turma</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">
                    <?php if ($podeGerenciar): ?>
                        Clique no botão "Inscrever Atleta" acima para cadastrar os jogadores da sua sala.
                    <?php else: ?>
                        O técnico responsável ainda não cadastrou os jogadores desta sala.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Partidas Disputadas pela Turma -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
        <h3 class="font-extrabold text-base text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-gamepad text-blue-600"></i>
            <span>Histórico de Jogos da Turma</span>
        </h3>

        <?php if (!empty($jogos)): ?>
            <div class="space-y-3">
                <?php foreach ($jogos as $j): 
                    $isMandante = ($j['turma_mandante_id'] == $id);
                ?>
                    <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-slate-500">
                            <span class="font-bold text-slate-700 bg-white px-2 py-1 rounded border border-slate-200">
                                <?= date('d/m/Y', strtotime($j['data_jogo'])) ?> • <?= substr($j['hora_jogo'], 0, 5) ?>
                            </span>
                            <span><?= htmlspecialchars($j['modalidade_nome']) ?></span>
                        </div>

                        <div class="flex items-center justify-center gap-3 font-bold text-sm text-slate-800">
                            <span class="<?= $isMandante ? 'text-blue-600 font-black' : '' ?>"><?= htmlspecialchars($j['m_sigla']) ?></span>
                            <?php if ($j['status'] === 'encerrado'): ?>
                                <span class="px-2 py-0.5 bg-slate-900 text-white rounded font-mono text-xs">
                                    <?= $j['placar_mandante'] ?> - <?= $j['placar_visitante'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-400 font-normal">vs</span>
                            <?php endif; ?>
                            <span class="<?= !$isMandante ? 'text-blue-600 font-black' : '' ?>"><?= htmlspecialchars($j['v_sigla']) ?></span>
                        </div>

                        <a href="partida_detalhes.php?id=<?= $j['id'] ?>" class="text-blue-600 hover:underline font-semibold">
                            Súmula <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-xs text-slate-400 text-center py-6">Esta turma ainda não possui jogos registrados no cronograma.</p>
        <?php endif; ?>
    </div>

</main>

<!-- Modais de Cadastro e Edição de Atleta -->
<?php if ($podeGerenciar): ?>
    <!-- Modal: Adicionar Atleta -->
    <div id="modal-aluno" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-blue-600"></i>
                    <span>Inscrever Atleta na Turma</span>
                </h3>
                <button type="button" onclick="fecharModalAluno()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/alunos_action.php?action=criar" method="POST" class="space-y-4">
                <input type="hidden" name="turma_id" value="<?= $turma['id'] ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo do Aluno *</label>
                    <input type="text" name="nome" placeholder="Ex: Gabriel Salles" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Número da Camisa</label>
                        <input type="number" name="numero_camisa" min="1" max="99" placeholder="Ex: 10" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Posição / Função</label>
                        <input type="text" name="posicao" placeholder="Ex: Goleiro, Ala, Fixo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">RG ou RA Escolar (Dado Protegido)</label>
                    <input type="text" name="rg_ra" placeholder="Ex: RA-123456-SP" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Contato de Emergência (Dado Protegido)</label>
                    <input type="text" name="contato_emergencia" placeholder="Ex: (11) 98888-7777 (Mãe)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharModalAluno()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Inscrever Atleta
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Atleta -->
    <div id="modal-editar-aluno" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-amber-500"></i>
                    <span>Editar Atleta</span>
                </h3>
                <button type="button" onclick="fecharModalEditarAluno()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/alunos_action.php?action=editar" method="POST" class="space-y-4">
                <input type="hidden" name="id" id="edit-aluno-id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo *</label>
                    <input type="text" name="nome" id="edit-aluno-nome" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Número Camisa</label>
                        <input type="number" name="numero_camisa" id="edit-aluno-camisa" min="1" max="99" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Posição</label>
                        <input type="text" name="posicao" id="edit-aluno-posicao" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">RG / RA Escolar</label>
                    <input type="text" name="rg_ra" id="edit-aluno-rg" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Contato de Emergência</label>
                    <input type="text" name="contato_emergencia" id="edit-aluno-contato" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharModalEditarAluno()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
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
    function abrirModalAluno() {
        document.getElementById('modal-aluno').classList.remove('hidden');
    }
    function fecharModalAluno() {
        document.getElementById('modal-aluno').classList.add('hidden');
    }

    function abrirModalEditarAluno(al) {
        document.getElementById('edit-aluno-id').value = al.id;
        document.getElementById('edit-aluno-nome').value = al.nome;
        document.getElementById('edit-aluno-camisa').value = al.numero_camisa || '';
        document.getElementById('edit-aluno-posicao').value = al.posicao || '';
        document.getElementById('edit-aluno-rg').value = al.rg_ra || '';
        document.getElementById('edit-aluno-contato').value = al.contato_emergencia || '';
        document.getElementById('modal-editar-aluno').classList.remove('hidden');
    }
    function fecharModalEditarAluno() {
        document.getElementById('modal-editar-aluno').classList.add('hidden');
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
