<?php namespace Edongkoy\Repositories\Videos;

# app/library/Repositories/Videos/EloquentVideosRepository.php

use Edongkoy\Repositories\Videos\Models\Videos;
use Edongkoy\Repositories\Videos\Models\VideoImages;
use Edongkoy\Repositories\Videos\Models\VideoCategories;
use Edongkoy\Repositories\Videos\Models\VideoSubCategories;
use Edongkoy\Repositories\Videos\Models\VideoCommentsLinks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class EloquentVideosRepository implements VideosRepositoryInterface {

	public function preview()
	{
		$this->unsetSessions();

		//$url = 'https://www.youtube.com/watch?v=uaWA2GbcnJU';
		$url = Input::get('url');
		parse_str(parse_url($url, PHP_URL_QUERY), $array);

		if (!isset($array['v'])) return status_error(array('message' => Lang::get('videos.invalid_youtube_video')));

		$headers = get_headers('http://gdata.youtube.com/feeds/api/videos/'.$array['v']);
		$pos = strpos($headers[0], '200');

		if ($pos === false) return status_error(array('message' => Lang::get('videos.invalid_youtube_video')));
		
		// check if the video has alredy been shared
		$query = Videos::where('video_id', $array['v'])->first();

		if ($query) return status_error(array('message' => Lang::get('videos.video_has_already_been_shared')));

		$content = file_get_contents('http://gdata.youtube.com/feeds/api/videos/'.$array['v'].'?v=2&alt=json&prettyprint=true');
		$json = json_decode($content, true);

		//echo '<pre>';
		//print_r($json);
		//dd();

		$author = file_get_contents($json['entry']['author'][0]['uri']['$t'].'?v=2&alt=json&prettyprint=true');
		$json_author = json_decode($author, true);

		$author_id = $json_author['entry']['yt$userId']['$t'];
		$author_location = $json_author['entry']['yt$location']['$t']; 

		//echo $author_id.'('.strlen($author_id).')<br/>';
		//echo $author_location;
		//dd();
		
		/*echo 'Title: '.$json['entry']['title']['$t'].'<br/>';
		echo 'Description: '.$json['entry']['media$group']['media$description']['$t'].'<br/>';
		echo 'Category Name: '.$json['entry']['media$group']['media$category'][0]['$t'].'<br/>';
		echo 'Category Label: '.$json['entry']['media$group']['media$category'][0]['label'].'<br/>';
		echo 'Duration: '.$json['entry']['media$group']['media$content'][0]['duration'].'<br/>';
		echo 'Comment Link: '.$json['entry']['gd$comments']['gd$feedLink']['href'].'<br/>';
		echo 'Total Comment: '.$json['entry']['gd$comments']['gd$feedLink']['countHint'].'<br/>';
		echo 'Total Views: '.$json['entry']['yt$statistics']['viewCount'].'<br/>';
		*/
		/*$thumbnails = array();

		foreach ($json['entry']['media$group']['media$thumbnail'] as $key => $value)
		{
			$thumbnails[$value['yt$name']] = $value['url'].'?'.$value['width'].'x'.$value['height'];					
		}*/	

		$description = $json['entry']['media$group']['media$description']['$t'];
		$desc_length = strlen($description);
		$description = $desc_length > 155 ? mb_substr($description, 0, 155).'...' : $description;

		Session::push('video_id', $array['v']);
		Session::push('video_title', $json['entry']['title']['$t']);
		Session::push('video_category', $json['entry']['media$group']['media$category'][0]['$t']);
		Session::push('video_sub_category', $json['entry']['media$group']['media$category'][0]['label']);
		Session::push('video_duration', $json['entry']['media$group']['media$content'][0]['duration']);

		if ($json['entry']['yt$accessControl'][0]['permission'] == 'allowed')
		{
			Session::push('video_comment_link', $json['entry']['gd$comments']['gd$feedLink']['href']);
			Session::push('video_total_comments', $json['entry']['gd$comments']['gd$feedLink']['countHint']);
		}

		Session::push('video_total_views', $json['entry']['yt$statistics']['viewCount']);
		Session::push('video_description', $json['entry']['media$group']['media$description']['$t']);
		Session::push('video_author_id', $author_id);
		Session::push('video_author_location', $author_location);		
		
		return status_ok(array(
			'description' => $description,
			'title' => $json['entry']['title']['$t'],
			'img' => $json['entry']['media$group']['media$thumbnail'][1]['url'],
			'descLength' => $desc_length,
			'duration' => formatTime($json['entry']['media$group']['media$content'][0]['duration']),
			'author_id' => $author_id,
			'author_location' => $author_location
		));		
	}

	public function add()
	{
		if (
			Session::has('video_id') &&
			Session::has('video_title') &&
			Session::has('video_description') &&
			Session::has('video_category') && 
			Session::has('video_sub_category') &&
			Session::has('video_duration') &&
			Session::has('video_total_views')
		)
		{
			//echo '<pre>';
			//print_r(Session::get('video_thumbnails')[0]); dd();
			// check if category already exists
			$query = VideoCategories::where('name', Session::get('video_category')[0])->first();

			if ($query)
			{
				$category_id = $query->id;
			}
			else
			{
				$query = VideoCategories::create(array(
					'name' => Session::get('video_category')[0]
				));
				$category_id = $query->id;
			}

			//check if subcategory already exists
			$query = VideoSubCategories::where('name', Session::get('video_sub_category')[0])->first();

			if ($query)
			{
				$sub_category_id = $query->id;
			}
			else
			{
				$query = VideoSubCategories::create(array(
					'name' => Session::get('video_sub_category')[0],
					'category_id' => $category_id
				));
				$sub_category_id = $query->id;
			}

			$user_logged_id = Auth::user()->id;
			$title = Input::get('title');
			$desc = Input::get('description');

			$description = $desc != '' ? $desc : Session::get('video_description')[0];
			$title = $title != '' ? $title : Session::get('video_title')[0];

			// insert video
			$yt_video_id = Session::get('video_id')[0];
			$query = Videos::create(array(
				'video_id' => $yt_video_id,
				'title' => $title,
				'description' => $description,
				'duration' => Session::get('video_duration')[0],
				'category_id' => $category_id,
				'sub_category_id' => $sub_category_id,
				'yt_user_id' => Session::get('video_author_id')[0],
				'yt_location' => Session::get('video_author_location')[0],
				'user_id' => $user_logged_id,
				'total_views' => Session::get('video_total_views')[0],
				'type' => 'youtube'
			));

			$video_id = $query->id;

			// insert images
			//$thumbnails = array_add(Session::get('video_thumbnails')[0], 'video_id', $video_id);
			//$query = VideoImages::create($thumbnails);

			// insert comment links
			if (Session::has('video_comment_link'))
			{
				$query = VideoCommentsLinks::create(array(
					'video_id' => $video_id,
					'link' => Session::get('video_comment_link')[0],
					'total_comments' => Session::get('video_total_comments')[0],
					'type' => 'youtube'
				));
			}

			$this->unsetSessions();

			return status_ok(array('url' => URL::route('watch').'?v='.$yt_video_id));
		}

		return status_error(array('message' => Lang::get('videos.invalid_youtube_video')));
	}

	public function updateVideos()
	{
		$query = Videos::all();

		foreach ($query as $key => $value)
		{
			$content = file_get_contents('http://gdata.youtube.com/feeds/api/videos/'.$value['video_id'].'?v=2&alt=json&prettyprint=true');
			$json = json_decode($content, true);

			//echo '<pre>';
			//print_r($json);
			//dd();

			$author = file_get_contents($json['entry']['author'][0]['uri']['$t'].'?v=2&alt=json&prettyprint=true');
			$json_author = json_decode($author, true);

			$author_id = $json_author['entry']['yt$userId']['$t'];
			$author_location = $json_author['entry']['yt$location']['$t'];

			$video = Videos::find($value->id);
			$video->yt_user_id = $author_id;
			$video->yt_location = $author_location;
			$video->save();

		}

		return status_ok();
	}

	public function unsetSessions()
	{
		Session::forget('video_id');
		Session::forget('video_title');
		Session::forget('video_category');
		Session::forget('video_sub_category');
		Session::forget('video_duration');
		Session::forget('video_comment_link');
		Session::forget('video_total_comments');
		Session::forget('video_total_views');
		Session::forget('video_description');
		Session::forget('video_author_id');
		Session::forget('video_author_location');
	}

	public function search()
	{
		require_once 'Google/Client.php';
		require_once 'Google/Service/YouTube.php';

		  /*
		   * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
		   * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
		   * Please ensure that you have enabled the YouTube Data API for your project.
		   */
		  $DEVELOPER_KEY = 'AIzaSyCwbPYiky8MOIh1gM0G39-3Yytjh1OLp6c';

		  $client = new \Google_Client();
		  $client->setDeveloperKey($DEVELOPER_KEY);

		  // Define an object that will be used to make all API requests.
		  $youtube = new \Google_Service_YouTube($client);

		  try {
			    // Call the search.list method to retrieve results matching the specified
			    // query term.
			    $searchResponse = $youtube->search->listSearch('id,snippet', array(
			      'q' => 'laravel',
			      'maxResults' => 10,
			    ));

			    $videos = '';
			    $channels = '';
			    $playlists = '';

			    // Add each result to the appropriate list, and then display the lists of
			    // matching videos, channels, and playlists.
			    foreach ($searchResponse['items'] as $searchResult) {
			      switch ($searchResult['id']['kind']) {
			        case 'youtube#video':
			          $videos .= sprintf('<li>%s (%s)</li>',
			              $searchResult['snippet']['title'], $searchResult['id']['videoId']);
			          break;
			        case 'youtube#channel':
			          $channels .= sprintf('<li>%s (%s)</li>',
			              $searchResult['snippet']['title'], $searchResult['id']['channelId']);
			          break;
			        case 'youtube#playlist':
			          $playlists .= sprintf('<li>%s (%s)</li>',
			              $searchResult['snippet']['title'], $searchResult['id']['playlistId']);
			          break;
			      }
			    }

			    echo '<pre>';
			    print_r($searchResponse['items']);
			    dd();
			    
			  } catch (Google_ServiceException $e) {
			    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
			      htmlspecialchars($e->getMessage()));
			  } catch (Google_Exception $e) {
			    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
			      htmlspecialchars($e->getMessage()));
			  }
			
	}

	public function getVideoInfo($id)
	{
		$query = Videos::where('video_id', $id)->first();

		if (!$query) return false;

		return array(
			'title' => $query->title,
			'description' => $query->description,
			'total_views' => number_format($query->total_views).' '.Lang::get('videos.views'),
			'category_id' => $query->category_id,
			'location' => $query->yt_location,
			'sharer' => userFullName($query->user),
			'sharer_url' => profileUrl($query->user->username->username),
			'sharer_image' => profileImage($query->user),
			'date' => time_ago($query->created_at),
			'google_ads' => $query->google_ads,
			'start' => $id == 'jZTCtpZyulM' ? 'start=72&' : '',
			'fb_share_url' => 'https://www.facebook.com/sharer.php?app_id='.Config::get('app.facebook.appId').'&u='.URL::route('watch').'?v='.$id
		);
	}

	public function getSuggestedVideos($category_id, $location)
	{
		/*$query = Videos::where('category_id', $category_id)
						->where('yt_location', $location)
						->paginate(20);*/

		$query = Videos::where('iframe', 1)
					->orderBy('id', 'desc')
					->get();

		$data = array();

		foreach ($query as $key => $value)
		{
			$title = $value->title;
			$video_id = $value->video_id;
			$data[$key]['title'] = strlen($title) > 68 ? mb_substr($title, 0, 68).'...' : $title;
			$data[$key]['video_id'] = $video_id;
			$data[$key]['duration'] = formatTime($value->duration);
			$data[$key]['url'] = URL::route('watch').'?v='.$video_id;
			$data[$key]['sharer'] = Lang::get('videos.shared_by').' '.userFullName($value->user);
			$data[$key]['views'] = number_format($value->total_views).' '.Lang::get('videos.views');
		}

		return $data;
	}

	public function getVideosByCategory($id)
	{
		$query = Videos::where('category_id', $id)
						//->where('yt_location', $location)
						->paginate(4);

		$data = array();

		foreach ($query as $key => $value)
		{
			$title = $value->title;
			$id = $value->id;
			$video_id = $value->video_id;
			$data[$key]['title'] = strlen($title) > 68 ? mb_substr($title, 0, 68).'...' : $title;
			$data[$key]['video_id'] = $video_id;
			$data[$key]['duration'] = formatTime($value->duration);
			$data[$key]['url'] = URL::route('watch').'?v='.$video_id;
			$data[$key]['img_url'] = '//i1.ytimg.com/vi/'.$video_id.'/mqdefault.jpg';
			$data[$key]['sharer'] = Lang::get('videos.shared_by').' '.userFullName($value->user);
			$data[$key]['views'] = number_format($value->total_views).' '.Lang::get('videos.views');
		}

		return $data;
	}

	public function getIframeVideos()
	{
		
		$query = Videos::where('iframe', 1)
					->orderBy('id', 'desc')
					->get();					

		$data = array();

		foreach ($query as $key => $value)
		{
			$title = $value->title;
			$data[$key]['title'] = strlen($title) > 68 ? mb_substr($title, 0, 68).'...' : $title;
			$data[$key]['video_id'] = $value->video_id;
			$data[$key]['duration'] = formatTime($value->duration);
			$data[$key]['sharer'] = Lang::get('videos.shared_by').' '.userFullName($value->user);
			$data[$key]['views'] = number_format($value->total_views).' '.Lang::get('videos.views');
		}

		return $data;
	}
}