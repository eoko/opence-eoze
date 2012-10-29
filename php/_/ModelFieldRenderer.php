<?php

abstract class ModelFieldRenderer {

	public abstract function render($value);

	public static function apply($renderer, $value, Model $model) {
		if ($renderer instanceof ModelFieldRenderer) {
			return $renderer->render($value, $model);
		} else if (is_callable($renderer)) {
			return call_user_func($renderer, $value, $model);
		} else if (is_array($renderer)) {
			return self::applyArrayRenderer($renderer, $value);
		} else if (is_string($renderer)) {
			return self::applyStringRenderer($renderer, $model);
		} else {
			throw new IllegalArgumentException("Invalid renderer: $renderer");
		}
	}

	/**
	 * Apply the given format string to the given model and return the result
	 * formatted string. The format string accepts Model's field names enclosed
	 * in %% (e.g. '%name%'). To prevent the character % to be parsed, it must
	 * be escaped with a backslash (e.g. '10\\%' -- notice the first extra
	 * backslash is to avoid interpretation by the PHP string parser).
	 * @param string $format
	 * @param Model $model
	 * @return string
	 */
	public static function applyStringRenderer($format, Model $model) {
		return str_replace('\\%', '%',
			preg_replace_callback('/([^\\\\]?)%([^% ]+)%/', function($m) use($model) {
				return $m[1] . $model->__get($m[2]);
			}, $format)
		);
	}

	public static function applyArrayRenderer($renderer, $value) {
		if (isset($renderer[$value])) {
			return $renderer[$value];
		} else if (isset($renderer[null])) {
			// null is the renderer default value
			if ($renderer[null] === false) {
				// false value for the default renderer means an exception
				// should be thrown
				throw new IllegalStateException(
					"Illegal value for field renderer: $value"
				);
			} else {
				return $renderer[null];
			}
		} else {
			// return vanilla value
			return $value;
		}
	}

}