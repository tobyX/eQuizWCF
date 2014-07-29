<?php
require_once (WCF_DIR . 'lib/data/message/poll/PollEditor.class.php');
require_once (WCF_DIR . 'lib/system/exception/UserInputException.class.php');

/**
 * EQuizEditor provides functions to create and edit a quiz.
 *
 * @package		com.toby.wcf.equiz
 * @author		Tobias Friebel
 * @copyright		2014 Tobias Friebel
 * @license		CC Namensnennung-Keine kommerzielle Nutzung-Keine Bearbeitung <http://creativecommons.org/licenses/by-nc-nd/2.0/de/>
 */
class EQuizEditor extends PollEditor
{
	protected $eQuizAnswers = array ();
	protected $errorField = '';
	protected $errorType = '';

	/**
	 * Creates a new EQuizEditor object.
	 *
	 * @param	integer		$eQuizID
	 * @param	integer		$messageID
	 * @param	string		$messageType
	 */
	public function __construct($eQuizID = 0, $messageID = 0, $messageType = 'eQuiz')
	{
		$this->data['pollID'] = $eQuizID;
		$this->data['messageID'] = $messageID;
		$this->data['messageType'] = $messageType;
		$this->data['timeout'] = 0;
		$this->data['choiceCount'] = 1;
		$this->data['votesNotChangeable'] = 1;

		if ($messageID != 0 || $eQuizID != 0)
		{
			// get poll
			$sql = "SELECT	*
					FROM 	wcf" . WCF_N . "_poll
					WHERE	" . ($messageID != 0 ? "
							messageID = " . $messageID . "
							AND messageType = '" . escapeString($messageType) . "'
							AND packageID = " . PACKAGE_ID : "pollID = " . $eQuizID . "
							AND packageID = " . PACKAGE_ID);
			$row = WCF :: getDB()->getFirstRow($sql);

			if (isset($row['pollID']))
			{
				$this->handleData($row);
				$this->canVotePoll = true;

				// get poll options
				$sql = "SELECT		*
						FROM 		wcf" . WCF_N . "_poll_option
						WHERE 		pollID = " . $this->pollID . "
						ORDER BY 	showOrder";

				$result = WCF :: getDB()->sendQuery($sql);

				$i = 0;
				while ($row = WCF :: getDB()->fetchArray($result))
				{
					$eQuizOption = new EQuizOption($row, $this);
					$this->pollOptionsArray[] = $eQuizOption->pollOption;
					$this->addOption($eQuizOption);

					$i++;
					if ($eQuizOption->isCorrectAnswer())
						$this->eQuizAnswers[] = $i;
				}
			}
		}

		$this->assign();
	}

	/**
	 * Reads the given parameters.
	 */
	public function readParams()
	{
		if (isset($_POST['eQuizAnswers']))
		{
			$eQuizAnswers = StringUtil :: trim($_POST['eQuizAnswers']);
			$this->eQuizAnswers = array_unique(ArrayUtil :: trim(explode(",", $eQuizAnswers)));
		}

		if (isset($_POST['eQuizSeverity']))
			$this->eQuizSeverity = intval($_POST['eQuizSeverity']);

		//chooseable answers will always base on count of correct answers
		$this->data['choiceCount'] = count($this->eQuizAnswers);

		parent :: readParams();
	}

	/**
	 * Checks the given parameters.
	 *
	 * @return	boolean
	 */
	public function checkParams()
	{
		if (!$this->question)
		{
			return;
		}

		if (count($this->pollOptionsArray) < 2)
		{
			$this->errorField = 'eQuizPossibleAnswers';
			$this->errorType = 'notEnoughQuestions';
		}

		if ($this->choiceCount < 1)
		{
			$this->errorField = 'eQuizAnswers';
			$this->errorType = 'noAnswers';
		}

		if ($this->choiceCount > count($this->pollOptionsArray) - 1)
		{
			$this->errorField = 'eQuizAnswers';
			$this->errorType = 'tooMuch';
		}

		foreach ($this->eQuizAnswers as $answer)
		{
			if ($answer < 1 || $answer > count($this->pollOptionsArray))
			{
				$this->errorField = 'eQuizAnswers';
				$this->errorType = 'invalid';
			}
		}

		//anticheat
		if ($this->eQuizSeverity != 0 && (EQUIZ_SEVERITY_MULTIPLY && ($this->eQuizSeverity < 1 || $this->eQuizSeverity > 5)))
		{
			$this->errorField = 'eQuizSeverity';
			$this->errorType = 'invalid';
		}

		if (!empty($this->errorField))
		{
			$this->valid = false;
			throw new UserInputException($this->errorField, $this->errorType);
		}

		$this->valid = true;
	}

