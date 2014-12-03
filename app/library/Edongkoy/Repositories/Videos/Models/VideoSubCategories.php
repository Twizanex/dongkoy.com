<?php namespace Edongkoy\Repositories\Videos\Models;

# app/library/Edongkoy/Repositories/Videos/Models/VideoSubCategories.php

class VideoSubCategories extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'video_sub_categories';
	protected $softDelete = true;
}