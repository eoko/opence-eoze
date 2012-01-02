Ext.ns('eo.wizard');

Oce.deps.wait('eo.wizard.Step', function() {

eo.wizard.SubwizardStep = Ext.extend(eo.wizard.Step, {

	constructor: function(config) {
		// panel must be created before the superclass constructor is called
		if (this.panel !== undefined) throw new Error(
			'Illegal config option for subwizard step: "panel"'
		);

		var wizard;
		if (config.wizard instanceof eo.wizard.WizardPanel) {
			wizard = this.panel = config.wizard;
		} else {
			wizard = this.panel = new eo.wizard.WizardPanel(config);
		}

		wizard.parentStep = this;

		this.superclass = eo.wizard.SubwizardStep.superclass;

		this.wizard = wizard;
		this.superclass.constructor.call(this, config);

		this.wizard = wizard;

		this.getStep = wizard.getStep.createDelegate(wizard);
	}

//	,getRootWizard: function() {
//		return this.wizard.getRootWizard();
//	}
//
	,setParent: function(parent) {
		this.superclass.setParent.call(this, parent);

		Ext.apply(this.wizard, {
			wait: this.parent.wait.createDelegate(this.parent)
			,enable: this.enable.createDelegate(this)
			,disable: this.disable.createDelegate(this)
			,finish: this.superclass.goNext.createDelegate(this)
		});

		this.wizard.getRootWizard = function() { return parent.getRootWizard(); };
	}

	,canFinish: function() {
		var cs = this.wizard.currentStep;
		if (cs && !cs.canFinish()) return false;
		return this.superclass.canFinish.call(this);
	}

	,hasNext: function() {
		var cs = this.wizard.currentStep;
		if (cs) {
			return cs.hasNext()
				|| (
					cs.canFinish()
					&& this.superclass.hasNext.call(this)
				);
		} else {
			return this.superclass.hasNext.call(this);
		}
	}
	,hasPrev: function() {
		var cs = this.wizard.currentStep;
		return (cs && cs.hasPrev()) || this.superclass.hasPrev.call(this);
	}

	,goNext: function() {
		if (this.wizard.currentStep) {
			this.wizard.next();
			// step must be updated (since setStep is not called, it won't be done otherwizse)
			this.parent.updateStep();
		} else {
			this.superclass.goNext.call(this);
		}
	}
	,goPrev: function() {
		if (this.wizard.currentStep && this.wizard.currentStep.hasPrev()) {
			this.wizard.prev();
			// step must be updated (since setStep is not called, it won't be done otherwizse)
			this.parent.updateStep();
		} else {
			this.superclass.goPrev.call(this);
		}
	}

	,findField: function(name) {
		return this.wizard.findField(name);
	}

	,getStepsPath: function() {
		return this.wizard.getStepsPath();
	}

});

}); // deps closure