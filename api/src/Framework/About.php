<?php
/**
 * EGroupware: About informations
 *
 * rewrite of the old PHPLib based about page
 * it now uses eTemplate2
 *
 * LICENSE:  GPL.
 *
 * @package     api
 * @subpackage  about
 * @author      Sebastian Ebling <hudeldudel@php.net>
 * @author      Ralf Becker <RalfBecker@outdoor-training.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @link        http://www.egroupware.org
 * @version     SVN: $Id$
 */

namespace EGroupware\Api\Framework;

use EGroupware\Api;
use EGroupware\Api\Etemplate;

/**
 * Shows informations about eGroupWare
 *  - general information
 *  - installed applications
 *  - installed templates
 *  - installed languages
 *
 * Usage:
 * <code>
 *  $aboutPage = new about();
 * </code>
 * The constuctor will do all
 *
 * There is no typical so, bo ui structure, this class is all in one
 *
 * @since		1.4
 */
class About
{
	/**
	 * Methods callable by menuaction GET parameter
	 *
	 * @var array
	 */
	var $public_functions = array(
		'index' => true,
		'detail' => true,
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		Api\Translation::add_app('admin');
	}


	/**
	 * output of list view
	 * collects data and shows a tabbed view that lists
	 *  - general informations
	 *  - installed applications
	 *  - installed templates
	 *  - installed languages
	 */
	function index()
	{
		$text_content = str_replace('GPLLINK',self::$knownLicenses['GPL'][0],'
<p><b>EGroupware is a <a href="GPLLINK" title="read more about open source and the GPL" target="_blank">free</a>
enterprise ready groupware software</b> for your network. It enables you to manage contacts, appointments, todos
and many more for your whole business.</p>
<p><b>EGroupware is a groupware server.</b> It comes with a native web-interface which allowes to access your data
from any platform all over the planet. Moreover you also have the choice to access the EGroupware server with
your favorite groupware client (Kontact, Evolution, Outlook, iCal, Lightning) and also with your mobile or PDA
via eSync.</p>
<p><b>EGroupware is international.</b> At the time, it supports more than
<a href="http://community.egroupware.org/languages" target="_blank">25 languages</a> including rtl support.</p>
<p><b>EGroupware is platform independent.</b> The server runs on Linux, Mac, Windows and many more other operating systems.
On the client side, all you need is a internet browser such as Chrome, Firefox, Safari or Internet Explorer
and many more.</p>
<p><b>EGroupware is developed by <a href="http://www.stylite.de/" target="_blank">Stylite AG</a></b> with contributions
from community developers.</p>
<br />
<p><b>For more information visit the <a href="http://www.egroupware.org" target="_blank">EGroupware Website</a></b></p>');

		// get informations about the applications
		$apps = array();
		foreach (array_keys(isset($GLOBALS['egw_info']['user']['apps']['admin']) ?
			$GLOBALS['egw_info']['apps'] : $GLOBALS['egw_info']['user']['apps']) as $app)
		{
			$apps[] = $this->_getParsedAppInfo($app);
		}
		usort($apps, function($a, $b)
		{
			return strcasecmp($a['title'], $b['title']);
		});
		array_unshift($apps, false);	// first empty row for eTemplate

		// get informations about the templates
		$templates = array();
		$templates[] = ''; // first empty row for eTemplate
		foreach($GLOBALS['egw']->framework->list_templates(true) as $info)
		{
			$templates[] = $this->_getParsedTemplateInfo($info);
		}

		// get informations about installed languages
		$translations = array();
		$translations[] = ''; // first empty row for eTemplate
		foreach(Api\Translation::get_installed_langs() as $translation => $translationinfo) {
			$translations[] = array(
				'langName'	=>	$translationinfo.' ('.$translation.')'
			);
		}

		// fill content array for eTemplate
		$changelog = null;
		$content = array(
			'apiVersion'	=> '<p>'.lang('EGroupware version').' <b>'.Api\Framework::api_version($changelog).'</b></p>',
			'applications'	=> $apps,
			'templates'		=> $templates,
			'translations'	=> $translations,
			'text_content'  => $text_content,
			'changelog'     => file_exists($changelog) ? file_get_contents($changelog) : 'not available',
		);

		$tmpl = new Etemplate('api.about.index');
		$tmpl->exec('api.'.__CLASS__.'.index', $content);
	}

	/**
	 * parse template informations from setup.inc.php file
	 *
	 * @param   array  $info	template template info
	 * @return  array   html formated informations about author(s),
	 *                  maintainer(s), version, license of the
	 *                  given application
	 *
	 * @access  private
	 * @since   1.4
	 */
	function _getParsedTemplateInfo($info)
	{
		// define the return array
		$info['image'] = file_exists(EGW_SERVER_ROOT.'/'.$info['icon']) ? $GLOBALS['egw_info']['server']['webserver_url'].'/'.$info['icon'] : Api\Image::find('thisdoesnotexist',array('navbar','nonav'));
		$info['author'] = $this->_getHtmlPersonalInfo($info, 'author');
		$info['maintainer'] = $this->_getHtmlPersonalInfo($info, 'maintainer');

		return $this->_linkLicense($info);
	}

	/**
	 * parse application informations from setup.inc.php file
	 *
	 * @param	string	$app	application name
	 * @return	array	html formated informations about author(s),
	 *					maintainer(s), version, license of the
	 *					given application
	 *
	 * @access  private
	 * @since   1.4
	 */
	function _getParsedAppInfo($app)
	{
		// we read all setup files once, as no every app has it's own file
		static $setup_info=null;
		if (is_null($setup_info))
		{
			foreach(array_keys($GLOBALS['egw_info']['apps']) as $_app)
			{
				if (file_exists($file = EGW_INCLUDE_ROOT.'/'.$_app.'/setup/setup.inc.php'))
				{
					include($file);
				}
			}
		}
		$app_info  = array_merge($GLOBALS['egw_info']['apps'][$app],$setup_info[$app]);

		// define the return array
		$icon_app = isset($app_info['icon_app']) ? $app_info['icon_app'] : $app;
		$icon = isset($app_info['icon']) ? $app_info['icon'] : 'navbar';
		$ret = $app_info+array(
			'app'           => $app,
			'title'         => lang(!empty($app_info['title']) ? $app_info['title'] : $app),
			'image'			=> Api\Image::find($icon_app, $icon) ? $icon_app.'/'.$app : 'api/nonav',
			'author'		=> '',
			'maintainer'	=> '',
			'version'		=> $app_info['version'],
			'license'		=> '',
			'description'	=> '',
			'note'			=> ''
		);

		if (isset($setup_info[$app]))
		{
			$ret['author'] = $this->_getHtmlPersonalInfo($setup_info[$app], 'author');
			$ret['maintainer'] = $this->_getHtmlPersonalInfo($setup_info[$app], 'maintainer');
			if ($app_info['version'] != $setup_info[$app]['version']) $ret['version'] .= ' ('.$setup_info[$app]['version'].')';
			$ret['license'] = $setup_info[$app]['license'];
			$ret['description'] = $setup_info[$app]['description'];
			$ret['note'] = $setup_info[$app]['note'];
		}

		return $this->_linkLicense($ret);
	}


	/**
	 * helper to parse author and maintainer info from setup_info array
	 *
	 * @param	array	$setup_info	setup_info[$app] array
	 *								($GLOBALS['egw_info']['template'][$template] array for template informations)
	 * @param	string	$f			'author' or 'maintainer', default='author'
	 * @return	string	html formated informations about author/maintainer
	 *
	 * @access  private
	 * @since   1.4
	 */
	function _getHtmlPersonalInfo($setup_info, $f = 'author')
	{
		$authors = array();
			// get the author(s)
			if ($setup_info[$f]) {
				// author is set
				if (!is_array($setup_info[$f])) {
					// author is no array
					$authors[0]['name'] = $setup_info[$f];
					if ($setup_info[$f.'_email']) {
						$authors[0]['email'] = $setup_info[$f.'_email'];
					}
					if ($setup_info[$f.'_url']) {
						$authors[0]['url'] = $setup_info[$f.'_url'];
					}

				} else {
					// author is array
					if ($setup_info[$f]['name']) {
						// only one author
						$authors[0]['name'] = $setup_info[$f]['name'];
						if ($setup_info[$f]['email']) {
							$authors[0]['email'] = $setup_info[$f]['email'];
						}
						if ($setup_info[$f]['url']) {
							$authors[0]['url'] = $setup_info[$f]['url'];
						}
					} else {
						// may be more authors
						foreach (array_keys($setup_info[$f]) as $number) {
							if ($setup_info[$f][$number]['name']) {
									$authors[$number]['name'] = $setup_info[$f][$number]['name'];
							}
							if ($setup_info[$f][$number]['email']) {
									$authors[$number]['email'] = $setup_info[$f][$number]['email'];
							}
							if ($setup_info[$f][$number]['url']) {
									$authors[$number]['url'] = $setup_info[$f][$number]['url'];
							}
						}
					}
				}
			}

		// html format authors
		$s = '';
		foreach ($authors as $author) {
			if ($s != '') {
					$s .= '<br />';
			}
			$s .= lang('name').': '.$author['name'];
			if ($author['email']) {
					$s .= '<br />'.lang('email').': <a href="mailto:'.$author['email'].'">'.$author['email'].'</a>';
			}
			if ($author['url']) {
					$s .= '<br />'.lang('url').': <a href="'.$author['url'].'" target="_blank">'.$author['url'].'</a>';
			}
		}
		return $s;
	}

	static public $knownLicenses = array(
		'GPL'	=> array('http://opensource.org/licenses/gpl-license.php','GNU General Public License version 2.0 or (at your option) any later version'),
		'GPL2'	=> array('http://opensource.org/licenses/gpl-2.0.php','GNU General Public License version 2.0'),
		'GPL3'	=> array('http://opensource.org/licenses/gpl-3.0.php','GNU General Public License version 3.0'),
		'LGPL'	=> array('http://opensource.org/licenses/lgpl-2.1.php','GNU Lesser General Public License, version 2.1'),
		'LGPL3'	=> array('http://opensource.org/licenses/lgpl-3.0.php','GNU Lesser General Public License, version 3.0'),
		'PHP'   => array('http://opensource.org/licenses/php.php','PHP License'),
	);

	/**
	 * surround license string with link to license if it is known
	 *
	 * @param   array  $info	containing value for key "license" with either string of license-name of array with keys "name" and "url"
	 * @return  array  with added values for keys "license_url", "license_title"
	 *
	 * @access  private
	 * @since   1.4
	 */
	function _linkLicense($info)
	{
		$license = $info['license'];
		$name = is_array($license) ? $license['name'] : $license;
		$url = is_array($license) && isset($license['url']) ? $license['url'] : '';
		$title = is_array($license) && isset($license['title']) ? $license['title'] : '';

		if (isset(self::$knownLicenses[strtoupper($name)]))
		{
			if (empty($url)) $url = self::$knownLicenses[$name=strtoupper($name)][0];
			if (empty($title)) $title = self::$knownLicenses[$name=strtoupper($name)][1];
		}

		return array(
			'license' => $name ? $name : 'none',
			'license_url' => $url,
			'license_title' => $title,
		)+$info;
	}
}
