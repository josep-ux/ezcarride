<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RiderController extends Controller
{
      public function dashboard(){
       return response()->json([
            'success' => true,
        ], 200);
    }
}
