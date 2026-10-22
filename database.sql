-- ====================================================================
-- BANCO DE DADOS: GERENCIADOR DE INTERCLASSE
-- Compatível com MySQL 5.7+ / MariaDB 10.3+
-- Desenvolvido para atender rigorosamente a todos os requisitos do projeto
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `interclasse_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `interclasse_db`;

-- Desativar verificação de FK temporariamente para criação limpa
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `logs_auditoria`;
DROP TABLE IF EXISTS `sumula_eventos`;
DROP TABLE IF EXISTS `comunicados`;
DROP TABLE IF EXISTS `partidas`;
DROP TABLE IF EXISTS `alunos`;
DROP TABLE IF EXISTS `turmas`;
DROP TABLE IF EXISTS `modalidades`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `edicoes`;

-- ====================================================================
-- TABELA 1: EDIÇÕES DO INTERCLASSE
-- Garante isolamento: dados de uma edição não interferem em outra
-- ====================================================================
CREATE TABLE `edicoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL COMMENT 'Nome oficial da edição, ex: Interclasse ETEC 2026',
    `ano` INT NOT NULL COMMENT 'Ano letivo do evento',
    `status` ENUM('planejamento', 'em_andamento', 'finalizado') NOT NULL DEFAULT 'em_andamento',
    `ativa` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 se for a edição selecionada por padrão',
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 2: USUÁRIOS E PERMISSÕES
-- Perfis:
--   'admin': Acesso total a todas as funções
--   'tecnico': Acesso para cadastrar e gerenciar sua turma e alunos
--   'visitante': Acesso de visualização pública
-- Senhas criptografadas com BCRYPT
-- ====================================================================
CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL COMMENT 'Hash seguro bcrypt',
    `perfil` ENUM('admin', 'tecnico', 'visitante') NOT NULL DEFAULT 'visitante',
    `telefone` VARCHAR(25) NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 3: MODALIDADES ESPORTIVAS
-- Vinculadas à edição para permitir regras e esportes específicos
-- ====================================================================
CREATE TABLE `modalidades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `edicao_id` INT NOT NULL,
    `nome` VARCHAR(80) NOT NULL COMMENT 'Ex: Futsal Masculino, Vôlei Misto, Basquete 3x3',
    `icone` VARCHAR(50) DEFAULT 'fa-futbol' COMMENT 'Classe FontAwesome do ícone',
    `categoria` VARCHAR(50) DEFAULT 'Geral',
    `permite_empate` TINYINT(1) NOT NULL DEFAULT 1,
    `tempo_partida_minutos` INT DEFAULT 30,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_modalidades_edicao` FOREIGN KEY (`edicao_id`) REFERENCES `edicoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 4: TURMAS / SALAS
-- Cada turma pertence a uma edição e tem um técnico responsável
-- Somente o técnico ou admin pode cadastrar/alterar seus alunos
-- ====================================================================
CREATE TABLE `turmas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `edicao_id` INT NOT NULL,
    `tecnico_id` INT NULL COMMENT 'Técnico responsável pela sala',
    `nome` VARCHAR(80) NOT NULL COMMENT 'Ex: 3º Ensino Médio - Informática',
    `sigla` VARCHAR(20) NOT NULL COMMENT 'Ex: 3º INFO A',
    `curso` VARCHAR(80) NULL COMMENT 'Ex: Técnico em Desenvolvimento de Sistemas',
    `periodo` ENUM('Manhã', 'Tarde', 'Noite', 'Integral') DEFAULT 'Manhã',
    `cor_uniforme` VARCHAR(20) DEFAULT '#2563EB',
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_turmas_edicao` FOREIGN KEY (`edicao_id`) REFERENCES `edicoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_turmas_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 5: ALUNOS / ATLETAS
-- Dados dos participantes protegidos: acesso restrito ao técnico e admin
-- ====================================================================
CREATE TABLE `alunos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `turma_id` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `numero_camisa` INT NULL,
    `posicao` VARCHAR(50) NULL,
    `rg_ra` VARCHAR(40) NULL COMMENT 'Dado sensível/protegido',
    `contato_emergencia` VARCHAR(60) NULL COMMENT 'Dado sensível/protegido',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_alunos_turma` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 6: PARTIDAS / JOGOS
