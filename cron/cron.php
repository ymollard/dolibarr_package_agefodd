<?php


class cron_agefodd
{

	private $db;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		global $db, $const;
		$this->db = $db;
		$this->const = $const;

	}


	public function sendAgendaToTrainee()
	{


		return 'This scheduled task is currently unavailable' ;
	}

}
