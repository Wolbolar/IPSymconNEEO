<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';


class NEEOConfigurator extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	private const ALEXA = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}'; // Alexa
	private const GOOGLE_HOME = '{BB6EF5EE-1437-4C80-A16D-DA0A6C885210}'; // Google Home
	private const HOMEKIT = '{7FC71134-CFD0-4909-819C-B794FE067FBC}'; // Homekit

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		// 1. Verfügbarer AIOSplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
		$this->ConnectParent("{A938EE1A-519B-4BAB-AEB1-EFC1B2B15A91}");

		$this->RegisterPropertyBoolean("GoogleHome", false);
		$this->RegisterPropertyBoolean("Alexa", false);
		$this->RegisterPropertyBoolean("Homekit", false);
		$this->RegisterPropertyBoolean("UseVisualisation", false);
		$this->RegisterAttributeBoolean("ExtendedDebug", false);
		$this->RegisterPropertyInteger("Visualisation", 0);
	}

	/**
	 * Interne Funktion des SDK.
	 */
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		$this->ValidateConfiguration();
	}

	private function ValidateConfiguration()
	{
		$GoogleHome = $this->ReadPropertyBoolean("GoogleHome");
		$Alexa = $this->ReadPropertyBoolean("Alexa");
		$Homekit = $this->ReadPropertyBoolean("Homekit");
		$Visualisation = $this->ReadPropertyInteger("Visualisation");
		$UseVisualisation = $this->ReadPropertyBoolean("UseVisualisation");

		if ($GoogleHome) {
			$this->Add_Devices_To_VoiceControl(self::GOOGLE_HOME); // Google Home
		}
		if ($Alexa) {
			$this->Add_Devices_To_VoiceControl(self::ALEXA); // Alexa
		}
		if ($Homekit) {
			$this->Add_Devices_To_VoiceControl(self::HOMEKIT); // Homekit
		}
		if ($Visualisation > 0 && $UseVisualisation == true) {
			$this->CreateLinksVisualisation($Visualisation); // create links
		}
		$this->SetStatus(102);
	}

	private function CreateLinksVisualisation($Visualisation)
	{
		// check for recipes
		$NEEORecipeInstanceID = IPS_GetInstanceListByModuleID('{935CA17E-9AE6-1992-7DD3-65B283B07C51}')[0]; // NEEO Recipe Devices
		if ($NEEORecipeInstanceID > 0) {
			$this->SendDebug('NEEO Configuration', 'Creating Links', 0);
			$recipe_list = IPS_GetChildrenIDs($NEEORecipeInstanceID);
			foreach ($recipe_list as $recipe) {
				$LinkID = IPS_CreateLink();             // create link
				$name = IPS_GetName($recipe);
				IPS_SetName($LinkID, $name);
				IPS_SetParent($LinkID, $Visualisation);
				IPS_SetLinkTargetID($LinkID, $recipe);
			}
		}
	}

	public function ExtendedDebug(bool $debug)
	{
		$this->WriteAttributeBoolean("ExtendedDebug", $debug);
		$this->LogMessage("Set Debug Mode to Extended", KL_DEBUG);
	}

	private function GetExtendedDebugMode()
	{
		$debug = $this->ReadAttributeBoolean("ExtendedDebug");
		return $debug;
	}

	private $suffix = 0;

	private function _getSuffix()
	{
		$this->suffix++;
		return $this->suffix;
	}

	private function CheckVoiceControl($guid)
	{
		$voicecontrol_id = 0;
		$voicecontrol_list = IPS_GetInstanceListByModuleID($guid);
		$voicecontrol_name = $this->GetVoiceControlName($guid);
		if (empty($voicecontrol_list)) {

			$this->SendDebug('NEEO Voice Control', 'No instance for voicecontrol' . $voicecontrol_name . ' found.', 0);
		} else {
			$voicecontrol_id = IPS_GetInstanceListByModuleID($guid)[0];
			$this->SendDebug('NEEO Voice Control', $voicecontrol_name . ' found with id' . $voicecontrol_id, 0);
		}
		return $voicecontrol_id;
	}

	private function GetVoiceControlName($guid)
	{
		$voicecontrol_name = "unkown";
		if ($guid == self::ALEXA) {
			$voicecontrol_name = "Alexa";
		}
		if ($guid == self::GOOGLE_HOME) {
			$voicecontrol_name = "Google Home";
		}
		if ($guid == self::HOMEKIT) {
			$voicecontrol_name = "Homekit";
		}
		return $voicecontrol_name;
	}

	public function Add_Devices_To_VoiceControl($guid)
	{
		$voicecontrol_id = $this->CheckVoiceControl($guid);
		if ($voicecontrol_id == 0) {
			return false;
		} else {
			$neeo_devices = IPS_GetInstanceListByModuleID('{935CA17E-9AE6-1992-7DD3-65B283B07C51}'); // NEEO Devices
			$add_devices = [];
			foreach ($neeo_devices as $neeo_device) {
				$recipe_switch = @IPS_GetObjectIDByIdent("LaunchRecipe", $neeo_device);
				if ($recipe_switch) {
					$add_devices[$neeo_device] = $recipe_switch;
				}
			}
			$configuration_json = IPS_GetConfiguration($voicecontrol_id);
			$configuration = json_decode($configuration_json, true);
			foreach ($configuration as $devicetype => $devices) {
				// Google
				if ($guid == self::GOOGLE_HOME) {
					if ($devicetype == "DeviceGenericSwitch") {
						$DeviceGenericSwitch = $this->SearchExistingDevice($devices, $add_devices, "OnOffID");
						//$DeviceGenericSwitch = SearchExistingDevice($devices, $add_devices);
					}
					if ($devicetype == "DeviceLightColor") {
						$DeviceLightColor = $devices;
						//$DeviceLightColor = $this->SearchExistingDevice($devices, $add_devices, "");
					}
					if ($devicetype == "DeviceLightDimmer") {
						$DeviceLightDimmer = $devices;
						//$DeviceLightDimmer = $this->SearchExistingDevice($devices, $add_devices, "");
					}
					if ($devicetype == "DeviceLightExpert") {
						$DeviceLightExpert = $devices;
						//$DeviceLightExpert = $this->SearchExistingDevice($devices, $add_devices, "");
					}
					if ($devicetype == "DeviceLightSwitch") {
						$DeviceLightSwitch = $devices;
						//$DeviceLightSwitch = $this->SearchExistingDevice($devices, $add_devices, "");
					}
					if ($devicetype == "DeviceSceneDeactivatable") {
						$DeviceSceneDeactivatable = $devices;
						//$DeviceSceneDeactivatable = $this->SearchExistingDevice($devices, $add_devices, "");
					}
					if ($devicetype == "DeviceSceneSimple") {
						$DeviceSceneSimple = $devices;
						//$DeviceSceneSimple = $this->SearchExistingDevice($devices, $add_devices, "");
					}
				}
				// Alexa
				if ($guid == self::ALEXA) {
					if ($devicetype == "DeviceGenericSwitch") {
						$DeviceGenericSwitch = $this->SearchExistingDevice($devices, $add_devices, "PowerControllerID");
						$this->SendDebug('Set Configuration', 'DeviceGenericSwitch ' . json_encode($DeviceGenericSwitch), 0);
						//$DeviceGenericSwitch = SearchExistingDevice($devices, $add_devices);
					}
					if ($devicetype == "DeviceGenericSlider") {
						$DeviceGenericSlider = $devices;
						// $DeviceGenericSlider = $this->SearchExistingDevice($devices, $add_devices, "");
					}
					if ($devicetype == "DeviceLightColor") {
						$DeviceLightColor = $devices;
						//$DeviceLightColor = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
					if ($devicetype == "DeviceLightDimmer") {
						$DeviceLightDimmer = $devices;
						//$DeviceLightDimmer = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
					if ($devicetype == "DeviceLightExpert") {
						$DeviceLightExpert = $devices;
						//$DeviceLightExpert = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
					if ($devicetype == "DeviceLightSwitch") {
						$DeviceLightSwitch = $devices;
						//$DeviceLightSwitch = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
					if ($devicetype == "DeviceDeactivatableScene") {
						$DeviceDeactivatableScene = $devices;
						//$DeviceDeactivatableScene = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
					if ($devicetype == "DeviceSimpleScene") {
						$DeviceSimpleScene = $devices;
						//$DeviceSimpleScene = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
					if ($devicetype == "DeviceThermostat") {
						$DeviceThermostat = $devices;
						//$DeviceThermostat = $this->SearchExistingDevice($devices, $add_devices, $guid);
					}
				}
				// Homekit
				if ($guid == self::HOMEKIT) {
					/*
					if ($devicetype == "BridgeID") {
						$BridgeID = $devices;
					}
					if ($devicetype == "BridgeName") {
						$BridgeName = $devices;
					}
					if ($devicetype == "BridgePort") {
						$BridgePort = $devices;
					}
					if ($devicetype == "AccessoryKeyPair") {
						$AccessoryKeyPair = $devices;
					}
					if ($devicetype == "AccessoryKeyPair") {
						$AccessoryKeyPair = $devices;
					}
					if ($devicetype == "AccessoryAirQualitySensor") {
						$AccessoryAirQualitySensor = $devices;
					}
					if ($devicetype == "AccessoryCarbonDioxideSensor") {
						$AccessoryCarbonDioxideSensor = $devices;
					}
					if ($devicetype == "AccessoryCarbonMonoxideSensor") {
						$AccessoryCarbonMonoxideSensor = $devices;
					}
					*/
					if ($devicetype == "AccessorySwitch") {
						$AccessorySwitch = $this->SearchExistingDevice($devices, $add_devices, "VariableID");
					}
				}
			}
			if ($guid == self::GOOGLE_HOME) {
				$configuration = json_encode(["DeviceGenericSwitch" => json_encode($DeviceGenericSwitch), "DeviceLightColor" => $DeviceLightColor, "DeviceLightDimmer" => $DeviceLightDimmer, "DeviceLightExpert" => $DeviceLightExpert, "DeviceLightSwitch" => $DeviceLightSwitch, "DeviceSceneDeactivatable" => $DeviceSceneDeactivatable, "DeviceSceneSimple" => $DeviceSceneSimple]);
			}
			if ($guid == self::ALEXA) {

				$configuration = json_encode(["DeviceGenericSwitch" => json_encode($DeviceGenericSwitch), "DeviceGenericSlider" => $DeviceGenericSlider, "DeviceLightColor" => $DeviceLightColor, "DeviceLightDimmer" => $DeviceLightDimmer, "DeviceLightExpert" => $DeviceLightExpert, "DeviceLightSwitch" => $DeviceLightSwitch, "DeviceDeactivatableScene" => $DeviceDeactivatableScene, "DeviceSimpleScene" => $DeviceSimpleScene, "DeviceThermostat" => $DeviceThermostat]);
			}
			if ($guid == self::HOMEKIT) {
				$configuration["AccessorySwitch"] = json_encode($AccessorySwitch);
				$configuration = json_encode($configuration);
			}
			IPS_SetConfiguration($voicecontrol_id, $configuration);
			IPS_ApplyChanges($voicecontrol_id); //Neue Konfiguration übernehmen
			return true;
		}
	}

	private function SearchExistingDevice($devices, $add_devices, $search)
	{
		$devices = json_decode($devices, true);
		foreach ($add_devices as $instanceid => $add_variable) {
			$key = array_search($add_variable, array_column($devices, $search));
			if (!$key) {
				$this->SendDebug('Set Configuration', 'Device ' . IPS_GetName($instanceid) . ' does not exist', 0);
				$devices = $this->AddDeviceToConfig($devices, $instanceid, $add_variable, $search);
			}
		}
		return $devices;
	}


	private function AddDeviceToConfig($devices, $instanceid, $add_variable, $search)
	{
		$name = IPS_GetName($instanceid);
		$key = array_search($name, array_column($devices, 'Name'));
		if ($key) {
			$name = $name . "_" . $this->_getSuffix();
		}
		end($devices);
		//$lastkey = key($devices);
		//array_push($devices, ["ID" => $lastkey+1, "Name" => $name, $search => $add_variable]);
		array_push($devices, ["ID" => "", "Name" => $name, $search => $add_variable]);
		return $devices;
	}

	/** Get Config Brain
	 *
	 * @return array
	 */
	private function Get_ListConfiguration()
	{
		$room_ips_id = 1;
		$config_list = [];
		$extended_debug = $this->GetExtendedDebugMode();
		$NEEOInstanceIDList = IPS_GetInstanceListByModuleID('{67252707-E627-4DFC-07D3-438452F20B23}'); // NEEO Devices
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$hubip = IPS_GetProperty($MyParent, 'Host');
		if($extended_debug)
		{
			$this->SendDebug('NEEO hubip', $hubip, 0);
		}
		$systeminfo = $this->SendData('GET', '/v1/systeminfo/');
		$systemdata = json_decode($systeminfo);
		$hostname = $systemdata->hostname;
		if($extended_debug)
		{
			$this->SendDebug('NEEO hostname', $hostname, 0);
		}
		$config = $this->SendData('GET', '/v1/projects/home/');
		$recipes_json = $this->SendData('GET', '/v1/api/Recipes');
		if($extended_debug)
		{
			$this->SendDebug('NEEO Config', $config, 0);
		}
		if($extended_debug)
		{
			$this->SendDebug('NEEO Recipes', $recipes_json, 0);
		}
		if (!empty($config)) {
			$data = json_decode($config);
			if (property_exists($data, "name")) {
				$name = $data->name;
				if($extended_debug)
				{
					$this->SendDebug('NEEO name', $name, 0);
				}
				$version = $data->version;
				if($extended_debug)
				{
					$this->SendDebug('NEEO version', $version, 0);
				}
				$label = $data->label;
				if($extended_debug)
				{
					$this->SendDebug('NEEO label', $label, 0);
				}
				$configured = $data->configured;
				if($extended_debug)
				{
					$this->SendDebug('NEEO configured', $configured, 0);
				}
				$gdprAccepted = $data->gdprAccepted;
				if($extended_debug)
				{
					$this->SendDebug('NEEO gdpr accepted', $gdprAccepted, 0);
				}
				$rooms = $data->rooms;
				foreach ($rooms as $room) {
					$room_name = $room->name;
					if($extended_debug)
					{
						$this->SendDebug('NEEO room name', $room_name, 0);
					}
					$room_icon = $room->icon;
					if($extended_debug)
					{
						$this->SendDebug('NEEO icon', $room_icon, 0);
					}
					$hasController = boolval($room->hasController);
					if($extended_debug)
					{
						$this->SendDebug('NEEO has controller', print_r($hasController, true), 0);
					}
					$devices = $room->devices;
					$config_list[] = ["id" => $room_ips_id,
						"type" => $this->Translate("room"),
						"room" => $this->Translate($room_name),
						"device" => $this->Translate("NEEO Room"),
						"manufacturer" => "NEEO",
						"name" => $this->Translate($room_name)
					];
					foreach ($devices as $device) {
						$instanceID = 0;
						$device_name = $device->name;
						if($extended_debug)
						{
							$this->SendDebug('NEEO device name', $device_name, 0);
						}
						$setPowerOn = "";
						$setPowerOff = "";
						$getPowerState = "";
						if (!empty($recipes_json)) {
							$recipes = json_decode($recipes_json);
							foreach ($recipes as $key => $recipe) {
								$detail = $recipe->detail;
								$recipe_roomname = urldecode($detail->roomname);
								$recipe_devicename = urldecode($detail->devicename);
								if ($recipe_roomname == $room_name && $recipe_devicename == $device_name) {
									$type = $recipe->type;
									if ($type == "launch") {
										$url = $recipe->url;
										$setPowerOn_URL = $url->setPowerOn;
										$poweron_start = strpos($setPowerOn_URL, "recipes/");
										$poweron_end = strpos($setPowerOn_URL, "/execute");
										$setPowerOn = substr($setPowerOn_URL, $poweron_start + 8, $poweron_end - $poweron_start - 8);
										$setPowerOff_URL = $url->setPowerOff;
										$poweroff_start = strpos($setPowerOn_URL, "recipes/");
										$poweroff_end = strpos($setPowerOn_URL, "/execute");
										$setPowerOff = substr($setPowerOff_URL, $poweroff_start + 8, $poweroff_end - $poweroff_start - 8);
										$getPowerState_URL = $url->getPowerState;
										$getPowerState_start = strpos($getPowerState_URL, "recipes/");
										$getPowerState_end = strpos($getPowerState_URL, "/isactive");
										$getPowerState = substr($getPowerState_URL, $getPowerState_start + 8, $getPowerState_end - $getPowerState_start - 8);
									}
									if ($type == "poweroff") {
										$url = $recipe->url;
										$setPowerOff_URL = $url->setPowerOff;
										$poweroff_start = strpos($setPowerOn_URL, "recipes/");
										$poweroff_end = strpos($setPowerOn_URL, "/execute");
										$setPowerOff = substr($setPowerOff_URL, $poweroff_start + 8, $poweroff_end - $poweroff_start - 8);
									}
								}
							}
						}
						$device_roomName = $device->roomName;
						if($extended_debug)
						{
							$this->SendDebug('NEEO device room name', $device_roomName, 0);
						}
						$device_roomKey = $device->roomKey;
						if($extended_debug)
						{
							$this->SendDebug('NEEO device room key', $device_roomKey, 0);
						}
						$adapterDeviceId = $device->adapterDeviceId;
						if($extended_debug)
						{
							$this->SendDebug('NEEO adapter device id', $adapterDeviceId, 0);
						}
						$details = $device->details;
						$sourceName = $details->sourceName;
						if($extended_debug)
						{
							$this->SendDebug('NEEO source name', $sourceName, 0);
						}
						$adapterName = $details->adapterName;
						if($extended_debug)
						{
							$this->SendDebug('NEEO adapter name', $adapterName, 0);
						}
						$type = $details->type;
						if($extended_debug)
						{
							$this->SendDebug('NEEO type', $type, 0);
						}
						$manufacturer = $details->manufacturer;
						if($extended_debug)
						{
							$this->SendDebug('NEEO manufacturer', $manufacturer, 0);
						}
						$detail_name = $details->name;
						if($extended_debug)
						{
							$this->SendDebug('NEEO detail name', $detail_name, 0);
						}
						$commandSets = $details->commandSets;
						$commandSetsJSON = json_encode($commandSets);
						if($extended_debug)
						{
							$this->SendDebug('NEEO command sets', json_encode($commandSets), 0);
						}
						$deviceCapabilitiesJSON = "";
						if (property_exists($details, "deviceCapabilities")) {
							$deviceCapabilities = $details->deviceCapabilities;
							$deviceCapabilitiesJSON = json_encode($deviceCapabilities);
							if($extended_debug)
							{
								$this->SendDebug('NEEO device capabilities', json_encode($deviceCapabilities), 0);
							}
						}
						$roles = $details->roles;
						if($extended_debug)
						{
							$this->SendDebug('NEEO roles', json_encode($roles), 0);
						}
						$capabilities = $details->capabilities;
						if($extended_debug)
						{
							$this->SendDebug('NEEO capabilities', json_encode($capabilities), 0);
						}
						$icon = $details->icon;
						if($extended_debug)
						{
							$this->SendDebug('NEEO icon', $icon, 0);
						}
						$powerMode = $device->powerMode;
						if($extended_debug)
						{
							$this->SendDebug('NEEO power mode', $powerMode, 0);
						}
						if (property_exists($device, 'macros')) {
							$macros = $device->macros;
							$macros_JSON = json_encode($macros);
							foreach ($macros as $macroname => $macro) {
								$macro_key = $macro->key;
								if($extended_debug)
								{
									$this->SendDebug('NEEO macro key', $macro_key, 0);
								}
								$componentType = $macro->componentType;
								if($extended_debug)
								{
									$this->SendDebug('NEEO component type', $componentType, 0);
								}
								$macro_name = $macro->name;
								if($extended_debug)
								{
									$this->SendDebug('NEEO macro name', $macro_name, 0);
								}
								$macro_label = $macro->label;
								if($extended_debug)
								{
									$this->SendDebug('NEEO macro label', $macro_label, 0);
								}
								$macro_deviceName = $macro->deviceName;
								if($extended_debug)
								{
									$this->SendDebug('NEEO macro device name', $macro_deviceName, 0);
								}
								$deviceKey = $macro->deviceKey;
								if($extended_debug)
								{
									$this->SendDebug('NEEO device key', $deviceKey, 0);
								}
								$macro_roomName = $macro->roomName;
								if($extended_debug)
								{
									$this->SendDebug('NEEO macro room name', $macro_roomName, 0);
								}
								$macro_roomKey = $macro->roomKey;
								if($extended_debug)
								{
									$this->SendDebug('NEEO macro room key', $macro_roomKey, 0);
								}
							}
						}
						$device_info = $this->SendData('GET', '/v1/projects/home/rooms/' . $device_roomKey . '/devices/' . $deviceKey . '/');
						if($extended_debug)
						{
							$this->SendDebug('NEEO device info', $device_info, 0);
						}
						foreach ($NEEOInstanceIDList as $NEEOInstanceID) {
							if (IPS_GetInstance($NEEOInstanceID)['ConnectionID'] == $MyParent && $deviceKey == IPS_GetProperty($NEEOInstanceID, 'deviceKey')) {
								$instanceID = $NEEOInstanceID;
							}
						}
						$config_list[] = [
							"instanceID" => $instanceID,
							"parent" => $room_ips_id,
							"id" => $deviceKey,
							"type" => $this->Translate($type),
							"room" => $this->Translate($room_name),
							"device" => $this->Translate($adapterName),
							"manufacturer" => $this->Translate($manufacturer),
							"name" => $this->Translate($device_name),
							"location" => [
								$this->Translate('devices'), "NEEO", $this->Translate('NEEO Devices'), $hostname . " (" . $hubip . ")", $this->Translate($room_name)
							],
							"create" => [
								[
									"moduleID" => "{67252707-E627-4DFC-07D3-438452F20B23}",
									"configuration" => [
										"neeo_hostname" => $hostname,
										"type" => $type,
										"device" => $adapterName,
										"room_name" => $room_name,
										"room_icon" => $room_icon,
										"hasController" => $hasController,
										"device_name" => $device_name,
										"device_roomName" => $device_roomName,
										"device_roomKey" => $device_roomKey,
										"commandSets" => $commandSetsJSON,
										"deviceCapabilities" => $deviceCapabilitiesJSON,
										"deviceKey" => $deviceKey,
										"macros" => $macros_JSON,
										"manufacturer" => $manufacturer,
										"device_info" => $device_info,
										"setPowerOn" => $setPowerOn,
										"setPowerOff" => $setPowerOff,
										"getPowerState" => $getPowerState,
										"NEEOVars" => true,
										"NEEOScript" => false
									]
								]
							]
						];

					}
					$room_ips_id++;
				}
			} else {
				if($extended_debug)
				{
					$this->SendDebug('NEEO Config', $data, 0);
				}
			}
			$instanceWebUIID = 0;
			$config_list[] = ["id" => $room_ips_id,
				"type" => "NEEO Web UI",
				"room" => "NEEO Web UI",
				"device" => "NEEO Web UI",
				"manufacturer" => "NEEO",
				"name" => "NEEO Web UI"
			];
			$NEEOWebUIInstanceIDList = IPS_GetInstanceListByModuleID('{F2EB7DBC-A770-43D1-A64A-089E8B0A7C37}'); // NEEO WebUI Devices
			foreach ($NEEOWebUIInstanceIDList as $NEEOWebUIInstanceID) {
				if (IPS_GetInstance($NEEOWebUIInstanceID)['ConnectionID'] == $MyParent && IPS_GetProperty($NEEOWebUIInstanceID, 'device_name') == "WEBUI") {
					$instanceWebUIID = $NEEOWebUIInstanceID;
				}
			}
			$config_list[] = [
				"instanceID" => $instanceWebUIID,
				"parent" => $room_ips_id,
				"id" => "WebUI",
				"type" => "NEEO Web UI",
				"room" => "NEEO Web UI",
				"device" => "NEEO Web UI",
				"manufacturer" => "NEEO",
				"name" => "NEEO Web UI",
				"location" => [
					$this->Translate('devices'), "NEEO", $this->Translate('NEEO Devices'), $hostname . " (" . $hubip . ")"
				],
				"create" => [
					[
						"moduleID" => "{F2EB7DBC-A770-43D1-A64A-089E8B0A7C37}",
						"configuration" => [
							"Host" => $hubip
						]
					]
				]
			];
			$room_ips_id++;
			$instanceRecipeUIID = 0;
			$config_list[] = ["id" => $room_ips_id,
				"type" => $this->Translate("recipes"),
				"room" => $this->Translate("NEEO Recipes"),
				"device" => $this->Translate("NEEO Recipes"),
				"manufacturer" => "NEEO",
				"name" => $this->Translate("NEEO Recipes"),
			];
			$NEEORecipeInstanceIDList = IPS_GetInstanceListByModuleID('{935CA17E-9AE6-1992-7DD3-65B283B07C51}'); // NEEO Recipe Devices
			foreach ($NEEORecipeInstanceIDList as $NEEORecipeInstanceID) {
				if (IPS_GetInstance($NEEORecipeInstanceID)['ConnectionID'] == $MyParent && IPS_GetProperty($NEEORecipeInstanceID, 'device_name') == "Recipes") {
					$instanceRecipeUIID = $NEEOWebUIInstanceID;
				}
			}
			$config_list[] = [
				"instanceID" => $instanceRecipeUIID,
				"parent" => $room_ips_id,
				"id" => "NEEO Recipes",
				"type" => $this->Translate("NEEO Recipes"),
				"room" => $this->Translate("NEEO Recipes"),
				"device" => $this->Translate("NEEO Recipes"),
				"manufacturer" => "NEEO",
				"name" => $this->Translate("NEEO Recipes"),
				"location" => [
					$this->Translate('devices'), "NEEO", $this->Translate('NEEO Devices'), $hostname . " (" . $hubip . ")"
				],
				"create" => [
					[
						"moduleID" => "{935CA17E-9AE6-1992-7DD3-65B283B07C51}",
						"configuration" => [
							"recipes" => $recipes_json
						]
					]
				]
			];
		}

		return $config_list;
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
				'type' => 'Image',
				'image' => 'data:image/png;base64, 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'
			],
			[
				'type' => 'Label',
				'label' => 'If you want to add recipes to voice control, please create first the instance then active the voicecontrol checkbox of choice'
			],
			[
				'type' => 'CheckBox',
				'name' => 'GoogleHome',
				'caption' => 'Google Home'
			],
			[
				'type' => 'CheckBox',
				'name' => 'Alexa',
				'caption' => 'Alexa'
			],
			[
				'type' => 'CheckBox',
				'name' => 'Homekit',
				'caption' => 'Homekit'
			],
			[
				'type' => 'ExpansionPanel',
				'caption' => 'Visualisation',
				'items' => [
					[
						'type' => 'Label',
						'label' => 'Select category for visualisation, creating recipe links'
					],
					[
						'type' => 'CheckBox',
						'name' => 'UseVisualisation',
						'caption' => 'create links'
					],
					[
						'type' => 'SelectCategory',
						'name' => 'Visualisation',
						'caption' => 'Visualisation'
					]
				]
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
				'type' => 'Configurator',
				'name' => 'NEEOConfiguration',
				'caption' => 'NEEO configuration',
				'rowCount' => 20,
				'add' => false,
				'delete' => false,
				'sort' => [
					'column' => 'room',
					'direction' => 'ascending'
				],
				'columns' => [
					[
						'caption' => 'ID',
						'name' => 'id',
						'width' => '200px',
						'visible' => false
					],
					[
						'caption' => 'Type',
						'name' => 'type',
						'width' => '200px'
					],
					[
						'caption' => 'Room',
						'name' => 'room',
						'width' => '200px'
					],
					[
						'caption' => 'Device',
						'name' => 'device',
						'width' => '200px'
					],
					[
						'caption' => 'Manufacturer',
						'name' => 'manufacturer',
						'width' => '250px'
					],
					[
						'caption' => 'Name',
						'name' => 'name',
						'width' => 'auto'
					]
				],
				'values' => $this->Get_ListConfiguration()
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
				'caption' => 'NEEO configurator created.'
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
			]
		];

		return $form;
	}

	/** Sendet Eine Anfrage an den IO und liefert die Antwort.
	 *
	 * @param string $Method
	 * @return string | array
	 */
	private function SendData(string $Method, string $command)
	{
		$Data['DataID'] = '{99C86935-D78B-9589-41FA-4CFE517C9273}';
		$Data['Buffer'] = ['Method' => $Method, 'Command' => $command, 'Content' => ""];
		$this->SendDebug('Method:', $Method, 0);
		$this->SendDebug('Command:', $command, 0);
		$this->SendDebug('Send:', json_encode($Data), 0);
		$this->SendDebug('Form:', json_last_error_msg(), 0);
		$ResultString = @$this->SendDataToParent(json_encode($Data));
		return $ResultString;
	}


}
