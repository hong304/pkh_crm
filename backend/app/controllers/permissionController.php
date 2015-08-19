<?php
/**
 * Created by PhpStorm.
 * User: PKH
 * Date: 18/8/2015
 * Time: 14:42
 */

class permissionController extends Controller {

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */

    public function getPermissionList(){

        if(Input::get('mode')=='posting'){
            pd(Input::get('data'));
        }



        $list1 = permission::get();
        foreach ($list1 as $k => $v){
            $lists[$v->permissionGroup]['action'][$v->name]='';
        }

        $list = permission::leftJoin('permission_role','permissions.id','=','permission_role.permission_id')->where('role_id','3')->get();

  //      pd($list);
foreach($list as $k =>$v){
    $lists[$v->permissionGroup]['action'][$v->name] = $v->role_id;
}

        foreach ($lists as $k1 => $v1){
            $arr[] = $v1;
        }

        pd($arr);

       /* $lists[1]['groupName']='產品管理';
        $lists[1]['name']='product';
        $lists[1]['action']['view']=1;
        $lists[1]['action']['edit']=0;
        $lists[1]['action']['add']=0;
        $lists[1]['action']['delete']=0;*/

        return Response::json($lists);
    }

}

//Testing