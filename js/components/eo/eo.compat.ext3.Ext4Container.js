/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext.define('eo.ext4.compat.Ext4Container', {
	
	extend: 'Ext.BoxComponent'
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
			this.afterCreateChild(this.child);
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
				if (Ext.isString(child) || child.xclass) {
					return Ext4.create(child);
				} else {
					return Ext.widget(child);
				}
			}
		}
	}

	/**
	 * Hook for external code to customize what properties are proxied from the child
	 * component by the compatibility container.
	 *
	 * For example, AjaxRouter adds the `href` property:
	 *
	 *     eo.ext4.compat.Ext4Container.prototype.afterCreateChild = Ext4.Function.createSequence(
	 *         eo.ext4.compat.Ext4Container.prototype.afterCreateChild,
	 *         function(child) {
	 *             this.href = child.href;
	 *         }
	 *     );
	 *
	 * @param {Ext.Component} child
	 */
	,afterCreateChild: function(child) {}
	
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
