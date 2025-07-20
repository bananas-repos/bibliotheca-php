# Copy the following directories and files to your installation folder, overriding the existing ones:
```
webclient/config/
webclient/lib/
webclient/view/
webclient/.htaccess
webclient/api.php
webclient/index.php
```

# DB changes. Run each line against your bibliotheca DB.
Replace #REPLACEME# with your table prefix. Default is bib
```
INSERT INTO `#REPLACEME#_sys_fields` (`id`, `identifier`, `displayname`, `type`, `searchtype`, `createstring`, `inputValidation`, `value`, `apiinfo`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES (NULL, 'artists', 'Artists', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), NOW(), NULL, '1', '1', 'rw-r--r--');
INSERT INTO `#REPLACEME#_menu` (`id`, `text`, `action`, `icon`, `owner`, `group`, `rights`, `position`, `category`) VALUES (NULL, 'System Information', 'sysinfo', 'info', '1', '1', 'rw-------', '3', 'show');
UPDATE `#REPLACEME#_sys_fields` SET `value` = 'DOS,Windows 1,Windows 2,Windows 3,Windows 95,Windows 99,Windows XP,Windows 2000,Windows ME,Windows Vista,Windows 7,Windows 8,Windows 10,Windows 11', `apiinfo` = 'One of DOS,Windows 1,Windows 2,Windows 3,Windows 95,Windows 99,Windows XP,Windows 2000,Windows ME,Windows Vista,Windows 7,Windows 8,Windows 10,Windows 11' WHERE `bib_sys_fields`.`id` = 17;
INSERT INTO `#REPLACEME#_sys_fields` (`id`, `identifier`, `displayname`, `type`, `searchtype`, `createstring`, `inputValidation`, `value`, `apiinfo`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES (NULL, 'isbn', 'ISBN', 'number', 'entrySingleNum', '`isbn` int(10) NULL, ADD INDEX (`isbn`)', '', NULL, 'int 10', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, '1', '1', 'rw-r--r--');
```

# New syntax in config files
"define(.." syntax can be replace with "const" syntax in all the config files.
See the default ones about the changes.
"define" syntax will still work for now.
