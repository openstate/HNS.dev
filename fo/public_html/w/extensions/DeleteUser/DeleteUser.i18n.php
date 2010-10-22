<?php
/**
 * Internationalisation file for the User Merge and Delete Extension.
 *
 * @addtogroup Extensions
 */

$messages = array();

$messages['en'] = array(
	'deleteuser'					=> 'Delete user $1',
	'deleteuser-desc'				=> "[[Special:DeleteUser|Deletes a user]] in the wiki database.",
	'deleteuser-warning'			=> 'WARNING',
	'deleteuser-warningtext'		=> 'Deleting a user is irreversible!',
	'deleteuser-cancel' 			=> 'Cancel',
	'deleteuser-submit' 			=> 'Delete user',
	'deleteuser-badtoken' 			=> 'Invalid edit token',
	'deleteuser-userdeleted' 		=> 'User "$1" has been deleted.',
	'deleteuser-userdeleted-log' 	=> 'Deleted user: $ ($3)',
	'deleteuser-updating' 			=> 'Updating $1 table ($2 to $3)',
	'deleteuser-success' 			=> 'Merge from $1 ($2) to $3 ($4) is complete.',
	'deleteuser-success-log' 		=> 'User $2 ($3) merged to $4 ($5)',
	'deleteuser-logpage'			=> 'User deletion log',
	'deleteuser-logpagetext'		=> 'This is a log of user delete actions.',
	'deleteuser-unmergable'			=> 'Unable to delete user - ID or name has been defined as undeletable.',
	'deleteuser-protectedgroup'		=> 'Unable to delete user - user is in a protected group.',
);

/** Dutch (Nederlands)
 */
$messages['nl'] = array(
	'deleteuser'					=> 'Gebruiker $1 verwijderen',
	'deleteuser-desc'				=> "[[Special:UserMerge|Verwijdert een gebruiker]]",
	'deleteuser-warning'			=> 'WAARSCHUWING',
	'deleteuser-warningtext'		=> 'Een gebruiker verwijderen kan niet ongedaan gemaakt worden!',
	'deleteuser-cancel'				=> 'Annuleren',
	'deleteuser-submit'				=> 'Gebruiker verwijderen',
	'deleteuser-badtoken'			=> 'Ongeldig bewerkingstoken',
	'deleteuser-userdeleted'		=> 'Gebruiker "$1" is verwijderd.',
	'deleteuser-userdeleted-log'	=> 'Verwijderde gebruiker: $2 ($3)',
	'deleteuser-updating'			=> 'Tabel $1 aan het bijwerken ($2 naar $3)',
	'deleteuser-success'			=> 'Samenvoegen van $1($2) naar $3($4) is afgerond.',
	'deleteuser-success-log'		=> 'Gebruiker $2 ($3) samengevoegd naar $4 ($5)',
	'deleteuser-logpage'			=> 'Logboek gebruikersverwijderingen',
	'deleteuser-logpagetext'		=> 'Dit is het logboek van gebruikersverwijderingen.',
	'deleteuser-unmergable'			=> 'Deze gebruiker kan niet verwijderd worden. De gebruikersnaam of het gebruikersnummer is ingesteld als niet te verwijderen.',
	'deleteuser-protectedgroup'		=> 'Het is niet mogelijk de gebruiker te verwijderen. De gebruiker zit in een beschermde groep.',
);
