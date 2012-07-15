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

        use \CuteControllers;

        class Sample extends CuteControllers\Base\Web {
            public function index()
            {
                echo "Hello! This is a test!";
            }

            public function demo()
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

Router
======
The Router class (static!) takes care of all the routing. It has a few useful methods:

`rewrite($from, $to)`
---------------------
Adds an alias from `$from` to `$to`. `$from` is a PCRE, and `$to` can use groups from `$from`.

`filter($lambda($request))`
----------------------------
Adds a pre-routing filter. This is essentially a more general-purpose rewrite, where you can base your
controller structure on any arbitrary element of the request which you'd like.

`start($path)`
--------------
Starts the router. `$path` should be the path to the directory containing your controllers. Here's how the
router works, in more detail:

 1. Apply all filters (see above)
 2. Apply all rewrites (see above)
 3. Check if a matching controller exists in the following order (where `:path` and `:file` are from the request object):
    1. `$path/:path/:file.php`
    2. `$path/:path.php`
 4. If a matching controller was found, call it and we're done:
    1. If `:file` wasn't in the path (case 2 above), call the `:file` function.
    2. Otherwise, call the `index` function.
 5. Otherwise, the page wasn't found:
    1. Send a 404 error.
    2. TODO: Traverse up the directories, until both: (a) a directory is found, and (b) it has an _error.php ErrorController.
    3. If none was found, throw an exception.