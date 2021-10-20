# Bitbucket CLI
This is a wrapper cli tool on top of https://github.com/BitbucketPHP/Client using https://laravel-zero.com/. This will help with working more efficiently when using bitbucket.

# Configuration
- add .bb.env file in your bb repo as per the .env.example file

## Commands
- create Pr `bb pr:create master --close=true --push=true`
- cherry-pick and raise multiple pr's for different target branches (multigit replacement)
- approve and merge pr's
- approve pr's
- merge pr's

## TODO
- get the reposlug and workspace from git using the current working directory.
- check if this also works when having installed this package globally.
- check if .env is configured before trying anything
- add check for git installed

### Sources
- https://github.com/BitbucketPHP/Client
- https://petstore.swagger.io/#/
- https://api.bitbucket.org/swagger.json
- https://github.com/czproject/git-php
- https://laravel-zero.com/docs/commands

## For testing use
- https://bitbucket.org/Bas_Smeets/testrepo

## Build
php bb app:build bb

