<?php namespace Edongkoy\Repositories\Image\Models;

class Images extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table   = 'images';

	public function album()
	{
		return $this->belongsTo('Edongkoy\Repositories\Image\Models\Albums', 'album_id');
	}
}