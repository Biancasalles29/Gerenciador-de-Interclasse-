<?php
/**
 * Página Inicial / Painel do Interclasse Escolar
 * Dashboard informativo, transparência e visão geral do evento
 */

$pageTitle = 'Início - Visão Geral';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/classificacao_helper.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];

// Estatísticas Rápidas
$stmtCountTurmas = $pdo->prepare("SELECT COUNT(*) FROM turmas WHERE edicao_id = :id");
$stmtCountTurmas->execute([':id' => $edicaoId]);
$totalTurmas = (int)$stmtCountTurmas->fetchColumn();

$stmtCountPartidas = $pdo->prepare("SELECT COUNT(*) FROM partidas WHERE edicao_id = :id");
$stmtCountPartidas->execute([':id' => $edicaoId]);
$totalPartidas = (int)$stmtCountPartidas->fetchColumn();

$stmtCountEncerradas = $pdo->prepare("SELECT COUNT(*) FROM partidas WHERE edicao_id = :id AND status = 'encerrado'");
$stmtCountEncerradas->execute([':id' => $edicaoId]);
$totalEncerradas = (int)$stmtCountEncerradas->fetchColumn();

$stmtCountGols = $pdo->prepare("
    SELECT COUNT(*) FROM sumula_eventos se 
    JOIN partidas p ON p.id = se.partida_id 
    WHERE p.edicao_id = :id AND se.tipo = 'gol_ponto'
");
$stmtCountGols->execute([':id' => $edicaoId]);
$totalGols = (int)$stmtCountGols->fetchColumn();

// Próximas 3 Partidas Agendadas
$stmtProximos = $pdo->prepare("
    SELECT p.*, tm.sigla AS m_sigla, tm.cor_uniforme AS m_cor, 
           tv.sigla AS v_sigla, tv.cor_uniforme AS v_cor,
           m.nome AS modalidade_nome, m.icone AS modalidade_icone
    FROM partidas p
    JOIN turmas tm ON tm.id = p.turma_mandante_id
    JOIN turmas tv ON tv.id = p.turma_visitante_id
    JOIN modalidades m ON m.id = p.modalidade_id
    WHERE p.edicao_id = :id AND p.status IN ('agendado', 'em_andamento')
    ORDER BY p.data_jogo ASC, p.hora_jogo ASC
    LIMIT 3
");
$stmtProximos->execute([':id' => $edicaoId]);
$proximosJogos = $stmtProximos->fetchAll();

// Últimos 3 Resultados Finalizados
$stmtUltimos = $pdo->prepare("
    SELECT p.*, tm.sigla AS m_sigla, tm.cor_uniforme AS m_cor, 
           tv.sigla AS v_sigla, tv.cor_uniforme AS v_cor,
           m.nome AS modalidade_nome, m.icone AS modalidade_icone
    FROM partidas p
    JOIN turmas tm ON tm.id = p.turma_mandante_id
    JOIN turmas tv ON tv.id = p.turma_visitante_id
    JOIN modalidades m ON m.id = p.modalidade_id
    WHERE p.edicao_id = :id AND p.status = 'encerrado'
    ORDER BY p.data_jogo DESC, p.hora_jogo DESC
    LIMIT 3
");
$stmtUltimos->execute([':id' => $edicaoId]);
$ultimosResultados = $stmtUltimos->fetchAll();

// Últimos Comunicados Oficiais
$stmtComunicados = $pdo->prepare("
    SELECT c.*, u.nome AS autor_nome 
    FROM comunicados c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.edicao_id = :id
    ORDER BY c.criado_em DESC
    LIMIT 3
");
$stmtComunicados->execute([':id' => $edicaoId]);
$comunicadosRecentes = $stmtComunicados->fetchAll();

// Classificação da primeira modalidade para o Top 3
$classificacaoDestaque = calcular_classificacao($pdo, $edicaoId);
$top3 = array_slice($classificacaoDestaque, 0, 3);
?>

<main class="flex-1 pb-16">
    <!-- Banner Hero Esportivo -->
    <section class="relative bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-white overflow-hidden py-10 sm:py-16 border-b border-slate-800">
        <!-- Detalhes de iluminação no fundo -->
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#3b82f6_1px,transparent_1px)] [background-size:16px_16px]"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                
                <div class="text-center lg:text-left max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-400/30 text-blue-300 text-xs font-semibold mb-4">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <?= htmlspecialchars($edicaoAtiva['nome'] ?? 'Interclasse') ?> (<?= $edicaoAtiva['ano'] ?? date('Y') ?>)
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-white leading-tight">
                        A Emoção do Esporte Escolar em <span class="bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">Tempo Real</span>
                    </h1>
                    <p class="mt-4 text-sm sm:text-base text-slate-300 leading-relaxed">
                        Acompanhe resultados ao vivo, tabelas de classificação atualizadas automaticamente, artilharia, cartões e o cronograma completo dos jogos da sua sala.
                    </p>
                    
                    <div class="mt-6 flex flex-wrap items-center justify-center lg:justify-start gap-3">
                        <a href="classificacao.php" class="px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition shadow-lg shadow-blue-600/30 flex items-center gap-2">
                            <i class="fa-solid fa-table-list"></i>
                            <span>Ver Classificação</span>
                        </a>
                        <a href="jogos.php" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-sm transition flex items-center gap-2">
                            <i class="fa-solid fa-calendar-days text-blue-400"></i>
                            <span>Calendário de Partidas</span>
                        </a>
                    </div>
                </div>

                <!-- Card Destaque / Próximo Duelo -->
                <div class="w-full max-w-md bg-slate-800/80 backdrop-blur-md rounded-2xl border border-slate-700 p-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-700/80 pb-3 mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-fire text-amber-400"></i> Destaque da Rodada
                        </span>
                        <span class="text-[11px] bg-blue-500/20 text-blue-300 px-2.5 py-0.5 rounded-full font-semibold border border-blue-500/30">
                            Fase de Grupos
                        </span>
                    </div>

                    <?php if (!empty($proximosJogos)): 
                        $destaque = $proximosJogos[0];
                    ?>
                        <div class="text-center mb-3">
                            <span class="text-xs text-slate-400 flex items-center justify-center gap-1.5">
                                <i class="fa-solid <?= htmlspecialchars($destaque['modalidade_icone']) ?> text-blue-400"></i>
                                <?= htmlspecialchars($destaque['modalidade_nome']) ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4 my-4">
                            <!-- Mandante -->
                            <div class="flex-1 text-center">
                                <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center text-white font-extrabold text-lg shadow-md mb-2" style="background-color: <?= htmlspecialchars($destaque['m_cor']) ?>;">
                                    <?= substr($destaque['m_sigla'], 0, 3) ?>
                                </div>
                                <h3 class="font-bold text-sm text-white truncate"><?= htmlspecialchars($destaque['m_sigla']) ?></h3>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">Mandante</span>
                            </div>

                            <!-- VS / Horário -->
                            <div class="flex flex-col items-center justify-center px-2">
                                <span class="text-xs font-black text-amber-400 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 mb-1">
                                    VS
                                </span>
                                <span class="text-xs font-bold text-slate-200">
                                    <?= substr($destaque['hora_jogo'], 0, 5) ?>
                                </span>
                                <span class="text-[10px] text-slate-400">
                                    <?= date('d/m', strtotime($destaque['data_jogo'])) ?>
                                </span>
                            </div>

                            <!-- Visitante -->
                            <div class="flex-1 text-center">
                                <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center text-white font-extrabold text-lg shadow-md mb-2" style="background-color: <?= htmlspecialchars($destaque['v_cor']) ?>;">
                                    <?= substr($destaque['v_sigla'], 0, 3) ?>
                                </div>
                                <h3 class="font-bold text-sm text-white truncate"><?= htmlspecialchars($destaque['v_sigla']) ?></h3>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">Visitante</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-700/80 flex items-center justify-between text-xs text-slate-400">
                            <span class="truncate flex items-center gap-1">
                                <i class="fa-solid fa-location-dot text-rose-400"></i>
                                <?= htmlspecialchars($destaque['local_jogo']) ?>
                            </span>
                            <a href="partida_detalhes.php?id=<?= $destaque['id'] ?>" class="text-blue-400 hover:text-blue-300 font-semibold inline-flex items-center gap-1">
                                Súmula <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-slate-400 text-sm">
                            <i class="fa-solid fa-calendar-xmark text-3xl mb-2 text-slate-500 block"></i>
                            Nenhuma partida agendada no momento.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <!-- Barra de Indicadores / Métricas Globais -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 sm:-mt-8 relative z-20">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-lg border border-slate-200/80 flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-800 leading-none block"><?= $totalTurmas ?></span>
                    <span class="text-xs font-semibold text-slate-500">Turmas Inscritas</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-lg border border-slate-200/80 flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-800 leading-none block"><?= $totalPartidas ?></span>
                    <span class="text-xs font-semibold text-slate-500">Total de Jogos</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-lg border border-slate-200/80 flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-800 leading-none block"><?= $totalEncerradas ?></span>
                    <span class="text-xs font-semibold text-slate-500">Jogos Encerrados</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-lg border border-slate-200/80 flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-futbol"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-800 leading-none block"><?= $totalGols ?></span>
                    <span class="text-xs font-semibold text-slate-500">Gols / Pontos</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Conteúdo Principal em Grid Responsivo -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Coluna 1 & 2: Jogos e Classificação Rápida -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Próximos Jogos -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <h2 class="text-lg font-bold text-slate-800">Próximas Partidas</h2>
                        </div>
                        <a href="jogos.php" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            Ver todas <i class="fa-solid fa-chevron-right text-[10px] ml-0.5"></i>
                        </a>
                    </div>

                    <?php if (!empty($proximosJogos)): ?>
                        <div class="space-y-3">
                            <?php foreach ($proximosJogos as $j): ?>
                                <div class="p-3.5 sm:p-4 rounded-xl border border-slate-100 hover:border-slate-300 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col sm:flex-row items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 text-xs text-slate-500 w-full sm:w-auto">
                                        <span class="font-bold text-slate-700 bg-white px-2 py-1 rounded border border-slate-200">
                                            <?= date('d/m', strtotime($j['data_jogo'])) ?> • <?= substr($j['hora_jogo'], 0, 5) ?>
                                        </span>
                                        <span class="truncate max-w-[150px]"><i class="fa-solid fa-location-dot text-rose-500 mr-1"></i><?= htmlspecialchars($j['local_jogo']) ?></span>
                                    </div>

                                    <div class="flex items-center justify-center gap-3 text-sm font-bold text-slate-800 w-full sm:w-auto">
                                        <span class="flex items-center gap-1.5 truncate">
                                            <span class="w-3 h-3 rounded-full" style="background-color: <?= $j['m_cor'] ?>;"></span>
                                            <?= htmlspecialchars($j['m_sigla']) ?>
                                        </span>
                                        <span class="text-xs text-slate-400 font-normal">vs</span>
                                        <span class="flex items-center gap-1.5 truncate">
                                            <span class="w-3 h-3 rounded-full" style="background-color: <?= $j['v_cor'] ?>;"></span>
                                            <?= htmlspecialchars($j['v_sigla']) ?>
                                        </span>
                                    </div>

                                    <div class="w-full sm:w-auto text-right">
                                        <a href="partida_detalhes.php?id=<?= $j['id'] ?>" class="inline-block w-full sm:w-auto py-1.5 px-3 bg-white hover:bg-blue-600 hover:text-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition text-center shadow-sm">
                                            Detalhes
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-6 text-xs text-slate-500">Nenhuma partida futura agendada.</p>
                    <?php endif; ?>
                </div>

                <!-- Últimos Resultados -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <i class="fa-solid fa-square-poll-vertical"></i>
                            </div>
                            <h2 class="text-lg font-bold text-slate-800">Resultados Recentes</h2>
                        </div>
                        <a href="jogos.php?status=encerrado" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            Histórico completo <i class="fa-solid fa-chevron-right text-[10px] ml-0.5"></i>
                        </a>
                    </div>

                    <?php if (!empty($ultimosResultados)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <?php foreach ($ultimosResultados as $res): ?>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center flex flex-col justify-between">
                                    <div class="text-[11px] font-semibold text-slate-500 mb-2 truncate">
                                        <?= htmlspecialchars($res['modalidade_nome']) ?>
                                    </div>

                                    <div class="flex items-center justify-center gap-2 my-2">
                                        <div class="flex-1 text-right">
                                            <span class="font-bold text-xs text-slate-800 block truncate"><?= htmlspecialchars($res['m_sigla']) ?></span>
                                        </div>
                                        <div class="px-2.5 py-1 bg-slate-900 text-white rounded-lg font-black text-sm tracking-wider">
                                            <?= $res['placar_mandante'] ?> - <?= $res['placar_visitante'] ?>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <span class="font-bold text-xs text-slate-800 block truncate"><?= htmlspecialchars($res['v_sigla']) ?></span>
                                        </div>
                                    </div>

                                    <div class="text-[10px] text-slate-400 mt-2">
                                        Finalizado • <a href="partida_detalhes.php?id=<?= $res['id'] ?>" class="text-blue-600 hover:underline">Súmula</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-6 text-xs text-slate-500">Nenhum resultado finalizado ainda.</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Coluna 3: Pódio / Classificação e Mural de Avisos -->
            <div class="space-y-8">
                
                <!-- Pódio / Líderes -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                            <h2 class="text-lg font-bold text-slate-800">Líderes do Torneio</h2>
                        </div>
                        <a href="classificacao.php" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            Tabela Completa
                        </a>
                    </div>

                    <?php if (!empty($top3)): ?>
                        <div class="space-y-2.5">
                            <?php foreach ($top3 as $idx => $t): ?>
                                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-extrabold <?= match($idx) {
                                            0 => 'bg-amber-400 text-slate-900 shadow-sm shadow-amber-300',
                                            1 => 'bg-slate-300 text-slate-800',
                                            2 => 'bg-amber-700 text-white',
                                            default => 'bg-slate-200 text-slate-600'
                                        } ?>">
                                            <?= $idx + 1 ?>º
                                        </span>
                                        <div>
                                            <h4 class="font-bold text-xs text-slate-800"><?= htmlspecialchars($t['sigla']) ?></h4>
                                            <span class="text-[10px] text-slate-500"><?= $t['jogos'] ?> jogos • <?= $t['vitorias'] ?>V</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-black text-sm text-blue-600 block"><?= $t['pontos'] ?> pts</span>
                                        <span class="text-[10px] text-slate-400">SG: <?= $t['saldo_gols'] >= 0 ? "+{$t['saldo_gols']}" : $t['saldo_gols'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-6 text-xs text-slate-500">Classificação ainda não iniciada.</p>
                    <?php endif; ?>
                </div>

                <!-- Mural de Comunicados Rápidos -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <h2 class="text-lg font-bold text-slate-800">Mural de Avisos</h2>
                        </div>
                        <a href="comunicados.php" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            Ver todos
                        </a>
                    </div>

                    <?php if (!empty($comunicadosRecentes)): ?>
                        <div class="space-y-3">
                            <?php foreach ($comunicadosRecentes as $aviso): ?>
                                <div class="p-3 rounded-xl border border-slate-100 bg-slate-50 text-xs">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="font-extrabold uppercase tracking-wider text-[9px] px-1.5 py-0.5 rounded <?= match($aviso['tipo']) {
                                            'urgente' => 'bg-rose-100 text-rose-700',
                                            'cronograma' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-blue-100 text-blue-700'
                                        } ?>">
                                            <?= htmlspecialchars(strtoupper($aviso['tipo'])) ?>
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            <?= date('d/m H:i', strtotime($aviso['criado_em'])) ?>
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-slate-800 mb-1 leading-snug"><?= htmlspecialchars($aviso['titulo']) ?></h4>
                                    <p class="text-slate-600 text-[11px] line-clamp-2 leading-relaxed"><?= htmlspecialchars($aviso['conteudo']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-6 text-xs text-slate-500">Nenhum aviso no momento.</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
