<?php namespace Edongkoy\Repositories\Image;

# app/library/Edongkoy/Repositories/Image/EloquentImageRepository.php

use Edongkoy\Repositories\Page\Models\Page;
use Edongkoy\Repositories\Image\Models\Albums;
use Edongkoy\Repositories\Image\Models\Images;
use Edongkoy\Repositories\Image\Models\CoverImages;
use Edongkoy\Repositories\Image\Models\ProfileImages;
use Edongkoy\Repositories\Users\Models\Usernames;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class EloquentImageRepository implements ImageRepositoryInterface {

	public function owner($user_id, $user_type)
	{
		$user_logged_id = Auth::user()->id;

		if ($user_type == 'user')
		{
			return $user_id == $user_logged_id ? true : false;
		}
		elseif ($user_type == 'page')
		{
			$page = Page::find($user_id);

			if ($page) return $page->user_id == $user_logged_id ? true : false; 
		}

		return false;
	}

	public function userInfo($username)
	{
		return Usernames::where('username', $username)->first();
	}

	public function uploadProfilePhoto()
	{
		$validation = new \Services\Validators\Image;

		if ($validation->passes())
		{
			$user_id = Session::get('user_id');
			$user_type = Session::get('user_type');
			$username = Session::get('username');
			$file = Input::file('image');

			if (!empty($file) && $this->owner($user_id, $user_type))
			{
				$filename = $this->uploadImage($file, $username);
				$album_id = $this->getAlbumId($user_id, $user_type, 1, 'profile.profile_photos');

				if ($album_id)
				{
					$image_id = $this->insertImage($album_id, $filename);

					if ($image_id)
					{
						$profile_image = $this->insertProfileImage($user_id, $image_id, $user_type);

						if ($profile_image)
						{
							Session::put('profile_image', imageUrl($filename, $username, 'xxlarge'));
							return status_ok(array('url' => action('ModalsController@getCrop', array($album_id, $image_id, $username, $filename)), 'src' => imageUrl($filename, $username, 'large')));
						}
					}
				}			
			}
		}

		return status_error();
	}

	public function addPhotos()
	{
		$validation = new \Services\Validators\Image;

		if ($validation->passes())
		{
			$user_id = Session::get('user_id');
			$user_type = Session::get('user_type');
			$username = Session::get('username');
			$album_id = Input::get('album_id');
			
			if (!$this->owner($user_id, $user_type)) return status_error();
			if (!$this->albumOwner($album_id, $user_id)) return status_error();

			foreach (Input::file('image') as $key => $file)
			{
				$filename = $this->uploadImage($file, $username);
				$insert_image = $this->insertImage($album_id, $filename);
			}

			return status_ok();		
		}

		return status_error();
	}

	public function uploadCoverPhoto()
	{
		$validation = new \Services\Validators\Image;

		if ($validation->passes())
		{
			$user_id = Session::get('user_id');
			$user_type = Session::get('user_type');
			$username = Session::get('username');
			$file = Input::file('image');

			if (!empty($file) && $this->owner($user_id, $user_type))
			{
				$filename = $this->uploadImage($file, $username);
				$album_id = $this->getAlbumId($user_id, $user_type, 2, 'profile.cover_photos');

				if ($album_id)
				{
					$image_id = $this->insertImage($album_id, $filename);

					if ($image_id)
					{
						$cover_image = $this->insertCoverImage($user_id, $image_id, $user_type);

						if ($cover_image)
						{
							$image_url = imageUrl($filename, $username, 'xxlarge', true);
							return status_ok(array('src' => $image_url['url'], 'mt' => $image_url['mt']));
						}
					}
				}			
			}
		}

		return status_error();
	}

	public function userPhotos($username)
	{
		$user_info = $this->userInfo($username);
		$user_id = $user_info->user_id;
		$user_type = $user_info->user_type;
		$total = 0;
		$ctr = 0;
		$data = '<tr>';
		
		$query = Albums::where('user_id', $user_id)
						->where('user_type', $user_type)
						->get();

		if ($query)
		{
			foreach ($query as $i => $j)
			{
				if ($j->images->count())
				{
					$album_type = $j->album_type;
					$album_id = $j->id;

					foreach ($j->images as $k => $l)
					{
						$image_type = $l->type;
						$image_id = $l->id;
						$cover = $album_type == 2 && $image_type != 0 ? true : false;
						$carousel_url = URL::action('ModalsController@getCarousel', array($user_id, $user_type, $username, $image_id, $album_id));
						$data .= '<td width="100"><a href="'.$carousel_url.'" class="modal-link"><img src="'.imageUrl($l->filename, $username, 'large', $cover, $image_type).'" width="100%"></td>';
						$total++;
						$ctr++;
						
						if ($ctr == 3)
						{
							$data .= '</tr></tr>';
							$ctr = 0;
						}

						if ($total == 6) break;
					}
				}

				if ($total == 6) break;
			}
		}

		return array(
			'total' => $total,
			'data' => $data
		);
	}

	public function crop($album_id, $image_id, $filename, $size = 'large')
	{
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');
		$username = Session::get('username');
		
		if ($this->owner($user_id, $user_type))
		{
			$filename = $this->uploadImage(null, $username, $filename, 'crop');

			return status_ok(array('src' => imageUrl($filename, $username, $size), 'filename' => $filename));
		}

		return status_error();
	}

	public function getAlbumId($user_id, $user_type, $album_type, $album_name)
	{
		$query = Albums::select(DB::raw('id'))
					->where('user_id', $user_id)
					->where('user_type', $user_type)
					->where('album_type', $album_type)
					->first();

		if (!$query)
		{
			$query = Albums::create(array(
							'user_id' => $user_id,
							'user_type' => $user_type,
							'album_type' => $album_type,
							'name' => $album_name
						));

			if (!$query) return false;		
		}

		return $query->id;
	}

	public function insertImage($album_id, $filename, $type = 0)
	{
		$query = Images::create(array(
						'album_id' => $album_id,
						'filename' => $filename,
						'type' => $type
					));

		if (!$query) return false;

		return $query->id;
	}

	public function insertProfileImage($user_id, $image_id, $user_type)
	{
		$query = ProfileImages::withTrashed()
					->where('user_id', $user_id)
					->where('user_type', $user_type)
					->first();

		if (!$query)
		{
			$query = ProfileImages::create(array(
						'user_id' => $user_id,
						'image_id' => $image_id,
						'user_type' => $user_type
					));

			if (!$query) return false;
		}
		else
		{
			if ($query->trashed()) $query->restore();

			$query->image_id = $image_id;
			$query->save();
		}

		return true;
	}

	public function insertCoverImage($user_id, $image_id, $user_type)
	{
		$query = CoverImages::withTrashed()
					->where('user_id', $user_id)
					->where('user_type', $user_type)
					->first();

		if (!$query)
		{
			$query = CoverImages::create(array(
						'user_id' => $user_id,
						'image_id' => $image_id,
						'user_type' => $user_type
					));

			if (!$query) return false;
		}
		else
		{
			if ($query->trashed()) $query->restore();

			$query->image_id = $image_id;
			$query->save();
		}

		return true;
	}

	public function changeProfileImage($album_id, $image_id)
	{
		$username = Session::get('username');
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');		

		if (!$this->owner($user_id, $user_type)) return status_error();

		$album_owner = Albums::select(DB::raw('id'))
						->where('id', $album_id)
						->where('user_id', $user_id)
						->first();

		if (!$album_owner) return status_error();

		$image = Images::select(DB::raw('id, filename'))
				->where('album_id', '=', $album_id)
				->where('id', '=', $image_id)
				->first();

		if (!$image) return status_error();

		$filename = $image->filename;

		$query = ProfileImages::withTrashed()
					->where('user_id', $user_id)
					->where('user_type', $user_type)
					->first();

		if ($query)
		{
			if ($query->trashed()) $query->restore();

			$query->image_id = $image_id;
			$query->save();
		}
		else
		{
			$query = ProfileImages::create(array(
							'user_id' => $user_id,
							'user_type' => $user_type,
							'image_id' => $image_id
						));

			if (!$query) return status_error();
		}

		Session::put('profile_image', imageUrl($filename, $username, 'xxlarge'));
		return status_ok(array(
			'src' => imageUrl($filename, $username, 'large'),
			'crop_url' => action('ModalsController@getCrop', array($album_id, $image_id, $username, $filename)),
			'profile_url' => profileUrl($username)
		));
	}

	public function imageOwner($album_id, $image_id, $user_id)
	{
		$album_owner = Albums::select(DB::raw('id'))
						->where('id', $album_id)
						->where('user_id', $user_id)
						->first();

		if (!$album_owner) return false;

		$image = Images::select(DB::raw('id, filename'))
				->where('album_id', '=', $album_id)
				->where('id', '=', $image_id)
				->first();

		if (!$image) return false;

		return $image;
	}

	public function albumOwner($album_id, $user_id)
	{
		$query = Albums::select(DB::raw('id'))
						->where('id', $album_id)
						->where('user_id', $user_id)
						->first();

		return !$query ? false : true;
	}

	public function makeAlbumCover($album_id, $image_id)
	{
		$username = Session::get('username');
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');		

		if (!$this->owner($user_id, $user_type)) return status_error();
		if (!$this->imageOwner($album_id, $image_id, $user_id)) return status_error();

		$query = Images::where('album_id', $album_id)
							->where('album_cover', 1)
							->first();

		if ($query)
		{
			$query->album_cover = 0;
			$query->save();
		}

		$query = Images::find($image_id);
		$query->album_cover = 1;
		$query->save();

		return status_ok();
	}

	public function deleteImage($album_id, $image_id)
	{
		$username = Session::get('username');
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');		

		if (!$this->owner($user_id, $user_type)) return status_error();

		$image = $this->imageOwner($album_id, $image_id, $user_id);

		if (!$image) return status_error();

		$query = Images::find($image_id);

		if (!$query) return status_error();

		$query->delete();
		
		return status_ok();
	}

	public function changeCover($album_id, $image_id)
	{
		$username = Session::get('username');
		$user_id = Session::get('user_id');
		$user_type = Session::get('user_type');		

		if (!$this->owner($user_id, $user_type)) return status_error();

		$image = $this->imageOwner($album_id, $image_id, $user_id);

		if (!$image) return status_error();

		$filename = $image->filename;

		$cover = CoverImages::withTrashed()
						->where('user_id', $user_id)
						->where('user_type', $user_type)
						->first();

		if (!$cover)
		{
			CoverImages::create(array(
				'user_id' => $user_id,
				'user_type' => $user_type,
				'image_id' => $image_id
			));
		}
		else
		{
			$cover->update(array('image_id' => $image_id));

			if ($cover->trashed())
			{
				$cover->restore();
			}

		}		

		$image_url = imageUrl($filename, $username, 'xxlarge', true);
		return status_ok(array('profile_url' => profileUrl($username), 'src' => $image_url['url'], 'mt' => $image_url['mt']));		
	}

	public function uploadImage($file, $dir, $filename = null, $action = 'both', $extra = null)
	{
		$path = Config::get('app.image_upload_path');
		$sizes = Config::get('app.sizes');
		$quality = Config::get('app.quality');
		$x = Input::get('x');
		$y = Input::get('y');
		$w = Input::get('w');
		$h = Input::get('h');				
		
		if (!preg_match("/\/$/", $path))
		{
			$path = $path.'/';
		}

		$path = $path.$dir.'/';
		
		if (! File::exists($path))
		{
			File::makeDirectory($path);
		}

		$src = $path.'/xxxlarge/'.$filename;

		if ($filename == null)
		{
			$filename = str_random(32).'.jpg';
			$src = $file->getRealPath();

			$info = getimagesize($src);

			if ($info[0] > $info[1])
			{
				$width = $info[1];
				$height = $info[1];
				$posx = round(($width - $height) / 2);
				$posy = 0;
			}
			else
			{
				$width = $info[0];
				$height = $info[0];
				$posx = 0;
				$posy = round(($height - $width) / 2);
			}

			$x = $posx;
			$y = $posy;
			$w = $width;
			$h = $height;
		}

		foreach ($sizes as $key => $value)
		{
			if (! File::exists($path.$key))
			{
				File::makeDirectory($path.$key);
			}

			$value = explode(',', $value);

			$quality = isset($value[3]) ? $value[3] : $quality;
			
			if ($value[2] == 'crop' and ($action == 'both' or $action == 'crop'))
			{
				\Image::make($src)->interlace()->crop($value[0], $value[1], $x, $y, $w, $h)->save($path.$key.'/'.$filename, $quality);
			}
			elseif ($value[2] == 'resize' and ($action == 'both' or $action == 'resize'))
			{
				\Image::make($src)->interlace()->resize(null, $value[1], true, false)->save($path.$key.'/'.$filename, $quality);
			}

		}

		return $filename;
	}
}