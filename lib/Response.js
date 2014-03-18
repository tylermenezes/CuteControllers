function Response(statusCode) {
	this._statusCode = statusCode;
	this._headers = {};
	this._body = null;
}

Response.prototype.body = function(body) {
	this._body = body;
	return this;
}

Response.prototype.headers = function(headers) {
	for (var key in headers) {
		if (!headers.hasOwnProperty(key)) {
			continue;
		}

		this._headers[key] = headers[key];
	}
	return this;
}

module.exports = Response;
