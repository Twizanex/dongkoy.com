<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/User.php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends \Eloquent implements UserInterface, RemindableInterface {

	protected $guarded = array('id');
	protected $softDelete = true;
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password');

	/**
	 * Get the unique identifier for the user.
	 *
	 * @return mixed
	 */
	public function getAuthIdentifier()
	{
		return $this->getKey();
	}

	/**
	 * Get the password for the user.
	 *
	 * @return string
	 */
	public function getAuthPassword()
	{
		return $this->password;
	}

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail()
	{
		return $this->email;
	}

	public function username()
	{
		return $this->hasOne('Edongkoy\Repositories\Users\Models\Usernames', 'user_id')
					->withTrashed()
					->where('user_type', 'user');
	}

	public function albums()
	{
		return $this->hasMany('Edongkoy\Repositories\Image\Models\Albums', 'user_id');
	}

	public function userFriends()
	{
		return $this->hasMany('Edongkoy\Repositories\Users\Models\UserFriends', 'user_id');
	}

	public function profileImage()
	{
		return $this->hasOne('Edongkoy\Repositories\Image\Models\ProfileImages', 'user_id')->where('user_type', 'user');
	}

	public function coverImage()
	{
		return $this->hasOne('Edongkoy\Repositories\Image\Models\CoverImages', 'user_id')->where('user_type', 'user');
	}

	public function socialIds()
	{
		return $this->hasOne('Edongkoy\Repositories\Users\Models\UserSocialIds', 'user_id');
	}

	public function role()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Role');
	}

	public function status()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Status', 'user_status');
	}

	public function socialId()
	{
		return $this->hasOne('Edongkoy\Repositories\Users\Models\UserSocialIds');
	}

	public function occupation()
	{
		return $this->hasOne('Edongkoy\Repositories\Users\Models\UserOccupation');
	}

	public function infoVisibility()
	{
		return $this->hasOne('Edongkoy\Repositories\Users\Models\UserInfoVisibility');
	}

	public function conversations()
	{
		return $this->hasMany('Edongkoy\Repositories\Users\Models\UserConversation');
	}

	public function pages()
	{
		return $this->hasMany('Edongkoy\Repositories\Page\Models\Page');
	}

	public function activities()
	{
		return $this->hasMany('Edongkoy\Repositories\Users\Models\UserActivity')->orderBy('id', 'desc');
	}

	public function emailNotificationsSettings()
	{
		return $this->hasOne('Edongkoy\Repositories\Users\Models\UserEmailNotifications');
	}

}