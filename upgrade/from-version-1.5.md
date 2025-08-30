# Added a new global search field
As of version 1.6, the field 'Combined Search' provides a much better search base.
How to change and use new field: Run the following sql query. Replace #REPLACEME# with the used DB prefix.
```
INSERT INTO `#REPLACEME#_sys_fields` (`id`, `identifier`, `displayname`, `type`, `searchtype`, `createstring`, `inputValidation`, `value`, `apiinfo`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES (NULL, 'combSearch', 'Combined Search', 'hidden', 'entryText', '`combSearch` text NULL DEFAULT NULL, ADD FULLTEXT (`combSearch`)', '', NULL, 'mysql text - Content will be auto generated from other entry fields', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, '1', '1', 'rw-r--r--');
```
Add the new field 'Combined Search' to a collection.
Change the 'Default gloabal seach field' to 'Combined search' and save.
After that check the 'Update combined search field data' option to create the search data
for the selected collection. Do this for every collection.
For all new entries the data will be created automatically.

# Added new constants to config.php file.
Use config.php.default as a help. The new lines are:
```
const LOGFILE = PATH_SYSTEMOUT.'/bibliotheca.log';
# CURL browser settings
const BROWSER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';
const BROWSER_ACCEPT = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
const BROWSER_ACCEPT_LANG = 'en-US,en;q=0.5';
```

# Updated tools configs
Please compare the default config files for googlebooks, imdbweb and musicbrainz and make the required  changes.

# Added new theme config to config.php file
Use config.php.default as a help. The new setting is:
```
# additional config for each theme with fallback
const UI_THEME_CONFIG = array(
    'default' => array(
        'coverImageMaxWidth' => 260 // in pixel. Supports image/jpeg, image/png, image/webp
    ),
    '98' => array(
        'coverImageMaxWidth' => 500 // in pixel. Supports image/jpeg, image/png, image/webp
    )
);
```
