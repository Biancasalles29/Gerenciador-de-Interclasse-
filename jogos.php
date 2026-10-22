<?php
/**
 * Cronograma Oficial de Partidas e Gestão de Jogos
 * 
 * Regras CORE:
 * - Horário, local e data obrigatórios em cada partida
 * - Equipes vinculadas a turmas
 * - Choque de horário bloqueado no backend
 * - Alterações no cronograma geram comunicado oficial
 */

$pageTitle = 'Partidas & Cronograma Oficial';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];

// Filtros
$filtroModalidade = isset($_GET['modalidade_id']) ? (int)$_GET['modalidade_id'] : 0;
$filtroStatus = trim($_GET['status'] ?? '');
$filtroData = trim($_GET['data'] ?? '');

// Buscar Modalidades e Turmas para os filtros e formulário de agendamento
$stmtMod = $pdo->prepare("SELECT * FROM modalidades WHERE edicao_id = :id ORDER BY nome ASC");
$stmtMod->execute([':id' => $edicaoId]);
$modalidades = $stmtMod->fetchAll();

$stmtTurmas = $pdo->prepare("SELECT id, nome, sigla, cor_uniforme FROM turmas WHERE edicao_id = :id ORDER BY sigla ASC");
$stmtTurmas->execute([':id' => $edicaoId]);
$turmas = $stmtTurmas->fetchAll();

// Montar query de partidas com filtros dinâmicos
$sql = "
    SELECT p.*, 
           tm.sigla AS m_sigla, tm.nome AS m_nome, tm.cor_uniforme AS m_cor,
           tv.sigla AS v_sigla, tv.nome AS v_nome, tv.cor_uniforme AS v_cor,
           m.nome AS modalidade_nome, m.icone AS modalidade_icone
    FROM partidas p
    JOIN turmas tm ON tm.id = p.turma_mandante_id
    JOIN turmas tv ON tv.id = p.turma_visitante_id
    JOIN modalidades m ON m.id = p.modalidade_id
    WHERE p.edicao_id = :edicao_id
";

$params = [':edicao_id' => $edicaoId];

if ($filtroModalidade > 0) {
    $sql .= " AND p.modalidade_id = :modalidade_id";
    $params[':modalidade_id'] = $filtroModalidade;
}

if (!empty($filtroStatus)) {
    $sql .= " AND p.status = :status";
    $params[':status'] = $filtroStatus;
}

if (!empty($filtroData)) {
    $sql .= " AND p.data_jogo = :data_jogo";
    $params[':data_jogo'] = $filtroData;
}

$sql .= " ORDER BY p.data_jogo ASC, p.hora_jogo ASC";

