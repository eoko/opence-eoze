<?php

namespace eoko\util;

interface HasProxy {

	public function &attach(&$var);
}

