<?php

namespace eoko\cqlix\generator;
//use eoko\cqlix\Relation;

use eoko\util\Arrays;

use UnsupportedOperationException;

use ModelRelationReferedByOneOnMultipleFields;
use ModelRelationReferedByMany;
use ModelRelationByAssoc;
use ModelRelationHasOne;
use ModelRelationHasMany;
use ModelRelationHasOneByAssoc;
use ModelRelationIndirectHasMany;
use ModelRelationIndirectHasOne;
use ModelRelationIndirectHasOneMirror;
use ModelRelation;
use ModelRelationCache;
use ModelRelationByReference;
use ModelRelationHasReference;
use ModelRelationReferencesOne;
use ModelRelationReferedByOne;
use ModelRelationReferredByOneAssoc;

class RelationTemplates {
	public static function load() {
		return true;
	}
}

abstract class TplRelation {
	
	public $alias;
	public $localDBTableName;
	public $targetDBTableName;

	/** @var TplRelation */
	private $reciproque;

	public $reciproqueName;
	public $reciproqueConfig;

	public $onDeleteAction = null;
	public $onUpdateAction = null;
	
	public $uniqueBy = null;
	
	public $config;

	function __construct($alias, $localTableName, $targetTableName, $reciproque) {
//		dump_trace();
		$this->alias = $alias;
		$this->localDBTableName = $localTableName;
		$this->targetDBTableName = $targetTableName;
		$this->reciproque = $reciproque;
	}

	public function __toString() {
		$class = get_relative_classname(get_class($this));
		return "Relation $class from $this->localDBTableName to $this->targetDBTableName as $this->alias";
	}

	public function getClass() {
		return substr(get_class($this), 3);
	}

	protected function getInfoClass() {
		return 'ModelRelationInfo';
	}

