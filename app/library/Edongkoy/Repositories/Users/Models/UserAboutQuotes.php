<?php namespace Edongkoy\Repositories\Users\Models;

# app\library\Edongkoy\Repositories\Users\Models\UserAboutQuotes.php

class UserAboutQuotes extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_about_quotations';

	public function aboutVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_about_quotes_id', 'about');
	}

	public function quotesVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_about_quotes_id', 'quotes');
	}
}