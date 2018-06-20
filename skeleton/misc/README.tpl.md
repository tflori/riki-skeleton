# <?= $projectName ?> 

Describe your project with a few words here.

## Installation

<?php if ($useDocker) : ?>
Copy the docker-compose.example.yml to docker-compose.yml and adjust it to your needs.

```console
$ docker-compose pull
$ docker-compose run composer install
$ docker-compose build
$ composer start
```
<?php else : ?>
```console
$ composer install
$ composer serve
```
<?php endif; ?>
