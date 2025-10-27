<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #1A4EFF 0%, #00E0C6 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .company-logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h1 {
            color: #1A4EFF;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .email-body p {
            margin-bottom: 15px;
            color: #555;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #1A4EFF;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #1540cc;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #1A4EFF;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .email-footer {
            background-color: #2B2F36;
            color: #ffffff;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }
        .footer-content {
            margin-bottom: 15px;
        }
        .footer-links {
            margin: 15px 0;
        }
        .footer-links a {
            color: #00E0C6;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #ffffff;
            text-decoration: none;
        }
        .copyright {
            margin-top: 15px;
            font-size: 12px;
            color: #999;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 25px 20px;
            }
            .email-header, .email-footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            @if(\App\Models\Setting::get('company.logo'))
                <img src="{{ asset(\App\Models\Setting::get('company.logo')) }}" alt="{{ \App\Models\Setting::get('company.name') }}" class="company-logo">
            @endif
            <h1 class="company-name">{{ \App\Models\Setting::get('company.name', config('app.name')) }}</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-content">
                <strong>{{ \App\Models\Setting::get('company.name', config('app.name')) }}</strong>
            </div>

            @if(\App\Models\Setting::get('company.address'))
            <div class="footer-content">
                {{ \App\Models\Setting::get('company.address') }}<br>
                {{ \App\Models\Setting::get('company.city') }}, {{ \App\Models\Setting::get('company.state') }} {{ \App\Models\Setting::get('company.postal_code') }}<br>
                {{ \App\Models\Setting::get('company.country') }}
            </div>
            @endif

            <div class="footer-content">
                @if(\App\Models\Setting::get('company.phone'))
                    Phone: {{ \App\Models\Setting::get('company.phone') }}<br>
                @endif
                @if(\App\Models\Setting::get('company.email'))
                    Email: <a href="mailto:{{ \App\Models\Setting::get('company.email') }}" style="color: #00E0C6;">{{ \App\Models\Setting::get('company.email') }}</a><br>
                @endif
                @if(\App\Models\Setting::get('company.website'))
                    Website: <a href="{{ \App\Models\Setting::get('company.website') }}" style="color: #00E0C6;">{{ \App\Models\Setting::get('company.website') }}</a>
                @endif
            </div>

            <div class="footer-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/admin') }}">Dashboard</a>
                <a href="{{ url('/docs') }}">Help Center</a>
            </div>

            <div class="copyright">
                &copy; {{ date('Y') }} {{ \App\Models\Setting::get('company.name', config('app.name')) }}. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
