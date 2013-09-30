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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\FieldNameResolver;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor as ProcessorInterface;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception;
use eoko\modules\EozeExt4\Exception as ModuleException;

/**
 * Abstract implementation for query processors. This class provides support for server field name
 * resolving.
 *
 * @since 2013-05-17 15:23
 */
abstract class AbstractProcessor implements ProcessorInterface {

	/**
	 * @var FieldNameResolver
	 */
	private $resolver;

	/**
	 * Creates a new processor.
	 *
	 * @param FieldNameResolver $resolver
	 * @param $data
	 */
	public function __construct(FieldNameResolver $resolver, $data) {
		$this->resolver = $resolver;
		$this->setData($data);
	}

	/**
	 * Sets the configuration data for this processor.
	 *
	 * This method must be implemented by child classes.
	 *
	 * @internal This method is not abstract in order to allow child classes to specialize the
	 * argument.
	 *
	 * @param mixed $data
	 */
	protected function setData(/** @noinspection PhpUnusedParameterInspection */ $data) {
		throw new ModuleException\UnsupportedOperation('Must be implemented by child class.');
	}

	/**
	 * Resolves the specified client field name to the associated server field name.
	 *
	 * @param string $clientField
	 * @param bool $require
	 * @throws Exception\UnknownField
	 * @return null|string
	 */
	protected function resolveFieldName($clientField, $require = true) {
		$field = $this->resolver->getServerFieldName($clientField);

		if ($field !== null) {
			return $field;
		} else if ($require) {
			throw new Exception\UnknownField($field);
		} else {
			return null;
		}
	}
}
