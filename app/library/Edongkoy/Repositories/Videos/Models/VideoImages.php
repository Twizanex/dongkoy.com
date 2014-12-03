<?php namespace Edongkoy\Repositories\Videos\Models;

# app/library/Edongkoy/Repositories/Videos/Models/VideoImages.php

class VideoImages extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'video_images';
	protected $softDelete = true;
}