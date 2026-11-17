@php
    // Relative links and images in term content need the site address in an email.
    $absolute = fn (string $html): string => preg_replace('/\b(href|src)="\/(?!\/)/', '$1="'.rtrim(url('/'), '/').'/', $html) ?? $html;
@endphp
<h2>{{ $heading }}</h2>
<hr>
<h1><a href="{{ url($term->path()) }}">{{ $term->term }}</a></h1>
<h3><em>&ldquo;{!! $absolute($entry['definition_html']) !!}&rdquo;</em></h3>
<hr>
@if ($entry['synonyms_html'] !== '')
    <h4>Synonyms:</h4>
    <p>{!! str_replace(\App\Support\TermLinker::WARNING_MARK, '&#9888; ', $entry['synonyms_html']) !!}</p>
@endif
<h4>Full explanation:</h4>
<p>{!! str_replace(\App\Support\TermLinker::WARNING_MARK, '&#9888; ', $absolute($entry['explanation_html'])) !!}</p>
@if ($entry['cartoon_html'] !== '')
    <h4>Cartoon:</h4>
    {!! $absolute($entry['cartoon_html']) !!}
@endif
@if ($entry['example_html'] !== '')
    <h4>Example:</h4>
    {!! $absolute($entry['example_html']) !!}
@endif
@if ($entry['see_also'] !== [])
    <h4>See also:</h4>
    <p>
        @foreach ($entry['see_also'] as $related)
            @unless ($loop->first)&nbsp;&nbsp;&middot;&nbsp;&nbsp;@endunless<a href="{{ url($related['path']) }}">{{ $related['term'] }}</a>
        @endforeach
    </p>
@endif
@if ($entry['links_html'] !== '')
    <h4>More content:</h4>
    <p>{!! $absolute($entry['links_html']) !!}</p>
@endif
<hr>
<span style="font-family: 'Courier New', monospace;">This term was modified by {{ $updatedBy->name }} at {{ now()->format('H:i \o\n l d F Y') }}.</span>
