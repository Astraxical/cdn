#!/bin/bash
# Cron setup script for hourly data branch sync
# This script helps set up the hourly cron job for syncing data to the Git branch

echo "Setting up hourly cron job for data branch sync..."

# Check if we're on a system that supports cron
if ! command -v crontab &> /dev/null; then
    echo "Crontab not available. Please set up the cron job manually:"
    echo "Add this line to your crontab (use 'crontab -e'):"
    echo "0 * * * * cd /path/to/your/cdn && php sync_data_branch.php"
    exit 1
fi

# Get the current directory
CURRENT_DIR=$(pwd)

# Create the cron job entry
CRON_ENTRY="0 * * * * cd $CURRENT_DIR && php sync_data_branch.php # CDN data branch sync"

# Add the cron job
(crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -

echo "Cron job added successfully!"
echo "The sync script will run every hour at minute 0"
echo "Current cron jobs:"
crontab -l