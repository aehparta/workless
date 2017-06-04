<?php

class WorklessController extends Core\Controller
{
	private $workless = null;
	private $projects = null;

	public function __construct($name, $config, $path, $slugs)
	{
		parent::__construct($name, $config, $path, $slugs);
		$this->workless = new Workless();
		$this->projects = $this->workless->getAll();
	}

	private function getProject($name)
	{
		if (!isset($this->projects[$name]))
		{
			throw new Exception404('Project does not exist: ' . $name);
		}
		return $this->projects[$name];
	}

	public function projectsAction()
	{
		$params             = array();
		$params['projects'] = $this->projects;
		return $this->display('projects.html', $params);
	}

	public function projectsNaviAction()
	{
		$params             = array();
		$params['projects'] = $this->projects;
		return $this->display('projects-navi.html', $params);
	}

	public function projectCreateAction()
	{
		$params = array();

		if ($this->kernel->method == 'post')
		{
			$data    = $this->input('data');
			$project = $this->workless->projectCreate($data);
			if ($project)
			{
				throw new RedirectException($this->route('project', array('name' => $project['name'])), 302);
			}
			else
			{
				$this->kernel->msg('error', $this->workless->getError());
			}
			$params['data'] = $data;
		}

		return $this->display('project-create.html', $params);
	}

	public function projectAction($name, $page, $path = false)
	{
		$project  = $this->getProject($name);
		$bin      = new Bin();
		$path     = urldecode($path);
		$file_key = $project['bin'] . '/documents/' . $path;

		if ($path && $page == 'documents')
		{
			if ($bin->readfile($file_key, true, true))
			{
				return true;
			}
		}

		if (!is_dir($bin->getFilepath($file_key)))
		{
			throw new Exception404('Invalid path: ' . $path);
		}

		$params                        = array();
		$params['project']             = $project;
		$params['page']                = $page;
		$params['path']                = $path;
		$params['bins']['description'] = $project['bin'] . '/description';

		return $this->display('project.html', $params);
	}

	public function calendarAction($name, $year = null, $month = null)
	{
		$project = $this->getProject($name);

		$params            = array();
		$params['project'] = $project;
		$params['page']    = 'calendar';
		$params['url']     = $this->route('calendar', array('name' => $name));
		if ($year !== null && $month !== null)
		{
			$params['defaultDate'] = $year . '-' . $month . '-1';
		}

		return $this->display('project.html', $params);
	}

	public function filesAction($name, $path = false)
	{
		$project = $this->getProject($name);
		$bin     = new Bin();

		$files_path = $project['bin'] . '/documents';
		$parent_len = strlen($files_path . '/');
		if ($path !== false)
		{
			$files_path .= '/' . $path;
		}

		$files = $bin->getFiles($files_path);
		foreach ($files as &$file)
		{
			$file['relative'] = substr($file['key'], $parent_len);
			$file['url']      = $this->route('project_documents', array('name' => $name, 'path' => $file['relative']));
		}

		$params          = array();
		$params['name']  = $name;
		$params['bin']   = $files_path;
		$params['files'] = $files;

		$current_path = $this->route('project', array('name' => $name, 'page' => 'documents'));
		$breadcrumbs  = array(
			array(
				'name' => 'Documents',
				'url'  => $current_path,
			),
		);
		foreach (explode('/', $path) as $folder)
		{
			if (strlen($folder) < 1)
			{
				continue;
			}
			$current_path .= $folder . '/';
			$breadcrumbs[] = array(
				'name' => $folder,
				'url'  => $current_path,
			);
		}
		$params['breadcrumbs'] = $breadcrumbs;

		return $this->display('project-files.html', $params);
	}

