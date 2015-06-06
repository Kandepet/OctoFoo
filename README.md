OctoFoo is a light and easy static website generator written in PHP. THis is a heavily hacked fork of PHPoole by Arnaud Ligny (https://github.com/Narno/PHPoole).
It takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) format), merges it with layouts ([Twig](http://twig.sensiolabs.org/) templates) and generates static HTML files that can be used for any website or blog. It also has the ability to deploy the static HTML directly to github pages.

**Q/A:**

* Why the name _OctoFoo_? It is based on the popular static blog generator OctoPress. The Foo signifies that this can be used to publish any static site, not just a blog.
* Is OctoFoo is stable? It is still in development, be careful!
* Is there a demo? Yes there is, see [http://hackerlabs.org/](http://hackerlabs.org/). The source for that is located at [http://github.com/HackerLabs/HackerLabs.github.io](http://github.com/HackerLabs/HackerLabs.github.io)
* How to get support? Through [GitHub issues](https://github.com/HackerLabs/OctoFoo/issues) system
* Can I contribute? Yes you could submit a [Pull Request](https://help.github.com/articles/using-pull-requests) on [GitHub](https://github.com/HackerLabs/OctoFoo)

Requirements
------------

### Use

* [PHP](https://github.com/php) 5.4+
* [Git](http://git-scm.com) (to deploy on GitHub Pages)

### Install plugins

* [Composer](http://getcomposer.org) (to install and update)

### Development

* [Composer](http://getcomposer.org) (to install / update dependencies)
 * [ZF2 components](https://github.com/zendframework)
 * [PHP Markdown](https://github.com/michelf/php-markdown)
 * [Twig](https://github.com/fabpot/Twig)


----

Usage
-----

### Get OctoFoo

    $ curl -SO http://hithub.com/HackerLabs/OctoFoo/master.zip

### Initialize

Once OctoFoo is downloaded, run the following command to build all files you need (in the curent or target folder).

    $ php octofoo.php [folder] --init

Alias: ```$ php octofoo.php [folder] -i```

Note: You can force initialization of an already initialized folder.

    $ php octofoo.php [folder] --init=force

After ```--init```, here's how the folder looks like:

    [folder]
    +-- _octofoo
        +-- assets
        |   +-- css
        |   +-- img
        |   +-- js
        +-- config.ini
        +-- content
        |   +-- *.md
        +-- layouts
        |   +-- *.html
        +-- router.php

#### _config.ini_

Website configuration file:

##### Site
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```name```        | The name of your website                       |
| ```baseline```    | The baseline of your website                   |
| ```description``` | The description of your website                |
| ```base_url```    | The URL of your website                        |
| ```language```    | The Language of your website (Use IETF format) |

##### Author
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```name```        | Your name                                      |
| ```email```       | Your e-mail address                            |
| ```home```        | The URL of your own website                    |

##### Deploy
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```repository```  | The URL of the GitHub repository               |
| ```branch```      | The target branch name                         |

#### _layouts_

Layouts folder: OctoFoo use [Twig](http://twig.sensiolabs.org) layouts (```index.html``` by default) to generate static HTML files.

#### _assets_

Assets folder: CSS, Javascript, images, fonts, etc.

#### _content_

Content folder: Where you can put your content (in [Markdown](http://daringfireball.net/projects/markdown/) format).

### Generate

Run the following command to generate your static website.

    $ php octofoo.php [folder] --generate

Alias: ```$ php octofoo.php [folder] -g```

After ```--generate```, here's how the folder looks like:

    [folder]
    +-- _octofoo
    |   +-- [...]
    +-- _octofoo_static_site
    |   +-- assets
    |   |   +-- css
    |   |   +-- img
    |   |   +-- js
    +-- robots.txt
    +-- sitemap-index.xml
    +-- sitemap.xml
    +-- *.html


### Serve

Run the following command to launch the built-in server to test your website before deployment.

    $ php octofoo.php [folder] --serve

Alias: ```$ php octofoo.php [folder] -s```

Then browse [http://localhost:8000](http://localhost:8000) or [http://<YOUR HOST/IP>:8000](http://<YOUR HOST/IP>:8000).

You can chain options. For example, if you want to generate then serve:
```$ php octofoo.php [folder] -gs```


### Deploy

Run the following command to deploy your website.

    $ php octofoo.php [folder] --deploy

Alias: ```$ php octofoo.php [folder] -d```

After ```--deploy```, a "cached copy" of ```[folder]``` is created at the same level: ```[.folder]```.

You can chain options. For example, if you want to generate then deploy:
```$ php octofoo.php [folder] -gd```

Note: This feature requires [Git](http://git-scm.com) and a [GitHub](https://github.com) account.
