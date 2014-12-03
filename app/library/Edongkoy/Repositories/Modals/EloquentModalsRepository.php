<?php namespace Edongkoy\Repositories\Modals;

# app/library/Edongkoy/Repositories/Modals/EloquentModalsRepository.php

use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Users\Models\Usernames;
use Edongkoy\Repositories\Users\Models\Conversation;
use Edongkoy\Repositories\Users\Models\Message;
use Edongkoy\Repositories\Users\Models\UserConversation;
use Edongkoy\Repositories\Users\Models\UserUnreadMessage;
use Edongkoy\Repositories\Users\Models\UserNotifications;
use Edongkoy\Repositories\Users\Models\UserStatus;
use Edongkoy\Repositories\Page\Models\Page;
use Edongkoy\Repositories\Page\Models\PageStatus;
use Edongkoy\Repositories\Page\Models\PageReviews;
use Edongkoy\Repositories\Page\Models\PageReviewComments;
use Edongkoy\Repositories\Admin\Models\ReportedBugs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class EloquentModalsRepository implements ModalsRepositoryInterface {

	public function type($username)
	{
		$query = Usernames::select(DB::raw('user_type, user_id'))
					->where('username', $username)
					->first();

		if (!$query) return false;

		return array(
			'user_id' => $query->user_id,
			'user_type' => $query->user_type
		);
	}

	public function getInfo($type, $id)
	{
		$valid_type = array('page', 'user', 'comment', 'review');

		if (!in_array($type, $valid_type)) return false;

		if ($type == 'page')
		{
			$query = Page::find($id);

			if (!$query) return false;

			$data['heading_text'] = Lang::get('modal.report_profile_title', array('name' => $query->page_name));
		}
		else if($type == 'user')
		{
			$query = User::find($id);

			if (!$query) return false;

			$data['heading_text'] = Lang::get('modal.report_profile_title', array('name' => userFullName($query)));
		}
		elseif ($type == 'review')
		{
			$query = PageReviews::find($id);

			if (!$query) return false;

			$data['heading_text'] = Lang::get('modal.report_a_review');
		}
		elseif ($type == 'comment')
		{
			$query = PageReviewComments::find($id);

			if (!$query) return false;

			$data['heading_text'] = Lang::get('modal.report_a_comment');
		}

		return $data;
	}

	public function memberFullName($user_id)
	{
		$query = User::select(DB::raw('firstname, middlename, lastname'))
					->where('id', $user_id)
					->first();

		if (!$query) return false;

		$middlename = $query->middlename != '' ? $query->middlename.' ' : '';

		return $query->firstname . ' '.$middlename . $query->lastname;
	}

	public function pageName($user_id)
	{
		$query = Page::select(DB::raw('page_name'))
					->where('id', $user_id)
					->first();

		if (!$query) return false;

		return $query->page_name;
	}

	public function sendMessage()
	{
		$validation = new \Services\Validators\SendMessage;

		if ($validation->passes())
		{
			$usernames = Input::get('participants');

			if (Input::has('participants'))
			{
				$subject = Input::get('subject');
				$message = Input::get('message');
				$recipients = array();
				
				foreach($usernames as $key => $username)
				{
					$query = Usernames::select(DB::raw('user_id, user_type'))
								->where('username', $username)
								->first();

					if ($query)
					{
						$recipients[$key]['user_id'] = $query->user_id;
						$recipients[$key]['user_type'] = $query->user_type;
					}
				}				

				$total = count($recipients);

				if (!$total) return status_error(array('message' => Lang::get('modal.please_provide_recipient')));

				$query = Conversation::create(array('subject' => $subject));
				$conversation_id = $query->id;

				if (!$query) return server_error();

				$user_logged_id = Auth::user()->id;
				$new_key = $total + 1;
				$recipients[$new_key]['user_id'] = $user_logged_id;
				$recipients[$new_key]['user_type'] = 'user';

				foreach($recipients as $key => $value)
				{
					$recipients[$key]['conversation_id'] = $conversation_id;
					$recipients[$key]['created_at'] = new \DateTime;
					$recipients[$key]['updated_at'] = new \DateTime;

					$query = UserConversation::create(array(
									'user_id' => $value['user_id'],
									'user_type' => $value['user_type'],
									'conversation_id' => $conversation_id
								));					

					# if user write a message to a page
					if ($value['user_type'] == 'page')
					{
						$user_conversation_id = $query->id;
						$page_name = '';

						$query = Page::find($value['user_id']);

						if ($query)
						{
							$recipients[$key]['page_name'] = $query->page_name;
							$recipients[$key]['owner_id'] = $query->user_id;
							$recipients[$key]['username'] = $query->username->username;
							$query = UserNotifications::create(array(
											'user_id' => $recipients[$key]['owner_id'],
											'fk_id'   => $user_conversation_id,
											'text'    => 2,
											'type'    => 2,
											'unread'  => 1
										));
						}
					}
				}

				$query = Message::create(array(
							'user_id' => $user_logged_id,
							'conversation_id' => $conversation_id,
							'message' => $message,
							'user_type' => 'user'
						));

				$message_id = $query->id;

				array_forget($recipients, $new_key);
				
				foreach($recipients as $key => $value)
				{
					if ($value['user_type'] == 'user')
					{
						UserUnreadMessage::create(array(
							'user_id'         => $value['user_id'],
							'message_id'      => $message_id,
							'conversation_id' => $conversation_id,
							'unread'          => 1
						));

						$recipient_id = $value['user_id'];
					}
					elseif ($value['user_type'] == 'page')
					{
						$recipient_id = $value['owner_id'];
					}

					$user = User::find($recipient_id);				
					$member_status = memberStatus($user->status);

					if ($member_status['id'] != 3)
					{
						$send_email = true;
						$settings = $user->emailNotificationsSettings;

						if ($settings)
						{
							if ($value['user_type'] == 'user')
							{
								$send_email = $settings->new_message == 1 ? true : false;
							}
							elseif ($value['user_type'] == 'page')
							{
								$send_email = $settings->new_page_message == 1 ? true : false;
							}
						}
						
						if ($send_email)
						{
							$sender = User::find($user_logged_id);
							$data['recipient_email'] = $user->email;							
							$data['recipient_name'] = userFullName($user);
							$data['recipient_firstname'] = $user->firstname;
							$data['sender_fullname'] = userFullName($sender);
							$data['message_content'] = $message;							
							
							if ($value['user_type'] == 'user')
							{
								$data['message_link'] = URL::route('message', array('username' => $user->username->username, 'id' => $conversation_id));
								$data['views'] = 'emails.user.new_message';
								$data['subject'] = Lang::get('reminders.new_message_subject', array('fullname' => $data['sender_fullname'], 'sitename' => Lang::get('global.site_name')));
							}
							elseif ($value['user_type'] == 'page')
							{
								$data['message_link'] = URL::route('message', array('username' => $value['username'], 'id' => $conversation_id));
								$data['views'] = 'emails.user.new_page_message';
								$data['subject'] = Lang::get('reminders.new_page_message_subject', array('pagename' => $value['page_name']));
							}							
							
							sendEmail($data);
						}
					}						
				}



				return status_ok(array('message' => Lang::get('modal.message_sent')));
			}

			return status_error(array('message' => Lang::get('modal.please_provide_recipient')));
		}

		return $validation->jsonErrors();
	}

	public function deactivateAccount()
	{
		$validation = new \Services\Validators\DeactivateAccount;

		if ($validation->passes())
		{
			$user = Auth::user();

			if (Hash::check(Input::get('password'), $user->password))
			{
				$query = UserStatus::where('user_id', $user->id)->first();

				if (!$query) return status_error();

				$query->status_id = 5;
				$query->save();

				return status_ok(array('url' => URL::route('showProfile', $user->username->username)));
			}

			return status_error(array('message' => Lang::get('profile.password_incorrect'), 'field_name' => 'password'));
		}

		return $validation->jsonErrors();
	}

	public function activateAccount()
	{
		$validation = new \Services\Validators\DeactivateAccount;

		if ($validation->passes())
		{
			$user = Auth::user();

			if (Hash::check(Input::get('password'), $user->password))
			{
				$query = UserStatus::where('user_id', $user->id)->first();

				if (!$query) return status_error();

				$query->status_id = 1;
				$query->save();

				return status_ok(array('url' => URL::route('showProfile', $user->username->username)));
			}

			return status_error(array('message' => Lang::get('profile.password_incorrect'), 'field_name' => 'password'));
		}

		return $validation->jsonErrors();
	}

	public function deactivatePage()
	{
		$validation = new \Services\Validators\DeactivateAccount;

		if ($validation->passes())
		{
			$user = Auth::user();
			$page_id = Input::get('page_id');

			if (Hash::check(Input::get('password'), $user->password))
			{
				$page = Page::find($page_id);
				
				if (!$page) return unknown_error();
				if ($user->id != $page->user_id) return status_error(array('message' => Lang::get('pages.deactivate_page_not_owner')));

				$page_status = PageStatus::where('page_id', $page_id)->first();
				$page_status->status_id = 5;
				$page_status->save();

				return status_ok();
			}

			return status_error(array('message' => Lang::get('profile.password_incorrect'), 'field_name' => 'password'));
		}

		return $validation->jsonErrors();
	}

	public function activatePage()
	{
		$validation = new \Services\Validators\DeactivateAccount;

		if ($validation->passes())
		{
			$user = Auth::user();
			$page_id = Input::get('page_id');

			if (Hash::check(Input::get('password'), $user->password))
			{
				$page = Page::find($page_id);
				
				if (!$page) return unknown_error();
				if ($user->id != $page->user_id) return status_error(array('message' => Lang::get('pages.activate_page_not_owner')));

				$page_status = PageStatus::where('page_id', $page_id)->first();
				$page_status->status_id = 1;
				$page_status->save();

				return status_ok();
			}

			return status_error(array('message' => Lang::get('profile.password_incorrect'), 'field_name' => 'password'));
		}

		return $validation->jsonErrors();
	}

	public function reportProblem()
	{
		$validation = new \Services\Validators\ReportProblem;

		if ($validation->passes())
		{
			$bug = Input::get('bug');
			$ip = $_SERVER['REMOTE_ADDR'];
			$query = ReportedBugs::create(array(
					'bug' => $bug,
					'ip' => $ip
				));

			if (!$query) return server_error();

			$data['views'] = 'emails.bugs';
			$data['recipient_email'] = 'darwinluague9001@gmail.com';
			$data['recipient_name'] = 'Darwin Luague';
			$data['subject'] = 'Reported Bugs';
			$data['bug'] = $bug;
			$data['ip'] = $ip;

			sendEmail($data);

			return status_ok(array('message' => Lang::get('global.bug_report_success_message')));
		}

		return $validation->jsonErrors();
	}

	public function contactUs()
	{
		if (Auth::check())
		{
			$validation = new \Services\Validators\ContactUsMember;
		}
		else
		{
			$validation = new \Services\Validators\ContactUsNonMember;
		}

		if ($validation->passes())
		{

			$ip = $_SERVER['REMOTE_ADDR'];
			$data['views'] = 'emails.contact_us';
			$data['recipient_email'] = 'darwinluague9001@gmail.com';
			$data['recipient_name'] = 'Darwin Luague';
			$data['subject'] = 'Inquiry';
			$data['fullname'] = Auth::check() ? userFullName(Auth::user()) :Input::get('name');
			$data['email'] = Auth::check() ? Auth::user()->email : Input::get('email');
			$data['profile_url'] = Auth::check() ? profileUrl(Auth::user()->username->username) : '';
			$data['concern'] = Input::get('message');
			$data['ip'] = $ip;

			sendEmail($data);

			return status_ok(array('message' => Lang::get('global.message_sent')));
		}

		return $validation->jsonErrors();
	}

	public function editPageReview()
	{
		$validation = new \Services\Validators\PageReview;

		if ($validation->passes())
		{
			$user_logged_id = Auth::user()->id;
			$review_id = Input::get('review_id');
			$owner_id = Input::get('owner_id');
			$page_id = Input::get('page_id');
			$rating = Input::get('rating');
			$review = Input::get('review');

			if ($user_logged_id != $owner_id) return status_error(array('message' => Lang::get('modal.you_are_not_allowed_to_edit_this_review')));

			$query = PageReviews::where('id', $review_id)
							->where('user_id', $user_logged_id)
							->where('page_id', $page_id)
							->first();

			if (!$query) return status_error(array('message' => Lang::get('modal.you_are_not_allowed_to_edit_this_review')));

			$query->rating = $rating;
			$query->review = $review;
			$query->save();

			return status_ok(array('review_text' => $review, 'review_id' => $review_id, 'rating' => starRating($rating)));
		}

		return $validation->jsonErrors();
	}

	public function getPageReview($review_id)
	{
		$query = PageReviews::find($review_id);

		if (!$query) return false;

		$data['review'] = $query->review;
		$data['rating'] = $query->rating;
		$data['page_id'] = $query->page_id;
		$data['owner_id'] = $query->user_id;

		return $data;
	}

	public function getPageReviewComment($comment_id)
	{
		$query = PageReviewComments::find($comment_id);

		if (!$query) return false;

		$data['comment'] = $query->comment;
		$data['owner_id'] = $query->user_id;
		$data['review_id'] = $query->review->id;

		return $data;
	}

	public function editPageReviewComment()
	{
		$user_logged_id = Auth::user()->id;
		$comment_id = Input::get('comment_id');
		$review_id = Input::get('review_id');
		$comment = Input::get('comment');
		$owner_id = Input::get('owner_id');

		if (empty($comment)) return status_error(array('message' => Lang::get('modal.please_write_a_comment')));

		if ($user_logged_id != $owner_id) return status_error(array('message' => Lang::get('modal.you_are_not_allowed_to_edit_this_comment')));

		$query = PageReviewComments::where('id', $comment_id)
					->where('user_id', $user_logged_id)
					->where('review_id', $review_id)
					->first();

		if (!$query) return status_error(array('message' => Lang::get('modal.you_are_not_allowed_to_edit_this_comment')));

		$query->comment = $comment;
		$query->save();

		return status_ok(array('comment_text' => $comment, 'comment_id' => $comment_id));
	}
}