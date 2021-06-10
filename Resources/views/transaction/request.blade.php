@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="font-weight-400 text-center mt-3">Request Money</h2>
    <p class="text-4 text-center mb-4">Request your money on anytime, anywhere in the world.</p>
    <div class="row">
      <div class="col-md-8 col-lg-6 col-xl-5 mx-auto">
        <div class="bg-light shadow-sm rounded p-3 p-sm-4 mb-4">
          <h3 class="text-5 font-weight-400 mb-3">Request Details</h3>

            @if ($errors->any())
            <div class="alert alert-danger">
                <p>{{$errors->first()}}</p>
            </div>
            @endif

            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif

            @if ($message = Session::get('failed'))
                <div class="alert alert-danger">
                <p>{{ $message }}</p>
                </div>
            @endif

          <div id="error-alert" class="alert alert-danger d-none">
            <p id="error-message"></p>
          </div>

          <!-- Send Money Form
          ============================================= -->
          <form id="form-send-money" method="post" action="{{ route('request') }}">
            @csrf
            <div class="form-group">
              <label for="emailID">From Account</label>

              <select name="from_wallet_id" value="{{ old('from_wallet_id') }}" data-style="custom-select bg-transparent border-0"  data-container="body" data-live-search="true" class="selectpicker form-control @error('from_wallet_id') is-invalid @enderror" required="">
                @foreach ($connections as $connection)
                <option value="{{$connection->wallet->id}}" data-icon="currency-flag currency-flag-{{strtolower($connection->wallet->currency)}} mr-1" data-subtext="{{$connection->wallet->currency}}">{{$connection->user_name}}</option>
                @endforeach
              </select>
              @error('from_wallet_id')
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
              @enderror
            </div>
            <div class="form-group">
              <label for="youSend">Amount</label>
              <div class="input-group">
                <input id="transaction-amount" type="number" name="transaction_amount"  value="{{ old('transaction_amount') }}" class="form-control @error('transaction_amount') is-invalid @enderror" required placeholder="Enter Amount">
                @error('transaction_amount')
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
              @enderror
              </div>
            </div>
            <hr>
            <p class="mb-1">Total fees
                <span class="text-3 float-right"> <span id="transaction-fee">0</span> {{$wallet->currency}} </span>
            </p>
            <p class="font-weight-500">Total To Request <span class="text-3 float-right"> <span id="total-amount">0</span> {{$wallet->currency}}</span></p>
            <button type="submit" class="btn btn-primary btn-block">Continue</button>
          </form>
          <!-- Send Money Form end -->
          @push('Scripts')
          <script>
            $("#transaction-amount").focusout(function(){
                var amount=parseFloat($('#transaction-amount').val());
                console.log(amount);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    method: 'post'
                    , url: "/transaction/fee"
                    , cache: false
                    , data: {
                        "_token": "{{ csrf_token() }}",
                        amount: amount,
                        type: 'request'
                    , }
                    , success: function(result) {
                        console.log("success");
                        var fee=parseFloat(result);
                        if( fee != 0){
                            $("#transaction-fee").text(fee);
                            $("#total-amount").text(amount);
                        }else{
                            $("#error-alert").removeClass("d-none");
                            $("#error-message").text("In Valid Fee");
                        }

                        //console.log(result);

                    }
                    , error: function(result) {
                        $("#error-alert").toggleClass("d-none");
                    }

                });

            });
          </script>
          @endpush
        </div>
      </div>
    </div>
  </div>
@endsection
