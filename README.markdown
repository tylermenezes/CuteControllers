# CuteControllers

A quick and simple way to build routers in PHP.

## Introduction

> There's a lack of tiny frameworks which help you build controllers in PHP. On the one hand, you have
> frameworks with fantastic router support (e.g. fuel, cake, codeigniter), but which contain lots of
> useless bloat. On the other hand, you have frameworks like Slim, which do routing very simply, but don't
> give you any help, to the point where it's necessary to write out a large chunk of code to act as some
> sort of weird back-front-controller.
>
> I created CuteControllers to be the framework which has exactly the right number of features to be
> useful as a front-controller. I usually use it with TinyDb, but there's literally no reason for that
> to be necessary.

# Requirements

 * PHP &ge; 5.4

# Quick Start

 1. Include CuteControllers.
 2. Make sure your web server has mod_rewrite enabled, `AllowOverride FileInfo` set, and then put this in your .htaccess file:

        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.php [L]
 3. Make a controller. Here's a sample using the REST controller trait. (There are some others built in, and you can
    even make your own! See below for more details.)

        <?php
        namespace MyApp\Controllers\test;

        class Sample {
            use \CuteControllers\Controller;

            public function get_index()
            {
                echo "In real life, putting echos in a controller is probably a bad idea.";
            }

            public function get_demo()
            {
                return ['my_name' => 'tylermenezes']
            }
        }
 4. Save it to `[/path/to/your/project]/Includes/MyApp/Controllers/test/sample.php`
 5. Start the router! In your index file, run:

        \CuteControllers\Router::start(dirname(__FILE__) . '/Includes/MyApp/Controllers');
 6. Visit it on the web! Here's a list of URLs which should work:
    * `index.php/test/sample/index.html`
    * `index.php/test/sample.html` (Same as above - uses the default method "index" in the controller)
    * `index.php/test/sample/index` and `index.php/test/sample` (The default extension is HMTL)
    * `index.php/test/sample/demo.xml`
    * `index.php/test/sample/demo.json`

# Controllers

Controllers are classes using the `\CuteControllers\Controller` trait.

The CuteControllers router will locate the controller file which matches the greatest amount of the URL. For example, if we had this
directory structure:

    |- Controllers
        |- test.php
        |- test
            |- foo.php

A request for `/test/foo/bar` would be routed to the file `Controllers/test/foo.php` file, while a request for `/test/bar` would be routed
to `Controllers/test.php`.

In both of these examples, there's a bit "left over" after the match -- `/bar`. CuteControllers uses this to determine which method in the
selected file to call. In both of these cases, it would attempt to call the `action_bar()` function, provided such a function exists,
takes no arguments, and is public.

Characters which aren't valid in the names of PHP methods will automatically be turned into underscores.

## Extension-Specific Matching

By default, the file extension is ignored in matching, so `foo.html` would be matched to `action_foo()`. If you want to match routes to
the file extension, you can do so by suffixing the method name with the extension, e.g. `action_foo_html()`, `action_foo_json()`, and so
on.

More specific matches always take priority over less specific ones, so `action_foo()` will be called as a fallback if an
extension-specific method doesn't exist.

Extension-specific matching is compatible with the following section, as well.

## Verb-Specific Matching

What if you want a function to only handle certain types of requests. For example, you might want a form to be displayed when the user
makes a GET request, and updated when they make a POST request. In such a case, you can prefix the function with the lower-cased HTTP
verb, e.g.:

    GET /test/xyz` => `function get_xyz()

As with extension-specific matching, these sort of functions have priority over the general-case functions. If we were to make a POST
request, and a `post_xyz()` function did not exist, it would simply be routed to the `action_xyz()` function (provided one exists).

Verb-specific matching is compatible with extension-specific matching, so you can create a `post_xyz_json()` function if you want.

## Default (index) Pages

If the file name is blank, it routes to a method as if the page "index" were requested. index supports extension-specific and
verb-specific matching, so you can get very specific in your matches.

## Arguments

We already saw that CuteControllers uses the "left over" bit from finding the controller file to determine which method to call. What
happens when, instead of `/bar`, we have even more extra, like `/bar/xyzzy`?

