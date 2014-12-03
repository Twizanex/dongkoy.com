<?php

use Edongkoy\Repositories\Videos\VideosRepositoryInterface as videos;
use Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface as globals;

class VideosController extends BaseController {

	protected $video;

	public function __construct(videos $video, globals $global)
	{
		$this->beforeFilter('auth', array(
			'only' => array(
				'getAdd',
				'postPreview',
				'postAdd'
			)
		));

		$this->beforeFilter('admin', array(
			'only' => array(
				'getUpdateVideos'
			)
		));

		$this->beforeFilter('ajax', array(
			'only' => array(
				'postPreview',
				'postAdd'
			)
		));
		$this->global = $global;
		$this->video = $video;
	}

	public function getIndex()
	{
		$this->layout->title = "Videos";
		$this->layout->metaDesc = "Videos";
		$this->layout->bodyClass = 'videos-home';
		$data['music'] = $this->video->getVideosByCategory(2);
		$data['film'] = $this->video->getVideosByCategory(4);
		$this->layout->content = View::make('videos.index')->with($data);
	}

	public function getAdd()
	{
		$this->video->unsetSessions();
		$this->layout->js = array('jquery.autosize-min.js', 'add_video.js');
		$this->layout->title = 'Add a video';
		$this->layout->content = View::make('videos.add');
	}

	public function getIframe()
	{
		$this->layout = View::make('layouts.iframe');
		$this->layout->title = '';
		$data['suggestedVideos'] = $this->video->getIframeVideos();
		$this->layout->content = View::make('videos.iframe')->with($data);
	}

	public function postPreview()
	{
		return $this->video->preview();
	}

	public function postAdd()
	{
		return $this->video->add();
	}

	public function getUpdateVideos()
	{
		return $this->video->updateVideos();
	}
}