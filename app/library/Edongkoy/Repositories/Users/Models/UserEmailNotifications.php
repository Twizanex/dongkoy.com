<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserEmailNotifications.php

class UserEmailNotifications extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'user_email_notification_settings';
}