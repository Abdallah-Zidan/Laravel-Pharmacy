<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Medicine;
use App\Order;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('order.create');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::all();
        $statuses= ['Confirmed'=>'4','Delivered'=>'5'];
        return view('order.create',[
            'users'=>$users,
            'statuses' =>$statuses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $order_params = $request->only([
            'order_user_id',
            'is_insured',
            'status_id'
        ]);
        $order_params['delivering_address_id'] = User::find($request['order_user_id'])
                                                    ->addresses()
                                                    ->where('is_main', '1')
                                                    ->first()->id;
        if (Auth::guard('admin')->check()) {
            $order_params['creator_type'] = 'admin';
            $order_params['doctor_id'] = null;
            $order_params['pharamcy_id'] = null;
        } else if (Auth::guard('pharmacy')->check()) {
            $order_params['creator_type'] = 'pharmacy';
            $order_params['doctor_id'] = null;
            $order_params['pharamcy_id'] = Auth::user()->id;
        } else if (Auth::guard('doctor')->check()) {
            $order_params['creator_type'] = 'doctor';
            $order_params['pharamcy_id'] = null;
            $order_params['doctor_id'] = Auth::user()->id;
        }
        $order_params['total_price'] = 0;
        $order = Order::create($order_params);
        $total_price = 0;
        for ($i = 0; $i < count($request['med_name']); $i++) {
            $name = $request['med_name'][$i];
            $type = $request['med_name'][$i];
            if (!$medicine=Medicine::where(['name' => $name, 'type' => $type])->get()->first()) {
                $medicine = Medicine::create(['name' => $name, 'type' => $type]);
                $medicine->orders()->attach($order, ['quantity' => $request['med_quantity'][$i], 'price' => $request['med_price'][$i]]);
                $total_price += $request['med_price'][$i] * $request['med_quantity'][$i];
            } else {
                $medicine->orders()->attach($order, ['quantity' => $request['med_quantity'][$i], 'price' => $request['med_price'][$i]]);
                $total_price += $request['med_price'][$i] * $request['med_quantity'][$i];
            }
        }
        $order->update(['total_price' => $total_price]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
