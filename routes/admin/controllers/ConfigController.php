<?php

/**
 * Config controller.
 */
class ConfigController extends Core\Controller
{
	/**************************************************************************/
	public function indexAction()
	{
		$params           = array();
		$params['config'] = $this->config;

		$this->display('config.html', $params);
		return true;
	}
}
