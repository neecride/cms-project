<?php

namespace App;

use Parsedown;

class Parsing{

    public function __construct()
    {}

    /**
     * SetParse instance de erusev Parsedown
     *
     * @return mixed
     */
    public function SetParse()
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        return $parsedown;
    }

    /**************
    * function prurification parser et smilyser
    ***************/
    public function Rendertext($content)
    {
        $content = $this->SetParse()->text($content);
        return $content;
    }

    public function Renderline($content)
    {
        $content = $this->SetParse()->line($content);
        return $content;
    }

    public function JustDemo(){
        return "**bonjour je suis du [markdown](https://www.markdownguide.org/basic-syntax/)**\n\n> c'est cool, comme le bbcode ça permet de se passé des balises html\n\n";
    }

    public function SimpleTextarea(string $id, string $sql=null,string $editor = 'editor1')
    {
        $req = isset($sql) && !empty($sql) ? $sql : $this->JustDemo() ;
        $value = isset($_POST[$id]) && !empty($_POST[$id]) ? htmlentities($_POST[$id]) : $req ;
        return "<textarea style=\"height: 200px;\" type='text' class='form-control' id=\"$editor\" name=\"$id\">$value</textarea>";
    }

    public function MarkDownEditor(string $id, string $sql=null,string $editor = 'editor1')
    {
        $req = isset($sql) && !empty($sql) ? $sql : $this->JustDemo() ;
        $value = isset($_POST[$id]) && !empty($_POST[$id]) ? strip_tags($_POST[$id]) : $req ;
        return "<textarea style=\"color:#515151;\" type='text' class='markdown' data-language='fr' data-height='100px' class='myarea form-control' id=\"$editor\" name=\"$id\">$value</textarea><div id='preview'> </div>";
    }

    public function MarkdownPerso(string $id, string $sql = null) {
        $req = isset($sql) && !empty($sql) ? $sql : $this->JustDemo();
        $value = isset($_POST[$id]) && !empty($_POST[$id]) ? strip_tags($_POST[$id]) : $req;
        $html = '<div class="form-group">';
        $html .= "<textarea style=\"color:#515151;\" class=\"editable\" name=\"$id\">$value</textarea>";
        $html .= '</div>';

        return $html;
    }

    public function input(string $id,string $type,string $PlaceHolder=null,string $required=null,string $sql=null)
    {
        $req = isset($sql) && !empty($sql) ? $sql : null ;
        //stocke la req si elle existe sinon met $_POST
        $value = isset($_POST[$id]) && !empty($_POST[$id]) ? strip_tags($_POST[$id]) : $req ;
        return "<input type=\"$type\" class=\"HoTagsI form-control\" id=\"$id\" name=\"$id\" placeholder=\"$PlaceHolder\" value=\"$value\" $required>";
    }
    
    /**
     * JchoisesInput
     *
     * @param  mixed $k
     * @return void
     */
    public function JchoisesInput(mixed $k)
    {
        $html =  '<select class="form-control js-choice" name="tags[]" multiple>';
        $html .= '<option value="">Choisissez vos tags</option>';
        foreach($k as $v){
            $html .= "<option value='$v->id'>".strip_tags($v->name)."</option>";
        }
        $html .= '</select>';

        return $html;
    }
	
	/**
	 * checkFilesOptions
	 *
	 * @param  mixed $sql
	 * @return void
	 */
	public function checkFilesOptions(string $sql = null)
    {
		$fichiers = scandir(RACINE.DS.'public'.DS.'templates');
        // Parcourt la liste des fichiers et dossiers
        foreach ($fichiers as $fichier) :
            $selected = null;
            if(isset($sql) && !empty($sql == $fichier)){
                $selected =  ' selected';
			}
            // Ignore les fichiers qui ne sont pas des dossiers ou le dossier courant ou le dossier parent
            if (is_dir(RACINE.DS.'public'.DS.'templates' . DS . $fichier) && $fichier !== '.' && $fichier !== '..') {
                // Affiche le nom du dossier
                echo "<option value='$fichier'$selected>$fichier</option>";
            }
        endforeach;
	}

}