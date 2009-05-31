ALTER TABLE wcf1_poll_option
ADD eQuizCorrectAnswer tinyint( 1 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE wcf1_poll_option_vote
ADD eQuizTimeAnswered tinyint( 1 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE wcf1_poll
ADD eQuizSeverity tinyint( 1 ) UNSIGNED NOT NULL DEFAULT '1';