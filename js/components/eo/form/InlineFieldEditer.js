/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 13 sept. 2012
 */
(function() {

Ext.ns('eo.form');

/**
 * Inline field editer plugin.
 */
eo.form.TextFieldInlineSubmitter = Ext.extend(Object, {
	
	emptyClass: 'x-form-empty-field'
	
	,constructor: function(config) {
		
		Ext.apply(this, config);
		
		this.submitter = new this.Submitter({
			scope: this
			,submitHandler: this.onSubmit
			,cancelHandler: this.onCancel
		});
	}
	
	,init: function(field) {
		
		this.field = field;
		
		this.mon = field.mon.createDelegate(field);
		this.mun = field.mun.createDelegate(field);
		
		field.on({
			scope: this
			,afterrender: this.afterRender
			,change: this.onValueChange
		});
		
		field.setValue = field.setValue.createSequence(this.onValueChange, this);
		field.applyEmptyText = field.applyEmptyText.createSequence(this.applyEmptyText, this);
	}
	
	// private
	,getTargetEl: function() {
		var f = this.field;
		return f.wrap || f.el;
	}
	
	/**
	 * Called after the {@link Ext.form.Field Field} has been rendered.
	 * @private
	 */
	,afterRender: function() {

		var ownerCt = this.field.ownerCt,
			targetEl = this.getTargetEl();

		// For many field types, we want to let the layout pass finish
		if (ownerCt) {
			// initial hidding of input elements
			ownerCt.on('afterlayout', function() {
				targetEl.setDisplayed(false);
			}, this, {single: true});
			
			// target element must be displayed when the container is doing layout
			// in order for it to be done correctly
			var layout = ownerCt.layout;
			if (layout) {
				// displaying hidden element before layout
				layout.layout = function() {
					if (!targetEl.isDisplayed()) {
						targetEl.setDisplayed(true);
						targetEl.hideAfterLayout = true;
					}
				}.createSequence(layout.layout);
				// and hidding them immediately after
				ownerCt.on('afterlayout', function() {
					if (targetEl.hideAfterLayout) {
						targetEl.hideAfterLayout = false;
						targetEl.setDisplayed(false);
					}
				});
			}
		} else {
			this.getTargetEl().setDisplayed(false);
		}
		
		this.inlineValueCt = this.field.container.createChild({
			tag: 'div'
			,cls: 'x-eo-editable-field x-form-text'
			// both works
//			,title: 'Click to edit'
//			,qtip: 'Click to edit'
		});
		this.inlineValueEl = this.inlineValueCt.createChild({
			tag: 'span'
			,cls: 'shorten'
		});
		this.inlineEditIconEl = this.inlineValueCt.createChild({
			tag: 'div'
			,cls: 'ico edit'
			,style: ''
		});
		
		// Init value
		this.onValueChange();
		
		// Init Events
		
		this.mon(this.inlineValueCt, 'click', this.onInlineEdit, this);

		this.mon(this.field.el, {
			scope: this
			,focus: function(el) {
				this.focused = true;
			}
			,blur: function(el) {
				this.focused = false;
			}
		});
	}
	
	// private
	,onValueChange: function() {
		var v = this.field.getValue(),
			vt = (Ext.isEmpty(v) ? '' : v),
			vel = this.inlineValueEl;
		if (vel) {
			vel.dom.innerHTML = vt;
			vel.removeClass(this.emptyClass);
			this.applyEmptyText();
		}
	}
	
	// private
	,applyEmptyText: function() {
		var f = this.field,
			vel = this.inlineValueEl,
			text = (this.emptyText === true && f.emptyText) || this.emptyText;
		if (vel && text && f.getRawValue().length < 1 && !f.hasFocus){
			vel.dom.innerHTML = text;
			vel.addClass(this.emptyClass);
		}
	}

	// private
	,onInlineEdit: function() {
		
		if (this.editing) {
			return;
		}
		
		this.editing = true;
		
		var targetEl = this.getTargetEl();
		
		this.inlineValueCt.setDisplayed(false);
		targetEl.setDisplayed(true);
//		this.el.on('blur', this.endInlineEdit, this, {
//			buffer: 1000
//		});
		
		this.mon(Ext.getBody(), 'click', this.beforeFocusLost, this, {
			delay: 10
		});
		
		this.previousValue = this.field.getValue();
//		this.originalValue = this.field.getValue();

		this.field.on('specialkey', function(f, e) {
			if (e.getKey() === e.ENTER) {
				this.onSubmit();
			}
		}, this);
		
		this.field.focus();
		
		this.submitter.attachTo(this.field, targetEl);
	}
	// private
	,endInlineEdit: function(force) {
		
		if (force !== true) {
			if (!this.editing) {
				return;
			}
//
//			if (this.focused) {
//				return;
//			}
		}
		
		this.editing = false;
		
		this.mun(Ext.getBody(), 'click', this.beforeFocusLost, this);
		
		this.inlineValueCt.setDisplayed(true);
		this.getTargetEl().setDisplayed(false);
		
		this.submitter.detach();
	}
	
	// private
	,beforeFocusLost: function() {
		if (!this.focused) {
			this.onFocusLost();
		}
	}
	
	,onFocusLost: function() {
		this.onSubmit();
	}
	
	,onCancel: function() {
		this.field.setValue(this.previousValue);
		this.endInlineEdit();
	}
	
	,onSubmit: function() {
		
		var f = this.field,
			ct = f.container,
			v = f.getValue(),
			wasEnabled = !f.disabled;
		
		if (this.submitting) {
			return;
		} else if (!f.isDirty()) {
			this.endInlineEdit(true);
			return;
		} else {
			this.submitting = true;
		}
		
		this.editing = false;
//		this.endInlineEdit();
		this.submitter.detach();

		f.disable();
		
		f.addClass('x-eo-editable-field-submitting');
		
		var ajax = ct.createChild({
			cls: 'ico ajax-loader x-eo-editable-field-ajax-loader'
		});
		
		ajax.setHeight(f.el.getHeight() - 2);
		ajax.anchorTo(this.getTargetEl(), 'tr-tr', [-1, 1]);
		
		this.doSaveRequest(function() {
			
			this.submitting = false;
			
			this.onValueChange();
			this.resetOriginalValue(f);
			ajax.remove();
			f.setEnabled(wasEnabled);
			f.removeClass('x-eo-editable-field-submitting');
			this.endInlineEdit(true);
		}, this);
		
//		debugger
		
//		this.endInlineEdit();
	}
	
	,doSaveRequest: function(cb, scope) {
		scope = scope || this;
		setTimeout(function() {
			cb.call(scope);
		}, 800);
	}
	
	,resetOriginalValue: function(field) {
		field.originalValue = field.getValue();
	}

	,Submitter: Ext.extend(Ext.util.Observable, {
		
		constructor: function(config) {
			Ext.apply(this, config);
			this.callParent(arguments);
		}
		
		,attachTo: function(field, targetEl) {
			
			if (!targetEl) {
				targetEl = field.wrap || field.el;
			}

			this.field = field;
			var ownerCt = field.ownerCt;
			
			this.el = (ownerCt.el || Ext.getBody()).createChild({
				tag: 'div'
				,cls: 'x-eo-editable-field-buttons-ct'
			});
			
			// save button
			var saveButton = this.el.createChild({
				tag: 'button'
				,type: 'submit'
				,cls: 'save'
			});
			saveButton.createChild({
				tag: 'span'
				,cls: 'ico tick'
			});
			if (this.submitHandler) {
				field.mon(saveButton, 'click', this.submitHandler, this.scope || this);
			}
			// cancel button
			var cancelButton = this.el.createChild({
				tag: 'button'
				,type: 'cancel'
				,cls: 'cancel'
			})
			cancelButton.createChild({
				tag: 'span'
				,cls: 'ico cross'
			});
			if (this.cancelHandler) {
				field.mon(cancelButton, 'click', this.cancelHandler, this.scope || this);
			}
			
			// Fix size (to prevent weird resize if parent container is resized)
			this.el.setSize(this.el.getSize());
			
			// anchor
			this.targetEl = targetEl;
			
			this.onAnchor();

			if (ownerCt) {
//				ownerCt.on('resize', this.onAnchor, this);
				ownerCt.on('afterlayout', this.onAnchor, this);
			}
		}
		
		// private
		,onAnchor: function() {
			this.el.anchorTo(this.targetEl, 'tr-br', [0, 0]);
		}
		
		,detach: function() {
			var el = this.el;
			if (el) {
				el.remove();
				delete this.el;
				var ownerCt = this.field.ownerCt;
				if (ownerCt) {
//					ownerCt.un('resize', this.onAnchor, this);
					ownerCt.un('afterlayout', this.onAnchor, this);
				}
			}
		}
	})

});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure