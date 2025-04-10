<?php 

namespace App;

use DateTime;
use IntlDateFormatter;

Class Parameters{

	private Database $cnx;

	public function __construct()
	{
		$this->cnx = new Database;
	}

	private function Params()
    {
	    return $this->cnx->Request("SELECT * FROM parameters");
	}
	
	/**
	 * GetParam
	 *
	 * @param  mixed $Param_id
	 * @param  mixed $activ
	 * @return mixed
	 */
	public function GetParam($Param_id,$activ='param_value')
    {
		return $this->Params()[$Param_id]->$activ;
	}

    public function AppDate($date){

        (int) $dateType = IntlDateFormatter::MEDIUM;
        (int) $timeType = IntlDateFormatter::NONE;
        $datefmt = datefmt_create('fr_FR',$dateType,$timeType,'Europe/Paris');

        return datefmt_format($datefmt ,strtotime($date));
    }
    
    /**
     * getTimeAgo permet d'afficher la date
     *
     * @param  mixed $postDate
     * @return void
     */
    public function getTimeAgo($postDate)
	{
		$now = new DateTime();
		$postDateTime = new DateTime($postDate);
		$interval = $postDateTime->diff($now);
		if ($interval->y >= 1) {
		  return 'il y a ' . $interval->y . ' an' . ($interval->y > 1 ? 's' : '');
		} elseif ($interval->m >= 1) {
		  return 'il y a ' . $interval->m . ' mois';
		} elseif ($interval->d >= 1) {
		  return 'il y a ' . $interval->d . ' jour' . ($interval->d > 1 ? 's' : '');
		} elseif ($interval->h >= 1) {
		  return 'il y a ' . $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
		} elseif ($interval->i >= 1) {
		  return 'il y a ' . $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
		} else {
		  return 'posté à l\'instant';
		}
	}

    /**
     * themeForLayout initialise le theme
     *
     * @param mixed $param
     */
    public function themeForLayout(): string
    {
        return $this->GetParam(3);
    }

}