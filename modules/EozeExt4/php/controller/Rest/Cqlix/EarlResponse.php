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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix;

use eoko\modules\EarlReport\EarlReport;
use ModelField;
use Zend\Http\Request;
use eoko\module\ModuleManager;
USE EarlReport\Data\Type as EarlType;

/**
 * @todo doc
 *
 * @since 2013-08-27 18:03
 */
class EarlResponse {

	public function __construct(\User $user, DataProxy $dataProxy, array $data, array $fields, $format) {
		$this->user = $user;
		$this->format = $format;
		$this->dataProxy = $dataProxy;
		$this->data = $data;
		$this->fields = $fields;
	}

	/**
	 * @return \EarlReport\EarlReport
	 */
	private function getEarl() {
		/** @var \eoko\modules\EarlReport\EarlReport $earlModule */
		$earlModule = ModuleManager::getModule('earl');
		return $earlModule->getEarl();
	}

	public function getExtension() {
		return $this->format;
	}

	public function writeFile(&$filename) {

		set_time_limit(180);

		$earl = $this->getEarl();
		$user = $this->user;
		$fields = $this->fields;
		$dataProxy = $this->dataProxy;
		$table = $dataProxy->getTable();

		$report = $earl->createReport()
			->setAddress($this->getAddress())
			->setTitle($this->getTitle())
			->setUser($user->getDisplayName(\User::DNF_PRETTY))
			->setUserEmail($user->getEmail());

		$sheet = $report->addWorksheet('Feuille 1');

		foreach ($fields as $fieldName => $title) {
			$colFormat = null;
			$field = $table->getField($dataProxy->getServerFieldName($fieldName));
			switch ($field->getType()) {
				case ModelField::T_INT:
					$colFormat = EarlType::INT;
					break;
				case ModelField::T_FLOAT:
				case ModelField::T_DECIMAL:
					$colFormat = EarlType::FLOAT;
					break;
				case ModelField::T_DATE:
					$colFormat = array(
						'type' => EarlType::DATE,
						'precision' => \EarlReport\Data\Format\Date::DAY,
					);
					break;
				case ModelField::T_DATETIME:
					$colFormat = array(
						'type' => EarlType::DATE,
						'precision' => \EarlReport\Data\Format\Date::SECOND,
					);
					break;
				case ModelField::T_BOOL:
					$colFormat = EarlType::BOOL;
					break;
				case ModelField::T_ENUM:
					/** @var \eoko\cqlix\EnumColumn $field */
					$colFormat = array(
//						'type' => \EarlReport\Data\Type::FLOAT,
						'renderer' => $field->getCodeLabels(),
					);
					break;
			}

			$sheet->addColumn(array(
				'title' => $title,
				'format' => $colFormat,
			));
		}

		$sheet->setRows(new \EarlReport\Data\Rows\NamedFieldsArray($this->data, array_keys($fields)));

		if (substr($filename, -4) !== '.' . $this->format) {
			$filename .= '.' . $this->format;
		}

		$earl->createWriter($report)->write($filename);
	}

	private function getAddress() {
		return '';
	}

	private function getTitle() {
		return 'Dossiers';
	}
}
