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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\Request;

use Request;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception;

/**
 * A wrapper for the {@link Request} class, that abstract it from the user class. This class also
 * provides configured param names that are accessible with constants.
 *
 * @since 2013-05-21 15:31
 */
class Params {

	const SORT = 1;
	const START = 6;
	const LIMIT = 2;
	const PAGE = 3;
	const FILTERS = 4;
	const TOTAL = 5;
	const DATA = 7;
	const ID = 8;
	const CONFIGURE = 9;
	const EXPAND = 10;
	const EXPANDED = 11;

	private $map = array(
		self::ID => 'id',
		self::SORT => 'sort',
		self::START => 'start',
		self::LIMIT => 'limit',
		self::PAGE => 'page',
		self::FILTERS => 'filter',
		self::TOTAL => 'total',
		self::DATA => 'data',
		self::CONFIGURE => 'configure',
		self::EXPAND => 'expand',
		self::EXPANDED => 'expanded',
	);

	/**
	 * @var \Request
	 */
	private $request;

	/**
	 * Creates a new Params object.
	 *
	 * @param Request $request
	 */
	public function __construct(Request $request) {
		$this->request = $request;
	}

	/**
	 * Gets the name of the param specified by one of this class' constants.
	 *
	 * @param string $param
	 * @throws Exception\UnknownRequestParam
	 * @return string
	 */
	public function getParamName($param) {
		if (isset($this->map[$param])) {
			return $this->map[$param];
		} else {
			throw new Exception\UnknownRequestParam;
		}
	}

	/**
	 * Returns an array in which the keys will be converted to the param name matching
	 * the param constant.
	 *
	 * @param array $array
	 * @throws Exception\UnknownRequestParam
	 * @return array
	 */
	public function convertArrayIndexes(array $array) {
		$converted = array();
		foreach ($array as $key => $value) {
			$name = $this->getParamName($key);
			$converted[$name] = $value;
		}
		return $converted;
	}

	/**
	 * Returns the preconfigured name of the param if the argument is one of this class' constants,
	 * or returns the argument itself.
	 *
	 * @param string $param
	 * @return string
	 */
	private function parseParamName($param) {
		return isset($this->map[$param])
			? $this->map[$param]
			: $param;
	}

	/**
	 * Returns true if the request contains the param specified by one of this class' constants,
	 * or a custom param name.
	 *
	 * @param string $param
	 * @return bool
	 */
	public function has($param) {
		$name = $this->parseParamName($param);
		return $this->request->has($name);
	}

	/**
	 * Gets the value of the param specified with one of this class' param constants, or
	 * a custom param name.
	 *
	 * @param string $param
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($param, $default = null) {
		$name = $this->parseParamName($param);
		return $this->request->get($name, $default);
	}

	/**
	 * Requires the value of the param specified with one of this class' param constants,
	 * or a custom param name.
	 *
	 * @param string $param
	 * @return mixed
	 * @throws Exception\Request\MissingRequiredParam
	 */
	public function req($param) {
		$request = $this->request;
		$name = $this->parseParamName($param);
		if ($request->has($name)) {
			return $request->getRaw($name);
		} else {
			throw new Exception\Request\MissingRequiredParam($name);
		}
	}

}
