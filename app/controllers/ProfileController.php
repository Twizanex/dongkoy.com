<?php

use Edongkoy\Repositories\Users\ProfileRepositoryInterface as user;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;
use Edongkoy\Repositories\Videos\VideosRepositoryInterface as videos;
use Edongkoy\Repositories\Image\ImageRepositoryInterface as image;

class ProfileController extends BaseController {

	protected $user;
    protected $image;
	
	public function __construct(user $user, globals $global, image $image, videos $video)
	{
		$this->beforeFilter('ajax', array(
            'only' => array(
                    'changeCover',
                    'changeProfileImage',
                    'makeAlbumCover',
                    'deleteImage',
                    'crop',
                    'uploadProfilePhoto',
                    'removeProfilePic',
                    'removeBannerPic',
                    'likeReview',
                    'commentReview',
                    'likeUnlikeReviewComment'
                )));
        $this->user = $user;
		$this->global = $global;
        $this->image = $image;
        $this->video = $video;
	}

	public function showProfile($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);

		$data['is_owner'] = $this->user->isOwner();
        $data['user_id'] = $user_id;

        $js_var = array();
        $js_files['jcrop'] = 'jquery.Jcrop.min.js';
        $js_files['profile_page'] = 'profile_page.js';
        $js_files['tokeninput'] = 'jquery.tokeninput.js';
        $js_files['autosize'] = 'jquery.autosize-min.js';
        $js_files['bootstrap_dialog'] = 'bootstrap-dialog.js';

        if (!Auth::check())
        {
        	$js_files['login'] = 'login.js';
        	$js_files['facebook'] = 'facebook.js';
        	$js_var['facebook_app_id'] = Config::get('app.facebook.appId');
    	}
        
    	$js_var['loadingText'] = Lang::get('global.loading');
    	$js_var['notImageError'] = Lang::get('profile.not_image_error');
    	$js_var['modalErrorHeaderText'] = Lang::get('profile.try_a_different_image');
    	$js_var['modalErrorText'] = Lang::get('profile.banner_too_small');
    	$js_var['user_id'] = $data['user_id'];       		