$stmtPartidas = $pdo->prepare($sql);
$stmtPartidas->execute($params);
$partidas = $stmtPartidas->fetchAll();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 mb-1">
                <span><?= htmlspecialchars($edicaoAtiva['nome']) ?></span>
                <span>•</span>
                <span>Calendário Oficial</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Partidas & Cronograma
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Datas, horários e locais de todos os confrontos do torneio.
            </p>
        </div>

        <?php if (is_admin()): ?>
            <div>
                <button type="button" onclick="abrirModalAgendamento()" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-xl transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Agendar Nova Partida</span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Barra de Filtros Responsiva -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6">
        <form method="GET" action="jogos.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            
            <!-- Filtro Modalidade -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Modalidade</label>
                <select name="modalidade_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="0">Todas as Modalidades</option>
                    <?php foreach ($modalidades as $mod): ?>
                        <option value="<?= $mod['id'] ?>" <?= $filtroModalidade == $mod['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro Status -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos os Status</option>
                    <option value="agendado" <?= $filtroStatus === 'agendado' ? 'selected' : '' ?>>Agendados</option>
                    <option value="em_andamento" <?= $filtroStatus === 'em_andamento' ? 'selected' : '' ?>>Ao Vivo / Em Andamento</option>
                    <option value="encerrado" <?= $filtroStatus === 'encerrado' ? 'selected' : '' ?>>Encerrados</option>
                    <option value="cancelado" <?= $filtroStatus === 'cancelado' ? 'selected' : '' ?>>Cancelados</option>
                </select>
            </div>

            <!-- Filtro Data -->
            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Data</label>
                <input type="date" name="data" value="<?= htmlspecialchars($filtroData) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Botões de Ação -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition text-center shadow-sm">
                    <i class="fa-solid fa-filter mr-1"></i> Filtrar
                </button>
                <a href="jogos.php" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs rounded-xl transition text-center">
                    Limpar
                </a>
            </div>

        </form>
    </div>

    <!-- Lista de Partidas -->
    <?php if (!empty($partidas)): ?>
        <div class="space-y-4">
            <?php foreach ($partidas as $p): 
                $isEncerrado = ($p['status'] === 'encerrado');
                $isAoVivo = ($p['status'] === 'em_andamento');
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition p-4 sm:p-5 flex flex-col md:flex-row items-center justify-between gap-4">
                    
                    <!-- Informações de Cronograma (Data, Horário, Local) -->
                    <div class="w-full md:w-56 flex-shrink-0 text-left border-b md:border-b-0 md:border-r border-slate-100 pb-3 md:pb-0 md:pr-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-800 mb-1">
                            <i class="fa-solid <?= htmlspecialchars($p['modalidade_icone']) ?> text-blue-600"></i>
                            <span><?= htmlspecialchars($p['modalidade_nome']) ?></span>
                        </div>
                        <div class="text-xs text-slate-600 flex items-center gap-1.5 font-medium mb-1">
                            <i class="fa-regular fa-calendar text-slate-400"></i>
                            <span><?= date('d/m/Y', strtotime($p['data_jogo'])) ?></span>
                            <span class="text-slate-300">•</span>
                            <i class="fa-regular fa-clock text-slate-400"></i>
                            <span class="font-bold text-slate-800"><?= substr($p['hora_jogo'], 0, 5) ?></span>
                        </div>
                        <div class="text-[11px] text-slate-500 truncate flex items-center gap-1.5">
                            <i class="fa-solid fa-location-dot text-rose-500"></i>
                            <span title="<?= htmlspecialchars($p['local_jogo']) ?>"><?= htmlspecialchars($p['local_jogo']) ?></span>
                        </div>
                        <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full <?= match($p['status']) {
                            'encerrado' => 'bg-slate-100 text-slate-600 border border-slate-200',
                            'em_andamento' => 'bg-emerald-100 text-emerald-700 animate-pulse border border-emerald-300',
                            'cancelado' => 'bg-rose-100 text-rose-700 border border-rose-200',
                            default => 'bg-blue-100 text-blue-700 border border-blue-200'
                        } ?>">
                            <?= match($p['status']) {
                                'encerrado' => 'Encerrado',
                                'em_andamento' => 'Ao Vivo',
                                'cancelado' => 'Cancelado',
                                default => 'Agendado'
                            } ?>
                        </span>
                    </div>

                    <!-- Confronto / Placar -->
                    <div class="flex-1 w-full flex items-center justify-between sm:justify-center gap-4 sm:gap-8 px-2">
                        
                        <!-- Mandante -->
                        <div class="flex-1 flex items-center justify-end gap-2.5 text-right">
                            <div>
                                <span class="font-black text-sm sm:text-base text-slate-900 block"><?= htmlspecialchars($p['m_sigla']) ?></span>
                                <span class="text-[10px] text-slate-400 hidden sm:block truncate max-w-[140px]"><?= htmlspecialchars($p['m_nome']) ?></span>
                            </div>
                            <span class="w-4 h-4 rounded-full flex-shrink-0 shadow-sm" style="background-color: <?= htmlspecialchars($p['m_cor']) ?>;"></span>
                        </div>

                        <!-- Placar ou VS -->
                        <div class="flex flex-col items-center justify-center min-w-[70px]">
                            <?php if ($isEncerrado || $isAoVivo): ?>
                                <div class="px-3.5 py-1.5 bg-slate-900 text-white rounded-xl font-black text-base sm:text-lg tracking-widest shadow-md">
                                    <?= (int)$p['placar_mandante'] ?> - <?= (int)$p['placar_visitante'] ?>
                                </div>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg font-black text-xs tracking-wider">
                                    VS
                                </span>
                            <?php endif; ?>
                            <span class="text-[10px] text-slate-400 mt-1 uppercase font-semibold"><?= htmlspecialchars($p['fase']) ?></span>
                        </div>

                        <!-- Visitante -->
                        <div class="flex-1 flex items-center justify-start gap-2.5 text-left">
                            <span class="w-4 h-4 rounded-full flex-shrink-0 shadow-sm" style="background-color: <?= htmlspecialchars($p['v_cor']) ?>;"></span>
                            <div>
                                <span class="font-black text-sm sm:text-base text-slate-900 block"><?= htmlspecialchars($p['v_sigla']) ?></span>
                                <span class="text-[10px] text-slate-400 hidden sm:block truncate max-w-[140px]"><?= htmlspecialchars($p['v_nome']) ?></span>
                            </div>
                        </div>

                    </div>

                    <!-- Ações -->
                    <div class="w-full md:w-auto flex items-center justify-end gap-2 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                        <a href="partida_detalhes.php?id=<?= $p['id'] ?>" class="flex-1 sm:flex-none px-3.5 py-2 bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 text-xs font-bold rounded-xl transition text-center shadow-sm">
                            <i class="fa-solid fa-clipboard-list mr-1"></i> Súmula
                        </a>

                        <?php if (is_admin()): ?>
                            <!-- Botão Lançar Placar Rápido -->
                            <button type="button" onclick="abrirModalPlacar(<?= htmlspecialchars(json_encode($p)) ?>)" class="px-3 py-2 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 text-xs font-bold rounded-xl transition" title="Lançar Resultado">
                                <i class="fa-solid fa-futbol"></i>
                            </button>

                            <!-- Botão Editar Cronograma -->
                            <button type="button" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($p)) ?>)" class="px-3 py-2 bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 text-xs font-bold rounded-xl transition" title="Alterar Horário/Local">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>

                            <!-- Excluir com confirmação modal -->
                            <a href="actions/partidas_action.php?action=excluir&id=<?= $p['id'] ?>" data-confirm="Tem certeza que deseja excluir esta partida do cronograma?" data-title="Excluir Partida" class="px-3 py-2 bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 text-xs font-bold rounded-xl transition" title="Excluir">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300 mb-3 block"></i>
            <h3 class="text-base font-bold text-slate-700">Nenhuma partida encontrada</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                Não há confrontos agendados para os filtros selecionados. Tente limpar os filtros ou agendar uma nova partida.
            </p>
        </div>
    <?php endif; ?>

