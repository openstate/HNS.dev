<?php /* Smarty version 2.6.19, created on 2009-07-10 15:53:20
         compiled from /home/ralf/hns-dev-fo/templates/frontofficeTemplate.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'sprintf', '/home/ralf/hns-dev-fo/templates/frontofficeTemplate.html', 8, false),array('modifier', 'strtolower', '/home/ralf/hns-dev-fo/templates/frontofficeTemplate.html', 11, false),array('modifier', 'htmlspecialchars', '/home/ralf/hns-dev-fo/templates/frontofficeTemplate.html', 36, false),array('modifier', 'urlencode', '/home/ralf/hns-dev-fo/templates/frontofficeTemplate.html', 42, false),)), $this); ?>

	<div id="globalWrapper">
		<div id="column-content">
	<div id="content">
		<a name="top" id="top"></a>
				<h1 id="firstHeading" class="firstHeading"><?php echo $this->_tpl_vars['title']; ?>
</h1>
		<div id="bodyContent">
			<h3 id="siteSub"><?php echo ((is_array($_tmp='Uit %s')) ? $this->_run_mod_handler('sprintf', true, $_tmp, 'HNS.dev') : sprintf($_tmp, 'HNS.dev')); ?>
</h3>

			<div id="contentSub"></div>
									<div id="jump-to-nav">Ga naar: <a href="#column-one"><?php echo ((is_array($_tmp='Navigatie')) ? $this->_run_mod_handler('strtolower', true, $_tmp) : strtolower($_tmp)); ?>
</a>, <a href="#searchInput"><?php echo ((is_array($_tmp='Zoeken')) ? $this->_run_mod_handler('strtolower', true, $_tmp) : strtolower($_tmp)); ?>
</a></div>			<!-- start content -->
<?php echo $this->_tpl_vars['content']; ?>

						<!-- end content -->
						<div class="visualClear"></div>
		</div>
	</div>

		</div>
		<div id="column-one">
	<div id="p-cactions" class="portlet">
		<h5>Aspecten/acties</h5>
		<div class="pBody">
			<ul>
	
				 <li id="ca-nstab-main" class="selected"><a title="Inhoudspagina bekijken [c]" accesskey="c">Pagina</a></li>
			</ul>

		</div>
	</div>

	<div class="portlet" id="p-personal">
		<h5>Persoonlijke instellingen</h5>
		<div class="pBody">
			<ul>
			<?php if ($this->_tpl_vars['user']->user_id): ?>
				<li id="pt-userpage"><a href="/wiki/Gebruiker:<?php echo ((is_array($_tmp=$this->_tpl_vars['user']->user_name)) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
" title="Uw gebruikerspagina [.]" accesskey="." class="?new?"><?php echo ((is_array($_tmp=$this->_tpl_vars['user']->user_name)) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
</a></li>
				<li id="pt-mytalk"><a href="/wiki/Overleg_gebruiker:<?php echo ((is_array($_tmp=$this->_tpl_vars['user']->user_name)) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
" title="Uw overlegpagina [n]" accesskey="n" class="?new?">Mijn overleg</a></li>

				<li id="pt-preferences"><a href="/wiki/Speciaal:Voorkeuren" title="Mijn voorkeuren">Mijn voorkeuren</a></li>
				<li id="pt-watchlist"><a href="/wiki/Speciaal:Volglijst" title="Pagina [l]" accesskey="l">Mijn volglijst</a></li>
				<li id="pt-mycontris"><a href="/wiki/Speciaal:Bijdragen/<?php echo ((is_array($_tmp=$this->_tpl_vars['user']->user_name)) ? $this->_run_mod_handler('htmlspecialchars', true, $_tmp) : htmlspecialchars($_tmp)); ?>
" title="Overzicht van uw bijdragen [y]" accesskey="y">My contributions</a></li>
				<li id="pt-logout"><a href="/w/index.php?title=Speciaal:Afmelden&amp;returnto=Redirect:<?php echo ((is_array($_tmp=$_SERVER['REQUEST_URI'])) ? $this->_run_mod_handler('urlencode', true, $_tmp) : urlencode($_tmp)); ?>
" title="Afmelden">Afmelden</a></li>
			<?php else: ?>
				<li id="pt-anonlogin"><a href="/w/index.php?title=Speciaal:Aanmelden&amp;returnto=Redirect:<?php echo ((is_array($_tmp=$_SERVER['REQUEST_URI'])) ? $this->_run_mod_handler('urlencode', true, $_tmp) : urlencode($_tmp)); ?>
" title="U wordt van harte uitgenodigd om u aan te melden als gebruiker, maar dit is niet verplicht [o]" accesskey="o">Aanmelden / registreren</a></li>
			<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="portlet" id="p-logo">
		<a style="background-image: url(/w/skins/common/images/logo.png);" href="/wiki/Hoofdpagina" title="Hoofdpaginalogo [z]" accesskey="z"></a>
	</div>
	<script type="text/javascript"> if (window.isMSIE55) fixalpha(); </script>
	<div class='generated-sidebar portlet' id='p-navigation'>
		<h5>Navigatie</h5>
		<div class='pBody'>
			<ul>

				<li id="n-mainpage-description"><a href="/wiki/Hoofdpagina">Hoofdpagina</a></li>
				<li id="n-portal"><a href="/wiki/HNS.dev:Gebruikersportaal" title="Informatie over het project: wie, wat, hoe en waarom">Gebruikersportaal</a></li>
				<li id="n-currentevents"><a href="/wiki/HNS.dev:In het nieuws" title="Achtergrondinformatie over actuele zaken">In het nieuws</a></li>
				<li id="n-recentchanges"><a href="/wiki/Speciaal:RecenteWijzigingen" title="De lijst van recente wijzigingen in deze wiki. [r]" accesskey="r">Recente wijzigingen</a></li>
				<li id="n-randompage"><a href="/wiki/Speciaal:Willekeurig" title="Een willekeurige pagina bekijken [x]" accesskey="x">Willekeurige pagina</a></li>
				<li id="n-help"><a href="/wiki/Help:Inhoud" title="Hulpinformatie over deze wiki">Help</a></li>

			</ul>
		</div>
	</div>
	<div id="p-search" class="portlet">
		<h5><label for="searchInput">Zoeken</label></h5>
		<div id="searchBody" class="pBody">
			<form action="/w/index.php" id="searchform"><div>
				<input type='hidden' name="title" value="Speciaal:Search"/>

				<input id="searchInput" name="search" type="text" title="<?php echo ((is_array($_tmp='%s doorzoeken')) ? $this->_run_mod_handler('sprintf', true, $_tmp, 'HNS.dev') : sprintf($_tmp, 'HNS.dev')); ?>
 [f]" accesskey="f" value="" />
				<input type='submit' name="go" class="searchButton" id="searchGoButton"	value="OK" title="Naar een pagina met deze naam gaan als die bestaat" />&nbsp;
				<input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="Zoeken" title="De pagina&#039;s voor deze tekst zoeken" />
			</div></form>
		</div>
	</div>
	<div class="portlet" id="p-tb">
		<h5>Hulpmiddelen</h5>

		<div class="pBody">
			<ul>
<li id="t-upload"><a href="/wiki/Speciaal:Uploaden" title="Bestanden uploaden [u]" accesskey="u">Bestand uploaden</a></li>
<li id="t-specialpages"><a href="/wiki/Speciaal:SpecialePaginas" title="Lijst van alle speciale pagina&#039;s [q]" accesskey="q">Speciale pagina's</a></li>

		</div>
	</div>
		</div><!-- end of the left (by default at least) column -->
			<div class="visualClear"></div>
			<div id="footer">
			<ul id="f-list">
					<li id="privacy"><a href="/wiki/HNS.dev:Privacybeleid" title="HNS.dev:Privacybeleid">Privacybeleid</a></li>
					<li id="about"><a href="/wiki/HNS.dev:Info" title="HNS.dev:Info"><?php echo ((is_array($_tmp='Over %s')) ? $this->_run_mod_handler('sprintf', true, $_tmp, 'HNS.dev') : sprintf($_tmp, 'HNS.dev')); ?>
</a></li>
					<li id="disclaimer"><a href="/wiki/HNS.dev:Algemeen voorbehoud" title="HNS.dev:Algemeen voorbehoud">Voorbehoud</a></li>
			</ul>
		</div>
</div>

		<script type="text/javascript">if (window.runOnloadHook) runOnloadHook();</script>
