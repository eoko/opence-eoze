<?php

namespace eoko\modules\GridModule;

use eoko\module\executor\JsonExecutor;
use eoko\util\Json;
use eoko\file\FileType;
use eoko\template\HtmlTemplate;
use eoko\cqlix\table_filters\TableHasFilters;
use eoko\database\Database;
use eoko\util\Strings;
use eoko\module\ModuleManager;

use eoko\cqlix\Exception\ModelAlreadyDeletedException;

use Model;
use ModelField;
use ModelTable;
use ModelTableQuery;
use ModelColumn;
use Query;
use ModelRelationInfo;
use ModelRelationInfoReferencesOne;
use Logger;
use Request;
use ExceptionHandler;
use UserSession;
use User;

use Exception;
use UserException;
use IllegalStateException, UnsupportedActionException, SystemException;
use IllegalArgumentException;
use ModelSaveException;

use eoko\util\GlobalEvents;

abstract class GridExecutor extends JsonExecutor {

	/** @var \ModelTable */
	protected $table;
	
	protected $formTemplatePath = 'formTemplates';

	private $plugins;
	
	protected function construct() {
		parent::construct();
		$this->initPlugins();
		GlobalEvents::fire(get_class(), 'initPlugins', $this);
	}
	
	protected function initPlugins() {}
	
	public function addPlugin(GridExecutor\Plugin $plugin) {
		$plugin->configure($this, $this->table, $this->request);
		$this->plugins[] = $plugin;
	}
	
