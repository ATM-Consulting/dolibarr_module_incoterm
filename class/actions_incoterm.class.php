<?php
class ActionsIncoterm
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */ 
     
    function doActions($parameters, &$object, &$action, $hookmanager) 
    {
    	global $db;
		/*echo '<pre>';
		print_r($object);
		echo '</pre>';*/
		
        if($action == "validmodincoterm"){
			if(isset($_REQUEST['incoterms']) && !empty($_REQUEST['incoterms'])){
				$db->query('UPDATE '.MAIN_DB_PREFIX.$object->table_element.' SET fk_incoterms = '.$_REQUEST['incoterms'].' WHERE rowid = '.$object->id);
			}
		}
		
 
        return 0;
    }
    
    function formObjectOptions ($parameters, &$object, &$action, $hookmanager) 
    {
    	global $db, $user, $conf;
		dol_include_once('/incoterm/config.php');
		/*echo '<pre>';
		print_r($object);
		echo '</pre>';exit;*/
		
    	
		/*
		 * INCOTERMS 
		 */	
		if(in_array('propalcard',explode(':',$parameters['context'])) 
				|| in_array('ordercard',explode(':',$parameters['context'])) 
				|| in_array('invoicecard',explode(':',$parameters['context'])) 
				|| in_array('expeditioncard',explode(':',$parameters['context']))
				|| in_array('thirdpartycard',explode(':',$parameters['context']))){
				
			/*
			 * INCOTERMS
			 */	
				if($action == "create"){
					
					$sql = "SELECT fk_incoterms FROM ".MAIN_DB_PREFIX."societe WHERE rowid = ".$_REQUEST['socid'];
					$resql = $db->query($sql);
					
					if($resql){
						$res = $db->fetch_object($resql);
						$id_incoterm = $res->fk_incoterms;
					}
					else 
						$id_incoterm = "";
					
					$sql = "SELECT rowid, code FROM ".MAIN_DB_PREFIX."c_incoterms ORDER BY rowid ASC";
					$resql = $db->query($sql);
					
					print '<tr><td>Incoterms</td>';
					print '<td colspan="2">';
					print '<select name="incoterms" class="flat" id="incoterms_id">';
					print '<option value="">&nbsp;</option>';
					
					while ($res = $db->fetch_object($resql)) {
						if($res->rowid == $id_incoterm){
							print '<option selected="selected" value="'.$res->rowid.'">'.$res->code.'</option>';
						}	
						else{
							print '<option value="'.$res->rowid.'">'.$res->code.'</option>';
						}
					}
					
					print '</select></td></tr>';
				}
				elseif($action == "modincoterm"){
					
					$sql = "SELECT fk_incoterms FROM ".MAIN_DB_PREFIX.$object->table_element." WHERE rowid = ".$object->id;
					$resql = $db->query($sql);
					
					if($resql){
						$res = $db->fetch_object($resql);
						$id_incoterm = $res->fk_incoterms;
					}
					else 
						$id_incoterm = "";
					
					$sql = "SELECT rowid, code FROM ".MAIN_DB_PREFIX."c_incoterms ORDER BY rowid ASC";
					$resql = $db->query($sql);
					$id_field = (in_array('thirdpartycard',explode(':',$parameters['context'])))? "socid" : "id";
					print '<tr><td>Incoterms</td>';
					print '<td colspan="2">';
					print '<form action="'.$_SERVER["PHP_SELF"].'?'.$id_field.'='.$object->id.'" method="post">';
					print '<input type="hidden" name="action" value="validmodincoterm" />';
					print '<select name="incoterms" class="flat" id="incoterms_id">';
					print '<option value="">&nbsp;</option>';
					
					while ($res = $db->fetch_object($resql)) {
						if($res->rowid == $id_incoterm)
							print '<option selected="selected" value="'.$res->rowid.'">'.$res->code.'</option>';
						else
							print '<option value="'.$res->rowid.'">'.$res->code.'</option>';
					}
					
					print '</select><input class="button" type="submit" value="Modifier"></form></td></tr>';
				}
				else{
					$sql = "SELECT fk_incoterms FROM ".MAIN_DB_PREFIX.$object->table_element." WHERE rowid = ".$object->id;
					$resql = $db->query($sql);
					if($resql){
						$res = $db->fetch_object($resql);
						
						$sql = "SELECT code FROM ".MAIN_DB_PREFIX."c_incoterms WHERE rowid = ".$res->fk_incoterms;
						$resql = $db->query($sql);
					}
					$id_field = (in_array('thirdpartycard',explode(':',$parameters['context'])))? "socid" : "id";
					print '<tr><td height="10"><table width="100%" class="nobordernopadding"><tbody><tr>';
					print '<td>Incoterms</td>';
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=modincoterm&'.$id_field.'='.$object->id.'">'
							.img_picto('Définir Incoterm', 'edit')
							.'</a></td>';
					PRINT '</tr></tbody></table></td>';
					print '<td colspan="3">';
					
					if($resql){
						$res = $db->fetch_object($resql);
						print $res->code;
					}
					
					print '</select></td></tr>';
					
				
				}
			
			
		}
			
        return 0;
    }

	function formAddObjectLine($parameters, &$object, &$action, $hookmanager){
		global $db,$user,$conf;
		
		
		return 0;
	}

 	function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
    	global $db, $user,$conf;
		
		
        return 0;
    }
}