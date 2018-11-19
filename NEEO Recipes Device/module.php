<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';

// Module for NEEO

class NEEORecipeDevice extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	// helper properties
	private $position = 0;

	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->ConnectParent("{A938EE1A-519B-4BAB-AEB1-EFC1B2B15A91}");

		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
		$this->RegisterPropertyString("device_name", "Recipes");
		$this->RegisterPropertyString("recipes", "");
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		// $this->SetReceiveDataFilter('.*' . $this->ReadPropertyString('device_name') . '.*');

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
		$recipes = $this->ReadPropertyString("recipes");
		if ($recipes != "") {
			$this->SendDebug('NEEO Recipes:', "register variables", 0);
			$this->SetupVariables();
		} else {
			$this->SendDebug('NEEO Recipes:', "unregister variables", 0);
			//$this->UnregisterVariable('WEBUI');
			//$this->SetStatus(202);
		}

	}

	public function SetupVariables()
	{
		$recipes = $this->ReadPropertyString("recipes");
		$recipes = json_decode($recipes, true);
		foreach ($recipes as $recipe) {
			$detail = $recipe["detail"];
			$roomname = urldecode($detail["roomname"]);
			$devicename = urldecode($detail["devicename"]);
			$uid = $recipe["uid"];
			$recipe_ident = $this->CreateIdent($uid);
			$objectid = $this->RegisterVariableBoolean('LaunchRecipe_' . $recipe_ident, $devicename, '~Switch', $this->_getPosition());
			$this->SendDebug("NEEO Device", "variable recipe object id : " . $objectid .  " room (" .$roomname. "), device (" .$devicename. ")", 0);
			$this->EnableAction('LaunchRecipe_' . $recipe_ident);
		}
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

		$this->WriteValues($action, $actionparameter, $device, $recipe);
	}

	protected function WriteValues($action, $actionparameter = NULL, $device = NULL, $recipe = NULL)
	{
		$recipes = $this->ReadPropertyString("recipes");
		if ($recipes != "") {

			$uid = $this->GetRecipeUID($recipe);
			$recipe_ident = $this->CreateIdent($uid);

			if ($action == "launch") {
				$this->SetValue("LaunchRecipe_" . $recipe_ident, true);
				$this->SendDebug("NEEO Recieve:", "Recipe " . $recipe . " started", 0);
			}
			if ($action == "poweroff") {
				$this->SetValue("LaunchRecipe_" . $recipe_ident, false);
				$this->SendDebug("NEEO Recieve:", "Recipe " . $recipe . " stopped", 0);
			}
		}
	}

	private function GetRecipeUID($recipe_name)
	{
		$uid = false;
		$recipes = $this->ReadPropertyString("recipes");
		$recipes = json_encode($recipes);
		foreach ($recipes as $recipe) {
			$detail = $recipe->detail;
			//$roomname = urldecode($detail->roomname);
			$devicename = urldecode($detail->devicename);
			if ($recipe_name == $devicename)
				$uid = $recipe["uid"];
		}
		return $uid;
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