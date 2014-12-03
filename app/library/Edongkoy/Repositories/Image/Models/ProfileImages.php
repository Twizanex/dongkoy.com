<?php namespace Edongkoy\Repositories\Image\Models;

class ProfileImages extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'profile_images';

	public function profileImage()
	{
		return $this->morphTo();
	}

	public function image()
	{
		return $this->belongsTo('Edongkoy\Repositories\Image\Models\Images', 'image_id');
	}
}