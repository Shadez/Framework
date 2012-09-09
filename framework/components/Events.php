<?php

/**
 * Copyright (C) 2011-2012 Shadez <https://github.com/Shadez/Framework>
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

class Events extends Component
{
	private $m_eventsDisabled = false;
	protected $m_events = array();

	public function initialize()
	{
		$this->m_events = array();

		$this->m_eventsDisabled = (bool) $this->c('Config')->getValue('site.events.disabled');

		return $this;
	}

	/**
	 * Checks if event can be triggered
	 * @param string $event
	 * @return bool
	 **/
	protected function isCanBeTriggered($event)
	{
		if ($this->m_eventsDisabled)
			return false;

		if (!isset($this->m_events[$event]))
			return false;

		if ($this->m_events[$event]['disabled'])
			return false;

		if (!is_callable($this->m_events[$event]['callback']))
			return false;

		return true;
	}

	/**
	 * Creates event
	 * @param string $event
	 * @param callable $callback
	 * @return Events_Component
	 **/
	public function createEvent($event, $callback)
	{
		if (!isset($this->m_events[$event]) && !$this->m_eventsDisabled)
		{
			$this->m_events[$event] = array(
				'event' => $event,
				'created' => microtime(),
				'triggered' => 0,
				'callback' => $callback,
				'disabled' => false,
				'eventData' => array()
			);
		}
		else
			$this->m_events[$event]['callback'] = $callback;

		return $this;
	}

	/**
	 * Removes callback for specific event
	 * @param string $event
	 * @return Events_Component
	 **/
	public function deleteCallback($event)
	{
		if (!isset($this->m_events[$event]) || $this->m_eventsDisabled)
			return $this;

		$this->m_events[$event]['callback'] = null;

		return $this;
	}

	/**
	 * Triggers event
	 * @param string $event
	 * @param array $eventData
	 * @param Object $triggeredBy = null
	 * @return array
	 **/
	public function triggerEvent($event, $eventData, $triggeredBy = null)
	{
		if ($this->m_eventsDisabled)
			return array();

		if (!$this->isCanBeTriggered($event))
		{
			$this->c('Log')->writeError('%s : event "%s" can not be called!', __METHOD__, $event);

			return array();
		}

		$eventData['triggeredBy'] = $triggeredBy;

		$this->m_events[$event]['triggered'] = microtime();
		$this->m_events[$event]['eventData'] = $eventData;

		$cbData = call_user_func($this->m_events[$event]['callback'], $this->m_events[$event]);

		return $cbData;
	}

	/**
	 * Disables event
	 * @param string $event
	 * @return Events_Component
	 **/
	public function disableEvent($event)
	{
		if (isset($this->m_events[$event]))
			$this->m_events[$event]['disabled'] = true;

		return $this;
	}
};