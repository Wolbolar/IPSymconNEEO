<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';

// Module for NEEO

class NEEOIO extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	// helper properties
	private $position = 0;

	public function Create()
	{
//Never delete this line!
		parent::Create();

		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.

		$this->RegisterPropertyString("Host", "");
		$this->RegisterPropertyInteger("Port", 3000);

		$this->RegisterPropertyString("webhookusername", "ipsymcon");
		$this->RegisterPropertyString("webhookpassword", "useripsh0me");
		$this->RegisterPropertyBoolean("NEEOVars", false);
		$this->RegisterPropertyInteger("NEEOScript", 0);

	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		$this->RegisterVariableString("hardwareRegion", "Hardware Region", "", $this->_getPosition());
		$this->RegisterVariableInteger("hardwareRevision", "Hardware Revision", "", $this->_getPosition());
		$this->RegisterVariableBoolean("touchButtonPressed", "Touch Button Pressed", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_account", "NEEO Account", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_brain_firmware", "NEEO Brain Firmware", "", $this->_getPosition());
		$this->RegisterVariableString("tr2version", "TR2 Version", "", $this->_getPosition());
		$this->RegisterVariableString("firmwareVersion", "Firmware Version", "", $this->_getPosition());
		$this->RegisterVariableString("hostname", "Host Name", "", $this->_getPosition());
		$this->RegisterVariableInteger("total_memory", "Total Memory", "", $this->_getPosition());
		$this->RegisterVariableInteger("free_memory", "Free Memory", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_ip", "NEEO IP", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_wlanip", "NEEO WLAN IP", "", $this->_getPosition());
		$this->RegisterVariableString("wlan_region", "WLAN Region", "", $this->_getPosition());
		$this->RegisterVariableString("wlan_country", "WLAN Country", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_action", "NEEO Action", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_device", "NEEO Device", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_room", "NEEO Room", "", $this->_getPosition());
		$this->RegisterVariableString("neeo_recipe", "NEEO Recipe", "", $this->_getPosition());

		$this->ValidateConfiguration();

	}

	/**
	 * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
	 * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
	 *
	 *
	 */

	private function ValidateConfiguration()
	{
		$host = $this->ReadPropertyString('Host');

		// NEEO app has no ability to use username and password at the moment
		// $webhookusername = $this->ReadPropertyString('webhookusername');
		// $webhookpassword = $this->ReadPropertyString('webhookpassword');

		// check IP NEEO
		if (!filter_var($host, FILTER_VALIDATE_IP) === false) {
			//IP ok
			$ipcheck = true;

		} else {
			$ipcheck = false;
			$this->SetStatus(203); //IP Adresse oder Host ist ungültig
		}
		/*
		//User und Passwort prüfen
		if ($webhookusername == "" || $webhookpassword == "") {
			$this->SetStatus(205); //Felder dürfen nicht leer sein
		}
		*/
		if ($ipcheck === true) {
			$this->RegisterHook("/hook/neeo");
		}

		// Status Aktiv
		$this->SetStatus(102);
	}

	/*
	public function GetConfigurationForParent()
	{
		$Config['Host'] = $this->GetHostIP();
		$Config['Port'] = 6524;
		$Config['BindPort'] = 6524;
		return json_encode($Config);
	}
*/

	protected function GetHostIP()
	{
		$ip = exec("sudo ifconfig eth0 | grep 'inet Adresse:' | cut -d: -f2 | awk '{ print $1}'");
		if ($ip == "") {
			$ipinfo = Sys_GetNetworkInfo();
			$ip = $ipinfo[0]['IP'];
		}
		return $ip;
	}


	private function RegisterHook($WebHook)
	{
		$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
		if (sizeof($ids) > 0) {
			$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
			$found = false;
			foreach ($hooks as $index => $hook) {
				if ($hook['Hook'] == $WebHook) {
					if ($hook['TargetID'] == $this->InstanceID)
						return;
					$hooks[$index]['TargetID'] = $this->InstanceID;
					$found = true;
				}
			}
			if (!$found) {
				$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
			}
			IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
			IPS_ApplyChanges($ids[0]);
		}
	}

	protected function GetConnectURL()
	{
		$InstanzenListe = IPS_GetInstanceListByModuleID("{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}");
		$InstanzCount = 0;
		$ConnectControl = 0;
		foreach ($InstanzenListe as $InstanzID) {
			$ConnectControl = $InstanzID;
			$InstanzCount++;
			$Childs[] = IPS_GetChildrenIDs($InstanzID);
		}

		if ($ConnectControl > 0) {
			$connectinfo = CC_GetUrl($ConnectControl);
			return $connectinfo;
		} else {
			return false;
		}
	}


	public function ReceiveData($JSONString)
	{
		$payload = json_decode($JSONString);
		$this->SendDebug("NEEO Recieve:", utf8_decode($payload->Buffer), 1);
		// $dataraw = utf8_decode($payload->Buffer);

	}

	/**
	 * This function will be called by the hook control. Visibility should be protected!
	 */

	protected function ProcessHookData()
	{
		# Capture JSON content
		$neeo_json = file_get_contents('php://input');
		$this->SendDebug("NEEO I/O Receive:", $neeo_json, 0);
		/*
		$webhookusername = $this->ReadPropertyString('webhookusername');
		$webhookpassword = $this->ReadPropertyString('webhookpassword');
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			$_SERVER['PHP_AUTH_USER'] = "";
			$this->SendDebug("NEEO:", "Webhook user is empty", 0);
		}
		if (!isset($_SERVER['PHP_AUTH_PW'])) {
			$_SERVER['PHP_AUTH_PW'] = "";
			$this->SendDebug("NEEO:", "Webhook password is empty", 0);
		}

		if (($_SERVER['PHP_AUTH_USER'] != $webhookusername) || ($_SERVER['PHP_AUTH_PW'] != $webhookpassword)) {
			$this->SendDebug("NEEO I/O Receive:", "PHP AUTH USER: ".$_SERVER['PHP_AUTH_USER'], 0);
			$this->SendDebug("NEEO I/O Receive:", "PHP AUTH PW: ".$_SERVER['PHP_AUTH_PW'], 0);
			$this->SendDebug("NEEO:", "wrong webhook user or password", 0);
			header('WWW-Authenticate: Basic Realm="NEEO WebHook"');
			header('HTTP/1.0 401 Unauthorized');
			echo "Authorization required";
			return;
		}
		*/
		echo "Webhook NEEO IP-Symcon";

		//workaround for bug
		if (!isset($_IPS))
			global $_IPS;
		if ($_IPS['SENDER'] == "Execute") {
			echo "This script cannot be used this way.";
			return;
		}

		//$this->SendDebug("NEEO Recieve:", utf8_decode($POST), 1);
		$neeo_data = json_decode($neeo_json);
		$this->SendDebug('Send:', json_encode(Array("DataID" => "{DAAA4574-9321-3497-D8BC-811D94206FDB}", "Buffer" => $neeo_data)), 0);
		$this->SendDebug('Form:', json_last_error_msg(), 0);
		$action = "";
		if (property_exists($neeo_data, 'action')) {
			$action = $neeo_data->action;
			$this->SendDebug("NEEO Recieve:", "Action: " . $action, 0);
			$this->SetValue("neeo_action", $action);
		}
		$device = "";
		if (property_exists($neeo_data, 'device')) {
			$device = $neeo_data->device;
			$this->SendDebug("NEEO Recieve:", "Device: " . $device, 0);
			$this->SetValue("neeo_device", $device);
		}
		$room = "";
		if (property_exists($neeo_data, 'room')) {
			$room = $neeo_data->room;
			$this->SendDebug("NEEO Recieve:", "Room: " . $room, 0);
			$this->SetValue("neeo_room", $room);
		}
		$actionparameter = "";
		if (property_exists($neeo_data, 'actionparameter')) {
			$actionparameter = $neeo_data->actionparameter;
			$this->SendDebug("NEEO Recieve:", "Action parameter: " . json_encode($actionparameter), 0);
		}
		$recipe = "";
		if (property_exists($neeo_data, 'recipe')) {
			$recipe = $neeo_data->recipe;
			$this->SendDebug("NEEO Recieve:", "Recipe: " . $recipe, 0);
			$this->SetValue("neeo_recipe", $recipe);
		}
		$neeo_scriptid = $this->ReadPropertyInteger("NEEOScript");
		if($neeo_scriptid != 0)
		{
			IPS_RunScriptEx($neeo_scriptid, ["action" => $action, "device" => $device, "room" => $room, "actionparameter" => $actionparameter, "recipe" => $recipe]);
		}
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{DAAA4574-9321-3497-D8BC-811D94206FDB}", "Buffer" => $neeo_data)));

	}

	/**
	 * Interne Funktion des SDK.
	 *
	 * @param $JSONString IPS-Datenstring
	 *
	 * @return string Die Antwort an den anfragenden Child
	 */
	public function ForwardData($JSONString)
	{
		$this->SendDebug('Forward Data:', $JSONString, 0);
		$data = json_decode($JSONString);
		$data = $data->Buffer;
		if(property_exists($data, 'Method'))
		{
			$method = $data->Method;
			$command = $data->Command;
			$content = $data->Content;
			$this->SendDebug('Method:', $method, 0);
			$this->SendDebug('Command:', $command, 0);
			if($method == "GET")
			{
				$result = $this->Send_NEEO_GET($command);
			}
			if($method == "PUT")
			{
				$result = $this->Send_NEEO_PUT($command, $content);
			}
			return $result;
		}

		return "";
	}

	protected function ReplaceSpecialCharacters($string)
	{
		$string = str_replace('Ã¼', 'ü', $string);
		return $string;
	}

	protected function CreateIdent($str)
	{
		$search = array("ä", "ö", "ü", "ß", "Ä", "Ö",
			"Ü", "&", "é", "á", "ó",
			" :)", " :D", " :-)", " :P",
			" :O", " ;D", " ;)", " ^^",
			" :|", " :-/", ":)", ":D",
			":-)", ":P", ":O", ";D", ";)",
			"^^", ":|", ":-/", "(", ")", "[", "]",
			"<", ">", "!", "\"", "§", "$", "%", "&",
			"/", "(", ")", "=", "?", "`", "´", "*", "'",
			"-", ":", ";", "²", "³", "{", "}",
			"\\", "~", "#", "+", ".", ",",
			"=", ":", "=)");
		$replace = array("ae", "oe", "ue", "ss", "Ae", "Oe",
			"Ue", "und", "e", "a", "o", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "", "");

		$str = str_replace($search, $replace, $str);
		$str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
		$how = '_';
		//$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
		$str = preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str);
		return $str;
	}


	// Get the brain configuration
	public function Get_Brain_Configuration()
	{
		$command = '/v1/projects/home/';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Get all rooms and it's child configurations.
	public function Get_All_Room_Configuration()
	{
		$command = '/v1/projects/home/rooms/';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}


	// Get a specific room and it's child configurations.
	protected function Get_Room_Configuration($Room_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Get all devices from a specific room and it's child configurations
	protected function Get_Room_Devices($Room_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/devices/';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Trigger a Macro (Push a button)
	protected function Trigger_Makros($Room_KEY, $Device_KEY, $Macro_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/devices/'.$Device_KEY.'/macros/'.$Macro_KEY.'/trigger';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// trigger a recipe
	protected function Trigger_Recipe($Room_KEY, $Device_KEY, $Macro_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/devices/'.$Device_KEY.'/macros/'.$Macro_KEY.'/trigger';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// power off a Scenario
	protected function PowerOff_Scenario($Room_KEY, $Scenario_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/scenarios/'.$Scenario_KEY.'/poweroff';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Start favourite Channel
	protected function Start_Channel($Room_KEY, $Device_KEY, $channel)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/devices/'.$Device_KEY.'/favorites/'.$channel.'/trigger';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}


	// Get system info
	public function Get_System_Info()
	{
		$command = '/v1/systeminfo/';
		$config = $this->Send_NEEO_GET($command);
		if($config != "")
		{
			$data = json_decode($config);
			$hardwareRegion = $data->hardwareRegion;
			$this->SendDebug("NEEO", "hardware region: ". $hardwareRegion, 0);
			$this->SetValue("hardwareRegion", $hardwareRegion);
			$hardwareRevision = $data->hardwareRevision;
			$this->SendDebug("NEEO", "hardware revision: ". $hardwareRevision, 0);
			$this->SetValue("hardwareRevision", $hardwareRevision);
			$touchButtonPressed = $data->touchButtonPressed;
			$this->SendDebug("NEEO", "touch button pressed: ". $touchButtonPressed, 0);
			$this->SetValue("touchButtonPressed", $touchButtonPressed);
			$neeo_account = $data->user;
			$this->SendDebug("NEEO", "neeo account: ". $neeo_account, 0);
			$this->SetValue("neeo_account", $neeo_account);
			$neeo_brain_firmware = $data->version;
			$this->SendDebug("NEEO", "neeo brain firmware: ". $neeo_brain_firmware, 0);
			$this->SetValue("neeo_brain_firmware", $neeo_brain_firmware);
			$tr2version = $data->tr2version;
			$this->SendDebug("NEEO", "tr2version: ". $tr2version, 0);
			$this->SetValue("tr2version", $tr2version);
			$firmwareVersion = $data->firmwareVersion;
			$this->SendDebug("NEEO", "firmware version: ". $firmwareVersion, 0);
			$this->SetValue("firmwareVersion", $firmwareVersion);
			$hostname = $data->hostname;
			$this->SendDebug("NEEO", "hostname: ". $hostname, 0);
			$this->SetValue("hostname", $hostname);
			$totalmem = $data->totalmem;
			$this->SendDebug("NEEO", "total memory: ". $totalmem, 0);
			$this->SetValue("total_memory", $totalmem);
			$freemem = $data->freemem;
			$this->SendDebug("NEEO", "free memory: ". $freemem, 0);
			$this->SetValue("free_memory", $freemem);
			$neeo_ip = $data->ip;
			$this->SendDebug("NEEO", "neeo ip: ". $neeo_ip, 0);
			$this->SetValue("neeo_ip", $neeo_ip);
			$neeo_wlanip = $data->wlanip;
			$this->SendDebug("NEEO", "neeo wlanip: ". $neeo_wlanip, 0);
			$this->SetValue("neeo_wlanip", $neeo_wlanip);
			$wlanregion = $data->wlanregion;
			$this->SendDebug("NEEO", "wlan region: ". $wlanregion, 0);
			$this->SetValue("wlan_region", $wlanregion);
			$wlancountry = $data->wlancountry;
			$this->SendDebug("NEEO", "wlan country: ". $wlancountry, 0);
			$this->SetValue("wlan_country", $wlancountry);
			//$wlaninfo = $data->wlaninfo;
			//$this->SendDebug("NEEO", "hardware revision: ". $hardwareRevision, 0);
		}
		return $config;
	}

	// Blink NEEO Brain LED
	public function Blink_LED()
	{
		$command = '/v1/systeminfo/identbrain';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Recipes
	public function Recipes()
	{
		$command = '/v1/api/Recipes';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Get recipe state
	protected function Get_State_Recipe($Room_KEY, $Recipe_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/recipes/'.$Recipe_KEY.'/isactive';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// get the active scenariokeys
	protected function Get_Active_Scenario()
	{
		$command = '/v1/projects/home/activescenariokeys';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	// Sonos start menu (you can find here the directory-key for each element
	protected function Sonos_Start_Menu($Room_KEY, $Device_KEY)
	{
		$command = '/v1/projects/home/rooms/'.$Room_KEY.'/devices/'.$Device_KEY.'/getdirectoryrootitems';
		$config = $this->Send_NEEO_GET($command);
		return $config;
	}

	/*
	#My sonos favorites

   POST http://brain-ip:3000/v1/projects/home/rooms/room-key/devices/device-key/directories/directory-key/browse


   #procecures per device (you can get here procedure-key)

   In orde to use it into the tigger url must look for the one that is named "ADD_TO_QUEUE_PROCEDURE"

   GET http://brain-ip:3000/v1/projects/home/rooms/room-key/devices/device-key/procedures


   #trigger an action (a favorite or from start menu)  -> where I found the problems hehe

   POST http://brain-ip:3000/v1/projects/home/rooms/room-key/devices/device-key/procedures/procedure-key/trigger
   */



	protected function Send_NEEO_GET($command)
	{
		// IP be found in the about screen of the NEEO app

		$brain_ip = $this->ReadPropertyString("Host");
		$brain_port = $this->ReadPropertyInteger("Port");
		$URL = $brain_ip.':'.$brain_port.$command;

		$header = ['Content-type:application/json'];

		//$header = [
		//'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
		//'Accept-Encoding: gzip, deflate',
		//'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
		//'Connection: keep-alive',
		//'Host: '.$brain_ip.':'.$brain_port,
		//'Upgrade-Insecure-Requests: 1',
		//'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36'
		//];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
		//$curl_info = curl_getinfo($ch);
		$this->SendDebug("NEEO Status Code", $status_code, 0);
		curl_close($ch);
		$this->SendDebug("NEEO Brain Response", $result, 0);
		return $result;
	}

	protected function Send_NEEO_PUT($command, $content)
	{
		// IP be found in the about screen of the NEEO app

		$brain_ip = $this->ReadPropertyString("Host");
		$brain_port = $this->ReadPropertyInteger("Port");
		$URL = $brain_ip.':'.$brain_port.$command;

		$header = ['Content-type:application/json'];

		//$header = [
		//'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
		//'Accept-Encoding: gzip, deflate',
		//'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
		//'Connection: keep-alive',
		//'Host: '.$brain_ip.':'.$brain_port,
		//'Upgrade-Insecure-Requests: 1',
		//'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36'
		//];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
		//$curl_info = curl_getinfo($ch);
		$this->SendDebug("NEEO Status Code", $status_code, 0);
		curl_close($ch);
		$this->SendDebug("NEEO Brain Response", $result, 0);
		return $result;
	}


	protected function SendNEEOPOST(string $URL, string $data_json)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		$result = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
		//$curl_info = curl_getinfo($ch);
		$this->SendDebug("NEEO Status Code", $status_code, 0);
		curl_close($ch);
		$this->SendDebug("NEEO Brain Response", $result, 0);
		return $result;
	}

	//Profile

	/**
	 * register profiles
	 * @param $Name
	 * @param $Icon
	 * @param $Prefix
	 * @param $Suffix
	 * @param $MinValue
	 * @param $MaxValue
	 * @param $StepSize
	 * @param $Digits
	 * @param $Vartype
	 */
	protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != $Vartype) {
				$this->_debug('profile', 'Variable profile type does not match for profile ' . $Name);
			}
		}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
	}

	/**
	 * register profile association
	 * @param $Name
	 * @param $Icon
	 * @param $Prefix
	 * @param $Suffix
	 * @param $MinValue
	 * @param $MaxValue
	 * @param $Stepsize
	 * @param $Digits
	 * @param $Vartype
	 * @param $Associations
	 */
	protected function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
	{
		if (is_array($Associations) && sizeof($Associations) === 0) {
			$MinValue = 0;
			$MaxValue = 0;
		}
		$this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

		if (is_array($Associations)) {
			foreach ($Associations AS $Association) {
				IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}
		} else {
			$Associations = $this->$Associations;
			foreach ($Associations AS $code => $association) {
				IPS_SetVariableProfileAssociation($Name, $code, $this->Translate($association), $Icon, -1);
			}
		}

	}


	protected function GetIPSVersion()
	{
		$ipsversion = floatval(IPS_GetKernelVersion());
		if ($ipsversion < 4.1) // 4.0
		{
			$ipsversion = 0;
		} elseif ($ipsversion >= 4.1 && $ipsversion < 4.2) // 4.1
		{
			$ipsversion = 1;
		} elseif ($ipsversion >= 4.2 && $ipsversion < 4.3) // 4.2
		{
			$ipsversion = 2;
		} elseif ($ipsversion >= 4.3 && $ipsversion < 4.4) // 4.3
		{
			$ipsversion = 3;
		} elseif ($ipsversion >= 4.4 && $ipsversion < 5) // 4.4
		{
			$ipsversion = 4;
		} else   // 5
		{
			$ipsversion = 5;
		}

		return $ipsversion;
	}

	/***********************************************************
	 * Configuration Form
	 ***********************************************************/

	/**
	 * build configuration form
	 * @return string
	 */
	public function GetConfigurationForm()
	{
		// return current form
		return json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$form = [
			[
				'type' => 'Label',
				'label' => 'Enter IP Adress of NEEO (can be found in the NEEO app under about):'
			],
			[
				'name' => 'Host',
				'type' => 'ValidationTextBox',
				'caption' => 'Host'
			],
			[
				'type' => 'Label',
				'label' => 'setup forward actions in the NEEO APP'
			],
			[
				'type' => 'Label',
				'label' => 'Open NEEO APP, goto Settings -> NEEO Brain-> Forward actions'
			],
			/*
			[
				'type' => 'Label',
				'label' => 'Set webbhook username:'
			],
			[
				'name' => 'webhookusername',
				'type' => 'ValidationTextBox',
				'caption' => 'webhook username'
			],
			[
				'type' => 'Label',
				'label' => 'Set webhook password:'
			],
			[
				'name' => 'webhookpassword',
				'type' => 'PasswordTextBox',
				'caption' => 'webhook password'
			],
			*/
			[
				'type' => 'Label',
				'label' => 'Enter following in the NEEO app:'
			],
			[
				'type' => 'Label',
				'label' => '{IP Adress} is the IP Adress from IP-Symcon'
			],
			/*
			[
				'type' => 'Label',
				'label' => '{webhook username} is the webhook username set above in IP-Symcon'
			],
			[
				'type' => 'Label',
				'label' => '{webhook password} is the webhook password set above in IP-Symcon'
			],
			[
				'type' => 'Label',
				'label' => 'NEEO app Forward Brain Actions -> Target host: {webhook username}:{webhook password}@{IP Adress}'
			],
			*/
			[
				'type' => 'Label',
				'label' => 'NEEO app Forward Brain Actions -> Target host: {IP Adress}'
			],
			[
				'type' => 'Label',
				'label' => 'NEEO app Forward Brain Actions -> Target port: 3777'
			],
			[
				'type' => 'Label',
				'label' => 'NEEO app Forward Brain Actions -> Path: /hook/neeo'
			],
			[
				'type' => 'Label',
				'label' => 'If you like to foward data from NEEO to a script please add script below:'
			],
			[
				'name' => 'NEEOScript',
				'type' => 'SelectScript',
				'caption' => 'Forward script'
			]
		];

		return $form;
	}

	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [
			[
				'type' => 'Label',
				'label' => 'Get NEEO System Information:'
			],
			[
				'type' => 'Button',
				'label' => 'Get Info',
				'onClick' => 'NEEOIO_Get_System_Info($id);'
			]
		];

		return $form;
	}

	/**
	 * return from status
	 * @return array
	 */
	protected function FormStatus()
	{
		$form = [
			[
				'code' => 101,
				'icon' => 'inactive',
				'caption' => 'Creating instance.'
			],
			[
				'code' => 102,
				'icon' => 'active',
				'caption' => 'NEEO IO created.'
			],
			[
				'code' => 104,
				'icon' => 'inactive',
				'caption' => 'interface closed.'
			],
			[
				'code' => 201,
				'icon' => 'inactive',
				'caption' => 'Please follow the instructions.'
			],
			[
				'code' => 202,
				'icon' => 'error',
				'caption' => 'address must not empty.'
			],
			[
				'code' => 203,
				'icon' => 'error',
				'caption' => 'No valid address.'
			],
			[
				'code' => 204,
				'icon' => 'error',
				'caption' => 'field must not be empty.'
			],
			[
				'code' => 206,
				'icon' => 'error',
				'caption' => 'select category for import.'
			]
		];

		return $form;
	}

	/**
	 * send debug log
	 * @param string $notification
	 * @param string $message
	 * @param int $format 0 = Text, 1 = Hex
	 */
	private function _debug(string $notification = NULL, string $message = NULL, $format = 0)
	{
		$this->SendDebug($notification, $message, $format);
	}

	/**
	 * return incremented position
	 * @return int
	 */
	private function _getPosition()
	{
		$this->position++;
		return $this->position;
	}

	/***********************************************************
	 * Migrations
	 ***********************************************************/

	/**
	 * Polyfill for IP-Symcon 4.4 and older
	 * @param string $Ident
	 * @param mixed $Value
	 */
	//Add this Polyfill for IP-Symcon 4.4 and older
	protected function SetValue($Ident, $Value)
	{

		if (IPS_GetKernelVersion() >= 5) {
			parent::SetValue($Ident, $Value);
		} else {
			SetValue($this->GetIDForIdent($Ident), $Value);
		}
	}
}

?>