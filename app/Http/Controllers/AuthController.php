<?php

namespace App\Http\Controllers;

use App\Visitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\APIHelpers;
use App\Http\Controllers\Controller;
use App\User;
use App\Setting;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login' , 'register' , 'invalid']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = request(['phone', 'password']);
        if (! $token = auth()->attempt($credentials)) {
            $response  = APIHelpers::createApiResponse(true , 401 , 'Invalid phone or password' , 'يرجي التاكد من رقم الهاتف او كلمة المرور' , null , $request->lang);
            return response()->json($response, 401);
        }
        if(! $request->fcm_token || !$request->unique_id || !$request->type){
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }
        $user = auth()->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();
        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($visitor){
            $visitor->user_id = $user->id;
            $visitor->save();
        }else{

            $visitor = new Visitor();
            $visitor->unique_id = $request->unique_id;
            $visitor->fcm_token = $request->fcm_token;
            $visitor->type = $request->type;
            $visitor->user_id = $user->id;
            $visitor->save();
        }
        $token = auth()->login($user);
        $user->token = $this->respondWithToken($token);
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $user , $request->lang);
        return response()->json($response , 200);


    }

    public function invalid(Request $request){

        $response = APIHelpers::createApiResponse(true , 401 ,  'تم تسجيل الخروج', 'تم تسجيل الخروج' , null, $request->lang );
        return response()->json($response , 401);
    }

    /*
    * create user
    */
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            "email" => 'required',
            "password" => 'required',
            "fcm_token" => 'required',
            "type" => "required", // 1 -> iphone , 2 -> android
            "unique_id" => "required",
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang);
            return response()->json($response , 406);
        }

        // check if phone number register before
        $prev_user_phone = User::where('phone', $request->phone)->first();
        if($prev_user_phone){
            $response = APIHelpers::createApiResponse(true , 409 ,  'رقم الهاتف موجود من قبل', 'رقم الهاتف موجود من قبل' , null, $request->lang );
            return response()->json($response , 409);
        }

        // check if email registered before
        $prev_user_email = User::where('email', $request->email)->first();
        if($prev_user_email){
            $response = APIHelpers::createApiResponse(true , 409 ,  'البريد الإلكتروني موجود من قبل', '' , null, $request->lang );
            return response()->json($response , 409);
        }

        $setting = Setting::find(1);
        $free_balance = $setting['free_balance'];

        $user = new User();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->fcm_token = $request->fcm_token;
        $user->free_balance = $user->free_balance + $free_balance;
        $user->my_wallet = $user->my_wallet + $free_balance;
        $user->free_ads_count = 0;
        $user->save();

        $visitor = Visitor::where('unique_id' , $request->unique_id)->first();
        if($visitor){
            $visitor->user_id = $user->id;
            $visitor->save();
        }else{
            $visitor = new Visitor();
            $visitor->unique_id = $request->unique_id;
            $visitor->fcm_token = $request->fcm_token;
            $visitor->type = $request->type;
            $visitor->user_id = $user->id;
            $visitor->save();
        }

        $token = auth()->login($user);
        $user->token = $this->respondWithToken($token);

        $response = APIHelpers::createApiResponse(false , 200  , '', '' , $user, $request->lang );
        return response()->json($response , 200);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = auth()->user();
        $response = APIHelpers::createApiResponse(false , 200  , '', '' , $user, $request->lang );
        return response()->json($response , 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , [], $request->lang );
        return response()->json($response , 200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $responsewithtoken = $this->respondWithToken(auth()->refresh());
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $responsewithtoken, $request->lang );
        return response()->json($response , 200);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 432000
        ];
    }

}
