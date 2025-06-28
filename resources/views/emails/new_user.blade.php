<p>Your account has been created.</p>
<p>Email: {{ $email }}</p>
<p>Password: {{ $password }}</p>
@if (!$verify_email)
    <p>Verification Code: {{ $code }}</p>
    <p>Please use this code to verify your email at: <a href="{{ url('/verify-email') }}">Verify Email</a></p>
@endif
