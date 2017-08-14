<?
$srv=null;
function SetValueOn($sId,$vsId,$val) {
	global $srv;
	if ($sId==0) {
		$sId=GetValue(31806);
	}
	if ($srv==null) {
		$srv=new RemoteServer($sId);
	}
	$rc=$srv->SetValue($vsId,$val);
	return "$rc $sId,$vsId";
}
function GetValueOn($sId,$vsId) {
	global $srv;
	if ($sId==0) {
		$sId=GetValue(31806);
	}
	if ($srv==null) {
		$srv=new RemoteServer($sId);
	}
	$val=$srv->GetRemoteValue($vsId);
	return $val;
}

function RPC_setEventActive($sId,$osId,$val) {
	global $srv;
	if ($sId==0) {
		$sId=GetValue(31806);
	}
	if ($srv==null) {
		$srv=new RemoteServer($sId);
	}
	$rc=$srv->IPS_SetEventActive($osId,$val);
	return $rc;
}
function openRpc($sId){
	$srv=new RemoteServer($sId);
	return $srv->openRpc();
}
function setAliveTo($sId){
	$srv=new RemoteServer($sId);
// 	print_r($srv);
	return $srv->setMyselfAlive();
}
function setDeadTo($sId){
	$srv=new RemoteServer($sId);
	return $srv->setMyselfDead();
}
function setServerMaster($sId){
	$srv=new RemoteServer($sId);
	return $srv->setMaster();
}
function starteOn($sId,$name) {
	$srv=new RemoteServer($sId);
	$srv->starteTask($name);
}

class RemoteServer extends IPSModule {
	protected $idSelf;
	protected $serverName;
	protected $serverIp;
	protected $os;
	protected $rpcPort;
	protected $idAliveFlag;
	protected $mediaplayer;
	protected $myIdRemote;
	protected $rpc;
	
	include_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "BonoxModulesTools.php");	
	
	public function __construct($InstanceID)
	{
		//Never delete this line!
		parent::__construct($InstanceID);
		$this->idSelf=$InstanceID;
		$this->rpc=$this->openRpc();
	}
	
	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->RegisterPropertyInteger (  "WatchdogInterval", 60 );
		$this->RegisterPropertyInteger (  "MIoRS", 0 );
		$this->RegisterPropertyBoolean (  "Wartung", false );
		$this->RegisterPropertyString (  "Username", "nowack@bit-nowack.de" );
		$this->RegisterPropertyString (  "Password", "ycgpa" );
		$this->RegisterTimer("Timeout", 120*1000, "RIS_testAlive();");
		
	}
	
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterVariableString("Name", "Name","",0);
		$this->RegisterVariableString("Betriebssystem", "Betriebssystem","",1);
		$this->RegisterVariableString("IP", "IP","",2);
		$this->RegisterVariableBoolean("Alive_Flag", "Alive_Flag","Alive",3);
		$this->RegisterVariableBoolean("Stoerung", "Störung","Alarm",4);
		$this->RegisterVariableInteger("Startzeit", "Startzeit","~UnixTimestamp",5);
		$this->RegisterVariableInteger("Laufzeit", "Laufzeit","Stunden",6);
		
		$iVal=$this->ReadPropertyInteger("MIoRS")+30;
		$this->SetTimerInterval("Timeout", $iVal*1000);
		
		
	
	}
	
	/**
	 * This function will be available automatically after the module is imported with the module control.
	 * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
	 *
	 * UWZ_RequestInfo($id);
	 *
	 */
