<?php

class BootstrapController extends Core\Controller
{
	/**************************************************************************/
	public function blockEditableAction($key)
	{
		$params = array();
		$params['key'] = $key;
		return $this->render('block-editable.html', $params);
	}


	/**************************************************************************/
	/**
	 * Return avatar url.
	 */
	public function avatarAction($size = 48)
	{
		$avatar = new Avatar();
		$user = $this->session->getUser();

		$params = array();
		$params['size'] = $size;
		$params['default'] = $avatar->getUrlDefault($size);
		$params['gravatar'] = $avatar->getUrlGravatar($size);
		if ($user) {
			$params['alt'] = $user->get('username');
		} else {
			$params['alt'] = 'User';
		}
		return $this->render('avatar.html', $params);
	}
}
