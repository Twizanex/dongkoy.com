<?php namespace Edongkoy\Repositories\Admin;

# app/library/Edongkoy/Repositories/Admin/EloquentAdminRepository.php

use Edongkoy\Repositories\Admin\Models\Countries;
use Edongkoy\Repositories\Admin\Models\Province;
use Edongkoy\Repositories\Admin\Models\Cities;
use Edongkoy\Repositories\Admin\Models\Categories;
use Edongkoy\Repositories\Admin\Models\Subcategories;
use Edongkoy\Repositories\Admin\Models\FactualCategories;
use Edongkoy\Repositories\Admin\Models\FactualIds;
use Edongkoy\Repositories\Admin\Models\BestBuyIds;
use Edongkoy\Repositories\Page\Models\Page;
use Edongkoy\Repositories\Page\Models\PageStatus;
use Edongkoy\Repositories\Page\Models\PageMap;
use Edongkoy\Repositories\Page\Models\PageSchedule;
use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Users\Models\UserStatus;
use Edongkoy\Repositories\Users\Models\UserSocialIds;
use Edongkoy\Repositories\Users\Models\FacebookTokens;
use Edongkoy\Repositories\Users\Models\UserFacebookFriends;
use Edongkoy\Repositories\Users\Models\ReportedByMember;
use Edongkoy\Repositories\Users\Models\ReportedByNonMember;
use Edongkoy\Repositories\Users\Models\UserNotifications;
use Edongkoy\Repositories\Users\Models\UserActivity;
use Edongkoy\Repositories\Users\Models\Usernames;
use Edongkoy\Repositories\Image\Models\Albums;
use Edongkoy\Repositories\Image\Models\CoverImages;
use Edongkoy\Repositories\Image\Models\Images;
use Edongkoy\Repositories\Image\Models\ProfileImages;
use Edongkoy\Repositories\Emails\Models\UserEmailConfirmation;
use Edongkoy\Repositories\Videos\Models\Videos;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\DomCrawler\Crawler;

class EloquentAdminRepository implements AdminRepositoryInterface {

	public function countries()
	{
		return Countries::orderBy('english_name', 'asc')->get();
	}

	public function categories()
	{
		return Categories::orderBy('category_name', 'asc')->get();
	}

	public function countriesPaginate($per_page = 5)
	{
		return Countries::paginate($per_page);
	}

	public function compileCountries()
	{
		$data = 'var province=[];';
		$data .= 'var cities=[];';

		foreach (Countries::all() as $key => $country)
		{
			$data .= "province[".$country->id."]=[";
			$total_province = count($country->province);
			$province_ctr = 1;

			foreach ($country->province as $key => $province)
			{
				$data .= "[".$province->id.",'".$province->name."']";

				if ($province_ctr < $total_province) $data .= ",";

				$province_ctr++;
			}

			$data .= '];';
		}

		foreach (Province::all() as $key => $province)
		{
			$data .= "cities[".$province->id."]=[";
			$total_cities = count($province->cities);
			$city_ctr = 1;

			foreach ($province->cities as $key => $city)
			{
				$data .= "[".$city->id.",\"".$city->name."\"]";

				if ($city_ctr < $total_cities) $data .= ",";

				$city_ctr++;
			}

			$data .= '];';
		}

		return \File::put('assets/js/min/countries.js', $data);
	
	}

	public function compileCategories()
	{
		$data = 'var subCategories=[];';
		
		foreach (Categories::all() as $key => $category)
		{
			$data .= "subCategories[".$category->id."]=[";
			$total_sub_cateogry = count($category->subCategories);
			$sub_category_ctr = 1;

			foreach ($category->subCategories as $key => $sub_category)
			{
				$data .= "[".$sub_category->id.",\"".$sub_category->sub_category_name."\"]";

				if ($sub_category_ctr < $total_sub_cateogry) $data .= ",";

				$sub_category_ctr++;
			}

			$data .= '];';
		}

		return \File::put('assets/js/min/categories.js', $data);
	}

	public function addCategory()
	{
		$action = Input::get('action');

		if ($action == 'add-category' OR $action == 'edit-category')
		{
			$validation = new \Services\Validators\Categories;
		}
		elseif ($action == 'add-sub-category' OR $action == 'edit-sub-category')
		{
			$validation = new \Services\Validators\Subcategories;
		}
		
		if ($validation->passes())
		{
			if ($action == 'add-category')
			{
				$category = Categories::create(Input::only('category_name'));

				$message = Lang::get('admin.success_category_add');
			}
			elseif ($action == 'edit-category')
			{
				$category = Categories::find(Input::get('category_id'));
				$category->category_name = Input::get('category_name');
				$category->save();
				$message = Lang::get('admin.success_category_edit');
			}
			elseif ($action == 'add-sub-category')
			{
				$sub_category = Subcategories::create(Input::only('sub_category_name', 'category_id'));
				$message = Lang::get('admin.success_sub_category_add');
			}
			elseif ($action == 'edit-sub-category')
			{
				$sub_category = Subcategories::find(Input::get('sub_category_id'));
				$sub_category->sub_category_name = Input::get('sub_category_name');
				$sub_category->save();
				$message = Lang::get('admin.success_sub_category_edit');
			}
			if(Request::ajax())
			{
				return Response::json(array(
						'status'	=> Lang::get('global.ok_status'),
						'message'	=> $message,
						'action'	=> $action
					)
				);
			}

			return Redirect::back()->with('flush_success-'.$action, $message);
		}

		if (Request::ajax())
		{
			return $validation->jsonErrors();
		}

		return Redirect::back()->withInput()->withErrors($validation->errors);
	}

	public function addCountries()
	{
		$action = Input::get('action');

		if ($action == 'add-country' OR $action == 'edit-country')
		{
			$validation = new \Services\Validators\Countries;
		}
		elseif ($action == 'add-province' OR $action == 'edit-province')
		{
			$validation = new \Services\Validators\Province;
		}
		elseif ($action == 'add-city' OR $action == 'edit-city')
		{
			$validation = new \Services\Validators\Cities;
		}
		
		if ($validation->passes())
		{
			if ($action == 'add-country')
			{
				$country = Countries::create(array_add(Input::only('english_name', 'french_name', 'local_name', 'region'), 'url', Str::slug(Input::get('english_name'))));

				$message = Lang::get('admin.success_country_add');
			}
			elseif ($action == 'edit-country')
			{
				$country = Countries::find(Input::get('country_id'));
				$country->english_name = Input::get('english_name');
				$country->french_name  = Input::get('french_name');
				$country->local_name   = Input::get('local_name');
				$country->region       = Input::get('region');
				$country->url          = Str::slug(Input::get('english_name'));
				$country->save();
				$message = Lang::get('admin.success_country_edit');
			}
			elseif ($action == 'add-province')
			{
				$province = Province::create(array_add(Input::only('country_id', 'name'), 'url', Str::slug(Input::get('name'))));
				$message = Lang::get('admin.success_province_add');
			}
			elseif ($action == 'edit-province')
			{
				$province = Province::find(Input::get('province_id'));
				$province->name = Input::get('name');
				$province->url = Str::slug(Input::get('name'));
				$province->save();
				$message = Lang::get('admin.success_province_edit');
			}
			elseif ($action == 'add-city')
			{
				$city = Cities::create(array_add(Input::only('name', 'postal_code', 'province_id'), 'url', Str::slug(Input::get('name'))));
				$message = Lang::get('admin.success_city_add');
			}
			elseif ($action == 'edit-city')
			{
				$city = Cities::find(Input::get('city_id'));
				$city->name        = Input::get('name');
				$city->postal_code = Input::get('postal_code');
				$city->url         = Str::slug(Input::get('name'));
				$city->save();
				$message = Lang::get('admin.success_city_edit');
			}

			if(Request::ajax())
			{
				return Response::json(array(
						'status'	=> Lang::get('global.ok_status'),
						'message'	=> $message,
						'action'	=> $action
					)
				);
			}

			return Redirect::back()->with('flush_success-'.$action, $message);
		}

		if (Request::ajax())
		{
			return $validation->jsonErrors();
		}

		return Redirect::back()->withInput()->withErrors($validation->errors);
	}

