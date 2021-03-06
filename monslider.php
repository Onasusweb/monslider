<?php

define('IMG_PATH',_PS_MODULE_DIR_.'monslider/pictures/images/');
define('THUMB_PATH',_PS_MODULE_DIR_.'monslider/pictures/thumbs/');
define('CROP_PATH',_PS_MODULE_DIR_.'monslider/pictures/crop/');
class monslider extends Module{
	
	public function __construct()
	{
	  $this->name = 'monslider';
	  $this->tab = 'Modules';
	  $this->version = '1.0';
	 
	  parent::__construct();
	 
	  $this->displayName = $this->l('HomeSlider Gallery Module');
	  $this->description = $this->l('Nice JQuery Gallery Slider');
	  $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
	}
	
	public function install()
	{
	  if(!parent::install()
        || !$this->setDefaults()
	    || !$this->registerHook('home')
        || !$this->registerHook('header')
        || !$this->registerHook('footer')
	    || !$this->registerHook('backOfficeHeader')
	    || !Configuration::updateValue('MOD_SLIDER_IMG', '../modules/monslider/imgs/logo_admin.png')
		|| !$this->installModuleTab('AdminSlider', array(1=>'My Photo Gallery Slider', 2=>'Ma Galerie Photo'), 2)
		/*Appel de la fonction de création de la table :*/
        || !$this->createTable()
        )
	    return false;
	  return true;
	}
	
	public function uninstall()
	{
	  if(!parent::uninstall()
	    || !Configuration::deleteByName('MOD_SLIDER_IMG')
	    || !$this->uninstallModuleTab('AdminSlider')
		//Suppression des dossiers images et thumbnails
		|| !$this->deleteFiles('../modules/monslider/pictures/images/')
        || !$this->deleteFiles('../modules/monslider/pictures/thumbs/')
        || !$this->deleteFiles('../modules/monslider/pictures/crop/')
        //Suppression de la table :
		|| !$this->deleteTable()
        )
	    return false;
	  return true;
	}
    
    public function setDefaults() {
        $defaults = array(      
            'CROPW' => '500',
            'CROPH' => '350'
        );
        $retval = TRUE;
        foreach ($defaults as $k => $v) $retval = $this->_set($k, $v) && $retval;
        return $retval;
    }
    protected function _set($varname, $value) {
        return Configuration::updateValue(strtoupper($this->name).'_'.$varname, $value);
    }
    
    protected function _unset($varname) {
        return Configuration::deleteByName(strtoupper($this->name).'_'.$varname);
    }
    
    public function _get($varname) {
        return Configuration::get(strtoupper($this->name).'_'.$varname);
    }
    
    //fonctionne mais retourne une erreur...à voir!
    public function deleteFiles($folder){
            
        $dossier=opendir($folder);
        
        while ($fichier = readdir($dossier))
        {
            if ($fichier != "." && $fichier != "..")
            {
                $Vidage= $folder.$fichier;
                @unlink($Vidage);
            }
        }
        closedir($dossier);
        return true;
    }
	
	public function getContent() {
	  $html = '';
	  if(Tools::isSubmit('submitSlider'))
	  {
	    if(Validate::isInt(Tools::getValue('cropW') && Validate::isInt(Tools::getValue('cropW'))))
	    {
    	    $this->_set('CROPW', Tools::getValue('newCropW'));
            $this->_set('CROPH', Tools::getValue('newCropH')); 
          
            $html .= $this->displayConfirmation($this->l('Settings updated.'));
	    }
	    else
	    {
	      $html .= $this->displayError($this->l('Invalid Value.'));
	    }
	  }
	  $cropW = $this->_get('CROPW');
      $cropH = $this->_get('CROPH');
	  $html .= '<h2>'.$this->l('Slider Module Configuration').'</h2>
	  <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
	    <fieldset>
	      <legend>'.$this->l('Gallery Settings').'</legend>
	      <h4>'.$this->l('Defines the gallery dimensions').'</h4>
	      <div class="margin-form">
	        <label for="cropW">'.$this->l('Set Gallery width').' : </label>	      
	        <input type="text" name="newCropW" value="'.$cropW.'" /><br /><br />
	        <label for="cropH">'.$this->l('Set Gallery height').' : </label>
	        <input type="text" name="newCropH" value="'.$cropH.'" />
	      </div>
	      <div class="clear center">
	        <p>&nbsp;</p>
	        <input class="button" type="submit" name="submitSlider" value="'.$this->l('   Save   ').'" />
	      </div>
	    </fieldset>
	  </form>';
	  return $html;
	}
	
