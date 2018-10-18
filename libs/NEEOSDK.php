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

	/** setManufacturer
	 * Optional parameter to set the device manufacturer. Default manufacturer is NEEO
	 * used to find and add the device in the NEEO app.
	 * * @param string $manufacturerName
	 */
	public function setManufacturer(string $manufacturerName)
	{

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

	}


	/** setIcon
	 * Optional parameter to define the device icon. The default icon is defined according to the device type if no custom icon is set.
	 * string identifying the icon, the following icons are currently available: 'sonos'
	 * @param string $icon
	 */
	public function setIcon(string $icon)
	{

	}



	/** setSpecificName
	 * Optional name to use when adding the device to a room (a name based on the type will be used by default, for example: 'Accessory'). Note this does not apply to devices using discovery.
	 * @param string $specificName
	 */
	public function setSpecificName(string $specificName)
	{

	}

	/** addAdditionalSearchToken
	 * Optional parameter define additional search tokens the user can enter in the NEEO App "Add Device" section.
	 * @param string $token
	 */
	public function addAdditionalSearchToken(string $token)
	{

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

	public function addButton()
	{

	}

	public function addButtonGroup()
	{

	}

	public function addButtonHandler()
	{

	}

	public function addSlider()
	{

	}

	public function addSensor()
	{

	}

	public function addPowerStateSensor()
	{

	}

	public function addSwitch()
	{

	}

	public function addTextLabel()
	{

	}

	public function addImageUrl()
	{

	}

	public function addDirectory()
	{

	}

	public function addCapability()
	{

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
	 * The following device types are supported by the SDK, They are all in UPPERCASE.

ACCESSOIRE

AVRECEIVER

DVB

DVD

GAMECONSOLE

LIGHT

MEDIAPLAYER

PROJECTOR

TV

VOD
	 */

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

}