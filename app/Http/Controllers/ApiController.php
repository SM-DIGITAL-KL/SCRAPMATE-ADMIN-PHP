<?php

namespace App\Http\Controllers;

/*
|--------------------------------------------------------------------------
| ApiController - DEPRECATED / MIGRATED
|--------------------------------------------------------------------------
|
| This controller has been MIGRATED to Node.js. All mobile app API endpoints
| are now handled by Node.js controllers:
|
| - AuthController (login, registration, OTP)
| - UserController (user profiles, FCM tokens)
| - ShopController (shop management, images, reviews)
| - ProductController (products, categories)
| - OrderController (orders, ratings)
| - DeliveryBoyController (delivery boys)
| - NotificationController (notifications)
| - UtilityController (utility functions)
|
| Node.js API Base URL: Configure in .env (NODE_URL)
| All API routes are in routes/apiRoutes.js
|
| This file is kept for reference only. Do not use for new development.
|
*/

use Validator;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Stevebauman\Location\Facades\Location;


use App\Models\User;
use App\Models\file_upload;
use App\Models\Shop;
use App\Models\Customer;
use App\Models\ShopImages;
use App\Models\DeliveryBoy;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Order;
use App\Models\Notifications;
use App\Models\OrderRatings;
use App\Models\Pushsms;
use App\Models\AdminProfile;
use App\Models\CallLog;
use App\Models\Package;
use App\Models\Invoice;

use App\Notifications\FirebaseNotification;
use App\User as AppUser;

/**
 * @deprecated This controller has been migrated to Node.js
 * All mobile app APIs are now handled by Node.js server
 * See routes/apiRoutes.js for current API endpoints
 */
class ApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'msg' => env('APP_NAME').' Server Running',
            'data' => ''
        ], 200);
    }
    public function get_table(Request $req)
    {
        if (empty($req->post('name'))) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        }

        $res = DB::table($req->get('name'))->get();
        return response()->json([
            'status' => 'success',
            'msg' => 'get data',
            'data' => $res
        ], 200);
    }
    public function count_row($t_name)
    {
        if (empty($t_name)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 200);
        }

        $res = DB::table($t_name)->count();
        return response()->json([
            'status' => 'success',
            'msg' => 'get data',
            'data' => $res
        ], 200);
    }
    public function get_table_condition(Request $req)
    {
        if (empty($req->post('name'))&&empty($req->post('where'))&&empty($req->post('value'))) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 200);
        }

        $res = DB::table($req->post('name'))->where($req->post('where'),$req->post('value'))->get();
        return response()->json([
            'status' => 'success',
            'msg' => 'get data',
            'data' => $res
        ], 200);
    }
    public function login_app($mob)
    {
        $mob = trim($mob);
        if (!empty($mob)) {
            $check_mob = DB::table('users')->where('mob_num', $mob)->first();
            $data['static_otp'] = $static_otp = rand(1000, 9999);
            if ($check_mob) {
                if ($mob == '9605056015'||$mob == '7994095833') {
                    $data['static_otp'] = $static_otp = 4876;
                }
                $data['user'] = User::select('id','name','email','mob_num','user_type')->where('mob_num', $mob)->where('user_type','!=','A')->where('user_type','!=','U')->first();
                if (empty($data['user'])) {
                    return response()->json([
                        'status' => 'success',
                        'msg' => 'This number is might be the Admin number',
                        'data' => ''
                    ], 200);
                } else {
                    $data['user']->shop_type = NULL;
                    if ($data['user']->user_type == 'S') {
                        $data['user']->shop_type = Shop::where('user_id', $data['user']->id)->first()->shop_type;
                        $data['user']->language = Shop::where('user_id', $data['user']->id)->first()->language;
                    } elseif ($data['user']->user_type == 'C') {
                        $data['user']->language = Customer::where('user_id', $data['user']->id)->first()->language;
                    } else {
                        $data['user']->language = DeliveryBoy::where('user_id', $data['user']->id)->first()->language;
                    }
                    // if ($data['user']->user_type == 'S') {
                    //     $data['data'] = $shopdata = Shop::where('user_id', $data['user']->id)->first();
                    //     if (!empty($shopdata->profile_photo)) {
                    //         $data['image'] = url('/assets/images/profile/' . $shopdata->profile_photo);
                    //     }
                    // } elseif ($data['user']->user_type == 'C') {
                    //     $data['data'] = $customerdata = Customer::where('user_id', $data['user']->id)->first();
                    //     if (!empty($customerdata->profile_photo)) {
                    //         $data['image'] = url('/assets/images/profile/' . $customerdata->profile_photo);
                    //     }
                    // } else{
                    //     $data['data'] = $deliverydata = DeliveryBoy::where('user_id', $data['user']->id)->first();
                    //     if (!empty($deliverydata->profile_img)) {
                    //         $data['image'] = url('/assets/images/deliveryboy/' . $deliverydata->profile_img);
                    //     }
                    // }
                    $send = Pushsms::send_otp($mob, $static_otp);
                    return response()->json([
                        'status' => 'success',
                        'msg' => 'Mobile number already exists',
                        'data' => $data
                    ], 200);
                }
            } else {
                $send = Pushsms::send_otp($mob, $static_otp);
                return response()->json([
                    'status' => 'success',
                    'msg' => 'New User',
                    'data' => $data
                ], 200);
            }
        }
    }

    public function users_register(Request $req)
    {
        $language = $req->post('language');
        $usertype = $req->post('usertype');
        $shop_type = $req->post('shop_type');
        $name = $req->post('name');
        $email = $req->post('email');
        $place = $req->post('place');
        $address = $req->post('address');
        $location = $req->post('location');
        $state = $req->post('state');
        $mob_number = $req->post('mob_number');
        $pincode = $req->post('pincode');
        $lat_log = $req->post('lat_log');
        $place_id = $req->post('place_id');
        
        if (empty($mob_number) || empty($email) || empty($name) || empty($usertype) || empty($address) || empty($language)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 200);
        } else{
            try {
                $validated = $req->validate([
                    'email' => 'required|unique:users,email',
                    'mob_number' => 'required|unique:users,mob_num',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errors = $e->validator->errors();
                if ($errors->has('email')) {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Email already exists',
                        'data' => ''
                    ], 200);
                } elseif ($errors->has('mob_number')) {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Mobile number already exists',
                        'data' => ''
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'something wend worng in form',
                        'data' => ''
                    ], 200);
                }
            }
            try {
                if($req->hasFile('profile_photo')){
                    $profile_photo = $req->file('profile_photo');
                    $path = public_path('assets/images/profile');
                    $filename = file_upload::upload_image($profile_photo, $path);
                    $return_name = $filename;
                    // print_r($return_name);die();
                }else{
                    $return_name = '';
                }
                $data = new User;
                $data->name = $name;
                $data->email = $email;
                $data->password = Hash::make($mob_number);
                $data->mob_num = $mob_number;
                $data->user_type = $usertype;
                $data->save();
    
                $user_id = $data->id;
                if ($usertype == 'S') {
                    $package = Package::setPackage($user_id);
                    $shop = new Shop;
                    $shop->user_id = $user_id;
                    $shop->email = $email;
                    $shop->shopname = $name;
                    $shop->contact = $mob_number;
                    $shop->address = $address;
                    $shop->location = $location;
                    $shop->state = $state;
                    $shop->place = $place;
                    $shop->language = $language;
                    $shop->profile_photo = $return_name;
                    $shop->shop_type = $shop_type;
                    $shop->pincode = $pincode;
                    $shop->lat_log = $lat_log;
                    $shop->place_id = $place_id;
                    $shop->save();
    
                    $inserted_id = $shop->id;
                    $datas['data'] = $shop = Shop::find($inserted_id);
                    if (!empty($shop->profile_photo)) {
                        $datas['data']->profile_photo = url('/assets/images/profile/' . $shop->profile_photo);
                    } else {
                        $datas['data']->profile_photo = '';
                    }
                    $datas['user'] = 'shop';
                } else {
                    $customer = new Customer;
                    $customer->user_id = $user_id;
                    $customer->email = $email;
                    $customer->name = $name;
                    $customer->contact = $mob_number;
                    $customer->address = $address;
                    $customer->location = $location;
                    $customer->state = $state;
                    $customer->place = $place;
                    $customer->language = $language;
                    $customer->profile_photo = $return_name;
                    $customer->pincode = $pincode;
                    $customer->lat_log = $lat_log;
                    $customer->place_id = $place_id;
                    $customer->save();
    
                    $inserted_id = $customer->id;
                    $datas['data'] = $cust = Customer::find($inserted_id);
                    if (!empty($cust->profile_photo)) {
                        $datas['data']->profile_photo = url('/assets/images/profile/' . $cust->profile_photo);
                    } else {
                        $datas['data']->profile_photo = '';
                    }
                    $datas['user'] = 'customer';
                }
                return response()->json([
                    'status' => 'success',
                    'msg' => 'User Add Successfully',
                    'data' => $datas
                ], 200);   
            } catch (\Throwable $e) {
                // store failed jobs in failed_jobs table
                DB::table('failed_jobs')->insert([
                    'connection' => 'users_register',
                    'queue' => 'default',
                    'payload' => json_encode($req->all()),
                    'exception' => json_encode($e->getMessage())
                ]);
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Server Error',
                    'data' => $e->getMessage()
                ], 500);
            }
        }
    }
    public function shop_image_upload(Request $req)
    {
        $shop_id = $req->post('shop_id');
        $shop_img = $req->file('shop_img');
        if (empty($shop_img) || empty($shop_id)) 
        {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);  
        } else {
            // print_r($shop_img);die();
            $path = public_path('assets/images/shopimages');
            $filename = file_upload::upload_image($shop_img, $path);
            $return_name = $filename;
            $fileSize = filesize($path . '/' . $filename);
            $fileSize = number_format($fileSize / 1024 / 1024, 2) . ' MB';

            $data = new ShopImages;
            $data->shop_id = $shop_id;
            $data->shop_img = $return_name;
            $data->filesize = $fileSize;
            $data->save();

            $inserted_id = $data->id;
            $uploaded_file = array('id' => $inserted_id, 'filename' => url('/assets/images/shopimages/' . $return_name), 'fileSize' => $fileSize);            
            return response()->json([
                'status' => 'success',
                'msg' => 'Image Upload Successfully',
                'data' =>  $uploaded_file
            ], 200);  
        }
    }
    public function shop_image_delete($image_id)
    {
        $shop_image = ShopImages::find($image_id);
        if (!empty($shop_image)) {
            $image = $shop_image->shop_img;
            $path = public_path('assets/images/shopimages');
            unlink($path . '/' . $image);
            $shop_image->delete();
            return response()->json([
                'status' => 'success',
                'msg' => 'Image Delete Successfully',
                'data' => ''
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'msg' => 'Image Not Found',
                'data' => ''
            ], 201);
        }
    }

    public function delv_boy_add(Request $req)
    {
        $shop_id = $req->post('shop_id');
        $user_type = $req->post('user_type');
        $name = $req->post('name');
        $dob = $req->post('dob');
        $age = $req->post('age');
        $email = $req->post('email');
        $phone = $req->post('phone');
        $address = $req->post('address');
        $profile_img = $req->file('profile_img');
        $licence_no = $req->post('licence_no');
        $licence_img_front = $req->file('licence_img_front');
        $licence_img_back = $req->file('licence_img_back');
        if (empty($shop_id) || empty($user_type) || empty($name) || empty($dob) || empty($age) || empty($email) || empty($phone) || empty($address))
        {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            try {
                $validated = $req->validate([
                    'email' => 'required|unique:users,email',
                    'phone' => 'required|unique:users,mob_num',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errors = $e->validator->errors();
                if ($errors->has('email')) {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Email already exists',
                        'data' => ''
                    ], 200);
                } elseif ($errors->has('phone')) {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Mobile number already exists',
                        'data' => ''
                    ], 200);
                }
            }
            if($req->hasFile('profile_img')){
                $profile_photo = $req->file('profile_img');
                $path = public_path('assets/images/deliveryboy');
                $filename = file_upload::upload_image($profile_photo, $path);
                $return_name = $filename;
                $fileSize = filesize($path . '/' . $filename);
                $fileSize = number_format($fileSize / 1024 / 1024, 2) . ' MB';
                // print_r($return_name);die();
            }else{
                $return_name = '';
                $fileSize = '';
            }
            if($req->hasFile('licence_img_front')){
                $licence_img_front = $req->file('licence_img_front');
                $path = public_path('assets/images/deliveryboy');
                $filename = file_upload::upload_image($licence_img_front, $path);
                $licence_img_front = $filename; 
            } else {
                $licence_img_front = '';
            }
            if($req->hasFile('licence_img_back')){
                $licence_img_back = $req->file('licence_img_back');
                $path = public_path('assets/images/deliveryboy');
                $filename = file_upload::upload_image($licence_img_back, $path);
                $licence_img_back = $filename;
            } else {
                $licence_img_back = '';
            }
            $data = new User;
            $data->name = $name;
            $data->email = $email;
            $data->password = Hash::make($phone);
            $data->mob_num = $phone;
            $data->user_type = $user_type;
            $data->save();

            $user_id = $data->id;
            $del_boy = new DeliveryBoy;
            $del_boy->user_id = $user_id;
            $del_boy->shop_id = $shop_id;
            $del_boy->name = $name;
            $del_boy->dob = $dob;
            $del_boy->age = $age;
            $del_boy->email = $email;
            $del_boy->phone = $phone;
            $del_boy->address = $address;
            $del_boy->licence_no = $licence_no;
            $del_boy->licence_img_front = $licence_img_front;
            $del_boy->licence_img_back = $licence_img_back;
            $del_boy->profile_img = $return_name;
            $del_boy->filesize = $fileSize;
            $del_boy->save();

            $inserted_id = $del_boy->id;
            $datas['data'] = $deliveryboy = DeliveryBoy::find($inserted_id);
            $datas['data']->profile_img = $deliveryboy->profile_img ? url("/assets/images/deliveryboy/$deliveryboy->profile_img") : '';
            $datas['data']->licence_img_front = $deliveryboy->licence_img_front ? url("/assets/images/deliveryboy/$deliveryboy->licence_img_front") : '';
            $datas['data']->licence_img_back = $deliveryboy->licence_img_back ? url("/assets/images/deliveryboy/$deliveryboy->licence_img_back") : '';
            return response()->json([
                'status' => 'success',
                'msg' => 'User Add Successfully',
                'data' => $datas
            ], 200); 
        }
    }
    public function shop_image_list($shop_id)
    {
        if (empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = ShopImages::where('shop_id', $shop_id)->get();
            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Shop Image Not Found',
                    'data' => ''
                ], 201);
            } else{
                foreach ($data as $key => $value) {
                    $data[$key]->shop_img = url('/assets/images/shopimages/' . $value->shop_img);
                }
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Shop Image List',
                    'data' => $data
                ], 200);
            }
        }
    }
    public function shop_cat_create(Request $req)
    {
        $shop_id = $req->post('shop_id');
        $cat_name = $req->post('cat_name');
        // $cat_img = $req->file('cat_img');
        $cat_img = $req->post('cat_img');
        if (empty($shop_id) || empty($cat_name))
        {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            try {
                $cat_name = explode(',', $cat_name);
                $cat_img = explode(',', $cat_img);
                $fileSize = '';
                foreach ($cat_name as $key => $value) {
                    $pro_cat = new ProductCategory;
                    $pro_cat->shop_id = $shop_id;
                    $pro_cat->cat_name = $value;
                    $pro_cat->cat_img = $cat_img[$key];
                    $pro_cat->filesize = $fileSize;
                    $pro_cat->save();
                }
                $pro_cat->save(); 
                $datas['data'] = ProductCategory::where('shop_id',$shop_id)->get();
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Shop category created',
                    'data' => $datas
                ], 200);
            } catch (\Exception $e) {
                DB::table('failed_jobs')->insert([
                    'connection' => 'shop_cat_create',
                    'queue' => 'default',
                    'payload' => json_encode($req->all()),
                    'exception' => json_encode($e->getMessage())
                ]);
                return response()->json([
                    'status' => 'error',
                    'msg' => $e->getMessage(),
                    'data' => ''
                ], 201);
            }
        }
    } 

    public function shop_cat_edit(Request $req) 
    {
        $category_id = $req->post('category_id');
        $cat_name = $req->post('cat_name');
        $cat_img = $req->file('cat_img');
        if (empty($category_id))
        {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $product_cat = ProductCategory::find($category_id);
            if (!empty($cat_name)){
                $product_cat->cat_name = $cat_name;
            }
            if(!empty($cat_img)){
                $profile_photo = $req->file('cat_img');
                $path = public_path('assets/images/product_category');
                if (file_exists($path . '/' . $product_cat->cat_img)) {
                    unlink($path . '/' . $product_cat->cat_img);
                }
                $filename = file_upload::upload_image($profile_photo, $path);
                $return_name = $filename;
                $fileSize = filesize($path . '/' . $filename);
                $fileSize = number_format($fileSize / 1024 / 1024, 2) . ' MB';
                $product_cat->cat_img = $return_name;
                $product_cat->filesize = $fileSize;
            }
            $product_cat->save();
            return response()->json([
                'status' => 'success',
                'msg' => 'Shop category updated',
                'data' => $product_cat
            ], 200);
        }
    }
    public function shop_cat_list($shop_id)
    {
        if (empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            // $data = ProductCategory::where('shop_id', $shop_id)->get();
            // if (empty($data)) {
            //     return response()->json([
            //         'status' => 'error',
            //         'msg' => 'Shop Image Not Found',
            //         'data' => ''
            //     ], 201);
            // } else{
            //     // foreach ($data as $key => $value) {
            //     //     if (empty($value->cat_img)) {
            //     //         $data[$key]->cat_img = '';
            //     //     } else {
            //     //         $data[$key]->cat_img = url('/assets/images/product_category/' . $value->cat_img);
            //     //     }
            //     // }
            //     foreach ($data as $key => $val) {
            //         $items = Product::where('cat_id',$id)->count();
            //         $data['items_count'] = $items;
            //     }
            //     return response()->json([
            //         'status' => 'success',
            //         'msg' => 'Shop Image List',
            //         'data' => $data
            //     ], 200);
            // }
            $data = ProductCategory::where('shop_id', $shop_id)->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Shop Image Not Found',
                    'data' => $data
                ], 200);
            }

            $data = $data->map(function ($category) {
                $category->items_count = Product::where('cat_id', $category->id)->count();
                return $category;
            });

            return response()->json([
                'status' => 'success',
                'msg' => 'Shop Image List',
                'data' => $data
            ], 200);
        }
    }
    public function delivery_boy_list($shop_id)
    {
        if (empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = DeliveryBoy::where('shop_id', $shop_id)->get();
            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Delivery boy Not Found',
                    'data' => ''
                ], 201);
            } else{
                foreach ($data as $key => $value) {
                    $data[$key]->profile_img = !empty($value->profile_img) ? url("/assets/images/deliveryboy/{$value->profile_img}") : '';
                    $data[$key]->licence_img_front = !empty($value->licence_img_front) ? url("/assets/images/deliveryboy/{$value->licence_img_front}") : '';
                    $data[$key]->licence_img_back = !empty($value->licence_img_back) ? url("/assets/images/deliveryboy/{$value->licence_img_back}") : '';
                }
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Delivery boy List',
                    'data' => $data
                ], 200);
            }
        }
    }
    public function users_profile_view($user_id)
    {
        if (empty($user_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        }

        try {
            $data = User::find($user_id);
            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'User Not Found',
                    'data' => ''
                ], 201);
            } else{
                if ($data->user_type == 'S') {
                    $data1 = Shop::where('user_id',$data->id)->first();
                    if ($data1->profile_photo) {
                        $data1->profile_photo = url('/assets/images/profile/' . $data1->profile_photo);
                    } else {
                        $data1->profile_photo = '';
                    }
                } elseif ($data->user_type == 'C') {
                    $data1 = Customer::where('user_id',$data->id)->first();
                    if ($data1->profile_photo) {
                        $data1->profile_photo = url('/assets/images/profile/' . $data1->profile_photo);
                    } else {
                        $data1->profile_photo = '';
                    }
                } else {
                    $data1 = DeliveryBoy::where('user_id',$data->id)->first();
                    if ($data1->profile_img) {
                        $data1->profile_img = url('/assets/images/deliveryboy/' . $data1->profile_img);
                    } else {
                        $data1->profile_img = '';
                    }
                }
                return response()->json([
                    'status' => 'success',
                    'msg' => 'User Details',
                    'data' => $data1
                ], 200);
            }
        } catch (\Exception $e) {
            DB::table('failed_jobs')->insert([
                'connection' => 'users_profile_view',
                'queue' => 'default',
                'payload' => json_encode($req->all()),
                'exception' => json_encode($e->getMessage())
            ]);

            return response()->json([
                'status' => 'error',
                'msg' => 'Google API request failed',
                'data' => $e->getMessage()
            ], 500);
            
        }
    }
    public function delv_boy_delete($deliveryBoyID,$shop_id)
    {
        $delivery = DeliveryBoy::where('id', $deliveryBoyID)->where('shop_id', $shop_id)->first();
        if (!empty($delivery)) {
            $image = $delivery->profile_img;
            $image1 = $delivery->licence_img_front;
            $image2 = $delivery->licence_img_back;
            if (!empty($image)) {
                $path = public_path('assets/images/deliveryboy');
                unlink($path . '/' . $image);
            }
            if (!empty($image1)) {
                $path = public_path('assets/images/deliveryboy');
                unlink($path . '/' . $image1);
            }
            if (!empty($image2)) {
                $path = public_path('assets/images/deliveryboy');
                unlink($path . '/' . $image2);
            }
            $user = User::find($delivery->user_id);
            if (!empty($user)) {
                $user->delete();
            }
            $delivery->delete();
            return response()->json([
                'status' => 'success',
                'msg' => 'Delete Successfully',
                'data' => ''
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'msg' => 'delivery boy Not Found',
                'data' => ''
            ], 201);
        }
    }
    public function shop_item_create(Request $req)
    {
        $shop_id = $req->post('shop_id');
        $cat_id = $req->post('cat_id');
        $name = $req->post('name');
        $amout = $req->post('amout');
        if (empty($shop_id) || empty($cat_id) || empty($name) || empty($amout))
        {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $item = new Product;
            $item->shop_id = $shop_id;
            $item->cat_id = $cat_id;
            $item->name = $name;
            $item->amout = $amout;
            $item->save();

            $data = Product::where('id', $item->id)->first();
            return response()->json([
                'status' => 'success',
                'msg' => 'Item Add Successfully',   
                'data' => $data
            ], 200);
        }

    }
    public function keyword_search($table='', $name='')
    {
        if (empty($table) || empty($name)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = DB::table($table)->where('name', 'like', $name . '%')->get();
            // $data = DB::table($table)->where('name', 'like', '%' . $name . '%')->order_by('name', 'asc') ->get();
            return response()->json([
                'status' => 'success',
                'msg' => ' Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function shop_item_list($shop_id='', $cat_id='')
    {
        if (empty($shop_id) || empty($cat_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else{
            $data = Product::where('shop_id', $shop_id)->where('cat_id', $cat_id)->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Empty List',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            }
        }
    }
    function shop_item_edit(Request $req, $id = '')
    {
        if (empty($id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Product::where('id', $id)->first();
            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Not Found',
                    'data' => ''
                ], 201);
            } else {
                $res = Product::where('id', $id)->update([
                    'name' => (!empty($req->post('name'))?$req->post('name'):$data->name),
                    'amout' => (!empty($req->post('amout'))?$req->post('amout'):$data->amout), 
                ]);
                $data = Product::where('id', $id)->first();
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Updated Successfully',
                    'data' => $data
                ], 200);
            }
        }
    }
    function get_user_by_id($user_id = '', $table = '')
    {
        if (empty($user_id) || empty($table)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = DB::table($table)->where('user_id', $user_id)->first();
            if (!empty($data->profile_photo)) {
                $data->image = url('/assets/images/profile/' . $data->profile_photo);
            } else {
                $data->image = '';
            }
            if ($table == 'delivery_boy') {
                if (!empty($data->profile_img)) {
                    $data->image = url('/assets/images/deliveryboy/' . $data->profile_img);
                } else {
                    $data->image = '';
                }
            }
            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Not Found',
                    'data' => ''
                ], 201);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            }
        }
    }
    function get_all_tables()
    {
        $data = DB::select("SHOW TABLES");
        return response()->json([
            'status' => 'success',
            'msg' => 'Successfull',
            'data' => $data
        ], 200);
    }
    public function shop_item_delete($item_id='')
    {
        if (empty($item_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Product::where('id', $item_id)->delete();
            if ($data) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Deleted Successfully',
                    'data' => '[]'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Not Found',
                    'data' => ''
                ], 201);
            }
            
        }
    }
    public function fcm_token_store(Request $req)
    {
        $user_id = $req->post('user_id');
        $fcm_token = $req->post('fcm_token');
        if (empty($user_id) || empty($fcm_token)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = DB::table('users')->where('id', $user_id)->update([
                'fcm_token' => $fcm_token,
                'fcm_token_time' => time()
            ]);
            if ($data) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Added Fcm Token Successfully',
                    'data' => ''
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'User Not Found',
                    'data' => ''    
                ], 201);
            }
        }
    }
    public function fcmTokenClear($user_id = '')
    {
        if (empty($user_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else{
            $data = DB::table('users')->where('id', $user_id)->update([
                'fcm_token' => null,
            ]);
            if ($data) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Fcm Token Clear Successfully',
                    'data' => ''
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'User Not Found',
                    'data' => ''    
                ], 201);
            }
        }
    }
    public function user_mob_verification(Request $req)
    {
        $user_id = $req->post('user_id');
        $status = $req->post('status');
        if (empty($user_id) || empty($status)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        }else{
            $data = User::where('id', $user_id)->update([
                'mob_verified_status' => $status
            ]);
            if ($data) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Verified Successfully',
                    'data' => ''
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'User Not Found',
                    'data' => ''    
                ], 201);
            }
        }
    }
    public function user_profile_pic_edit(Request $req)
    {
        $user_id = $req->post('user_id');
        $profile_img = $req->file('profile_img');
        if (empty($user_id) || empty($profile_img)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $users = User::where('id', $user_id)->first();
            if (empty($users)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'User Not Found',
                    'data' => ''
                ], 201);
            } else {
                if ($users->user_type == 'S') {
                    $shop = Shop::where('user_id', $user_id)->first();
                    if ($shop) {
                        $path = public_path('assets/images/profile');
                        if (!empty($shop->profile_photo)) {
                            if (file_exists($path . '/' . $shop->profile_photo)) {
                                unlink($path . '/' . $shop->profile_photo);
                            }
                        }
                        $filename = file_upload::upload_image($profile_img, $path);
                        $shop->profile_photo = $filename;
                        $shop->save();
                
                        return response()->json([
                            'status' => 'success',
                            'msg' => 'Profile Updated Successfully',
                            'data' => ''
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'msg' => 'Shop not found',
                            'data' => ''
                        ], 404);
                    }
                } elseif ($users->user_type == 'C') { 
                    $customer = Customer::where('user_id', $user_id)->first();
                    if ($customer) {
                        $path = public_path('assets/images/profile');
                        if (!empty($customer->profile_photo)) {
                            if (file_exists($path . '/' . $customer->profile_photo)) {
                                unlink($path . '/' . $customer->profile_photo);
                            }
                        }
                        $filename = file_upload::upload_image($profile_img, $path);
                        $customer->profile_photo = $filename;
                        $customer->save();
                
                        return response()->json([
                            'status' => 'success',
                            'msg' => 'Profile Updated Successfully',
                            'data' => ''
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'msg' => 'Customer not found',
                            'data' => ''
                        ], 404);
                    }
                }
                 
            }
        }
    }

    public function shop_cat_delete($cat_id = '')
    {
        if (empty($cat_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $category = ProductCategory::find($cat_id);
            if (empty($category)){
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Category not found',
                    'data' => ''
                ], 404);
            } else {
                $products = Product::where('cat_id', $cat_id)->count();
                if ($products != 0) {
                    return response()->json([
                        'status' => 'success',
                        'msg' => 'Cant Delete . Products Added Under This Category',
                        'data' => ''
                    ], 200);
                } else {
                    // $image = $category->cat_img;
                    // if (!empty($image)) {
                    //     $path = public_path('assets/images/product_category');
                    //     unlink($path . '/' . $image);
                    // }
                    $category->delete();
                    return response()->json([
                        'status' => 'success',
                        'msg' => 'Successfully Deleted',
                        'data' => ''
                    ], 200);
                }
            }
            // echo '<pre>';print_r($category);die();
        }
    }
    public function all_pro_category(Request $req){
        $shop_id = $req->get('shop_id');
        // $category = category_img_keywords::all();
        // $category_names = $category->unique('cat_name')->map(function($item){
        //     return [
        //         'cat_img' => !empty($item->cat_img) ? url('/assets/images/product_category/' . $item->cat_img) : '',
        //         'cat_img' => $item->cat_img,
        //         'cat_name' => $item->cat_name
        //     ];
        // })->toArray();
        // $cat_array = array_values($category_names);
        $category = DB::table('category_img_keywords')->get();
        if ($shop_id) {
            $product_category = ProductCategory::where('shop_id', $shop_id)->get()->pluck('cat_name')->toArray();
            $categoryName = array_values(array_diff($category->pluck('category_name')->toArray(), $product_category));
            $category = DB::table('category_img_keywords')->whereIn('category_name', $categoryName)->get();
        }
        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'data' => $category
        ], 200);
    }

    public function shops_list_for_sale(Request $req)
{
    $lat_log = $req->post('lat_log');
    $categ = $req->post('category');
    $matchRadius = 15; // km for matching shops
    $api_key = env('APP_GOOGLE_API_KEY');

    // Parse reference point
    [$refLat, $refLng] = explode(',', $lat_log);
    $refLat = (float)$refLat;
    $refLng = (float)$refLng;

    try {
        // Process category filter if provided
        $shop_ids = [];
        if (!empty($categ)) {
            $categ = explode(',', $categ);
            $shop_cate = [];
            
            foreach ($categ as $cat) {
                $cat_shops = ProductCategory::where('cat_name', $cat)
                    ->pluck('shop_id')
                    ->toArray();
                
                if (empty($shop_cate)) {
                    $shop_cate = $cat_shops;
                } else {
                    $shop_cate = array_intersect($shop_cate, $cat_shops);
                }
            }
            
            if (empty($shop_cate)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Shops Not Found in these categories',
                    'data' => ''
                ], 201);
            }
            
            $shop_ids = $shop_cate;
        }

        // Get nearby shops from database with distance calculation
        $query = Shop::selectRaw("
            id,
            lat_log,
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(SUBSTRING_INDEX(lat_log, ',', 1))) * 
                cos(radians(SUBSTRING_INDEX(lat_log, ',', -1)) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(SUBSTRING_INDEX(lat_log, ',', 1)))
            )) AS distance
        ", [$refLat, $refLng, $refLat])
        ->where('del_status', 1)
        ->where('status', 2)
        ->having('distance', '<=', $matchRadius)
        ->orderBy('distance', 'asc');

        // Apply category filter if shop IDs were found
        if (!empty($shop_ids)) {
            $query->whereIn('id', $shop_ids);
        }

        $shops = $query->get(['id', 'lat_log', 'distance']);

        // Format shop locations for Distance Matrix API
        $shop_loc = [];
        foreach ($shops as $shop) {
            $shop_loc[$shop->id] = $shop->lat_log;
        }

        // Get precise distances from Google Distance Matrix API
        $ordered_data = $this->getShopDistances($lat_log, $shop_loc, $api_key);

        return response()->json([
            'status' => 'success',
            'msg' => 'Data retrieved',
            'data' => $ordered_data,
            // 'search_center' => [$refLat, $refLng],
            // 'search_radius_km' => $matchRadius,
            // 'filtered_categories' => $categ ?? null
        ], 200);

    } catch (\Exception $e) {
        DB::table('failed_jobs')->insert([
            'connection' => 'shops_list_for_sale',
            'queue' => 'default',
            'payload' => json_encode($req->all()),
            'exception' => json_encode($e->getMessage())
        ]);
        
        return response()->json([
            'status' => 'error',
            'msg' => 'Failed to fetch nearby shops',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get shop distances using Google Distance Matrix API
 */
    protected function getShopDistances($origin, $shop_loc, $api_key)
    {
        $batch_size = 25;
        $batches = array_chunk(array_values($shop_loc), $batch_size, true);
        $all_results = [];
        $client = new Client();

        foreach ($batches as $batch) {
            try {
                $response = $client->request('GET', 'https://maps.googleapis.com/maps/api/distancematrix/json', [
                    'query' => [
                        'origins' => $origin,
                        'destinations' => implode('|', $batch),
                        'key' => $api_key
                    ]
                ]);

                $responseData = json_decode($response->getBody(), true);
                if (isset($responseData['rows'][0]['elements'])) {
                    $all_results = array_merge($all_results, $responseData['rows'][0]['elements']);
                }
            } catch (\Exception $e) {
                Log::error('Google Distance Matrix API error: ' . $e->getMessage());
                continue; // Skip this batch but continue with others
            }
        }

        // Compile final results with shop data
        $ordered_data = [];
        $i = 0;
        
        foreach ($shop_loc as $shop_id => $lat_long) {
            if (!isset($all_results[$i])) {
                $i++;
                continue;
            }

            $element = $all_results[$i];
            if (isset($element['distance']['value']) && $element['distance']['value'] <= 15000) {
                $shop = Shop::find($shop_id);
                
                if ($shop) {
                    $ordered_data[] = [
                        'shop_id' => $shop_id,
                        'shop_name' => $shop->shopname,
                        'phone' => $shop->contact,
                        'address' => $shop->address,
                        'image' => $shop->profile_photo ? url('/assets/images/profile/' . $shop->profile_photo) : '',
                        'lat_long' => $lat_long,
                        'distance' => $element['distance']['text'] ?? 'N/A',
                        'distance_value' => $element['distance']['value'] ?? 0, // Add this for sorting
                        'duration' => $element['duration']['text'] ?? 'N/A'
                    ];
                }
            }
            $i++;
        }

        // Sort the results by distance in ascending order
        usort($ordered_data, function($a, $b) {
            return $a['distance_value'] <=> $b['distance_value'];
        });

        // Remove the temporary distance_value field if you don't want it in the response
        $ordered_data = array_map(function($item) {
            unset($item['distance_value']);
            return $item;
        }, $ordered_data);

        return $ordered_data;
    }

    public function shops_list_for_sale1(Request $req) {
        $pincode = $req->post('pincode');
        $lat_log = $req->post('lat_log');
        $categ = $req->post('category');
        $api_key = env('APP_GOOGLE_API_KEY');
    
        if (empty($pincode) || empty($lat_log)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $categ = explode(',', $categ);
            $shop_cate = ProductCategory::whereIn('cat_name', $categ)->get()->toArray();
            $shop_cate = array();
            foreach ($categ as $cat) {
                $cat_shops = ProductCategory::where('cat_name', $cat)->pluck('shop_id')->toArray();
                if (empty($shop_cate)) {
                    $shop_cate = $cat_shops;
                } else {
                    $shop_cate = array_intersect($shop_cate, $cat_shops);
                }
            }
            if (empty($shop_cate)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Shops Not Found in these categories',
                    'data' => ''
                ], 201);
            } else {
                $shop_ids = $shop_cate;
            }

            $shops = Shop::select('lat_log','id')->where('del_status', 1)->whereIn('id', $shop_ids)->get();
            // $shops = Shop::whereIn('id', $shop_ids)->where('pincode', $pincode)->get();

            $shop_loc = [];
            foreach ($shops as $shop) {
                if (!empty($shop->lat_log) && $shop->lat_log != '0.0,0.0') {
                    $shop_loc[$shop->id] = $shop->lat_log;
                }
            }
            // echo '<pre>';print_r($shop_loc);die();
            if (empty($shop_loc)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Shop Not Found',
                    'data' => ''
                ], 201);
            } else {
                // $lati_long = implode('|', array_slice(array_values($shop_loc), 0, 25));
                // // $lati_long = implode('|', array_values($shop_loc));
                // $client = new Client();
                // try {
                //     $response = $client->request('GET', 'https://maps.googleapis.com/maps/api/distancematrix/json', [
                //         'query' => [
                //             'origins' => $lat_log,
                //             'destinations' => $lati_long,
                //             'key' => $api_key
                //         ]
                //     ]);
                //     $responseData = json_decode($response->getBody(), true);
                //     $ordered_data = [];
                //     if (isset($responseData['rows'][0]['elements'])) {
                //         $elements = $responseData['rows'][0]['elements'];
                //         $i = 0;
                //         foreach ($shop_loc as $shop_id => $lat_long) {
                //             if (isset($elements[$i]['distance']['value']) && $elements[$i]['distance']['value'] <= 15000) {
                //                 $shopdata = Shop::where('id', $shop_id)->first();
                //                 if (!empty($shopdata->profile_photo)) {
                //                     $shopdata->profile_photo = url('/assets/images/profile/' . $shopdata->profile_photo);
                //                 } else {
                //                     $shopdata->profile_photo = '';
                //                 }
                //                 $distance = $elements[$i]['distance']['text'] ?? 'N/A';
                //                 $duration = $elements[$i]['duration']['text'] ?? 'N/A';
                //                 $ordered_data[] = [
                //                     'shop_id' => $shop_id,
                //                     'shop_name' => $shopdata->shopname,
                //                     'phone' => $shopdata->contact,
                //                     'address' => $shopdata->address,
                //                     'image' => $shopdata->profile_photo,
                //                     'lat_long' => $lat_long,
                //                     'distance' => $distance,
                //                     'duration' => $duration
                //                 ];
                                
                //             }
                //             $i++;
                //         }
                //     }
                //     return response()->json([
                //         'status' => 'success',
                //         'msg' => 'Data retrieved',
                //         'data' => $ordered_data
                //     ]);
                    
                // } catch (\Exception $e) {
                //     return response()->json([
                //         'status' => 'error',
                //         'msg' => 'Google API request failed',
                //         'data' => ''
                //     ], 500);
                // }
                    $batch_size = 25;
                    $batches = array_chunk(array_values($shop_loc), $batch_size, true);

                    $all_results = [];
                    // print_r(json_encode(array_values($shop_loc)));die;
                    foreach ($batches as $batch) {
                        $lati_long = implode('|', $batch);
                        $client = new Client();
                        try {
                            $response = $client->request('GET', 'https://maps.googleapis.com/maps/api/distancematrix/json', [
                                'query' => [
                                    'origins' => $lat_log,
                                    'destinations' => $lati_long,
                                    'key' => $api_key
                                ]
                            ]);
                            $responseData = json_decode($response->getBody(), true);
                            if (isset($responseData['rows'][0]['elements'])) {
                                $all_results = array_merge($all_results, $responseData['rows'][0]['elements']);
                            }
                        } catch (\Exception $e) {
                            DB::table('failed_jobs')->insert([
                                'connection' => 'shops_list_for_sale',
                                'queue' => 'default',
                                'payload' => json_encode($req->all()),
                                'exception' => json_encode($e->getMessage())
                            ]);
                            return response()->json([
                                'status' => 'error',
                                'msg' => 'Google API request failed',
                                'data' => $e->getMessage()
                            ], 500);
                        }
                    }
                    // echo '<pre>';print_r($all_results);die();
                    $ordered_data = [];
                    $i = 0;
                    foreach ($shop_loc as $shop_id => $lat_long) {
                        if (isset($all_results[$i]['distance']['value']) && $all_results[$i]['distance']['value'] <= 15000) {
                            $shopdata = Shop::where('id', $shop_id)->first();
                            if (!empty($shopdata->profile_photo)) {
                                $shopdata->profile_photo = url('/assets/images/profile/' . $shopdata->profile_photo);
                            } else {
                                $shopdata->profile_photo = '';
                            }
                            $distance = $all_results[$i]['distance']['text'] ?? 'N/A';
                            $duration = $all_results[$i]['duration']['text'] ?? 'N/A';
                            $ordered_data[] = [
                                'shop_id' => $shop_id,
                                'shop_name' => $shopdata->shopname,
                                'phone' => $shopdata->contact,
                                'address' => $shopdata->address,
                                'image' => $shopdata->profile_photo,
                                'lat_long' => $lat_long,
                                'distance' => $distance,
                                'duration' => $duration
                            ];
                        }
                        $i++;
                    }
                    return response()->json([
                        'status' => 'success',
                        'msg' => 'Data retrieved',
                        'data' => $ordered_data
                    ]);


            }
        }
    }
    public function items_list_for_sale(Request $req) {
        $shop_id = $req->post('shop_id');
        $category = $req->post('category');
        if (empty($shop_id) || empty($category)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $categ = explode(',', $category);
            $categoryIds = ProductCategory::whereIn('cat_name', $categ)->pluck('id')->toArray();
            $data = Product::where('shop_id', $shop_id)->whereIn('cat_id', $categoryIds)->get();

            $groupedData = [];
            foreach ($data as $product) {
                $categoryName = ProductCategory::find($product->cat_id)->cat_name;
                if (in_array($categoryName, $categ)) {
                    $groupedData[$categoryName][] = $product;
                }
            }

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Empty List',
                    'data' => $groupedData
            ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $groupedData
            ], 200);
            }
        }
    }

    public function shop_dash_counts($shop_id='') {
        if (empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data['shop_category_count'] = ProductCategory::where('shop_id', $shop_id)->count();
            $data['shop_item_count'] = Product::where('shop_id', $shop_id)->count();
            $data['total_oders'] = Order::where('shop_id', $shop_id)->count();
            $data['pending_orders'] = Order::where('shop_id', $shop_id)->where('status', 1)->count();
            $data['shop_accepted_orders'] = Order::where('shop_id', $shop_id)->where('status', 2)->count();
            $data['pickupman_orders'] = Order::where('shop_id', $shop_id)->where('status', 3)->count();
            $data['completed_orders'] = Order::where('shop_id', $shop_id)->where('status', 4)->count();
            $data['cancelled_orders'] = Order::where('shop_id', $shop_id)->where('status', 5)->count();
            $data['customers_count'] = Order::where('shop_id', $shop_id)->distinct()->count('customer_id');

            
            $months = range(1, 12);
            $monthly_orders_count = array_fill_keys(array_map(fn($m) => date('F', mktime(0, 0, 0, $m, 10)), $months), 0);
            $monthly_order_amount = $monthly_orders_count;
            
            $orders = Order::where('shop_id', $shop_id)->where('status', 4)->whereYear('created_at', date('Y'))->get(['created_at', 'estim_price']);

            foreach ($orders as $order) {
                $monthName = date('F', strtotime($order->created_at));
                $monthly_orders_count[$monthName]++;
                $monthly_order_amount[$monthName] += $order->estim_price;
            }
            $data['monthly_orders_count'] = $monthly_orders_count;
            $data['monthly_order_amount'] = $monthly_order_amount;


            // $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            // $monthly_orders_count = [];
            // foreach ($months as $month) {
            //     $count = Order::where('shop_id', $shop_id)
            //         ->whereYear('created_at', date('Y'))
            //         ->whereMonth('created_at', date('m', strtotime($month . ' 1')))
            //         ->count();
            //     $monthly_orders_count[$month] = $count ?: 0;
            // }
            // $data['monthly_orders_count'] = $monthly_orders_count;
            // $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            // $monthly_order_amount = [];
            // foreach ($months as $month) {
            //     $monthly_order_amount[$month] = Order::where('shop_id', $shop_id)
            //         ->whereYear('created_at', date('Y'))
            //         ->whereMonth('created_at', date('m', strtotime($month . ' 1')))
            //         ->sum('estim_price') ?: 0;
            // }
            $user_id = Shop::where('id', $shop_id)->first()->user_id;
            $data['subscrption'] = Package::BalanceCount($user_id);
            Package::checkUserPackage($user_id);
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function delivery_boy_edit(Request $req) {
        $del_boy_id = $req->post('del_boy_id');
        $name = $req->post('name');
        $dob = $req->post('dob');
        $age = $req->post('age');
        $address = $req->post('address');
        $licence_no = $req->post('licence_no');
        $profile_img = $req->file('profile_img');
        $licence_img_front = $req->file('licence_img_front');
        $licence_img_back = $req->file('licence_img_back');
        if (empty($del_boy_id) || empty($name) || empty($dob) || empty($age) || empty($address))
        {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = DeliveryBoy::find($del_boy_id);
            if($req->hasFile('profile_img')){
                $profile_img = $req->file('profile_img');
                $path = public_path('assets/images/deliveryboy');
                if (!empty($data->profile_img)) {
                    if (file_exists($path . '/' . $data->profile_img)) {
                        unlink($path . '/' . $data->profile_img);
                    }
                }
                $filename = file_upload::upload_image($profile_img, $path);
                $return_name = $filename;
                $fileSize = filesize($path . '/' . $filename);
                $fileSize = number_format($fileSize / 1024 / 1024, 2) . ' MB';
                // echo '<pre>';print_r($fileSize);die();
            }else{
                $return_name = $data->profile_img;
                $fileSize = $data->filesize;
            }
            
            if($req->hasFile('licence_img_front')){
                $licence_img_front = $req->file('licence_img_front');
                $path = public_path('assets/images/deliveryboy');
                if (!empty($data->licence_img_front)) {
                    if (file_exists($path . '/' . $data->licence_img_front)) {
                        unlink($path . '/' . $data->licence_img_front);
                    }
                }
                $filename = file_upload::upload_image($licence_img_front, $path);
                $licence_img_front = $filename; 
            } else {
                $licence_img_front = '';
            }
            if($req->hasFile('licence_img_back')){
                $licence_img_back = $req->file('licence_img_back');
                $path = public_path('assets/images/deliveryboy');
                if (!empty($data->licence_img_back)) {
                    if (file_exists($path . '/' . $data->licence_img_back)) {
                        unlink($path . '/' . $data->licence_img_back);
                    }
                }
                $filename = file_upload::upload_image($licence_img_back, $path);
                $licence_img_back = $filename;
            } else {
                $licence_img_back = '';
            }
            
            $data->name = $name;
            $data->dob = $dob;
            $data->age = $age;
            $data->address = $address;
            $data->licence_no = $licence_no;
            $data->licence_img_front = $licence_img_front;
            $data->licence_img_back = $licence_img_back;
            $data->profile_img = $return_name;
            $data->filesize = $fileSize;
            $data->save();

            $datas['data'] = $deliveryboy = DeliveryBoy::find($del_boy_id);
            $datas['data']->profile_img = $deliveryboy->profile_img ? url("/assets/images/deliveryboy/$deliveryboy->profile_img") : '';
            $datas['data']->licence_img_front = $deliveryboy->licence_img_front ? url("/assets/images/deliveryboy/$deliveryboy->licence_img_front") : '';
            $datas['data']->licence_img_back = $deliveryboy->licence_img_back ? url("/assets/images/deliveryboy/$deliveryboy->licence_img_back") : '';

            // echo '<pre>';print_r($data);die();
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $datas
            ], 200);
        }
    }
    public function cust_order_placeing(Request $req) {
        $customer_id = $req->post('customer_id');
        $shop_id = $req->post('shop_id');
        $orderdetails = $req->post('orderdetails');
        $customerdetails = $req->post('customerdetails');
        $shopdetails = $req->post('shopdetails');
        $deliverytype = $req->post('deliverytype');
        $estim_weight = $req->post('estim_weight');
        $estim_price = $req->post('estim_price');
        $distance = $req->post('distance');
        $cust_place = $req->post('cust_place');
        $image1 = $req->file('image1');
        $image2 = $req->file('image2');
        $image3 = $req->file('image3');
        $image4 = $req->file('image4');
        $image5 = $req->file('image5');
        $image6 = $req->file('image6');
        if (empty($customer_id) || empty($shop_id) || empty($orderdetails) || empty($customerdetails) || empty($shopdetails) || empty($deliverytype)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            if ($req->hasFile('image1')) {
                $path = public_path('assets/images/order');
                $filename = file_upload::upload_image($image1, $path);
                $image1 = $filename;
            } else {
                $image1 = '';
            }
            if ($req->hasFile('image2')) {
                $path = public_path('assets/images/order');
                $filename = file_upload::upload_image($image2, $path);
                $image2 = $filename;
            } else {
                $image2 = '';
            }
            if ($req->hasFile('image3')) {
                $path = public_path('assets/images/order');
                $filename = file_upload::upload_image($image3, $path);
                $image3 = $filename;
            } else {
                $image3 = '';
            }
            if ($req->hasFile('image4')) {
                $path = public_path('assets/images/order');
                $filename = file_upload::upload_image($image4, $path);
                $image4 = $filename;
            } else {
                $image4 = '';
            }
            if ($req->hasFile('image5')) {
                $path = public_path('assets/images/order');
                $filename = file_upload::upload_image($image5, $path);
                $image5 = $filename;
            } else {
                $image5 = '';
            }
            if ($req->hasFile('image6')) {
                $path = public_path('assets/images/order');
                $filename = file_upload::upload_image($image6, $path);
                $image6 = $filename;
            } else {
                $image6 = '';
            }
            $last_order = Order::orderBy('id', 'desc')->first();
            if(!empty($last_order)){
                $ordernumber = $last_order->order_number + 1;
            }else{
                $ordernumber = 10000;
            }

            $data = new Order();
            $data->customer_id = $customer_id;
            $data->shop_id = $shop_id;
            $data->order_number = $ordernumber;
            $data->orderdetails = $orderdetails;
            $data->customerdetails = $customerdetails;
            $data->shopdetails = $shopdetails;
            $data->del_type = $deliverytype;
            $data->estim_weight = $estim_weight;
            $data->estim_price = $estim_price;
            $data->date = date('Y-m-d');
            $data->image1 = $image1;
            $data->image2 = $image2;
            $data->image3 = $image3;
            $data->image4 = $image4;
            $data->image5 = $image5;
            $data->image6 = $image6;
            // echo '<pre>';print_r($data);die();
            $data->save();

            $customer = Customer::where('id', $customer_id)->first()->user_id;
            $shop = Shop::where('id', $shop_id)->first()->user_id;

            $shop_contact = Shop::where('id', $shop_id)->first()->contact;

            $send = Pushsms::send_sms($shop_contact, $distance, $cust_place);
            $cust_fcm = User::where('id', $customer)->first()->fcm_token;
            $shop_fcm = User::where('id', $shop)->first()->fcm_token;
            $notif = '' ;
            $notif1 = '' ;
            if (!empty($cust_fcm)) {
                try {
                    $user = User::find($customer);
                    $title = "Order Confirmed!";
                    $message = "Thank you for your order! Our team will arrange collection soon. We’ll keep you updated.";
                    
                    $notification = new FirebaseNotification($title, $message, $cust_fcm);
                    Notification::sendNow($user, $notification);

                    $notification->sendToFirebase();
                    $notif = 'Notification Sent' ;
                } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                    DB::table('failed_jobs')->insert([
                        'connection' => 'cust_order_placeing',
                        'queue' => 'default',
                        'payload' => json_encode($req->all()),
                        'exception' => json_encode($e->getMessage())
                    ]);
                    $notif = 'Notification Not Sent' ;
                }
            } else {
                $notif = 'No FCM Token' ;
            }
            if (!empty($shop_fcm)) {
                try {
                    $user = User::find($shop);
                    $customer = Customer::where('id', $customer_id)->first()->name;
                    $title = $customer." has placed a Scrap Order";
                    $message = "A new scrap materials order, located within ".$distance.", is ready for collection. Please review the details and coordinate the pickup with the customer at ".$cust_place;
                    $notification = new FirebaseNotification($title, $message, $shop_fcm);
                    Notification::sendNow($user, $notification);

                    $notification->sendToFirebase();
                    $notif1 = 'Notification Sent' ;
                } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                    DB::table('failed_jobs')->insert([
                        'connection' => 'cust_order_placeing',
                        'queue' => 'default',
                        'payload' => json_encode($req->all()),
                        'exception' => json_encode($e->getMessage())
                    ]);
                    $notif1 = 'Notification Not Sent' ;
                }
            } else {
                $notif1 = 'No FCM Token';
            }

            $data['customer_notif'] = $notif;
            $data['shop_notif'] = $notif1;
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
        // echo '<pre>';print_r($_FILES); 
        // echo '<pre>';print_r($_POST);die();
    }
    public function order_details($order_no='') {
        if (empty($order_no)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Order::where('order_number', $order_no)->get();
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ]);
        }
        // echo '<pre>';print_r($order_no);die();
    }
    public function category_img_list() {
        $path = public_path('assets/images/appimages/categoryimagesstatic');
        $images = array_map(function ($image) {
            return asset('assets/images/appimages/categoryimagesstatic/' . $image);
        }, array_diff(scandir($path), array('..', '.')));
        $images = array_values($images);
        return response()->json([
            'status' => 'success',
            'msg' => 'Successfull',
            'data' => $images
        ], 200);
        // echo '<pre>';print_r($images);die();
    }
    public function cust_ads_type_edit(Request $req) {
        $customer_id = $req->post('customer_id');
        $address = $req->post('address');
        $building_no = $req->post('building_no');
        $nearby = $req->post('nearby');
        $addres_type = $req->post('addres_type');
        $lat_log = $req->post('lat_log');
        if (empty($customer_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else{
            $customer = Customer::find($customer_id);
            $customer->address = $address ?? $customer->address;
            $customer->building_no = $building_no ?? $customer->building_no;
            $customer->nearby = $nearby ?? $customer->nearby;
            $customer->addres_type = $addres_type ?? $customer->addres_type;
            $customer->lat_log = $lat_log ?? $customer->lat_log;
            $customer->save();
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $customer
            ], 200);
        }
    }
    public function shop_ads_type_edit(Request $req) {
        $shop_id = $req->post('shop_id');
        $address = $req->post('address');
        $lat_log = $req->post('lat_log');
        if (empty($shop_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else{
            $shop = Shop::find($shop_id);
            // echo '<pre>';print_r($shop);die;
            $shop->address = $address ?? $shop->address;
            $shop->lat_log = $lat_log ?? $shop->lat_log;
            $shop->save();
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $shop
            ], 200);
        }
    }
    public function test1(Request $request) {
        $user_id = $request->post('user_id'); 
        if (empty($user_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $user = User::find($user_id);
            $deviceToken = $user ? $user->fcm_token : null;
            if (empty($deviceToken)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'empty Fcm token',
                    'data' => ''
                ], 201);
            } else{
            // $deviceToken = $request->post('deviceToken'); 

                try {
                    $user = User::find($user_id);
                    $title = "New Notification";
                    $message = "You have a new notification!";

                    $notification = new FirebaseNotification($title, $message, $deviceToken);
                    Notification::sendNow($user, $notification);

                    $notification->sendToFirebase();
                } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                    DB::table('failed_jobs')->insert([
                        'connection' => 'users_register',
                        'queue' => 'test1',
                        'payload' => json_encode($req->all()),
                        'exception' => json_encode($e->getMessage())
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'msg' => $e->getMessage(),
                        'data' => ''
                    ], 201);
                }
                

                return response()->json([
                    'status' => 'success',
                'msg' => 'Successfull',
                'data' => ''
                ], 200);
            }
        }
    }
    public function noti_by_id($user_id='', $offset='') {
        if (empty($user_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $limit = 10;
            if (!empty($offset)) {
                $data = Notifications::where('notifiable_id', $user_id)->orderBy('notification_id', 'DESC')->skip($offset*$limit)->take($limit)->get();
            } else{
                $data = Notifications::where('notifiable_id', $user_id)->orderBy('notification_id', 'DESC')->get();
            }
            if (count($data) > 0) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => 'empty data'
                ], 200);
            }
        }
        // echo '<pre>';print_r($order_no);die();
    }
    public function shop_orders($shop_id = '', $status = '', $offset = 0) {
        if (empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 400); 
        }
        // 1 = request pending, 2 = shop accepted, 4 = completed
        $limit = 10;
        $offset = is_numeric($offset) ? (int) $offset : 0;
        $query = Order::where('shop_id', $shop_id)->orderBy('id', 'DESC');
        if (!empty($status)) { 
            $query->where('status', $status);
        }
        if ($offset !== null) { 
            $query->skip($offset * $limit)->take($limit);
        }
        $data = $query->get();
        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Not found',
                'data' => ''
            ], 200);
        }
        foreach ($data as $order) {
            for ($i = 1; $i <= 6; $i++) {
                $imageField = "image$i";
                $order->$imageField = $order->$imageField ? asset("assets/images/order/{$order->$imageField}") : null;
            }
        }
        return response()->json([
            'status' => 'success',
            'msg' => 'Successful',
            'data' => $data
        ], 200);
    }
    

    // public function shop_orders($shop_id='', $status = '', $offset = '') {
    //     if (empty($shop_id)){
    //         return response()->json([
    //             'status' => 'error',
    //             'msg' => 'empty param',
    //             'data' => ''
    //         ], 201);
    //     } else {
    //         // 1 = request pending ,2 =shop accepted ,4= completed
    //         $limit = 10;

    //         $query = Order::where('shop_id', $shop_id)->orderBy('id', 'DESC');
    //         if (!empty($status)) { 
    //             $query->where('status', '=', $status);
    //         }
    //         if (!empty($offset)) { 
    //             $query->skip($offset * $limit)->take($limit);
    //         }
    //         $data = $query->get();
    //         foreach ($data as $key => $order) {
    //             $data[$key]->image1 = $order->image1 ? asset('assets/images/order/' . $order->image1) : null;
    //             $data[$key]->image2 = $order->image2 ? asset('assets/images/order/' . $order->image2) : null;
    //             $data[$key]->image3 = $order->image3 ? asset('assets/images/order/' . $order->image3) : null;
    //             $data[$key]->image4 = $order->image4 ? asset('assets/images/order/' . $order->image4) : null;
    //             $data[$key]->image5 = $order->image5 ? asset('assets/images/order/' . $order->image5) : null;
    //             $data[$key]->image6 = $order->image6 ? asset('assets/images/order/' . $order->image6) : null;
    //         }
    //         if (count($data) > 0) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'msg' => 'Successfull',
    //                 'data' => $data
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'msg' => 'Not found',
    //                 'data' => ''
    //             ], 200);
    //         }
    //     }
    //     // echo '<pre>';print_r($order_no);die();
    // }

    public function customer_pending_orders($customer_id='') {
        if (empty($customer_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Order::where('customer_id', $customer_id)
            ->where(function ($query) {
                $query->where('status', '!=', 4) 
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', 4) 
                                ->whereDate('updated_at', Carbon::today()); 
                    });
            })
            ->orderBy('id', 'DESC')
            ->get();
            // $data = Order::where('customer_id', $customer_id)->where('status', '!=', 4)->orderBy('id', 'DESC')->get();
            foreach ($data as $key => $value) {
                $data[$key]->image1 = !empty($value->image1) ? url('/assets/images/order/' . $value->image1) : '';
                $data[$key]->image2 = !empty($value->image2) ? url('/assets/images/order/' . $value->image2) : '';
                $data[$key]->image3 = !empty($value->image3) ? url('/assets/images/order/' . $value->image3) : '';
                $data[$key]->image4 = !empty($value->image4) ? url('/assets/images/order/' . $value->image4) : '';
                $data[$key]->image5 = !empty($value->image5) ? url('/assets/images/order/' . $value->image5) : '';
                $data[$key]->image6 = !empty($value->image6) ? url('/assets/images/order/' . $value->image6) : '';
            }
            if (count($data) > 0) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => 'empty data'
                ], 200);
            }
        }
        // echo '<pre>';print_r($order_no);die();
    }
    public function customer_orders($customer_id='') {
        if (empty($customer_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Order::where('customer_id', $customer_id)->orderBy('id', 'DESC')->get();
            foreach ($data as $key => $value) {
                $data[$key]->image1 = !empty($value->image1) ? url('/assets/images/order/' . $value->image1) : '';
                $data[$key]->image2 = !empty($value->image2) ? url('/assets/images/order/' . $value->image2) : '';
                $data[$key]->image3 = !empty($value->image3) ? url('/assets/images/order/' . $value->image3) : '';
                $data[$key]->image4 = !empty($value->image4) ? url('/assets/images/order/' . $value->image4) : '';
                $data[$key]->image5 = !empty($value->image5) ? url('/assets/images/order/' . $value->image5) : '';
                $data[$key]->image6 = !empty($value->image6) ? url('/assets/images/order/' . $value->image6) : '';
            }
            if (count($data) > 0) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => 'empty data'
                ], 200);
            }
        }
        // echo '<pre>';print_r($order_no);die();
    }
    public function delv_orders($delv_boy_id='') {
        if (empty($delv_boy_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Order::where('delv_id', $delv_boy_id)->orderBy('id', 'DESC')->get();
            foreach ($data as $key => $value) {
                $data[$key]->image1 = !empty($value->image1) ? url('/assets/images/order/' . $value->image1) : '';
                $data[$key]->image2 = !empty($value->image2) ? url('/assets/images/order/' . $value->image2) : '';
                $data[$key]->image3 = !empty($value->image3) ? url('/assets/images/order/' . $value->image3) : '';
                $data[$key]->image4 = !empty($value->image4) ? url('/assets/images/order/' . $value->image4) : '';
                $data[$key]->image5 = !empty($value->image5) ? url('/assets/images/order/' . $value->image5) : '';
                $data[$key]->image6 = !empty($value->image6) ? url('/assets/images/order/' . $value->image6) : '';
            }
            if (count($data) > 0) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => 'empty data'
                ], 200);
            }
        }
        // echo '<pre>';print_r($order_no);die();
    }

    public function delv_completed_orders($delv_boy_id='') {
        if (empty($delv_boy_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Order::where('delv_id', $delv_boy_id)->where('status', '=', 4)->orderBy('id', 'DESC')->get();
            foreach ($data as $key => $value) {
                $data[$key]->image1 = !empty($value->image1) ? url('/assets/images/order/' . $value->image1) : '';
                $data[$key]->image2 = !empty($value->image2) ? url('/assets/images/order/' . $value->image2) : '';
                $data[$key]->image3 = !empty($value->image3) ? url('/assets/images/order/' . $value->image3) : '';
                $data[$key]->image4 = !empty($value->image4) ? url('/assets/images/order/' . $value->image4) : '';
                $data[$key]->image5 = !empty($value->image5) ? url('/assets/images/order/' . $value->image5) : '';
                $data[$key]->image6 = !empty($value->image6) ? url('/assets/images/order/' . $value->image6) : '';
            }
            if (count($data) > 0) {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => 'empty data'
                ], 200);
            }
        }
        // echo '<pre>';print_r($order_no);die();
    }
    public function notif_read(Request $request) {
        $notification_id = $request->post('notification_id');
        if (empty($notification_id)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Notifications::where('notification_id', $notification_id)->first();
            $data->read_at = $date = date('Y-m-d H:i:s');
            $data->save();
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function order_status_change(Request $request) {
        $order_number = $request->post('order_number');
        $status = $request->post('status');
        $delv_id = $request->post('delv_id');
        $amount = $request->post('amount');
        $quantity = $request->post('quantity');
        if (empty($order_number) || empty($status)){
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = Order::where('order_number', $order_number)->first();
            if ($status == 3) {
                if (empty($delv_id)){
                    $data->delv_id = null;
                } else {
                    $data->delv_id = $delv_id;
                }
            }
            $data->estim_price = $amount ?? $data->estim_price;
            $data->estim_weight = $quantity ?? $data->estim_weight;
            $data->status = $status;
            $data->save();
            
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function custOrderRating(Request $request) 
    {
        $order_number = $request->post('order_number');
        $customer_id = $request->post('customer_id');
        $shop_id = $request->post('shop_id');
        $rating = $request->post('rating');
        $comment = $request->post('comment');
        if (empty($order_number) || empty($customer_id) || empty($rating) || empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = OrderRatings::where('order_number', $order_number)->first();
            if (empty($data)) {
                $data = new OrderRatings;
                $data->order_number = $order_number;
                $data->customer_id = $customer_id;
                $data->shop_id = $shop_id;
            }
            $data->rating = $rating;
            $data->comment = $comment;
            $data->save();
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function shopReviews($shop_id='') {
        if (empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = OrderRatings::where('shop_id', $shop_id)->get();
            if (count($data) > 0) {
                foreach ($data as $key => $value) {
                    $data[$key]->customer_name = Customer::where('id', $value->customer_id)->first()->name;
                    $customer_profile = Customer::where('id', $value->customer_id)->first()->profile_photo; 
                    $data[$key]->customer_profile = !empty($customer_profile) ? url('/assets/images/profile/' . $customer_profile) : NULL;
                }
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => 'No Reviews Found'
                ], 200);
            }
        }
    }
    public function cust_dash_counts($customer_id='') {
        if (empty($customer_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data['total_order_count'] = Order::where('customer_id', $customer_id)->count();
            $data['toatal_order_estimated_amount'] = (int)Order::where('customer_id', $customer_id)->sum('estim_price');
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function delv_boy_dash_counts($delv_boy_id='') {
        if (empty($delv_boy_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data['total_order_count'] = Order::where('delv_id', $delv_boy_id)->count();
            $data['total_pending_order_count'] = Order::where('delv_id', $delv_boy_id)->where('status', 3)->count();
            $data['total_completed_order_count'] = Order::where('delv_id', $delv_boy_id)->where('status', 4)->count();
            $data['toatal_order_estimated_amount'] = (int)Order::where('delv_id', $delv_boy_id)->sum('estim_price');
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $data
            ], 200);
        }
    }
    public function smstesting() {
        $send = Pushsms::testSms();
        if ($send) {
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' => $send
            ], 200);
        }
    }
    public function userProEdit(Request $request) {
        $user_id = $request->post('user_id');
        if (empty($user_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $data = User::where('id', $user_id)->first();
            if (empty($data)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'empty param',
                    'data' => ''
                ], 201);
            } else {
                if (!empty($request->post('email'))) {
                    $email = $request->post('email');
                    if (!empty($email)) {
                        $emailExists = User::where('email', $email)->exists();
                        if ($emailExists) {
                            return response()->json([
                                'status' => 'error',
                                'msg' => 'Email already exists',
                                'data' => ''
                            ], 201);
                        }
                    }
                }
                $data->name = !empty($request->post('name')) ? $request->post('name') : $data->name;
                $data->email = !empty($request->post('email')) ? $request->post('email') : $data->email;
                $data->save();
                if ($data->user_type == 'S') {
                    $shop = Shop::where('user_id', $user_id)->first();
                    $shop->shopname = !empty($request->post('name')) ? $request->post('name') : $shop->shopname;
                    $shop->email = !empty($request->post('email')) ? $request->post('email') : $shop->email;
                    $shop->address = !empty($request->post('address')) ? $request->post('address') : $shop->address;
                    $shop->save();
                } elseif ($data->user_type == 'C') {
                    $customer = Customer::where('user_id', $user_id)->first();
                    $customer->name = !empty($request->post('name')) ? $request->post('name') : $customer->name;
                    $customer->email = !empty($request->post('email')) ? $request->post('email') : $customer->email;
                    $customer->address = !empty($request->post('address')) ? $request->post('address') : $customer->address;
                    $customer->save();
                } elseif ($data->user_type == 'D') {
                    $del_boy = DeliveryBoy::where('user_id', $user_id)->first();
                    $del_boy->name = !empty($request->post('name')) ? $request->post('name') : $del_boy->name;
                    $del_boy->email = !empty($request->post('email')) ? $request->post('email') : $del_boy->email;
                    $del_boy->address = !empty($request->post('address')) ? $request->post('address') : $del_boy->address;
                    $del_boy->save();
                }
                return response()->json([
                    'status' => 'success',
                    'msg' => 'Successfull',
                    'data' => $data
                ], 200);
            }
        }
    }
    public function versionCheck($version='')
	{
        return response()->json([
            'status' => 'success',
            'message' => 'You are on the latest version',
            'data' => 'hide'
        ], 200);

        if (empty($version)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        } else {
            $appVersion = AdminProfile::first()->appVersion;
            $current_version = $appVersion;
            if (version_compare($version, $current_version, '=')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You are on the latest version',
                    'data' => 'hide'
                ], 200);
            } elseif (version_compare($version, $current_version, '<')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'New Version Available',
                    'data' => 'show'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You are on the latest version',
                    'data' => 'hide'
                ], 200);
            }
        }
	}

    public function PermanentDelete(Request $r) {
        $user_id = $r->post('user_id');
        if (empty($user_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        }

        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'msg' => 'User Not Found',
                'data' => ''
            ], 201);
        }
        if ($user->user_type == 'C') {
            Customer::where('user_id', $user_id)->update(['del_status' => 2]);
        }
        if ($user->user_type == 'S') {
            Shop::where('user_id', $user_id)->update(['del_status' => 2]);
        }
        if ($user->user_type == 'D') {
            DeliveryBoy::where('user_id', $user_id)->update(['del_status' => 2]);
        }
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Delete Permanent',
            'data' => ''
        ], 200);

    }

    public function stateAllow(){
        $ip = request()->ip();
        $ip = request()->header('X-Forwarded-For') ?? request()->ip();

        $position = Location::get($ip);
        echo"<pre>"; print_r($position);

    }

    public function savecallLog(Request $r){
        $order_id = $r->post('order_id');
        if (empty($order_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 200);
        }

        $conn = Order::find($order_id);
        if (!$conn) {
            return response()->json(
                [
                    'status' => 'error',
                    'msg' => 'Order Not Found',
                    'data' => ''
                ], 200
            );
        }
        $conn->call_log = 1;
        $conn->save();
        return response()->json(
            [
                'status' => 'success',
                'msg' => 'Successfully updated',
                'data' => ''
            ], 200
        );
    }

    public function savecallLogCust(Request $r){
        $order_id = $r->post('order_id');
        if (empty($order_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 200);
        }

        $conn = Order::find($order_id);
        if (!$conn) {
            return response()->json(
                [
                    'status' => 'error',
                    'msg' => 'Order Not Found',
                    'data' => ''
                ], 200
            );
        }
        $conn->customerCallLog = 1;
        $conn->save();
        return response()->json(
            [
                'status' => 'success',
                'msg' => 'Successfully updated',
                'data' => ''
            ], 200
        );
    }

    public function thirdPartyCredentials()
    {
        $data['google_api_key'] = env('APP_GOOGLE_API_KEY');
        return response()->json([
            'status' => 'success',
            'msg' => 'Successfull',
            'data' =>  $data
        ], 200);
    }

    public function searchShopCallLogSave(Request $r) {
        $user_id = $r->post('user_id');
        $shop_id = $r->post('shop_id');
        if (empty($user_id) || empty($shop_id)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 200);
        }

        $conn = new CallLog;
        $conn->user_id = $user_id;
        $conn->shop_id = $shop_id;
        $conn->save();

        return response()->json([
            'status' => 'success',
            'msg' => 'Successfull',
            'data' =>  ''
        ], 200);
    }

    public function failedJobs(Request $r){
        DB::table('failed_jobs')->insert([
            'connection' => $r->connection,
            'queue' => $r->queue,
            'payload' => json_encode($r->payload),
            'exception' => json_encode($r->exception)
        ]);
    }

    public function packagesSub(){
        $data = Package::where('status', 1)->where('type',2)->get();
        if (count($data) > 0) {
            return response()->json([
                'status' => 'success',
                'msg' => 'Successfull',
                'data' =>  $data
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'msg' => 'No Data Found',
                'data' =>  ''
            ], 200);
        }
    }

    // public function saveUserPackages(Request $request) 
    // {
    //     $user_id = $request->post('user_id');
    //     $package_id = $request->post('package_id');
    //     $payment_moj_id = $request->post('payment_moj_id');
    //     $payment_req_id = $request->post('payment_req_id');
    //     $pay_details = $request->post('pay_details');

    //     if (empty($user_id) || empty($package_id) || empty($payment_moj_id) || empty($payment_req_id) || empty($pay_details)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'msg' => 'empty param',
    //             'data' => ''
    //         ], 201);
    //     } else {
    //         $package = Package::find($package_id);
    //         $type = $package->type == 1 ? 'Free' : ($package->type == 2 ? 'Paid' : '');
    //         if (empty($package)) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'msg' => 'Package Not Found',
    //                 'data' => ''
    //             ], 201);
    //         } else {
    //             $invoice = new Invoice;
    //             $invoice->user_id = $user_id;
    //             $invoice->payment_moj_id = $payment_moj_id;
    //             $invoice->payment_req_id = $payment_req_id;
    //             $invoice->name = $package->name;
    //             $invoice->displayname = $package->displayname;
    //             $invoice->type = $type;
    //             $invoice->duration = $package->duration;
    //             $invoice->price = $package->price;
    //             $invoice->pay_details = $pay_details;
    //             $invoice->from_date = date('Y-m-d');
    //             $invoice->to_date = date('Y-m-d', strtotime('+'.$package->duration.' days'));
    //             $invoice->save();
    //             return response()->json([
    //                 'status' => 'success',
    //                 'msg' => 'Successfull',
    //                 'data' =>  ''
    //             ], 200);
    //         }
    //     }
    // }

    public function saveUserPackages(Request $request) 
    {
        $user_id = $request->post('user_id');
        $package_id = $request->post('package_id');
        $payment_moj_id = $request->post('payment_moj_id');
        $payment_req_id = $request->post('payment_req_id');
        $pay_details = $request->post('pay_details');

        if (empty($user_id) || empty($package_id) || empty($payment_moj_id) || empty($payment_req_id) || empty($pay_details)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'empty param',
                'data' => ''
            ], 201);
        }

        $package = Package::find($package_id);
        if (empty($package)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Package Not Found',
                'data' => ''
            ], 201);
        }

        $type = $package->type == 1 ? 'Free' : ($package->type == 2 ? 'Paid' : '');
        
        // Check if user has any active invoices
        $latestInvoice = Invoice::where('user_id', $user_id)
            ->where('to_date', '>=', date('Y-m-d'))
            ->orderBy('to_date', 'desc')
            ->first();

        $fromDate = date('Y-m-d');
        
        // If there's an active invoice, use its to_date as the new from_date
        if ($latestInvoice) {
            $fromDate = $latestInvoice->to_date;
        }

        $toDate = date('Y-m-d', strtotime('+'.$package->duration.' days', strtotime($fromDate)));

        $invoice = new Invoice;
        $invoice->user_id = $user_id;
        $invoice->payment_moj_id = $payment_moj_id;
        $invoice->payment_req_id = $payment_req_id;
        $invoice->name = $package->name;
        $invoice->displayname = $package->displayname;
        $invoice->type = $type;
        $invoice->duration = $package->duration;
        $invoice->price = $package->price;
        $invoice->pay_details = $pay_details;
        $invoice->from_date = $fromDate;
        $invoice->to_date = $toDate;
        $invoice->save();

        return response()->json([
            'status' => 'success',
            'msg' => 'Successful',
            'data' => ''
        ], 200);
    }

    public function paymentHistory(Request $r) {
        $user_id = $r->post('user_id');
        $offset = $r->post('offset', 0);
        $limit = $r->post('limit', 10);
        $invoices = Invoice::select('id','payment_moj_id', 'payment_req_id', 'from_date', 'to_date', 'name', 'displayname', 'type', 'duration', 'price')
            ->where('user_id', $user_id)
            ->where('type','Paid')
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();
        $total_count = Invoice::where('user_id', $user_id)->count();
        return response()->json([
            'status' => 'success',
            'msg' => 'Successful',
            'count' => $total_count,
            'data' => $invoices
        ], 200);
    }
}

