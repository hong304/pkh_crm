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
       // $lists = permission::with('role')->get()->toArray();
        $lists[0]['groupName']='客戶管理';
        $lists[0]['name']='customer';
        $lists[0]['action']['view']=1;
        $lists[0]['action']['edit']=1;
        $lists[0]['action']['add']=1;
        $lists[0]['action']['delete']=0;

        $lists[1]['groupName']='產品管理';
        $lists[1]['name']='product';
        $lists[1]['action']['view']=1;
        $lists[1]['action']['edit']=0;
        $lists[1]['action']['add']=0;
        $lists[1]['action']['delete']=0;

        return Response::json($lists);
    }

}

//Testing