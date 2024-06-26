# Fields

Bibliotheca provides a set of fields which can be used to define which data an entry in your collection
can be saved.
Those field definitions are stored in the DB itself and can currently only managed there. But only of
you know how.

Each collection can have there own set of fields. But at least the title field should be available.
Fields can be managed after a collection has been created.

# Build your oown

If you want to create new ones here is an explanation how they work
A field is defined in the `sys_fields` table. It needs a `_saveField` and optinal `_loadFieldValue` method
in `manageentry.class` and the `_loadFieldValue` also in `mancubus.class`
HTML definitions are needed in `view/UI_THEME/entry` and `view/UI_THEME/manageentry`
Modification on `advancedsearch.php` if search needs some special treatment for this field.

# Fields in sys_fields table

Have a look into the table. Special ones are described here.

`identifier` Unique string within the how sys_fields table.

`displayname` Text which will be displayed for this field.

`type` Specifies the type which then lets the code "know" what to do with this field.
Needs a html definition in `view/UI_THEME/entry` and `view/UI_THEME/manageentry`.
A `_loadFieldValue_TYPE` method in `manageentry.class` and `mancubus.class` if it needs special data process reading
A `_saveField_TYPE` method in `manageentry.class` for data saving.
Modification in `advancedsearch.php` if search needs some special treatment for this field

`searchtype` Every field with `entry*` is a simple search field and can be used in global search.

+ tag = releation to lookup2entry table 
+ entryText = text col in entry table 
+ entrySingleText = entry col in entry table. Single value 
+ entrySingleNum = entry col in entry table. Number. Single value. Can be searched by with ><

`createstring` The SQL create string which is run as you add it to your collection. Not everyone needs one!

`inputValidation` Defines the additional input validation. Currently only `allowSpace` is available. 
Used for `lookupmultiple` field to allow tags with whitespace in its values.

`value` The value which is displayed as a selection for the user. Needed for a selection type field

`apiinfo` Text description what type of data the api expects if you want to fill this field.


# Fieldmigration

Here is an example to migrate a single text field into a lookupmultiple
```
INSERT INTO bib_collection_entry2lookup_3 (`fk_field`,`fk_entry`,`value`) SELECT '32',`id`,`artist` FROM `bib_collection_entry_3` WHERE `artist` <> '';
```
The 32 is the ID from the target field.
