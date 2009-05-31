{if $eQuiz|isset && $messageID == $eQuizPostID}
	{if $eQuiz->showResult()}
		<div class="poll border">
			<div class="container-3 pollQuestion">
				<div class="containerIcon"><img src="{@RELATIVE_WCF_DIR}icon/pollM.png" alt="" /></div>
				<div class="containerContent">
					<h4>{lang}wcf.equiz{/lang}</h4>
					<p>{$eQuiz->question}</p>
				</div>
			</div>
			{foreach from=$eQuiz->getSortedPollOptions() item=eQuizOption}
				<div class="container-{cycle values="1,2"}{if $eQuizOption->isChecked()} pollOptionChecked{/if}">
					<div class="containerIcon">
						<p class="smallFont">{@$eQuizOption->getPercent()|round:0}%</p>
					</div>
					<div class="containerIcon">
						<p class="smallFont"><img src="{@RELATIVE_WCF_DIR}icon/{if $eQuizOption->isCorrectAnswer()}successS{else}errorS{/if}.png" alt="" /></p>
					</div>
					<div class="containerContent">
						<p class="smallFont pollOption">{$eQuizOption->pollOption}{if $eQuizOption->votes} ({#$eQuizOption->votes}){/if}</p>
						<p class="smallFont">{$eQuizOption->getVotedUsers()}</p>
					</div>
				</div>
			{/foreach}
			<div class="container-3 pollResults">{lang}wcf.equiz.result{/lang}</div>
			{assign var="timeOutUsers" value=$eQuiz->getTimeOutUsers()}
			{if !$timeOutUsers|empty}<div class="container-3">{lang}wcf.equiz.timeOutUsers{/lang} {$timeOutUsers}</div>{/if}
			<div class="container-3">{lang}wcf.equiz.copyright{/lang}</div>
		</div>
	{else}
		<form method="post" id="eQuiz{$quizID}">
			<div class="poll border">
				<div class="container-3 pollQuestion">
					<div class="containerIcon"><img src="{@RELATIVE_WCF_DIR}icon/pollM.png" alt="" /></div>
					<div class="containerContent">
						<h4>{lang}wcf.equiz{/lang} &nbsp;&nbsp;&nbsp; {lang}wcf.equiz.countDown{/lang} <span id="eQuizTimer{$quizID}"></span> {lang}wcf.equiz.secounds{/lang}</h4>
						<p>{$eQuiz->question}</p>
						{if $eQuiz->choiceCount > 1 && $eQuiz->choiceCount < $eQuiz->getPollOptions()|count}
							<p class="smallFont">{lang}wcf.equiz.choiceCount{/lang}</p>
						{/if}
					</div>
				</div>
				{if $activeQuizID|isset && $activeQuizID == $quizID && $errorField == 'quizAnswerID'}
					<p class="innerError">
						{if $errorType == 'notValid'}{lang}wcf.equiz.answer.notValid{/lang}{/if}
					</p>
				{/if}

				{foreach from=$eQuiz->getPollOptions() item=eQuizOption}
					<div class="container-{cycle values="1,2"}">
						<div class="containerIcon">
							<input id="pollOption{@$eQuizOption->pollOptionID}" {if $eQuiz->choiceCount > 1}type="checkbox" name="quizAnswerID[]"{else}type="radio" name="quizAnswerID"{/if} value="{@$eQuizOption->pollOptionID}" {if $eQuizOption->isChecked()}checked="checked" {/if}/>
						</div>
						<div class="containerContent"><label for="pollOption{@$eQuizOption->pollOptionID}" class="smallFont">{$eQuizOption->pollOption}</label></div>
					</div>
				{/foreach}

				<div class="container-3 pollResults">
					<input type="submit" value="{lang}wcf.global.button.submit{/lang}" />
					{@SID_INPUT_TAG}
					<input type="hidden" name="answerQuiz" value="true" />
					<input type="hidden" name="quizID" value="{@$quizID}" />
				</div>

				<div class="container-3">{lang}wcf.equiz.copyright{/lang}</div>

				<script type="text/javascript" src="{@RELATIVE_WCF_DIR}js/EQuiz.class.js"></script>
				<script type="text/javascript">
					//<![CDATA[
					new EQuiz({$quizID}, {EQUIZ_TIMEOUT});
					//]]>
				</script>
				{if $eQuiz->choiceCount > 1}
					<script type="text/javascript">
						//<![CDATA[
						new Poll({@$quizID}, {@$eQuiz->choiceCount}, new Array({implode from=$eQuiz->getPollOptions() item=pollOption}{@$eQuizOption->pollOptionID}{/implode}));
						//]]>
					</script>
				{/if}
			</div>

		</form>
	{/if}
{/if}