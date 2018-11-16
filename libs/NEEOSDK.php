<?php
/**
 * Created by PhpStorm.
 * User: Fonzo
 * Date: 09.06.2018
 * Time: 23:44
 */

namespace NEEOSDK;


class NEEOSDK
{
	// device building

	private $NeeoID = 0;
	private $Device_Configuration = [];

	function __construct() {
		$config = $this->Get_Configuration();
		if(empty($config))
		{
			$this->NeeoID = 0;
		}
		else{
			if ( is_array( $config) ) {

				end( $config );
				$last_id = key( $config );
				var_dump($last_id);
				$this->NeeoID = $last_id + 1;
			}
		}
		$this->Device_Configuration[$this->NeeoID]["id"] = $this->NeeoID;
		$this->Device_Configuration[$this->NeeoID]["manufacturer"] = "NEEO";
		$this->Device_Configuration[$this->NeeoID]["type"] = "ACCESSOIRE";
	}

	private function Set_Configuration()
	{
		$config_json = json_encode($this->Device_Configuration);
		SetValue(IPS_GetObjectIDByIdent("NEEO_Config", 33521 /*[Geräte\NEEO\NEEO SRS]*/), $config_json);
	}

	private function Get_Configuration()
	{
		$config_json = GetValue(IPS_GetObjectIDByIdent("NEEO_Config", 33521 /*[Geräte\NEEO\NEEO SRS]*/));
		$config = json_decode($config_json, true);
		$this->Device_Configuration = $config;
		return $config;
	}

	private function Get_Last_NEEOID()
	{
		$config = $this->Get_Configuration();
		// last NEEOID
		end($config);
		$NEEOID = key($config);
		return $NEEOID;
	}


	private function Get_CapabilityID()
	{
		$config = $this->Get_Configuration();
		if (array_key_exists("capabilities", $config[$this->Get_Last_NEEOID()]))
		{
			// last capability
			$capabilities = $config[$this->Get_Last_NEEOID()]["capabilities"];
			end($capabilities);
			$capabilities_id = key($capabilities);
			$capabilities_id = $capabilities_id +1;
		}
		else
		{
			$capabilities_id = 0;
		}
		return $capabilities_id;
	}

