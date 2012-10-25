CuteControllers
===============
A quick and simple way to build routers in PHP.

Introduction
------------
> There's a lack of tiny frameworks which help you build controllers in PHP. On the one hand, you have
> frameworks with fantastic router support (e.g. fuel, cake, codeigniter), but which contain lots of
> useless bloat. On the other hand, you have frameworks like Slim, which do routing very simply, but don't
> give you any help, to the point where it's necessary to write out a large chunk of code to act as some
> sort of weird back-front-controller.
>
> I created CuteControllers to be the framework which has exactly the right number of features to be
> useful as a front-controller. I usually use it with TinyDb, but there's literally no reason for that
> to be necessary.

Requirements
------------
 * PHP &ge; 5.3.5

Quick Start
-----------
 1. Include CuteControllers as a git submodule. You could also download it, but it doesn't feel right.
    1. `git submodule add git://github.com/tylermenezes/CuteControllers.git .submodules/CuteControllers`
    2. `ln -s .submodules/CuteControllers/CuteControllers Includes/CuteControllers`
 2. Run an SPL class loader. Here's an example of a good one: [https://gist.github.com/426304]
    1. `git submodule add git://gist.github.com/426304.git .submodules/SPLClassLoader`
    2. `ln -s .submodules/SPLClassLoader/SPLClassLoader.php Includes/SPLClassLoader.php`
    3. Include it in your index file:
            require_once('Includes/SplClassLoader.php');
            $loader = new SplClassLoader();
            $loader->register();
 3. Make a controller. Here's an example to get you started.
        <?php
        namespace MyApp\Controllers\Test;

        class Sample extends \CuteControllers\Base\Web {
            public function __index()
            {
                echo "Hello! This is a test!";
            }

            public function __demo()
            {
                echo "In real life, putting echos in a controller is probably a bad idea.";
            }
        }
 4. Save it to `[/path/to/your/project]/controllers/test/sample.php`
 5. Start the router! In your index file, run:
        \CuteControllers\Router::start();
 6. Set up your .htaccess file, if you're using Apache and don't want to have to have /index.php/
 7. Visit it on the web! Here's a list of URLs which should work:
    * `/test/sample/index`
    * `/test/sample` (Same as above - uses the default method "index" in the controller)
    * `/test/sample/demo`
    * `/test/sample/demo.html` (Same as above, extensions are ignored when using the Web Controller.
      See the Controller Types section below.)


Controllers
===========
Controllers are classes implementing `Base\Controller` (those which provide a `route()` method). You should never
override `__construct` in a controller, always override `before()`.

The current Request is available from any controller at `$this->request`.

Generally you'll want to use one of the pre-built controller types (see below).


Controller Types
================
CuteControllers implements several useful controller types by default. (You can define your own by extending from
`\Base\Controller` and implementing the `route()` method.)

Web
---
Routes to a method with the same name as the file name. (e.g. `/test/xyz` => `function xyz()`). If the file name is blank,
it routes to a method named `index`. Ignores the file extension. Will only route to public methods.

Rest
----
Similar to the Web controller, but provides several useful shortcuts for restful APIs. Routes to `:verb_:file` where `:verb`
is the lowercase name of the HTTP verb the request was made with. If an object is `return`ed from the method, it will outputted
in the appropriate format given the file extension (e.g. converted to JSON if the URL ends in .json). (If no file extension is
present, it will assume HTML.)

RestCrud
--------
Simplified version of the Rest controller. `/uri/to/controller/:id` routes to `create(:id)`, `read(:id)`, `update(:id)`, and
`delete(:id)` methods, depending on the HTTP verb. If no `:id` is specified, routes to `list()`, except if both the query
paramater `search` is defined, and a method `search(:term)` exists. As with the REST controller above, if the function
returns a non-null value, it will output the appropriate response serialization.

Request
=======
The Request object provides useful methods for finding out what the user asked for.

Properties
----------
 * `ip` - The user's IP
 * `username` - The username (if there is one)
 * `password` - The password (if there is one)
 * `method` - Uppercase HTTP verb
 * `scheme` - http or https
 * `hostname` - The hostname the user is visiting (e.g. `localhost`, `foo.com`, etc)
 * `port` - The port the user's connected to, usually 80 or 443
 * `uri` - App-relative URI
 * `full_uri` - Full URI, relative to the webroot. This includes things such as `/folder/index.php/`.
 * `path` - Part of the URI used to find the controller file (magic - based on `uri`)
 * `file` - Part of the URI not used to find the controller file, e.g. the method name in a Web controller. (magic -
   based on `uri`)
 * `file_name` - Part of the file before the last ., if one exists. (magic - based on `uri`)
 * `file_ext` - Part of the file after the last ., if one exists. (magic - based on `uri`)
 * `segments` - Array of all path segments. (magic, based on `uri`)
 * `query` - Part of the URL after the ?
 * `body` - Anything in the body of the HTTP request (such as during a `PATCH` request)

`get`, `post`, and `request`
----------------------------
The request object also provides useful shortcuts for `$_GET`, `$_POST`, and `$_REQUEST`. These are the methods `get(:name)`,
`post(:name)`, and `request(:name)`. They are functionally equivalent to their global variable counterparts.


Router
======
The Router class (static!) takes care of all the routing. It has a few useful methods:

`rewrite($from, $to)`
---------------------
Adds an alias from `$from` to `$to`. `$from` is a PCRE, and `$to` can use groups from `$from`.

`filter($lambda($request))`
----------------------------
Adds a pre-routing filter. This is essentially a more general-purpose rewrite, where you can base your controller structure
on any arbitrary element of the request which you'd like.

`start($path)`
--------------
Starts the router. `$path` should be the path to the directory containing your controllers.

Handling Errors
===============
All HTTP errors throw an exception of type `\CuteControllers\HttpError`. `getCode()` will return the HTTP error code, and
`getMessage()` will return the associated HTTP error message. If you catch all exceptions of this type from the
`Router::start(0)` method, you can handle them in whatever way works best in your application.
