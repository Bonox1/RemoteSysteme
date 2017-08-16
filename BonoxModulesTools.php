<?
// class BonoxModulesTools extends IPSModule {
// 	protected $debugOn;

// 	public function __construct($InstanceID)
// 	{
// 		//Never delete this line!
// 		parent::__construct($InstanceID);
// 	}

	protected function getIdSelf() {
		return $this->idSelf;
	}
// 	protected function getVarIdUnderOLd($Name,$pId) {
// 		$arr=IPS_GetChildrenIDs($pId);
// 		foreach ($arr as $id) {
// 				if (IPS_GetName($id)==$Name) {
// 					return IPS_GetObjectIDByName($Name, $pId);
// 				}
// 		}
// 		return NULL;
// 	}

	protected function changeNameToIdent($name) {
		$newName=str_replace(Array(" ", "/",   "-",".",":","ö", "ü", "ä", "(",")",",","Ü", "Ö", "Ä", "ß" ,"*",""  ,"+","%"),
				Array("_", "Pro", "", "", "_","oe","ue","ae","_","_","_","Ue","Oe","Ue","ss","x","EUR","Plus","Proz"),$name);
		$newName=str_replace(Array("__", ),Array("_"),$newName);
		return $newName;
	}
	protected function GetValue($pId,$link=true) {
		$obj=IPS_GetObject($pId);
		$t=$obj["ObjectType"];
		if ($t==6 and $link) {
			return GetValue($this->getLinkedId($pId));
			}
		return GetValue($pId);
	}
	protected function getVarIdUnder($Name,$pId,$link=true) {
		$ident=$this->changeNameToIdent($Name);
		$id=$this->getObjIdUnder($ident, $pId, $link);
		if ($id<>NULL) {
			return $id;
		}
		$id=@IPS_GetObjectIDByName($Name, $pId);

		if ($id<>0) {
			print_r("\n ************** Kein Ident: $ident für Name: $Name mit ID: $id \n");
			$obj=IPS_GetObject($id);
			$t=$obj["ObjectType"];
			if ($t==6 and $link) {
				return $this->getLinkedId($id);
			}
			return $id;
		}
		return NULL;
	}
	protected function getObjIdUnder($Name,$pId,$link=true) {
		$id=@IPS_GetObjectIDByIdent($Name, $pId);
		if (!$id) {
			$id=@IPS_GetObjectIDByName($Name, $pId);
		}
		if ($id) {
			$obj=IPS_GetObject($id);
			$t=$obj["ObjectType"];
			if ($t==6 and $link) {
				return $this->getLinkedId($id);
			}
			return $id;
		}
		return NULL;
	}
	protected function getVarIdInTree($pfad,$link=true) {
		return $this->getVarIdInTreeUnder($this->idSelf, $pfad);
	}
	protected function getObjIdInTree($pfad,$link=true) {
		return $this->getObjIdInTreeUnder($this->idSelf, $pfad);
	}
	protected function getVarIdInTreeUnder($pid,$pfad,$link=true) {
		$idRoot=$pid;
		foreach ($pfad as $pName) {
			$idRoot=$this->getVarIdUnder($pName, $idRoot);
			if ($idRoot==NULL) {
				return NULL;
			}
		}
		return $idRoot;
	}
	protected function getObjIdInTreeUnder($pid,$pfad,$link=true) {
		$idRoot=$pid;
		foreach ($pfad as $pName) {
			$idRoot=$this->getObjIdUnder($pName, $idRoot);
			if ($idRoot==NULL) {
				return NULL;
			}
		}
		return $idRoot;
	}
	protected function getVarValueUnder($pName,$pId,$link=true) {
// 		print_r($pName);
		$zid=$this->getVarIdUnder($pName, $pId);
		return GetValue($zid);
	}
	protected function getVarValueByIdentUnder($pName,$pId,$link=true) {
		// 		print_r($pName);
		$zid=$this->getObjIdUnder($pName, $pId);
		return GetValue($zid);
	}

	protected function getVarValueInTree($pfad,$link=true) {
		return $this->getVarValueInTreeUnder($this->idSelf,$pfad);
	}
	protected function getVarValueByIdentInTree($pfad,$link=true) {
		return $this->getVarValueByIdentInTreeUnder($this->idSelf,$pfad);
	}
	protected function getVarValueInTreeUnder($pId,$pfad,$link=true) {
// 		$this->debug(print_r($pfad,true),"Pfad");
		$id=$this->getVarIdInTreeUnder($pId,$pfad);
		if ($id) {
			return GetValue($id);
		}
		return NULL;
	}
	protected function getVarValueByIdentInTreeUnder($pId,$pfad,$link=true) {
		return(GetValue($this->getObjIdInTreeUnder($pId,$pfad)));
	}
	protected function setVarValueInTree($pfad,$val) {
		return $this->setVarValueInTreeUnder($this->idSelf, $pfad, $val);
	}
	protected function setVarValueInTreeUnder($pId,$pfad,$val) {
		$idVar=$this->getVarIdInTreeUnder($pId,$pfad);
// 		print_r("pId=".$pId." pfad=\n");
// 		print_r($pfad);
		SetValue($idVar,$val);
	}
	protected function getVarId($Name,$link=true) {
		return $this->getVarIdUnder($Name, $this->getIdSelf());
	}
	protected function getObjId($Name,$link=true) {
		return $this->getObjIdUnder($Name, $this->getIdSelf());
	}
	protected function getVarValue($Name,$link=true) {
		$id=$this->getVarId($Name);
		if ($id!=NULL) {
			return GetValue($id);
		}
		return NULL;
	}
	protected function getVarValueByIdent($Name,$link=true) {
		$id=$this->getObjId($Name,$link);
		if ($id!=NULL) {
// 			print_r("\nI\n");
			return GetValue($id);
		}
		return NULL;
	}
	protected function setVarValue($Name,$pValue) {
		$this->setVarValueUnder($this->idSelf,$Name, $pValue);
	}
	protected function setVarValueUnder($pId,$Name,$pValue) {
		$id=$this->getVarIdUnder($Name, $pId);
//		echo $Name;
		return SetValue($id,$pValue);
	}
