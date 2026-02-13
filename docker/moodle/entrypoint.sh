#!/bin/bash
set -e

# Create /moodle symlink directory structure for subpath access
# Create /moodle symlink in public directory
if [ -d "/var/www/html/public" ]; then
    cd /var/www/html/public
    if [ ! -e "moodle" ]; then
        ln -s . moodle
        chown -h www-data:www-data moodle
    fi
fi

# Execute the original command (Apache)
exec apache2-foreground
