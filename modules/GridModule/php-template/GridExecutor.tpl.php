namespace <?php echo $namespace ?>;

use <?php echo $tableName ?>;

class <?php echo $class ?> extends <?php echo $extend ?> {

	/** @var \ModelTable */
	protected $table;

	protected $modelName = '<?php echo $modelName ?>';
	
	protected $title = '<?php echo $title ?>';

	protected function construct() {
		parent::construct();
		$this->table = <?php echo $tableName ?>::getInstance();
	}

<?php if (isset($hasMergeMembers) && $hasMergeMembers): ?>
	protected $hasMergeMembers = true;
	protected $mergeMembersTable = '<?php echo $mergeMembersTable ?>';
<?php else: ?>
	protected $hasMergeMembers = false;

<?php endif ?>
	protected static function getModelName() {
		return $this->modelName;
	}

<?php if (isset($autocomplete)): ?>
	protected function getAutoCompleteSelectString() {
		return '<?php echo $autocomplete ?>';
	}

<?php elseif (isset($label)): ?>
	protected function getAutoCompleteSelectString() {
		return '<?php echo $label ?>';
	}

<?php endif ?>
<?php if (isset($tabPages)): ?>
	protected function getFormPageNames() {
		return array(<?php echo $tabPages ?>);
	}

<?php endif ?>
<?php if (isset($relationSelectionModes)): ?>
	protected function getRelationSelectionModes($mode = 'form') {
		$selModes = <?php echo $relationSelectionModes ?>;
		return $selModes[$mode];
	}

<?php endif ?>
<?php if (isset($selectionModeForRelations)): ?>
	protected function getSelectionModeForRelations($mode = 'form') {
		$selModes = <?php echo $selectionModeForRelations ?>;
		return $selModes[$mode];
	}

<?php endif ?>
<?php if (isset($add_mod_autoVals)): ?>
	protected function addExtraSetters(&$form, &$setters, &$missingFields) {
<?php list($autoVal, $unset) = $autoVal ?>
<?php if (isset($autoVal)): ?>
		$setters['<?php echo $name ?>'] = <?php echo $autoVal ?>;
<?php endif ?>
<?php if (isset($unset)): ?>
		unset($missingFields['<?php echo $unset ?>']);
<?php endif ?>
	}

<?php endif ?>
}