Instructions for using Travis CI
================================

Using [Travis CI](https://travis-ci.com) is really easy. Travis is free for open source projects and semi cheap for closed source client projects too.

Travis CI is really useful because you can run any shell commands for testing the site before it is deployed and you can also run any shell commands to publis the site.

How do install Travis CI cli-tools and authenticate to servers
--------------------------------------------------------------
Well first you need to have a Travis account which is linked to the Github organization.

Then you need to install travis-cli tool:
```
$ gem install travis --no-rdoc --no-ri
```

And login to Travis CI Pro with your credentials
```bash
$ travis login --pro
```

How do I enable travis for my project?
--------------------------------------

You can enable travis by running
```bash
$ travis enable
```

You need to allow Travis CI to authenticate into the Servers. Every project in travis has unique ssh-key which you can use for authentication.
```
$ travis pubkey > travis.pub
$ ssh-copy-id -i travis.pub www-admin@83.136.254.252
```

**Note:** Also check that Travis container IP-addresses are allowed to connect from the firewall! These server IP-addresses can be found from their documentation: https://docs.travis-ci.com/user/ip-addresses/ 

For closed source projects and client projects remember to allow `travis-ci.com` addresses.


How do I do deployments with Travis?
------------------------------------

We have included example best practise configuration `.travis.yml` into this repo.

You need to remove the project template `.travis.yml` from the root folder first:

```bash
$ rm .travis.yml
$ git add .travis.yml
$ git commit .travis.yml -m "Removed WunderTools .travis.yml"
```

And then you need to tell git to move the `.travis.yml` to the project root.
```bash
$ git mv drupal/.travis.yml .
```

To do automatic deployments with travis you need to update `.travis-extra.yml` file to your project root and fill in your server details. For example if your staging server is `100.100.100.100` and production server is `200.200.200.200` you would add them into `.travis-extra.yml` like this:

```yml
env:
  branch:
    master:
      DEPLOY_HOST: 100.100.100.100
      DEPLOY_BASE_PATH: /var/www/insert-folder-of-stage-sitename-here
      DEPLOY_SITE_ENV: stage
  branch:
    production:
      DEPLOY_HOST: 200.200.200.200
      DEPLOY_BASE_PATH: /var/www/insert-folder-of-stage-sitename-here
      DEPLOY_SITE_ENV: production
```