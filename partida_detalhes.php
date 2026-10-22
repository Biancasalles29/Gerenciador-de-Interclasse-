<?php
/**
 * Detalhes da Partida e Súmula Oficial
 * 
 * Exibe lances em tempo real:
 * - Gols / Pontos
 * - Assistências
 * - Cartões Amarelos e Vermelhos
 * - Escalação dos atletas
 */

$pageTitle = 'Súmula da Partida';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// Buscar detalhes da partida
$stmtPartida = $pdo->prepare("
    SELECT p.*, 
           tm.sigla AS m_sigla, tm.nome AS m_nome, tm.cor_uniforme AS m_cor, tm.tecnico_id AS m_tecnico_id,
           tv.sigla AS v_sigla, tv.nome AS v_nome, tv.cor_uniforme AS v_cor, tv.tecnico_id AS v_tecnico_id,
           m.nome AS modalidade_nome, m.icone AS modalidade_icone
    FROM partidas p
    JOIN turmas tm ON tm.id = p.turma_mandante_id
    JOIN turmas tv ON tv.id = p.turma_visitante_id
    JOIN modalidades m ON m.id = p.modalidade_id
    WHERE p.id = :id
");
$stmtPartida->execute([':id' => $id]);
$partida = $stmtPartida->fetch();

if (!$partida) {
    set_flash_message('error', 'Partida não encontrada.');
    header('Location: jogos.php');
    exit;
}

// Buscar eventos da súmula (gols, assistências, cartões)
$stmtEventos = $pdo->prepare("
    SELECT se.*, a.nome AS aluno_nome, a.numero_camisa, t.sigla AS turma_sigla, t.cor_uniforme 
    FROM sumula_eventos se
    JOIN alunos a ON a.id = se.aluno_id
    JOIN turmas t ON t.id = se.turma_id
    WHERE se.partida_id = :id
    ORDER BY se.minuto ASC, se.id ASC
");
$stmtEventos->execute([':id' => $id]);
$eventos = $stmtEventos->fetchAll();

// Buscar elenco de ambas as turmas
$stmtMandanteAlunos = $pdo->prepare("SELECT id, nome, numero_camisa, posicao FROM alunos WHERE turma_id = :id AND ativo = 1 ORDER BY numero_camisa ASC, nome ASC");
$stmtMandanteAlunos->execute([':id' => $partida['turma_mandante_id']]);
$mandanteAlunos = $stmtMandanteAlunos->fetchAll();

$stmtVisitanteAlunos = $pdo->prepare("SELECT id, nome, numero_camisa, posicao FROM alunos WHERE turma_id = :id AND ativo = 1 ORDER BY numero_camisa ASC, nome ASC");
$stmtVisitanteAlunos->execute([':id' => $partida['turma_visitante_id']]);
$visitanteAlunos = $stmtVisitanteAlunos->fetchAll();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Breadcrumb e Voltar -->
    <div class="mb-4">
        <a href="jogos.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-blue-600 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Voltar para Lista de Jogos</span>
        </a>
    </div>

    <!-- Placar Principal e Card de Duelo -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-700/80 mb-8 relative overflow-hidden">
        
        <!-- Fundo decorativo -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700 text-slate-300 text-xs font-semibold">
                <i class="fa-solid <?= htmlspecialchars($partida['modalidade_icone']) ?> text-blue-400"></i>
                <?= htmlspecialchars($partida['modalidade_nome']) ?> • <?= htmlspecialchars($partida['fase']) ?>
            </span>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 max-w-3xl mx-auto">
            
            <!-- Mandante -->
            <div class="flex-1 text-center sm:text-right">
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto sm:ml-auto sm:mr-0 rounded-2xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg mb-3" style="background-color: <?= htmlspecialchars($partida['m_cor']) ?>;">
                    <?= substr($partida['m_sigla'], 0, 3) ?>
                </div>
                <h2 class="text-lg sm:text-2xl font-black tracking-tight text-white"><?= htmlspecialchars($partida['m_sigla']) ?></h2>
                <span class="text-xs text-slate-400 block truncate"><?= htmlspecialchars($partida['m_nome']) ?></span>
            </div>

            <!-- Placar Central -->
            <div class="flex flex-col items-center justify-center px-4">
                <?php if ($partida['status'] === 'encerrado' || $partida['status'] === 'em_andamento'): ?>
                    <div class="px-6 py-2.5 bg-slate-950/80 border border-slate-700 rounded-2xl font-black text-3xl sm:text-4xl tracking-widest text-white shadow-inner">
                        <?= (int)$partida['placar_mandante'] ?> - <?= (int)$partida['placar_visitante'] ?>
                    </div>
                <?php else: ?>
                    <div class="px-5 py-2 bg-slate-800/80 border border-slate-700 rounded-2xl font-black text-xl text-amber-400">
                        VS
                    </div>
                <?php endif; ?>

                <span class="mt-2 text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full <?= match($partida['status']) {
                    'encerrado' => 'bg-slate-800 text-slate-300 border border-slate-700',
                    'em_andamento' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-pulse',
                    default => 'bg-blue-500/20 text-blue-300 border border-blue-500/30'
                } ?>">
                    <?= match($partida['status']) {
                        'encerrado' => 'Encerrado',
                        'em_andamento' => 'Ao Vivo',
                        default => 'Agendado'
                    } ?>
                </span>
            </div>

            <!-- Visitante -->
            <div class="flex-1 text-center sm:text-left">
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto sm:mr-auto sm:ml-0 rounded-2xl flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-lg mb-3" style="background-color: <?= htmlspecialchars($partida['v_cor']) ?>;">
                    <?= substr($partida['v_sigla'], 0, 3) ?>
                </div>
                <h2 class="text-lg sm:text-2xl font-black tracking-tight text-white"><?= htmlspecialchars($partida['v_sigla']) ?></h2>
                <span class="text-xs text-slate-400 block truncate"><?= htmlspecialchars($partida['v_nome']) ?></span>
            </div>

        </div>

        <!-- Rodapé do Card: Data, Hora e Local Obrigatórios -->
        <div class="mt-8 pt-4 border-t border-slate-800 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
                <i class="fa-regular fa-calendar text-blue-400"></i>
                <?= date('d/m/Y', strtotime($partida['data_jogo'])) ?>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1.5">
                <i class="fa-regular fa-clock text-blue-400"></i>
                <?= substr($partida['hora_jogo'], 0, 5) ?>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-location-dot text-rose-400"></i>
                <?= htmlspecialchars($partida['local_jogo']) ?>
            </span>
        </div>

        <?php if (!empty($partida['observacoes'])): ?>
            <div class="mt-3 text-center text-xs text-slate-400 italic">
                "<?= htmlspecialchars($partida['observacoes']) ?>"
            </div>
        <?php endif; ?>

    </div>

    <!-- Grid: Súmula de Eventos e Lançador de Lances -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Coluna 1 & 2: Linha do Tempo da Súmula -->
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <h3 class="text-base sm:text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-timeline text-blue-600"></i>
                        <span>Súmula Oficial: Lances & Estatísticas</span>
                    </h3>
                    <span class="text-xs text-slate-500 font-semibold"><?= count($eventos) ?> registros</span>
                </div>

                <?php if (!empty($eventos)): ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($eventos as $ev): ?>
                            <div class="py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 rounded-xl px-2 transition">
                                
                                <div class="flex items-center gap-3">
                                    <!-- Ícone do Evento -->
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base flex-shrink-0 <?= match($ev['tipo']) {
                                        'gol_ponto' => 'bg-emerald-100 text-emerald-700',
                                        'assistencia' => 'bg-blue-100 text-blue-700',
                                        'cartao_amarelo' => 'bg-amber-100 text-amber-600',
                                        'cartao_vermelho' => 'bg-rose-100 text-rose-700',
                                        default => 'bg-slate-100 text-slate-600'
                                    } ?>">
                                        <i class="fa-solid <?= match($ev['tipo']) {
                                            'gol_ponto' => 'fa-futbol',
                                            'assistencia' => 'fa-hands-clapping',
                                            'cartao_amarelo' => 'fa-square',
                                            'cartao_vermelho' => 'fa-square',
                                            default => 'fa-circle-dot'
                                        } ?>"></i>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-sm text-slate-900"><?= htmlspecialchars($ev['aluno_nome']) ?></span>
                                            <?php if ($ev['numero_camisa']): ?>
                                                <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">#<?= $ev['numero_camisa'] ?></span>
                                            <?php endif; ?>
                                            <span class="text-xs font-semibold text-slate-500">• <?= htmlspecialchars($ev['turma_sigla']) ?></span>
                                        </div>

                                        <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                                            <span class="font-bold text-slate-700 uppercase tracking-wide text-[10px]">
                                                <?= match($ev['tipo']) {
                                                    'gol_ponto' => '⚽ Gol / Ponto',
                                                    'assistencia' => '🎯 Assistência',
                                                    'cartao_amarelo' => '🟨 Cartão Amarelo',
                                                    'cartao_vermelho' => '🟥 Cartão Vermelho',
                                                    default => 'Lance'
                                                } ?>
                                            </span>
                                            <?php if ($ev['minuto']): ?>
                                                <span>aos <?= $ev['minuto'] ?>'</span>
                                            <?php endif; ?>
                                            <?php if (!empty($ev['observacao'])): ?>
                                                <span class="text-slate-400 italic">"<?= htmlspecialchars($ev['observacao']) ?>"</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if (is_admin()): ?>
                                    <form action="actions/sumula_action.php?action=remover_evento" method="POST" class="inline">
                                        <input type="hidden" name="evento_id" value="<?= $ev['id'] ?>">
                                        <input type="hidden" name="partida_id" value="<?= $partida['id'] ?>">
                                        <button type="submit" data-confirm="Deseja remover este registro da súmula?" data-title="Remover Lance" class="text-slate-400 hover:text-rose-600 p-1.5 rounded-lg transition" title="Remover Lance">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-file-pen text-3xl mb-2 text-slate-300 block"></i>
                        <p class="text-xs">Nenhum evento registrado na súmula desta partida ainda.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Escalação das Turmas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Elenco Mandante -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                        <span class="w-3.5 h-3.5 rounded-full" style="background-color: <?= $partida['m_cor'] ?>;"></span>
                        <h4 class="font-bold text-sm text-slate-800 truncate"><?= htmlspecialchars($partida['m_sigla']) ?> (Elenco)</h4>
                    </div>
                    <?php if (!empty($mandanteAlunos)): ?>
                        <ul class="divide-y divide-slate-100 text-xs">
                            <?php foreach ($mandanteAlunos as $al): ?>
                                <li class="py-1.5 flex items-center justify-between">
                                    <span class="text-slate-700 font-medium">
                                        <strong class="text-slate-900 w-5 inline-block">#<?= $al['numero_camisa'] ?? '-' ?></strong>
                                        <?= htmlspecialchars($al['nome']) ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($al['posicao'] ?? '') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-xs text-slate-400">Nenhum atleta cadastrado.</p>
                    <?php endif; ?>
                </div>

                <!-- Elenco Visitante -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
                        <span class="w-3.5 h-3.5 rounded-full" style="background-color: <?= $partida['v_cor'] ?>;"></span>
                        <h4 class="font-bold text-sm text-slate-800 truncate"><?= htmlspecialchars($partida['v_sigla']) ?> (Elenco)</h4>
                    </div>
                    <?php if (!empty($visitanteAlunos)): ?>
                        <ul class="divide-y divide-slate-100 text-xs">
                            <?php foreach ($visitanteAlunos as $al): ?>
                                <li class="py-1.5 flex items-center justify-between">
                                    <span class="text-slate-700 font-medium">
                                        <strong class="text-slate-900 w-5 inline-block">#<?= $al['numero_camisa'] ?? '-' ?></strong>
                                        <?= htmlspecialchars($al['nome']) ?>
                                    </span>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($al['posicao'] ?? '') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-xs text-slate-400">Nenhum atleta cadastrado.</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <!-- Coluna 3: Painel de Controle da Súmula (Admin) -->
        <div>
            <?php if (is_admin()): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 sticky top-20">
                    <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-pen-nib text-blue-600"></i>
                        <span>Registrar Lance na Súmula</span>
                    </h3>

                    <form action="actions/sumula_action.php?action=adicionar_evento" method="POST" class="space-y-4">
                        <input type="hidden" name="partida_id" value="<?= $partida['id'] ?>">

                        <!-- Escolha da Turma -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Turma do Atleta *</label>
                            <select name="turma_id" id="sumula-turma" required onchange="filtrarAlunos(this.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione a turma...</option>
                                <option value="<?= $partida['turma_mandante_id'] ?>"><?= htmlspecialchars($partida['m_sigla']) ?></option>
                                <option value="<?= $partida['turma_visitante_id'] ?>"><?= htmlspecialchars($partida['v_sigla']) ?></option>
                            </select>
                        </div>

                        <!-- Escolha do Atleta -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Atleta / Aluno *</label>
                            <select name="aluno_id" id="sumula-aluno" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione a turma primeiro...</option>
                            </select>
                        </div>

                        <!-- Tipo de Evento -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tipo de Lance *</label>
                            <select name="tipo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                                <option value="gol_ponto">⚽ Gol / Ponto Marcado</option>
                                <option value="assistencia">🎯 Assistência para Gol</option>
                                <option value="cartao_amarelo">🟨 Cartão Amarelo</option>
                                <option value="cartao_vermelho">🟥 Cartão Vermelho</option>
                            </select>
                        </div>

                        <!-- Minuto -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Minuto do Jogo</label>
                            <input type="number" name="minuto" min="1" max="150" placeholder="Ex: 14" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Observações -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Observação do Lance</label>
                            <input type="text" name="observacao" placeholder="Ex: Gol de falta, falta tática" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md">
                            Adicionar à Súmula
                        </button>
                    </form>

                    <script>
                    const mandanteAlunos = <?= json_encode($mandanteAlunos) ?>;
                    const visitanteAlunos = <?= json_encode($visitanteAlunos) ?>;
                    const mandanteId = <?= $partida['turma_mandante_id'] ?>;
                    const visitanteId = <?= $partida['turma_visitante_id'] ?>;

                    function filtrarAlunos(turmaId) {
                        const select = document.getElementById('sumula-aluno');
                        select.innerHTML = '<option value="">Selecione o atleta...</option>';

                        const lista = (parseInt(turmaId) === mandanteId) ? mandanteAlunos : visitanteAlunos;
                        lista.forEach(al => {
                            const opt = document.createElement('option');
                            opt.value = al.id;
                            opt.textContent = (al.numero_camisa ? '#' + al.numero_camisa + ' ' : '') + al.nome;
                            select.appendChild(opt);
                        });
                    }
                    </script>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 rounded-2xl border border-blue-200 p-5 text-center text-xs text-blue-800">
                    <i class="fa-solid fa-lock text-2xl text-blue-600 mb-2 block"></i>
                    <h4 class="font-bold text-sm mb-1">Transparência Esportiva</h4>
                    <p class="leading-relaxed">
                        A súmula oficial é auditada e atualizada exclusivamente pelos árbitros e administradores da comissão esportiva.
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