// 	public function getInstValue($pName) {
// 		$idInst=IPS_GetObjectIDByName($pName,$this->idSelf);
// 		if ($idInst<>0) {
// 			$idVal=IPS_GetObjectIDByName("Value", $idInst);
// 			if ($idVal<>0) {
// 				return GetValue($idVal);
// 			} else {
// 				return NULL;
// 			}
// 		} else {
// 			return NULL;
// 		}
// 	}
	protected function getVarChanged($name) {
		$varArr=$this->getVarProperties ($name);
		return $varArr['VariableChanged'];
	}
	protected function getVarUpdated($name) {
		$varArr=$this->getVarProperties ($name);
		return $varArr ['VariableUpdated'];
	}
	protected function getVarUnderUpdated($parent,$name) {
		$varArr=$this->getVarPropertiesUnder ($parent,$name);
		return $varArr ['VariableUpdated'];
	}
	protected function getVarProperties($name) {
		$id=$this->getVarId($name);
		return IPS_GetVariable ($id );
	}
	protected function getVarPropertiesUnder($parent,$name) {
		$id=$this->getVarIdUnder($name, $parent);
		return IPS_GetVariable ($id );
	}
	protected function getEIBValue($Name) {
		$idInst=$this->getVarId($Name);
		if ($idInst<>NULL) {
			$idVal=IPS_GetObjectIDByIdent("Value", $idInst);
			if ($idVal<>0) {
				return GetValue($idVal);
			} else {
				return NULL;
			}
			;
		} else {
			return NULL;
		}
		;
	}
	protected function getEIBValueUnder($Name,$pId,$link=true) {
		$idInst=$this->getVarIdUnder($Name,$pId,$link);
		if ($idInst<>NULL) {
			$idVal=IPS_GetObjectIDByName("Value", $idInst);
			if ($idVal<>0) {
				return GetValue($idVal);
			} else {
				return NULL;
			}
			;
		} else {
			return NULL;
		}
		;
	}
	protected function setEIBScale($Name,$pValue) {
		$idInst=$this->getVarId($Name);
		EIB_Scale($idInst, $pValue);
	}
	protected function setEIBSwitch($Name,$pValue) {
		$idInst=$this->getVarId($Name);
		EIB_Switch($idInst, $pValue);
	}
	protected function isLink($pId) {
		$vObj=IPS_getObject($pId);
		$vTyp=$vObj ["ObjectType"];
		return ($vTyp==6);
		;
	}
	protected function getLinkedId($pLink) {
	 	$temp=IPS_getLink($pLink);
		$vVarId=$temp["TargetID"];
		return $vVarId;

	}
	protected function generateCat($name,$idParent) {
		$id = IPS_CreateCategory();       // Kategorie anlegen
		IPS_SetName($id, "$name"); // Kategorie benennen
		IPS_SetParent($id,$idParent);
		return $id;
	}
	protected function array_last($arr) {
		$anz=count($arr);
		return $arr[$anz-1];
	}
	protected function tagesbeginn($now) {
		$heute=mktime(0,0,0,date('n',$now),date('j',$now),date('Y',$now));
		return $heute;
	}
	public function debug($msg,$name=NULL) {
		if ($this->debugOn) {
// 			print_r(time()." ");
			print_r(date("d.m.Y H:i:s")." ");
			if (!$name==NULL) {
				print_r($name."=");
			}
			print_r($msg);
			print_r("\n");
		}
	}
	public function setDebug($param) {
		$this->debugOn=$param;
	}
	public function getTag($ts) {
		return intval(date("j",$ts));
	}
	public function getMonat($ts) {
		return intval(date("n",$ts));
	}
	public function getDatum($ts) {
		return date("d.m.Y",$ts);
	}
	public function getDatumJMT($ts) {
		return date("Y.m.d",$ts);
	}
	public function getDatumZeit($ts) {
		return date("d.m.Y H:m:s",$ts);
	}
// }

?>
