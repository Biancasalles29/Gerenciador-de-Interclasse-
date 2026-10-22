<?php
/**
 * Estatísticas Individuais & Coletivas
 * 
 * Requisitos:
 * - Artilharia (Gols / Pontos)
 * - Assistências
 * - Cartões Amarelos e Vermelhos
 * - Transparência pública
 */

$pageTitle = 'Estatísticas & Artilharia';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$edicaoId = $edicaoAtiva['id'];

// Filtro por Modalidade
$filtroModalidade = isset($_GET['modalidade_id']) ? (int)$_GET['modalidade_id'] : 0;

$stmtMod = $pdo->prepare("SELECT * FROM modalidades WHERE edicao_id = :id ORDER BY nome ASC");
$stmtMod->execute([':id' => $edicaoId]);
$modalidades = $stmtMod->fetchAll();

// 1. Ranking de Artilharia (Gols / Pontos)
$sqlArtilharia = "
    SELECT a.id, a.nome, a.numero_camisa, t.sigla AS turma_sigla, t.cor_uniforme,
           COUNT(se.id) AS total_gols
    FROM sumula_eventos se
    JOIN alunos a ON a.id = se.aluno_id
    JOIN turmas t ON t.id = se.turma_id
    JOIN partidas p ON p.id = se.partida_id
    WHERE p.edicao_id = :edicao_id AND se.tipo = 'gol_ponto'
";
$paramsArt = [':edicao_id' => $edicaoId];
if ($filtroModalidade > 0) {
    $sqlArtilharia .= " AND p.modalidade_id = :mod_id";
    $paramsArt[':mod_id'] = $filtroModalidade;
}
$sqlArtilharia .= " GROUP BY a.id, a.nome, a.numero_camisa, t.sigla, t.cor_uniforme ORDER BY total_gols DESC, a.nome ASC LIMIT 20";

$stmtArt = $pdo->prepare($sqlArtilharia);
$stmtArt->execute($paramsArt);
$artilheiros = $stmtArt->fetchAll();

// 2. Ranking de Assistências
$sqlAssistencia = "
    SELECT a.id, a.nome, a.numero_camisa, t.sigla AS turma_sigla, t.cor_uniforme,
           COUNT(se.id) AS total_assistencias
    FROM sumula_eventos se
    JOIN alunos a ON a.id = se.aluno_id
    JOIN turmas t ON t.id = se.turma_id
    JOIN partidas p ON p.id = se.partida_id
    WHERE p.edicao_id = :edicao_id AND se.tipo = 'assistencia'
";
$paramsAss = [':edicao_id' => $edicaoId];
if ($filtroModalidade > 0) {
    $sqlAssistencia .= " AND p.modalidade_id = :mod_id";
    $paramsAss[':mod_id'] = $filtroModalidade;
}
$sqlAssistencia .= " GROUP BY a.id, a.nome, a.numero_camisa, t.sigla, t.cor_uniforme ORDER BY total_assistencias DESC, a.nome ASC LIMIT 15";

$stmtAss = $pdo->prepare($sqlAssistencia);
$stmtAss->execute($paramsAss);
$assistencias = $stmtAss->fetchAll();

// 3. Disciplina / Cartões Amarelos e Vermelhos
$sqlCartoes = "
    SELECT a.id, a.nome, a.numero_camisa, t.sigla AS turma_sigla, t.cor_uniforme,
           SUM(CASE WHEN se.tipo = 'cartao_amarelo' THEN 1 ELSE 0 END) AS total_amarelos,
           SUM(CASE WHEN se.tipo = 'cartao_vermelho' THEN 1 ELSE 0 END) AS total_vermelhos,
           COUNT(se.id) AS total_cartoes
    FROM sumula_eventos se
    JOIN alunos a ON a.id = se.aluno_id
    JOIN turmas t ON t.id = se.turma_id
    JOIN partidas p ON p.id = se.partida_id
    WHERE p.edicao_id = :edicao_id AND se.tipo IN ('cartao_amarelo', 'cartao_vermelho')
";
$paramsCar = [':edicao_id' => $edicaoId];
if ($filtroModalidade > 0) {
    $sqlCartoes .= " AND p.modalidade_id = :mod_id";
    $paramsCar[':mod_id'] = $filtroModalidade;
}
$sqlCartoes .= " GROUP BY a.id, a.nome, a.numero_camisa, t.sigla, t.cor_uniforme ORDER BY total_vermelhos DESC, total_amarelos DESC LIMIT 15";

