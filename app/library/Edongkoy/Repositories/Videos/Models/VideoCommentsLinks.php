<?php namespace Edongkoy\Repositories\Videos\Models;

# app/library/Edongkoy/Repositories/Videos/Models/VideoCommentsLinks.php

class VideoCommentsLinks extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'video_comments_links';
	protected $softDelete = true;
}