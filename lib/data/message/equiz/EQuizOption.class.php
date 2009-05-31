<?php
require_once (WCF_DIR . 'lib/data/message/equiz/EQuiz.class.php');
require_once (WCF_DIR . 'lib/data/message/poll/PollOption.class.php');

/**
 * This class represents an answer of a quiz.
 *
 * @package		com.toby.wcf.equiz
 * @author		Tobias Friebel
 * @copyright	2008 Tobias Friebel
 * @license		CC Namensnennung-Keine kommerzielle Nutzung-Keine Bearbeitung <http://creativecommons.org/licenses/by-nc-nd/2.0/de/>
 */
class EQuizOption extends PollOption
{
	/**
	 * Is this answer correct?
	 *
	 * @return bool
	 */
	public function isCorrectAnswer()
	{
		return $this->eQuizCorrectAnswer;
	}

	/**
	 * get all users which choose this answer
	 *
	 * @return string
	 */
	public function getVotedUsers()
	{
		$sql = "SELECT 		user.username, poll_option_vote.eQuizTimeAnswered
				FROM 		wcf" . WCF_N . "_poll_option_vote poll_option_vote
				JOIN		wcf" . WCF_N . "_user user ON (user.userID = poll_option_vote.userID)
				WHERE 		poll_option_vote.pollOptionID = " . $this->pollOptionID;

		$result = WCF :: getDB()->sendQuery($sql);

		$users = '';
		while ($row = WCF :: getDB()->fetchArray($result))
		{
			if ($users != '')
				$users .= ', ';

			$users .= $row['username'].'('.$row['eQuizTimeAnswered'].')';
		}

		return $users;
	}
}
?>