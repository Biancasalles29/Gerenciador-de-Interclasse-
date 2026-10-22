<?php
/**
 * Mural Oficial de Comunicados & Alterações de Cronograma
 * 
 * Regra CORE:
 * - Alterações no cronograma DEVEM ser comunicadas dentro do site
 * - Avisos urgentes e comunicados gerais
 */

$pageTitle = 'Mural de Comunicados';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];
$filtroTipo = trim($_GET['tipo'] ?? '');

$sql = "
    SELECT c.*, u.nome AS autor_nome, u.perfil AS autor_perfil,
           p.data_jogo, p.hora_jogo, p.local_jogo,
           tm.sigla AS m_sigla, tv.sigla AS v_sigla
    FROM comunicados c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    LEFT JOIN partidas p ON p.id = c.partida_id
    LEFT JOIN turmas tm ON tm.id = p.turma_mandante_id
    LEFT JOIN turmas tv ON tv.id = p.turma_visitante_id
    WHERE c.edicao_id = :edicao_id
";

$params = [':edicao_id' => $edicaoId];

if (!empty($filtroTipo) && in_array($filtroTipo, ['urgente', 'cronograma', 'geral'])) {
    $sql .= " AND c.tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}

$sql .= " ORDER BY (c.tipo = 'urgente') DESC, c.criado_em DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comunicados = $stmt->fetchAll();
?>

<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 mb-1">
                <span><?= htmlspecialchars($edicaoAtiva['nome']) ?></span>
                <span>•</span>
                <span>Informativo Oficial</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Mural de Comunicados
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Avisos oficiais, informes urgentes e todas as alterações de cronograma do torneio.
            </p>
        </div>

        <?php if (is_admin() || is_tecnico()): ?>
            <div>
                <button type="button" onclick="abrirModalComunicado()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Publicar Comunicado</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filtros de Tipo -->
    <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-6 scrollbar-none border-b border-slate-200">
        <a href="comunicados.php" class="px-3.5 py-2 rounded-xl text-xs font-bold transition <?= empty($filtroTipo) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            Todos os Avisos
        </a>
        <a href="comunicados.php?tipo=cronograma" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $filtroTipo === 'cronograma' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            <i class="fa-solid fa-clock-rotate-left text-xs"></i>
            <span>Alterações de Cronograma</span>
        </a>
        <a href="comunicados.php?tipo=urgente" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= $filtroTipo === 'urgente' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            <i class="fa-solid fa-triangle-exclamation text-xs"></i>
            <span>Urgentes</span>
        </a>
        <a href="comunicados.php?tipo=geral" class="px-3.5 py-2 rounded-xl text-xs font-bold transition <?= $filtroTipo === 'geral' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
            Informativos Gerais
        </a>
    </div>

    <!-- Lista de Comunicados -->
    <?php if (!empty($comunicados)): ?>
        <div class="space-y-4">
            <?php foreach ($comunicados as $com): 
                $isUrgente = ($com['tipo'] === 'urgente');
                $isCronograma = ($com['tipo'] === 'cronograma');
            ?>
                <div class="bg-white rounded-2xl border p-5 sm:p-6 shadow-sm transition hover:shadow-md <?= $isUrgente ? 'border-rose-200 bg-rose-50/20' : ($isCronograma ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200') ?>">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider <?= match($com['tipo']) {
                                'urgente' => 'bg-rose-100 text-rose-700 border border-rose-300',
                                'cronograma' => 'bg-amber-100 text-amber-700 border border-amber-300',
                                default => 'bg-blue-100 text-blue-700 border border-blue-200'
                            } ?>">
                                <i class="fa-solid <?= match($com['tipo']) {
                                    'urgente' => 'fa-circle-exclamation',
                                    'cronograma' => 'fa-clock-rotate-left',
                                    default => 'fa-circle-info'
                                } ?>"></i>
                                <?= match($com['tipo']) {
                                    'urgente' => 'Urgente',
                                    'cronograma' => 'Alteração de Cronograma',
                                    default => 'Comunicado Oficial'
                                } ?>
                            </span>

                            <span class="text-xs text-slate-400">
                                <?= date('d/m/Y \à\s H:i', strtotime($com['criado_em'])) ?>
                            </span>
                        </div>

                        <?php if (is_admin()): ?>
                            <div>
                                <a href="actions/comunicados_action.php?action=excluir&id=<?= $com['id'] ?>" data-confirm="Deseja remover este comunicado do mural?" data-title="Excluir Comunicado" class="text-slate-400 hover:text-rose-600 text-xs transition p-1" title="Excluir">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2 class="text-base sm:text-lg font-black text-slate-900 mb-2 leading-snug">
                        <?= htmlspecialchars($com['titulo']) ?>
                    </h2>

                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line mb-4">
                        <?= htmlspecialchars($com['conteudo']) ?>
                    </p>

                    <!-- Se o comunicado tiver partida associada -->
                    <?php if ($com['partida_id'] && $com['m_sigla']): ?>
                        <div class="p-3 bg-white rounded-xl border border-slate-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 shadow-inner">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900"><?= htmlspecialchars($com['m_sigla']) ?> vs <?= htmlspecialchars($com['v_sigla']) ?></span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600"><?= date('d/m/Y', strtotime($com['data_jogo'])) ?> às <?= substr($com['hora_jogo'], 0, 5) ?></span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-500 truncate"><?= htmlspecialchars($com['local_jogo']) ?></span>
                            </div>
                            <a href="partida_detalhes.php?id=<?= $com['partida_id'] ?>" class="text-blue-600 hover:underline font-bold text-xs inline-flex items-center gap-1">
                                Ver Súmula da Partida <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-solid fa-user-pen text-slate-400"></i>
                            Publicado por: <strong class="text-slate-700"><?= htmlspecialchars($com['autor_nome'] ?? 'Comissão Organizadora') ?></strong>
                        </span>
                        <span class="text-slate-400">Canal Oficial Interclasse</span>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i class="fa-solid fa-envelope-open-text text-4xl text-slate-300 mb-3 block"></i>
            <h3 class="text-base font-bold text-slate-700">Nenhum comunicado encontrado</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                Fique atento! Novas alterações de horários e avisos oficiais serão publicados aqui.
            </p>
        </div>
    <?php endif; ?>

</main>

<!-- Modal: Novo Comunicado -->
<?php if (is_admin() || is_tecnico()): ?>
    <div id="modal-comunicado" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-bullhorn text-blue-600"></i>
                    <span>Publicar Novo Comunicado</span>
                </h3>
                <button type="button" onclick="fecharModalComunicado()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/comunicados_action.php?action=criar" method="POST" class="space-y-4">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Título do Comunicado *</label>
                    <input type="text" name="titulo" placeholder="Ex: Alteração do local das finais de Futsal" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tipo de Aviso *</label>
                    <select name="tipo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                        <option value="geral">Informativo Geral</option>
                        <option value="cronograma">Alteração de Cronograma</option>
                        <option value="urgente">Aviso Urgente (Destaque Vermelho)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Conteúdo da Mensagem *</label>
                    <textarea name="conteudo" rows="4" placeholder="Escreva os detalhes e orientações para os alunos e técnicos..." required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharModalComunicado()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Publicar Comunicado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalComunicado() {
        document.getElementById('modal-comunicado').classList.remove('hidden');
    }
    function fecharModalComunicado() {
        document.getElementById('modal-comunicado').classList.add('hidden');
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
