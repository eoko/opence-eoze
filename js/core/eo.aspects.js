(function() {

	var NS = Ext.ns('eo.aspects');

	NS.Runnable = function(o, config) {

		Ext.apply(this, config);

		var fireEvent
	};

	NS.Disableable = eo.Object.create({

		constructor: function(o, config) {

			Ext.apply(this, config);

			this.o = o;
			o.aspects = o.aspects || {};
			o.aspects.Disableable = this;

			this.enabled = this.enabled !== false;

			var me = this;

			var fireEvent = this.events ?
				this.doFireEvent.createDelegate(this)
				: function() {};

			Ext.apply(o, {
				setEnabled: function(enabled) {
					if (me.enabled !== enabled) {
						me.enabled = enabled;
						if (enabled) fireEvent('enable');
						else fireEvent('disable');
					}
				}
				,enable: this.enable
				,disable: this.disable
				,isEnabled: this.isEnabled
				,isDisabled: this.isDesabled
			})
		}

		// published
		,setEnabled: function(enabled) {
			var me = this.aspects.Disableable;
			if (me.enabled !== enabled) {
				me.enabled = enabled;
				if (me.events) {
					if (enabled) this.fireEvent('enable', this);
					else this.fireEvent('disable', this);
				}
			}
		}
		,enable: function() {
			this.setEnabled(true);
		}
		,disable: function() {
			this.setEnabled(false);
		}
		,isEnabled: function() {
			return this.aspects.Disableable.enabled !== false;
		}
		,isDisabled: function() {
			return this.aspects.Disableable.enabled === false;
		}


		// aspect internal
		,doFireEvent: function(e, args) {
			var o = this.o;
			if (arguments.length > 2) {
				var evt = e;
				args = Array.prototype.slice.call(args, 1);
				o.fireEvent.apply(o, evt, args);
			} else if (arguments.length == 2) {
				o.fireEvent(e, args);
			} else if (arguments.length == 1) {
				o.fireEvent(e, o);
			} else {
				throw new Error('eo.aspects.Disableable');
			}
		}

	}); // NS.Disableable

})(); // closure