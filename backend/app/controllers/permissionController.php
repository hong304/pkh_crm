<?php
/**
 * Created by PhpStorm.
 * User: PKH
 * Date: 18/8/2015
 * Time: 14:42
 */

class permissionController extends Controller {



    public function getUserGroup(){

        if(Auth::user()->can('allow_permission')){
            return Response::json(0);
            exit;
        }

        $roles = role::get();
        foreach($roles as $pg)
        {
            $userGroup[] = [
                'id' => $pg->id,
                'name' => $pg->name
            ];
        }
        return Response::json($userGroup);
    }

    public function getPermissionList(){
        if(Auth::user()->can('allow_permission')){
            return Response::json(0);
            exit;
        }
        if(Input::get('mode')=='posting'){
            $data = Input::get('data');
            foreach($data as $k => $v){
                foreach($v['action'] as $k1 =>$v1){
                    $iq = permission::where('name',$k1)->first();
                    if($iq != null){
                        $iq->role()->detach($v['roleId']);
                    if($v1 != '')
                        $iq->role()->attach($v1);
                    }
                }
            }
        }

$role_id = Input::get('roleId')['id'];

        $list1 = permission::whereNotNull('nameGroup')->get();
        foreach ($list1 as $k => $v){
            $lists[$v->permissionGroup]['name']=$v->nameGroup;
            $lists[$v->permissionGroup]['roleId']=$role_id;
            $lists[$v->permissionGroup]['groupName']=$v->permissionGroup;
            $lists[$v->permissionGroup]['action'][$v->name]='';
        }

        $list = permission::leftJoin('permission_role','permissions.id','=','permission_role.permission_id')->where('role_id',$role_id)->whereNotNull('nameGroup')->get();


foreach($list as $k =>$v){
    $lists[$v->permissionGroup]['action'][$v->name] = $v->role_id;
}

        foreach ($lists as $k1 => $v1){
            $arr[] = $v1;
        }

      return Response::json($arr);
    }

}

//Testing