	public function hookHome($params)
    {
        global $smarty;
        
        $sql = 'SELECT id,extension,titre FROM `'._DB_PREFIX_.'slider` WHERE publish = 1';
        $result = Db::getInstance()->ExecuteS($sql);
        $smarty->assign('resultat', $result) ;
        $chemin = 'modules/monslider/pictures/';
        $cheminImg = $chemin.'images/';
        $cheminThumbs = $chemin.'thumbs/';
        $cheminCrop = $chemin.'crop/';
        $smarty->assign('imgPath', $cheminImg) ;
        $smarty->assign('thumbPath', $cheminThumbs) ;
        $smarty->assign('cropPath', $cheminCrop) ;
        
        return $this->display(__FILE__, 'monslider.tpl');
    }
    public function hookHeader($params)
    {
        Tools::addCSS(_MODULE_DIR_.'monslider/css/style.css', 'all');
        Tools::addCSS(_MODULE_DIR_.'monslider/css/advanced-slider-base.css', 'all');
        Tools::addCSS(_MODULE_DIR_.'monslider/css/pixel/pixel.css', 'all');    
    }
    public function hookFooter($params)
    {
        global $smarty;
        $smarty->assign('JsPath', _MODULE_DIR_.'monslider/js') ;
        
        return $this->display(__FILE__, 'footer.tpl');
    }
    public function hookBackOfficeHeader($params)
    {
        //echo "<link type='text/css' href='../modules/monslider/css/ui-darkness/jquery-ui-1.8.18.custom.css' rel='stylesheet' />";
        //echo "<link type='text/css' href='../modules/monslider/css/south-street/jquery-ui-1.8.18.custom.css' rel='stylesheet' />";
        //echo "<link type='text/css' href='../modules/monslider/css/admin-slider.css' rel='stylesheet' />";
    }
    
    private function installModuleTab($tabClass, $tabName, $idTabParent)
    {
      @copy(_PS_MODULE_DIR_.$this->name.'/imgs/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');
      $tab = new Tab();
      $tab->name = $tabName;
      $tab->class_name = $tabClass;
      $tab->module = $this->name;
      $tab->id_parent = $idTabParent;
      if(!$tab->save())
        return false;
      return true;
    }
    
    private function uninstallModuleTab($tabClass)
    {
      $idTab = Tab::getIdFromClassName($tabClass);
      if($idTab != 0)
      {
        $tab = new Tab($idTab);
        $tab->delete();
        return true;
      }
      return false;
    }
    
