<?php namespace Edongkoy\Repositories\Users;

# app/library/Edongkoy/Users/EloquentProfleRepository.php

use Edongkoy\Repositories\Users\Models\Usernames;
use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Users\Models\UserStatus;
use Edongkoy\Repositories\Users\Models\RelationshipStatus;
use Edongkoy\Repositories\Users\Models\UserBasicInfo;
use Edongkoy\Repositories\Users\Models\UserInfoVisibility;
use Edongkoy\Repositories\Users\Models\Visibility;
use Edongkoy\Repositories\Users\Models\UserAboutQuotes;
use Edongkoy\Repositories\Users\Models\UserOccupation;
use Edongkoy\Repositories\Users\Models\UserContactInfo;
use Edongkoy\Repositories\Users\Models\UserSocialNetworks;
use Edongkoy\Repositories\Users\Models\UserFriends;
use Edongkoy\Repositories\Users\Models\UserNotifications;
use Edongkoy\Repositories\Users\Models\UserPagesLikes;
use Edongkoy\Repositories\Users\Models\UserConversation;
use Edongkoy\Repositories\Users\Models\UserUnreadMessage;
use Edongkoy\Repositories\Users\Models\Message;
use Edongkoy\Repositories\Users\Models\ReportedByMember;
use Edongkoy\Repositories\Users\Models\ReportedByNonMember;
use Edongkoy\Repositories\Users\Models\UserReviewsLikes;
use Edongkoy\Repositories\Users\Models\UserActivity;
use Edongkoy\Repositories\Image\Models\ProfileImages;
use Edongkoy\Repositories\Image\Models\CoverImages;
use Edongkoy\Repositories\Image\Models\Images;
use Edongkoy\Repositories\Image\Models\Albums;
use Edongkoy\Repositories\Page\Models\PageMap;
use Edongkoy\Repositories\Page\Models\Page;
use Edongkoy\Repositories\Page\Models\PageSchedule;
use Edongkoy\Repositories\Page\Models\PageReviews;
use Edongkoy\Repositories\Page\Models\PageReviewComments;
use Edongkoy\Repositories\Page\Models\PageReviewCommentLikes;
use Edongkoy\Repositories\Emails\Models\UserEmailConfirmation;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class EloquentProfileRepository implements ProfileRepositoryInterface {

	
	/**
	* Retrieve user info from $table with an id = $id
	* @param $model
	* @param $id
	* @param array $needed
	* @return array $user
	*/
	public function findById($model, $id, $needed = null, array $condition = null)
	{
		if (is_null($needed))
		{
			$query = $model::find($id);
		}
		else
		{
			$query = $model::select(DB::raw($needed))
								->where('id', '=', $id)
								->first();
		}

		if(!$query) throw new \NotFoundException('User not found by id');

		return $query;
	}

	/**
	* Retrieve user info by username
	* @param $model
	* @param $username
	* @param array $needed
	* @return array
	*/
	public function findByUsername($username, $needed = null)
	{
		$model = $this->userType($username) == 'user' ? 'Edongkoy\Repositories\Users\Models\User' : 'Edongkoy\Repositories\Page\Models\Page';

		return $this->findById($model, $this->userId($username), $needed);
	}

	public function pages($username, $limit = 10, $needed = null)
	{
		if (is_null($needed))
		{
			
			if ($this->isOwner() || role() == 3)
			{
				$query = Page::where('user_id', Session::get('user_id'))
						->orderBy('pages.id', 'desc')
						->paginate(5);
			}
			else
			{
				$query = Page::join('page_status', 'pages.id', '=', 'page_status.page_id')
						->where('pages.user_id', Session::get('user_id'))
						->where('page_status.status_id', 1)
						->orderBy('pages.id', 'desc')
						->paginate(5);
			}				
			
		}
		else
		{
			$query = Page::select(DB::raw($needed))
							->where('user_id', Session::get('user_id'))
							->paginate(5);
		}

		$data = array();
        
        if ($query)
        {
            $ctr = 1;
            $total = count($query);
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');
                $data[$key]['date'] = time_ago($value->created_at);
                $data[$key]['status'] = $this->isOwner() ? '<div>'.pageStatus($value->status).'</div>' : '';
                $data[$key]['ctr'] = $ctr;

                if ($ctr != $total)
                {
                    $ctr = $ctr == 4 ? 0 : $ctr;
                    $ctr++;
                }
                else
                {
                    $data[$key]['ctr'] = 'last';
                }
            }
        }

        return array(
        	'data' => $data,
        	'pagination' => $query->links()
        );	
	}		

	public function profileImagePreview($type, $id)
	{
		if ($type == 'user')
		{
			$query = User::find($id);
			$name = $query->firstname;
			$src = profileImage($query, 'xxlarge');
		}
		elseif ($type == 'page')
		{
			$query = Page::find($id);
			$name = $query->page_name;
			$src = profileImage($query, 'xxlarge');
		}

		return array(
			'name' => $name,
			'src' => $src
		);
	}

	public function updateProfileImage($image_id, $filename)
	{
		$profile = ProfileImages::withTrashed()->find($image_id);
		$profile->filename = $filename;
		$profile->type = 1;
		$profile->save();

		if ($profile->trashed()) $profile->restore();

		return true;
	}	

	public function removeProfilePic()
	{
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');
		
		if (!$this->isOwner()) return status_error();

		$query = ProfileImages::select(DB::raw('id'))
						->where('user_id', $user_id)
						->where('user_type', $user_type)
						->first();

		if (!$query) return status_error();

		$query->delete();

		$image = defaultProfileImage();

		return status_ok(array('url' => $image['url']));
	}

	public function removeBannerPic()
	{
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');
		
		if (!$this->isOwner()) return status_error(array('message' => 'Not the owner'));

		$query = CoverImages::select(DB::raw('id'))
						->where('user_id', $user_id)
						->where('user_type', $user_type)
						->first();

		if (!$query) return status_error(array('message' => 'no cover image'));

		$query->delete();

		$image = defaultCoverImage();

		return status_ok(array('url' => $image['url']));
	}

	public function usernameInfo($username)
	{
		$user = Usernames::where('username', $username)->first();
				
		if (!$user) throw new \NotFoundException('User not found by id');

		return $user;
	}

	public function userId($username)
	{
		$user = Usernames::select(DB::raw('user_id'))
				->where('username', $username)
				->first();

		if (!$user) throw new \NotFoundException('User not found');

		return $user->user_id;
	}

	public function userType($username)
	{
		$user = Usernames::select(DB::raw('user_type'))
				->where('username', $username)
				->first();

		if (!$user) throw new \NotFoundException('User not found');

		return $user->user_type;
	}
	
	public function getAlbums($user_id, $user_type, $username, $modal = true)
	{
		$albums = Albums::select(DB::raw('id, name, album_type'))
				->where('user_id', $user_id)
				->where('user_type', $user_type)
				->get();

		$total = $albums->count();		
		$data = array();
		$ctr = 0;

		if ($total)
		{
			foreach ($albums as $key => $value)
			{
				$album_id = $value->id;
				$album_type = $value->album_type;				
				$default = defaultProfileImage();
				$data[$key]['id'] = $album_id;
				$data[$key]['label'] = $album_type == 1 || $album_type == 2 ? Lang::get($value->name) : $value->name;				
				$data[$key]['img_url'] = $default['url'];
				$data[$key]['total_photos'] = Lang::get('profile.empty');

				$album_cover = !$value->albumCover ? $value->randomAlbumCover : $value->albumCover;
				
				if ($album_cover)
				{
					$filename = $album_cover->filename;
					$image_type = $album_cover->type;
					$cover = $album_type == 2 && $image_type != 0 ? true : false;
				}		 				
				
				if ($modal)
				{
					$data[$key]['photos_url'] = action('ModalsController@getPhotos', $data[$key]['id']);
					
					if ($album_cover)
					{
						$data[$key]['img_url'] = imageUrl($filename, $username, 'xlarge', $cover, $image_type);
					}
				}
				else
				{
					$data[$key]['photos_url'] = URL::route('photos', array($username, $album_id));
					
					if ($album_cover)
					{
						$total_photos = $value->images()->count();
						$data[$key]['img_url'] = imageUrl($filename, $username, 'large', $cover, $image_type);
						$data[$key]['total_photos'] = $total_photos.' '.strtolower(Lang::choice('profile.number_photos', $total_photos));
					}
				}

				$ctr++;

				$data[$key]['ctr'] = $ctr;

				if ($ctr == 3) $ctr = 0;
			}
		}

		return array(
			'total' => $total,
			'data' => $data
		);	
	}

	public function getAlbumPhotos($album_id, $user_id, $user_type, $username, $action = '')
	{
		$album = Albums::find($album_id);
		$album_type = $album->album_type;
		$album_name = $album_type == 1 || $album_type == 2 ? Lang::get($album->name) : $album->name;
		$updated_at = time_ago($album->updated_at);

		$images = Images::select(DB::raw('id, filename, type'))
					->where('album_id', $album_id)
					->orderBy('id', 'desc')
					->get();

		$total = $images->count();		
		$data = array();
		$ctr = 0;

		if ($total)
		{
			foreach ($images as $key => $value)
			{
				$filename = $value->filename;
				$image_id = $value->id;
				$image_type = $value->type;
				$cover = $album_type == 2 && $image_type != 0 ? true : false;
				$data[$key]['id'] = $image_id;
				$data[$key]['img_url'] = imageUrl($filename, $username, 'xlarge', $cover, $image_type);
				$make_profile_url = URL::route('changeProfileImage', array($album_id, $image_id));
				$make_cover_url = URL::route('changeCover', array($album_id, $image_id));

				if ($action == 'changecover')
				{
					$data[$key]['action_url'] = $make_cover_url;				
				}
				elseif ($action == 'changeprofile')
				{
					$data[$key]['action_url'] = $make_profile_url;
				}
				elseif ($action == 'carousel')
				{
					$data[$key]['action_url'] = URL::action('ModalsController@getCarousel', array($user_id, $user_type, $username, $image_id, $album_id));
					$data[$key]['img_url'] = imageUrl($filename, $username, 'large', $cover, $image_type);
					$data[$key]['crop_url'] = action('ModalsController@getCrop', array($album_id, $image_id, $username, $filename));
					$data[$key]['make_profile_url'] = $make_profile_url;
					$data[$key]['make_cover_url'] = $make_cover_url;
					$data[$key]['make_album_cover_url'] = URL::route('makeAlbumCover', array($album_id, $image_id));
					$data[$key]['delete_image_url'] = URL::route('deleteImage', array($album_id, $image_id));				
				}
				elseif ($action == 'modalCarousel')
				{
					$data[$key]['img_url'] = imageUrl($filename, $username, 'xxlarge', $cover, $image_type);
				}
				
				$ctr++;

				$data[$key]['ctr'] = $ctr;

				if ($ctr == 3) $ctr = 0;
			}
		}

		return array(
			'total' => $total,
			'data' => $data,
			'album_name' => $album_name,
			'updated_at' => $updated_at
		);
	}

	public function isOwner()
	{
		if (Auth::check())
		{
			$user_id = Session::get('user_id');
			$logged_user_id = Auth::user()->id;

			if (Session::get('user_type') == 'user')
			{
				return $user_id == $logged_user_id ? true : false;
			}
			elseif (Session::get('user_type') == 'page')
			{
				$page = Page::select(DB::raw('user_id'))
						->where('id', $user_id)
						->first();

				if ($page) return $page->user_id == $logged_user_id ? true : false;				
			}
		}
		
		return false;		
	}

	public function confirmEmail($username, $token)
	{
		$query = UserEmailConfirmation::where('token', $token)->first();

		if (!$query) return Redirect::to('/');

		$user_id = $query->user_id;

		if ($user_id != Auth::user()->id) return Redirect::to('/');

		UserStatus::where('user_id', $user_id)->update(array('status_id' => 1));

		Session::put('confirmEmailSuccess', 1);
		
		$query->delete();

		return Redirect::to('/'.$username);
	}

	public function updateUserBasicInfo()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UpdateUserBasicInfo;

			if ($validation->passes())
			{
				$gender = Input::get('gender');
				$men = Input::get('men') == 'on' ? 1 : 0;
				$women = Input::get('women') == 'on' ? 1 : 0;
				$month = Input::get('month');
				$day = Input::get('day');
				$year = Input::get('year');
				$relationship_status = Input::get('relationshipStatus');
				$languages = Input::get('languages');
				$religion = Input::get('religion');
				$birthday = $day.'-'.$month.'-'.$year;
				$user_id = Auth::user()->id;

				if($birthday != '0-0-0')
				{
					if (!checkdate($month, $day, $year))
					{
						return status_error(array('message' => Lang::get('profile.birthday_error')));
					}

					$birthday = date('Y-m-d', strtotime($birthday));
				}
				else
				{
					$birthday = '0000-00-00';
				}

				$flag = UserBasicInfo::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if ($flag)
				{
					$user_basic_info_id = $flag->id;

					$user = UserBasicInfo::find($user_basic_info_id);

					if (!$user) return server_error();

					$user->gender = $gender;
					$user->birthday = $birthday;
					$user->men = $men;
					$user->women = $women;
					$user->relationship_status = $relationship_status;
					$user->languages = $languages;
					$user->religion = $religion;
					$user->save();
				}
				else
				{
					$user = UserBasicInfo::create(array(
						'user_id' => $user_id,
						'gender' => $gender,
						'birthday' => $birthday,
						'women' => $women,
						'men' => $men,
						'relationship_status' => $relationship_status,
						'languages' => $languages,
						'religion' => $religion
					));

					if (!$user) return server_error();

					$user_basic_info_id = $user->id;
				}

				$gender_visibility = Input::get('genderVisibility');
				$birthday_visibility = Input::get('birthdayVisibility');
				$interested_in_visibility = Input::get('interestedInVisibility');
				$relationship_status_visibility = Input::get('relationshipStatusVisibility');
				$languages_visibility = Input::get('languagesVisibility');
				$religion_visibility = Input::get('religionVisibility');

				$flag = UserInfoVisibility::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if($flag)
				{
					$user = UserInfoVisibility::find($flag->id);

					if (!$user) return server_error();

					$user->gender = $gender_visibility;
					$user->birthday = $birthday_visibility;
					$user->interested_in = $interested_in_visibility;
					$user->relationship_status = $relationship_status_visibility;
					$user->languages = $languages_visibility;
					$user->religion = $religion_visibility;

					if (!$user->user_basic_info_id) $user->user_basic_info_id = $user_basic_info_id;

					$user->save();
				}
				else
				{
					$user = UserInfoVisibility::create(array(
						'user_id' => $user_id,
						'gender' => $gender_visibility,
						'birthday' => $birthday_visibility,
						'interested_in' => $interested_in_visibility,
						'relationship_status' => $relationship_status_visibility,
						'languages' => $languages_visibility,
						'religion' => $religion_visibility,
						'user_basic_info_id' => $user_basic_info_id
					));

					if (!$user) return server_error();
				}

				return status_ok(array('message' => Lang::get('global.update_saved')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function userBasicInfo($user_id)
	{
		$query = UserBasicInfo::where('user_id', $user_id)->take(1)->first();
		
		if (!$query)
		{
			return array(
				'genderVisibility' => 1,
				'birthdayVisibility' => 5,
				'interestedInVisibility' => 1,
				'relationshipStatusVisibility' => 1,
				'languagesVisibility' => 1,
				'religionVisibility' => 1,
				'women' => 0,
				'men' => 0,
				'genderVisibilityText' => Lang::get('profile.public'),
				'birthdayVisibilityText' => Lang::get('profile.public_full_date'),
				'interestedInVisibilityText' => Lang::get('profile.public'),
				'relationshipStatusVisibilityText' => Lang::get('profile.public'),
				'languagesVisibilityText' => Lang::get('profile.public'),
				'religionVisibilityText' => Lang::get('profile.public')
			);
		}

		$birthday = explode('-', $query->birthday);
		$year = $birthday[0];
		$month = $birthday[1];
		$day = $birthday[2];
		$genderVisibility = 1;

		foreach($query->genderVisibility as $value)
		{
			$genderVisibility = $value->id;
			$genderVisibilityText = $value->name;
		}

		foreach($query->birthdayVisibility as $value)
		{
			$birthdayVisibility = $value->id;
			$birthdayVisibilityText = $value->name;
		}

		foreach($query->interestedInVisibility as $value)
		{
			$interestedInVisibility = $value->id;
			$interestedInVisibilityText = $value->name;
		}

		foreach($query->relationshipStatusVisibility as $value)
		{
			$relationshipStatusVisibility = $value->id;
			$relationshipStatusVisibilityText = $value->name;
		}

		foreach($query->languagesVisibility as $value)
		{
			$languagesVisibility = $value->id;
			$languagesVisibilityText = $value->name;
		}

		foreach($query->religionVisibility as $value)
		{
			$religionVisibility = $value->id;
			$religionVisibilityText = $value->name;
		}

		return array(
			'gender' => $query->gender,
			'month' => $month,
			'day' => $day,
			'year' => $year,
			'women' => $query->women,
			'men' => $query->men,
			'relationshipStatus' => $query->relationship_status,
			'languages' => $query->languages,
			'religion' => $query->religion,
			'genderVisibility' => $genderVisibility,
			'birthdayVisibility' => $birthdayVisibility,
			'interestedInVisibility' => $interestedInVisibility,
			'relationshipStatusVisibility' => $relationshipStatusVisibility,
			'languagesVisibility' => $languagesVisibility,
			'religionVisibility' => $religionVisibility,
			'genderVisibilityText' => $genderVisibilityText,
			'birthdayVisibilityText' => $birthdayVisibilityText,
			'interestedInVisibilityText' => $interestedInVisibilityText,
			'relationshipStatusVisibilityText' => $relationshipStatusVisibilityText,
			'languagesVisibilityText' => $languagesVisibilityText,
			'religionVisibilityText' => $religionVisibilityText
		);
	}

	public function userStatuses()
	{
		$query = RelationshipStatus::select(DB::raw('id, name'))->get();

		$status = array();
		$status[0] = '---';
		
		foreach ($query as $key => $value)
		{
			$status[$value->id] = $value->name;
		}

		return $status;
	}

	public function updateUserAbout()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UserAbout;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$about = Input::get('aboutYou');

				$query = UserAboutQuotes::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if ($query)
				{
					$user = UserAboutQuotes::find($query->id);

					if (!$user) return server_error();

					$user_about_quotes_id = $query->id;
					
					$user->about = $about;
					$user->save();
				}
				else
				{
					$user = UserAboutQuotes::create(array('user_id' => $user_id, 'about' => $about));

					if(!$user) return server_error();

					$user_about_quotes_id = $user->id;
				}

				$flag = UserInfoVisibility::select(DB::raw('id'))->where('user_id', $user_id)->first();
				$user_about_visibility = Input::get('userAboutVisibility');

				if($flag)
				{
					$user = UserInfoVisibility::find($flag->id);

					if (!$user) return server_error();

					if (!$user->user_about_quotes_id) $user->user_about_quotes_id = $user_about_quotes_id;

					$user->about = $user_about_visibility;
					$user->save();
				}
				else
				{
					$user = UserInfoVisibility::create(array('user_id' => $user_id, 'about' => $user_about_visibility, 'user_about_quotes_id' => $user_about_quotes_id));

					if (!$user) return server_error();
				}

				return status_ok(array('message' => Lang::get('global.update_saved')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function userAbout($user_id)
	{
		$user = UserAboutQuotes::where('user_id', $user_id)->first();

		if (!$user) return array(
				'userAboutVisibility' => 1,
				'userAboutVisibilityText' => Lang::get('profile.public')
			);

		foreach($user->aboutVisibility as $value)
		{
			$userAboutVisibility = $value->id;
			$userAboutVisibilityText = $value->name;
		}

		return array(
				'aboutYou' => $user->about,
				'userAboutVisibility' => $userAboutVisibility,
				'userAboutVisibilityText' => $userAboutVisibilityText
			);
	}

	public function updateUserQuotes()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UserQuotes;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$quotes = Input::get('userQuotes');

				$query = UserAboutQuotes::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if ($query)
				{
					$user = UserAboutQuotes::find($query->id);

					if (!$user) return server_error();

					$user_about_quotes_id = $query->id;

					$user->quotes = $quotes;
					$user->save();
				}
				else
				{
					$user = UserAboutQuotes::create(array('user_id' => $user_id, 'quotes' => $quotes));

					if (!$user) return server_error();

					$user_about_quotes_id = $user->id;
				}

				$flag = UserInfoVisibility::select(DB::raw('id'))->where('user_id', $user_id)->first();
				$user_quotes_visibility = Input::get('userQuotesVisibility');

				if($flag)
				{
					$user = UserInfoVisibility::find($flag->id);

					if (!$user) return server_error();

					if (!$user->user_about_quotes_id) $user->user_about_quotes_id = $user_about_quotes_id;

					$user->quotes = $user_quotes_visibility;
					$user->save();
				}
				else
				{
					$user = UserInfoVisibility::create(array('user_id' => $user_id, 'quotes' => $user_quotes_visibility, 'user_about_quotes_id' => $user_about_quotes_id));

					if (!$user) return server_error();
				}

				return status_ok(array('message' => Lang::get('global.update_saved')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function userQuotes($user_id)
	{
		$user = UserAboutQuotes::where('user_id', $user_id)->first();

		if (!$user) return array(
				"userQuotesVisibility" => 1,
				"userQuotesVisibilityText" => Lang::get('profile.public')
			);

		foreach($user->quotesVisibility as $value)
		{
			$userQuotesVisibility = $value->id;
			$userQuotesVisibilityText = $value->name;
		}

		return array(
			'userQuotes' => $user->quotes,
			'userQuotesVisibility' => $userQuotesVisibility,
			'userQuotesVisibilityText' => $userQuotesVisibilityText
		);

	}

	public function updateUserOccupation()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UserOccupation;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$company_name = Input::get('companyName');
				$company_address = Input::get('companyAddress');
				$position = Input::get('position');

				$query = UserOccupation::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if ($query)
				{
					$user = UserOccupation::find($query->id);

					if (!$user) return server_error();

					$user_occupation_id = $query->id;

					$user->company_name = $company_name;
					$user->address = $company_address;
					$user->position = $position;
					$user->save();
				}
				else
				{
					$user = UserOccupation::create(array('user_id' => $user_id, 'company_name' => $company_name, 'address' => $company_address, 'position' => $position));

					if (!$user) return server_error();

					$user_occupation_id = $user->id;
				}

				$flag = UserInfoVisibility::select(DB::raw('id'))->where('user_id', $user_id)->first();
				$user_occupation_visibility = Input::get('userOccupationVisibility');

				if ($flag)
				{
					$user = UserInfoVisibility::find($flag->id);

					if (!$user) return server_error();

					if (!$user->user_occupation_id) $user->user_occupation_id = $user_occupation_id;

					$user->occupation = $user_occupation_visibility;
					$user->save();
				}
				else
				{
					$user = UserInfoVisibility::create(array('user_id' => $user_id, 'occupation' => $user_occupation_visibility, 'user_occupation_id' => $user_occupation_id));

					if (!$user) return server_error();
				}

				return status_ok(array('message' => Lang::get('global.update_saved')));

			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function userOccupation($user_id)
	{
		$user = UserOccupation::where('user_id', $user_id)->first();

		if(!$user) return array(
			'userOccupationVisibility' => 1,
			'userOccupationVisibilityText' => Lang::get('profile.public')
		);

		foreach($user->occupationVisibility as $value)
		{
			$userOccupationVisibility = $value->id;
			$userOccupationVisibilityText = $value->name;
		}

		return array(
			'companyName' => $user->company_name,
			'companyAddress' => $user->address,
			'position' => $user->position,
			'userOccupationVisibility' => $userOccupationVisibility,
			'userOccupationVisibilityText' => $userOccupationVisibilityText
		);
	}

	public function updateUserContactInfo()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UserContactInfo;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$mobile = Input::get('mobilePhone');
				$landline = Input::get('landline');
				$address = Input::get('address');
				$website = Input::get('website');

				$query = UserContactInfo::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if ($query)
				{
					$user = UserContactInfo::find($query->id);

					if (!$user) return server_error();

					$user_contact_info_id = $query->id;

					$user->mobile = $mobile;
					$user->landline = $landline;
					$user->address = $address;
					$user->website = $website;
					$user->save();
				}
				else
				{
					$user = UserContactInfo::create(array('user_id' => $user_id, 'mobile' => $mobile, 'landline' => $landline, 'address' => $address, 'website' => $website));

					if (!$user) return server_error();

					$user_contact_info_id = $user->id;
				}

				$query = UserInfoVisibility::select(DB::raw('id'))->where('user_id', $user_id)->first();

				$userEmailVisibility = Input::get('userEmailVisibility');
				$userMobileVisibility = Input::get('userMobileVisibility');
				$userLandlineVisibility = Input::get('userLandlineVisibility');
				$userAddressVisibility = Input::get('userAddressVisibility');
				$userWebsiteVisibility = Input::get('userWebsiteVisibility');

				if ($query)
				{
					$user = UserInfoVisibility::find($query->id);

					if (!$user) return server_error();

					$user->email = $userEmailVisibility;
					$user->mobile_phone = $userMobileVisibility;
					$user->landline = $userLandlineVisibility;
					$user->address = $userAddressVisibility;
					$user->website = $userWebsiteVisibility;

					if (!$user->user_contact_info_id) $user->user_contact_info_id = $user_contact_info_id;

					$user->save();
				}
				else
				{
					$user = UserInfoVisibility::create(array('user_id' => $user_id, 'email' => $userEmailVisibility, 'mobile_phone' => $userMobileVisibility, 'landline' => $userLandlineVisibility, 'address' => $userAddressVisibility, 'website' => $userWebsiteVisibility, 'user_contact_info_id' => $user_contact_info_id));

					if (!$user) return server_error();
				}

				return status_ok(array('message' => Lang::get('global.update_saved')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function userContactInfo($user_id)
	{
		$user = UserContactInfo::where('user_id', $user_id)->first();

		if (!$user) return array(
			'userEmailVisibility' => 2,
			'userMobileVisibility' => 2,
			'userLandlineVisibility' => 2,
			'userAddressVisibility' => 2,
			'userWebsiteVisibility' => 1,
			'userEmailVisibilityText' => Lang::get('profile.only_me'),
			'userMobileVisibilityText' => Lang::get('profile.only_me'),
			'userLandlineVisibilityText' => Lang::get('profile.only_me'),
			'userAddressVisibilityText' => Lang::get('profile.only_me'),
			'userWebsiteVisibilityText' => Lang::get('profile.public') 
		);

		foreach ($user->emailVisibility as $value)
		{
			$userEmailVisibility = $value->id;
			$userEmailVisibilityText = $value->name;
 		}

 		foreach ($user->mobileVisibility as $value)
 		{
 			$userMobileVisibility = $value->id;
 			$userMobileVisibilityText = $value->name;
 		}

 		foreach ($user->landlineVisibility as $value)
 		{
 			$userLandlineVisibility = $value->id;
 			$userLandlineVisibilityText = $value->name;
 		}

 		foreach ($user->addressVisibility as $value)
 		{
 			$userAddressVisibility = $value->id;
 			$userAddressVisibilityText = $value->name;
 		}

 		foreach ($user->websiteVisibility as $value)
 		{
 			$userWebsiteVisibility = $value->id;
 			$userWebsiteVisibilityText = $value->name;
 		}

 		return array(
 			'mobilePhone' => $user->mobile,
 			'landline' => $user->landline,
 			'address' => $user->address,
 			'website' => $user->website,
 			'userEmailVisibility' => $userEmailVisibility,
 			'userMobileVisibility' => $userMobileVisibility,
 			'userLandlineVisibility' => $userLandlineVisibility,
 			'userAddressVisibility' => $userAddressVisibility,
 			'userWebsiteVisibility' => $userWebsiteVisibility,
 			'userEmailVisibilityText' => $userEmailVisibilityText,
 			'userMobileVisibilityText' => $userMobileVisibilityText,
 			'userLandlineVisibilityText' => $userLandlineVisibilityText,
 			'userAddressVisibilityText' => $userAddressVisibilityText,
 			'userWebsiteVisibilityText' => $userWebsiteVisibilityText
 		);
	}

	public function updateUserSocialNetworks()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\UserSocialNetworks;

			if ($validation->passes())
			{
				$user_id = Auth::user()->id;
				$facebook = Input::get('facebook');
				$twitter = Input::get('twitter');
				$googlePlus = Input::get('googlePlus');
				$instagram = Input::get('instagram');
				$youtube = Input::get('youtube');

				$user = UserSocialNetworks::select(DB::raw('id'))->where('user_id', $user_id)->first();

				if ($user)
				{
					$user = UserSocialNetworks::find($user->id);

					if (!$user) return server_error();

					$user_social_networks_id = $user->id;

					$user->facebook = $facebook;
					$user->twitter = $twitter;
					$user->google_plus = $googlePlus;
					$user->instagram = $instagram;
					$user->youtube = $youtube;
					$user->save();
				}
				else
				{
					$user = UserSocialNetworks::create(array('user_id' => $user_id, 'facebook' => $facebook, 'twitter' => $twitter, 'google_plus' => $googlePlus, 'instagram' => $instagram, 'youtube' => $youtube));

					if (!$user) return server_error();

					$user_social_networks_id = $user->id;
				}

				$flag = UserInfoVisibility::select(DB::raw('id'))->where('user_id', $user_id)->first();

				$facebookVisibility = Input::get('userFacebookVisibility');
				$twitterVisibility = Input::get('userTwitterVisibility');
				$googlePlusVisibility = Input::get('userGooglePlusVisibility');
				$instagramVisibility = Input::get('userInstagramVisibility');
				$youtubeVisibility = Input::get('userYoutubeVisibility');

				if($flag)
				{
					$user = UserInfoVisibility::find($flag->id);

					if (!$user) return server_error();

					$user->facebook = $facebookVisibility;
					$user->twitter = $twitterVisibility;
					$user->google_plus = $googlePlusVisibility;
					$user->instagram = $instagramVisibility;
					$user->youtube = $youtubeVisibility;

					if (!$user->user_social_networks_id) $user->user_social_networks_id = $user_social_networks_id;

					$user->save();
				}
				else
				{
					$user = UserInfoVisibility::create(array('user_id' => $user_id, 'facebook' => $facebookVisibility, 'twitter' => $twitterVisibility, 'google_plus' => $googlePlusVisibility, 'instagram' => $instagramVisibility, 'youtube' => $youtubeVisibility, 'user_social_networks_id' => $user_social_networks_id));

					if (!$user) return server_error();
				}
				
				return status_ok(array('message' => Lang::get('global.update_saved')));
			}

			return $validation->jsonErrors();
		}
		
		return Redirect::to('/');
	}

	public function userSocialNetworks($user_id)
	{
		$user = UserSocialNetworks::where('user_id', $user_id)->first();

		if (!$user) return array(
			'userFacebookVisibility' => 1,
			'userTwitterVisibility' => 1,
			'userGooglePlusVisibility' => 1,
			'userInstagramVisibility' => 1,
			'userYoutubeVisibility' => 1,
			'userFacebookVisibilityText' => Lang::get('profile.public'),
			'userTwitterVisibilityText' => Lang::get('profile.public'),
			'userGooglePlusVisibilityText' => Lang::get('profile.public'),
			'userInstagramVisibilityText' => Lang::get('profile.public'),
			'userYoutubeVisibilityText' => Lang::get('profile.public')
		);

		foreach($user->facebookVisibility as $value)
		{
			$userFacebookVisibility = $value->id;
			$userFacebookVisibilityText = $value->name;
		}

		foreach($user->twitterVisibility as $value)
		{
			$userTwitterVisibility = $value->id;
			$userTwitterVisibilityText = $value->name;
		}

		foreach($user->googlePlusVisibility as $value)
		{
			$userGooglePlusVisibility = $value->id;
			$userGooglePlusVisibilityText = $value->name;
		}

		foreach($user->instagramVisibility as $value)
		{
			$userInstagramVisibility = $value->id;
			$userInstagramVisibilityText = $value->name;
		}

		foreach($user->youtubeVisibility as $value)
		{
			$userYoutubeVisibility = $value->id;
			$userYoutubeVisibilityText = $value->name;
		}

		return array(
			'facebook' => $user->facebook,
			'twitter' => $user->twitter,
			'googlePlus' => $user->google_plus,
			'instagram' => $user->instagram,
			'youtube' => $user->youtube,
			'userFacebookVisibility' => $userFacebookVisibility,
			'userTwitterVisibility' => $userTwitterVisibility,
			'userGooglePlusVisibility' => $userGooglePlusVisibility,
			'userInstagramVisibility' => $userInstagramVisibility,
			'userYoutubeVisibility' => $userYoutubeVisibility,
			'userFacebookVisibilityText' => $userFacebookVisibilityText,
			'userTwitterVisibilityText' => $userTwitterVisibilityText,
			'userGooglePlusVisibilityText' => $userGooglePlusVisibilityText,
			'userInstagramVisibilityText' => $userInstagramVisibilityText,
			'userYoutubeVisibilityText' => $userYoutubeVisibilityText
		);
	}

	public function userLinks($user_id, $visitor_id = null)
	{
		$facebook = '';
		$twitter = '';
		$google = '';
		$youtube = '';
		$website = '';
		$instagram = '';

		$social = UsersocialNetworks::where('user_id', $user_id)->first();
		$contact_info = UserContactInfo::where('user_id', $user_id)->first();

		if ($social)
		{
			foreach($social->facebookVisibility as $value)
			{
				$userFacebookVisibility = $value->id;				
			}

			foreach($social->twitterVisibility as $value)
			{
				$userTwitterVisibility = $value->id;
			}

			foreach($social->googlePlusVisibility as $value)
			{
				$userGooglePlusVisibility = $value->id;
			}

			foreach($social->instagramVisibility as $value)
			{
				$userInstagramVisibility = $value->id;
			}

			foreach($social->youtubeVisibility as $value)
			{
				$userYoutubeVisibility = $value->id;
			}

			if (is_null($visitor_id))
			{
				if ($userFacebookVisibility == 1) $facebook = $social->facebook;
				if ($userTwitterVisibility == 1) $twitter = $social->twitter;
				if ($userGooglePlusVisibility == 1) $google = $social->google_plus;
				if ($userInstagramVisibility == 1) $instagram = $social->instagram;
				if ($userYoutubeVisibility == 1) $youtube = $social->youtube;
			}
		}

		if ($contact_info)
		{
			foreach($contact_info->websiteVisibility as $value)
			{
				$userWebsiteVisibility = $value->id;
			}

			if (is_null($visitor_id))
			{
				if ($userWebsiteVisibility == 1) $website = $contact_info->website;				
			}
		}

		return array(
			'facebook' => $facebook,
			'twitter' => $twitter,
			'google' => $google,
			'youtube' => $youtube,
			'instagram' => $instagram,
			'website' => $website
		);


	}

	public function friendshipStatus($user_id)
	{
		if (Auth::check())
		{
			$logged_user_id = Auth::user()->id;

			$query = UserFriends::where('user_id', $logged_user_id)
					->where('friend_id', $user_id)
					->first();

			if ($query)
			{ 
				if ($query->accepted) return 'friends';

				return 'pending';
			}

			$query = UserFriends::where('user_id', $user_id)
					->where('friend_id', $logged_user_id)
					->first();

			if ($query) return 'confirm';
		}

		return 'add-friend';
	}

	public function friends($user_id)
	{
		$array = array();

		if (Auth::check())
		{
			$from_whom_ids = array();
			$to_whom_ids = array();
			$friends = array();
			$user_logged_id = 0;

			$query = UserFriends::where('user_id', $user_id)
						->where('accepted', 1)
						->orderBy('id', 'desc')
						->get();

		
			$user_logged_id = Auth::user()->id;
			$user_friends = UserFriends::where('user_id', $user_logged_id)->get();

			if (count($user_friends))
			{
				foreach($user_friends as $key => $friend)
				{
					if ($friend->accepted)
					{
						$friends[$key] = $friend->friend_id;
					}
					else
					{
						$to_whom_ids[$key] = $friend->friend_id;
					}
				}
			}
			
			$friend_request = UserFriends::where('friend_id', $user_logged_id)
								->where('accepted', 0)
								->get();

			if ($friend_request)
			{
				foreach($friend_request as $key => $request)
				{
					$from_whom_ids[$key] = $request->user_id; 
				}
			}
		
			foreach($query as $key => $user)
			{
				$array[$key]['link'] = '';

				if (in_array($user->friend_id, $to_whom_ids))
				{
					$array[$key]['link'] = Lang::get('global.pending_friend_request');				
				}
				else if (in_array($user->friend_id, $from_whom_ids))
				{
					$array[$key]['link'] = '<a href="#" class="confirm-friend-request" data-type="link" data-action="accept-friend-request" data-id="'.$user->friend_id.'">'.Lang::get('global.confirm_friend_request').'</a>';
				}	
				else if (in_array($user->friend_id, $friends))
				{
					$array[$key]['link'] = '<span class="glyphicon glyphicon-check green"></span> '.Lang::get('global.friends');
				}
				else
				{
					if ($user_logged_id != $user->friend_id)
					{
						$array[$key]['link'] = '<a href="#" class="add-friend" data-type="link" data-action="add-friend" data-id="'.$user->friend_id.'">'.Lang::get('global.add_friend').'</a>';
					}
				}

				$array[$key]['name'] = $user->friends->firstname.' '.$user->friends->middlename.' '.$user->friends->lastname;
				$array[$key]['img_url'] = profileImage($user->friends);
				$array[$key]['profile_link'] = profileUrl($user->friends->username->username);
				$array[$key]['id'] = $user->friend_id;
			}
		}

		return $array;
	}

	public function pageLikeButton($page_id)
	{
		if (Auth::check())
		{
			$query = UserPagesLikes::where('user_id', Auth::user()->id)
						->where('page_id', $page_id)
						->first();

			if ($query) return '<a href="#" class="btn btn-success unlike" data-id="'.$page_id.'" data-action="unlike-page" data-type="button" data-loading-text="'.Lang::get('global.loading').'"><span class="glyphicon glyphicon-ok"></span> '.Lang::get('profile.liked').'</a>';
		}

		return '<a href="#" class="btn btn-success like" data-id="'.$page_id.'" data-action="like-page" data-type="button" data-loading-text="'.Lang::get('global.loading').'"><span class="glyphicon glyphicon-thumbs-up"></span> '.Lang::get('profile.like').'</a>';
	}

	public function peopleWhoLikes($page_id)
	{
		$query = UserPagesLikes::where('page_id', $page_id)->orderBy('id', 'desc')->paginate(5);

		if (!$query) return false;

		$data = array();
		foreach($query as $key => $value)
		{
			$data[$key]['name'] = $value->whoLikes->firstname.' '.$value->whoLikes->middlename.' '.$value->whoLikes->lastname;
			$data[$key]['profile_pic'] = profileImage($value->whoLikes);
			$data[$key]['profile_link'] = profileUrl($value->whoLikes->username->username);
			$data[$key]['user_id'] = $value->user_id;
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function pageMap($page_id)
	{
		$query = PageMap::where('page_id', $page_id)->first();

		if (!$query)
		{
			return array(
				'latitude' => 0,
				'longitude' => 0,
				'landmark' => '',
				'zoom_level' => 14
			);
		}

		return array(
			'latitude' => $query->latitude,
			'longitude' => $query->longitude,
			'landmark' => $query->landmark,
			'zoom_level' => $query->zoom_level
		);
	}

	public function updatePageMap()
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\PageMap;

			if ($validation->passes())
			{
				$page_id = Session::get('user_id');
				$latitude = Input::get('latitude');
				$longitude = Input::get('longitude');
				$zoom_level = Input::get('zoom_level');
				$landmark = Input::get('landmark');

				$query = PageMap::where('page_id', $page_id)->first();

				if (!$query)
				{
					$insert = PageMap::create(array(
						'page_id' => $page_id,
						'latitude' => $latitude,
						'longitude' => $longitude,
						'landmark' => $landmark,
						'zoom_level' => $zoom_level
					));

					return status_ok(array('message' => Lang::get('pages.edit_page_success_message')));
				}

				$query->latitude = $latitude;
				$query->longitude = $longitude;
				$query->landmark = $landmark;
				$query->zoom_level = $zoom_level;
				$query->save();

				return status_ok(array('message' => Lang::get('pages.edit_page_success_message')));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function updatePageSchedule()
	{
		if (Request::ajax())
		{
			$page_id = Session::get('user_id');
			$schedule = Input::get('schedule');
			$query = PageSchedule::where('page_id', $page_id)->first();

			if (!$query)
			{
				$insert = PageSchedule::create(array('page_id' => $page_id, 'schedule' => $schedule));

				if (!$insert) return server_error();

				return status_ok(array('message' => Lang::get('pages.edit_page_success_message')));
			}

			$query->schedule = $schedule;
			$query->save();

			return status_ok(array('message' => Lang::get('pages.edit_page_success_message')));
		}

		return Redirect::to('/');
	}

	public function messages($user_id, $type)
	{
		$query = UserConversation::where('user_id', $user_id)
						->where('user_type', $type)
						->orderBy('id', 'desc')
						->paginate(5);

		if (!$query) return false;

		$data = array();
		foreach ($query as $key => $value)
		{
			$username = $value->user_type == 'user' ? $value->user->username->username : $value->page->username->username;
			$data[$key]['subject'] = $value->subject->subject;
			$data[$key]['url'] = URL::route('message', array('username' => $username, 'id' => $value->conversation_id));

			$data[$key]['id'] = $value->conversation_id;
			$data[$key]['created_at'] = time_ago($value->created_at);
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function message($user_id, $type, $conversation_id)
	{
		$query = UserConversation::where('user_id', $user_id)
							->where('user_type', $type)
							->where('conversation_id', $conversation_id)
							->first();

		if (!$query) return false;

		$subject = $query->subject->subject;

		$query = Message::where('conversation_id', $conversation_id)
					->get();

		$data = array();

		foreach ($query as $key => $value)
		{
			$user = $value->user_type == 'user' ? $value->user : $value->page;
			$data[$key]['name'] = userFullName($user, $value->user_type);
			$data[$key]['profile_url'] = profileUrl($user->username->username);
			$data[$key]['profile_pic'] = profileImage($user);
			$data[$key]['message'] = $value->message;
			$data[$key]['id'] = $value->id;	
			$data[$key]['created_at'] = $value->created_at;	
		}

		return array(
			'subject' => $subject,
			'data' => $data
		);
	}

	public function reply($username, $user_id, $type, $conversation_id)
	{
		if (Request::ajax())
		{
			$validation = new \Services\Validators\Reply;

			if ($validation->passes())
			{
				$query = UserConversation::where('user_id', $user_id)
							->where('user_type', $type)
							->where('conversation_id', $conversation_id)
							->first();

				if (!$query) return unknown_error();

				$reply = Input::get('reply');

				$query = Message::create(array(
							'message' => $reply,
							'user_id' => $user_id,
							'conversation_id' => $conversation_id,
							'user_type' => $type
						));

				if (!$query) return server_error();

				$message_id = $query->id;
				$message_created = $query->created_at;
				$user_logged_id = Auth::user()->id;

				$query = UserConversation::withTrashed()
								->where('conversation_id', $conversation_id)
								->get();

				$sender_name = '';

				if ($type == 'page')
				{
					$page = Page::select(DB::raw('page_name'))
								->where('id', $user_id)
								->first();

					if ($page)
					{
						$sender_name = $page->page_name;
					}
				}

				foreach ($query as $key => $value)
				{
					$user_type = $value->user_type;
					$is_owner = false;

					if ($user_type == 'page')
					{
						$page = Page::select(DB::raw('page_name, user_id'))
									->where('id', $value->user_id)
									->first();

						if ($page)
						{
							if ($page->user_id == $user_logged_id) $is_owner = true;
						}
					}
					elseif ($user_type == 'user')
					{
						if ($value->user_id == $user_logged_id) $is_owner = true;						
					}

					if (!$is_owner)
					{
						if ($value->trashed())
						{
							$value->restore();
						}				

						if ($user_type == 'user')
						{
							$recipient_id = $value->user_id;

							UserUnreadMessage::create(array(
								'user_id'         => $recipient_id,
								'message_id'      => $message_id,
								'unread'          => 1,
								'conversation_id' => $conversation_id
							));
						}
						elseif ($user_type == 'page')
						{
							if ($page)
							{
								$recipient_id = $page->user_id;
								$page_name = $page->page_name;

								UserNotifications::create(array(
									'user_id' => $recipient_id,
									'fk_id'   => $value->id,
									'text'    => 2,
									'type'    => 2,
									'unread'  => 1
								));
							}
						}

						$user = User::find($recipient_id);				
						$member_status = memberStatus($user->status);

						if ($member_status['id'] != 3)
						{
							$send_email = true;
							$settings = $user->emailNotificationsSettings;

							if ($settings)
							{
								if ($user_type == 'user')
								{
									$send_email = $settings->new_message == 1 ? true : false;
								}
								elseif ($user_type == 'page')
								{
									$send_email = $settings->new_page_message == 1 ? true : false;
								}
							}
							
							if ($send_email)
							{
								if ($type == 'user')
								{
									$sender = User::find($user_logged_id);
									$data['sender_fullname'] = userFullName($sender);
								}
								elseif ($type == 'page')
								{
									$data['sender_fullname'] = $sender_name;
								}

								$data['recipient_email'] = $user->email;								
								$data['recipient_name'] = userFullName($user);								
								$data['message_content'] = $reply;
								$data['message_link'] = URL::route('message', array('username' => $user->username->username, 'id' => $conversation_id));
								
								if ($user_type == 'user')
								{
									$data['views'] = 'emails.user.new_message';
									$data['subject'] = Lang::get('reminders.new_message_subject', array('fullname' => $data['sender_fullname'], 'sitename' => Lang::get('global.site_name')));
								}
								elseif ($user_type == 'page')
								{
									$data['views'] = 'emails.user.new_page_message';
									$data['subject'] = Lang::get('reminders.new_page_message_subject', array('pagename' => $page_name));
								}							
								
								sendEmail($data);
							}
						}
					}
				}

				if ($type == 'user')
				{
					$user = User::find($user_id);
				}
				elseif ($type == 'page')
				{
					$user = Page::find($user_id);
				}

				return status_ok(array(
					'name' => userFullName($user, $type),
					'reply' => nl2br($reply),
					'profile_pic' => profileImage($user),
					'profile_url' => profileUrl($username),
					'message_id' => $message_id,
					'delete' => Lang::get('global.delete'),
					'created_at' => time_ago($message_created),
					'user_type' => $type,
					'user_id' => $user_id
				));
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function reportProfile($profile_id, $type)
	{
		if (Request::ajax())
		{
			if (!Auth::check())
			{
				$validation = new \Services\Validators\ReportProfileNotLogged;

				if ($validation->passes())
				{
					#check captcha
					if (!recaptcha(Input::get('recaptcha_challenge_field'), Input::get('recaptcha_response_field')))
					{
						return status_error(array(
							'message' => Lang::get('global.wrong_captcha'),
							'field_name' => 'recaptcha_response_field',
							'wrong_captcha' => true
						));
					}

					$query = ReportedByNonMember::create(array(
											'fullname' => Input::get('fullname'),
											'email' => Input::get('email'),
											'reason' => Input::get('reason'),
											'id_fk' => $profile_id,
											'type' => $type,
											'ip' => $_SERVER['REMOTE_ADDR']
										));

					if (!$query) return server_error();

					return status_ok(array('message' => Lang::get('profile.success_report_profile_message')));
				}
			}
			else
			{
				$validation = new \Services\Validators\ReportProfileLogged;

				if ($validation->passes())
				{
					$query = ReportedByMember::create(array(
									'reason' => Input::get('reason'),
									'user_id' => Auth::user()->id,
									'id_fk' => $profile_id,
									'type' => $type
								));

					if (!$query) return server_error();

					return status_ok(array('message' => Lang::get('profile.success_report_profile_message')));
				}
			}

			return $validation->jsonErrors();
		}

		return Redirect::to('/');
	}

	public function activities($user_id)
	{
		$query = User::find($user_id);

		$total = 0;

		if ($query)
		{
			$profile_pic = profileImage($query);
			$fullname = userFullName($query);
			$profile_link = profileUrl($query->username->username);
			
 			$activities = $query->activities()->paginate(5);
			$total = count($activities);

			$data = array();
			foreach ($activities as $key => $value)
			{
				$data[$key]['activity_name'] = $value->name->name;
				$data[$key]['profile_pic'] = $profile_pic;
				$data[$key]['fullname'] = $fullname;
				$data[$key]['profile_link'] = $profile_link;
				$data[$key]['date'] = time_ago($value->updated_at);
				$data[$key]['type'] = $value->activities_id;

				if ($data[$key]['type'] == 4 || $data[$key]['type'] == 2)
				{
					$data[$key]['page_profile_image'] = profileImage($value->page, 'large');
					$data[$key]['page_name'] = $value->page->page_name;
					$data[$key]['page_url'] = profileUrl($value->page->username->username);
					$data[$key]['cat_city'] = $value->page->subCategory->sub_category_name.' '.Lang::get('global.in').' '.$value->page->city->name;					

					$description = $value->page->description;
					$data[$key]['description'] = strlen($description) > 255 ? substr($description, 0, 255).'...' : $description;
				}
				elseif ($data[$key]['type'] == 3)
				{
					$data[$key]['friend_name'] = userFullName($value->friend);
					$data[$key]['friend_profile_link'] = profileUrl($value->friend->username->username);
				}
				elseif ($data[$key]['type'] == 8)
				{
					$page_username = $value->pageReviews->page->username->username;
					$data[$key]['page_name'] = $value->pageReviews->page->page_name;
					$data[$key]['page_url'] = profileUrl($page_username);
					$data[$key]['review'] = $value->pageReviews->review;
					$data[$key]['review_url'] = URL::route('review', array(
												'username' => $page_username,
												'review_id' => $value->pageReviews->id
											));

					$rating = $value->pageReviews->rating;
					$data[$key]['rating'] = '';

					for ($i=1; $i <= 5; $i++)
					{ 
						if ($i < $rating || $i == $rating)
						{
							$data[$key]['rating'] .= '<span class="orange-small glyphicon glyphicon-star active"></span>';
						}
						else
						{
							$data[$key]['rating'] .= '<span class="orange-small glyphicon glyphicon-star"></span>';
						}
					}
				}
				elseif ($data[$key]['type'] == 9)
				{
					$data[$key]['page_name'] = $value->pageReviewsLikes->pageReviews->page->page_name;
					$data[$key]['page_url'] = URL::route('review', array(
												$value->pageReviewsLikes->pageReviews->page->username->username,
												$value->pageReviewsLikes->PageReviews->id
											));
				}
				elseif ($data[$key]['type'] == 10)
				{
					$data[$key]['page_name'] = $value->pageReviewsComments->review->page->page_name;
					$data[$key]['page_url'] = URL::route('review', array(
												$value->pageReviewsComments->review->page->username->username,
												$value->pageReviewsComments->review->id
											));
				}
				elseif ($data[$key]['type'] == 11)
				{
					$data[$key]['page_name'] = $value->pageReviewsCommentsLikes->comment->review->page->page_name;
					$data[$key]['page_url'] = URL::route('review', array(
												$value->pageReviewsCommentsLikes->comment->review->page->username->username,
												$value->pageReviewsCommentsLikes->comment->review->id
											));
				}
			}
		}

		return array(
			'data' => $data,
			'total' => $total,
			'pagination' => $activities->links()
		);
	}

	public function deleteConversation($username)
	{
		if (Request::ajax())
		{
			$info = $this->usernameInfo($username);

			if (!$info) return status_error();

			$user_id = $info->user_id;
			$user_type = $info->user_type;

			Session::put('username', $username);
			Session::put('user_id', $user_id);
			Session::put('user_type', $user_type);

			$data['is_owner'] = $this->isOwner();        
	        
			if (!$data['is_owner'])
			{
				return status_error();
			}

			foreach (Input::get('conversationId') as $key => $value)
			{
				$query = UserConversation::select(DB::raw('id'))
							->where('user_id', $user_id)
							->where('conversation_id', $value)
							->first();

				if ($query)
				{
					$conversation_id = $query->id;
					$query->delete();

					if ($user_type == 'page')
					{
						$query = UserNotifications::where('user_id', Auth::user()->id)
									->where('fk_id', $conversation_id)
									->where('type', 2)
									->get();

						if ($query)
						{
							foreach ($query as $key => $value)
							{
								$value->delete();
							}							
						}
					}
				}

				
				if ($user_type == 'user')
				{
					$query = UserUnreadMessage::select(DB::raw('id'))
								->where('user_id', $user_id)
								->where('conversation_id', $value)
								->first();

					if ($query) $query->delete();
				}		
			}

			return status_ok(array('ids' => Input::get('conversationId')));
		}

		return Redirect::to('/');
	}

	public function pageReviews($page_id)
	{
		$reviews = PageReviews::where('page_id', $page_id)
						->orderBy('id', 'desc')
						->paginate(5);
		$data = array();
		$total = $reviews->getTotal();

		$logged = Auth::check() ? true : false; 

		if ($total)
		{
			foreach($reviews as $key => $review)
			{
				$page_username = $review->page->username->username;
				$review_id = $review->id;
				$total_likes = $review->likes;

				$data[$key]['reviewer_name'] = userFullName($review->reviewer);
				$data[$key]['reviewer_profile_url'] = profileUrl($review->reviewer->username->username);
				$data[$key]['reviewer_profile_image'] = profileImage($review->reviewer);
				$data[$key]['review'] = linkify($review->review);
				$data[$key]['date'] = time_ago($review->created_at, true);
				$data[$key]['owner_id'] = $review->user_id;
				$data[$key]['review_url'] = URL::route('review', array(
												'username' => $page_username,
												'review_id' => $review_id
											));
				$data[$key]['review_like_url'] = URL::route('likeReview', array(
												'username' => $page_username,
												'review_id' => $review_id
											));
				$data[$key]['review_id'] = $review_id;
				$data[$key]['total_likes'] = $total_likes;
				$data[$key]['likes_text'] = Lang::get('profile.total_people_like_this', array('total' => $total_likes));

				if ($logged) $data[$key]['logged_user_profile_image'] = profileImage(Auth::user(), 'small');				

				if ($total_likes == 1)
				{
					//dd($review->userLikes);
					foreach ($review->user_likes as $like)
					{
						$name = '<a href="'.profileUrl($like->user->username->username).'">'.userFullName($like->user).'</a>';
						$data[$key]['likes_text'] = Lang::get('profile.name_likes_this', array('name' => $name));
					}
					
				}

				$rating = $review->rating;
				$data[$key]['rating'] = starRating($rating);				

				$data[$key]['like_unlike_action'] = 'like';
				$data[$key]['like_unlike_text'] = Lang::get('global.like');

				if ($logged)
				{
					$user_logged_id = Auth::user()->id;
					$query = UserReviewsLikes::where('user_id', $user_logged_id)
									->where('page_reviews_id', $review_id)
									->first();

					if ($query)
					{
						$data[$key]['like_unlike_action'] = 'unlike';
						$data[$key]['like_unlike_text'] = Lang::get('global.unlike');

						if ($total_likes == 1)
						{
							$data[$key]['likes_text'] = Lang::get('profile.you_like_this');
						}
						elseif ($total_likes == 2)
						{
							foreach ($review->user_likes as $like)
							{
								if ($like->user_id != $user_logged_id)
								{
									$name = '<a href="'.profileUrl($like->user->username->username).'">'.userFullName($like->user).'</a>';
									$data[$key]['likes_text'] = Lang::get('profile.you_and_name_like_this', array('name' => $name));
								}
							}							
						}
						elseif ($total_likes > 2)
						{
							$data[$key]['likes_text'] = Lang::get('profile.you_and_number_others_like_this', array('total' => $total_likes - 1));
						}
					}
				}

				$comments = array();

				$query = $review->comments;
				//$data[$key]['total_comments'] = $query->getTotal();
				//$data[$key]['total_remaining_comments'] = $data[$key]['total_comments'] - 5;

				foreach ($query as $comment_key => $value)
				{
					$user = $value->user;
					$comments[$comment_key]['id'] = $value->id;
					$comments[$comment_key]['name'] = userFullName($user);
					$comments[$comment_key]['img_url'] = profileImage($user, 'small');
					$comments[$comment_key]['profile_url'] = profileUrl($user->username->username);
					$comments[$comment_key]['comment'] = $value->comment;
					$comments[$comment_key]['date'] = time_ago($value->created_at, true);
					$comments[$comment_key]['like_url'] = URL::route('likeUnlikeReviewComment');
					$comments[$comment_key]['total_likes'] = $value->likes;
					$comments[$comment_key]['owner_id'] = $value->user_id;
					$comments[$comment_key]['like_unlike_text'] = Lang::get('global.like');
					$comments[$comment_key]['like_unlike_action'] = 'like';
					$comments[$comment_key]['edited'] = false;
					$comments[$comment_key]['edit'] = false;
					$comments[$comment_key]['delete'] = false;
					$comments[$comment_key]['report'] = true;

					if ($value->created_at != $value->updated_at)
					{
						$comments[$comment_key]['edited'] = true;
					}

					if ($logged)
					{
						if ($user_logged_id == $value->user_id)
						{
							$comments[$comment_key]['edit'] = true;
							$comments[$comment_key]['delete'] = true;
							$comments[$comment_key]['report'] = false;
						}
					}

					foreach ($value->userLikes as $comment_like_key => $comment_like)
					{
						if ($logged)
						{
							if ($comment_like->user_id == $user_logged_id)
							{
								$comments[$comment_key]['like_unlike_text'] = Lang::get('global.unlike');
								$comments[$comment_key]['like_unlike_action'] = 'unlike';
							}
						}
					}
				}

				$data[$key]['comments'] = $comments;
			}
		}

		return array(
			'total' => $total,
			'data' => $data,
			'pagination' => $reviews->links()
		);
	}

	public function pageReview($review_id)
	{
		$query = PageReviews::find($review_id);

		if (!$query) return false;

		$logged = Auth::check() ? true : false;

		$reviewer = $query->reviewer;
		$page_username = $query->page->username->username;
		$data['reviewer_name'] = userFullName($reviewer);
		$data['reviewer_photo'] = profileImage($reviewer);
		$data['reviewer_profile_url'] = profileUrl($reviewer->username->username);
		$data['review'] = linkify($query->review);
		$data['page_name'] = $query->page->page_name;
		$data['page_url'] = profileUrl($page_username);
		$data['date'] = time_ago($query->created_at, 'ago');
		$data['owner_id'] = $query->user_id;
		$data['review_id'] = $review_id;
		$data['total_likes'] = $query->likes;
		$data['total'] = 1;
		$data['review_like_url'] = URL::route('likeReview', array(
												'username' => $page_username,
												'review_id' => $review_id
											));
		$data['review_url'] = URL::route('review', array(
												'username' => $page_username,
												'review_id' => $review_id
											));

		$data['likes_text'] = Lang::get('profile.total_people_like_this', array('total' => $data['total_likes']));
		$data['page_username'] = $page_username;

		if ($logged) $data['logged_user_profile_image'] = profileImage(Auth::user(), 'small');

		if ($data['total_likes'] == 1)
		{
			foreach ($query->user_likes as $like)
			{
				$name = '<a href="'.profileUrl($like->user->username->username).'">'.userFullName($like->user).'</a>';
				$data['likes_text'] = Lang::get('profile.name_likes_this', array('name' => $name));
			}
			
		}

		$rating = $query->rating;
		$data['rating'] = starRating($rating);		

		$data['like_unlike_action'] = 'like';
		$data['like_unlike_text'] = Lang::get('global.like');

		if ($logged)
		{
			$user_logged_id = Auth::user()->id;
			$query_logged_user_reviews = UserReviewsLikes::where('user_id', $user_logged_id)
							->where('page_reviews_id', $data['review_id'])
							->first();

			if ($query_logged_user_reviews)
			{
				$data['like_unlike_action'] = 'unlike';
				$data['like_unlike_text'] = Lang::get('global.unlike');

				if ($data['total_likes'] == 1)
				{
					$data['likes_text'] = Lang::get('profile.you_like_this');
				}
				elseif ($data['total_likes'] == 2)
				{
					foreach ($query->user_likes as $like)
					{
						if ($like->user_id != $user_logged_id)
						{
							$name = '<a href="'.profileUrl($like->user->username->username).'">'.userFullName($like->user).'</a>';
							$data['likes_text'] = Lang::get('profile.you_and_name_like_this', array('name' => $name));
						}
					}							
				}
				elseif ($data['total_likes'] > 2)
				{
					$data['likes_text'] = Lang::get('profile.you_and_number_others_like_this', array('total' => $data['total_likes'] - 1));
				}
			}
		}

		$comments = array();

		foreach ($query->comments as $comment_key => $value)
		{
			$user = $value->user;
			$comments[$comment_key]['id'] = $value->id;
			$comments[$comment_key]['name'] = userFullName($user);
			$comments[$comment_key]['img_url'] = profileImage($user, 'small');
			$comments[$comment_key]['profile_url'] = profileUrl($user->username->username);
			$comments[$comment_key]['comment'] = $value->comment;
			$comments[$comment_key]['date'] = time_ago($value->created_at, true);
			$comments[$comment_key]['like_url'] = URL::route('likeUnlikeReviewComment');
			$comments[$comment_key]['total_likes'] = $value->likes;
			$comments[$comment_key]['owner_id'] = $value->user_id;
			$comments[$comment_key]['like_unlike_text'] = Lang::get('global.like');
			$comments[$comment_key]['like_unlike_action'] = 'like';
			$comments[$comment_key]['edited'] = false;
			$comments[$comment_key]['edit'] = false;
			$comments[$comment_key]['delete'] = false;
			$comments[$comment_key]['report'] = true;

			if ($value->created_at != $value->updated_at)
			{
				$comments[$comment_key]['edited'] = true;
			}

			if ($logged)
			{
				if ($user_logged_id == $value->user_id)
				{
					$comments[$comment_key]['edit'] = true;
					$comments[$comment_key]['delete'] = true;
					$comments[$comment_key]['report'] = false;
				}
			}

			foreach ($value->userLikes as $comment_like_key => $comment_like)
			{
				if ($logged)
				{
					if ($comment_like->user_id == $user_logged_id)
					{
						$comments[$comment_key]['like_unlike_text'] = Lang::get('global.unlike');
						$comments[$comment_key]['like_unlike_action'] = 'unlike';
					}
				}
			}
		}

		$data['comments'] = $comments;

		return $data;
	}

	public function likeUnlikeReview($username, $review_id)
	{
		$info = $this->usernameInfo($username);
        $user_id = $info->user_id;
        $user_type = $info->user_type;

        if ($user_type == 'user') return Request::ajax() ? status_error() : Redirect::route('showProfile', $username);

        if (!Auth::check())
        {
            Session::put('request_url', URL::route('review', array($username, $review_id)));
            return status_error(array('message' => 'not_login', 'url' => action('ModalsController@getLogin')));
        }

        $action = Input::get('action');
        $actions = array('like', 'unlike');

        if (!in_array($action, $actions)) return status_error();

        $review = PageReviews::find($review_id);

        if (!$review) return status_error();

        $total = $review->likes;
        $reviewer_id = $review->user_id;
        $page_id = $review->page_id;        

        $user_logged_id = Auth::user()->id;
        $query = UserReviewsLikes::withTrashed()
        					->where('user_id', $user_logged_id)
        					->where('page_reviews_id', $review_id)
        					->first();

        $action_text = Lang::get('global.like');
        $next_action = 'like';
        $likesPanelText = Lang::get('profile.you_like_this');
        $send_email = false;
       	
       	if ($query)
       	{
       		$user_reviews_likes_id = $query->id;
       		$query_user_notification = UserNotifications::withTrashed()
										->where('fk_id', $user_reviews_likes_id)
										->where('type', 6)
										->first();

       		if ($action == 'like')
       		{
       			if ($query->trashed())
       			{
       				$query->restore();
       				$total = $total + 1;

       				if ($query_user_notification)
       				{
       					$send_email = true;
       					       				
       					if ($query_user_notification->trashed()) $query_user_notification->restore();
       				}				
       			}

       			$action_text = Lang::get('global.unlike');
       			$next_action = 'unlike';       			   			
       		}
       		else
       		{
       			if (!$query->trashed())
       			{
       				$query->delete();
       				$total = $total - 1;

       				if ($query_user_notification)
       				{
       					if (!$query_user_notification->trashed()) $query_user_notification->delete();
       				}				     				
       			}       			
       		}
       	}
       	else
       	{
       		if ($action == 'like')
       		{
       			$query = UserReviewsLikes::create(array(
       					'user_id' => $user_logged_id,
       					'page_reviews_id' => $review_id
       				));

       			$user_reviews_likes_id = $query->id;
       			$action_text = Lang::get('global.unlike');
       			$next_action = 'unlike';
       			$total = $total + 1;
       			
       			if ($reviewer_id != $user_logged_id)
       			{
	       			$insert = UserNotifications::create(array(
		       			'user_id' => $reviewer_id,
		       			'fk_id' => $user_reviews_likes_id,
		       			'type' => 6
		       		));

		       		$send_email = true;
	       		}   			
       		}
       	}

       	$review->likes = $total;
       	$review->save();

       	if ($action == 'like')
       	{
       		if ($total == 2)
			{
				foreach ($review->user_likes as $like)
				{
					if ($like->user_id != $user_logged_id)
					{
						$name = '<a href="'.profileUrl($like->user->username->username).'">'.userFullName($like->user).'</a>';
						$likesPanelText = Lang::get('profile.you_and_name_like_this', array('name' => $name));
					}
				}							
			}
			elseif ($total > 2)
			{
				$likesPanelText = Lang::get('profile.you_and_number_others_like_this', array('total' => $total - 1));
			}
       	}
       	else
       	{
       		if ($total == 1)
			{
				foreach ($review->user_likes as $like)
				{
					$name = '<a href="'.profileUrl($like->user->username->username).'">'.userFullName($like->user).'</a>';
					$likesPanelText = Lang::get('profile.name_likes_this', array('name' => $name));
				}					
			}
			elseif ($total > 1)
			{
				$likesPanelText = Lang::get('profile.total_people_like_this', array('total' => $total));
			}
       	}      	

       	if ($send_email)
       	{
       		$reviewer = User::find($reviewer_id);
       		$reviewer_status = memberStatus($reviewer->status);

       		if ($reviewer_status['id'] != 3)
       		{
       			$settings = $reviewer->emailNotificationsSettings;

       			if ($settings)
       			{
       				$send_email = $settings->page_review_like == 1 ? true : false;
       			}

       			if ($send_email)
       			{
	       			$page = Page::find($page_id);
	       			$member = User::find($user_logged_id);
	       			$data['views'] = 'emails.page.review_like';
	       			$data['recipient_name'] = userFullName($reviewer);
	       			$data['recipient_firstname'] = $reviewer->firstname;
	       			$data['recipient_email'] = $reviewer->email;
	       			$data['member_fullname'] = userFullName($member);
	       			$data['member_profile_pic'] = profileImage($member, 'small');
	       			$data['member_profile_link'] = profileUrl($member->username->username);
	       			$data['review_url'] = URL::route('review', array($username, $review_id));
	       			$data['page_name'] = $page->page_name;
	       			$data['subject'] = Lang::get('reminders.page_review_like_subject');

	       			sendEmail($data);
       			}
       		}
       	}

       	$query = UserActivity::withTrashed()
       			->where('user_id', $user_logged_id)
				->where('fk_id', $user_reviews_likes_id)
				->where('activities_id', 9)
				->first();

       	if ($action == 'like')
       	{
       		if ($query)
       		{
       			if ($query->trashed())
       			{
       				$query->restore();
       			}
       		}
       		else
       		{
       			
       			$create = UserActivity::create(array(
       						'user_id' => $user_logged_id,
       						'fk_id' => $user_reviews_likes_id,
       						'activities_id' => 9
       					));

       			if (!$create) return server_error();
       		}
       	}
       	else
       	{
       		if ($query)
       		{
       			if (!$query->trashed())
       			{
       				$query->delete();
       			}
       		}
       	}

        return status_ok(array(
        	'action_text' => $action_text,
        	'action' => $next_action,
        	'total' => $total,
        	'likesPanelText' => $likesPanelText
        ));
	}

	public function likeUnlikeReviewComment()
	{
		$comment_id = Input::get('comment_id');
		$page_username = Input::get('page_username');
		$review_id = Input::get('review_id');

		if (!Auth::check())
		{
			Session::put('request_url', URL::route('review', array($page_username, $review_id)));
			return status_error(array('message' => 'not-login', 'url' => action('ModalsController@getLogin')));
		}

		$user_logged_id = Auth::user()->id;		
		$action = Input::get('action');
		$actions = array('like', 'unlike');

		if (!in_array($action, $actions)) return status_error();

		$comment = PageReviewComments::find($comment_id);

		if (!$comment) return status_error();

		$total_likes = $comment->likes;
		$commenter_id = $comment->user_id;
		$page_id = $comment->review->page_id;
		$query = PageReviewCommentLikes::withTrashed()
					->where('user_id', $user_logged_id)
					->where('comment_id', $comment_id)
					->first();

		$action_text = Lang::get('global.like');
		$next_action = 'like';
		$send_email = false;

		if ($query)
		{
			$page_review_comment_like_id = $query->id;
			$query_user_notification = UserNotifications::withTrashed()
				->where('user_id', $user_logged_id)
				->where('fk_id', $page_review_comment_like_id)
				->where('type', 8)
				->first();

			if ($action == 'like')
			{
				if ($query->trashed())
				{
					$query->restore();
					$total_likes = $total_likes + 1;

					if ($query_user_notification)
					{
						if ($query_user_notification->trashed())
						{
							$send_email = true;
							$query_user_notification->restore();
						}
					}
				}

				$action_text = Lang::get('global.unlike');
				$next_action = 'unlike';
			}
			else
			{
				if (!$query->trashed())
				{
					$query->delete();
					$total_likes = $total_likes - 1;

					if ($query_user_notification)
					{
						if (!$query_user_notification->trashed())
						{
							$query_user_notification->delete();
						}
					}
				}
			}
		}
		else
		{
			if ($action == 'like')
			{
				$query = PageReviewCommentLikes::create(array(
							'user_id' => $user_logged_id,
							'comment_id' => $comment_id
				));

				if (!$query) return status_error(array('message' => 'Unable to like'));

				$page_review_comment_like_id = $query->id;
				$total_likes = $total_likes + 1;
				$action_text = Lang::get('global.unlike');
				$next_action = 'unlike';

				if ($commenter_id != $user_logged_id)
				{
					$query = UserNotifications::create(array(
							'user_id' => $commenter_id,
							'fk_id' => $page_review_comment_like_id,
							'type' => 8
					));

					$send_email = true;
				}
			}
		}

		$comment->likes = $total_likes;
		$comment->save();

		if ($send_email)
       	{
       		$commenter = User::find($commenter_id);
       		$commenter_status = memberStatus($commenter->status);

       		if ($commenter_status['id'] != 3)
       		{
       			$settings = $commenter->emailNotificationsSettings;

       			if ($settings)
       			{
       				$send_email = $settings->page_review_comment_like == 1 ? true : false;
       			}

       			if ($send_email)
       			{
	       			$page = Page::find($page_id);
	       			$member = User::find($user_logged_id);
	       			$data['views'] = 'emails.page.review_comment_like';
	       			$data['recipient_name'] = userFullName($commenter);
	       			$data['recipient_firstname'] = $commenter->firstname;
	       			$data['recipient_email'] = $commenter->email;
	       			$data['member_fullname'] = userFullName($member);
	       			$data['member_profile_pic'] = profileImage($member, 'small');
	       			$data['member_profile_link'] = profileUrl($member->username->username);
	       			$data['review_url'] = URL::route('review', array($page_username, $review_id));
	       			$data['page_name'] = $page->page_name;
	       			$data['subject'] = Lang::get('reminders.page_review_comment_like_subject');

	       			sendEmail($data);
       			}
       		}
       	}

		$query = UserActivity::withTrashed()
       			->where('user_id', $user_logged_id)
				->where('fk_id', $page_review_comment_like_id)
				->where('activities_id', 11)
				->first();

       	if ($action == 'like')
       	{
       		if ($query)
       		{
       			if ($query->trashed())
       			{
       				$query->restore();
       			}
       		}
       		else
       		{
       			
       			$create = UserActivity::create(array(
       						'user_id' => $user_logged_id,
       						'fk_id' => $page_review_comment_like_id,
       						'activities_id' => 11
       					));

       			if (!$create) return server_error();
       		}
       	}
       	else
       	{
       		if ($query)
       		{
       			if (!$query->trashed())
       			{
       				$query->delete();
       			}
       		}
       	}

        return status_ok(array(
        	'action_text' => $action_text,
        	'action' => $next_action,
        	'total_likes' => $total_likes
        ));
	}

	public function commentReview($username, $review_id)
	{
		$comment = Input::get('comment');

		if (empty($comment)) return status_error();

		$query = PageReviews::find($review_id);

		if (!$query) return status_error();

		$logged_user = Auth::user();
		$user_logged_id = $logged_user->id;
		$reviewer_id = $query->user_id;

		$insert = PageReviewComments::create(array(
				'user_id' => $user_logged_id,
				'review_id' => $review_id,
				'comment' => $comment
		));

		if (!$insert) return server_error(array('message' => 'Insert Review Failed'));

		$comment_id = $insert->id;
		$comment_date = $insert->created_at;

		if ($user_logged_id != $reviewer_id)
       	{
       		$reviewer = User::find($reviewer_id);
       		$reviewer_status = memberStatus($reviewer->status);

       		if ($reviewer_status['id'] != 3)
       		{
       			$settings = $reviewer->emailNotificationsSettings;
       			$send_email = true;

       			if ($settings)
       			{
       				$send_email = $settings->page_review_comment == 1 ? true : false;
       			}

       			if ($send_email)
       			{
	       			$data['views'] = 'emails.page.review_comment';
	       			$data['recipient_name'] = userFullName($reviewer);
	       			$data['recipient_firstname'] = $reviewer->firstname;
	       			$data['recipient_email'] = $reviewer->email;
	       			$data['member_fullname'] = userFullName($logged_user);
	       			$data['member_profile_pic'] = profileImage($logged_user, 'small');
	       			$data['member_profile_link'] = profileUrl($logged_user->username->username);
	       			$data['review_url'] = URL::route('review', array($username, $review_id));
	       			$data['subject'] = Lang::get('reminders.new_page_review_comment_subject');

	       			sendEmail($data);
       			}
       		}
       	}

		if ($user_logged_id != $reviewer_id)
		{
			$insert = UserNotifications::create(array(
					'user_id' => $reviewer_id,
					'fk_id' => $comment_id,
					'type' => 7
			));

			if (!$insert) return server_error(array('message' => 'Insert User Notifications Failed'));
		}
		
		$insert = UserActivity::create(array(
				'user_id' => $user_logged_id,
				'fk_id' => $comment_id,
				'activities_id' => 10
		));

		if (!$insert) return server_error(array('message' => 'Insert User Activity Failed'));

		$html = '<li id="reviewCommentWrapper-'.$comment_id.'" class="review-comment-wrapper" data-id="'.$comment_id.'">
					<table width="100%">
						<tr>
							<td width="37" class="vat"><img src="'.profileImage($logged_user).'" width="32" height="32"></td>
							<td>
								<div><a href="'.profileUrl($logged_user->username->username).'"><b>'.userFullName($logged_user).'</b></a> <span id="commentText-'.$comment_id.'">'.$comment.'</span></div>								
								<ul class="review-action clearfix np">
									<li>
										<span class="text-muted">'.time_ago($comment_date, true).'</span>
									</li>
									<li><span class="separator glyphicon glyphicon-stop"></span></li>
									<li>
										<span id="reviewCommentLikeLoading-'.$comment_id.'" class="loading-small hidden"></span>
										<a id="likeReviewComment-'.$comment_id.'" data-href="'.URL::route('likeUnlikeReviewComment').'" data-id="'.$comment_id.'" data-action="like" data-page-username="'.$username.'" data-review-id="'.$review_id.'" rel="nofollow" class="like-review-comment">'.Lang::get('global.like').'</a>
									</li>
									<li id="reviewCommentTotalLikesSeparator-'.$comment_id.'" class="hidden"><span class="separator glyphicon glyphicon-stop"></span></li>
									<li id="reviewCommentTotalLikesWrapper-'.$comment_id.'" class="mt2px label label-success comment-like-hidable  hidden"><span class="glyphicon glyphicon-thumbs-up"></span> <span id="reviewCommentTotalLikes-'.$comment_id.'">0</span></li>
								</ul>
							</td>
							<td class="vat" width="25">
								<div class="btn-group pull-right">
									<span id="reviewCommentDropdownTrigger-'.$comment_id.'" class="glyphicon glyphicon-remove review-comment-dropdown-trigger hidden" role="button" data-toggle="dropdown"></span>
									<ul id="reviewCommentMenu-'.$comment_id.'" class="dropdown-menu review-comment-menu" role="menu" aria-labelledby="reviewCommentDropdown-'.$comment_id.'">
											<li role="presentation">
												<a role="menuitem" tabindex="-1" class="modal-link" href="'.action('ModalsController@getEditPageReviewComment', array($comment_id)).'">'.Lang::get('global.edit').'</a>
											</li>										
											<li role="presentation"><a role="menuitem" tabindex="-1" data-href="'.URL::route('deletePageReviewComment').'" data-id="'.$comment_id.'" data-owner-id="'.$user_logged_id.'" class="delete-page-review-comment">'.Lang::get('global.delete').'</a></li>										
									</ul>
								</div>
							</td>
						</tr>
					</table>
				</li>';

		return status_ok(array(
			'html' => $html
		));
	}
}