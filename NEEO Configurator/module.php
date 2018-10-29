<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';



class NEEOConfigurator extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		// 1. Verfügbarer AIOSplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
		$this->ConnectParent("{A938EE1A-519B-4BAB-AEB1-EFC1B2B15A91}");
	}

	/**
	 * Interne Funktion des SDK.
	 */
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
	}


	/** Get Config Brain
	 *
	 * @return array
	 */
	private function Get_ListConfiguration()
	{
		$room_ips_id = 1;
		$config_list = [];
		$NEEOInstanceIDList = IPS_GetInstanceListByModuleID('{67252707-E627-4DFC-07D3-438452F20B23}'); // NEEO Devices
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$hubip = IPS_GetProperty($MyParent,'Host');
		$this->SendDebug('NEEO hubip', $hubip, 0);
		$systeminfo = $this->SendData('GET', '/v1/systeminfo/');
		$systemdata = json_decode($systeminfo);
		$hostname = $systemdata->hostname;
		$this->SendDebug('NEEO hostname', $hostname, 0);
		$config = $this->SendData('GET', '/v1/projects/home/');


		$this->SendDebug('NEEO Config', $config, 0);
		if(!empty($config))
		{
			$data = json_decode($config);
			if(property_exists($data, "name"))
			{
				$name = $data->name;
				$this->SendDebug('NEEO name', $name, 0);
				$version = $data->version;
				$this->SendDebug('NEEO version', $version, 0);
				$label = $data->label;
				$this->SendDebug('NEEO label', $label, 0);
				$configured = $data->configured;
				$this->SendDebug('NEEO configured', $configured, 0);
				$gdprAccepted = $data->gdprAccepted;
				$this->SendDebug('NEEO gdpr accepted', $gdprAccepted, 0);
				$rooms = $data->rooms;
				foreach($rooms as $room)
				{
					$room_name = $room->name;
					$this->SendDebug('NEEO room name', $room_name, 0);
					$room_icon = $room->icon;
					$this->SendDebug('NEEO icon', $room_icon, 0);
					$hasController = boolval($room->hasController);
					$this->SendDebug('NEEO has controller', print_r($hasController, true), 0);
					$devices = $room->devices;
					$config_list[] = [ "id" => $room_ips_id,
						"type" => $this->Translate("room"),
						"room" => $this->Translate($room_name)
					];
					foreach($devices as $device)
					{
						$instanceID = 0;
						$device_name = $device->name;
						$this->SendDebug('NEEO device name', $device_name, 0);
						$device_roomName = $device->roomName;
						$this->SendDebug('NEEO device room name', $device_roomName, 0);
						$device_roomKey = $device->roomKey;
						$this->SendDebug('NEEO device room key', $device_roomKey, 0);
						$adapterDeviceId = $device->adapterDeviceId;
						$this->SendDebug('NEEO adapter device id', $adapterDeviceId, 0);
						$details = $device->details;
						$sourceName = $details->sourceName;
						$this->SendDebug('NEEO source name', $sourceName, 0);
						$adapterName = $details->adapterName;
						$this->SendDebug('NEEO adapter name', $adapterName, 0);
						$type = $details->type;
						$this->SendDebug('NEEO type', $type, 0);
						$manufacturer = $details->manufacturer;
						$this->SendDebug('NEEO manufacturer', $manufacturer, 0);
						$detail_name = $details->name;
						$this->SendDebug('NEEO detail name', $detail_name, 0);
						$commandSets = $details->commandSets;
						$commandSetsJSON = json_encode($commandSets);
						$this->SendDebug('NEEO command sets', json_encode($commandSets), 0);
						$deviceCapabilities = $details->deviceCapabilities;
						$deviceCapabilitiesJSON = json_encode($deviceCapabilities);
						$this->SendDebug('NEEO device capabilities', json_encode($deviceCapabilities), 0);
						$roles = $details->roles;
						$this->SendDebug('NEEO roles', json_encode($roles), 0);
						$capabilities = $details->capabilities;
						$this->SendDebug('NEEO capabilities', json_encode($capabilities), 0);
						$icon = $details->icon;
						$this->SendDebug('NEEO icon', $icon, 0);
						$powerMode = $device->powerMode;
						$this->SendDebug('NEEO power mode', $powerMode, 0);
						if(property_exists($device, 'macros'))
						{
							$macros = $device->macros;
							$macros_JSON = json_encode($macros);
							foreach($macros as $macroname => $macro)
							{
								$macro_key = $macro->key;
								$this->SendDebug('NEEO macro key', $macro_key, 0);
								$componentType = $macro->componentType;
								$this->SendDebug('NEEO component type', $componentType, 0);
								$macro_name = $macro->name;
								$this->SendDebug('NEEO macro name', $macro_name, 0);
								$macro_label = $macro->label;
								$this->SendDebug('NEEO macro label', $macro_label, 0);
								$macro_deviceName = $macro->deviceName;
								$this->SendDebug('NEEO macro device name', $macro_deviceName, 0);
								$deviceKey = $macro->deviceKey;
								$this->SendDebug('NEEO device key', $deviceKey, 0);
								$macro_roomName = $macro->roomName;
								$this->SendDebug('NEEO macro room name', $macro_roomName, 0);
								$macro_roomKey = $macro->roomKey;
								$this->SendDebug('NEEO macro room key', $macro_roomKey, 0);
							}
						}
						$device_info = $this->SendData('GET', '/v1/projects/home/rooms/'.$device_roomKey.'/devices/'.$deviceKey.'/');
						$this->SendDebug('NEEO device info', $device_info, 0);
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
									"configuration" =>  [
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
										"NEEOVars" => true,
										"NEEOScript" => false
									]
								]
							]
						];

					}
					$room_ips_id++;
				}
			}else{
				$this->SendDebug('NEEO Config', $data, 0);
			}
			$instanceWebUIID = 0;
			$config_list[] = [ "id" => $room_ips_id,
				"type" => "room",
				"room" => "NEEOWebUI"
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
						"configuration" =>  [
							"Host" => $hubip
						]
					]
				]
			];
		}

		return $config_list;
	}



	/**
	 * Interne Funktion des SDK.
	 */

	public function GetConfigurationForm()
	{
		$Values = $this->Get_ListConfiguration();
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		/* does not work
		if (count($Values) > 0) {
			foreach ($Values as $key => $row) {
				$SortRoom[$key] = $row['room'];
				$SortType[$key] = $row['type'];
			}
			array_multisort($SortRoom, SORT_ASC, $SortType, SORT_ASC, $Values);
		}
		*/
		$Form['actions'][0]['values'] = $Values;
		$this->SendDebug('FORM', json_encode($Form), 0);
		$this->SendDebug('FORM', json_last_error_msg(), 0);
		return json_encode($Form);
	}


	/** Sendet Eine Anfrage an den IO und liefert die Antwort.
	 *
	 * @param string $Method
	 * @return string | array
	 */
	private function SendData(string $Method, string $command)
	{
		$Data['DataID'] = '{99C86935-D78B-9589-41FA-4CFE517C9273}';
		$Data['Buffer'] = ['Method' => $Method, 'Command' => $command, 'Content' => "" ];
		$this->SendDebug('Method:', $Method, 0);
		$this->SendDebug('Command:', $command, 0);
		$this->SendDebug('Send:', json_encode($Data), 0);
		$this->SendDebug('Form:', json_last_error_msg(), 0);
		$ResultString = @$this->SendDataToParent(json_encode($Data));
		return $ResultString;
	}


}
