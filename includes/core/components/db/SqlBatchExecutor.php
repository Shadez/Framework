<?php

/**
 * Copyright (C) 2009-2012 Shadez <https://github.com/Shadez>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 **/

class SqlBatchExecutor_Db_Component extends Component
{
	protected $m_sql = '';
	protected $m_items = null;
	protected $m_analyzed = false;
	protected $m_runType = '';
	protected $m_modelInfo = array();
	protected $m_result = null;

	public function execute($items)
	{
		if (!is_array($items))
			throw new SqlBatchExecutor_Exception_Component('Unknown type of object passed!');

		return $this->clearValues()
			->setItems($items)
			->runSql()
			->m_result;
	}

	private function clearValues()
	{
		$this->m_sql = '';
		$this->m_items = array();
		$this->m_analyzed = false;
		$this->m_runType = '';
		$this->m_modelInfo = array();
		$this->m_result = null;

		return $this;
	}

	private function runSql()
	{
		if (!$this->m_analyzed)
			return $this;

		switch ($this->m_runType)
		{
			case 'update':
				$this->parseUpdateSql();
				break;
			case 'insert':
				$this->parseInsertSql();
				break;
		}

		if (!$this->m_sql)
			throw new SqlBatchExecutor_Exception_Component('No SQL query found!');

		if ($this->m_runType == 'update')
		{
			$result = true;

			foreach ($this->m_sql as $sql)
				if (!$this->c('Db')->{$this->m_modelInfo['dbType']}()->query($sql))
					$result = false;
		}
		else
			$result = $this->c('Db')->{$this->m_modelInfo['dbType']}()->query($this->m_sql);

		$this->m_result = $result;

		unset($result);

		return $this;
	}

	protected function parseUpdateSql()
	{
		$sql = array();

		foreach ($this->m_items as &$it)
			if ($it)
				$sql[] = $it->getSql(true);

		$this->m_sql = $sql;

		return $this;
	}

	protected function parseInsertSql()
	{
		$items = array();

		$sql = '';

		foreach ($this->m_items as &$it)
		{
			if (!$it)
				continue;

			if (!$sql)
				$sql = $it->getSql(1);

			$items[] = $it->getSql(2);
		}

		$items_sql = implode(', ', $items);

		$sql .= $items_sql . ';';

		$this->m_sql = $sql;

		return $this;
	}

	protected function setItems($items)
	{
		$this->m_items = $items;

		return $this->analyzeItems();
	}

	private function analyzeItems()
	{
		if (!$this->m_items)
			return $this;

		$first_model = is_object($this->m_items[0]) ? $this->m_items[0]->getModelInfo() : false;

		if (!$first_model)
			return $this;

		$size = sizeof($this->m_items);

		if ($size > 1)
		{
			for ($i = 1; $i < $size; ++$i)
			{
				if (!$this->m_items[$i])
					return $this;

				if (!$this->compare($first_model, $this->m_items[$i]->getModelInfo()))
					return $this;
			}
		}

		$this->m_analyzed = true;

		if ($first_model['type'] == 'update')
			$this->m_runType = 'update';
		elseif ($first_model['type'] == 'insert')
			$this->m_runType = 'insert';
		else
			throw new SqlBatchExecutor_Exception_Component('Unknown execution type provided ("' . $first_model['type'] . '")!');

		$this->m_modelInfo = $first_model;

		return $this;
	}

	private function compare($model1, $model2)
	{
		if (!$model1 || !$model2 || !isset($model1['name'], $model1['dbType'], $model1['type']) || !isset($model2['name'], $model2['dbType'], $model2['type']))
			return false;

		return $model1['name'] == $model2['name'] && $model1['dbType'] == $model2['dbType'] && $model1['type'] == $model2['type'];
	}

	private function getSqlFromItem($id)
	{
		if (!$id || !isset($this->m_items[$id]) || !$this->m_items[$id])
			return '';

		return $this->m_items[$id]->getSql();
	}
}