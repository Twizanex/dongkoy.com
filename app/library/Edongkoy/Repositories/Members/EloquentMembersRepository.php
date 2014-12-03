<?php namespace Edongkoy\Repositories\Members;

# app/library/Edongkoy/Repositories/Members/EloquentMembersRepository.php
use Edongkoy\Repositories\Users\Models\User;
use Edongkoy\Repositories\Users\Models\UserFriends;
use Edongkoy\Repositories\Users\Models\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Form;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class EloquentMembersRepository implements MembersRepositoryInterface {

	public function newMembers()
	{
		$from_whom_ids = array();
		$to_whom_ids = array();
		$friends = array();

		if(Auth::check())
		{
			$user_id = Auth::user()->id;
			$members = UserStatus::where('status_id', 1)
						->where('user_id', '!=', $user_id)
						->orderBy('user_id', 'desc')
						->take(6)
						->get();

			$user_friends = UserFriends::where('user_id', $user_id)->get();

			if (count($user_friends))
			{
				foreach($user_friends as $key => $friend)
				{
					if ($friend->accepted)
					{
						$friends[$key] = $friend->friend_id;
					}
					else
					{
						$to_whom_ids[$key] = $friend->friend_id;
					}
				}
			}
			
			$friend_request = UserFriends::where('friend_id', $user_id)
								->where('accepted', 0)
								->get();

			if ($friend_request)
			{
				foreach($friend_request as $key => $request)
				{
					$from_whom_ids[$key] = $request->user_id; 
				}
			}
			
		}
		else
		{
			$members = UserStatus::where('status_id', 1)
						->orderBy('user_id', 'desc')
						->take(6)
						->get();
		}			

		$array = array();
		$data = '<tr>';
		$ctr = 0;

		foreach($members as $key => $member)
		{
			if (in_array($member->user_id, $to_whom_ids))
			{
				$array[$key]['link'] = Lang::get('global.pending_friend_request');				
			}
			else if (in_array($member->user_id, $from_whom_ids))
			{
				$array[$key]['link'] = '<a href="#" class="confirm-friend-request-side" data-type="link" data-action="accept-friend-request" data-id="'.$member->user_id.'">'.Lang::get('global.confirm_friend_request').'</a>';
			}	
			else if (in_array($member->user_id, $friends))
			{
				$array[$key]['link'] = '<span class="glyphicon glyphicon-check green"></span> '.Lang::get('global.friends');
			}
			else
			{
				$array[$key]['link'] = '<a href="#" class="add-friend" data-type="link" data-action="add-friend" data-id="'.$member->user_id.'">'.Lang::get('global.add_friend').'</a>';
			}

			$array[$key]['img_url'] = profileImage($member->userStatus, 'large');
			$array[$key]['name'] = $member->userStatus->firstname.' '.$member->userStatus->middlename.' '.$member->userStatus->lastname;
			$array[$key]['profile_link'] = profileUrl($member->userStatus->username->username);
			$array[$key]['id'] = $member->user_id;

			$data .= '<td width="100"><a href="'.$array[$key]['profile_link'].'"><img src="'.$array[$key]['img_url'].'" width="100%" /></a></td>';
			$ctr++;

			if ($ctr == 3)
			{
				$data .= '</tr><tr>';
				$ctr = 0;
			}


		}

		return $data;
	}
}