	public function calendarDayAction($name, $year, $month, $day)
	{
		$this->kernel->historyDisable();
		$project = $this->getProject($name);
		$bin     = new Bin();

		if (!checkdate($month, $day, $year))
		{
			throw new Exception404('Invalid date.');
		}

		$key               = $project['bin'] . '/calendar/' . $year . '/' . $month . '/' . $day;
		$key_doc           = $key . '/document';
		$files_path        = $key . '/attachments';
		$page              = 1;
		$new               = true;
		$previous_document = null;

		/* calculate page */
		$key_cal = $project['bin'] . '/calendar';
		$years   = $bin->getFiles($key_cal);
		foreach ($years as $y)
		{
			if ($y['type'] != 'dir')
			{
				continue;
			}
			$months = $bin->getFiles($y['key']);
			foreach ($months as $m)
			{
				if ($m['type'] != 'dir')
				{
					continue;
				}
				$days = $bin->getFiles($m['key']);
				foreach ($days as $d)
				{
					if ($d['type'] != 'dir')
					{
						continue;
					}
					$path = $bin->getFilepath($d['key'] . '/document');
					if (!file_exists($path))
					{
						continue;
					}
					$previous_document = $d['key'] . '/document';
					if (mktime(0, 0, 0, $month, $day, $year) <= mktime(0, 0, 0, $m['name'], $d['name'], $y['name']))
					{
						break;
					}
					$page++;
				}
			}
		}

		/* setup data */
		$params            = array();
		$params['project'] = $name;
		$params['key']     = $key_doc;
		$params['date']    = mktime(0, 0, 0, $month, $day, $year);
		$params['year']    = $year;
		$params['month']   = $month;
		$params['day']     = $day;
		$params['weekday'] = $this->tr('weekdays/' . date('N', $params['date']));
		$params['data']    = null;
		$params['bin']     = $files_path;
		$params['page']    = $page;

		if ($bin->read($key_doc, $data))
		{
			$params['data'] = @json_decode($data, true);
			if ($params['data'] !== null)
			{
				$new = false;
			}
		}
		$params['new'] = $new;
		if ($new)
		{
			$params['data']            = array();
			$params['data']['created'] = time();

			/* load previous document if it exists */
			if ($previous_document !== null && $bin->read($previous_document, $previous_data))
			{
				$previous_data = @json_decode($previous_data, true);
				$started       = array();
				$unfinished    = array();
				if (isset($previous_data['work']['progress']['started']))
				{
					$started = $previous_data['work']['progress']['started'];
				}
				if (isset($previous_data['work']['progress']['unfinished']))
				{
					$unfinished = $previous_data['work']['progress']['unfinished'];
				}
				$params['data']['work']['progress']['unfinished'] = array_merge($started, $unfinished);
				if (isset($previous_data['machines']))
				{
					$params['data']['machines'] = $previous_data['machines'];
				}
				if (isset($previous_data['workers']))
				{
					$params['data']['workers'] = $previous_data['workers'];
				}
			}
		}

		$files      = $bin->getFiles($files_path);
		$parent_len = strlen($files_path . '/');
		foreach ($files as &$file)
		{
			$file['relative'] = substr($file['key'], $parent_len);
			$file['url']      = $this->route('bin:dl', array('key' => $file['key']));
		}
		$params['files'] = $files;

		return $this->display('worksite-document.html', $params);
	}

	public function calendarTimeframeAction($name, $year1, $month1, $day1, $year2, $month2, $day2)
	{
		$this->kernel->historyDisable();
		$project = $this->getProject($name);

		if (!checkdate($month1, $day1, $year1))
		{
			throw new Exception404('Invalid from date.');
		}
		if (!checkdate($month2, $day2, $year2))
		{
			throw new Exception404('Invalid to date.');
		}
		$from = mktime(0, 0, 0, $month1, $day1, $year1);
		$to   = mktime(0, 0, 0, $month2, $day2, $year2);
		if ($from > $to)
		{
			throw new Exception404('Invalid from date must be earlier date that to date');
		}

		$bin    = new Bin();
		$events = array();

		for ($time = $from; $time <= $to; $time += (24 * 60 * 60))
		{
			$year    = date('Y', $time);
			$month   = date('n', $time);
			$day     = date('j', $time);
			$key     = $project['bin'] . '/calendar/' . $year . '/' . $month . '/' . $day;
			$key_doc = $key . '/document';

			if (!$bin->read($key_doc, $data))
			{
				continue;
			}
			$data = @json_decode($data, true);
			if ($data === null)
			{
				continue;
			}

			$slugs    = array('name' => $name, 'year' => $year, 'month' => $month, 'day' => $day);
			$events[] = array(
				'url'       => $this->route('calendar_day', $slugs),
				'title'     => '',
				'start'     => date('Y-m-d', $time),
				'accepted'  => isset($data['accepted']) ? true : false,
				'confirmed' => isset($data['confirmed']) ? true : false,
			);
		}

		$params           = array();
		$params['events'] = $events;

		return $this->display(null, $params);
	}

