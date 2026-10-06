<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDP Empire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1f15 0%, #111827 50%, #0f172a 100%);
            min-height: 100vh;
            overflow: hidden;
            color: #ffffff;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(22, 139, 97, 0.4);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 20s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 18s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 22s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 16s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 24s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 19s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 0.5s; animation-duration: 21s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 2.5s; animation-duration: 17s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 4.5s; animation-duration: 23s; }
        .particle:nth-child(10) { left: 15%; animation-delay: 1.5s; animation-duration: 25s; }
        .particle:nth-child(11) { left: 25%; animation-delay: 3.5s; animation-duration: 14s; }
        .particle:nth-child(12) { left: 35%; animation-delay: 0.8s; animation-duration: 26s; }
        .particle:nth-child(13) { left: 45%; animation-delay: 2.8s; animation-duration: 15s; }
        .particle:nth-child(14) { left: 55%; animation-delay: 4.8s; animation-duration: 27s; }
        .particle:nth-child(15) { left: 65%; animation-delay: 1.8s; animation-duration: 13s; }

        @keyframes float {
            0%, 100% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: pulse 8s infinite ease-in-out;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: #168B61;
            top: -100px;
            right: -100px;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            background: #0F684A;
            bottom: -50px;
            left: -50px;
            animation-delay: 4s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: #22c55e;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 2s;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.2); opacity: 0.5; }
        }

        .grid-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(22, 139, 97, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(22, 139, 97, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
        }

        .mouse-follower {
            position: fixed;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(22, 139, 97, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 5;
            transform: translate(-50%, -50%);
        }

        .hero {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(22, 139, 97, 0.1);
            border: 1px solid rgba(22, 139, 97, 0.3);
            border-radius: 100px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #168B61;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out 0.2s both;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .badge:hover {
            background: rgba(22, 139, 97, 0.2);
            border-color: rgba(22, 139, 97, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(22, 139, 97, 0.2);
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            background: #168B61;
            border-radius: 50%;
            animation: blink 2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .hero-title {
            font-size: clamp(2.5rem, 8vw, 6rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .line {
            display: block;
        }

        .highlight {
            background: linear-gradient(135deg, #168B61 0%, #22c55e 50%, #168B61 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: #9ca3af;
            max-width: 600px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .cta-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #168B61 0%, #0F684A 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(22, 139, 97, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 40px rgba(22, 139, 97, 0.5);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(22, 139, 97, 0.5);
            transform: translateY(-3px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .floating-card {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.25rem;
            animation: floatCard 6s ease-in-out infinite;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .floating-card:hover {
            transform: scale(1.1) !important;
            border-color: rgba(22, 139, 97, 0.5);
            box-shadow: 0 20px 60px rgba(22, 139, 97, 0.2);
        }

        .floating-card-1 { top: 15%; left: 8%; animation-delay: 0s; }
        .floating-card-2 { top: 20%; right: 8%; animation-delay: 2s; }
        .floating-card-3 { bottom: 25%; left: 10%; animation-delay: 4s; }
        .floating-card-4 { bottom: 20%; right: 10%; animation-delay: 1s; }

        @keyframes floatCard {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: rgba(22, 139, 97, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .card-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .card-desc {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .stats {
            display: flex;
            gap: 3rem;
            margin-top: 4rem;
            animation: fadeInUp 0.8s ease-out 1s both;
        }

        .stat {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 1rem;
            border-radius: 12px;
        }

        .stat:hover {
            background: rgba(22, 139, 97, 0.1);
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #168B61;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }

        .scroll-mouse {
            width: 28px;
            height: 44px;
            border: 2px solid rgba(22, 139, 97, 0.5);
            border-radius: 14px;
            position: relative;
        }

        .scroll-wheel {
            width: 4px;
            height: 8px;
            background: #168B61;
            border-radius: 2px;
            position: absolute;
            left: 50%;
            top: 8px;
            transform: translateX(-50%);
            animation: scrollWheel 2s infinite;
        }

        @keyframes scrollWheel {
            0% { opacity: 1; transform: translateX(-50%) translateY(0); }
            100% { opacity: 0; transform: translateX(-50%) translateY(16px); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(22, 139, 97, 0.3);
            transform: scale(0);
            animation: ripple-effect 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-effect {
            to { transform: scale(4); opacity: 0; }
        }

        @media (max-width: 768px) {
            .floating-card { display: none; }
            .stats { gap: 1.5rem; }
            .stat-value { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="mouse-follower" id="mouseFollower"></div>

    <div class="particles">
        @for($i = 0; $i < 15; $i++)
            <div class="particle"></div>
        @endfor
    </div>

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-pattern"></div>

    <div class="floating-card floating-card-1" data-tilt>
        <div class="card-icon">📊</div>
        <div class="card-title">Analytics</div>
        <div class="card-desc">Real-time insights</div>
    </div>

    <div class="floating-card floating-card-2" data-tilt>
        <div class="card-icon">🔒</div>
        <div class="card-title">Security</div>
        <div class="card-desc">Enterprise grade</div>
    </div>

    <div class="floating-card floating-card-3" data-tilt>
        <div class="card-icon">⚡</div>
        <div class="card-title">Performance</div>
        <div class="card-desc">Lightning fast</div>
    </div>

    <div class="floating-card floating-card-4" data-tilt>
        <div class="card-icon">🎯</div>
        <div class="card-title">Targeting</div>
        <div class="card-desc">Precision control</div>
    </div>

    <section class="hero">
        <div class="hero-content">
            <div class="badge" id="badge">
                <span class="badge-dot"></span>
                API Status: Online
            </div>

            <h1 class="hero-title">
                <span class="line"><span style="animation: fadeInUp 0.8s ease-out 0.1s both">Welcome to</span></span>
                <span class="line"><span class="highlight" style="animation: fadeInUp 0.8s ease-out 0.3s both">CDP Empire</span></span>
                <span class="line"><span style="animation: fadeInUp 0.8s ease-out 0.5s both">API Platform</span></span>
            </h1>

            <p class="hero-subtitle">
                The ultimate Customer Data Platform API that powers your applications
                with real-time data, analytics, and intelligent automation.
            </p>

            <div class="cta-group">
                <a href="/docs" class="btn btn-primary magnetic" id="cta-primary">
                    <span>API Documentation</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
                <a href="/health" class="btn btn-secondary magnetic" id="cta-secondary">
                    <span>Health Check</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                </a>
            </div>

            <div class="stats">
                <div class="stat">
                    <div class="stat-value">v1.0</div>
                    <div class="stat-label">API Version</div>
                </div>
                <div class="stat">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
                <div class="stat">
                    <div class="stat-value">REST</div>
                    <div class="stat-label">API Type</div>
                </div>
                <div class="stat">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
        </div>

        <div class="scroll-indicator">
            <div class="scroll-mouse">
                <div class="scroll-wheel"></div>
            </div>
        </div>
    </section>

    <script>
        const mouseFollower = document.getElementById('mouseFollower');
        let mouseX = 0, mouseY = 0;
        let followerX = 0, followerY = 0;

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });

        function animateFollower() {
            followerX += (mouseX - followerX) * 0.1;
            followerY += (mouseY - followerY) * 0.1;
            mouseFollower.style.left = followerX + 'px';
            mouseFollower.style.top = followerY + 'px';
            requestAnimationFrame(animateFollower);
        }
        animateFollower();

        document.querySelectorAll('[data-tilt]').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            });
        });

        document.querySelectorAll('.magnetic').forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
            });

            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translate(0, 0)';
            });
        });

        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                const rect = this.getBoundingClientRect();
                ripple.style.left = (e.clientX - rect.left) + 'px';
                ripple.style.top = (e.clientY - rect.top) + 'px';
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });

        document.querySelectorAll('.stat').forEach(stat => {
            stat.addEventListener('mouseenter', () => {
                stat.style.transform = 'translateY(-5px) scale(1.05)';
            });
            stat.addEventListener('mouseleave', () => {
                stat.style.transform = 'translateY(0) scale(1)';
            });
        });

        document.addEventListener('mousemove', (e) => {
            const moveX = (e.clientX - window.innerWidth / 2) * 0.02;
            const moveY = (e.clientY - window.innerHeight / 2) * 0.02;

            document.querySelectorAll('.floating-card').forEach((card, index) => {
                const speed = (index + 1) * 0.5;
                card.style.transform = `translate(${moveX * speed}px, ${moveY * speed}px)`;
            });
        });
    </script>
</body>
</html>
