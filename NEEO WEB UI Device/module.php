<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';

// Module for NEEO

class NEEOWebUIDevice extends IPSModule
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
		$this->RegisterPropertyString("device_name", "WEBUI");
		$this->RegisterPropertyString("Host", "");
		$this->RegisterPropertyString("webui", ":3200/eui");
		$this->RegisterPropertyInteger("height", 500);
		$this->RegisterPropertyInteger("width", 600);
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
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
		$host = $this->ReadPropertyString("Host");
		$webui = $this->ReadPropertyString("webui");
		$height = $this->ReadPropertyInteger("height");
		$width = $this->ReadPropertyInteger("width");
		if ($host != "") {
			$this->RegisterVariableString('WEBUI', $this->Translate('NEEO Web UI'), '~HTMLBox', $this->_getPosition());
			$this->SetValue("WEBUI", "<iframe src='http://".$host.$webui."' height=".$height."px width=".$width."px>");
			$this->SetStatus(102);
		} else {
			$this->UnregisterVariable('WEBUI');
			$this->SetStatus(202);
		}

	}


	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		//$this->SendDebug("NEEO Recieve:", $data, 0);
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