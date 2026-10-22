<?php
/**
 * Componente Visual de TOAST e MODAL Customizado
 * 
 * Atende às regras:
 * - Mensagens de erro/sucesso claras na interface (TOAST)
 * - Nenhuma caixa nativa do navegador (alert / confirm); tudo tratado em MODAL
 */

require_once __DIR__ . '/flash.php';
$flashMessages = get_flash_messages();
?>

<!-- Container Flutuante para Toasts -->
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-sm w-full px-4 sm:px-0 pointer-events-none"></div>

<!-- Modal Genérico do Sistema (Substituto de alert() e confirm()) -->
<div id="system-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4 transition-opacity duration-300 opacity-0 pointer-events-none">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 transform scale-95 transition-transform duration-300" id="system-modal-box">
        <!-- Ícone Dinâmico -->
        <div id="system-modal-icon-container" class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 bg-blue-100 text-blue-600">
            <i id="system-modal-icon" class="fa-solid fa-circle-info text-2xl"></i>
        </div>

        <!-- Título e Mensagem -->
        <h3 id="system-modal-title" class="text-xl font-bold text-center text-slate-800 mb-2">Confirmação</h3>
        <p id="system-modal-message" class="text-sm text-center text-slate-600 mb-6 leading-relaxed">Mensagem descritiva</p>

        <!-- Ações do Modal -->
        <div class="flex items-center justify-center gap-3" id="system-modal-actions">
            <button type="button" id="system-modal-cancel" class="flex-1 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">
                Cancelar
            </button>
            <button type="button" id="system-modal-confirm" class="flex-1 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm shadow-md">
                Confirmar
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Biblioteca Global de Notificações Toast e Modais
 */
const AppUI = {
    // -------------------------------------------------------------
    // SISTEMA DE TOAST
    // -------------------------------------------------------------
    toast(message, type = 'info', title = null) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const config = {
            success: { bg: 'bg-emerald-600', icon: 'fa-check-circle', titleDefault: 'Sucesso!' },
            error:   { bg: 'bg-rose-600',    icon: 'fa-circle-xmark', titleDefault: 'Atenção / Erro!' },
            warning: { bg: 'bg-amber-600',   icon: 'fa-triangle-exclamation', titleDefault: 'Aviso!' },
            info:    { bg: 'bg-blue-600',    icon: 'fa-circle-info',  titleDefault: 'Informação' }
        }[type] || { bg: 'bg-slate-800', icon: 'fa-bell', titleDefault: 'Notificação' };

        const toastEl = document.createElement('div');
        toastEl.className = `${config.bg} text-white p-4 rounded-xl shadow-2xl flex items-start gap-3 transform translate-y-2 opacity-0 transition-all duration-300 pointer-events-auto border border-white/20`;

        toastEl.innerHTML = `
            <div class="text-xl flex-shrink-0 mt-0.5">
                <i class="fa-solid ${config.icon}"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-bold text-sm tracking-wide leading-tight">${title || config.titleDefault}</h4>
                <p class="text-xs text-white/90 mt-0.5 break-words">${message}</p>
            </div>
            <button type="button" class="text-white/70 hover:text-white transition flex-shrink-0" onclick="this.parentElement.remove()">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        `;

        container.appendChild(toastEl);

        // Animação de entrada
        requestAnimationFrame(() => {
            toastEl.classList.remove('translate-y-2', 'opacity-0');
        });

        // Auto remoção após 4.5 segundos
        setTimeout(() => {
            toastEl.classList.add('opacity-0', 'scale-95');
            setTimeout(() => toastEl.remove(), 300);
        }, 4500);
    },

    // -------------------------------------------------------------
    // SISTEMA DE MODAL (Substituição de alert e confirm)
    // -------------------------------------------------------------
    modalCallback: null,

    modal({ title = 'Aviso', message = '', type = 'confirm', confirmText = 'Confirmar', cancelText = 'Cancelar', onConfirm = null }) {
        const modal = document.getElementById('system-modal');
        const box = document.getElementById('system-modal-box');
        const titleEl = document.getElementById('system-modal-title');
        const msgEl = document.getElementById('system-modal-message');
        const iconEl = document.getElementById('system-modal-icon');
        const iconContainer = document.getElementById('system-modal-icon-container');
        const cancelBtn = document.getElementById('system-modal-cancel');
        const confirmBtn = document.getElementById('system-modal-confirm');

        titleEl.textContent = title;
        msgEl.innerHTML = message;
        confirmBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;

        // Customizar cores e ícones pelo tipo
        if (type === 'danger' || type === 'error') {
            iconContainer.className = 'w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 bg-rose-100 text-rose-600';
            iconEl.className = 'fa-solid fa-triangle-exclamation text-2xl';
            confirmBtn.className = 'flex-1 py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl transition text-sm shadow-md';
        } else if (type === 'alert') {
            iconContainer.className = 'w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 bg-blue-100 text-blue-600';
            iconEl.className = 'fa-solid fa-circle-info text-2xl';
            confirmBtn.className = 'flex-1 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm shadow-md';
        } else {
            iconContainer.className = 'w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 bg-blue-100 text-blue-600';
            iconEl.className = 'fa-solid fa-circle-question text-2xl';
            confirmBtn.className = 'flex-1 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm shadow-md';
        }

        // Se for alerta simples, esconde botão cancelar
        if (type === 'alert') {
            cancelBtn.classList.add('hidden');
        } else {
            cancelBtn.classList.remove('hidden');
        }

        this.modalCallback = onConfirm;

        // Mostrar com animação suave
        modal.classList.remove('hidden', 'pointer-events-none');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            box.classList.remove('scale-95');
        });
    },

    closeModal() {
        const modal = document.getElementById('system-modal');
        const box = document.getElementById('system-modal-box');
        if (!modal) return;
        modal.classList.add('opacity-0');
        box.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
            this.modalCallback = null;
        }, 200);
    },

    confirm(message, onConfirm, title = 'Confirmar Ação', type = 'danger') {
        this.modal({
            title: title,
            message: message,
            type: type,
            confirmText: 'Sim, Prosseguir',
            cancelText: 'Cancelar',
            onConfirm: onConfirm
        });
    },

    alert(message, title = 'Atenção') {
        this.modal({
            title: title,
            message: message,
            type: 'alert',
            confirmText: 'Entendido',
            onConfirm: null
        });
    }
};

// Configurar ouvintes de eventos para fechar modal
document.addEventListener('DOMContentLoaded', () => {
    const cancelBtn = document.getElementById('system-modal-cancel');
    const confirmBtn = document.getElementById('system-modal-confirm');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => AppUI.closeModal());
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const cb = AppUI.modalCallback;
            AppUI.closeModal();
            if (typeof cb === 'function') {
                cb();
            }
        });
    }

    // Interceptar qualquer formulário ou link com [data-confirm]
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm') || 'Tem certeza que deseja executar esta ação?';
            const title = this.getAttribute('data-title') || 'Confirmar Operação';
            const type = this.getAttribute('data-type') || 'danger';
            
            AppUI.confirm(message, () => {
                if (this.tagName === 'A') {
                    window.location.href = this.href;
                } else if (this.form) {
                    this.form.submit();
                }
            }, title, type);
        });
    });

    // Despachar mensagens Flash vindas do backend PHP
    <?php if (!empty($flashMessages)): ?>
        <?php foreach ($flashMessages as $msg): ?>
            AppUI.toast(
                <?= json_encode($msg['message']) ?>, 
                <?= json_encode($msg['type']) ?>, 
                <?= json_encode($msg['title']) ?>
            );
        <?php endforeach; ?>
    <?php endif; ?>
});
</script>
