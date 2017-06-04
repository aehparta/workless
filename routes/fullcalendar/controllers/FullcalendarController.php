<?php

class FullcalendarController extends Core\Controller
{
	public function calendarAction()
	{
		$params = array();
		
		return $this->display('calendar.html', $params);
	}
}
