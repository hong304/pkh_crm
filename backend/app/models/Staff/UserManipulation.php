<?php

$role = new Toddish\Verify\Models\Role;
$user = new Toddish\Verify\Models\User;
$permission = new Toddish\Verify\Models\Permission;

class UserManipulation {

    private $_staffId = '';

    public function __construct($staffId = false)
    {
        $this->action = $staffId ? 'update' : 'create';


    }

	
	public function save($info)
	{
	    if($this->action == 'create')
        {
            $e = $info['info'];
            $user = new Toddish\Verify\Models\User;
            $user->username = $e['username'];
            $user->password = $e['password'];
            $user->name = $e['name'];
            $user->verified = 1;
            $user->save();

            $user->roles()->sync(array($e['groups']['value']));

            return $user->id;
        }
        elseif($this->action == 'update')
        {
            $user = User::where('id', $info['info']['id'])->firstOrFail();
            
            // update user info first
            $e = $info['info'];
            $user->username = $e['username'];
            if(isset($e['password']))
            {
                $user->password = $e['password'];
            }
            $user->name = $e['name'];
            $user->save();
            
            // synchronize permission
            
            
            $role = DB::select("SELECT * FROM role_user WHERE user_id = ?", [$info['info']['id']]);
            DB::statement("DELETE FROM permission_role WHERE role_id = ?", [$role[0]->role_id]);
            
            foreach($info['permission'] as $pid=>$i)
            {
                if($i['selected'])
                {
                    DB::insert('insert into permission_role (role_id, permission_id) values (?, ?)', [$role[0]->role_id, $pid]);
                }
            }
            
            // synchronize user zone
            DB::statement("DELETE FROM UserZone WHERE userId = ?", [$info['info']['id']]);

            foreach($info['zone'] as $pid=>$i)
            {
                if($i['selected'])
                {
                    
                    DB::insert('insert into UserZone (userId, zoneId) values (?, ?)', [$info['info']['id'], $pid]);
                }
            }
            
            $this->_staffId = $user->id;
            
            return $user->id;
        }
	    
	}
}