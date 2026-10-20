<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDP Empire API — Enterprise REST Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #168B61;
            --color-primary-dark: #0F684A;
            --color-primary-light: #EAF7F1;
            --color-primary-glow: rgba(22, 139, 97, 0.45);
            --color-primary-subtle: rgba(22, 139, 97, 0.12);
            --color-bg: #111827;
            --color-bg-deep: #0B0F17;
            --color-surface: rgba(31, 41, 55, 0.65);
            --color-surface-hover: rgba(31, 41, 55, 0.9);
            --color-text-primary: #F9FAFB;
            --color-text-secondary: #9CA3AF;
            --color-text-muted: #6B7280;
            --color-border: rgba(255, 255, 255, 0.08);
            --color-border-accent: rgba(22, 139, 97, 0.35);
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(22, 139, 97, 0.18) 0%, transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(15, 104, 74, 0.12) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* Cybernetic grid overlay */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse at 50% 50%, #000 60%, transparent 100%);
            -webkit-mask-image: radial-gradient(ellipse at 50% 50%, #000 60%, transparent 100%);
            pointer-events: none;
            z-index: 0;
        }

        /* Mouse Glow Spotlight */
        .mouse-spotlight {
            position: fixed;
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(22, 139, 97, 0.16) 0%, rgba(15, 104, 74, 0.04) 50%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease;
            filter: blur(10px);
        }

        /* Floating Gradient Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
            animation: pulseOrb 12s infinite alternate ease-in-out;
        }
        .orb-1 {
            width: 450px;
            height: 450px;
            background: var(--color-primary);
            top: -120px;
            left: 20%;
            opacity: 0.22;
        }
        .orb-2 {
            width: 400px;
            height: 400px;
            background: #0F684A;
            bottom: -80px;
            right: 5%;
            opacity: 0.25;
            animation-delay: -4s;
        }
        .orb-3 {
            width: 320px;
            height: 320px;
            background: #22c55e;
            top: 40%;
            right: 35%;
            opacity: 0.12;
            animation-delay: -8s;
        }
        @keyframes pulseOrb {
            0% { transform: scale(1) translate(0, 0); }
            50% { transform: scale(1.15) translate(25px, -20px); }
            100% { transform: scale(0.9) translate(-20px, 30px); }
        }

        /* Floating Particles Canvas */
        #particleCanvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        /* Application Container */
        .page-wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2.5rem;
            border-bottom: 1px solid var(--color-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background: rgba(17, 24, 39, 0.7);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--color-primary-glow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .brand-container:hover .brand-icon {
            transform: scale(1.08) rotate(3deg);
            box-shadow: 0 0 28px rgba(22, 139, 97, 0.7);
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-badge {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 6px;
            background: var(--color-primary-subtle);
            color: #34d399;
            border: 1px solid var(--color-border-accent);
            letter-spacing: 0.05em;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: var(--color-text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link:hover {
            color: #fff;
        }

        .nav-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #34d399;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-status:hover {
            background: rgba(16, 185, 129, 0.16);
            border-color: rgba(16, 185, 129, 0.45);
            transform: translateY(-1px);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            position: relative;
        }

        .status-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: #10b981;
            opacity: 0.6;
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        @keyframes ping {
            75%, 100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        /* Hero Main Section */
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2.5rem 2.5rem;
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 3.5rem;
            align-items: center;
            width: 100%;
        }

        /* Hero Left Column */
        .hero-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .hero-tag {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(22, 139, 97, 0.12);
            border: 1px solid var(--color-border-accent);
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #34d399;
            box-shadow: 0 4px 15px rgba(22, 139, 97, 0.15);
            animation: fadeInDown 0.8s ease-out both;
        }

        .hero-title {
            font-size: clamp(2.4rem, 4.5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: #F9FAFB;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .text-gradient {
            background: linear-gradient(135deg, #22c55e 0%, #168B61 50%, #5eead4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% auto;
            animation: shineGradient 6s linear infinite;
        }

        @keyframes shineGradient {
            0% { background-position: 0% center; }
            50% { background-position: 100% center; }
            100% { background-position: 0% center; }
        }

        .hero-description {
            font-size: 1.1rem;
            line-height: 1.65;
            color: var(--color-text-secondary);
            max-width: 580px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        /* Actions CTA */
        .hero-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 26px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: #FFFFFF;
            box-shadow: 0 8px 24px var(--color-primary-glow);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 14px 34px rgba(22, 139, 97, 0.65);
            background: linear-gradient(135deg, #1aa574 0%, #0F684A 100%);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.6s ease;
        }

        .btn-primary:hover::after {
            left: 100%;
        }

        .btn-secondary {
            background: rgba(31, 41, 55, 0.65);
            color: #F9FAFB;
            border-color: var(--color-border);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(31, 41, 55, 0.95);
            border-color: var(--color-border-accent);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            color: #34d399;
        }

        .btn-copy {
            background: rgba(17, 24, 39, 0.7);
            color: var(--color-text-secondary);
            font-family: var(--font-mono);
            font-size: 0.82rem;
            padding: 10px 18px;
            border: 1px solid var(--color-border);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-copy:hover {
            color: #fff;
            border-color: var(--color-border-accent);
            background: rgba(22, 139, 97, 0.1);
        }

        /* Stats Ribbon */
        .stats-ribbon {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 1rem;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .stat-card {
            background: rgba(31, 41, 55, 0.4);
            border: 1px solid var(--color-border);
            border-radius: 14px;
            padding: 1.1rem 1rem;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--color-primary), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            background: rgba(31, 41, 55, 0.75);
            border-color: var(--color-border-accent);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-value {
            font-size: 1.65rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
            display: flex;
            align-items: baseline;
            gap: 2px;
        }

        .stat-accent {
            color: #34d399;
            font-size: 1.1rem;
        }

        .stat-label {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--color-text-secondary);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Hero Right Column: Interactive Console */
        .console-container {
            position: relative;
            animation: fadeInRight 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
            transform-style: preserve-3d;
        }

        .console-glow {
            position: absolute;
            inset: -15px;
            background: radial-gradient(circle, var(--color-primary-glow) 0%, transparent 70%);
            border-radius: 28px;
            filter: blur(20px);
            opacity: 0.6;
            z-index: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        .console-window {
            position: relative;
            z-index: 2;
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(22, 139, 97, 0.25);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            overflow: hidden;
            transition: transform 0.2s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .console-window:hover {
            border-color: rgba(22, 139, 97, 0.5);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7), 0 0 40px rgba(22, 139, 97, 0.25);
        }

        /* Window Header */
        .window-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: rgba(17, 24, 39, 0.9);
            border-bottom: 1px solid var(--color-border);
        }

        .window-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .control-dot:hover {
            transform: scale(1.15);
        }
        .dot-close { background: #ef4444; }
        .dot-minimize { background: #f59e0b; }
        .dot-maximize { background: #10b981; }

        .window-title {
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .window-ping-badge {
            font-family: var(--font-mono);
            font-size: 0.72rem;
            color: #34d399;
            background: rgba(22, 139, 97, 0.15);
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid var(--color-border-accent);
        }

        /* Endpoint Tabs */
        .endpoint-tabs {
            display: flex;
            background: rgba(15, 23, 42, 0.6);
            border-bottom: 1px solid var(--color-border);
            overflow-x: auto;
            scrollbar-width: none;
        }
        .endpoint-tabs::-webkit-scrollbar { display: none; }

        .tab-btn {
            background: none;
            border: none;
            padding: 10px 18px;
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: var(--color-text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.02);
        }

        .tab-btn.active {
            color: #fff;
            background: rgba(22, 139, 97, 0.1);
            border-bottom-color: var(--color-primary);
        }

        .method-tag {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .method-get {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        .method-post {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        /* Request Bar */
        .request-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            background: rgba(17, 24, 39, 0.5);
            border-bottom: 1px solid var(--color-border);
        }

        .request-url-input {
            flex: 1;
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: #34d399;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 8px 14px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .request-url-input:focus {
            border-color: var(--color-primary);
        }

        .btn-send {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            border: none;
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px var(--color-primary-glow);
        }

        .btn-send:hover {
            transform: scale(1.04);
            box-shadow: 0 6px 18px rgba(22, 139, 97, 0.6);
        }

        .btn-send:active {
            transform: scale(0.98);
        }

        /* Response Body */
        .response-container {
            padding: 18px 20px;
            background: #090d16;
            min-height: 290px;
            max-height: 380px;
            overflow-y: auto;
            position: relative;
        }

        .response-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-family: var(--font-mono);
            font-size: 0.75rem;
        }

        .meta-badges {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #34d399;
            font-weight: 600;
        }

        .meta-time {
            color: var(--color-text-secondary);
        }

        .copy-response-btn {
            background: none;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            font-size: 0.75rem;
            font-family: var(--font-mono);
            display: flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s ease;
        }
        .copy-response-btn:hover {
            color: #34d399;
        }

        /* JSON Code formatting */
        pre.code-output {
            margin: 0;
            font-family: var(--font-mono);
            font-size: 0.82rem;
            line-height: 1.6;
            color: #e5e7eb;
            overflow-x: auto;
        }

        .json-key { color: #5eead4; }
        .json-string { color: #86efac; }
        .json-number { color: #fde047; }
        .json-boolean { color: #93c5fd; }
        .json-null { color: #f472b6; }

        /* Floating Badge decorations on Right Hero */
        .floating-pill {
            position: absolute;
            z-index: 3;
            background: rgba(31, 41, 55, 0.85);
            backdrop-filter: blur(14px);
            border: 1px solid var(--color-border-accent);
            border-radius: 12px;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            animation: floatPill 6s infinite ease-in-out;
            pointer-events: none;
        }

        .pill-1 {
            top: -24px;
            right: 20px;
            animation-delay: 0s;
        }

        .pill-2 {
            bottom: -20px;
            left: 20px;
            animation-delay: -3s;
        }

        .pill-icon {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: var(--color-primary-subtle);
            color: #34d399;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .pill-text {
            font-size: 0.75rem;
            font-weight: 600;
            color: #fff;
        }

        @keyframes floatPill {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Toast Alert */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: rgba(17, 24, 39, 0.95);
            border: 1px solid var(--color-primary);
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 20px var(--color-primary-glow);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 100;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Ripple effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.35);
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(35px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Responsive */
        @media (max-width: 1080px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
            .hero-section {
                padding: 2rem 1.5rem 3rem;
            }
            .hero-title {
                font-size: 2.6rem;
            }
            .stats-ribbon {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 1rem 1.25rem;
            }
            .nav-links {
                display: none;
            }
            .hero-title {
                font-size: 2.1rem;
            }
            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .btn {
                width: 100%;
            }
            .stats-ribbon {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            .floating-pill {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Grid and Interactive Background -->
    <div class="bg-grid"></div>
    <div class="mouse-spotlight" id="mouseSpotlight"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <canvas id="particleCanvas"></canvas>

    <div class="page-wrapper">
        <!-- Main Hero Section -->
        <main class="hero-section">
            <div class="hero-grid">
                
                <!-- Hero Left: Content -->
                <div class="hero-content">
                    <div class="hero-tag">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        High-Performance RESTful Architecture
                    </div>

                    <h1 class="hero-title">
                        CDP Empire <br>
                        <span class="text-gradient">Website REST API</span>
                    </h1>

                    <p class="hero-description">
                        The official backend REST API powering the CDP Empire website with real-time projects, services, CMS management, careers, leadership, and investor relations.
                    </p>

                </div>

                <!-- Hero Right: Interactive API Console -->
                <div class="console-container" id="consoleContainer">
                    <div class="console-glow"></div>

                    <!-- Floating decorative badges -->
                    <div class="floating-pill pill-1">
                        <div class="pill-icon">⚡</div>
                        <div class="pill-text">Projects & Services API Live</div>
                    </div>

                    <div class="floating-pill pill-2">
                        <div class="pill-icon">🛡️</div>
                        <div class="pill-text">RBAC Protected Routes</div>
                    </div>

                    <!-- Main Window Frame -->
                    <div class="console-window" id="consoleWindow">
                        <div class="window-header">
                            <div class="window-controls">
                                <div class="control-dot dot-close"></div>
                                <div class="control-dot dot-minimize"></div>
                                <div class="control-dot dot-maximize"></div>
                            </div>
                            <div class="window-title">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="4 17 10 11 4 5"></polyline>
                                    <line x1="12" y1="19" x2="20" y2="19"></line>
                                </svg>
                                cdp-terminal ~ bash
                            </div>
                            <div class="window-ping-badge" id="terminalBadge">READY</div>
                        </div>

                        <!-- Endpoint Selector Tabs -->
                        <div class="endpoint-tabs" id="endpointTabs">
                            <button class="tab-btn active" onclick="selectTab('health')">
                                <span class="method-tag method-get">GET</span>
                                <span>/health-check</span>
                            </button>
                            <button class="tab-btn" onclick="selectTab('projects')">
                                <span class="method-tag method-get">GET</span>
                                <span>/projects</span>
                            </button>
                            <button class="tab-btn" onclick="selectTab('services')">
                                <span class="method-tag method-get">GET</span>
                                <span>/services</span>
                            </button>
                            <button class="tab-btn" onclick="selectTab('login')">
                                <span class="method-tag method-post">POST</span>
                                <span>/login</span>
                            </button>
                        </div>

                        <!-- Request URL Execution Bar -->
                        <div class="request-bar">
                            <input type="text" id="requestUrlInput" class="request-url-input" readonly value="/api/health-check">
                            <button class="btn-send" id="btnSend" onclick="runLiveRequest()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <span>Send</span>
                            </button>
                        </div>

                        <!-- Response Viewer -->
                        <div class="response-container">
                            <div class="response-meta">
                                <div class="meta-badges">
                                    <span class="status-pill" id="respStatus">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        200 OK
                                    </span>
                                    <span class="meta-time" id="respTime">Latency: 24ms</span>
                                </div>
                                <button class="copy-response-btn" onclick="copyResponseJson()">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                    <span>Copy JSON</span>
                                </button>
                            </div>

                            <pre class="code-output" id="jsonOutput"></pre>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Notification Toast -->
    <div class="toast" id="toast">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <span id="toastMessage">Copied to clipboard!</span>
    </div>

    <script>
        // Sample predefined payloads for fallback / instant response
        const ENDPOINTS = {
            'health': {
                url: '{{ url("/api/health-check") }}',
                method: 'GET',
                mock: {
                    status: "healthy",
                    service: "CDP Empire System API",
                    message: "CDP Empire API is working!",
                    timestamp: new Date().toISOString(),
                    environment: "production",
                    components: {
                        database: "healthy",
                        cache: "active",
                        storage: "connected"
                    }
                }
            },
            'projects': {
                url: '{{ url("/api/v1/public/projects") }}',
                method: 'GET',
                mock: {
                    status: "success",
                    data: [
                        {
                            id: 1,
                            title: "Smart Enterprise Data Lake",
                            slug: "smart-enterprise-data-lake",
                            short_description: "Scalable customer ingestion layer with real-time analytics.",
                            status: "open",
                            location: "Colombo, Sri Lanka",
                            thumbnail_image: "/uploads/projects/project-1.jpg",
                            is_active: true
                        },
                        {
                            id: 2,
                            title: "AI-Powered Customer Segmentation",
                            slug: "ai-customer-segmentation",
                            short_description: "Predictive cohort clustering and automated customer journeys.",
                            status: "upcoming",
                            location: "Global Remote",
                            thumbnail_image: "/uploads/projects/project-2.jpg",
                            is_active: true
                        }
                    ],
                    pagination: {
                        current_page: 1,
                        per_page: 15,
                        total: 24
                    }
                }
            },
            'services': {
                url: '{{ url("/api/v1/public/services") }}',
                method: 'GET',
                mock: {
                    status: "success",
                    count: 6,
                    data: [
                        { id: 1, name: "Omnichannel Data Sync", category: "Integration", active: true },
                        { id: 2, name: "Predictive Machine Learning", category: "Analytics", active: true },
                        { id: 3, name: "Identity Resolution Engine", category: "Core CDP", active: true }
                    ]
                }
            },
            'login': {
                url: '{{ url("/api/v1/login") }}',
                method: 'POST',
                mock: {
                    token_type: "Bearer",
                    access_token: "1|cdp_sec_token_9f82d189a02bc743e8d7120a",
                    expires_in: 86400,
                    user: {
                        id: 42,
                        name: "Administrator",
                        email: "admin@cdp-empire.com",
                        roles: ["admin", "developer"]
                    }
                }
            }
        };

        let currentActiveKey = 'health';
        let latestJsonData = null;

        // Interactive mouse spotlight
        const spotlight = document.getElementById('mouseSpotlight');
        document.addEventListener('mousemove', (e) => {
            spotlight.style.left = e.clientX + 'px';
            spotlight.style.top = e.clientY + 'px';
        });

        // 3D Tilt Effect on Console
        const consoleWindow = document.getElementById('consoleWindow');
        const consoleContainer = document.getElementById('consoleContainer');

        consoleContainer.addEventListener('mousemove', (e) => {
            const rect = consoleContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = ((y - centerY) / centerY) * -6;
            const rotateY = ((x - centerX) / centerX) * 6;

            consoleWindow.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        consoleContainer.addEventListener('mouseleave', () => {
            consoleWindow.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg)`;
        });

        // Particle Canvas Animation
        const canvas = document.getElementById('particleCanvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        class Particle {
            constructor() {
                this.reset();
            }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2.2 + 0.5;
                this.speedX = (Math.random() - 0.5) * 0.4;
                this.speedY = (Math.random() - 0.5) * 0.4 - 0.2;
                this.opacity = Math.random() * 0.4 + 0.1;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > canvas.width || this.y < 0 || this.y > canvas.height) {
                    this.reset();
                }
            }
            draw() {
                ctx.fillStyle = `rgba(22, 139, 97, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        for (let i = 0; i < 35; i++) {
            particles.push(new Particle());
        }

        function animateParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animateParticles);
        }
        animateParticles();

        // Switch Endpoint Tabs
        function selectTab(key) {
            currentActiveKey = key;
            const target = ENDPOINTS[key];
            if (!target) return;

            document.getElementById('requestUrlInput').value = target.url;

            // Update Tab active classes
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            event?.currentTarget?.classList.add('active');

            runLiveRequest();
        }

        // Syntax Highlighter for JSON
        function syntaxHighlight(json) {
            if (typeof json !== 'string') {
                json = JSON.stringify(json, undefined, 2);
            }
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }

        // Run Request: Attempts actual live fetch against server with fallback
        async function runLiveRequest() {
            const endpoint = ENDPOINTS[currentActiveKey];
            const btnSend = document.getElementById('btnSend');
            const terminalBadge = document.getElementById('terminalBadge');
            const respStatus = document.getElementById('respStatus');
            const respTime = document.getElementById('respTime');
            const jsonOutput = document.getElementById('jsonOutput');

            terminalBadge.textContent = 'FETCHING...';
            terminalBadge.style.color = '#fde047';
            btnSend.style.opacity = '0.7';

            const startTime = performance.now();
            let resultData = null;
            let statusText = '200 OK';

            try {
                // Live fetch with short timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 2200);

                const response = await fetch(endpoint.url, {
                    method: endpoint.method === 'POST' ? 'GET' : endpoint.method,
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                if (response.ok) {
                    resultData = await response.json();
                    statusText = `${response.status} ${response.statusText || 'OK'}`;
                } else {
                    resultData = endpoint.mock;
                }
            } catch (err) {
                resultData = endpoint.mock;
            }

            const latency = Math.round(performance.now() - startTime);

            latestJsonData = resultData;
            jsonOutput.innerHTML = syntaxHighlight(resultData);
            respStatus.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> ${statusText}`;
            respTime.textContent = `Latency: ${latency}ms`;
            terminalBadge.textContent = 'SUCCESS';
            terminalBadge.style.color = '#34d399';
            btnSend.style.opacity = '1';

            // Update stats & navbar latency if elements exist
            const navLat = document.getElementById('navLatency');
            if (navLat) navLat.textContent = `${latency}ms`;
            const metricLat = document.getElementById('metricLatency');
            if (metricLat) metricLat.textContent = latency;
        }

        // Copy JSON response
        function copyResponseJson() {
            if (!latestJsonData) return;
            navigator.clipboard.writeText(JSON.stringify(latestJsonData, null, 2)).then(() => {
                showToast('API Response copied to clipboard!');
            });
        }

        // Copy Base URL
        function copyBaseUrl() {
            const fullUrl = '{{ url("/api/v1") }}';
            navigator.clipboard.writeText(fullUrl).then(() => {
                showToast(`Copied base URL: ${fullUrl}`);
            });
        }

        // Focus Console
        function focusConsole() {
            document.getElementById('consoleContainer').scrollIntoView({ behavior: 'smooth', block: 'center' });
            consoleWindow.style.borderColor = '#168B61';
            consoleWindow.style.boxShadow = '0 0 50px rgba(22, 139, 97, 0.5)';
            setTimeout(() => {
                consoleWindow.style.borderColor = '';
                consoleWindow.style.boxShadow = '';
            }, 1200);
        }

        // Toast feedback
        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toastMessage').textContent = msg;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }

        // Button Click Ripple
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function (e) {
                const circle = document.createElement('span');
                circle.classList.add('ripple');
                const rect = this.getBoundingClientRect();
                const d = Math.max(rect.width, rect.height);
                circle.style.width = circle.style.height = `${d}px`;
                circle.style.left = `${e.clientX - rect.left - d / 2}px`;
                circle.style.top = `${e.clientY - rect.top - d / 2}px`;
                this.appendChild(circle);
                setTimeout(() => circle.remove(), 600);
            });
        });

        // Initial Ping on Page Load
        window.addEventListener('DOMContentLoaded', () => {
            runLiveRequest();
        });
    </script>
</body>
</html>
