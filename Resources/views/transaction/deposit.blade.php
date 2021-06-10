@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="font-weight-400 text-center mt-3 mb-4">Deposit Money</h2>
    <div class="row">
      <div class="col-md-8 col-lg-6 col-xl-5 mx-auto">
        <div id="deposit-container" class="bg-light shadow-sm rounded p-3 p-sm-4 mb-4">
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

          <div id="success-alert" class="alert alert-success d-none">
            <p id="success-message"></p>
          </div>

          <!-- Deposit Money Form  -->
          <form id="deposit-form" method="post" accept="{{ route('deposit') }}">
            @csrf
            <div class="form-group">
              <label for="youSend">Amount</label>
              <div class="input-group">
                <input  id="transaction-amount" type="number" name="amount_paid" value="{{ old('amount_paid') }}"  class="form-control @error('amount_paid') is-invalid @enderror" required step="0.02" placeholder="Enter The Amount">

                @error('amount_paid')
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
               </div>
            </div>

            <div class="form-group">
                <label for="youSend">Reference Number</label>
                <div class="input-group">
                  <input type="text" name="reference_number" value="{{$referenceNumber}}" class="form-control @error('reference_number') is-invalid @enderror" data-bv-field="reference_number" id="reference_number" placeholder="Enter The Reference Number" readonly>

                  {{-- <span class="invalid-feedback d-block" onclick="sendReferenceNumberEmail('{{$referenceNumber}}')" style="color: #2dbe60;cursor: pointer;" role="alert">
                    <strong>Send my Email</strong>
                  </span> --}}

                  @error('reference_number')
                  <span class="invalid-feedback d-block" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

            </div>

            <div class="form-group">
              <label for="paymentMethod">Payment Method</label>
              <select id="payment-method-select" name="type" class="custom-select" required="">
                <option value="">Select Payment Method</option>
                @foreach ($paymentMethods as $paymentMethod)
                <option value="{{$paymentMethod->name}}">{{$paymentMethod->display_name}}</option>
                @endforeach

              </select>
                @error('type')
                <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <p class="text-muted mt-4">Transactions fees
                <span  class="float-right d-flex align-items-center">
                    <span class="mr-1" id="transaction-fee">0 </span> {{$wallet->currency}}
                </span>

            </p>
            <hr>
            <p class="font-weight-500">You'll deposit
                 <span class="text-3 float-right">
                     <span id="total-amount" class="mr-1">0</span>
                      {{$wallet->currency}}
                 </span>
            </p>
            <button class="btn btn-primary btn-block">Continue</button>
          </form>
          @push('Scripts')
          <script>

              function getFeeDeposit() {
                var amount=parseFloat($('#transaction-amount').val());
                var paymentMethod=$('#payment-method-select').val();

                if(isNaN(amount)){
                    $("#error-alert").removeClass("d-none");
                    $("#error-message").text("Please Enter The Amount");
                    $('#payment-method-select').val("null");
                    return false;

                }

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    method: 'post'
                    , url: "{{route('deposit.fee')}}"
                    , cache: false
                    , data: {
                        "_token": "{{ csrf_token() }}",
                        amount: amount,
                        paymentMethod:paymentMethod,
                    }
                    , success: function(result) {
                        console.log("success");
                        var fee=parseFloat(result);

                        $("#transaction-fee").text(fee);
                        $("#total-amount").text(amount - fee);


                    }
                    , error: function(result) {
                        console.log("error");
                        console.log(result);
                        // $('.alert').show();
                        // $('.alert').html("هناك خطاء");
                    }

                });
              }

            $("#payment-method-select").change(function(e){
                getFeeDeposit()
            });
            $("#transaction-amount").on("input", function(){
                getFeeDeposit()
            });

            $("#deposit-form").submit(function( event ) {
                showLoading("#deposit-container");
            });

            // $("#transaction-amount").change(function(e){
            //     getFeeDeposit()
            // });

            // function sendReferenceNumberEmail(referenceNumber) {

            //     showLoading("#deposit-container");

            //     $.ajaxSetup({
            //         headers: {
            //             'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            //         }
            //     });

            //     $.ajax({
            //         method: 'post'
            //         , url: '{{route("sendReferenceNumberEmail")}}'
            //         , cache: false
            //         , data: {
            //             "_token": "{{ csrf_token() }}",
            //             referenceNumber: referenceNumber
            //         , }
            //         , success: function(result) {
            //             hideLoading("#deposit-container");
            //             $("#success-alert").removeClass("d-none");
            //             $("#success-message").text(result.message);


            //         }
            //         , error: function(result) {
            //             hideLoading("#deposit-container");
            //             $("#error-alert").removeClass("d-none");
            //             $("#error-message").text('The email has not been sent successfully');

            //         }

            //     });
            // }
          </script>
          @endpush
          <!-- Deposit Money Form end -->
        </div>
      </div>
    </div>
  </div>
@endsection