// 	public function __construct($sId) {
// 		parent::__construct();
// // 		$this->setDebug(true);
// 		$this->idSelf=$sId;
// 		$this->serverIp=$this->getVarValue('IP');
// 		$this->serverName=$this->getVarValue('Name');
// 		$this->os=$this->getVarValue('Betriebssystem');
// 		$this->idAliveFlag=$this->getObjId("Alive_Flag");
// 		if (!$this->idAliveFlag) {
// 			$this->idAliveFlag=$this->getObjId("Alive Flag");
// 		}
// 		switch ($this->getOS()) {
// 			case "Windows":
// 				$this->rpcPort=3777;
// 				break;
// 			case "RPI":
// 				$this->rpcPort=3777;
// 				break;
// 			case "Android":
// 				$this->rpcPort=3777;
// 				break;
// 		}
// 		if ($id=$this->getObjId('MediaplayerProxy') and class_exists ('MediaplayerProxy' ,$autoload = false )) {
// 			$className="MediaplayerProxy$this->os";
// 			$this->mediaplayer=new $className($this,$id);
// 		}
// 		if ($id=$this->getObjId('My_Id_on_Remote_System')) {
// 			$this->myIdRemote=GetValue($id);
// 		}
// 		$this->debug($this);
// 	}

	public function getIP() {
		return $this->serverIp;
	}

	public function getName() {
		return $this->serverName;
	}

	public function getOS() {
		return $this->os;
	}

	public function isActive() {
		return $this->GetValue(GetIDForIdent('Alive_Flag'));
	}

	public function openRpc($noTest=false) {
		if ($noTest or $this->isActive()) {
			$ip=$this->GetValue(GetIDForIdent("IP"));
			$username=$this->ReadPropertyString("Username");
			$pswd=$this->ReadPropertyString("Password");
			$rpc=new JSONRPC("$username:$pswd@$ip:3777/api/");
		} else {
			$rpc=false;
		}
		return $rpc;
	}
	public function getMediaplayer() {
		return $this->mediaplayer;
	}
	public function testAlive() {
		if (!isset($_IPS)) global $_IPS;
		$sender=@$_IPS['SENDER'];
// 		print_r ("Sender=".$sender."\n");
		if ($sender=='Variable' or $sender=='Execute') {
			$flg=$this->getVarValueByIdent('Alive_Flag');
			if ($flg) {
				$delay=$this->getVarValueByIdent('Messinterval')+60;
				IPS_SetScriptTimer($_IPS['SELF'],$delay);
				$stoerung=$this->getVarValueByIdent("Stoerung");
				if ($stoerung<>NULL) {
					$this->setVarValue("Stoerung", false);
				}
				$rc=true;
			} else {
				IPS_SetScriptTimer($_IPS['SELF'],0);
				$rc=false;
			}
		} else {
			$this->setVarValue('Alive Flag', false);
//			IPS_SetScriptTimer($_IPS['SELF'],0);
			$this->SetTimerInterval("Timeout", 0);
			$wartung=$this->getVarValueByIdent("Wartung");
			if (!$wartung) {
				$idStoerung=$this->getObjId("Stoerung");
				if ($idStoerung<>NULL) {
					$this->setVarValue("Stoerung", true);
				}
			}
			$rc=false;
		}
		return $rc;
	}
	public function SetValue($vsId,$val) {
		try {
			$rpc=$this->openRpc(true);
			$rpc->SetValue($vsId,$val);
			IPS_LogMessage("Remote Server","setze $val auf $vsId");
			return true;
		} catch (Exception $e) {
			IPS_LogMessage("Remote Server","RPC-Fehler\n");
			IPS_LogMessage(Print_r($e->getMessage()." Line ".$e->getLine()." in File ".$e->getFile()."\n"));
			return false;
		}
	}
