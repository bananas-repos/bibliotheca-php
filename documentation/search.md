# Basic search

It works on the defined default search field for each collection.
The field will be searched with the mysql full text search.

# Adwanced search

It combines the mysql full text search with some added functionality as described in the  advanced search view.

# Search limitations

The search is a mysql full text search.
This has some limitations. Noticable will be the stopwords. Like searching for "what" will return nothing.
https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html
https://dev.mysql.com/doc/refman/8.0/en/fulltext-stopwords.html
https://dev.mysql.com/doc/refman/8.0/en/fulltext-restrictions.html
