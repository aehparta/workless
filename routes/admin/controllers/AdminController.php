<?php

/**
 * Admin controller.
 */
class AdminController extends Core\Controller
{
	public function indexAction()
	{
		$params = array();
		$this->display('index.html', $params);
		return true;
	}

	public function usersAction()
	{
		$users  = $this->session->getUsers();
		$params = array();

		if ($this->format == 'json')
		{
			foreach ($users as $user)
			{
				$params['users'][] = $user->toArray();
			}
		}
		else
		{
			$params['users'] = $users;
		}

		return $this->display('users.html', $params);
	}

	public function userDeleteAction($username)
	{
		$this->kernel->historyDisable();

		$user = $this->findUser($username);
		if (!$user)
		{
			throw new Exception500($this->tr('msg/error/user-invalid'));
		}

		if (!$user->delete())
		{
			throw new Exception500($this->tr('msg/error/user-delete'));
		}
		else
		{
			$this->kernel->msg('success', $this->tr('msg/success/user-delete'));
		}

		if ($this->kernel->method != 'delete')
		{
			throw new RedirectException($this->kernel->historyPop(), 302);
		}

		return $this->display();
	}

	public function userRolesAction($username, $role = null)
	{
		$this->kernel->historyDisable();

		$user = $this->findUser($username);
		if (!$user)
		{
			throw new Exception500($this->tr('msg/error/user-invalid'));
		}

		$data = array();
		if ($this->kernel->method == 'get')
		{
			$data = $user->getRoles();
		}
		else if ($role === null)
		{
			throw new Exception500($this->tr('msg/error/user-role-required'));
		}
		else if ($role == 'role:root')
		{
			throw new Exception500($this->tr('msg/error/user-role-required'));
		}
		else if ($this->kernel->method == 'delete')
		{
			$user->removeRole($role);
		}
		else if ($this->kernel->method == 'put' || $this->kernel->method == 'post')
		{
			$user->addRole($role);
		}

		if ($this->kernel->method == 'post')
		{
			throw new RedirectException($this->kernel->historyPop(), 302);
		}

		$this->display(null, $data);
	}

	public function rolesAction()
	{
		$roles           = new RolesDefault();
		$params          = array();
		$params['roles'] = $roles->getAll();
		$params['users'] = $this->session->getUsers();

		/* do not show root */
		// unset($params['roles']['root']);

		return $this->display('roles.html', $params);
	}

	public function roleAction($role)
	{
		$params = array();

		return $this->display('role.html', $params);
	}

	private function findUser($username)
	{
		$user           = null;
		$authenticators = $this->kernel->getConfigValue('modules', 'Core\Session', 'authenticators');
		foreach ($authenticators as $userclass)
		{
			try
			{
				$user = new $userclass($username);
			}
			catch (Exception $e)
			{
				$user = null;
				continue;
			}
			break;
		}
		return $user;
	}
}
