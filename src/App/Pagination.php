<?php

namespace App;

/**
 * Class Pagination
 * Gère la pagination SQL et HTML pour les listes (forums, réponses, etc.).
 */
class Pagination
{
    /** @var \PDO */
    private $cnx;

    /** @var string */
    private $statement;

    /** @var mixed|null */
    private $attr;

    /** @var int */
    private $perpage;

    /** @var mixed */
    private $count;

    /** @var mixed */
    private $router;

    /** @var mixed */
    private $flash;

    /**
     * Pagination constructor.
     *
     * @param \PDO       $cnx       Connexion PDO
     * @param mixed      $router    Router pour générer des routes
     * @param string     $statement Nom de la requête SQL
     * @param int|null   $attr      Paramètre de filtre éventuel
     * @param int|null   $perpage   Résultats par page
     * @param mixed|null $flash     Service de flash message
     */
    public function __construct(
        $cnx,
        mixed $router,
        string $statement,
        ?int $attr = null,
        ?int $perpage = 10,
        mixed $flash = null
    ) {
        $this->cnx = $cnx;
        $this->statement = $statement;
        $this->attr = $attr;
        $this->perpage = $perpage;
        $this->router = $router;
        $this->flash = $flash;
    }
	
	/**
	 * Perpage
	 *
	 * @return int
	 */
	public function Perpage(): int
	{
		return (int) $this->perpage;
	}

    /**
     * getInt vérifie si l'index page est bien un int redirige si non
     *
     * @param  mixed $name
     * @param  mixed $default
     * @return int
     */
    private function getInt(string $name, ?int $default = null): ?int
    {
        $match = $this->router->matchRoute();
        if(!isset($_GET[$name])) return $default;
        	if($_GET[$name] === '0') return 0;
				if(!filter_var($_GET[$name], FILTER_VALIDATE_INT)) {
					if(isset($match['params']) && $match['params'] != null){
						if(isset($match['params']['slug']) && $match['params']['slug'] != null){
							header('Location:' . $this->router->routeGenerate($match['name'], ['slug' => $match['params']['slug'], 'id' => $match['params']['id']]));
						}else{
							header('Location:' . $this->router->routeGenerate($match['name'], ['id' => $match['params']['id']]));
						}
					}else{
						header('Location:' . $this->router->routeGenerate($match['name']));
					}
					$this->flash->setFlash("Le paramètre $name dans l'url n'est pas un entier",'orange');
					http_response_code(301);
					exit();
				}
        return (int) $_GET[$name];
    }

    /**
     * getPositiveInt vérifie si l'index page est bien un int positive redirige si non
     *
     * @param  mixed $name
     * @param  mixed $default
     * @return int
     */
    private function getPositiveInt(string $name, ?int $default = null): ?int
    {
		$match = $this->router->matchRoute();
        $param = self::getInt($name, $default);
        if($param !== null && $param <= 0){
            if(isset($match['params']) && $match['params'] != null){
                if(isset($match['params']['slug']) && $match['params']['slug'] != null){
                    header('Location:' . $this->router->routeGenerate($match['name'], ['slug' => $match['params']['slug'], 'id' => $match['params']['id']]));
                }else{
                    header('Location:' . $this->router->routeGenerate($match['name'], ['id' => $match['params']['id']]));
                }
            }else{
                header('Location:' . $this->router->routeGenerate($match['name']));
            }
            $this->flash->setFlash("Le paramètre $name dans l'url n'est pas un entier positif",'orange');
            http_response_code(301);
            exit();
        }
        return $param;
    }

	
	/**
	 * isExistPage vérifie si une page existe redirige si non
	 *
	 * @return void
	 */
	public function isExistPage(): void
	{
		$match = $this->router->matchRoute();
		if($this->CurrentPage() > $this->PageTotal() && $this->CurrentPage() > 1) {
			$this->flash->setflash("Ce numéro de page n'existe pas","orange");
			if(isset($match['params']['slug']) && $match['params']['slug'] != null){
				header('Location:' . $this->router->routeGenerate($match['name'], ['slug' => $match['params']['slug'], 'id' => $match['params']['id']]));
			}else{
				header('Location:' . $this->router->routeGenerate($match['name'], ['id' => $match['params']['id']]));
			}
			http_response_code(301);
			exit();
		}
	}

    /**
     * Retourne la page courante (GET ?page=...).
     */
	public function CurrentPage(): int
	{
		return $this->getPositiveInt('page', 1);
	}

	
	/**
	 * isPage retourn la page en get démarre de zero
	 *
	 * @return int
	 */
	public function PageTotal()
	{
		return ceil($this->cnx->CountIdForpagination($this->statement,$this->attr,$this->count)/$this->perpage);
	}

	/**
	 * userLinkPage retourn un lien qui redirige vers la dernière réponse
	 */
	public function userLinkPage(int $id,int $idrep,int $countid): string
	{
		$t = (int) ceil($countid/$this->perpage);
		if($this->perpage >= 1){
			if($t === 1){
			  $userLinkPage =  $this->router->routeGenerate('viewtopic', ['id' => $id]).'#rep-' . $idrep;
			}else{
			  $userLinkPage =  $this->router->routeGenerate('viewtopic', ['id' => $id]).'?page='.$t.'#rep-' . $idrep;
			}
		}
		return $userLinkPage;
	}
	