	public function getInfoDeclaration($additionalParams = '', $closing = true) {

		$table = $this->targetDBTableName !== $this->localDBTableName ?
				$this->getTargetTableName() . 'Proxy::get()'
				: '$this';
		
		$config = array(
			'name'     => $this->getName(),
		);

		if ($this->uniqueBy) {
			$config['uniqueBy'] = $this->uniqueBy;
		}
		if ($this->onDeleteAction) {
			$config['onDeleteAction'] = '%onDeleteAction%';
		}
		
		$config = var_export($config, true);
		$config = str_replace("array (", "array(", $config);
		$config = str_replace("\n  ", "\n\t\t\t\t\t", $config);
		$config = str_replace("\n)", "\n\t\t\t\t)", $config);
		
		if ($this->onDeleteAction) {
			$config = str_replace("'%onDeleteAction%'", $this->exportOnDeleteAction(), $config);
		}

		ob_start();
?>
new <?php echo $this->getInfoClass() ?>(<?php echo $config ?>
, $this, <?php echo $this->getTargetTableName() ?>Proxy::get()<?php
if ($additionalParams !== '') echo ', ' . $additionalParams; ?>
<?php
/*
?>
new <?php echo $this->getInfoClass() ?>('<?php echo $this->getName() ?>
', $this, <?php echo $this->getTargetTableName() ?>::getInstance()<?php
if ($additionalParams !== '') echo ', ' . $additionalParams; ?>
<?php
*/
		return ob_get_clean() . ($closing ? ')' : '');
	}

	public function getDeclaration($head = true, $additionalParams = '', $closing = true) {

		if ($this->alias === null) {
			$this->alias = $this->makeAlias();
		}
		$name = $this->getName();

		ob_start();

		$tabs = "\t\t\t\t\t";
		$rTabs = PHP_EOL . $tabs;
?>
new <?php echo $this->getClass() ?>(<?php echo $head ? $rTabs : '' ?>
'<?php echo $this->getName() ?>', <?php echo 'self::getInstance()'//$this->getLocalTableName() ?>
, <?php echo $this->getTargetTableName() ?>::getInstance(), <?php echo $head ? $rTabs
. $this->reciproque->getDeclaration(false) : 'null' ?>
<?php if ($additionalParams !== '') echo ($head ? ',' . $rTabs : ', ') . $additionalParams; ?>
<?php
		return ob_get_clean() . ($closing ? ')' : '');
	}

	protected function makeAlias() {
		return null;
	}

	protected function formatAlias($alias) {
		return $alias;
	}

	public function getName() {

//		echo "getName(): $this->targetDBTableName: $this->alias, $this->prefix\n";

//		echo get_class($this) . ' => ';
//		echo "getName(): $this->targetDBTableName: $this->alias, $this->prefix\n";
		
		if (!$this->alias) {
			$this->alias = $this->makeAlias();
		}
		
		if ($this->alias === null) {
			// filter out local table prefix in target table name
			$regex = '/^(?:' . preg_quote($this->localDBTableName)
					. '|' . preg_quote(NameMaker::singular($this->localDBTableName)) . ')'
					. '_(?P<target>.+)$/';
			if (preg_match($regex, $this->targetDBTableName, $matches)) {
				$target = $matches['target'];
			} else {
				$target = $this->targetDBTableName;
			}
			
			if ($this instanceof ModelRelationHasOne) {
				$name = NameMaker::modelFromDB($target);
			} else if ($this instanceof ModelRelationHasMany) {
				$name = NameMaker::pluralizeModel(NameMaker::modelFromDB($target));
			} else {
				print_r($this);
				throw new IllegalStateException(get_class($this) . ' => ' . $this);
			}
			return $this->formatAlias($name);
		} else {
			return $this->alias;
		}
	}

	public function equals(TplRelation $other, $testReciproque = true) {
		return get_class($this) === get_class($other)
			&& $this->targetDBTableName === $other->targetDBTableName
			&& $this->getName() === $other->getName()
			&& (!$testReciproque
					|| (($this->reciproque !== null || $other->reciproque === null)
						|| $this->reciproque->equals($other->reciproque, false)));
	}
	
	public function getTargetType() {
		if ($this instanceof ModelRelationHasOne) {
			return NameMaker::modelFromDB($this->targetDBTableName);
		} else if ($this instanceof ModelRelationHasMany) {
			return 'array';
		} else {
			print_r($this);
			throw new IllegalStateException(get_class($this) . ' => ' . $this);
		}
	}

	public function getTargetModelName() {
		return NameMaker::modelFromDB($this->targetDBTableName);
	}

	public function getTargetTableName() {
		return NameMaker::tableFromDB($this->targetDBTableName);
	}

	public function getLocalTableName() {
		return NameMaker::tableFromDB($this->localDBTableName);
	}

	public function getAlias() {
	 return $this->alias;
	}

	public function setAlias($alias) {
	 $this->alias = $alias;
	}

	public function setTargetTableName($targetTableName) {
	 $this->targetDBTableName = $targetTableName;
	}

	public function setLocalTableName($dbTableName) {
		$this->localDBTableName = $dbTableName;
	}

	public function getReciproque() {
		return $this->reciproque;
	}

	public function setReciproque($reciproque) {
		$this->reciproque = $reciproque;
	}

	public function configure(array $config = null) {

		$this->config = Arrays::apply($this->config, $config);
		
		if (isset($this->config['onDelete'])) {
			$this->onDeleteAction = $config['onDelete'];
		}
		
		if (isset($this->config['uniqueBy'])) {
			$this->uniqueBy = $this->config['uniqueBy'];
		}
	}
	
	public function exportConfig($pre = '') {
		$export = var_export($this->config, true);
		$export = str_replace("\n", "\n" . $pre, $export);
		$export = str_replace('array (', 'array(', $export);
		return $export;
	}
	
	public function exportOnDeleteAction() {
		switch ($this->onDeleteAction) {
			case 'NOTHING':
			case 'DELETE':
			case 'SET_NULL':
			case 'RESTRICT':
				return "ModelRelationInfoHasReference::ODA_$this->onDeleteAction";
			default:
				throw new UnsupportedOperationException("onDeleteAction: $this->onDeleteAction");
		}
	}

}

/**
 * Marker interface for Relations representing a direct reference from a foreign
 * table to the parent table.
 */
interface TplRelationIsRefered {

}

abstract class TplRelationByReference extends TplRelation {

	public $referenceField;
	public $prefix;

	function __construct($localTableName, $targetTableName, $alias, $reciproque,
			$referenceField, $prefix) {

		parent::__construct($alias, $localTableName, $targetTableName, $reciproque);
		$this->referenceField = $referenceField;
		$this->prefix = $prefix;
	}

	protected function  getInfoClass() {
		return 'ModelRelationInfoByReference';
	}

	public function getDeclaration($head = true, $ignored = '', $ignored2 = true) {
		return parent::getDeclaration($head, "'{$this->referenceField}'");
	}

	public function getInfoDeclaration($additionalParams = '', $closing = true) {
		return parent::getInfoDeclaration("'$this->referenceField'");
	}

	public function equals(TplRelation $other, $testReciproque = true) {
		return parent::equals($other, $testReciproque)
				&& $this->referenceField === $other->referenceField;
	}
	public function getReferenceField() {
		return $this->referenceField;
	}

	public function setReferenceField($referenceField) {
	 $this->referenceField = $referenceField;
	}

	public function getPrefix() {
	 return $this->prefix;
	}

	public function setPrefix($prefix) {
	 $this->prefix = $prefix;
	}


}

class TplRelationReferencesOne extends TplRelationByReference
		implements ModelRelationHasOne {

	protected function getInfoClass() {
		return 'ModelRelationInfoReferencesOne';
	}

	public function __toString() {
		$class = get_relative_classname(get_class($this));
		return "Indirect $class relation from $this->localDBTableName on $this->referenceField "
				. "to $this->targetDBTableName as $this->alias";
	}

	protected function makeAlias() {
//		if (\property_exists($this, 'referencingAlias') && $this->referencingAlias !== null) {
		if (isset($this->referencingAlias) && $this->referencingAlias !== null) {
			return $this->referencingAlias;
		} else if ($this->prefix === null) {
			return null;
		} else {
			if (preg_match('/^(.+)__(.+?)$/', trim($this->prefix, '_'), $m)) {
				$this->reciproqueName = NameMaker::camelCase($m[2], true);
				return NameMaker::camelCase($m[1], true);
			} else {
				if (substr($this->prefix, -1) === '_') {
					return NameMaker::camelCase(substr($this->prefix, 0, -1), true);
				} else {
					return NameMaker::camelCase($this->prefix, true) 
							. NameMaker::modelFromDB($this->targetDBTableName);
				}
			}
		}
	}
}

class TplRelationReferedByOne extends TplRelationReferencesOne implements TplRelationIsRefered {

	public $constraintName, $referencingTableName, $referencedTableName;
	protected $referencingAlias;

	protected function getInfoClass() {
		return 'ModelRelationInfoReferedByOne';
	}
	
	public function setReferencingAlias($referencingAlias) {
		return $this->referencingAlias = $referencingAlias;
	}

//	// --- with constraints
//	protected function makeAlias() {
////		$this->defaultConstraintName = "fk_{$this->referencingTableName}_$this->referencedTableName";
////		if (substr($this->constraintName, 0, strlen($this->defaultConstraintName)) === $this->defaultConstraintName) {
////			if ($this->referencingAlias === null) {
////				// no alias
////				return null;
////			} else {
////				return NameMaker::modelFromDB($this->referencingTableName) . 'OfWhichIs'
////					. $this->referencingAlias;
////			}
////		} else {
////			// Remove the fk_ prefix if present
////			if (substr($this->constraintName, 0, 3) === 'fk_')
////					$this->constraintName = substr($this->constraintName, 3);
////			// Use the constraint name as alias
////			return NameMaker::camelCase($this->constraintName);
////		}
//	}
}

class TplRelationReferedByMany extends TplRelationByReference
		implements ModelRelationHasMany, TplRelationIsRefered {

	public $constraintName, $referencingTableName, $referencedTableName;
	
	protected $referencingAlias;

	public function setReferencingAlias($referencingAlias) {
		return $this->referencingAlias = $referencingAlias;
	}

	protected function makeAlias() {
		if ($this->referencingAlias !== null) {
			return NameMaker::plural($this->referencingAlias);
		} else if ($this->prefix !== null) {
			if (substr($this->prefix, -1) === '_') {
				return NameMaker::camelCase(substr($this->prefix, 0, -1), true);
			} else {
				return NameMaker::camelCase($this->prefix, true) . NameMaker::pluralizeModel(
						NameMaker::modelFromDB($this->targetDBTableName));
			}
		}
		return null;
	}

	protected function getInfoClass() {
		return 'ModelRelationInfoReferedByMany';
	}
}

abstract class TplRelationByAssoc extends TplRelation {

	public $assocTableName;
	public $localForeignKey;
	public $otherForeignKey;

	function __construct($alias, $localTableName, $targetTableName, $reciproque,
			$assocTableName, $localForeignKey, $otherForeignKey) {

		parent::__construct($alias, $localTableName, $targetTableName, $reciproque);

		$this->assocTableName = $assocTableName;
		$this->localForeignKey = $localForeignKey;
		$this->otherForeignKey = $otherForeignKey;
	}

	public function __toString() {
		$class = get_relative_classname(get_class($this));
		return "Indirect $class relation from $this->localDBTableName on $this->localForeignKey "
				. "to $this->targetDBTableName on $this->otherForeignKey through "
				. "$this->assocTableName as $this->alias";
	}

	protected function getInfoClass() {
		return 'ModelRelationInfoByAssoc';
	}

	public function getInfoDeclaration($additionalParams = '', $closing = true) {
		$assocTable = NameMaker::tableFromDB($this->assocTableName);
		return parent::getInfoDeclaration("{$assocTable}Proxy::get(), "
				. "'$this->localForeignKey', '$this->otherForeignKey'");
//		return parent::getInfoDeclaration("{$this->assocTableName}::getInstance(), "
//				. "'{$this->localForeignKey}', '{$this->otherForeignKey}'");
	}

	public function getDeclaration($head = true, $ignored = '', $ignored2 = true) {
		$assocTable = NameMaker::tableFromDB($this->assocTableName);
		return parent::getDeclaration($head,
				"{$assocTable}::getInstance(), '$this->localForeignKey', '$this->otherForeignKey'"
				);
	}

	public function equals(TplRelation $other, $testReciproque = true) {
		return parent::equals($other, $testReciproque)
				&& $this->assocTableName === $other->assocTableName
				&& $this->localForeignKey === $other->localForeignKey
				&& $this->otherForeignKey === $other->otherForeignKey;
	}

	public function formatAlias($alias) {
		return 'AssocTo' . $alias;
	}
}

class TplRelationIndirectHasMany extends TplRelationByAssoc
		implements ModelRelationHasMany {

}

class TplRelationIndirectHasOne extends TplRelationByAssoc
		implements ModelRelationHasOne {


}
