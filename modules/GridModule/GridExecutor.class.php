<?php

namespace eoko\modules\GridModule;

use eoko\module\executor\JsonExecutor;
use eoko\util\Json;
use eoko\file\FileType;
use eoko\template\HtmlTemplate;
use eoko\cqlix\table_filters\TableHasFilters;
use eoko\database\Database;

use Model;
use ModelTable;
use ModelTableQuery;
use ModelColumn;
use Query;
use ModelRelationInfoReferencesOne;
use Logger;
use Request;
use ExceptionHandler;
use UserSession;

use Exception;
use UserException;
use IllegalStateException, UnsupportedActionException, SystemException;
use ModelSaveException;

abstract class GridExecutor extends JsonExecutor {

	/** @var \ModelTable */
	protected $table;

	protected $formTemplatePath = 'formTemplates';

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
		return $this->callInTransaction('doAdd');
	}

	protected function doAdd() {
		
		$request = $this->request->getSub('form');

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

		$this->beforeSaveModel($model);

		if (!$model->save(true)) {
			throw new ModelSaveException('Model save error');
		}

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

		return true;
	}
	
	// TODO eoze cleanout (rhodia leftover)
	protected function add_createContext(Request $form, $setters) {
		return array(
			'year' => $this->request->get('year', null)
		);
	}

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

	function auto_complete() {
	
		$table = $this->table;
//REM		$context = array(
//			'year' => $this->request->get('year', null)
//		);
//		$query = $table->createQuery($context);
		$query = $table->createQuery($this->createAutoCompleteQueryContext());
		
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
		if (method_exists($query, 'whereIsActif')) $query->whereIsActif(); // TODO actif

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

	protected function createLoadQuery($selContext = 'form', $relModes = null) {

		if ($relModes === null) {
			if (null === $relModes = $this->getRelationSelectionModes($selContext)) {
				$relModes = ModelTable::LOAD_NONE;
			}
		}

//		dump($relModes);
//		unset($relModes[3]['Contact->Conjoint']);
		$query = $this->table->createLoadQuery(
			$relModes,
			$this->getLoadQueryContext()
		);

		$this->createLoadQuery_sort($query);
		
		$this->createLoadQuery_filters($query);

		$this->createLoadQuery_search($query);

		$this->createLoadQuery_extra($query);

		if ($this->table instanceof TableHasFilters) {
			$this->table->addLoadQueryFilters($query, $this->request->get('filters'));
		}

		return $query;
	}

	protected function createLoadQuery_sort(ModelTableQuery $query) {

		if ($this->request->has('sort')) {
			$sort = $this->request->get('sort', null);
			$dir = $this->request->get('dir', 'ASC');

			if (is_array($sort)) {
				foreach ($sort as $sortEntry) {
					$query->thenOrderBy(
						$sortEntry['field'], $sortEntry['direction']
//						$this->table->getField(
//							$sortEntry['field'])->orderClause($sortEntry['direction'], $query->dbTable
//						)
					);
				}
			} else {
				$query->thenOrderBy(
					$sort, $dir
//					$this->table->getField($sort)->orderClause($dir, $query->dbTable)
				);
			}
		}
	}
	
	private static $filter_acceptedOperators = array(
		'eq' => '=',
		'gt' => '>',
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
					case 'numeric':
						$op = self::$filter_acceptedOperators[$filter['comparison']];
						$query->andWhere("`$field` $op ?", $value);
						break;
					
					case 'boolean':
						$query->andWhere("`$field` = ?", $value ? 1 : 0);
						break;
					
					case 'list':
						$query->andWhereIn(`$field`, $value);
						break;
					
					case 'string':
						$value = str_replace('%', 'µ§~€PLACEHOLDER_FOR_STAR', $value);
						$value = str_replace('_', 'µ§~€PLACEHOLDER_FOR_QT', $value);
						$value = str_replace('*', '%', $value);
						$value = str_replace('?', '_', $value);
						$value = str_replace('µ§~€PLACEHOLDER_FOR_STAR', '\\%', $value);
						$value = str_replace('µ§~€PLACEHOLDER_FOR_QT', '\\%', $value);
						$query->andWhere("`$field` LIKE ?", $value);
						break;
				}
			}
		}
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

			$queryWhere = $query->createWhere();

			foreach ($fields as $field) {
//				$field = $query->getQualifiedName($field);
				if (strstr($field, '`')) throw new IllegalStateException('*Injection*');
				$extQuery = strtolower($extQuery);
				$queryWhere->orWhere("`$field` LIKE ?", "$extQuery%");
			}

			$query->andWhere($queryWhere);
		}
	}

	protected function addLoadQueryFiltersWhenAll(ModelTableQuery $query, array $filters) {}
	protected function addLoadQueryFilters(ModelTableQuery $query, array $filters) {}

	protected function createLoadQuery_extra(ModelTableQuery $query) {}

	protected function getLoadQueryContext() {
		return array();
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
		if (count($r) === 0) {
			$r = array();
			$count = 0;
		} else {
			$count = $query->executeCount(true);
		}

		$this->count = $count;
		$this->data = $r;

		return true;
	}
	
	protected function beforeExecuteLoadQuery(ModelTableQuery $query) {}

	  //////////////////////////////////////////////////////////////////////////
	 // LOAD -- One Model
	//////////////////////////////////////////////////////////////////////////

	public function load_one($id = null) {

		$this->requestPrefix = $this->modRequestPrefix;

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

		$query = $this->createLoadQuery('form')->selectFirst();

		$idField = $query->getQualifiedName($this->table->getPrimaryKeyName());
		$result = $query->andWhere("$idField = ?", $id)->executeSelectFirst();

		$model = $this->table->loadModel($id, $this->load_one_createContext());

		if ($model === null) {
			return false;
		}

		$this->generateLoadFormPages($model);

		$this->data = $result;

		return true;
	}

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

	protected function doMod() {
		
		$request = $this->request->requireSub('form');
		$setters = array();
		$missingFields = array();

		// Excluding auto columns, in order to get a chance to grab it from the
		// request, if set...
		$cols = $this->table->getColumns(false, true);

		$idName = $this->table->getPrimaryKeyName();
//		$setters[$idName] = $request->req($idName);
		if ((null === $id = $request->get($idName))
				&& (null === $id = $this->request->get($idName))) {
			$id = $this->request->req('id');
		}

		unset($cols[$idName]);

//REM		foreach ($cols as $col) {
////			$col instanceof ModelColumn; // DBG
//			if ($request->has($col->name)) {
//				$setters[$col->name] = $request->req($col->name, false);
//			}
//		}
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
		$model = $this->table->loadModel($id, $this->load_one_createContext());
		
		if ($model === null) {
			throw new SystemException('Cannot load model with id: ' . $id);
		}
		
//		array(
//			'year' => $this->request->req('year')
//		));
//		dump($setters, 50);
//		dump($model);
//		dump($model->getInternal()->fields);
		$model->setFields($setters);
//		$model = $this->table->createModel($setters);

		$this->beforeSaveModel($model);

		$model->saveManaged();

		return true;
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

	protected function beforeSaveModel(&$model) {}

	  //////////////////////////////////////////////////////////////////////////
	 // DELETE
	//////////////////////////////////////////////////////////////////////////
	
	protected function beforeDeleteMultiple($ids) {}
	
	public function delete_multiple() {
		return $this->callInTransaction('doDeleteMultiple');
	}
	
	protected function doDeleteMultiple() {
		
		$ids = $this->request->req('ids');
		$count = count($ids);
		if (false !== $this->beforeDeleteMultiple($ids)) {
			if ($count === $n = $this->table->deleteWherePkIn($ids)) {
				$this->deletedCount = $count;
				return true;
			} else {
				if ($n < $count) {
					throw new SystemException(
						'Delete failed',
						lang('Une erreur a empêché la suppression de tous les enregistrements.')
					);
				} else if ($n > $count) {
					Logger::getLogger('GridController')->error('Terrible mistake, I have '
						. 'deleted more reccords than required here!!! {} rows deleted', $n);
					throw new SystemException('Terrible Mistake');
				}
				return false;
			}
		} else {
			return false;
		}
	}
	
	protected function beforeDeleteOne($id) {}

	public function delete_one() {
		return $this->callInTransaction('doDeleteOne');
	}
	
	protected function doDeleteOne() {
		$id = $this->request->req($this->table->getPrimaryKeyName());
		if (false !== $this->beforeDeleteOne($id)) {
			if (1 === $n = $this->table->deleteWherePkIn(array($id))) {
				return true;
			} else {
				Logger::getLogger('GridController')->error('{} rows deleted', $n);
				if ($n > 0) {
					Logger::getLogger('GridController')->error('Terrible mistake, I have '
						. 'deleted more than 1 reccord here!!! {} rows deleted', $n);
					throw new SystemException('Terrible Mistake');
				}
				throw new SystemException('Delete failed');
				return false;
			}
		} else {
			return false;
		}
	}

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

	public function export() {

		$fields = $this->request->req('fields');
		$format = $this->request->req('format');

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
		$query = $this->createLoadQuery('grid');

		$start = $this->request->get('realstart', false, true);
		if ($start === false) $start = $this->request->get('start', 0, true);

		$query->limit(
			$this->request->get('limit', 20),
			$start
//			$this->request->get('start', 0, true)
		);
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

		$exporter = new \Exporter($this->makeExportFilename());

		switch ($format) {
			case 'csv': 
				$url = $exporter->exportCSV($result);
				break;
			case 'pdf':
				$url = $exporter->exportPDF($result, $fields, $this->table, $this->makePdfTitle());
//				$url = $this->exportPDF(
//					$result,
//					$fields,
//					$this->table,
//					$this->makePdfTitle()
//				);
				break;
			default: throw new IllegalStateException('Unreachable code');
		}

		$this->url = $url;

		return true;
	}

	protected function makeExportFilename() {
		return 'Export';
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