    /**
     * Calcule l'offset SQL.
     */
	private function offset(): int 
	{
		return $this->perpage * ($this->CurrentPage() - 1);
	}
		
    /**
     * Retourne la clause SQL OFFSET LIMIT.
     */
	public function setOffset(): string
	{
		return ' '. intval($this->perpage) .' OFFSET '. intval($this->offset());
	}

    /**
     * Génère le bouton "Page précédente" pour une pagination classique.
     */
	public function Prev(string $url): ?string
	{
		$match = $this->router->matchRoute();
		if($this->PageTotal() >= 2):
			if ($this->CurrentPage() > 1) {
				$link = $url;
				if ($this->CurrentPage() > 2) {
					$link .= "?page=" . ($this->CurrentPage() - 1);
				}
				$here = $link;
				return "<li class='page-item'><a class='page-link' href='$here'><i class='fas fa-angle-double-left'></i></a></li>";
			} else {
				return '<li class="disabled page-item"><a class="page-link"><i class="fas fa-angle-double-left"></i></a></li>';
			}
		endif;
	}

    /**
     * Génère le bouton "Page suivante" pour une pagination classique.
     */
	public function Next(string $url): ?string
	{
		$match = $this->router->matchRoute();
		if($this->PageTotal() >= 2):
			if ($this->CurrentPage() < $this->PageTotal()) {

				$curentplus = $url."?page=" . ($this->CurrentPage()+1);
				return "<li class='page-item'><a class='page-link' href='$curentplus'><i class='fas fa-angle-double-right'></i></a></li>";

			} else {
				return '<li class="disabled page-item"><a class="page-link"><i class="fas fa-angle-double-right"></i></a></li>';
			}
		endif;
	}

    /**
     * Génère une mini-pagination en carrousel (tinyPagination).
     * Affiche un nombre fixe de pages visibles avec des flèches pour défiler.
     */
	public function tinyPagination(int $id, int $countid)
	{
		$t = (int) ceil($countid / $this->perpage);
		if ($t > 1) {
			echo '<div class="uri">';
			echo '  <span class="urileft"><i class="fas fa-caret-left"></i></span>';
			echo '  <div class="item-container">'; // Conteneur pour les items défilants

			for ($i = 1; $i <= $t; $i++) {
				$href = $i === 1
					? $this->router->routeGenerate('viewtopic', ['id' => $id])
					: $this->router->routeGenerate('viewtopic', ['id' => $id]) . '?page=' . $i;

				echo "<a class=\"item\" href=\"$href\">$i</a>";
			}

			echo '  </div>'; // fin .item-container
			echo '  <span class="uriright"><i class="fas fa-caret-right"></i></span>';
			echo '</div>';
		}
	}

	/**
     * Génère la pagination complète avec liens + "..."
     */
	public function pageFor(): string
	{
		$match = $this->router->matchRoute();

		if ($this->PageTotal() < 2) {
			return '';
		}

		// Détermine l'URL de base en fonction de la route
		$routeName = $match['name'] ?? null;
		$params = $match['params'] ?? [];

		switch ($routeName) {
			case 'forum-tags':
				$url = $this->router->routeGenerate($routeName, [
					'slug' => $params['slug'],
					'id' => $params['id']
				]);
				break;

			case 'viewtopic':
				$url = $this->router->routeGenerate($routeName, [
					'id' => $params['id']
				]);
				break;

			case 'forum':
			default:
				$url = $this->router->routeGenerate($routeName);
				break;
		}

		$currentPage = $this->CurrentPage();
		$totalPages = $this->PageTotal();
		$visiblePages = 2;

		$output = $this->prev($url);

		for ($i = 1; $i <= $totalPages; $i++) {
			$isNearCurrent = abs($i - $currentPage) < $visiblePages;
			$isAtStartOrEnd = $i <= $visiblePages || $i > $totalPages - $visiblePages;

			if ($isAtStartOrEnd || $isNearCurrent) {
				if ($i === $currentPage) {
					$output .= "<li class='page-item active'><a class='page-link'>{$i}</a></li>";
				} else {
					$href = ($i === 1) ? $url : "{$url}?page={$i}";
					$output .= "<li class='page-item'><a class='page-link' href='{$href}'>{$i}</a></li>";
				}
			} else {
				// Évite l'affichage de plusieurs "..."
				if ($i < $currentPage - $visiblePages) {
					$i = $currentPage - $visiblePages;
				} elseif ($i > $currentPage + $visiblePages && $i < $totalPages - $visiblePages) {
					$i = $totalPages - $visiblePages;
				}

				$ellipsisPage = $i - 1;
				$ellipsisHref = "{$url}?page={$ellipsisPage}";
				$output .= "<li class='page-item'><a class='page-link' href='{$ellipsisHref}'>...</a></li>";
			}
		}

		$output .= $this->next($url);
		
		return $output;
	}

}