    //Création de la table lors de l'installation
    private function createTable()
    {
        if (!Db::getInstance()->Execute(
        'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'slider` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `extension` varchar(5) NOT NULL,
          `titre` varchar(100) NOT NULL,
          `publish` tinyint(1) NOT NULL,
          `picsOrder` int(11) NOT NULL,
          `cropped` tinyint(1) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'
        ))        
            return false;
        return true;  
    }    
    //Suppression de la table lors de la désinstallation
    private function deleteTable()
    {
       return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'slider`;');        
    }
    
    public function selectCropped(){
        $sql='SELECT * FROM `'._DB_PREFIX_.'slider` ORDER BY id DESC';            
        if($row = Db::getInstance()->getRow($sql)){
             if ($row['cropped'] == 0)
                return false;
             else 
                 return true;
        }
        else
            return false;
    }
    
	public function addPicture(){
	    if(Tools::getValue('valider')){
            if(isset($_FILES['fichier'])) {                   
                $erreur = $_FILES['fichier']['error'];        
                if ($erreur == 0) {
                    $type = $_FILES['fichier']['type'];
                    $type_img = strtolower(substr($type, 0,5));
        
                    if ($type_img == "image") {
                        $tab = explode('/',$type);
                        $extension = $tab[1];
                        
                        
                        if($this->selectCropped()){            
                            $cropped = 0;
                            $sql='INSERT INTO `'._DB_PREFIX_.'slider` (`extension`,`cropped`) VALUES ("'.$extension.'",'.$cropped.') ';            
                            if($row = Db::getInstance()->Execute($sql)){                        
                                $id = Db::getInstance()->Insert_ID();                                
                            }
                        }
                        else{
                            $sql = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'slider` WHERE cropped=0 ORDER BY id DESC ');
                            if ($sql){
                                Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'slider` SET extension = "'.$extension.'" WHERE id= '.$sql['id'].' ');
                                $id = $sql['id'];
                            }
                            else{
                                $cropped = 0;
                                $sql='INSERT INTO `'._DB_PREFIX_.'slider` (`extension`,`cropped`) VALUES ("'.$extension.'",'.$cropped.') ';            
                                if($row = Db::getInstance()->Execute($sql)){                        
                                    $id = Db::getInstance()->Insert_ID();                                
                                }
                            }
                        }
                        
                        
                        $fichierFinal = $id .'.'. $extension;                        
                        move_uploaded_file($_FILES['fichier']['tmp_name'], IMG_PATH . $fichierFinal);
                        
                        switch ($extension) {
                            case 'jpeg':
                            case 'jpg':
                                $source   = imagecreatefromjpeg(IMG_PATH . $fichierFinal);
                                break;
                            case 'png':
                                $source   = imagecreatefrompng(IMG_PATH . $fichierFinal);                                
                                break;
                            case 'gif':
                                $source   = imagecreatefromgif(IMG_PATH . $fichierFinal);                                
                                break;
                        }
                        $source_x = imagesx($source);
                        $source_y = imagesy($source);
                        
                        return array( 'img'         =>$fichierFinal,
                                      'size_x'      =>$source_x,
                                      'size_y'      =>$source_y,
                                      'extension'   =>$extension,
                                      'id'          =>$id,
                                      //'publish'     =>$row['publish'],
                                      //'titre'       =>$row['titre']
                        );
                    }
                }
            }
        }
	}
   
