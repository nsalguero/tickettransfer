<?php


/**
 * Gestion des droits de chaque profil
 * @author Etienne
 *
 */
class PluginTickettransferProfileconfig extends CommonDBTM {
	function getTabNameForItem(CommonGLPI $item, $withtemplate=0)
	{
		if ($item->getType() == 'Profile') {
			return __("Ticket transfer", 'tickettransfer');
		}
		return '';
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0)
	{
		if ($item->getType() == 'Profile') {
			$ID = $item->getField('id');
			$profile = new self();
			
			// si le profil n'existe pas dans la base, je l'ajoute
			if (!$profile->GetfromDB($ID)) {
				$profile->add(array('id' => $ID));
			}
			
			$profile->showForm($item->getField('id'));  // on affiche le formulaire pour le profil sélectionné
		}
		return true;
	}

	/**
	 * Fonction qui affiche le formulaire du plugin
	 * @param type $id id du profil pour lequel on affiche les droits
	 * @param type $options
	 * @return boolean
	 */
	function showForm($id, $options=array())
	{		
		$target = $this->getFormURL();
		if (isset($options['target'])) {
			$target = $options['target'];
		}
		
		if (!Session::haveRight("profile","r")) {
			return false;
		}
		
		if ($id){
			$this->getFromDB($id);
		}
		
		echo "<form action='".$target."' method='post'>";
		echo "<table class='tab_cadre_fixe'>";
		
		echo "<tr><th colspan='2' class='center b'>".__('Ticket transfer plugin settings', 'tickettransfer')."</th></tr>";
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>".__('Allow ticket transfer to entity', 'tickettransfer')."</td><td>";
		Dropdown::showYesNo("transfer_toentity", $this->fields["transfer_toentity"]);
		echo "</td></tr>";
		
		if (Session::haveRight("profile", "w")) {
			echo "<tr class='tab_bg_1'>";
			echo "<td class='center' colspan='2'>";
			echo "<input type='hidden' name='id' value=$id>";
			echo "<input type='submit' name='update_user_profile'"._sx('button', 'Upgrade')." class='submit'>";
			echo "</td></tr>";
		}
		echo "</table>";
		Html::closeForm();
	}

	
	static function onProfileChange() {
		$prof = new self();
		if ($prof->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
			$_SESSION['plugin']['tickettransfer']['profileconfig'] = $prof->fields;
		} else {
			unset($_SESSION['plugin']['tickettransfer']['profileconfig']);
		}
	}
	
}
?>