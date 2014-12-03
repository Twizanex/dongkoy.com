<?php

use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class BaseController extends Controller {

	/*
	* The layout that should be use for response
	*/

	protected $layout = 'layouts.master';
    protected $topAlertConfirmEmail = false;
    protected $useOtherEmailModal = false;
    protected $global;
    protected $metaDesc = '';
    protected $robots = 'index, follow';
    protected $totalPages;
    protected $totalMembers;
    protected $totalYoutube;
    protected $totalIframeYoutube;
    protected $ogImage = '';    

    /**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if (unConfirmedEmail())
        {
            $this->topAlertConfirmEmail = true;
            $this->useOtherEmailModal = true;
        }

        if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout)
				->with(array(
						'inverse'                 => false,
                        'topAlertDanger'          => false,
                        'modalAlertMessage'       => false,
                        'topAlertInfo'            => false,
                        'modalConfirmMessage'     => false,
                        'modal'                   => false,
                        'css'                     => array(),
                        'js_var'                  => array(),
                        'js'                      => array(),
                        'useOtherEmailModal'      => $this->useOtherEmailModal,
                        'topAlertConfirmEmail'    => $this->topAlertConfirmEmail,
						'currentRouteName'        => Route::currentRouteName(),
                        'showGoogleMap'           => false,
                        'friendRequest'           => $this->global->friendRequest(),
                        'userNotifications'       => $this->global->userNotifications(),
                        'newMessage'              => $this->global->newMessage(),
                        'metaDesc'                => $this->metaDesc,
                        'robots'                  => $this->robots,
                        'google_ads'              => false,
                        'total_pages'             => $this->totalPages,
                        'total_members'           => $this->totalMembers,
                        'ogImage'                 => $this->ogImage,
                        'totalYoutube'            => $this->totalYoutube,
                        'totalIframeYoutube'      => $this->totalIframeYoutube,
                        'bodyClass'               => ''
					)
				);
		}
	}

	/**
	* autosuggest
	*
	* @return json
	*/
    public function autosuggest()
    {
    	if(Request::ajax())
    	{
    		$query_string = Input::get('search');
    		$id = Input::get('id');
    		$action = Input::get('action');
    		$ok = false;
    		
    		if (!empty($query_string))
    		{
    			if($action == 'suggest-province')
    			{
	    			$data = Province::select(DB::raw('id, name'))
	    						->where('country_id', $id)
	    						->where('name', 'like', '%'.$query_string.'%')
	    						->take(10)
	    						->get()->toArray();
    			}
    			elseif ($action == 'suggest-cities')
    			{
    				$data = Cities::select(DB::raw('id, name'))
	    						->where('province_id', $id)
	    						->where('name', 'like', '%'.$query_string.'%')
	    						->take(10)
	    						->get()->toArray();
    			}

    			if (count($data) > 0)
    			{
    				$status = true;
    			}
    		}
    		else
    		{
    			if ($action == 'suggest-province')
    			{
    				$data = Helper::array_province($id, true);
    				$ok = ! $data ? false : true;
    			}
    			else if ($action == 'suggest-cities')
    			{
    				$data = Helper::array_cities($id, true);
    				$ok = ! $data ? false : true;
    			}
    		}

    		if ($ok)
    		{
    			return status_ok(array('mylist'  => $data));
    		}
    		else
    		{
    			return status_error(array('message' => Lang::get('global.server_error')));
    		}
    						
    	}

    	return Redirect::to('/');
    }
}