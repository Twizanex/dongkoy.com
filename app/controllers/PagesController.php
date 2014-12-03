<?php

use Edongkoy\Repositories\Page\PageRepositoryInterface as page;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class PagesController extends BaseController {

    protected $page;

    public function __construct(page $page, globals $global)
    {
        $this->beforeFilter('auth', array('only' => array('create', 'store', 'update')));
        $this->page = $page;
        $this->global = $global;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $data['pages'] = $this->page->getAllPages();

        if ($data['pages']['currentPage'] != 1)
        {
            $this->layout->robots = 'noindex, follow';
        }

        $this->layout->categories = $this->global->getPageCategories();
        $this->layout->title = Lang::get('pages.pages');
        $this->layout->content = View::make('pages.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        
        if (Input::has('category'))
        {
            $rules = array('category' => 'numeric|exists:categories,id');
            $validation = Validator::make(Input::all(), $rules);

            if ($validation->fails())
            {
                return Redirect::action('PagesController@create');
            }
        }
        $this->layout->title = Lang::get('pages.create_page');        
        $this->layout->js_var = array('province_or_state_label' => Lang::get('pages.province_state'), 'city_label' => Lang::get('pages.city_or_municipality'), 'choose_a_province_or_state' => Lang::get('pages.choose_a_province'), 'choose_a_city_or_municipality' => Lang::get('pages.choose_a_city'), 'choose_a_sub_category' => Lang::get('pages.choose_a_sub_category'));
        $this->layout->js = array('jquery.autosize-min.js', 'categories.js', 'countries.js', 'create_page.js');

        $data['array_province'] = array_province(Input::old('country_id'));
        $data['array_cities'] = array_cities(Input::old('province_id'));
        $data['all_categories'] = $this->page->getAllCategories();

        $this->layout->content = View::make('pages.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        return $this->page->create();
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $category_name
     * @return Response
     */
    public function show($category_name)
    {
        $valid = $this->global->getPageCategories();
        $sub_cat_id = Input::get('subcat');
        $city_id = Input::get('city');
        
        if (!in_array($category_name, $valid['slugs']))
        {
            return Redirect::action('PagesController@index');
        }
         
        $cat_id = $valid['info'][$category_name]['id'];
        $data['header_text1'] = $valid['info'][$category_name]['name'];
        $data['header_url1'] = action('PagesController@show', $category_name);
        $data['header_text2'] = '';
        $data['header_url2'] = '';

        if ($sub_cat_id == '' && $city_id == '')
        {
            $data['pages'] = $this->page->getAllPagesByCatId($cat_id);            
        }
        elseif ($sub_cat_id != '' && $city_id == '')
        {
            $data['pages'] = $this->page->getAllPagesBySubCatId($sub_cat_id);

            if (!$data['pages']) return Redirect::action('PagesController@index');

            $data['header_text2'] = $data['pages']['sub_cat_name'];
            $data['header_url2'] = action('PagesController@show', array($category_name, 'subcat' => $sub_cat_id));
        }
        elseif ($sub_cat_id != '' && $city_id != '')
        {
            $data['pages'] = $this->page->getAllPagesBySubCatIdAndCityId($sub_cat_id, $city_id);

            if (!$data['pages']) return Redirect::action('PagesController@index');

            $data['header_text2'] = $data['pages']['header_text'];
            $data['header_url2'] = action('PagesController@show', array($category_name, 'subcat' => $sub_cat_id, 'city' => $city_id));
            $places = '';
            $ctr = 1;
            $per_page = $data['pages']['per_page'];

            foreach ($data['pages']['data'] as $value)
            {
                if ($ctr != $per_page)
                {
                    $places .= $value['page_name'].', ';
                }
                else
                {
                    $places .= $value['page_name'].' '.Lang::get('global.and_more').'.';
                }
                $ctr++;
            }

            $this->layout->metaDesc = Lang::get('global.site_name').' '.Lang::get('global.recommendations_for').' '.$data['pages']['sub_cat_name'].' '.Lang::get('global.in').' '.$data['pages']['city_name'].'. '.Lang::get('global.places_like').' '.$places;
        }
        elseif ($sub_cat_id == '' && $city_id != '')
        {
            $data['pages'] = $this->page->getAllPagesByCatIdAndCityId($cat_id, $city_id);

            if (!$data['pages']) return Redirect::action('PagesController@index');

            $data['header_text1'] = $data['header_text1'].' '.Lang::get('global.in').' '.$data['pages']['city_name'];
            $data['header_url1'] = action('PagesController@show', array($category_name, 'city' => $city_id));
            $places = '';
            $ctr = 1;
            $per_page = $data['pages']['per_page'];

            foreach ($data['pages']['data'] as $value)
            {
                if ($ctr != $per_page)
                {
                    $places .= $value['page_name'].', ';
                }
                else
                {
                    $places .= $value['page_name'].' '.Lang::get('global.and_more').'.';
                }
                $ctr++;
            }

            $this->layout->metaDesc = Lang::get('global.site_name').' '.Lang::get('global.recommendations_for').' '.$category_name.' '.Lang::get('global.in').' '.$data['pages']['city_name'].'. '.Lang::get('global.places_like').' '.$places;
        }
        else
        {
            return Redirect::action('PagesController@index');
        }

        if ($data['pages']['currentPage'] != 1)
        {
            $this->layout->robots = 'noindex, follow';
        }
        
        $this->layout->category_name = $data['header_text1'];
        $this->layout->category_url = $data['header_url1'];
        $this->layout->sub_category_name = $data['header_text2'];
        $this->layout->sub_category_url = $data['header_url2'];
        $this->layout->google_ads = $cat_id != 4 ? true : false;
        $this->layout->categories = $this->global->getPageCategories($city_id);

        //$queries = DB::getQueryLog();
        //echo '<pre>';
        //print_r($queries);
        //dd();
        
        $this->layout->title = $data['header_text2'] == '' ? $data['header_text1'] : $data['header_text2'];
        $this->layout->content = View::make('pages.category')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        return $this->page->update($id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

}