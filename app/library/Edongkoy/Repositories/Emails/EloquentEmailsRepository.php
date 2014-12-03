<?php namespace Edongkoy\Repositories\Emails;

# app/library/Edongkoy/Repositories/Emails/EloquentEmailsRepository.php

use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Emails\Models\UserEmailConfirmation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;

class EloquentEmailsRepository implements EmailsRepositoryInterface {

	public function resendConfirmationEmail()
	{
		if (Request::ajax())
		{
			if (userStatus() == 3)
			{
				$user = UserEmailConfirmation::where('user_id', Auth::user()->id)->first();

				$data['username'] = auth_username();
				$data['token'] = $user->token;
				$data['firstname'] = Auth::user()->firstname;

				Mail::send('emails.confirmation', $data, function($message)
				{
					$email = Auth::user()->email;
					$name = Auth::user()->firstname.' '.Auth::user()->lastname;
					$message->to($email, $name)->subject(Lang::get('reminders.registration_verification'));
				});

				return status_ok(array('message' => stringReplace(Lang::get('reminders.resend_confirmation_email_success'), array(':email' => Auth::user()->email))));
			}

			return status_error(array('message' => Lang::get('global.unknown_error')));
		}

		return Redirect::to('/');
	}

	public function changeEmail()
	{
		if (Request::ajax())
		{
			if (Auth::check())
			{
				$validation = new \Services\Validators\ChangeEmail;

				if ($validation->passes())
				{
					$id = Auth::user()->id;

					$user = User::find($id);

					if (!$user) return status_error(array('message' => Lang::get('global.unknown_error')));

					if (Hash::check(Input::get('changeEmailPassword'), $user->password))
					{
						$email = Input::get('newEmail');

						$user = $user->update(array('email' => $email));

						$user = UserEmailConfirmation::where('user_id', $id)->first();

						$data['username'] = auth_username();
						$data['token'] = $user->token;
						$data['firstname'] = Auth::user()->firstname;	

						Mail::send('emails.confirmation', $data, function($message)
						{
							$email = Input::get('newEmail');
							$name = Auth::user()->firstname.' '.Auth::user()->lastname;
							$message->to($email, $name)->subject(Lang::get('reminders.registration_verification'));
						});

						return status_ok(array('message' => stringReplace(Lang::get('reminders.resend_confirmation_email_success'), array(':email' => $email))));
					}

					return status_error(array('message' => Lang::get('modal.invalid_password'), 'field_name' => 'changeEmailPassword'));
				}

				return $validation->jsonErrors();
			}

			return status_error(array('message' => Lang::get('global.unknown_error')));
		}

		return Redirect::to('/');
	}
}