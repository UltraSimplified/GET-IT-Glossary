<p style="font-size: 16px;">Hi {{ $user->first_name }},</p>

<p style="font-size: 16px;">
    You can now edit the term
    <em><a href="{{ url($term->path()) }}" style="color: #16306b;">&ldquo;{{ $term->term }}&rdquo;</a></em>
    in the GET-IT Glossary.
</p>

<p style="font-size: 16px;">
    <a href="{{ url('/terms') }}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; border-radius: 3px; background-color: #16306b; border-top: 12px solid #16306b; border-bottom: 12px solid #16306b; border-right: 18px solid #16306b; border-left: 18px solid #16306b; display: inline-block;">View my terms on GET-IT &rarr;</a>
</p>
<p><br></p>
<hr>
<small>This email was sent from the GET-IT Glossary ({{ config('glossary.instance') }}) at {{ url('/') }}</small>
