<?php namespace Edongkoy\Repositories\Emails;

# app/library/Edongkoy/Repositories/Emails/EmailsRepositoryInterface.php

interface EmailsRepositoryInterface {

	public function resendConfirmationEmail();
	public function changeEmail();
}