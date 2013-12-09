<?php

class HTML_Helper
{

	public $formId = 0;
	
	public $formType = '';

	public function HTML_Helper()
	{
		
	}
	
	public function escape($str, $escape = TRUE)
	{
		if ($escape)
		{
			return h($str);
		}
		return $str;
	}
	
	public function tag($name, $value = '', $attributes = array())
	{
		$escape = TRUE;
		if (isset($attributes['escape']))
		{
			$escape = $attributes['escape'];
			unset($attributes['escape']);
		}
		$open_tag = (isset($attributes['open']) && $attributes['open']);
		$end_tag  = (isset($attributes['end']) && $attributes['end']);
		unset($attributes['open'], $attributes['end']);
		
		$attrs = '';
		foreach ($attributes as $attr_name => $attr_value)
		{
			if ( ! is_bool($attr_value))
				$attrs .= ' ' . $this->escape($attr_name, $escape) . '="' . $this->escape($attr_value, $escape) . '"';
		}
		
		$format = '<%s%s>%s</%s>';
		
		if ((isset($attributes['empty']) && $attributes['empty']) OR 
			($value === '' && in_array($name, array('input', 'meta', 'br', 'hr', 'img'))))
		{
			//TODO: XHTML 分岐
			$format = '<%s%s>';
			unset($attributes['empty']);
		}
		if ($open_tag)
		{
			$format = '<%s%s>';
		}
		else if ($end_tag)
		{
			$format = '</%s>';
		}
		
		$html = sprintf($format, $name ,$attrs, $this->escape($value, $escape), $name);
		
		
		return $html;
	}
	
	/**
	 * create ORIGAMI url of params
	 *
	 * @params mixed $url url string OR page string OR command name and params;
	 */
	public function url($url = '')
	{
		global $script, $defaultpage;

		if ($url === '')
		{
			$url = $defaultpage;
		}
		
		if (is_array($url))
		{
			$http_query = http_build_query($url);
			return $script . '?' . $http_query;
		}
		else
		{
			if (is_url($url))
			{
				return $url;
			}
			else if (is_page($url))
			{
				return $script . '?' . rawurlencode($url);
			}
			else if (is_pagename($url))
			{
				return $script . '?cmd=edit&' . rawurlencode($url);
			}
			else
			{
				return $url;
			}
		}
	}
	
	public function link($label, $to = '', $options = array())
	{
		$to = $this->url($to);
		
		$options = array_merge(array(
			'href' => $to
		), $options);
		return $this->tag('a', $label, $options);
	}
	
	
	
	public function form($type = 'horizontal', $options = array())
	{
		global $script;
		
		$this->formId++;
		
		$options = array_merge(array(
			'action' => $script,
			'method' => 'GET',
		), $options);
		
		//bootstrap form type
		if (in_array($type, array('horizontal', 'search', 'inline', 'navbar')))
		{
			if ($type == 'navbar')
			{
				$options['class'] .= ' navbar-search pull-left';
			}
			else
			{
				$options['class'] .= ' form-' . $type;
			}
			
			$this->formType = $type;
		}
		
		$options['open'] = TRUE;
		
		return $this->tag('form', '', $options);
	}
	
	public function form_end($submit = FALSE)
	{
		if ($submit !== FALSE)
		{
			return $this->submit($submit) . $this->tag('form', '', array('end' => TRUE));
		}
		return $this->tag('form', '', array('end' => TRUE));
	}
	
	public function input($name, $options = array())
	{
		global $vars;
		//TODO: type 分岐
		$options = array_merge(array(
			'type' => 'text',
			'name' => $name,
			'label' => $name,
			'div' => TRUE,
			'value' => isset($vars[$name]) ? $vars[$name] : '',
			'data-error' => isset($vars[$name.'_error']) ? $vars[$name.'_error'] : '',
			'class' => 'form-control',
		), $options);
		
		$input_body = FALSE;
		if ($options['type'] === 'textarea')
		{
			$input_body = $this->textarea($name, $options);
		}
		else if ($options['type'] === 'hidden')
		{
			$options['div'] = FALSE;
		}
		else if ($options['type'] === 'radio')
		{
			$radio_data = $options['data'];
			unset($options['data']);
			$input_body = $this->radio($name, $radio_data, $options);
		}
		else if ($options['type'] === 'select')
		{
			$option_data = $options['data'];
			unset($options['data']);
			$input_body = $this->select($name, $option_data, $options);
		}
		
		//ラベル処理
		$format = '%s';
		if ($this->formType === 'horizontal' && $options['div'] !== FALSE)
		{
			$label = $options['label'] !== FALSE ? $this->tag('label', $options['label'], array('class' => 'control-label col-sm-3', 'for' => '', 'escape' => $options['escape'])) : '';
			$format = $this->tag('div',
				$label .
				$this->tag('div',
					'%s' .
					($options['help'] ? $this->tag('span', $options['help'], array('class' => 'help-block', 'escape' => $options['escape'])) : ''),
				array('class' => 'controls col-sm-9', 'escape' => FALSE)),
			array('class' => 'form-group', 'escape' => FALSE));
			
		}
		else if ($this->formType === '' && $options['div'] !== FALSE)
		{
			$label = $options['label'] !== FALSE ? $this->tag('label', $options['label'], array('for' => '', 'escape' => $options['escape'])) : '';
			$format = $this->tag('div', $label . '%s', array('escape' => FALSE));
		}
		unset($options['label'], $options['help']);
		
		if ($input_body === FALSE)
		{
			return sprintf($format, $this->tag('input', '', $options));
		}
		else
		{
			return sprintf($format, $input_body);
		}
	}
	