	public function executeAction($name, &$returnValue) {
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				if ($plugin->executeAction($name, $returnValue)) {
					return true;
				}
			}
		}
		return parent::executeAction($name, $returnValue);
	}

	function get_module() {
		// UNTESTED
		$override = $this->request->has('name') ? null : array(
			'name' => $this->module->getName()
		);
		$this->forward("{$this->module->getName()}.jsfile", 'get_module', $override);
	}

	protected function beforeAction() {
		parent::beforeAction();
		// TODO SECURITY real security management...
		UserSession::requireLoggedIn();
	}
	
	protected final function callInTransaction($method) {
		if (!method_exists($this, $method)) {
			throw new IllegalArgumentException('Missing method: ' . get_class() . "::$method");
		}
		$db = DataBase::getDefaultConnection();
		if (!$db->beginTransaction()) {
			Logger::get($this)->warn('Transactions not supported');
		} else {
			Logger::get($this)->info('Beginning data transaction');
		}
		try {
			$result = $this->$method();
			if (!$db->commit()) {
				throw new IllegalStateException('Data commit failed');
			}
			return $result;
		} catch (Exception $ex) {
			if ($db->rollBack()) {
				Logger::get($this)->info('Data modifications have been rolled back');
			} else {
				Logger::get($this)->error('Data modifications cannot be rolled back');
			}
			throw $ex;
		}
	}
	
	  //////////////////////////////////////////////////////////////////////////
	 // ADD
	////////////////////////////////////////////////////////////////////////////

	public final function add() {
		try {
			return $this->callInTransaction('doAdd');
		} catch (\PDOException $ex) {
			return $this->handleSqlError($ex->errorInfo);
		} catch (\SqlException $ex) {
			return $this->handleSqlError($ex->errorInfo);
		}
	}
	
	protected function handleSqlError($errorInfo) {
		list($sqlState, $errorCode, $message) = $errorInfo;
		switch ($sqlState) {
			case '23000':
				if (preg_match(
						'/^Duplicate entry \'(?P<value>[^\']+)\' for key \'(?P<key>[^\']+)/',
						$message, $matches)
						&& $matches['key'] !== 'PRIMARY') {
					
					if (($field = $this->table->getField($matches['key']))) {
						$label = strtolower($field->getMeta()->label);
					}

					$this->errorMessage = "La valeur '$matches[value]' "
							. ($label ? "pour le champ <em>$label</em> " : null)
							. 'doit être unique.';
				} 
				break;
		}
		return false;
	}

	protected function doAdd() {

		if ($this->request->hasSub('data')) {
			$request = $this->request->getSub('data');
		} else {
			$request = $this->request->requireSub('form');
		}
		
		if (false === $this->prepareAddData($request)) {
			return false;
		}

		$setters = array();
		$missingFields = array();

		$cols = $this->table->getColumns(ModelColumn::OP_CREATE, true);

		foreach ($cols as $col) {
			$this->add_getField($request, $col, $setters, $missingFields);
		}

		foreach ($this->table->getRelationsInfo() as $rel) {
//			$rel instanceof ModelRelationInfo;
			if ($request->has($rel->name)) {
				$setters[$rel->name] = $request->getRaw($rel->name);

				if ($rel instanceof ModelRelationInfoReferencesOne) {
					// the field will be set by the relation
					unset($missingFields[$rel->referenceField]);
				}
			}
		}

		foreach ($request->getAll() as $k => $in) {
			if (strstr($k, '->') !== false) {
				$setters[$k] = $in;
			}
		}

		$this->addExtraSetters($request, $setters, $missingFields);
		$this->add_extra($request, $setters, $missingFields);

		if (count($missingFields) > 0) {
			$msg = '<p>' . lang('Les champs suivant doivent obligatoirement être renseignés :') . '</p>';
			$msg .= '<ul>';
			foreach ($missingFields as $f) $msg .= "<li>$f</li>";
			$msg .= '</ul>';
//			throw new UserException($msg, lang('Formulaire Incomplet'));
			foreach ($missingFields as $f) $errors[$f] = lang('Champ obligatoire');

			$this->getResponse()
					->merge('errors', $errors)
					->set('errorHandlerVersion', '0.10.11')
					->set('message', false)
					->set('errorMessage', $msg)
					->set('system', false)
					->set('form', true)
					;
//			ExtJSResponse::put('errors', $errors);
//			ExtJSResponse::put('system', false);
//			ExtJSResponse::put('form', true);
			return false;
		}

//		dump($setters);

		$model = $this->table->createNewModel(
			$setters
			,false
			,$this->add_createContext($request, $setters)
		);

		$this->saveModel($model, true);
//		throw new \Exception('x');

		$this->newId = $id = $model->getPrimaryKeyValue();
//		ExtJSResponse::put('newId', $id = $model->getPrimaryKeyValue());

		// Put full new model's data in answer, if requested
		if ($this->request->get('dataInResponse', false)) {
			$query = $this->createLoadQuery('form')->selectFirst();

			$idField = $this->table->getPrimaryKeyName();
			$result = $query->andWhere("`$idField` = ?", $id)->executeSelectFirst();

			$this->data = $result;
//			ExtJSResponse::put('data', $result);
		}
		
		$this->afterAdd($model);

		return true;
	}
	
	protected function afterAdd(Model $model) {}
	
	protected function prepareAddData(Request &$data) {}
	
	protected function add_createContext(Request $form, $setters) {}

	protected function add_getField(Request $request, $col, &$setters, &$missingFields) {
		if ($request->has($col->name, true)) {
			$setters[$col->name] = $request->get($col->name);
		} else if ($col->isRequired(ModelColumn::OP_CREATE)) {
			$missingFields[$col->name] = $col->name;
		}
	}

	protected function add_extra(&$form, &$setters, &$missingFields) {}

	/**
	 * Hook for add and mod action, providing opportunity to modify the $setters
	 * of the Model. If overriden, this method is required to call its
	 * parent::addExtraSetters, in order to be sure that the module configuration
	 * will be applied.
	 * @param Request $form
	 * @param array $setters
	 * @param array $missingFields
	 */
	protected function addExtraSetters(&$form, &$setters, &$missingFields) {}

	  //////////////////////////////////////////////////////////////////////////
	 // AUTOCOMPLETE
	////////////////////////////////////////////////////////////////////////////

	public function auto_complete() {
		Logger::get($this)->warn('auto_complete action is deprecated, use autoComplete');
		return $this->autoComplete();
	}
	
	public function autoComplete() {
		
		$table = $this->table;
//REM		$context = array(
//			'year' => $this->request->get('year', null)
//		);
//		$query = $table->createQuery($context);
		$query = $table->createReadQuery($this->createAutoCompleteQueryContext());
		
		$selects = $this->getAutoCompleteSelects($query);

		if ($selects === null || $selects === false)
			throw new UnsupportedActionException($this);

		if (
			$this->request->has('autoComplete')
			&& $this->table->hasRelation(
				$autoComplete = $this->request->getRaw('autoComplete')
			)
		) {
			$table = $this->table->getRelationInfo($autoComplete)
					->getTargetTable();
		}

		$query->select($selects);
		if (method_exists($query, 'whereIsActif')) {
			$query->whereIsActif(); // TODO actif
		}

		if ($this->request->has('limit', true)) {
			if ($this->request->has('start', true)) {
				$query->limit(
					$this->request->getRaw('limit'),
					$this->request->getRaw('start')
				);
			} else {
				$query->limit(
					$this->request->getRaw('limit')
				);
			}
		}

		$table->applyAutoCompleteSort($query);

		$this->autoComplete_prepareQuery($query);

		$this->autoComplete_processSearchQuery($query, $this->request->get('query', null));
		
		if ($this->request->has('initValue')) {
			$q = clone $query;
			$q->where($q->createWhere(
				$q->getQualifiedName($this->table->getPrimaryKeyName()) . '=?',
				$this->request->getRaw('initValue')
			));
			$selectedRow = $q->executeSelectFirst();
		}

		$result = $query->executeSelect();
		if (isset($selectedRow)) $result[] = $selectedRow;

		if (count($result) === 0) {
			$result = array();
			$count = 0;
		} else {
			$count = $query->executeCount(true);
		}

		$this->data = $result;
		$this->count = $count;
		
		return true;
	}

	protected function createAutoCompleteQueryContext() {
		return $this->getLoadQueryContext();
	}

	protected function getAutoCompleteSelectString() {
		return $this->module->getConfig()->node('extra/autoComplete', false, false);
	}

	protected function autoComplete_prepareQuery(Query $query) {}

	protected function getAutoCompleteSelects(ModelTableQuery $query) {

		$table = $this->table;

		$formatString = $this->getAutoCompleteSelectString();

		if ($table->hasPrimaryKey()) {
			$pk = $table->getPrimaryKeyName();
		} else {
			throw new IllegalStateException('Primary key is required in table: ' . $table->name);
		}

		if ($formatString === null) {
			if ($table->hasName()) {
				$name = $table->getNameFieldName();
			} else {
				return null;
			}
		} else {
			$name = $query->createQualifiedFormattedSelect('name', $formatString);
		}

		return array(
			'id' => $pk,
			'name' => $name
		);
	}

	protected function processAutoCompleteQuery(ModelTableQuery $query, $extQuery) {
		Logger::get($this)->warn(
				'Deprecated. Avoid processAutoCompleteQuery, use autoComplete_processSearchQuery'
		);
		return $this->autoComplete_processSearchQuery($query, $extQuery);
	}
	protected function autoComplete_processSearchQuery(ModelTableQuery $query, $extQuery) {

		if ($extQuery !== null) {

			$format = $this->getAutoCompleteSelectString();

			if ($format === null) {
				if ($this->table->hasName()) {
					$query instanceof Query;
					$format = $query->getQualifiedName($this->table->getNameFieldName());
				} else {
					throw new IllegalStateException();
				}
			} else {
				$format = Query::format($format, $query);
			}

//			$query->andWhere("LOWER($format) LIKE ?", strtolower($extQuery) . '%');
			$query->andWhere("$format LIKE ?", strtolower($extQuery) . '%');
		}
	}

	  //////////////////////////////////////////////////////////////////////////
	 // LOAD -- Shared
	////////////////////////////////////////////////////////////////////////////

	protected function createLoadQuery($selContext = 'form', $relModes = null, $columns = null) {

		if ($relModes === null) {
			if (null === $relModes = $this->getRelationSelectionModes($selContext)) {
				$relModes = ModelTable::LOAD_NONE;
			}
		}
//		dump(array(
//			'form' => $this->getRelationSelectionModes('form'),
//			'grid' => $this->getRelationSelectionModes('grid'),
//		));
		
//		dump($relModes);
//		unset($relModes[3]['Contact->Conjoint']);
		
		if ($columns === null) {
			$columns = $this->request->get('columns');
		}
		
		$query = $this->table->createLoadQuery(
			$relModes,
			$this->getLoadQueryContext(),
			$columns
		);
		
		$this->applyLoadQueryParams($query);

		if ($this->table instanceof TableHasFilters) {
			$this->table->addLoadQueryFilters($query, $this->request->get('filters'));
		}
		
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->afterCreateLoadQuery($query);
			}
		}

		return $query;
	}
	
	private function applyLoadQueryParams(ModelTableQuery $query) {
		$this->createLoadQuery_sort($query);
		$this->createLoadQuery_filters($query);
		$this->createLoadQuery_search($query);
		$this->createLoadQuery_extra($query);
	}

	protected function createLoadQuery_sort(ModelTableQuery $query) {

		if ($this->request->has('sort')) {
			$sort = $this->request->get('sort', null);
			$dir = $this->request->get('dir', 'ASC');

			if (is_array($sort)) {
				foreach ($sort as $sortEntry) {
					if (!$this->table->hasColumn($sortEntry['field'])
							&& $this->table->hasField($sortEntry['field'])) {
						$query->join($this->table->getRelationInfo($sortEntry['field']));
					}
					$query->thenOrderBy($sortEntry['field'], $sortEntry['direction']);
				}
			} else {
				if (!$this->table->hasColumn($sort) && $this->table->hasField($sort)) {
					$query->join($this->table->getRelationInfo($sort));
				} 
				$query->thenOrderBy($sort, $dir);
			}
		}
	}
	
	private static $filter_acceptedOperators = array(
		'eq' => '=',
		'gt' => '>',
		'lt' => '<',
	);
	
	private static $filter_acceptedOperators_date = array(
		'eq' => '=',
		'gt' => '>=',
		'lt' => '<',
	);
	
	private static $filter_acceptedTypes = array(
		'boolean' => 'boolean',
		'date' => 'date',
		'list' => 'list',
		'numeric' => 'numeric',
		'string' => 'string',
	);

	protected function createLoadQuery_filters(ModelTableQuery $query) {
		
//		dump($this->request->toArray());

		if (null !== $filters = $this->request->get('columnFilters', null)) {
			foreach ($filters as $filter) {
				
				$type = self::$filter_acceptedTypes[$filter['type']];
				
				$field = $this->table->getField($filter['field']);
				$field = $filter['field'];
				$value = $filter['value'];
				
				switch ($type) {
					case 'date':
						$date = \DateTime::createFromFormat('d/m/Y', $value);
						$value = $date->format('Y-m-d');
						$op = self::$filter_acceptedOperators_date[$filter['comparison']];
						$query->andWhere("`$field` $op ?", $value);
						break;
					case 'numeric':
						$op = self::$filter_acceptedOperators[$filter['comparison']];
						$query->andWhere("`$field` $op ?", $value);
						break;
					
					case 'boolean':
						$query->andWhere("`$field` = ?", $value ? 1 : 0);
						break;
					
					case 'list':
						// If the field points directly to a relation (not a relation
						// field), we must specify that we aim at the id field
						$f = $this->table->getField($field);
						if ($f instanceof ModelRelationInfo) {
							$field .= '->' . $f->getTargetTable()->getPrimaryKeyName();
						}
						
						// Processing filter
						$where = $query->createWhere();
						foreach ($value as $i => $f) {
							if ($f === '${null}') {
								$where->orWhere("`$field` IS NULL");
								unset($value[$i]);
							}
						}
						if ($value) {
							$where->orWhereIn($field, $value);
						}
						$query->andWhere($where);
						break;
					
					case 'string':
						$query->andWhere("`$field` LIKE ?", 
								$this->createLoadQuery_processColumnFilterString($value));
						break;
				}
			}
		}
	}
	
	protected function createLoadQuery_processColumnFilterString($value) {
		$value = str_replace('%', 'µ§~€PLACEHOLDER_FOR_STAR', $value);
		$value = str_replace('_', 'µ§~€PLACEHOLDER_FOR_QT', $value);
		$value = str_replace('*', '%', $value);
		$value = str_replace('?', '_', $value);
		$value = str_replace('µ§~€PLACEHOLDER_FOR_STAR', '\\%', $value);
		$value = str_replace('µ§~€PLACEHOLDER_FOR_QT', '\\_', $value);
		return $value;
	}

	protected function createLoadQuery_search(ModelTableQuery $query) {

		if ($this->request->has('query', true)) {
			$extQuery = $this->request->getRaw('query');

			if ($this->request->has('searchFields')) {
				$fields = $this->request->getRaw('searchFields');
				Logger::dbg('Has field in request: {}', $fields);
				$fields = Json::decode($fields);
				Logger::dbg('Decoded search fields: {}', $fields);
			} else {
				$fields = array();
//				$fields = $this->table->
			}
//			print_r($fields);die;
			
			foreach (explode(' ', $extQuery) as $word) {
				
				$word = strtolower($word);
				$word = preg_quote($word);

				$queryWhere = $query->createWhere();

				foreach ($fields as $field) {
	//				$field = $query->getQualifiedName($field);
					if (strstr($field, '`')) throw new IllegalStateException('*Injection*');
//					$queryWhere->orWhere("`$field` LIKE ?", "$word%");
					$queryWhere->orWhere("`$field` REGEXP ?", "[[:<:]]$word");
				}

				$query->andWhere($queryWhere);
			}
		}
	}

	protected function addLoadQueryFiltersWhenAll(ModelTableQuery $query, array $filters) {}
	protected function addLoadQueryFilters(ModelTableQuery $query, array $filters) {}

	protected function createLoadQuery_extra(ModelTableQuery $query) {}

	protected function createQueryContext() {
		$context = array();
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->onCreateQueryContext($this->request, $context);
			}
		}
		return $context;
	}
	
	protected function getLoadQueryContext() {
		return $this->createQueryContext();
	}
	
	protected function addModelContext(Model $model) {
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->addModelContext($model);
			}
		}
	}

	/**
	 * Get an array listing relation names by loading mode (as set by config),
	 * depending on the specified mode (action).
	 * @param const $selMode
	 * @return array or NULL
	 */
	protected function getRelationSelectionModes($selMode = 'form') {}

	/**
	 * Get an array matching relation names with their loading mode (as set by
	 * config), depending on the specified mode (action).
	 * @return array or NULL
	 */
	protected function getSelectionModeForRelations($selMode = 'form') {}

	  //////////////////////////////////////////////////////////////////////////
	 // LOAD -- Multiple Rows
	//////////////////////////////////////////////////////////////////////////

	public function load() {

////		$cometMsg = <<<JS
////{success: true, alert: "Loading {$this->module->getName()} ..."}
////JS;
////		$path = CACHE_PATH . 'comet/' . UserSession::getUser()->id;
//		if (!file_exists(dirname($path))) mkdir(dirname($path), 0777, true);
//		file_put_contents($path, $cometMsg);
		
		$query = $this->createLoadQuery('grid');

		$start = $this->request->get('realstart', false, true);
		if ($start === false) $start = $this->request->get('start', 0, true);

		$query->limit(
			$this->request->get('limit', 20),
			$start
//			$this->request->get('start', 0, true)
		);
		
		$this->beforeExecuteLoadQuery($query);
		
		$r = $query->executeSelect();

		foreach ($r as &$record) {
			foreach ($record as $field => &$value) {
				$value = $this->table->getField($field)->castValue($value);
			}
			unset($value);
		}
		
		if (count($r) === 0) {
			$r = array();
			$count = 0;
		} else {
			$countQuery = clone $query;
			$count = $countQuery->executeCount();
		}
		
		$this->afterExecuteLoadQuery($query);
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->afterExecuteLoadQuery($query);
			}
		}

		$this->count = $count;
		$this->data = $r;

		return true;
	}
	
	protected function beforeExecuteLoadQuery(ModelTableQuery $query) {}
	
	protected function afterExecuteLoadQuery(ModelTableQuery $query) {}

	  //////////////////////////////////////////////////////////////////////////
	 // LOAD -- One Model
	//////////////////////////////////////////////////////////////////////////

	public function load_one($id = null) {
		Logger::get($this)->warn('Deprecated, use loadOne');
		return $this->loadOne($id);
	}
	
	public function loadOne($id = null) {

		if ($id === null) {
			$id = $this->request->req($this->table->getPrimaryKeyName());
		}
		
		if (!$this->doLoadOne($id)) {
			$msg = <<<'MSG'
L'enregistrement sélectioné n'existe pas dans la base de donnée. Ceci signifie
probablement qu'il vient d'être effacé par un autre utilisateur. Utilisez le
bouton "Rafraichir" pour mettre à jour l'affichage.
MSG;
			throw new UserException($msg, 'Enregistrement inexistant'); // i18n
		}
		
		return true;
	}
		
	protected function doLoadOne($id) {
		$model = $this->table->loadModel($id, $this->load_one_createContext());
		return $this->doLoadOneFromModel($model);
	}
	
	protected function doLoadOneFromModel(Model $model) {
		
		$data = $this->loadOne_loadData($model);

		if ($model === null) {
			return false;
		}

		$this->generateLoadFormPages($model);
		
		$this->loadOne_addExtraData($data, $model);

		$this->data = $data;

		return true;
	}

	protected function loadOne_loadData(Model $model) {
		
		$query = $this->createLoadQuery('form')->selectFirst();
		$idField = $query->getQualifiedName($this->table->getPrimaryKeyName());
		return $query->andWhere("$idField = ?", $model->getPrimaryKeyValue())->executeSelectFirst();

		// Possible alternative implemenation:
		//
		//	$specs = array('*');
		//
		//	foreach ($relations as $mode => $values) {
		//		switch ($mode) {
		//			case ModelTable::LOAD_NAME:
		//				// TODO
		//				break;
		//			case ModelTable::LOAD_ID:
		//				// TODO
		//				break;
		//			case ModelTable::LOAD_FULL:
		//				foreach ($values as $relation => $fields) {
		//					$specs = array_merge($specs, array_keys($fields));
		//				}
		//			break;
		//		}
		//	}
		// 
		// return $model->getDataAs($specs);
	}
	protected function loadOne_addExtraData(array &$data, Model $model) {}

	protected function load_one_createContext() {
		return array();
	}

	// --- Form Templates ------------------------------------------------------

	protected function putFormPageTemplateExtra($tpl, Model $model) {}

	protected function getFormPageNames() {
		return array();
	}
	
	protected function generateLoadFormPages(Model $model) {
		foreach ($this->getFormPageNames() as $pageName) {
			$this->generateFormPage($pageName, $model);
		}
	}

	protected function generateFormPage($name, Model $model) {
		try {
			if (null !== $path = $this->searchPath($name, FileType::HTML_TPL)) {
//			if (null !== $tpl = $this->createHtmlTemplate($name)) {
				$tpl = HtmlTemplate::create($this)->setFile($path);
				$modelName = get_class($model);
				$modelName = strtolower($modelName[0]) . substr($modelName, 1);
				$tpl->model = $model;
				$tpl->$modelName = $model;
				$this->putFormPageTemplateExtra($tpl, $model);
				$this->put('pages', $name, $tpl->render(true));
//				ExtJSResponse::pushIn('pages', $tpl->renderString(), $key = $name);
			} else {
				// TODO render 404
				$this->put('pages', $name, "<h1>404 Not Found</h1>"
					. "Missing template: $name"
				);
			}
		} catch(Exception $ex) {
			ExceptionHandler::processException($ex, false);
			Logger::get($this)->error('Form template generation error: {}', $ex);
			$this->put('pages', $name, lang(
				'<h1>Erreur</h1>Une erreur a empêcher de générer cette page d\'information.'
				. ' Nous sommes désolé pour le désagrément, vous pouvez signaler '
				. 'cette erreur au support technique pour aider à la résoudre.'
			));
		}
	}

	/**
	 * @internal makes searchPath go look into the formTemplate folder.
	 * @todo Add a correct implementation of form templates directory structure
	 * (they will be searched each time an html template is searched here, that
	 * sucks...)
	 */
	public function _getFileFinderForHtml_Tpl($fallback) {
		return new \eoko\file\BasicFinder(
			$this->module->getBasePath() . $this->formTemplatePath,
			$this->module->getBaseUrl() . $this->formTemplatePath,
			array(
				'forbidUpwardResolution' => true,
				'fallbackFinder' => $fallback
			)
		);
	}

	  //////////////////////////////////////////////////////////////////////////
	 // EDIT
	//////////////////////////////////////////////////////////////////////////

	public final function mod() {
		return $this->callInTransaction('doMod');
	}
	
	protected function getFormDataRequest() {
		if ($this->request->hasSub('data')) {
			return $this->request->getSub('data');
		} else {
			return $this->request->requireSub('form');
		}
	}

	protected function doMod() {
		
		$request = $this->getFormDataRequest();

		$setters = array();
		$missingFields = array();

		foreach ($request->getAll() as $k => $in) {
			if ($this->table->hasSetter($k)) {
				$setters[$k] = $in;
			}
		}

		foreach ($this->table->getRelationsInfo() as $rel) {
//			$rel instanceof ModelRelationInfo; // DBG
			if ($request->has($rel->name)) {
				$setters[$rel->name] = $request->getRaw($rel->name);
			}
		}

		foreach ($request->getAll() as $k => $in) {
			if (strstr($k, '->') !== false) {
				$setters[$k] = $in;
			}
		}

		$this->addExtraSetters($request, $setters, $missingFields);
		$this->mod_getFields($request, $setters, $missingFields);

		// TODO rx add a where constraint to loadModel
		// $table->loadModel($id, QueryWhere::create('year = ?', 2008)
		$model = $this->createModelForUpdate($request, $setters);
		
//		dump($setters, 50);
//		dump($model);
		
		$model->setFields($setters);

		$this->saveModel($model, false);

		return true;
	}
	
	protected function createModelForUpdate($request, $setters) {

		$idName = $this->table->getPrimaryKeyName();
		if ((null === $id = $request->get($idName))
				&& (null === $id = $this->request->get($idName))) {
			$id = $this->request->req('id');
		}
		
		$model = $this->table->loadModel($id, $this->load_one_createContext());
		
		if ($model === null) {
			throw new SystemException('Cannot load model with id: ' . $id);
		}
		
		return $model;
	}

	protected function mod_getFields(Request $request, &$setters, &$missingFields) {}
	
	public function toggleFieldValue() {
		$name = $this->request->req('name');
		$id = $this->request->req('id');
		
		$q = $this->table->createQuery();
		
		$q->set(array(
			$name => new \SqlVariable("IF({$q->getQualifiedName($name)},0,1)")
		));
				
		$success = 1 === $q->where('id=?', $id)->executeUpdate();
		
		$this->data = $this->table->loadModel($id)->getData();

		return $success;
	}

	  //////////////////////////////////////////////////////////////////////////
	 // SAVE - Shared
	//////////////////////////////////////////////////////////////////////////

	protected function saveModel(Model $model, $new = null) {
		
		if ($new === null) {
			$new = $model->isNew();
		}
		
		$this->beforeSaveModel($model, $new);

		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->beforeSaveModel($model, $new);
			}
		}
		
		$model->save($new);
		
		$this->afterSaveModel($model, $new);

		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->afterSaveModel($model, $new);
			}
		}
	}
	
	protected function beforeSaveModel(Model $model) {
		$this->addModelContext($model);
	}
	
	protected function afterSaveModel(Model $model, $wasNew) {}

	  //////////////////////////////////////////////////////////////////////////
	 // DELETE
	//////////////////////////////////////////////////////////////////////////
	
	/**
	 * @deprecated Use {@link delete}
	 */
	public function delete_one() {
		return $this->onDelete();
	}
	
	/**
	 * @deprecated Use {@link delete}
	 */
	public function delete_multiple() {
		return $this->onDelete();
	}
	
	public function delete() {
		return $this->onDelete();
	}
	
	private function onDelete() {
		
		try {
			return $this->callInTransaction('doDelete');
		} 
		
		catch (ModelAlreadyDeletedException $ex) {
			
			list($ids, $count) = $this->getDeleteVariables();

			$alreadyDeleted = $this->table->createQuery()
					->whereIn($this->table->getPrimaryKeyName(), $ids)
					->andWhere('`deleted` = 1')
					->executeCount();

			if ($count === 1) {
				$this->errorMessage = "L'enregistrement n'existe pas (peut-être a-t-il "
						. 'été supprimé par un ature utilisateur ?)';
			}
			if ($alreadyDeleted === $count) {
				$this->errorMessage = "Les enregistrements n'existent pas (peut-être ont-ils "
						. 'été supprimés par un autre utilisateur ?)';
			}
			if ($alreadyDeleted < $count) {
				$this->errorMessage = "Certains des enregistrements sélectionnés n'existent "
						. 'pas. Peut-être ont-ils été supprimés par un autre utilisateur.';
			}

			return false;
		}
	}
	
	protected function afterDelete(array $ids) {}
	
	protected function beforeDelete(array $ids) {}

	private function onAfterDelete(array $ids) {
		
		$this->afterDelete($ids);
		
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				$plugin->afterDelete($ids);
			}
		}
	}
	
	private function onBeforeDelete(array $ids) {
		
		if (false === $this->beforeDelete($ids)) {
			return false;
		}
		
		if ($this->plugins) {
			foreach ($this->plugins as $plugin) {
				if (false === $plugin->beforeDelete($ids)) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	private function getDeleteVariables() {
		
		$ids = $this->request->requireFirst(array(
			$this->table->getPrimaryKeyName(), 'id', 'ids'
		));
		
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		
		$count = count($ids);
		
		return array($ids, $count);
	}
	
	protected function doDelete() {
		
		list($ids, $count) = $this->getDeleteVariables();
		
		if ($this->onBeforeDelete($ids)) {
			if ($count === $n = $this->table->deleteWherePkIn($ids)) {
				$this->deletedCount = $count;
				$this->onAfterDelete($ids);
				return true;
			} else {
				if ($n < $count) {
					throw new SystemException(
						"Delete failed (expected: $count, actual: $n)",
						lang('Une erreur a empêché la suppression de tous les enregistrements.')
					);
				} else if ($n > $count) {
					Logger::getLogger('GridController')->error('Terrible mistake, I have '
						. 'deleted more reccords than required here!!! {} rows deleted', $n);
					throw new SystemException('Terrible Mistake');
				} else {
					throw new IllegalStateException('Unreachable code');
				}
			}
		} else {
			return false;
		}
	}

//	protected function doDeleteOne($id = null) {
//		if ($id === null) {
//			$id = $this->request->req($this->table->getPrimaryKeyName());
//		}
//		if (false !== $this->beforeDelete(array($id))) {
//			if (1 === $n = $this->table->deleteWherePkIn(array($id))) {
//				return true;
//			} else {
//				Logger::getLogger('GridController')->error('{} rows deleted', $n);
//				if ($n > 0) {
//					Logger::getLogger('GridController')->error('Terrible mistake, I have '
//						. 'deleted more than 1 reccord here!!! {} rows deleted', $n);
//					throw new SystemException('Terrible Mistake');
//				}
//				throw new SystemException('Delete failed');
//				return false;
//			}
//		} else {
//			return false;
//		}
//	}

	  //////////////////////////////////////////////////////////////////////////
	 // SUBSET
	////////////////////////////////////////////////////////////////////////////

	public function load_subset() {

//		Eg. Usage:
//		{
//			controller: 'contacts'
//			action: 'load_subset'
//			subset: 'enfant'
//			id: 43
//		}

		$subset = $this->request->req('subset', true);
		if (preg_match('/^(.+)\+(.+)$/', $subset, $m)) {
			list(,$subset,$subsetParam) = $m;
		} else {
			$subsetParam = null;
		}

		if (null !== $r = $this->loadSubset($subset)) {
			return $r;
		}

		Logger::get($this)->debug('Subset is {}', $subset);

		$query = $this->table->getHasManyRelationInfo(
			$subset
		)->createLoadQueryFor(
			$this->request->req('id', true),
			$this->load_subset_createContext()
		);

		$this->selectSubsetFields($query, $subset);
		$this->beforeLoadSubsetQuery($subset, $query, $subsetParam);

		$this->data = $query->executeSelect();

		return true;
	}

	protected function loadSubset($subset) {}

	protected function load_subset_createContext() {
		return array();
	}

	protected function beforeLoadSubsetQuery($subset, ModelTableQuery $query, $subsetParam) {}
	protected function selectSubsetFields(ModelTableQuery $query, $subset) {}

	  //////////////////////////////////////////////////////////////////////////
	 // EXPORT
	////////////////////////////////////////////////////////////////////////////

	protected function makePdfTitle() {
		return $this->title;
	}
	
	/**
	 * Gets the module name in a filesystem & url friendly version.
	 * @return string
	 */
	protected function getSlug() {
		if (null !== $slug = $this->getModule()->getConfig()->getValue('module/slug', null)) {
			return $slug;
		} else {
			return Strings::slugify($this->title);
		}
	}
	
	private $earl;
	
	/**
	 * @return EarlReport\EarlReport
	 */
	private function getEarl() {
		if (!$this->earl) {
			$earlModule = $this->getModule()->getConfig()->getValue('extra/earlReportModule');
			if (!$earlModule) {
				throw new IllegalStateException('Cannot export as pdf without earl report module '
						. '(extra/earlReportModule config key) configured.');
			}
			$earlModule = ModuleManager::getModule($earlModule);
			if (false == $earlModule instanceof \eoko\modules\EarlReport\EarlReport) {
				throw new IllegalStateException('extra/earlReportModule must point to an instance '
						. 'of class eoko\modules\EarlReport\EarlReport.');
			}
			$this->earl = $earlModule->getEarl();
		}
		return $this->earl;
	}

	public function export() {
		
		set_time_limit(180);
		
		$allowedFormats = array(
			'pdf', 'xls', 'ods'
		);

		$fields = $this->request->req('fields');
		$format = $this->request->req('format');
		
		if (!in_array($format, $allowedFormats, true)) {
			throw new IllegalStateException();
		}

//		dump($fields);

//		$query = $this->createLoadQuery('grid', $this->makeRelationMode($fields, 'grid'));
//		$result = $query->execute();
////		$result = $this->table->selectFields($fields, $query)->execute();
//
////		$result = $this->getTable()->createQuery()
////					->select($fieldNames)
////					->execute();

//TODO		$rowOrder = array();
//		$i = 0;
//		foreach ($fields as $field => &$name) {
//			$rowOrder["__col_$i"] = $name;
//			$name = "__col_$i";
//			$i++;
//		}
//
//		$query = $this->table->createQuery($this->getLoadQueryContext());
//
//		$this->table->selectFields($fields, $query);
//
//		$this->createLoadQuery_sort($query);
//
//		$this->createLoadQuery_search($query);
//
//		$this->createLoadQuery_extra($query);
//
//		if ($this->request->has('filters', true)) {
//			$filters = array();
//			foreach ($this->request->getRaw('filters') as $filter) {
//				$filters[$filter] = true;
//			}
////			$this->addLoadQueryFilters($query, $filters);
//			if (!isset($filters['all']) || !$filters['all']) {
//				$this->addLoadQueryFilters($query, $filters);
//			}
//		}

		// tmp
		$query = $this->createLoadQuery('grid', null, array_keys($fields));

//		$start = $this->request->get('realstart', false, true);
//		if ($start === false) $start = $this->request->get('start', 0, true);
//
//		$query->limit(
//			$this->request->get('limit', 20),
//			$start
////			$this->request->get('start', 0, true)
//		);
		// /tmp
		
		$result = $query->execute();

//RODO		// order
//		foreach ($result as &$row) {
//			$newRow = array();
//			foreach ($rowOrder as $alias => $name) {
//				$newRow[$name] = $row[$alias];
//			}
//			$row = $newRow;
//		}
//		dump(count($result));
		
//		var_export(array_slice($result, 0, 2));
//		
//		dump(array(
//			$fields,
//			array_slice($result, 0, 2),
//		));

		$earl = $this->getEarl();
		
		$user = UserSession::getUser();
		
		$report = $earl->createReport()
				->setAddress(<<<TXT
CE Rhodia - Site Belle Étoile
BP 103
69192 SAINT FONS CEDEX
TXT
				)
				->setTitle($this->makePdfTitle())
				->setUser($user->getDisplayName(User::DNF_PRETTY))
				->setUserEmail($user->getEmail());
		
		$sheet = $report->addWorksheet('Feuille 1');
		
		foreach ($fields as $field => $title) {
			$colFormat = null;
			$f = $this->table->getField($field);
			switch ($f->getType()) {
				case ModelField::T_INT:
				case ModelField::T_FLOAT:
				case ModelField::T_DECIMAL:
					$colFormat = \EarlReport\Data\Type::FLOAT;
					break;
				case ModelField::T_DATE:
					$colFormat = array(
						'type' => \EarlReport\Data\Type::DATE,
						'precision' => \EarlReport\Data\Format\Date::DAY,
					);
					break;
				case ModelField::T_DATETIME:
					$colFormat = array(
						'type' => \EarlReport\Data\Type::DATE,
						'precision' => \EarlReport\Data\Format\Date::SECOND,
					);
					break;
				case ModelField::T_BOOL:
					$colFormat = \EarlReport\Data\Type::BOOL;
					break;
				case ModelField::T_ENUM:
//					$f instanceof \eoko\cqlix\EnumColumn;
					$colFormat = array(
//						'type' => \EarlReport\Data\Type::FLOAT,
						'renderer' => $f->getCodeLabels(),
					);
					break;
			}
			$col = $sheet->addColumn(array(
				'title' => $title,
				'format' => $colFormat,
			));
		}
		
//		$result = array_slice($result, 0, 2); // DEBUG
		$sheet->setRows(new \EarlReport\Data\Rows\NamedFieldsArray($result, array_keys($fields)));
		
		$slug = $this->getSlug();
		$slugDir = $slug ? "$slug/" : null;
		$directory = EXPORTS_PATH . $slugDir;
		$filename = $this->makeExportFilename($directory, $format);
		
		$file = $directory . $filename;
		$url = EXPORTS_BASE_URL . $slugDir . $filename;
		
		$earl->createWriter($report)->write($file);
		
//		$exporter = new \Exporter($this->makeExportFilename());
//		$exporter->setDirectory($this->getSlug());
//
//		switch ($format) {
//			case 'csv': 
//				$url = $exporter->exportCSV($result);
//				break;
//			case 'pdf':
//				$url = $exporter->exportPDF($result, $fields, $this->table, $this->makePdfTitle());
////				$url = $this->exportPDF(
////					$result,
////					$fields,
////					$this->table,
////					$this->makePdfTitle()
////				);
//				break;
//			default: throw new IllegalStateException('Unreachable code');
//		}

		$this->url = $url;

//		header('Content-Type: application/force-download');
//header('Content-type: application/pdf');
//header('Content-Disposition: attachment; filename="downloaded.pdf"');
		
		return true;
	}

	private function makeExportFilename($directory, $ext, $date = true) {
		$s = 'Export';
		
		if ($date) {
			$s .= '_' . date('Ymd_His');
		}
		
		// Ensure unicity
		$n = null;
		$i = 1;
		while (file_exists("$directory$s$n.$ext")) {
			$i++;
			$n = "_$i";
		}
		
		$s .= "$n.$ext";
		
		return $s;
	}

	protected function beforeWritePdf(\PdfExport $pdfExport) {}

	public function exportPDF($result, $fields, ModelTable $table, $title) {
		$pdfExport = new \PdfExport($result, $fields, $table, $title);
		
		$this->beforeWritePdf($pdfExport);

		$filename = $this->getFileName('pdf');
		$pdfExport->writeFile(self::getAbsolutePath($filename));

		return self::getUrl($filename);
	}

}