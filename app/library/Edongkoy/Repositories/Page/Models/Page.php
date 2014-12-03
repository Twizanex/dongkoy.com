<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/Page.php

class Page extends \Eloquent {
   
    protected $guarded = array('id');
    protected $softDelete = true;
    protected $table = 'pages';

    public function username()
    {
    	return $this->hasOne('Edongkoy\Repositories\Users\Models\Usernames', 'user_id')
                    ->where('user_type', '=', 'page');
    }

    public function province()
    {
    	return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Province');
    }

    public function country()
    {
    	return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Countries');
    }

    public function city()
    {
        return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Cities');
    }

    public function category()
    {
        return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Categories', 'category_id');
    }

    public function subCategory()
    {
    	return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Subcategories', 'sub_category_id');
    }

    public function profileImage()
    {
        return $this->hasOne('Edongkoy\Repositories\Image\Models\ProfileImages', 'user_id')->where('user_type', 'page');
    }

    public function coverImage()
    {
        return $this->hasOne('Edongkoy\Repositories\Image\Models\CoverImages', 'user_id')->where('user_type', 'page');
    }

    public function status()
    {
        return $this->belongsToMany('Edongkoy\Repositories\Page\Models\Status', 'page_status');
    }

    public function map()
    {
        return $this->hasOne('Edongkoy\Repositories\Page\Models\PageMap', 'page_id');
    }

    public function schedule()
    {
        return $this->hasOne('Edongkoy\Repositories\Page\Models\PageSchedule', 'page_id');
    }

    public function owner()
    {
        return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
    }
}