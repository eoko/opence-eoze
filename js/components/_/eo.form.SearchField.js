(function() {
	
Ext.ns("eo.form");
	
var sp = Ext.form.TriggerField,
	spp = sp.prototype;
	
eo.form.SearchField = Ext.extend(sp, {
	
	emptyText: "Chercher"
	
	,constructor: function() {
		spp.constructor.apply(this, arguments);
		
		this.addEvents("search", "clearsearch");
		
		this.on({
			scope: this
			,buffer: 350
			,keydown: this.onFilter
		});
	}
	
	// private
	,onFilter: function() {
		this.fireEvent("search", this, this.getValue());
	}
	
	,initComponent: function() {
		
		Ext.applyIf(this, {
			enableKeyEvents: true
			,triggerClass : 'x-form-clear-trigger'
		});
		
		spp.initComponent.call(this);
		
		this.addClass("eo-form-searchfield");
	}
	
	// private
	,onTriggerClick: function() {
		this.setValue();
		this.fireEvent("search", this, null);
		this.fireEvent("clearsearch", this);
	}
	
	// private
	,setEmpty: function(empty) {
		var el = this.el,
			dom = el.dom;
		if (el.hasClass(this.emptyClass)) {
			if (!empty) {
				el.removeClass(this.emptyClass);
				dom.style.paddingLeft = "3px";
				el.setWidth(el.getWidth() + 21);
			}
		} else {
			if (empty) {
	            el.addClass(this.emptyClass);
				dom.style.paddingLeft = "24px";
				el.setWidth(el.getWidth() - 21);
			}
		}
	}
	
    ,applyEmptyText: function(){
        if(this.rendered && this.emptyText && this.getRawValue().length < 1 && !this.hasFocus){
            this.setRawValue(this.emptyText);
            this.setEmpty(true);
		}
    }
	
    ,setValue : function(v){
        if(this.emptyText && this.el && !Ext.isEmpty(v)){
            this.setEmpty(false);
        }
        Ext.form.TextField.superclass.setValue.apply(this, arguments);
        this.applyEmptyText();
        this.autoSize();
        return this;
    }
	
	,preFocus: function(){
        var el = this.el,
            isEmpty;
        if(this.emptyText){
            if(el.dom.value == this.emptyText){
                this.setRawValue('');
                isEmpty = true;
            }
            this.setEmpty(false);
        }
        if(this.selectOnFocus || isEmpty){
            el.dom.select();
        }
    }

});

Ext.reg("eo.search", eo.form.SearchField);

	
})(); // closure