@extends('frontend.layouts.app')

@section('content')

<section class="pt-5 mb-4">
    <div class="container">
        <div class="row">
            <div class="col-xl-8 mx-auto">
                <div class="row aiz-steps arrow-divider">
                    <div class="col active">
                        <div class="text-center text-primary">
                            <i class="la-3x mb-2 las la-shopping-cart"></i>
                            <h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('1. My Cart')}}</h3>
                        </div>
                    </div>
                    <div class="col">
                        <div class="text-center">
                            <i class="la-3x mb-2 opacity-50 las la-check-circle"></i>
                            <h3 class="fs-14 fw-600 d-none d-lg-block opacity-50">{{ translate('2. Confirmation')}}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-4" id="cart-summary">
    <div class="container">
        @if( $carts && count($carts) > 0 )
            <form id="checkout-form" action="{{ route('checkout.store_delivery_info') }}" method="post">
                @csrf
                <input type="hidden" name="payment_option" value="cash_on_delivery">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="shadow-sm bg-white p-3 p-lg-4 rounded text-left">
                            <div class="mb-4">
                                <div class="row gutters-5 d-none d-lg-flex border-bottom mb-3 pb-3">
                                    <div class="col-md-5 fw-600">{{ translate('Product')}}</div>
                                    <div class="col fw-600">{{ translate('Price')}}</div>
                                    <div class="col fw-600">{{ translate('Tax')}}</div>
                                    <div class="col fw-600">{{ translate('Quantity')}}</div>
                                    <div class="col fw-600">{{ translate('Total')}}</div>
                                    <div class="col-auto fw-600">{{ translate('Remove')}}</div>
                                </div>
                                <ul class="list-group list-group-flush">
                                    @php
                                        $total = 0;
                                    @endphp
                                    @foreach ($carts as $key => $cartItem)
                                        @php
                                            $product = \App\Models\Product::find($cartItem['product_id']);
                                            $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                                            $total = $total + ($cartItem['price'] + $cartItem['tax']) * $cartItem['quantity'];
                                            $product_name_with_choice = $product->getTranslation('name');
                                            if ($cartItem['variation'] != null) {
                                                $product_name_with_choice = $product->getTranslation('name').' - '.$cartItem['variation'];
                                            }
                                        @endphp
                                        <li class="list-group-item px-0 px-lg-3">
                                            <div class="row gutters-5">
                                                <div class="col-lg-5 d-flex">
                                                    <span class="mr-2 ml-0">
                                                        <img
                                                            src="{{ uploaded_asset($product->thumbnail_img) }}"
                                                            class="img-fit size-60px rounded"
                                                            alt="{{ $product->getTranslation('name')  }}"
                                                        >
                                                    </span>
                                                    <span class="fs-14 opacity-60">{{ $product_name_with_choice }}</span>
                                                </div>

                                                <div class="col-lg col-4 order-1 order-lg-0 my-3 my-lg-0">
                                                    <span class="opacity-60 fs-12 d-block d-lg-none">{{ translate('Price')}}</span>
                                                    <span class="fw-600 fs-16">{{ single_price($cartItem['price']) }}</span>
                                                </div>
                                                <div class="col-lg col-4 order-2 order-lg-0 my-3 my-lg-0">
                                                    <span class="opacity-60 fs-12 d-block d-lg-none">{{ translate('Tax')}}</span>
                                                    <span class="fw-600 fs-16">{{ single_price($cartItem['tax']) }}</span>
                                                </div>

                                                <div class="col-lg col-6 order-4 order-lg-0">
                                                    @if($cartItem['digital'] != 1 && $product->auction_product == 0)
                                                        <div class="row no-gutters align-items-center aiz-plus-minus mr-2 ml-0">
                                                            <button class="btn col-auto btn-icon btn-sm btn-circle btn-light" type="button" data-type="minus" data-field="quantity[{{ $cartItem['id'] }}]">
                                                                <i class="las la-minus"></i>
                                                            </button>
                                                            <input type="number" name="quantity[{{ $cartItem['id'] }}]" class="col border-0 text-center flex-grow-1 fs-16 input-number" placeholder="1" value="{{ $cartItem['quantity'] }}" min="{{ $product->min_qty }}" max="{{ $product_stock->qty }}" onchange="updateQuantity({{ $cartItem['id'] }}, this)">
                                                            <button class="btn col-auto btn-icon btn-sm btn-circle btn-light" type="button" data-type="plus" data-field="quantity[{{ $cartItem['id'] }}]">
                                                                <i class="las la-plus"></i>
                                                            </button>
                                                        </div>
                                                    @elseif($product->auction_product == 1)
                                                        <span class="fw-600 fs-16">1</span>
                                                    @endif
                                                </div>
                                                <div class="col-lg col-4 order-3 order-lg-0 my-3 my-lg-0">
                                                    <span class="opacity-60 fs-12 d-block d-lg-none">{{ translate('Total')}}</span>
                                                    <span class="fw-600 fs-16 text-primary">{{ single_price(($cartItem['price'] + $cartItem['tax']) * $cartItem['quantity']) }}</span>
                                                </div>
                                                <div class="col-lg-auto col-6 order-5 order-lg-0 text-right">
                                                    <a href="javascript:void(0)" onclick="removeFromCartView(event, {{ $cartItem['id'] }})" class="btn btn-icon btn-sm btn-soft-primary btn-circle">
                                                        <i class="las la-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="px-3 py-2 border-top d-flex justify-content-between">
                                <span class="opacity-60 fs-15">{{translate('Subtotal')}}</span>
                                <span class="fw-600 fs-17">{{ single_price($total) }}</span>
                            </div>
                        </div>
                        {{-- ... --}}
                        <div class="shadow-sm bg-white p-3 p-lg-4 rounded text-left">
                            <div shipping-info>
                                <div class="card-header p-3">
                                    <h5 class="fs-16 fw-600 mb-0">{{translate('Shipping Info')}}</h5>
                                </div>
                                @if(Auth::check())
                                    <div class="shadow-sm bg-white rounded mb-4">
                                        <div class="row gutters-5">
                                            @foreach (Auth::user()->addresses as $key => $address)
                                                <div class="col-md-6 mb-3">
                                                    <label class="aiz-megabox d-block bg-white mb-0">
                                                        <input type="radio" name="address_id" value="{{ $address->id }}" @if ($address->set_default)
                                                            checked
                                                                @endif required>
                                                        <span class="d-flex p-3 aiz-megabox-elem">
                                                <span class="aiz-rounded-check flex-shrink-0 mt-1"></span>
                                                <span class="flex-grow-1 pl-3 text-left">
                                                    <div>
                                                        <span class="opacity-60">{{ translate('Address') }}:</span>
                                                        <span class="fw-600 ml-2">{{ $address->address }}</span>
                                                    </div>
                                                    {{-- <div>
                                                        <span class="opacity-60">{{ translate('Postal Code') }}:</span>
                                                        <span class="fw-600 ml-2">{{ $address->postal_code }}</span>
                                                    </div> --}}
                                                    <div>
                                                        <span class="opacity-60">{{ translate('Thana') }}:</span>
                                                        <span class="fw-600 ml-2">{{ optional($address->city)->name }}</span>
                                                    </div>
                                                    <div>
                                                        <span class="opacity-60">{{ translate('District') }}:</span>
                                                        <span class="fw-600 ml-2">{{ optional($address->state)->name }}</span>
                                                    </div>
                                                    {{-- <div>
                                                        <span class="opacity-60">{{ translate('Country') }}:</span>
                                                        <span class="fw-600 ml-2">{{ optional($address->country)->name }}</span>
                                                    </div> --}}
                                                    <div>
                                                        <span class="opacity-60">{{ translate('Phone') }}:</span>
                                                        <span class="fw-600 ml-2">{{ $address->phone }}</span>
                                                    </div>
                                                </span>
                                            </span>
                                                    </label>
                                                    <div class="dropdown position-absolute right-0 top-0">
                                                        <button class="btn bg-gray px-2" type="button" data-toggle="dropdown">
                                                            <i class="la la-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                                            <a class="dropdown-item" onclick="edit_address('{{$address->id}}')">
                                                                {{ translate('Edit') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <input type="hidden" name="checkout_type" value="logged">
                                            <div class="col-md-6 mx-auto mb-3" >
                                                <div class="border p-3 rounded mb-3 c-pointer text-center bg-white h-100 d-flex flex-column justify-content-center" onclick="add_new_address()">
                                                    <i class="las la-plus la-2x mb-3"></i>
                                                    <div class="alpha-7">{{ translate('Add New Address') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="shadow-sm bg-white rounded mb-4">
                                        <div class="p-3">
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label>{{ translate('Name')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <input type="text" class="form-control mb-3" id="name" name="name" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label>{{ translate('Phone')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <input type="text" class="form-control mb-3" placeholder="{{ translate('+880')}}" name="phone" value="" required>
                                                </div>
                                            </div>
                                            <div class="row d-none">
                                                <div class="col-md-2">
                                                    <label>{{ translate('Country')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <div class="mb-3">
                                                        <select class="form-control aiz-selectpicker" data-live-search="true" data-placeholder="{{ translate('Select your country') }}" name="country_id" required>
                                                            <option value="">{{ translate('Select your country') }}</option>
                                                            @foreach (\App\Models\Country::where('status', 1)->get() as $key => $country)
                                                                <option value="{{ $country->id }}" @if($country->name == 'Bangladesh') selected @endif>{{ $country->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label>{{ translate('District')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <select class="form-control mb-3 aiz-selectpicker" data-live-search="true" name="state_id" required>

                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label>{{ translate('Thana')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <select class="form-control mb-3 aiz-selectpicker" data-live-search="true" name="city_id" required>
                                                        
                                                    </select>
                                                </div>
                                            </div>

                                            @if (get_setting('google_map') == 1)
                                                <div class="row">
                                                    <input id="searchInput" class="controls" type="text" placeholder="{{translate('Enter a location')}}">
                                                    <div id="map"></div>
                                                    <ul id="geoData">
                                                        <li style="display: none;">Full Address: <span id="location"></span></li>
                                                        {{-- <li style="display: none;">Postal Code: <span id="postal_code"></span></li> --}}
                                                        <li style="display: none;">Country: <span id="country"></span></li>
                                                        <li style="display: none;">Latitude: <span id="lat"></span></li>
                                                        <li style="display: none;">Longitude: <span id="lon"></span></li>
                                                    </ul>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-2" id="">
                                                        <label for="exampleInputuname">Longitude</label>
                                                    </div>
                                                    <div class="col-md-10" id="">
                                                        <input type="text" class="form-control mb-3" id="longitude" name="longitude" readonly="">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-2" id="">
                                                        <label for="exampleInputuname">Latitude</label>
                                                    </div>
                                                    <div class="col-md-10" id="">
                                                        <input type="text" class="form-control mb-3" id="latitude" name="latitude" readonly="">
                                                    </div>
                                                </div>
                                            @endif

                                            <input type="hidden" class="form-control mb-3" name="postal_code" value="">

                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label>{{ translate('Address')}}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <textarea class="form-control mb-3" placeholder="{{ translate('Your Address')}}" rows="2" name="address" required></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <label>{{ translate('Any additional info?') }}</label>
                                                </div>
                                                <div class="col-md-10">
                                                    <textarea name="additional_info" rows="5" class="form-control" placeholder="{{ translate('Type your text') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mx-auto">
                        <div id="cart_summary">
                            @include('frontend.partials.cart_summary')
                        </div>

                        <div class="pt-3">
                            <label class="aiz-checkbox">
                                <input type="checkbox" required id="agree_checkbox" checked>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('I agree to the') }}</span>
                            </label>
                            <a href="{{ route('terms') }}">{{ translate('terms and conditions') }}</a>,
                            <a href="{{ route('returnpolicy') }}">{{ translate('return policy') }}</a> &
                            <a href="{{ route('privacypolicy') }}">{{ translate('privacy policy') }}</a>
                        </div>

                        <div class="row align-items-center pt-3">
                            <div class="col-6">
                                <a href="{{ route('home') }}" class="link link--style-3">
                                    <i class="las la-arrow-left"></i>
                                    {{ translate('Return to shop') }}
                                </a>
                            </div>
                            <div class="col-6 text-right">
                                <button type="button" onclick="submitOrder(this)"
                                    class="btn btn-primary fw-600">{{ translate('Complete Order') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @else
            <div class="row">
                <div class="col-xl-8 mx-auto">
                    <div class="shadow-sm bg-white p-4 rounded">
                        <div class="text-center p-3">
                            <i class="las la-frown la-3x opacity-60 mb-3"></i>
                            <h3 class="h4 fw-700">{{translate('Your Cart is empty')}}</h3>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>

@endsection

@section('modal')
    <div class="modal fade" id="login-modal">
        <div class="modal-dialog modal-dialog-zoom">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-600">{{ translate('Login')}}</h6>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="p-3">
                        <form class="form-default" role="form" action="{{ route('cart.login.submit') }}" method="POST">
                            @csrf
                            @if (addon_is_activated('otp_system') && env("DEMO_MODE") != "On")
                                <div class="form-group phone-form-group mb-1">
                                    <input type="tel" id="phone-code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ old('phone') }}" placeholder="" name="phone" autocomplete="off">
                                </div>

                                <input type="hidden" name="country_code" value="">

                                <div class="form-group email-form-group mb-1 d-none">
                                    <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{  translate('Email') }}" name="email" id="email" autocomplete="off">
                                    @if ($errors->has('email'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                <div class="form-group text-right">
                                    <button class="btn btn-link p-0 opacity-50 text-reset" type="button" onclick="toggleEmailPhone(this)">{{ translate('Use Email Instead') }}</button>
                                </div>
                            @else
                                <div class="form-group">
                                    <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{  translate('Email') }}" name="email" id="email" autocomplete="off">
                                    @if ($errors->has('email'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="form-group">
                                <input type="password" class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ translate('Password')}}" name="password" id="password">
                            </div>

                            <div class="row mb-2">
                                <div class="col-6">
                                    <label class="aiz-checkbox">
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <span class=opacity-60>{{  translate('Remember Me') }}</span>
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                                <div class="col-6 text-right">
                                    <a href="{{ route('password.request') }}" class="text-reset opacity-60 fs-14">{{ translate('Forgot password?')}}</a>
                                </div>
                            </div>

                            <div class="mb-5">
                                <button type="submit" class="btn btn-primary btn-block fw-600">{{  translate('Login') }}</button>
                            </div>
                        </form>

                    </div>
                    <div class="text-center mb-3">
                        <p class="text-muted mb-0">{{ translate('Dont have an account?')}}</p>
                        <a href="{{ route('user.registration') }}">{{ translate('Register Now')}}</a>
                    </div>
                    @if(get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1)
                        <div class="separator mb-3">
                            <span class="bg-white px-3 opacity-60">{{ translate('Or Login With')}}</span>
                        </div>
                        <ul class="list-inline social colored text-center mb-3">
                            @if (get_setting('facebook_login') == 1)
                                <li class="list-inline-item">
                                    <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook">
                                        <i class="lab la-facebook-f"></i>
                                    </a>
                                </li>
                            @endif
                            @if(get_setting('google_login') == 1)
                                <li class="list-inline-item">
                                    <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google">
                                        <i class="lab la-google"></i>
                                    </a>
                                </li>
                            @endif
                            @if (get_setting('twitter_login') == 1)
                                <li class="list-inline-item">
                                    <a href="{{ route('social.login', ['provider' => 'twitter']) }}" class="twitter">
                                        <i class="lab la-twitter"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection




@if(auth()->check())
<div class="modal fade" id="new-address-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('New Address') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-default" role="form" action="{{ route('addresses.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="p-3">
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ translate('Phone')}}</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" class="form-control mb-3" placeholder="{{ translate('+880')}}" name="phone" value="" required>
                            </div>
                        </div>
                        <div class="row d-none">
                            <div class="col-md-2">
                                <label>{{ translate('Country')}}</label>
                            </div>
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <select class="form-control aiz-selectpicker" data-live-search="true" data-placeholder="{{ translate('Select your country') }}" name="country_id" required>
                                        <option value="">{{ translate('Select your country') }}</option>
                                        @foreach (\App\Models\Country::where('status', 1)->get() as $key => $country)
                                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ translate('District')}}</label>
                            </div>
                            <div class="col-md-10">
                                <select class="form-control mb-3 aiz-selectpicker" data-live-search="true" name="state_id" required>

                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ translate('Thana')}}</label>
                            </div>
                            <div class="col-md-10">
                                <select class="form-control mb-3 aiz-selectpicker" data-live-search="true" name="city_id" required>
                                    
                                </select>
                            </div>
                        </div>

                        @if (get_setting('google_map') == 1)
                            <div class="row">
                                <input id="searchInput" class="controls" type="text" placeholder="{{translate('Enter a location')}}">
                                <div id="map"></div>
                                <ul id="geoData">
                                    <li style="display: none;">Full Address: <span id="location"></span></li>
                                    {{-- <li style="display: none;">Postal Code: <span id="postal_code"></span></li> --}}
                                    <li style="display: none;">Country: <span id="country"></span></li>
                                    <li style="display: none;">Latitude: <span id="lat"></span></li>
                                    <li style="display: none;">Longitude: <span id="lon"></span></li>
                                </ul>
                            </div>

                            <div class="row">
                                <div class="col-md-2" id="">
                                    <label for="exampleInputuname">Longitude</label>
                                </div>
                                <div class="col-md-10" id="">
                                    <input type="text" class="form-control mb-3" id="longitude" name="longitude" readonly="">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2" id="">
                                    <label for="exampleInputuname">Latitude</label>
                                </div>
                                <div class="col-md-10" id="">
                                    <input type="text" class="form-control mb-3" id="latitude" name="latitude" readonly="">
                                </div>
                            </div>
                        @endif

                        {{-- <div class="row">
                            <div class="col-md-2">
                                <label>{{ translate('Postal code')}}</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" class="form-control mb-3" placeholder="{{ translate('Your Postal Code')}}" name="postal_code" value="" required>
                            </div>
                        </div> --}}
                        <div class="row">
                            <div class="col-md-2">
                                <label>{{ translate('Address')}}</label>
                            </div>
                            <div class="col-md-10">
                                <textarea class="form-control mb-3" placeholder="{{ translate('Your Address')}}" rows="2" name="address" required></textarea>
                            </div>
                        </div>
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-address-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('New Address') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" id="edit_modal_body">

            </div>
        </div>
    </div>
</div>
@endif

@section('script')
    <script type="text/javascript">
        var minimum_order_amount_check = {{ get_setting('minimum_order_amount_check') == 1 ? 1 : 0 }};
        var minimum_order_amount =
            {{ get_setting('minimum_order_amount_check') == 1 ? get_setting('minimum_order_amount') : 0 }};

        function submitOrder(el) {
            $(el).prop('disabled', true);
            if ($('#agree_checkbox').is(":checked")) {
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var offline_payment_active = '{{ addon_is_activated('offline_payment') }}';
                    if (offline_payment_active == 'true' && $('.offline_payment_option').is(":checked") && $('#trx_id')
                        .val() == '') {
                        AIZ.plugins.notify('danger',
                            '{{ translate('You need to put Transaction id') }}');
                        $(el).prop('disabled', false);
                    } else {
                        $('#checkout-form').submit();
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
                $(el).prop('disabled', false);
            }
        }

        function add_new_address(){
            $('#new-address-modal').modal('show');
        }

        function edit_address(address) {
            var url = '{{ route("addresses.edit", ":id") }}';
            url = url.replace(':id', address);

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url,
                type: 'GET',
                success: function (response) {
                    $('#edit_modal_body').html(response.html);
                    $('#edit-address-modal').modal('show');
                    AIZ.plugins.bootstrapSelect('refresh');

                    @if (get_setting('google_map') == 1)
                    var lat     = -33.8688;
                    var long    = 151.2195;

                    if(response.data.address_data.latitude && response.data.address_data.longitude) {
                        lat     = response.data.address_data.latitude;
                        long    = response.data.address_data.longitude;
                    }

                    initialize(lat, long, 'edit_');
                    @endif
                }
            });
        }

        $(document).on('change', '[name=country_id]', function() {
            var country_id = $(this).val();
            get_states(country_id);
        });

        $(document).on('change', '[name=state_id]', function() {
            var state_id = $(this).val();
            get_city(state_id);
        });

        $(document).on('change', '[name=city_id]', function() {
            var city_id = $(this).val();
            $.get('{{ url()->current() }}', {
                city_id,
            }, function(data){
                $('#cart_summary').html(data.cart_summary);
            });
        });

        function get_states(country_id) {
            $('[name="state"]').html("");
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('get-state')}}",
                type: 'POST',
                data: {
                    country_id  : country_id
                },
                success: function (response) {
                    var obj = JSON.parse(response);
                    if(obj != '') {
                        $('[name="state_id"]').html(obj);
                        AIZ.plugins.bootstrapSelect('refresh');
                    }
                }
            });
        }

        function get_city(state_id) {
            $('[name="city"]').html("");
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('get-city')}}",
                type: 'POST',
                data: {
                    state_id: state_id
                },
                success: function (response) {
                    var obj = JSON.parse(response);
                    console.log(obj)
                    if(obj != '') {
                        $('[name="city_id"]').html(obj);
                        AIZ.plugins.bootstrapSelect('refresh');
                    }
                }
            });
        }
    </script>


    @if (get_setting('google_map') == 1)
        @include('frontend.partials.google_map')
    @endif

    <script type="text/javascript">
        $(document).ready(function() {
            var country_id = $('[name=country_id]').val();
            get_states(country_id);
        });

        function removeFromCartView(e, key){
            e.preventDefault();
            removeFromCart(key);
        }

        function updateQuantity(key, element){
            $.post('{{ route('cart.updateQuantity') }}', {
                _token   :  AIZ.data.csrf,
                id       :  key,
                quantity :  element.value
            }, function(data){
                updateNavCart(data.nav_cart_view,data.cart_count);
                $('#cart-summary').html(data.cart_view);
            });
        }

        function showCheckoutModal(){
            $('#login-modal').modal();
        }

        // Country Code
        var isPhoneShown = true,
            countryData = window.intlTelInputGlobals.getCountryData(),
            input = document.querySelector("#phone-code");

        for (var i = 0; i < countryData.length; i++) {
            var country = countryData[i];
            if(country.iso2 == 'bd'){
                country.dialCode = '88';
            }
        }

        var iti = intlTelInput(input, {
            separateDialCode: true,
            utilsScript: "{{ static_asset('assets/js/intlTelutils.js') }}?1590403638580",
            onlyCountries: @php echo json_encode(\App\Models\Country::where('status', 1)->pluck('code')->toArray()) @endphp,
            customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                if(selectedCountryData.iso2 == 'bd'){
                    return "01xxxxxxxxx";
                }
                return selectedCountryPlaceholder;
            }
        });

        var country = iti.getSelectedCountryData();
        $('input[name=country_code]').val(country.dialCode);

        input.addEventListener("countrychange", function(e) {
            // var currentMask = e.currentTarget.placeholder;

            var country = iti.getSelectedCountryData();
            $('input[name=country_code]').val(country.dialCode);

        });

        function toggleEmailPhone(el){
            if(isPhoneShown){
                $('.phone-form-group').addClass('d-none');
                $('.email-form-group').removeClass('d-none');
                $('input[name=phone]').val(null);
                isPhoneShown = false;
                $(el).html('{{ translate('Use Phone Instead') }}');
            }
            else{
                $('.phone-form-group').removeClass('d-none');
                $('.email-form-group').addClass('d-none');
                $('input[name=email]').val(null);
                isPhoneShown = true;
                $(el).html('{{ translate('Use Email Instead') }}');
            }
        }

        function display_option(key){

        }
        function show_pickup_point(el) {
            var value = $(el).val();
            var target = $(el).data('target');

            // console.log(value);

            if(value == 'home_delivery'){
                if(!$(target).hasClass('d-none')){
                    $(target).addClass('d-none');
                }
            }else{
                $(target).removeClass('d-none');
            }
        }

    </script>
@endsection
