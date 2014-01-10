<?php

interface VirtualField extends ModelField {

	/**
	 * Sets the alias (i.e. field name) of the virtual field.
	 *
	 * @param string $alias
	 * @return VirtualField $this
	 */
	function setAlias($alias);

	function select(ModelTableQuery $query, $alias = null, QueryAliasable $aliasable = null);

	function getClause(ModelTableQuery $query, QueryAliasable $aliasable = null);

	function isCachable();

	function isSelectable(QueryAliasable $aliaser);

	function configureMeta($config);
}
