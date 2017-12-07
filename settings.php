 <?php


defined('MOODLE_INTERNAL') || die;
//admin_setting_configpasswordunmask
if (!class_exists('knowledgefox_admin_setting_configpasswordunmask')) {
class knowledgefox_admin_setting_configpasswordunmask extends admin_setting_configtext {
		// check needed, because moodle includes this file twice
	

		public function write_setting($data) {
				global $DB;
				if($oldval=$DB->get_record('config_plugins', array('plugin' => 'knowledgefox','name' => 'knowledgeauthpwd'))){
					if($oldval->value!=$data){
						$data=sha1($data);
					}
				}
				
			$ret = parent::write_setting($data);

			if ($ret != '') {
				return $ret;
			}
			return '';
		}
	}
}
	
$settings->add(new admin_setting_configtext('knowledgefox/knowledgefoxserver', 'Server Url Knowledgefox',
	'Server Url Knowledgefox2', "https://knowledgefox.net", PARAM_URL));
	
	$settings->add(new admin_setting_configtext('knowledgefox/knowledgeauthuser', 'Benutzername',
	'Benutzer bei Knowledgefox mit erweiterten Rechten zum Anlegen von Benutzern und Gruppenzuordnung', "", PARAM_TEXT));
	
	$settings->add(new knowledgefox_admin_setting_configpasswordunmask('knowledgefox/knowledgeauthpwd', 'Passwort',
	'Benutzer bei Knowledgefox mit erweiterten Rechten zum Anlegen von Benutzern und Gruppenzuordnung', "", PARAM_TEXT));
	
	/*$settings->add(new knowledgefox_admin_setting_configpasswordunmask('knowledgefox/knowledgeauthpwd', 'Passwort',
	'Das Passwort wird automatisch verschlüsselt!', "", PARAM_TEXT));*/
	

	?>