<?php
/**
 * EGroupware admin - change EGw category
 *
 * @link http://www.egroupware.org
 * @author Nathan Gray <ng@egroupware.org>
 * @package admin
 * @copyright (c) 2018 Nathan Gray <ng@egroupware.org>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

use EGroupware\Api;

/**
 * setup command: change EGw category
 *
 * @property-read string $app app whos category to change (Categories->app_name)
 * @property-read array $set category data to set, value of null or "" to remove
 * @property-read array $old old values to record
 */
class admin_cmd_category extends admin_cmd
{
	/**
	 * Allow to run this command via setup-cli
	 */
	//const SETUP_CLI_CALLABLE = true;	// need to check how to parse arguments

	/**
	 * Constructor
	 *
	 * @param array|string $data data array or app whos category to change
	 * @param array $set =null category data to set, value of null or "" to remove
	 * @param array $old =null old values to record
	 * @param array $other =null values for keys "requested", "requested_email", "comment", etc
	 */
	function __construct($data, array $set=null, array $old=null, $other=null)
	{

		if (!is_array($data))
		{
			$data = array(
				'app' => $data,
				'set' => $set,
				'old' => $old,
			)+(array)$other;
		}
		else if ($data['appname'])
		{
			$this->app = $data['appname'];
		}
		if(!$old && $old !== NULL && $set['id'])
		{
			$data['old'] = Api\Categories::read($set['id']);
		}
		//echo __CLASS__.'::__construct()'; _debug_array($domain);
		admin_cmd::__construct($data);
	}

	/**
	 * run the command: write the configuration to the database
	 *
	 * @param boolean $check_only =false only run the checks (and throw the exceptions), but not the command itself
	 * @return string success message
	 * @throws Exception(lang('Wrong credentials to access the header.inc.php file!'),2);
	 * @throws Exception('header.inc.php not found!');
	 */
	protected function exec($check_only=false)
	{
		$cats = new Api\Categories('',$this->app);
		if ($check_only)
		{
			return $cats->check_consistency4update($this->set);
		}

		// store the cat
		$this->cat_id = $this->set['id'] ? $cats->edit($this->set) : $cats->add($this->set);

		// Clean data for history
		$set =& $this->set;
		$old =& $this->old;
		unset($old['last_mod']);
		unset($set['old_parent'], $set['base_url'], $set['last_mod'], $set['all_cats'], $set['no_private']);
		foreach($set as $key => $value)
		{
			if(array_key_exists($key, $old) && $old[$key] == $value)
			{
				unset($set[$key]);
				unset($old[$key]);
			}
		}
		$this->set = $set;
		$this->old = $old;

		return lang('Category saved.');
	}

	/**
	 * Return a title / string representation for a given command, eg. to display it
	 *
	 * @return string
	 */
	function __tostring()
	{
		return lang('%1 category \'%2\' %3',
			lang($this->app),
			Api\Categories::id2name($this->cat_id),
			$this->old ? lang('edited') : lang('added')
		);
	}
}
