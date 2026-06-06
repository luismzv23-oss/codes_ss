<!DOCTYPE html>
<?php
    function eventFlagMarkup(?string $code): string
    {
        $code = preg_replace('/[^a-z-]/', '', strtolower((string) $code));
        if ($code === '') {
            return '';
        }

        $styles = [
            'ar' => 'linear-gradient(#74acdf 0 33%, #fff 33% 66%, #74acdf 66%)',
            'at' => 'linear-gradient(#ed2939 0 33%, #fff 33% 66%, #ed2939 66%)',
            'au' => 'linear-gradient(#012169,#012169)',
            'az' => 'linear-gradient(#00b5e2 0 33%,#ef3340 33% 66%,#509e2f 66%)',
            'ba' => 'linear-gradient(135deg,#002395 0 72%,#fecb00 72%)',
            'be' => 'linear-gradient(90deg,#000 0 33%,#fae042 33% 66%,#ed2939 66%)',
            'bo' => 'linear-gradient(#d52b1e 0 33%,#f9e300 33% 66%,#007934 66%)',
            'br' => 'linear-gradient(135deg,#009b3a 0 100%)',
            'ca' => 'linear-gradient(90deg,#d52b1e 0 25%,#fff 25% 75%,#d52b1e 75%)',
            'cd' => 'linear-gradient(135deg,#007fff 0 42%,#f7d618 42% 50%,#ce1021 50% 58%,#007fff 58%)',
            'ch' => 'linear-gradient(#d52b1e,#d52b1e)',
            'cl' => 'linear-gradient(90deg,#0039a6 0 33%,#fff 33%),linear-gradient(#fff 0 50%,#d52b1e 50%)',
            'ci' => 'linear-gradient(90deg,#f77f00 0 33%,#fff 33% 66%,#009e60 66%)',
            'co' => 'linear-gradient(#fcd116 0 50%,#003893 50% 75%,#ce1126 75%)',
            'cv' => 'linear-gradient(#003893 0 50%,#fff 50% 56%,#cf2027 56% 62%,#fff 62% 68%,#003893 68%)',
            'cy' => 'linear-gradient(#fff,#fff)',
            'cw' => 'linear-gradient(#002b7f 0 62%,#f9e814 62% 70%,#002b7f 70%)',
            'cz' => 'linear-gradient(150deg,#11457e 0 35%,transparent 35%),linear-gradient(#fff 0 50%,#d7141a 50%)',
            'de' => 'linear-gradient(#000 0 33%,#dd0000 33% 66%,#ffce00 66%)',
            'dk' => 'linear-gradient(90deg,transparent 0 30%,#fff 30% 40%,transparent 40%),linear-gradient(transparent 0 42%,#fff 42% 56%,transparent 56%),#c60c30',
            'dz' => 'linear-gradient(90deg,#006233 0 50%,#fff 50%)',
            'ec' => 'linear-gradient(#ffd100 0 50%,#034ea2 50% 75%,#ed1c24 75%)',
            'eg' => 'linear-gradient(#ce1126 0 33%,#fff 33% 66%,#000 66%)',
            'es' => 'linear-gradient(#aa151b 0 25%,#f1bf00 25% 75%,#aa151b 75%)',
            'eu' => 'radial-gradient(circle at 50% 50%,#fbbf24 0 7%,transparent 8%),#1d4ed8',
            'fr' => 'linear-gradient(90deg,#0055a4 0 33%,#fff 33% 66%,#ef4135 66%)',
            'gb-eng' => 'linear-gradient(90deg,transparent 0 42%,#ce1124 42% 58%,transparent 58%),linear-gradient(transparent 0 38%,#ce1124 38% 62%,transparent 62%),#fff',
            'gb-sct' => 'linear-gradient(35deg,transparent 0 42%,#fff 42% 58%,transparent 58%),linear-gradient(145deg,transparent 0 42%,#fff 42% 58%,transparent 58%),#0065bd',
            'gh' => 'linear-gradient(#ce1126 0 33%,#fcd116 33% 66%,#006b3f 66%)',
            'gr' => 'repeating-linear-gradient(#0d5eaf 0 11%,#fff 11% 22%)',
            'ht' => 'linear-gradient(#00209f 0 50%,#d21034 50%)',
            'iq' => 'linear-gradient(#ce1126 0 33%,#fff 33% 66%,#000 66%)',
            'ir' => 'linear-gradient(#239f40 0 33%,#fff 33% 66%,#da0000 66%)',
            'it' => 'linear-gradient(90deg,#009246 0 33%,#fff 33% 66%,#ce2b37 66%)',
            'jo' => 'linear-gradient(145deg,#ce1126 0 36%,transparent 36%),linear-gradient(#000 0 33%,#fff 33% 66%,#007a3d 66%)',
            'sa' => 'linear-gradient(#006c35,#006c35)',
            'se' => 'linear-gradient(90deg,transparent 0 30%,#fecc00 30% 42%,transparent 42%),linear-gradient(transparent 0 40%,#fecc00 40% 58%,transparent 58%),#006aa7',
            'sn' => 'linear-gradient(90deg,#00853f 0 33%,#fdef42 33% 66%,#e31b23 66%)',
            'tn' => 'radial-gradient(circle at 50% 50%,#fff 0 28%,transparent 29%),#e70013',
            'tr' => 'radial-gradient(circle at 43% 50%,#fff 0 22%,transparent 23%),radial-gradient(circle at 48% 50%,#e30a17 0 18%,transparent 19%),#e30a17',
            'us' => 'repeating-linear-gradient(#b22234 0 7.7%,#fff 7.7% 15.4%)',
            'uy' => 'repeating-linear-gradient(#fff 0 11%,#0038a8 11% 22%)',
            'uz' => 'linear-gradient(#1eb5e5 0 32%,#ce1126 32% 36%,#fff 36% 64%,#ce1126 64% 68%,#009739 68%)',
            've' => 'linear-gradient(#ffcc00 0 33%,#00247d 33% 66%,#cf142b 66%)',
            'za' => 'linear-gradient(90deg,#007a4d 0 55%,#de3831 55% 72%,#002395 72%)',
        ];

        $background = $styles[$code] ?? 'linear-gradient(135deg,#64748b,#94a3b8)';

        return "<span title='" . esc(strtoupper($code)) . "' style='width:34px;height:23px;display:inline-block;border-radius:4px;background:{$background};box-shadow:0 0 0 1px rgba(255,255,255,0.28);vertical-align:-4px;'></span>";
    }
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    
    <!-- FOUC Prevention Script -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('codex_ss_theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light-theme');
            }
        })();
    </script>

    <!-- Resource Hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://unpkg.com/htmx.org@1.9.10" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" as="script">
    <link rel="preload" href="https://unpkg.com/lucide@latest" as="script">

    <!-- CSS / Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Palette HSL & Colors */
            --hue-dark: 228;
            --hue-primary: 20;
            --hue-success: 161;
            --hue-danger: 0;

            --bg-dark: hsl(var(--hue-dark), 50%, 5%); /* #060913 */
            --bg-panel: hsla(222, 47%, 11%, 0.65); /* rgba(15, 23, 42, 0.65) */
            --bg-panel-solid: #0f172a;
            --primary: hsl(var(--hue-primary), 95%, 54%); /* #f97316 */
            --primary-gradient: linear-gradient(135deg, #ff7e40 0%, #ff4500 100%);
            --primary-hover: #ea580c;
            --primary-glow: rgba(249, 115, 22, 0.15);
            --success-glow: rgba(16, 185, 129, 0.25);
            --danger-glow: rgba(239, 68, 68, 0.25);
            --border-glow: rgba(99, 102, 241, 0.15);

            --text-main: #f8fafc;
            --text-muted: #64748b;
            --border: rgba(255, 255, 255, 0.06);
            --border-hover: rgba(255, 255, 255, 0.12);
            --border-active: rgba(249, 115, 22, 0.4);
            --success: #10b981;
            --danger: #ef4444;
            --odd-bg: rgba(30, 41, 59, 0.45);
            --odd-hover: rgba(51, 65, 85, 0.7);
            --odd-selected: #f97316;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 8px 30px rgba(0, 0, 0, 0.35);
            --transition-speed: 0.3s;
        }

        html.light-theme {
            --bg-dark: #f8fafc;
            --bg-panel: rgba(255, 255, 255, 0.75);
            --bg-panel-solid: #ffffff;
            --primary: #f97316;
            --primary-gradient: linear-gradient(135deg, #ff7e40 0%, #ea580c 100%);
            --primary-hover: #ea580c;
            --primary-glow: rgba(249, 115, 22, 0.08);
            --success-glow: rgba(16, 185, 129, 0.15);
            --danger-glow: rgba(239, 68, 68, 0.15);
            --border-glow: rgba(99, 102, 241, 0.1);

            --text-main: #0f172a;
            --text-muted: #475569; /* Slate más oscuro para mejor contraste */
            --border: rgba(15, 23, 42, 0.16); /* Bordes más visibles */
            --border-hover: rgba(15, 23, 42, 0.28);
            --border-active: rgba(249, 115, 22, 0.6);
            --success: #10b981;
            --danger: #ef4444;
            --odd-bg: #f1f5f9; /* Fondo gris sólido para cuotas en modo claro */
            --odd-hover: #e2e8f0; /* Hover gris sólido */
            --odd-selected: #f97316;
            --shadow-sm: 0 2px 8px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* Smooth theme transition */
        html, body, aside, main, header, section, div, button, span, a, input {
            transition: background-color var(--transition-speed) ease, 
                        border-color var(--transition-speed) ease, 
                        color var(--transition-speed) ease;
        }

        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(236, 72, 153, 0.03), transparent 30%),
                        var(--bg-dark); 
            color: var(--text-main); 
            height: 100vh; 
            overflow: hidden; 
        }

        html.light-theme body {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.07), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(236, 72, 153, 0.04), transparent 35%),
                        var(--bg-dark);
        }

        /* SCROLLBARS */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
        html.light-theme ::-webkit-scrollbar-thumb { background: rgba(15, 23, 42, 0.1); }
        html.light-theme ::-webkit-scrollbar-thumb:hover { background: rgba(15, 23, 42, 0.2); }

        /* NAVBAR */
        .topbar {
            height: 64px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            justify-content: space-between;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 50;
        }
        html.light-theme .topbar {
            background: rgba(255, 255, 255, 0.75);
            box-shadow: 0 4px 30px rgba(15, 23, 42, 0.05);
        }
        .logo { 
            font-family: 'Outfit', sans-serif; 
            font-size: 1.6rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #ff7e40, #ff2a6d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.03em;
        }
        .user-nav { display: flex; gap: 0.75rem; align-items: center; }
        
        .wallet-widget {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            padding: 0.45rem 0.9rem;
            border-radius: 0.6rem;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: inset 0 1px 1px rgba(255,255,255,0.05);
            transition: all 0.2s ease;
        }
        .wallet-widget:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }
        html.light-theme .wallet-widget {
            background: rgba(15, 23, 42, 0.03);
            box-shadow: inset 0 1px 1px rgba(15, 23, 42, 0.02);
        }
        html.light-theme .wallet-widget:hover {
            background: rgba(15, 23, 42, 0.06);
            border-color: rgba(15, 23, 42, 0.1);
        }

        .wallet-add-btn {
            background: var(--success);
            color: #fff;
            border: none;
            border-radius: 6px;
            width: 22px;
            height: 22px;
            font-size: 0.85rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, background-color 0.2s;
        }
        .wallet-add-btn:hover {
            transform: scale(1.1);
            background: #059669;
        }
        .user-badge {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            padding: 0.45rem 0.85rem;
            border-radius: 0.6rem;
            height: 38px;
        }
        html.light-theme .user-badge {
            background: rgba(15, 23, 42, 0.02);
            color: var(--text-main);
        }

        .nav-link {
            color: var(--text-main);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: color 0.2s ease;
            border: 1px solid var(--border);
            padding: 0.45rem 0.85rem;
            border-radius: 0.6rem;
            height: 38px;
        }
        .nav-link:hover {
            color: var(--primary);
            background: rgba(255,255,255,0.03);
            border-color: var(--border-hover);
        }
        html.light-theme .nav-link:hover {
            background: rgba(15, 23, 42, 0.03);
        }

        .btn-login { 
            background: transparent; 
            color: var(--text-main); 
            border: 1px solid var(--border); 
            padding: 0.45rem 1.1rem; 
            border-radius: 0.6rem; 
            cursor: pointer; 
            font-weight: 600; 
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
        }
        .btn-login:hover {
            background: rgba(255, 255, 255, 0.03);
            border-color: var(--border-hover);
        }
        html.light-theme .btn-login:hover {
            background: rgba(15, 23, 42, 0.03);
        }

        .btn-register { 
            background: var(--primary-gradient); 
            color: white; 
            border: none; 
            padding: 0.45rem 1.1rem; 
            border-radius: 0.6rem; 
            cursor: pointer; 
            font-weight: 600; 
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
        }
        .btn-register:hover {
            opacity: 0.95;
            box-shadow: 0 4px 18px rgba(249, 115, 22, 0.35);
            transform: translateY(-1px);
        }

        /* Mobile specific toggles inside topbar */
        .mobile-nav-toggle, .mobile-slip-toggle {
            display: none;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 0.6rem;
            width: 38px;
            height: 38px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.2s;
        }
        .mobile-nav-toggle:hover, .mobile-slip-toggle:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-hover);
        }
        html.light-theme .mobile-nav-toggle:hover, html.light-theme .mobile-slip-toggle:hover {
            background: rgba(15, 23, 42, 0.05);
        }
        .mobile-badge-count {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--primary-gradient);
            color: white;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 5px;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(249, 115, 22, 0.4);
        }

        /* LAYOUT 3 COLUMNS */
        .layout-grid {
            display: grid;
            grid-template-columns: 280px 1fr 340px;
            height: calc(100vh - 64px);
        }

        /* COLUMN: LEFT NAV */
        .left-col {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 1.5rem 1rem;
        }
        html.light-theme .left-col {
            background: rgba(255, 255, 255, 0.45);
        }
        .nav-title { 
            font-size: 0.72rem; 
            text-transform: uppercase; 
            color: var(--text-muted); 
            font-weight: 700; 
            margin-bottom: 0.85rem; 
            letter-spacing: 0.08em; 
        }
        .sport-item { 
            display: flex; 
            align-items: center; 
            gap: 0.85rem; 
            padding: 0.65rem 0.85rem; 
            border-radius: 0.6rem; 
            cursor: pointer; 
            transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1); 
            font-size: 0.9rem; 
            font-weight: 500; 
            color: var(--text-muted);
            border: 1px solid transparent;
            text-decoration: none;
            margin-bottom: 3px;
        }
        .sport-item:hover { 
            background: rgba(255, 255, 255, 0.02); 
            color: var(--text-main);
            transform: translateX(6px);
        }
        html.light-theme .sport-item:hover {
            background: rgba(15, 23, 42, 0.03);
        }
        .sport-item.active {
            background: rgba(249, 115, 22, 0.06);
            border-color: rgba(249, 115, 22, 0.12);
            color: var(--text-main);
            position: relative;
        }
        html.light-theme .sport-item.active {
            background: rgba(249, 115, 22, 0.08);
            border-color: rgba(249, 115, 22, 0.15);
        }
        
        /* COLUMN: CENTER CONTENT */
        .center-col {
            overflow-y: auto;
            padding: 1.5rem 2rem;
            background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.02), transparent 50%);
        }

        .back-link {
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: var(--primary);
        }

        .event-hero {
            background: linear-gradient(135deg, #0f172a, #131a35, #1e1b4b, #0f172a);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.75rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        html.light-theme .event-hero {
            background: linear-gradient(135deg, #ffffff, #f1f5f9, #e0e7ff, #ffffff);
        }
        .event-meta {
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1rem;
        }
        .scoreboard {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .team {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            min-width: 0;
            color: var(--text-main);
        }
        .team.away {
            justify-content: flex-end;
            text-align: right;
        }
        .versus {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 800;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            min-width: 50px;
            text-align: center;
        }
        html.light-theme .versus {
            background: rgba(15, 23, 42, 0.05);
        }
        .event-time {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .market-toolbar {
            position: sticky;
            top: -1.5rem;
            z-index: 12;
            background: linear-gradient(180deg, var(--bg-dark) 0%, rgba(6,9,19,0.92) 100%);
            border-bottom: 1px solid var(--border);
            margin: 0 -2rem 1.25rem;
            padding: 0.9rem 2rem 1rem;
            backdrop-filter: blur(18px);
        }
        html.light-theme .market-toolbar {
            background: linear-gradient(180deg, var(--bg-dark) 0%, rgba(248,250,252,0.92) 100%);
        }
        .market-search {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            background: rgba(15, 23, 42, 0.64);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 0.7rem 0.9rem;
            margin-bottom: 0.75rem;
        }
        html.light-theme .market-search {
            background: rgba(255, 255, 255, 0.78);
        }
        .market-search input {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-main);
            font: inherit;
            font-weight: 650;
        }
        .market-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.1rem;
        }
        .market-tab {
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
            border-radius: 999px;
            padding: 0.46rem 0.75rem;
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 850;
            white-space: nowrap;
        }
        html.light-theme .market-tab {
            background: rgba(15, 23, 42, 0.04);
        }
        .market-tab.active {
            color: #fff;
            background: var(--primary-gradient);
            border-color: transparent;
        }
        .market-group {
            margin-bottom: 1rem;
        }
        .market-group-header {
            width: 100%;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.035);
            color: var(--text-main);
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        html.light-theme .market-group-header {
            background: rgba(15, 23, 42, 0.04);
        }
        .market-group-title {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 850;
        }
        .market-count {
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            padding: 0.16rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 850;
        }

        .market-card {
            background: var(--bg-panel);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 0.85rem;
            margin-bottom: 1.25rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .market-card[style*="display: none"] {
            margin: 0;
        }
        html.light-theme .market-card {
            background: rgba(255, 255, 255, 0.7);
        }
        .market-title {
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            padding: 1rem 1.25rem;
            font-size: 1.05rem;
            background: rgba(15, 23, 42, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        html.light-theme .market-title {
            background: rgba(15, 23, 42, 0.02);
        }
        .market-type-pill {
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            padding: 0.16rem 0.5rem;
            font: 800 0.66rem 'Inter', sans-serif;
            text-transform: uppercase;
        }
        .market-odds {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            padding: 1.25rem;
        }

        .odd-btn {
            background: rgba(30, 41, 59, 0.3);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border);
            border-radius: 0.6rem;
            padding: 0.65rem 1rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }
        html.light-theme .odd-btn {
            background: rgba(241, 245, 249, 0.85);
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.6);
        }
        .odd-btn:hover { 
            background: rgba(51, 65, 85, 0.55); 
            border-color: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
        }
        html.light-theme .odd-btn:hover {
            background: rgba(226, 232, 240, 0.95);
            border-color: rgba(15, 23, 42, 0.15);
        }
        .odd-btn.selected { 
            background: var(--primary-gradient); 
            border-color: transparent; 
            color: white; 
            box-shadow: 0 0 18px rgba(249, 115, 22, 0.45), inset 0 1px 2px rgba(255, 255, 255, 0.25);
        }
        .odd-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.02em; }
        .odd-btn.selected .odd-label { color: rgba(255,255,255,0.85); }
        .odd-val { font-size: 1.05rem; font-weight: 800; font-family: 'Outfit', sans-serif; }

        /* COLUMN: RIGHT (BET SLIP) */
        .right-col {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        html.light-theme .right-col {
            background: rgba(255, 255, 255, 0.5);
        }
        .bet-slip-header {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--border);
            text-align: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
            background: rgba(15, 23, 42, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        html.light-theme .bet-slip-header {
            background: rgba(241, 245, 249, 0.9);
        }
        .bet-slip-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1rem;
        }
        .empty-slip {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 3rem;
            padding: 0 1rem;
            line-height: 1.5;
        }
        
        /* Selections inside Bet Slip */
        .selection-item {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border);
            border-left: 3px solid var(--primary);
            border-radius: 0.6rem;
            padding: 0.85rem 1rem;
            margin-bottom: 0.85rem;
            position: relative;
            transition: all 0.25s ease;
        }
        html.light-theme .selection-item {
            background: rgba(255, 255, 255, 0.7);
        }
        .selection-item:hover {
            border-color: rgba(255, 255, 255, 0.12);
            border-left-color: var(--primary);
            background: rgba(15, 23, 42, 0.85);
            transform: translateX(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2), 0 0 10px var(--primary-glow);
        }
        html.light-theme .selection-item:hover {
            border-color: rgba(15, 23, 42, 0.15);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
        }
        .btn-remove { 
            position: absolute; 
            top: 0.75rem; 
            right: 0.75rem; 
            background: transparent; 
            border: none; 
            color: var(--text-muted); 
            cursor: pointer; 
            font-size: 1.3rem; 
            line-height: 1;
            transition: color 0.2s, transform 0.2s;
        }
        .btn-remove:hover { 
            color: var(--danger); 
            transform: scale(1.15);
        }
        .sel-teams { font-size: 0.88rem; font-weight: 600; margin-bottom: 0.3rem; padding-right: 1.5rem; color: var(--text-main); }
        .sel-market { font-size: 0.76rem; color: var(--text-muted); margin-bottom: 0.6rem; font-weight: 500; }
        .sel-odd-row { display: flex; justify-content: space-between; align-items: center; }
        .sel-choice { font-weight: 700; color: var(--primary); font-size: 0.95rem; }
        .sel-val { font-weight: 800; font-family: 'Outfit', sans-serif; font-size: 1.05rem; }

        .bet-slip-footer {
            padding: 1.25rem;
            background: rgba(15, 23, 42, 0.85);
            border-top: 1px solid var(--border);
        }
        html.light-theme .bet-slip-footer {
            background: rgba(241, 245, 249, 0.95);
        }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.9rem; align-items: center; }
        .stake-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.6rem;
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 1.25rem;
            text-align: right;
            transition: all 0.25s ease;
            font-family: 'Outfit', sans-serif;
        }
        html.light-theme .stake-input {
            background: rgba(255, 255, 255, 0.8);
            color: var(--text-main);
        }
        .stake-input:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 14px var(--primary-glow), inset 0 1px 1px rgba(255, 255, 255, 0.05);
            background: rgba(15, 23, 42, 0.8);
        }
        html.light-theme .stake-input:focus {
            background: #ffffff;
            box-shadow: 0 0 14px var(--primary-glow);
        }
        .ticket-footer {
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            padding: 0.85rem 1rem;
            border-radius: 0.6rem;
            box-shadow: inset 0 1px 1px rgba(255,255,255,0.02);
        }
        html.light-theme .ticket-footer {
            background: rgba(15, 23, 42, 0.02);
        }
        .footer-item {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .footer-item.align-end {
            align-items: flex-end;
            text-align: right;
        }
        .footer-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .footer-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
        }
        .footer-value.success {
            color: var(--success);
            font-size: 1.2rem;
        }
        .btn-place-bet {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 0.6rem;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }
        .btn-place-bet:hover { 
            opacity: 0.95; 
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35);
            transform: translateY(-1px);
        }
        .btn-place-bet:disabled { 
            background: rgba(51, 65, 85, 0.4); 
            border: 1px solid var(--border);
            cursor: not-allowed; 
            color: var(--text-muted); 
            box-shadow: none;
            transform: none;
        }
        html.light-theme .btn-place-bet:disabled {
            background: rgba(226, 232, 240, 0.8);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: 34px;
            transition: .3s;
            background: #334155;
            border: 1px solid var(--border);
        }
        html.light-theme .slider {
            background: #cbd5e1;
        }
        .slider::before {
            position: absolute;
            content: '';
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: .3s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }
        input:checked + .slider {
            background: var(--primary-gradient);
            border-color: transparent;
        }
        input:checked + .slider::before {
            transform: translateX(22px);
        }

        .badge {
            background: var(--primary-gradient);
            color: white;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            margin-left: 0.5rem;
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
            display: inline-block;
        }

        .nav-icon {
            width: 16px;
            height: 16px;
            color: var(--text-muted);
            transition: color 0.2s ease;
        }

        /* Real-Time Flash Animations */
        .flash-up { animation: flashGreenGlow 1.5s cubic-bezier(0.25, 1, 0.5, 1); }
        .flash-down { animation: flashRedGlow 1.5s cubic-bezier(0.25, 1, 0.5, 1); }
        
        @keyframes flashGreenGlow {
            0% { 
                box-shadow: 0 0 15px rgba(16, 185, 129, 0.8), inset 0 0 8px rgba(16, 185, 129, 0.5); 
                border-color: rgba(16, 185, 129, 0.8);
            }
            100% { 
                box-shadow: none; 
                border-color: var(--border);
            }
        }
        @keyframes flashRedGlow {
            0% { 
                box-shadow: 0 0 15px rgba(239, 68, 68, 0.8), inset 0 0 8px rgba(239, 68, 68, 0.5); 
                border-color: rgba(239, 68, 68, 0.8);
            }
            100% { 
                box-shadow: none; 
                border-color: var(--border);
            }
        }

        /* Mobile Overlay */
        .mobile-overlay {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 35;
            display: none;
        }

        /* RESPONSIVE MEDIA QUERIES (< 1024px) */
        @media (max-width: 1024px) {
            .mobile-nav-toggle, .mobile-slip-toggle {
                display: flex;
            }

            .layout-grid {
                grid-template-columns: 1fr;
                position: relative;
            }

            .left-col {
                position: fixed;
                top: 64px;
                left: 0;
                bottom: 0;
                width: 280px;
                z-index: 40;
                background: var(--bg-panel-solid);
                border-right: 1px solid var(--border);
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 10px 0 30px rgba(0,0,0,0.25);
            }

            .left-col.open {
                transform: translateX(0);
            }

            .right-col {
                position: fixed;
                top: 64px;
                right: 0;
                bottom: 0;
                width: 320px;
                z-index: 40;
                background: var(--bg-panel-solid);
                border-left: 1px solid var(--border);
                transform: translateX(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: -10px 0 30px rgba(0,0,0,0.25);
            }

            .right-col.open {
                transform: translateX(0);
            }

            .mobile-overlay {
                display: block;
            }

            .center-col {
                padding: 1rem;
            }

            .market-toolbar {
                top: -1rem;
                margin: 0 -1rem 1rem;
                padding: 0.85rem 1rem;
            }

            .market-odds {
                grid-template-columns: 1fr;
            }

            .scoreboard {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                text-align: center;
            }
            .team.away {
                justify-content: flex-start;
                text-align: left;
            }
            .versus {
                align-self: center;
                max-width: 60px;
            }
        }
    </style>
</head>
<body x-data="betSlipApp()">
    <!-- Topbar -->
    <header class="topbar">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button @click="isLeftColOpen = !isLeftColOpen; isRightColOpen = false" class="mobile-nav-toggle" aria-label="Abrir menú de deportes">
                <i data-lucide="menu" style="width:20px;height:20px;"></i>
            </button>
            <a class="logo" href="/">Codex SS</a>
        </div>
        <div class="user-nav">
            <!-- Theme Switcher -->
            <button @click="toggleTheme()" class="nav-link" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; background: transparent; border: 1px solid var(--border);" aria-label="Cambiar tema">
                <span x-show="!isLightTheme" style="display: inline-flex;"><i data-lucide="sun" style="width:18px;height:18px;color:#fbbf24;"></i></span>
                <span x-show="isLightTheme" style="display: inline-flex;"><i data-lucide="moon" style="width:18px;height:18px;color:#6366f1;"></i></span>
            </button>

            <?php if(session()->get('isLoggedIn')): ?>
                <div class="wallet-widget">
                    <i data-lucide="wallet" style="width:16px;height:16px;color:var(--primary);"></i>
                    <span x-text="walletBalance.toFixed(2) + ' K'"></span>
                    <button onclick="handleDeposit()" class="wallet-add-btn">+</button>
                    <button onclick="handleWithdrawal()" class="wallet-add-btn" title="Solicitar retiro">-</button>
                </div>
                <a href="/sportsbook/history" class="nav-link">
                    <i data-lucide="history" style="width:16px;height:16px;"></i>
                    Mis Apuestas
                </a>
                <a href="/sportsbook/transactions" class="nav-link">
                    <i data-lucide="receipt" style="width:16px;height:16px;"></i>
                    Transacciones
                </a>
                <a href="/sportsbook/kyc" class="nav-link">
                    <i data-lucide="id-card" style="width:16px;height:16px;"></i>
                    KYC
                </a>
                <a href="/sportsbook/responsible-limits" class="nav-link">
                    <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                    Limites
                </a>
                <span class="user-badge">
                    <i data-lucide="user" style="width:16px;height:16px;"></i>
                    <?= esc(session()->get('username')) ?>
                </span>
                <?php if ((int) session()->get('role_id') === 1): ?>
                    <a href="/dashboard/overview" class="btn-register">Dashboard</a>
                <?php endif; ?>
                <a href="/auth/logout" class="btn-login">Cerrar sesión</a>
            <?php else: ?>
                <a href="/auth/login" class="btn-login">Iniciar Sesión</a>
                <a href="/auth/register" class="btn-register">Regístrate</a>
            <?php endif; ?>

            <!-- Bet Slip Mobile Trigger -->
            <button @click="isRightColOpen = !isRightColOpen; isLeftColOpen = false" class="mobile-slip-toggle" aria-label="Ver boleto de apuestas">
                <i data-lucide="shopping-bag" style="width:20px;height:20px;"></i>
                <span x-show="selections.length > 0" class="mobile-badge-count" x-text="selections.length"></span>
            </button>
        </div>
    </header>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" x-show="isLeftColOpen || isRightColOpen" @click="isLeftColOpen = false; isRightColOpen = false" style="display: none;"></div>

    <!-- Layout Grid -->
    <main class="layout-grid">
        <!-- Left Column: Navigation -->
        <aside class="left-col" :class="isLeftColOpen ? 'open' : ''">
            <a href="/" style="text-decoration: none; color: var(--text-main);">
                <div class="sport-item">
                    <i data-lucide="globe" class="nav-icon" style="width:16px;height:16px;"></i>
                    <span>Todos los Deportes</span>
                </div>
            </a>
            
            <div class="nav-title" style="margin-top: 1.5rem;">Deportes A-Z</div>
            <?php foreach($sports as $sport): ?>
                <?php $isSportActive = (isset($_GET['sport_id']) && $_GET['sport_id'] == $sport['id']); ?>
                <a href="/?sport_id=<?= $sport['id'] ?>" style="text-decoration: none; color: inherit;">
                    <div class="sport-item <?= $isSportActive ? 'active' : '' ?>">
                        <span style="font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;"><?= str_replace('?', '', $sport['icon']) ?: '🎯' ?></span>
                        <span><?= esc($sport['name']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>

            <div class="nav-title" style="margin-top: 1.5rem;">Ligas Populares</div>
            <?php foreach($leagues as $league): ?>
                <?php $isLeagueActive = (isset($_GET['league_id']) && $_GET['league_id'] == $league['id']); ?>
                <a href="/?league_id=<?= $league['id'] ?>" style="text-decoration: none; color: inherit;">
                    <div class="sport-item <?= $isLeagueActive ? 'active' : '' ?>">
                        <i data-lucide="star" class="nav-icon" style="width:14px;height:14px;color: var(--primary); fill: var(--primary);"></i>
                        <span><?= esc($league['name']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- Center Column -->
        <section class="center-col">
            <a class="back-link" href="/">
                <i data-lucide="chevron-left" style="width:16px;height:16px;"></i> Volver a apuestas
            </a>
            
            <section class="event-hero">
                <div class="event-meta"><?= esc($event['sport_name']) ?> / <?= esc($event['league_name']) ?><?= !empty($event['league_country']) ? ' / ' . esc($event['league_country']) : '' ?></div>
                <div class="scoreboard">
                    <div class="team"><?= eventFlagMarkup($event['home_flag'] ?? null) ?><span><?= esc($event['home_team']) ?></span></div>
                    <div class="versus">
                        <?php if ($event['score_home'] !== null && $event['score_away'] !== null): ?>
                            <?= (int) $event['score_home'] ?> - <?= (int) $event['score_away'] ?>
                        <?php else: ?>
                            VS
                        <?php endif; ?>
                    </div>
                    <div class="team away"><span><?= esc($event['away_team']) ?></span><?= eventFlagMarkup($event['away_flag'] ?? null) ?></div>
                </div>
                <div class="event-time">
                    <i data-lucide="clock" style="width:14px;height:14px;"></i>
                    <span><?= esc($event['start_time']) ?></span>
                    <?php if (!empty($event['venue'])): ?>
                        <span>&bull; <?= esc($event['venue']) ?></span>
                    <?php endif; ?>
                    &bull; 
                    <span class="live-badge" style="vertical-align: middle; line-height: 1;">
                        <?php if ($event['status'] === 'live'): ?>
                            <span class="pulse-dot"></span>
                        <?php endif; ?>
                        <?= $event['status'] === 'live' ? 'SEGUIMIENTO EN VIVO' : esc(strtoupper($event['status'])) ?>
                    </span>
                </div>
            </section>

            <?php 
            $isLive = ($event['status'] === 'live');
            $startTime = strtotime($event['start_time']);
            $isTooClose = ($startTime !== false && ($startTime - time()) <= 1800);
            $isRestricted = $isLive || $isTooClose;

            $marketGroupsMeta = [
                'principal' => ['label' => 'Principales', 'icon' => 'target', 'types' => ['1x2', 'h2h', 'moneyline']],
                'goles' => ['label' => 'Goles y puntos', 'icon' => 'activity', 'types' => ['totals', 'over_under', 'total_goals', 'total_points', 'team_totals', 'team_totals_away', 'btts', 'both_teams_to_score']],
                'handicap' => ['label' => 'Handicap', 'icon' => 'scale', 'types' => ['handicap', 'spread', 'double_chance']],
                'exactos' => ['label' => 'Marcadores exactos', 'icon' => 'list-checks', 'types' => ['correct_score']],
                'especiales' => ['label' => 'Especiales', 'icon' => 'sparkles', 'types' => ['props', 'qualifies', 'outright', 'outright_champion']],
                'otros' => ['label' => 'Otros mercados', 'icon' => 'circle-dot', 'types' => []],
            ];
            $groupedMarkets = array_fill_keys(array_keys($marketGroupsMeta), []);
            foreach ($markets as $market) {
                $type = strtolower((string) ($market['type'] ?? ''));
                $groupKey = 'otros';
                foreach ($marketGroupsMeta as $candidateKey => $meta) {
                    if (in_array($type, $meta['types'], true)) {
                        $groupKey = $candidateKey;
                        break;
                    }
                }
                $groupedMarkets[$groupKey][] = $market;
            }
            $availableGroups = array_filter($groupedMarkets, static fn ($items) => !empty($items));
            $totalOddsCount = 0;
            foreach ($markets as $market) {
                $totalOddsCount += count($market['odds'] ?? []);
            }
            ?>

            <div class="market-toolbar">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:0.8rem;">
                    <div>
                        <h2 style="font-family:Outfit,sans-serif;font-weight:850;font-size:1.35rem;letter-spacing:-0.02em;">Mercados</h2>
                        <div style="color:var(--text-muted);font-size:0.8rem;font-weight:700;"><?= count($markets) ?> mercados &bull; <?= $totalOddsCount ?> cuotas</div>
                    </div>
                    <?php if ($isRestricted): ?>
                        <div style="display:flex;align-items:center;gap:0.4rem;color:#fca5a5;background:rgba(239,68,68,0.14);border:1px solid rgba(239,68,68,0.30);border-radius:999px;padding:0.42rem 0.7rem;font-size:0.76rem;font-weight:850;">
                            <i data-lucide="lock" style="width:14px;height:14px;"></i>
                            Apuestas cerradas
                        </div>
                    <?php endif; ?>
                </div>
                <label class="market-search">
                    <i data-lucide="search" style="width:18px;height:18px;color:var(--text-muted);"></i>
                    <input type="search" x-model.debounce.150ms="marketSearch" placeholder="Buscar mercado o seleccion">
                </label>
                <div class="market-tabs">
                    <button type="button" class="market-tab" :class="{active: activeMarketGroup === 'all'}" @click="activeMarketGroup = 'all'">Todos</button>
                    <?php foreach ($availableGroups as $groupKey => $groupMarkets): ?>
                        <button type="button" class="market-tab" :class="{active: activeMarketGroup === '<?= esc($groupKey, 'attr') ?>'}" @click="activeMarketGroup = '<?= esc($groupKey, 'js') ?>'">
                            <?= esc($marketGroupsMeta[$groupKey]['label']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($availableGroups as $groupKey => $groupMarkets): ?>
                <?php $groupMeta = $marketGroupsMeta[$groupKey]; ?>
                <section class="market-group" x-show="groupVisible('<?= esc($groupKey, 'js') ?>')">
                    <button type="button" class="market-group-header" @click="toggleMarketGroup('<?= esc($groupKey, 'js') ?>')">
                        <span class="market-group-title">
                            <i data-lucide="<?= esc($groupMeta['icon'], 'attr') ?>" style="width:18px;height:18px;color:var(--primary);"></i>
                            <?= esc($groupMeta['label']) ?>
                        </span>
                        <span style="display:flex;align-items:center;gap:0.55rem;">
                            <span class="market-count"><?= count($groupMarkets) ?></span>
                            <i data-lucide="chevron-down" style="width:18px;height:18px;transition:transform .2s;" :style="openMarkets['<?= esc($groupKey, 'js') ?>'] ? 'transform:rotate(180deg)' : ''"></i>
                        </span>
                    </button>
                    <div x-show="openMarkets['<?= esc($groupKey, 'js') ?>']">
                        <?php foreach ($groupMarkets as $market): ?>
                            <?php $marketSearchText = strtolower($market['name'] . ' ' . $market['type'] . ' ' . implode(' ', array_column($market['odds'] ?? [], 'selection'))); ?>
                            <section class="market-card" x-show="marketVisible(<?= json_encode($marketSearchText) ?>, '<?= esc($groupKey, 'js') ?>')" x-transition.opacity>
                                <div class="market-title">
                                    <span><?= esc($market['name']) ?></span>
                                    <span class="market-type-pill"><?= esc($market['type']) ?></span>
                                </div>
                                <div class="market-odds">
                                    <?php if (!empty($market['odds'])): ?>
                                        <?php foreach ($market['odds'] as $odd): ?>
                                            <?php $isOddAvailable = !$isRestricted && $market['status'] === 'open' && (int) $odd['active'] === 1 && $odd['status'] === 'pending'; ?>
                                            <?php if (!$isOddAvailable): ?>
                                                <button class="odd-btn" disabled style="opacity: 0.45; cursor: not-allowed; pointer-events: none; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                                    <span class="odd-label"><?= esc($odd['selection']) ?></span>
                                                    <i data-lucide="lock" style="width: 12px; height: 12px; opacity: 0.6;"></i>
                                                </button>
                                            <?php else: ?>
                                                <button id="odd-btn-<?= $odd['id'] ?>"
                                                        class="odd-btn"
                                                        :class="isSelected(<?= $odd['id'] ?>) ? 'selected' : ''"
                                                        @click='toggleSelection({
                                                            id: <?= $odd['id'] ?>,
                                                            event_id: <?= (int) $event['id'] ?>,
                                                            teams: <?= json_encode($event['home_team'] . ' vs ' . $event['away_team']) ?>,
                                                            market: <?= json_encode($market['name']) ?>,
                                                            selection: <?= json_encode($odd['selection']) ?>,
                                                            odds: <?= $odd['odds_decimal'] ?>,
                                                            event_status: <?= json_encode($event['status']) ?>,
                                                            event_start_time: <?= json_encode($event['start_time']) ?>
                                                        })'>
                                                    <span class="odd-label"><?= esc($odd['selection']) ?></span>
                                                    <span class="odd-val" id="odd-val-<?= $odd['id'] ?>"><?= number_format($odd['odds_decimal'], 2) ?></span>
                                                </button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="color:var(--text-muted);padding:1rem;">Mercado no disponible</div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <?php if (empty($markets)): ?>
                <div style="color:var(--text-muted);padding:3rem 1rem;text-align:center;">Este evento aún no tiene mercados publicados.</div>
            <?php endif; ?>
            <section style="margin-top:1.5rem;border:1px solid var(--border);border-radius:0.8rem;background:var(--bg-panel);padding:1rem;">
                <div style="font-size:0.78rem;font-weight:900;text-transform:uppercase;letter-spacing:0.08em;color:var(--primary);">Reglas del evento</div>
                <p style="color:var(--text-muted);font-size:0.86rem;line-height:1.55;margin-top:0.35rem;">
                    Las apuestas se aceptan solo en prepartido habilitado. Si una cuota cambia antes de confirmar, el boleto pide aceptar la nueva cuota. Los mercados suspendidos o eventos en vivo quedan bloqueados.
                </p>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;">
                    <a class="market-tab" href="/apuestas-deportivas/reglas-de-apuestas">Reglas</a>
                    <a class="market-tab" href="/apuestas-deportivas/juego-responsable">Juego responsable</a>
                    <a class="market-tab" href="/apuestas-deportivas/soporte">Soporte</a>
                </div>
            </section>
        </section>

        <!-- Right Column: Bet Slip -->
        <aside class="right-col" :class="isRightColOpen ? 'open' : ''">
            <div class="bet-slip-header">
                Boleto de Apuestas <span x-show="selections.length > 0" :key="selections.length" class="badge" x-text="selections.length"></span>
            </div>

            <!-- Creador de Apuestas Toggle -->
            <div class="builder-toggle-container" style="padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(249, 115, 22, 0.04);">
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="wrench" style="width:16px;height:16px;color:var(--primary);"></i> Creador de Apuestas
                </span>
                <label class="switch">
                    <input type="checkbox" x-model="isBuilderActive" @change="toggleBuilderMode()">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="bet-slip-body">
                <!-- Builder Validation Error -->
                <div x-show="isBuilderActive && builderError" style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); border-radius: 0.5rem; padding: 0.75rem; font-size: 0.82rem; color: #fca5a5; margin-bottom: 1rem;" x-text="builderError"></div>

                <template x-if="selections.length === 0">
                    <div class="empty-slip">
                        <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Tu boleto está vacío.<br>Haz clic en una cuota para agregar una apuesta.
                    </div>
                </template>

                <template x-for="sel in selections" :key="sel.id">
                    <div class="selection-item">
                        <button class="btn-remove" @click="removeSelection(sel.id)">&times;</button>
                        <div class="sel-teams" x-text="sel.teams"></div>
                        <div class="sel-market" x-text="sel.market"></div>
                        <div class="sel-odd-row">
                            <span class="sel-choice" x-text="sel.selection"></span>
                            <span class="sel-val" x-text="sel.odds.toFixed(2)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="bet-slip-footer">
                <!-- Slip validation error (live or starts in <30 mins) -->
                <div x-show="getBetSlipError()" style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); border-radius: 0.5rem; padding: 0.75rem; font-size: 0.82rem; color: #fca5a5; margin-bottom: 1rem;" x-text="getBetSlipError()"></div>

                <div class="summary-row">
                    <span style="color: var(--text-muted);">Tipo de Apuesta:</span>
                    <strong x-text="getBetType()"></strong>
                </div>
                
                <input type="number" class="stake-input" x-model="stake" :placeholder="'Importe (K) • Min: ' + minStake + ' - Max: ' + maxStake" :min="minStake" :max="maxStake" style="margin-top: 1rem;">
                
                <div class="ticket-footer">
                    <div class="footer-item">
                        <span class="footer-label">Cuota Total</span>
                        <span class="footer-value" x-text="totalOdds.toFixed(2)"></span>
                    </div>
                    <div class="footer-item align-end">
                        <span class="footer-label">Ganancia Potencial</span>
                        <span class="footer-value success"><span x-text="potentialPayout.toFixed(2)"></span> K</span>
                    </div>
                </div>

                <?php if(session()->get('isLoggedIn')): ?>
                    <button class="btn-place-bet" :disabled="selections.length === 0 || isNaN(parseFloat(stake)) || parseFloat(stake) < minStake || parseFloat(stake) > maxStake || isPlacingBet || (isBuilderActive && builderError) || getBetSlipError() !== ''" @click="placeBet" style="margin-top: 1rem;">
                        <span x-text="isPlacingBet ? 'Procesando...' : 'Apostar'"></span>
                    </button>
                <?php else: ?>
                    <div x-show="selections.length > 0" class="login-invite" style="margin-top: 1rem; padding: 0.75rem; background: rgba(99, 102, 241, 0.08); border: 1px dashed rgba(99, 102, 241, 0.3); border-radius: 8px; text-align: center;">
                        <p style="font-size: 0.78rem; color: var(--text-primary); margin-bottom: 0.5rem; line-height: 1.35;">
                            🔒 <strong>¡Tu selección está guardada!</strong> Inicia sesión para confirmar tu apuesta.
                        </p>
                    </div>
                    <button class="btn-place-bet" style="margin-top: 1rem; background: var(--border); color: #fff;" onclick="window.location.href='/auth/login'">
                        Inicia sesión para apostar
                    </button>
                <?php endif; ?>
            </div>
        </aside>
    </main>

    <!-- Scripts -->
    <script src="https://unpkg.com/htmx.org@1.9.10" defer></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    
    <script>
        const initWalletBalance = <?= isset($walletBalance) ? $walletBalance : '0.00' ?>;
        const initMinStake = <?= isset($minStake) ? $minStake : '100.00' ?>;
        const initMaxStake = <?= isset($maxStake) ? $maxStake : '100000.00' ?>;

        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
        });

        function betSlipApp() {
            return {
                selections: [],
                stake: '',
                isBuilderActive: false,
                builderOdds: 0.00,
                builderError: '',
                isPlacingBet: false,
                walletBalance: initWalletBalance,
                minStake: initMinStake,
                maxStake: initMaxStake,
                isLeftColOpen: false,
                isRightColOpen: false,
                isLightTheme: false,
                marketSearch: '',
                activeMarketGroup: 'all',
                openMarkets: {
                    principal: true,
                    goles: true,
                    handicap: true,
                    exactos: false,
                    especiales: false,
                    otros: true
                },

                saveSlip() {
                    localStorage.setItem('codex_ss_bet_slip', JSON.stringify(this.selections));
                },

                init() {
                    const savedTheme = localStorage.getItem('codex_ss_theme') || 'dark';
                    this.isLightTheme = (savedTheme === 'light');
                    if (this.isLightTheme) {
                        document.documentElement.classList.add('light-theme');
                    } else {
                        document.documentElement.classList.remove('light-theme');
                    }

                    // Load selections from localStorage
                    const savedSlip = localStorage.getItem('codex_ss_bet_slip');
                    if (savedSlip) {
                        try {
                            this.selections = JSON.parse(savedSlip);
                            this.updateBuilderOdds();
                        } catch (e) {
                            console.error("Error parsing saved bet slip:", e);
                        }
                    }
                },

                toggleTheme() {
                    this.isLightTheme = !this.isLightTheme;
                    if (this.isLightTheme) {
                        document.documentElement.classList.add('light-theme');
                        localStorage.setItem('codex_ss_theme', 'light');
                    } else {
                        document.documentElement.classList.remove('light-theme');
                        localStorage.setItem('codex_ss_theme', 'dark');
                    }
                },

                toggleMarketGroup(group) {
                    this.openMarkets[group] = !this.openMarkets[group];
                },

                groupVisible(group) {
                    return this.activeMarketGroup === 'all' || this.activeMarketGroup === group;
                },

                marketVisible(haystack, group) {
                    if (!this.groupVisible(group)) return false;
                    const term = this.normalizeSearch(this.marketSearch);
                    if (!term) return true;
                    return this.normalizeSearch(haystack).includes(term);
                },

                normalizeSearch(value) {
                    return String(value || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '');
                },

                async toggleSelection(oddData) {
                    const idx = this.selections.findIndex(s => s.id === oddData.id);
                    if (idx >= 0) {
                        this.selections.splice(idx, 1);
                    } else {
                        if (!this.isBuilderActive) {
                            this.selections = this.selections.filter(s => s.event_id !== oddData.event_id);
                        }
                        this.selections.push(oddData);
                    }
                    this.saveSlip();
                    await this.updateBuilderOdds();
                },
                async removeSelection(id) {
                    this.selections = this.selections.filter(s => s.id !== id);
                    this.saveSlip();
                    await this.updateBuilderOdds();
                },
                isSelected(id) {
                    return this.selections.some(s => s.id === id);
                },
                getBetType() {
                    if (this.selections.length === 0) return '-';
                    if (this.isBuilderActive) return 'Creador de Apuestas (Bet Builder)';
                    if (this.selections.length === 1) return 'Simple';
                    return 'Combinada (' + this.selections.length + ' selecciones)';
                },
                get totalOdds() {
                    if (this.selections.length === 0) return 0.00;
                    if (this.isBuilderActive) {
                        return this.builderOdds;
                    }
                    return this.selections.reduce((acc, curr) => acc * curr.odds, 1);
                },
                get potentialPayout() {
                    const st = parseFloat(this.stake);
                    if (isNaN(st) || st <= 0) return 0.00;
                    return st * this.totalOdds;
                },
                getBetSlipError() {
                    for (let s of this.selections) {
                        if (s.event_status === 'live') {
                            return `No se permiten apuestas en "${s.teams}" porque está en vivo.`;
                        }
                        if (s.event_start_time) {
                            const startTime = new Date(s.event_start_time.replace(/-/g, '/')).getTime();
                            const now = new Date().getTime();
                            if (startTime - now <= 1800000) { // 30 minutes
                                return `No se permiten apuestas en "${s.teams}" porque comienza en menos de 30 minutos.`;
                            }
                        }
                    }
                    if (this.stake !== '') {
                        const st = parseFloat(this.stake);
                        if (isNaN(st) || st <= 0) return 'El importe debe ser mayor a 0.';
                        if (st < this.minStake) return `El importe mínimo por apuesta es ${this.minStake} K.`;
                        if (st > this.maxStake) return `El importe máximo por apuesta es ${this.maxStake} K.`;
                    }
                    return '';
                },
                async updateBuilderOdds() {
                    if (this.selections.length === 0) {
                        this.builderOdds = 0.00;
                        this.builderError = '';
                        return;
                    }
                    
                    if (!this.isBuilderActive) {
                        this.builderError = '';
                        return;
                    }

                    try {
                        const response = await fetch('/api/sportsbook/calculate-builder', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                            },
                            body: JSON.stringify({ selections: this.selections })
                        });
                        const result = await response.json();
                        if (result.status === 'success') {
                            this.builderOdds = result.odds;
                            this.builderError = '';
                        } else {
                            this.builderOdds = 0.00;
                            this.builderError = result.message;
                        }
                    } catch (e) {
                        console.error("Error calculating builder odds:", e);
                        this.builderError = "Error al calcular cuotas combinadas.";
                    }
                },
                async toggleBuilderMode() {
                    if (!this.isBuilderActive) {
                        const seenEvents = new Set();
                        this.selections = this.selections.filter(s => {
                            if (seenEvents.has(s.event_id)) return false;
                            seenEvents.add(s.event_id);
                            return true;
                        });
                        this.saveSlip();
                    }
                    await this.updateBuilderOdds();
                },
                async placeBet(acceptOddsChanges = false) {
                    if (this.selections.length === 0 || this.stake <= 0) return;
                    if (this.isBuilderActive && this.builderError) return;
                    this.isPlacingBet = true;

                    try {
                        const response = await fetch('/sportsbook/placeBet', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                            },
                            body: JSON.stringify({ selections: this.selections, stake: this.stake, accept_odds_changes: acceptOddsChanges })
                        });
                        const result = await response.json();

                        if (result.status === 'success') {
                            this.walletBalance = result.new_balance;
                            this.selections = [];
                            this.saveSlip();
                            this.stake = '';
                            Swal.fire({ 
                                icon: 'success', 
                                title: 'Apuesta Confirmada', 
                                text: result.message + ' Ticket ID: #' + result.ticket_id, 
                                background: 'var(--bg-panel)', 
                                color: '#fff', 
                                confirmButtonColor: 'var(--primary)' 
                            });
                        } else if (result.status === 'odds_changed') {
                            const html = (result.changes || []).map(change => `
                                <div style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px solid rgba(255,255,255,0.08);padding:0.55rem 0;text-align:left;">
                                    <div>
                                        <div style="font-weight:800;">${change.teams}</div>
                                        <div style="font-size:0.82rem;opacity:0.75;">${change.market} - ${change.selection}</div>
                                    </div>
                                    <div style="font-weight:900;white-space:nowrap;">${Number(change.old_odds).toFixed(2)} &rarr; ${Number(change.new_odds).toFixed(2)}</div>
                                </div>
                            `).join('');

                            const confirmation = await Swal.fire({
                                icon: 'warning',
                                title: 'Cuotas actualizadas',
                                html,
                                showCancelButton: true,
                                confirmButtonText: 'Aceptar cambios',
                                cancelButtonText: 'Cancelar',
                                background: 'var(--bg-panel)',
                                color: '#fff',
                                confirmButtonColor: 'var(--primary)'
                            });

                            if (confirmation.isConfirmed) {
                                for (const change of result.changes || []) {
                                    const selection = this.selections.find(item => Number(item.id) === Number(change.id));
                                    if (selection) selection.odds = Number(change.new_odds);
                                }
                                this.saveSlip();
                                await this.updateBuilderOdds();
                                await this.placeBet(true);
                            }
                        } else if (result.message && result.message.includes('iniciar')) {
                            window.location.href = '/auth/login';
                        } else {
                            Swal.fire({ 
                                icon: 'error', 
                                title: 'Error', 
                                text: result.message, 
                                background: 'var(--bg-panel)', 
                                color: '#fff', 
                                confirmButtonColor: 'var(--primary)' 
                            });
                        }
                    } catch (error) {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error de Red', 
                            text: 'No se pudo conectar con el servidor.', 
                            background: 'var(--bg-panel)', 
                            color: '#fff', 
                            confirmButtonColor: 'var(--primary)' 
                        });
                    } finally {
                        this.isPlacingBet = false;
                    }
                }
            }
        }

        function handleDeposit() {
            Swal.fire({
                title: 'Depositar Fondos',
                input: 'number',
                inputLabel: 'Monto a depositar (K)',
                inputPlaceholder: 'Ej. 5000',
                showCancelButton: true,
                confirmButtonText: 'Ir a Pagar',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-panel)',
                color: '#fff',
                confirmButtonColor: 'var(--primary)',
                inputAttributes: {
                    min: 1,
                    step: 1
                },
                preConfirm: (amount) => {
                    if (!amount || amount <= 0) {
                        Swal.showValidationMessage('Ingresa un monto válido');
                        return false;
                    }
                    return amount;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/checkout?amount=' + parseFloat(result.value);
                }
            });
        }

        function handleWithdrawal() {
            Swal.fire({
                title: 'Solicitar Retiro',
                html: `
                    <input id="withdraw-amount" class="swal2-input" type="number" min="1000" step="1" placeholder="Monto a retirar (K)">
                    <input id="withdraw-holder" class="swal2-input" type="text" placeholder="Titular de la cuenta">
                    <input id="withdraw-document" class="swal2-input" type="text" placeholder="Documento del titular">
                    <input id="withdraw-account" class="swal2-input" type="text" placeholder="Alias, CVU o cuenta destino">
                    <label style="display:flex;align-items:center;gap:0.5rem;margin:0.75rem 2.6rem 0;color:#cbd5e1;font-size:0.82rem;text-align:left;">
                        <input id="withdraw-own-account" type="checkbox" style="width:auto;margin:0;">
                        La cuenta destino es propia y coincide con mi KYC
                    </label>
                    <textarea id="withdraw-note" class="swal2-textarea" placeholder="Nota opcional"></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Solicitar',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-panel)',
                color: '#fff',
                confirmButtonColor: 'var(--primary)',
                preConfirm: () => {
                    const amount = parseFloat(document.getElementById('withdraw-amount').value);
                    const holder = document.getElementById('withdraw-holder').value.trim();
                    const documentNumber = document.getElementById('withdraw-document').value.trim();
                    const target = document.getElementById('withdraw-account').value.trim();
                    const ownAccount = document.getElementById('withdraw-own-account').checked;
                    const note = document.getElementById('withdraw-note').value.trim();
                    if (!amount || amount < 1000) {
                        Swal.showValidationMessage('El retiro mínimo es 1000 K');
                        return false;
                    }
                    if (!target || target.length < 5) {
                        Swal.showValidationMessage('Indica una cuenta destino válida');
                        return false;
                    }
                    if (!holder || holder.length < 3) {
                        Swal.showValidationMessage('Indica el titular de la cuenta');
                        return false;
                    }
                    if (!documentNumber || documentNumber.length < 6) {
                        Swal.showValidationMessage('Indica el documento del titular');
                        return false;
                    }
                    if (!ownAccount) {
                        Swal.showValidationMessage('Debes confirmar que la cuenta destino es propia');
                        return false;
                    }
                    return { amount, target_account: target, account_holder: holder, account_document: documentNumber, own_account_confirmed: ownAccount, note };
                }
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                const response = await fetch('/sportsbook/withdrawal-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                    },
                    body: JSON.stringify(result.value)
                });
                const data = await response.json();
                if (data.status !== 'success') {
                    Swal.fire({ icon: 'error', title: 'No se pudo solicitar', text: data.message || 'Intenta nuevamente', background: 'var(--bg-panel)', color: '#fff' });
                    return;
                }
                const app = document.querySelector('body')._x_dataStack?.[0];
                if (app && typeof data.new_balance !== 'undefined') app.walletBalance = parseFloat(data.new_balance);
                Swal.fire({ icon: 'success', title: 'Retiro solicitado', text: data.message, background: 'var(--bg-panel)', color: '#fff', confirmButtonColor: 'var(--primary)' });
            });
        }

        // --- WEBSOCKET REAL-TIME LOGIC ---
        document.addEventListener("DOMContentLoaded", function() {
            // Conectar al servidor Socket.io
            const socket = io('http://localhost:3000');
            
            socket.on('connect', () => {
                console.log('Conectado al servidor de WebSockets para cuotas en vivo');
                // Opcional: suscribirse al evento actual
                // socket.emit('subscribe_event', <?= (int)$event['id'] ?>);
            });

            socket.on('odd_update', (change) => {
                const btn = document.getElementById('odd-btn-' + change.odd_id);
                const valSpan = document.getElementById('odd-val-' + change.odd_id);
                
                if (btn && valSpan) {
                    // Update visual value
                    valSpan.innerText = parseFloat(change.new_value).toFixed(2);
                    
                    // Determinar dirección para animación CSS (como en Betsson)
                    const direction = parseFloat(change.new_value) > parseFloat(change.old_value) ? 'up' : 'down';
                    
                    // Add flash animation based on direction
                    btn.classList.remove('flash-up', 'flash-down');
                    void btn.offsetWidth; // trigger reflow to restart animation
                    if (direction === 'up') {
                        btn.classList.add('flash-up');
                    } else {
                        btn.classList.add('flash-down');
                    }

                    // Update Alpine state if needed (Boleto de apuestas)
                    const alpineApp = document.querySelector('body')._x_dataStack?.[0];
                    if (alpineApp) {
                        const slipItem = alpineApp.selections.find(s => Number(s.id) === Number(change.odd_id));
                        if (slipItem) {
                            slipItem.odds = parseFloat(change.new_value);
                            alpineApp.updateBuilderOdds();
                        }
                    }
                }
            });

            socket.on('disconnect', () => {
                console.log('Desconectado del servidor de WebSockets');
            });
        });
    </script>
</body>
</html>
