<?php
/*
 * ----------------------------------------------------------------------------
 * 'GuMax' style sheet for CSS2-capable browsers.
 *       Loosely based on the monobook style
 *
 * @Version 3.3
 * @Author Paul Y. Gu, <gu.paul@gmail.com>
 * @Copyright paulgu.com 2007 - http://www.paulgu.com/
 * @License: GPL (http://www.gnu.org/copyleft/gpl.html)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * ----------------------------------------------------------------------------
 */

if( !defined( 'MEDIAWIKI' ) )
    die( -1 );

/** */
require_once('includes/SkinTemplate.php');

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class SkinGuMax extends SkinTemplate {
    /** Using GuMax */
    function initPage( &$out ) {
        SkinTemplate::initPage( $out );
        $this->skinname  = 'gumax';
        $this->stylename = 'gumax';
        $this->template  = 'GuMaxTemplate';
    }
}

/**
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class GuMaxTemplate extends QuickTemplate {
    /**
     * Template filter callback for GuMax skin.
     * Takes an associative array of data set from a SkinTemplate-based
     * class, and a wrapper for MediaWiki's localization database, and
     * outputs a formatted page.
     *
     * @access private
     */
    function execute() {
        // Suppress warnings to prevent notices about missing indexes in $this->data
        wfSuppressWarnings();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="<?php $this->text('xhtmldefaultnamespace') ?>" <?php
    foreach($this->data['xhtmlnamespaces'] as $tag => $ns) {
        ?>xmlns:<?php echo "{$tag}=\"{$ns}\" ";
    } ?>xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">

<head>
    <meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
    <?php $this->html('headlinks') ?>
    <title><?php $this->text('pagetitle') ?></title>
    <style type="text/css" media="screen,projection">/*<![CDATA[*/ @import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/gumax_main.css?<?php echo $GLOBALS['wgStyleVersion'] ?>"; /*]]>*/</style>
    <link rel="stylesheet" type="text/css" <?php if(empty($this->data['printable']) ) { ?>media="print"<?php } ?> href="<?php $this->text('stylepath') ?>/common/commonPrint.css?<?php echo $GLOBALS['wgStyleVersion'] ?>" />
    <link rel="stylesheet" type="text/css" media="handheld" href="<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/handheld.css?<?php echo $GLOBALS['wgStyleVersion'] ?>" />
    <!--[if lt IE 5.5000]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE50Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
    <!--[if IE 5.5000]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE55Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
    <!--[if IE 6]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE60Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
    <!--[if IE 7]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE70Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
    <!--[if lt IE 7]><script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath') ?>/common/IEFixes.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>
    <meta http-equiv="imagetoolbar" content="no" /><![endif]-->

    <?php print Skin::makeGlobalVariablesScript( $this->data ); ?>

    <script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/common/wikibits.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"><!-- wikibits js --></script>
    <?php    if($this->data['jsvarurl'  ]) { ?>
        <script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('jsvarurl'  ) ?>"><!-- site js --></script>
    <?php    } ?>
    <?php    if($this->data['pagecss'   ]) { ?>
        <style type="text/css"><?php $this->html('pagecss'   ) ?></style>
    <?php    }
        if($this->data['usercss'   ]) { ?>
        <style type="text/css"><?php $this->html('usercss'   ) ?></style>
    <?php    }
        if($this->data['userjs'    ]) { ?>
        <script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('userjs' ) ?>"></script>
    <?php    }
        if($this->data['userjsprev']) { ?>
        <script type="<?php $this->text('jsmimetype') ?>"><?php $this->html('userjsprev') ?></script>
    <?php    }
    if($this->data['trackbackhtml']) print $this->data['trackbackhtml']; ?>
    <!-- Head Scripts -->
    <?php $this->html('headscripts') ?>
</head>

<body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload'    ]) { ?>onload="<?php     $this->text('body_onload')     ?>"<?php } ?>
 class="mediawiki <?php $this->text('nsclass') ?> <?php $this->text('dir') ?> <?php $this->text('pageclass') ?>">

<div class="gumax-center" align="center">
<div id="gumax-rbox" align="left">
<div class="gumax_head_acc">
        <!-- Login -->
		<div id="gumax-p-corporate" style="width:auto; text-align:left; float: left;">
			<ul><li><a href="http://en.hetnieuwestemmen.nl">Back to Het Nieuwe Stemmen &raquo;</a></li></ul>
		</div>
        <div id="gumax-p-login">
            <ul>
              <?php $lastkey = end(array_keys($this->data['personal_urls'])) ?>
              <?php foreach($this->data['personal_urls'] as $key => $item) if($this->data['loggedin']==1) {
              ?><li id="gumax-pt-<?php echo Sanitizer::escapeId($key) ?>"><a href="<?php
               echo htmlspecialchars($item['href']) ?>"<?php
              if(!empty($item['class'])) { ?> class="<?php
               echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
               echo htmlspecialchars($item['text']) ?></a>
               <?php // if($key != $lastkey) echo "|" ?></li>
             <?php } ?>
            </ul>
        </div>
        <!-- end of Login -->
		
		<!-- Special and Login menu -->
        <div id="gumax-p-special">
            <ul>
              <?php $lastkey = end(array_keys($this->data['personal_urls'])) ?>
              <?php foreach($this->data['personal_urls'] as $key => $item) if($this->data['loggedin']==0) {
              ?><li id="gumax-pt-<?php echo Sanitizer::escapeId($key) ?>"><a href="<?php
               echo htmlspecialchars($item['href']) ?>"<?php
              if(!empty($item['class'])) { ?> class="<?php
               echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
               echo htmlspecialchars($item['text']) ?></a>
               <?php // if($key != $lastkey) echo "|" ?></li>
             <?php } ?>
            </ul>
        </div>
        <!-- end of Special and Login menu -->

</div>
<div class="gumax_box_acc">
<div class="gumax-rbroundbox"><div class="gumax-rbtop"><div><div></div></div></div>
<div class="gumax-rbcontentwrap"><div class="gumax-rbcontent">

    <div class="gumax">
    <!-- =================== gumax-page =================== -->
    <div id="gumax-page">
    <?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>

    <!-- ===== Header ===== -->
    <div id="gumax-header">
        <a name="top" id="contentTop"></a>

        <!-- Site Logo -->
        <div id="gumax-p-logo">
            <div id="p-logo">
            <a style="background-image: url(<?php $this->text('logopath') ?>);" <?php
                ?>href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href'])?>" <?php
                ?>title="<?php $this->msg('mainpage') ?>"></a>
            </div>
        </div>
		<script type="<?php $this->text('jsmimetype') ?>"> if (window.isMSIE55) fixalpha(); </script>
        <!-- end of Site Logo -->

        <!-- date time -->
        <!--
        <div id="gumax-p-date">
            <?php echo date("F j, Y, l, z") ?>
        </div>
        -->
        <!-- end of date time -->



        <!-- Search -->
        <div id="gumax-p-search" class="gumax-portlet">
            <div id="gumax-searchBody" class="gumax-pBody">
                <form action="<?php $this->text('searchaction') ?>" id="searchform"><div style="padding-right:10px;">
                    <input id="searchInput" name="search" type="text" <?php
                        if($this->haveMsg('accesskey-search')) {
                            ?>accesskey="<?php $this->msg('accesskey-search') ?>"<?php }
                        if( isset( $this->data['search'] ) ) {
                            ?> value="<?php $this->text('search') ?>"<?php } ?> />
                    <input type='submit' name="go" class="searchButton" id="searchGoButton" value="<?php $this->msg('searcharticle') ?>" />
                    <input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg('searchbutton') ?>" />
                </div></form>
            </div>
        </div> <!-- end of gumax-p-search DIV -->
        <!-- end of Search -->
    </div> <!-- end of header DIV -->
    <!-- ===== end of Header ===== -->

    <!-- Navigation Menu -->
    <div id="gumax-p-navigation-wrapper">
        <?php foreach ($this->data['sidebar'] as $bar => $cont) { ?>
            <div class='gumax-portlet' id='gumax-p-<?php echo Sanitizer::escapeId($bar) ?>'>
                <h5><?php $out = wfMsg( $bar ); if (wfEmptyMsg($bar, $out)) echo $bar; else echo $out; ?></h5>
                <div id="gumax-p-navigation">
                    <ul>
                        <?php foreach($cont as $key => $val) { ?>
                            <li id="<?php echo Sanitizer::escapeId($val['id']) ?>"<?php
                            if ( $val['active'] ) { ?> class="active" <?php }
                            ?>><a href="<?php echo htmlspecialchars($val['href']) ?>"><?php echo htmlspecialchars($val['text']) ?></a></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php } ?>
    </div>
    <!-- end of Navigation Menu -->

    <div id="main_menu_spacer"></div>

    <!-- ===== dynamic site logo ===== -->
    <?php $str1 = $this->data['pageclass']; $str2 = str_replace("page-", "", $str1); $pagename = strtolower($str2) ?> <!-- Get page name -->
    <?php
        $file_ext_collection = array('.gif', '.png', '.jpg');
        $found = false;
        foreach ($file_ext_collection as $file_ext)
        {
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->data['stylepath'] . '/' . $this->data['stylename'] . '/images/header/' . $pagename . $file_ext;
            if (file_exists($filename)) {
                $sitelogo = $this->data['stylepath'] . '/' . $this->data['stylename'] . '/images/header/' . $pagename . $file_ext;
                $found = true;
                break;
            }
        }
        if($found) {
            // $sitelogo = $this->data['stylepath'] . '/' . $this->data['stylename'] . '/images/header/' . 'default.jpg';
    ?>
        <div id="gumax-site-logo">
            <a style="background-image: url(<?php echo $sitelogo ?>);" <?php
                ?>href="<?php echo htmlspecialchars( $GLOBALS['wgTitle']->getLocalURL() )?>" <?php
                ?>title="<?php $this->data['displaytitle']!=""?$this->html('title'):$this->text('title') ?>"></a>
        </div>
    <?php } ?>
    <!-- ===== end of dynamic site picture ===== -->

    <!-- ===== Content body ===== -->
    <div id="gumax-content-body">
    <!-- Main Content -->
    <div id="content">
        <a name="top" id="top"></a>
        <?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
        <h1 class="firstHeading"><?php $this->data['displaytitle']!=""?$this->html('title'):$this->text('title') ?></h1>
        <div id= "bodyContent" class="gumax-bodyContent">
            <h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
            <div id="contentSub"><?php $this->html('subtitle') ?></div>
            <?php if($this->data['undelete']) { ?><div id="contentSub2"><?php $this->html('undelete') ?></div><?php } ?>
            <?php if($this->data['newtalk'] ) { ?><div class="usermessage"><?php $this->html('newtalk')  ?></div><?php } ?>
            <?php if($this->data['showjumplinks']) { ?><div id="jump-to-nav"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div><?php } ?>
            <!-- start content -->
            <?php $this->html('bodytext') ?>
            <?php if($this->data['catlinks']) { ?><div id="catlinks"><?php $this->html('catlinks') ?></div><?php } ?>
            <!-- end content -->
            <div class="visualClear"></div>
        </div>
    </div>
    <!-- end of Main Content -->
    </div>
    <!-- ===== end of Content body ===== -->

    <!-- ===== gumax-content-actions ===== -->
    <div id="gumax-content-actions">
        <ul>
            <?php $lastkey = end(array_keys($this->data['content_actions'])) ?>
            <?php foreach($this->data['content_actions'] as $key => $action) if($this->data['loggedin']==1) { ?>
               <li id="ca-<?php echo Sanitizer::escapeId($key) ?>" <?php
                   if($action['class']) { ?>class="<?php echo htmlspecialchars($action['class']) ?>"<?php } ?>
               ><a href="<?php echo htmlspecialchars($action['href']) ?>"><?php
                   echo htmlspecialchars($action['text']) ?></a> <?php
                   // if($key != $lastkey) echo "&#8226;" ?></li>
            <?php } ?>
        </ul>
    </div>
    <!-- ===== end of gumax-content-actions ===== -->

    </div> <!-- end of gumax-page DIV -->
    <!-- =================== end of gumax-page =================== -->

    <div id="footer_spacer"></div>

    <!-- =================== gumax-page-footer =================== -->
    <div id="gumax-page-footer">
        <!-- personal tools  -->
        <div id="gumax-personal-tools">
            <ul>
            <?php if($this->data['loggedin']==1) { ?>
              <?php if($this->data['notspecialpage']) { foreach( array( 'whatlinkshere', 'recentchangeslinked' ) as $special ) { ?>
              <li id="t-<?php echo $special?>"><a href="<?php
                echo htmlspecialchars($this->data['nav_urls'][$special]['href'])
                ?>"><?php echo $this->msg($special) ?></a></li>
              <?php } } ?><?php if($this->data['feeds']) { ?>
                  <li id="feedlinks"><?php foreach($this->data['feeds'] as $key => $feed) {
                  ?><span id="feed-<?php echo Sanitizer::escapeId($key) ?>"><a href="<?php
                  echo htmlspecialchars($feed['href']) ?>"><?php echo htmlspecialchars($feed['text'])?></a>&nbsp;</span><?php } ?></li> <?php } ?>
              <?php foreach( array('contributions', 'blockip', 'emailuser', 'upload', 'specialpages') as $special ) { ?> <?php
                  if($this->data['nav_urls'][$special]) {?><li id="t-<?php echo $special ?>"><a href="<?php
                  echo htmlspecialchars($this->data['nav_urls'][$special]['href'])
                  ?>"><?php $this->msg($special) ?></a> <?php
                     // if($special != 'specialpages') echo "|" ?> </li>
                <?php } ?>
              <?php }

                if(!empty($this->data['nav_urls']['permalink']['href'])) { ?>
                        <li id="t-permalink"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['permalink']['href'])
                        ?>"><?php $this->msg('permalink') ?></a></li><?php
                } elseif ($this->data['nav_urls']['permalink']['href'] === '') { ?>
                        <li id="t-ispermalink"><?php $this->msg('permalink') ?></li><?php
                }

                wfRunHooks( 'GuMaxTemplateToolboxEnd', array( &$this ) ); ?>

            <?php } ?>
            </ul>
        </div>
        <!-- end of personal tools  -->

        <!-- gumax-footer -->
        <div id="gumax-footer">
            <div id="gumax-f-message">
                <?php if($this->data['lastmod']) { ?><span id="f-lastmod"><?php    $this->html('lastmod')    ?></span>
                <?php } ?><?php if($this->data['viewcount']) { ?><span id="f-viewcount"><?php  $this->html('viewcount')  ?> </span>
                <?php } ?>
            </div>

            <ul id="gumax-f-list">
                <!--
                <?php
                        $footerlinks = array(
                            'numberofwatchingusers', 'credits',
                            'privacy', 'about', 'disclaimer', 'tagline',
                        );
                        foreach( $footerlinks as $aLink ) {
                            if( isset( $this->data[$aLink] ) && $this->data[$aLink] ) {
                ?>				<li id="<?php echo$aLink?>"><?php $this->html($aLink) ?></li>
                <?php 		}
                        }
                ?>
                -->
                <!--<li><a href="#">&copy; 2005 - 2007 PaulGu.com</a></li>
                <li><a href="http://mediawiki.org">Powered by MediaWiki</a></li>
                <li id="f-designby"><a href="http://paulgu.com">Design by Paul Gu</a></li>-->
            </ul>
        </div>  <!-- end of gumax-footer -->
        <?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
    </div>  <!-- end of gumax-page-footer -->
    <!-- =================== end of gumax-page-footer =================== -->
    </div>  <!-- class: gumax -->

</div></div>
<div class="gumax-rbbot"><div><div></div></div></div></div>
<div class="special_bottom_acc"><div style="float:right;"><a style="color:#fff;" href="http://en.hetnieuwestemmen.nl/modules/forms/contact">contact us</a> | <a href="<?php echo str_replace('$1', 'Terms', $this->data['articlepath']); ?>" style="color:#fff;">terms and conditions</a></div>&copy; 2009 - <a style="color:#fff;" href="http://en.hetnieuwestemmen.nl">Stichting Het Nieuwe Stemmen</a></div>
<div class="special_participation_acc"><a href="http://www.digitalepioniers.nl/"><img src="/w/skins/common/images/dplogo.gif" style="float:right;padding-left:10px;" /></a>THE DEVELOPMENT OF HNS.DEV WAS MADE POSSIBLE BY A CONTRIBUTION OF KNOWLEDGELAND THROUGH THE DIGITAL PIONEERS EPARTICIPATION ROUND REGULATION (WHICH WAS INITIATED BY THE MINISTRY OF THE INTERIOR AND KINGDOM RELATIONS). DIGITAL PIONIEERS GIVES FINANCIAL AND ORGANISATORIAL SUPPORT TO INNOVATIVE INTERNET INITIATIVES.</div>
</div>

<div class="gumax_foot_acc"></div>
</div>
</div> <!--  end of gumax-center -->

<?php $this->html('reporttime') ?>

</body></html>
<?php
	wfRestoreWarnings();
	} // end of execute() method
} // end of class
?>