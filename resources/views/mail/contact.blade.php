<p style="font-size: 16px;">{{ $senderName }} &lt;{{ $senderEmail }}&gt; {{ $intro }}</p>

<h3 style="font-size: 16px; white-space: pre-line;">{{ $body }}</h3>

<p style="font-size: 16px;">
    <a href="{{ $actionUrl }}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; border-radius: 3px; background-color: #16306b; border-top: 12px solid #16306b; border-bottom: 12px solid #16306b; border-right: 18px solid #16306b; border-left: 18px solid #16306b; display: inline-block;">sign in to GET-IT &rarr;</a>
</p>
<p><br></p>
<hr>
<small>This email was sent from the GET-IT Glossary ({{ config('glossary.instance') }}) at {{ url('/') }}</small>