</main>

<!-- ============================================================== -->
<!-- MODAIS ADMINISTRATIVOS (Agendar, Editar Cronograma e Lançar Placar) -->
<!-- ============================================================== -->
<?php if (is_admin()): ?>
    
    <!-- Modal 1: Agendar Nova Partida -->
    <div id="modal-agendar" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-plus text-blue-600"></i>
                    <span>Agendar Nova Partida</span>
                </h3>
                <button type="button" onclick="fecharModalAgendar()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/partidas_action.php?action=criar" method="POST" class="space-y-4">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Modalidade *</label>
                    <select name="modalidade_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione a modalidade...</option>
                        <?php foreach ($modalidades as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mandante *</label>
                        <select name="turma_mandante_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['sigla']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Visitante *</label>
                        <select name="turma_visitante_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['sigla']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Data *</label>
                        <input type="date" name="data_jogo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Horário *</label>
                        <input type="time" name="hora_jogo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Local do Jogo *</label>
                    <input type="text" name="local_jogo" placeholder="Ex: Quadra 1, Ginásio Poliesportivo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fase</label>
                        <input type="text" name="fase" value="Fase de Grupos" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Observações</label>
                        <input type="text" name="observacoes" placeholder="Opcional" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="p-3 bg-blue-50 rounded-xl text-[11px] text-blue-700 leading-relaxed border border-blue-200">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    <strong>Regra Anti-Choque:</strong> O sistema bloqueará automaticamente caso alguma das turmas já possua jogo no mesmo horário.
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" onclick="fecharModalAgendar()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Salvar Partida
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Editar Partida / Alterar Cronograma -->
    <div id="modal-editar" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-500"></i>
                    <span>Editar Partida / Cronograma</span>
                </h3>
                <button type="button" onclick="fecharModalEditar()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/partidas_action.php?action=editar" method="POST" class="space-y-4">
                <input type="hidden" name="id" id="edit-id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Modalidade *</label>
                    <select name="modalidade_id" id="edit-modalidade-id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                        <?php foreach ($modalidades as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mandante *</label>
                        <select name="turma_mandante_id" id="edit-mandante-id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['sigla']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Visitante *</label>
                        <select name="turma_visitante_id" id="edit-visitante-id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['sigla']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Data *</label>
                        <input type="date" name="data_jogo" id="edit-data-jogo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Horário *</label>
                        <input type="time" name="hora_jogo" id="edit-hora-jogo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Local do Jogo *</label>
                    <input type="text" name="local_jogo" id="edit-local-jogo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fase</label>
                        <input type="text" name="fase" id="edit-fase" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                        <select name="status" id="edit-status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                            <option value="agendado">Agendado</option>
                            <option value="em_andamento">Em Andamento</option>
                            <option value="encerrado">Encerrado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl text-[11px] text-amber-800 leading-relaxed border border-amber-200">
                    <i class="fa-solid fa-bullhorn mr-1 text-amber-600"></i>
                    <strong>Comunicação Automática:</strong> Qualquer alteração na data, horário ou local gerará um aviso público automático no Mural de Comunicados!
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" onclick="fecharModalEditar()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Lançar Placar Rápido -->
    <div id="modal-placar" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-futbol text-emerald-600"></i>
                    <span>Lançar / Atualizar Placar</span>
                </h3>
                <button type="button" onclick="fecharModalPlacar()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/resultados_action.php?action=lancar" method="POST" class="space-y-4">
                <input type="hidden" name="partida_id" id="placar-partida-id">

                <div class="flex items-center justify-around my-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="text-center">
                        <label id="placar-label-mandante" class="block font-black text-sm text-slate-900 mb-1">MANDANTE</label>
                        <input type="number" name="placar_mandante" id="placar-mandante" min="0" required class="w-20 text-center font-black text-2xl bg-white border border-slate-300 rounded-xl py-2 focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <span class="text-lg font-bold text-slate-400">X</span>
                    <div class="text-center">
                        <label id="placar-label-visitante" class="block font-black text-sm text-slate-900 mb-1">VISITANTE</label>
                        <input type="number" name="placar_visitante" id="placar-visitante" min="0" required class="w-20 text-center font-black text-2xl bg-white border border-slate-300 rounded-xl py-2 focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status da Partida</label>
                    <select name="status" id="placar-status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">
                        <option value="encerrado">Encerrado (Final da Partida)</option>
                        <option value="em_andamento">Em Andamento (Ao Vivo)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Observações do Árbitro / Súmula</label>
                    <textarea name="observacoes" id="placar-obs" rows="2" placeholder="Opcional..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 text-xs text-slate-800"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharModalPlacar()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Salvar Placar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalAgendamento() {
        document.getElementById('modal-agendar').classList.remove('hidden');
    }
    function fecharModalAgendar() {
        document.getElementById('modal-agendar').classList.add('hidden');
    }

    function abrirModalEditar(partida) {
        document.getElementById('edit-id').value = partida.id;
        document.getElementById('edit-modalidade-id').value = partida.modalidade_id;
        document.getElementById('edit-mandante-id').value = partida.turma_mandante_id;
        document.getElementById('edit-visitante-id').value = partida.turma_visitante_id;
        document.getElementById('edit-data-jogo').value = partida.data_jogo;
        document.getElementById('edit-hora-jogo').value = partida.hora_jogo.substring(0, 5);
        document.getElementById('edit-local-jogo').value = partida.local_jogo;
        document.getElementById('edit-fase').value = partida.fase;
        document.getElementById('edit-status').value = partida.status;
        document.getElementById('modal-editar').classList.remove('hidden');
    }
    function fecharModalEditar() {
        document.getElementById('modal-editar').classList.add('hidden');
    }

    function abrirModalPlacar(partida) {
        document.getElementById('placar-partida-id').value = partida.id;
        document.getElementById('placar-label-mandante').textContent = partida.m_sigla;
        document.getElementById('placar-label-visitante').textContent = partida.v_sigla;
        document.getElementById('placar-mandante').value = partida.placar_mandante !== null ? partida.placar_mandante : 0;
        document.getElementById('placar-visitante').value = partida.placar_visitante !== null ? partida.placar_visitante : 0;
        document.getElementById('placar-status').value = partida.status === 'agendado' ? 'encerrado' : partida.status;
        document.getElementById('placar-obs').value = partida.observacoes || '';
        document.getElementById('modal-placar').classList.remove('hidden');
    }
    function fecharModalPlacar() {
        document.getElementById('modal-placar').classList.add('hidden');
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
