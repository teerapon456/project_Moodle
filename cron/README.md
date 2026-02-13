# Automated User Synchronization (Portal -> Moodle)

This directory contains the script `sync_users.php` which synchronizes user data from the HR Portal database to the Moodle LMS database.

## How it works
1.  Connects to Portal DB (`myhr_portal`) and Moodle DB (`moodle`).
2.  Fetches active users from Portal.
3.  Updates existing users in Moodle (Email, Name, Department, Institution).
4.  Creates new users in Moodle if they don't exist.
5.  Sets authentication method to `myhrauth` (SSO).
6.  Suspends users who are inactive in Portal.

## Manual Execution
To run the synchronization manually (e.g., for testing):

```bash
docker exec myhr-portal php cron/sync_users.php
```

## Automated Setup (Cron Job)
To run this automatically every night (e.g., at 02:00 AM), add the following line to the **Host Machine's** crontab.

1.  Open crontab:
    ```bash
    crontab -e
    ```

2.  Add this line:
    ```cron
    0 2 * * * docker exec myhr-portal php /var/www/html/cron/sync_users.php >> /var/log/myhr_sync.log 2>&1
    ```

## Logs
The script outputs logs to standard output. By redirecting to `/var/log/myhr_sync.log`, you can check the status of the last run.
