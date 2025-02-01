<?php

namespace App\Http\Controllers;

use App\Utility\PayfastUtility;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\City;
use App\Models\CombinedOrder;
use App\Models\Country;
use App\Models\Product;
use App\Models\State;
use App\Utility\PayhereUtility;
use App\Utility\NotificationUtility;
use Session;
use Auth;
use Spatie\GoogleTagManager\GoogleTagManagerFacade;

class CheckoutController extends Controller
{

    public function __construct()
    {
        //
    }

    private function cart() {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::user()->id);
        }

        return Cart::where('temp_user_id', request()->session()->get('temp_user_id'));
    }

    //check the selected payment gateway and redirect to that controller accordingly
    public function checkout(Request $request)
    {
        // Minumum order amount check
        if(get_setting('minimum_order_amount_check') == 1){
            $subtotal = 0;
            foreach ($this->cart()->get() as $key => $cartItem){ 
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                flash(translate('You order amount is less then the minimum order amount'))->warning();
                return redirect()->route('home');
            }
        }
        // Minumum order amount check end
        
        if ($request->payment_option != null) {
            (new OrderController)->store($request);

            $request->session()->put('payment_type', 'cart_payment');
            
            $data['combined_order_id'] = $request->session()->get('combined_order_id');
            $request->session()->put('payment_data', $data);

            if ($request->session()->get('combined_order_id') != null) {

                // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
                $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
                if (class_exists($decorator)) {
                    return (new $decorator)->pay($request);
                }
                else {
                    $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));
                    $manual_payment_data = array(
                        'name'   => $request->payment_option,
                        'amount' => $combined_order->grand_total,
                        'trx_id' => $request->trx_id,
                        'photo'  => $request->photo
                    );
                    foreach ($combined_order->orders as $order) {
                        $order->manual_payment = 1;
                        $order->manual_payment_data = json_encode($manual_payment_data);
                        $order->save();
                    }
                    flash(translate('Your order has been placed successfully. Please submit payment information from purchase history'))->success();
                    return redirect()->route('order_confirmed');
                }
            }
        } else {
            flash(translate('Select Payment Option.'))->warning();
            return back();
        }
    }

    //redirects to this method after a successfull checkout
    public function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order = Order::findOrFail($order->id);
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            calculateCommissionAffilationClubPoint($order);
        }
        Session::put('combined_order_id', $combined_order_id);
        return redirect()->route('order_confirmed');
    }

    public function get_shipping_info(Request $request)
    {
        $carts = $this->cart()->get();
//        if (Session::has('cart') && count(Session::get('cart')) > 0) {
        if ($carts && count($carts) > 0) {
            $categories = Category::all();
            return view('frontend.shipping_info', compact('categories', 'carts'));
        }
        flash(translate('Your cart is empty'))->success();
        return back();
    }

    public function store_shipping_info(Request $request)
    {
        $rules = [
            'name' => 'required',
            'address' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'city_id' => 'required|integer',
            'postal_code' => 'nullable',
            'phone' => 'required',
        ];

        if ($request->address_id == null) {
            $address = $request->validate($rules);
        } else {
            $address = data_get(
                Address::findOrFail($request->address_id)->toArray(),
                array_keys($rules)
            );
        }

        $carts = $this->cart()->get();
        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        foreach ($carts as $key => $cartItem) {
            $cartItem->address_id = $request->address_id;
            $cartItem->destination = $address;
            $cartItem->save();
        }

        $carrier_list = array();
        if(get_setting('shipping_type') == 'carrier_wise_shipping'){
            $zone = \App\Models\Country::where('id',$carts[0]['address']['country_id'])->first()->zone_id;

            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id',function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->get();
        }
        
        return view('frontend.delivery_info', compact('carts','carrier_list'));
    }

    public function store_delivery_info(Request $request)
    {
        $rules = [
            'name' => 'required',
            'address' => 'required',
            // 'country_id' => 'required|integer',
            // 'state_id' => 'required|integer',
            'city_id' => 'required|integer',
            'postal_code' => 'nullable',
            'phone' => 'required',
        ];

        $carts = $this->cart()->get();

        if($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        if ($request->address_id == null) {
            $address = $request->validate($rules);
            $address['country_id'] = Country::where('name', 'Bangladesh')->first()->id ?? null;
            $address['state_id'] = State::where('name', 'Default')->first()->id ?? null;
        } else {
            $address = data_get(
                Address::findOrFail($request->address_id)->toArray(),
                array_keys($rules)
            );
        }

        foreach ($carts as $key => $cartItem) {
            $cartItem->address_id = $request->address_id;
            $cartItem->destination = $address;
            $cartItem->save();
        }

        // if (Auth::check()) {
        //     $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        // } else {
        //     $shipping_info = array_merge($destination = $carts[0]['destination'], [
        //         'city' => City::find($destination['city_id'])->name,
        //         'state' => State::find($destination['state_id'])->name,
        //         'country' => Country::find($destination['country_id'])->name,
        //     ]);
        // }

        $tax = 0;
        $shipping = 0;
        $subtotal = 0;

        foreach ($carts as $key => $cartItem) {
            $product = Product::find($cartItem['product_id']);
            $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];

            if(get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product->user_id] == 'pickup_point'){
                if ($request['shipping_type_' . $product->user_id] == 'pickup_point') {
                    $cartItem['shipping_type'] = 'pickup_point';
                    $cartItem['pickup_point'] = $request['pickup_point_id_' . $product->user_id];
                } else {
                    $cartItem['shipping_type'] = 'home_delivery';
                }
                $cartItem['shipping_cost'] = 0;
                if ($cartItem['shipping_type'] == 'home_delivery') {
                    $cartItem['shipping_cost'] = getShippingCost($carts, $key);
                }
            }
            else{
                $cartItem['shipping_type'] = 'carrier';
                $cartItem['carrier_id'] = $request['carrier_id_' . $product->user_id];
                $cartItem['shipping_cost'] = getShippingCost($carts, $key, $cartItem['carrier_id']);
            }

            $shipping += $cartItem['shipping_cost'];
            $cartItem->save();
        }

        return $this->checkout($request);
    }

    public function apply_coupon_code(Request $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        $response_message = array();

        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $carts = $this->cart()
                                    ->where('owner_id', $coupon->user_id)
                                    ->get();

                    $coupon_discount = 0;
                    
                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) { 
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }

                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) { 
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }

                    if($coupon_discount > 0){
                        $this->cart()
                            ->where('owner_id', $coupon->user_id)
                            ->update(
                                [
                                    'discount' => $coupon_discount / count($carts),
                                    'coupon_code' => $request->code,
                                    'coupon_applied' => 1
                                ]
                            );
                        $response_message['response'] = 'success';
                        $response_message['message'] = translate('Coupon has been applied');
                    }
                    else{
                        $response_message['response'] = 'warning';
                        $response_message['message'] = translate('This coupon is not applicable to your cart products!');
                    }
                    
                } else {
                    $response_message['response'] = 'warning';
                    $response_message['message'] = translate('You already used this coupon!');
                }
            } else {
                $response_message['response'] = 'warning';
                $response_message['message'] = translate('Coupon expired!');
            }
        } else {
            $response_message['response'] = 'danger';
            $response_message['message'] = translate('Invalid coupon!');
        }

        $carts = $this->cart()
                ->get();
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        $returnHTML = view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'))->render();
        return response()->json(array('response_message' => $response_message, 'html'=>$returnHTML));
    }

    public function remove_coupon_code(Request $request)
    {
        $this->cart()
                ->update(
                        [
                            'discount' => 0.00,
                            'coupon_code' => '',
                            'coupon_applied' => 0
                        ]
        );

        $coupon = Coupon::where('code', $request->code)->first();
        $carts = $this->cart()
                ->get();

        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        return view('frontend.partials.cart_summary', compact('coupon', 'carts', 'shipping_info'));
    }

    public function apply_club_point(Request $request) {
        if (addon_is_activated('club_point')){

            $point = $request->point;

            if(Auth::user()->point_balance >= $point) {
                $request->session()->put('club_point', $point);
                flash(translate('Point has been redeemed'))->success();
            }
            else {
                flash(translate('Invalid point!'))->warning();
            }
        }
        return back();
    }

    public function remove_club_point(Request $request) {
        $request->session()->forget('club_point');
        return back();
    }

    public function order_confirmed()
    {
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));

        $this->cart()->delete();

        //Session::forget('club_point');
        //Session::forget('combined_order_id');
        
        foreach($combined_order->orders as $order){
            NotificationUtility::sendOrderPlacedNotification($order);
        }

        $order = $combined_order->orders->first();
        $shipping_cost = 0;
        $items = $order->orderDetails->map(function($orderDetail, $i) use (&$shipping_cost) {
            $shipping_cost += $orderDetail->shipping_cost;
            return [
                'item_id' => $orderDetail->order_id,
                'item_name' => $orderDetail->product->name,
                'affiliation' => request()->getHttpHost(),
                'coupon' => '',
                'discount' => 0,
                'index' => $i,
                'item_brand' => $orderDetail->product->brand->name ?? '',
                'item_category' => $orderDetail->product->category->name ?? '',
                'item_category2' => '',
                'item_category3' => '',
                'item_category4' => '',
                'item_category5' => '',
                'item_list_id' => '',
                'item_list_name' => '',
                'item_variant' => '',
                'location_id' => '',
                'price' => $orderDetail->price,
                'quantity' => $orderDetail->quantity,
            ];
        })->values()->toArray();
        $ecommerce = [
            'currency' => 'BDT',
            'value' => $order->grand_total,
            'transaction_id' => $order->order_id,
            'coupon' => '',
            'shipping' => $shipping_cost,
            'tax' => 0,
            'items' => $items,
        ];
        
        $customerData = json_decode($combined_order->shipping_address, true);
        GoogleTagManagerFacade::push([
            'ecommerce' => $ecommerce,
            'event' => 'purchase',
            'customer' => $customerData + [
                'user_id' => $combined_order->user_id,
                'first_name' => explode(' ', $customerData['name'], 2)[0] ?? '',
                'last_name' => explode(' ', $customerData['name'], 2)[1] ?? '',
            ],
        ]);

        return view('frontend.order_confirmed', compact('combined_order'));
    }
}
