<?php

namespace Db\Parsers;
class MySQL extends \Component
{
	/**
	 * Parses SQL data and returns MySQL query for Db\QueryBuilder component
	 * @param array $table_aliases
	 * @param array $fields
	 * @param int $fieldsCount
	 * @param array $childModels
	 * @param array $sql
	 * @param array &$params
	 * @param Db\QueryBuilder $builder
	 * @param array &$localeFields
	 * @return string
	 **/
	public function parseQueryBuilderSqlData($table_aliases, $fields, $fieldsCount, $childModels, $sql, &$params, $builder, &$localeFields)
	{
		$rawSql = 'SELECT' . NL;
		$field_num = 0;

		$localeFields = array();

		foreach ($fields as $tName => $table)
		{
			if (!$tName || !$table)
				continue;

			$model = $builder->getModelByTable($tName);

			if (!$model)
				continue;

			$alias = $table_aliases[$tName];
			if (!$alias)
				$alias = 't' . rand(50, 100);

			$size_fields = sizeof($table);
			for ($i = 0; $i < $size_fields; ++$i)
			{
				if (!isset($table[$i]) || !$table[$i])
					continue;

				$skipAs = false;

				if (isset($sql['function']))
				{
					$function = $builder->getFunctionForField($builder->getModel()->m_table, $table[$i]);
					if ($function)
					{
						$alias_f_func = $builder->getAliasForFieldFunction($builder->getModel()->m_table, $table[$i]);

						if ($alias_f_func)
							$skipAs = true;

						$rawSql .= strtoupper($function) . '(' . '`' . $alias . '`.`' . $table[$i] . '`' . ')' . $alias_f_func;
					}
					else
						$rawSql .= '`' . $alias . '`.`' . $table[$i] . '`';
				}
				else
					$rawSql .= '`' . $alias . '`.`' . $table[$i] . '`';

				if (!$skipAs)
				{
					$tempAlias = null;

					// Check if this field is DbLocale field
					// If it is, set it to temporary name (for cases when localization is missing in DB)
					if (isset($model->m_fields[$table[$i]]) && $model->m_fields[$table[$i]] == 'DbLocale')
					{
						if (!isset($localeFields[$model->m_table]))
							$localeFields[$model->m_table] = array();

						$localeFields[$model->m_table][$table[$i] . '_temporary'] = array(
							'field' => $table[$i],
							'temp'  => $table[$i] . '_temporary',
							'alias' => (isset($model->m_aliases[$table[$i]]) ? $model->m_aliases[$table[$i]] : $table[$i])
						);

						$tempAlias = $table[$i] . '_temporary';
					}

					$rawSql .= ' AS `';

					if ($tempAlias != null)
						$rawSql .= $tempAlias;
					elseif (isset($model->m_aliases[$table[$i]]))
						$rawSql .= $model->m_aliases[$table[$i]];
					else
						$rawSql .= $table[$i];

					$rawSql .= '`';
				}

				$field_num++;

				if ($field_num < $fieldsCount)
					$rawSql .= ',';

				$rawSql .= NL;
			}
		}

		$rawSql .= 'FROM `' . $builder->getModel()->m_table . '` AS `' . $table_aliases[$builder->getModel()->m_table] . '`' . NL;

		if (isset($sql['join']))
		{
			$join_sql = array();

			$join_size = sizeof($sql['join']);

			for ($i = 0; $i < $join_size; ++$i)
			{
				$j = &$sql['join'][$i];

				if (!isset($j['model']) || !isset($childModels[$j['model']]))
					continue;

				if (!isset($join_sql[$j['model']]))
					$join_sql[$j['model']] = '';

				$alias = $table_aliases[$childModels[$j['model']]->m_table];

				$mJoin_table = $builder->getModel()->m_table;
				$mJoin_alias = $table_aliases[$builder->getModel()->m_table];

				if (isset($j['join_model']))
				{
					$model = $builder->getModelByName($j['join_model']);

					if ($model)
					{
						$mJoin_table = $model->m_table;
						$mJoin_alias = $table_aliases[$model->m_table];
					}
				}

				if ($join_sql[$j['model']] == '')
				{
					$join_sql[$j['model']] .= strtoupper($j['type']) . ' JOIN `' . $childModels[$j['model']]->m_table . '`';
					$join_sql[$j['model']] .= ' AS `' . $alias . '` ON `' . $alias . '`.`' . $j['child'] . '` = ';

					if (!$j['child_value'])
						$join_sql[$j['model']] .= '`' . $mJoin_alias . '`.`' . $j['parent'] . '`';
					else
						$join_sql[$j['model']] .= '\'' . $j['child_value'] . '\'';
				}
				else
				{
					$join_sql[$j['model']] .= ' AND `' . $alias . '`.`' . $j['child'] . '` = ';

					if (!$j['child_value'])
						$join_sql[$j['model']] .= '`' . $mJoin_alias . '`.`' . $j['parent'] . '`';
					else
						$join_sql[$j['model']] .= '\'' . $j['child_value'] . '\'';
				}

				$join_sql[$j['model']] .= NL;
			}

			if ($join_sql)
				foreach ($join_sql as $join_rawSql)
					$rawSql .= $join_rawSql;
		}

		if (isset($sql['where']))
		{
			$rawSql .= 'WHERE' . NL;

			$changed = false;

			$count = sizeof($sql['where']);
			$current = 0;

			foreach ($sql['where'] as $cond)
			{
				if ((!isset($cond['table']) || !isset($cond['field']) || !isset($cond['condition'])) && !isset($cond['multi']))
					continue;

				if (isset($cond['multi']))
				{
					if (!isset($cond['conditions']) || !$cond['conditions'])
						continue;

					$rawSql .= ' (';
					$cSize = sizeof($cond['conditions']);
					$cCurrent = 0;
					foreach ($cond['conditions'] as $c)
					{
						if (!$c)
							continue;

						if (is_array($c[2]))
						{
							$tmp = $builder->arrayConditionToString($c[2], $alias, $c[1]);

							if ($tmp)
								$rawSql .= $tmp;
						}
						else
							$rawSql .= '`' . $alias . '`.`' . $c[1] . '`' . $c[2];

						++$cCurrent;

						if ($cCurrent < $cSize)
							$rawSql .= ' ' . $cond['insideCond'] . ' ';
					}
					$rawSql .= ' )';
					
				}
				else
				{
					$alias = $table_aliases[$cond['table']];
					if (is_array($cond['condition']))
					{
						$tmp = $builder->arrayConditionToString($cond['condition'], $alias, $cond['field']);

						if ($tmp)
							$rawSql .= $tmp;
					}
					else
					{
						if (isset($cond['like']) && $cond['like'])
							$rawSql .= '`' . $alias . '`.' . $cond['field'] . ' ' . $cond['condition'];
						else
							$rawSql .= ($cond['binary'] ? ' BINARY ' : '') . '`' . $alias . '`.`' . $cond['field'] . '`' . $cond['condition'];
					}
				}

				++$current;
				if ($current < $count)
				{
					if (!isset($cond['next']))
						$rawSql .= ' AND';
					else
						$rawSql .= ' ' . $cond['next'];
				}

				if (isset($cond['params']))
					$params = array_merge($params, $cond['params']);
				
				$changed = true;

				$rawSql .= NL;
			}
			if (!$changed)
				$rawSql .= ' 1' . NL;
		}

		if (isset($sql['group']))
		{
			$g = $sql['group'][0];
			if ($g)
				$rawSql .= 'GROUP BY `' . $table_aliases[$builder->getModelByName($g['model'])->m_table] . '`.`' . $g['field'] . '`' . NL;
		}

		if (isset($sql['random'], $sql['random'][0]))
		{
			if (isset($sql['random'][0]['useRandom']) && $sql['random'][0]['useRandom'])
				$rawSql .= 'ORDER BY RAND()' . NL;
		}
		else if (isset($sql['order']))
		{
			$rawSql .= 'ORDER BY ' . NL;

			$multiorder = false;
			foreach ($sql['order'] as &$entry)
			{
				if (!isset($entry[0]) || !is_array($entry[0]))
					continue;

				$fields_info = $entry[0];
				$type = $entry[1];
				foreach ($fields_info as $model => &$fields)
				{
					$m = $builder->getModelByName($model);
					if (!$m)
						continue;

					$current = 0;
					$size = sizeof($fields);

					foreach ($fields as $probKey => &$field)
					{
						if (is_array($field) && (!is_numeric($probKey) && is_string($probKey)))
						{
							// Using multi order
							$multiorder = true;
							$rawSql .= '`' . $table_aliases[$m->m_table] . '`.`' . $probKey . '` ' . strtoupper($field[0]);
						}
						else
							$rawSql .= '`' . $table_aliases[$m->m_table] . '`.`' . $field . '` ';

						if ($current < $size-1)
							$rawSql .= ', ';
		
						++$current;
					}
				}
			}
			if (!$multiorder)
				$rawSql .= ' ' . strtoupper($type);
		}

		if (isset($sql['limit']))
		{
			if (isset($sql['limit'][0][1]))
			{
				$rawSql .= ' LIMIT ' . $sql['limit'][0][1];
				if (isset($sql['limit'][0][0]))
					$rawSql .= ', ' . $sql['limit'][0][0];

				$rawSql .= NL;
			}
		}

		return $rawSql;
	}
};