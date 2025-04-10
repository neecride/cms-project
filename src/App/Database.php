<?php

namespace App;

use PDO;

class Database{
	
    private $dbname;
    private $dbuser;
    private $dbhost;
    private $dbpass;
    private ?PDO $pdo = null;

    public function __construct()
	{

		// Vérifie si le fichier config existe
		$configFile = RACINE. DS . 'lib' . DS .'config.php';
		if (!file_exists($configFile)) {
			throw new \RuntimeException('Le fichier de configuration est introuvable. Veuillez exécuter le script d\'installation.');
		}
	
		// Inclut le fichier de configuration
		require_once $configFile;


       	$this->dbhost = DBHOST;
        $this->dbname = DBNAME; 
        $this->dbuser = DBUSER; 
        $this->dbpass = DBPSWD; 
    }

	protected function Getpdo(): PDO {
		if ($this->pdo === null) {
			try {
				$pdo = new PDO(
					"mysql:dbname={$this->dbname};host={$this->dbhost}",
					$this->dbuser,
					$this->dbpass,
					[
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
					]
				);
				$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->pdo = $pdo;
			} catch (\PDOException $e) {
				// Enregistre le message d'erreur détaillé dans les logs
				error_log('[Database Error]: ' . $e->getMessage());
	
				// Lance une nouvelle exception avec un message générique
				throw new \RuntimeException('Impossible de se connecter à la base de données.');
			}
		}
	
		return $this->pdo;
	}
	

	/**
	 * thisPDO
	 *
	 * @return PDO
	 */
	public function thisPDO(): PDO
	{
		return $this->Getpdo();
	}

    /**
     * Request
     *
     * @param  mixed $statement
     * @param  array $attrs
     * @param  int $one
     * @return mixed
     */
    public function Request(string $statement,?array $attrs=null,?int $one=null)
	{
        if(!is_null($attrs))
		{
            $req = $this->getPDO()->prepare($statement);
            $req->execute($attrs);
            if(!is_null($one)){
            	//retour 1 result
         		return $req->fetch();
            }else{
            	//return all result
                return $req->fetchAll();
            }
        }else{
			$req = $this->getPDO()->query($statement);
			if(!is_null($one)){
				//retour 1 result
				return $req->fetch();
            }else{
            	//return all result
                return $req->fetchAll();
            }
        }
        return null;
    }
	
	/**
	 * CountObj
	 *
	 * @param  mixed $statement
	 * @param  mixed $attr
	 * @return void
	 */
	public function CountObj(string $statement,array $attr)
	{

		$counter = $this->Getpdo()->prepare($statement,$attr);
        $counter->execute($attr);
        $count = $counter->fetchObject();
        return $count;
	}
	
	/**
	 * LastInsertID
	 *
	 * @return int
	 */
	public function LastInsertID(): int
	{
		return $this->Getpdo()->lastInsertId();
	}

}