// 	public function GetValue($vsId) {
// 		try {
// 			$rpc=$this->openRpc(true);
// 			$val=$rpc->GetValue($vsId);
// 			IPS_LogMessage("Remote Server","hole $val von $vsId");
// 			return $val;
// 		} catch (Exception $e) {
// 			IPS_LogMessage("Remote Server","RPC-Fehler\n");
// 			IPS_LogMessage(Print_r($e->getMessage()." Line ".$e->getLine()." in File ".$e->getFile()."\n"));
// 			return NULL;
// 		}
// 	}
	public function GetRemoteValue($vsId) {
		try {
			$rpc=$this->openRpc(true);
			$val=$rpc->GetValue($vsId);
			//IPS_LogMessage("RPC","setze $val auf $vsId");
			return $val;
		} catch (Exception $e) {
// 			IPS_LogMessage("RPC","RPC-Fehler\n");
			IPS_LogMessage("Remote Server",print_r($e->getMessage()." Line ".$e->getLine()." in File ".$e->getFile()."\n"));
			return false;
		}
	}
	function starteTask($name) {
		$msg="DeaktiviereStandby\r\n";
		$msg.="StarteApp $name\r\n";
		$this->sendToHaussteuerung($msg);
	}
	public function setMyselfAlive() {
		try {
			$rpc=$this->openRpc(true);
			$intvalId=$rpc->IPS_GetVariableIDByName("Messinterval",$this->myIdRemote);
			$intval=$this->getVarValue('Messinterval');
			$rpc->SetValue($intvalId, $intval);

			$aliveId=$rpc->IPS_GetVariableIDByName("Alive Flag",$this->myIdRemote);
			$rpc->SetValue($aliveId, true);

			$ipId=$rpc->IPS_GetVariableIDByName("IP",$this->myIdRemote);
			$myIP=GetValue(IPS_GetVariableIDByName("IP",0));
			$rpc->SetValue($ipId, $myIP);

			$nameId=$rpc->IPS_GetVariableIDByName("Name",$this->myIdRemote);
			$name=GetValue(IPS_GetVariableIDByName("Servername",0));
			$rpc->SetValue($nameId, $name);

			$osId=$rpc->IPS_GetVariableIDByName("Betriebssystem",$this->myIdRemote);
			$os=GetValue(IPS_GetVariableIDByName("Betriebssystem",0));
			$rpc->SetValue($osId, $os);

			$osId=$rpc->IPS_GetVariableIDByName("Startzeit",$this->myIdRemote);
			if ($osId) {
				$os=GetValue(IPS_GetVariableIDByName("Startzeit",0));
				$rpc->SetValue($osId, $os);
			}

			IPS_SetScriptTimer($_IPS['SELF'],$intval);
			return true;
		} catch (Exception $e) {
			print_r("RPC-Fehler\n");
			Print_r($e->getMessage()." Line ".$e->getLine()." in File ".$e->getFile()."\n");
// 			print_r($e->getTrace());
			return false;
		}
	}

	public function setMyselfDead() {
		try {
			$rpc=$this->openRpc(true);

			$aliveId=$rpc->IPS_GetVariableIDByName("Alive Flag",$this->myIdRemote);
			$rpc->SetValue($aliveId, false);
			$parentId=IPS_getParent($_IPS['SELF']);
			$aliveScripdId=IPS_GetObjectIDByName("setAlive",$parentId);
			IPS_SetScriptTimer($aliveScripdId,0);
			return true;
		} catch (Exception $e) {
			print_r("RPC-Fehler\n");
			Print_r($e->getMessage()." Line ".$e->getLine()." in File ".$e->getFile()."\n");
			// 			print_r($e->getTrace());
			return false;
		}
	}
// 	Nur für Android


	private function sendToHaussteuerung($msg) {
		$in=$msg;
		//ab hier jetzt nichts mehr verändern!!!
		if (!$this->isActive()) {
			Echo "Android-Server ist nicht erreichbar";
			return;
		}

		$address = $this->getIP();
		$service_port = 2001;

		$in .= "\r\n";

		$in = mb_convert_encoding($in, "UTF-8");
		error_reporting(E_ALL);

		//echo "<h2>TCP/IP-Verbindung</h2>\n";

		/* Einen TCP/IP-Socket erzeugen. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			echo "socket_create() fehlgeschlagen: Grund: " . socket_strerror(socket_last_error()) . "\n";
			return;
		} else {
			//    echo "OK.\n";
		}

		//echo "Versuche, zu '$address' auf Port '$service_port' zu verbinden ...";
		$result = socket_connect($socket, $address, $service_port);
		if ($result === false) {
			echo "Versuche, zu '$address' auf Port '$service_port' zu verbinden ...";
			echo "socket_connect() fehlgeschlagen.\nGrund: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
			socket_close($socket);
			return;
		} else {
		//   echo "OK.\n";
		}

		$out = '';

		//echo "HTTP request senden ...";
		socket_write($socket, $in, strlen($in));
		//echo "OK.\n";

		//echo "Serverantwort lesen:\n";
		$loop=0;
		while ($out = socket_read($socket, 2048) and $loop < 10) {
		echo $out;
		$loop++;
		}

		//echo "\nSocket schließen ...";
		socket_close($socket);
		//echo "OK.\n\n";
	}
}

?>
