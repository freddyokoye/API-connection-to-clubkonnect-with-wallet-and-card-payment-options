<?php

namespace App\Http\Controllers;

use App\StaticOption;
use App\Airtime;
use App\Airtimesale;
use App\Models\basicControl;
use App\Models\Currency;
use App\Models\Wallet;
use App\Jamb;
use App\Jambsale;
use App\Waec;
use App\Waecsale;
use App\Internet;
use App\Decoder;
use App\Decodersale;
use App\Decodersubscriptions;
use App\Internetbundle;
use App\Internetbundlesale;
use App\Electricity;
use App\Electricitysale;
use App\BettingWallet;
use App\BettingWalletsale;
use App\Action;
use App\Transfer;
use App\TransferLog;
use App\Notice;
use App\Scheme;
use App\Models\GeneralSetting;
use App\Gateway;
use App\User;
use App\UserAdvert;
use App\UserLog;
use Carbon\Carbon;
use DateTime;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\HomePageStaticSettings;
use Illuminate\Support\Facades\Session;
use function MongoDB\BSON\toJSON;
use Illuminate\Support\Str;
class ProductionsController extends Controller
{
    /**
     * Create a new controller instance. 
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        
        
        
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    
   
     public function buyairtime($id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Airtime::find($id);
        $data['page_title'] = "Buy Recharge Card";
        
        $settings = GeneralSetting::first();
       

        return view('frontend.user.airtime.buy', $data, compact('network','user','rewards','settings','defaultcurrency','wallet'));
    }

	public function postairtime(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'amount' => 'required|numeric|min:100|max:50000',
            'code' => 'required',
            'radio' => 'required',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        $network = Airtime::whereCode($request->code)->first();
       if ($request->radio == "pay_bank") {
           
           

            $buy['user_id'] = $user->id;
            $buy['full_name'] = $user->firstname .' '. $user->lastname;
            $buy['username'] = $user->username;
            $buy['network'] = $network->name;
            $buy['phone'] = $request->number;
            $buy['amount'] = $request->amount;
            $buy['network_code'] = $request->code;
            $buy['transaction_id'] = Str::random(8).Str::random(8);
            $data = Airtimesale::create($buy)->transaction_id;

            return redirect()->route('recharge.buyPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
            
            
            if ($wallet->balance <= 100){

            $notify[]=['error','Insufficient Balance! Your wallet can not be empty nor go bellow 10 please fund your account'];
            return back()->withNotify($notify)->withInput();


        }
            
        if ($wallet->balance < $request->amount){


            $notify[]=['error','Insufficient Balance! Not enough funds in your deposit wallet'];
            return back()->withNotify($notify)->withInput(); 


        }
        if ($wallet->balance <= 0){

            $notify[]=['error','Insufficient Balance! Not enough funds in your deposit wallet'];
            return back()->withNotify($notify)->withInput(); 


        }
            
		
        if ($wallet->balance > $request->amount){
           
           if ($wallet->balance < 100){
               
               $notify[]=['error','Insufficient Balance! Your wallet can not be empty nor go bellow 10 please fund your account'];
            return back()->withNotify($notify)->withInput(); 


            }
        
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIAirtimeV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&MobileNetwork=".$request->code."&Amount=".$request->amount."&MobileNumber=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            'Accept: application/json'
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

			
		if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MINIMUM_100"){
		     
		     $notify[]=['error','incorrect! Minimum amount is 100'];
            return back()->withNotify($notify)->withInput();

		}
		
		 if ($result == "MINIMUM_50000"){
		     
		     $notify[]=['error','incorrect! Maximum amount is 50,000'];
            return back()->withNotify($notify)->withInput();

		}
		 if ($result == "MISSING_MOBILENETWORK"){
		     
		     $notify[]=['error','incorrect! Mobile Network Is Empty. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();
        }
	
			
		$wallet->balance -= $request->amount;
        $wallet->save();	
        

         Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Airtime',
		]);
		 $network = Airtime::whereCode($request->code)->first();
		 Airtimesale::create([

					'user_id'=>$user->id,
                    'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$request->code,
					'amount'=>$request->amount,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
       $notify[]=['success','Purchase Successful! order has been received'];
       return redirect()->route('userAirtime')->withNotify($notify)->withInput();

		   

    	}
    	
    }
 }  
    
    public function printbuyairtime($id)
    {
        $user = Auth::user();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Airtime::find($id);
        $data['page_title'] = "Buy Print Airtime";
        
        $settings = GeneralSetting::first();
       

        return view('frontend.user.airtime.printbuy', $data, compact('network','user','rewards','settings'));
    }
    
    
    public function buycallcardonline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


            $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIAirtimeV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&MobileNetwork=".$request->code."&Amount=".$request->amount."&MobileNumber=".$request->number."&RequestID=".$user->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

		
		 if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MINIMUM_100"){
		     
		     $notify[]=['error','incorrect! Minimum amount is 100'];
            return back()->withNotify($notify)->withInput();

		}
		
		 if ($result == "MINIMUM_50000"){
		     
		     $notify[]=['error','incorrect! Maximum amount is 50,000'];
            return back()->withNotify($notify)->withInput();

		}
		 if ($result == "MISSING_MOBILENETWORK"){
		     
		     $notify[]=['error','incorrect! Mobile Network Is Empty. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
                Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Airtime',
		]);

              $network = Airtime::whereCode($request->code)->first();
		 Airtimesale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
		$notify[]=['success','Purchase Successful', 'Your order has been received'];
       return redirect()->route('userAirtime')->withNotify($notify)->withInput();

                    
               }
    	
    public function cardonlinePreview($transaction_id)
    {
        $user = Auth::user();
        $buy = Airtimesale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        $data['page_title'] = "Buy Recharge Card";
        
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();


        
            return view('frontend.user.airtime.rechargecard-preview', $data , compact('pays', 'rave','user'));
        
        abort(404);
    }
    
     public function buyinternet($code)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Internetbundle::select('id', 'code', 'name', 'plan', 'cost', 'image')->orderBy('cost', 'desc')->whereCode($code)->whereStatus(1)->get();
        $settings = GeneralSetting::first();
        $data['page_title'] = "Buy Data";
       

        return view('frontend.user.internet.select', $data, compact('network','user','rewards','settings','wallet','defaultcurrency'));
    }

   public function buyinternet2($id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Internetbundle::find($id);
        $settings = GeneralSetting::first();
        $data['page_title'] = "Buy Data";
       

        return view('frontend.user.internet.buy', $data, compact('network','user','rewards','settings','wallet','defaultcurrency'));
    }
    
    public function postinternet(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'radio' => 'required',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        
        $network = Internet::whereCode($request->code)->first();
       if ($request->radio == "pay_bank") {
           
           

            $buy['user_id'] = $user->id;
            $buy['full_name'] = $user->firstname .' '. $user->lastname;
            $buy['username'] = $user->username;
            $buy['data'] = $request->name;
            $buy['phone'] = $request->number;
            $buy['amount'] = $request->amount;
            $buy['network_code'] = $request->code;
            $buy['transaction_id'] = Str::random(8).Str::random(8);
            $data = Internetbundlesale::create($buy)->transaction_id;
          
            

            return redirect()->route('card.dataPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
            
            
             if ($wallet->balance <= 100){

            $notify[]=['error','Insufficient Balance! Your wallet can not be empty nor go bellow 10 please fund your account'];
            return back()->withNotify($notify)->withInput();


        }
            
        if ($wallet->balance < $request->amount){


            $notify[]=['error','Insufficient Balance! Not enough funds in your deposit wallet'];
            return back()->withNotify($notify)->withInput(); 


        }
        if ($wallet->balance <= 0){

            $notify[]=['error','Insufficient Balance! Not enough funds in your deposit wallet'];
            return back()->withNotify($notify)->withInput(); 


        }
        
        if ($wallet->balance > $request->amount){
		
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIDatabundleV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&MobileNetwork=".$request->code."&DataPlan=".$request->plan."&Amount=".$request->amount."&MobileNumber=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			
			
		  if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_MOBILENETWORK"){
		     
		     $notify[]=['error','incorrect! Mobile Network Is Empty. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		if ($result == "INVALID_DATAPLAN"){
		    
		    $notify[]=['error','incorrect! You have selected an invalid data plan. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		if ($result == "MISSING_DATAPLAN"){
		    
		    $notify[]=['error','incorrect! Data plan is empty. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		
		 
			
			
        $wallet->balance -= $request->amount;
        $wallet->save();
        
        
        
         Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Data Bundle',
		]);
		 $network = Internet::whereCode($request->code)->first();
		 Internetbundlesale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'data'=>$request->name,
					'network_code'=>$request->code,
					'amount'=>$request->amount,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
       $notify[]=['success','Purchase Successful! order has been received'];
       return redirect()->route('userData')->withNotify($notify)->withInput();

        
            }

    	}
    }
    
  
  public function buydatacardonline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


            $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIDatabundleV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&MobileNetwork=".$request->code."&DataPlan=".$request->plan."&Amount=".$request->amount."&MobileNumber=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			

        if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_MOBILENETWORK"){
		     
		     $notify[]=['error','incorrect! Mobile Network Is Empty. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		if ($result == "INVALID_DATAPLAN"){
		    
		    $notify[]=['error','incorrect! You have selected an invalid data plan. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		if ($result == "MISSING_DATAPLAN"){
		    
		    $notify[]=['error','incorrect! Data plan is empty. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		
		


             Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Data Bundle',
		]);
		 $network = Internet::whereCode($request->code)->first();
		 Internetbundlesale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'data'=>$request->name,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
		
		     
		     $notify[]=['succes','Purchase Successful! Your order has been received'];
       return redirect()->route('userData')->withNotify($notify)->withInput();

		
            

               }  
    
    	
 public function carddataPreview($transaction_id)
    {
        $user = Auth::user();
        $buy = Internetbundlesale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        
        $data['page_title'] = "Buy Data";
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();


        
            return view('frontend.user.internet.card-dataPreview', $data , compact('pays', 'rave','user'));
        
        abort(404);
    }   	
    	 
    
     public function buyelectricity($id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Electricity::whereId($id)->first();
        $settings = GeneralSetting::first();
        $data['page_title'] = "Pay Electricity Bill";
       

        return view('frontend.user.electricity.buy', $data, compact('network','user','rewards','settings','wallet','defaultcurrency'));
    }
    
    public function postelectricity(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'amount' => 'required|numeric|min:1000|max:50000',
            'company' => 'required',
            'type' => 'required',
            'phone' => 'required|numeric',
            
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $company = Electricity::whereCode($request->company)->first();

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        
        
       
        
        

           $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIVerifyElectricityV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ElectricCompany=".$request->company."&MeterType=".$request->type."&Meterno=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );

		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			
		
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 
		 if ($result == "INVALID_MeterNo"){

           $notify[]=['error','incorrect! An invalid Meter number was entered'];
            return back()->withNotify($notify)->withInput(); 
		}
		
		if ($result == "MISSING_MeterType"){

            $notify[]=['error','incorrect! MeterType field is empty'];
            return back()->withNotify($notify)->withInput(); 
		}
		
		if ($result == "MeterType_NOT_AVAILABLE"){

           $notify[]=['error','Sorry! Selected MeterType is not currently available'];
            return back()->withNotify($notify)->withInput(); 
		}
		if ($result == "MISSING_Electricity"){

            $notify[]=['error','incorrect! Electricity Company field is empty'];
            return back()->withNotify($notify)->withInput(); 
		}
		
		 if ($result == "INVALID_ElectricCompany"){

            $notify[]=['error','incorrect! Electricity Company field is empty'];
            return back()->withNotify($notify)->withInput(); 
		}
		

       
		 
 		 
         $data = (object) array(

                "name"=>$result,
                "number"=>$request->number,
                "amount"=>$myair,
                "code"=>$request->company,
                "type"=>$request->type,
                "company"=>$company->slogan,
                "phone" => $request->phone,
                );
        
        return view('frontend.user.electricity.preview', compact('data','user','rewards','settings','wallet','defaultcurrency','api_key','api_id'));
            

    	}
    	
    	
    	 public function userpayelectricity(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'amount' => 'required|numeric',
            'company' => 'required',
            'meter' => 'required',
            'phone' => 'required|numeric',
            'radio' => 'required',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        
         $network = Electricity::where('code', $request->company)->first();
       if ($request->radio == "pay_bank") {
           
           
            
            $buy['user_id'] = $user->id;
            $buy['network'] = $network->name;
            $buy['data'] = $request->meter;
            $buy['phone'] = $request->phone;
            $buy['number'] = $request->number;
            $buy['amount'] = $myair;
            $buy['network_code'] = $request->company;
            $buy['customer'] = $request->customer;
            $buy['transaction_id'] = Str::random(20);
            $data = Electricitysale::create($buy)->transaction_id;
          
           

            return redirect()->route('card.powerPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
            

        
        
        if ($wallet->balance > $myair){
            
            
            $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIElectricityV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ElectricCompany=".$request->company."&MeterType=".$request->meter."&PhoneNo=".$request->phone."&MeterNo=".$request->number."&Amount=".$request->amount."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
		
			
			
		 if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput();  

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput();

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		 
		 if ($result == "INVALID_MeterNo"){

           $notify[]=['error','incorrect! An invalid Meter number was entered'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		if ($result == "MISSING_MeterType"){

            $notify[]=['error','incorrect! MeterType field is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		if ($result == "MeterType_NOT_AVAILABLE"){

           $notify[]=['error','Sorry! Selected MeterType is not currently available'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		if ($result == "MISSING_Electricity"){

            $notify[]=['error','incorrect! Electricity Company field is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		 if ($result == "INVALID_ElectricCompany"){

            $notify[]=['error','incorrect! Electricity Company field is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		
		
		 
		
		
		    
		    $wallet->balance -= $myair;
        $wallet->save();
		
        
         Action::create([

					'user_id'=>$user->id,
					'action' => 'Paid Electricity Bill',
		]);
		$network = Electricity::whereCode($request->company)->first();
		Electricitysale::create([

					'user_id'=>$user->id,
					'network'=>$network->name,
					'data'=>$request->meter,
					'phone'=>$request->phone,
					'network_code'=>$request->company,
					'amount'=>$myair,
					'number'=>$request->number,
					'customer'=>$request->customer,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
		$notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
       }

         $notify[]=['error','Check Balance! Fund Your Wallet'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
  
		    
		}

    }
    
    
     public function cardpowerPreview($transaction_id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $settings = GeneralSetting::first();
        $buy = Electricitysale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        
        $data['page_title'] = "Pay Electricity Bill";
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();


        
            return view('frontend.user.electricity.card-powerPreview', $data , compact('pays', 'rave','user','settings','wallet','defaultcurrency'));
        
        abort(404);
    } 
    
    public function buypoweronline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


          $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIElectricityV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ElectricCompany=".$request->company."&MeterType=".$request->meter."&PhoneNo=".$request->phone."&MeterNo=".$request->number."&Amount=".$request->amount."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			

        	
		 if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput();

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

		}
		 
		 if ($result == "INVALID_MeterNo"){

           $notify[]=['error','incorrect! An invalid Meter number was entered'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		if ($result == "MISSING_MeterType"){

            $notify[]=['error','incorrect! MeterType field is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput();
		}
		
		if ($result == "MeterType_NOT_AVAILABLE"){

           $notify[]=['error','Sorry! Selected MeterType is not currently available'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		if ($result == "MISSING_Electricity"){

            $notify[]=['error','incorrect! Electricity Company field is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		 if ($result == "INVALID_ElectricCompany"){

            $notify[]=['error','incorrect! Electricity Company field is empty'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 
		}
		
		
		    
		    


             Action::create([

					'user_id'=>$user->id,
					'action' => 'Paid Electricity Bill',
		]);
		
		$network = Electricity::whereCode($request->company)->first();
		Electricitysale::create([

					'user_id'=>$user->id,
					'network'=>$network->name,
					'data'=>$request->meter,
					'phone'=>$request->phone,
					'network_code'=>$request->company,
					'amount'=>$myair,
					'number'=>$request->number,
					'customer'=>$request->customer,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
           $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userelectricity')->withNotify($notify)->withInput(); 

              
    }       
   
   
     public function buydecoder($plan)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Decodersubscriptions::whereCode($plan)->get();
        $image = Decoder::whereCode($plan)->first();
        $settings = GeneralSetting::first();
        $data['page_title'] = "Pay Cable TV";
       

        return view('frontend.user.decoder.select', $data, compact('network','image','user','rewards','settings','wallet','defaultcurrency'));
    }
    
       public function buydecoder2($id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Decodersubscriptions::whereId($id)->first();
        $image = Decoder::whereCode($network->code)->first();
        $settings = GeneralSetting::first();
        $matso = $defaultcurrency->value;
        $myair = $network->cost;
        $data['page_title'] = "Pay Cable TV";
        
        
        if ($wallet->balance <= 100){

            $notify[]=['error','Insufficient Balance! Your wallet can not be empty nor go bellow 10 please fund your account'];
            return back()->withNotify($notify)->withInput();


        }
            
        if ($wallet->balance < $myair){


            $notify[]=['error','Insufficient Balance! Not enough funds in your deposit wallet'];
            return back()->withNotify($notify)->withInput(); 


        }
        if ($wallet->balance <= 0){

            $notify[]=['error','Insufficient Balance! Not enough funds in your deposit wallet'];
            return back()->withNotify($notify)->withInput(); 


        }
       

        return view('frontend.user.decoder.buy', $data, compact('network','user','image','rewards','settings','wallet','defaultcurrency'));
    }
    
    public function postdecoder(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'code' => 'required',
            'plan' => 'required',
            'phone' => 'required',
            
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $bundle = Decodersubscriptions::wherePlan($request->plan)->whereCode($request->code)->first();
        
        
        $myair = $bundle->cost + 100;

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        
        
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIVerifyCableTVV1.0.asp?UserID=".$request->uid."&APIKey=".$request->key."&CableTV=".$request->code."&SmartCardNo=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			
	
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return back()->withNotify($notify)->withInput();

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		if ($result == "MISSING_PACKAGE"){

            $notify[]=['error','Sorry! Package field is empty'];
            return back()->withNotify($notify)->withInput(); 
		}
		
		 if ($result == "MISSING_CABLETV"){

            $notify[]=['error','Sorry! CableTV field is empty'];
            return back()->withNotify($notify)->withInput(); 
		}
			
		 if ($result == "INVALID_SMARTCARDNO"){
		     
		     $notify[]=['error','Invalid Decoder Number! You have entered an invalid decoder/smart card number. Please Try Again'];
            return back()->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "PACKAGE_NOT_AVAILABLE"){
		     
		     $notify[]=['error','Service Not Available! Your slected subscription plan is not available at the moment'];
            return back()->withNotify($notify)->withInput(); 

		}
		

        
		 
 		 
         $data = (object) array(

                "name"=>$result,
                "number"=>$request->number,
                "code"=>$request->code,
                "plan"=>$request->plan,
                "phone"=>$request->phone,
                "cost"=>$myair,
                "pname"=>$bundle->name,
                );
        
        return view('frontend.user.decoder.preview', compact('data','user','rewards','settings','wallet','defaultcurrency'));
            

    	}
    	
    	 public function paydecoder(Request $request)
      {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'plan' => 'required',
            'code' => 'required',
            'radio' => 'required',
            'phone'=> 'required|numeric',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->cost + 100;
        
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        
        $network = Decoder::whereId($request->code)->first();
       if ($request->radio == "pay_bank") {
           
          

            $buy['user_id'] = $user->id;
            $buy['data'] = $request->pname;
            $buy['number'] = $request->number;
            $buy['phone'] = $request->phone;
            $buy['amount'] = $myair;
            $buy['network_code'] = $request->code;
            $buy['customer'] = $request->customer;
            $buy['transaction_id'] = Str::random(8).Str::random(8);
            $data = Decodersale::create($buy)->transaction_id;

		

            return redirect()->route('card.tvPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
            

        
        $cost = $myair;
        if ($wallet->balance > $cost){
		
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APICableTVV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&CableTV=".$request->code."&Package=".$request->plan."&SmartCardNo=".$request->number."&PhoneNo=".$request->phone."&Amount=".$request->cost."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			
			
		 if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		if ($result == "MISSING_PACKAGE"){

            $notify[]=['error','Sorry! Package field is empty'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  
		}
		
		 if ($result == "MISSING_CABLETV"){

            $notify[]=['error','Sorry! CableTV field is empty'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  
		}
			
		 if ($result == "INVALID_SMARTCARDNO"){
		     
		     $notify[]=['error','Invalid Decoder Number! You have entered an invalid decoder/smart card number. Please Try Again'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 

		}
		
		 if ($result == "PACKAGE_NOT_AVAILABLE"){
		     
		     $notify[]=['error','Service Not Available! Your slected subscription plan is not available at the moment'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 

		}
		
            
           
		
		
		    
		    $wallet->balance -= $cost;
        $wallet->save();
		
        
         Action::create([

					'user_id'=>$user->id,
					'action' => 'TV Subscription',
		]);
		$network = Decoder::whereId($request->code)->first();
		
		Decodersale::create([

					'user_id'=>$user->id,
					'network'=>$network->name,
					'data'=>$request->pname,
					'network_code'=>$request->code,
					'amount'=>$cost,
					'number'=>$request->number,
					'phone'=>$request->phone,
					'customer'=>$request->customer,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
		$notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 
       }

         $notify[]=['error','Check Balance! Fund Your Wallet'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 
            }

    	}
    	
    
    public function cardtvPreview($transaction_id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $buy = Decodersale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        
        $data['page_title'] = "Pay Cable TV";
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();


        
            return view('frontend.user.decoder.card-tvPreview', $data , compact('pays', 'rave','user','wallet','defaultcurrency'));
        
        abort(404);
    } 
    
    
    public function buytvonline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->cost + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


         $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APICableTVV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&CableTV=".$request->code."&Package=".$request->plan."&SmartCardNo=".$request->number."&PhoneNo=".$request->phone."&Amount=".$request->cost."&RequestID=".$request->id."&CallBackURL=https://www.vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			
        if ($result == "INSUFFICIENT_BALANCE"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		if ($result == "INSUFFICIENT_APIAMOUNT"){
		     
		     
		     $notify[]=['error','Sorry Service Not Available! Please Try Again latet'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		 if ($result == "MISSING_CREDENTIALS"){
		     
		     $notify[]=['error','incorrect! The URL format is not valid. Please try again'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput(); 

		}
		
		  if ($result == "INVALID_RECIPIENT"){
		      
		      $notify[]=['error','invalid phone number! You have entered an invalid phone number. Please Try Again'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		
		 if ($result == "NVALID_ AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is not valid'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		
		 if ($result == "MISSING_AMOUNT"){
		     
		     $notify[]=['error','incorrect! Amount is empty'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		if ($result == "MISSING_PACKAGE"){

            $notify[]=['error','Sorry! Package field is empty'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  
		}
		
		 if ($result == "MISSING_CABLETV"){

            $notify[]=['error','Sorry! CableTV field is empty'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  
		}
			
		 if ($result == "INVALID_SMARTCARDNO"){
		     
		     $notify[]=['error','Invalid Decoder Number! You have entered an invalid decoder/smart card number. Please Try Again'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		
		 if ($result == "PACKAGE_NOT_AVAILABLE"){
		     
		     $notify[]=['error','Service Not Available! Your slected subscription plan is not available at the moment'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

		}
		

		
		Action::create([

					'user_id'=>$user->id,
					'action' => 'TV Subscription',
		]);
		$name = Decoder::whereId($request->code)->first();
		
		Decodersale::create([

					'user_id'=>$user->id,
					'network'=>$name->name,
					'data'=>$request->pname,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'number'=>$request->number,
					'phone'=>$request->phone,
					'customer'=>$request->customer,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
           $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userdecoder')->withNotify($notify)->withInput();  

                
    } 
    

  public function buyjamb($id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Jamb::find($id);
        $data['page_title'] = "Buy Jamb Epin";
        $settings = GeneralSetting::first();
       

        return view('frontend.user.jamb.buy', $data, compact('network','user','rewards','settings','wallet','defaultcurrency'));
    }
    
    
    
    public function postjamb(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'amount' => 'required|numeric|min:100|max:50000',
            'code' => 'required',
            'radio' => 'required',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        $network = Jamb::whereCode($request->code)->first();
       if ($request->radio == "pay_bank") {
           
           

            $buy['user_id'] = $user->id;
            $buy['full_name'] = $user->firstname .' '. $user->lastname;
            $buy['username'] = $user->username;
            $buy['network'] = $network->name;
            $buy['phone'] = $request->number;
            $buy['amount'] = $myair;
            $buy['network_code'] = $request->code;
            $buy['transaction_id'] = Str::random(8).Str::random(8);
            $data = Jambsale::create($buy)->transaction_id;

            return redirect()->route('jamb.buyPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
            
            
            if ($wallet->balance <= 10){

            session()->flash('message', "Your wallet can not be empty nor go bellow 10 please fund your account.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
            
        if ($wallet->balance < $myair){

            session()->flash('message', "You don't have enough funds in your deposit wallet.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
        if ($wallet->balance > $myair){
           
           if ($wallet->balance < 10){

            session()->flash('message', "Your wallet can not be empty nor go bellow 10 please fund your account.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
        if ($wallet->balance < 10){

            session()->flash('message', "Your wallet can not be empty nor go bellow 10 please fund your account.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
            
		
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIJAMBV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ExamType=".$request->code."&Amount=".$request->amount."&PhoneNo=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            'Accept: application/json'
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

			
		 if ($result == "INVALID_RECIPIENT"){

            session()->flash('message', "You have entered an invalid phone number. Please Try Again");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Number');

            return redirect()->route('userJamb');
		}
		
		 if ($result == "INSUFFICIENT_APIBALANCE"){

            session()->flash('message', "Purchase Successful But Merchant API Doesnt Have Enough Fund To Service Your Request. No fund deducted from your wallet");
            Session::flash('type', 'info');
            Session::flash('title', 'Insufficient API Balance');

            return redirect()->route('userJamb');
		}
		
		 if ($result == "INVALID_DATAPLAN"){

            session()->flash('message', "You have selected an invalid data plan. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Insufficient Amount');

            return redirect()->route('userJamb');
		}
		 if ($result == "MISSING_MOBILENETWORK"){

            session()->flash('message', "Mobile Network Is Empty. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Insufficient Amount');

            return redirect()->route('userJamb');
		}
			
		$wallet->balance -= $myair;
        $wallet->save();	
    
        
         Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Jamb Epin',
		]);
		 $network = Jamb::whereCode($request->code)->first();
		 Jambsale::create([

					'user_id'=>$user->id,
                    'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
       
        $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userJamb')->withNotify($notify)->withInput(); 
            }

    	}
    	
    }
    
    public function buyjambonline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


            $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIJAMBV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ExamType=".$request->code."&Amount=".$request->amount."&PhoneNo=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

        if ($result == "INVALID_RECIPIENT"){

            session()->flash('message', "You have entered an invalid phone number. Please Try Again");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Number');

            return redirect()->route('userJamb');
		}
		
		 if ($result == "INSUFFICIENT_APIBALANCE"){

            session()->flash('message', "Purchase Successful But Merchant API Doesnt Have Enough Fund To Service Your Request. No fund deducted from your wallet");
            Session::flash('type', 'info');
            Session::flash('title', 'Insufficient API Balance');

            return redirect()->route('userJamb');
		}
		
		 if ($result == "INVALID_DATAPLAN"){

            session()->flash('message', "You have selected an invalid data plan. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Data Plan');

            return redirect()->route('userJamb');
		}
		 if ($result == "MISSING_MOBILENETWORK"){

            session()->flash('message', "Mobile Network Is Empty. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Missing Number');

            return redirect()->route('userJamb');
		}


              $network = Jamb::whereCode($request->code)->first();
		 Jambsale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);

           
           $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userJamb')->withNotify($notify)->withInput(); 

               }
    	
    public function jambonlinePreview($transaction_id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $buy = Jambsale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        
        $data['page_title'] = "Buy Jamb Epin";
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();


        
            return view('frontend.user.jamb.jamb-preview', $data , compact('pays', 'rave','user','wallet','defaultcurrency'));
        
        abort(404);
    }
    
    public function buywaec($id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $network = Waec::find($id);
        $data['page_title'] = "Buy Waec Epin";
        
        $settings = GeneralSetting::first();
       

        return view('frontend.user.waec.buy', $data, compact('network','user','rewards','settings','wallet','defaultcurrency'));
    }
    
    public function postwaec(Request $request)
    {
        $this->validate($request, [
            'number'=> 'required|numeric',
            'amount' => 'required|numeric|min:100|max:50000',
            'code' => 'required',
            'radio' => 'required',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);

        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        $network = Waec::whereCode($request->code)->first();
       if ($request->radio == "pay_bank") {
           
           

            $buy['user_id'] = $user->id;
            $buy['full_name'] = $user->firstname .' '. $user->lastname;
            $buy['username'] = $user->username;
            $buy['network'] = $network->name;
            $buy['phone'] = $request->number;
            $buy['amount'] = $myair;
            $buy['network_code'] = $request->code;
            $buy['transaction_id'] = Str::random(8).Str::random(8);
            $data = Waecsale::create($buy)->transaction_id;

            return redirect()->route('waec.buyPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
            
            
            if ($wallet->balance <= 10){

            session()->flash('message', "Your wallet can not be empty nor go bellow 10 please fund your account.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
            
        if ($wallet->balance < $myair){

            session()->flash('message', "You don't have enough funds in your deposit wallet.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
        if ($wallet->balance > $myair){
           
           if ($wallet->balance < 10){

            session()->flash('message', "Your wallet can not be empty nor go bellow 10 please fund your account.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
        if ($wallet->balance < 10){

            session()->flash('message', "Your wallet can not be empty nor go bellow 10 please fund your account.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
            
		
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIWAECV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ExamType=".$request->code."&Amount=".$request->amount."&PhoneNo=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            'Accept: application/json'
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

			
		 if ($result == "INVALID_RECIPIENT"){

            session()->flash('message', "You have entered an invalid phone number. Please Try Again");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Number');

            return redirect()->back();
		}
		
		 if ($result == "INSUFFICIENT_APIBALANCE"){

            session()->flash('message', "Purchase Successful But Merchant API Doesnt Have Enough Fund To Service Your Request. No fund deducted from your wallet");
            Session::flash('type', 'info');
            Session::flash('title', 'Insufficient API Balance');

            return redirect()->back();
		}
		
		 if ($result == "INVALID_DATAPLAN"){

            session()->flash('message', "You have selected an invalid data plan. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Insufficient Amount');

            return redirect()->back();
		}
		 if ($result == "MISSING_MOBILENETWORK"){

            session()->flash('message', "Mobile Network Is Empty. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Insufficient Amount');

            return redirect()->back();
		}
		
		
			
		$wallet->balance -= $myair;
        $wallet->save();	
        

        
         Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Waec Epin',
		]);
		 $network = Waec::whereCode($request->code)->first();
		 Waecsale::create([

					'user_id'=>$user->id,
                    'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
		
       

        $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userWaec')->withNotify($notify)->withInput();
            }

    	}
    	
    }
    
    public function buywaeconline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


            $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIWAECV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&ExamType=".$request->code."&Amount=".$request->amount."&PhoneNo=".$request->number."&RequestID=".$request->id."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

        if ($result == "INVALID_RECIPIENT"){

            session()->flash('message', "You have entered an invalid phone number. Please Try Again");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Number');

            return redirect()->back();
		}
		
		 if ($result == "INSUFFICIENT_APIBALANCE"){

            session()->flash('message', "Purchase Successful But Merchant API Doesnt Have Enough Fund To Service Your Request. No fund deducted from your wallet");
            Session::flash('type', 'info');
            Session::flash('title', 'Insufficient API Balance');

            return redirect()->back();
		}
		
		 if ($result == "INVALID_DATAPLAN"){

            session()->flash('message', "You have selected an invalid data plan. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Data Plan');

            return redirect()->back();
		}
		 if ($result == "MISSING_MOBILENETWORK"){

            session()->flash('message', "Mobile Network Is Empty. Please try again");
            Session::flash('type', 'error');
            Session::flash('title', 'Missing Number');

            return redirect()->back();
		}
		
		Action::create([

					'user_id'=>$user->id,
					'action' => 'Bought Waec Epin',
		]);


              $network = Waec::whereCode($request->code)->first();
		 Waecsale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$request->code,
					'amount'=>$myair,
					'phone'=>$request->number,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);

            
            
           $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('userWaec')->withNotify($notify)->withInput();

               }
    	
    public function waeconlinePreview($transaction_id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $buy = Waecsale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        
        $data['page_title'] = "Buy Waec Epin";
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();

        
            return view('frontend.user.waec.waec-preview', $data , compact('pays', 'rave','user','wallet','defaultcurrency'));
        
        abort(404);
    }
    
    public function buybettingwallet()
    {
        $user = Auth::user();
        
        $data['settings'] = GeneralSetting::first();
        $currency_id = basicControl()->base_currency;
        $data['wallet'] = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $data['page_title'] = "Betting Wallet";
        $data['category'] = BettingWallet::whereStatus(1)->orderBy('code', 'desc')->get();
        return view('frontend.user.bettingwallet.buy_bettingwallet', $data);
    }
    
    // new
    public function postbettingwallet(Request $request)
    {
        $this->validate($request, [
            'BettingCompany' => 'required',
            'CustomerID'   =>  'required',
            //'UserID'   =>  'required|numeric',
            //'APIKey'   =>  'required|numeric',
            'amount'   =>  'required|numeric',
        ], [
            'radio.required' => 'Please select a method to payment '
        ]);
        
         $user = Auth::user();
         
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);
        $myair = $request->amount + 100;
        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;
        $network = BettingWallet::where('code',$request->BettingCompany)->first();
        if ($request->radio == "pay_bank") {
           
           

            $buy['user_id'] = $user->id;
            $buy['full_name'] = $user->firstname .' '. $user->lastname;
            $buy['username'] = $user->username;
            $buy['network'] = $network->name;
            $buy['phone'] = $user->mobile;
            $buy['amount'] = $myair;
            $buy['network_code'] = $network->code;
            $buy['trx_type'] = $network->name;
            $buy['transaction_id'] = Str::random(8).Str::random(8);
            $data = BettingWalletsale::create($buy)->transaction_id;

            return redirect()->route('bettingwallet.buyPreview', $data);
            
        } elseif ($request->radio == "pay_wallet") {
       
        if ($wallet->balance < $myair){

            session()->flash('message', "You don't have enough funds in your deposit wallet.");
            Session::flash('type', 'warning');
            Session::flash('title', 'Insufficient Balance');

            return redirect()->back();


        }
        
        $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIBettingV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&BettingCompany=".$request->BettingCompany."CustomerID=".$request->CustomerID."&Amount=".$request->amount."&RequestID=".$request->CustomerID."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);
			
			if ($result == "INVALID_CREDENTIALS"){

            session()->flash('message', "The UserID and API key combination is not correct");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Number');

            return redirect()->back();
		}
		
		 if ($result == "INSUFFICIENT_APIBALANCE"){

            session()->flash('message', "Purchase Successful But Merchant API Doesnt Have Enough Fund To Service Your Request. No fund deducted from your wallet");
            Session::flash('type', 'info');
            Session::flash('title', 'Insufficient API Balance');

            return redirect()->back();
		}
		
		 if ($result == "MISSING_CREDENTIALS"){

            session()->flash('message', "The URL format is not valid");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid URL');

            return redirect()->back();
		}
		 if ($result == "MISSING_USERID"){

            session()->flash('message', "Username field is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'Username field is empty');

            return redirect()->back();
		}
		 if ($result == "MISSING_APIKEY"){

            session()->flash('message', "API Key field is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'API Key field is empty');

            return redirect()->back();
		}
		 if ($result == "MISSING_BETTINGCOMPANY"){

            session()->flash('message', "Betting Company field is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'Betting Company field is empty');

            return redirect()->back();
		}
		 if ($result == "MISSING_AMOUNT"){

            session()->flash('message', "Amount is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'Amount is empty');

            return redirect()->back();
		}
		 if ($result == "INVALID_ AMOUNT"){

            session()->flash('message', "Amount is not valid");
            Session::flash('type', 'error');
            Session::flash('title', 'mount is not valid');

            return redirect()->back();
		}
		 if ($result == "MINIMUM_100"){

            session()->flash('message', "Minimum amount is 100");
            Session::flash('type', 'error');
            Session::flash('title', 'Minimum amount is 100');

            return redirect()->back();
		}
		 if ($result == "MINIMUM_50000"){

            session()->flash('message', "Minimum amount is 50,000");
            Session::flash('type', 'error');
            Session::flash('title', 'Minimum amount is 50,000');

            return redirect()->back();
		}
		 if ($result == "INVALID_CUSTOMERID"){

            session()->flash('message', "An invalid customer id was entered");
            Session::flash('type', 'error');
            Session::flash('title', 'invalid customer id');

            return redirect()->back();
		}
			

            if ($wallet->balance > $myair){
                
		$wallet->balance -= $myair;
        $wallet->save();
                
        
         Action::create([

					'user_id'=>$user->id,
					'action' => 'Funded Betting Wallet',
		]);
		 
		$network = BettingWallet::whereCode($request->BettingCompany)->first();
		 BettingWalletsale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$network->code,
					'amount'=>$myair,
					'phone'=>$user->mobile,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);
       

        $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('buybettingwallet')->withNotify($notify)->withInput();
            }
        
        // return redirect()->back();
    }
    }
    public function bettingwalletonline(Request $request)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $matso = $defaultcurrency->value;
        $myair = $request->amount + 100;
        $time = date('M j, Y  H:i:s', strtotime($user->bonus));
        $rewards = json_encode($time);

        $settings = GeneralSetting::first();
        $api_id = $settings->api_id;
        $api_key = $settings->api_key;


            $baseUrl = "https://www.nellobytesystems.com";
        $endpoint = "/APIBettingV1.asp?UserID=".$request->uid."&APIKey=".$request->key."&BettingCompany=".$request->BettingCompany."CustomerID=".$request->CustomerID."&Amount=".$request->amount."&RequestID=".$request->CustomerID."&CallBackURL=https://vhodia.com";
        $httpVerb = "GET";
        $contentType = "application/json"; //e.g charset=utf-8
        $headers = array (
            "Content-Type: $contentType",
            
        );
		
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $baseUrl.$endpoint);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content = json_decode(curl_exec( $ch ),true);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
        	curl_close($ch);
			$result = implode(', ', (array)$content);

        if ($result == "INVALID_CREDENTIALS"){

            session()->flash('message', "The UserID and API key combination is not correct");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid Number');

            return redirect()->back();
		}
		
		 if ($result == "INSUFFICIENT_APIBALANCE"){

            session()->flash('message', "Purchase Successful But Merchant API Doesnt Have Enough Fund To Service Your Request. No fund deducted from your wallet");
            Session::flash('type', 'info');
            Session::flash('title', 'Insufficient API Balance');

            return redirect()->back();
		}
		
		 if ($result == "MISSING_CREDENTIALS"){

            session()->flash('message', "The URL format is not valid");
            Session::flash('type', 'error');
            Session::flash('title', 'Invalid URL');

            return redirect()->back();
		}
		 if ($result == "MISSING_USERID"){

            session()->flash('message', "Username field is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'Username field is empty');

            return redirect()->back();
		}
		 if ($result == "MISSING_APIKEY"){

            session()->flash('message', "API Key field is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'API Key field is empty');

            return redirect()->back();
		}
		 if ($result == "MISSING_BETTINGCOMPANY"){

            session()->flash('message', "Betting Company field is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'Betting Company field is empty');

            return redirect()->back();
		}
		 if ($result == "MISSING_AMOUNT"){

            session()->flash('message', "Amount is empty");
            Session::flash('type', 'error');
            Session::flash('title', 'Amount is empty');

            return redirect()->back();
		}
		 if ($result == "INVALID_ AMOUNT"){

            session()->flash('message', "Amount is not valid");
            Session::flash('type', 'error');
            Session::flash('title', 'mount is not valid');

            return redirect()->back();
		}
		 if ($result == "MINIMUM_100"){

            session()->flash('message', "Minimum amount is 100");
            Session::flash('type', 'error');
            Session::flash('title', 'Minimum amount is 100');

            return redirect()->back();
		}
		 if ($result == "MINIMUM_50000"){

            session()->flash('message', "Minimum amount is 50,000");
            Session::flash('type', 'error');
            Session::flash('title', 'Minimum amount is 50,000');

            return redirect()->back();
		}
		 if ($result == "INVALID_CUSTOMERID"){

            session()->flash('message', "An invalid customer id was entered");
            Session::flash('type', 'error');
            Session::flash('title', 'invalid customer id');

            return redirect()->back();
		}
		 
		 
		 Action::create([

					'user_id'=>$user->id,
					'action' => 'Funded Betting Wallet',
		]);

              $network = BettingWallet::whereCode($request->BettingCompany)->first();
		 BettingWalletsale::create([

					'user_id'=>$user->id,
					'full_name' => $user->firstname .' '. $user->lastname,
                    'username' => $user->username,
					'network'=>$network->name,
					'network_code'=>$network->code,
					'amount'=>$myair,
					'phone'=>$user->mobile,
					'transaction_id'=> Str::random(8).Str::random(8),
		]);

            
           $notify[]=['success','Completed! Payment Successful. Thank you for paying your bulls through us'];
            return redirect()->route('buybettingwallet')->withNotify($notify)->withInput();

               
}
    public function bettingwalletonlinePreview($transaction_id)
    {
        $user = Auth::user();
        $currency_id = basicControl()->base_currency;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency_id]);
        $defaultcurrency = Currency::where('is_default', 'Yes')->first();
        $buy = BettingWalletsale::where('transaction_id', $transaction_id)->where('user_id', $user->id)->first();
        
        $data['page_title'] = "Buy Waec Epin";
        $data['buy'] = $buy;
        $pays = StaticOption::whereId(835)->get();
        $rave = StaticOption::whereId(1112)->get();

        
            return view('frontend.user.bettingwallet.bettingwallet-preview', $data , compact('pays', 'rave','user','wallet','defaultcurrency'));
        
        abort(404);
    }

}
