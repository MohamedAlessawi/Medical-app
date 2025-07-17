<p>مرحباً،</p>

<p>تم إنشاء حسابك كأدمن لمركز <strong>{{ $center_name }}</strong>.</p>

@if(isset($password) && $password !== '[Use your existing password]')
    <p>بيانات الدخول:</p>
    <ul>
        <li>البريد: <strong>{{ $email }}</strong></li>
        <li>كلمة السر: <strong> {{ $password }}</strong></li>
    </ul>
@else
    <p>يمكنك الآن استخدام حسابك الحالي للدخول إلى تطبيق الأدمن الخاص بالمركز.</p>
@endif

<p>تحياتنا،</p>
<p>فريق الدعم</p>
