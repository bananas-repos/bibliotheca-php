# Config change

Updated `BROWSER_AGENT` string. See default config file for its new value.

# DB changes

Run each line against your bibliotheca DB.

Replace `#REPLACEME#` with your table prefix. Default is bib

```
UPDATE `#REPLACEME#_tool` SET `name` = 'Metacritic Movie', `action` = 'metacriticMovie' WHERE `bib_tool`.`id` = 1;
```

```
INSERT INTO `#REPLACEME#_sys_fields` (`id`, `identifier`, `displayname`, `type`, `searchtype`, `createstring`, `inputValidation`, `value`, `apiinfo`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES (NULL, 'metacritic', 'sysfield.metacritic', 'text', 'entrySingleText', '`metacritic` varchar(4) NULL DEFAULT NULL', '', '', 'string 4', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, '1', '1', 'rw-r--r--');  
```

# Remove not needed folders and files

The following files and direcotries should be deleted.

```
config/config-imdbweb.php
config/config-imdbweb.php.default
lib/imdbweb.class.php
systemout/imdb
view/default/tool-imdbweb.html
view/default/tool-imdbweb.php
view/98/tool-imdbweb.html
view/98/tool-imdbweb.php
```

# Tool changes

Since IMDB scraping is not working anymore, Metracritic does it for now. 
It replaces IMDB and needs only the initial setup. See the tool documentation for that.
