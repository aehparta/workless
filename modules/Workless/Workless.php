<?php

class Workless extends Core\Module
{
	private $bin_key = 'workless/projects';

	public function __construct()
	{
		parent::__construct();
	}

	public function getAll()
	{
		$bin      = new Bin();
		$files    = $bin->getFiles($this->bin_key);
		$projects = array();
		foreach ($files as $file)
		{
			if ($file['type'] != 'dir')
			{
				continue;
			}

			$name = $file['name'];
			$key  = $this->bin_key . '/' . $name;
			if (!$bin->checkAccess($key))
			{
				continue;
			}

			$projects[$name] = array(
				'name' => $name,
				'bin'  => $key,
			);
		}
		return $projects;
	}

	public function projectCreate($data)
	{
		if (!isset($data['name']))
		{
			$this->logError('Project name is missing.');
			return false;
		}
		$name = trim($data['name']);
		if (strlen($name) < 1)
		{
			$this->logError('Project name is invalid.');
			return false;
		}
		if (strpos($name, '/') !== false)
		{
			$this->logError('Character "/" is not allowed in project name.');
			return false;
		}

		$bin = new Bin();
		$key = $this->bin_key . '/' . $name;
		if ($bin->stat($key))
		{
			$this->logError('Project already exists: ' . $name);
			return false;
		}

		if (!$bin->folderCreate($key))
		{
			$this->logError('Unable to create new project: ' . $name);
			return false;
		}

		$bin->folderCreate($key . '/documents');
		$bin->folderCreate($key . '/calendar');

		$access = array('user:'.$this->kernel->session->get('username'));
		$bin->save($key . '/.access', json_encode($access));

		$project = array(
			'name' => $name,
			'bin'  => $key,
		);

		return $project;
	}
}
