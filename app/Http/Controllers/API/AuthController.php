<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationEmail;
use App\Mail\VerifyOTPEmail;

class AuthController extends BaseController
{

    public function AdminSendInvitation(Request $request){
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $email = $request->email;

        $emailData = [
            'to' => $email
        ];

        if(!empty($email)){

            \Mail::send('api_email.invitation', $emailData, function ($m) use ($emailData) {
                $m->from('info@royalgujarati.com', 'Authantication System');
                $m->to($emailData['to'], 'Test User')->subject('Invitation Received.');
            });

            // Mail::to($email)->send(new InvitationEmail);
            
            return $this->sendResponse($email, 'Admin Send Invaitation Successfully.');
        }

    }

    public function UserRegister(Request $request){

        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'confirm_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        $otp = random_int(100000, 999999);

        $requestData = [
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'otp' => $otp,
            'registered_at' => date('Y-m-d h:i')
        ];

        $userRegister = User::create($requestData);

        $emailData = [
            'otp' => $otp,
            'to' => $request->email
        ];

        \Mail::send('api_email.verify_otp', $emailData, function ($m) use ($emailData) {
            $m->from('info@royalgujarati.com', 'Authantication System');
            $m->to($emailData['to'], 'Test User')->subject('Invitation Received.');
        });
   
        return $this->sendResponse($userRegister->toArray(), 'Please verify email address.');
    }

    public function CheckOTP(Request $request){
        
        $validator = Validator::make($request->all(), [
            'otp' => 'required|min:6',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        $getUser = User::where('id', $request->user_id)->first();

        if(!empty($getUser)){
            if($getUser->otp == $request->otp){
                
                User::where('id', $request->user_id)->update(['otp' => NULL]);
                
                $getUser->token =  $getUser->createToken('User Register')->accessToken;
                return $this->sendResponse($getUser->toArray(), 'User register successfully.');

            } else {
                return $this->sendError('OTP Missmatched.', ['error'=>'Please enter valid OTP (One Time Password).']);
            }
        } else {
            return $this->sendError('Not Found.', ['error'=>'User not found.']);
        }

        return $this->sendResponse($userRegister->toArray(), 'User register successfully.');
    }

    public function UserLogin(Request $request){

        $getEmail = User::where('email', $request->email)->first();
        
        if(!empty($getEmail)){
            $checkEmailVerify = User::where('email', $request->email)
                                    ->whereNull('otp')
                                    ->first();

            if(!empty($checkEmailVerify)){
                if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
                    $user = Auth::user();
                    $user->token =  $user->createToken('User Login')->accessToken;
           
                    return $this->sendResponse($user, 'User login successfully.');
                } else { 
                    return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
                } 
            } else {
                return $this->sendError('Not Verify Emnail.', ['error'=>'Please verify email.']);
            }

        } else {
            return $this->sendError('Not Registered.', ['error'=>'Email not registered.']);
        }
    }

    public function UserProfileUpdate(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'username' => 'required|unique:users,username,'.$request->user_id,
            // 'email' => 'required|email|unique:users,email,'.$request->user_id,
            'role' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        if(!empty($request->image)){
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|dimensions:max_width=256,max_height=256'
            ]);
       
            if($validator->fails()){
                return $this->sendError('Validation Error.', $validator->errors());       
            }

            $imageName = \Str::random(5).'_'.time().'.'.$request->image->getClientOriginalExtension();

            $request->image->move('public/images/profile/', $imageName);

            User::where('id', $request->user_id)->update(['profile_image' => $imageName]);          
        }

        $requestData = [
            'name' => $request->name,
            // 'username' => $request->username,
            // 'email' => $request->email,
            'role' => $request->role,
        ];

        User::where('id', $request->user_id)->update($requestData);

        $getUser = User::where('id', $request->user_id)->first();
        $getUser->profileURL = url('public/images/profile');

        return $this->sendResponse($getUser->toArray(), 'User profile update successfully.');
    }

    public function getMyMessages(){
        echo "Hello This is my function!";
        echo "Hey";
        die;
    }
}