$stmtCar = $pdo->prepare($sqlCartoes);
$stmtCar->execute($paramsCar);
$cartoes = $stmtCar->fetchAll();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 mb-1">
                <span><?= htmlspecialchars($edicaoAtiva['nome']) ?></span>
                <span>•</span>
                <span>Desempenho Individual</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Estatísticas & Transparência
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">
                Artilharia, assistências e disciplina apuradas lance a lance.
            </p>
        </div>

        <!-- Seletor de Modalidade -->
        <div class="flex items-center gap-2">
            <form method="GET" action="estatisticas.php" class="flex items-center gap-2">
                <select name="modalidade_id" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500">
                    <option value="0">Todas as Modalidades</option>
                    <?php foreach ($modalidades as $mod): ?>
                        <option value="<?= $mod['id'] ?>" <?= $filtroModalidade == $mod['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Grid de Seções de Estatísticas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Bloco 1: Artilharia (Gols / Pontos) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-slate-900 text-base">Artilharia</h2>
                        <span class="text-[11px] text-slate-400">Gols / Pontos Marcados</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                    Chuteira de Ouro
                </span>
            </div>

            <?php if (!empty($artilheiros)): ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($artilheiros as $pos => $art): ?>
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black <?= match($pos) {
                                    0 => 'bg-amber-400 text-slate-900 shadow-sm shadow-amber-300',
                                    1 => 'bg-slate-200 text-slate-800',
                                    2 => 'bg-amber-700/80 text-white',
                                    default => 'text-slate-500 bg-slate-50'
                                } ?>">
                                    <?= $pos + 1 ?>º
                                </span>
                                <div>
                                    <h4 class="font-extrabold text-xs text-slate-900"><?= htmlspecialchars($art['nome']) ?></h4>
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full inline-block" style="background-color: <?= $art['cor_uniforme'] ?>;"></span>
                                        <?= htmlspecialchars($art['turma_sigla']) ?>
                                        <?php if ($art['numero_camisa']): ?>• #<?= $art['numero_camisa'] ?><?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-sm text-slate-900 block"><?= $art['total_gols'] ?></span>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">gols</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center py-10 text-xs text-slate-400">Nenhum gol registrado ainda.</p>
            <?php endif; ?>
        </div>

        <!-- Bloco 2: Assistências -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-hands-clapping"></i>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-slate-900 text-base">Assistências</h2>
                        <span class="text-[11px] text-slate-400">Garçons do Torneio</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                    Passes
                </span>
            </div>

            <?php if (!empty($assistencias)): ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($assistencias as $pos => $ass): ?>
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black <?= match($pos) {
                                    0 => 'bg-blue-600 text-white shadow-sm',
                                    1 => 'bg-blue-100 text-blue-800',
                                    2 => 'bg-slate-100 text-slate-700',
                                    default => 'text-slate-500 bg-slate-50'
                                } ?>">
                                    <?= $pos + 1 ?>º
                                </span>
                                <div>
                                    <h4 class="font-extrabold text-xs text-slate-900"><?= htmlspecialchars($ass['nome']) ?></h4>
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full inline-block" style="background-color: <?= $ass['cor_uniforme'] ?>;"></span>
                                        <?= htmlspecialchars($ass['turma_sigla']) ?>
                                        <?php if ($ass['numero_camisa']): ?>• #<?= $ass['numero_camisa'] ?><?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-sm text-blue-700 block"><?= $ass['total_assistencias'] ?></span>
                                <span class="text-[10px] text-slate-400 uppercase font-semibold">passes</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center py-10 text-xs text-slate-400">Nenhuma assistência registrada.</p>
            <?php endif; ?>
        </div>

        <!-- Bloco 3: Cartões e Disciplina -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-slate-900 text-base">Disciplina</h2>
                        <span class="text-[11px] text-slate-400">Cartões Amarelos & Vermelhos</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                    Fair Play
                </span>
            </div>

            <?php if (!empty($cartoes)): ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($cartoes as $pos => $car): ?>
                        <div class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div>
                                    <h4 class="font-extrabold text-xs text-slate-900"><?= htmlspecialchars($car['nome']) ?></h4>
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full inline-block" style="background-color: <?= $car['cor_uniforme'] ?>;"></span>
                                        <?= htmlspecialchars($car['turma_sigla']) ?>
                                        <?php if ($car['numero_camisa']): ?>• #<?= $car['numero_camisa'] ?><?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-xs font-bold">
                                <?php if ($car['total_amarelos'] > 0): ?>
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200 flex items-center gap-1">
                                        <span class="w-2 h-3 bg-amber-500 rounded-sm inline-block"></span>
                                        <?= $car['total_amarelos'] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($car['total_vermelhos'] > 0): ?>
                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-200 flex items-center gap-1">
                                        <span class="w-2 h-3 bg-rose-600 rounded-sm inline-block"></span>
                                        <?= $car['total_vermelhos'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-10 text-emerald-600">
                    <i class="fa-solid fa-shield-heart text-3xl mb-2 text-emerald-400 block"></i>
                    <p class="text-xs font-bold">Nenhum cartão registrado!</p>
                    <span class="text-[10px] text-slate-400">Torneio exemplar com total espírito esportivo.</span>
                </div>
            <?php endif; ?>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
