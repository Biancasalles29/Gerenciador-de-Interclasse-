# Gerenciador de Interclasse Escolar 🏆

Sistema completo desenvolvido para organização, controle e gestão transparente de jogos e torneios esportivos escolares, atendendo rigorosamente a todos os requisitos do documento `requisitos.md`.

---

## 🚀 Stack Tecnológica

- **Backend:** PHP estruturado com sessões nativas (`session_start()`).
- **Banco de Dados:** MySQL / MariaDB utilizando **PDO** (`PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`, Prepared Statements contra SQL Injection).
- **Frontend:** HTML5 semântico, Tailwind CSS (Mobile-First), FontAwesome 6, Google Fonts (Inter).
- **Notificações & Diálogos:** Sistema integrado de **TOAST** flutuante e **MODAL** customizado (substituindo `alert()` e `confirm()` nativos).

---

## 🎯 Regras de Negócio e Funcionalidades (CORE)

| Regra / Requisito | Como foi Implementado |
| :--- | :--- |
| **Senhas com Hash Bcrypt** | Senhas tratadas exclusivamente com `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10])` e verificadas com `password_verify`. |
| **Página de Históricos & Auditoria** | Tabela `logs_auditoria` e tela `historico_logs.php` registrando data/hora, usuário, ação (LOGIN, ALTERAR_CRONOGRAMA, RESULTADO, etc.), tabela afetada, diff e IP para auditoria futura. |
| **Perfis e Permissões (RBAC)** | - **Administrador:** Acesso irrestrito a configurações, edições, modalidades, cronograma, placares e usuários.<br>- **Técnicos:** Podem cadastrar e gerenciar exclusivamente suas salas e os atletas de suas salas.<br>- **Visitantes/Alunos:** Acesso público a tabelas, jogos, classificações, artilharia e comunicados. |
| **Pontuação e Classificação Automática** | Vitória = 3 pontos, Empate = 1 ponto, Derrota = 0 pontos. A classificação (`classificacao.php`) é calculada em tempo real com critérios oficiais de desempate (PTS > V > SG > GP > GC). |
| **Partidas com Data, Hora e Local** | Toda partida possui obrigatoriamente `data_jogo`, `hora_jogo` e `local_jogo`. |
| **Equipes Vinculadas a Turmas** | Confrontos associam diretamente `turma_mandante_id` e `turma_visitante_id`. |
| **Anti-Choque de Horário** | Validação antes de salvar: uma equipe **só pode jogar 1 partida por horário**. Se houver conflito de horário/data para a mesma turma, o sistema bloqueia e emite um Toast de alerta. |
| **Comunicação de Alteração de Cronograma** | Quando a data, horário ou local de uma partida é alterado, o sistema registra automaticamente um comunicado oficial na tabela `comunicados` com tipo `cronograma`, alertando todos os usuários no site. |
| **Proteção de Dados Pessoais** | Dados de estudantes (RG/RA, contatos de emergência) são protegidos. Apenas o técnico da própria sala e administradores têm permissão para visualizá-los; para os demais usuários, os dados são mascarados como `•••••••• (Restrito)`. |
| **Isolamento de Edições** | Todas as tabelas de turmas, jogos, súmulas e comunicados são atreladas a `edicao_id`. Os dados de uma edição **nunca interferem em outra**. |
| **Interface Mobile-First & Toasts** | Design 100% responsivo para celulares e desktops com gaveta de menu móvel, modais Tailwind modernos e toasts flutuantes para sucesso/erro. |

---

## 📁 Estrutura de Arquivos