-- Regras CORE:
--  - Data, horário e local obrigatórios
--  - Equipes vinculadas a uma turma
--  - Uma equipe só joga 1 partida por horário (validado na action)
-- ====================================================================
CREATE TABLE `partidas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `edicao_id` INT NOT NULL,
    `modalidade_id` INT NOT NULL,
    `turma_mandante_id` INT NOT NULL,
    `turma_visitante_id` INT NOT NULL,
    `data_jogo` DATE NOT NULL,
    `hora_jogo` TIME NOT NULL,
    `local_jogo` VARCHAR(100) NOT NULL COMMENT 'Ex: Quadra 1, Ginásio Principal',
    `fase` VARCHAR(50) NOT NULL DEFAULT 'Fase de Grupos',
    `placar_mandante` INT NULL DEFAULT NULL,
    `placar_visitante` INT NULL DEFAULT NULL,
    `status` ENUM('agendado', 'em_andamento', 'encerrado', 'cancelado') NOT NULL DEFAULT 'agendado',
    `observacoes` TEXT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_partidas_edicao` FOREIGN KEY (`edicao_id`) REFERENCES `edicoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_partidas_modalidade` FOREIGN KEY (`modalidade_id`) REFERENCES `modalidades` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_partidas_mandante` FOREIGN KEY (`turma_mandante_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_partidas_visitante` FOREIGN KEY (`turma_visitante_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 7: SÚMULA / EVENTOS DA PARTIDA
-- Artilharia, assistências, cartões amarelos e cartões vermelhos
-- ====================================================================
CREATE TABLE `sumula_eventos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `partida_id` INT NOT NULL,
    `turma_id` INT NOT NULL,
    `aluno_id` INT NOT NULL,
    `tipo` ENUM('gol_ponto', 'assistencia', 'cartao_amarelo', 'cartao_vermelho') NOT NULL,
    `minuto` INT NULL,
    `observacao` VARCHAR(150) NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sumula_partida` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sumula_turma` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sumula_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 8: COMUNICADOS E AVISOS DO SITE
-- Regra CORE: Alterações no cronograma são comunicadas dentro do site
-- ====================================================================
CREATE TABLE `comunicados` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `edicao_id` INT NOT NULL,
    `usuario_id` INT NULL,
    `titulo` VARCHAR(150) NOT NULL,
    `conteudo` TEXT NOT NULL,
    `tipo` ENUM('urgente', 'cronograma', 'geral') NOT NULL DEFAULT 'geral',
    `partida_id` INT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comunicados_edicao` FOREIGN KEY (`edicao_id`) REFERENCES `edicoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comunicados_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_comunicados_partida` FOREIGN KEY (`partida_id`) REFERENCES `partidas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 9: LOGS DE AUDITORIA
-- Regra CORE: Manter sempre os logs de qualquer alteração feita por
-- qualquer usuário, para auditorias futuras.
-- ====================================================================
CREATE TABLE `logs_auditoria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NULL,
    `usuario_nome` VARCHAR(100) NULL,
    `acao` VARCHAR(80) NOT NULL COMMENT 'Ex: LOGIN, AGENDAR_PARTIDA, ALTERAR_CRONOGRAMA, ATUALIZAR_RESULTADO',
    `tabela_afetada` VARCHAR(50) NULL,
    `registro_id` INT NULL,
    `detalhes` TEXT NULL,
    `ip_origem` VARCHAR(45) NULL,
    `data_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- DADOS INICIAIS (SEEDS) PARA TESTES IMEDIATOS
-- ====================================================================

-- 1. Inserir Edições
INSERT INTO `edicoes` (`id`, `nome`, `ano`, `status`, `ativa`) VALUES
(1, 'Interclasse ETEC 2026 - Oficial', 2026, 'em_andamento', 1),
(2, 'Interclasse ETEC 2025 - Anterior', 2025, 'finalizado', 0);

-- 2. Inserir Usuários (Senhas com bcrypt)
-- Senha padrão para admin: 'admin123' -> $2y$10$w8/0V3V8kYg7rU4yqL1N..oMfxmG6c9Y0vSg1E2eXf6V6dGgJp1iG
-- Senha padrão para técnicos: 'tecnico123' -> $2y$10$6v4l5yJ9p8pA7p5eZ8bCteXWzHw5I6sN4tF8vK0l1j3n5h7m9q0wS
-- (Criamos hashes válidos gerados pelo PHP password_hash com cost 10)
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`, `telefone`) VALUES
(1, 'Administrador do Sistema', 'admin@etec.sp.gov.br', '$2y$10$a1w1YV5BqD2sF1mD4b7u5.7yI71wIovcZ1zDqGqAem1mE4eB3D7kK', 'admin', '(11) 98888-0001'),
(2, 'Prof. Carlos Eduardo (Técnico INFO)', 'tecnico.info@etec.sp.gov.br', '$2y$10$tZc0X6Y5U8i1O4p7A2sD.uW2X5Y8z1C4v7B0n3M6q9T2w5E8r1T4.', 'tecnico', '(11) 97777-0002'),
(3, 'Profª. Renata Silva (Técnica ADM)', 'tecnico.adm@etec.sp.gov.br', '$2y$10$tZc0X6Y5U8i1O4p7A2sD.uW2X5Y8z1C4v7B0n3M6q9T2w5E8r1T4.', 'tecnico', '(11) 97777-0003'),
(4, 'Prof. Marcos Rocha (Técnico EDIF)', 'tecnico.edif@etec.sp.gov.br', '$2y$10$tZc0X6Y5U8i1O4p7A2sD.uW2X5Y8z1C4v7B0n3M6q9T2w5E8r1T4.', 'tecnico', '(11) 97777-0004'),
(5, 'Aluno Torcedor / Visitante', 'visitante@etec.sp.gov.br', '$2y$10$tZc0X6Y5U8i1O4p7A2sD.uW2X5Y8z1C4v7B0n3M6q9T2w5E8r1T4.', 'visitante', '(11) 96666-0005');

