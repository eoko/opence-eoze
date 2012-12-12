/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 9 janv. 2012
 */

Ext.override(Ext.grid.View, {

	// Fixes fitColumns. Multiple fitting when one pass includes a grid width that is
	// less that the fixed columns total width broked rapport between flexed columns.
	fitColumns : function(preventRefresh, onlyExpand, omitColumn) {
		var grid          = this.grid,
			colModel      = this.cm,
			totalColWidth = colModel.getTotalWidth(false),
			gridWidth     = this.getGridInnerWidth(),
			extraWidth    = gridWidth - totalColWidth,
			columns       = [],
			extraCol      = 0,
			width         = 0,
			colWidth, fraction, i;
        
		// not initialized, so don't screw up the default widths
		if (gridWidth < 20 || extraWidth === 0) {
			return false;
		}
        
		var visibleColCount = colModel.getColumnCount(true),
			totalColCount   = colModel.getColumnCount(false),
			adjCount        = visibleColCount - (Ext.isNumber(omitColumn) ? 1 : 0);
        
		if (adjCount === 0) {
			adjCount = 1;
			omitColumn = undefined;
		}
		
		//FIXME: the algorithm used here is odd and potentially confusing. Includes this for loop and the while after it.
		for (i = 0; i < totalColCount; i++) {
			if (!colModel.isFixed(i) && i !== omitColumn) {
				colWidth = colModel.getColumnWidth(i);
				columns.push(i, colWidth);
				
				if (!colModel.isHidden(i)) {
					extraCol = i;
					width += colWidth;
				}
			}
		}
        
		fraction = (gridWidth - colModel.getTotalWidth()) / width;
        
		while (columns.length) {
			colWidth = columns.pop();
			i        = columns.pop();
            
			colModel.setColumnWidth(i, Math.max(grid.minColumnWidth, Math.floor(colWidth + colWidth * fraction)), true);
		}
        
		//this has been changed above so remeasure now
		totalColWidth = colModel.getTotalWidth(false);

		// rx: the following is flawed beyond repair (and seemingly useless)
//		if (totalColWidth > gridWidth) {
//			var adjustCol = (adjCount == visibleColCount) ? extraCol : omitColumn,
//				newWidth  = Math.max(1, colModel.getColumnWidth(adjustCol) - (totalColWidth - gridWidth));
//            
//			colModel.setColumnWidth(adjustCol, newWidth, true);
//		}
        
		if (preventRefresh !== true) {
			this.updateAllColumnWidths();
		}
        
		return true;
	}
	
});