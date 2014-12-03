<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserSocialNetworks.php

class UserSocialNetworks extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_social_networks';

	public function facebookVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_social_networks_id', 'facebook');
	}

	public function twitterVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_social_networks_id', 'twitter');
	}

	public function googlePlusVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_social_networks_id', 'google_plus');
	}

	public function instagramVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_social_networks_id', 'instagram');
	}

	public function youtubeVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_social_networks_id', 'youtube');
	}
}