<?php
/** \file
* \brief Contains code for the UserMerge Class (extends SpecialPage).
*/

///Special page class for the User Merge and Delete extension
/**
 * Special page that allows sysops to merge referances from one
 * user to another user - also supports deleting users following
 * merge.
 *
 * @addtogroup Extensions
 */
class UnblockUser extends SpecialPage {
	function __construct() {
		parent::__construct( 'UnblockUser', 'unblockuser' );
	}

	function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		wfLoadExtensionMessages( 'UnblockUser' );

		$this->setHeaders();

		if ( !$wgUser->isAllowed( 'unblockuser' ) ) {
			$wgOut->permissionRequired( 'unblockuser' );
			return;
		}

		// init variables
		$user_text = '';
		$validUser = false;

		if ( strlen( $wgRequest->getText( 'user' )) > 0 ) {
			//POST data found
			$user = Title::newFromText( $wgRequest->getText( 'user' ) );
			$user_text = is_object( $user ) ? $user->getText() : '';


			if ( strlen( $user_text ) > 0 ) {
				$objUser = User::newFromName( $user_text );
				$userID = $objUser->idForName();

				global $wgUser;

				if ( !is_object( $objUser ) ) {
					$validUser = false;
					$wgOut->wrapWikiMsg( "<div class='error'>\n$1</div>", 'unblockuser-baduser' );
				} else {
					$validUser = true;

				}
			} else {
				$validUser = false;
				$wgOut->addHTML( "<span style=\"color: red;\">" . wfMsg('unblockuser-nouser') . "</span><br />\n" );
			}
		} else {
			//NO POST data found
		}

		if ( $validUser ) {
			//go time, baby
			if ( !$wgUser->matchEditToken( $wgRequest->getVal( 'token' ) ) ) {
				//bad editToken
				$wgOut->addHTML( "<span style=\"color: red;\">" . wfMsg( 'unblockuser-badtoken' ) . "</span><br />\n" );
			} else {
				//good editToken
				$objUser->load();
				$objUser->resetPasswordFailed();
				$wgOut->addHTML('<span style="color: green;">'.wfMsgExt('unblockuser-userunblocked', ('parsemag'), $objUser->getName()).'</span><br />'."\n");
			}
		}

		$wgOut->addHTML(
			Xml::openElement( 'form', array( 'method' => 'post', 'action' => $this->getTitle()->getLocalUrl(), 'id' => 'unblockuserform' ) ) .
			Xml::openElement( 'table', array( 'id' => 'mw-unblockuser-table' ) ) .
			"<tr>
				<td class='mw-label'>" .
					Xml::label( wfMsg( 'unblockuser-user' ), 'user' ) .
				"</td>
				<td class='mw-input'>" .
					Xml::input( 'user', 20, $user_text, array( 'type' => 'text', 'tabindex' => '1', 'onFocus' => "document.getElementById('user').select;" ) ) . ' ' .
				"</td>
			</tr>
			<tr>
				<td>&nbsp;
				</td>
				<td class='mw-submit'>" .
					Xml::submitButton( wfMsg( 'unblockuser-submit' ), array( 'tabindex' => '2' ) ) .
				"</td>
			</tr>" .
			Xml::closeElement( 'table' ) .
			Xml::hidden( 'token', $wgUser->editToken() ) .
			Xml::closeElement( 'form' ) . "\n"
		);
	}
}
