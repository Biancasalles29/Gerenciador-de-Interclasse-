# Gerenciador de Interclasse

## Objetivos
crie um site desenvolvido para organizar jogos escolares. onde os tecnicos de suas devidas salas podem cadastrar seus alunos e olhar demais informações(artilharia, assistencias cartões amarelos e vermelhos, data local e horario.)

### Stack Tecnlógico
- Backend: PHP estruturado com sessões nativas
- Banco de dados: MySQL (PDO para segurança)
- Frontend: HTML5, PHP, CSS, Tailwind CSS

#### Regras de negócio (CORE)
Tratar senhas de usuários com hash bcript
O sistema deve ter uma página de históricos e manter sempre os logs de qualquer alteração feita por 
qualquer usuário, para auditorias futuras.
Somente os tecnicos poderiam cadastrar suas salas, as demais pessoas teriam acesso somente as informações vitoria vale 3 pontos empate 1 e derrota 0 pontos o sistema deve calcular os pontos automaticamente assim como classificação que tambem deve ser atualizada automaticamente após cada resultado. usuarios e permissões administrador tem acesso a tudo tecnicos tem acesso a informações e acesso a cadastrar as salas e demais pessoas tem acesso somente as informações.
 


##### Regras Globais
- Use sempre PDO para conexão e queries no MySQL para evitar SQL Injections
- Mantenha o código limpo e comente apenas logicas complexas.
- Separe os arquivos de forma lógica: um arquivo para conexão com a base (bd.php) e scripts de backend isolados e views em HTML5/PHP, nunca faça o sistema como um monolito, deixe sempre separados todas as regras para facilitar os futuros upgrades.
 - Estilize as telas em Tailwind de forma responsiva priorizando o MobileFrist.
 - Retorne sempre as mensagens de erros de forma claras na interface para o usuário (TOAST)
 - sempre trate as mensagens de caixa de mensagens nativas do navegador em um MODAL. dados pessoais devem ser acessiveis somente aos usuarios que tem permissao, erros nao devem causar perda de dados dados de uma edição de interclasse nao podem interferir em outra transparencia em relação a resultados e demais informações
