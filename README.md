# cuter-controllers

A quick and simple way to build routers in JavaScript.

## Introduction

> There's way too many tiny frameworks with help your build controllers in JavaScript. On another hand, theres a whole bunch of frameworks which aslo have fantastic fouter support. Which is awesome.
>
> I created cuter-controllers to be the framework which may be just another Javascript framework just because. I usually use it with [mde/model](https://github.com/mde/model), but there's literally no reason for that to be necessary.

# Requirements

 * Node `>0.11.x`

# Quick Start

1. `npm install --save cuter-controllers`
1. Make a controller. Here's a sample using the REST controller trait:
    ```js
    module.exports = {
        get_index: function * (req) {
            console.log('In real life, putting echos in a controller is probably a bad idea.');
            return;
        },
        get_demo: function * (req) {
            return {
                'my_name': 'tylermenezes'
            };
        },
        get_response: function * (req) {
            var res = new Response(418).headers({
                "Content-Type": "text/html"
            }).body('<b>Hello world!</b>');
            return res;
        }
    }
    ```
1. Save it to `[/path/to/your/project]/controllers/<controllername>.js`
1. Start the router! In your index file, run:
    require('cuter-controllers').start('./controllers');

    * Then run with the `--harmony` tag:

    node --harmony server.js

1. Visit it on the web! Here's a list of URLs which should work:
    * `/test`
    * `/test/index`
    * `/test/demo`
    * `/test/response`

# Controllers

pbbbbbbbbbbbt. i hate documentation.
