<x-mail::message>
# Verify your email

Use the verification code below to continue on **{{ $appName }}**.

<x-mail::panel>
<div style="font-size: 32px; font-weight: 700; letter-spacing: 8px; text-align: center;">{{ $code }}</div>
</x-mail::panel>

This code expires in **{{ $expiryMinutes }} minutes**. If you didn't request it, you can safely ignore this email.

Good luck & have fun,<br>
{{ $appName }}
</x-mail::message>
