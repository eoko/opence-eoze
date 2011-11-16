/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 nov. 2011
 */
Ext.ns('eo');

(function() {

var spp = Ext.Panel.prototype;
	
eo.JsonPanel = Ext.extend(Ext.Panel, {

	isFormField: true
//		,height: 200
	,autoScroll: true
	,bodyCssClass: "eo-json-list"

	,expandJson: true

	,initComponent: function() {

		this.root = {
//				nodeType: 'sync'
			text: 'root'
			,id: 'root'
		};

		this.tbar = [{
			text: 'json_'
			,tooltip: 'Expand <i>json_</i> Strings'
			,enableToggle: true
			,pressed: true
			,scope: this
			,toggleHandler: function(b, s) {
				this.expandJson = s;
				this.refreshRender();
			}
		},'-',{
			iconCls: 'ico collapse-all'
			,tooltip: 'Collapse All Collection'
			,scope: this
			,handler: function() {
				this.collapseAll();
			}
		},{
			iconCls: 'ico expand-all'
			,tooltip: 'Expand All Collection'
			,scope: this
			,handler: function() {
				this.expandAll();
			}
		}];

		spp.initComponent.call(this);
	}
	,collapseAll: Ext.emptyFn
	,expandAll: Ext.emptyFn
	,valueToHtml: function(value) {

		var me = this,
			dom = Ext.get(Ext.DomHelper.createDom({tag:"div"})),
			i = 0,
			offset = 30,
			collapsedLines = [], expandedLines = [];

		this.collapseAll = function() {
			Ext.each(expandedLines.slice(0), function(fn) {
				fn();
			});
		};
		this.expandAll = function() {
			Ext.each(collapsedLines.slice(0), function(fn) {
				fn();
			});
		};

		var decode = function(s) {
			try {
				return Ext.util.JSON.decode(s);
			} catch (e) {
				try {
					return Ext.util.JSON.decode(decodeURIComponent(s));
				} catch (e) {
					return new Error('Decoding Error');
				}
			}
		};

		var span = function(cls, html) {
			return {tag:'span', cls:cls, html:html};
		};

		var delim = function(html) {
			return span('delimiter', html);
		};

		var createLine = function(list, level, key) {

			i++;

			var line = list.createChild({tag: "div", cls: "line offset" + (i%2)});

			var folder = line.createChild(span('fold gutter'));
			line.createChild(span('number gutter', i));
			var code = line.createChild({tag:'code', 
					style: "margin-left: " + (40+level*offset+5) + "px;"});

			if (key) {
				code.createChild(span('key', key));
				code.createChild(delim(': '));
			}

			return {
				code: code
				,line: line
				,folder: folder

				,setExpandable: function(list, rdelim, closeLine) {

					folder.addClass('collapse');

					var collapsed = false,
						del;

					var onClick = function(e) {
						if (e) {
							e.stopPropagation();
						}
						if (!del) {
							del = code.createChild(delim('&nbsp;&hellip;&nbsp;'+rdelim
									+ (closeLine.hasComma ? ',' : '')));
						}
						if (collapsed) {
							folder.removeClass('expand');
							folder.addClass('collapse');
							list.setDisplayed(true);
							del.setDisplayed(false);
							// toggle all support
							collapsedLines.remove(onClick);
							expandedLines.push(onClick);
						} else {
							folder.removeClass('collapse');
							folder.addClass('expand');
							list.setDisplayed(false);
							del.setDisplayed(true);
							// toggle all support
							expandedLines.remove(onClick);
							collapsedLines.push(onClick);
						}
						collapsed = !collapsed;
					};

					expandedLines.push(onClick);

					folder.on('click', onClick);
					line.on('click', onClick);
				}
			};
		};

		var convertValue = function(value) {
			var cls,
				v = value;
			if (v === null || v === undefined || Ext.isBoolean(v)) {
				cls = 'keyword';
				v = '' + v;
			} else if (Ext.isNumber(v)) {
				cls = 'number';
			} else if (Ext.isBoolean(v)) {
				cls = 'boolean';
			} else if (Ext.isString(v)) {
				cls = 'string';
				v = '<span class="delimiter">"</span>' + v + '<span class="delimiter">"</span>';
			}
			return {
				value: v,
				cls: 'value ' + cls
			};
		}

		var decodeValue = function(el, key, value, level, lastLine) {

			if (lastLine) {
				lastLine.code.createChild({tag:'span', cls:'delimiter', html:','});
				lastLine.hasComma = true;
			}

			if (me.expandJson && key && Ext.isString(value) && /^json_/.test(key)) {
				value = decode(value);
			}

			if (Ext.isArray(value)) return (function() {

				var line = createLine(el, level, key);
				line.code.createChild({tag:'span', cls: 'delimiter', html:'['});

				var closeLine = line,
					lastLine;

				if (value.length) {
					var myList = el.createChild({tag:'div', cls:'list'});
					Ext.each(value, function(v) {
						lastLine = decodeValue(myList, null, v, level+1, lastLine);
					});
					closeLine = createLine(myList, level);
					line.setExpandable(myList, ']', closeLine);
				}

				closeLine.code.createChild({tag:'span', cls:'delimiter', html:']'});

				return closeLine;

			})(); else if (Ext.isObject(value)) return (function() {

//					if (lastLine && lastLine.type === '}') {
//						lastLine.code.createChild({tag:'span', cls: 'delimiter', html:'{'});
//					} else {
					var line = createLine(el, level, key);
					line.code.createChild({tag:'span', cls: 'delimiter', html:'{'});
//					}
				var myList = el.createChild({tag:'div', cls:'list'}),
					lll; // local last line

				Ext.iterate(value, function(k,v) {
					lll = decodeValue(myList, k, v, level+1, lll );
				});
				var closeLine = createLine(myList, level);
				closeLine.type = '}';
				closeLine.code.createChild({tag:'span', cls:'delimiter', html:'}'});

				if (el !== dom) {
					line.setExpandable(myList, '}', closeLine);
				}

				return closeLine;

			})(); else {
				var line = createLine(el, level, key),
					o = convertValue(value);
				line.code.createChild({tag:'span', cls:o.cls, html:o.value});
				return line;
			}

			return;

			if (Ext.isObject(value)) Ext.iterate(value, function(k, v) {

				i++;

				var line = list.createChild({tag: "div", cls: "line offset" + (i%2)});

				var folder = line.createChild({tag:'span', cls: 'fold gutter'});
				line.createChild({tag:'span', cls: 'number gutter', html: i});
				var code = line.createChild({tag:'code', 
						style: "margin-left: " + (40+level*offset+5) + "px;"});
				code.createChild({tag:'span', cls: 'key', html: k});
				code.createChild({tag:'span', cls: 'delimiter', html: ': '});

				if (/^json_(.*)$/.test(k)) {

					var ldelim = code.createChild({tag:'span', cls:'delimiter', html: '{ '}),
						ellip = code.createChild({tag:'span', cls: 'value keyword', html:'&hellip;'});

					ellip.createChild({tag:'span', cls:'delimiter', html: ' }'});

//						ldelim.setDisplayed(false);
//						rdelim.setDisplayed(false);
					ellip.setDisplayed(false);

					var foldList = decodeValue(list, v, level+1);

					(function() {
						i++;
						var line = foldList.createChild({tag: "div", cls: "line offset" + (i%2)});
						line.createChild({tag:'span', cls: 'fold gutter'});
						line.createChild({tag:'span', cls: 'number gutter', html: i});
						line.createChild({tag:'code', 
								style: "margin-left: " + (40+level*offset+5) + "px;"})
							.createChild({tag:'span', cls:'delimiter', html: ' }'});
					})();

					folder.addClass('collapse');
					var collapsed = false;
					folder.on('click', function() {
						collapsed = !collapsed;
						if (collapsed) {
							folder.removeClass('collapse');
							folder.addClass('expand');
						} else {
							folder.removeClass('expand');
							folder.addClass('collapse');
						}
						ellip.setDisplayed(collapsed);
						foldList.setDisplayed(!collapsed);
					});
				} else {
					var cls = '';
					if (Ext.isString(v)) {
						cls = 'string';
						code.createChild({tag:'span', cls: 'delimiter' + cls, html: '"'});
						code.createChild({tag:'span', cls: 'value ' + cls, html: v});
						code.createChild({tag:'span', cls: 'delimiter' + cls, html: '"'});
					} else {
						if (v === null || v === undefined || Ext.isBoolean(v)) {
							cls = 'keyword';
							v = '' + v;
						} else if (Ext.isNumber(v)) {
							cls = 'number';
						} else if (Ext.isBoolean(v)) {
							cls = 'boolean';
						}
						code.createChild({tag:'span', cls: 'value ' + cls, html: v});
					}
				}
			});

			else if (Ext.isArray(value)) (function() {

				i++;

				var line = list.createChild({tag: "div", cls: "line offset" + (i%2)});

				var folder = line.createChild({tag:'span', cls: 'fold gutter'});
				line.createChild({tag:'span', cls: 'number gutter', html: i});
				var code = line.createChild({tag:'code', 
						style: "margin-left: " + (40+level*offset+5) + "px;"});
//					code.createChild({tag:'span', cls: 'key', html: k});
				code.createChild({tag:'span', cls: 'delimiter', html: '[ '});
				code.createChild({tag:'span', cls: 'value', html: value.join(', ')});
				code.createChild({tag:'span', cls: 'delimiter', html: ' ]'});

				debugger
				Ext.each(value, function(v) {

				});
			})(); // isArray

			return list;
		}

		decodeValue(dom, null, decode(value), 0);

		return dom;
	}
	,getName: function() {
		return this.name;
	}
	,refreshRender: function() {
		var dom = this.valueToHtml(this.value);
		if (this.rendered) {
			this.body.update('');
			dom.appendTo(this.body);
		} else {
			this.valueDom = dom;
		}
	}
	,setValue: function(v) {
		this.value = v;
		this.refreshRender();
	}
	,getValue: function() {
		return this.value;
	}
	,afterRender: function() {
		spp.afterRender.apply(this, arguments);
		this.body.update('');
		if (this.valueDom) {
			this.valueDom.appendTo(this.body);
			delete this.valueDom;
		}
	}

	,reset: function() {
		this.setValue(null);
	}
	,markInvalid: Ext.emptyFn
	,clearInvalid: Ext.emptyFn
});

Ext.reg('jsonpanel', eo.JsonPanel);

})(); // closure