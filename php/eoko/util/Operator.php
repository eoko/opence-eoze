<?php

namespace eoko\util;

interface Operator {

	const EQUAL         = '=';
	const MORE          = '>';
	const MORE_OR_EQUAL = '>=';
	const LESS          = '<';
	const LESS_OR_EQUAL = '<=';

}

interface HasOperator extends Operator {

}
