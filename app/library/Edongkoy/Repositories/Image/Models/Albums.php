<?php namespace Edongkoy\Repositories\Image\Models;

# app/library/Edongkoy/Repositories/Image/Models/Albums.php

class Albums extends \Eloquent {

	protected $guarded = array('id');
	protected $table   = 'albums';

	public function images()
	{
		return $this->hasMany('Edongkoy\Repositories\Image\Models\Images', 'album_id')->orderBy('id', 'desc');
	}

	public function albumCover()
	{
		return $this->hasOne('Edongkoy\Repositories\Image\Models\Images', 'album_id')->where('album_cover', 1);
	}

	public function randomAlbumCover()
	{
		return $this->hasOne('Edongkoy\Repositories\Image\Models\Images', 'album_id')->orderBy('id', 'desc');
	}
}