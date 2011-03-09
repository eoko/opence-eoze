Ext.ns('eo.wizard');

Oce.deps.wait(eo.wizard.deps, function() {

// The base class to be overriden by eo.wizard.Window
if (!eo.wizard.WindowBase) {
	eo.wizard.WindowBase = Ext.Window;
} else if (Ext.isString(eo.wizard.WindowBase)) {
	eo.wizard.WindowBase = Ext.ns(eo.wizard.WindowBase);
}

eo.wizard.Step = Ext.extend(Ext.util.Observable, {

	constructor: function(config) {
		eo.wizard.Step.superclass.constructor.call(this, config);
		Ext.apply(this, config);

		if (!this.name) this.name = Ext.id();

		if (false == this.panel instanceof Ext.Panel) {
			this.panel = Ext.create(Ext.apply({
				xtype: "panel"
				,padding: 10
			}, this.panel));
		}

		this.enabled = true;

		if (this.parent) this.setParent(this.parent);
	}

	,getRootWizard: function() {
		return this.parent.getRootWizard();
	}

	,setParent: function(parent) {
		this.parent = parent;
	}

	,getParent: function() {
		return this.parent;
	}

	,getTitle: function() {
		return this.stepTitle || this.title;
	}

	,enable: function() {
		this.enabled = true;
	}
	,disable: function() {
		this.enabled = false;
	}

	,getPanel: function() {
		return this.panel;
	}

	,canFinish: function() {
		if (this.finish) {
			if (Ext.isFunction(this.finish)) return this.finish();
			else return this.finish;
		} else {
			return false;
		}
	}

	,hasPrev: function() {
		var prev = this.getPrev();
		if (!prev) return false;
		else return prev.enabled;
	}
	,hasNext: function() {
		var next = this.getNext();
		if (!next) return false;
		else return next.enabled;
	}

	,setPrevIf: function(step) {
		if (this.prev === undefined) this.prev = step;
	}
	,setNextIf: function(step) {
		if (this.next === undefined) this.next = step;
	}

	,getNextOrPrev: function(prop) {
		var n = this[prop];
		if (!n) {
			return false;
		} else if (n instanceof eo.wizard.Step) {
			return n;
		} else if (Ext.isFunction(n)) {
			// we cannot require the step here, because this method is called
			// during parent's construction, before parent.steps exists...
			return this.parent.getStep(n.call(this), false);
		} else if (Ext.isString(n)) {
			// we cannot require the step here, because this method is called
			// during parent's construction, before parent.steps exists...
			return this.parent.getStep(n, false);
		} else {
			throw new Error('Invalid step property "' + prop + '": ' + n);
		}
	}

	,getResolvedNext: function() {
		if (this.nextStep) {
			return this.parent.getStep(this.nextStep);
		} else {
			return this.getNext();
		}
	}

	,getNext: function() {
		return this.getNextOrPrev("next");
	}
	,getPrev: function() {
		return this.getNextOrPrev("prev");
	}

	,continueFinish: function() {
		this.parent.finish();
	}
	,continueNext: function() {
		this.parent.setStep(this.getNext());
	}
	,goNext: function() {
		if (this.canFinish()) {
			if (this.onFinish) {
				if (this.onFinish(this.continueFinish.createDelegate(this))) {
					this.parent.finish();
				}
			} else {
				this.parent.finish();
			}
		} else {
			if (this.onNext) {
				if (this.onNext(this.continueNext.createDelegate(this), this)) {
					this.continueNext();
				}
			} else {
				this.continueNext();
			}
		}
	}
	,goPrev: function() {
		this.parent.setStep(this.getPrev());
	}

	,wait: function() {
		this.parent.wait.apply(this.parent, arguments);
	}

});

eo.wizard.FormStep = Ext.extend(eo.wizard.Step, {

	formXType: "form"
	,formDefaults: {
		defaults: {
			xtype: "textfield"
			,enableKeyEvents: true
		}
	}

	,requireValid: true

	,constructor: function(config) {

		if (this.panel !== undefined) throw new Error(
			'Illegal config option for subwizard ste: "panel"'
		);

		this.superclass = eo.wizard.FormStep.superclass;
		var me = this;

		var form;
		if (config.form instanceof Ext.FormPanel) {
			form = config.form.getForm();
			this.panel = config.form;
		} else {
			var cfg = Ext.apply(Ext.apply({
				xtype: this.formXType
				,autoScroll: true
				,monitorValid: config.requireValid !== undefined ?
					config.requireValid : this.requireValid
				,padding: 10
			}, this.formDefaults), config.form);
			this.panel = Ext.create(cfg);
			form = this.panel.getForm();
		}

		this.superclass.constructor.call(this, config);

		this.form = form;

		// --- Rewrite next if it is given as an array
		if (this.conditions) {
			
			var tests = Ext.isArray(this.conditions) ? this.conditions : [this.conditions];

			var ff = function(field) {
				if (field instanceof Ext.form.Field) return field;
				if ((field = me.form.findField(field))) {
					return field;
				} else {
					throw new Error('No field "' + field + '" in wizard step\'s form');
				}
			}

			var testFields = function(fields) {
				if (!Ext.isArray(fields)) fields = [fields];
				var field;
				for (var i=0,l=fields.length; i<l; i++) {
					field = ff(fields[i]);
					if (!field.isValid(true) || !field.getValue()) return false;
//					if (!field.getValue()) return false;
				}
				return true;
			}

			this.defaultActions = {
				next: this.next
				,prev: this.prev
				,finish: this.finish
			};

			var getComplete = function() {
				for (var i=0,l=tests.length; i<l; i++) {
					if (testFields(tests[i].fields)) {
						return tests[i];
					}
				}
				return false;
			};

			(this.beforeTestComplete = function() {
				var t = getComplete();
				if (t) {
					me.next = "next" in t && t.next !== true ? t.next : me.defaultActions.next;
					me.prev = "prev" in t && t.next !== true ? t.prev : me.defaultActions.prev;
					me.finish = "finish" in t && t.next !== true ? t.finish : me.defaultActions.finish;
				} else {
					me.next = false;
					me.finish = false;
					me.prev = me.defaultActions.prev;
				}
			})();
		}

		// --- Add events to fields required to complete a step
		if (this.monitorFields !== false) {

			var addListener = function(field) {
				if (Ext.isString(field)) {
					if (!(field = ff(field))) throw new Error(
						'No field "' + field + '" in form'
					);
				}

				var e;
				// Note: the order of the instanceof tests is important since
				// combobox is an instance of TextField, DateField an instance
				// of ComboBox, etc.
				if (field instanceof Ext.form.CompositeField) {
					field.items.each(addListener);
					return;
				} else if (field instanceof Ext.form.Checkbox) {
					// do not wait for blur, for checkboxes and subclasses
					// (this includes radio buttons)
					e = "check"
				} else if (field instanceof Ext.form.DateField) {
					e = ["select"];
					if (field.enableKeyEvents) {
						e.push("keyup");
					} else {
						e.push("change");
					}
				} else if (field instanceof Ext.form.ComboBox) {
					e = "select";
				} else if (field instanceof Ext.form.TextField && field.enableKeyEvents) {
					e = "keyup";
				} else {
					e = "change";
				}

				if (!Ext.isArray(e)) {
					field.on(e, me.testComplete, me);
				} else {
					Ext.each(e, function(evt){
						field.on(evt, me.testComplete, me);
					});
				}
			}

			if (Ext.isArray(this.monitorFields)) {
				Ext.each(this.monitorFields, addListener);
			} else if (Ext.isString(this.monitorFields)) {
				addListener(this.monitorFields)
			} else {
				// Monitor all fields
				this.form.items.each(addListener);
			}
		}

		this.lastHasNext = this.hasNext();
		this.lastFinish = this.canFinish();
		this.lastHasPrev = this.hasPrev();
		this.lastNext = this.getNext();
	}

	,setPrevIf: function(step) {
		if (this.defaultActions) {
			if (this.defaultActions.prev === undefined) this.defaultActions.prev = step;
			this.beforeTestComplete();
		} else {
			this.superclass.setPrevIf.call(this, step);
		}
	}
	,setNextIf: function(step) {
		if (this.defaultActions) {
			if (this.defaultActions.next === undefined) this.defaultActions.next = step;
			this.beforeTestComplete();
		} else {
			this.superclass.setNextIf.call(this, step);
		}
	}

	,testComplete: function() {
		if (this.beforeTestComplete) this.beforeTestComplete();
		
		var hasNext = this.hasNext()
			,finish = this.canFinish()
			,hasPrev = this.hasPrev()
			,next = this.getNext()
			;
			
		if (hasNext !== this.lastHasNext 
				|| finish !== this.lastFinish 
				|| hasPrev !== this.lastHasPrev
				|| next !== this.lastNext
		) {
			this.lastHasNext = hasNext;
			this.lastHasPrev = hasPrev;
			this.lastFinish = finish;
			
			if (this.lastNext !== next) {
				this.lastNext = next;
				next.prev = this;
			}
			
			this.parent.updateStep();
		}
	}

	,findField: function(name) {
		return this.form.findField(name);
	}
});

Oce.deps.reg('eo.wizard.Step');

eo.wizard.texts = {
	prev: "< Previous"
	,next: "Next >"
	,cancel: "Cancel"
	,finish: "Finish"
};

/**
 * <h1>General principles of the wizard</h1>
 * 
 * <h2>Dynamic steps</h2>
 * For any step, the next step can be computed dynamically by overriding the 
 * step's next() method.
 * Previous steps, however, cannot be computed in the same way (by overridding
 * the prev method), because prev steps are dynamically set internally according
 * to the next step of each step. This is a technical limitation (to be able to
 * predict the full step path -- previous steps as well as next steps -- from 
 * the currently set step. However, this is consistant with an end-user 
 * expectation that their actions in a step may have an influence on the steps 
 * to come, but not on the steps they have already completed.
 * 
 * @param {Object} [config] Configuration of the wizard
 * 
 * @class
 * @todo clean this doc block
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since before 09/03/11 20:06
 */
eo.WizardPanel = eo.wizard.WizardPanel = function(config) { return Ext.extend(Ext.Panel, {
		// this is wrapped in a dull function to hint the doc parser

	texts: eo.wizard.texts

	,constructor: function(config) {

		this.addEvents({
			updatebuttons: true
			,updateprogress: true
			,beforesubmit: true
			,aftersubmit: true
		});

		var me = this;

		// --- Create Steps ---

		var steps = new Oce.util.HashEx
			,panels = new Oce.util.ArrayEx
			;

		if (!config.steps) throw new Error(
			'Missing wizard required config options: "steps"'
		);

		var firstStep, lastStep
			,hasLast = false
			;

//		Ext.iterate(config.steps, function(name, step) {
		var configSteps = config.steps;
		if (Ext.isObject(configSteps)) {
			configSteps = [];
			Ext.iterate(config.steps, function(name, step) {
				step.name = name;
				configSteps.push(step);
			});
		}
		Ext.each(configSteps, function(step) {

			// Ensure the step is intantiated, forcing the name
			step = me.createStep(Ext.apply({
				panelIndex: panels.length
			}, step));

			var name = step.name;

			// Determine whether the wizard has last step (to auto set one, if
			// not the case)
			if (step.finish) {
				hasLast = true;
			}

			// Keep values
			//... steps
			steps[name] = step;
			if (!firstStep) firstStep = step;
			if (lastStep) {
				lastStep.setNextIf(step);
				step.setPrevIf(lastStep);
			}
			lastStep = step;
			//... panels
			panels.push(step.getPanel());
		});

		if (!hasLast && lastStep) {
			lastStep.finish = true;
		}

		// --- Wait panel ---
		if (config.wait) {
			var p = config.wait;
			if (p instanceof Ext.Panel) {
				p = p;
			} else {
				p = Ext.create(Ext.apply({
					xtype: "panel"
//					,html: "Please, be patient!"
					,html: "Veuillez patienter..."
				}, p));
			}

			this.waitPanelIndex = panels.length;
			panels.push(p);

			var configWait = config.wait;
			delete config.wait;
		}

		// --- Config ---
		var cfg = Ext.apply({
//			,flex: 1
			layout: "card"
			,activeItem: 0
			,defaults: {
				bodyStyle: "background: #f5f5f5"
//				,border: false
				,bodyBorder: false
//				,padding: 10
			}
			,border: false
			,bodyBorder: false
			,items: panels
			,cancellable: true
		}, config);


		// --- Super ---
		eo.wizard.WizardPanel.superclass.constructor.call(this, cfg);
		if (configWait !== undefined) config.wait = configWait;

		// --- Instance ---
		this.steps = steps;

		this.currentStep = firstStep;
	}

	,createStep: function(config, name) {
		if (config instanceof eo.wizard.Step) {
			// Enforce name
			if (name) {
				if (config.name !== name) throw new Error(
					'Bad "name" set for step: "' + name + '" (should be: "' + name + '")'
				)
			} else {
				if (!config.name) throw new Error('Missing prop "name" in step');
			}
			// Check parent
			if (config.parent !== this) config.setParent(this);
			return config;
		} else {

			if (name) {
				if (config.name !== undefined) {
					if (config.name !== name) throw new Error(
						'Illegal wizard step["' + name
						+ '"] config option "name" (must be left blank, or '
						+ 'equals the step name)'
					);
				} else {
					config = Ext.apply({name: name}, config);
				}
			}

			config = Ext.apply({parent: this}, config);

			if (config.xtype) {
				return Ext.create(config);
			} else if (config.panel) {
				return new eo.wizard.Step(config);
			} else if (config.form) {
				return new eo.wizard.FormStep(config);
			} else if (config.steps) {
				return new eo.wizard.SubwizardStep(config);
			} else {
				return new eo.wizard.Step(Ext.apply({
					panel: {
						html: "<h1>" + (config.stepTitle || config.title) + "</h1><p>"
							+ (config.desc || "") + "</p>"
					}
				}, config));
			}
		}
	}

	,addStep: function(step) {

		if (arguments.length > 1) {
			var me = this, r = [];
			Ext.each(arguments, function(a){
				r.push(me.addStep(a));
			});
			return r;
		}

		step = this.createStep(step);
		step.panelIndex = this.items.length;
		this.steps[step.name] = step;
		this.add(step.panel);

		return step;
	}

	/**
	 * Add a step to this wizard, adding it to the step path, as the last step
	 * (ie. after the current last step).
	 */
	,pushStep: function(step) {
		
		if (arguments.length > 1) {
			for (var i=0,len=arguments.length; i<len; i++) {
				step = this.pushStep(arguments[i]);
			}
			return step;
		}

		step = this.addStep(step);

		if (!this.currentStep) {
			this.currentStep = step;
			this.getLayout().setActiveItem(step.panelIndex);
			step.finish = true;
		} else {
			var last = this.getLastStep();
			if (last) {
				last.setNextIf(step);
				step.setPrevIf(last);
				step.finish = last.finish;
				delete last.finish;
			}
		}

		return step;
	}

	,getLastStep: function() {
		return this.getStepsPath().pop();
	}

	,removeStep: function(step) {

		if (Ext.isString(step)) {
			step = this.getStep(step);
		} else if (false == step instanceof eo.wizard.Step) throw new Error(
			"Invalid argument: " + step
		);

		this.remove(step.panel);

		delete this.steps[step.name];

		var pi = step.panelIndex;
		this.steps.each(function(step) {
			if (step.panelIndex > pi) step.panelIndex--;
		});
	}

	,getRootWizard: function() {
		return this;
	}

	,getStep: function(name, require, searchChildren, searchRoot) {
		
		// getStep can be called during child steps construction, that is before
		// the current step has finished its own construction, and so this.steps
		// could be undefined...
		if (this.steps) {
			searchChildren = searchChildren !== false;
			searchRoot = searchRoot !== false;

			if (name instanceof eo.wizard.Step) {
				return name;
			} else if (name in this.steps) {
				return this.steps[name];
			}

			if (searchRoot) {
				var root = this.getRootWizard();
				if (root !== this) return root.getStep(name, require, searchChildren, false);
			}

			var r = false;
			if (searchChildren) {
				this.steps.each(function(step) {
					if (!r && step.getStep) {
						// the call of getStep() on the children must have
						// searchParents set to false, to prevent infinite recursion
						r = step.getStep(name, false, searchChildren, false);
						if (r) return false;
					}
				});
				if (r) return r;
			}
		}

		if (require === false) {
			return undefined;
		} else {
			throw new Error('No step "' + name + '" in wizard');
		}
	}
	
	,getStepsPath: function() {

		var getSteps = function(step) {
			if (step instanceof eo.wizard.SubwizardStep) {
				return doGetSteps(step.wizard);
			} else {
				return [step];
			}
		};

		var doGetSteps = function(wizard) {

			var cs = wizard.currentStep;

			if (!cs) {
				return [];
			}

			var r = [];
			var steps = getSteps(cs);
			if (steps && steps.length) r = [].concat(steps);
				
			var step = cs;
			while ((step = step.getPrev())) {
				steps = getSteps(step);
				if (steps && steps.length) r = Array.prototype.concat.call(steps, r);
			}

			step = cs;
			while (!step.canFinish() && (step = step.getResolvedNext())) {
				steps = getSteps(step);
				if (steps && steps.length) r.concat(steps);
			}

			return r;
		};

		return doGetSteps(this);
	}

	,findField: function(name, step) {
		if (step) {
			return this.getStep(step, true).findField(name);
		} else {
//			var field;
//			Ext.iterate(this.steps, function(n,step){
//				var f;
//				if (step.findField && (f = step.findField(name))) {
//					field = f;
//					return false;
//				}
//			});
//			return field;
			var fs = this.findFieldAndStep(name);
			if (fs) return fs.field;
		}
	}

	,findFieldAndStep: function(name) {
		var r;
		Ext.iterate(this.steps, function(n,step){
			var f;
			if (step.findField && (f = step.findField(name))) {
				r = {
					field: f,
					step: step
				};
				return false;
			}
		});
		return r;
	}

	,getFieldValue: function(name, step) {
		var field = this.getField(name, step);
		if (!field) throw new Error('Wizard has no field "' + name + '"');
		return field.getValue();
	}
	
	,disableButtons: function() {
		this.fireEvent("updatebuttons", {
			prev: {disable: true}
			,next: {disable: true}
			,cancel: {disable: true}
		});
	}

	,disableNavigation: function() {
		this.fireEvent("updatebuttons", {
			prev: {disable: true}
			,next: {disable: true}
		});
	}

	,updateButtons: function() {
		
		if (!this.hasListener("updatebuttons")) return;
		
		var cfg = {prev:{},next:{},cancel:{}}
			,cs = this.currentStep
			;

		if (cs.canFinish()) {
			cfg.next.enable = true;
			cfg.next.text = this.texts.finish;
		} else {
			cfg.next.text = this.texts.next;

			if (cs.hasNext()) {
				cfg.next.enable = true;
			} else {
				cfg.next.disable = true;
			}
		}

		if (cs.hasPrev()) {
			cfg.prev.enable = true;
		} else {
			cfg.prev.disable = true;
		}

		if (this.cancellable) {
			cfg.cancel.enable = true;
		} else {
			cfg.cancel.disable = true;
			if (this.hideCancel) {
				cfg.cancel.hide = true;
			}
		}

		this.fireEvent("updatebuttons", cfg);
	}

	,updateProgress: function() {
		if (this.hasListener("updateprogress")) {
			this.fireEvent("updateprogress", this);
		}
	}

	,updateStep: function() {
		var w = this.getRootWizard();
		w.updateButtons();
		w.updateProgress();
	}

	,setStep: function(step) {
		if (arguments.length > 1) {
			var wizard = this;
			for (var i=0,l=arguments.length; i<l; i++) {
				if (!wizard) throw new Error();
				step = wizard.setStep(arguments[i]);
				wizard = step.wizard;
			}
		} else {
			step = this.getStep(step);
			if (step.getParent() !== this) {
				step.getParent().setStep(step);
			} else {
				this.getLayout().setActiveItem(step.panelIndex);
				this.currentStep = step;
				if (this.parentStep) this.parentStep.parent.setStep(this.parentStep);
				// This is required so that if the set step dynamically decide
				// its next step, it is given the opportunity to update its
				// dynamic next step previous step
				// That is: step.next().prev = step, which is done in 
				// testComplete...
				if (step.testComplete) step.testComplete();
			}
		}
		this.updateStep();
		return step;
	}

	,setActiveItem: function(index) {
		this.getLayout().setActiveItem(index);
	}

	,next: function() {
		this.currentStep.goNext();
	}
	,prev: function() {
		this.currentStep.goPrev();
	}

	,getFormData: function(opts) {

		opts = Ext.apply(this.submitOptions || {}, opts);
		var steps = opts.steps || this.getStepsPath();
		var getStep = this.getStep.createDelegate(this);

		var formData = {};
		if (opts.tree) {
			Ext.each(steps, function(step) {
				step = getStep(step);
				if (step instanceof eo.wizard.FormStep && step.submit !== false) {
					formData[step.name] = Oce.form.getFormData(step.form);
				}
			});
		} else {
			Ext.each(steps, function(step) {
				step = getStep(step);
				var prefix = opts.addPrefix ? step.name + "_" : false;
				if (step instanceof eo.wizard.FormStep && step.submit !== false) {
					Ext.apply(
						formData,
						Oce.form.getFormData(step.form, prefix)
					);
				}
			});
		}

		return formData;
	}
	
	// private
	,onSubmit: function(finish, opts) {

		if (!this.fireEvent('beforesubmit', this)) {
			return;
		}
		
		this.disableButtons();

//		opts = Ext.apply(this.submitOptions || {}, opts);
//		opts = Ext.apply(Ext.apply({}, this.submitOptions), opts);
//
//		opts = Ext.apply({
//			jsonParam: false
//			,tree: false
//			,addPrefix: false
//		}, opts);
		var so = this.submitOptions || {};
		var baseParams = Ext.apply({}, so.params);
		opts = Ext.apply(Ext.apply({
			jsonParam: false
			,tree: false
			,addPrefix: false
		}, so), opts);
		var params = opts.params = Ext.apply(baseParams, opts.params)

		var steps = opts.steps || this.getStepsPath();
		var getStep = this.getStep.createDelegate(this);

		var formData = {};
		if (opts.jsonParam && opts.tree) {
			Ext.each(steps, function(step) {
				step = getStep(step);
				if (step instanceof eo.wizard.FormStep && step.submit !== false) {
					formData[step.name] = Oce.form.getFormData(step.form);
				}
			});
		} else {
			var applyStepFormData = function(step, prefix) {
				step = getStep(step);

				if (opts.addPrefix) {
					prefix = (prefix || step.name) + "_";
				} else {
					prefix = false;
				}
//				prefix = opts.addPrefix ? step.name + "_" : false;

				if (step instanceof eo.wizard.SubwizardStep) {
					Ext.each(step.getStepsPath(), function(step) {
						applyStepFormData(step, prefix);
					});
				} else if (step instanceof eo.wizard.FormStep && step.submit !== false) {
					Ext.apply(
						formData,
						Oce.form.getFormData(step.form, prefix)
					);
				}
			};
			Ext.each(steps, applyStepFormData);
		}

//		var params = Ext.apply({}, opts.params);
		if (opts.jsonParam) {
			// submit the form with a json param
			var pp = params[opts.jsonParam];
			params[opts.jsonParam] = encodeURIComponent(Ext.encode(
				pp ? Ext.apply(pp, formData) : formData
			));
		} else {
			// serialize the form and submit
			Ext.apply(params, formData);
		}
//		opts.params = params;
		
		// Do the request
		delete opts.jsonParam;
		delete opts.tree;
		delete opts.addPrefix;
		
		var request, successCB;
		if (Oce.Ajax) {
			request = Oce.Ajax.request;
			successCB = "onSuccess";
			if (opts.waitTarget === undefined) {
				opts.waitTarget = this.findParentBy(function(p){return p.owner === undefined});
			}
		} else {
			request = Ext.Ajax.request;
			successCB = "success";
		}
		
		var me = this;
		var prev = opts[successCB];
		opts[successCB] = function() {
			if (prev) prev.apply(this, arguments);
			me.fireEvent('aftersubmit', this, true);
			if (finish) finish.apply(this, arguments);
		}
		
		var prev2 = opts["callback"];
		opts["onFailure"] = function(obj, e) {
			if (prev2) prev2.apply(this, arguments);
			me.updateButtons();
			
			if (obj.errors) {
				Ext.iterate(obj.errors, function(field, msg) {
					var fs = me.findFieldAndStep(field);
					if (fs) {
						fs.field.markInvalid(msg);
					}
				});
			}
			e.forceMsgBox = true;
		}

		request(opts);
	}
	
	// private
	,finishActions: {
		close: function() {
			return true;
		}
		,submit: function() {this.onSubmit.apply(this, arguments);}
	}
	
	,finish: function() {
		if (this.onFinish) {
			var opt = this.onFinish,
				doFinish = this.fireEvent.createDelegate(this, ["complete", this]);
			
			if (Ext.isFunction(opt)) {
				var r = opt.call(this, doFinish);
				if (r) {
					if (r.action) {
						opt = r;
					} else if (r === true) {
						doFinish();
					}
				}
			} //  else {
				var action, args;
				
				if (Ext.isString(opt)) {
					action = this.finishActions[opt];
					args = [doFinish];
				} else if (Ext.isObject(opt)) {
					action = this.finishActions[opt.action];
					args = [doFinish, opt];
				} else {
					throw new Error("Invalid onFinish value: " + this.onFinish);
				}
				
				// execute action
				if (!action) {
					throw new Error('Invalid onFinish action: ' + opt);
				} else {
					if (action.apply(this, args)) {
						doFinish();
					}
				}
//			}
		} else {
			this.fireEvent("complete", this);
		}
	}

	,wait: function(opts) {

		this.fireEvent("updatebuttons", {
			next: {
				disable: true
			}
			,prev: {
				disable: true
			}
			,cancel: {
				disable: true
			}
		});

		this.getLayout().setActiveItem(this.waitPanelIndex);
	}
	
});
};

eo.wizard.Progress= Ext.extend(Ext.Panel, {

	getSteps: function() {

		var currentDesc, parentDescs = [], parents = true, rootLvl;
		
		var doGetSteps = function(wizard, root, lvl, parent, rootParent) {

			var me = this;
			var hasSub = false;

			var getDesc = function(step) {
				var finish = step.canFinish(),
					r = {
						title: step.getTitle()
//						,current: false
						,finish: finish && (!parent || !parent.getNext())
							&& (!rootParent || rootParent === parent || !rootParent.getNext())
						,unresolved: !finish && !step.getResolvedNext()
						,desc: step.desc || step.shortDesc
						,explain: step.explain || step.explaination || step.longDesc
					};

				if (step instanceof eo.wizard.SubwizardStep) {
					r.steps = doGetSteps(step.wizard, false, lvl+1, step, rootParent || step);
					hasSub = true;
				}

				return r;
			};

			var cs = wizard.currentStep;
			var r;
			
			if (cs) {
	//			var csDesc = getDesc(cs),
	//				r = [Ext.apply(csDesc, {
	//					current: root && !(cs instanceof eo.wizard.SubwizardStep)
	//
	//				})];
				var csDesc = getDesc(cs);

				r = [csDesc];

				if (!currentDesc) {
					rootLvl = lvl;
					currentDesc = csDesc;
				} else if (lvl <= rootLvl && parents) {
					parentDescs.push(csDesc);
				}

				if (root) parents = false;

				var step = cs;
				while ((step = step.getPrev())) {
					r = Array.prototype.concat.call(Ext.apply(getDesc(step), {
						done: true
					}), r);
				}

				step = cs;
				while (!step.canFinish() && (step = step.getResolvedNext())) {
					r.push(getDesc(step));
				}
			}

			return {
				steps: r
				,currentStep: csDesc
				,hasSub: hasSub
			};
		};

		var r = doGetSteps(this.wizard, true, 0);

		if (currentDesc) {
			currentDesc.current = true;
			r.absCurrent = currentDesc;
		}
		var parentTree = [];
		Ext.each(parentDescs, function(d) {
			d.currentParent = true;
			parentTree = Array.prototype.concat(d, parentTree);
		});
		r.parentTree = parentTree;

		return r;
	}
});

eo.wizard.SimpleProgress = Ext.extend(eo.wizard.Progress, {

	constructor: function(config) {

		var cfg = Ext.apply({
			height: 70
			,padding: 5
		}, config);

		eo.wizard.SimpleProgress.superclass.constructor.call(this, cfg);
	}

	,updateProgress: function(wizard) {

		var steps = this.getSteps(wizard);

		var getBullets = function(steps, extraCls) {
			var bullets = '';

			Ext.each(steps, function(step) {
				var cls = 'step';

				if (extraCls) cls += ' ' + extraCls;

				if (step.current) {
					cls += ' current';
				} else if (!step.done) {
					if (step.steps) {
						cls += ' hasSub';
					} else {
						if (step.unresolved) {
							cls += ' unresolved';
						} else if (step.finish) {
							cls += ' final'
						}
					}
				}

				bullets += '<li class="' + cls + '">&nbsp;</li>';
			});

			return '<ul class="progress bullets">' + bullets + '</ul>';
		}

		var html = '<div class="wizard progress simple' + (steps.hasSub ? " hasSub" : "") + '">'
			+ '<h1>' + steps.currentStep.title + '</h1>'
			+ getBullets(steps.steps)
			;

		var css = steps.currentStep.steps;
		if (css) {
			html += '<h2>' + css.currentStep.title + '</h2>'
				+ getBullets(css.steps, "sub")
		}

		html += '</div>';

		if (this.el) {
			this.update(html);
		} else {
			this.html = html;
		}
	}

	,wrap: function(wizard, panelConfig) {

		this.wizard = wizard;

		wizard.flex = 1;

		this.wizard.on("updateprogress", this.updateProgress, this);

		return Ext.apply({
			xtype: "panel"
			,items: [this, wizard]
			,layout: {
				type: "vbox"
				,align: "stretch"
			}
			,border: false
			,bodyBorder: false
		}, panelConfig);
	}

});

eo.wizard.DetailledProgress = Ext.extend(eo.wizard.Progress, {

	details: true
	,desc: false
	,explain: true

	,constructor: function(config) {

		var cfg = Ext.apply({
			width: 120
			,padding: 5
			,border: false
			,style: {
				borderRightWidth: "1px"
			}
		}, config);

		eo.wizard.DetailledProgress.superclass.constructor.call(this, cfg);
	}

	,wrap: function(wizard, panelConfig) {
		this.wizard = wizard;
		wizard.flex = 1;
		this.wizard.on("updateprogress", this.updateProgress,this);
		return Ext.apply({
			xtype: "panel"
			,items: panelConfig && panelConfig.right ? [wizard, this] : [this, wizard]
			,layout: {
				type: "hbox"
				,align: "stretch"
			}
			,border: false
			,bodyBorder: false
		}, panelConfig);
	}

	,updateProgress: function(wizard) {

		var steps = this.getSteps(wizard);

		var lvl = 0;
		var getBullets = function(steps, extraCls) {
			var bullets = '';

			Ext.each(steps.steps, function(step) {

				var cls = 'step';

				if (extraCls) cls += ' ' + extraCls;

				if (step.current) {
					cls += ' current';
				} else if (!step.done) {
					if (step.steps) {
						cls += ' hasSub';
					} else {
						if (step.unresolved) {
							cls += ' unresolved';
						}
					}
				}
				
				if (step.finish) {
					cls += ' final'
				}

				var content = step.title;

				if (step.currentParent) {
					cls += ' activeTree';
					content = '<span class="currentParent">' + content + '</span>';
				}

				if (step.steps) {
					lvl++;
					content += getBullets(step.steps, 'sub sub' + lvl);
					lvl--;
				}

				bullets += '<li class="' + cls + '">' + content + '</li>';
			});

			return '<ul class="progress bullets">' + bullets + '</ul>';
		}

		var html = '<div class="wizard progress detailled">'
			+ getBullets(steps)
			;

		if (this.details) {
			var cs = steps.currentStep;
			html += '<div class="desc">'
				+ '<hr/>';

			if (steps.parentTree.length == 0) {
				html += '<h1>' + cs.title
					+ (!this.desc || !cs.desc ? '' : '<span class="desc">' + cs.desc + '</span>')
					+ '</h1>';
			} else {
				cs = steps.absCurrent;
				var pst = steps.parentTree, ps = pst[0];

				html += '<ul>'
					+ '<li><h1>' + ps.title
					+ (!this.desc || !ps.desc ? '' : '<span class="desc">' + ps.desc + '</span>')
					+ '</h1></li>'
					;

				var end = '';
				for (var i=1,l=pst.length; i<l; i++) {
					html += '<ul><li>'
						+ '<h2>' + pst[i].title
						+ (!this.desc || !pst[i].desc ? '' : '<span class="desc">' + pst[i].desc + '</span>')
						+ '</h2>'
						+ '</li>'
						;
					end += '</ul>';
				}

				html += '<ul><li>'
					+ '<h2>' + cs.title
					+ (!this.desc || !cs.desc ? '' : '<span class="desc">' + cs.desc + '</span>')
					+ '</h2>'
					+ '</li></ul>'
					;

				html += '</ul>' + end;
			}

			if (this.explain && cs.explain) {
				html += '<p class="explain">' + cs.explain + '</p>';
			}

		}

		html += '</div>';

		if (this.el) {
			this.update(html);
		} else {
			this.html = html;
		}
	}

});

eo.wizard.progress = {

	flavors: {
		simple: eo.wizard.SimpleProgress
		,hSimple: eo.wizard.SimpleProgress
		,detailled: eo.wizard.DetailledProgress
		,vDetailled: eo.wizard.DetailledProgress
	}

	/**
	 * @private
	 * Instanciates a progress panel of the specified category, if needed, and
	 * wrap it with the given wizard.
	 * @param {mixed} wizard
	 * @param {String|Object} progress The name of a ProgressPanel flavor as a
	 * String (the panel will then be created with default options), a config
	 * object (which must include the type option to specify the flavor), or an
	 * already instanciated ProgressPanel object.
	 * @param {Object} panelConfig A config object to be applied to the returned
	 * wrapping panel.
	 */
	,wrap: function(wizard, progress, panelConfig) {
		if (progress instanceof Ext.Panel) {
			return progress.wrap(wizard);
		} else {
			if (Ext.isString(progress)) {
				progress = new this.flavors[progress]();
			} else if (Ext.isObject(progress)) {
				progress = new this.flavors[progress.type](progress);
			} else {
				throw new Error('Invalid param type: "progress" => ' + progress);
			}
			return progress.wrap(wizard, panelConfig);
		}
	}

};

eo.WizardWindow = eo.wizard.Window = Ext.extend(eo.wizard.WindowBase, {

	texts: eo.wizard.texts

	,constructor: function(config) {

		if (!config.wizard) throw new Error('Missing config option: "wizard"');

		var me = this
			,my = {
				buttons: {}
			}
			,wizard = config.wizard;

		// Instantiate wizard
		if (!(wizard instanceof eo.wizard.WizardPanel)) {
			wizard = new eo.wizard.WizardPanel(wizard);
		}

		// Buttons
		var closable = (wizard.cancellable === undefined
				|| wizard.cancellable ? true : false)

			,prevButton = my.buttons.prev = new Ext.Button({
				text: this.texts.prev
				,listeners: {
					click: {fn: wizard.prev, scope:wizard}
				}
			})
			,nextButton = my.buttons.next = new Ext.Button({
				text: this.texts.next
				,listeners: {
					click: {fn: wizard.next, scope:wizard}
				}
			})
			,cancelButton = !closable ? null : my.buttons.cancel = new Ext.Button({
				text: this.texts.cancel
				,handler: function() {me.close()}
			})
			;

		// Config
		var cfg = Ext.apply({
			title: "Wizard"
			,layout: "fit"
			,items: config.progress ?
				eo.wizard.progress.wrap(wizard, config.progress, config.progressPanelConfig)
				: wizard
			,width: 500
			,height: 400

			,closable: closable

			,buttons: Array.prototype.concat(
				(cancelButton ? [cancelButton," "," "] : [])
				,[prevButton, nextButton]
			)

		}, config);

		eo.wizard.Window.superclass.constructor.call(this, cfg);

		// --- Instance Members ---
		Ext.apply(this, my);
		this.wizard = wizard;

		// --- Listeners ---
		wizard.on({
			scope: this
			,updatebuttons: this.updateButtons
			,complete: this.onComplete
			,close: function() {
				if (!wizard.cancellable) return false;
			}
		});

		wizard.updateStep();
	}

	,deactivateContent: function() {
		this.wizard.disableNavigation();
	}

	,activateContent: function() {
		this.wizard.updateButtons();
	}

	,updateButtons: function(config) {
		var me = this;

		Ext.iterate(config, function(bName, cfg) {
			var b = me.buttons[bName];

			if (cfg.disable) b.disable();
			else if (cfg.enable) b.enable();

			if (cfg.text) b.setText(cfg.text);

			if (cfg.hide) b.hide();
			else b.show();
		});
	}

	,onComplete: function() {
		this.close();
	}
});

function testWizard() {

	var cfg = {
//		progress: "detailled"
//		progress: "simple"
		title: "This is a test wizard"
		,wizard: {
			wait: true
			
//			,onFinish: "submit"
			,onFinish: {
				action: "submit"
				,json_param: "json_form"
				,params: {
					controller: "root"
					,action: "testEcho"
					,wait: 2
				}
				,waitMessage: "Wait..."
			}
			
			,steps: {
				first: {
					stepTitle: "First"
					,desc: "So, this is a wizard you'll have to complete to get what you want!"
				}
				,form: {
					stepTitle: "FormWizard"
					,form: {
						items: [{
							fieldLabel: "Prénom"
							,name: "firstname"
							,allowBlank: false
							,value: "eric"
						}, {
							fieldLabel: "Nom"
							,name: "lastname"
							,value: "super"
						}, {
							fieldLabel: "Passer"
							,xtype: "checkbox"
							,name: "pass"
						}]
					}
					,conditions: [{
						fields: "pass"
//						,prev: false
					}, {
						fields: "lastname"
						,finish: true
					}]
					,onFinish: function(finish) {
						alert("coucou");
						finish();
					}
				}
				,mid: {
					stepTitle: "Wizard Step"
					,desc: "Example subwizard"
					,steps: {
						a: {
							stepTitle: "Étape 1/2"
							,desc: "Example description"
							,explain: "You know, you must <strong>complete</strong> this step before the show can go on..."
//							,finish: function() {
//								return true;
//							}
//							,onFinish: function(finish) {
//
////								this.disable();
//								this.wait();
//
//								setTimeout(function(){
//									finish();
//								}, 1000);
//							}
						}
						,b: {
							stepTitle: "Étape 2/2"
							,desc: "Description, still..."
							,steps: {
								ba: {
									stepTitle: "Step 2/2a and more text for a long title!"
									,desc: "Ô, my :)"
									,explain: "Straight to the <strong>goal</strong>."
								},
								bb: {
									stepTitle: "Step 2/2b"
								}
							}
						}
					}
//					,finish: true
					,onFinish: function(finish) {

						this.wait();

						var me = this;
						setTimeout(function(){
//							me.wizard.setStep("a")
							finish();
						}, 1000);
					}
					,onNext: function(next) {
						this.disable();
						next();
					}
				}
//				,panel: {
//					title: "PanelWizard"
////					,panel:
//				}
				,last: {
					stepTitle: "Last"
					,finish: true
					,onFinish: function(finish) {

//						this.disable();
						this.wait();

						this.parent.removeStep("first");

						var step = this.parent.addStep({
							stepTitle: "NouvO"
							,desc: "Brand new step, you won't believe it!"
							,name: "newOne" + Math.random()
							,prev: this.parent.getStep("first", false)
							,next: this
						});

						var ss = this.parent.setStep.createDelegate(this.parent);
						setTimeout(function(){
//							ss("mid", "a");
//							ss(step);
							finish();
						}, 1000);
					}
				}
				,last2: {
					stepTitle: "Neverland"
				}
			}
		}
	};

	(new eo.wizard.Window(Ext.apply({progress:"detailled"}, cfg))).show();
//	(new eo.wizard.Window(Ext.apply({progress:"simple"}, cfg))).show();

}

}); // deps