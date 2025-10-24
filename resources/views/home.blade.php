<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Complete Clarity. Total Control.</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #1A4EFF;
            --accent: #00E0C6;
            --secondary: #2B2F36;
            --background: #F7F8FA;
            --text-dark: #1A1A1A;
            --text-light: #6B7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
        }

        .gradient-text {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(26, 78, 255, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid var(--primary);
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .sphere {
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(26, 78, 255, 0.1), rgba(0, 224, 198, 0.1));
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        .sphere::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid var(--primary);
            opacity: 0.2;
            animation: pulse 3s ease-in-out infinite;
        }

        .sphere::after {
            content: '';
            position: absolute;
            width: 120%;
            height: 120%;
            top: -10%;
            left: -10%;
            border-radius: 50%;
            border: 1px solid var(--accent);
            opacity: 0.1;
            animation: pulse 3s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.2; }
            50% { transform: scale(1.05); opacity: 0.3; }
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
        }

        .icon-blue { background: linear-gradient(135deg, rgba(26, 78, 255, 0.1), rgba(26, 78, 255, 0.05)); color: var(--primary); }
        .icon-cyan { background: linear-gradient(135deg, rgba(0, 224, 198, 0.1), rgba(0, 224, 198, 0.05)); color: var(--accent); }
        .icon-green { background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); color: #10B981; }
        .icon-orange { background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(249, 115, 22, 0.05)); color: #F97316; }
        .icon-purple { background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(168, 85, 247, 0.05)); color: #A855F7; }
        .icon-gray { background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(107, 114, 128, 0.05)); color: var(--secondary); }
    </style>
</head>
<body class="antialiased">
    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center px-6 py-12 relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-20 right-20 sphere hidden lg:block"></div>

        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center relative z-10">
            <!-- Left Content -->
            <div>
                <div class="inline-block mb-6 px-4 py-2 bg-blue-50 rounded-full">
                    <span class="text-sm font-semibold gradient-text">v{{ config('app-version.version') }}</span>
                </div>

                <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                    <span class="gradient-text">OptimaSphere</span>
                    <span class="block text-4xl lg:text-5xl text-gray-700 font-light mt-2">ERP</span>
                </h1>

                <p class="text-xl text-gray-600 mb-4 leading-relaxed">
                    Complete clarity. Total control.
                </p>

                <p class="text-lg text-gray-500 mb-8 leading-relaxed">
                    Your operations, in perfect harmony. From insight to action — in one sphere.
                </p>

                <div class="flex flex-wrap gap-4">
                    @guest
                        <a href="{{ url('/admin') }}" class="btn-primary">
                            Get Started
                        </a>
                        <a href="{{ url('/admin/register') }}" class="btn-secondary">
                            Sign Up Free
                        </a>
                    @else
                        <a href="{{ url('/admin') }}" class="btn-primary">
                            Go to Dashboard
                        </a>
                    @endguest
                </div>

                <div class="mt-12 flex items-center gap-8 text-sm text-gray-500">
                    <div>
                        <div class="text-2xl font-bold gradient-text">360°</div>
                        <div>Business View</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold gradient-text">Real-time</div>
                        <div>Insights</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold gradient-text">All-in-One</div>
                        <div>Platform</div>
                    </div>
                </div>
            </div>

            <!-- Right Content - Abstract Sphere -->
            <div class="hidden lg:flex items-center justify-center">
                <div class="relative">
                    <div class="sphere"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-6 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Everything you need to <span class="gradient-text">optimize</span>
                </h2>
                <p class="text-xl text-gray-600">
                    Modular, scalable, and built for growth
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Finance Module -->
                <div class="card p-8">
                    <div class="feature-icon icon-blue">💰</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Finance</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Complete financial control with real-time reporting, automated workflows, and powerful analytics.
                    </p>
                </div>

                <!-- HR Module -->
                <div class="card p-8">
                    <div class="feature-icon icon-green">👥</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Human Resources</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Streamline your workforce management from recruitment to retirement.
                    </p>
                </div>

                <!-- Sales Module -->
                <div class="card p-8">
                    <div class="feature-icon icon-orange">📈</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Sales & CRM</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Track opportunities, manage customers, and close deals faster.
                    </p>
                </div>

                <!-- Inventory Module -->
                <div class="card p-8">
                    <div class="feature-icon icon-purple">📦</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Inventory</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Real-time stock tracking, automated reordering, and warehouse optimization.
                    </p>
                </div>

                <!-- Manufacturing Module -->
                <div class="card p-8">
                    <div class="feature-icon icon-gray">⚙️</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Manufacturing</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Production planning, quality control, and shop floor management.
                    </p>
                </div>

                <!-- Project Management -->
                <div class="card p-8">
                    <div class="feature-icon icon-cyan">🎯</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Project Management</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Plan, execute, and deliver projects on time and on budget.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="card p-12" style="background: linear-gradient(135deg, #1A4EFF, #00E0C6);">
                <h2 class="text-4xl font-bold mb-6" style="color: #ffffff;">
                    Ready to optimize everything?
                </h2>
                <p class="text-xl mb-8" style="color: rgba(255, 255, 255, 0.9);">
                    Join businesses worldwide who've achieved complete clarity
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    @guest
                        <a href="{{ url('/admin/register') }}" style="background: #ffffff; color: #1A4EFF; padding: 16px 32px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s ease;">
                            Start Free Trial
                        </a>
                        <a href="{{ url('/admin') }}" style="background: transparent; border: 2px solid #ffffff; color: #ffffff; padding: 14px 32px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s ease;">
                            Sign In
                        </a>
                    @else
                        <a href="{{ url('/admin') }}" style="background: #ffffff; color: #1A4EFF; padding: 16px 32px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s ease;">
                            Go to Command Center
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 bg-white border-t">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-center md:text-left">
                    <div class="text-2xl font-bold gradient-text mb-2">OptimaSphere ERP</div>
                    <div class="text-sm text-gray-500">Complete clarity. Total control.</div>
                </div>

                <div class="flex items-center gap-6 text-sm text-gray-500">
                    <span>{{ now()->year }} &copy;</span>
                    <a href="https://webtech-solutions.hu" target="_blank" rel="noopener noreferrer" class="hover:text-blue-600 transition-colors font-medium">
                        Webtech-Solutions
                    </a>
                    <span>|</span>
                    <span>v{{ config('app-version.version') }}</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
