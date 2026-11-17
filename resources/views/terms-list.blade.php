@inject('currentSite', 'App\Support\CurrentSite')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>List of current terms | {{ $currentSite->site()->name }}</title>
</head>
<body>
    <h1>List of current terms in the GET-IT Glossary</h1>
    <table style="border: 1px solid #888;">
        <thead>
            <tr style="background-color: #aaa; font-weight: bold; text-align: center;"><th>Term</th></tr>
        </thead>
        <tbody>
@foreach ($terms as $term)
            <tr style="border: 1px solid #888;"><td><a href="{{ $term->path() }}">{{ $term->term }}</a></td></tr>
@endforeach
        </tbody>
    </table>
</body>
</html>
