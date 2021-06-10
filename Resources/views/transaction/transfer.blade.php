@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="font-weight-400 text-center mt-3">Transfer Money</h2>
    <p class="text-4 text-center mb-4">Send your money on anytime, anywhere in the world.</p>
    <div class="row">
      <div class="col-md-8 col-lg-6 col-xl-5 mx-auto">
        <div class="bg-light shadow-sm rounded p-3 p-sm-4 mb-4">
          <h3 class="text-5 font-weight-400 mb-3">Transfer Details</h3>

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

          <!-- Send Money Form
          ============================================= -->
          <form id="form-send-money" method="post" action="{{ route('transfer') }}">
            @csrf
            <div class="form-group">
              <label for="emailID">Recipient</label>

              <select name="to_wallet_id" value="{{ old('to_wallet_id') }}" data-style="custom-select bg-transparent border-0"  data-container="body" data-live-search="true" class="selectpicker form-control @error('to_wallet_id') is-invalid @enderror" required="">
                @foreach ($connections as $connection)
                <option value="{{$connection->wallet->id}}" data-icon="currency-flag currency-flag-{{strtolower($connection->wallet->currency)}} mr-1" data-subtext="{{$connection->wallet->currency}}">{{$connection->user_name}}</option>
                @endforeach
              </select>

              @error('to_wallet_id')
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
              @enderror
            </div>
            <div class="form-group">
              <label for="youSend">Amount</label>
              <div class="input-group">
                <input type="number" id="transaction-amount" name="transaction_amount"  value="{{ old('transaction_amount') }}" class="form-control @error('transaction_amount') is-invalid @enderror" required placeholder="Enter Amount">
                @error('transaction_amount')
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
              @enderror
              </div>
            </div>

            <p class="text-muted mt-4">Transactions fees
                <span  class="float-right d-flex align-items-center">
                    <span class="mr-1" id="transaction-fee">0 </span> {{$wallet->currency}}
                </span>

            </p>
            <hr>
            <p class="font-weight-500">You'll Transfer
                 <span class="text-3 float-right">
                     <span id="total-amount" class="mr-1">0</span>
                      {{$wallet->currency}}
                 </span>
            </p>
            <button type="submit" class="btn btn-primary btn-block">Continue</button>
          </form>
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
                        type: 'transfer'
                    , }
                    , success: function(result) {
                        console.log("success");
                        var fee=parseFloat(result);
                        $("#transaction-fee").text(fee);
                        $("#total-amount").text(amount + fee);
                        // if( fee != 0){

                        // }else{
                        //     $("#error-alert").removeClass("d-none");
                        //     $("#error-message").text("In Valid Fee");
                        // }

                        //console.log(result);

                    }
                    , error: function(result) {
                        console.log("error");
                        console.log(result);
                        // $('.alert').show();
                        // $('.alert').html("هناك خطاء");
                    }

                });

            });
          </script>
          @endpush
          <!-- Send Money Form end -->
        </div>
      </div>
    </div>
  </div>
@endsection
