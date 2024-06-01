# Upgrade

Each release has its own file in the upgrade folder.
Basic flow would be to extract the new release and copy over the following files and directories:

```
webclient/config/
webclient/lib/
webclient/view/
webclient/.htaccess
webclient/api.php
webclient/index.php
```

and follow the upgrade file(s). After the first launch, visit `/index.php?p=managesystem` and check if there any
further changes to be done.

In those files there are the steps needed to make an upgrade. If you upgrade multiple versions make sure to read
all the files in the correct order. Within the upgrade files itself there is an order. Make sure to follow.

# Why no automatic updates

Doing so, the process serving the application needs write access to the files. Which can be a security risk.
Providing write access only to storage or temporary files and not the application files itself, it is more
secure (there is no complete security).
The downside are manual updates.
