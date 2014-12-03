<?php namespace Edongkoy\Repositories\Image\Models;

class CoverImages extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'cover_images';


	public function image()
	{
		return $this->belongsTo('Edongkoy\Repositories\Image\Models\Images', 'image_id');
	}
}