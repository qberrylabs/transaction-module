
<div id="create-reference-number" class="modal fade " role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title font-weight-400">Create Reference Number</h5>
            <button type="button" class="close font-weight-400" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body p-4">
            <form id="create-connection-form" method="post" action="{{ route('createReferenceNumber') }}">
                @csrf
                <div class="row">
                    <div class="col-12">
                    <div class="form-group">
                        <label for="mobileNumber">Payment Method Name <span class="text-muted font-weight-500">(Required)</span></label>
                        @php
                            $user=Auth::user();
                            $paymentMethods=Modules\PaymentMethodeModule\Entities\PaymentMethod::where('country',$user->country)->get();

                        @endphp
                        <select name="type" value="{{ old('type') }}" data-style="custom-select bg-transparent border-0"  data-container="body" data-live-search="true" class="selectpicker form-control @error('type') is-invalid @enderror" required="">
                            @foreach ($paymentMethods as $paymentMethod)
                                <option value="{{$paymentMethod->name}}">{{$paymentMethod->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    </div>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Save Changes</button>
            </form>
        </div>
        </div>
    </div>
    </div>
