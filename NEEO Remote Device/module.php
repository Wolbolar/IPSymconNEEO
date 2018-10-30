<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';
include_once(__DIR__ . "/../libs/SSDPTraits.php");

// Module for NEEO

class NEEORemoteDevice extends IPSModule
{
	use BufferHelper,
		DebugHelper,
		InstanceStatus /* Diverse Methoden für die Verwendung im Splitter */ {
		InstanceStatus::MessageSink as IOMessageSink; // MessageSink gibt es sowohl hier in der Klasse, als auch im Trait InstanceStatus. Hier wird für die Methode im Trait ein Alias benannt.
		InstanceStatus::RegisterParent as IORegisterParent; // MessageSink gibt es sowohl hier in der Klasse, als auch im Trait InstanceStatus. Hier wird für die Methode im Trait ein Alias benannt.
	}

	// helper properties
	private $position = 0;

	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->ConnectParent("{A938EE1A-519B-4BAB-AEB1-EFC1B2B15A91}");

		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
		$this->RegisterPropertyString("neeo_hostname", "");
		$this->RegisterPropertyString("type", "");
		$this->RegisterPropertyString("device", "");
		$this->RegisterPropertyString("room_name", "");
		$this->RegisterPropertyString("room_icon", "");
		$this->RegisterPropertyBoolean("hasController", false);
		$this->RegisterPropertyString("device_name", "");
		$this->RegisterPropertyString("device_roomName", "");
		$this->RegisterPropertyString("device_roomKey", "");
		$this->RegisterPropertyString("commandSets", "");
		$this->RegisterPropertyString("deviceCapabilities", "");
		$this->RegisterPropertyString("deviceKey", "");
		$this->RegisterPropertyString("macros", "");
		$this->RegisterPropertyString("manufacturer", "");
		$this->RegisterPropertyString("device_info", "");
		$this->RegisterPropertyBoolean("NEEOVars", false);
		$this->RegisterPropertyBoolean("NEEOScript", false);
		$this->RegisterPropertyBoolean("NEEOForwardScript", false);
		$this->RegisterPropertyInteger("NEEOForwardScriptID", 0);
	}

	public function ApplyChanges()
	{
		// Wir wollen wissen wann IPS fertig ist mit dem starten, weil vorher funktioniert der Datenaustausch nicht.
		$this->RegisterMessage(0, IPS_KERNELSTARTED);

		// Wenn sich unserer IO ändert, wollen wir das auch wissen.
		$this->RegisterMessage($this->InstanceID, FM_CONNECT);
		$this->RegisterMessage($this->InstanceID, FM_DISCONNECT);

		//Never delete this line!
		parent::ApplyChanges();
		// Wenn Kernel nicht bereit, dann warten... IPS_KERNELSTARTED/KR_READY kommt ja gleich
		if (IPS_GetKernelRunlevel() <> KR_READY)
			return;

		$this->SetReceiveDataFilter('.*' . $this->ReadPropertyString('device_name') . '.*');

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
		$NEEOScript = $this->ReadPropertyBoolean("NEEOScript");
		$NEEOForwardScript = $this->ReadPropertyBoolean("NEEOForwardScript");
		$NEEOForwardScriptID = $this->ReadPropertyInteger("NEEOForwardScriptID");
		$type = $this->ReadPropertyString("type");
		if ($NEEOForwardScript == true && $NEEOForwardScriptID == 0) {
			$this->SetStatus(205);
		}
		if ($type == "") {
			$this->SetStatus(206);
		}
		if ($type != "") {
			$this->SetupVariables();
		}
		if ($NEEOScript) {
			$this->SetNEEOInstanceScripts();
		}
		$this->SetStatus(102);
	}

	/**
	 * Interne Funktion des SDK.
	 * Verarbeitet alle Nachrichten auf die wir uns registriert haben.
	 * @access public
	 */
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{
		// Zuerst mal den Trait InstanceStatus die Nachtichten verarbeiten lassen:
		$this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);
		switch ($Message) {
			case IPS_KERNELSTARTED: // only after IP-Symcon started
				$this->KernelReady(); // if IP-Symcon is ready
				break;
		}
	}

	/**
	 * Wird ausgeführt wenn der Kernel hochgefahren wurde.
	 * @access protected
	 */
	protected function KernelReady()
	{
		$this->ApplyChanges();
	}

	public function SetNEEOInstanceScripts()
	{
		$parent_room = IPS_GetParent($this->InstanceID);
		$parent_neeo_brain = IPS_GetParent($parent_room);
		$neeo_hostname = IPS_GetName($parent_neeo_brain);
		$parent_neeo_category = IPS_GetParent($parent_neeo_brain);
		$top_level_catid = $this->CreateNEEOScriptCategory($neeo_hostname, $parent_neeo_category);
		$room_catid = $this->CreateNEEOScriptCategoryRoom($neeo_hostname, $top_level_catid);
		$catid = $this->CreateNEEOScriptCategoryDevice($neeo_hostname, $room_catid);
		$this->CreateDeviceSkripts($catid);
	}

	private function CreateNEEOScriptCategory($neeo_hostname, $parent_neeo_category)
	{
		//Prüfen ob Kategorie schon existiert
		$NEEO_Script_CategoryID = @IPS_GetObjectIDByIdent("top_level_NEEOScript_" . $this->CreateIdent($neeo_hostname), $parent_neeo_category);
		if ($NEEO_Script_CategoryID === false) {
			$NEEO_Script_CategoryID = IPS_CreateCategory();
			IPS_SetName($NEEO_Script_CategoryID, $neeo_hostname . " " . $this->Translate("Scripts"));
			IPS_SetIdent($NEEO_Script_CategoryID, "top_level_NEEOScript_" . $this->CreateIdent($neeo_hostname)); // Ident muss eindeutig sein
			IPS_SetInfo($NEEO_Script_CategoryID, $neeo_hostname);
			IPS_SetParent($NEEO_Script_CategoryID, $parent_neeo_category);
		}
		$this->SendDebug("NEEO Script Category Brain", $NEEO_Script_CategoryID, 0);
		return $NEEO_Script_CategoryID;
	}

	private function CreateNEEOScriptCategoryRoom($neeo_hostname, $top_level_catid)
	{
		$roomname = $this->ReadPropertyString("room_name");
		//Prüfen ob Kategorie schon existiert
		$NEEO_Script_CategoryID = @IPS_GetObjectIDByIdent("room_NEEOScript_" . $this->CreateIdent($neeo_hostname) . "_" . $this->CreateIdent($roomname), $top_level_catid);
		if ($NEEO_Script_CategoryID === false) {
			$NEEO_Script_CategoryID = IPS_CreateCategory();
			IPS_SetName($NEEO_Script_CategoryID, $roomname);
			IPS_SetIdent($NEEO_Script_CategoryID, "room_NEEOScript_" . $this->CreateIdent($neeo_hostname) . "_" . $this->CreateIdent($roomname)); // Ident muss eindeutig sein
			IPS_SetInfo($NEEO_Script_CategoryID, $roomname);
			IPS_SetParent($NEEO_Script_CategoryID, $top_level_catid);
		}
		$this->SendDebug("NEEO Script Category Room", $NEEO_Script_CategoryID, 0);
		return $NEEO_Script_CategoryID;
	}

	private function CreateNEEOScriptCategoryDevice($neeo_hostname, $room_catid)
	{
		$roomname = $this->ReadPropertyString("room_name");
		$devicename = $this->ReadPropertyString("device_name");

		//Prüfen ob Kategorie schon existiert
		$NEEO_Script_CategoryID = @IPS_GetObjectIDByIdent("device_NEEOSkript_" . $this->CreateIdent($neeo_hostname) . "_" . $this->CreateIdent($roomname) . "_" . $this->CreateIdent($devicename), $room_catid);
		if ($NEEO_Script_CategoryID === false) {
			$NEEO_Script_CategoryID = IPS_CreateCategory();
			IPS_SetName($NEEO_Script_CategoryID, $devicename);
			IPS_SetIdent($NEEO_Script_CategoryID, "device_NEEOSkript_" . $this->CreateIdent($neeo_hostname) . "_" . $this->CreateIdent($roomname) . "_" . $this->CreateIdent($devicename)); // Ident muss eindeutig sein
			IPS_SetInfo($NEEO_Script_CategoryID, $roomname);
			IPS_SetParent($NEEO_Script_CategoryID, $room_catid);
		}
		$this->SendDebug("NEEO Skript Category Device", $NEEO_Script_CategoryID, 0);
		return $NEEO_Script_CategoryID;
	}

	private function CreateDeviceSkripts($catid)
	{
		$this->CreateMacroSkripts($catid);
	}

	private function CreateMacroSkripts($catid)
	{
		$devicename = $this->ReadPropertyString("device_name");
		$devicekey = $this->ReadPropertyString("deviceKey");
		$macros = $this->GetMacros();
		foreach ($macros as $command_type => $macro) {
			$macro_key = $macro->key;
			//Prüfen ob Script schon existiert
			$Scriptname = $devicename . "_" . $command_type;
			$scriptident = $this->CreateIdent("NEEO_Script_Device_" . $devicekey . "_Macro_" . $macro_key);
			$ScriptID = @IPS_GetObjectIDByIdent($scriptident, $catid);
			if ($ScriptID === false) {
				$ScriptID = IPS_CreateScript(0);
				IPS_SetName($ScriptID, $Scriptname);
				IPS_SetParent($ScriptID, $catid);
				IPS_SetIdent($ScriptID, $scriptident);
				$content = "<?" . PHP_EOL .
					"NEEO_Trigger_Makro(" . $this->InstanceID . ",\"" . $macro_key . "\");" . PHP_EOL .
					"?>";

				IPS_SetScriptContent($ScriptID, $content);
			}
		}
	}

	private function GetMacroKey($macro_command)
	{
		$macros = $this->GetMacros();
		foreach ($macros as $command_type => $macro) {
			$macro_key = $macro->key;
			if ($macro_command == $command_type) {
				return $macro_key;
			}
		}
		return false;
	}

	public function SetupVariables()
	{
		$NEEOVars = $this->ReadPropertyBoolean("NEEOVars");
		if ($NEEOVars) {
			$this->CreateNEEOVariables();
		} else {
			$this->DeleteNEEOVariables();
		}

		// $NEEOScript = $this->ReadPropertyBoolean('NEEOScript');
	}

	protected function CreateNEEOVariables()
	{
		$type = $this->ReadPropertyString("type");
		$device_type = $this->ReadPropertyString("device");
		$this->SendDebug("NEEO Device", "type: " . $type, 0);
		$device_config = $this->Get_Device();
		$device = json_decode($device_config);
		$macros = $device->macros;
		$details = $device->details;
		$this->SendDebug("NEEO Device", "details: " . json_encode($details), 0);
		$manufacturer = $details->manufacturer;
		$this->SendDebug("NEEO Device", "manufacturer: " . $manufacturer, 0);
		$name = $details->name;
		$this->SendDebug("NEEO Device", "name: " . $name, 0);
		// NEEO Cranium
		if ($manufacturer == "NEEO" && $name == "Cranium") {
			$objectid = $this->RegisterVariableBoolean('BRAIN_LED_STATE', $this->Translate('LED State'), '~Switch', $this->_getPosition());
			$this->SendDebug("NEEO Device", "variable LED STATE object id : " . $objectid, 0);
			$this->EnableAction('BRAIN_LED_STATE');
			$this->SetBrainReboot();
		}

		// MEDIAPLAYER (Shield etc.)
		if ($type == "MEDIAPLAYER") {
			if ($device_type == "mediaplayer") {
				// Nvidia Shield
				if ($manufacturer == "Nvidia") {
					$shield = strpos($name, "SHIELD TV");
					if ($shield >= 0) {
						$keys_miscellaneous = [
							["command" => "Home", "icon" => "HouseRemote"]
						];
						$this->SetMiscellaneous($name, $keys_miscellaneous);
						$keys_navigationbasic = [
							["command" => "DirectionDown", "icon" => "HollowArrowDown"],
							["command" => "DirectionLeft", "icon" => "HollowArrowLeft"],
							["command" => "DirectionRight", "icon" => "HollowArrowRight"],
							["command" => "DirectionUp", "icon" => "HollowArrowUp"],
							["command" => "Ok", "icon" => "Execute"]
						];
						$this->SetNavigationBasic($name, $keys_navigationbasic);
						$keys_navigationdvd = [
							["command" => "Back", "icon" => "HollowArrowLeft"]
						];
						$this->SetNavigationDVD($name, $keys_navigationdvd);
						$keys_tranportextended = [
							["command" => "Skip Back", "icon" => ""],
							["command" => "Skip Forward", "icon" => ""]
						];
						$this->SetTransportExtended($name, $keys_tranportextended);
						$this->SetVarVolume();
					}
				}
			}
		}

		// GAMECONSOLE
		if ($type == "GAMECONSOLE") {
			// Nvidia Shield
			if ($manufacturer == "Nvidia" || $manufacturer == "NVIDIA") {
				$shield = strpos($name, "SHIELD TV");
				if ($shield >= 0) {
					$keys_miscellaneous = [
						["command" => "Home", "icon" => "HouseRemote"]
					];
					$this->SetMiscellaneous($name, $keys_miscellaneous);
					$keys_navigationbasic = [
						["command" => "DirectionDown", "icon" => "HollowArrowDown"],
						["command" => "DirectionLeft", "icon" => "HollowArrowLeft"],
						["command" => "DirectionRight", "icon" => "HollowArrowRight"],
						["command" => "DirectionUp", "icon" => "HollowArrowUp"],
						["command" => "Ok", "icon" => "Execute"]
					];
					$this->SetNavigationBasic($name, $keys_navigationbasic);
					$keys_navigationdvd = [
						["command" => "Back", "icon" => "HollowArrowLeft"]
					];
					$this->SetNavigationDVD($name, $keys_navigationdvd);
					$keys_tranportextended = [
						["command" => "Skip Back", "icon" => ""],
						["command" => "Skip Forward", "icon" => ""]
					];
					$this->SetTransportExtended($name, $keys_tranportextended);
					$this->SetVarVolume();
				}
			}
			// Playstation
			if ($manufacturer == "Sony" || $manufacturer == "SONY") {
				$keys_miscellaneous = [
					["command" => "Home", "icon" => "HouseRemote"]
				];
				$this->SetMiscellaneous($name, $keys_miscellaneous);
				$keys_navigationbasic = [
					["command" => "DirectionDown", "icon" => "HollowArrowDown"],
					["command" => "DirectionLeft", "icon" => "HollowArrowLeft"],
					["command" => "DirectionRight", "icon" => "HollowArrowRight"],
					["command" => "DirectionUp", "icon" => "HollowArrowUp"]
				];
				$this->SetNavigationBasic($name, $keys_navigationbasic);
				$keys_navigationdvd = [
					["command" => "Back", "icon" => "HollowArrowLeft"]
				];
				$this->SetNavigationDVD($name, $keys_navigationdvd);
				$keys_tranportextended = [
					["command" => "Skip Back", "icon" => ""],
					["command" => "Skip Forward", "icon" => ""]
				];
				$this->SetTransportExtended($name, $keys_tranportextended);
				$this->SetVarVolume();
			}
		}
		foreach ($macros as $commandname => $macro) {
			if ($commandname == "POWER ON" || $commandname == "POWER_ON") {
				$this->SendDebug("NEEO Device", "Setup variable STATE", 0);
				$objectid = $this->RegisterVariableBoolean('STATE', $this->Translate('State'), '~Switch', $this->_getPosition());
				$this->SendDebug("NEEO Device", "variable STATE object id : " . $objectid, 0);
				$this->EnableAction('STATE');
			}
			if ($commandname == "DIGIT 0") {
				$this->SetNumericBasic();
			}
			if ($commandname == "PLAY") {
				// MEDIAPLAYER (Shield etc.)
				if ($type == "MEDIAPLAYER") {
					if ($device_type == "mediaplayer") {
						// Nvidia Shield
						if ($manufacturer == "Nvidia") {
							$shield = strpos($name, "SHIELD TV");
							if ($shield >= 0) {
								$keys_tranportbasic = [
									["command" => "Stop", "icon" => ""],
									["command" => "Play", "icon" => ""],
									["command" => "Pause", "icon" => ""],
									["command" => "Rewind", "icon" => ""],
									["command" => "FastForward", "icon" => ""]
								];
								$this->SetTransportBasic($name, $keys_tranportbasic);
							}
						} // Playstation
						elseif ($manufacturer == "Sony") {
							$keys_tranportbasic = [
								["command" => "Stop", "icon" => ""],
								["command" => "Play", "icon" => ""],
								["command" => "Pause", "icon" => ""],
								["command" => "Rewind", "icon" => ""],
								["command" => "FastForward", "icon" => ""]
							];
							$this->SetTransportBasic($name, $keys_tranportbasic);
						} else {
							$keys_tranportbasic = [
								["command" => "Stop", "icon" => ""],
								["command" => "Play", "icon" => ""],
								["command" => "Pause", "icon" => ""],
								["command" => "Rewind", "icon" => ""],
								["command" => "FastForward", "icon" => ""]
							];
							$this->SetTransportBasic($name, $keys_tranportbasic);
						}
					}
				}
				// GAMECONSOLE
				if ($type == "GAMECONSOLE") {
					// Nvidia Shield
					if ($manufacturer == "Nvidia" || $manufacturer == "NVIDIA") {
						$shield = strpos($name, "SHIELD TV");
						if ($shield >= 0) {
							$keys_tranportbasic = [
								["command" => "Stop", "icon" => ""],
								["command" => "Play", "icon" => ""],
								["command" => "Pause", "icon" => ""],
								["command" => "Rewind", "icon" => ""],
								["command" => "FastForward", "icon" => ""]
							];
							$this->SetTransportBasic($name, $keys_tranportbasic);
						}
					}
					// Playstation
					if ($manufacturer == "Sony" || $manufacturer == "SONY") {
						$shield = strpos($name, "SHIELD TV");
						if ($shield >= 0) {
							$keys_tranportbasic = [
								["command" => "Stop", "icon" => ""],
								["command" => "Play", "icon" => ""],
								["command" => "Pause", "icon" => ""],
								["command" => "Rewind", "icon" => ""],
								["command" => "FastForward", "icon" => ""]
							];
							$this->SetTransportBasic($name, $keys_tranportbasic);
						}
					}
				}
			}

			// ACCESSOIRE
			// Screen
			if ($commandname == "SCREEN UP (WHITE)") {
				$this->RegisterProfileAssociation(
					'NEEO.ScreenTransport',
					'',
					'',
					'',
					0,
					3,
					0,
					0,
					1,
					[
						[0, $this->Translate('Screen down (white)'), '', -1],
						[1, $this->Translate('Screen down (black)'), '', -1],
						[2, $this->Translate('Screen up (black)'), '', -1],
						[3, $this->Translate('Screen up (white)'), '', -1]
					]
				);
				$this->RegisterVariableInteger("ScreenTransport", $this->Translate('Screen'), "NEEO.ScreenTransport", $this->_getPosition());
				$this->EnableAction('ScreenTransport');
			}


			/*
 * Playstation
 * ANGLE
AUDIO
BACK
CIRCLE
CLEAR

DIGIT 0
DIGIT 1
DIGIT 2
DIGIT 3
DIGIT 4
DIGIT 5
DIGIT 6
DIGIT 7
DIGIT 8
DIGIT 9
EXIT
FORWARD
FUNCTION BLUE
FUNCTION GREEN
FUNCTION RED
FUNCTION YELLOW
GUIDE
INFO
L1
L2
L3
MENU
NEXT
OPEN/CLOSE
OPTIONS
PAGE DOWN
PAGE UP
PAUSE
PLAY
POWER OFF
POWER ON
PREVIOUS
R1
R2
R3
RETURN
REVERSE
SELECT
SQUARE
START
STOP
SUBTITLE
TIME
TRIANGLE
VIEW
X
 */

		}
		// LIGHT
		if ($type == "LIGHT") {
			if ($device_type == "hue") {
				$this->RegisterVariableInteger('LEVEL', $this->Translate('Level'), '~Intensity.255', $this->_getPosition());
				$this->EnableAction('LEVEL');
			}
		}

		// PROJECTOR


		// DVB

		// TV

		// SONOS


	}

	protected function DeleteNEEOVariables()
	{
		$type = $this->ReadPropertyString("type");
		// $device = $this->ReadPropertyString("device");
		$this->SendDebug("NEEO Device", "type: " . $type, 0);
		$device_config = $this->Get_Device();
		$device = json_decode($device_config);
		$macros = $device->macros;
		$details = $device->details;
		$this->SendDebug("NEEO Device", "details: " . json_encode($details), 0);
		$manufacturer = $details->manufacturer;
		$this->SendDebug("NEEO Device", "manufacturer: " . $manufacturer, 0);
		$name = $details->name;
		$this->SendDebug("NEEO Device", "name: " . $name, 0);
		// NEEO Cranium
		if ($manufacturer == "NEEO" && $name == "Cranium") {
			$this->SendDebug("NEEO Device", "delete variable BRAIN_LED_STATE", 0);
			$this->UnregisterVariable('BRAIN_LED_STATE');
			$this->SendDebug("NEEO Device", "delete variable BRAIN_REBOOT", 0);
			$this->UnregisterVariable('BRAIN_REBOOT');
		}

		foreach ($macros as $commandname => $macro) {
			if ($commandname == "POWER ON" || $commandname == "POWER_ON") {
				$this->SendDebug("NEEO Device", "delete variable STATE", 0);
				$this->UnregisterVariable('STATE');
			}
			if ($commandname == "DIGIT 0") {
				$this->SendDebug("NEEO Device", "delete variable NumericBasic", 0);
				$this->UnregisterVariable('NumericBasic');
			}
			if ($commandname == "PLAY") {
				$this->SendDebug("NEEO Device", "delete variable TransportBasic", 0);
				$this->UnregisterVariable('TransportBasic');
			}
			// ACCESSOIRE
			// Screen
			if ($commandname == "SCREEN UP (WHITE)") {
				$this->SendDebug("NEEO Device", "delete variable ScreenTransport", 0);
				$this->UnregisterVariable('ScreenTransport');
			}
			// GAMECONSOLE
			// Playstation

		}
		// LIGHT
		if ($type == "LIGHT") {
			if ($device == "hue") {
				$this->SendDebug("NEEO Device", "delete variable Level", 0);
				$this->UnregisterVariable('LEVEL');
			}
		}

		// PROJECTOR


		// DVB

		// TV

		// SONOS

		// MEDIAPLAYER
	}

	protected function SetNumericBasic()
	{
		$this->SendDebug("NEEO Device", "Setup variable numeric basic", 0);
		$this->RegisterProfileAssociation(
			'NEEO.NumericBasic',
			'',
			'',
			'',
			0,
			9,
			0,
			0,
			1,
			[
				[0, $this->Translate('Key 0'), '', -1],
				[1, $this->Translate('Key 1'), '', -1],
				[2, $this->Translate('Key 2'), '', -1],
				[3, $this->Translate('Key 3'), '', -1],
				[4, $this->Translate('Key 4'), '', -1],
				[5, $this->Translate('Key 5'), '', -1],
				[6, $this->Translate('Key 6'), '', -1],
				[7, $this->Translate('Key 7'), '', -1],
				[8, $this->Translate('Key 8'), '', -1],
				[9, $this->Translate('Key 9'), '', -1]
			]
		);
		$objectid = $this->RegisterVariableInteger("NumericBasic", $this->Translate('Numeric Basic'), "NEEO.NumericBasic", $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable NumericBasic object id : " . $objectid, 0);
		$this->EnableAction('NumericBasic');
	}

	protected function SetTransportBasic($devicename, $keys_tranportbasic)
	{
		$devicename = $this->CreateIdent($devicename);
		$MaxValue = count($keys_tranportbasic) - 1;
		$associations = [];
		$i = 0;
		foreach ($keys_tranportbasic as $key_tranportbasic) {
			$associations[] = [$i, $this->Translate($key_tranportbasic["command"]), $key_tranportbasic["icon"], -1];
			$i++;
		}
		$this->SendDebug("NEEO Device", "Setup variable transport basic", 0);
		$this->RegisterProfileAssociation(
			'NEEO.TransportBasic.' . $devicename,
			'',
			'',
			'',
			0,
			$MaxValue,
			0,
			0,
			1,
			$associations
		);
		$objectid = $this->RegisterVariableInteger("TransportBasic", $this->Translate('Transport Basic'), "NEEO.TransportBasic." . $devicename, $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable TransportBasic object id : " . $objectid, 0);
		$this->EnableAction('TransportBasic');
	}

	protected function SetTransportExtended($devicename, $keys_tranportextended)
	{
		$devicename = $this->CreateIdent($devicename);
		$MaxValue = count($keys_tranportextended) - 1;
		$associations = [];
		$i = 0;
		foreach ($keys_tranportextended as $key_tranportextended) {
			$associations[] = [$i, $this->Translate($key_tranportextended["command"]), $key_tranportextended["icon"], -1];
			$i++;
		}
		$this->SendDebug("NEEO Device", "Setup variable transport extended", 0);
		$this->RegisterProfileAssociation(
			'NEEO.TransportExtended.' . $devicename,
			'',
			'',
			'',
			0,
			$MaxValue,
			0,
			0,
			1,
			$associations
		);
		$objectid = $this->RegisterVariableInteger("TransportExtended", $this->Translate('Transport Extended'), "NEEO.TransportExtended." . $devicename, $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable TransportExtended object id : " . $objectid, 0);
		$this->EnableAction('TransportExtended');
	}

	protected function SetMiscellaneous($devicename, $keys_miscellaneous)
	{
		$devicename = $this->CreateIdent($devicename);
		$MaxValue = count($keys_miscellaneous) - 1;
		$associations = [];
		$i = 0;
		foreach ($keys_miscellaneous as $key_miscellaneous) {
			$associations[] = [$i, $this->Translate($key_miscellaneous["command"]), $key_miscellaneous["icon"], -1];
			$i++;
		}
		$this->SendDebug("NEEO Device", "Setup variable miscellaneous", 0);
		$this->RegisterProfileAssociation(
			'NEEO.Miscellaneous.' . $devicename,
			'',
			'',
			'',
			0,
			$MaxValue,
			0,
			0,
			1,
			$associations
		);
		$objectid = $this->RegisterVariableInteger("Miscellaneous", $this->Translate('Miscellaneous'), "NEEO.Miscellaneous." . $devicename, $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable miscellaneous object id : " . $objectid, 0);
		$this->EnableAction('Miscellaneous');
	}

	protected function SetNavigationBasic($devicename, $keys_navigationbasic)
	{
		$devicename = $this->CreateIdent($devicename);
		$MaxValue = count($keys_navigationbasic) - 1;
		$associations = [];
		$i = 0;
		foreach ($keys_navigationbasic as $key_navigationbasic) {
			$associations[] = [$i, $this->Translate($key_navigationbasic["command"]), $key_navigationbasic["icon"], -1];
			$i++;
		}
		$this->SendDebug("NEEO Device", "Setup variable NavigationBasic", 0);
		$this->RegisterProfileAssociation(
			'NEEO.NavigationBasic.' . $devicename,
			'',
			'',
			'',
			0,
			$MaxValue,
			0,
			0,
			1,
			$associations
		);
		$objectid = $this->RegisterVariableInteger("NavigationBasic", $this->Translate('NavigationBasic'), "NEEO.NavigationBasic." . $devicename, $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable NavigationBasic object id : " . $objectid, 0);
		$this->EnableAction('NavigationBasic');
	}

	protected function SetNavigationDVD($devicename, $keys_navigationdvd)
	{
		$devicename = $this->CreateIdent($devicename);
		$MaxValue = count($keys_navigationdvd) - 1;
		$associations = [];
		$i = 0;
		foreach ($keys_navigationdvd as $key_navigationdvd) {
			$associations[] = [$i, $this->Translate($key_navigationdvd["command"]), $key_navigationdvd["icon"], -1];
			$i++;
		}
		$this->SendDebug("NEEO Device", "Setup variable NavigationDVD", 0);
		$this->RegisterProfileAssociation(
			'NEEO.NavigationDVD.' . $devicename,
			'',
			'',
			'',
			0,
			$MaxValue,
			0,
			0,
			1,
			$associations
		);
		$objectid = $this->RegisterVariableInteger("NavigationDVD", $this->Translate('NavigationDVD'), "NEEO.NavigationDVD." . $devicename, $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable NavigationDVD object id : " . $objectid, 0);
		$this->EnableAction('NavigationDVD');
	}

	protected function SetVarVolume()
	{
		$this->SendDebug("NEEO Device", "Setup variable Volume", 0);
		$this->RegisterProfileAssociation(
			'NEEO.Volume',
			'',
			'',
			'',
			0,
			2,
			0,
			0,
			1,
			[
				[0, $this->Translate('Mute'), '', -1],
				[1, $this->Translate('Volume Down'), '', -1],
				[2, $this->Translate('Volume Up'), '', -1]
			]
		);
		$objectid = $this->RegisterVariableInteger("Volume", $this->Translate('Volume'), "NEEO.Volume", $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable Volume object id : " . $objectid, 0);
		$this->EnableAction('Volume');
	}

	protected function SetBrainReboot()
	{
		$this->SendDebug("NEEO Device", "Setup variable Reboot", 0);
		$this->RegisterProfileAssociation(
			'NEEO.Brian.Reboot',
			'',
			'',
			'',
			0,
			0,
			0,
			0,
			1,
			[
				[0, $this->Translate('Reboot'), '', -1]
			]
		);
		$objectid = $this->RegisterVariableInteger("BRAIN_REBOOT", $this->Translate('Reboot'), "NEEO.Brian.Reboot", $this->_getPosition());
		$this->SendDebug("NEEO Device", "variable Reboot object id : " . $objectid, 0);
		$this->EnableAction('BRAIN_REBOOT');
	}

	private function CreateIdent($str)
	{
		$search = array("ä", "ö", "ü", "ß", "Ä", "Ö",
			"Ü", "&", "é", "á", "ó", "-", " :)",
			" :D", " :-)", " :P", " :O", " ;D", " ;)", " ^^",
			" :|", " :-/", ":)", ":D", ":-)", ":P", ":O",
			";D", ";)", "^^", ":|", ":-/", "(", ")",
			"[", "]", "<", ">", "!", "\"", "§",
			"$", "%", "&", "/", "(", ")", "=",
			"?", "`", "´", "*", "'", ":", ";",
			"²", "³", "{", "}", "\\", "~", "#",
			"+", ".", ",", "=", ":", "=)");
		$replace = array("ae", "oe", "ue", "ss", "Ae", "Oe",
			"Ue", "und", "e", "a", "o", "_", "",
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

	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		// $this->SendDebug("NEEO Recieve:", $data, 0);
		$actionparameter = NULL;
		$payload = $data->Buffer;
		$action = "";
		$device = "";
		$room = "";
		$actionparameter = "";
		$recipe = "";
		if (property_exists($payload, 'action')) {
			$action = $payload->action;
			$this->SendDebug("NEEO Recieve:", "Action: " . $action, 0);
		}
		if (property_exists($payload, 'device')) {
			$device = $payload->device;
			$this->SendDebug("NEEO Recieve:", "Device: " . $device, 0);
		}
		if (property_exists($payload, 'room')) {
			$room = $payload->room;
			$this->SendDebug("NEEO Recieve:", "Room: " . $room, 0);
		}
		if (property_exists($payload, 'actionparameter')) {
			$actionparameter = $payload->actionparameter;
			$this->SendDebug("NEEO Recieve:", "Action parameter: " . json_encode($actionparameter), 0);
		}
		if (property_exists($payload, 'recipe')) {
			$recipe = $payload->recipe;
			$this->SendDebug("NEEO Recieve:", "Recipe: " . $recipe, 0);
		}
		$NEEOForwardScript = $this->ReadPropertyBoolean("NEEOForwardScript");
		$NEEOForwardScriptID = $this->ReadPropertyInteger("NEEOForwardScriptID");
		if ($NEEOForwardScript == true && $NEEOForwardScriptID != 0) {
			IPS_RunScriptEx($NEEOForwardScriptID, array("action" => $action, "device" => $device, "room" => $room, "actionparameter" => $actionparameter, "recipe" => $recipe));
		}

		$this->WriteValues($action, $actionparameter, $device);
	}

	protected function WriteValues($action, $actionparameter = NULL, $device = NULL)
	{
		if ($action == "POWER_OFF") {
			$this->SetValue("STATE", false);
			$this->SendDebug("NEEO Recieve:", "Set State to false", 0);
		}
		if ($action == "POWER_ON") {
			$this->SetValue("STATE", true);
			$this->SendDebug("NEEO Recieve:", "Set State to true", 0);
		}
		if ($action == "Light" && $actionparameter == 0) {
			$this->SetValue("STATE", false);
			$this->SendDebug("NEEO Recieve:", "Set State to false", 0);
		}
		if ($action == "Light" && $actionparameter == 1) {
			$this->SetValue("STATE", true);
			$this->SendDebug("NEEO Recieve:", "Set State to true", 0);
		}
		if ($action == "LED_ON") {
			if ($device == "NEEO Cranium") {
				$this->SetValue("BRAIN_LED_STATE", true);
				$this->SendDebug("NEEO Recieve:", "Set LED State to true", 0);
			}
		}
		if ($action == "LED_OFF") {
			if ($device == "NEEO Cranium") {
				$this->SetValue("BRAIN_LED_STATE", false);
				$this->SendDebug("NEEO Recieve:", "Set LED State to false", 0);
			}
		}
		if ($action == "brightness") {
			if ($actionparameter == 0) {
				$this->SetValue("STATE", false);
				$this->SendDebug("NEEO Recieve:", "Set State to false", 0);
			} else {
				$this->SetValue("STATE", true);
				$this->SendDebug("NEEO Recieve:", "Set State to true", 0);
			}
			$this->SetValue("LEVEL", $actionparameter);
		}
	}

	// Get a specific device and it's child configurations
	public function Get_Device()
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/';
		$config = $this->SendData('GET', $command);
		$device = json_decode($config);
		$name = $device->name;
		$this->SendDebug("NEEO Device:", "name: " . $name, 0);
		$roomName = $device->roomName;
		$this->SendDebug("NEEO Device:", "room: " . $roomName, 0);
		$roomKey = $device->roomKey;
		$this->SendDebug("NEEO Device:", "room key: " . $roomKey, 0);
		$adapterDeviceId = $device->adapterDeviceId;
		$this->SendDebug("NEEO Device:", "adapter device id: " . $adapterDeviceId, 0);
		$details = $device->details;
		$sourceName = $details->sourceName;
		$this->SendDebug("NEEO Device:", "source name: " . $sourceName, 0);
		$adapterName = $details->adapterName;
		$this->SendDebug("NEEO Device:", "adapter name: " . $adapterName, 0);
		$type = $details->type;
		$this->SendDebug("NEEO Device:", "type: " . $type, 0);
		$manufacturer = $details->manufacturer;
		$this->SendDebug("NEEO Device:", "manufacturer: " . $manufacturer, 0);
		$icon = $details->icon;
		$this->SendDebug("NEEO Device:", "icon: " . $icon, 0);
		$name = $details->name;
		$this->SendDebug("NEEO Device:", "name: " . $name, 0);
		$commandSets = $details->commandSets;
		$this->SendDebug("NEEO Device:", "command sets: " . json_encode($commandSets), 0);
		//$macros = $device->macros;
		$switches = $device->switches;
		if (property_exists($switches, 'Light')) {
			$light = $switches->Light;
			$light_name = $light->name;
			$this->SendDebug("NEEO Device:", "light name: " . $light_name, 0);
			$label = $light->label;
			$this->SendDebug("NEEO Device:", "light label: " . $label, 0);
			$light_switch_key = $light->key;
			$this->SendDebug("NEEO Device:", "light switch key: " . $light_switch_key, 0);
			$componentType = $light->componentType;
			$this->SendDebug("NEEO Device:", "component type: " . $componentType, 0);
			// $sensor = $light->sensor;
		}

		// $sensors = $device->sensors;
		$sliders = $device->sliders;
		if (property_exists($sliders, 'brightness')) {
			$brightness = $sliders->brightness;
			$slider_key = $brightness->key;
			$this->SendDebug("NEEO Device:", "slider key: " . $slider_key, 0);
			$slider_name = $brightness->name;
			$this->SendDebug("NEEO Device:", "slider name: " . $slider_name, 0);
			$slider_label = $brightness->label; // Dimmer
			$this->SendDebug("NEEO Device:", "slider label: " . $slider_label, 0);
			$slider_range = $brightness->range;
			$range_min = $slider_range[0];
			$range_max = $slider_range[1];
			$this->SendDebug("NEEO Device:", "Range from " . $range_min . " to " . $range_max, 0);
			$slider_unit = $brightness->unit;
			$this->SendDebug("NEEO Device:", "unit: " . $slider_unit, 0);
			$slider_componentType = $brightness->componentType;
			$this->SendDebug("NEEO Device:", "component type: " . $componentType, 0);
			// $slider_sensor = $brightness->sensor;
		}

		return $config;
	}

	// Get all macros from a specific device
	public function Get_Device_Makros()
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/macros';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// Trigger a Macro (Push a button)
	public function Trigger_Makro($Macro_KEY)
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/macros/' . $Macro_KEY . '/trigger';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// trigger a recipe
	public function Trigger_Recipe($Macro_KEY)
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/macros/' . $Macro_KEY . '/trigger';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// power off a Scenario
	protected function PowerOff_Scenario($Scenario_KEY)
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/scenarios/' . $Scenario_KEY . '/poweroff';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// Start favourite Channel
	protected function Start_Channel($channel)
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/favorites/' . $channel . '/trigger';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	/** Set Brightness
	 * @param int $value 0- 255
	 */
	public function SetBrightness(int $value)
	{
		$this->Set_Slider("brightness", $value);
	}

	// Set Slider
	protected function Set_Slider($slidertype, $value)
	{
		$Slider_KEY = $this->GetSliderKey($slidertype);
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/sliders/' . $Slider_KEY . '/';
		$content = '{"value":' . $value . '}';
		$result = $this->SendData('PUT', $command, $content);
		return $result;
	}

	protected function GetSliderKey($slidertype)
	{
		$device_info = $this->ReadPropertyString("device_info");
		$device = json_decode($device_info);
		if ($slidertype == "brightness") {
			$sliders = $device->sliders;
			$brightness = $sliders->brightness;
			$slider_key = $brightness->key;
		}
		return $slider_key;
	}

	// Set a switch power on
	public function Switch_PowerOn()
	{
		$Switch_KEY = $this->GetSwitchKey("Light");
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/switches/' . $Switch_KEY . '/on';
		$result = $this->SendData('PUT', $command);
		return $result;
	}

	// Set a switch power off
	public function Switch_PowerOff()
	{
		$Switch_KEY = $this->GetSwitchKey("Light");
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/switches/' . $Switch_KEY . '/off';
		$result = $this->SendData('PUT', $command);
		return $result;
	}

	protected function GetSwitchKey($switchtype)
	{
		$device_info = $this->ReadPropertyString("device_info");
		$device = json_decode($device_info);
		if ($switchtype == "Light") {
			$switches = $device->switches;
			if (property_exists($switches, 'Light')) {
				$light = $switches->Light;
				$light_switch_key = $light->key;
			}
		}
		return $light_switch_key;
	}

	public function PowerToogle(bool $state)
	{
		if ($state) {
			$response = $this->PowerOn();
		} else {
			$response = $this->PowerOff();
		}
		return $response;
	}

	public function PowerOn()
	{
		$macro_key_poweron = $this->GetMacroKey("POWER_ON");
		$this->SendDebug("NEEO Device", "Power On", 0);
		$this->SendDebug("NEEO Device", "macro key:" . $macro_key_poweron, 0);
		$response = $this->Trigger_Makro($macro_key_poweron);
		return $response;
	}

	public function PowerOff()
	{
		$macro_key_poweroff = $this->GetMacroKey("POWER_OFF");
		$this->SendDebug("NEEO Device", "Power Off", 0);
		$this->SendDebug("NEEO Device", "macro key:" . $macro_key_poweroff, 0);
		$response = $this->Trigger_Makro($macro_key_poweroff);
		return $response;
	}

	public function LED_On()
	{
		$cranium = $this->CheckCranium();
		if ($cranium) {
			$macro_key_led_on = $this->GetMacroKey("LED_ON");
			$this->SendDebug("NEEO Cranium", "LED on", 0);
			$this->SendDebug("NEEO Cranium", "macro key:" . $macro_key_led_on, 0);
			$response = $this->Trigger_Makro($macro_key_led_on);
			return $response;
		} else {
			$this->SendDebug("NEEO Cranium", "No cranium device found", 0);
			return false;
		}
	}

	public function LED_Off()
	{
		$cranium = $this->CheckCranium();
		if ($cranium) {
			$macro_key_led_off = $this->GetMacroKey("LED_OFF");
			$this->SendDebug("NEEO Cranium", "LED off", 0);
			$this->SendDebug("NEEO Cranium", "macro key:" . $macro_key_led_off, 0);
			$response = $this->Trigger_Makro($macro_key_led_off);
			return $response;
		} else {
			$this->SendDebug("NEEO Cranium", "No cranium device found", 0);
			return false;
		}
	}

	public function Brain_Reboot()
	{
		$cranium = $this->CheckCranium();
		if ($cranium) {
			$macro_key_led_on = $this->GetMacroKey("REBOOT_BRAIN");
			$this->SendDebug("NEEO Cranium", "Reboot Brain", 0);
			$this->SendDebug("NEEO Cranium", "macro key:" . $macro_key_led_on, 0);
			$response = $this->Trigger_Makro($macro_key_led_on);
			return $response;
		} else {
			$this->SendDebug("NEEO Cranium", "No cranium device found", 0);
			return false;
		}
	}

	private function CheckCranium()
	{
		$cranium = false;
		$device_config = $this->Get_Device();
		$device = json_decode($device_config);
		$details = $device->details;
		$manufacturer = $details->manufacturer;
		$name = $details->name;
		// NEEO Cranium
		if ($manufacturer == "NEEO" && $name == "Cranium") {
			$cranium = true;
		}
		return $cranium;
	}

	// Recipes
	public function Recipes()
	{
		$command = '/v1/api/Recipes';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// Get recipe state
	protected function Get_State_Recipe($Recipe_KEY)
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/recipes/' . $Recipe_KEY . '/isactive';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// get the active scenariokeys
	protected function Get_Active_Scenario()
	{
		$command = '/v1/projects/home/activescenariokeys';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	// Sonos start menu (you can find here the directory-key for each element
	protected function Sonos_Start_Menu()
	{
		$command = '/v1/projects/home/rooms/' . $this->ReadPropertyString("device_roomKey") . '/devices/' . $this->ReadPropertyString("deviceKey") . '/getdirectoryrootitems';
		$config = $this->SendData('GET', $command);
		return $config;
	}

	private function SendData(string $Method, $command, $content = NULL)
	{
		$Data['DataID'] = '{D9983673-4093-BE00-3D73-84D6124CB016}';
		$Data['Buffer'] = ['Method' => $Method, 'Command' => $command, 'Content' => $content];
		$this->SendDebug('Method:', $Method, 0);
		$this->SendDebug('Command:', $command, 0);
		$this->SendDebug('Content:', $content, 0);
		$ResultString = @$this->SendDataToParent(json_encode($Data));
		return $ResultString;
	}


	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case "STATE":
				$this->PowerToogle($Value);
				break;
			case "LEVEL":
				$this->Set_Slider("brightness", $Value);
				break;
			case "BRAIN_LED_STATE":
				if ($Value) {
					$this->LED_On();
				} else {
					$this->LED_Off();
				}
				break;
			case "BRAIN_REBOOT":
				$this->Brain_Reboot();
				break;
			default:
				$this->SendDebug("NEEO", "Invalid ident", 0);
		}
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
		$type = $this->ReadPropertyString("type");
		if ($type == "") {
			$form = [
				[
					'type' => 'Label',
					'label' => 'Please do not create a device manually, you have to use the NEEO configurator for setup'
				]
			];
		} else {
			$form = [
				[
					'type' => 'CheckBox',
					'name' => 'NEEOVars',
					'caption' => 'NEEO variables'
				],
				[
					'type' => 'CheckBox',
					'name' => 'NEEOScript',
					'caption' => 'NEEO scripts'
				],
				[
					'type' => 'CheckBox',
					'name' => 'NEEOForwardScript',
					'caption' => 'NEEO Forward Script'
				]
			];
			$NEEOForwardScript = $this->ReadPropertyBoolean("NEEOForwardScript");
			if ($NEEOForwardScript) {
				$form = array_merge_recursive(
					$form,
					[
						[
							'name' => 'NEEOForwardScriptID',
							'type' => 'SelectScript',
							'caption' => 'Forward Script'
						]
					]
				);
			}
		}


		return $form;
	}

	/**
	 * return form actions
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [
			[
				'type' => 'Tree',
				'name' => 'device_features',
				'caption' => 'Device features',
				'rowCount' => 5,
				'add' => false,
				'delete' => false,
				'sort' => [
					'column' => 'macro',
					'direction' => 'ascending'
				],
				'columns' => [
					[
						'name' => 'type',
						'label' => 'type',
						'width' => '200px'
					],
					[
						'name' => 'device',
						'label' => 'device',
						'width' => '200px'
					],
					[
						'name' => 'device_roomName',
						'label' => 'room name',
						'width' => '200px'
					],
					[
						'name' => 'device_roomKey',
						'label' => 'room key',
						'width' => '150px'
					],
					[
						'name' => 'manufacturer',
						'label' => 'manufacturer',
						'width' => '150px',

					],
					[
						'name' => 'device_name',
						'label' => 'device name',
						'width' => '250px',

					],
					[
						'name' => 'deviceKey',
						'label' => 'device key',
						'width' => '150px',

					],
					[
						'name' => 'macro',
						'label' => 'macro',
						'width' => 'auto'
					]
				],
				'values' => $this->GetMacroList()
			]
		];

		return $form;
	}

	protected function GetMacroList()
	{
		$device_roomName = $this->ReadPropertyString("device_roomName");
		$device_roomKey = $this->ReadPropertyString("device_roomKey");
		$device_name = $this->ReadPropertyString("device_name");
		$deviceKey = $this->ReadPropertyString("deviceKey");
		$manufacturer = $this->ReadPropertyString("manufacturer");
		$type = $this->ReadPropertyString("type");
		$device = $this->ReadPropertyString("device");
		$id = 1;
		$macros = $this->GetMacros();
		$macro_list = [
			[
				'id' => $id,
				'type' => $this->Translate($type),
				'device' => $this->Translate($device),
				'device_roomName' => $this->Translate($device_roomName),
				'device_roomKey' => $device_roomKey,
				'manufacturer' => $this->Translate($manufacturer),
				'device_name' => $this->Translate($device_name),
				'deviceKey' => $deviceKey
			]
		];
		if ($macros) {
			foreach ($macros as $macroname => $macro) {
				$id++;
				$macro_label = $macro->label;
				$macro_list[] = [
					'id' => $id,
					'parent' => 1,
					'macro' => $macro_label
				];
			}
		}
		return $macro_list;
	}

	public function GetMacros()
	{
		$macros_JSON = $this->ReadPropertyString("macros");
		$macros = false;
		if ($macros_JSON != "") {
			$macros = json_decode($macros_JSON);
			foreach ($macros as $macroname => $macro) {
				$macro_key = $macro->key;
				$this->SendDebug('NEEO', 'macro key: ' . $macro_key, 0);
				$componentType = $macro->componentType;
				$this->SendDebug('NEEO', 'componentType: ' . $componentType, 0);
				$macro_name = $macro->name;
				$this->SendDebug('NEEO', 'macro name: ' . $macro_name, 0);
				$macro_label = $macro->label;
				$this->SendDebug('NEEO', 'macro label: ' . $macro_label, 0);
				$macro_deviceName = $macro->deviceName;
				$this->SendDebug('NEEO', 'macro device name: ' . $macro_deviceName, 0);
				$deviceKey = intval($macro->deviceKey);
				$this->SendDebug('NEEO', 'device key: ' . $deviceKey, 0);
				$macro_roomName = $macro->roomName;
				$this->SendDebug('NEEO', 'macro room name: ' . $macro_roomName, 0);
				$macro_roomKey = intval($macro->roomKey);
				$this->SendDebug('NEEO', 'macro room key: ' . $macro_roomKey, 0);
			}
		}
		return $macros;
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
				'caption' => 'NEEO created.'
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
				'code' => 205,
				'icon' => 'error',
				'caption' => 'please select a script.'
			],
			[
				'code' => 206,
				'icon' => 'error',
				'caption' => 'please use the configurator for setup, do not create the instace manually.'
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