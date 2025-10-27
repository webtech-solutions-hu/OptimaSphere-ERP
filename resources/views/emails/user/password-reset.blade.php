@extends('emails.layout')

@section('content')
    <h1>Password Reset Request</h1>

    <p>Hello {{ $user->name }},</p>

    <p>We received a request to reset your password for your {{ \App\Models\Setting::get('company.name', config('app.name')) }} account.</p>

    <p>Click the button below to create a new password:</p>

    <a href="{{ $resetUrl }}" class="button">Reset Password</a>

    <div class="info-box">
        <strong>Security Notice:</strong><br>
        This password reset link will expire in {{ config('auth.passwords.users.expire') }} minutes for security reasons.
        If you did not request a password reset, please ignore this email or contact support if you're concerned about your account security.
    </div>

    <p>If the button doesn't work, you can copy and paste the following URL into your browser:</p>
    <p style="word-break: break-all; color: #666;">{{ $resetUrl }}</p>

    <p>Best regards,<br>
    The {{ \App\Models\Setting::get('company.name', config('app.name')) }} Team</p>
@endsection
