/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 17/08/11 19:16
 */

Ext.ns("Ext.ux.grid").DragDropOrderablePlugin = Ext.extend(Object, {

	constructor: function(config) {
		Ext.apply(this, config);
	}
	
	,init: function(grid) {
		this.grid = grid;
		
		grid.enableDragDrop = true;
		
		Ext.applyIf(grid, {
			
			ddText: "Placer cet item"

//			,sm: new Ext.grid.RowSelectionModel({
//				singleSelect: true
//				,listeners: {
//					scope: grid
//					,beforerowselect: function(sm,i,ke,row) {
//						this.ddText = row.data["title"];
//					}
//				}
//			})
		});

		var tf = this.titleField,
			tfn = this.titleRenderer,
			sm = grid.getSelectionModel();
		if (sm && sm instanceof Ext.grid.RowSelectionModel) {
			if (tf) {
				sm.on({
					beforerowselect: function(sm,i,ke,row) {
						grid.ddText = row.get(tf);
					}
				})
			} else if (tfn) {
				sm.on({
					beforerowselect: function(sm,i,ke,row) {
						grid.ddText = tfn(row);
					}
				})
			}
		}
		
		if (!grid.ddGroup || grid.ddGroup === "GridDD") {
			grid.ddGroup = grid.id;
		}
		
		grid.on({
			scope: this
			,afterrender: function() {
				this.grid_initDropTarget.call(grid, this);
			}
		});
	}
	
	// private
	,grid_initDropTarget: function(pg) {
		var sm = this.getSelectionModel(),
			s = this.store;
		this.ddTarget = new Ext.dd.DropTarget(this.getView().mainBody, {
			ddGroup: this.ddGroup
			,notifyDrop: function(dd, e, data) {
				var rows = sm.getSelections();
				var ci = dd.getDragData(e).rowIndex;
				if (sm.hasSelection()) {
					for (var i = 0; i < rows.length; i++) {
						s.remove(s.getById(rows[i].id));
						s.insert(ci,rows[i]);
					}
					// order field
					var of = pg.orderField;
					if (of) (function() {
						var i = 0;
						s.each(function(r) {
							r.data[of] = i++;
						});
						s.fireEvent("datachanged", s);
					})();
					sm.selectRecords(rows);
				}
			}
		});
	}
});

//use.OrderableGridPanel = (function() { 
//		
//		var sp = Ext.grid.EditorGridPanel,
//			spp = sp.prototype;
//		
//		var OrderableGridPanel = Ext.extend(sp, {
//			
//			enableDragDrop: true
//			,ddText: "Placer cet item"
//			
//			,constructor: function(config) {
//				spp.constructor.apply(this, arguments);
//				this.on("afterrender", this.initDropTarget, this);
//			}
//			
//			,initComponent: function() {
//				
//				if (!this.ddGroup || !this.ddGroup === "GridDD") {
//					this.ddGroup = this.id;
//				}
//				
//				Ext.applyIf(this, {
//					sm: new Ext.grid.RowSelectionModel({
//						singleSelect: true
//						,listeners: {
//							scope: this
//							,beforerowselect: function(sm,i,ke,row) {
//								this.ddText = row.data["title"];
//							}
//						}
//					})
//				});
//				
//				spp.initComponent.call(this);
//			}
//
//		});
//	})();
