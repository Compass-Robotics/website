# Compass Robotics

Compass Robotics is an FRC team from Sarasota FL.

## Getting started

Use [DDEV](https://ddev.com) to run Drupal CMS locally, follow these instructions:

1. Install DDEV following the [documentation](https://ddev.com/get-started/)
2. Open the command line and `cd` to the root directory of this project
3. Run `ddev launch`



## Documentation


## Deploy updates

Due to oddities with our hosting the following deploy steps must be used:
1. ssh to the server and cd to the location.
2. git pull
3. /usr/bin/php8.4-cli /homepages/31/d176840445/htdocs/composer/composer.phar install
4. drush deploy

## Contributing & Support

Pull requests are required if you wish to contribute to this codebase.

## License

Drupal CMS and all derivative works are licensed under the [GNU General Public License, version 2 or later](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

Learn about the [Drupal trademark and logo policy here](https://www.drupal.com/trademark).
