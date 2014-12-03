<?php namespace Edongkoy\Repositories\Users\Models;

# app\library\Edongkoy\Repositories\Users\Models\UserContactInfo.php

class UserContactInfo extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_contact_info';

	public function emailVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_contact_info_id', 'email');
	}

	public function mobileVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_contact_info_id', 'mobile_phone');
	}

	public function landlineVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_contact_info_id', 'landline');
	}

	public function addressVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_contact_info_id', 'address');
	}

	public function websiteVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_contact_info_id', 'website');
	}
}