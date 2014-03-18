var fs = require('fs'),
	path = require('path');

var server = require('./server');

var ROOT_DIR = path.resolve('.');

// var HTTP_VERBS = [
// 	'GET',
// 	'HEAD',
// 	'POST',
// 	'PUT',
// 	'DELETE',
// 	'TRACE',
// 	'OPTIONS',
// 	'CONNECT',
// 	'PATCH'
// ];

module.exports = function(dir, opt) {
	var opt = opt || {};
	opt.port = opt.port || 3000 || process.env.PORT;
	opt.splitter = opt.splitter || '_';
	opt.baseUrl = opt.baseUrl || '';

	var CONTROLLERS_DIR = path.join(ROOT_DIR, dir);
	var controllers = fs.readdirSync(CONTROLLERS_DIR);

	var routes = [];

	for (var i = 0; i < controllers.length; i++) {
		var controller = require(path.join(CONTROLLERS_DIR, controllers[i]));

		var controllerName = path.basename(controllers[i], '.js');

		for (var key in controller) {
			if (!controller.hasOwnProperty(key)) {
				continue;
			}

			var method = key.substr(0, key.indexOf(opt.splitter)),
				action = key.substr(key.indexOf(opt.splitter) + opt.splitter.length, key.length),
				fn = controller[key];

			routes.push({
				method: method,
				controller: controllerName,
				action: action,
				fn: fn
			});
		}
	}

	return server(opt, routes);
}

module.exports.start = function(dir, opts, cb) {
	var app = module.exports(dir, opts);
	server.start(app, cb);
}
