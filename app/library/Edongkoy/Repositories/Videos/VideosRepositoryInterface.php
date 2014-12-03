<?php namespace Edongkoy\Repositories\Videos;

# app/library/Edongkoy/Repositories/Videos/VideosRepositoryInterface.php

interface VideosRepositoryInterface {
	
	public function preview();
	public function add();
	public function search();
	public function unsetSessions();
	public function getVideoInfo($id);
	public function getSuggestedVideos($category_id, $location);
	public function updateVideos();
	public function getIframeVideos();
	public function getVideosByCategory($id);
}