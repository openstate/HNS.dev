<?php
/** \file
* \brief Contains code for the DeleteUser Class (extends SpecialPage).
*/

///Special page class for the User Merge and Delete extension
/**
 * Special page that allows sysops to merge referances from one
 * user to another user - also supports deleting users following
 * merge.
 *
 * @addtogroup Extensions
 */
class DeleteUser extends SpecialPage {
	function __construct() {
		parent::__construct( 'DeleteUser', 'deleteuser' );
	}

	function getDescription() {
		global $wgUser;
		return wfMsgExt( strtolower( $this->mName ), ('parsemag'), $wgUser->getName() );
	}

	function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser, $wgArticlePath, $wgServer;
		
		wfLoadExtensionMessages( 'DeleteUser' );

		$this->setHeaders();

		if ( !$wgUser->isLoggedIn() ) {
			$wgOut->loginToUse();
			return;
		}

		$success = false;
		if ( $wgRequest->getVal( 'token' )  ) {
			//POST data found
			if ( $wgRequest->getVal('cancel') ) {
				$wgOut->redirect($wgServer.str_replace('$1', $wgUser->getUserPage(), $wgArticlePath));
				return;
			} else {
				//go time, baby
				if ( !$wgUser->matchEditToken( $wgRequest->getVal( 'token' ) ) ) {
					//bad editToken
					$wgOut->addHTML( "<span style=\"color: red;\">" . wfMsg( 'deleteuser-badtoken' ) . "</span><br />\n" );
				} else {
					//good editToken
					$wgOut->addHTML('<span style="color: green;">'.wfMsgExt('deleteuser-userdeleted', ('parsemag'), $wgUser->getName()).'</span><br />'."\n");
					$u = User::newFromId($wgUser->getId());
					$u->load();
					$wgUser->logout();
					$wgUser->loadDefaults();
					$this->mergeUser( 'Anonymous', 0, $u->getName(), $u->getId());
					$this->deleteUser($u->getId(), $u->getName());
					$success = true;
				}
			}
		} else {
			//NO POST data found
		}

		if (!$success) {
			$wgOut->addHTML(
				Xml::openElement( 'form', array( 'method' => 'post', 'action' => $this->getTitle()->getLocalUrl(), 'id' => 'deleteuserform' ) ) .
				"<span style=\"color: red;\"><strong>".wfMsg('deleteuser-warning')."</strong> ".wfMsg('deleteuser-warningtext')."</span>".
				Xml::openElement( 'table', array( 'id' => 'mw-deleteuser-table' ) ) .
				"<tr>
				<tr>
					<td class='mw-submit'>".
						Xml::submitButton( wfMsg( 'deleteuser-cancel' ), array( 'name' => 'cancel', 'tabindex' => '2' ) ) .
					"</td>
					<td class='mw-submit'>" .
						Xml::submitButton( wfMsg( 'deleteuser-submit' ), array( 'tabindex' => '2' ) ) .
					"</td>
				</tr>" .
				Xml::closeElement( 'table' ) .
				Xml::hidden( 'token', $wgUser->editToken() ) .
				Xml::closeElement( 'form' ) . "\n"
			);
		}
	}

	///Function to delete users following a successful mergeUser call
	/**
	 * Removes user entries from the user table and the user_groups table
	 *
	 * @param $olduserID int ID of user to delete
	 * @param $olduser_text string Username of user to delete
	 *
	 * @return Always returns true - throws exceptions on failure.
	 */
	private function deleteUser( $olduserID, $olduser_text ) {
		/*global $wgOut;
		$wgOut->addHTML('DELETE ['.$olduserID.' '.$olduser_text.']');
		return;*/
		global $wgOut,$wgUser;

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'user_groups', array( 'ug_user' => $olduserID ) );
		$dbw->delete( 'user', array( 'user_id' => $olduserID ) );
		//$wgOut->addHTML( wfMsg( 'deleteuser-userdeleted', $olduser_text, $olduserID ) );

		$log = new LogPage( 'deleteuser' );
		$log->addEntry( 'deleteuser', $wgUser->getUserPage(),'',array($olduser_text,$olduserID) );

		$users = $dbw->selectField( 'user', 'COUNT(*)', array() );
		$admins = $dbw->selectField( 'user_groups', 'COUNT(*)', array( 'ug_group' => 'sysop' ) );
		$dbw->update( 'site_stats',
			array( 'ss_users' => $users, 'ss_admins' => $admins ),
			array( 'ss_row_id' => 1 ) );
		return true;
	}

	///Function to merge database referances from one user to another user
	/**
	 * Merges database references from one user ID or username to another user ID or username
	 * to preserve referential integrity.
	 *
	 * @param $newuser_text string Username to merge referances TO
	 * @param $newuserID int ID of user to merge referances TO
	 * @param $olduser_text string Username of user to remove referances FROM
	 * @param $olduserID int ID of user to remove referances FROM
	 *
	 * @return Always returns true - throws exceptions on failure.
	 */
	private function mergeUser( $newuser_text, $newuserID, $olduser_text, $olduserID ) {
		/*global $wgOut;
		$wgOut->addHTML('MERGE ['.$newuserID.' '.$newuser_text.' <= '.$olduserID.' '.$olduser_text.']');
		return;*/
		global $wgOut, $wgUser;

		$textUpdateFields = array(
			array('archive','ar_user_text'),
			array('revision','rev_user_text'),
			array('filearchive','fa_user_text'),
			array('image','img_user_text'),
			array('oldimage','oi_user_text'),
			array('recentchanges','rc_user_text'),
			array('ipblocks','ipb_address')
		);

		$idUpdateFields = array(
			array('archive','ar_user'),
			array('revision','rev_user'),
			array('filearchive','fa_user'),
			array('image','img_user'),
			array('oldimage','oi_user'),
			array('recentchanges','rc_user'),
			array('logging','log_user')
		);

		$dbw = wfGetDB( DB_MASTER );

		foreach ( $idUpdateFields as $idUpdateField ) {
			$dbw->update( $idUpdateField[0], array( $idUpdateField[1] => $newuserID ), array( $idUpdateField[1] => $olduserID ) );
			//$wgOut->addHTML( wfMsg('deleteuser-updating', $idUpdateField[0], $olduserID, $newuserID ) . "<br />\n" );
		}

		foreach ( $textUpdateFields as $textUpdateField ) {
			$dbw->update( $textUpdateField[0], array( $textUpdateField[1] => $newuser_text ), array( $textUpdateField[1] => $olduser_text ) );
			//$wgOut->addHTML( wfMsg( 'deleteuser-updating', $textUpdateField[0], $olduser_text, $newuser_text ) . "<br />\n" );
		}

		$dbw->delete( 'user_newtalk', array( 'user_id' => $olduserID ));

		//$wgOut->addHTML("<hr />\n" . wfMsg('deleteuser-success',$olduser_text,$olduserID,$newuser_text,$newuserID) . "\n<br />");

		$log = new LogPage( 'deleteuser' );
		$log->addEntry( 'mergeuser', $wgUser->getUserPage(),'',array($olduser_text,$olduserID,$newuser_text,$newuserID) );

		return true;
	}
}
