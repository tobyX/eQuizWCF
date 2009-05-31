<?php
require_once (WCF_DIR . 'lib/data/message/equiz/EQuizOption.class.php');
require_once (WCF_DIR . 'lib/data/message/poll/Poll.class.php');

/**
 * This class represents an eQuiz in a message.
 *
 * @package		com.toby.wcf.equiz
 * @author		Tobias Friebel
 * @copyright	2008 Tobias Friebel
 * @license		CC Namensnennung-Keine kommerzielle Nutzung-Keine Bearbeitung <http://creativecommons.org/licenses/by-nc-nd/2.0/de/>
 */
class EQuiz extends Poll
{
	/**
	 * Returns the sorted answers of this quiz.
	 *
	 * @return	array
	 */
	public function getSortedPollOptions()
	{
		$pollOptions = $this->getPollOptions();

		if ($this->sortByResult)
		{
			uasort($pollOptions, array('EQuizOption', 'compareResult'));
		}

		return $pollOptions;
	}

	/**
	 * Returns true, if the active can't answer this question.
	 *
	 * @return	boolean
	 */
	public function showResult()
	{
		return !$this->canVote();
	}

	/**
	 * Returns true, if the active user is allowed answer this quiz.
	 *
	 * @return	boolean		true, if the active user is allowed to answer this quiz
	 */
	public function canVote()
	{
		$eQuizSessionTime = WCF :: getSession()->getVar('eQuizTime'.$this->pollID);

		if ($eQuizSessionTime === null)
		{
			return $this->canVotePoll && !$this->voted;
		}
		else
		{
			return $this->canVotePoll && $eQuizSessionTime + EQUIZ_TIMEOUT >= TIME_NOW;
		}
	}

	/**
	 * Adds a new answer to this quiz.
	 *
	 * @param	EQuizOption	$option		new option
	 */
	public function addOption(EQuizOption $option)
	{
		$this->pollOptions[$option->pollOptionID] = $option;
	}

	/**
	 * get all users which answered, but did timeout or such
	 *
	 * @return string
	 */
	public function getTimeOutUsers()
	{
		$sql = "SELECT 		user.username
				FROM 		wcf" . WCF_N . "_poll_vote poll_vote
				JOIN		wcf" . WCF_N . "_user user ON (user.userID = poll_vote.userID)
				WHERE 		poll_vote.pollID = " . $this->pollID ." AND
							poll_vote.userID NOT IN (SELECT poll_option_vote.userID FROM
							wcf" . WCF_N . "_poll_option_vote poll_option_vote
							WHERE poll_option_vote.pollID = " . $this->pollID .")";

		$result = WCF :: getDB()->sendQuery($sql);

		$users = '';
		while ($row = WCF :: getDB()->fetchArray($result))
		{
			if ($users != '')
				$users .= ', ';

			$users .= $row['username'];
		}

		return $users;
	}
}
?>