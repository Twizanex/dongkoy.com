<?php

use Edongkoy\Repositories\Admin\AdminRepositoryInterface as admin;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class AdminController extends BaseController {

	protected $admin;

	public function __construct(admin $admin, globals $global)
	{
		$this->beforeFilter('auth');
		$this->beforeFilter('admin');
		$this->admin = $admin;
		$this->global = $global;
		$this->totalPages = $this->admin->totalPages();
		$this->totalMembers = $this->admin->totalMembers();
		$this->totalYoutube = $this->admin->totalYoutube();
		$this->totalIframeYoutube = $this->admin->totalIframeYoutube();
		$this->robots = 'noindex, nofollow';
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{		
		$this->layout->title = 'Welcome to edongkoy.com';
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_countries.js');
		$data['countries'] = $this->admin->countriesPaginate(5);
		$this->layout->content = View::make('admin.countries')->with($data);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getCountries()
	{
		$this->layout->title = Lang::get('admin.manage_countries');
		$this->layout->inverse = true;
		$this->layout->js_var = array(
			'deleteCountryHeader' => Lang::get('admin.delete_country'),
			'deleteButtonText' => Lang::get('global.delete'),
			'deleteCountryMessage' => Lang::get('admin.delete_country_confirm_text'),
			'deleteLoadingText' => Lang::get('global.deleting')
		);
		$this->layout->js = array('manage_countries.js');
		$data['countries'] = $this->admin->countriesPaginate();
		$this->layout->content = View::make('admin.countries')->with($data);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getCategories()
	{
		$this->layout->title = Lang::get('admin.manage_categories');
		$this->layout->inverse = true;
		$this->layout->js_var = array(
			'deleteCategoryHeader' => Lang::get('admin.delete_category'),
			'deleteButtonText' => Lang::get('global.delete'),
			'deleteCategoryMessage' => Lang::get('admin.delete_category_confirm_text'),
			'deleteLoadingText' => Lang::get('global.deleting')
		);
		$this->layout->js = array('manage_categories.js');
		$data['categories'] = $this->admin->categories();
		$this->layout->content = View::make('admin.categories', $data);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['active_members'] = $this->admin->activeUsers();
		$data['unconfirmed_members'] = $this->admin->unconfirmedUsers();
		$data['blocked_members'] = $this->admin->blockedUsers();
		$data['flagged_users_by_members'] = $this->admin->flaggedUsersByMembers();
		$data['flagged_users_by_non_members'] = $this->admin->flaggedUsersByNonMembers();
		$data['deleted_members'] = $this->admin->deletedUsers();
		$this->layout->content = View::make('admin.members')->with($data);
	}

	public function getActiveMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['active_members'] = $this->admin->activeUsers();		
		$this->layout->content = View::make('admin.members.active')->with($data);
	}

	public function getUnconfirmedMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['unconfirmed_members'] = $this->admin->unconfirmedUsers();		
		$this->layout->content = View::make('admin.members.unconfirmed')->with($data);
	}

	public function getBlockedMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['blocked_members'] = $this->admin->blockedUsers();		
		$this->layout->content = View::make('admin.members.blocked')->with($data);
	}

	public function getFlaggedUsersByMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['flagged_users_by_members'] = $this->admin->flaggedUsersByMembers();		
		$this->layout->content = View::make('admin.members.flagged_by_members')->with($data);
	}

	public function getFlaggedUsersByNonMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['flagged_users_by_non_members'] = $this->admin->flaggedUsersByNonMembers();		
		$this->layout->content = View::make('admin.members.flagged_by_non_members')->with($data);
	}

	public function getDeactivatedMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['deactivated_members'] = $this->admin->deactivatedUsers();		
		$this->layout->content = View::make('admin.members.deactivated')->with($data);
	}

	public function getDeletedMembers()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('manage_members.js');
		$data['deleted_members'] = $this->admin->deletedUsers();		
		$this->layout->content = View::make('admin.members.deleted')->with($data);
	}	

	public function getActivePages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['active_pages'] = $this->admin->activePages();
		$this->layout->content = View::make('admin.pages.active')->with($data);
	}

	public function getPagesWithGoogleAds()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['pages'] = $this->admin->pagesWithGoogleAds();
		$this->layout->content = View::make('admin.pages.with_google_ads')->with($data);
	}

	public function getPendingPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['unconfirmed_pages'] = $this->admin->unconfirmedPages();
		$this->layout->content = View::make('admin.pages.pending')->with($data);
	}

	public function getDisapprovedPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['disapproved_pages'] = $this->admin->disapprovedPages();
		$this->layout->content = View::make('admin.pages.disapproved')->with($data);
	}

	public function getBlockedPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['blocked_pages'] = $this->admin->blockedPages();
		$this->layout->content = View::make('admin.pages.blocked')->with($data);
	}

	public function getFlaggedByMembersPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['flagged_pages_by_members'] = $this->admin->flaggedPagesByMembers();
		$this->layout->content = View::make('admin.pages.flagged_by_members')->with($data);
	}

	public function getFlaggedByNonMembersPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['flagged_pages_by_non_members'] = $this->admin->flaggedPagesByNonMembers();
		$this->layout->content = View::make('admin.pages.flagged_by_non_members')->with($data);
	}

	public function getDeactivatedPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['deactivated_pages'] = $this->admin->deactivatedPages();
		$this->layout->content = View::make('admin.pages.deactivated')->with($data);
	}

	public function getDeletedPages()
	{
		$this->layout->title = Lang::get('admin.manage_members');
		$this->layout->inverse = true;
		$this->layout->js = array('bootstrap-dialog.js', 'manage_pages.js');
		$data['deleted_pages'] = $this->admin->deletedPages();
		$this->layout->content = View::make('admin.pages.deleted')->with($data);
	}

	public function postManagePages()
	{
		return $this->admin->managePages();
	}

	public function postManageMembers()
	{
		return $this->admin->manageMembers();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function postCountriesExec()
	{
		return $this->admin->addCountries();
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function postCategoriesExec()
	{
		return $this->admin->addCategory();
	}

	/**
	* write the countries, province and cities to a js file
	*/
	public function getCompileCountriesProvinceCities()
	{
		return $this->admin->compileCountries();
	}

	/**
	* write the categories and sub-categories to a js file
	*/
	public function getCompileCategories()
	{
		return $this->admin->compileCategories();
	}

	/**
	* delete category
	*/
	public function postDeleteCategory()
	{
		return $this->admin->deleteCategory();
	}

	/**
	* delete sub-category
	*/
	public function postDeleteSubCategory()
	{
		return $this->admin->deleteSubCategory();
	}

	/**
	* delete country
	*/
	public function postDeleteCountry()
	{
		return $this->admin->deleteCountry();
	}

	/**
	* delete country
	*/
	public function postDeleteProvince()
	{
		return $this->admin->deleteProvince();
	}

	/**
	* delete country
	*/
	public function postDeleteCity()
	{
		return $this->admin->deleteCity();
	}

	public function getFactual()
	{
		$this->layout->title = Lang::get('admin.get_factual_data');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array();
		$data['categories'] = $this->admin->getFactualCategories();		
		$this->layout->content = View::make('admin.factual')->with($data);
	}

	public function postFactual()
	{
		return $this->admin->factual();
	}

	public function getBestbuyapi()
	{
		return $this->admin->bestbuyapi();
	}

	public function getYoutube()
	{
		$this->layout->title = Lang::get('admin.youtube');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('admin_videos.js');		
		$data['youtube'] = $this->admin->getYoutube();		
		$this->layout->content = View::make('admin.youtube')->with($data);
	}

	public function getIframeYoutube()
	{
		$this->layout->title = Lang::get('admin.iframe_youtube');
		$this->layout->inverse = true;
		$this->layout->js_var = array();
		$this->layout->js = array('admin_videos.js');
		$data['youtube'] = $this->admin->getIframeYoutube();
		$this->layout->content = View::make('admin.iframe_youtube')->with($data);
	}

	public function postUpdateIframeVideo()
	{
		return $this->admin->updateIframeVideo();
	}

	public function getUpdatePageGoogleAds()
	{
		return $this->admin->updatePageGoogleAds();
	}

	public function getUpdateVideosFacebookShares()
	{
		return $this->admin->updateVideosFacebookShares();
	}

	public function getBloggerAutoPost()
	{
		return $this->admin->bloggerAutoPost();
	}

	public function getParseHtml()
	{
		return $this->admin->parseHtml();
	}

	public function getFactualGenerateExcel()
	{
		return $this->admin->factualGenerateExcel();
	}
}