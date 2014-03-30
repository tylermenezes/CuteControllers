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
