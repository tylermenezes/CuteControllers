var http = require('http');

var controllers = require('../../../cuter-controllers');

//give us access to Response for special responses
global.Response = controllers.Response;

//start the controllers

var app = controllers('./controllers', {
	port: 3000,
	baseUrl: ''
});

http.createServer(app).listen(app.get('port'), function() {
	console.log('server listening on port ' + app.get('port'));
});

// or (convenience method);
// controllers.start('./controllers', {
// 	port: 3000,
// 	baseUrl: 'tang'
// });
