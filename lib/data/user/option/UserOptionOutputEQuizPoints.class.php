<?php
require_once (WCF_DIR . 'lib/data/user/option/UserOptionOutput.class.php');
require_once (WCF_DIR . 'lib/data/user/User.class.php');

/**
 * This class will clean db-output
 *
 * @package		com.toby.wcf.equiz
 * @author		Tobias Friebel
 * @copyright	2008 Tobias Friebel
 * @license		CC Namensnennung-Keine kommerzielle Nutzung-Keine Bearbeitung <http://creativecommons.org/licenses/by-nc-nd/2.0/de/>
 */
class UserOptionOutputEQuizPoints implements UserOptionOutput
{

	/**
	 * @see UserOptionOutput::getShortOutput()
	 */
	public function getShortOutput(User $user, $optionData, $value)
	{
		return $this->getOutput($user, $optionData, $value);
	}

	/**
	 * @see UserOptionOutput::getMediumOutput()
	 */
	public function getMediumOutput(User $user, $optionData, $value)
	{
		return $this->getOutput($user, $optionData, $value);
	}

	/**
	 * @see UserOptionOutput::getOutput()
	 */
	public function getOutput(User $user, $optionData, $value)
	{
		if (empty($value) || $value == '0')
			return '0,00';

		return number_format($value, 2, ',', ' ');;
	}
}
?>