        if ($user_type == 'page')
        {
        	$page = $info->page;
            $data['page_status'] = profileStatus($page->status);
        	if (!isPagePublic($data['page_status']['id']) and !$data['is_owner'] and role() != 3) return Redirect::to('/');
        	if ($data['page_status']['id'] == 2) $this->layout->topAlertInfo = Lang::get('pages.page_under_moderation_message');
        	if ($data['page_status']['id'] == 4) $this->layout->topAlertDanger = Lang::get('pages.this_page_is_blocked_by_moderator', array('terms_link' => URL::route('terms'), 'privacy_link' => URL::route('privacy')));
        	if ($data['page_status']['id'] == 6) $this->layout->topAlertDanger = Lang::get('pages.this_page_is_disapproved_by_moderator', array('terms_link' => URL::route('terms'), 'privacy_link' => URL::route('privacy')));
            if ($data['page_status']['id'] == 5) $this->layout->topAlertDanger = Lang::get('pages.you_deactivated_this_page');

        	$page_city = $page->city;
            $page_city = $page_city ? $page_city->name.' ' : '';
            $page_province = $page->province;
            $page_province = $page_province ? $page_province->name.' ' : '';
            $page_country = $page->country->english_name;

            $this->layout->showGoogleMap = true;
            $this->layout->page_status = $data['page_status']['id'];
            $this->layout->country_name = $page_country;
            $this->layout->province_name = $page_province;
            $this->layout->city_name = $page_city;

        	$data['links'] = array(
        		'facebook' => $page->facebook,
        		'twitter' => $page->twitter,
        		'google' => $page->google,
        		'youtube' => $page->youtube,
        		'website' => $page->website
        	);

        	$this->layout->friends = array();
        	$js_var['like'] = Lang::get('profile.like');
        	$js_var['liked'] = Lang::get('profile.liked');
        	$js_var['unlike'] = Lang::get('profile.unlike');
            $js_var['deletePageReviewUrl'] = URL::route('deletePageReview');
            $js_var['deleteReviewHeadingText'] = Lang::get('profile.delete_review');
            $js_var['deleteReviewConfirmMessage'] = Lang::get('profile.delete_review_confirm_message');
            $js_var['deleteReviewCommentHeadingText'] = Lang::get('profile.delete_review_comment');
            $js_var['deleteReviewCommentConfirmMessage'] = Lang::get('profile.delete_review_comment_confirm_message');
            $js_var['deleteButtonText'] = Lang::get('global.delete');
            $js_var['cancelButtonText'] = Lang::get('global.cancel');       	

        	$data['page_like_button'] = $this->user->pageLikeButton($user_id);
        	$data['page_map'] = $this->user->pageMap($user_id);
            $data['page_address'] = $page->address.' '.$page_city . $page_province . $page_country;

			$js_var['latitude'] = $data['page_map']['latitude'];
			$js_var['longitude'] = $data['page_map']['longitude'];
			$js_var['zoomLevel'] = $data['page_map']['zoom_level'];
			$js_var['address'] =  $data['page_address'];
    		

    		$js_files['googleMapExternal'] = 'googleMapExternal';
    		$js_files['googleMap'] = 'page_profile_google_map.js';
            $js_files['likeReview'] = 'like_review.js';            

    		$page_name = $page->page_name;
            $fullname = $page_name;
            $data['firstname'] = $page_name;
            $page_description = $page->description;
            $this->layout->title = $page_name.' '.$page_city.$page_province;

            if ($page_description != '')
            {
    		  $this->layout->metaDesc = strlen($page_description) > 156 ? substr($page_description, 0, 153).'...' : $page_description;
            }
            else
            {
                $this->layout->metaDesc = $page_name.' '.$data['page_address'];
            }

            $category_name = $page->category['category_name'];
            $sub_category_name = $page->subCategory['sub_category_name'];

            $this->layout->page_name = $page_name;
            $this->layout->page_id = $user_id;
            $this->layout->facebook_page = $data['links']['facebook'];
            $this->layout->category_name = $category_name;
            $this->layout->sub_category_name = $sub_category_name;          

            $data['profile_picture'] = profileImage($page, 'large', true);            
            $data['banner_picture'] = coverImage($page, 'xxlarge', true);
            $data['total_likes'] = $page->likes;
            $data['user'] = $page;
            $data['category_slug'] = Str::slug($category_name);
            $data['sub_category_slug'] = Str::slug($sub_category_name);
            $data['sub_category_id'] = $page->sub_category_id;
            $data['city_id'] = $page->city_id;
            $data['page_id'] = $user_id;
            $data['reviews'] = $this->user->pageReviews($user_id);

            $this->layout->category_url = action('PagesController@show', array($data['category_slug'], 'city' => $data['city_id']));
            $this->layout->sub_category_url = action('PagesController@show', array($data['category_slug'], 'subcat' => $data['sub_category_id'], 'city' => $data['city_id']));
        }

