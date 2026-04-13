<?php
/**
 * Partial común del <head>: Tailwind CDN + config + Inter font + tokens de diseño.
 * Usar: <?php partial('partials/head'); ?>
 * El sistema de design tokens se inyecta via tailwind.config para que funcione
 * con clases arbitrarias (bg-brand-600, text-ink-900, etc.)
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#4F46E5">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%234F46E5'/><text x='50' y='68' text-anchor='middle' fill='white' font-size='60' font-family='Arial' font-weight='800'>T</text></svg>">

<!-- Tailwind CDN + config -->
<script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                display: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                brand: {
                    50:  '#EEF2FF',
                    100: '#E0E7FF',
                    200: '#C7D2FE',
                    300: '#A5B4FC',
                    400: '#818CF8',
                    500: '#6366F1',
                    600: '#4F46E5',
                    700: '#4338CA',
                    800: '#3730A3',
                    900: '#312E81',
                    950: '#1E1B4B',
                },
                accent: {
                    50:  '#FAF5FF',
                    400: '#C084FC',
                    500: '#A855F7',
                    600: '#9333EA',
                    700: '#7E22CE',
                },
                ink: {
                    50:  '#F8FAFC',
                    100: '#F1F5F9',
                    200: '#E2E8F0',
                    300: '#CBD5E1',
                    400: '#94A3B8',
                    500: '#64748B',
                    600: '#475569',
                    700: '#334155',
                    800: '#1E293B',
                    900: '#0F172A',
                    950: '#020617',
                },
            },
            boxShadow: {
                'soft':    '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                'elev':    '0 4px 12px -2px rgb(15 23 42 / 0.08), 0 2px 6px -1px rgb(15 23 42 / 0.04)',
                'lift':    '0 20px 40px -12px rgb(15 23 42 / 0.15), 0 8px 16px -8px rgb(15 23 42 / 0.1)',
                'brand':   '0 10px 30px -10px rgb(79 70 229 / 0.45)',
                'glow':    '0 0 0 4px rgb(79 70 229 / 0.12)',
            },
            fontSize: {
                'display-xl': ['5rem', { lineHeight: '1', letterSpacing: '-0.04em', fontWeight: '800' }],
                'display-lg': ['4rem', { lineHeight: '1.05', letterSpacing: '-0.035em', fontWeight: '800' }],
                'display-md': ['3rem', { lineHeight: '1.1', letterSpacing: '-0.03em', fontWeight: '800' }],
                'display-sm': ['2.25rem', { lineHeight: '1.15', letterSpacing: '-0.025em', fontWeight: '700' }],
            },
            animation: {
                'fade-in-up': 'fadeInUp 0.6s ease-out',
                'float': 'float 4s ease-in-out infinite',
                'pulse-slow': 'pulse 3s ease-in-out infinite',
            },
            keyframes: {
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
            },
        }
    }
};
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>

<style>
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
        font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    }
    [x-cloak] { display: none !important; }

    /* Scrollbar fino */
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background: #F1F5F9; }
    ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 5px; }
    ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

    /* Gradient text utility */
    .text-gradient {
        background: linear-gradient(135deg, #4F46E5 0%, #A855F7 50%, #EC4899 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    /* Glass effect for sticky nav */
    .glass {
        background-color: rgba(255, 255, 255, 0.75);
        backdrop-filter: saturate(180%) blur(20px);
        -webkit-backdrop-filter: saturate(180%) blur(20px);
    }

    /* Grid pattern background */
    .bg-grid {
        background-image:
            linear-gradient(to right, rgb(226 232 240 / 0.6) 1px, transparent 1px),
            linear-gradient(to bottom, rgb(226 232 240 / 0.6) 1px, transparent 1px);
        background-size: 48px 48px;
    }
    .bg-grid-dark {
        background-image:
            linear-gradient(to right, rgb(255 255 255 / 0.05) 1px, transparent 1px),
            linear-gradient(to bottom, rgb(255 255 255 / 0.05) 1px, transparent 1px);
        background-size: 48px 48px;
    }

    /* Subtle noise texture (opcional) */
    .bg-noise {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cfilter id='n'%3E%3CfeTurbulence baseFrequency='0.9'/%3E%3CfeColorMatrix values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.03 0'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* Button press state */
    .btn-press { transition: transform .12s ease, box-shadow .12s ease; }
    .btn-press:active { transform: translateY(1px) scale(.98); }

    /* Focus ring más premium */
    .focus-ring:focus { outline: none; box-shadow: 0 0 0 4px rgb(79 70 229 / 0.15); }
</style>
