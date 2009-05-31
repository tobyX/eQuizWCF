/**
 * This JS-Class will display a countdown
 *
 * @package		com.toby.wcf.equiz
 * @author		Tobias Friebel
 * @copyright	2008 Tobias Friebel
 * @license		CC Namensnennung-Keine kommerzielle Nutzung-Keine Bearbeitung <http://creativecommons.org/licenses/by-nc-nd/2.0/de/>
 */
function EQuiz(quizID, timeToLive)
{
	this.quizID = quizID;
	this.timeToLive = timeToLive;
	this.currentTime = timeToLive;
	this.timer;
	var myself = this;

	this.init = function()
	{
		document.getElementById('eQuizTimer'+this.quizID).innerHTML = this.timeToLive;
		this.timer = window.setInterval(function callMethod() { myself.countDown(); }, 1000);
	}

	this.countDown = function()
	{
		this.currentTime--;
		document.getElementById('eQuizTimer'+this.quizID).innerHTML = this.currentTime;

		if (this.currentTime == 0)
		{
			window.clearInterval(this.timer);
			this.sendForm();
		}
	}

	this.sendForm = function()
	{
		var form = document.getElementById('eQuiz'+this.quizID);
		form.submit();
	}

	this.init();
}