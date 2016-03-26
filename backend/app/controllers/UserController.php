<?php

class UserController extends BaseController {


	public function authenticationProcess()
	{



        if((isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== false)))
           die('Cant use IE, please use Chrome.');


	    if(Input::has('_token'))
	    {
	        // Runs validator before db validation
	        $rules = [
	            'username' => 'required|min:3|max:12|alpha_num',
	            'password' => 'required|min:8|max:12',
	        ];
	        $validator = Validator::make(Input::all(), $rules);
	        if ($validator->fails())
	        {
	           
	            return Redirect::action('UserController@authenticationProcess')->with('flash_error', 'Invalid Credential. Please try again');
	        }
	        else
	        {
    	        try
    	        {
    	            Auth::attempt(array('username' => Input::get('username'), 'password' => Input::get('password')));

    	            /* 
    	             * 29Dec2014 Redirect to any zone
    	             * return Redirect::action('UserController@selectZone');
    	             * 
    	             */
    	            
    	            $zone = UserZone::select('zoneId')->where('userId', Auth::user()->id)->first();
    	            Zone::setCurrentZone($zone->zoneId);
    	            
    	            /*
    	             * 2015Jan16 Add zone to temp zone to facilitate ajax processing
    	             */
    	            
    	            $loginRc = new LoginAudit();
    	            $loginRc->user = Auth::user()->id;
    	            $loginRc->time_in = time();
    	            $loginRc->save();
    	            
    	            Session::put('LoginId', $loginRc->id); 
    	            Session::put('logintime',time());
    	            
    	            $zones = UserZone::where('userId', Auth::user()->id)->lists('zoneId');
    	            $user = User::find(Auth::user()->id);
    	            $user->temp_zone = implode(',', $zones);
                    $user->logintime = Session::get('logintime');

                    if(User::where('id',Auth::user()->id)->with('role')->first()->role[0]->id != 3 && $_SERVER['HTTP_HOST'] != 'b.pingkeehong.com' && $_SERVER['HTTP_HOST'] != 'pkh-b.sylam.net')
                        $user->disabled = 1;
                    if(User::where('id',Auth::user()->id)->with('role')->first()->role[0]->id == 1)
                        $user->disabled = 0;
    	            $user->save(); 
    	            
                    return Redirect::to($_SERVER['frontend']);

    	            exit;
    	        }
    	        catch (Toddish\Verify\UserDeletedException $e)
    	        {
    	            SecurityLog::write('User has been deleted', $e);
    	            return Redirect::action('UserController@authenticationProcess')->with('flash_error', '密碼錯誤，請重試。');
    	        }
    	        catch (Toddish\Verify\UserNotFoundException $e)
    	        {
    	            SecurityLog::write('User cannot be found', $e);
    	            return Redirect::action('UserController@authenticationProcess')->with('flash_error', '密碼錯誤，請重試。');
    	        }
    	        catch (Toddish\Verify\UserUnverifiedException $e)
    	        {
    	            SecurityLog::write('Unverified User', $e);
    	            return Redirect::action('UserController@authenticationProcess')->with('flash_error', '密碼錯誤，請重試。');
    	        }
    	        catch (Toddish\Verify\UserDisabledException $e)
    	        {
    	            SecurityLog::write('User has been disabled', $e);
    	            return Redirect::action('UserController@authenticationProcess')->with('flash_error', '帳號已被暫停，請聯絡相關人員。');
    	        }
    	        catch (Toddish\Verify\UserPasswordIncorrectException $e)
    	        {
    	            SecurityLog::write('User password incorrect', $e);
    	            return Redirect::action('UserController@authenticationProcess')->with('flash_error', 'Invalid Credential. Please try again');
    	        }
	        }              
	    }elseif(!Auth::guest()){
            return Redirect::to($_SERVER['frontend']);
            exit;
        }
	    return View::make('user/AuthenticationForm');
	}
	
	public function selectZone()
	{
	    // TBU: Add zone verification
	    
	    Session::put('zone', Input::get('zoneId'));
	    return Response::json(['response'=>'ok']);
	}

	//20150214 Logout Function
	public function logoutProcess()
	{
	    $mode = Input::get('mode');
	    if(!in_array($mode, ['auto', 'manual']))
	    {
	        App::abort(404, 'Unknown Deauthorization Mode');
	    }
	    
	    // enable this user
	    $user = Auth::user();
	    $user->disabled = 0;
	    $user->save();
	    
	    // log this entry
	    $loginRc = LoginAudit::find(Session::get('LoginId'));
	    $loginRc->time_out = time();
	    $loginRc->mode = $mode;
	    $loginRc->save();
	    
	    // really logout
		Auth::logout();
		
		return Redirect::action('UserController@authenticationProcess'); 
	}
	
	public function changePassword()
	{
	    if(Input::has('_token'))
	    {

            $pwd  = Input::get('newpassword');

            if( !preg_match("#[0-9]+#", $pwd) ) {
                return Redirect::to('/changePassword')->with('flash_error', '新密碼必須最少包含1個數字');
            }
            if( !preg_match("#[a-z]+#", $pwd) ) {
                return Redirect::to('/changePassword')->with('flash_error', '新密碼必須最少包含1個英文');
            }

	        if (Hash::check(Input::get('oldpassword'), Auth::user()->getAuthPassword())) {
	            return Redirect::to('/changePassword')->with('flash_error', '舊密碼錯誤');
	        }
	        elseif(strlen(Input::get('newpassword')) < 8 )
            {
                return Redirect::to('/changePassword')->with('flash_error', '新密碼必須最少由8位英文及數字組成');
            }
            elseif(Input::get("newpassword") != Input::get('newpassword2'))
            {
                return Redirect::to('/changePassword')->with('flash_error', '密碼不相符');
            }
            else
            {    	        
    	        $user = User::findOrFail(Auth::user()->id);
    	        $user->password = Input::get('newpassword');
    	        $user->save();
    	        

    	            return Redirect::to($_SERVER['frontend']);

    	        exit;
            }
	    }
	    return View::make('user/AuthenticationChangePW');
	}
	
	public function jsonManiulateStaff()
	{


        if(Input::get('mode') == 'del'){
            //User::where('id',Input::get('customer_id'))->update(['disabled'=>1,'deleted'=>1]);
           User::find(Input::get('customer_id'))->roles()->detach();
           User::where('id',Input::get('customer_id'))->delete();

            return [];
        }

	    $id = Input::get('StaffId');
	    $account = Input::get('account');
	    $zones = Input::get('zone');
	    
	    // update user information
	    $user = User::where('id', $id)->first();
	    $user->username = $account['username'];
	    $user->name = $account['name'];
	    $user->email = $account['email'];
        $user->disabled = $account['status']['value'];
	    if(isset($account['password']))
	    {
			if(Auth::user()->role[0]->level>=$account['roles']['level'])
	        	$user->password = $account['password'];
	    }
	    $user->save();

        //pd(Input::all());

	    // update role
        // level 8 = Sales Manager
		if(Auth::user()->role[0]->level>=8)
	    	$user->roles()->sync(array($account['roles']['id']));
	    
	    
	    // update zone
	    DB::statement("DELETE FROM UserZone WHERE userId = ?", [$user->id]);
	    
	    foreach($zones as $zone)
	    {
	        if($zone['assigned'])
	        {
	           DB::insert('insert into UserZone (userId, zoneId) values (?, ?)', [$user->id, $zone['zoneId']]);
	        }
	    }
	    
	    
	}

    public function addStaff(){

        $e = Input::get('info');
        $zone = Input::get('zone');

        $user = new Toddish\Verify\Models\User;
        $user->username = $e['username'];
        $user->password = $e['password'];
        $user->name = $e['name'];
        $user->verified = 1;
        $user->save();

       // $user->roles()->sync(array($e['groups']['value']));
        $user->roles()->sync(['4']);

        $i = 0;


        if($e['groups']['value'] == 2 || $e['groups']['value'] == 5 || $e['groups']['value'] == 3)
            foreach($zone as $pid=>$i)
            {
                      DB::insert('insert into UserZone (userId, zoneId) values (?, ?)', [$user->id, $i['zoneId']]);
                     $i++;

            }
        else
            foreach($zone as $pid=>$i)
            {
                if(isset($i['selected']))
                {
                    DB::insert('insert into UserZone (userId, zoneId) values (?, ?)', [$user->id, $i['zoneId']]);
                    $i++;
                }
            }

        if($i == 0){
            DB::insert('insert into UserZone (userId, zoneId) values (?, ?)', [$user->id, 1]);
        }

    }

	public function jsonQueryStaff()
	{
	
	
	    $mode = Input::get('mode');
	
	    if($mode == 'collection')
	    {
	        $filter = Input::get('filterData');
           // Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);
	        $staff = User::select('*');

            if($filter['ceritera'] != '')
                  $staff->where('name', 'LIKE', '%'.$filter['ceritera'].'%');

	      //  $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
	        $staff = $staff->with('role');



            return Datatables::of($staff)
                ->addColumn('delete', function ($s) {
                    if(Auth::user()->can('delete_staff'))
                        return '<span onclick="delCustomer(\''.$s->id.'\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
                    else
                        return '';
                })->addColumn('link', function ($s) {
                    if(Auth::user()->can('edit_staff'))
                        return '<span onclick="editStaff(\''.$s->id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                    else
                        return '';

                })->editColumn('disabled',function($c) {
                    if($c->disabled == 0){
                        return '正常';
                    }else{
                        return '暫停';
                    }
                })
                ->make(true);


	    }



	   if($mode == 'single')
	    {
	        $staff['account'] = User::where('id', Input::get('StaffId'))->with('roles')->first();
	        
	        // load all available role
	        $staff['available_roles'] = DB::table('roles')->get(); 
	        
	        // load all zone
	        $zones = Zone::all();
	        $assignedZones = UserZone::select('zoneId')->where('userId', $staff['account']->id)->get();
	        $assignedZoneCustom = [];
	        foreach($assignedZones as $assignedZone)
	        {
	            $assignedZoneCustom[] = $assignedZone->zoneId;
	        }
	        foreach($zones as $zone)
	        {
	            $zone->assigned = in_array($zone->zoneId, $assignedZoneCustom);	                
	        }
	        $staff['zones'] = $zones;
	        
	        // load login records
	        $staff['loginrecords'] = LoginAudit::where('user', Input::get('StaffId'))->where('time_in', '>', time()-60*60*24*30)->orderby('id', 'desc')->get();
	        foreach($staff['loginrecords'] as $lr)
	        {
	            $lr->hash = Crypt::encrypt($lr->id);
	        }
            return Response::json($staff);
	    }
	    elseif($mode == 'forcelogout')
	    {
	        $hash = Crypt::decrypt(Input::get('hash'));
	        
	        $audit = LoginAudit::where('id', $hash)->first();
	        $audit->time_out = time();
	        $audit->mode = "Kick";
	        $audit->save();
	        
	        $user = User::where('id', $audit->user)->first();
	        $user->disabled = 0;
	        $user->save();
	        
	        $staff = [];

            return Response::json($staff);

	    }
	

	}

}