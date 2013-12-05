<?php
/** 
 * @file
 * File where the translation elements are stored for datamalico ; it uses the mil_ help library for translation.
 *
 * Here the variable $mil_lang_common will be merged with with the $GLOBALS['mil_lang_common']. See it in the mil_.conf.php file.
 */

// search
$mil_lang_common['operator_equals'] = "=&nbsp; (Strictement égal à)";
$mil_lang_common['operator_gt_or_eq'] = ">= (Supérieur ou égal à)";
$mil_lang_common['operator_lt_or_eq'] = "<= (Inférieur ou égal à)";
$mil_lang_common['operator_gt'] = ">&nbsp; (Supérieur à)";
$mil_lang_common['operator_lt'] = "<&nbsp; (Inférieur à)";
$mil_lang_common['operator_between'] = "..&nbsp; (Entre les valeurs (valeurs incluses)";
$mil_lang_common['operator_containing'] = "*&nbsp; (Contient)";
$mil_lang_common['operator_not_contain'] = "!* (Ne contient pas)";
$mil_lang_common['operator_different'] = "!= (Strictement différent de)";
$mil_lang_common['operator_regexp'] = "regexp (Expression régulière)";
$mil_lang_common['operator_notregexp'] = "!regexp (Expression régulière)";
$mil_lang_common['operator_begins'] = "Commence par";
$mil_lang_common['operator_notbegins'] = "Ne commence pas par";
$mil_lang_common['operator_ends'] = "Fini par";
$mil_lang_common['operator_notends'] = "Ne fini pas par";



// ######################################################
// ERRORS

$mil_lang_common['ERROR'] = "Une erreur est survenue. Contactez le webmaster pour plus d'information.";
$mil_lang_common['webmaster_is_notified'] = "Le webmaster vient d'en être informé, et va corriger le problème. Nous vous prions d'accepter nos excuses, et vous demandons de réessayer ultérieurement.";

$mil_lang_common['horizontal_access_false'] = "Vous n'avez pas les droits suffisants pour effectuer cette action.";


$mil_lang_common['hey'] = "Hé ho !";
$mil_lang_common['field_must_be_filled'] = "Veuillez remplir ce champ.";


// select
$mil_lang_common['open_link_text'] = "Voir";

//update
$mil_lang_common['NO_ROW_UPDATED'] = "changement effectué.";
$mil_lang_common['1_ROW_UPDATED'] = "changement effectué.";
$mil_lang_common['X_ROWS_UPDATED'] = "changements effectués.";
$mil_lang_common['ERROR_NO_ACCESS_TO_UPDATE'] = "Vous n'avez pas les droits de modification suffisants.";

//insert
$mil_lang_common['NO_INSERT_DONE'] = "Aucune ajout n'a été effectuée dans la base de donnée.";
$mil_lang_common['INSERT_SUCCESSFULL'] = "Ajout à la base de donnée réussi.";
$mil_lang_common['ERROR_ON_INSERT'] = "Erreur lors de l'insertion.";
$mil_lang_common['ERROR_ON_INSERT_NO_AUTOINC'] = "Erreur lors de l'insertion. Car il n'y a pas de champs autoincrement à la table :";
$mil_lang_common['ERROR_ON_INSERT_CANT_GET_AUTOINC'] = "Erreur lors de l'insertion. Impossible de connaître le next_id pour la table :";
$mil_lang_common['ERROR_ON_INSERT_NEXTID_VS_JUSTINSERTEDID'] = "Erreur lors de l'insertion. Les valeurs (next_id vs just_inserted_id) sont différentes :";
$mil_lang_common['ERROR_NO_RIGHT_TO_INSERT'] = "Vous n'avez pas les droits d'insertion suffisants.";

//delete
$mil_lang_common['DELETION_SUCCESSFULL'] = "Suppression réussie.";
$mil_lang_common['ERROR_NO_RIGHT_TO_DELETE'] = "Vous n'avez pas le droits de supression suffisants.";


// General:
$mil_lang_common['please_choose'] = "-- Au choix --";
$mil_lang_common['no'] = "Non";
$mil_lang_common['yes'] = "Oui";


// pagination
$mil_lang_common['NO_RESULT_DISPLAYED'] = "Aucun résultat.";	// No result
$mil_lang_common['1_RESULT_DISPLAYED'] = "résultat";		// Page 1 of 1 - (1 result)
$mil_lang_common['PAGE_X_OF_N_1'] = "Page"; 			// Page 3 of 15 - (146 results)
$mil_lang_common['PAGE_X_OF_N_2'] = "sur";			// Page 3 of 15 - (146 results)	
$mil_lang_common['X_RESULTS_DISPLAYED'] = "résultats";		// Page 3 of 15 - (146 results)




// #########################
// French help for encoding:

// �	é	�
// �	è	�
// �	ê	�
// �	à	�
// �	ô
// �	°
// �	ç	�
// �	û
// ...	…
//
//
//
// dès qu'elle sera publiée
// (de 0 à 23)
// ... être retiré, N° d'article, Français
// Êtes-vous sûr de vouloir supprimer définitivement cette image ?
// Etc is …
// Une chambre aux goûts du jour


?>