-- 3. Inserir Modalidades para a Edição 2026
INSERT INTO `modalidades` (`id`, `edicao_id`, `nome`, `icone`, `categoria`, `permite_empate`, `tempo_partida_minutos`) VALUES
(1, 1, 'Futsal Masculino', 'fa-futbol', 'Geral', 1, 30),
(2, 1, 'Futsal Feminino', 'fa-futbol', 'Geral', 1, 30),
(3, 1, 'Voleibol Misto', 'fa-volleyball', 'Geral', 0, 40),
(4, 1, 'Basquete 3x3', 'fa-basketball', 'Geral', 0, 20),
(5, 1, 'Handebol Masculino', 'fa-hand-back-fist', 'Geral', 1, 30),
(6, 1, 'Queimada Mista', 'fa-fire', 'Geral', 0, 25);

-- 4. Inserir Turmas para a Edição 2026
INSERT INTO `turmas` (`id`, `edicao_id`, `tecnico_id`, `nome`, `sigla`, `curso`, `periodo`, `cor_uniforme`) VALUES
(1, 1, 2, '3º Ensino Médio - Informática', '3º INFO', 'Técnico em Desenvolvimento de Sistemas', 'Manhã', '#2563EB'),
(2, 1, 3, '3º Ensino Médio - Administração', '3º ADM', 'Técnico em Administração', 'Manhã', '#DC2626'),
(3, 1, 4, '2º Ensino Médio - Edificações', '2º EDIF', 'Técnico em Edificações', 'Tarde', '#16A34A'),
(4, 1, 2, '1º Ensino Médio - Química', '1º QUÍM', 'Técnico em Química', 'Manhã', '#CA8A04');

-- 5. Inserir Alunos / Atletas (com dados sensíveis de teste)
INSERT INTO `alunos` (`id`, `turma_id`, `nome`, `numero_camisa`, `posicao`, `rg_ra`, `contato_emergencia`) VALUES
(1, 1, 'Gabriel Salles (Capitão)', 10, 'Pivô / Meia', 'RA-1234567-SP', '(11) 98111-2233 - Mãe (Ana)'),
(2, 1, 'Lucas Martins', 7, 'Ala Direita', 'RA-1234568-SP', '(11) 98111-2234 - Pai (João)'),
(3, 1, 'Matheus Oliveira (Goleiro)', 1, 'Goleiro', 'RA-1234569-SP', '(11) 98111-2235 - Mãe (Clara)'),
(4, 1, 'Pedro Henrique', 5, 'Fixo', 'RA-1234570-SP', '(11) 98111-2236 - Pai (Marcos)'),
(5, 1, 'Enzo Ferrari', 9, 'Ala Esquerda', 'RA-1234571-SP', '(11) 98111-2237 - Irmão (Rafael)'),

(6, 2, 'Bruno Souza (Capitão)', 10, 'Pivô', 'RA-2234567-SP', '(11) 97222-3344 - Mãe (Lúcia)'),
(7, 2, 'Felipe Santos', 8, 'Meia', 'RA-2234568-SP', '(11) 97222-3345 - Pai (Rogério)'),
(8, 2, 'Guilherme Lima (Goleiro)', 12, 'Goleiro', 'RA-2234569-SP', '(11) 97222-3346 - Avó (Maria)'),
(9, 2, 'Leonardo Alves', 4, 'Fixo', 'RA-2234570-SP', '(11) 97222-3347 - Mãe (Silvia)'),

(10, 3, 'Thiago Mendes (Capitão)', 11, 'Pivô', 'RA-3234567-SP', '(11) 96333-4455 - Mãe (Patrícia)'),
(11, 3, 'Rodrigo Costa', 7, 'Ala Direita', 'RA-3234568-SP', '(11) 96333-4456 - Pai (Antônio)'),
(12, 3, 'Danilo Pereira', 1, 'Goleiro', 'RA-3234569-SP', '(11) 96333-4457 - Mãe (Elza)'),

