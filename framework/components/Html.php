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

class Html extends Component
{
	protected $m_html = '';

	/**
	 	$wrappers = array(
			'checkbox' => array(
				'wrapper' => 'div',
				'attribs' => array(
					'class' => 'input checkbox'
				)
			),
			'text' => array(
				'wrapper' => 'div',
				'attribs' => array(
					'class' => 'input text long'
				),
			)
	 	);
	 **/
	/**
	 * Generates HTML form of Model's fields
	 * @param string $modelName
	 * @param array $values = array()
	 * @param array $attribs = array()
	 * @param array $wrappers = array()
	 * @return string
	 **/
	public function generateFormFields($modelName, $values = array(), $attribs = array(), $wrappers = array())
	{
		$model = $this->i('\Models\\' . $modelName);

		if (!$model || !$model->m_formFields)
			return '';

		$html = '';

		foreach ($model->m_fields as $field => $type)
		{
			if (isset($wrappers[$field]))
			{
				$html .= '<' . $wrappers[$field]['wrapper'];
				if (isset($wrappers[$field]['attribs']) && $wrappers[$field]['attribs'])
					foreach ($wrappers[$field]['attribs'] as $attr => $val)
						$html .= ' ' . $attr . '="' . $val . '"';

				$html .= '>';
			}

			$labelId = isset($attribs[$field]['id']) ? $attribs[$field]['id'] : null;

			$html .= '<label' . ($labelId ? ' for="' . $labelId . '"' : '') . '>';

			$label = $this->c('I18n')->getString('forms.' . strtolower($modelName) . '.' . $field);

			if ($label != 'forms.' . strtolower($modelName) . '.' . $field)
				$html .= $label;
			else
				$html .= ucwords(str_replace('_', ' ', strtolower($field)));
			$html .= '</label>';

			if ($model->m_formFields[$field] == 'textarea')
				$html .= '<textarea';
			elseif ($model->m_formFields[$field] == 'select')
				$html .= '<select';
			else
				$html .= '<input type="' . $model->m_formFields[$field] . '" value="' . (isset($values[$field]) ? $values[$field] : '') . '" ';

			if (isset($attribs[$field]) && $attribs[$field])
			{
				if (in_array($model->m_formFields[$field], array('checkbox', 'radio')) && isset($attribs[$field]['id']))
					$labelId = $attribs[$field]['id'];

				foreach ($attribs[$field] as $attr => $val)
					$html .= ' ' . $attr . '="' . $val . '"';
			}

			if ($model->m_formFields[$field] == 'textarea')
				$html .= '>' . (isset($values[$field]) ? $values[$field] : '') . '</textarea>';
			elseif ($model->m_formFields[$field] == 'select')
			{
				$html .= '>';

				if (isset($values[$field]) && $values[$field])
				{
					foreach ($values[$field] as $fv)
					{
						if (!$fv || !isset($fv['value']))
							continue;

						$html .= '<option value="' . $fv['value'] . '" ' . (isset($fv['selected']) ? 'selected="selected"' : '') . '>' . $this->c('I18n')->getString($fv['caption']) . '</option>';
					}
				}
			}
			else
				$html .= ' />';

			if (isset($wrappers[$field]))
				$html .= '</' . $wrappers[$field]['wrapper'] . '>';
		}

		return $html;
	}
};