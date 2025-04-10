<?php

namespace Action;

use App\Database;

class TagAction {

	private Database $cnx;

	public function __construct()
	{
		$this->cnx = new Database;
 	}

    /**
     * Met à jour les tags associés à un topic
     * @param int $topicId L'ID du topic
     * @param array $tags Les nouveaux tags à associer au topic
     * @return bool true si les tags ont été mis à jour avec succès, false sinon
     */
    public function updateTopicTags(int $topicId, array $tags): bool 
    {
        // Vérifier que chaque tag est unique
        if (count($tags) != count(array_unique($tags))) 
        {
            return false; // Il y a des tags en double
        }
        // Supprimer les anciens tags qui ne font plus partie de la liste
        $this->cnx->Request("DELETE FROM f_topic_tags WHERE topic_id = ? AND tag_id NOT IN (" . implode(",", $tags) . ")",[$topicId]);
        // Ajouter les nouveaux tags qui ne sont pas déjà associés au topic
        // on vérifie que les tags existe via la class validator pas besoin de le faire ici
        $existingTags = $this->getExistTopicTags($topicId);
        foreach ($tags as $tag) 
        {
            //on insert que les nouveau tags
            if (!in_array($tag,$existingTags)) 
            {
                if($this->tagExists($tag))
                {
                    $this->cnx->Request("INSERT INTO f_topic_tags (topic_id, tag_id) VALUES (?, ?)",[$topicId, $tag]);
                }
            }
        }
        return true; // Les tags ont été mis à jour avec succès
    }

    /**
     * insertTagsOnNewTopic insert les tags pour le nouveau topic
     *
     * @param  array $tags
     * @param  int $lastID
     * @return void
     */
    public function insertTagsOnNewTopic(array $tags, int $lastID): void
    {
        foreach($tags as $item)
        {
            $this->cnx->Request("INSERT INTO f_topic_tags SET tag_id = ?, topic_id = ?",[$item, $lastID]);
        }
    }

    /**
     * Vérifie si un tag avec l'ID donné existe dans la base de données.
     * @param int $tagId L'ID du tag à rechercher
     * @return bool true si le tag existe, false sinon
     */
    public function tagExists(int $tagId)
    {
        return $this->cnx->CountObj("SELECT COUNT(*) FROM f_topic_tags WHERE id = ?",[$tagId]);
    }

    /**
     * Récupère les tags existants pour un topic donné dans la table topic_tags.
     * @param  $topic_id ID du topic pour lequel récupérer les tags.
     * @return array Tableau contenant les ID des tags existants pour le topic.
     */
    private function getExistTopicTags($topic_id): array 
    {
        $stmt = $this->cnx->Request("SELECT tag_id FROM f_topic_tags WHERE topic_id = ?",[$topic_id]);
        $existing_topic_tags = array();
        foreach ($stmt as $row) {
            $existing_topic_tags[] = $row->tag_id;
        }
        return $existing_topic_tags;
    }


  }