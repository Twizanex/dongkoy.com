<?php

use Edongkoy\Repositories\Modals\ModalsRepositoryInterface as modals;
use Edongkoy\Repositories\Users\ProfileRepositoryInterface as profile;

class ModalsController extends Controller {

	protected $modal;
	protected $profile;

	public function __construct(modals $modal, profile $profile)
	{
		$this->beforeFilter('ajax');
		$this->beforeFilter('modalAuth', array(
			'only' => array(
						'getMessage',
						'getDeactivateAccount',
						'getActivateAccount',
						'postDeactivateAccount',
						'postActivateAccount',
						'getDeactivatePage',
						'getActivatePage',
						'postDeactivatePage',
						'postActivatePage',
						'getEditPageReview',
						'postEditPageReview',
						'getEditPageReviewComment',
						'postEditPageReviewComment'
					)
			)
		);
		$this->modal = $modal;
		$this->profile = $profile;
		$this->robots = 'noindex, nofollow';
	}

	public function getMessage($username)
	{
		$data['type'] = $this->modal->type($username);
		
		if (!$data['type']) return modalUnknownError();

		$data['user_id'] = $data['type']['user_id'];
		$data['username'] = $username;
		$data['name'] = $data['type']['user_type'] == 'user' ? $this->modal->memberFullName($data['user_id']) : $this->modal->pageName($data['user_id']);

		if (!$data['name']) return modalUnknownError();

		return View::make('modals.message')->with($data);
	}

	public function postSendMessage()
	{
		if (!Auth::check()) return modalUnknownError();
		return $this->modal->sendMessage();
	}

	public function getConfirmDeleteMessage($id, $user_type, $user_id)
	{
		$data['id'] = $id;
		$data['user_type'] = $user_type;
		$data['user_id'] = $user_id;
		$data['heading'] = Lang::get('profile.delete_message');
		$data['text'] = Lang::get('profile.sure_delete_message');
		$data['action_text'] = Lang::get('global.delete');
		$data['action'] = 'delete-message';
		$data['loading_text'] = Lang::get('global.deleting');
		return View::make('modals.confirm_delete_message')->with($data);
	}

	public function getReport($type, $id)
	{
		$data['info'] = $this->modal->getInfo($type, $id);

		if (!$data['info']) return modalUnknownError();

		$data['id'] = $id;
		$data['type'] = $type;
		$data['heading_text'] = $data['info']['heading_text'];

		return View::make('modals.report')->with($data);		
	}

	public function postConfirmDeleteConversation()
	{
		$data['heading'] = Input::get('title');
		$data['bodyText'] = Input::get('bodyText');
		$data['actionText'] = Input::get('actionText');
		$data['dataId'] = Input::get('dataId');
		return View::make('modals.confirm_delete_conversation')->with($data);
	}

	public function postConfirmDeleteImage()
	{
		$data['actionUrl'] = Input::get('actionUrl');
		return View::make('modals.confirm')->with($data);
	}

	public function getAlbums($user_id, $user_type, $username, $action)
	{
		$data['albums'] = $this->profile->getAlbums($user_id, $user_type, $username);
		$data['action'] = $action;
		$data['user_id'] = $user_id;
		$data['user_type'] = $user_type;
		$data['username'] = $username;
		return View::make('modals.albums')->with($data);
    }

    public function getPhotos($album_id, $action, $user_id, $user_type, $username)
	{
		$data['photos'] = $this->profile->getAlbumPhotos($album_id, $user_id, $user_type, $username, $action);
		$data['action'] = $action;
		$data['album_url'] = URL::action('ModalsController@getAlbums', array($user_id, $user_type, $username, $action));
		return View::make('modals.photos')->with($data);
    }

    public function getCarousel($user_id, $user_type, $username, $image_id, $album_id)
    {
    	$data['photos'] = $this->profile->getAlbumPhotos($album_id, $user_id, $user_type, $username, 'modalCarousel');
		$data['image_id'] = $image_id;
		return View::make('modals.carousel')->with($data);
    }

    public function getCrop($album_id, $photo_id, $username, $filename)
    {
    	//$filename = $filename.'.jpg';
    	$data['action_url'] = URL::route('crop');    	
    	$data['src'] = imageUrl($filename, $username, 'xxxlarge');
    	$data['image_id'] = $photo_id;
    	$data['album_id'] = $album_id;
    	$data['filename'] = $filename;
    	return View::make('modals.crop')->with($data);
    }

    public function getEmailConfirmedMessage($email)
    {
    	$data['email'] = $email;
    	return View::make('modals.email_confirmed_message')->with($data);
    }

    public function getLogin()
    {
    	return View::make('modals.login');
    }

    public function getChangeEmail()
    {
    	return View::make('modals.change_email');
    }

    public function getFacebookNewPassword()
    {
    	return View::make('modals.fb_new_password');
    }

    public function getFacebookNewPasswordUsernameConflict()
    {
    	return View::make('modals.fb_new_password_username_conflict');
    }

    public function getFacebookNoEmailUsernameConflict()
    {
    	return View::make('modals.fb_no_email_username_conflict');
    }

    public function getFacebookNoEmailUsernameOk()
    {
    	return View::make('modals.fb_no_email_username_ok');
    }

    public function getProfilePicPreview($type, $id)
    {
    	$data['info'] = $this->profile->profileImagePreview($type, $id);
    	return View::make('modals.profile_image_preview')->with($data);
    }

    public function getUnknownError()
    {
    	return View::make('modals.unknown_error');
    }

	public function getDeactivatePage($page_id, $page_name)
	{
		$data['page_id'] = $page_id;
		$data['page_name'] = $page_name;
		return View::make('modals.deactivate_page')->with($data);
	}

	public function getActivatePage($page_id, $page_name)
	{
		$data['page_id'] = $page_id;
		$data['page_name'] = $page_name;
		return View::make('modals.activate_page')->with($data);
	}

	public function postDeactivatePage()
	{
		return $this->modal->deactivatePage();
	}

	public function postActivatePage()
	{
		return $this->modal->activatePage();
	}

	public function getDeactivateAccount()
	{
		return View::make('modals.deactivate_account');
	}

	public function getActivateAccount()
	{
		return View::make('modals.activate_account');
	}

	public function postActivateAccount()
	{
		return $this->modal->activateAccount();
	}

	public function postDeactivateAccount()
	{
		return $this->modal->deactivateAccount();
	}

	public function getReportProblem()
	{
		return View::make('modals.report_problem');
	}

	public function postReportProblem()
	{
		return $this->modal->reportProblem();
	}

	public function getContactUs()
	{
		return View::make('modals.contact_us');
	}

	public function postContactUs()
	{
		return $this->modal->contactUs();
	}

	public function getEditPageReview($review_id)
	{
		$data['review_id'] = $review_id;
		$data['review'] = $this->modal->getPageReview($review_id);
		return View::make('modals.edit_page_review')->with($data);
	}

	public function postEditPageReview()
	{
		return $this->modal->editPageReview();
	}

	public function getEditPageReviewComment($comment_id)
	{
		$data['comment_id'] = $comment_id;
		$data['comment'] = $this->modal->getPageReviewComment($comment_id);
		return View::make('modals.edit_page_review_comment')->with($data);
	}

	public function postEditPageReviewComment()
	{
		return $this->modal->editPageReviewComment();
	}
}