<?php

class Supplier extends Eloquent  {
   
   /* public function getUpdatedAtAttribute($attr) {
     //   return Carbon::parse($attr)->format('d/m/Y - h:ia'); //Change the format to whichever you desire
        return date("Y-m-d", $attr);
    }*/
    //Laveral built_in updated_at functions override front_end displayed date. Dont need to do function call
}