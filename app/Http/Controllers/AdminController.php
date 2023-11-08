<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TempCustomer;
use App\Models\OrderChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Order_file_upload;




class AdminController extends Controller
{
    public function adminloginpage()
    {
        return view('admin.login');
    }

    public function adminLogin(Request $request)
    {

        $request->validate([
            'email' => 'required',
            'password' => 'required',

        ]);
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            if (Auth::user()->user_type == "admin") {
                return redirect('/');
                // return redirect()->route('admin-vieworders', app()->getLocale())->with('success', 'You have successfully logged in');
            } else {
                Auth::logout();
                return redirect(__('routes.admin-login'))->with('danger', 'Oops! You do not have the required access permission');
            }
        }
        return redirect(__('routes.admin-login'))->with('danger', 'Oppes! You have entered invalid credentials');
    }

    public function goAdminLogin()
    {
        return redirect()->route('admin-login', ['locale' => app()->getLocale()]);
    }

    public function adminLogout()
    {
        Session::flush();
        Auth::logout();

        return Redirect(__('routes.admin-login'));
    }

    public function viewOrders(Request $request)
    {

        $authuser = auth()->user()->id;
        if ($request->status_filter != '') {
            if ($request->status_filter == 'pending') {
                $data = Order::with('Order_address', 'category', 'user')->orderBy('id', 'desc')->where('status', 'pending')->get();
            }
            if ($request->status_filter == 'delivered') {
                $data = Order::with('Order_address', 'category', 'user')->orderBy('id', 'desc')->where('status', 'delivered')->get();
            }
            return Datatables::of($data)->addIndexColumn()
                ->editColumn('catgory', function ($row) {
                    $category = $row->category->category_name;
                    return $category;
                })
                ->editColumn('user', function ($row) {
                    $user = $row->user->name;
                    return $user;
                })
                ->editColumn('date', function ($row) {
                    $date = $row->created_at->format('Y-m-d');
                    return $date;
                })
                ->editColumn('selection', function ($row) {
                    $date = __($row->selection);
                    return $date;
                })
                ->addColumn('action', function ($row) {

                    $btn = '<div class="d-flex" style="gap:20px;">
                                    <div><a class="btn btn-secondary btn-sm" href=' . __("routes.admin-orderdetails") . $row->id . '>Order detail</a></div>
                                </div>';
                    return $btn;
                })
                ->rawColumns(['catgory', 'date', 'user', 'action', 'selection'])
                ->make(true);
        } else {
            if ($request->ajax()) {
                $data = Order::with('Order_address', 'category', 'user')->orderBy('id', 'desc')->get();
                return Datatables::of($data)->addIndexColumn()
                    ->editColumn('catgory', function ($row) {
                        $category = $row->category->category_name;
                        return $category;
                    })
                    ->editColumn('user', function ($row) {
                        $user = $row->user->name;
                        return $user;
                    })
                    ->editColumn('date', function ($row) {
                        $date = $row->created_at->format('Y-m-d');
                        return $date;
                    })
                    ->editColumn('selection', function ($row) {
                        $date = __($row->selection);
                        return $date;
                    })
                    ->addColumn('action', function ($row) {

                        $btn = '<div class="d-flex" style="gap:20px;">
                                    <div><a class="btn btn-secondary btn-sm" href=' . __("routes.admin-orderdetails") . $row->id . '>Order detail</a></div>
                                </div>';
                        return $btn;
                    })
                    ->rawColumns(['catgory', 'date', 'user', 'action', 'selection'])
                    ->make(true);
            }
        }
        return view('admin.orders.vieworders');
    }

    public function changePassword()
    {
        return view('admin.changepassword');
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'oldpassword' => 'required',
            'newpassword' => 'required',
            'password_confirmation' => 'required|same:newpassword'
        ]);

        $hashedPassword = Auth::user()->password;
        if (Hash::check($request->oldpassword, $hashedPassword)) {

            $users = User::find(Auth::user()->id);
            $users->password = bcrypt($request->newpassword);
            $users->save();
            session()->flash('message', 'password updated successfully');
            return redirect(__('/'));
        } else {
            session()->flash('message', 'old password does not matched');
            return redirect(__('routes.admin-changepassword'));
        }
    }

    public function adminProfile()
    {
        $authuser = auth()->user()->id;
        $user = User::where('id', $authuser)->first();
        return view('admin.profile.profile', compact('user'));
    }
    public function profileUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('sidebar', true);
        }
        $user = auth()->user();
        $address = $request->address;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '-' . $file->getClientOriginalName();
            $destination = $user->id . '-profile' . $filename;
            $path = Storage::disk('s3')->put($destination, file_get_contents($file));
            $imageUrl = Storage::disk('s3')->url($destination);
            $user->image = $imageUrl;
        }
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->contact_no = $request->input('number');
        $user->address = $address;
        $user->save();
        return redirect()->back()->with('success', 'Profile updated successfully!')->with('sidebar', true);
    }
    public function orderDetails($locale, $id)
    {
        $order = Order::with('Order_address', 'Orderfile_uploads', 'Orderfile_formats')->where('id', $id)->first();
        return view('common.orderdetails', compact('order'));
    }


    public function AdminviewOrders(Request $request)
    {
        $authuser = auth()->user()->id;
        if ($request->status_filter != '') {
            if ($request->status_filter == 'pending') {
                $data = Order::with('Order_address', 'category', 'user')->orderBy('id', 'desc')->where('status', 'pending')->get();
            }
            if ($request->status_filter == 'delivered') {
                $data = Order::with('Order_address', 'category', 'user')->orderBy('id', 'desc')->where('status', 'delivered')->get();
            }
            return Datatables::of($data)->addIndexColumn()
                ->editColumn('catgory', function ($row) {
                    $category = $row->category->category_name;
                    return $category;
                })
                ->editColumn('user', function ($row) {
                    $user = $row->user->name;
                    return $user;
                })
                ->editColumn('date', function ($row) {
                    $date = $row->created_at->format('Y-m-d');
                    return $date;
                })
                ->editColumn('selection', function ($row) {
                    $date = __($row->selection);
                    return $date;
                })
                ->addColumn('action', function ($row) {

                    $btn = '<div class="d-flex" style="gap:20px;">
                                    <div><a class="btn btn-secondary btn-sm" href=' . __("routes.admin-orderdetails") . $row->id . '>Order detail</a></div>
                                </div>';
                    return $btn;
                })
                ->rawColumns(['catgory', 'date', 'user', 'action', 'selection'])
                ->make(true);
        } else {
            if ($request->ajax()) {
                $data = Order::with('Order_address', 'category', 'user')->orderBy('id', 'desc')->get();
                return Datatables::of($data)->addIndexColumn()
                    ->editColumn('catgory', function ($row) {
                        $category = $row->category->category_name;
                        return $category;
                    })
                    ->editColumn('user', function ($row) {
                        $user = $row->user->name;
                        return $user;
                    })
                    ->editColumn('date', function ($row) {
                        $date = $row->created_at->format('Y-m-d');
                        return $date;
                    })
                    ->editColumn('selection', function ($row) {
                        $date = __($row->selection);
                        return $date;
                    })
                    ->addColumn('action', function ($row) {

                        $btn = '<div class="d-flex" style="gap:20px;">
                                    <div><a class="btn btn-secondary btn-sm" href=' . __("routes.admin-orderdetails") . $row->id . '>Order detail</a></div>
                                </div>';
                        return $btn;
                    })
                    ->rawColumns(['catgory', 'date', 'user', 'action', 'selection'])
                    ->make(true);
            }
        }
        return response()->json([
            'messgae' => 'done'
        ]);
    }

    public function CustomerList(Request $request)
    {
        if ($request->ajax()) {
            $data = User::orderBy('id', 'desc')->where('user_type', 'customer')->get();
            $temp_data = TempCustomer::orderBy('id', 'desc')->get();
            // $order_changes = OrderChange::orderBy('id', 'desc')->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('edit', function ($row) {
                    $edit = '
                        <div class="d-flex" style="gap:20px;">
                            <div style="display: flex; margin:auto;">
                                <button onclick="editCustomerProfile(' . $row->id . ')" style="border:none; background-color:inherit;"><img src="' . asset('asset/images/DetailIcon.svg') . '" alt="order-detail-icon" ></button>
                            </div>
                        </div>';
                    return $edit;
                })
                ->addColumn('request', function ($row) use ($temp_data) {
                    $req = '';
                    foreach ($temp_data as $temp) {
                        if ($temp->customer_id == $row->id) {
                            $req = '
                                <div class="d-flex" style="gap:20px;">
                                    <div style="display: flex; margin:auto;">
                                        <button onclick="HandleProfileRequest(' . $row->id . ')" style="border:none; background-color:inherit;"><i class="fa-solid fa-exclamation blink" style="color:#ff0000; transform:scale(2,1);"></i></button>
                                    </div>
                                </div>
                            ';
                        }
                    }
                    // foreach ($order_changes as $order_change) {
                    //     if ($order_change->customer_id == $row->id) {
                    //         $req = '
                    //             <div class="d-flex" style="gap:20px;">
                    //                 <div style="display: flex; margin:auto;">
                    //                     <button onclick="HandleProfileRequest(' . $row->id . ')" style="border:none; background-color:inherit;"><i class="fa-solid fa-exclamation blink" style="color:#ff0000; transform:scale(2,1);"></i></button>
                    //                 </div>
                    //             </div>
                    //         ';
                    //     }
                    // }
                    return $req;
                })
                ->rawColumns(['edit', 'request'])
                ->make(true);
        }
    }

    public function getDifferences($locale, $id)
    {
        $user = User::findOrFail($id);
        $tempCustomer = TempCustomer::where('customer_id', $id)->orderBy('id', 'desc')->first();
        // $order_changes = OrderChange::where('customer_id', $id)->orderBy('id', 'desc')->get();

        $responseText = '';
        $count = 0;

        // foreach ($order_changes as $order_change) {
        //     $responseText .= '"' . $order_change["customer_name"] . '" hat eine Nachricht zu Bestellnummer ' . $order_change['order_number'] . ' gesendet: „' . $order_change['message'] . '“<br />';
        // }

        if ($tempCustomer) {
            if ($user['name'] != $tempCustomer['name']) {
                $responseText .= 'Der Kundin hat eine Änderung des "name" von "' . $user['name'] . '" in "' . $tempCustomer['name'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['first_name'] != $tempCustomer['first_name']) {
                $responseText .= 'Der Kundin hat eine Änderung des "first_name" von "' . $user['first_name'] . '" in "' . $tempCustomer['first_name'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['email'] != $tempCustomer['email']) {
                $responseText .= 'Der Kundin hat eine Änderung des "email" von "' . $user['email'] . '" in "' . $tempCustomer['email'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['company'] != $tempCustomer['company']) {
                $responseText .= 'Der Kundin hat eine Änderung des "company" von "' . $user['company'] . '" in "' . $tempCustomer['company'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['company_addition'] != $tempCustomer['company_addition']) {
                $responseText .= 'Der Kundin hat eine Änderung des "company_addition" von "' . $user['company_addition'] . '" in "' . $tempCustomer['company_addition'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['street_number'] != $tempCustomer['street_number']) {
                $responseText .= 'Der Kundin hat eine Änderung des "street_number" von "' . $user['street_number'] . '" in "' . $tempCustomer['street_number'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['postal_code'] != $tempCustomer['postal_code']) {
                $responseText .= 'Der Kundin hat eine Änderung des "postal_code" von "' . $user['postal_code'] . '" in "' . $tempCustomer['postal_code'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['location'] != $tempCustomer['location']) {
                $responseText .= 'Der Kundin hat eine Änderung des "location" von "' . $user['location'] . '" in "' . $tempCustomer['location'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['country'] != $tempCustomer['country']) {
                $responseText .= 'Der Kundin hat eine Änderung des "country" von "' . $user['country'] . '" in "' . $tempCustomer['country'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['website'] != $tempCustomer['website']) {
                $responseText .= 'Der Kundin hat eine Änderung des "websitewebsite" von "' . $user['name'] . '" in "' . $tempCustomer['name'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['phone'] != $tempCustomer['phone']) {
                $responseText .= 'Der Kundin hat eine Änderung des "phone" von "' . $user['phone'] . '" in "' . $tempCustomer['phone'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['mobile'] != $tempCustomer['mobile']) {
                $responseText .= 'Der Kundin hat eine Änderung des "mobile" von "' . $user['mobile'] . '" in "' . $tempCustomer['mobile'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['tax_number'] != $tempCustomer['tax_number']) {
                $responseText .= 'Der Kundin hat eine Änderung des "tax_number" von "' . $user['tax_number'] . '" in "' . $tempCustomer['tax_number'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['vat_number'] != $tempCustomer['vat_number']) {
                $responseText .= 'Der Kundin hat eine Änderung des "vat_number" von "' . $user['vat_number'] . '" in "' . $tempCustomer['vat_number'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['register_number'] != $tempCustomer['register_number']) {
                $responseText .= 'Der Kundin hat eine Änderung des "register_number" von "' . $user['register_number'] . '" in "' . $tempCustomer['register_number'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['kd_group'] != $tempCustomer['kd_group']) {
                $responseText .= 'Der Kundin hat eine Änderung des "kd_group" von "' . $user['kd_group'] . '" in "' . $tempCustomer['kd_group'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['kd_category'] != $tempCustomer['kd_category']) {
                $responseText .= 'Der Kundin hat eine Änderung des "kd_category" von "' . $user['kd_category'] . '" in "' . $tempCustomer['kd_category'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['payment_method'] != $tempCustomer['payment_method']) {
                $responseText .= 'Der Kundin hat eine Änderung des "payment_method" von "' . $user['payment_method'] . '" in "' . $tempCustomer['payment_method'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['bank_name'] != $tempCustomer['bank_name']) {
                $responseText .= 'Der Kundin hat eine Änderung des "bank_name" von "' . $user['bank_name'] . '" in "' . $tempCustomer['bank_name'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['IBAN'] != $tempCustomer['IBAN']) {
                $responseText .= 'Der Kundin hat eine Änderung des "IBAN" von "' . $user['IBAN'] . '" in "' . $tempCustomer['IBAN'] . '" angefordert.<br />';
                $count++;
            }
            if ($user['BIC'] != $tempCustomer['BIC']) {
                $responseText .= 'Der Kundin hat eine Änderung des "BIC" von "' . $user['BIC'] . '" in "' . $tempCustomer['BIC'] . '" angefordert.<br />';
                $count++;
            }
        }
        $responseText = substr($responseText, 0, strlen($responseText) - 2);

        echo $responseText;
    }

    public function acceptChangeRequest(Request $request)
    {
        $user = User::findOrFail($request->post('id'));
        $tempCustomer = TempCustomer::where('customer_id', $request->post('id'))->orderBy('id', 'desc')->first();
        $user->name = $request->post('name');
        $user->first_name = $request->post('first_name');
        $user->email = $request->post('email');
        $user->company = $request->post('company');
        $user->company_addition = $request->post('company_addition');
        $user->street_number = $request->post('street_number');
        $user->postal_code = $request->post('postal_code');
        $user->location = $request->post('location');
        $user->country = $request->post('country');
        $user->website = $request->post('website');
        $user->phone = $request->post('phone');
        $user->mobile = $request->post('mobile');
        $user->tax_number = $request->post('tax_number');
        $user->vat_number = $request->post('vat_number');
        $user->register_number = $request->post('register_number');
        $user->kd_group = $request->post('kd_group');
        $user->kd_category = $request->post('kd_category');
        $user->payment_method = $request->post('payment_method');
        $user->bank_name = $request->post('bank_name');
        $user->IBAN = $request->post('IBAN');
        $user->BIC = $request->post('BIC');
        $user->save();
        $tempCustomer->delete();
        return 'successfully changed!';
    }
    public function declineChangeRequest($locale, $id)
    {
        $user = TempCustomer::where('customer_id', $id)->orderBy('id', 'desc')->first();
        $user->delete();
    }
    public function GetCustomerProfile(Request $request)
    {
        $profile = User::findOrfail($request->get('id'));
        $temp = TempCustomer::where('customer_id', $request->get('id'))->first();
        return response()->json(['profile' => $profile, 'temp' => $temp]);
    }

    public function ChangeProfile(Request $request)
    {
        $id = $request->post('id');
        $customer_number = $request->post('customer_number');
        $name = $request->post('name');
        $first_name = $request->post('first_name');
        $email = $request->post('email');
        $company = $request->post('company');
        $company_addition = $request->post('company_addition');
        $street_number = $request->post('street_number');
        $postal_code = $request->post('postal_code');
        $location = $request->post('location');
        $country = $request->post('country');
        $website = $request->post('website');
        $phone = $request->post('phone');
        $mobile = $request->post('mobile');
        $tax_number = $request->post('tax_number');
        $vat_number = $request->post('vat_number');
        $register_number = $request->post('register_number');
        $kd_group = $request->post('kd_group');
        $kd_category = $request->post('kd_category');
        $payment_method = $request->post('payment_method');
        $bank_name = $request->post('bank_name');
        $IBAN = $request->post('IBAN');
        $BIC = $request->post('BIC');

        $data = User::where('id', $id)->first();
        $data->customer_number = $customer_number;
        $data->name = $name;
        $data->first_name = $first_name;
        $data->email = $email;
        $data->company = $company;
        $data->company_addition = $company_addition;
        $data->street_number = $street_number;
        $data->postal_code = $postal_code;
        $data->location = $location;
        $data->country = $country;
        $data->website = $website;
        $data->phone = $phone;
        $data->mobile = $mobile;
        $data->tax_number = $tax_number;
        $data->vat_number = $vat_number;
        $data->register_number = $register_number;
        $data->kd_group = $kd_group;
        $data->kd_category = $kd_category;
        $data->payment_method = $payment_method;
        $data->bank_name = $bank_name;
        $data->IBAN = $IBAN;
        $data->BIC = $BIC;
        $data->save();
    }
    public function AddCustomer(Request $request)
    {
        $customer_number = $request->post('customer_number');
        $name = $request->post('name');
        $first_name = $request->post('first_name');
        $company = $request->post('company');
        $company_addition = $request->post('company_addition');
        $street_number = $request->post('street_number');
        $postal_code = $request->post('postal_code');
        $location = $request->post('location');
        $country = $request->post('country');
        $website = $request->post('website');
        $phone = $request->post('phone');
        $mobile = $request->post('mobile');
        $tax_number = $request->post('tax_number');
        $vat_number = $request->post('vat_number');
        $register_number = $request->post('register_number');
        $kd_group = $request->post('kd_group');
        $kd_category = $request->post('kd_category');
        $payment_method = $request->post('payment_method');
        $bank_name = $request->post('bank_name');
        $IBAN = $request->post('IBAN');
        $BIC = $request->post('BIC');
        $email = $request->post('email');
        $password = $request->post('password');

        $add_customer = User::create([
            'customer_number' => $customer_number,
            'name' => $name,
            'first_name' => $first_name,
            'company' => $company,
            'company_addition' => $company_addition,
            'street_number' => $street_number,
            'postal_code' => $postal_code,
            'location' => $location,
            'country' => $country,
            'website' => $website,
            'phone' => $phone,
            'mobile' => $mobile,
            'tax_number' => $tax_number,
            'vat_number' => $vat_number,
            'register_number' => $register_number,
            'kd_group' => $kd_group,
            'kd_category' => $kd_category,
            'payment_method' => $payment_method,
            'bank_name' => $bank_name,
            'IBAN' => $IBAN,
            'BIC' => $BIC,
            'email' => $email,
            'password' => Hash::make($password),
            'user_type' => 'customer',
            'image' => 'https://upload-files-cos.s3.amazonaws.com/6-profile1693225145-1692011047-2021-10-20.jpg',
        ]);
        $add_customer->save();
    }
    public function CustomerSearchTable(Request $request)
    {
        $customers = User::orderBy('id', 'desc')->where('user_type', 'customer')
            ->where(function ($query) use ($request) {
                $query->where('customer_number', 'LIKE', '%' . $request->search_filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->search_filter . '%')
                    ->orWhere('company', 'LIKE', '%' . $request->search_filter . '%')
                    ->orWhere('postal_code', 'LIKE', '%' . $request->search_filter . '%');
            })->get();
        $html = '';
        if (count($customers) > 0) {
            foreach ($customers as $item) {
                $html .= '<tr><td>' . $item->customer_number . '</td>' .
                    '<td>' . $item->company . '</td>' .
                    '<td>' . $item->name . '</td>' .
                    '<td>' . $item->first_name . '</td>' .
                    '<td>' . $item->phone . '</td>' .
                    '<td>' . $item->email . '</td>' .
                    '<td>' . $item->street_number . '</td>' .
                    '<td>' . $item->postal_code . '</td>' .
                    '<td>' . $item->location . '</td>' .
                    '<td>' . $item->country . '</td></tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" class="text-center">No data</td></tr>';
        }
        $data['id'] = count($customers) > 0 ? $customers[0]->id : null;
        $data['customer_number'] = count($customers) > 0 ? $customers[0]->customer_number : null;
        $data['company'] = count($customers) > 0 ? $customers[0]->company : null;
        $data['ordered_from'] = count($customers) > 0 ? $customers[0]->name : null;
        $data['first_name'] = count($customers) > 0 ? $customers[0]->first_name : null;
        $data['phone'] = count($customers) > 0 ? $customers[0]->phone : null;
        $data['email'] = count($customers) > 0 ? $customers[0]->email : null;
        $data['street_number'] = count($customers) > 0 ? $customers[0]->street_number : null;
        $data['postal_code'] = count($customers) > 0 ? $customers[0]->postal_code : null;
        $data['location'] = count($customers) > 0 ? $customers[0]->location : null;
        $data['country'] = count($customers) > 0 ? $customers[0]->country : null;
        $data['html'] = $html;
        echo json_encode($data);
    }
    public function adminFileUpload(Request $request)
    {
        $type = $request->post('type');
        $deliver_time = $request->post('deliver_time');
        $project_name = $request->post('project_name');
        $size = $request->post('size');
        $width_height = $request->post('width_height');
        $products = $request->post('products');
        $special_instructions = $request->post('special_instructions');
        $customer_number = $request->post('customer_number');
        $searched_id = $request->post('searched_id');
        $last_order = Order::orderBy('order_number', 'desc')->first();

        $order = Order::where('type', $type)
            ->where('project_name', $project_name)
            ->where('size', $size)
            ->where('deliver_time', $deliver_time)
            ->where('width_height', $width_height)
            ->where('products', $products)
            ->where('special_instructions', $special_instructions)->first();

        if ($order == null) {
            $order = new Order();
            $order->customer_number = $customer_number;
            $order->order_number = $last_order == null ? '0001' : sprintf('%04s', $last_order->order_number + 1);
            $order->project_name = $project_name;
            $order->ordered_from = "Lion Werbe GmbH";
            $order->status = 'Offen';
            $order->type = $type;
            $order->size = $size;
            $order->deliver_time = $deliver_time;
            $order->width_height = $width_height;
            $order->products = $products;
            $order->special_instructions = $special_instructions;
            $order->category_id = 1;
            $order->user_id = $searched_id;
            $order->assigned_to = 4;
            $order->org_id = $searched_id;
            $order->save();
        }

        $files = $request->file("files");
        $uploadDir = 'public/';
        $filePath = $order->customer_number . '/' .
            $order->customer_number . '-' . $order->order_number . '-' . $order->project_name . '/Originaldatei/';
        $path = $uploadDir . $filePath;
        foreach ($files as $key => $file) {
            // Check whether the current entity is an actual file or a folder (With a . for a name)
            if (strlen($file->getClientOriginalName()) != 1) {
                Storage::makeDirectory($uploadDir);
                $fileName = $order->customer_number . '-' . $order->order_number . '-' . ($key + 1) . '.' . $file->getClientOriginalExtension();
                $exist_file = Order_file_upload::where('base_url', 'LIKE', 'storage/' . $filePath . '%')->orderBy('base_url', 'desc')->first();
                if ($exist_file != null) {
                    $filePathArray = explode('/', $exist_file->base_url);
                    $fileNameArray = explode('-', $filePathArray[4]);
                    $fileExtensionArray = explode('.', $fileNameArray[2]);
                    $index = $fileExtensionArray[0];
                    $index = $index + 1;
                    $fileName = $order->customer_number . '-' . $order->order_number . '-' . $index . '.' . $file->getClientOriginalExtension();
                    if ($file->storePubliclyAs($path, $fileName)) {
                        $order_file_upload = new Order_file_upload();
                        $order_file_upload->order_id = $order->id;
                        $order_file_upload->index = $index;
                        $order_file_upload->extension = $file->getClientOriginalExtension();
                        $order_file_upload->base_url = 'storage/' . $filePath . $fileName;
                        $order_file_upload->save();
                        echo "The file " . $fileName . " has been uploaded";
                    } else
                        echo "Error";
                } else {
                    if ($file->storePubliclyAs($path, $fileName)) {
                        $order_file_upload = new Order_file_upload();
                        $order_file_upload->order_id = $order->id;
                        $order_file_upload->index = $key + 1;
                        $order_file_upload->extension = $file->getClientOriginalExtension();
                        $order_file_upload->base_url = 'storage/' . $filePath . $fileName;
                        $order_file_upload->save();
                        echo "The file " . $fileName . " has been uploaded";
                    } else
                        echo "Error";
                }

            }
        }
        return "OK!";
    }
    public function ConfirmProfile(Request $request)
    {
        $id = $request->post('admin_confirm_profile_id');
        $customer_number = $request->post('customer_number');
        $user = User::findOrfail($id);
        $user->customer_number = $customer_number;
        $user->save();
    }
    public function DeclineProfile(Request $request)
    {
        $id = $request->post('admin_decline_profile_id');
        User::findOrfail($id)->delete();
    }
}