<?php namespace Edongkoy\Repositories\GlobalRepo;

# app/library/Edongkoy/Repositories/Global/EloquentGlobalRepository.php

use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Users\Models\UserFriends;
use Edongkoy\Repositories\Users\Models\UserNotifications;
use Edongkoy\Repositories\Users\Models\UserUnreadMessage;
use Edongkoy\Repositories\Admin\Models\Categories;
use Edongkoy\Repositories\Page\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class EloquentGlobalRepository implements GlobalRepositoryInterface {

	public function friendRequest()
	{
		if (Auth::check())
		{
			$query = UserFriends::where('friend_id', Auth::user()->id)
					->where('accepted', 0)
					->where('hidden', 0)
					->orderBy('id', 'desc')
					->get();			

			if ($query)
			{
				$data = array();
				$unread = 0;

				foreach($query as $key => $member)
				{
					$data[$key]['name'] = $member->friendRequest->firstname.' '.$member->friendRequest->middlename.' '.$member->friendRequest->lastname;
					$data[$key]['img_url'] = profileImage($member->friendRequest);
					$data[$key]['profile_link'] = profileUrl($member->friendRequest->username->username);
					$data[$key]['id'] = $member->user_id;

					if ($member->unread) $unread++;
				}

				return array(
					'unread' => $unread,
					'requests' => $data
				);
			}
		}

		return false;
	}

	public function userNotifications()
	{
		if (Auth::check())
		{
			$user_logged_id = Auth::user()->id;

			$query = UserNotifications::where('user_id', $user_logged_id)
					->orderBy('id', 'desc')
					->paginate(5);

			if ($query)
			{
				$data = array();
				$unread = UserNotifications::select(DB::raw('id'))
							->where('user_id', $user_logged_id)
							->where('unread', 1)
							->get();

				$unread = $unread->count();

				foreach($query as $key => $value)
				{
					$type = $value->type;

					if ($type == 1)
					{
						$data[$key]['name'] = userFullName($value->friendRequestAccepted);
						$data[$key]['img_url'] = profileImage($value->friendRequestAccepted);
						$data[$key]['link'] = profileUrl($value->friendRequestAccepted->username->username);
						$data[$key]['text'] = Lang::get('global.accepted_your_friend_request');
						$data[$key]['id'] = $value->user_id;						
					}
					elseif ($type == 2)
					{
						$data[$key]['name'] = $value->message->page->page_name;
						$data[$key]['img_url'] = profileImage($value->message->page);						
						$data[$key]['link'] = URL::route('message', array('username' => $value->message->page->username->username, 'id' => $value->message->conversation_id));
						$data[$key]['text'] = Lang::get('global.page_received_a_new_message');
						
					}
					elseif ($type == 3)
					{
						$data[$key]['name'] = $value->pageStatus->page_name;
						$data[$key]['img_url'] = profileImage($value->pageStatus);						
						$data[$key]['link'] = profileUrl($value->pageStatus->username->username);
												
						$text = $value->text;

						if ($text == 3)
						{
							$data[$key]['text'] = Lang::get('profile.is_now_active');
						}
						elseif ($text == 4)
						{
							$data[$key]['text'] = Lang::get('profile.has_been_blocked');
						}
						elseif ($text == 5)
						{
							$data[$key]['text'] = Lang::get('profile.has_been_disapproved');
						}
						elseif ($text == 6)
						{
							$data[$key]['text'] = Lang::get('profile.has_been_deleted');
						}												
					}
					elseif ($type == 4)
					{
						$data[$key]['name'] = userFullName($value->pageLike->whoLikes);
						$data[$key]['img_url'] = profileImage($value->pageLike->whoLikes);						
						$data[$key]['link'] = profileUrl($value->pageLike->page->username->username);
						$data[$key]['text'] = Lang::get('global.likes').' <b>'.$value->pageLike->page->page_name.'</b>';						
					}
					elseif ($type == 5)
					{
						$user_reviews = $value->userReviews;
						$data[$key]['name'] = userFullName($user_reviews->reviewer);
						$data[$key]['img_url'] = profileImage($user_reviews->reviewer);						
						$data[$key]['link'] = URL::route('review', array(
													'username' => $user_reviews->page->username->username,
													'review_id' => $user_reviews->id
												));
						$data[$key]['text'] = Lang::get('reminders.wrote_a_review_about').' <b>'.$user_reviews->page->page_name.'</b>';
					}
					elseif ($type == 6)
					{
						$data[$key]['name'] = userFullName($value->userReviewsLikes->user);
						$data[$key]['img_url'] = profileImage($value->userReviewsLikes->user);						
						$data[$key]['link'] = URL::route('review', array(
													'username' => $value->userReviewsLikes->pageReviews->page->username->username,
													'review_id' => $value->userReviewsLikes->page_reviews_id
												));
						$data[$key]['text'] = Lang::get('global.likes_your_review_about').' <b>'.$value->userReviewsLikes->pageReviews->page->page_name.'</b>';
					}
					elseif ($type == 7)
					{
						$review = $value->reviewComment;
						$user = $review->user;
						$data[$key]['name'] = userFullName($user);
						$data[$key]['img_url'] = profileImage($user);
						$data[$key]['link'] = URL::route('review', array(
							'username' => $review->review->page->username->username,
							'review_id' => $review->review_id
						));

						$data[$key]['text'] = Lang::get('global.wrote_a_comment_about_your_review');
					}
					elseif ($type == 8)
					{
						$review_comment_likes = $value->reviewCommentLikes;
						$user = $review_comment_likes->user;
						$data[$key]['name'] = userFullName($user);
						$data[$key]['img_url'] = profileImage($user);
						$data[$key]['link'] = URL::route('review', array(
							'username' => $review_comment_likes->comment->review->page->username->username,
							'review_id' => $review_comment_likes->comment->review_id
						));

						$review_comment_text = $review_comment_likes->comment->comment;
						$review_comment_text = strlen($review_comment_text) > 50 ? substr($review_comment_text, 0, 50).'...' : $review_comment_text;
						$data[$key]['text'] = Lang::get('global.like_your_comment_in_a_review_about', array('comment' => $review_comment_text));
					}										

					$data[$key]['time'] = time_ago($value->created_at);					
				}

				return array(
					'unread' => $unread,
					'notifications' => $data,
					'pagination' => $query->links()
				);
			}
		}

		return false;
	}

	public function newMessage()
	{
		if (Auth::check())
		{
			$user_id = Auth::user()->id;
			$query = UserUnreadMessage::where('user_id', $user_id)
							->take(5)
							->orderBy('id', 'desc')
							->get();

			if (!$query) return false;

			$data = array();
			$unread = 0;

			foreach($query as $key => $value)
			{
				$message = $value->message->message;
				$user_type = $value->message->user_type;
				$user = $user_type == 'user' ? $value->message->user : $value->message->page;
				$data[$key]['name'] = userFullName($user, $user_type);
				$data[$key]['img_url'] = profileImage($user);
				$data[$key]['profile_link'] = profileUrl($user->username->username);
				$data[$key]['message'] = strlen($message) <= 55 ? $message : substr($message, 0, 52).'...';
				$data[$key]['created_at'] = time_ago($value->created_at);
				$data[$key]['id'] = $value->message_id;
				$data[$key]['message_url'] = URL::route('message', array('username' => Auth::user()->username->username, 'id' => $value->message->conversation_id));

				if ($value->unread) $unread++;
			}

			return array(
				'unread' => $unread,
				'data' => $data
			);
		}

		return false;
	}

	public function getPageCategories($city_id=null)
	{
		$query = Categories::all();
		$data = array();
		$slugs = array();
		$info = array();
		
		foreach ($query as $key => $value)
		{
			$category_name = $value->category_name;
			$slug = Str::slug($category_name);
			$data[$key]['category_name'] = $category_name;
			//$data[$key]['total_pages'] = $value->pages()->count();
			$data[$key]['url'] = action('PagesController@show', $slug);
			$data[$key]['url'] = $city_id == null ? $data[$key]['url'] : $data[$key]['url'].'?city='.$city_id;
			$slugs[$key] = $slug;
			$info[$slug]['id'] = $value->id;
			$info[$slug]['name'] = $category_name;
		}

		return array(
			'data' => $data,
			'slugs' => $slugs,
			'info' => $info
		);
	}

	public function sitemap()
	{
		$pages = Page::paginate(1000);

	    $total_pages = ceil($pages->getTotal() / $pages->getPerPage());

	    //return status_ok(array('message' => $total_pages));
	    		
	    for ($i = 0; $i <= $total_pages; $i++)
	    {
	    	// add sitemaps (loc, lastmod (optional))
	    	if ($i == 0)
	    	{
	    		\Sitemap::addSitemap(URL::route('sitemapPages'));
	    	}
	    	else
	    	{
	    		\Sitemap::addSitemap(URL::route('sitemapPages').'?page='.$i);
	    	}
	    	    
	    }

	    // show sitemap
	    return \Sitemap::render('sitemapindex');
	}

	public function sitemapPages()
	{
		// add items
	    $pages = Page::paginate(1000);

	    foreach ($pages as $page)
	    {
	        \Sitemap::add(profileUrl($page->username->username), $page->updated_at, '0.5', 'weekly');
	    }

	    // show sitemap
	    return \Sitemap::render('xml');
	}
}