	public function textarea($name, $options = array())
	{
		$options = array_merge(array(
			'name' => $name,
			'cols' => '60',
			'rows' => '5',
			'data-exnote' => 'onready'
		), $options);
		
		$value = isset($options['value']) ? $options['value'] : '';
		
		unset($options['empty'], $options['value'], $options['help'], $options['label']);
		
		return $this->tag('textarea', $value, $options);
	}
	
	/**
	 * create single/malutiple input:radio
	 *
	 * @param string $name parameter name
	 * @param assoc $data radio data array [{label, value}, ...]
	 * @param assoc $options 
	 */
	public function radio($name, $data, $options = array())
	{
		global $vars;
		static $checked = FALSE;
		$default_value = isset($vars[$name]) ? $vars[$name] : '';
		$default_value = isset($options['value']) ? $options['value'] : $default_value;
		
		$body = '';
		
		if ( ! is_array($data)) return $body;
		
		$options = array_merge(array(
			'name' => $name
		), $options);

		$options['type'] = 'radio';
		
		if (range(0, count($data)-1) == array_keys($data))
		{
			$checked = FALSE;
			foreach ($data as $radio_data)
			{
				if ( ! $checked && $radio_data['value'] == $default_value)
				{
					$radio_data['checked'] = 'ckecked';
					$checked = TRUE;
				}
				$body .= $this->radio($name, $radio_data, $options);
			}
		}
		else
		{
			$format = '%s';
			if ($options['label'] !== FALSE OR isset($data['label']))
			{
				// label>input:radio
				$format = $this->tag('label', '%s %s', array('class'=>'radio'));
				unset($options['label']);
			}
			$options['value'] = $data['value'];
			if (isset($data['checked']))
			{
				$options['checked'] = $data['checked'];
			}
			$body = sprintf($format, $this->tag('input', '', $options), $this->escape($data['label'], $options['escape']));
		}
		
		return $body;
		
	}
	
	/**
	 * create single/malutiple input:radio
	 *
	 * @param string $name parameter name
	 * @param assoc $data option data array [{label, value}, ...]
	 * @param assoc $options 
	 */
	public function select($name, $data, $options = array())
	{
		global $vars;
		$body = '';
		
		$options = array_merge(array(
			'open' => TRUE,
			'value' => '',
		), $options);
		
		$value = isset($vars[$name]) ? $vars[$name] : $options['value'];
		unset($options['value'], $options['label']);
		
		$option_body = '';
		foreach ($data as $option_data)
		{
			$label = $option_data['label'];
			unset($option_data['label']);

			if ($value === $option_data['value'])
			{
				$option_data['selected'] = 'selected';
			}
			
			$option_body .= $this->tag('option', $label, $option_data);
		}
		
		return $this->tag('select', '', $options) . $option_body . '</select>';
		
	}
	
	public function hidden($name, $options = array())
	{
		$options = array_merge(array(
			'div' => FALSE,
		), $options);

		$options['type'] = 'hidden';
		
		return $this->input($name, $options);
	}
	
	public function submit($value = 'Submit', $options = array())
	{
		$options = array_merge(array(
			'class' => 'btn btn-primary',
			'value' => $value,
			'div'  => TRUE
		), $options);
		
		$options['type'] = 'submit';
		
		$format = '%s';
		if ($this->formType === 'horizontal' && $options['div'])
		{
			$format = $this->tag('div', '%s', array('class' => 'col-sm-offset-3 col-sm-9'));
			unset($options['wrap']);
		}
		
		return sprintf($format, $this->tag('input', '', $options));
	}
	
	public function getErrors()
	{
		global $vars;
		$errors = array();
		foreach ($vars as $key => $value)
		{
			if (substr($key, -6) === '_error')
			{
				$errors[substr($key, 0, -6)] = $value;
			}
		}
		return $errors;
	}
	
	public function error($name, $options = array())
	{
		global $vars;
		$body = '';
		
		$options = array_merge(array(
			'tag'   => 'span',
			'class' => 'text-error',
			'span'  => FALSE,
		), $options);
		
		$span = $options['span'];
		$tag = $options['tag'];
		
		unset($options['tag'], $options['span']);
		
		if (isset($vars[$name.'_error']))
		{
			$msg = $vars[$name.'_error'];
			if ($span)
			{
				$body = $this->tag($tag, $msg, $options);
			}
			else
			{
				$body = $msg;
			}
		}
		return $body;
	}
	
	public function alert($content, $type = '', $close = TRUE, $options = array())
	{
		
		if ( ! $content) return '';
		
		$options = array_merge(array(
			'tag' => 'div',
			'escape' => FALSE,
			'class' => '',
		), $options);
		
		$options['class'] .= ' alert';
		if ($type)
		{
			$options['class'] .= ' alert-' . h($type);
		}
		
		$tag = $options['tag'];
		unset($options['tag']);
		
		if ($close)
		{
			$closebtn = $this->tag('button', '&times;', array('escape'=>FALSE, 'data-dismiss'=>'alert', 'class'=>'close'));
			$content = $closebtn . $content;
		}
		

		return $this->tag($tag, $content, $options);
		
	}
	
}

/* End of file html_helper.php */
/* Location: lib/html_helper.php */