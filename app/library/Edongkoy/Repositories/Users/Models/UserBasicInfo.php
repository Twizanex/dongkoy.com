<?php namespace Edongkoy\Repositories\Users\Models;

# app\library\Edongkoy\Repositories\Models\UserBasicInfo.php

class UserBasicInfo extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_basic_info';

	public function genderVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_basic_info_id', 'gender');
	}

	public function birthdayVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_basic_info_id', 'birthday');
	}

	public function interestedInVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_basic_info_id', 'interested_in');
	}

	public function relationshipStatusVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_basic_info_id', 'relationship_status');
	}

	public function languagesVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_basic_info_id', 'languages');
	}

	public function religionVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_basic_info_id', 'religion');
	}
}