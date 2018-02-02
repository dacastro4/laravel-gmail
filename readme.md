# Installing
the file `.env.example` should always have the latest version of the .env with all the needed keys to make the project run.

# After cloning

* Create a new repo at [BitBucket](https://bitbucket.org/repo/create)
* Clone the repo from [Standard Laravel App](https://bitbucket.org/dani_castro/standard_laravel_app) to your local machine.
* git remote rename origin upstream
* git remote add origin URL_TO_GITHUB_REPO (Ex: git remote add origin ssh://git@bitbucket.org/metromediaworks/pidn.git)
* git push origin master

# Requirement

This app uses:

* sockets with laravel echo server and laravel echo
* redis for cache, queue and broadcast