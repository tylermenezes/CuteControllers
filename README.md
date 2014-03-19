# cuter-controllers

A quick and simple way to build routers in JavaScript.

## Introduction

> There's way too many tiny frameworks with help your build controllers in JavaScript. On another hand, theres a whole bunch of frameworks which aslo have fantastic fouter support. Which is awesome.
>
> I created cuter-controllers to be the framework which may be just another Javascript framework just because. I usually use it with [mde/model](https://github.com/mde/model), but there's literally no reason for that to be necessary.

# Requirements

 * Node `>0.11.x`

# Quick Start

`npm install --save cuter-controllers`

Make a controller. Here's a sample using the REST controller trait (functions are JavaScript generators and either `return` or `throw` data. `req` is an [express request object](http://expressjs.com/3x/api.html#req.params)):
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
    get_error: function * (req) {
        throw 'good error!';
    },
    get_response: function * (req) {
        // send a specific status code + custom headers with a Response object
        var res = new require('cuter-controllers').Response(418).headers({
            "Content-Type": "text/html"
        }).body('<b>Hello world!</b>');
        return res;
    }
}
```

Save it to `[/path/to/your/project]/controllers/<controllername>.js`

Start the router! In your index file, run:
```js
require('cuter-controllers').start('./controllers');
```

Then run with the `--harmony` tag:
```sh
node --harmony server.js
```

Visit it on the web! Here's a list of URLs which should work:
* `/test`
* `/test/index`
* `/test/demo`
* `/test/response`

# Controllers

pbbbbbbbbbbbt. i hate documentation.
