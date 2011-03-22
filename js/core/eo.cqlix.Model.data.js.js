Oce.deps.wait("eo.cqlix.Model", function() {
	
	eo.cqlix.Model.addAspect('data', eo.Class({
		
		constructor: function(model) {
			this.model = model;
		}
		
		,createDataStore: function(config) {
			throw new Error("Not implemented yet");
		}
		
	}));
	
}); // deps closure