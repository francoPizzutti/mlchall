## Requirements

- PHP 7.4 or later
- Composer 1
- xdebug


## Installation

git clone 

cd mlchall

cp .env.test.dist .env.test

composer install --no-cache


## Dev server: 

symfony server:start

## Run tests

composer phpunit

- Reports will output at ${PWD}/reports/index.html