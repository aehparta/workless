<?php

/**
 * Frontpage controller.
 */
class FrontpageController extends Core\Controller
{
    /**************************************************************************/
    public function indexAction()
    {
        $params = array(
        	'roles' => array(),
        );

        $user = $this->session->getUser();
        if ($user) {
        	$params['roles'] = $user->getRoles();
    	}

        $this->display('frontpage.html', $params);
        return true;
    }
    
    
    /**************************************************************************/
    public function notFoundAction()
    {
        $params = array();
        $this->display('404.html', $params);
        return true;
    }
    
}

