<?php
function show_conv($file, $socid,$nom_courrier)
{
	global $langs, $conf, $db, $id, $form, ${
		'flag_bc_'.$socid};

		$model = $file;
		$file = $file.'_'.$id.'_'.$socid.'.pdf';

		$agf = new Agefodd_convention($db);
		$result = $agf->fetch($id, $socid);

		// Si la convention a déjà été complété (création d'un entrée dans la table)
		if ($agf->id)
		{
			if (is_file($conf->agefodd->dir_output.'/'.$file))
			{
				// afficher
				$legende = $langs->trans("AgfDocOpen");
				$mess = '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=agefodd&file='.$file.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';

				// Regenerer
				$legende = $langs->trans("AgfDocRefresh");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=refresh&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';

				// Supprimer
				$legende = $langs->trans("AgfDocDel");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=del&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';
			}
			else
			{
				// Création de la convention au format PDF
				$legende = $langs->trans("AgfDocCreate");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=create&socid='.$socid.'&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
			}

			// editer la convention pour modification
			$legende = $langs->trans("AgfDocEdit");
			$mess.= '<a href="'.dol_buildpath('/agefodd/session/convention.php',1).'?action=edit&id='.$agf->id.'" alt="'.$legende.'" title="'.$legende.'">';
			$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';


		}
		else
		{
			// Si la convention n'a pas encore été renseignée, il faut le faire maintenant
			$legende = $langs->trans("AgfDocEdit");
			$mess.= '<a href="'.dol_buildpath('/agefodd/session/convention.php',1).'?action=create&sessid='.$id.'&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
			$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';

		}

		if (empty(${
			'flag_bc_'.$socid})) $mess = $form->textwithpicto('',$langs->trans("AgfFactureFacNoBonHelp"),1,'help');

			return $mess;
}

function show_doc($file, $socid, $nom_courrier)
{
	global $langs, $conf, $id, ${
		'flag_bc_'.$socid}, $form, $idform;

		$model = $file;
		if(!empty($nom_courrier)) $file = $file.'-'.$nom_courrier.'_'.$id.'_'.$socid.'.pdf';
		elseif (!empty($socid)) $file = $file.'_'.$id.'_'.$socid.'.pdf';
		elseif ($model=='fiche_pedago') $file=$file.'_'.$idform.'.pdf';
		else $file = $file.'_'.$id.'.pdf';

		if (is_file($conf->agefodd->dir_output.'/'.$file))
		{
			// afficher
			$legende = $langs->trans("AgfDocOpen");
			$mess = '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=agefodd&file='.$file.'" alt="'.$legende.'" title="'.$legende.'">';
			$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';

			// Regenerer
			$legende = $langs->trans("AgfDocRefresh");
			$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=refresh&model='.$model.'&cour='.$nom_courrier.'&idform='.$idform.'" alt="'.$legende.'" title="'.$legende.'">';
			$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';

			// Supprimer
			$legende = $langs->trans("AgfDocDel");
			$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=del&model='.$model.'&cour='.$nom_courrier.'&idform='.$idform.'" alt="'.$legende.'" title="'.$legende.'">';
			$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';

		}
		else
		{
			// Génereration des documents
			if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_'.$model.'.modules.php')))
			{
				$legende = $langs->trans("AgfDocCreate");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=create&socid='.$socid.'&model='.$model.'&cour='.$nom_courrier.'&idform='.$idform.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
			}
			else
			{
				$mess = $form->textwithpicto('',$langs->trans("AgfDocNoTemplate"),1,'warning');
			}
		}
		return $mess;
}

