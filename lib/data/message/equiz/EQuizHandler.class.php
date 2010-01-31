<?php
require_once (WCF_DIR . 'lib/data/message/equiz/EQuiz.class.php');
require_once (WCF_DIR . 'lib/data/message/equiz/EQuizOption.class.php');
require_once (WCF_DIR . 'lib/system/exception/UserInputException.class.php');
require_once (WCF_DIR . 'lib/system/exception/PermissionDeniedException.class.php');
require_once (WCF_DIR . 'lib/system/exception/IllegalLinkException.class.php');

/**
 * This class reads one quiz from database and handles the request on a specific quiz.
 *
 * @package		com.toby.wcf.equiz
 * @author		Tobias Friebel
 * @copyright	2008 Tobias Friebel
 * @license		CC Namensnennung-Keine kommerzielle Nutzung-Keine Bearbeitung <http://creativecommons.org/licenses/by-nc-nd/2.0/de/>
 */
class EQuizHandler
{
	protected $quiz = array ();
	protected $forwardScript = null;
	protected $messageType = '';

	/**
	 * Creates a new Polls object.
	 *
	 * @param	int			$quizID
	 * @param	boolean		$canAnswerQuiz	true, if the active user has permission to vote poll
	 * @param	string		$forwardScript
	 * @param	string		$messageType
	 * @param	int			$userID
	 */
	public function __construct($quizID, $canAnswerQuiz = true, $forwardScript = null, $messageType = 'eQuiz', $userID)
	{
		if ($userID == WCF :: getUser()->userID)
			$canAnswerQuiz = false;

		$this->quiz = new EQuiz($quizID, null, $canAnswerQuiz);

		// get quiz options
		$sql = "SELECT 		poll_option_vote.*,
							poll_option.*
				FROM 		wcf" . WCF_N . "_poll_option poll_option
				LEFT JOIN 	wcf" . WCF_N . "_poll_option_vote poll_option_vote
				ON 			(poll_option_vote.pollOptionID = poll_option.pollOptionID
							" . (!WCF :: getUser()->userID ? "AND poll_option_vote.ipAddress = '" . escapeString(WCF :: getSession()->ipAddress) . "'" : '') . "
							AND poll_option_vote.userID = " . WCF :: getUser()->userID . ")
				WHERE 		poll_option.pollID = " . $quizID . "
				ORDER BY 	poll_option.showOrder";

		$result = WCF :: getDB()->sendQuery($sql);

		while ($row = WCF :: getDB()->fetchArray($result))
		{
			$this->quiz->addOption(new EQuizOption($row, $this->quiz));
		}

		$this->messageType = $messageType;
		$this->forwardScript = $forwardScript;

		//answer this quiz?
		$this->handleRequest();
	}

	/**
	 * Returns the quiz if any
	 *
	 * @return	EQuiz
	 */
	public function getQuiz()
	{
		if (is_object($this->quiz))
			return $this->quiz;

		return null;
	}

	/**
	 * Counts the polls of the active page.
	 *
	 * @return	integer
	 */
	public function countPolls()
	{
		return (is_object($this->quiz) ? 1 : 0);
	}

