<div id="eQuizEdit">
	<fieldset class="noJavaScript">
		<legend class="noJavaScript">{lang}wcf.equiz{/lang}</legend>
		<div class="formElement{if $errorField == 'eQuizQuestion'} formError{/if}">
			<div class="formFieldLabel">
				<label for="pollQuestion">{lang}wcf.equiz.question{/lang}</label>
			</div>
			<div class="formField">
				<input type="text" class="inputText" name="pollQuestion" id="pollQuestion" value="{$pollQuestion}" />
				{if $errorField == 'eQuizQuestion'}
					<p class="innerError">
						{if $errorType == 'empty'}{lang}wcf.global.error.empty{/lang}{/if}
					</p>
				{/if}
			</div>
			<div class="formFieldDesc">
				<p>{lang}wcf.equiz.question.description{/lang}</p>
			</div>
		</div>

		<div class="formElement{if $errorField == 'eQuizPossibleAnswers'} formError{/if}">
			<div class="formFieldLabel">
				<label for="pollOptions">{lang}wcf.equiz.answers{/lang}</label>
			</div>
			<div class="formField">
				<textarea name="pollOptions" id="pollOptions" rows="5" cols="20">{$pollOptions}</textarea>
				{if $errorField == 'eQuizPossibleAnswers'}
					<p class="innerError">
						{if $errorType == 'notEnoughQuestions'}{lang}wcf.equiz.answers.notEnoughQuestions{/lang}{/if}
					</p>
				{/if}
			</div>
			<div class="formFieldDesc">
				<p>{lang}wcf.equiz.answers.description{/lang}</p>
			</div>
		</div>

		<div class="formElement{if $errorField == 'eQuizAnswers'} formError{/if}">
			<div class="formFieldLabel">
				<label for="pollAnswers">{lang}wcf.equiz.correctAnswers{/lang}</label>
			</div>
			<div class="formField{if $errorField == 'eQuizAnswers'} formError{/if}">
				<input type="text" class="inputText" name="eQuizAnswers" value="{$eQuizAnswers}" id="eQuizAnswers" />
				{if $errorField == 'eQuizAnswers'}
					<p class="innerError">
						{if $errorType == 'noAnswers'}{lang}wcf.equiz.correctAnswers.noAnswers{/lang}{/if}
						{if $errorType == 'invalid'}{lang}wcf.equiz.correctAnswers.invalid{/lang}{/if}
						{if $errorType == 'tooMuch'}{lang}wcf.equiz.correctAnswers.tooMuch{/lang}{/if}
					</p>
				{/if}
			</div>
			<div class="formFieldDesc">
				<p>{lang}wcf.equiz.correctAnswers.description{/lang}</p>
			</div>
		</div>
		{if EQUIZ_SEVERITY_MULTIPLY}
		<div class="formElement{if $errorField == 'eQuizSeverity'} formError{/if}">
			<div class="formFieldLabel">
				<label for="eQuizSeverity">{lang}wcf.equiz.severity{/lang}</label>
			</div>
			<div class="formField">
				<select name="eQuizSeverity">
					<option {if $eQuizSeverity == 1}selected="selected" {/if}value="1">{lang}wcf.equiz.severity.1{/lang}</option>
					<option {if $eQuizSeverity == 2}selected="selected" {/if}value="2">{lang}wcf.equiz.severity.2{/lang}</option>
					<option {if $eQuizSeverity == 3}selected="selected" {/if}value="3">{lang}wcf.equiz.severity.3{/lang}</option>
					<option {if $eQuizSeverity == 4}selected="selected" {/if}value="4">{lang}wcf.equiz.severity.4{/lang}</option>
					<option {if $eQuizSeverity == 5}selected="selected" {/if}value="5">{lang}wcf.equiz.severity.5{/lang}</option>
				</select>
			</div>
			<div class="formFieldDesc">
				<p>{lang}wcf.equiz.severity.description{/lang}</p>
			</div>
		</div>
		{/if}
	</fieldset>
</div>

<script type="text/javascript">
	//<![CDATA[
	tabbedPane.addTab('eQuizEdit', {if $errorField == 'eQuizQuestion' || $errorField == 'eQuizPossibleAnswers' || $errorField == 'eQuizAnswers'}true{else}false{/if});
	//]]>
</script>