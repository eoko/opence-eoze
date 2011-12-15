<?php

/**
 * This file is used to fill the gap in IDE and doc generator class hierarchy.
 * It is not actually used in runtime code.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 déc. 2011
 */

namespace rhodia\modules\GridModule {
	class GridBase extends \eoko\modules\GridModule\GridExecutor {}
}

namespace rhodia\modules\contacts {
	class GridBase extends \rhodia\modules\GridModule\Grid {}
}

namespace rhodia\modules\members {
	class GridBase extends \rhodia\modules\GridModule\Grid {}
}

namespace rhodia\modules\seasons {
	class GridBase extends \rhodia\modules\GridModule\Grid {}
}