	/**
	 * Handles the request on a poll.
	 */
	protected function handleRequest()
	{
		if (isset($_REQUEST['quizID']))
			$quizID = intval($_REQUEST['quizID']);
		else
			$quizID = 0;

		if (isset($_POST['answerQuiz']) && WCF :: getSession()->getVar('eQuizTime' . $this->quiz->pollID) !== null)
		{
			if (isset($_POST['quizAnswerID']))
			{
				if (is_array($_POST['quizAnswerID']))
					$quizAnswerID = ArrayUtil :: toIntegerArray($_POST['quizAnswerID']);
				else
					$quizAnswerID = intval($_POST['quizAnswerID']);
			}
			else
			{
				$quizAnswerID = 0;
			}

			// get poll
			if (!is_object($this->quiz))
			{
				throw new IllegalLinkException();
			}

			try
			{
				// error handling
				if (!$this->quiz->canVote())
				{
					WCF :: getSession()->unregister('eQuizTime' . $quizID);
					HeaderUtil :: redirect($this->forwardScript . (strstr($this->forwardScript, '?') ? '&' : '?') . 'postID=' . $this->quiz->messageID . SID_ARG_2ND_NOT_ENCODED . '#post' . $this->quiz->messageID);
					exit();
				}

				if (is_array($quizAnswerID) && $this->quiz->choiceCount < count($quizAnswerID))
				{
					WCF :: getSession()->unregister('eQuizTime' . $quizID);
					HeaderUtil :: redirect($this->forwardScript . (strstr($this->forwardScript, '?') ? '&' : '?') . 'postID=' . $this->quiz->messageID . SID_ARG_2ND_NOT_ENCODED . '#post' . $this->quiz->messageID);
					exit();
				}

				if (is_array($quizAnswerID))
				{
					foreach ($quizAnswerID as $answerID)
					{
						if ($answerID == 0)
							continue;

						$eQuizOption = $this->quiz->getPollOption($answerID);
						if ($eQuizOption === null)
						{
							throw new UserInputException('quizAnswerID', 'notValid');
						}
					}
				}
				else if ($quizAnswerID != 0)
				{
					$eQuizOption = $this->quiz->getPollOption($quizAnswerID);
					if ($eQuizOption === null)
					{
						throw new UserInputException('quizAnswerID', 'notValid');
					}
				}

				$this->quiz->addVote();
				$rightAnswers = 0;
				$wrongAnswers = 0;

				if (is_array($quizAnswerID))
				{
					foreach ($quizAnswerID as $answerID)
					{
						if ($answerID == 0)
							continue;

						$eQuizOption = $this->quiz->getPollOption($answerID);
						$eQuizOption->addVote();

						if ($eQuizOption->isCorrectAnswer())
						{
							$rightAnswers++;
						}
						else
						{
							$wrongAnswers++;
						}

						$sql = "UPDATE	wcf" . WCF_N . "_poll_option
								SET 	votes = votes + 1
								WHERE 	pollOptionID = " . $answerID;
						WCF :: getDB()->registerShutdownUpdate($sql);

						$sql = "INSERT INTO	wcf" . WCF_N . "_poll_option_vote
											(pollID, pollOptionID, userID, ipAddress, eQuizTimeAnswered)
								VALUES 		(" . $quizID . ",
											" . $answerID . ",
											" . WCF :: getUser()->userID . ",
											'" . escapeString(WCF :: getSession()->ipAddress) . "',
										 	" . (TIME_NOW - WCF :: getSession()->getVar('eQuizTime' . $quizID)) . ")";
						WCF :: getDB()->registerShutdownUpdate($sql);
					}
				}
				else if ($quizAnswerID != 0)
				{
					$eQuizOption = $this->quiz->getPollOption($quizAnswerID);
					$eQuizOption->addVote();

					if ($eQuizOption->isCorrectAnswer())
					{
						$rightAnswers++;
					}
					else
					{
						$wrongAnswers++;
					}

					$sql = "UPDATE	wcf" . WCF_N . "_poll_option
							SET 	votes = votes + 1
							WHERE 	pollOptionID = " . $quizAnswerID;
					WCF :: getDB()->registerShutdownUpdate($sql);

					$sql = "INSERT INTO	wcf" . WCF_N . "_poll_option_vote
										(pollID, pollOptionID, userID, ipAddress, eQuizTimeAnswered)
							VALUES 		(" . $quizID . ",
										" . $quizAnswerID . ",
										" . WCF :: getUser()->userID . ",
										'" . escapeString(WCF :: getSession()->ipAddress) . "',
										 " . (TIME_NOW - WCF :: getSession()->getVar('eQuizTime' . $quizID)) . ")";
					WCF :: getDB()->registerShutdownUpdate($sql);
				}

				$this->addPoints($rightAnswers, $wrongAnswers);

				WCF :: getSession()->unregister('eQuizTime' . $quizID);

				if ($this->forwardScript != null)
				{
					// forward to message page
					HeaderUtil :: redirect($this->forwardScript . (strstr($this->forwardScript, '?') ? '&' : '?') . 'postID=' . $this->quiz->messageID . SID_ARG_2ND_NOT_ENCODED . '#post' . $this->quiz->messageID);
					exit();
				}
				else
				{
					$this->quiz->setShowResult(true);
				}
			}
			catch (UserInputException $e)
			{
				WCF :: getTPL()->assign(array (
					'errorField' => $e->getField(),
					'errorType' => $e->getType(),
					'activeQuizID' => $quizID
				));
			}
		}
		elseif ($this->quiz->canVote() && WCF :: getSession()->getVar('eQuizTime' . $this->quiz->pollID) === null)
		{
			$sql = "UPDATE	wcf" . WCF_N . "_poll
					SET 	votes = votes + 1
					WHERE 	pollID = " . $this->quiz->pollID;
			WCF :: getDB()->registerShutdownUpdate($sql);

			$sql = "INSERT INTO	wcf" . WCF_N . "_poll_vote
								(pollID, userID, ipAddress)
					VALUES		(" . $this->quiz->pollID . ",
								" . WCF :: getUser()->userID . ",
								'" . escapeString(WCF :: getSession()->ipAddress) . "')";
			WCF :: getDB()->registerShutdownUpdate($sql);

			WCF :: getSession()->register('eQuizTime' . $this->quiz->pollID, TIME_NOW);

			$editor = WCF :: getUser()->getEditor();
			$editor->updateOptions(array (
				'eQuizAnswered' => ++$editor->eQuizAnswered
			));

			//Zuerst ziehen wir einmal die Negativpunkte ab, damit Timeouts und Abbrecher trotzdem die Negativpunkte bekommen
			$this->addPoints(-1, -1);
		}
	}