	public function activeUsers()
	{
		$query = User::join('user_status', 'users.id', '=', 'user_status.user_id')
						->where('user_status.status_id', 1)
						->orderBy('users.id', 'desc')
						->paginate(5);

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = userFullName($value);
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['status'] = pageStatus($value->status);
				$data[$key]['role'] = $value->role;
				$data[$key]['facebook_id'] = $value->socialId ? $value->socialId->facebook_id : 0;

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" data-id="'.$data[$key]['id'].'" data-action="show-google-ads">'.Lang::get('admin.show_google_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" data-id="'.$data[$key]['id'].'" data-action="remove-google-ads">'.Lang::get('admin.remove_google_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function flaggedUsersByMembers()
	{
		$query = ReportedByMember::where('type', 'user')
									->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->user->id;
				$data[$key]['fullname'] = userFullName($value->user);
				$data[$key]['profile_link'] = profileUrl($value->user->username->username);
				$data[$key]['img'] = profileImage($value->user);
				$data[$key]['status'] = pageStatus($value->user->status);
				$data[$key]['role'] = $value->user->role;
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['reporter_name'] = userFullName($value->reporter);
				$data[$key]['reporter_profile_link'] = profileUrl($value->reporter->username->username);
				$data[$key]['reason'] = $value->reason;
				$data[$key]['facebook_id'] = $value->user->socialId ? $value->user->socialId->facebook_id : 0;

				foreach ($data[$key]['role'] as $key => $role)
				{
					$data[$key]['role_name'] = $role['name'];
				
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function flaggedUsersByNonMembers()
	{
		$query = ReportedByNonMember::where('type', 'user')
									->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->user->id;
				$data[$key]['fullname'] = userFullName($value->user);
				$data[$key]['profile_link'] = profileUrl($value->user->username->username);
				$data[$key]['img'] = profileImage($value->user);
				$data[$key]['status'] = pageStatus($value->user->status);
				$data[$key]['role'] = $value->user->role;
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['reporter_name'] = $value->fullname;
				$data[$key]['reason'] = $value->reason;
				$data[$key]['facebook_id'] = $value->user->socialId ? $value->user->socialId->facebook_id : 0;

				foreach ($data[$key]['role'] as $key => $role)
				{
					$data[$key]['role_name'] = $role['name'];
				
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function unconfirmedUsers()
	{
		$query = User::join('user_status', 'users.id', '=', 'user_status.user_id')
						->where('user_status.status_id', 3)
						->orderBy('users.id', 'desc')
						->paginate();

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = userFullName($value);
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['status'] = pageStatus($value->status);
				$data[$key]['role'] = $value->role;
				$data[$key]['facebook_id'] = $value->socialId ? $value->socialId->facebook_id : 0;

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="show-ads">'.Lang::get('admin.show_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="remove-ads">'.Lang::get('admin.remove_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function blockedUsers()
	{
		$query = User::join('user_status', 'users.id', '=', 'user_status.user_id')
						->where('user_status.status_id', 4)
						->orderBy('user_status.updated_at', 'desc')
						->paginate();

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = userFullName($value);
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['status'] = pageStatus($value->status);
				$data[$key]['role'] = $value->role;
				$data[$key]['facebook_id'] = $value->socialId ? $value->socialId->facebook_id : 0;

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="show-ads">'.Lang::get('admin.show_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="remove-ads">'.Lang::get('admin.remove_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function deactivatedUsers()
	{
		$query = User::join('user_status', 'users.id', '=', 'user_status.user_id')
						->where('user_status.status_id', 5)
						->orderBy('users.id', 'desc')
						->paginate(5);

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = userFullName($value);
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['status'] = pageStatus($value->status);
				$data[$key]['role'] = $value->role;
				$data[$key]['facebook_id'] = $value->socialId ? $value->socialId->facebook_id : 0;

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="show-ads">'.Lang::get('admin.show_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="remove-ads">'.Lang::get('admin.remove_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function deletedUsers()
	{
		$query = User::join('user_status', 'users.id', '=', 'user_status.user_id')
						->where('user_status.status_id', 6)
						->orderBy('users.id', 'desc')
						->paginate();

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = userFullName($value);
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['status'] = pageStatus($value->status);
				$data[$key]['role'] = $value->role;
				$data[$key]['facebook_id'] = $value->socialId ? $value->socialId->facebook_id : 0;

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="show-ads">'.Lang::get('admin.show_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="remove-ads">'.Lang::get('admin.remove_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function totalMembers()
	{
		$query = User::all();
		$total = User::count();
		$active = 0;
		$unconfirmed = 0;
		$blocked = 0;
		$flagged_by_members = ReportedByMember::where('type', 'user')->count();
		$flagged_by_non_members = ReportedByNonMember::where('type', 'user')->count();
		$deactivated = 0;
		$deleted = 0;

		foreach ($query as $i)
		{
			foreach ($i->status as $j)
			{
				if ($j->id == 1) $active++;
				if ($j->id == 3) $unconfirmed++;
				if ($j->id == 4) $blocked++;
				if ($j->id == 5) $deactivated++;
				if ($j->id == 6) $deleted++;
			}
		}

		return array(
			'total' => $total,
			'active' => $active,
			'unconfirmed' => $unconfirmed,
			'blocked' => $blocked,
			'flagged_by_members' => $flagged_by_members,
			'flagged_by_non_members' => $flagged_by_non_members,
			'deactivated' => $deactivated,
			'deleted' => $deleted
		);
	}

	public function totalPages()
	{
		$total = Page::count();
		$active = PageStatus::where('status_id', 1)->count();
		$pending = PageStatus::where('status_id', 2)->count();
		$disapproved = PageStatus::where('status_id', 6)->count();
		$blocked = PageStatus::where('status_id', 4)->count();
		$flagged_by_members = ReportedByMember::where('type', 'page')->count();
		$flagged_by_non_members = ReportedByNonMember::where('type', 'page')->count();
		$deactivated = PageStatus::where('status_id', 5)->count();
		$deleted = PageStatus::where('status_id', 7)->count();
		$with_google_ads = Usernames::where('user_type', 'page')->where('google_ads', 1)->count();

		return array(
			'total' => $total,
			'active' => number_format($active),
			'pending' => $pending,
			'disapproved' => $disapproved,
			'blocked' => $blocked,
			'flagged_by_members' => $flagged_by_members,
			'flagged_by_non_members' => $flagged_by_non_members,
			'deactivated' => $deactivated,
			'deleted' => $deleted,
			'with_google_ads' => number_format($with_google_ads)
		);
	}

	public function activePages()
	{
		$query = Page::whereHas('status', function($q)
                {
                    $q->where('status_id', 1);
                })
                ->orderBy('id', 'desc')
                ->paginate(6);

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="show-ads">'.Lang::get('admin.show_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="remove-ads">'.Lang::get('admin.remove_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function pagesWithGoogleAds()
	{
		$query = Page::whereHas('status', function($q)
                {
                    $q->where('status_id', 1);
                })
				->whereHas('username', function($q)
				{
					$q->where('google_ads', 1);
				})
                ->orderBy('id', 'desc')
                ->paginate(6);

		$data = array();
		
		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);

				$data[$key]['google_ads'] = '<span class="label label-danger">No Google Ads</span>';
				$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="show-ads">'.Lang::get('admin.show_ads').'</a></li>';
				
				if ($value->username->google_ads)
				{
					$data[$key]['google_ads'] = '<span class="label label-info">Google Ads</span>';
					$data[$key]['show_remove_ads'] = '<li><a href="#" id="showRemoveAds-'.$data[$key]['id'].'" data-id="'.$data[$key]['id'].'" data-action="remove-ads">'.Lang::get('admin.remove_ads').'</a></li>';
				}
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function flaggedPagesByMembers()
	{
		$query = ReportedByMember::where('type', 'page')
									->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->page->id;
				$data[$key]['page_name'] = $value->page->page_name;
				$data[$key]['url'] = profileUrl($value->page->username->username);
				$data[$key]['img'] = profileImage($value->page, 'large');
				$data[$key]['page_status'] = $value->page->status;
				$data[$key]['page_status_label'] = pageStatus($data[$key]['page_status']);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->page->subCategory->sub_category_name;
				$data[$key]['reporter_name'] = userFullName($value->reporter);
				$data[$key]['reporter_profile_link'] = profileUrl($value->reporter->username->username);
				$data[$key]['reason'] = $value->reason;
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function flaggedPagesByNonMembers()
	{
		$query = ReportedByNonMember::where('type', 'page')
									->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->page->id;
				$data[$key]['page_name'] = $value->page->page_name;
				$data[$key]['url'] = profileUrl($value->page->username->username);
				$data[$key]['img'] = profileImage($value->page, 'large');
				$data[$key]['page_status'] = $value->page->status;
				$data[$key]['page_status_label'] = pageStatus($data[$key]['page_status']);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->page->subCategory->sub_category_name;
				$data[$key]['reporter_name'] = $value->fullname;
				$data[$key]['reason'] = $value->reason;				
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function unconfirmedPages()
	{
		$query = Page::join('page_status', 'pages.id', '=', 'page_status.page_id')
						->where('page_status.status_id', 2)
						->orderBy('pages.id', 'desc')
						->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);	
	}

	public function disapprovedPages()
	{
		$query = Page::join('page_status', 'pages.id', '=', 'page_status.page_id')
						->where('page_status.status_id', 6)
						->orderBy('pages.id', 'desc')
						->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function blockedPages()
	{
		$query = Page::join('page_status', 'pages.id', '=', 'page_status.page_id')
						->where('page_status.status_id', 4)
						->orderBy('pages.id', 'desc')
						->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function deactivatedPages()
	{
		$query = Page::whereHas('status', function($q)
                {
                    $q->where('status_id', 5);
                })
                ->orderBy('id', 'desc')
                ->paginate(6);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function deletedPages()
	{
		$query = Page::join('page_status', 'pages.id', '=', 'page_status.page_id')
						->where('page_status.status_id', 7)
						->orderBy('pages.id', 'desc')
						->paginate(5);

		$data = array();

		if ($query)
		{
			foreach ($query as $key => $value)
			{
				$data[$key]['id'] = $value->id;
				$data[$key]['name'] = $value->page_name;
				$data[$key]['img'] = profileImage($value, 'large');
				$data[$key]['url'] = profileUrl($value->username->username);
				$data[$key]['date'] = time_ago($value->created_at);
				$data[$key]['sub_cat'] = $value->subCategory->sub_category_name;
				$data[$key]['status'] = pageStatus($value->status);
			}
		}

		return array(
			'data' => $data,
			'pagination' => $query->links()
		);
	}

	public function manageMembers()
	{
		if (Request::ajax())
		{
			$user_id = Input::get('user_id');
			$action = Input::get('action');
			$update_status = true;

			if ($action == 'block')
			{
				$status_id = 4;
			} 
			elseif ($action == 'unblocked')
			{
				$query = UserEmailConfirmation::where('user_id', $user_id)->first();
				$status_id = $query ? 3 : 1;
			}
			elseif ($action == 'flag')
			{
				$status_id = 2;
			} 
			elseif ($action == 'delete')
			{
				$status_id = 6;

				$username = Usernames::where('user_id', $user_id)
										->where('user_type', 'user')
										->first();

				if (!$username) return status_error(array('message' => 'Nothing to delete'));
				
				$username->delete();
			}
			elseif ($action == 'restore')
			{
				$query = UserEmailConfirmation::where('user_id', $user_id)->first();
				$status_id = $query ? 3 : 1;

				$username = Usernames::onlyTrashed()
										->where('user_id', $user_id)
										->where('user_type', 'user')
										->first();

				if (!$username) return status_error(array('message' => 'Nothing to restore'));

				$username->restore();
			}
			elseif ($action == 'show-google-ads')
			{
				$username = Usernames::where('user_id', $user_id)
										->where('user_type', 'user')
										->first();

				if (!$username) return status_error(array('message' => 'Nothing ads to show'));

				$username->google_ads = 1;
				$username->save();
				$update_status = false;
			}
			elseif ($action == 'remove-google-ads')
			{
				$username = Usernames::where('user_id', $user_id)
										->where('user_type', 'user')
										->first();

				if (!$username) return status_error(array('message' => 'Nothing ads to show'));

				$username->google_ads = 0;
				$username->save();
				$update_status = false;
			}
			elseif ($action == 'get-fb-friends')
			{
				$social_id = UserSocialIds::select(DB::raw('facebook_id'))->where('user_id', $user_id)->first();

				if (!$social_id) return server_error();

				$fb = FacebookTokens::select(DB::raw('token'))->where('fb_id', $social_id->facebook_id)->first();

				if (!$fb) return server_error();

				$facebook = new \Facebook(Config::get('app.facebook'));
				$facebook->setAccessToken($fb->token);
				$fb_id = $facebook->getUser();

				$friends = $facebook->api('/me?fields=friends');

				$total = count($friends['friends']['data']);

				if ($total == 0) return status_error(array('message' => 'No friends'));

				$data = array();

				foreach ($friends['friends']['data'] as $key => $value)
				{
					$query = UserFacebookFriends::select(DB::raw('id'))
												->where('user_id', $user_id)
												->where('fb_user_id', $value['id'])
												->first();

					if ($query)
					{
						$query->name = $value['name'];
						$query->save();
					}
					else
					{
						$data[$key]['user_id'] = $user_id;
						$data[$key]['fb_user_id'] = $value['id'];
						$data[$key]['name'] = $value['name'];
						UserFacebookFriends::create($data[$key]);
					}
				}
		
				return status_ok(array('message' => $data));
			}
			else
			{
				return status_error(array('message' => 'Action not allowed'));
			}

			if ($update_status)
			{
				$update = UserStatus::where('user_id', $user_id)->update(array('status_id' => $status_id));

				if (!$update) return server_error(array('message' => 'User status not updated'));
			}

			return status_ok();
		}

		return Redirect::to('/');
	}

	public function deleteCategory()
	{
		$id = Input::get('id');
		
		$query = Categories::find($id);

		if (!$query) return server_error();

		$query->delete();

		$query = Subcategories::where('category_id', $id)->delete();

		if (!$query) return server_error();

		return status_ok();
	}

	public function deleteSubCategory()
	{
		$id = Input::get('id');

		$query = Subcategories::find($id);

		if (!$query) return server_error();

		$query->delete();

		return status_ok();
	}

	public function deleteCountry()
	{
		$query = Countries::find(Input::get('id'));

		if (!$query) return server_error();

		$query->delete();

		return status_ok();
	}

	public function deleteProvince()
	{
		$query = Province::find(Input::get('id'));

		if (!$query) return server_error();

		$query->delete();

		return status_ok();
	}

	public function deleteCity()
	{
		$query = Cities::find(Input::get('id'));

		if (!$query) return server_error();

		$query->delete();
		
		return status_ok();
	}

	public function managePages()
	{
		if (Request::ajax())
		{
			$action = Input::get('action');
			$page_id = Input::get('page_id');
			$status_id = false;
			$send_email = true;
			$update_status = true;
			$notify = true;
			$update_activity = true;

			$page = Page::find($page_id);

			if (!$page) return status_error();

			$owner_id = $page->user_id;
			$page_username = $page->username->username;

			if ($action == 'approve')
			{
				$status_id = 1;
				$text_id = 3;
				$views = 'approved';
				$subject = 'reminders.your_page_has_been_approved';
			}
			elseif ($action == 'block')
			{
				$status_id = 4;
				$text_id = 4;
				$views = 'blocked';
				$subject = 'reminders.your_page_has_been_blocked';
			}
			elseif ($action == 'unblock')
			{
				$status_id = 1;
				$text_id = 3;
				$views = 'unblocked';
				$subject = 'reminders.your_page_has_been_unblocked';
			}
			elseif ($action == 'disapprove')
			{
				$status_id = 6;
				$text_id = 5;
				$views = 'disapproved';
				$subject = 'reminders.your_page_has_been_disapproved';
			}
			elseif ($action == 'delete')
			{				
				$query = Usernames::where('user_id', $page_id)
									->where('user_type', 'page')
									->first();

				if (!$query) return status_error();

				$query->delete();
				$status_id = 7;
				$text_id = 6;
				$views = 'deleted';
				$subject = 'reminders.your_page_has_been_deleted';
				
			}
			elseif ($action == 'restore')
			{
				$query = Usernames::onlyTrashed()
									->where('user_id', $page_id)
									->where('user_type', 'page')
									->first();

				if (!$query) return status_error();

				$query->restore();
				$status_id = 1;
				$text_id = 3;
				$views = 'approved';
				$subject = 'reminders.your_page_has_been_approved';
			}
			elseif ($action == 'show-ads' || $action == 'remove-ads')
			{
				$send_email = false;
				$update_status = false;
				$notify = false;
				$update_activity = false;

				$query = Usernames::where('user_id', $page_id)
									->where('user_type', 'page')
									->first();

				if ($action == 'show-ads') $query->google_ads = 1;
				if ($action == 'remove-ads') $query->google_ads = 0;
				$query->save();
			}
			else
			{
				return status_error(array('message' => "Action not allowed"));
			}
			
			if ($update_status)
			{
				$update = PageStatus::where('page_id', $page_id)->update(array('status_id' => $status_id));

				if (!$update) return server_error();
			}

			if ($notify)
			{
				$query = UserNotifications::create(array(
												'user_id' => $owner_id,
												'fk_id' => $page_id,
												'text' => $text_id,
												'type' => 3
											));

				if (!$query) return server_error();
			}				

			$member_status = memberStatus($page->owner->status);

			if ($member_status['id'] != 3 && $send_email)
			{
				$settings = $page->owner->emailNotificationsSettings;
				
				if ($settings)
				{
					if ($action == 'approve' || $action == 'disapprove')
					{
						$send_email = $settings->page_approved_disapproved == 1 ? true : false;
					}
					elseif ($action == 'block' || $action == 'unblock')
					{
						$send_email = $settings->page_blocked_unblocked == 1 ? true : false;
					}
				}

				if ($send_email)
				{
					$views = 'emails.page.'.$views;
					$data['recipient_name'] = userFullName($page->owner);
					$data['recipient_email'] = $page->owner->email;
					$data['recipient_firstname'] = $page->owner->firstname;
					$data['page_name'] = $page->page_name;
					$data['page_url'] = profileUrl($page_username);
					$data['subject'] = Lang::get($subject, array('pagename' => '"'.$data['page_name'].'"'));

					Mail::send($views, $data, function($message) use ($data)
					{
						$message->to($data['recipient_email'], $data['recipient_name'])
								->subject($data['subject']);
					});
				}
			}

			if ($update_activity)
			{
				if ($action == 'approve' || $action == 'unblock' || $action == 'restore')
				{
					$query = UserActivity::onlyTrashed()
								->where('user_id', $owner_id)
								->where('fk_id', $page_id)
								->where('activities_id', 2)
								->first();

					if ($query)
					{
						$query->restore();
					}
					else
					{
						$insert = UserActivity::create(array(
										'user_id' => $owner_id,
										'fk_id' => $page_id,
										'activities_id' => 2
									));

						if (!$insert) return server_error();
					}
				}
				else
				{
					$query = UserActivity::where('user_id', $owner_id)
										->where('fk_id', $page_id)
										->where('activities_id', 2)
										->first();

					if ($query)
					{
						$query->delete();
					}
				}					
			}

			return status_ok();
		}

		return Redirect::to('/');
	}

	public function getYoutube()
	{
		$query = Videos::where('iframe', 0)
					->orderBy('id', 'desc')
					->paginate(5);

		$data = array();

		foreach ($query as $key => $value)
		{
			$data[$key]['id'] = $value->id;
			$data[$key]['video_id'] = $value->video_id;
			$data[$key]['title'] = $value->title;
			$data[$key]['sharer'] = Lang::get('videos.shared_by').' '.userFullName($value->user);
			$data[$key]['views'] = number_format($value->total_views).' '.Lang::get('videos.views');
			$data[$key]['duration'] = formatTime($value->duration);
			$data[$key]['fb_shares'] = $value->fb_shares.' shares';
		}

		return array(
			'data' => $data,
			'pagination' => $query->links(),
			'total' => $query->getTotal()
		);
	}

	public function getIframeYoutube()
	{
		$query = Videos::where('iframe', 1)
					->orderBy('fb_shares', 'desc')
					->paginate(5);

		$data = array();

		foreach ($query as $key => $value)
		{
			$data[$key]['id'] = $value->id;
			$data[$key]['video_id'] = $value->video_id;
			$data[$key]['title'] = $value->title;
			$data[$key]['sharer'] = Lang::get('videos.shared_by').' '.userFullName($value->user);
			$data[$key]['views'] = number_format($value->total_views).' '.Lang::get('videos.views');
			$data[$key]['duration'] = formatTime($value->duration);
			$data[$key]['fb_shares'] = $value->fb_shares.' shares';
		}

		return array(
			'data' => $data,
			'pagination' => $query->links(),
			'total' => $query->getTotal()
		);
	}

	public function totalYoutube()
	{
		return Videos::where('iframe', 0)->count();
	}

	public function totalIframeYoutube()
	{
		return Videos::where('iframe', 1)->count();
	}

	public function updateIframeVideo()
	{
		$id = Input::get('id');
		$action = Input::get('action');

		$query = Videos::find($id);

		if (!$query) return status_error(array('message' => 'Video not exist'));

		if ($action == 'remove-from-iframe')
		{
			$query->iframe = 0;
		}
		else
		{
			$query->iframe = 1;
			$query->google_ads = 1;
		}

		$query->save();

		return status_ok(array('message' => 'Video has been added to iframe'));
	}

	public function updatePageGoogleAds()
	{	
		$total = $query = Usernames::where('user_type', 'page')
						->where('google_ads', 0)
						->count();
		$total = ceil($total / 100);		
		$offset = 0;

		for ($i=0; $i < $total; $i++)
		{ 
			$query = Usernames::where('user_type', 'page')
							->where('google_ads', 0)
							->orderBy('id', 'asc')
							->skip($offset)
							->take(100)
							->get();		

			foreach ($query as $key => $value)
			{
				$page = Page::find($value->user_id);

				if ($page->category_id != 4)
				{
					$value->google_ads = 1;
					$value->save();
				}
				//sleep(5);
			}

			$offset = $offset + 100;
			//sleep(5);
		}

		return status_ok();
	}

	public function updateVideosFacebookShares()
	{
		$query = Videos::where('iframe', 1)->get();

		echo '<pre>';
		foreach ($query as $key => $value)
		{
			$url = URL::route('watch').'?v='.$value->video_id;			
			$json = file_get_contents('https://graph.facebook.com/?id='.$url);
			$json = json_decode($json, true);
			
			if (isset($json['shares']))
			{
				$value->fb_shares = $json['shares'];
				$value->save();
			}		
		}

		dd();
	}

	public function getFactualCategories()
	{
		return FactualCategories::orderBy('en', 'asc')->get();
	}

	public function bestbuyapi()
	{
		$user_logged_id = Auth::user()->id;
		$total_inserted = 0;		

		for ($i=141; $i <= 150 ; $i++)
		{ 
			$json_string = file_get_contents('http://api.remix.bestbuy.com/v1/stores?format=json&apiKey=2yakeq6kbp35b4jw4bzpgqfz&page='.$i);
			$parsed_json = json_decode($json_string);
			echo 'Total: '.count($parsed_json->stores).'<br/>';

			foreach ($parsed_json->stores as $key => $store)
			{
				$store_id = $store->storeId;
				$query = BestBuyIds::where('store_id', $store_id)->first();	

				if ($store->region != 'PR' && $store->country == 'US')
				{			
					if (!$query)
					{
						$username = Str::slug($store->longName);

						$query = Usernames::where('username', $username)->first();

						if (!$query)
						{
							$city_id = '';
							$country_id = 234;
							$query = Cities::where('name', $store->city)->get();
							
							if ($query->count())
							{
								foreach ($query as $key => $city)
								{
									if ($city->province->country_id == 234 && $city->province->abbreviation == $store->region)
									{
										$province_id = $city->province_id;
										$city_id = $city->id;
									}
								}
							}
							
							if ($city_id == '')
							{
								$query = Province::where('abbreviation', $store->region)->first();

								if ($query)
								{
									$province_id = $query->id;

									$query = Cities::create(array(
											'province_id' => $province_id,
											'name' => $store->city,
											'postal_code' => $store->fullPostalCode,
											'url' => Str::slug($store->city)
									));

									if ($query) $city_id = $query->id;
								}
							}

							if ($city_id != '')
							{
								$page_name = $store->longName;
								$address = $store->address;							
								$contact_number = $store->phone;
								$website = "http://stores.bestbuy.com/".$store_id."/details/";
								$facebook = "http://www.facebook.com/bestbuy";
								$twitter = "https://twitter.com/BestBuy";
								$description = 'Store Services:<br/>';

								foreach ($store->services as $key => $service)
								{
									$description .= $service->service.'<br/>';
								}

								$query = Page::create(array(
										'page_name' => $page_name,
										'category_id' => 5,
										'sub_category_id' => 169,
										'address' => $address,
										'country_id' => $country_id,
										'province_id' => $province_id,
										'city_id' => $city_id,
										'description' => $description,
										'contact_number' => $contact_number,
										'website' => $website,
										'facebook' => $facebook,
										'twitter' => $twitter,
										'user_id' => $user_logged_id,
										'homepage_visible' => 0
								));

								if (!$query) return status_error(array('message' => 'Error insert page'));

								$page_id = $query->id;

								$query = BestBuyIds::create(array(
										'store_id' => $store_id,
										'page_id' => $page_id
								));

								$query = Usernames::create(array(
										'user_id' => $page_id,
										'user_type' => 'page',
										'username' => $username
								));

								$query = PageMap::create(array(
										'page_id' => $page_id,
										'latitude' => $store->lat,
										'longitude' => $store->lng,
										'zoom_level' => 14
								));

								$query = PageStatus::create(array(
										'page_id' => $page_id,
										'status_id' => 1
								));

								$search = array('Mon:', 'Tue:', 'Wed:', 'Thurs:', 'Fri:', 'Sat:', 'Sun:', ';');
								$replace = array('Monday:', 'Tuesday:', 'Wednesday:', 'Thursday:', 'Friday:', 'Saturday:', 'Sunday:', '<br/>');
								$schedule = str_replace($search, $replace, $store->hoursAmPm);

								$query = PageSchedule::create(array(
										'page_id' => $page_id,
										'schedule' => $schedule
								));

								$total_inserted++;
								echo '<span style="color:#0f0;">City: '.$store->city.' | Region: '.$store->region.' | Country: '.$store->country.' - inserted</span><br/>';
							}
							else
							{
								echo '<span style="color:#f00;">City: '.$store->city.' | Region: '.$store->region.' | Country: '.$store->country.' - not in db</span><br/>';
							}
						}
						else
						{
							echo '<span style="color:#ff0;">City: '.$store->city.' | Region: '.$store->region.' | Country: '.$store->country.' - exists</span><br/>';
						}
					}
				}
				else
				{
					echo '<span style="color:#00f;">City: '.$store->city.' | Region: '.$store->region.' | Country: '.$store->country.' - Not US Branch</span><br/>';
				}
			}
		}

		echo 'Total inserted: '.$total_inserted;

		dd();
	}

	public function factualGenerateExcel()
	{
		echo '<pre>';			
		$country_code = 'ph';
		$country_id = 169;

		$province_id = 25;
		$province_name = 'Cebu';
		$counter = 0;

		$cities = Cities::where('province_id', $province_id)
					//->where('id', '>', 4936)
					->take(100)
					->get();

		foreach ($cities as $city)
		{
			sleep(5);
			$city_id = $city->id;
			$city_name = $city->name;			
			
			echo 'City id: '.$city_id.'<br/>';
			echo 'City name: '.$city_name.'<br/>';
			echo 'Province id: '.$province_id.'<br/>';
			echo 'Country id: '.$country_id.'<br/>';			

			$cat_array = array(
				'347' => 92, // Restaurants
				'436' => 220, // Hotels and Motels
				'221' => 227, // Banking and Finance
				'80' => 167, // Pharmacies
				'312' => 127, // Bars
				'349' => 222, // Barbecue
				'280' => 204, // Beauty Salons and Barbers
				'130' => 153, // Bookstores
				'351' => 36, // Burgers
				'421' => 266, // Car and Truck Rentals
				'29' => 256, // Colleges and Universities
				'133' => 169, // Computers and Electronics
				'137' => 180, // Construction Supplies
				'138' => 161, // Convenience Stores
				'141' => 165, // Department Stores
				'453' => 258, // Embassies
				'355' => 62, // Fast Food
				'338' => 92, // Food and Dining
				'417' => 177, // Gas Stations
				'37' => 258, // Government Departments and Agencies
				'110' => 115, // Historic and Protected Sites
				'74' => 272, // Hospitals, Clinics and Medical Centers
				'311' => 117, // Museums
				'334' => 137, // Night Clubs
				'387' => 219, // Outdoors
				'166' => 273, // Pawn Shops
				'291' => 201, // Real Estate
				'53' => 253, // Chocolate
				'438' => 223, // Resorts
				'364' => 96, // Seafood
				'169' => 187, // Shopping Centers and Malls
				'285' => 206, //Spas
				'170' => 207, // Sporting Goods
				'372' => 219, // Sports and Recreation
				'365' => 102, // Steakhouses
				'171' => 274, // Supermarkets and Groceries
				'430' => 214, // Travel
				'440' => 214, // Travel Agents and Tour Operators
				'306' => 268, // Web Design and Development
				'371' => 126, // Zoos, Aquariums and Wildlife Sanctuaries
				'363' => 89, // Pizza
				'303' => 235, // Shipping, Freight, and Material Transportation
				'318' => 137, // Adult
				'47' => 254, // Organizations and Associations
				'332' => 116, //Movie Theatres
				'34' => 255, // Primary and Secondary Schools
				'220' => 275, // Accounting and Bookkeeping
				'193' => 276, // Advertising and Marketing
				'348' => 38, // American Restaurant
				'457' => 63, // Asian Restaurant
				'350' => 271, // Buffets
				'342' => 42, // Cafes, Coffee and Tea Houses
				'352' => 41, // Chinese Restaurant
				'356' => 64, // French Restaurant
				'357' => 72, // Indian Restaurant
				'358' => 74, // Italian Restaurant
				'359' => 75, // Japanese Restaurant
				'360' => 77, // Korean Restaurant
				'361' => 82, // Mexican Restaurant
				'366' => 103, // Sushi Restaurant
				'367' => 108, // Thai Restaurant
				'287' => 211, // Tattooing
				'35' => 243, //Tutoring and Educational Services
				'385' => 179, // Gyms and Fitness Centers
			);			
			
			foreach ($cat_array as $cat_key => $cat_value)
			{
				if ($city_id != '' && $province_id != '' && $country_id != '')
				{		
					$offset = 0;
					/*Cdr0sJRda76eKcfGGH1CiLSweIj5mmnFJRv9WYVy
					Cn1jUVdwlXqZdMU9tN50Ps2qIBk2sRvUrxS01CjB

					Cm9IKZ7bdWJlZ9EMswhdorpx6EunDIIPp2TnPXnN
					wntOnXAayhbCatRF0Bd7hHmbMqJN8BHEy3IUMeGu*/

					$factual = new \Factual("Cdr0sJRda76eKcfGGH1CiLSweIj5mmnFJRv9WYVy","Cn1jUVdwlXqZdMU9tN50Ps2qIBk2sRvUrxS01CjB");
					$factual_query = new \FactualQuery;

					for($i = 0; $i < 10; $i++)
					{	
						
			

						$factual_query->offset($offset);
						$factual_query->limit(50);
						$factual_query->field("category_ids")->in($cat_key);
						$factual_query->field("country")->in($country_code);
						$factual_query->field("region")->in($province_name);
						$factual_query->field("locality")->in($city_name); 
						$res = $factual->fetch("places-v3", $factual_query);

						/*$query->offset(10);
						$query->limit(50);
						$query->field("region")->equal("Cebu");
						$query->field("placetype")->equal("locality");  //we don't want counties, etc.
						$query->only("name,placetype,longitude,latitude"); //"take only what you need from me.."(singing)
						$res = $factual->fetch("world-geographies", $query);*/

						$total = count($res->getData());

						echo $total.'<br/><br/>';

						//$total = 0;
						if ($total != 0)
						{
							foreach ($res->getData() as $key => $value)
							{
								$postcode = isset($value['postcode']) ? $value['postcode'] : '';
								$factual_id = $value['factual_id'];															
								$page_name = $value['name'].' '.$value['locality'].' '.$value['region'].' '.$postcode;
								$address = isset($value['address']) ? $value['address'] : '';								
								$address = $address.' '.$value['locality'].' '.$value['region'].' '.$postcode;
								$website = isset($value['website']) ? $value['website'] : '';
								$contact_number = isset($value['tel']) ? $value['tel'] : '';
								$long = isset($value['longitude']) ? $value['longitude'] : '';
								$lat = isset($value['latitude']) ? $value['latitude'] : '';
								$hours_display = isset($value['hours_display']) ? $value['hours_display'] : '';

								$value['category_labels'] = array_unique(array_flatten($value['category_labels']));
								$category_labels = '';

								foreach ($value['category_labels'] as $cat_labels_key => $cat_labels_value)
								{
									$category_labels .= $cat_labels_value.',';
								}					
				

								$data[$counter]['factual_id'] = $factual_id;
								$data[$counter]['business_name'] = $page_name;
								$data[$counter]['address'] = $address;
								$data[$counter]['contact_number'] = $contact_number;
								$data[$counter]['website'] = $website;
								$data[$counter]['latitude'] = $lat;
								$data[$counter]['longitude'] = $long;
								$data[$counter]['hours'] = $hours_display;
								$data[$counter]['labels'] = substr($category_labels, 0, (strlen($category_labels) - 1)); 

								$counter++; 
							}
						}

						//echo '<br/><br/>';
						//print_r($res->getData());

						if ($total < 50) break;

						$offset = $offset + 50;
					}			
				}
			}

			if (isset($data))
			{
				Excel::create($city_name.' '.$province_name, function($excel) use($data) {

				    $excel->sheet('Sheet1', function($sheet) use($data) {

				        $sheet->fromArray($data);

				    });

				})->store('xls');
			}			
		}		

		dd();
	}

	public function factual()
	{
		//echo public_path();
		//$json_string = file_get_contents(public_path().'\assets\factual_taxonomy.json');
		//$parsed_json = json_decode($json_string);
		
		echo '<pre>';
		/*print_r($parsed_json);
		foreach ($parsed_json as $key => $value)
		{
			//echo $key.'<br/>';
			//echo $value->{'labels'}->{'en'}.'<br/>';

			$query = FactualCategories::create(array(
				'category_id' => $key,
				'kr' => $value->{'labels'}->{'kr'},
				'zh_hant' => $value->{'labels'}->{'zh_hant'},
				'en' => $value->{'labels'}->{'en'},
				'zh' => $value->{'labels'}->{'zh'},
				'jp' => $value->{'labels'}->{'jp'},
				'pt' => $value->{'labels'}->{'pt'},
				'de' => $value->{'labels'}->{'de'},
				'it' => $value->{'labels'}->{'it'},
				'es' => $value->{'labels'}->{'es'},
				'fr' => $value->{'labels'}->{'fr'}
			));
		}*/		

		$category = 306;

		$country_code = 'us';
		$country_id = 234;

		$province_id = 102;
		$province_name = 'IL';

		$cities = Cities::where('province_id', $province_id)
					->where('id', '>', 4936)
					->take(100)
					->get();

		foreach ($cities as $city)
		{
			sleep(5);
			$city_id = $city->id;
			$city_name = $city->name;

			$category_id = '';
			
			echo 'City id: '.$city_id.'<br/>';
			echo 'City name: '.$city_name.'<br/>';
			echo 'Province id: '.$province_id.'<br/>';
			echo 'Country id: '.$country_id.'<br/>';
			echo 'Category id: '.$category.'<br/>';

			$cat_array = array(
				'347' => 92, // Restaurants
				'436' => 220, // Hotels and Motels
				'221' => 227, // Banking and Finance
				'80' => 167, // Pharmacies
				'312' => 127, // Bars
				'349' => 222, // Barbecue
				'280' => 204, // Beauty Salons and Barbers
				'130' => 153, // Bookstores
				'351' => 36, // Burgers
				'421' => 266, // Car and Truck Rentals
				'29' => 256, // Colleges and Universities
				'133' => 169, // Computers and Electronics
				'137' => 180, // Construction Supplies
				'138' => 161, // Convenience Stores
				'141' => 165, // Department Stores
				'453' => 258, // Embassies
				'355' => 62, // Fast Food
				'338' => 92, // Food and Dining
				'417' => 177, // Gas Stations
				'37' => 258, // Government Departments and Agencies
				'110' => 115, // Historic and Protected Sites
				'74' => 272, // Hospitals, Clinics and Medical Centers
				'311' => 117, // Museums
				'334' => 137, // Night Clubs
				'387' => 219, // Outdoors
				'166' => 273, // Pawn Shops
				'291' => 201, // Real Estate
				'53' => 253, // Chocolate
				'438' => 223, // Resorts
				'364' => 96, // Seafood
				'169' => 187, // Shopping Centers and Malls
				'285' => 206, //Spas
				'170' => 207, // Sporting Goods
				'372' => 219, // Sports and Recreation
				'365' => 102, // Steakhouses
				'171' => 274, // Supermarkets and Groceries
				'430' => 214, // Travel
				'440' => 214, // Travel Agents and Tour Operators
				'306' => 268, // Web Design and Development
				'371' => 126, // Zoos, Aquariums and Wildlife Sanctuaries
				'363' => 89, // Pizza
				'303' => 235, // Shipping, Freight, and Material Transportation
				'318' => 137, // Adult
				'47' => 254, // Organizations and Associations
				'332' => 116, //Movie Theatres
				'34' => 255, // Primary and Secondary Schools
				'220' => 275, // Accounting and Bookkeeping
				'193' => 276, // Advertising and Marketing
				'348' => 38, // American Restaurant
				'457' => 63, // Asian Restaurant
				'350' => 271, // Buffets
				'342' => 42, // Cafes, Coffee and Tea Houses
				'352' => 41, // Chinese Restaurant
				'356' => 64, // French Restaurant
				'357' => 72, // Indian Restaurant
				'358' => 74, // Italian Restaurant
				'359' => 75, // Japanese Restaurant
				'360' => 77, // Korean Restaurant
				'361' => 82, // Mexican Restaurant
				'366' => 103, // Sushi Restaurant
				'367' => 108, // Thai Restaurant
				'287' => 211, // Tattooing
				'35' => 243, //Tutoring and Educational Services
				'385' => 179, // Gyms and Fitness Centers
			);		

			if (isset($cat_array[$category]))
			{
				$subcat_id = $cat_array[$category];
				$query = Subcategories::find($subcat_id);

				if ($query)
				{
					$category_id = $query->category_id;				
				}
			}
			
			if ($category_id != '' && $city_id != '' && $province_id != '' && $country_id != '')
			{		
				$offset = 0;
				$user_logged_id = Auth::user()->id;

				/*Cdr0sJRda76eKcfGGH1CiLSweIj5mmnFJRv9WYVy
				Cn1jUVdwlXqZdMU9tN50Ps2qIBk2sRvUrxS01CjB

				Cm9IKZ7bdWJlZ9EMswhdorpx6EunDIIPp2TnPXnN
				wntOnXAayhbCatRF0Bd7hHmbMqJN8BHEy3IUMeGu*/

				$factual = new \Factual("Cdr0sJRda76eKcfGGH1CiLSweIj5mmnFJRv9WYVy","Cn1jUVdwlXqZdMU9tN50Ps2qIBk2sRvUrxS01CjB");
				$factual_query = new \FactualQuery;

				for($i = 0; $i < 10; $i++)
				{	
					
		

					$factual_query->offset($offset);
					$factual_query->limit(50);
					$factual_query->field("category_ids")->in($category);
					$factual_query->field("country")->in($country_code);
					$factual_query->field("region")->in($province_name);
					$factual_query->field("locality")->in($city_name); 
					$res = $factual->fetch("places-v3", $factual_query);

					/*$query->offset(10);
					$query->limit(50);
					$query->field("region")->equal("Cebu");
					$query->field("placetype")->equal("locality");  //we don't want counties, etc.
					$query->only("name,placetype,longitude,latitude"); //"take only what you need from me.."(singing)
					$res = $factual->fetch("world-geographies", $query);*/

					$total = count($res->getData());

					echo $total.'<br/><br/>';

					//$total = 0;
					if ($total != 0)
					{
						foreach ($res->getData() as $key => $value)
						{
							$factual_id = $value['factual_id'];

							$query = FactualIds::where('factual_id', $factual_id)->first();
							
							if (!$query)
							{
								$username = Str::slug($value['name']);

								$query = Usernames::where('username', $username)->first();

								if (!$query)
								{
									FactualIds::create(array(
										'factual_id' => $factual_id
									));
									
									$page_name = $value['name'];
									$address = isset($value['address']) ? $value['address'] : '';
									$website = isset($value['website']) ? $value['website'] : '';
									$contact_number = isset($value['tel']) ? $value['tel'] : '';
									$long = isset($value['longitude']) ? $value['longitude'] : '';
									$lat = isset($value['latitude']) ? $value['latitude'] : '';

									$query = Page::create(array(
										'page_name' => $page_name,
										'category_id' => $category_id,
										'sub_category_id' => $subcat_id,
										'address' => $address,
										'country_id' => $country_id,
										'province_id' => $province_id,
										'city_id' => $city_id,
										'contact_number' => $contact_number,
										'website' => $website,
										'user_id' => $user_logged_id,
										'homepage_visible' => 0
									));

									if (!$query) return status_error(array('message' => 'Error insert page'));

									$page_id = $query->id;

									Usernames::create(array(
										'user_id' => $page_id,
										'username' => $username,
										'user_type' => 'page'
									));

									PageStatus::create(array(
										'page_id' => $page_id,
										'status_id' => 1
									));

									if ($lat != '' && $long != '')
									{						
										PageMap::create(array(
											'page_id' => $page_id,
											'latitude' => $lat,
											'longitude' => $long,
											'zoom_level' => 14
										));
									}
								}
							}
						}
					}

					//echo '<br/><br/>';
					//print_r($res->getData());

					if ($total < 50) break;

					$offset = $offset + 50;
				}			
			}
		}
		dd();
	}

	public function bloggerAutoPost()
	{
		//print_r(Session::all());
		header('Content-Type: text/html; charset=UTF-8');
		set_time_limit(0);
		$google_client = new \Google_Client();
		$google_client->setApplicationName('Blogger_Auto_Post');
		$google_client->setClientId('835435567682-538u4hv9um79kkpkrn3u2mcdfjj8741i.apps.googleusercontent.com');
		$google_client->setClientSecret('vn7O3Yj1UCUn9IU0-osOvBlc');
		$google_client->setRedirectUri('http://localhost:8000/admin/blogger-auto-post');	
		
		if (!Session::has('google_outh2_access_token') && isset($_GET['code']))
		{
			$google_client->authenticate($_GET['code']);
			Session::put('google_outh2_access_token', $google_client->getAccessToken());
		}

		if (Session::has('google_outh2_access_token'))
		{
			$google_client->setAccessToken(Session::get('google_outh2_access_token'));
		}

		if (!$google_client->isAccessTokenExpired())
		{	
			$service = new \Google_Service_Blogger($google_client);
			$post = new \Google_Service_Blogger_Post();
			Excel::load('app/storage/exports/Burger King Las Vegas NV.xls', function($reader) use ($post, $service)
			{
				//echo '<pre>';
				//print_r($reader->get()->toArray());
				//$reader = $reader->get()->toArray();
				$ctr = 0;
				$reader->skip(0);
				$reader->take(50);
				$reader->each(function($sheet) use ($post, $service, $ctr)
				{

				    // Loop through all rows
				    $sheet->each(function($row) use ($post, $service, $ctr)
				    {
				    	//var_dump($row).'<br/><br/><br/>';
				    	/*$business_name = ucwords(strtolower($row->providername));
				    	$address = str_replace(
				    		array(',', 'Dasmarinas', 'Paranaque', 'Nino'),
				    		array('', 'Dasmariñas', 'Parañaque', 'Niño'),
				    		ucwords(strtolower($row->address))
				    	);
				    	$accreditation = ucwords(strtolower($row->accreditation));
				    	echo $business_name.'<br/>';
				    	echo $accreditation.'<br/>';
				    	echo $address.'<br/><br/>';*/
				    	//echo $row->contact_numbers.'<br/>';
				    	//echo $row->store_number.'<br/>';
				    	//echo $row->landmark.'<br/>';
				    	//echo $row->schedule.'<br/><br/>';

				    	//$fax = $row->fax;
				    	//$fax = $fax != '' ? $fax : 'Not available';

				    	//$contact_numbers = $row->contact_numbers;
				    	//$contact_numbers = $contact_numbers != '' ? $contact_numbers : 'Not available';

				    	//$schedule = $row->schedule;
				    	//$schedule = $schedule != '' ? $schedule : 'Not available';

				    	//$landmark = $row->landmark;
				    	//$landmark = $landmark != '' ? $landmark : 'Not available';

				    	// Walgreens
				    	/*$content = '<div class="separator" style="clear: both; text-align: center;">
<a href="http://2.bp.blogspot.com/-3dQB6GRI9pE/U9h07KiJeJI/AAAAAAAADlc/G5Q2W7IONrU/s1600/walgreens-logo.png" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$row->business_name.'" border="0" src="http://2.bp.blogspot.com/-3dQB6GRI9pE/U9h07KiJeJI/AAAAAAAADlc/G5Q2W7IONrU/s1600/walgreens-logo.png" title="'.$row->business_name.'" /></a></div>
<a href="http://www.walgreens.com/" rel="nofollow" target="_blank">Website</a> l <a href="http://www.facebook.com/Walgreens" rel="nofollow" target="_blank">Facebook Page</a> l <a href="https://twitter.com/WALGREENS" rel="nofollow" target="_blank">Twitter</a> l <a href="https://foursquare.com/walgreens" rel="nofollow" target="_blank">Foursquare</a> l <a href="https://plus.google.com/115629332750558200099/posts" rel="nofollow" target="_blank">Google+</a><br />
<b><span style="color: #990000;">Address:</span></b> '.$row->address.'<br />
<b><span style="color: #990000;">Telephone / Landline / Contact Number:</span></b> '.$contact_numbers.'<br />
<b><span style="color: #990000;">Landmark:</span></b> '.$landmark.'<br />
<b><span style="color: #990000;">Business Hours / Open:</span></b> '.$schedule.'<br />
<b><span style="color: #990000;">Store #:</span></b> '.$row->store_number.'<br />
<i><span style="color: #666666;">Holiday hours may differ from our regular store hours.Walgreens Pharmacy '.$row->address.' Telephone / Landline / Contact Number: '.$contact_numbers.'</span></i>';*/
						
						// Motorcyle Store
						/*$content = '<div class="separator" style="clear: both; text-align: center;">
<a href="http://2.bp.blogspot.com/-SrgOHh20uBY/U_h4KVwCJjI/AAAAAAAADmY/1FwrpfhiU9A/s1600/motorcycle-icon.png" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$row->business_name.'" border="0" src="http://2.bp.blogspot.com/-SrgOHh20uBY/U_h4KVwCJjI/AAAAAAAADmY/1FwrpfhiU9A/s1600/motorcycle-icon.png" title="'.$row->business_name.'" /></a></div>
<i><span style="color: #666666;">Honda Motorcycle Authorized Dealer &nbsp;/ 3S Shop</span></i><br />
<b><span style="color: #990000;">Address:</span></b> '.$row->address.'<br />
<b><span style="color: #990000;">Telephone / Landline / Contact Number:</span></b> '.$contact_numbers.'<br />
<b><span style="color: #990000;">Fax:</span></b> '.$fax.$row->business_name.' '.$row->address.' Telephone / Landline / Contact Number: '.$contact_numbers;*/

				    	// Pag-IBIG Fund
				    	/*$content = '<div class="separator" style="clear: both; text-align: center;">
<a href="http://1.bp.blogspot.com/-YL0fL4k5a64/VAMnGieYveI/AAAAAAAADnI/Fu0mtEAXVZ4/s1600/393298_461095210578685_1726064396_n.jpg" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$row->business_name.'" border="0" src="http://1.bp.blogspot.com/-YL0fL4k5a64/VAMnGieYveI/AAAAAAAADnI/Fu0mtEAXVZ4/s1600/393298_461095210578685_1726064396_n.jpg" height="100" title="'.$row->business_name.'" width="100" /></a></div>
<a href="http://www.pagibigfund.gov.ph/" rel="nofollow" target="_blank">Website</a> | <a href="http://www.facebook.com/pages/Pag-IBIG-Fund-HDMF/135568203131389" rel="nofollow" target="_blank">Facebook Page</a> | <a href="http://www.youtube.com/user/pagibigfundHDMF" rel="nofollow" target="_blank">Youtube</a><br />
<b><span style="color: #990000;">Address:</span></b>&nbsp;'.$row->address.'Pag-IBIG Fund '.$row->address.' Telephone / Landline / Contact Numbers: '.$contact_numbers.'<br />';

if ($row->landmark != '')
{
	$content .= '<b><span style="color: #990000;">Landmark:</span></b>&nbsp;'.$row->landmark.'<br />';
}

$content .= '<b><span style="color: #990000;">Telephone / Landline / Contact Numbers:</span></b>&nbsp;'.$contact_numbers.'<br />';

if ($row->fax != '')
{
	$content .= '<b><span style="color: #990000;">Fax:</span></b>&nbsp;'.$row->fax.'<br />';
}

if ($row->email != '')
{
	$content .= '<b><span style="color: #990000;">Email:</span></b>&nbsp;'.$row->email;
}*/
					//pal
					/*$content = '<div class="separator" style="clear: both; text-align: center;">
					<a href="http://2.bp.blogspot.com/-0EQ_oAo1Y1k/VAk2-FZujUI/AAAAAAAADnY/_zTLTTFXuFI/s1600/Philippine-Airlines-Logo.jpg" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$row->business_name.'" border="0" src="http://2.bp.blogspot.com/-0EQ_oAo1Y1k/VAk2-FZujUI/AAAAAAAADnY/_zTLTTFXuFI/s1600/Philippine-Airlines-Logo.jpg" title="'.$row->business_name.'" /></a></div>
					<b><span style="color: #990000;">Address:</span></b>&nbsp;'.$row->address.'<br />
					<b><span style="color: #990000;">Telephone / Landline/ Contact Numbers:</span></b>&nbsp;'.$contact_numbers.'<br />';
					
					if ($row->fax != '')
					{
						$content .= '<b><span style="color: #990000;">Fax:</span></b>&nbsp;'.$row->fax.'<br />';
					}

					if ($row->office_hours != '')
					{
						$content .= '<b><span style="color: #990000;">Office Hours / Open:</span></b>&nbsp;'.$row->office_hours.'<br />';
					}

					if ($row->supervisor != '')
					{
						$content .= '<b><span style="color: #990000;">Supervisor:</span></b>&nbsp;'.$row->supervisor.'<br />';
					}

					if ($row->email != '')
					{
						$content .= '<b><span style="color: #990000;">Email:</span></b>&nbsp;'.$row->email.'<br />';
					}

					if ($row->note != '')
					{
						$content .= '<b><span style="color: #990000;">Note:</span></b>&nbsp;'.$row->note.'<br />';
					}

					$content .= '<br />'.$row->business_name.' '.$row->address.' Telephone / Landline/ Contact Numbers: '.$contact_numbers;*/

					// Ford
					/*$content = '<div class="separator" style="clear: both; text-align: center;">
<a href="http://3.bp.blogspot.com/-xdve8fYZpgs/VAps1gZ2UuI/AAAAAAAADno/MUQffAO4rjE/s1600/1503979_584599024953074_1100361955_n.jpg" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$row->business_name.'" border="0" src="http://3.bp.blogspot.com/-xdve8fYZpgs/VAps1gZ2UuI/AAAAAAAADno/MUQffAO4rjE/s1600/1503979_584599024953074_1100361955_n.jpg" height="100" title="'.$row->business_name.'" width="100" /></a></div>
<a href="http://www.ford.com.ph/" rel="nofollow" target="_blank">Website</a> | <a href="https://www.facebook.com/FordPhilippines" rel="nofollow" target="_blank">Facbook Page</a><br />
<b><span style="color: #990000;">Address:</span></b>&nbsp;'.$row->address.'<br />
<span style="color: #990000;"><b>Telephone / Landline / Contact Number:</b></span>&nbsp;'.$contact_numbers.'<br />';
					
					
					if ($row->fax != '')
					{
						$content .= '<b><span style="color: #990000;">Fax:</span></b>&nbsp;'.$row->fax.'<br />';
					}

					if ($row->email != '')
					{
						$content .= '<b><span style="color: #990000;">Email:</span></b>&nbsp;'.$row->email.'<br />';
					}

					$content .= $row->business_name.' '.$row->address.' Telephone / Landline/ Contact Numbers: '.$contact_numbers;*/
					
				    //factual
				    /*$business_name = $row->business_name;
				    $address = $row->address;
				    $contact_number = $row->contact_number;
				    $website = $row->website != '' ? '<b><span style="color: #990000;">Website:</span></b> <a href="'.$row->website.'" rel="nofollow" target="_blank">'.$row->website.'</a><br />' : '';
				    $longitude = $row->longitude;
				    $latitude = $row->latitude;
				    $hours = $row->hours;
				    $hours = $hours != '' ? '<b><span style="color: #990000;">Hours Open:</span></b> '.$hours.'<br />' : '';

				    $content = '<b><span style="color: #990000;">Address:</span></b> '.$address.'<br />
								<b><span style="color: #990000;">Telephone / Landline / Contact Number:</span></b> '.$contact_number.'<br />';

					$content .= $website.$hours.$business_name.' '.$address.' Telephone / Landline / Contact Number: '.$contact_number.'<br/>';
					
					$content .= '<b><span style="color: #990000;">Map of '.$business_name.'</span></b><br/><iframe frameborder="0" height="300" src="https://www.google.com/maps/embed/v1/place?key=AIzaSyCY0VfO1R7JEn8B7b2J9zu5AxFAy9R33tY&amp;q='.$latitude.','.$longitude.'&amp;zoom=14" style="border: 1px solid #ccc;" width="100%"></iframe>';
					*/

						$address = $row->address;
				    	$city = $row->city;
				    	$state = $row->state;
				    	$zip = $row->zip;
				    	$country = $row->country;
				    	$contact_number = $row->phone;
				    	$hours_weekdays = $row->hours_weekdays;
				    	$hours_weekends = $row->hours_weekends;
				    	$latitude = $row->latitude;
				    	$longitude = $row->longitude;

				    	$business_name = 'Burger King '.$address.' '.$city.' '.$state;
				    	$address = $address.', '.$city.' '.$state.' '.$zip.', '.$country;
				    					    	
				    $content = '<div class="clearfix">
<div class="separator" style="clear: both; text-align: center;">
<img src="http://2.bp.blogspot.com/-ibcFaYWaJtY/VHPnWn5_8OI/AAAAAAAADw0/xmkr_6V90RY/s1600/burger-king.jpg" style="display: none;" />
<a href="http://3.bp.blogspot.com/-8Krw3biD4qw/VHPnV5yIPDI/AAAAAAAADws/IoVSE3iakdI/s1600/burger-king-logo.jpg" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$business_name.'" border="0" src="http://3.bp.blogspot.com/-8Krw3biD4qw/VHPnV5yIPDI/AAAAAAAADws/IoVSE3iakdI/s1600/burger-king-logo.jpg" title="'.$business_name.'" /></a></div>
<a href="http://www.bk.com/" rel="nofollow" target="_blank">Website</a> | <a href="https://www.facebook.com/burgerking" rel="nofollow" target="_blank">Facebook Page</a> | <a href="https://twitter.com/BurgerKing" rel="nofollow" target="_blank">Twitter</a> | <a href="http://instagram.com/burgerking" rel="nofollow" target="_blank">Instagram</a> | <a href="https://www.youtube.com/user/bk" rel="nofollow" target="_blank">YouTube</a> | <a href="https://plus.google.com/+BURGERKING/posts" rel="nofollow" target="_blank">Google+</a><br />
<b><span style="color: #990000;">Address:</span></b>&nbsp;'.$address.'<br />
<b><span style="color: #990000;">Telephone / Landline / Contact Numbers:</span></b>&nbsp;'.$contact_number.'<br />
<b><span style="color: #990000;">Hours Open:</span></b>&nbsp;'.$hours_weekdays.' / '.$hours_weekends.'<br />
<b><span style="color: #990000;">About:</span></b>&nbsp;Every day, more than 11 million guests visit BURGER KING® restaurants around the world. And they do so because our restaurants are known for serving high-quality, great-tasting, and affordable food. Founded in 1954, BURGER KING® is the second largest fast food hamburger chain in the world. The original HOME OF THE WHOPPER®, our commitment to premium ingredients, signature recipes, and family-friendly dining experiences is what has defined our brand for more than 50 successful years.<br/>Burger King '.$address.'<br />
<br /></div>
<div class="panel panel-default">
<div class="clearfix">
<iframe class="pull-left" frameborder="0" height="300" src="https://www.google.com/maps/embed/v1/place?key=AIzaSyCY0VfO1R7JEn8B7b2J9zu5AxFAy9R33tY&amp;q='.$latitude.','.$longitude.'&amp;zoom=14" width="100%"></iframe>
</div>
<h5 class="panel-footer">
Map of Burger King '.$address.'</h5>
</div>';

				    	/*$content = '<div class="separator" style="clear: both; text-align: center;">
<img src="http://3.bp.blogspot.com/-1MgOnNIATTg/VGiUfc6UiLI/AAAAAAAADv4/ZHagtFipjAM/s1600/the-generics-pharmacy-logo.png" style="display: none;" />
<a href="http://4.bp.blogspot.com/-4R3w_nihbgo/VGiUeyf8WtI/AAAAAAAADv0/SIvWDvkl3cQ/s1600/the-generics-pharmacy-logo.jpg" imageanchor="1" style="clear: left; float: left; margin-bottom: 1em; margin-right: 1em;"><img alt="'.$business_name.'" border="0" src="http://4.bp.blogspot.com/-4R3w_nihbgo/VGiUeyf8WtI/AAAAAAAADv0/SIvWDvkl3cQ/s1600/the-generics-pharmacy-logo.jpg" title="'.$business_name.'" /></a></div>
<a href="http://www.thegenericspharmacy.com/" rel="nofollow" target="_blank">Website</a> | <a href="https://www.facebook.com/tgpthegenericspharmacy" rel="nofollow" target="_blank">Facebook Page</a> | <a href="https://twitter.com/tgppharma" rel="nofollow" target="_blank">Twitter</a> | <a href="http://instagram.com/tgpinoy" rel="nofollow" target="_blank">Instagram</a>&nbsp;| <a href="https://plus.google.com/u/0/b/103339997329789353919/103339997329789353919/posts" rel="nofollow" target="_blank">Google+</a><br />
<b><span style="color: #990000;">Address:</span></b> '.$address.'<br />
<div>
<b><span style="color: #990000;">Telephone / Landline / Contact Number:</span></b> '.$contact_number.'<br/>
<b><span style="color: #990000;">Fax:</span></b> '.$fax.'
</div>
<div>
<b><span style="color: #990000;">Email:</span></b>&nbsp;<a href="mailto:customercare@tgp.com.ph" rel="nofollow" target="_blank">customercare@tgp.com.ph</a><br />
<!--more--></div>
<div>
The Generics Pharmacy '.$address.' Telephone / Landline / Contact Number: '.$contact_number.'<br/>
<b><span style="color: #990000;">About:</span></b> THE GENERICS PHARMACY (TGP) started out as small pharmaceutical company in 1949. Acknowledging the dire need for quality medicines but at affordable prices, the company focused on generic medicines to provide the Filipino with a more affordable alternative. In 2001, the company ventured into retail, starting with only a single outlet. As demand for its products grew, the company decided to bring their medicines more accessible to all the far reaches of the country through the FRANCHISING business model. The historic year was 2007, starting with twenty (20) outlets within Metro Manila.</div>
<div>
<br /></div>
<div>
Now as The Generics Pharmacy (TGP), it revolutionized the entire pharmaceutical and retail industry with its bold and unusual path to growth. Who would think that a pharmacy carrying only pure generic drugs rapidly take off? After the initial struggles and birth pains, the healthcare landscape has embraced and accepted generic medicine as it has proven to be as effective and of high quality standards and yet, truly affordable to every Filipino.</div>
<div>
<br /></div>
<div>
Now only on its 7th year in full pharmacy retail and franchising, TGP has dotted the entire archipelago with more than strong 1600+ outlets, making healthcare accessible to every Juan. As expansion grew rapidly, so with the numerous awards and recognition TGP received from various prestigious retail, franchising, marketing, social entrepreneurship and management organizations. The most recent award, the GAWAD GENERIC SUMMIT is made even more valuable by the fact that it was conferred by our own Department of Health (DOH), in celebration of Philippines’ 25th Generic Summit celebrated last September 2013. This is solid proof that TGP is now well accepted and trusted as source of quality and affordable generic medicines. TGP is now the largest retail pharmacy chain in the country.</div>
<div>
<br /></div>
<div>
We carry on with the vision to hit 2000 strong outlets by 2015.</div>';*/


				    	$post->setTitle($business_name);
						$post->setContent($content);
						$post->setLabels(array('Fastfood', 'Food / Beverages', 'Burger King'));
						$post->setCustomMetaData($business_name);
						$service->posts->insert('7421912686553689515', $post);
				    });

				});				
			});						
		}
		else
		{
			Session::forget('google_outh2_access_token');
			$google_client->addScope(\Google_Service_Blogger::BLOGGER);
			$google_client->setAccessType('offline');
			$authUrl = $google_client->createAuthUrl();
			echo '<a href="'.$authUrl.'">Login Here</a>';
		}

		return 1;
		//dd();
	}

	public function parseHtml()
	{
		set_time_limit(0);
		Session::forget('data');			
		for ($i=0; $i <= 12 ; $i++)
		{ 
			$content = file_get_contents('http://www.bk.com/locations?field_geofield_distance[origin][lat]=36.1699412&field_geofield_distance[origin][lon]=-115.13982959999998&page='.$i.'&target=Las%20Vegas,%20NV');

			$crawler = new Crawler();
			$crawler->addHtmlContent($content, 'UTF-8');
			echo '<pre>';

			$crawler->filter('div.bk-restaurants ul li')
					->each(function ($node, $i) use ($crawler)
					{
	     				//$url = 'http://www.apple.com'.$node->attr('href');
		     				
	     				//if ($url != 'http://www.apple.com/retail/trumbull/' && $url != 'http://www.apple.com/retail/solomonpondmall/')
	     				//{
		     				//$content = file_get_contents($url);
		     				//$crawler->addHtmlContent($content, 'UTF-8');
						
						$id = $node->filter('div.bk-counter')->text();
												
						Session::push('data.'.$id.'.id', $node->filter('div.bk-id')->text());
						Session::push('data.'.$id.'.Address', $node->filter('div.bk-address1')->text());
						Session::push('data.'.$id.'.City', $node->filter('div.bk-city')->text());
						Session::push('data.'.$id.'.State', $node->filter('div.bk-state')->text());
						Session::push('data.'.$id.'.Zip', $node->filter('div.bk-zip')->text());
						Session::push('data.'.$id.'.Country', $node->filter('div.bk-country')->text());
						Session::push('data.'.$id.'.Hours Weekdays', $node->filter('div.bk-weekday-hours')->text());
						Session::push('data.'.$id.'.Hours Weekends', $node->filter('div.bk-weekend-hours')->text());
						Session::push('data.'.$id.'.latitude', $node->filter('div.bk-latitude')->text());
						Session::push('data.'.$id.'.longitude', $node->filter('div.bk-longitude')->text());

						if ($node->filter('div.bk-phone')->count())
						{
							Session::push('data.'.$id.'.Phone', $node->filter('div.bk-phone')->text());							
						}
						else
						{
							Session::push('data.'.$id.'.Phone', 'Not available');
						}

							//$data[$i]['Address'] = $node->filter('td')->eq(1)->text().' Philippines';
							//$data[$i]['Contact Numbers'] = $node->filter('td')->eq(2)->text();
		     				//$store_title = $crawler->filter('td.x124')->text();
		     				/*$store_name = $crawler->filter('div.store-name')->text();
		     				$street_address = $crawler->filter('div.street-address')->text();
		     				$locality = $crawler->filter('span.locality')->text();
		     				$region = $crawler->filter('span.region')->text();
		     				$postal_code = $crawler->filter('span.postal-code')->text();
		     				$contact_number = $crawler->filter('div.telephone-number')->text();
		     				$directions = $crawler->filter('div.store-directions')->text();
		     				$hours = $crawler->filter('table.store-info')->text();	     				

		     				$crawler->clear();

		     				//echo $store_name.'<br/>';
		     				$data[$i]['Business Name'] = $store_title.' '.$store_name.' '.$locality.' '.$region.' '.$postal_code;
		     				$data[$i]['Address'] = $street_address.' '.$locality.' '.$region.' '.$postal_code;
		     				$data[$i]['Contact Numbers'] = $contact_number;
		     				$data[$i]['Hours'] = $hours;
		     				$data[$i]['directions'] = $directions;
		     				$data[$i]['Map'] = $url;

		     				
		     				//sleep(3);*/

		     				//Session::push('data.'.$id.'.id', $data[$i]['Business Name']);
		     				//Session::push('data.'.$i.'.Address', $data[$i]['Address']);
		     				//Session::push('data.'.$i.'.Contact Numbers', $data[$i]['Contact Numbers']);
		     				//Session::push('data.'.$i.'.Hours', $data[$i]['Hours']);
		     				//Session::push('data.'.$i.'.directions', $data[$i]['directions']);
		     				//Session::push('data.'.$i.'.Map', $data[$i]['Map']);
		     				
		     				/*
		     				if ($i == 2)
		     				{
			 					Excel::create('Apple Store', function($excel) use($data)
			 					{

								    $excel->sheet('Sheet1', function($sheet) use($data)
								    {

								        $sheet->fromArray($data);

								    });

								})->store('xls');
							}*/					
	     				//} 	     				   				   				
	    			});			
	    }	

		foreach (Session::get('data') as $key => $value)
		{
			$data[$key]['id'] = $value['id'][0];
			$data[$key]['Address'] = $value['Address'][0];
			$data[$key]['City'] = $value['City'][0];
			$data[$key]['State'] = $value['State'][0];
			$data[$key]['Zip'] = $value['Zip'][0];
			$data[$key]['Country'] = $value['Country'][0];
			$data[$key]['Phone'] = $value['Phone'][0];
			$data[$key]['Hours Weekdays'] = $value['Hours Weekdays'][0];
			$data[$key]['Hours Weekends'] = $value['Hours Weekends'][0];
			$data[$key]['latitude'] = $value['latitude'][0];
			$data[$key]['longitude'] = $value['longitude'][0];
		}

		/*$data = array(
		    array('Business Name' => 'data1', 'Contact Numbers' => 'data2'),
		    array('Business Name' => 'data3', 'Contact Numbers' => 'data4')
		);*/

		//print_r($data);

		Excel::create('Burger King Las Vegas NV', function($excel) use($data) {

		    $excel->sheet('Sheet1', function($sheet) use($data) {

		        $sheet->fromArray($data);		        

		    });

		})->store('xls');

		dd();
	}
}