In this case, if the routed method takes arguments, the extra bits will be sliced at every `/`, and passed params-wise into the controller.
For example, if you wanted to have a user profile page accessible at `/users/:username`, you could create the function:

    function users($username)
    {
        echo "Welcome to $username's home on the web!";
    }

The method will be called only if the number of extra URL bits exactly corresponds with the number of arguments your function accepts.

## Taking Special Actions

Controllers will automatically execute methods named `before_*()` and `after_*()` before and after routing, respectively. You can use
these methods to apply access control or content-type manipulation.

Because the methods only need to start with `before_` and `after_`, you can have multiple traits which add actions, e.g.:

    trait RequiresLogin
    {
        function before_require_user()
        {
            if (!Models\User::is_logged_in()) {
                throw new \CuteControllers\HttpError(401);
            }
        }
    }

The execution order of `before_*()` and `after_*()` functions is undefined.

## Automatic Serialization

If an object is `return`ed from the method, it will outputted in the appropriate format given the file extension. If no
extension is provided, it's assumed to be HTML if the object is a primitive, or JSON if it's an array or object.

The following file extension types are supported:

  * `html` - Echos the result, and serves the content-type `text/html`
  * `json` - JSON-encodes the result, and serves the content-type `application/json`
  * `jsonp` - JSONp implementation. JSON-encodes the result, and wraps it in a function call to the parameter specified
    in the request parameter `callback`. Disabled by default, set `$this->__enable_xdomain` to TRUE to enable it.
  * `jsonh` - Double JSON-encodes the result, and wraps it in a script which passes it with parent.postMessage(). The
     script sends the result to the domain specified in the request paramater `domain`. This is mainly used for cross
     domain POSTing and file uploads in IE8 and 9. Disabled by default, set `$this->__enable_xdomain` to TRUE to enable
     it.
  * `serialized` - PHP-serializes the result and serves it with content-type `application/vnd.php.serialized`
  * `php` - `var_export`s the data, and serves it with content-type `application/vnd.php.serialized`

## Helper Functions

The Controller trait adds a few useful helper functions to your class:

  * `require_get($name)` - Requires the named parameter be provided in the query-string
  * `require_post($name)` - Requires the named parameter be provided in the postbody
  * `require_param($name)` - Requires the named parameter be provided in either the query-string or the postbody
  * `redirect($url, [$status])` - Redirects the user and immediately stops execution. Relative paths will be translated. You can specify
    the status code in the second parameter if desired.

The `require_*` functions also accept multiple arguments params-wise, so `$this->require_post('username', 'password')` is valid.

## Custom Controllers

It's possible to customize the routing once CuteControllers has matched the controller file. Instead of using the default
`\CuteControllers\Controller` trait, create a custom one which exposes the `cc_route()` method. Keep in mind that, in doing this, none of
the features described in this section will be available.

# Request

The Request object provides useful methods for finding out what the user asked for. It's automatically accessible in Controllers using
`$this->request`.

## Properties

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

## `get`, `post`, and `param`

The request object also provides useful shortcuts for `$_GET`, and `$_POST`. These are the methods `get(:name)` and
`post(:name)`. They are functionally equivalent to their global variable counterparts.

If you're not sure if something is coming in as a `GET` or `POST` (and don't care), you can use `param(:name)` . This
first checks if the paramater is in `GET`, then `POST`.

All three methods return NULL if the paramater isn't set.


# Router

The Router class takes care of all the routing. While you can create an instance of the router class, for almost all
uses, you should use the following static methods:

## `rewrite($from, $to)`

Adds an alias from `$from` to `$to`. `$from` is a PCRE, and `$to` can use groups from `$from`. (Leave off the opening
and closing slashes, and don't worry about escaping forward slashes. CuteControllers does all of this automatically.)

## `start($path)`

Starts the router. `$path` should be the path to the directory containing your controllers.


# Handling Errors

All HTTP errors throw an exception of type `\CuteControllers\HttpError`. `getCode()` will return the HTTP error code, and
`getMessage()` will return the associated HTTP error message. If you catch all exceptions of this type from the
`Router::start(0)` method, you can handle them in whatever way works best in your application.
