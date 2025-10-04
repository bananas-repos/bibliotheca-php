# Unpack

Unpack the archive in a temp folder. Follow the following steps.
After that you can delete the unpacked files

# DB Setup

Create a DB and choose a prefix (A _ is added automatically as separation) for your tables.
Write down those values. You need them later.

# Move files

Move the content of webclient folder to your webspace.

# File rights

Make sure that `systemout` folder is read/write accessible for your webserver user. Recursive.
Make sure that `storage` folder is read/write accessible for your webserver user. Recursive.
Make sure that `config` folder is read/write accessible for your webserver user.
Make sure that `setup` folder is read/write accessible for your webserver user. Recursive.

# Setup

Open your browser and visit your newly created bibliotheca installation setup with `/setup`
Follow the instructions and remember your settings from step DB setup.
After completion the setup will delete itself. Remove the `/setup` from the url and you are done.

# Access

Open your browser and visit your newly created bibliotheca installation.
Default user `admin` and password: `test`

# First steps

Login with default admin account and change the password! Create your own user. Create your first collection.
See more in first-steps.md

# To re-run the setup

Upload the setup folder again. It deletes itself after a successful setup.
