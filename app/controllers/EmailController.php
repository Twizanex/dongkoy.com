<?php

use Edongkoy\Repositories\Emails\EmailsRepositoryInterface as emails;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class EmailController extends BaseController {

	protected $email;

	public function __construct(emails $email, globals $global)
	{
		$this->email = $email;
		$this->global = $global;
	}

	public function getResendConfirmationEmail()
	{
		return $this->email->resendConfirmationEmail();
	}

	public function postChangeEmail()
	{
		return $this->email->changeEmail();
	}
}