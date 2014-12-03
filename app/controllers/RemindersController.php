<?php

use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class RemindersController extends BaseController {

	public function __construct(globals $global)
	{
		$this->global = $global;
	}

	/**
	 * Display the password reminder view.
	 *
	 * @return Response
	 */
	public function getRemind()
	{
		$this->layout->title = Lang::get('reminders.page_title');
		$this->layout->content = View::make('password.remind');
	}

	/**
	 * Handle a POST request to remind a user of their password.
	 *
	 * @return Response
	 */
	public function postRemind()
	{
		switch ($response = Password::remind(Input::only('email'), function($message)
		{
			$message->subject(Lang::get('reminders.subject'));
		}))
		{
			case 'reminders.user':
				return Redirect::back()->with('error', Lang::get($response));

			case 'reminders.sent':
				return Redirect::back()->with('status', Lang::get($response));
		}
	}

	/**
	 * Display the password reset view for the given token.
	 *
	 * @param  string  $token
	 * @return Response
	 */
	public function getReset($token = null)
	{
		if (is_null($token)) App::abort(404);

		$this->layout->title = Lang::get('reminders.page_title');
		$this->layout->content = View::make('password.reset')->with('token', $token);
	}

	/**
	 * Handle a POST request to reset a user's password.
	 *
	 * @return Response
	 */
	public function postReset()
	{
		$credentials = Input::only(
			'email', 'password', 'password_confirmation', 'token'
		);

		$response = Password::reset($credentials, function($user, $password)
		{
			$user->password = Hash::make($password);

			$user->save();
		});

		switch ($response)
		{
			case 'reminders.password':
			case 'reminders.token':
			case 'reminders.user':
				return Redirect::back()->with('error', Lang::get($response));

			case 'reminders.reset':
				return Redirect::route('loginPage');
		}
	}

}