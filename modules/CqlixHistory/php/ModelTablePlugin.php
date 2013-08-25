<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\modules\CqlixHistory;

use ModelTable;
use Zend\EventManager\Event;

/**
 * This plugin will monitor events of one target table, and attach a {@link ModelPlugin} to
 * each newly instantiated models.
 *
 * The same {@link ModelPluginContext context} will be used for every model instances.
 *
 * @category Eoze
 * @package CqlixHistory
 * @since 2013-04-03 14:07
 */
class ModelTablePlugin {

	/**
	 * @var ModelPluginContext
	 */
	private $context;

	/**
	 * Creates a new instance of this plugin for the specified target table.
	 *
	 * @param ModelTable $table
	 * @param $config
	 */
	public function __construct(ModelTable $table, $config) {

		$this->context = new ModelPluginContext($table, $config);

		$table->getEventManager()->attach(ModelTable::EVENT_MODEL_CREATED, array($this, 'onModelCreated'));
	}

	/**
	 * Attaches a new instance of this plugin to the specified table.
	 *
	 * @param ModelTable $table
	 * @param array|ModelPluginConfiguration $config
	 * @return ModelTablePlugin
	 */
	public static function attach(ModelTable $table, $config) {
		return new ModelTablePlugin($table, $config);
	}

	/**
	 * Event handler for model instantiation.
	 *
	 * @param Event $e
	 */
	public function onModelCreated(Event $e) {
		$model = $e->getParam('model');
		ModelPlugin::attach($model, $this->context);
	}
}
