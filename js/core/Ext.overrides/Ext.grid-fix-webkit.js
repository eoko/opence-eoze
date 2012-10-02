/**
 * @author http://www.sencha.com/forum/showthread.php?198124-Grids-are-rendered-differently-in-upcoming-versions-of-Google-Chrome/page6
 * @since 27 sept. 2012
 */

if (!Ext.isDefined(Ext.webKitVersion)) {
    Ext.webKitVersion = Ext.isWebKit ? parseFloat(/AppleWebKit\/([\d.]+)/.exec(navigator.userAgent)[1], 10) : NaN;
}

/*
 * Box-sizing was changed beginning with Chrome v19.  For background information, see:
 * http://code.google.com/p/chromium/issues/detail?id=124816
 * https://bugs.webkit.org/show_bug.cgi?id=78412
 * https://bugs.webkit.org/show_bug.cgi?id=87536
 * http://www.sencha.com/forum/showthread.php?198124-Grids-are-rendered-differently-in-upcoming-versions-of-Google-Chrome&p=824367
 */
if (Ext.isWebKit && Ext.webKitVersion >= 535.2) { // probably not the exact version, but the issues started appearing in chromium 19
    Ext.override(Ext.grid.ColumnModel, {
        getTotalWidth: function (includeHidden) {
            if (!this.totalWidth) {
                var boxsizeadj = 2;
                this.totalWidth = 0;
                for (var i = 0, len = this.config.length; i < len; i++) {
                    if (includeHidden || !this.isHidden(i)) {
                        this.totalWidth += (this.getColumnWidth(i) + boxsizeadj);
                    }
                }
            }
            return this.totalWidth;
        }
    });


    Ext.onReady(function() {
        Ext.get(document.body).addClass('ext-chrome-fixes');
        Ext.util.CSS.createStyleSheet('@media screen and (-webkit-min-device-pixel-ratio:0) {.x-grid3-cell{box-sizing: border-box !important;}}', 'chrome-fixes-box-sizing');
    });
}