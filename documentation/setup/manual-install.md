# Unpack

Unpack the archive in a temp folder. Follow the steps. After you can delete the unpacked files.

# DB Setup

Create a DB and choose a prefix (A _ is added automatically as separation) for your tables.
Open the provided sql file. Search for `#REPLACEME#` and replace it with your table prefix.
Save. Import this file into you newly created DB.
It is a mysql dump import file. Works with phpmyadmin too.

# Config

Copy `webclient/config/config.php.default` to `webclient/config/config.php`. Edit and fill in the DB details.

Change `PATH_ABSOLUTE` to you installation path and `PATH_WEB_STORAGE` relative to your webroot.

# Move files

Move the content of webclient folder to your webspace. Make sure the location matches
the `PATH_ABSOLUTE` config in `config.php` file

# File rights

Make sure that `systemout` folder is read/write accessible with your webserver user. Recursive.
Make sure that `storage` folder is read/write accessible with your webserver user. Recursive.

# Access

Open your browser and visit your newly created bibliotheca installation.
Default user `admin` and password: `test`

# First steps

Login with default admin account and change the password! Create your own user. Create your first collection.
See more in first-steps.md
