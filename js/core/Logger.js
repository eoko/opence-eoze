/**
 * @author Éric Ortéga <eric@mail.com>
 */

Ext.namespace('Oce');

Oce.Logger = function() {

	var Levels = {
		ALL:	[0,'ALL'],
		DEBUG:	[1,'DEBUG'],
		INFO:	[2,'INFO'],
		WARNING:[3,'WARNING'],
		ERROR:	[4,'ERROR']
	}

	var logLevel = Levels.ALL;

	var logImpl = function(level, args) {
		var msg;

		if (args === undefined) {
			msg = '' + undefined;
		} else {
			if (args.length > 1) {
				msg = args[0];
				var i = 1;
				msg = msg.replace(/{}/g, function() {return args[i++]});
			} else if (args == 0) {
				console.log('Invalid log entry (missing msg parameter)');
			} else {
				msg = args[0];
			}
		}

		if (this.console !== undefined) {
			console.log(level[1] + ': ' + msg);
		}
	}

	return {
		debug: function(msg) { if (logLevel[0] <= Levels.DEBUG[0]) logImpl(Levels.DEBUG, arguments); },
		info: function(msg) { if (logLevel[0] <= Levels.INFO[0]) logImpl(Levels.INFO, arguments); },
		warn: function(msg) { if (logLevel[0] <= Levels.WARNING[0]) logImpl(Levels.WARNING, arguments); },
		error: function(msg) { if (logLevel[0] <= Levels.ERROR[0]) logImpl(Levels.ERROR, arguments); }
	}
}()