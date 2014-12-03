<?php namespace Edongkoy\Repositories\Videos\Models;

# app/library/Edongkoy/Repositories/Videos/Models/VideoCategories.php

class VideoCategories extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'video_categories';
	protected $softDelete = true;
}