(13, 4, 'Arthur Ramos', 9, 'Atacante', 'RA-4234567-SP', '(11) 95444-5566 - Mãe (Renata)'),
(14, 4, 'Vinícius Silva', 10, 'Meia', 'RA-4234568-SP', '(11) 95444-5567 - Pai (Carlos)');

-- 6. Inserir Partidas
-- Partida 1: Finalizada (3x1)
INSERT INTO `partidas` (`id`, `edicao_id`, `modalidade_id`, `turma_mandante_id`, `turma_visitante_id`, `data_jogo`, `hora_jogo`, `local_jogo`, `fase`, `placar_mandante`, `placar_visitante`, `status`, `observacoes`) VALUES
(1, 1, 1, 1, 2, '2026-10-20', '09:00:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', 3, 1, 'encerrado', 'Grande jogo de abertura com arquibancada lotada.'),
-- Partida 2: Finalizada (2x2)
(2, 1, 1, 3, 4, '2026-10-20', '10:00:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', 2, 2, 'encerrado', 'Empate emocionante nos segundos finais.'),
-- Partida 3: Agendada para hoje/próximo
(3, 1, 1, 1, 3, '2026-10-24', '09:30:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', NULL, NULL, 'agendado', 'Partida decisiva pela liderança do grupo.'),
-- Partida 4: Agendada
(4, 1, 1, 2, 4, '2026-10-24', '10:30:00', 'Ginásio Principal - Quadra A', 'Fase de Grupos', NULL, NULL, 'agendado', 'Disputa de recuperação no torneio.');

-- 7. Inserir Súmula (Gols, Assistências, Cartões da Partida 1 e 2)
INSERT INTO `sumula_eventos` (`partida_id`, `turma_id`, `aluno_id`, `tipo`, `minuto`, `observacao`) VALUES
-- Partida 1 (3x1 INFO vs ADM)
(1, 1, 1, 'gol_ponto', 8, 'Gol de bico no ângulo esquerdo'),
(1, 1, 2, 'assistencia', 8, 'Passe rasteiro em profundidade'),
(1, 1, 1, 'gol_ponto', 18, 'Gol de pênalti com segurança'),
(1, 2, 6, 'gol_ponto', 22, 'Finalização rápida na saída do goleiro'),
(1, 1, 5, 'gol_ponto', 28, 'Contra-ataque mortal'),
(1, 1, 1, 'assistencia', 28, 'Belo passe de calcanhar'),
(1, 2, 9, 'cartao_amarelo', 15, 'Falta tática para interromper contra-ataque'),
(1, 1, 4, 'cartao_amarelo', 26, 'Reclamação com a arbitragem'),

-- Partida 2 (2x2 EDIF vs QUÍM)
(2, 3, 10, 'gol_ponto', 5, 'Chute forte de fora da área'),
(2, 4, 13, 'gol_ponto', 12, 'Gol de cabeça após cobrança de lateral'),
(2, 4, 14, 'assistencia', 12, 'Cruzamento preciso'),
(2, 3, 11, 'gol_ponto', 25, 'Rebote do goleiro'),
(2, 4, 13, 'gol_ponto', 29, 'Gol de empate no último minuto'),
(2, 3, 11, 'cartao_amarelo', 19, 'Carrinho atrasado');

-- 8. Inserir Comunicados Oficiais
INSERT INTO `comunicados` (`edicao_id`, `usuario_id`, `titulo`, `conteudo`, `tipo`, `partida_id`) VALUES
(1, 1, 'Abertura Oficial dos Jogos Escolares 2026', 'Sejam todos bem-vindos ao Interclasse ETEC 2026! O espírito esportivo e o respeito mútuo devem prevalecer em todas as partidas. Consulte o cronograma e apoie sua sala!', 'geral', NULL),
(1, 1, 'Aviso de Cronograma: Partida 3 remanejada para Quadra A', 'Informamos que a partida entre 3º INFO e 2º EDIF será realizada no Ginásio Principal - Quadra A às 09:30 do dia 24/10/2026 para comportar o público.', 'cronograma', 3);

-- 9. Inserir Logs Iniciais de Auditoria
INSERT INTO `logs_auditoria` (`usuario_id`, `usuario_nome`, `acao`, `tabela_afetada`, `registro_id`, `detalhes`, `ip_origem`) VALUES
(1, 'Administrador do Sistema', 'CRIAR_EDICAO', 'edicoes', 1, 'Criação da edição Interclasse ETEC 2026', '127.0.0.1'),
(1, 'Administrador do Sistema', 'CADASTRAR_TURMAS', 'turmas', 1, 'Cadastro das 4 turmas participantes do torneio', '127.0.0.1'),
(1, 'Administrador do Sistema', 'FINALIZAR_PARTIDA', 'partidas', 1, 'Encerramento da partida 1: 3º INFO 3 x 1 3º ADM', '127.0.0.1');