        if ($user_type == 'user')
        {
        	$data['user_status'] = profileStatus($info->user->status);
        	if (!isUserPublic($data['user_status']['id']) and !$data['is_owner'] and role() != 3) return Redirect::to('/');
        	if ($data['user_status']['id'] == 2) $this->layout->topAlertInfo = Lang::get('pages.page_under_moderation_message');
        	if ($data['user_status']['id'] == 4) $this->layout->topAlertDanger = Lang::get('pages.this_page_is_blocked_by_moderator');
            if ($data['user_status']['id'] == 5) $this->layout->topAlertDanger = Lang::get('profile.your_profile_is_deactivated', array('url' => URL::route('settings')));

        	$data['links'] = $this->user->userLinks($user_id);
        	$data['friendship_status'] = $this->user->friendshipStatus($user_id);
        	$this->layout->friends = $this->user->friends($user_id);

        	$js_var['friends'] = Lang::get('global.friends');
        	$js_var['unfriend'] = Lang::get('profile.unfriend');
        	$js_var['pendingFriendRequest'] = Lang::get('global.pending_friend_request');
        	$js_var['addFriendText'] = Lang::get('global.add_friend');        	

        	//$js_files['add_friend'] = 'add_friend.js';

        	$fullname = userFullName($info->user);
            $this->layout->title = $fullname;            

        	$data['activities'] = $this->user->activities($data['user_id']);
            $data['firstname'] = $info->user->firstname;
            $data['profile_picture'] = profileImage($info->user, 'large', true);            
            $data['banner_picture'] = coverImage($info->user, 'xxlarge', true);
            $data['user'] = $info->user;
            
        }

        if(Session::has('confirmEmailSuccess'))
        {
        	$js_var['account_confirmed_message_url'] = action('ModalsController@getEmailConfirmedMessage', Auth::user()->email);
            Session::forget('confirmEmailSuccess');
        }

        $this->layout->inverse = false;
        $this->layout->css = array('token-input.css', 'token-input-facebook.css');
        $this->layout->js_var = $js_var;
		$this->layout->js = $js_files;
        $this->layout->user_type = $user_type;
        $this->layout->photos = array('total' => 0);
        
        if ($user_type == 'page' || ($user_type == 'user' && Auth::check()))
        {
            $this->layout->photos = $this->image->userPhotos($username);
        }

        $data['fullname'] = $fullname;
        $data['username'] = $username;
        $data['user_type'] = $user_type;       

        $data['change_profile_pic_url'] = URL::action('ModalsController@getAlbums', array($data['user_id'], $data['user_type'], $data['username'], 'changeprofile'));
        $data['change_cover_url'] = URL::action('ModalsController@getAlbums', array($data['user_id'], $data['user_type'], $data['username'], 'changecover'));
        $data['crop_profile_pic_url'] = URL::action('ModalsController@getCrop', array($data['profile_picture']['album_id'], $data['profile_picture']['image_id'], $username, $data['profile_picture']['filename']));              
                
        $this->layout->google_ads = $info->google_ads;
        $this->layout->is_owner = $data['is_owner'];
        $this->layout->view = $data;
        $this->layout->username = $username;

        if ($data['banner_picture']['filename'] != 'default_cover.jpg')
        {
            $this->layout->ogImage = $data['banner_picture']['url'];
        }
        else
        {
            $this->layout->ogImage = URL::asset('assets/img/banner.jpg');
        }

        $data['logged_user_id'] = Auth::check() ? Auth::user()->id : false;
        $data['currentRouteName'] = Route::currentRouteName();
        $data['view'] = $data;

        //$queries = DB::getQueryLog();
        //echo '<pre>';
        //print_r($queries);
        //dd();

