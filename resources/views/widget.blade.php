@inject('currentSite', 'App\Support\CurrentSite')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $currentSite->site()->name }}</title>
    <style>
        /* GET-IT Glossary widget: self-contained so it can sit in an iframe on any site. */
        body {
            background-color: #edf8f3;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        h3 {
            color: #296951;
            margin-left: 5px;
            margin-bottom: 5px;
        }

        select {
            border: 3px solid #eaebea;
            border-radius: 6px;
            padding: 2px;
        }

        #getit_definition {
            border: 3px solid #eaebea;
            border-radius: 6px;
            background: white;
            padding: 10px;
        }

        #getit_definition strong {
            color: rebeccapurple;
        }

        #getit_terms {
            width: 100%;
            margin-bottom: 10px;
            font-size: 18px !important;
        }
    </style>
</head>
<body>
    <div class="content">
        <h3>GET-IT Glossary</h3>
        <select id="getit_terms" aria-label="Select a term">
            <option value="">Select a term</option>
@foreach ($terms as $term)
            <option value="{{ $term['definition'] }}">{{ $term['term'] }}</option>
@endforeach
        </select>
        <div id="getit_definition" aria-live="polite">
            <h2>About GET-IT</h2>
            <p><em>GET-IT provides plain language definitions of health research terms</em></p>
        </div>
    </div>
    <script>
        (function () {
            var select = document.getElementById('getit_terms');
            var panel = document.getElementById('getit_definition');
            var about = panel.innerHTML;

            select.addEventListener('change', function () {
                var option = select.options[select.selectedIndex];

                if (!option.value) {
                    panel.innerHTML = about;
                    return;
                }

                var heading = document.createElement('h2');
                heading.textContent = option.text;

                var definition = document.createElement('em');
                // Definitions are written by glossary editors and contain inline markup.
                definition.innerHTML = '&ldquo;' + option.value + '&rdquo;';

                panel.replaceChildren(heading, definition, document.createElement('br'), document.createElement('br'));
            });
        })();
    </script>
</body>
</html>
