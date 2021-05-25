 <?php


defined('MOODLE_INTERNAL') || die;
//admin_setting_configpasswordunmask
if (!class_exists('knowledgefox_admin_setting_configpasswordunmask')) {
class knowledgefox_admin_setting_configpasswordunmask extends admin_setting_configtextarea {
		// check needed, because moodle includes this file twice
	

		public function write_setting($data) {
				global $DB;

            $data = explode("\r\n", $data);
            for($i=0;$i<count($data);$i++){
                $data[$i] = explode(";", $data[$i]);
            }


            $oldval=$DB->get_record('config_plugins', array('plugin' => 'knowledgefox','name' => 'knowledgefoxserver'));
            $oldval = $oldval->value;

            $oldval = explode("\r\n", $oldval);
            for($i=0;$i<count($oldval);$i++){
                $oldval[$i] = explode(";", $oldval[$i]);
            }
            $dataExists = false;
            for($i = 0; $i<count($data); $i++){
                for($j = 0; $j < count($oldval); $j++){
                    if($data[$i][0] == $oldval[$j][0]){
                        if ($data[$i][2] != $oldval[$j][2]){
                            $data[$i][2]=sha1($data[$i][2]);
                        }
                        $dataExists = true;
                        break;
                    }
                }
                if(!$dataExists){
                    $data[$i][2]=sha1($data[$i][2]);
                }
                $dataExists = false;
            }


            for($i=0;$i<count($data);$i++){
                if(count($data[$i]) < 4){
                    $data[$i][3] = "0";
                }
                $data[$i] = implode(";", $data[$i]);
            }
            $data = implode("\r\n", $data);
				
			$ret = parent::write_setting($data);

			if ($ret != '') {
				return $ret;
			}
			return '';
		}
	}
}
	
//$settings->add(new admin_setting_configtext('knowledgefox/knowledgefoxserver', 'Server Url Knowledgefox',
//	'Server Url Knowledgefox2', "https://knowledgefox.net", PARAM_URL));
//
//	$settings->add(new admin_setting_configtext('knowledgefox/knowledgeauthuser', 'Benutzername',
//	'Benutzer bei Knowledgefox mit erweiterten Rechten zum Anlegen von Benutzern und Gruppenzuordnung', "", PARAM_TEXT));
//
//	$settings->add(new knowledgefox_admin_setting_configpasswordunmask('knowledgefox/knowledgeauthpwd', 'Passwort',
//	'Benutzer bei Knowledgefox mit erweiterten Rechten zum Anlegen von Benutzern und Gruppenzuordnung', "", PARAM_TEXT));

 $settings->add(new knowledgefox_admin_setting_configpasswordunmask('knowledgefox/knowledgefoxserver', 'Server Data Knowledgefox',
     'Eingabe: Server-URL;Benutzername;Passwort;KursbereichsId (Optional)', "", PARAM_TEXT));
	
	/*$settings->add(new knowledgefox_admin_setting_configpasswordunmask('knowledgefox/knowledgeauthpwd', 'Passwort',
	'Das Passwort wird automatisch verschlüsselt!', "", PARAM_TEXT));*/
	

	?>