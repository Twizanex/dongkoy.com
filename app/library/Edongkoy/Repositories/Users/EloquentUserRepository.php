<?php namespace Edongkoy\Repositories\Users;

# app/library/Edongkoy/Repositories/Users/Repositories/EloquentUserRepository.php

use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Users\Models\Usernames;
use Edongkoy\Repositories\Users\Models\RoleUser;
use Edongkoy\Repositories\Users\Models\UserStatus;
use Edongkoy\Repositories\Users\Models\UserSocialIds;
use Edongkoy\Repositories\Users\Models\UserInfoVisibility;
use Edongkoy\Repositories\Users\Models\UserSocialNetworks;
use Edongkoy\Repositories\Users\Models\FacebookTokens;
use Edongkoy\Repositories\Users\Models\UserFriends;
use Edongkoy\Repositories\Users\Models\UserNotifications;
use Edongkoy\Repositories\Users\Models\UserPagesLikes;
use Edongkoy\Repositories\Users\Models\UserConversation;
use Edongkoy\Repositories\Users\Models\Message;
use Edongkoy\Repositories\Users\Models\UserUnreadMessage;
use Edongkoy\Repositories\Users\Models\UserActivity;
use Edongkoy\Repositories\Users\Models\UserEmailNotifications;
use Edongkoy\Repositories\Page\Models\Page;
use Edongkoy\Repositories\Page\Models\PageReviews;
use Edongkoy\Repositories\Page\Models\PageReviewComments;
use Edongkoy\Repositories\Image\Models\Albums;
use Edongkoy\Repositories\Image\Models\Images;
use Edongkoy\Repositories\Image\Models\ProfileImages;
use Edongkoy\Repositories\Image\Models\CoverImages;
use Edongkoy\Repositories\Emails\Models\UserEmailConfirmation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Form;

class EloquentUserRepository implements UserRepositoryInterface {

	public function login()
	{
		$validation = new \Services\Validators\Login;
		
		if ($validation->passes())
		{
			if (Auth::attempt(array('email' => Input::get('email_username'), 'password' => Input::get('login_password'))))
			{
				$user = User::find(Auth::user()->id);
				$user->touch();
				$username = Auth::user()->username->username;

				if (Request::ajax())
				{
					$modal = false;
					$action = URL::route('showProfile', $username);

					if (Session::has('request_url')) $action = Session::get('request_url');
					if (Session::has('activate_modal')) $modal = true;

					return status_ok(array(
						'url' => $action,
						'modal' => $modal
					));
				}

				if (Session::has('redirect'))
				{
					$redirect = Session::get('redirect');
					Session::forget('redirect');
					return Redirect::to($redirect);
				}
				else
				{
					return Redirect::intended($username);
				}
			}
			else
			{
				if (Request::ajax())
				{
					return Response::json($validation->ajaxMessage(Lang::get('global.error_status'), Lang::get('login.incorrect_email_password')));
				}

				Input::flash();
				return Redirect::to('login')
					->with('flash_errors', Lang::get('login.incorrect_email_password'));
			}
		}

		if (Request::ajax())
		{
			return $validation->jsonErrors();
		}

		return Redirect::back()->withInput()->withErrors($validation->errors);
	}

	public function register()
	{
		$validation = new \Services\Validators\Signup;

		if ($validation->passes())
		{			
			$data['username'] = strtolower(Input::get('username'));

			if (in_array($data['username'], Config::get('custom.usernames')))
			{
				if (Request::ajax())
				{
					return status_error(array('message' => Lang::get('signup.username_taken'), 'field_name' => 'username'));
				}
				else
				{
					return Redirect::back()->withInput()->with('username_not_allowed', Lang::get('signup.username_taken'));
				}
			}

			$email = Input::get('email');
			$data['firstname'] = Input::get('firstname');
			$data['lastname'] = Input::get('lastname');
			$data['token'] = str_random(50);

			$user = User::create(array(
				'firstname' => $data['firstname'],
				'lastname' => $data['lastname'],
				'password' => Hash::make(Input::get('password')),
				'email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']
			));
			
			$user_id = $user->id;

			Usernames::create(array('user_id' => $user_id, 'username' => $data['username'], 'user_type' => 'user'));

			RoleUser::create(array('user_id' => $user_id, 'role_id' => 1));

			UserStatus::create(array('user_id' => $user_id, 'status_id' => 3));

			UserEmailConfirmation::create(array('user_id' => $user_id, 'token' => $data['token']));

			UserActivity::create(array('user_id' => $user_id, 'activities_id' => 1));

			$user = User::find($user_id);

			if ($user)
			{
				Auth::login($user);

				Mail::send('emails.confirmation', $data, function($message)
				{
					$firstname = Input::get('firstname');
					$lastname = Input::get('lastname');
					$email = Input::get('email');

					$message->to($email, $firstname.' '.$lastname)->subject(Lang::get('reminders.registration_verification'));
				});

				$action = route('showProfile', Auth::user()->username->username);

				if (Session::has('redirect'))
				{
					$redirect = Session::get('redirect');
					Session::forget('redirect');
					$action = $redirect;
				}

				if (Request::ajax())
				{
					return status_ok(array('url' => $action));
				}

				return Redirect::to($action);
			}

			return Redirect::to('login');
		}

		if (Request::ajax())
		{
			return $validation->jsonErrors();
		}

