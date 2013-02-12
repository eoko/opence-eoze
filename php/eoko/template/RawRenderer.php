<?php

namespace eoko\template;

class RawRenderer extends Renderer {

	protected function doRender() {
		echo $this->getContent();
	}
}
