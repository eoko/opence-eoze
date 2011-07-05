/**
 * @author Shea Frederick - http://www.vinylfox.com
 * @class Ext.ux.form.HtmlEditor.Word
 * @extends Ext.util.Observable
 * <p>A plugin that creates a button on the HtmlEditor for pasting text from Word without all the jibberish html.</p>
 */

Ext.ns("Ext.ux.form.HtmlEditor");

Ext.ux.form.HtmlEditor.Word = Ext.extend(Ext.util.Observable, {
    // Word language text
//    langTitle: 'Word Paste',
//    langToolTip: 'Cleanse text pasted from Word or other Rich Text applications',
    langTitle: 'Copie à partir de Word',
    langToolTip: 'Nettoie le code du texte copié à partir de Word ou autre traitement de texte',
    wordPasteEnabled: true,
    // private
	curLength: 0,
	lastLength: 0,
	lastValue: '',
	// private
    init: function(cmp){
        
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
		this.cmp.on('initialize', this.onInit, this, {delay:100, single: true});
        
    },
	// private
	onInit: function(){

		// Opera does not support the paste event at all
		if (Ext.isOpera) {
			Ext.EventManager.on(this.cmp.getDoc(), {
				'keyup': this.checkIfPaste,
				scope: this
			});

			this.lastValue = this.cmp.getValue();
			this.curLength = this.lastValue.length;
			this.lastLength = this.lastValue.length;

		// Gecko does fire the paste event, but doesn't give access to the
		// pasted data
		} else {
//		} else if (Ext.isGecko) {
			var lastValue;
			this.cmp.setValue("");

			var done;
			var changeHandler = {
				fn: function() {
					if (done) return;
					done = true;
					this.doClean(lastValue);
					// clear buffer
					lastValue = null;
				}
				,scope: this
				,single: true
			};

			Ext.EventManager.on(this.cmp.getDoc(), 'paste', function() {
				if (!this.wordPasteEnabled) return;
				lastValue = this.cmp.getValue();
				done = false;

//				Ext.EventManager.on(this.cmp.getDoc(), 'keyup', changeListener, this, { single: true });
				Ext.EventManager.on(this.cmp.getDoc(), {
					keyup: changeHandler
					,mouseup: changeHandler
					,change: changeHandler
				});
				setTimeout(changeHandler.fn.createDelegate(this), 300);
			}, this);

//			Ext.EventManager.on(this.cmp.getDoc(), 'change', function() {
//				debugger
//			}, this);

//		} else {
//			this.cmp.getDoc().body.addEventListener('paste', this.onPaste.createDelegate(this), true);
		}
	},

	onPaste: function(e) {
		e.preventDefault();
		var data = e.clipboardData.getData("Text");
		this.cmp.insertAtCursor(this.fixWordPaste(data));
	},

	doClean: function(lastValue) {

		this.cmp.suspendEvents();

//		var newValue = this.cmp.getValue(),
//			newLength = newValue.length,
//			lastLength = lastValue.length;

		this.cmp.execCmd("undo");
//		this.cmp.insertAtCursor(this.fixWordPaste(newValue));

//		for (var diffAt=0; diffAt<newLength; diffAt++){
//			if (lastValue[diffAt] != newValue[diffAt]){
//				break;
//			}
//		}

//		var parts = [
//			newValue.substr(0, diffAt),
//			this.fixWordPaste(newValue.substr(diffAt, (newLength - lastLength))),
//			newValue.substr((newLength - lastLength) + diffAt, newLength)
//		];

		// TODO internet explorer support
//		var win = this.cmp.win,
//			sel = win && win.getSelection(),
//			range = sel && sel.getRangeAt(0);
//
//		var caretPos = range ? Math.min(range.startOffset, range.endOffset) : false;


//		this.cmp.setValue(parts.join(''));
//		this.cmp.insertAtCursor(parts[1]);

//		if (caretPos !== false) {
//			sel.removeRange(range);
//			sel.addRange(range);
//			var newRange = this.cmp.getDoc().createRange();
//			// TODO internet explorer support
//			newRange.setStart(sel.anchorNode, caretPos);
//			newRange.setEnd(sel.anchorNode, caretPos);
//			sel.addRange(newRange);
//		}

		this.cmp.resumeEvents();
	},


	// private
	checkIfPaste: function(e){

		var diffAt = 0;
		this.curLength = this.cmp.getValue().length;
		
		if (e.V == e.getKey() && e.ctrlKey && this.wordPasteEnabled){
			
			this.cmp.suspendEvents();
			
			diffAt = this.findValueDiffAt(this.cmp.getValue());
			var parts = [
				this.cmp.getValue().substr(0, diffAt),
				this.fixWordPaste(this.cmp.getValue().substr(diffAt, (this.curLength - this.lastLength))),
				this.cmp.getValue().substr((this.curLength - this.lastLength)+diffAt, this.curLength)
			];
			this.cmp.setValue(parts.join(''));
			
			this.cmp.resumeEvents();
		}
		
		this.lastLength = this.cmp.getValue().length;
		this.lastValue = this.cmp.getValue();
		
	},
	// private
	findValueDiffAt: function(val){
		
		for (var i=0;i<this.curLength;i++){
			if (this.lastValue[i] != val[i]){
				return i;			
			}
		}
		
	},
    /**
     * Cleans up the jubberish html from Word pasted text.
     * @param wordPaste String The text that needs to be cleansed of Word jibberish html.
     * @return {String} The passed in text with all Word jibberish html removed.
     */
    fixWordPaste: function(wordPaste) {

		var wp = wordPaste;

        var removals = [/&nbsp;/ig, /[\r\n]/g, /<(xml|style)[^>]*>.*?<\/\1>/ig, /<\/?(meta|object|span)[^>]*>/ig,
			/<\/?[A-Z0-9]*:[A-Z]*[^>]*>/ig, /(lang|class|type|href|name|title|id|clear)=\"[^\"]*\"/ig, /style=(\'\'|\"\")/ig, /<![\[-].*?-*>/g, 
			/MsoNormal/g, /<\\?\?xml[^>]*>/g, /<\/?o:p[^>]*>/g, /<\/?v:[^>]*>/g, /<\/?o:[^>]*>/g, /<\/?st1:[^>]*>/g, /&nbsp;/g, 
            /<\/?SPAN[^>]*>/g, /<\/?FONT[^>]*>/ig, /<\/?STRONG[^>]*>/ig, /<\/?H1[^>]*>/g, /<\/?H2[^>]*>/g, /<\/?H3[^>]*>/g, /<\/?H4[^>]*>/g,
            /<\/?H5[^>]*>/g, /<\/?H6[^>]*>/g, /<\/?P[^>]*><\/P>/g, /<!--(.*)-->/g, /<!--(.*)>/g, /<!(.*)-->/g, /<\\?\?xml[^>]*>/g, 
            /<\/?o:p[^>]*>/g, /<\/?v:[^>]*>/g, /<\/?o:[^>]*>/g, /<\/?st1:[^>]*>/g, /style=\"[^\"]*\"/g, /style=\'[^\"]*\'/g, /lang=\"[^\"]*\"/g, 
            /lang=\'[^\"]*\'/g, /class=\"[^\"]*\"/g, /class=\'[^\"]*\'/g, /type=\"[^\"]*\"/g, /type=\'[^\"]*\'/g, /href=\'#[^\"]*\'/g, 
            /href=\"#[^\"]*\"/g, /name=\"[^\"]*\"/g, /name=\'[^\"]*\'/g, / clear=\"all\"/g, /id=\"[^\"]*\"/g, /title=\"[^\"]*\"/g, 
            /<span[^>]*>/g, /<\/?span[^>]*>/g, /<title>(.*)<\/title>/g, /class=/g, /<meta[^>]*>/g, /<link[^>]*>/g, /<style>(.*)<\/style>/g, 
            /<w:[^>]*>(.*)<\/w:[^>]*>/g];

        Ext.each(removals, function(s){
            wp = wp.replace(s, "");
        });
        
        // keep the divs in paragraphs
        wp = wp.replace(/<div[^>]*>/g, "<p>");
        wp = wp.replace(/<\/?div[^>]*>/g, "</p>");

		// remove ugly preceding spaces...
//		wp = wp.replace(/\s\s|\s(>)|(<)\s/g, "$1$2");
		// ... and empty paragraphs
		wp = wp.replace(/<p><\/p>/gi, "");
		// use valid brs
		wp = wp.replace(/<br>/gi, "<br/>");

        return wp;
        
    },
	// private
    onRender: function() {
        
        this.cmp.getToolbar().add({
            iconCls: 'x-edit-wordpaste',
            pressed: true,
            handler: function(t){
                t.toggle(!t.pressed);
                this.wordPasteEnabled = !this.wordPasteEnabled;
            },
            scope: this,
            tooltip: {
                text: this.langToolTip
            },
            overflowText: this.langTitle
        });
		
    }
});