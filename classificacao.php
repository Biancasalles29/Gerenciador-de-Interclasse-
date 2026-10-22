<?php
/**
 * Tabela Oficial de Classificação Automática
 * 
 * Regras CORE:
 * - Vitória vale 3 pontos, empate 1, derrota 0
 * - Classificação recalculada automaticamente após cada resultado
 * - Isolamento por edição e modalidade
 */

$pageTitle = 'Classificação Oficial';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/classificacao_helper.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];

// Buscar todas as modalidades esportivas da edição
$stmtMod = $pdo->prepare("SELECT * FROM modalidades WHERE edicao_id = :id ORDER BY id ASC");
$stmtMod->execute([':id' => $edicaoId]);
$modalidades = $stmtMod->fetchAll();

// Modalidade selecionada via GET ou padrão primeira
$modSelecionadaId = isset($_GET['modalidade_id']) ? (int)$_GET['modalidade_id'] : ($modalidades[0]['id'] ?? 0);

// Buscar detalhes da modalidade selecionada
$modalidadeAtual = null;
foreach ($modalidades as $m) {
    if ($m['id'] == $modSelecionadaId) {
        $modalidadeAtual = $m;
        break;
    }
}

// Calcular a classificação da modalidade
$classificacao = calcular_classificacao($pdo, $edicaoId, $modSelecionadaId);
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Cabeçalho da Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 mb-1">
                <span><?= htmlspecialchars($edicaoAtiva['nome']) ?></span>
                <span>•</span>
                <span>Tabela Geral</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Classificação Oficial
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Regra: Vitória = 3 pontos | Empate = 1 ponto | Derrota = 0 pontos. Atualização em tempo real.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="jogos.php<?= $modSelecionadaId ? '?modalidade_id=' . $modSelecionadaId : '' ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition inline-flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-blue-500"></i>
                <span>Ver Partidas</span>
            </a>
            <a href="estatisticas.php" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-xl transition inline-flex items-center gap-2">
                <i class="fa-solid fa-chart-simple"></i>
                <span>Artilharia</span>
            </a>
        </div>
    </div>

    <!-- Navegação por Modalidades (Tabs) -->
    <?php if (!empty($modalidades)): ?>
        <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-6 scrollbar-none border-b border-slate-200">
            <?php foreach ($modalidades as $m): ?>
                <a href="classificacao.php?modalidade_id=<?= $m['id'] ?>" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition <?= $m['id'] == $modSelecionadaId ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' ?>">
                    <i class="fa-solid <?= htmlspecialchars($m['icone'] ?? 'fa-trophy') ?> text-xs"></i>
                    <span><?= htmlspecialchars($m['nome']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tabela de Classificação Responsiva -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <!-- Topo da Tabela com Modalidade Atual -->
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg font-bold shadow-sm">
                    <i class="fa-solid <?= htmlspecialchars($modalidadeAtual['icone'] ?? 'fa-trophy') ?>"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-base sm:text-lg text-slate-800">
                        <?= htmlspecialchars($modalidadeAtual['nome'] ?? 'Modalidade') ?>
                    </h2>
                    <span class="text-xs text-slate-500">
                        Fase de Grupos • Critérios: PTS > V > SG > GP
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Zona de Classificação (Top 2)</span>
            </div>
        </div>

        <?php if (!empty($classificacao)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-600 uppercase font-black tracking-wider text-[11px] border-b border-slate-200">
                            <th class="py-3 px-3 sm:px-4 text-center w-12">Pos</th>
                            <th class="py-3 px-4">Turma / Sala</th>
                            <th class="py-3 px-3 text-center font-black text-blue-700 bg-blue-50/50">PTS</th>
                            <th class="py-3 px-2.5 text-center">J</th>
                            <th class="py-3 px-2.5 text-center">V</th>
                            <th class="py-3 px-2.5 text-center">E</th>
                            <th class="py-3 px-2.5 text-center">D</th>
                            <th class="py-3 px-2.5 text-center hidden sm:table-cell">GP</th>
                            <th class="py-3 px-2.5 text-center hidden sm:table-cell">GC</th>
                            <th class="py-3 px-2.5 text-center font-bold">SG</th>
                            <th class="py-3 px-2.5 text-center hidden md:table-cell">%</th>
                            <th class="py-3 px-4 text-center hidden lg:table-cell">Forma Recente</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($classificacao as $idx => $row): 
                            $isClassificado = ($row['posicao'] <= 2);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition <?= $isClassificado ? 'bg-emerald-50/10' : '' ?>">
                                
                                <!-- Posição -->
                                <td class="py-3.5 px-3 sm:px-4 text-center font-black text-xs">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg <?= match($row['posicao']) {
                                        1 => 'bg-amber-400 text-slate-900 shadow-sm shadow-amber-300 font-extrabold',
                                        2 => 'bg-slate-200 text-slate-800 font-bold',
                                        3 => 'bg-amber-700/80 text-white font-bold',
                                        default => 'text-slate-600 bg-slate-100'
                                    } ?>">
                                        <?= $row['posicao'] ?>º
                                    </span>
                                </td>

                                <!-- Nome da Turma -->
                                <td class="py-3.5 px-4">
                                    <a href="turma_detalhes.php?id=<?= $row['turma_id'] ?>" class="flex items-center gap-2.5 group">
                                        <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 shadow-sm" style="background-color: <?= htmlspecialchars($row['cor_uniforme']) ?>;"></span>
                                        <div>
                                            <div class="font-extrabold text-slate-900 group-hover:text-blue-600 transition flex items-center gap-1.5">
                                                <span><?= htmlspecialchars($row['sigla']) ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-normal hidden sm:block truncate max-w-xs">
                                                <?= htmlspecialchars($row['nome']) ?>
                                            </div>
                                        </div>
                                    </a>
                                </td>

                                <!-- Pontos (Destaque) -->
                                <td class="py-3.5 px-3 text-center font-black text-sm text-blue-700 bg-blue-50/30">
                                    <?= $row['pontos'] ?>
                                </td>

                                <!-- Jogos -->
                                <td class="py-3.5 px-2.5 text-center font-bold text-slate-700">
                                    <?= $row['jogos'] ?>
                                </td>

                                <!-- Vitórias -->
                                <td class="py-3.5 px-2.5 text-center text-slate-700 font-semibold">
                                    <?= $row['vitorias'] ?>
                                </td>

                                <!-- Empates -->
                                <td class="py-3.5 px-2.5 text-center text-slate-700 font-semibold">
                                    <?= $row['empates'] ?>
                                </td>

                                <!-- Derrotas -->
                                <td class="py-3.5 px-2.5 text-center text-slate-700 font-semibold">
                                    <?= $row['derrotas'] ?>
                                </td>

                                <!-- Gols Pró -->
                                <td class="py-3.5 px-2.5 text-center text-slate-500 hidden sm:table-cell">
                                    <?= $row['gols_pro'] ?>
                                </td>

                                <!-- Gols Contra -->
                                <td class="py-3.5 px-2.5 text-center text-slate-500 hidden sm:table-cell">
                                    <?= $row['gols_contra'] ?>
                                </td>

                                <!-- Saldo de Gols -->
                                <td class="py-3.5 px-2.5 text-center font-bold <?= $row['saldo_gols'] > 0 ? 'text-emerald-600' : ($row['saldo_gols'] < 0 ? 'text-rose-600' : 'text-slate-500') ?>">
                                    <?= $row['saldo_gols'] > 0 ? "+{$row['saldo_gols']}" : $row['saldo_gols'] ?>
                                </td>

                                <!-- Aproveitamento % -->
                                <td class="py-3.5 px-2.5 text-center text-slate-500 font-mono text-xs hidden md:table-cell">
                                    <?= $row['aproveitamento'] ?>%
                                </td>

                                <!-- Forma Recente -->
                                <td class="py-3.5 px-4 text-center hidden lg:table-cell">
                                    <div class="flex items-center justify-center gap-1">
                                        <?php if (!empty($row['ultimos_jogos'])): ?>
                                            <?php foreach ($row['ultimos_jogos'] as $res): ?>
                                                <span class="w-5 h-5 rounded flex items-center justify-center text-[10px] font-black text-white <?= match($res) {
                                                    'V' => 'bg-emerald-500',
                                                    'E' => 'bg-amber-500',
                                                    'D' => 'bg-rose-500',
                                                    default => 'bg-slate-300'
                                                } ?>" title="<?= match($res) {'V' => 'Vitória', 'E' => 'Empate', 'D' => 'Derrota'} ?>">
                                                    <?= $res ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-16 px-4">
                <i class="fa-solid fa-ranking-star text-4xl text-slate-300 mb-3 block"></i>
                <h3 class="text-base font-bold text-slate-700">Nenhuma turma com partidas cadastradas nesta modalidade</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    Os pontos serão exibidos automaticamente assim que as partidas forem agendadas e concluídas.
                </p>
            </div>
        <?php endif; ?>

        <!-- Rodapé da Tabela: Legenda Explicativa -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-bold text-slate-700">Legenda:</span>
                <span><strong>PTS:</strong> Pontos</span>
                <span><strong>J:</strong> Jogos</span>
                <span><strong>V:</strong> Vitórias (3 pts)</span>
                <span><strong>E:</strong> Empates (1 pt)</span>
                <span><strong>D:</strong> Derrotas (0 pt)</span>
                <span><strong>GP:</strong> Gols Pró</span>
                <span><strong>GC:</strong> Gols Contra</span>
                <span><strong>SG:</strong> Saldo de Gols</span>
                <span><strong>%:</strong> Aproveitamento</span>
            </div>

            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span>
                <span class="text-[11px]">V = Vitória</span>
                <span class="w-3 h-3 rounded bg-amber-500 inline-block ml-1"></span>
                <span class="text-[11px]">E = Empate</span>
                <span class="w-3 h-3 rounded bg-rose-500 inline-block ml-1"></span>
                <span class="text-[11px]">D = Derrota</span>
            </div>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