	/**
	 * will add/sub points to user
	 *
	 * @param int $rightAnswered
	 * @param int $wrongAnswered
	 */
	protected function addPoints($rightAnswered, $wrongAnswered)
	{
		if ($rightAnswered == -1 && $wrongAnswered == -1)
		{
			$points = -EQUIZ_POINTS * (EQUIZ_SEVERITY_MULTIPLY ? $this->quiz->eQuizSeverity : 1);
			
			if (defined('GUTHABEN_ENABLE_GLOBAL') && defined('EQUIZ_GUTHABEN_PLAY') && EQUIZ_GUTHABEN_PLAY > 0)
			{
				Guthaben :: sub(EQUIZ_GUTHABEN_PLAY, 'wbb.guthaben.log.equizplay', $this->quiz->question, 'index.php?page=Thread&postID='.$this->quiz->messageID, null, true);
			}
		}
		else
		{
			//Erst die abgezogenen Punkte wieder aufrechnen...
			$points = EQUIZ_POINTS * (EQUIZ_SEVERITY_MULTIPLY ? $this->quiz->eQuizSeverity : 1);

			$points += $rightAnswered * (EQUIZ_POINTS / $this->quiz->choiceCount) * (EQUIZ_SEVERITY_MULTIPLY ? $this->quiz->eQuizSeverity : 1);

			$points -= $wrongAnswered * (EQUIZ_POINTS / $this->quiz->choiceCount) * (EQUIZ_SEVERITY_MULTIPLY ? $this->quiz->eQuizSeverity : 1);
			
			if (defined('GUTHABEN_ENABLE_GLOBAL') && defined('EQUIZ_GUTHABEN_WIN') && EQUIZ_GUTHABEN_WIN > 0)
			{
				$guthaben = $rightAnswered * (EQUIZ_GUTHABEN_WIN / $this->quiz->choiceCount) * (EQUIZ_SEVERITY_MULTIPLY ? $this->quiz->eQuizSeverity : 1);
				$guthaben -= $wrongAnswered * (EQUIZ_GUTHABEN_WIN / $this->quiz->choiceCount) * (EQUIZ_SEVERITY_MULTIPLY ? $this->quiz->eQuizSeverity : 1);
				
				Guthaben :: add($guthaben, 'wbb.guthaben.log.equizwin', $this->quiz->question, 'index.php?page=Thread&postID='.$this->quiz->messageID);
			}
		}

		$editor = WCF :: getUser()->getEditor();
		$editor->updateOptions(array (
			'eQuizPoints' => round($editor->eQuizPoints + $points, 2)
		));
	}
}
?>
