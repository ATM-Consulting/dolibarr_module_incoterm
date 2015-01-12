<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/propale/modules_propale.php
 *  \ingroup    propale
 *  \brief      Fichier contenant la classe mere de generation des propales en PDF
 *  			et la classe mere de numerotation des propales
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';   // Requis car utilise dans les classes qui heritent


/**
 *	Classe mere des modeles de propale
 */
abstract class ModelePDFPropales extends CommonDocGenerator
{
	var $error='';


	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  string	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='propal';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}
}


/**
 *	Classe mere des modeles de numerotation des references de propales
 */
abstract class ModeleNumRefPropales
{
	var $error='';

	/**
	 * Return if a module can be used or not
	 *
	 * @return	boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *  Renvoi la description par defaut du modele de numerotation
	 *
	 * 	@return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("propale");
		return $langs->trans("NoDescription");
	}

	/**
	 * 	Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("propale");
		return $langs->trans("NoExample");
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 * 	Renvoi prochaine valeur attribuee
	 *
	 *	@param		Societe		$objsoc     Object third party
	 *	@param		Propal		$propal		Object commercial proposal
	 *	@return     string      Valeur
	 */
	function getNextValue($objsoc,$propal)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *  Renvoi version du module numerotation
	 *
	 *  @return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}


/**
 *  Create a document onto disk according to template module.
 *
 * 	@param	    DoliDB		$db  			Database handler
 * 	@param	    object		$object			Object proposal
 * 	@param	    string		$modele			Force model to use ('' to not force)
 * 	@param		Translate	$outputlangs	Object langs to use for output
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 * 	@return     int         				0 if KO, 1 if OK
 */
function propale_pdf_create($db, $object, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	global $conf,$user,$langs;
	$langs->load("propale");

	$error=0;

	$srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
	    if (! empty($conf->global->PROPALE_ADDON_PDF))
	    {
	        $modele = $conf->global->PROPALE_ADDON_PDF;
	    }
	    else
	    {
	        $modele = 'azur';
	    }
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/propale/doc/".$file,0);
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }

	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);
		//$obj->message = $message;
		
		// Appel des triggers
		include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		$interface=new Interfaces($db);
		$result=$interface->run_triggers('BEFORE_PROPAL_BUILDDOC',$object,$user,$langs,$conf);
		if ($result < 0) { $error++; $this->errors=$interface->errors; }
		// Fin appel triggers
		
		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($object);

			// Success in building document. We build meta file.
			dol_meta_create($object);

			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('PROPAL_BUILDDOC',$object,$user,$langs,$conf);
			if ($result < 0) { $error++; $obj->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"propal_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}