	/** setAdapterName
	 * Adaptername consists of name + objectid
	 * * @param string $adapterName
	 */
	public function setAdapterName(string $adapterName)
	{
		$this->Device_Configuration[$this->NeeoID]["adapterName"] = $adapterName;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** setManufacturer
	 * Optional parameter to set the device manufacturer. Default manufacturer is NEEO
	 * used to find and add the device in the NEEO app.
	 * * @param string $manufacturerName
	 */
	public function setManufacturer(string $manufacturerName)
	{
		$this->Device_Configuration[$this->NeeoID]["manufacturer"] = $manufacturerName;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** setType
	 * Optional parameter to define the device type. Default type is ACCESSOIRE.
	 * It is used to determine the display style and wiring suggestions in the NEEO app.
	 * Please note, ACCESSOIRE devices do not generate a view but can be used in other views as shortcut.
	 * supported device classes, either 'ACCESSORY', 'AVRECEIVER', 'DVB' (aka. satellite receiver), 'DVD' (aka. disc player),
	 * 'GAMECONSOLE', 'LIGHT', 'MEDIAPLAYER', 'PROJECTOR', 'TV' or 'VOD' (aka. Video-On-Demand box like Apple TV, Fire TV...)
	 * * @param string $type
	 */
	public function setType(string $type)
	{
		$this->Device_Configuration[$this->NeeoID]["type"] = $type;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}


	/** setIcon
	 * Optional parameter to define the device icon. The default icon is defined according to the device type if no custom icon is set.
	 * string identifying the icon, the following icons are currently available: 'sonos'
	 * @param string $icon
	 */
	public function setIcon(string $icon)
	{
		$this->Device_Configuration[$this->NeeoID]["icon"] = $icon;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}



	/** setSpecificName
	 * Optional name to use when adding the device to a room (a name based on the type will be used by default, for example: 'Accessory'). Note this does not apply to devices using discovery.
	 * @param string $specificName
	 */
	public function setSpecificName(string $specificName)
	{
		$this->Device_Configuration[$this->NeeoID]["name"] = $specificName;
		$this->Device_Configuration[$this->NeeoID]["device"]["name"] = $specificName;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** addAdditionalSearchToken
	 * Optional parameter define additional search tokens the user can enter in the NEEO App "Add Device" section.
	 * @param string $token
	 */
	public function addAdditionalSearchToken(string $token)
	{
		$this->Device_Configuration[$this->NeeoID]["tokens"] = $token;
		$this->Device_Configuration[$this->NeeoID]["device"]["tokens"] = explode(" ", $this->Device_Configuration[$this->NeeoID]["tokens"]);
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}


	/** Register a discovery function for your device. This function can be only defined once per device definition.
	 * @param $messages
	 * @param $controller
	 */
	protected function enableDiscovery($messages, $controller)
	{

	}

	/** Enable a registration or pairing step before discovery your device, for example if the device you want support needs to a pairing code to work.
	 * This function can be only defined once per device definition. enableRegistration can only be used when enableDiscovery is also used - for the user registration
	 * takes place before discovery NOTE: This function is experimental and should not be used in production. It is subject to change, currently account login is not
	 * supported the data is transfer is not yet encrypted!
	 * @param $options
	 * @param $controller
	 */
	protected function enableRegistration($options, $controller)
	{

	}

	/** This function allows you to check if the current device type supports timing related information.
	 *
	 */
	public function supportsTiming()
	{

	}

	/** This function allows you to define timing related information, which will be used to generate the recipe.
	 * int powerOnDelayMs	how long does it take (in ms) until the device is powered on and is ready to accept new commands
	 * int sourceSwitchDelayMs	how long does it take (in ms) until the device switched input and is ready to accept new commands
	 * int shutdownDelayMs	how long does it take (in ms) until the device is powered off and is ready to accept new commands
	 * { powerOnDelayMs: 2000, sourceSwitchDelayMs: 500, shutdownDelayMs: 1000 }
	 * @param string $configuration
	 */
	protected function defineTiming(string $configuration)
	{

	}

	/** This is used for devices which need to send dynamic value updates (for example switches or sliders state) to the Brain they are registered on.
	 * When the device is added to a Brain the SDK will call the controller function with an update function as argument (aka. inject the function).
	 * This function can be used to then send updates to the Brain when the value of the device updates. For example a device with a physical slider
	 * can use this to keep the digital slider in sync. This function can be only defined once per device definition.
	 * @param $controller
	 */
	protected function registerSubscriptionFunction($controller)
	{

	}

	public function registerInitialiseFunction()
	{

	}

	public function registerDeviceSubscriptionHandler()
	{

	}

	/** Add a button for this device, can be called multiple times for multiple buttons.
	 * addButton can be combined with addButtonGroups. You need to be call the addButtonHandler function.
	 * IMPORTANT: If your device supports a discrete "Power On" and "Power Off" command, name the macros like in the example below.
	 * addButton('POWER ON', 'Power On' )	addButton('POWER OFF', 'Power Off')
	 * In that case the NEEO Brain automatically recognise this feature and those commands to in the prebuild Recipes.
	 * @param string $name
	 * @param string $label
	 * @return array
	 */
	public function addButton(string $name, string $label)
	{
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "button";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Add multiple buttons defined by the button group name.
	 * The UI elements on the NEEO Brain are build automatically depending on the existing buttons of a device.
	 * You can add multiple ButtonGroups to a device and you can combine ButtonGroups with addButton calls.
	 * You need to be call the addButtonHandler function.
	 * @param string $name
	 * @return array
	 */
	public function addButtonGroup(string $name)
	{
		$i = $this->Get_CapabilityID();
		// todo add correct type
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "button";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Handles the events for all the registered buttons.
	 * This function can be only defined once per device definition and MUST be defined if you have added at least one button.
	 * @param string $controller
	 * @return array
	 */
	public function addButtonHandler(string $controller)
	{
		$i = 0;
		// todo add controller
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $controller;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Add a (range) slider to your custom device
	 * @param string $name identifier of this element
	 * @param string $label optional, visible label in the mobile app or on the NEEO Remote
	 * @param string $range optional, custom range of slider, default 0..100
	 * @param string $unit optional, user readable label, default %
	 * @param string $getter function return the current slider value
	 * @param string $action function update the current slider value
	 * @return array
	 */
	public function addSlider(string $name, string $label, string $range, string $unit, string $getter, string $action)
	{
		if(empty($range))
		{
			$range = "[0, 100]";
		}
		if(empty($unit))
		{
			$unit = "%";
		}
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "slider";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["type"] = "range";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["range"] = json_decode($range, true);;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["unit"] = $unit;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["sensor"] = $action;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $getter;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Add a range/binary sensor to your custom device
	 * @param string $name Identifier of this element
	 * @param string $label Optional, visible label in the mobile app or on the NEEO Remote
	 * @param string $type Type of sensor, the available types are binary, range, power (should be done using addPowerStateSensor), string, array
	 * @param string $range Optional, custom range of sensor, default 0..100
	 * @param string $unit Optional, user readable label, default %
	 * @param string $getter A Function that returns the current sensor value
	 * @return array
	 */
	public function addSensor(string $name, string $label, String $type, string $range, string $unit, string $getter)
	{
		if(empty($range))
		{
			$range = "[0, 100]";
		}
		if(empty($unit))
		{
			$unit = "%";
		}
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "slider";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["type"] = $type;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["range"] = json_decode($range, true);;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["slider"]["unit"] = $unit;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $getter;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Add a power sensor to your custom device, so the NEEO Brain knows when this device is powered on or off.
	 * See registerSubscriptionFunction how the controller can send powerOn and powerOff notifications to the Brain.
	 * @param string $getter
	 * @return array
	 */
	public function addPowerStateSensor(string $getter)
	{
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $getter;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Add a (binary) switch to your custom element
	 * @param string $name identifier of this element
	 * @param string $label optional, visible label in the mobile app or on the NEEO Remote
	 * @param string $setter update current value of the Switch
	 * @param string $getter return current value of the Switch
	 * @return array
	 */
	public function addSwitch(string $name, string $label, string $setter, string $getter)
	{
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "switch";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $getter;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Add a text label to your custom element (for example to display the current artist)
	 * @param string $name
	 * @param string $label
	 * @param string $getter
	 * @return array
	 */
	public function addTextLabel(string $name, string $label, string $getter)
	{
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "textlabel";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $getter;
		$this->Set_Configuration();
		return $this->Device_Configuration;
		// $DevConfigAR[$NeeoID]["capabilities"][11]["sensor"] = "CHANNELACTUALTEXTLABEL_SENSOR";
	}

	/** Add an image to your custom element (for example to display the album cover of the current track)
	 * @param string $name identifier of this element. etc. ChannelIcon
	 * @param string $label optional, visible label in the mobile app or on the NEEO Remote. etc. Channellogo
	 * @param string $uri HTTP URI pointing to an image resource. JPG and PNG images are supported. etc. http://www.buildup.eu/sites/default/files/pictures/picture-1-1423845685.png
	 * @param string $size image size in the ui, either 'small' or 'large'. The small image has the size of a button while the large image is a square image using full width of the client.
	 * @param string $controller returns the address (URL) to the current image. etc. "/device/enigma2-".$InstanceID."/ChannelIcon"
	 * @return array
	 */
	public function addImageUrl(string $name, string $label, string $uri, string $size, string $controller)
	{
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "imageurl";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["imageUri"] = $uri;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["size"] = $size;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $controller;
		$this->Set_Configuration();
		return $this->Device_Configuration;

		/*
		$DevConfigAR[$NeeoID]["capabilities"][13]["sensor"] = "CHANNELICON_SENSOR";
		*/
	}

	/** Define additional device directories which can be browsed on the device
	 * @param string $name identifier of this element.
	 * @param string $label optional, visible label in the mobile app or on the NEEO Remote.
	 * @param bool $isQueue optional, name of the directory to be used for the queue - mediaplayer only
	 * @param bool $isRoot optional, name of the directory that will be the 'root' level of your list
	 * @param string $getter should return a list built by listBuilder so the App/NEEO Remote can display the browse result as a list. If the getter callback encounters an error, you can build a list with a 'ListInfoItem' to inform the user about the error
	 * @param string $action will be called when an item is clicked
	 * @return array
	 */
	public function addDirectory(string $name, string $label, bool $isQueue, bool $isRoot, string $getter, string $action)
	{
		$i = $this->Get_CapabilityID();
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["type"] = "directory";
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["name"] = $name;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["label"] = $label;
		$this->Device_Configuration[$this->NeeoID]["capabilities"][$i]["path"] = $getter;
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Define queue directory which can be browsed on the device
	 * @param string $name identifier of this element.
	 * @param string $label optional, visible label in the mobile app or on the NEEO Remote.
	 * @param string $getter should return a list built by listBuilder so the App/NEEO Remote can display the browse result as a list.
	 * If the getter callback encounters an error, you can build a list with a 'ListInfoItem' to inform the user about the error.
	 * the getter function is called with (deviceId, params) parameter. params contains information about the current list and contains those fields:
	 * params.browseIdentifier: the browseIdentifier you defined for an entry, empty to fetch the root directory
	 * params.limit: maximal page size
	 * params.offset: offset position is list to show the next page of lists
	 * @param string $action will be called when an item is clicked
	 * @return array
	 */
	public function addQueueDirectory(string $name, string $label, string $getter, string $action)
	{
		// todo getter
		// call_user_func($classname .'::say_hello'); // Seit 5.2.3
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/** Define root directory which can be browsed on the device
	 * @param string $name identifier of this element.
	 * @param string $label optional, visible label in the mobile app or on the NEEO Remote.
	 * @param string $getter should return a list built by listBuilder so the App/NEEO Remote can display the browse result as a list.
	 * If the getter callback encounters an error, you can build a list with a 'ListInfoItem' to inform the user about the error.
	 * the getter function is called with (deviceId, params) parameter. params contains information about the current list and contains those fields:
	 * params.browseIdentifier: the browseIdentifier you defined for an entry, empty to fetch the root directory
	 * params.limit: maximal page size
	 * params.offset: offset position is list to show the next page of lists
	 * @param string $action will be called when an item is clicked
	 * @return array
	 */
	public function addRootDirectory(string $name, string $label, string $getter, string $action)
	{
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}

	/**Define additional device capabilities, currently supported capabilities (case sensitive):
	 * - "alwaysOn" – the device does not need to be powered on to be useable. You don't need to specify 'POWER ON' and 'POWER OFF' buttons and the device is not identified as "Not so smart device"
	 * "bridgeDevice" – This capability is used after you add a new device, then you have the option to select "Add more from this bridge". For example Philips Hue - the discovered device (Gateway) supports multiple devices (Lamps).
	 * "addAnotherDevice" - This capability is used after you add a new device that uses discovery. It gives the option to select "Add another ${device name}"
	 * @param $capability
	 * @return array
	 */
	public function addCapability($capability)
	{
		$this->Set_Configuration();
		return $this->Device_Configuration;
	}




	// implementationservices devicestate

	public function addDevice()
	{

	}

	public function registerStateUpdate()
	{

	}

	public function getAllDevices()
	{

	}

	public function getClientObjectIfReachable()
	{

	}

	public function getCachePromise()
	{

	}

	public function updateReachable()
	{

	}

	public function buildInstance()
	{

	}







// device list listbuilder

	public function addListItem()
	{

	}
	public function addListHeader()
	{

	}
	public function addListTiles()
	{

	}
	public function addListInfoItem()
	{

	}
	public function addListButtons()
	{

	}






// index

	public function discoverOneBrain()
	{

	}
	public function getRecipes()
	{

	}
	public function getRecipesPowerState()
	{

	}
	public function buildDevice()
	{

	}
	public function buildDeviceState()
	{

	}
	public function buildBrowseList()
	{

	}
	public function startServer()
	{

	}

	public function stopServer()
	{

	}



	/*
	 * Mediacontrol button names

PLAY
PAUSE

STOP

SKIP BACKWARD

SKIP FORWARD

FORWARD

PREVIOUS

NEXT

REVERSE

PLAY PAUSE TOGGLE

INFO



Color button names

FUNCTION BLUE
FUNCTION GREEN
FUNCTION ORANGE
FUNCTION RED
FUNCTION YELLOW


Digit button names

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

DIGIT SEPARATOR

DIGIT ENTER



Direction button names

CURSOR DOWN

CURSOR LEFT

CURSOR RIGHT

CURSOR UP

CURSOR ENTER



Additional buttons

ENTER

EXIT

HOME

GUIDE



Menu buttons

MENU
BACK


Power button names

POWER OFF
POWER ON

POWER TOGGLE



Tuner button names

CHANNEL UP
CHANNEL DOWN



Format button names

FORMAT 16:9
FORMAT 4:3

FORMAT AUTO

FORMAT SCROLL



Volume button names

VOLUME UP
VOLUME DOWN

MUTE TOGGLE



Input button names

INPUT 1
INPUT 2
INPUT AM
INPUT AUX 1
INPUT HDMI 1


All button names i could find.
These are not necessarily known as a 'special' button by the remote. these are just those that i could find in my API/configuration

#
*
3D
ALT-F4
AUDIO
BACK
CANCEL
CAP LOCK
CHANNEL DOWN
CHANNEL UP
CLEAR
CLEAR QUEUE
CLR
CURSOR DOWN
CURSOR ENTER
CURSOR LEFT
CURSOR RIGHT
CURSOR UP
DELETE
DIGIT 0
DIGIT 1
DIGIT 10
DIGIT 10+
DIGIT 11
DIGIT 12
DIGIT 2
DIGIT 3
DIGIT 4
DIGIT 5
DIGIT 6
DIGIT 7
DIGIT 8
DIGIT 9
DIGIT ENTER
DIGIT SEPARATOR
DIMMER
DIRECT TUNE
DISPLAY
DVD ANGLE
DVD AUDIO
E MANUAL
EXIT
FORMAT 16:9
FORMAT 4:3
FORMAT AUTO
FORMAT SCROLL
FORWARD
FUNCTION BLUE
FUNCTION GREEN
FUNCTION ORANGE
FUNCTION RED
FUNCTION YELLOW
GUIDE
HOME
INFO
INPUT 1
INPUT 10
INPUT 11
INPUT 12
INPUT 13
INPUT 1394
INPUT 14
INPUT 15
INPUT 16
INPUT 17
INPUT 18
INPUT 19
INPUT 2
INPUT 20
INPUT 21
INPUT 22
INPUT 23
INPUT 3
INPUT 4
INPUT 5
INPUT 6
INPUT 7
INPUT 8
INPUT 9
INPUT AM
INPUT AUX 1
INPUT BD/DVD
INPUT BLUETOOTH
INPUT CABLE/SATELLITE
INPUT COMPONENT 1
INPUT COMPONENT 2
INPUT COMPOSITE 1
INPUT COMPOSITE 2
INPUT DVI 1
INPUT FM
INPUT GAME
INPUT HDMI 1
INPUT HDMI 2
INPUT HDMI 3
INPUT HDMI 4
INPUT NET
INPUT PC
INPUT PHONO
INPUT S VIDEO 1
INPUT SCART 1
INPUT SCROLL
INPUT STREAM BOX
INPUT TUNER 1
INPUT TV
INPUT TV/CD
INPUT USB 1
INPUT VGA 1
INPUT VGA 2
KIOSK
LANGUAGE
LIVE TV
MENU
MENU DISC
MENU DVD
MENU MAIN
MENU POP UP
MENU SMART HOME
MENU TOP
MESSENGER
MODE
MODE GAME 1
MODE MOVIE/TV
MODE MUSIC
MODE STEREO
MOUSE
MUTE TOGGLE
MUTE UNMUTE
MY APPS
MY DISTRIBUTED AUDIO
MY HOME
MY LIGHTS
MY MUSIC
MY PICTURES
MY SECURITY
MY THERMOSAT
MY TV
MY VIDEOS
NEXT
NEXT TRACK
OEM1
OEM2
ONLINE SPOTLIGHT
ONLINE SPOTLIGHT PARTNER SPECIFIC APP
OPEN/CLOSE
OPTIONS
OUTPUT RESOLUTION
PAUSE
PLAY
PLAY PAUSE
PLAY PAUSE TOGGLE
POWER OFF
POWER ON
POWER TOGGLE
POWER_ALL_OFF
POWER_OFF
POWER_ON
PRESET DOWN
PRESET UP
PREVIOUS
PREVIOUS CHANNEL
PREVIOUS TRACK
PRINT
RADIO
RANDOM
REBOOT
RECORD
RECORDED TV
REPEAT
REPEAT TOGGLE
REPLAY 10 SEC
REVERSE
SEARCH
SHUFFLE TOGGLE
SKIP BACKWARD
SKIP FORWARD
SLEEP
SMART HUB
SPEAKER A
SPEAKER B
STOP
SUBTITLE
TELETEXT
TITLE
TONE
TONE +
TONE -
TOOLS
TUNING DOWN
TUNING UP
UN PAIR
VOLUME DOWN
VOLUME UP
WINDOWS
WRITE
	 */

	// helper


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