<?php
/**
 * Cabeçalho Padrão (Header)
 * Frontend: HTML5, CSS, Tailwind CSS (Mobile-First)
 */

if (!isset($pageTitle)) {
    $pageTitle = 'Gerenciador de Interclasse Escolar';
}

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/auth.php';

$edicaoAtiva = get_active_edicao($pdo);
$usuarioLogado = get_logged_user();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($edicaoAtiva['nome'] ?? 'Interclasse') ?></title>
    
    <!-- Meta tags SEO / PWA Mobile -->
    <meta name="description" content="Sistema de Gestão Esportiva Escolar - Interclasse">
    <meta name="theme-color" content="#1d4ed8">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            60: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        school: {
                            gold: '#f59e0b',
                            trophy: '#eab308',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Estilos Customizados Adicionais -->
    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        /* Mobile tap highlight fix */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-800 bg-slate-50 font-sans antialiased selection:bg-blue-500 selection:text-white">