	/**
	 * Saves the poll in database.
	 */
	public function save()
	{
		if ($this->valid)
		{
			if ($this->pollID)
			{
				// update quiz
				$this->update();
			}
			else
			{
				// create new quiz
				$this->pollID = $this->createQuiz($this->messageID, $this->messageType, $this->question, $this->pollOptionsArray, $this->choiceCount, $this->eQuizAnswers, $this->eQuizSeverity);
				$editor = WCF :: getUser()->getEditor();
				$editor->updateOptions(array (
					'eQuizCreated' => ++$editor->eQuizCreated,
					'eQuizPoints' => $editor->eQuizPoints + EQUIZ_CREATED
				));

				if (defined('GUTHABEN_ENABLE_GLOBAL') && defined('EQUIZ_GUTHABEN_CREATE') && EQUIZ_GUTHABEN_CREATE > 0)
				{
					Guthaben :: add(EQUIZ_GUTHABEN_CREATE, 'wbb.guthaben.log.equizcreated', $this->question, 'index.php?page=Thread&postID='.$this->messageID);
				}
			}
		}
		else if ($this->pollID)
		{
			$this->delete();
		}
	}

	/**
	 * Updates the data of an existing poll.
	 */
	public function update()
	{
		$sql = "UPDATE 	wcf" . WCF_N . "_poll
				SET		question = '" . escapeString($this->question) . "',
						choiceCount = " . $this->choiceCount . ",
						eQuizSeverity = " . $this->eQuizSeverity . ",
						endTime = 0,
						votesNotChangeable = 1,
						sortByResult = 0
				WHERE 	pollID = " . $this->pollID;
		WCF :: getDB()->registerShutdownUpdate($sql);

		// search for unchanged or moved options
		$showOrder = 0;
		foreach ($this->pollOptionsArray as $outerKey => $newPollOption)
		{
			foreach ($this->pollOptions as $innerKey => $pollOption)
			{
				if ($pollOption->pollOption == $newPollOption)
				{
					if (in_array($showOrder + 1, $this->eQuizAnswers))
						$eQuizAnswer = 1;
					else
						$eQuizAnswer = 0;

					if ($showOrder != $pollOption->showOrder || $eQuizAnswer != $pollOption->eQuizCorrectAnswer)
					{
						// position of this option changed
						$sql = "UPDATE	wcf" . WCF_N . "_poll_option
								SET 	showOrder = " . $showOrder . ",
										eQuizCorrectAnswer = " . $eQuizAnswer . "
								WHERE 	pollOptionID = " . $pollOption->pollOptionID;
						WCF :: getDB()->registerShutdownUpdate($sql);
					}

					unset($this->pollOptions[$innerKey]);
					$this->pollOptionsArray[$outerKey] = '';
					break;
				}
			}

			$showOrder++;
		}

		// search for renamed or added options
		$showOrder = 0;
		foreach ($this->pollOptionsArray as $outerKey => $newPollOption)
		{
			if (!empty($newPollOption))
			{
				$renamed = false;
				foreach ($this->pollOptions as $innerKey => $pollOption)
				{
					if (in_array($showOrder + 1, $this->eQuizAnswers))
						$eQuizAnswer = 1;
					else
						$eQuizAnswer = 0;

					if ($pollOption->showOrder == $showOrder || $eQuizAnswer != $pollOption->eQuizCorrectAnswer)
					{
						// option probably renamed
						$sql = "UPDATE	wcf" . WCF_N . "_poll_option
								SET 	pollOption = '" . escapeString($newPollOption) . "',
										eQuizCorrectAnswer = " . $eQuizAnswer . "
								WHERE 	pollOptionID = " . $pollOption->pollOptionID;
						WCF :: getDB()->registerShutdownUpdate($sql);

						unset($this->pollOptions[$innerKey]);
						$this->pollOptionsArray[$outerKey] = '';
						$renamed = true;
						break;
					}
				}

				// option probably added
				if (!$renamed)
				{
					if (in_array($showOrder + 1, $this->eQuizAnswers))
						$eQuizAnswer = 1;
					else
						$eQuizAnswer = 0;

					$sql = "INSERT INTO 	wcf" . WCF_N . "_poll_option
											(pollID, pollOption, showOrder, eQuizCorrectAnswer)
							VALUES		(" . $this->pollID . ",
										'" . escapeString($newPollOption) . "',
										" . $showOrder . ",
										" . $eQuizAnswer . ")";
					WCF :: getDB()->registerShutdownUpdate($sql);
				}
			}

			$showOrder++;
		}

		// delete removed options
		foreach ($this->pollOptions as $pollOption)
		{
			$sql = "DELETE FROM	wcf" . WCF_N . "_poll_option
					WHERE		pollOptionID = " . $pollOption->pollOptionID;
			WCF :: getDB()->registerShutdownUpdate($sql);

			$sql = "DELETE FROM	wcf" . WCF_N . "_poll_option_vote
					WHERE 		pollOptionID = " . $pollOption->pollOptionID;
			WCF :: getDB()->registerShutdownUpdate($sql);
		}
	}

