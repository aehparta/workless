<?php

/**
 * ProjectEntity
 */
class ProjectEntity
{
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $access;

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set name
	 *
	 * @param  string          $name
	 * @return ProjectEntity
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set access
	 *
	 * @param  string          $access
	 * @return ProjectEntity
	 */
	public function setAccess($access)
	{
		$this->access = $access;

		return $this;
	}

	/**
	 * Get access
	 *
	 * @return string
	 */
	public function getAccess()
	{
		return $this->access;
	}
}
