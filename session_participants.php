<?php

require_once('config.php');

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;

$result = restrictedArea($user, 'planformation', 0, 'planformation');

require_once('./class/sessionformation.class.php');
require_once('./class/formation.class.php');
require_once('./class/participant.class.php');

$langs->load('planformation@planformation');
	
$PDOdb = new TPDOdb;

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originId = GETPOST('originid', 'int');

$session = new TSessionFormation;
$formation = new TFormation;
$participant = new TParticipantSession;

if(! empty($id)) {
	if(! $session->load($PDOdb, $id)) {
		setEventMessage($langs->trans('ImpossibleLoadElement'), 'errors');
		_list($PDOdb, $session, $formation);
		exit;
	}

	if(! empty($session->fk_formation)) {
		$formation->load($PDOdb, $session->fk_formation);
	}
} else {
	setEventMessage($langs->trans('PFSessionNotFound'), 'errors');
	
	header('Location: ' . dol_buildpath('/planformation/session.php', 1) . '?action=list');
	exit;
}


switch($action) {

	case 'addattendee':
		$userId = GETPOST('fk_user');
		if($userId > 0) {
			$participant->fk_session = $session->id;
			$participant->fk_user = $userId;
			$participant->save($PDOdb);
		}

		_list($PDOdb, $session, $formation, $participant);
	break;

	case 'deleteattendee':
		$attendeeRowid = GETPOST('attendee');

		if($attendeeRowid > 0) {
			$participant->load($PDOdb, $attendeeRowid);
			$participant->delete($PDOdb);
		}

		_list($PDOdb, $session, $formation, $participant);
	break;

	case 'list':
	default:
		_list($PDOdb, $session, $formation, $participant);
}



function _list(&$PDOdb, &$session, &$formation, &$participant) {
	global $langs, $conf;

	_header_list($session, 'attendees');

	
	print load_fiche_titre($langs->trans('PFSessionAttendeeList'), '');


	$TParticipants = $session->getParticipants($PDOdb, $session->rowid);


	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre">' . $langs->trans('Name') . '</th>';
	print '<th class="liste_titre">&nbsp;</th>';
	print '</tr>';


	$nbParticipants = count($TParticipants);

	foreach($TParticipants as $p) {

		$actionsButtons = '';
		
		if($session->statut == 0) {
			$actionButtons = '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $session->id . '&action=deleteattendee&attendee=' . $p->rowid. '">' . img_picto('', 'delete') . '</a>';
		}
		
		print '<tr>';
		print '<td>' . img_picto('', 'object_user') . ' <a href="'. dol_buildpath('/user/card.php' , 1) .'?id=' . $p->fk_user . '">' . $p->lastname. ' ' . $p->firstname . '</a></td>';
		print '<td align="right">' . $actionButtons.'</td>';
		print '</tr>';

	}
	
	if($nbParticipants == 0) {
		print '<tr class="impair"><td colspan="2" align="center">';
		print $langs->trans('PFNoSessionAttendee');
		print '</td></tr>';
	}


	// Ajout nouveau participant
	
	if($session->statut == 0) {
		print '<tr class="liste_titre"><td colspan="2">' . $langs->trans('PFAddNewSessionAttendee') . '</td></tr>';
		
		
		$TUsersPotentiels = $session->getUsersNotSignedUp($PDOdb);

		$formCore = new TFormCore($_SERVER['PHP_SELF'] . '?id=' . $session->id, 'formAddAttendee', 'POST');

		$TUsersCombo = array();

		foreach($TUsersPotentiels AS $u) {
			$TUsersCombo[$u->rowid] = $u->lastname . ' ' . $u->firstname;
		}
		
		dol_include_once('/core/class/html.form.class.php');

		$form = new Form($db);

		print '<tr class="liste_titre">';
		print '<th class="liste_titre">' . $langs->trans('Name') . '</th>';
		print '<th class="liste_titre">&nbsp;</th>';
		print '</tr>';

		if(count($TUsersPotentiels) > 0) {
			print '<tr class="impair">';
			print '<td>' . $formCore->combo('', 'fk_user', $TUsersCombo, '0', 1, '', ' style="min-width:150px"'). '</td>';
			print '<td align="right">'. $formCore->hidden('action', 'addattendee') . $formCore->hidden('fk_session', $session->rowid) . $formCore->btsubmit($langs->trans('Add'), 'addattendee') . '</td>';
			print '</tr>';
		} else {
			print '<tr class="impair"><td colspan="2" align="center">' . $langs->trans('PFAllUsersInThisSession') . '</td></tr>';
		}

		$formCore->end();

	}
	
	print "</table>";

}



function _header_list(&$session, $active) {
	global $langs;
	
	dol_include_once('/planformation/lib/planformation.lib.php');
	
	llxHeader('', $langs->trans('PFFormationSession'),'','',0,0);
	
	$head = session_prepare_head($session);
	dol_fiche_head($head, $active, $langs->trans('PFFormationSession'), 0);
}