	/**
	 * Assigns the data of this poll to the template engine.
	 */
	protected function assign()
	{
		WCF :: getTPL()->assign(array (
			'pollID' => $this->pollID,
			'pollQuestion' => $this->question,
			'pollOptions' => implode("\n", $this->pollOptionsArray),
			'eQuizAnswers' => implode(",", $this->eQuizAnswers),
			'eQuizSeverity' => $this->eQuizSeverity,
			'errorField' => $this->errorField,
			'errorType' => $this->errorType
		));
	}

	/**
	 * Copies all sql data of the polls with the given message ids.
	 *
	 * @param	string		$messageIDs
	 * @param	array		$messageMapping
	 * @param	string		$messageType
	 *
	 * @return	array		$pollMapping
	 */
	public static function copyAll($messageIDs, &$messageMapping, $messageType = 'eQuiz')
	{
		if (empty($messageIDs))
			return array ();

		// copy 'poll' data
		$pollMapping = array ();
		$pollIDs = '';
		$sql = "SELECT	*
				FROM	wcf" . WCF_N . "_poll
				WHERE 	messageID IN (" . $messageIDs . ")
						AND messageType = '" . escapeString($messageType) . "'
						AND packageID = " . PACKAGE_ID;
		$result = WCF :: getDB()->sendQuery($sql);
		while ($row = WCF :: getDB()->fetchArray($result))
		{
			if (!empty($pollIDs))
				$pollIDs .= ',';
			$pollIDs .= $row['pollID'];

			$newPollID = self :: insert($row['question'], array (
				'packageID' => PACKAGE_ID,
				'messageID' => $messageMapping[$row['messageID']],
				'messageType' => $row['messageType'],
				'time' => $row['time'],
				'choiceCount' => $row['choiceCount'],
				'votes' => $row['votes'],
				'votesNotChangeable' => 1,
				'sortByResult' => 0,
				'eQuizSeverity' => $row['eQuizSeverity']
			));

			$pollMapping[$row['pollID']] = $newPollID;
		}

		if (empty($pollIDs))
			return array ();

		// copy 'poll_option' data
		$pollOptionMapping = array ();
		$sql = "SELECT	*
				FROM 	wcf" . WCF_N . "_poll_option
				WHERE 	pollID IN (" . $pollIDs . ")";
		$result = WCF :: getDB()->sendQuery($sql);
		while ($row = WCF :: getDB()->fetchArray($result))
		{
			$sql = "INSERT INTO	wcf" . WCF_N . "_poll_option
								(pollID, pollOption, votes, showOrder, eQuizCorrectAnswer)
					VALUES		(" . $pollMapping[$row['pollID']] . ",
								'" . escapeString($row['pollOption']) . "',
								" . $row['votes'] . ",
								" . $row['showOrder'] . ",
								" . $row['eQuizCorrectAnswer'] . ")";
			WCF :: getDB()->registerShutdownUpdate($sql);

			$pollOptionMapping[$row['pollOptionID']] = WCF :: getDB()->getInsertID();
		}

		// copy 'poll_option_vote' data
		$sql = "SELECT	*
				FROM 	wcf" . WCF_N . "_poll_option_vote
				WHERE 	pollID IN (" . $pollIDs . ")";
		$result = WCF :: getDB()->sendQuery($sql);
		$inserts = '';
		while ($row = WCF :: getDB()->fetchArray($result))
		{
			if (!empty($inserts))
				$inserts .= ',';
			$inserts .= "(" . $pollMapping[$row['pollID']] . ", " . $pollOptionMapping[$row['pollOptionID']] . ", " . $row['userID'] . ", '" . escapeString($row['ipAddress']) . "')";
		}

		if (!empty($inserts))
		{
			$sql = "INSERT INTO	wcf" . WCF_N . "_poll_option_vote
								(pollID, pollOptionID, userID, ipAddress)
					VALUES		" . $inserts;
			WCF :: getDB()->registerShutdownUpdate($sql);
		}

		// copy 'poll_vote' data
		$sql = "SELECT	*
				FROM 	wcf" . WCF_N . "_poll_vote
				WHERE	pollID IN (" . $pollIDs . ")";
		$result = WCF :: getDB()->sendQuery($sql);
		$inserts = '';
		while ($row = WCF :: getDB()->fetchArray($result))
		{
			if (!empty($inserts))
				$inserts .= ',';
			$inserts .= "(" . $pollMapping[$row['pollID']] . ", " . $row['isChangeable'] . ", " . $row['userID'] . ", '" . escapeString($row['ipAddress']) . "')";
		}

		if (!empty($inserts))
		{
			$sql = "INSERT INTO	wcf" . WCF_N . "_poll_vote
								(pollID, isChangeable, userID, ipAddress)
					VALUES		" . $inserts;
			WCF :: getDB()->registerShutdownUpdate($sql);
		}

		return $pollMapping;
	}