	public function documentAction($name, $action, $year, $month, $day)
	{
		$this->kernel->historyDisable();
		$project = $this->getProject($name);

		if (!checkdate($month, $day, $year))
		{
			throw new Exception404('Invalid date.');
		}

		$key     = $project['bin'] . '/calendar/' . $year . '/' . $month . '/' . $day;
		$key_doc = $key . '/document';
		$bin     = new Bin();

		if ($action == 'delete')
		{
			if (!$this->authorize('role:foreman'))
			{
				throw new Exception403('Access denied.');
			}
			if (!$bin->delete($key))
			{
				throw new Exception500('Document not found.');
			}
			throw new RedirectException($this->kernel->historyPop());
		}

		if (!$bin->read($key_doc, $data))
		{
			throw new Exception500('Document not found.');
		}

		$data = @json_decode($data, true);
		if ($data === null)
		{
			throw new Exception500('Document data is invalid.');
		}

		$user = $this->session->getUser();
		$name = $user->get('name');
		if (strlen($name) < 1)
		{
			$name = $user->get('username');
		}

		if ($action == 'confirm' && $this->authorize('role:foreman') && empty($data['confirmed']))
		{
			$data['confirmed']      = $name;
			$data['confirmed_date'] = time();
		}
		else if ($action == 'accept' && $this->authorize('role:supervisor') && empty($data['accepted']))
		{
			$data['accepted']      = $name;
			$data['accepted_date'] = time();
		}
		else
		{
			throw new Exception500('Invalid document action.');
		}

		if (!$this->authorize('role:foreman') && !$this->authorize('role:supervisor'))
		{
			throw new Exception403('Access denied.');
		}

		// foreach ()
		if (!$bin->save($key_doc, json_encode($data)))
		{
			throw new Exception500('Failed to save document data.');
		}

		throw new RedirectException($this->kernel->historyPop());
	}

	public function adminAction($name)
	{
		$params  = array();
		$project = $this->getProject($name);
		$access  = array('user:' . $this->session->get('username'));

		$key = $project['bin'] . '/.access';
		$bin = new Bin();
		if ($bin->read($key, $data))
		{
			$data = @json_decode($data, true);
			if ($data !== null)
			{
				$access = $data;
			}
		}

		$params['users'] = array();
		foreach ($access as $user)
		{
			$a = explode(':', $user);
			if (count($a) != 2)
			{
				continue;
			}
			if ($a[0] != 'user')
			{
				continue;
			}
			$user = $this->session->getUser($a[1]);
			if ($user)
			{
				$params['users'][] = $user;
			}
		}

		$params['project'] = $name;

		$this->display('project-admin.html', $params);
	}

	public function accessAction($name, $username)
	{
		$project = $this->getProject($name);
		$key     = $project['bin'] . '/.access';
		$access  = array();
		$bin     = new Bin();
		if ($bin->read($key, $data))
		{
			$data = @json_decode($data, true);
			if ($data !== null)
			{
				$access = $data;
			}
		}

		if ($this->kernel->method == 'put')
		{
			$user_a = 'user:' . $username;
			if (!in_array($user_a, $access))
			{
				$access[] = $user_a;
				$bin->save($key, json_encode($access));
			}
		}
		else if ($this->kernel->method == 'delete')
		{
			$user_a = 'user:' . $username;
			if (in_array($user_a, $access))
			{
				$access = array_diff($access, array($user_a));
				$bin->save($key, json_encode($access));
			}
		}

		$this->display();
	}
}
