#!/bin/bash

# Path to the PHP config file
CONFIG_FILE="/data/wwwroot/codebase/pure_config.php"

# Extract database credentials from the PHP file
DB_HOST=$(grep "const DB_HOST" $CONFIG_FILE | cut -d"'" -f2)
DB_NAME=$(grep "const DB_NAME" $CONFIG_FILE | cut -d"'" -f2)
DB_USER=$(grep "const DB_USER" $CONFIG_FILE | cut -d"'" -f2)
DB_PWD=$(grep "const DB_PWD" $CONFIG_FILE | cut -d"'" -f2)

# Backup file name
BACKUP_NAME="backup_$(date +%Y%m%d%H%M%S).sql"

# Perform the MySQL backup
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PWD $DB_NAME > $BACKUP_NAME

# Check if backup was successful
if [ ! -f $BACKUP_NAME ]; then
    echo "Backup failed"
    exit 1
else
    echo "Backup successful: $BACKUP_NAME"
fi

# Code to commit and push and save to github repo.

# Add the backup file to the repository
#git add $BACKUP_NAME

# Commit the backup
#git commit -m "Added database backup $BACKUP_NAME"

# Push the changes to the remote repository
#git push origin master
