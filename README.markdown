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

Router
======
The Router class (static!) takes care of all the routing. It has a few useful methods:

`rewrite($from, $to)`
---------------------
Adds an alias from `$from` to `$to`. `$from` is a PCRE, and `$to` can use groups from `$from`.

`register_filter($lambda($request))`
------------------------------------
Adds a pre-routing filter. This is essentially a more general-purpose rewrite, where you can base your
controller structure on any arbitrary element of the request which you'd like.

`start($path)`
--------------
Starts the router. `$path` should be the path to the directory containing your controllers. Here's how the
router works, in more detail:

 1. Apply all filters (see above)
 2. Apply all rewrites (see above)
 3. Check if a matching controller exists in the following order:
    1. `$path/:path/:file.php` (where `:path` and `:file` are from the request object)
    2. `$path/:path/:file/index.php`
 4. If a matching controller was found, call it and we're done! Otherwise, the page wasn't found:
    1. Send a 404 error.
    2. Traverse up the directories, until both: (a) a directory is found, and (b) it has an _error.php ErrorController.
    3. If none was found, throw an exception.