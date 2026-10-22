<?php
/**
 * Rodapé Padrão (Footer)
 * Inclui o sistema de TOAST e MODAL e scripts globais
 */

require_once __DIR__ . '/modal_toast.php';
?>

<footer class="mt-auto bg-slate-900 border-t border-slate-800 text-slate-400 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Coluna 1: Sobre o Evento -->
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold">
                        <i class="fa-solid fa-trophy text-amber-300"></i>
                    </div>
                    <span class="text-white font-extrabold text-base tracking-tight">Gerenciador de Interclasse</span>
                </div>
                <p class="text-slate-400 text-xs leading-relaxed max-w-md">
                    Plataforma oficial de organização esportiva escolar. Transparência, respeito e integração entre turmas com resultados em tempo real, controle de pontuação automática e proteção de dados.
                </p>
                <div class="flex items-center gap-4 mt-4 text-slate-500">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-emerald-500"></i> Dados Protegidos</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-bolt text-amber-400"></i> Atualizações Frequentes</span>
                </div>
            </div>

            <!-- Coluna 2: Acesso Rápido -->
            <div>
                <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">Acesso Rápido</h4>
                <ul class="space-y-2">
                    <li><a href="classificacao.php" class="hover:text-blue-400 transition">Tabela de Classificação</a></li>
                    <li><a href="jogos.php" class="hover:text-blue-400 transition">Cronograma de Jogos</a></li>
                    <li><a href="estatisticas.php" class="hover:text-blue-400 transition">Artilharia & Cartões</a></li>
                    <li><a href="comunicados.php" class="hover:text-blue-400 transition">Mural de Comunicados</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Auditoria & Informações -->
            <div>
                <h4 class="text-white font-bold text-xs uppercase tracking-wider mb-3">Transparência</h4>
                <ul class="space-y-2">
                    <li><a href="historico_logs.php" class="hover:text-blue-400 transition">Auditoria do Sistema</a></li>
                    <li><a href="turmas.php" class="hover:text-blue-400 transition">Turmas Inscritas</a></li>
                    <li><a href="login.php" class="hover:text-blue-400 transition">Área do Técnico / Admin</a></li>
                    <li><a href="install.php" class="hover:text-blue-400 transition">Verificar Banco de Dados</a></li>
                </ul>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left text-[11px] text-slate-500">
            <div>
                &copy; <?= date('Y') ?> Interclasse Escolar ETEC. Todos os direitos reservados.
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1">
                    <i class="fa-solid fa-code text-blue-500"></i> PHP & Tailwind CSS
                </span>
                <span>•</span>
                <span class="inline-flex items-center gap-1">
                    <i class="fa-solid fa-database text-emerald-500"></i> MySQL PDO
                </span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
