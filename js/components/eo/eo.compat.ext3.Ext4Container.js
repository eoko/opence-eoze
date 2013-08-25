/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext.define('eo.ext4.compat.Ext4Container', {
	
	extend: 'Ext.Container'
	,alias: ['widget.compat.container']
	
	,afterRender: function() {
		
		this.on('resize', function() {
			var c = this.child;
			if (c) {
				c.setSize(this.getSize());
			}
		}, this);
		
		if (this.child) {
			this.child = this.createComponent();
			this.child.render(this.el);
		}
		
		this.callParent(arguments);
	}
	
	,setChild: function(component) {
		this.child = component;
		if (this.rendered) {
			component.setSize(this.getSize());
			component.render(this.el);
		}
	}
	
	,createComponent: function() {
		var child = this.child;
		if (child) {
			if (child.render) {
				return child;
			} else {
				if (child.xclass) {
					return Ext4.create(child);
				} else {
					return Ext.widget(child);
				}
			}
		}
	}
	
	/**
	 * @private
	 */
	,get: function(prop) {
		var c = this.child,
			m = prop.substr(0,1).toUpperCase() + prop.substr(1),
			args = Array.prototype.call(arguments, 1);
		return c && c[m].apply(m, args);
	}

	,getTitle: function() {
		return this.get('title');
	}
	
});
