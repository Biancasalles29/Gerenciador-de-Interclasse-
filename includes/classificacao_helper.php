<?php
/**
 * Helper de Cálculo Automático da Classificação
 * 
 * Regras CORE:
 * - Vitória = 3 pontos
 * - Empate = 1 ponto
 * - Derrota = 0 pontos
 * - Atualização automática após cada resultado registrado
 * - Critérios de desempate oficiais
 */

/**
 * Calcula e retorna a tabela de classificação oficial
 * 
 * @param PDO $pdo Conexão PDO
 * @param int $edicao_id ID da Edição
 * @param int|null $modalidade_id ID da Modalidade (se null, busca a primeira ativa)
 * @return array Dados estruturados da classificação e das turmas
 */
function calcular_classificacao(PDO $pdo, int $edicao_id, ?int $modalidade_id = null): array {
    // 1. Se não especificou modalidade, busca a primeira da edição
    if (!$modalidade_id) {
        $stmtMod = $pdo->prepare("SELECT id FROM modalidades WHERE edicao_id = :edicao_id ORDER BY id ASC LIMIT 1");
        $stmtMod->execute([':edicao_id' => $edicao_id]);
        $modalidade_id = (int)$stmtMod->fetchColumn();
        if (!$modalidade_id) {
            return [];
        }
    }

    // 2. Buscar todas as turmas da edição
    $stmtTurmas = $pdo->prepare("
        SELECT id, nome, sigla, curso, cor_uniforme 
        FROM turmas 
        WHERE edicao_id = :edicao_id
        ORDER BY sigla ASC
    ");
    $stmtTurmas->execute([':edicao_id' => $edicao_id]);
    $turmasRaw = $stmtTurmas->fetchAll();

    // Inicializar matriz de dados por turma
    $tabela = [];
    foreach ($turmasRaw as $t) {
        $tabela[$t['id']] = [
            'turma_id'      => $t['id'],
            'nome'          => $t['nome'],
            'sigla'         => $t['sigla'],
            'curso'         => $t['curso'],
            'cor_uniforme'  => $t['cor_uniforme'],
            'pontos'        => 0,
            'jogos'         => 0,
            'vitorias'      => 0,
            'empates'       => 0,
            'derrotas'      => 0,
            'gols_pro'      => 0,
            'gols_contra'   => 0,
            'saldo_gols'    => 0,
            'aproveitamento'=> 0,
            'ultimos_jogos' => [] // array de 'V', 'E', 'D'
        ];
    }

    // 3. Buscar todas as partidas encerradas desta edição e modalidade
    $stmtPartidas = $pdo->prepare("
        SELECT id, turma_mandante_id, turma_visitante_id, placar_mandante, placar_visitante, data_jogo, hora_jogo
        FROM partidas 
        WHERE edicao_id = :edicao_id 
          AND modalidade_id = :modalidade_id 
          AND status = 'encerrado' 
          AND placar_mandante IS NOT NULL 
          AND placar_visitante IS NOT NULL
        ORDER BY data_jogo ASC, hora_jogo ASC
    ");
    $stmtPartidas->execute([
        ':edicao_id'     => $edicao_id,
        ':modalidade_id' => $modalidade_id
    ]);
    $partidas = $stmtPartidas->fetchAll();

    // 4. Processar resultados e somar pontos conforme a regra (3-1-0)
    foreach ($partidas as $p) {
        $mandanteId  = $p['turma_mandante_id'];
        $visitanteId = $p['turma_visitante_id'];
        $gm          = (int)$p['placar_mandante'];
        $gv          = (int)$p['placar_visitante'];

        if (!isset($tabela[$mandanteId]) || !isset($tabela[$visitanteId])) {
            continue;
        }

        // Jogos disputados
        $tabela[$mandanteId]['jogos']++;
        $tabela[$visitanteId]['jogos']++;

        // Gols pró e contra
        $tabela[$mandanteId]['gols_pro']    += $gm;
        $tabela[$mandanteId]['gols_contra'] += $gv;
        $tabela[$visitanteId]['gols_pro']   += $gv;
        $tabela[$visitanteId]['gols_contra']+= $gm;

        // Regra de pontuação
        if ($gm > $gv) {
            // Vitória do mandante (3 pontos)
            $tabela[$mandanteId]['pontos']   += 3;
            $tabela[$mandanteId]['vitorias'] += 1;
            $tabela[$mandanteId]['ultimos_jogos'][] = 'V';

            // Derrota do visitante (0 pontos)
            $tabela[$visitanteId]['derrotas'] += 1;
            $tabela[$visitanteId]['ultimos_jogos'][] = 'D';

        } elseif ($gm === $gv) {
            // Empate (1 ponto para cada)
            $tabela[$mandanteId]['pontos']  += 1;
            $tabela[$mandanteId]['empates'] += 1;
            $tabela[$mandanteId]['ultimos_jogos'][] = 'E';

            $tabela[$visitanteId]['pontos']  += 1;
            $tabela[$visitanteId]['empates'] += 1;
            $tabela[$visitanteId]['ultimos_jogos'][] = 'E';

        } else {
            // Vitória do visitante (3 pontos)
            $tabela[$visitanteId]['pontos']   += 3;
            $tabela[$visitanteId]['vitorias'] += 1;
            $tabela[$visitanteId]['ultimos_jogos'][] = 'V';

            // Derrota do mandante (0 pontos)
            $tabela[$mandanteId]['derrotas'] += 1;
            $tabela[$mandanteId]['ultimos_jogos'][] = 'D';
        }
    }

    // 5. Calcular saldo de gols e aproveitamento
    foreach ($tabela as $id => &$linha) {
        $linha['saldo_gols'] = $linha['gols_pro'] - $linha['gols_contra'];
        $pontosPossiveis = $linha['jogos'] * 3;
        $linha['aproveitamento'] = $pontosPossiveis > 0 ? round(($linha['pontos'] / $pontosPossiveis) * 100, 1) : 0;
        // Manter apenas os últimos 5 jogos
        $linha['ultimos_jogos'] = array_slice($linha['ultimos_jogos'], -5);
    }
    unset($linha);

    // 6. Ordenar pelos critérios oficiais de desempate
    // 1º Pontos DESC, 2º Vitórias DESC, 3º Saldo de Gols DESC, 4º Gols Pró DESC, 5º Gols Contra ASC
    usort($tabela, function($a, $b) {
        if ($b['pontos'] !== $a['pontos']) {
            return $b['pontos'] <=> $a['pontos'];
        }
        if ($b['vitorias'] !== $a['vitorias']) {
            return $b['vitorias'] <=> $a['vitorias'];
        }
        if ($b['saldo_gols'] !== $a['saldo_gols']) {
            return $b['saldo_gols'] <=> $a['saldo_gols'];
        }
        if ($b['gols_pro'] !== $a['gols_pro']) {
            return $b['gols_pro'] <=> $a['gols_pro'];
        }
        if ($a['gols_contra'] !== $b['gols_contra']) {
            return $a['gols_contra'] <=> $b['gols_contra']; // Menos gols sofridos fica na frente
        }
        return strcmp($a['sigla'], $b['sigla']);
    });

    // 7. Atribuir posições no ranking
    $pos = 1;
    foreach ($tabela as &$item) {
        $item['posicao'] = $pos++;
    }

    return $tabela;
}
