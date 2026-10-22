<?php
/**
 * Tela de Autenticação (Login e Cadastro)
 * Mobile-First, Tailwind CSS, Senhas BCRYPT
 */

$pageTitle = 'Entrar no Sistema';
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redireciona para a home
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 flex items-center justify-center p-4 sm:p-6 my-6 sm:my-12">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden">
        
        <!-- Topo com Logo e Boas-Vindas -->
        <div class="bg-gradient-to-br from-slate-900 to-blue-950 p-6 sm:p-8 text-center text-white relative">
            <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-white mx-auto mb-3 shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-trophy text-2xl text-amber-300"></i>
            </div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight">Gerenciador de Interclasse</h1>
            <p class="text-xs text-blue-200 mt-1">Acesso seguro para técnicos, alunos e comissão</p>
        </div>

        <!-- Seletor de Abas (Login / Cadastro) -->
        <div class="flex border-b border-slate-200 bg-slate-50 text-xs font-bold text-slate-500">
            <button type="button" id="tab-login" onclick="alternarAba('login')" class="flex-1 py-3 text-center border-b-2 border-blue-600 text-blue-600 bg-white transition">
                <i class="fa-solid fa-right-to-bracket mr-1"></i> Acessar Conta
            </button>
            <button type="button" id="tab-registro" onclick="alternarAba('registro')" class="flex-1 py-3 text-center border-b-2 border-transparent hover:text-slate-700 transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Criar Conta / Técnico
            </button>
        </div>

        <div class="p-6 sm:p-8">
            
            <!-- Formulário 1: Login -->
            <form id="form-login" action="actions/auth_action.php?action=login" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">E-mail Institucional</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="input-email" required placeholder="seu.email@etec.sp.gov.br" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Senha de Acesso</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="senha" id="input-senha" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-md hover:shadow-lg">
                    Entrar no Sistema
                </button>

                <!-- Credenciais Rápidas de Teste (Demo) -->
                <div class="mt-6 pt-5 border-t border-slate-100">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2 text-center">
                        Atalhos de Acesso Rápido para Teste
                    </span>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" onclick="preencherDemo('admin@etec.sp.gov.br', 'admin123')" class="p-2 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 text-left transition text-[11px]">
                            <span class="font-bold block text-slate-800">👑 Administrador</span>
                            <span class="text-[10px] text-slate-500 font-mono">admin123</span>
                        </button>
                        <button type="button" onclick="preencherDemo('tecnico.info@etec.sp.gov.br', 'tecnico123')" class="p-2 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 border border-slate-200 text-left transition text-[11px]">
                            <span class="font-bold block text-slate-800">📋 Técnico INFO</span>
                            <span class="text-[10px] text-slate-500 font-mono">tecnico123</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Formulário 2: Registro -->
            <form id="form-registro" action="actions/auth_action.php?action=registro" method="POST" class="space-y-4 hidden">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nome Completo *</label>
                    <input type="text" name="nome" required placeholder="Prof. João da Silva" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">E-mail *</label>
                    <input type="email" name="email" required placeholder="joao.silva@etec.sp.gov.br" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Perfil Desejado</label>
                        <select name="perfil" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                            <option value="tecnico">Técnico de Sala</option>
                            <option value="visitante">Aluno / Visitante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Telefone / WhatsApp</label>
                        <input type="text" name="telefone" placeholder="(11) 99999-8888" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Senha (Mínimo 6 caracteres) *</label>
                    <input type="password" name="senha" required minlength="6" placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="p-3 bg-emerald-50 rounded-xl text-[11px] text-emerald-800 border border-emerald-200 leading-relaxed">
                    <i class="fa-solid fa-lock mr-1"></i>
                    Sua senha será protegida com hash seguro <strong>bcrypt</strong> antes de ser armazenada.
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-md hover:shadow-lg">
                    Concluir Cadastro
                </button>
            </form>

        </div>
    </div>
</main>

<script>
function alternarAba(aba) {
    const tabLogin = document.getElementById('tab-login');
    const tabRegistro = document.getElementById('tab-registro');
    const formLogin = document.getElementById('form-login');
    const formRegistro = document.getElementById('form-registro');

    if (aba === 'login') {
        tabLogin.className = 'flex-1 py-3 text-center border-b-2 border-blue-600 text-blue-600 bg-white transition';
        tabRegistro.className = 'flex-1 py-3 text-center border-b-2 border-transparent hover:text-slate-700 transition';
        formLogin.classList.remove('hidden');
        formRegistro.classList.add('hidden');
    } else {
        tabRegistro.className = 'flex-1 py-3 text-center border-b-2 border-emerald-600 text-emerald-600 bg-white transition';
        tabLogin.className = 'flex-1 py-3 text-center border-b-2 border-transparent hover:text-slate-700 transition';
        formRegistro.classList.remove('hidden');
        formLogin.classList.add('hidden');
    }
}

function preencherDemo(email, senha) {
    document.getElementById('input-email').value = email;
    document.getElementById('input-senha').value = senha;
    AppUI.toast('Credenciais preenchidas! Clique em Entrar no Sistema.', 'info', 'Atalho de Teste');
}

if (window.location.hash === '#registro') {
    alternarAba('registro');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