function show_fac($file, $socid, $mdle)
{
	global $langs, $conf, $db, $id, $form, ${
		'flag_bc_'.$socid};

		$agf = new Agefodd_facture($db);
		$result = $agf->fetch($id, $socid);


		// Gestion des bons de commande (ou brouillon de facture)
		if ($mdle == 'bc')
		{
			if ($agf->comid)
			{
				${
					'flag_bc_'.$socid} = $agf->comid;

					// Consulter la fiche Dolibarr du BC
					$legende = $langs->trans("AgfFactureSeeBon",$agf->comref);
					$mess.= '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$agf->comid.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';

					// Aller au formulaire d'envoi de mail
					$legende = $langs->trans("AgfFactureSeeBonMail",$agf->comref);
					$mess.= '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$agf->comid.'&action=presend&mode=init" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';

					// Délier le bon de commande
					$legende = $langs->trans("AgfFactureUnselectBon");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&id='.$id.'&type=bc&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.dol_buildpath('/agefodd/img/unlink.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';
			}
			else
			{
				$mess = '<table class="nobordernopadding"><tr>';

				// Generer le bon de commande
				$legende = $langs->trans("AgfFactureGenererBon");
				$mess .= '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?action=create&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess .= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a></td>';

				// Lier un bon de commande existant
				$legende = $langs->trans("AgfFactureSelectBon");
				$mess.= '<td><a href="'.dol_buildpath('/agefodd/session/document.php',1).'?action=link&id='.$id.'&type=bc&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';$mess.= '<img src="'.dol_buildpath('/agefodd/img/link.png',1).'" border="0" align="absmiddle" hspace="2px" ></a></td>';

				$mess.= '<td>'.$form->textwithpicto('',$langs->trans("AgfFactureBonBeforeSelectHelp"),1,'help').'</td>';

				$mess .= '</tr></table>';
			}
		}
		// gestion des factures
		elseif ($mdle == 'fac')
		{
			if ($agf->facid)
			{
				$legende = $langs->trans("AgfFactureSeeFac").' '.$agf->facnumber;
				$mess = '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$agf->facid.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';

				// Aller au formulaire d'envoi de mail
				$legende = $langs->trans("AgfFactureSeeFacMail",$agf->facnumber);
				$mess.= '<a href="'.DOL_URL_ROOT.'/compta/facture.php?id='.$agf->comid.'&action=presend&mode=init" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';

				// Délier la facture
				$legende = $langs->trans("AgfFactureUnselectFac");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&id='.$id.'&type=fac&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.dol_buildpath('/agefodd/img/unlink.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';

			}
			else
			{
				if (!empty(${
					"flag_bc_$socid"}))
					{
						$mess = '';

						// Créer la facture
						$legende = $langs->trans("AgfFactureAddFac");
						$commande_static= new Commande($db);
						$mess.= '<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&origin='.$commande_static->element.'&originid='.$agf->comid.'&socid='.$socid.'"  alt="'.$legende.'" title="'.$legende.'">';
						$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';

						// lier une facture existante
						$legende = $langs->trans("AgfFactureSelectFac");
						$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?action=link&id='.$id.'&type=fac&socid='.$socid.'" alt="'.$legende.'" alt="'.$legende.'" title="'.$legende.'">';$mess.= '<img src="'.dol_buildpath('/agefodd/img/link.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';
					}
					else
					{
						$mess = $form->textwithpicto('',$langs->trans("AgfFactureFacNoBonHelp"),1,'help');
					}
			}
		}
		else
		{
			$mess = 'error';
		}
		return $mess;
}

function document_line($intitule, $level=2, $mdle, $socid=0, $nom_courrier='')
{
	print '<tr style="height:14px">'."\n";
	if ($level == 2)
	{
		print '<td style="border:0px; width:10px">&nbsp;</td>'."\n";
		print '<td style="border-right:0px;">';
	}
	else print '<td colspan="2" style="border-right:0px;">';
	print $intitule.'</td>'."\n";
	if ( $mdle == 'bc' || $mdle == 'fac')
	{
		print '<td style="border-left:0px;" align="right">'.show_fac($mdle, $socid, $mdle).'</td></tr>'."\n";
	}
	elseif ( $mdle == 'convention')
	{
		print '<td style="border-left:0px; width:200px" align="right">'.show_conv($mdle, $socid,$nom_courrier).'</td></tr>'."\n";
	}
	else
	{
		print '<td style="border-left:0px; width:200px"  align="right">'.show_doc($mdle, $socid, $nom_courrier).'</td></tr>'."\n";
	}
}

function document_send_line($intitule, $level=2, $mdle, $socid=0, $nom_courrier='')
{
	global $conf,$langs,$id, $idform;
	$langs->load('mails');
	print '<tr style="height:14px">'."\n";
	if ($level == 2)
	{
		print '<td style="border:0px; width:10px">&nbsp;</td>'."\n";
		print '<td style="border-right:0px;">';
	}
	else print '<td colspan="2" style="border-right:0px;">';
	print $intitule.'</td>'."\n";
	if ( $mdle == 'bc' || $mdle == 'fac')
	{
		print '<td style="border-left:0px;" align="right">'.show_fac($mdle, $socid, $mdle).'</td></tr>'."\n";
	}
	elseif ( $mdle == 'convention')
	{
		print '<td style="border-left:0px; width:200px"  align="right">';

		// Check if file exist
		$filename = 'convention_'.$id.'_'.$socid.'.pdf';
		$file = $conf->agefodd->dir_output . '/' .$filename;
		if(file_exists($file)) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=presend_convention&mode=init"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> '.$langs->trans('SendMail').'</a>';
		}
		else print $langs->trans('AgfDocNotDefined');

		print '</td></tr>'."\n";
	}
	else if ($mdle == 'fiche_presence') {

		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		//$filename = 'fiche_presence_'.$id.'_'.$socid.'.pdf';
		$filename = 'fiche_presence_'.$id.'.pdf';
		$file = $conf->agefodd->dir_output . '/' .$filename;
		if(file_exists($file)) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=presend_presence&mode=init"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> '.$langs->trans('SendMail').'</a>';
		}
		else print $langs->trans('AgfDocNotDefined');
		print '</td></tr>'."\n";

	}
	else if ($mdle == 'attestation') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'attestation_'.$id.'_'.$socid.'.pdf';
		$file = $conf->agefodd->dir_output . '/' .$filename;
		if(file_exists($file)) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=presend_attestation&mode=init"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> '.$langs->trans('SendMail').'</a>';
		}
		else print $langs->trans('AgfDocNotDefined');
		print '</td></tr>'."\n";
	}
	else
	{
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'fiche_pedago_'.$idform.'.pdf';
		$file = $conf->agefodd->dir_output . '/' .$filename;
		if(file_exists($file)) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=presend_pedago&mode=init"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> '.$langs->trans('SendMail').'</a>';
		}
		else print $langs->trans('AgfDocNotDefined');
		print '</td></tr>'."\n";
	}
}
