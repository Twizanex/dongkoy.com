<?php namespace Edongkoy\Repositories\Page;

# app/library/Edongkoy/Repositories/Page/EloquentPageRepository.php

use Edongkoy\Repositories\Page\Models\Page;
use Edongkoy\Repositories\Page\Models\PageStatus;
use Edongkoy\Repositories\Users\Models\Usernames;
use Edongkoy\Repositories\Users\Models\UserActivity;
use Edongkoy\Repositories\Admin\Models\Categories;
use Edongkoy\Repositories\Admin\Models\Subcategories;
use Edongkoy\Repositories\Admin\Models\Province;
use Edongkoy\Repositories\Admin\Models\Cities;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class EloquentPageRepository implements PageRepositoryInterface {

	public function getAllCategories()
    {
        return Categories::orderBy('category_name', 'asc')->get();
    }

    public function create()
	{
		$province_id = Input::get('province_id');
        $city_id = Input::get('city_id');
        $country_id = Input::get('country_id');
        $has_province = $this->hasProvince($country_id);

        if (($province_id and $city_id) or $has_province)
        {
            $validation = new \Services\Validators\CreatePageWithProvinceAndCity;
        }
        else if ($province_id and !$city_id)
        {
            $validation = new \Services\Validators\CreatePageWithProvinceOnly;
        }
        else
        {
            $validation = new \Services\Validators\CreatePageWithOutProvince;
        }
        
        $username = strtolower(Input::get('page_username'));

        if (in_array($username, Config::get('custom.usernames')))
        {
            if (Request::ajax())
            {
                return status_error(array('message' => Lang::get('signup.username_taken')));
            }
            else
            {
                return Redirect::back()->withInput()->with('username_not_allowed', Lang::get('signup.username_taken'));
            }
        }

        if ($validation->passes())
        {
            $owner_id = Auth::user()->id;
            $page = Page::create(array_add(Input::except('page_username', 'submit'), 'user_id', $owner_id));

            $page_id = $page->id;            

            Usernames::create(array('user_id' => $page_id, 'username' => $username, 'user_type' => 'page'));

            PageStatus::create(array('page_id' => $page_id, 'status_id' => 1));
            UserActivity::create(array(
                                        'user_id' => $owner_id,
                                        'fk_id' => $page_id,
                                        'activities_id' => 2
                                    ));

            return status_ok(array(
                'action' => 'createPage',
                'redirect' => action('ProfileController@showProfile', array('username' => $username))
            ));
        }

        if (Request::ajax())
        {
            return $validation->jsonErrors();
        }

        return Redirect::back()->withInput()->withErrors($validation->errors);
	}

    public function update($id)
    {
        $province_id = Input::get('province_id');
        $city_id = Input::get('city_id');
        $country_id = Input::get('country_id');
        $has_province = $this->hasProvince($country_id);

        if (($province_id and $city_id) or $has_province)
        {
            $validation = new \Services\Validators\EditPageWithProvinceAndCity;
        }
        else if ($province_id and !$city_id)
        {
            $validation = new \Services\Validators\EditPageWithProvinceOnly;
        }
        else
        {
            $validation = new \Services\Validators\EditPageWithOutProvince;
        }

        if ($validation->passes())
        {
            $user_logged_id = Auth::user()->id;

            $page = Page::where('id', $id)->where('user_id', $user_logged_id)->first();

            if (!$page) return status_error();
            
            $page->page_name = Input::get('page_name');
            $page->category_id = Input::get('category_id');
            $page->sub_category_id = Input::get('sub_category_id');
            $page->country_id = $country_id;
            $page->province_id = $province_id;
            $page->city_id = $city_id;
            $page->address = Input::get('address');
            $page->description = Input::get('description');
            $page->website = Input::get('website');
            $page->contact_number = Input::get('contact_number');
            $page->email = Input::get('email');
            $page->facebook = Input::get('facebook');
            $page->twitter = Input::get('twitter');
            $page->google = Input::get('google');
            $page->youtube = Input::get('youtube');

            $status = profileStatus($page->status);

            if ($status['id'] == 4) PageStatus::where('page_id', $id)->update(array('status_id' => 2));
            
            $page->save();
            return status_ok(array('action' => 'editPage', 'message' => Lang::get('pages.edit_page_success_message')));
        }

        if (Request::ajax())
        {
            return $validation->jsonErrors();
        }

        $country_id = $has_province ? $country_id : false;
        $province_id = $has_province ? $province_id : false;

        return Redirect::back()->withInput()->withErrors($validation->errors)->with(array('edit_page_error_message' => Lang::get('pages.edit_page_error_message'), 'country_id' => $country_id, 'province_id' => $province_id));
    }

    public function getHomePages()
    {
        $query = Page::with('username', 'province', 'country', 'city', 'category', 'subCategory', 'profileImage', 'status')->select('pages.*')
                ->join('page_status', 'page_status.page_id', '=', 'pages.id')
                ->where('page_status.status_id', 1)
                ->where('pages.homepage_visible', 1)
                ->orderBy('pages.id', 'desc')
                ->paginate(6);

        $data = array();
        
        if ($query)
        {
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');
            }
        }

        return array(
            'data' => $data,
            'currentPage' => $query->getCurrentPage(),
            'pagination' => $query->links()
        );
    }

    public function getAllPages()
    {
        $query = Page::with('username', 'province', 'country', 'city', 'category', 'subCategory', 'profileImage', 'status')->select('pages.*')
                ->join('page_status', 'page_status.page_id', '=', 'pages.id')
                ->where('page_status.status_id', 1)
                ->orderBy('pages.id', 'desc')
                ->paginate(6);

        $data = array();
        
        if ($query)
        {
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');

                $page_city = $value->city;
                $page_city = $page_city ? $page_city->name.' ' : '';
                $page_province = $value->province;
                $page_province = $page_province ? $page_province->name.' ' : '';
                $page_country = $value->country->english_name;

                $data[$key]['address'] = $value->address.' '.$page_city . $page_province . $page_country;
            }
        }

        return array(
            'data' => $data,
            'currentPage' => $query->getCurrentPage(),
            'pagination' => $query->links()
        );
    }

    public function getAllPagesByCatId($cat_id)
    {
        $query = Page::with('username', 'province', 'country', 'city', 'category', 'subCategory', 'profileImage', 'status')->select('pages.*')
                ->join('page_status', 'page_status.page_id', '=', 'pages.id')
                ->where('page_status.status_id', 1)
                ->where('category_id', $cat_id)
                ->orderBy('pages.id', 'desc')
                ->paginate(6);

        $data = array();
        
        if ($query)
        {
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');

                $page_city = $value->city;
                $page_city = $page_city ? $page_city->name.' ' : '';
                $page_province = $value->province;
                $page_province = $page_province ? $page_province->name.' ' : '';
                $page_country = $value->country->english_name;

                $data[$key]['address'] = $value->address.' '.$page_city . $page_province . $page_country;
            }
        }

        return array(
            'data' => $data,
            'currentPage' => $query->getCurrentPage(),
            'pagination' => $query->links()
        );
    }

    public function getAllPagesBySubCatId($sub_cat_id)
    {
        $query = Subcategories::find($sub_cat_id);

        if (!$query) return false;

        $sub_cat_name = $query->sub_category_name;

        $query = Page::with('username', 'province', 'country', 'city', 'category', 'subCategory', 'profileImage', 'status')->select('pages.*')
                ->join('page_status', 'page_status.page_id', '=', 'pages.id')
                ->where('page_status.status_id', 1)
                ->where('sub_category_id', $sub_cat_id)
                ->orderBy('pages.id', 'desc')
                ->paginate(6);

        $data = array();
        
        if ($query)
        {
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');

                $page_city = $value->city;
                $page_city = $page_city ? $page_city->name.' ' : '';
                $page_province = $value->province;
                $page_province = $page_province ? $page_province->name.' ' : '';
                $page_country = $value->country->english_name;

                $data[$key]['address'] = $value->address.' '.$page_city . $page_province . $page_country;
            }
        }

        return array(
            'data' => $data,
            'currentPage' => $query->getCurrentPage(),
            'sub_cat_name' => $sub_cat_name,
            'pagination' => $query->appends(array('subcat' => $sub_cat_id))->links()
        );
    }

    public function getAllPagesByCatIdAndCityId($cat_id, $city_id)
    {
        $query = Cities::find($city_id);

        if (!$query) return false;

        $city_name = $query->name;

        $query = Page::with('username', 'province', 'country', 'city', 'category', 'subCategory', 'profileImage', 'status')->select('pages.*')
                ->join('page_status', 'page_status.page_id', '=', 'pages.id')
                ->where('page_status.status_id', 1)
                ->where('pages.category_id', $cat_id)
                ->where('pages.city_id', $city_id)
                ->orderBy('pages.id', 'desc')
                ->paginate(6);

        $data = array();
        
        if ($query)
        {
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');

                $page_city = $value->city;
                $page_city = $page_city ? $page_city->name.' ' : '';
                $page_province = $value->province;
                $page_province = $page_province ? $page_province->name.' ' : '';
                $page_country = $value->country->english_name;

                $data[$key]['address'] = $value->address.' '.$page_city . $page_province . $page_country;
            }
        }

        return array(
            'data' => $data,
            'currentPage' => $query->getCurrentPage(),
            'city_name' => $city_name,
            'per_page' => $query->getPerPage(),
            'pagination' => $query->appends(array('city' => $city_id))->links()
        );
    }

    public function getAllPagesBySubCatIdAndCityId($sub_cat_id, $city_id)
    {
        $query = Subcategories::find($sub_cat_id);

        if (!$query) return false;

        $sub_cat_name = $query->sub_category_name;

        $query = Cities::find($city_id);

        if (!$query) return false;

        $city_name = $query->name;

        $query = Page::with('username', 'province', 'country', 'city', 'category', 'subCategory', 'profileImage', 'status')->select('pages.*')
                ->join('page_status', 'page_status.page_id', '=', 'pages.id')
                ->where('page_status.status_id', 1)
                ->where('sub_category_id', $sub_cat_id)
                ->where('pages.city_id', $city_id)
                ->orderBy('pages.id', 'desc')
                ->paginate(6);

        $data = array();
        
        if ($query)
        {
            foreach ($query as $key => $value)
            {
                $data[$key]['page_name'] = $value->page_name;
                $data[$key]['sub_category'] = $value->subCategory->sub_category_name;
                $data[$key]['url'] = URL::route('showProfile', $value->username->username);
                $data[$key]['image'] = profileImage($value, 'large');

                $page_city = $value->city;
                $page_city = $page_city ? $page_city->name.' ' : '';
                $page_province = $value->province;
                $page_province = $page_province ? $page_province->name.' ' : '';
                $page_country = $value->country->english_name;

                $data[$key]['address'] = $value->address.' '.$page_city . $page_province . $page_country;
            }
        }

        return array(
            'data' => $data,
            'currentPage' => $query->getCurrentPage(),
            'sub_cat_name' => $sub_cat_name,
            'header_text' => $sub_cat_name.' '.Lang::get('global.in').' '.$city_name,
            'city_name' => $city_name,
            'per_page' => $query->getPerPage(),
            'pagination' => $query->appends(array('subcat' => $sub_cat_id, 'city' => $city_id))->links()
        );
    }

    public function hasProvince($country_id)
    {
        $province = Province::select(DB::raw('id'))
                        ->where('country_id', $country_id)
                        ->first();

        return !$province ? false : true;
    }
}