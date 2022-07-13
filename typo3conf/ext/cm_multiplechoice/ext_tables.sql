CREATE TABLE tx_cmmultiplechoice_domain_model_questions (
	question varchar(255) NOT NULL DEFAULT '',
	questionanswer int(11) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_cmmultiplechoice_domain_model_answers (
	questions int(11) unsigned DEFAULT '0' NOT NULL,
	answer varchar(255) NOT NULL DEFAULT '',
	correct smallint(1) unsigned NOT NULL DEFAULT '0'
);
