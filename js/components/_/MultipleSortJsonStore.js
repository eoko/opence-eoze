
Oce.MultipleSortJsonStore = Ext.extend(Ext.data.JsonStore, {

	constructor: function(config) {
		this.addEvents({
			'singlesort': true
		});
		Oce.MultipleSortJsonStore.superclass.constructor.call(this, config);
	},

	multiSort: function(sorters, direction) {

		// Must clone sorters to avoid depending on an object that could be modified externally
		sorters = Ext.clone(sorters, true);

		if (sorters.length < 2) {
			this.singleSort(sorters[0].field, sorters[0].direction);
			return;
		}

		this.hasMultiSort = true;
		direction = direction || "ASC";

		//toggle sort direction
		if (this.multiSortInfo && direction == this.multiSortInfo.direction) {
			direction = direction.toggle("ASC", "DESC");
		}

		// 2012-12-13 22:05
		// Avoid applying the same sort twice
		if (this.multiSortInfo) {
			if (Ext.encode(this.multiSortInfo.sorters) === Ext.encode(sorters)) {
				return;
			}
		}

		/**
         * @property multiSortInfo
         * @type Object
         * Object containing overall sort direction and an ordered array of sorter configs used when sorting on multiple fields
         */
		this.multiSortInfo = {
			sorters  : sorters,
			direction: direction
		};

		if (this.remoteSort) {
//			this.remoteMultiSort(sorters[0].field, sorters[0].direction);
			this.applyRemoteSort(sorters.field, sorters.direction);

		} else {
			this.applySort();
			this.fireEvent('datachanged', this);
		}
	},

	// Overriden to clear the multisort
	singleSort: function(fieldName, dir) {
		if (!dir) {
			if (this.sortInfo && this.sortInfo.field === fieldName) {
				dir = this.sortInfo.dir;
			} else {
				dir = 'ASC';
			}
		}
		if (false !== this.fireEvent('beforesinglesort', this, fieldName, dir)) {
			delete this.sortInfo.json;
			this.hasMultiSort = false;
			Oce.MultipleSortJsonStore.superclass.singleSort.apply(this, arguments);
			this.fireEvent('singlesort', this, fieldName, this.sortInfo.direction)
		}
	},

	applyRemoteSort: function(fieldName, dir) {

        var sorters = this.multiSortInfo.sorters;

		if (!this.multiSortInfo || sorters.length < 2) {
			this.singleSort(fieldName, dir);
			return;
		}

		sortToggle = this.sortToggle;

//		var field = this.fields.get(fieldName);
//		if (!field) return false;
//
//		var name   = field.name,
//		sortInfo   = this.sortInfo || null,
//		sortToggle = this.sortToggle ? this.sortToggle[name] : null;
//
//		if (!dir) {
//			if (sortInfo && sortInfo.field == name) { // toggle sort dir
//				dir = (this.sortToggle[name] || 'ASC').toggle('ASC', 'DESC');
//			} else {
//				dir = field.sortDir;
//			}
//		}
//
//		this.sortToggle[name] = dir;
//		this.sortInfo = {
//			field: name,
//			direction: dir
//		};
//		this.hasMultiSort = false;

//		this.lastOptions.params.json = encodeURIComponent(Ext.util.JSON.encode({
//			sort: this.multiSortInfo.sorters
//		}))

//		this.sortInfo = null;
		this.sortInfo = {
//			json: encodeURIComponent(Ext.util.JSON.encode({
			json: {
				sort: this.multiSortInfo.sorters
			}
		}

		Ext.each(this.multiSortInfo.sorters, function(sorter) {
			this.sortToggle[sorter.field] = sorter.direction
		}, this)

		if (!this.load(this.lastOptions)) {
			if (sortToggle) {
				this.sortToggle = sortToggle;
			}
//				if (sortInfo) {
//					this.sortInfo = sortInfo;
//				}
		}
	}

	,load : function(options) {
		var previousOptions = this.lastOptions;
        options = Ext.apply({}, options);
        this.storeOptions(options);
        if(this.sortInfo && this.remoteSort){
            var pn = this.paramNames;
			options.params = Ext.apply({}, options.params);
			if ('json' in this.sortInfo) {
				options.params.json_sort = encodeURIComponent(
					Ext.util.JSON.encode(this.sortInfo.json.sort)
				);
			} else {
				options.params[pn.sort] = this.sortInfo.field;
				options.params[pn.dir] = this.sortInfo.direction;
			}
        }
        try {
			this.lastOptions = {
				params: Ext.apply({}, options.params)
			}
//			if (options.params.json) {
//				options.params.json = encodeURIComponent(
//					Ext.util.JSON.encode(options.params.json)
//				);
//			}
            return this.execute('read', null, options);
        } catch(e) {
			this.lastOptions = previousOptions;
            this.handleException(e);
            return false;
        }
    }

	,getSortState: function() {
		if (this.hasMultiSort) {
//			return this.multiSortInfo;
			return {
				field: this.multiSortInfo.sorters[0].field
				,direction: this.multiSortInfo.sorters[0].direction
			};
		} else {
			return Oce.MultipleSortJsonStore.superclass.getSortState.apply(this, arguments);
		}
	}

})
