# Tools

Each collection can have additional tools. The availability is configured in collection management.

A tool needs an DB entry in _tool and matching files in `view/THEME/tool/`. Best is to copy an entry
and change name, description and action. The other settings can be left as they are, unless you know what they do.
Filenames are `tool-ACTION.html|php`
A optional configuration file `config/config-ACTION.php`
A documentation is a must.
Add needed translation keys into eng.ini as a minimum.

As a base the provided imdbweb parse is already included. It makes it possible to search for a movie within
https://www.imdb.com and let you pick with information should be saved into the entry.
