var http = require('http'),
	path = require('path');

var express = require('express'),
	co = require('co');

function getApp(opt, routes) {
	var app = express();

	app.set('port', opt.port);
	app.use(express.logger('tiny'));
	app.use(express.json());
	app.use(express.urlencoded());
	app.use(express.methodOverride());
	app.use(app.router);

	for (var i = 0; i < routes.length; i++) {
		var route = routes[i];

		//add a special case for /index
		if ('index' == route.action) {
			registerAction(app, route.method, opt.baseUrl, route.controller, '', route.fn);
		}

		registerAction(app, route.method, opt.baseUrl, route.controller, route.action, route.fn);
	}

	return app;
}

function startServer(app, cb) {
	http.createServer(app).listen(app.get('port'), function() {
		console.log('cuter-controllers server listening on port ' + app.get('port'));
		if (typeof cb == 'function') {
			cb();
		}
	});
}

module.exports = getApp;
module.exports.start = startServer;

function registerAction(app, method, baseUrl, controller, action, fn) {
	var routePath = path.join('/', baseUrl, controller, action);
	app[method](routePath, getAction(fn));

	console.log('added route -> ' + method + ': ' + routePath);
}

function getAction(fn) {
	var Response = require('./Response');

	return function(req, res) {
		co(function * () {
			try {
				var body = yield fn(req);
				if (body instanceof Response) {
					res.statusCode = body._statusCode;
					for (var key in body._headers) {
						//TODO: accept lowercase and whatever headers keys
						res.setHeader(key, body._headers[key]);
					}
					res.send(body._body);
				} else {
					res.send(body);
				}
			} catch (e) {
				res.statusCode = 500;
				res.setHeader('Content-Type', 'text/plain');
				res.send(e.stack);
			}
		}).call(this);
	};
}
