# Config change

Updated BROWSER_AGENT string. See default config file for its new value.

# DB changes. 

Run each line against your bibliotheca DB.

Replace #REPLACEME# with your table prefix. Default is bib

```
UPDATE `#REPLACEME#_sys_fields` SET `createstring` = '`imdbrating` varchar(4) NULL DEFAULT NULL', `apiinfo` = 'string 4' WHERE `#REPLACEME#_sys_fields`.`identifier` = 'imdbrating';
``` 

# Remove not needed folders and files

If in `PATH_SYSTEMOUT` following folders are present, you can delete them.

```
cache\
cast\
posters\
```
