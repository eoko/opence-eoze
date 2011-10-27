<?php

namespace eoze\util;

/**
 * Generic interface for reading values in a (key, value) data source.
 * 
 * Implementing classes are expected to consider a dot '.' character in
 * the $key as a separator. That is, the following must be TRUE:
 * 
 * <code>
 * $data->get('path')->get('to') === $data->get('path.to');
 * </code>
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
interface Data {
	
	/**
	 * Returns TRUE if the $key is set in the data source, even if the value
	 * is NULL.
	 * 
	 * @param string $key The tested $key, dot separated (see {@link Data})
	 */
	function has($key);
	
	/**
	 * Get the value for the given $key. If no value is set for the given $key
	 * in the data source, then an exception will be raised (NULL value are
	 * considered to be set, as opposed to the {@link isset()} function behaviour).
	 * 
	 * @param string $key The tested $key, dot separated (see {@link Data})
	 */
	function get($key);
	
	/**
	 * Get the value for the given $key. If no value is set for the given $key
	 * in the data source, then $default will be returned (NULL value are
	 * considered to be set, as opposed to the {@link isset()} function behaviour).
	 * 
	 * @param string $key The tested $key, dot separated (see {@link Data})
	 */
	function getOr($key, $default = null);
}
