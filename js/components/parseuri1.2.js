// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License
// http://blog.stevenlevithan.com/archives/parseuri

function parseUri (str) {
	var	o   = parseUri.options,
		m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
		uri = {},
		i   = 14;

	while (i--) uri[o.key[i]] = m[i] || "";

	uri[o.q.name] = {};
	uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
		if ($1) uri[o.q.name][$1] = $2;
	});

	// addition by Éric Ortéga 11/19/10 3:47 PM
	uri.valueOf = function() {
		
		var ns = "";
		var query = ns;

		if (this.queryKey) {
			var qk = this.queryKey,
				parts = [];
			var has = false;
			for (var key in qk) {
				has = true;
				parts.push(key + "=" + qk[key]);
			}
			if (has) query = "?" + parts.join('&');
		}

		var URIComponent
			= this.directory
			+ this.file
			+ query
			+ (this.anchor ? "#" + this.anchor : ns);

		if (this.encode !== false) URIComponent = encodeURIComponent(URIComponent);

		return (this.protocol ? this.protocol + "://" : ns)
			+ (this.user ? this.user + "@" + this.password : ns)
			+ this.host
			+ (this.port ? ":" + this.port : ns)
			+ URIComponent
			;
	}

	if (!uri.queryKey) uri.queryKey = {};
	// end addition

	return uri;
};

parseUri.options = {
	strictMode: false,
	key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
	q:   {
		name:   "queryKey",
		parser: /(?:^|&)([^&=]*)=?([^&]*)/g
	},
	parser: {
		strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
		loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
	}
};