	/**
	 * Creates a new quiz.
	 *
	 * @param	integer		$messageID
	 * @param	string		$messageType
	 * @param	string		$pollQuestion
	 * @param	array		$pollOptions
	 * @param	integer		$choiceCount
	 * @param 	array		$eQuizAnswers
	 * @param 	integer		$eQuizSeverity
	 * @param	boolean		$isPublic
	 *
	 * @return	integer		$pollID
	 */
	public function createQuiz($messageID, $messageType, $pollQuestion, $pollOptions, $choiceCount, $eQuizAnswers, $eQuizSeverity)
	{
		// insert poll
		$pollID = self :: insert($pollQuestion, array (
			'packageID' => PACKAGE_ID,
			'messageID' => $messageID,
			'messageType' => $messageType,
			'time' => TIME_NOW,
			'choiceCount' => $choiceCount,
			'votesNotChangeable' => 1,
			'sortByResult' => 0,
			'eQuizSeverity' => $eQuizSeverity
		));

		// insert poll options
		$showOrder = 0;
		$inserts = '';
		foreach ($pollOptions as $option)
		{
			if (!empty($inserts))
				$inserts .= ',';

			if (in_array($showOrder + 1, $eQuizAnswers))
				$eQuizAnswer = 1;
			else
				$eQuizAnswer = 0;

			$inserts .= "(" . $pollID . ", '" . escapeString($option) . "', " . $showOrder . ", " . $eQuizAnswer . ")";

			$showOrder++;
		}

		if (!empty($inserts))
		{
			$sql = "INSERT INTO		wcf" . WCF_N . "_poll_option
									(pollID, pollOption, showOrder, eQuizCorrectAnswer)
					VALUES			" . $inserts;
			WCF :: getDB()->registerShutdownUpdate($sql);
		}

		return $pollID;
	}
}
?>