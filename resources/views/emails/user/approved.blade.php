@extends('emails.layout')

@section('content')
    <h1>Your Account Has Been Approved!</h1>

    <p>Hello {{ $user->name }},</p>

    <p>Great news! Your account has been approved by our administrators and you now have full access to {{ \App\Models\Setting::get('company.name', config('app.name')) }}.</p>

    <div class="info-box">
        <strong>What's Next?</strong><br>
        You can now log in and start using all the features available to you. Explore your dashboard, manage your profile, and take advantage of all the tools we provide.
    </div>

    <a href="{{ url('/admin') }}" class="button">Login to Dashboard</a>

    <p>If you experience any issues or have questions, our support team is here to help.</p>

    <p>Welcome aboard!<br>
    The {{ \App\Models\Setting::get('company.name', config('app.name')) }} Team</p>
@endsection