```text
gerenciador-de-interclasse/
│
├── config/
│   └── database.php                # Configuração de credenciais MySQL
├── bd.php                          # Conexão central via PDO (Prepared Statements)
├── database.sql                    # Script SQL completo com schema e seeds
│
├── includes/
│   ├── auth.php                    # Controle de sessão, RBAC, bcrypt e proteção de dados
│   ├── logger.php                  # Função global de log de auditoria
│   ├── flash.php                   # Mensagens Flash (TOAST)
│   ├── classificacao_helper.php    # Cálculo dinâmico dos 3 pontos e classificação
│   ├── header.php                  # Header HTML5, Tailwind CSS, FontAwesome
│   ├── navbar.php                  # Barra de navegação responsiva mobile-first
│   ├── footer.php                  # Rodapé padrão
│   └── modal_toast.php             # Componentes JS/Tailwind para TOAST e MODAL
│
├── actions/                        # Controllers isolados (Sem monólito)
│   ├── auth_action.php             # Login, logout e registro de técnico
│   ├── turmas_action.php           # CRUD de salas (restrito ao técnico ou admin)
│   ├── alunos_action.php           # CRUD de atletas (com dados sensíveis protegidos)
│   ├── partidas_action.php         # Agendamento, validação anti-choque e comunicado
│   ├── resultados_action.php       # Lançamento de placar e recálculo
│   ├── sumula_action.php           # Registro de gols, assistências e cartões
│   ├── modalidades_action.php      # Gestão de modalidades esportivas
│   ├── comunicados_action.php      # Postagem de comunicados oficiais
│   ├── edicoes_action.php          # Alternância e criação de edições
│   └── admin_usuarios_action.php   # Gestão de usuários e permissões
│
├── views/                          # Telas de gestão e módulos específicos
│   ├── modalidades.php             # Gestão de esportes
│   ├── edicoes.php                 # Gestão de edições do torneio
│   └── admin_usuarios.php          # Gestão de contas e perfis
│
├── index.php                       # Dashboard principal com destaques e próximos jogos
├── classificacao.php               # Tabela de classificação com pontuação automática (3-1-0)
├── jogos.php                       # Calendário oficial, filtros e agendamentos
├── partida_detalhes.php            # Súmula do jogo: gols, assistências, cartões e elencos
├── estatisticas.php                # Artilharia geral, assistências e fair play (cartões)
├── comunicados.php                 # Mural de avisos e notificações de alterações
├── turmas.php                      # Salas inscritas e cadastro por técnicos
├── turma_detalhes.php              # Detalhes da turma e lista de alunos protegida
├── historico_logs.php              # Histórico de auditoria com filtros
├── login.php                       # Tela de login e cadastro com atalhos de teste
├── install.php                     # Instalador com assistente visual em 1 clique
└── README.md                       # Documentação do projeto
```

---

## 💻 Como Executar o Projeto

### Opção 1: Via XAMPP / WampServer (Recomendado)
1. Inicie o **Apache** e o **MySQL** no painel do XAMPP.
2. Certifique-se de que a pasta `gerenciador-de-interclasse` está em `C:\xampp\htdocs\gerenciador-de-interclasse`.
3. Abra o navegador e acerte o banco de dados em:
   ```text
   http://localhost/gerenciador-de-interclasse/install.php
   ```
4. Clique em **"Criar Banco e Importar Dados"**. O sistema criará o banco `interclasse_db` com todas as 9 tabelas e dados de teste instantaneamente!
5. Acesse o sistema em:
   ```text
   http://localhost/gerenciador-de-interclasse/index.php
   ```

### Opção 2: Via PHP Built-in Server
Se você tiver o PHP e MySQL instalados:
```bash
php -S localhost:8000
```
E acesse `http://localhost:8000/install.php`.

---

## 🔑 Credenciais Pré-Cadastradas para Demonstração

| Perfil | E-mail | Senha | O que pode fazer |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@etec.sp.gov.br` | `admin123` | Acesso total: edições, modalidades, cronograma, resultados, usuários, logs de auditoria. |
| **Técnico (INFO)** | `tecnico.info@etec.sp.gov.br` | `tecnico123` | Gerenciar a turma 3º INFO, cadastrar/editar seus alunos e ver seus dados protegidos. |
| **Técnico (ADM)** | `tecnico.adm@etec.sp.gov.br` | `tecnico123` | Gerenciar a turma 3º ADM e inscrever seus atletas. |
| **Visitante** | `visitante@etec.sp.gov.br` | `tecnico123` | Visualização pública de jogos, classificações, artilharia e comunicados. |

*(Na tela de login, há botões rápidos que preenchem as credenciais de teste com apenas 1 clique).*

---

## 🛡️ Segurança e Integridade

- **Prepared Statements Obrigatórios:** Toda consulta utiliza parâmetros bindados (`:param`) com PDO, eliminando riscos de SQL Injection.
- **Transações PDO:** Operações críticas de resultados e súmula utilizam `$pdo->beginTransaction()`, `$pdo->commit()` e `$pdo->rollBack()`.
- **Prevenção de Perda de Dados:** Turmas com partidas agendadas ou finalizadas não podem ser excluídas sem antes tratar o cronograma.
- **Auditoria Contínua:** Todas as ações (criação, edição, exclusão, logins) geram registros imutáveis em `logs_auditoria` com IP, autor e detalhes da alteração.