   public function crop(){
        if(Tools::getValue('validCrop')){
                        
            $extension = Tools::getValue('extension');
            $titre = pSQL(Tools::getValue('titre'));
            $cropped = 1;
            if(Tools::getValue('publishStart')) $publishStart = 1; else $publishStart = 0;
            
            $id = Tools::getValue('idImg');
            
            $sql = 'UPDATE `'._DB_PREFIX_.'slider` SET titre= "'.$titre.'", cropped = '.$cropped.', publish = '.$publishStart.' WHERE id= '.$id.' ';
            
            
            if(Db::getInstance()->Execute($sql)){
                //$id = mysql_insert_id();    
                
                $fichierFinal = $id .'.'. $extension;
                
                switch ($extension) {
                            case 'jpeg':
                            case 'jpg':
                                $srcCrop   = imagecreatefromjpeg(IMG_PATH . $fichierFinal);
                                break;
                            case 'png':
                                $srcCrop   = imagecreatefrompng(IMG_PATH . $fichierFinal);                                
                                break;
                            case 'gif':
                                $srcCrop   = imagecreatefromgif(IMG_PATH . $fichierFinal);                                
                                break;
                }
                $cropPos_x = Tools::getValue('x');
                $cropPos_y = Tools::getValue('y');
                $srcCrop_w = Tools::getValue('w');
                $srcCrop_h = Tools::getValue('h');
                
                $cropW = $this->_get('CROPW');
                $cropH = $this->_get('CROPH');
                
                if($srcCrop_w < $cropW && $srcCrop_h < $cropH){
                    $crop_x = $srcCrop_w;
                    $crop_y = $srcCrop_h;
                }
                else{
                    $crop_x = $cropW;
                    $crop_y = $cropH;
                }
                $crop = imagecreatetruecolor($crop_x,$crop_y);
                
                imagecopy($crop,$srcCrop,0,0,$cropPos_x,$cropPos_y,$srcCrop_w,$srcCrop_h);

                switch ($extension) {
                            case 'jpeg':
                            case 'jpg':
                                imagejpeg($crop,CROP_PATH . $fichierFinal);
                                break;
                            case 'png':
                                imagepng($crop,CROP_PATH . $fichierFinal);                                
                                break;
                            case 'gif':
                                imagegif($crop,CROP_PATH . $fichierFinal);                               
                                break;
                }
                
                //Création de la vignette
                switch ($extension) {
                            case 'jpeg':
                            case 'jpg':
                                $source   = imagecreatefromjpeg(CROP_PATH . $fichierFinal);
                                break;
                            case 'png':
                                $source   = imagecreatefrompng(CROP_PATH . $fichierFinal);                                
                                break;
                            case 'gif':
                                $source   = imagecreatefromgif(CROP_PATH . $fichierFinal);                                
                                break;
                }
                
                $thumb_x_final = 80;
                $thumb_y_final = 50;
                
                $thumb      = imagecreatetruecolor($thumb_x_final,$thumb_y_final);
                 
                //On récupère les dimensions
                $source_x   = imagesx($source);
                $source_y   = imagesy($source);
                $thumb_x    = imagesx($thumb);
                $thumb_y    = imagesy($thumb);
                //pour conserver les proportions
                $thumb_y_final = ($thumb_x * $source_y) / $source_x;
                                            
                imagecopyresampled($thumb,$source,0,0,0,0,$thumb_x,$thumb_y_final,$source_x,$source_y);
                
                switch ($extension) {
                            case 'jpeg':
                            case 'jpg':
                                imagejpeg($thumb,THUMB_PATH . $fichierFinal);
                                break;
                            case 'png':
                                imagepng($thumb,THUMB_PATH . $fichierFinal);
                                break;
                            case 'gif':
                                imagegif($thumb,THUMB_PATH . $fichierFinal);
                                break;
                }
            }
        }
    }

    public function showImage(){
        $result = Db::getInstance()->ExecuteS('SELECT id, extension, titre, publish FROM `'._DB_PREFIX_.'slider` ORDER BY picsOrder ASC');
        if (!$result) 
            return false;
        return $result;
    }
    
    public function showHide(){
            $toggle = Tools::getValue('toggle');
            $id     = Tools::getValue('id');
            
            if ($toggle == 1)
                $toggle = 0;
            else 
                $toggle = 1;
            
            $resultPublish = Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'slider` SET publish= '.$toggle.' WHERE id= '.$id.' ');
            
            if (!$resultPublish)
                return false;  
    }
    
    public function deleteImage(){
        if(Tools::getValue('validDelete')){
            $id         = Tools::getValue('id');
            $extension  = Tools::getValue('extension');
            
            $picTab = array('images/',
                            'crop/',
                            'thumbs/'            
                            );
            foreach ($picTab as $value) {
                echo _PS_MODULE_DIR_.'monslider/pictures/'.$value.$id.$extension;
                @unlink(_PS_MODULE_DIR_.'monslider/pictures/'.$value.$id.".".$extension);
            }
            
            $resultDelete = Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'slider` WHERE id = '.$id.' ');
            if (!$resultDelete)
                return false;
        }
    }
    
    public function sortImage(){
        $img = Tools::getValue('img');
        $ok  = true;
        for ($i = 0; $i < count($img); $i++) {
                
            $sql='UPDATE `'._DB_PREFIX_.'slider` SET `picsOrder`=' . $i . ' WHERE `id`=' . $img[$i];
            if(!Db::getInstance()->Execute($sql))
                $ok = false;
        }
        
        if ($ok)
            echo $this->l('Position updated.');
        else 
            echo $this->l('Position could not be updated.');
            }
	
}

?>