        $this->layout->content = View::make('users.profile')->with($data);
	}

	public function showPages($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

        if ($user_type == 'page') return Redirect::route('showProfile', $username);

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);       
        
        $data['user_status'] = profileStatus($info->user->status);
        if (!isUserPublic($data['user_status']['id']) and !$data['is_owner'] and role() != 3) return Redirect::to('/');

		$fullname = userFullName($info->user);
		$this->layout->title = $fullname."'s ".Lang::get('global.pages');		
        $this->layout->inverse = false;
		$this->layout->js_var = array();
        $this->layout->js = array();
        $data['username'] = $username;
        $data['pages'] = $this->user->pages($username);
        $data['firstname'] = $info->user->firstname;
        $this->layout->user_type = $user_type;
        $data['is_owner'] = $this->user->isOwner();
        $this->layout->is_owner = $data['is_owner'];
        $this->layout->username = $username;
        $this->layout->view = $data;
        $this->layout->google_ads = $info->google_ads;
		$this->layout->content = View::make('users.pages')->with($data);
	}

	public function editPage($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);

		$data['is_owner'] = $this->user->isOwner();
        $this->layout->is_owner = $data['is_owner'];
        
		if (!$data['is_owner'])
		{
			return Redirect::route('showProfile', array('username' => $username));
		}

		$this->layout->robots = 'NOINDEX,NOFOLLOW';		
        $this->layout->inverse = false;
		$data['username'] = $username;
		$data['user_type'] = $user_type;        
        $this->layout->user_type = $user_type;        
        $data['profile_link'] = profileUrl($username);
        $data['currentRouteName'] = Route::currentRouteName();

        $this->layout->view = $data;
        $this->layout->google_ads = $info->google_ads;

        if ($user_type == 'page')
        {
			$data['page_id'] = $user_id;
            $data['user_info'] = $info->page;           

			if ($data['currentRouteName'] == 'editPage')
			{
    			$data['array_province'] = array_province($info->page->country_id);
                $data['array_cities'] = array_cities($info->page->province_id);
                $this->layout->js_var = array('notImageError' => Lang::get('profile.not_image_error'), 'modalErrorHeaderText' => Lang::get('profile.try_a_different_image'), 'province_or_state_label' => Lang::get('pages.province_state'), 'city_label' => Lang::get('pages.city_or_municipality'), 'choose_a_province_or_state' => Lang::get('pages.choose_a_province'), 'choose_a_city_or_municipality' => Lang::get('pages.choose_a_city'), 'choose_a_sub_category' => Lang::get('pages.choose_a_sub_category'));
            	$this->layout->js = array('jquery.Jcrop.min.js', 'jquery.autosize-min.js', 'categories.js', 'countries.js', 'create_page.js', 'profile_page.js');
        	}
        	else if ($data['currentRouteName'] == 'editMap')
        	{
        		$data['page_map'] = $this->user->pageMap($user_id);

        		$this->layout->js_var = array(
        			'latitude' => $data['page_map']['latitude'],
        			'longitude' => $data['page_map']['longitude'],
        			'zoomLevel' => $data['page_map']['zoom_level'],
        			'address' => $info->page->address.' '.$info->page->city->name.' '.$info->page->province->name.' '.$info->page->country->english_name
        		);

        		$this->layout->js = array(
        			'googleMapExternal' => 'googleMapExternal',
        			'googleMap' => 'update_google_map.js'
        		);        		
        	}
        	else if ($data['currentRouteName'] == 'editSchedule')
        	{
        		$data['page_schedule'] = array(
        			'schedule' => !$info->page->schedule ? '' : $info->page->schedule->schedule
        		);

        		$this->layout->js_var = array();
        		$this->layout->js = array('page_schedule.js');
        	}

            $this->layout->title = $info->page->page_name;
			$this->layout->content = View::make('pages.edit')->with($data);
		}
		else
		{
			$data['user_info'] = $info->user;
            $data['user_statuses'] = $this->user->userStatuses();
			$data['user_occupation'] = $this->user->userOccupation($user_id);
			$data['user_contact_info'] = $this->user->userContactInfo($user_id);
			$data['user_about'] = $this->user->userAbout($user_id);
			$data['user_basic_info'] = $this->user->userBasicInfo($user_id);
			$data['user_quotes'] = $this->user->userQuotes($user_id);
			$data['social_networks'] = $this->user->userSocialNetworks($user_id);
			$this->layout->js_var = array('notImageError' => Lang::get('profile.not_image_error'), 'modalErrorHeaderText' => Lang::get('profile.try_a_different_image'));
        	$this->layout->js = array('jquery.Jcrop.min.js', 'jquery.autosize-min.js', 'profile_page.js', 'update_user_info.js');
			$this->layout->title = userFullName($info->user);
            $this->layout->content = View::make('users.update_info')->with($data);
		}
	}

    public function albums($username)
    {
        $info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

        if ($user_type == 'user' && !Auth::check()) return Redirect::route('showProfile', $username);

        Session::put('username', $username);
        Session::put('user_id', $user_id);
        Session::put('user_type', $user_type);

        $data['albums'] = $this->user->getAlbums($user_id, $user_type, $username, false);
        $data['user_profile'] = profileUrl($username);
        $data['is_owner'] = $this->user->isOwner();

        if ($user_type == 'user')
        {
            $data['title'] = Lang::get('profile.name_albums', array('name' => $info->user->firstname));
        }
        elseif ($user_type == 'page')
        {
            $data['title'] = Lang::get('profile.name_albums', array('name' => $info->page->page_name));
        }

        $this->layout->google_ads = false;
        $this->layout->title = $data['title'];
        $this->layout->js_var = array();
        $this->layout->content = View::make('users.albums')->with($data);
    }

    public function photos($username, $album_id)
    {
        $info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

        if ($user_type == 'user' && !Auth::check()) return Redirect::route('showProfile', $username);             

        Session::put('username', $username);
        Session::put('user_id', $user_id);
        Session::put('user_type', $user_type);

        $data['photos'] = $this->user->getAlbumPhotos($album_id, $user_id, $user_type, $username, 'carousel');
        $data['user_profile'] = profileUrl($username);
        $data['user_albums'] = URL::route('albums', $username);
        $data['is_owner'] = $this->user->isOwner();
        $data['album_id'] = $album_id;

        if ($user_type == 'user')
        {
            $data['title'] = Lang::get('profile.name_photos', array('name' => $info->user->firstname));
        }
        elseif ($user_type == 'page')
        {
            $data['title'] = Lang::get('profile.name_photos', array('name' => $info->page->page_name));
        }

        $this->layout->google_ads = false;
        $this->layout->title = $data['title'];
        $this->layout->js_var = array(
            'confirmDeletePhotoTitle' => Lang::get('modal.delete_photo'),
            'confirmDeletePhotoMessage' => Lang::get('modal.delete_photo_message'),
            'confirmDeletePhotoOk' => Lang::get('modal.delete'),
            'cancelText' => Lang::get('global.cancel')
        );
        $this->layout->js = array('bootstrap-dialog.js', 'jquery.Jcrop.min.js', 'photos.js');
        $this->layout->content = View::make('users.photos')->with($data);
    }

    public function review($username, $review_id)
    {
        $info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

        if ($user_type == 'user') return Redirect::route('showProfile', $username);

        $data['review'] = $this->user->pageReview($review_id);

        if (!$data['review']) return Redirect::route('showProfile', $username);

        $js_var = array();
        $js_files['bootstrap_dialog'] = 'bootstrap-dialog.js';
        $js_files['autosize'] = 'jquery.autosize-min.js';
        $js_files['likeReview'] = 'like_review.js';
        $js_var['deletePageReviewUrl'] = URL::route('deletePageReview');
        $js_var['deleteReviewHeadingText'] = Lang::get('profile.delete_review');
        $js_var['deleteReviewConfirmMessage'] = Lang::get('profile.delete_review_confirm_message');
        $js_var['deleteReviewCommentHeadingText'] = Lang::get('profile.delete_review_comment');
        $js_var['deleteReviewCommentConfirmMessage'] = Lang::get('profile.delete_review_comment_confirm_message');
        $js_var['deleteButtonText'] = Lang::get('global.delete');
        $js_var['cancelButtonText'] = Lang::get('global.cancel');        

        if (!Auth::check())
        {
            $js_files['login'] = 'login.js';
            $js_files['facebook'] = 'facebook.js';
            $js_var['facebook_app_id'] = Config::get('app.facebook.appId');
        }

        $data['logged_user_id'] = Auth::check() ? Auth::user()->id : false;
        $data['username'] = $username;
        $this->layout->js_var = $js_var;
        $this->layout->js = $js_files;
        $this->layout->title = $data['review']['reviewer_name'].' '.Lang::get('pages.wrote_a_review_about').' '.$data['review']['page_name'];
        $this->layout->metaDesc = strlen($data['review']['review']) > 156 ? substr($data['review']['review'], 0, 153).'...' : $data['review']['review'];
        $this->layout->content = View::make('pages.review')->with($data);
    }    

	public function messages($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);

		$data['is_owner'] = $this->user->isOwner();
        $this->layout->is_owner = $data['is_owner'];
        
		if (!$data['is_owner'])
		{
			return Redirect::route('showProfile', array('username' => $username));
		}

		$js_var = array(
			'deleteText' => Lang::get('global.delete'),
			'deleteConversationHeadingText' => Lang::get('profile.delete_conversation_heading_text'),
			'deleteConversationBodyText' => Lang::get('profile.delete_conversation_body_text'),
		);
		$js_files = array('messages.js');

		$data['profile_link'] = profileUrl($username);
        $data['currentRouteName'] = Route::currentRouteName();
        $data['username'] = $username;
        $data['user_type'] = $user_type;

        $data['messages'] = $this->user->messages($user_id, $user_type);

		$this->layout->google_ads = $info->google_ads;
        $this->layout->title = Lang::get('profile.messages');
        $this->layout->robots = 'NOINDEX,NOFOLLOW';
		$this->layout->js_var = $js_var;
		$this->layout->js = $js_files;
		$this->layout->content = View::make('users.messages')->with($data);
	}

	public function message($username, $id)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);

		$data['is_owner'] = $this->user->isOwner();
        $this->layout->is_owner = $data['is_owner'];
        
		if (!$data['is_owner'])
		{
			return Redirect::route('showProfile', array('username' => $username));
		}

		$data['message'] = $this->user->message($user_id, $user_type, $id);

        if (!$data['message']) return Redirect::route('messages', array('username' => $username));

        $js_var = array();
		$js_files = array();

		$data['profile_link'] = profileUrl($username);
        $data['currentRouteName'] = Route::currentRouteName();
        $data['username'] = $username;
        $data['conversation_id'] = $id;
        $data['user_type'] = $user_type;
        $data['user_id'] = $user_id;

        $js_files['reply'] = 'reply.js'; 

		$this->layout->google_ads = $info->google_ads;
        $this->layout->title = Lang::get('profile.messages');
        $this->layout->robots = 'NOINDEX,NOFOLLOW';
		$this->layout->js_var = $js_var;
		$this->layout->js = $js_files;
		$this->layout->content = View::make('users.message')->with($data);
	}

	public function deleteConversation($username)
	{
		return $this->user->deleteConversation($username);
	}

	public function pageLikes($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);

		if ($user_type == 'user') return Redirect::route('showProfile', array('username' => $username));

		$data['user_info'] = $info->page;
        $data['fullname'] = userFullName($data['user_info'], 'page');

		$this->layout->google_ads = $info->google_ads;
        $this->layout->title = Lang::get('profile.people_who_like').' '.$data['fullname'];
        $this->layout->metaDesc = strlen($data['user_info']['description']) > 156 ? substr($data['user_info']['description'], 0, 153).'...' : $data['user_info']['description'];
		$this->layout->js_var = array();
		$this->layout->js = array();

		$data['likes'] = $this->user->peopleWhoLikes($user_id);

        if (!count($data['likes'])) return Redirect::route('showProfile', array('username' => $username));

		$data['page_profile_link'] = profileUrl($username);

		$this->layout->content = View::make('pages.likes')->with($data);
	}

    public function likeReview($username, $review_id)
    {
        return $this->user->likeUnlikeReview($username, $review_id);
    }

    public function likeUnlikeReviewComment()
    {
        return $this->user->likeUnlikeReviewComment();
    }

    public function commentReview($username, $review_id)
    {
        return $this->user->commentReview($username, $review_id);
    }

	public function crop()
	{
		$album_id = Input::get('album_id');
        $image_id = Input::get('image_id');
        $filename = Input::get('filename');
        return $this->image->crop($album_id, $image_id, $filename);
	}

    public function makeAlbumCover($album_id, $image_id)
    {
        return $this->image->makeAlbumCover($album_id, $image_id);
    }

    public function deleteImage($album_id, $image_id)
    {
        return $this->image->deleteImage($album_id, $image_id);
    }

    public function addPhotos()
    {
        return $this->image->addPhotos();
    }

    public function uploadProfilePhoto()
    {
        return $this->image->uploadProfilePhoto();
    }

    public function uploadCoverPhoto()
    {
        return $this->image->uploadCoverPhoto();
    }

	public function removeProfilePic()
	{
		return $this->user->removeProfilePic();			
	}

	public function removeBannerPic()
	{
		return $this->user->removeBannerPic();			
	}   	

	public function changeProfileImage($album_id, $image_id)
    {
        return $this->image->changeProfileImage($album_id, $image_id);
    }

    public function changeCover($album_id, $image_id)
	{
		return $this->image->changeCover($album_id, $image_id);    
	}

	public function confirmEmail($username, $token)
	{
		return $this->user->confirmEmail($username, $token);
	}

	public function updateOccupation()
	{
		return $this->user->updateOccupation();
	}

	public function updateUserBasicInfo()
	{
		return $this->user->updateUserBasicInfo();
	}

	public function updateUserAbout()
	{
		return $this->user->updateUserAbout();
	}

	public function updateUserQuotes()
	{
		return $this->user->updateUserQuotes();
	}

	public function updateUserOccupation()
	{
		return $this->user->updateUserOccupation();
	}

	public function updateUserContactInfo()
	{
		return $this->user->updateUserContactInfo();
	}

	public function updateUserSocialNetworks()
	{
		return $this->user->updateUserSocialNetworks();
	}

	public function updatePageMap($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);

		$data['is_owner'] = $this->user->isOwner();
        
		if (!$data['is_owner'])
		{
			return Redirect::route('showProfile', array('username' => $username));
		}

		return $this->user->updatePageMap();
	}

	public function updatePageSchedule($username)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);
		
		$data['is_owner'] = $this->user->isOwner();
        
		if (!$data['is_owner'])
		{
			return Redirect::route('showProfile', array('username' => $username));
		}

		return $this->user->updatePageSchedule();
	}

	public function reply($username, $conversation_id)
	{
		$info = $this->user->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

		Session::put('username', $username);
		Session::put('user_id', $user_id);
		Session::put('user_type', $user_type);
		
		$data['is_owner'] = $this->user->isOwner();
        
		if (!$data['is_owner'])
		{
			return unknown_error();
		}

		return $this->user->reply($username, $user_id, $user_type, $conversation_id);
	}

	public function reportProfile($profile_id, $type)
	{
		return $this->user->reportProfile($profile_id, $type);
	}

    public function watch()
    {
        $data['video_id'] = Input::get('v');
        $data['video_info'] = $this->video->getVideoInfo($data['video_id']);
        $this->layout->suggestedVideos = $this->video->getSuggestedVideos($data['video_info']['category_id'], $data['video_info']['location']);

        $description = $data['video_info']['description'];
        $this->layout->metaDesc = strlen($description) > 155 ? substr($description, 0, 155).'...' : $description;
        $this->layout->title = $data['video_info']['title'];
        $this->layout->ogImage = 'https://i1.ytimg.com/vi/'.$data['video_id'].'/hqdefault.jpg';
        $this->layout->js = array('watch.js');
        $this->layout->google_ads = $data['video_info']['google_ads'];
        $this->layout->content = View::make('videos.watch')->with($data);
    }
}