@extends('emails.layout')

@section('content')
    <h1>Welcome to {{ \App\Models\Setting::get('company.name', config('app.name')) }}!</h1>

    <p>Hello {{ $user->name }},</p>

    <p>We're excited to have you on board! Your account has been successfully created and you can now access the {{ \App\Models\Setting::get('company.name', config('app.name')) }} platform.</p>

    <div class="info-box">
        <strong>Account Details:</strong><br>
        Email: {{ $user->email }}<br>
        Username: {{ $user->name }}<br>
        Account Type: {{ $user->role?->name ?? 'User' }}
    </p>
    </div>

    <p>To get started, please click the button below to access your dashboard:</p>

    <a href="{{ url('/admin') }}" class="button">Access Dashboard</a>

    <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>

    <p>Best regards,<br>
    The {{ \App\Models\Setting::get('company.name', config('app.name')) }} Team</p>
@endsection
