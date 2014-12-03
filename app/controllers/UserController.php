<?php

use Edongkoy\Repositories\Users\UserRepositoryInterface as user;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class UserController extends BaseController {

	protected $user;	

	public function __construct(user $user, globals $global)
	{
		$this->beforeFilter('ajax', array(
			'only' => array(
						'writePageReview',
						'deleteReview'
					)
		));		
		$this->user = $user;
		$this->global = $global;
	}

	public function login()
	{
		if (Auth::check())
		{
			return Redirect::to('/');
		}	

		$this->layout->title = 'Login l '.Lang::get('global.site_name');
		$this->layout->js_var = array('facebook_app_id' => Config::get('app.facebook.appId'));
		$this->layout->js = array('signin.js', 'facebook.js');

		$data['need_login_message'] = '';

		if (Session::has('need_login_message'))
		{
			$data['need_login_message'] = Session::get('need_login_message');
			Session::forget('need_login_message');
		}

		if (Input::has('redirect'))
		{
			Session::put('redirect', Input::get('redirect'));
		}

		$this->layout->content = View::make('users.login')->with($data);
	}

	public function loginExec()
	{
		return $this->user->login();				
	}

	public function signup()
	{
		if (Auth::check())
		{
			return Redirect::to('/');
		}

		if (Input::has('redirect'))
		{
			Session::put('redirect', Input::get('redirect'));
		}	
		
		$this->layout->title = 'Signup l '.Lang::get('global.site_name');
		$this->layout->js_var = array('facebook_app_id' => Config::get('app.facebook.appId'));
		$this->layout->js = array('signup.js', 'facebook.js');
		$this->layout->content = View::make('users.signup');
	}

	public function signupExec()
	{
		return $this->user->register();
	}

	public function resetPassword($token = null)
	{
		if (Auth::check())
		{
			return Redirect::to('/');
		}
		$this->layout->title = Lang::get('reminders.page_title');
		$this->layout->js_var = array();
		$this->layout->js = array();
		$data['token'] = $token;
		$this->layout->content = View::make('users.reset_password', $data);
	}

	public function facebookAuth()
	{
		return $this->user->facebookAuth();
	}

	public function settings()
	{
		$this->layout->title = Lang::get('profile.general_account_settings');
		$this->layout->js_var = array();
		$this->layout->js = array('settings.js');
		
		$data['email_notifications'] = $this->user->emailNotificationsSettings();

		$this->layout->content = View::make('users.settings')->with($data);
	}

	public function notifications()
	{
		$this->layout->title = Lang::get('profile.notifications');

		$data['userNotifications'] = $this->global->userNotifications();

		$this->layout->content = View::make('users.notifications')->with($data);
	}

	public function changeName()
	{
		return $this->user->changeName();
	}

	public function changePassword()
	{
		return $this->user->changePassword();
	}

	public function addFriend()
	{
		return $this->user->addFriend();
	}

	public function unfriend()
	{
		return $this->user->unfriend();
	}

	public function updateUnreadFriendRequest()
	{
		return $this->user->updateUnreadFriendRequest();
	}

	public function acceptFriendRequest()
	{
		return $this->user->acceptFriendRequest();
	}

	public function deniedFriendRequest()
	{
		return $this->user->deniedFriendRequest();
	}

	public function updateUnreadNotifications()
	{
		return $this->user->updateUnreadNotifications();
	}

	public function jsonFriends()
	{
		return $this->user->jsonFriends();
	}

	public function likePage()
	{
		return $this->user->likePage();
	}

	public function unlikePage()
	{
		return $this->user->unlikePage();
	}

	public function deleteMessage()
	{
		return $this->user->deleteMessage();
	}

	public function updateUnreadMessages()
	{
		return $this->user->updateUnreadMessages();
	}

	public function changeEmailNotifications()
	{
		return $this->user->changeEmailNotifications();
	}

	public function writePageReview()
	{
		return $this->user->writePageReview();
	}

	public function deletePageReview()
	{
		return $this->user->deletePageReview();
	}

	public function deletePageReviewComment()
	{
		return $this->user->deletePageReviewComment();
	}	
}