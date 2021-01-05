# UNE Drupal 8 Docker Compose Configuration

This is the Docker configuration for UNE.edu Drupal 8/9.

The codebase can be pulled separately here, but it primarily
hosted on Gitlab with Redfin Solutions.

https://github.com/guylyons/une-drupal8


# Custom Files

- settings.local.php
- drush aliases

# Notes

- Composer has to be version 1 to work with Drupal 8. I think in 9 it will move to Composer 2.
- The default database config is set to connect to 'une,' and NOT 'unedev.'
