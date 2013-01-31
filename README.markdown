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
 * PHP &ge; 5.4

Quick Start
-----------
 1. Include CuteControllers as a git submodule. You could also download it, but it doesn't feel right.
 2. Make a controller. Here's a sample using the REST controller trait. (There are some others built in, and you can
    even make your own! See below for more details.)

        <?php
        namespace MyApp\Controllers\test;

        class Sample {
            use \CuteControllers\Base\Rest;

            public function __get_index()
            {
                echo "In real life, putting echos in a controller is probably a bad idea.";
            }

            public function __get_demo()
            {
                return ['my_name' => 'tylermenezes']
            }
        }
 4. Save it to `[/path/to/your/project]/Includes/MyApp/Controllers/test/sample.php`
 5. Start the router! In your index file, run:
        \CuteControllers\Route(dirname(__FILE__) . '/Includes/MyApp/Controllers');
 6. Visit it on the web! Here's a list of URLs which should work:
    * `index.php/test/sample/index.html`
    * `index.php/test/sample.html` (Same as above - uses the default method "index" in the controller)
    * `index.php/test/sample/index` and `index.php/test/sample` (The default extension is HMTL)
    * `index.php/test/sample/demo.xml`
    * `index.php/test/sample/demo.json`
 7. Isn't that `index.php` ugly? Let's get rid of it with mod_rewrite! Make sure your web server has mod_rewrite
    enabled, `AllowOverride FileInfo` set, and then put this in your .htaccess file:

        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.php [L]

Controllers
===========
Controllers are classes using the `\CuteControllers\Base\Controller` trait, and which provide a `__route()` method.
Since making your own routers all the time would be annoying, CuteControllers includes some default controller types
which are documented in the *Built-In Controller Types* section.

(Note that, as you can see in the Getting Started example, if you use a built-in controller type, you don't need to use
the `Controller` trait, since the built-in type does that for you.)

Controllers will automatically execute methods named `__before()` and `__after()` before and after routing,
respectively. You can use these methods to apply access control or content-type manipulation.


Built-In Controller Types
==========================
CuteControllers implements several useful controller types by default. You can use one of these controller types by
adding `use \CuteControllers\Base\[Type]` in your controller class.

Web
---
Routes to a method with the same name as the file name. (e.g. `/test/xyz` => `function __xyz()`). If the file name is
blank, it routes to a method named `__index`. Web ignores the file extension.

Rest
----
Similar to the Web controller, but provides several useful shortcuts for restful APIs. Routes to `__:verb_:file` where
`:verb` is the lowercase name of the HTTP verb the request was made with.

If an object is `return`ed from the method, it will outputted in the appropriate format given the file extension. If no
extension is provided, it's assumed to be HTML. The following file extension types are supported:

  * `html` - Echos the result, and serves the content-type `text/html`
  * `json` - JSON-encodes the result, and serves the content-type `application/json`
  * `jsonp` - JSONp implementation. JSON-encodes the result, and wraps it in a function call to the paramater specified
    in the request paramater `callback`. Disabled by default, set `$this->__enable_xdomain` to TRUE to enable it.
  * `jsonh` - Double JSON-encodes the result, and wraps it in a script which passes it with parent.postMessage(). The
     script sends the result to the domain specified in the request paramater `domain`. This is mainly used for cross
     domain POSTing and file uploads in IE8 and 9. Disabled by default, set `$this->__enable_xdomain` to TRUE to enable
     it.
  * `serialized` - PHP-serializes the result and serves it with content-type `application/vnd.php.serialized`
  * `php` - `var_export`s the data, and serves it with content-type `application/vnd.php.serialized`

RestCrud
--------
Simplified version of the Rest controller. `/uri/to/controller/:id` routes to `__create(:id)`, `__read(:id)`,
`__update(:id)`, and `__delete(:id)` methods, depending on the HTTP verb.

If no `:id` is specified, routes to `__list()`, except if both the query paramater `search` is defined, and a method
`__search(:term)` exists.

As with the REST controller above, if the function returns a non-null value, it will output the appropriate response
serialization according to the format. It will also check `$this->__enable_xdomain` before allowing access to the
`jsonp` and `jsonh` response types.

Making Your Own Controllers
===========================

If you really want to make your own controllers, you can do that! Controllers need to provide a `__route()` function,
which specifies what the controller should do. The `__route()` function has two useful bits of data it can use to
accomplish its goal:

  * `$this->routing_data` - Information about the state of the request. `$this->routing_data->path` contains the path of
  the file which was routed to, `$this->routing_data->matched_path` contains the part of the request which matched the
  file, and `$this->routing_data->unmatched_path` contains the part after that.
  * `$this->request` is the request object representing the user's HTTP request. Information about working with that is
  provided in the next section.

Request
=======
The Request object provides useful methods for finding out what the user asked for.

Properties
----------
 * `user_ip` - The user's IP
 * `method` - Uppercase HTTP verb
 * `scheme` - http or https
 * `username` - The username (if there is one)
 * `password` - The password (if there is one)
 * `host` - The hostname the user is visiting (e.g. `localhost`, `foo.com`, etc)
 * `port` - The port the user's connected to, usually 80 or 443
 * `path` - The URI to the resource the user requested
 * `file` - Part of the URI not used to find the controller file, e.g. the method name in a Web controller. (magic -
   based on `path`)
 * `file_name` - Part of the file before the last ., if one exists. (magic - based on `file`)
 * `file_ext` - Part of the file after the last ., if one exists. (magic - based on `file`)
 * `segments` - Array of all path segments. (magic, based on `path`)
 * `query` - Part of the URL after the ?
 * `body` - Anything in the body of the HTTP request (such as during a `PATCH` request)

`get`, `post`, and `param`
----------------------------
The request object also provides useful shortcuts for `$_GET`, and `$_POST`. These are the methods `get(:name)` and
`post(:name)`. They are functionally equivalent to their global variable counterparts.

If you're not sure if something is coming in as a `GET` or `POST` (and don't care), you can use `param(:name)` . This
first checks if the paramater is in `GET`, then `POST`.

All three methods return NULL if the paramater isn't set.


Router
======
The Router class takes care of all the routing. While you can create an instance of the router class, for almost all
uses, you should use the following static methods:

`rewrite($from, $to)`
---------------------
Adds an alias from `$from` to `$to`. `$from` is a PCRE, and `$to` can use groups from `$from`. (Leave off the opening
and closing slashes, and don't worry about escaping forward slashes. CuteControllers does all of this automatically.)

`start($path)`
--------------
Starts the router. `$path` should be the path to the directory containing your controllers.


Handling Errors
===============
All HTTP errors throw an exception of type `\CuteControllers\HttpError`. `getCode()` will return the HTTP error code, and
`getMessage()` will return the associated HTTP error message. If you catch all exceptions of this type from the
`Router::start(0)` method, you can handle them in whatever way works best in your application.
