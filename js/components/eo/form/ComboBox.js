/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 mai 2012
 */
Ext.ns('eo.form');

/**
 * @inheritdoc
 */
eo.form.ComboBox = Ext.extend(Ext.form.ComboBox, {
	
	/**
	 * @cfg {Boolean}
	 * True to expand the combo when its text input is clicked.
	 */
	expandOnFieldCLick: true
	
    ,initEvents: function() {
		eo.form.ComboBox.superclass.initEvents.apply(this, arguments);
		this.initEnterKeyEvents();
		this.initExpandOnFieldClickEvents();
	}
	
	// private
	,initExpandOnFieldClickEvents: function() {
		if (this.expandOnFieldCLick && this.editable) {
			this.el.on('click', function() {
//				this.preventNextSelectOnLoad = true
				this.onTriggerClick();
			}, this);
		}
	}

	// Overridden to prevent selection of the whole text if the combo
	// element was clicked (and not the trigger)
	//
	// private
	,onLoad: function(){
		if(!this.hasFocus){
			return;
		}
		if(this.store.getCount() > 0 || this.listEmptyText){
			this.expand();
			this.restrictHeight();
			// 16/06/12 19:07
			// realLastQuery is a workaround for a bug induced by AbstractJsonCombo
			// which clears lastQuery on its datachanged event.
			if(this.lastQuery === this.allQuery || this.realLastQuery == this.allQuery){
				if(this.editable){
					// we must let pass the possible click on the input element,
					// that will immediately after clear the selection of the whole
					// text
					var me = this;
					setTimeout(function() {
						var dom = me.el.dom;
						if (dom) {
							dom.select();
						}
					}, 50);
				}
				
				if(this.autoSelect !== false && !this.selectByValue(this.value, true)){
					this.select(0, true);
				}
			}else{
				if(this.autoSelect !== false){
					this.selectNext();
				}
				if(this.typeAhead && this.lastKey != Ext.EventObject.BACKSPACE && this.lastKey != Ext.EventObject.DELETE){
					this.taTask.delay(this.typeAheadDelay);
				}
			}
		}else{
			this.collapse();
		}
	}
	
	// private
	,initEnterKeyEvents: function() {
		
		var isClosingKey = false;
		
		this.on('specialkey', function(me, e) {
			if (e.getKey() === e.ENTER) {
				if (isClosingKey) {
					isClosingKey = false;
				} else {
					/**
					* @event enterkey
					* Fires when the enterkey is pressed and is not interpreted as a navigation key
					* (that is, to select an item in the list).
					* @param {eo.form.ComboBox} this
					* @param {Ext.EventObject} e The event object.
					*/
					me.fireEvent('enterkey', me, e);
				}
			}
		});
		
		this.on('expand', function() {
			isClosingKey = false;
		});
		
		this.keyNav.enter = function(e) {
			// If the 
			var wasExpanded = this.isExpanded();
			this.onViewClick(true);
			if (wasExpanded && !this.isExpanded()) {
				isClosingKey = true;
			}
		}
	}

	// for getSelectedId()
	// private
	,assertValue: function() {
        var val = this.getRawValue(),
            rec;

        if(this.valueField && Ext.isDefined(this.value)){
            rec = this.findRecord(this.valueField, this.value);
        }
        if(!rec || rec.get(this.displayField) != val){
            rec = this.findRecord(this.displayField, val);
        }
		
		this.selectedId = rec && rec.id;
		
//		eo.form.ComboBox.superclass.assertValue.apply(this, arguments);

		// The following is the code from Ext.form.ComboBox.assertValue,
		// slightly modified to prevent converting integer input to record
		// ids...

		if (this.valueField && Ext.isDefined(this.value)) {
			rec = this.findRecord(this.valueField, this.value);
		}
		if (!rec || rec.get(this.displayField) != val) {
			rec = this.findRecord(this.displayField, val);
		}
		if (!rec && this.forceSelection) {
			if (val.length > 0 && val != this.emptyText) {
				this.el.dom.value = Ext.value(this.lastSelectionText, '');
				this.applyEmptyText();
			} else {
				this.clearValue();
			}
		} else {
			if (rec) {
				if (this.valueField) {
					// onSelect may have already set the value and by doing so
					// set the display field properly.  Let's not wipe out the
					// valueField here by just sending the displayField.
					if (this.value == val){
						return;
					}
					val = rec.get(this.valueField || this.displayField);
				}
				this.setValue(val);
			} else {
				eo.form.ComboBox.superclass.setValue.call(this, val);
			}
		}
	}
	
	// overridden to update selectedId on setValue
	,setValue: function(v) {
		var r = eo.form.ComboBox.superclass.setValue.apply(this, arguments);
		this.selectedId = v;
		return r;
	}
	
	// for getSelectedId()
	// private
    ,onSelect: function(record, index){
		this.selectedId = record && record.id;
		eo.form.ComboBox.superclass.onSelect.apply(this, arguments);
    }

	/**
	 * Gets the id of the selected record, or null if none is selected.
	 * @return {String/null}
	 */
	,getSelectedId: function() {
		var id = this.selectedId;
		return Ext.isEmpty(id) ? null : id;
	}
});

Ext.reg('combo', eo.form.ComboBox);
Oce.deps.reg('eo.form.ComboBox');