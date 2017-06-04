<?php

class Avatar extends Core\Module
{
	/**************************************************************************/
	public function getUrl($size = false)
	{
		/* return gravatar url if possible */
		$url = $this->getUrlGravatar($size);
		if ($url)
		{
			return $url;
		}
		/* return default url */
		$url = $this->getUrlDefault($size);
		if ($url)
		{
			return $url;
		}
		return null;
	}

	/**************************************************************************/
	public function getUrlDefault($size = false)
	{
		$url = $this->getModuleValue('default');
		if ($url)
		{
			return $this->kernel->url($url);
		}
		return null;
	}

	/**************************************************************************/
	public function getUrlGravatar($size = false)
	{
		$user = $this->kernel->session->getUser();
		$gurl = $this->getModuleValue('gravatar', 'url');
		$d    = $this->getModuleValue('gravatar', 'default');
		if ($user && $gurl)
		{
			$email = $user->get('email');
			if ($email)
			{
				$url = $gurl . md5(strtolower(trim($email))) . '?'. ($d ? '&d=' . $d : '') . ($size ? '&s=' . $size : '');

				return $url;
			}
		}
		return null;
	}
}
