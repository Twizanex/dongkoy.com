<?php

use Edongkoy\Repositories\Members\MembersRepositoryInterface as members;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;
use Edongkoy\Repositories\Page\PageRepositoryInterface as page;

class HomeController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	protected $members;
	protected $page;
	

	public function __construct(members $members, globals $global, page $page)
	{
		$this->members = $members;
		$this->global = $global;
		$this->page = $page;
	}

	public function showWelcome()
	{
		try {

		    //$geocode = Geocoder::geocode('124.107.67.229');
		    //echo '<pre>';
		    //print_r($geocode);
		    //dd();
		    // ...
		} catch (\Exception $e) {
		    // Here we will get "The FreeGeoIpProvider does not support Street addresses." ;)
		    //echo $e->getMessage();
		}


		$this->layout->title = Lang::get('global.site_name');
		$this->layout->metaDesc = Lang::get('global.home_meta_desc');
		$this->layout->js_var = array();
		$this->layout->js = array('jquery.dotdotdot.min.js', 'homepage.js');
		$this->layout->ogImage = URL::asset('assets/img/banner.jpg');
		$this->layout->members = $this->members->newMembers();
		$data['pages'] = $this->page->getHomePages();

		if ($data['pages']['currentPage'] != 1)
        {
            $this->layout->robots = 'noindex, follow';
        }

		$this->layout->categories = $this->global->getPageCategories();
		$this->layout->content = View::make('home')->with($data);
	}

	public function terms()
	{
		$this->layout->title = Lang::get('global.terms_of_use_title');
		$this->layout->js_var = array();
		$this->layout->js = array();
		$this->layout->content = View::make('terms');
	}

	public function privacy()
	{
		$this->layout->title = Lang::get('global.privacy_policy_title');
		$this->layout->js_var = array();
		$this->layout->js = array();
		$this->layout->content = View::make('privacy_policy');
	}

	public function sitemap()
	{
		return $this->global->sitemap();
	}

	public function sitemapPages()
	{
		return $this->global->sitemapPages();
	}

}