		return Redirect::back()->withInput()->withErrors($validation->errors);
	}

	public function facebookAuth()
	{
		if (Request::ajax())
		{
			$action = '';
			$modal = false;

			if (Session::has('request_url'))
			{
				$action = Session::get('request_url');				
			}

			if (Session::has('activate_modal'))
			{
				$modal = true;				
			}
			
			if (Input::has('reset'))
			{
				Session::forget('second');
				Session::forget('email');
				Session::forget('cover');
				Session::forget('facebook_id');
				Session::forget('first_name');
				Session::forget('last_name');
				Session::forget('username');
				Session::forget('link');
				Session::forget('no_email_username_conflict');
				Session::forget('username_conflict');
				Session::forget('no_email_username_ok');
				Session::forget('new_password');
				Session::forget('unconfirmed_email');
				Session::forget('user_id');
				Session::forget('new_password_username_conflict');

				return status_ok(array('message' => 'reset'));
			}
			
			if (!Session::has('second'))
			{
				$fb_token = Input::get('access_token');
				$facebook = new \Facebook(Config::get('app.facebook'));
				$facebook->setAccessToken($fb_token);
				$facebook_id = $facebook->getUser();				

				if ($facebook_id)
				{
					//$user_profile = $facebook->api('/me', 'GET');
					//return status_ok(array('message' => $user_profile));

					$token = FacebookTokens::where('fb_id', $facebook_id)->first();

					if($token)
					{
						$token->token = $fb_token;
						$token->save();
					}
					else
					{
						FacebookTokens::create(array('fb_id' => $facebook_id, 'token' => $fb_token));
					}

					$exist = UserSocialIds::select(DB::raw('user_id'))->where('facebook_id', $facebook_id)->first();					

					if ($exist)
					{
						$user = User::find($exist->user_id);

						Auth::login($user);						

						if (Session::has('redirect'))
						{
							$redirect = Session::get('redirect');
							Session::forget('redirect');
							$action = $redirect;
						}
						else
						{
							$action = $action == '' ? route('showProfile', $user->username->username) : $action;
						}

						return status_ok(array(
							'message' => 'login',
							'url' => $action,
							'modal' => $modal
						));
					}
				
					$user_profile = $facebook->api('/me', 'GET');
					$images = $facebook->api('/me?fields=cover');					

					Session::put('second', true);

					if(isset($user_profile['email'])) Session::put('email', $user_profile['email']);
					if(isset($images['cover'])) Session::put('cover', $images['cover']['source']);

					Session::put('facebook_id', $facebook_id);
					Session::put('first_name', $user_profile['first_name']);
					Session::put('last_name', $user_profile['last_name']);
					Session::put('username', $user_profile['username']);
					Session::put('link', $user_profile['link']);				
				
					if (!isset($user_profile['email']))
					{
						$username = Usernames::where('username', Session::get('username'))->first();

						if ($username)
						{
							Session::put('no_email_username_conflict', true);
							Session::put('username_conflict', true);
							return status_error(array(
									'message' => 'no-email-username-conflict',
									'url' => action('ModalsController@getFacebookNoEmailUsernameConflict')
								));
						}
						
						Session::put('no_email_username_ok', true);
						return status_error(array(
							'message' => 'no-email-username-ok',
							'url' => action('ModalsController@getFacebookNoEmailUsernameOk')
						));
					}

					$user = User::where('email', $user_profile['email'])->first();

					if ($user)
					{
						foreach ($user->status as $value)
						{
							$user_status = $value->id;
						}

						if ($user_status == 3)
						{
							Session::put('new_password', true);
							Session::put('unconfirmed_email', true);
							Session::put('user_id', $user->id);
							return status_error(array(
								'message' => 'new-password',
								'url' => action('ModalsController@getFacebookNewPassword')
							));
						}

						$insert = UserSocialIds::create(array('user_id' => $user->id, 'facebook_id' => $facebook_id));

						if (!$insert) return server_error();

						$query = UserSocialNetworks::create(array('user_id' => $user->id, 'facebook' => Session::get('link')));

						UserInfoVisibility::create(array('user_id' => $user->id, 'user_social_networks_id' => $query->id));

						Auth::login($user);

						if (Session::has('redirect'))
						{
							$redirect = Session::get('redirect');
							Session::forget('redirect');
							$action = $redirect;
						}
						else
						{
							$action = $action == '' ? route('showProfile', $user->username->username) : $action;
						}

						return status_ok(array(
							'message' => 'login',
							'url' => $action,
							'modal' => $modal
						));
					}

					$username = Usernames::where('username', Session::get('username'))->first();

					if (in_array(Session::get('username'), Config::get('custom.usernames')))
					{
						$username = true;
					}

					if ($username)
					{
						Session::put('new_password_username_conflict', true);
						return status_error(array(
							'message' => 'new-password-username-conflict',
							'url' => action('ModalsController@getFacebookNewPasswordUsernameConflict')
						));
					}

					Session::put('new_password', true);
					return status_error(array(
						'message' => 'new-password',
						'url' => action('ModalsController@getFacebookNewPassword')
					));
				}

				return server_error();
			}

			if (Session::has('no_email_username_ok'))
			{
				$validation = new \Services\Validators\SocialNetworkSignupNoEmailUsernameOk;

				if (!$validation->passes()) return $validation->jsonErrors();

				Session::put('email', Input::get('email'));
				$password = \Hash::make(Input::get('password'));
				$user_status = 3;
			}

			if (Session::has('no_email_username_conflict'))
			{
				$validation = new \Services\Validators\SocialNetworkSignupNoEmailUsernameConflict;

				if (!$validation->passes()) return $validation->jsonErrors();

				Session::put('email', Input::get('email'));
				Session::put('username', Input::get('username'));
				$password = \Hash::make(Input::get('password'));
				$user_status = 3;
			}

			if (Session::has('new_password'))
			{
				$validation = new \Services\Validators\SocialNetworkSignupNewPassword;

				if (!$validation->passes()) return $validation->jsonErrors();

				$password = \Hash::make(Input::get('password'));

				if (Session::has('unconfirmed_email'))
				{
					$user = User::find(Session::get('user_id'));

					if (!$user) return server_error();
					
					$user->password = $password;
					$user->save();
					
					$insert = UserSocialIds::create(array('user_id' => Session::get('user_id'), 'facebook_id' => Session::get('facebook_id')));
					
					if (!$insert) return server_error();

					Auth::login($user);

					if (Session::has('redirect'))
					{
						$redirect = Session::get('redirect');
						Session::forget('redirect');
						$action = $redirect;
					}
					else
					{
						$action = $action == '' ? route('showProfile', $user->username->username) : $action;
					}

					return status_ok(array(
						'message' => 'login',
						'url' => $action,
						'modal' => $modal
					));
				}

				$user_status = 3;
			}

			if (Session::has('new_password_username_conflict'))
			{
				$validation = new \Services\Validators\SocialNetworkSignupNewPasswordUsernameConflict;

				if (!$validation->passes()) return $validation->jsonErrors();
				
				Session::put('username', Input::get('username'));
				$password = \Hash::make(Input::get('password'));
				$user_status = 3;
			}

			$user = User::create(array(
				'firstname' => Session::get('first_name'),
				'lastname' => Session::get('last_name'),
				'email' => Session::get('email'),
				'password' => $password,
				'ip' => $_SERVER['REMOTE_ADDR']
			));

			if (!$user) return server_error();

			$user_id = $user->id;

			Usernames::create(array(
				'user_id' => $user_id,
				'username' => Session::get('username'),
				'user_type' => 'user'
			));

			RoleUser::create(array(
				'user_id' => $user_id,
				'role_id' => 1
			));

			UserStatus::create(array(
				'user_id' => $user_id,
				'status_id' => $user_status
			));

			UserSocialIds::create(array(
				'user_id' => $user_id,
				'facebook_id' => Session::get('facebook_id')
			));

			UserActivity::create(array(
				'user_id' => $user_id,
				'activities_id' => 1
			));

			if (Session::has('cover'))
			{
				$cover_album = Albums::create(array(
							'user_id' => $user_id,
							'user_type' => 'user',
							'album_type' => 2,
							'name' => 'profile.cover_photos'
						));

				$insert_image = Images::create(array(
								'album_id' => $cover_album->id,
								'filename' => Session::get('cover'),
								'type' => 2
							));

				CoverImages::create(array(
							'user_id' => $user_id,
							'user_type' => 'user',
							'image_id' => $insert_image->id
						));
			}

			$profile_album = Albums::create(array(
							'user_id' => $user_id,
							'user_type' => 'user',
							'album_type' => 1,
							'name' => 'profile.profile_photos'
						));

			$insert_image = Images::create(array(
							'album_id' => $profile_album->id,
							'filename' => 'https://graph.facebook.com/'.Session::get('facebook_id').'/',
							'type' => 2
						));

			ProfileImages::create(array(
						'user_id' => $user_id,
						'user_type' => 'user',
						'image_id' => $insert_image->id
					));

			$query = UserSocialNetworks::create(array('user_id' => $user_id, 'facebook' => Session::get('link')));

			UserInfoVisibility::create(array('user_id' => $user_id, 'user_social_networks_id' => $query->id));

			$data['username'] = Session::get('username');
			$data['firstname'] = Session::get('first_name');
			$data['token'] = str_random(50);

			UserEmailConfirmation::create(array('user_id' => $user_id, 'token' => $data['token']));

			Auth::login($user);

			Mail::send('emails.confirmation', $data, function($message)
			{
				$message->to(Session::get('email'), Session::get('first_name').' '.Session::get('last_name'))->subject(Lang::get('reminders.registration_verification'));
			});

			$username = Session::get('username');

			Session::forget('second');
			Session::forget('email');
			Session::forget('cover');
			Session::forget('facebook_id');
			Session::forget('first_name');
			Session::forget('last_name');
			Session::forget('username');
			Session::forget('link');
			Session::forget('no_email_username_conflict');
			Session::forget('username_conflict');
			Session::forget('no_email_username_ok');
			Session::forget('new_password');
			Session::forget('unconfirmed_email');
			Session::forget('user_id');
			Session::forget('new_password_username_conflict');

			if (Session::has('redirect'))
			{
				$redirect = Session::get('redirect');
				Session::forget('redirect');
				$action = $redirect;
			}
			else
			{
				$action = $action == '' ? $username : $action;
			}

			return status_ok(array(
				'message' => 'login',
				'url' => $action,
				'modal' => $modal
			));
		}

		return Redirect::to('/');
	}

	public function changeName()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\ChangeName;

			if ($validation->passes())
			{
				$user = Auth::user();

				if (Hash::check(Input::get('password'), $user->password))
				{

					$user->firstname = Input::get('firstname');
					$user->middlename = Input::get('middlename');
					$user->lastname = Input::get('lastname');
					$user->save();

					return status_ok(array('message' => Lang::get('global.update_saved')));
				}

				return status_error(array('message' => Lang::get('profile.password_incorrect'), 'field_name' => 'password'));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function changePassword()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\ChangePassword;

			if ($validation->passes())
			{
				$user = User::find(Auth::user()->id);

				if (!$user) return server_error();

				if (Hash::check(Input::get('currentPassword'), $user->password))
				{
					$user->password = Hash::make(Input::get('newPassword'));
					$user->save();

					return status_ok(array('message' => Lang::get('profile.password_have_been_saved')));
				}

				return status_error(array('message' => Lang::get('profile.password_incorrect'), 'field_name' => 'currentPassword'));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function addFriend()
	{
		if (Request::ajax())
		{
			if (!Auth::check()) return status_error(array('message' => 'login', 'target' => URL::route('loginPage')));

			$validation = new \Services\Validators\AddFriend;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$friend_id = Input::get('friend_id');
				$request_ok = false;

				if ($user_id == $friend_id) return status_error(array('message' => Lang::get('global.cannot_add_your_self')));

				$query = UserFriends::withTrashed()->where('user_id', $user_id)
						->where('friend_id', $friend_id)
						->first();

				if (!$query)
				{		
					$request = UserFriends::create(array(
						'user_id' => $user_id,
						'friend_id' => $friend_id
					));									

					$request_ok = true;					
				}

				if ($query->trashed())
				{
					$query->restore();
					$request_ok = true;					
				}

				if ($request_ok)
				{
					$user = User::find($friend_id);					
					$member_status = memberStatus($user->status);

					if ($member_status['id'] != 3)
					{
						$send_email = true;
						$settings = $user->emailNotificationsSettings;

						if ($settings)
						{
							$send_email = $settings->friend_request == 1 ? true : false;
						}
						
						if ($send_email)
						{
							$sender = User::find($user_id);
							$data['views'] = 'emails.user.friend_request';
							$data['recipient_name'] = userFullName($user);
							$data['recipient_email'] = $user->email;
							$data['recipient_firstname'] = $user->firstname;
							$data['sender_fullname'] = userFullName($sender);
							$data['sender_profile_pic'] = profileImage($sender, 'small');
							$data['sender_profile_link'] = profileUrl($sender->username->username);
							$data['subject'] = Lang::get('reminders.friend_request_subject', array('firstname' => $data['recipient_firstname']));

							sendEmail($data);
						}
					}
					return status_ok(array('message' => Lang::get('global.friend_request_sent')));
				}

				return status_error(array('message' => Lang::get('global.duplicate_friend_request')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function unfriend()
	{
		if (Request::ajax())
		{
			$user_id = Auth::user()->id;
			$friend_id = Input::get('friend_id');

			$query = UserFriends::where('user_id', $user_id)
						->where('friend_id', $friend_id)
						->where('accepted', 1)
						->first();

			if ($query)
			{
				$query->accepted = 0;
				$query->unread = 1;
				$query->save();
				$query->delete();

				$query = UserFriends::where('user_id', $friend_id)
						->where('friend_id', $user_id)
						->where('accepted', 1)
						->first();

				if ($query)
				{
					$query->accepted = 0;
					$query->unread = 1;
					$query->save();
					$query->delete();
				}

				$query = UserNotifications::where('user_id', $friend_id)
							->where('fk_id', $user_id)
							->where('type', 1)
							->first();

				if ($query) $query->delete();

				$data['user_id'] = $user_id;
				$data['friend_id'] = $friend_id;

				$query = UserActivity::where(function($query) use ($data)
							{
								$query->where(function($query) use ($data)
										{
										  	$query->where('user_id', $data['user_id'])
										  		  ->where('fk_id', $data['friend_id']);
										})
										->orWhere(function($query) use ($data)
										{
										  	$query->where('user_id', $data['friend_id'])
										  		  ->where('fk_id', $data['user_id']);
										});										
							})							
							->where('activities_id', 3)
							->get();

				if ($query->count())
				{
					foreach ($query as $key => $value)
					{
						$value->delete();
					}					
				}

				return status_ok();
			}
			return status_error();
		}

		return Redirect::to('/');
	}

	public function updateUnreadFriendRequest()
	{
		if (Request::ajax())
		{
			$query = UserFriends::where('friend_id', Auth::user()->id)
					->where('unread', 1)
					->update(array('unread' => 0));

			if (!$query) return server_error();

			return status_ok();
		}

		return Redirect::to('/');
	}

	public function updateUnreadNotifications()
	{
		if (Request::ajax())
		{
			$query = UserNotifications::where('user_id', Auth::user()->id)
					->where('unread', 1)
					->update(array('unread' => 0));

			if (!$query) return server_error();

			return status_ok();
		}

		return Redirect::to('/');
	}

	public function acceptFriendRequest()
	{
		if (Request::ajax())
		{
			if (Input::has('friend_id'))
			{
				$friend_id = Input::get('friend_id');
				$user_id = Auth::user()->id;

				$query = UserFriends::where('friend_id', $user_id)
						->where('user_id', $friend_id)
						->where('accepted', 0)
						->first();

				if (!$query) return status_ok(array('message' => Lang::get('profile.request_no_longer_valid')));
				
				$query->accepted = 1;
				$query->hidden = 0;
				$query->save();

				$query = UserFriends::withTrashed()->where('friend_id', $friend_id)
							->where('user_id', $user_id)
							->where('accepted', 0)
							->first();

				if ($query)
				{
					$query->accepted = 1;
					$query->unread = 0;
					$query->save();
					$query->restore();
				}
				else
				{
					$query = UserFriends::create(array('user_id' => $user_id, 'friend_id' => $friend_id, 'accepted' => 1, 'unread' => 0));
				}

				if (!$query) return server_error();

				$query = UserNotifications::create(array(
										'user_id' => $friend_id,
										'fk_id' => $user_id,
										'text' => 1,
										'type' => 1
									));

				if (!$query) return server_error();

				$data['user_id'] = $user_id;
				$data['friend_id'] = $friend_id;

				$query = UserActivity::onlyTrashed()
							->where(function($query) use ($data)
							{
								$query->where(function($query) use ($data)
										{
										  	$query->where('user_id', $data['user_id'])
										  		  ->where('fk_id', $data['friend_id']);
										})
										->orWhere(function($query) use ($data)
										{
										  	$query->where('user_id', $data['friend_id'])
										  		  ->where('fk_id', $data['user_id']);
										});										
							})							
							->where('activities_id', 3)
							->get();

				if ($query->count())
				{
					foreach ($query as $key => $value)
					{
						$value->restore();
					}					
				}
				else
				{
					$date = new \DateTime;
					$data = array(
								array(
									'user_id' => $user_id,
									'fk_id' => $friend_id,
									'activities_id' => 3,
									'created_at' => $date,
									'updated_at' => $date
								),
								array(
									'user_id' => $friend_id,
									'fk_id' => $user_id,
									'activities_id' => 3,
									'created_at' => $date,
									'updated_at' => $date
								)
							);

					$query = UserActivity::insert($data);
				}

				$user = User::find($friend_id);					
				$member_status = memberStatus($user->status);

				if ($member_status['id'] != 3)
				{
					$send_email = true;
					$settings = $user->emailNotificationsSettings;

					if ($settings)
					{
						$send_email = $settings->friend_confirmation == 1 ? true : false;
					}
					
					if ($send_email)
					{
						$sender = User::find($user_id);
						$data['views'] = 'emails.user.friend_confirmation';
						$data['recipient_name'] = userFullName($user);
						$data['recipient_email'] = $user->email;
						$data['recipient_firstname'] = $user->firstname;
						$data['sender_fullname'] = userFullName($sender);
						$data['sender_profile_pic'] = profileImage($sender, 'small');
						$data['sender_profile_link'] = profileUrl($sender->username->username);
						$data['subject'] = Lang::get('reminders.friend_confirmation_subject', array('fullname' => $data['sender_fullname']));

						sendEmail($data);
					}
				}

				return status_ok(array('message' => Lang::get('profile.friend_request_accepted'), 'user_id' => $friend_id));
			}

			return server_error();
		}

		return Redirect::to('/');
	}

	public function deniedFriendRequest()
	{
		if (Request::ajax())
		{
			if (Input::has('friend_id'))
			{
				$friend_id = Input::get('friend_id');
				$user_id = Auth::user()->id;

				$query = UserFriends::where('friend_id', $user_id)
						->where('user_id', $friend_id)
						->where('hidden', 0)
						->where('accepted', 0)
						->first();

				if (!$query) return status_ok(array('message' => Lang::get('profile.request_no_longer_valid')));
				
				$query->hidden = 1;
				$query->save();

				return status_ok(array('message' => Lang::get('profile.friend_request_denied')));
			}
		}

		return Redirect::to('/');
	}

	public function jsonFriends()
	{
		$query = User::select(DB::raw('users.id as id, users.firstname, users.middlename, users.lastname'))
					->join('user_friends', 'users.id', '=', 'user_friends.friend_id')
					->where('user_friends.user_id', Auth::user()->id)
					->where('user_friends.accepted', 1)
					->where(function($query)
					{
						$q = Input::get('q');
						$query->where('users.firstname', 'like', '%'.$q.'%')
								->orWhere('users.middlename', 'like', '%'.$q.'%')
								->orWhere('users.lastname', 'like', '%'.$q.'%');
					})
					->take(5)
					->get();

		$friends = array();

		foreach ($query as $key => $user)
		{
			$friends[$key]['name'] = $user->firstname.' '.$user->middlename.' '.$user->lastname;
			$friends[$key]['img_url'] = profileImage($user);
			$friends[$key]['id'] = $user->id;
			$friends[$key]['username'] = $user->username->username;
		}

		return $friends;
	}

	public function likePage()
	{
		if (Request::ajax())
		{
			if (!Auth::check())
			{
				Session::put('need_login_message', Lang::get('global.need_login_message'));
				return status_error(array('message' => 'login', 'target' => URL::route('loginPage')));
			}

			$logged_user = Auth::user();
			$user_logged_id = $logged_user->id;
			$page_id = Input::get('page_id');			
				
			$page = Page::find($page_id);
			
			if ($page)
			{
				$page_owner_id = $page->user_id;
				$member_status = memberStatus($page->owner->status);

				$query = UserPagesLikes::onlyTrashed()->where('user_id', $user_logged_id)
						->where('page_id', $page_id)
						->first();

				if ($query)
				{
					$query->restore();
				}
				else
				{
					$query = UserPagesLikes::create(array('user_id' => $user_logged_id, 'page_id' => $page_id));

					if (!$query) return server_error();
				}

				$user_pages_likes_id = $query->id;

				$total = $page->likes + 1;

				$page->likes = $total;
				$page->save();

				if ($page_owner_id != $user_logged_id)
				{
					$notifications = UserNotifications::onlyTrashed()
								->where('fk_id', $user_pages_likes_id)
								->where('type', 4)
								->first();

					if ($notifications)
					{
						$notifications->unread = 1;
						$notifications->save();
						$notifications->restore();
					}
					else
					{
						$query = UserNotifications::create(array(
											'user_id' => $page_owner_id,
											'fk_id' => $user_pages_likes_id,
											'type' => 4
										));

						if (!$query) return server_error();
					}

					if ($member_status['id'] != 3)
					{
						$send_email = true;
						$settings = $page->owner->emailNotificationsSettings;
						
						if ($settings)
						{
							$send_email = $settings->page_like == 1 ? true : false;							
						}

						if ($send_email)
						{
							$data['recipient_name'] = userFullName($page->owner);
							$data['recipient_email'] = $page->owner->email;
							$data['recipient_firstname'] = $page->owner->firstname;
							$data['member_fullname'] = userFullName($logged_user);
							$data['member_profile_pic'] = profileImage($logged_user, 'small');
							$data['member_profile_link'] = profileUrl($logged_user->username->username);
							$data['page_name'] = $page->page_name;
							$data['page_url'] = profileUrl($page->username->username);
							$data['subject'] = Lang::get('pages.user_like_a_page', array('membername' => $data['member_fullname'], 'pagename' => $data['page_name']));

							Mail::send('emails.page.likes', $data, function($message) use ($data)
							{
								$message->to($data['recipient_email'], $data['recipient_name'])
										->subject($data['subject']);
							});
						}
					}
				}

				$query = UserActivity::onlyTrashed()
							->where('user_id', $user_logged_id)
							->where('fk_id', $page_id)
							->where('activities_id', 4)
							->first();

				if ($query)
				{
					$query->restore();
				}
				else
				{
					$query = UserActivity::create(array(
									'user_id' => $user_logged_id,
									'fk_id' => $page_id,
									'activities_id' => 4
								));
				}

				return status_ok(array('total' => $total, 'text' => Lang::choice('profile.total_likes', $total), 'url' => URL::route('pageLikes', $page->username->username)));
			}

			return status_error();
		}

		return Redirect::to('/');
	}

	public function unlikePage()
	{
		if (Request::ajax())
		{
			$user_logged_id = Auth::user()->id;
			$page_id = Input::get('page_id');

			$query_page_like = UserPagesLikes::where('user_id', $user_logged_id)
								->where('page_id', $page_id)
								->first();

			if (!$query_page_like) return status_error();

			$user_page_like_id = $query_page_like->id;
			$query = Page::find($page_id);

			if ($query)
			{	
				$query_page_like->delete();

				$total = $query->likes - 1;

				$query->likes = $total;
				$query->save();

				$notifications = UserNotifications::where('fk_id', $user_page_like_id)
								->where('type', 4)
								->first();

				if ($notifications)
				{
					$notifications->delete();
				}

				$query = UserActivity::where('user_id', $user_logged_id)
							->where('fk_id', $page_id)
							->where('activities_id', 4)
							->first();

				if ($query)
				{
					$query->delete();
				}

				return status_ok(array('total' => $total, 'text' => Lang::choice('profile.total_likes', $total)));
			}

			return status_error();
		}

		return Redirect::to('/');
	}

	public function deleteMessage()
	{
		if (Request::ajax())
		{
			$id = Input::get('id');
			$user_type = Input::get('user_type');
			
			$query = Message::select(DB::raw('conversation_id'))
						->where('id', $id)
						->first();

			if (!$query) return status_error();

			$user_id = Auth::user()->id;

			if ($user_type == 'page')
			{
				$page_id = Input::get('user_id');
				$owner = Page::where('id', $page_id)
							->where('user_id', $user_id)
							->first();

				if (!$owner) return status_error();

				$user_id = $page_id;				
			}

			$conversation_id = $query->conversation_id;

			$query = UserConversation::where('user_id', $user_id)
								->where('conversation_id', $conversation_id)
								->first();

			if (!$query) return status_error();

			$query = Message::find($id);
			$query->delete();

			$query = UserUnreadMessage::where('message_id', $id)->delete();			

			return status_ok();
		}

		return Redirect::to('/');
	}

	public function updateUnreadMessages()
	{
		if (Request::ajax())
		{
			$user_id = Auth::user()->id;

			UserUnreadMessage::where('user_id', $user_id)->update(array('unread' => 0));

			return status_ok();
		}

		return Redirect::to('/');
	}

	public function emailNotificationsSettings()
	{
		$data = array(
			'friend_request_option1_active' => '',
			'friend_request_option1_checked' => '',
			'friend_request_option2_active' => ' active',
			'friend_request_option2_checked' => 'checked',
			'friend_confirmation_option1_active' => '',
			'friend_confirmation_option1_checked' => '',
			'friend_confirmation_option2_active' => ' active',
			'friend_confirmation_option2_checked' => 'checked',
			'new_message_option1_active' => '',
			'new_message_option1_checked' => '',
			'new_message_option2_active' => ' active',
			'new_message_option2_checked' => 'checked',
			'new_page_message_option1_active' => '',
			'new_page_message_option1_checked' => '',
			'new_page_message_option2_active' => ' active',
			'new_page_message_option2_checked' => 'checked',
			'new_page_like_option1_active' => '',
			'new_page_like_option1_checked' => '',
			'new_page_like_option2_active' => ' active',
			'new_page_like_option2_checked' => 'checked',
			'new_page_review_option1_active' => '',
			'new_page_review_option1_checked' => '',
			'new_page_review_option2_active' => ' active',
			'new_page_review_option2_checked' => 'checked',
			'new_page_review_like_option1_active' => '',
			'new_page_review_like_option1_checked' => '',
			'new_page_review_like_option2_active' => ' active',
			'new_page_review_like_option2_checked' => 'checked',
			'new_page_review_comment_option1_active' => '',
			'new_page_review_comment_option1_checked' => '',
			'new_page_review_comment_option2_active' => ' active',
			'new_page_review_comment_option2_checked' => 'checked',
			'new_page_review_comment_like_option1_active' => '',
			'new_page_review_comment_like_option1_checked' => '',
			'new_page_review_comment_like_option2_active' => ' active',
			'new_page_review_comment_like_option2_checked' => 'checked',
			'page_approved_disapproved_option1_active' => '',
			'page_approved_disapproved_option1_checked' => '',
			'page_approved_disapproved_option2_active' => ' active',
			'page_approved_disapproved_option2_checked' => 'checked',
			'page_blocked_unblocked_option1_active' => '',
			'page_blocked_unblocked_option1_checked' => '',
			'page_blocked_unblocked_option2_active' => ' active',
			'page_blocked_unblocked_option2_checked' => 'checked',
		);

		$query = UserEmailNotifications::where('user_id', Auth::user()->id)->first();

		if ($query)
		{
			$friend_request = $query->friend_request;
			$friend_confirmation = $query->friend_confirmation;
			$new_message = $query->new_message;
			$new_page_message = $query->new_page_message;
			$page_like = $query->page_like;
			$page_review = $query->page_review;
			$page_review_like = $query->page_review_like;
			$page_review_comment = $query->page_review_comment;
			$page_review_comment_like = $query->page_review_comment_like;
			$page_approved_disapproved = $query->page_approved_disapproved;
			$page_blocked_unblocked = $query->page_blocked_unblocked;
									
			$data = array(
				'friend_request_option1_active' => $friend_request == 0 ? ' active' : '',
				'friend_request_option1_checked' => $friend_request == 0 ? 'checked' : '',
				'friend_request_option2_active' => $friend_request == 1 ? ' active' : '',
				'friend_request_option2_checked' => $friend_request == 1 ? 'checked' : '',
				'friend_confirmation_option1_active' => $friend_confirmation == 0 ? ' active' : '',
				'friend_confirmation_option1_checked' => $friend_confirmation == 0 ? 'checked' : '',
				'friend_confirmation_option2_active' => $friend_confirmation == 1 ? ' active' : '',
				'friend_confirmation_option2_checked' => $friend_confirmation == 1 ? 'checked' : '',
				'new_message_option1_active' => $new_message == 0 ? ' active' : '',
				'new_message_option1_checked' => $new_message == 0 ? 'checked' : '',
				'new_message_option2_active' => $new_message == 1 ? ' active' : '',
				'new_message_option2_checked' => $new_message == 1 ? 'checked' : '',
				'new_page_message_option1_active' => $new_page_message == 0 ? ' active' : '',
				'new_page_message_option1_checked' => $new_page_message == 0 ? 'checked' : '',
				'new_page_message_option2_active' => $new_page_message == 1 ? ' active' : '',
				'new_page_message_option2_checked' => $new_page_message == 1 ? 'checked' : '',
				'new_page_like_option1_active' => $page_like == 0 ? ' active' : '',
				'new_page_like_option1_checked' => $page_like == 0 ? 'checked' : '',
				'new_page_like_option2_active' => $page_like == 1 ? ' active' : '',
				'new_page_like_option2_checked' => $page_like == 1 ? 'checked' : '',
				'new_page_review_option1_active' => $page_review == 0 ? ' active' : '',
				'new_page_review_option1_checked' => $page_review == 0 ? 'checked' : '',
				'new_page_review_option2_active' => $page_review == 1 ? ' active' : '',
				'new_page_review_option2_checked' => $page_review == 1 ? 'checked' : '',
				'new_page_review_like_option1_active' => $page_review_like == 0 ? ' active' : '',
				'new_page_review_like_option1_checked' => $page_review_like == 0 ? 'checked' : '',
				'new_page_review_like_option2_active' => $page_review_like == 1 ? ' active' : '',
				'new_page_review_like_option2_checked' => $page_review_like == 1 ? 'checked' : '',
				'new_page_review_comment_option1_active' => $page_review_comment == 0 ? ' active' : '',
				'new_page_review_comment_option1_checked' => $page_review_comment == 0 ? 'checked' : '',
				'new_page_review_comment_option2_active' => $page_review_comment == 1 ? ' active' : '',
				'new_page_review_comment_option2_checked' => $page_review_comment == 1 ? 'checked' : '',
				'new_page_review_comment_like_option1_active' => $page_review_comment_like == 0 ? ' active' : '',
				'new_page_review_comment_like_option1_checked' => $page_review_comment_like == 0 ? 'checked' : '',
				'new_page_review_comment_like_option2_active' => $page_review_comment_like == 1 ? ' active' : '',
				'new_page_review_comment_like_option2_checked' => $page_review_comment_like == 1 ? 'checked' : '',
				'page_approved_disapproved_option1_active' => $page_approved_disapproved == 0 ? ' active' : '',
				'page_approved_disapproved_option1_checked' => $page_approved_disapproved == 0 ? 'checked' : '',
				'page_approved_disapproved_option2_active' => $page_approved_disapproved == 1 ? ' active' : '',
				'page_approved_disapproved_option2_checked' => $page_approved_disapproved == 1 ? 'checked' : '',
				'page_blocked_unblocked_option1_active' => $page_blocked_unblocked == 0 ? ' active' : '',
				'page_blocked_unblocked_option1_checked' => $page_blocked_unblocked == 0 ? 'checked' : '',
				'page_blocked_unblocked_option2_active' => $page_blocked_unblocked == 1 ? ' active' : '',
				'page_blocked_unblocked_option2_checked' => $page_blocked_unblocked == 1 ? 'checked' : ''
			);
		}

		return $data;
	}

	public function changeEmailNotifications()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UserEmailNotificationsSettings;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$friend_request = Input::get('friend_request');
				$friend_confirmation = Input::get('friend_confirmation');
				$new_message = Input::get('new_message');
				$new_page_message = Input::get('new_page_message');
				$page_like = Input::get('new_page_like');
				$page_review = Input::get('new_page_review');
				$page_review_like = Input::get('new_page_review_like');
				$page_review_comment = Input::get('new_page_review_comment');
				$page_review_comment_like = Input::get('new_page_review_comment_like');
				$page_approved_disapproved = Input::get('page_approved_disapproved');
				$page_blocked_unblocked = Input::get('page_blocked_unblocked');

				$query = UserEmailNotifications::where('user_id', $user_id)->first();

				if (!$query)
				{
					$query = UserEmailNotifications::create(array(
										'user_id' => $user_id,
										'friend_request' => $friend_request,
										'friend_confirmation' => $friend_confirmation,
										'new_message' => $new_message,
										'new_page_message' => $new_page_message,
										'page_like' => $page_like,
										'page_review' => $page_review,
										'page_review_like' => $page_review_like,
										'page_review_comment' => $page_review_comment,
										'page_review_comment_like' => $page_review_comment_like,
										'page_approved_disapproved' => $page_approved_disapproved,
										'page_blocked_unblocked' => $page_blocked_unblocked
									));

					if (!$query) return server_error();
				}
				else
				{
					$query->friend_request = $friend_request;
					$query->friend_confirmation = $friend_confirmation;
					$query->new_message = $new_message;
					$query->new_page_message = $new_page_message;
					$query->page_like = $page_like;
					$query->page_review = $page_review;
					$query->page_review_like = $page_review_like;
					$query->page_review_comment = $page_review_comment;
					$query->page_review_comment_like = $page_review_comment_like;
					$query->page_approved_disapproved = $page_approved_disapproved;
					$query->page_blocked_unblocked = $page_blocked_unblocked;
					$query->save();
				}

				return status_ok(array('message' => Lang::get('global.update_saved')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function writePageReview()
	{
		$validation = new \Services\Validators\PageReview;

		if ($validation->passes())
		{
			$page_id = Input::get('page_id');
			$user_logged_id = Auth::user()->id;
			$rating = Input::get('rating');
			$review_text = Input::get('review');
			$logged_user = Auth::user();

			$page = Page::find($page_id);

			if (!$page) return status_error();

			$page_owner_id = $page->user_id;
			$page_username = $page->username->username;

			$query = PageReviews::create(array(
							'user_id' => $user_logged_id,
							'page_id' => $page_id,
							'rating' => $rating,
							'review' => $review_text
						));

			if (!$query) return server_error();

			$review_id = $query->id;
			$review_date = time_ago($query->created_at);
			$query = UserActivity::create(array(
							'user_id' => $user_logged_id,
							'fk_id' => $review_id,
							'activities_id' => 8
						));

			if (!$query) return server_error();

			$user_logged_full_name = userFullname($logged_user);
			$user_logged_profile_image_default = profileImage($logged_user);
			$user_logged_profile_image_small = profileImage($logged_user, 'small');
			$user_logged_profile_url = profileUrl($logged_user->username->username);
			$page_review_url = URL::route('review', array($page_username, $review_id));
			$review_like_url = URL::route('likeReview', array($page_username, $review_id));

			if ($page_owner_id != $user_logged_id)
			{
				$notifications = UserNotifications::create(array(
										'user_id' => $page_owner_id,
										'fk_id' => $review_id,
										'type' => 5,
										'unread' => 1
									));

				if (!$notifications) return server_error();

				$page_owner = User::find($page_owner_id);
				$page_owner_status = memberStatus($page_owner->status);

				if ($page_owner_status['id'] != 3)
				{
					$settings = $page_owner->emailNotificationsSettings;
					$send_email = true;

					if ($settings)
					{
						$send_email = $settings->page_review == 1 ? true : false;
					}

					if ($send_email)
					{
						$data['views'] = 'emails.page.review';
						$data['recipient_name'] = userFullName($page_owner);
						$data['recipient_firstname'] = $page_owner->firstname;
						$data['recipient_email'] = $page_owner->email;
						$data['member_fullname'] = $user_logged_full_name;
						$data['member_profile_pic'] = $user_logged_profile_image_small;
						$data['member_profile_link'] = $user_logged_profile_url;
						$data['review_url'] = $page_review_url;
						$data['page_name'] = $page->page_name;
						$data['subject'] = Lang::get('reminders.new_page_review');

						sendEmail($data);
					}
				}
			}
			
			$total_reviews = intval(Input::get('total_reviews')) + 1;

			$review = '<li id="review-'.$review_id.'" class="list-group-item">
							<table width="100%">
								<tbody>
									<tr>
										<td width="60" class="vat"><img src="'.$user_logged_profile_image_default.'" width="50" height="50" class="pull-left"></td>
										<td>
											<div><b><a href="'.$user_logged_profile_url.'">'.$user_logged_full_name.'</a></b></div>
											<div class="clearfix">
												<span id="reviewStars-'.$review_id.'">'.starRating($rating).'</span>					
												<a href="'.$page_review_url.'" class="text-muted review-date">'.$review_date.'</a>
											</div>
										</td>
									</tr>
										<td colspan="2">
											<p id="reviewText-'.$review_id.'" class="nmb mt5px">'.$review_text.'</p>
											<ul class="review-action clearfix np">
												<li>
													<span id="reviewLikeLoading-'.$review_id.'" class="loading-small hidden"></span>
													<a href="'.$review_like_url.'" data-id="'.$review_id.'" data-action="like" class="like-review" rel="nofollow">'.Lang::get('global.like').'</a>			
												</li>
												<li><span class="separator glyphicon glyphicon-stop"></span></li>
												<li>
													<a class="review-comment-link" data-id="'.$review_id.'">'.Lang::get('global.comment').'</a>
												</li>
												<li><span class="separator glyphicon glyphicon-stop"></span></li>
												<li>
													<a class="modal-link" href="'.action('ModalsController@getEditPageReview', array($review_id)).'">'.Lang::get('global.edit').'</a>
												</li>
												<li><span class="separator glyphicon glyphicon-stop"></span></li>
												<li>
													<a class="delete-review" data-review-id="'.$review_id.'" data-owner-id="'.$user_logged_id.'" data-total-reviews="'.$total_reviews.'" data-page-name="profile-page">'.Lang::get('global.delete').'</a>
												</li>
																								
											</ul>
											
											<ul class="review-results mt5px np">
												<li id="reviewLikesPanel-'.$review_id.'" class="review-likes-panel hidden">
													<span class="glyphicon glyphicon-thumbs-up"></span>
													<span id="reviewLikesPanelText-'.$review_id.'"></span>
												</li>
												<li id="reviewCommentFormWrapper-'.$review_id.'" class="comments">
													<table width="100%">
														<tr>
															<td width="37" class="vat"><img src="'.$user_logged_profile_image_default.'" width="32" height="32"></td>
															<td>
																'.Form::open(array(
																		"route" => array("commentReview", $page_username, $review_id),			
																		"id" => "reviewCommentForm-".$review_id,							
																		"class" => "pr"
																	)).'
																	<textarea name="comment" id="reviewCommentTextarea-'.$review_id.'" class="form-control review-comment-text-input" placeholder="'.Lang::get('profile.write_a_comment').'" rows="1" data-id="'.$review_id.'"></textarea>
																	<i id="commentLoading-'.$review_id.'" class="loading-small hidden"></i>
																'.Form::close().'
															</td>															
														</tr>
													</table>
												</li>												
											</ul>											
										</td>
									</tr>
								</tbody>
							</table>
						</li>';			

			return status_ok(array(
				'message' => Lang::get('profile.your_review_was_successfully_published'),
				'review' => $review,
				'total_reviews_text' => Lang::choice('profile.total_reviews', $total_reviews, array('total' => $total_reviews)),
				'total_reviews' => $total_reviews
			));
		}

		return $validation->jsonErrors();
	}

	public function deletePageReview()
	{
		$review_id = Input::get('review_id');
		$owner_id = Input::get('owner_id');
		$user_logged_id = Auth::user()->id;

		if ($owner_id != $user_logged_id) return status_error();

		$query = PageReviews::where('id', $review_id)
					->where('user_id', $user_logged_id)
					->first();

		if (!$query) return status_error();

		$page_id = $query->page_id;
		$query->delete();

		$total_reviews = intval(Input::get('total_reviews')) - 1;
		$page_name = Input::get('page_name');
		$redirect_url = '';

		if ($page_name == 'review-page')
		{
			$query = Page::find($page_id);

			$redirect_url = profileUrl($query->username->username);
		}


		return status_ok(array(
			'total_reviews' => Lang::choice('profile.total_reviews', $total_reviews, array('total' => $total_reviews)),
			'redirect_url' => $redirect_url
		));
	}

	public function deletePageReviewComment()
	{
		$comment_id = Input::get('comment_id');
		$owner_id = Input::get('owner_id');
		$user_logged_id = Auth::user()->id;

		if ($owner_id != $user_logged_id) return status_error();

		$query = PageReviewComments::where('id', $comment_id)
					->where('user_id', $user_logged_id)
					->first();

		if (!$query) return status_error();

		$query->delete();

		return status_ok();
		
	}
}