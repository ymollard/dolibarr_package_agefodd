<?php
// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');

/**
 * Manage certificate
 */
class Agefodd_stagiaire_soc_history extends CommonObject
{
	public $error; // !< To return error code (or message)
	public $errors = array(); // !< To return several error codes (or messages)
	public $element = 'agefodd_stagiaire_soc_history'; // !< Id that identify managed objects
	public $table_element = 'agefodd_stagiaire_soc_history'; // !< Name of table without prefix where object is stored
	public $id;
	public $fk_stagiaire;
	public $fk_soc;
	public $fk_user_creat;
	public $datec;
	public $tms;
	public $date_start;
	public $date_end;

	public $fields=array(
		'date_start' => array ('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'position' => 500,  'visible' => 1),
		'date_end' => array ('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'position' => 501,  'visible' => 1),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'User', 'enabled'=>1, 'position'=>510, 'notnull'=>1, 'visible'=>1, 'foreignkey'=>'user.rowid'),
		'fk_stagiaire' => array('type'=>'integer:Agefodd_Stagiaire:agefodd/class/agefodd_stagiaire.class.php', 'label'=>'AgfFichePresByTraineeTraineeTitleM', 'enabled'=>1, 'position'=>511, 'notnull'=>0, 'visible'=>0),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'Company', 'enabled'=>1, 'position'=>499, 'notnull'=>0, 'visible'=>1)
	);

	/**
	 * Agefodd_stagiaire_soc_history constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		global $conf;
		$this->db = $db;
		$this->init();
	}

	/**
	 * Historize soc of trainee
	 * @param   int   $fk_soc
	 * @param   bool  $onCreate
	 * @return  int   > 0 IF OK, < 0 IF KO
	 */
	public function historize($fk_soc, $onCreate = false) {
		global $user;
		if(!$onCreate) {
			$this->fetchCommon(0,'', ' AND date_end IS NULL AND fk_stagiaire = '.$this->fk_stagiaire);
			if ($this->fk_soc == $fk_soc) return 1;
			$this->date_end = dol_now();
			$res = $this->updateCommon($user);
			if($res < 0) return -1;
		}
		$this->id = 0;
		$this->fk_user_creat = $user->id;
		$this->fk_soc = $fk_soc;
		$this->date_start = dol_now();
		$this->date_end = null;
		$res = $this->createCommon($user);
		return $res;
	}

	/**
	 * Delete All history of a trainee
	 * @param int   $fk_stagiaire
	 * @return int > 0 IF OK, < 0 IF KO
	 */
	public function deleteByStagiaire($fk_stagiaire) {
		global $user;

		$THistory = $this->fetchByStagiaire($fk_stagiaire);
		if(!empty($THistory)) {
			foreach($THistory as $history) {
				$res = $history->deleteCommon($user);
				if($res < 0) return -1;
			}
		}
		return 1;
	}

	/**
	 * @param int   $fk_stagiaire
	 * @return array
	 */
	public function fetchByStagiaire($fk_stagiaire) {
		$THistory = array();
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE fk_stagiaire = '.$fk_stagiaire;
		$resql = $this->db->query($sql);
		if($resql && $this->db->num_rows($resql) > 0) {
			while($obj = $this->db->fetch_object($resql)) {
				$tempHistory = new self($this->db);
				$tempHistory->fetchCommon($obj->rowid);
				$THistory[$obj->rowid] = $tempHistory;
			}
		}
		return $THistory;
	}

	/**
	 * Function to init fields
	 *
	 * @return bool
	 */
	protected function init()
	{
		$this->id = 0;
		$this->date_creation = 0;
		$this->tms = 0;

		if(!isset($this->fields['rowid'])) $this->fields['rowid']=array('type'=>'integer','index'=>true);
		if(!isset($this->fields['datec'])) $this->fields['datec']=array('type'=>'date');
		if(!isset($this->fields['tms'])) $this->fields['tms']=array('type'=>'date');

		if (!empty($this->fields))
		{
			foreach ($this->fields as $field=>$info)
			{
				if ($this->isDate($info)) $this->{$field} = time();
				elseif ($this->isArray($info)) $this->{$field} = array();
				elseif ($this->isInt($info)) $this->{$field} = (int) 0;
				elseif ($this->isFloat($info)) $this->{$field} = (double) 0;
				else $this->{$field} = '';
			}

			$this->to_delete=false;
			$this->is_clone=false;

			return true;
		}
		else
		{
			return false;
		}

	}

}
