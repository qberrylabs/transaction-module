<div class="bg-light shadow-sm rounded py-4 mb-4">
    <div class="d-flex justify-content-between">
        <h3 class="text-5 font-weight-400 d-flex align-items-center px-4 mb-3">All Transactions</h3>
        <a href="{{ route('request') }}" class="btn btn-link">Create New Request</a>
    </div>

    <!-- Title-->
    <div class="transaction-title py-2 px-4">
      <div class="row">
        <div class="col-2 col-sm-1 text-center"><span class="">Date</span></div>
        <div class="col col-sm-3">Description</div>
        <div class="col-auto col-sm-1 d-none d-sm-block text-center">Status</div>
        <div class="col-3 col-sm-2 text-right">Amount</div>
        <div class="col-3 col-sm-5 text-right">Action</div>
      </div>
    </div>
    <!-- Title End -->

    <!-- Transaction List -->
    <div class="transaction-list">
        @foreach ($transactions as $transaction)
        <div class="transaction-item px-4 py-3" data-toggle="modal" data-target="#transaction-detail">
            <div class="row align-items-center flex-row">

                <div class="col-2 col-sm-1 text-center">
                    <span class="d-block text-1 font-weight-300 text-uppercase">{{$transaction->transaction_date}}</span>
                </div>

                <div class="col col-sm-3">
                    <span class="d-block text-4">{{$transaction->user_name}}</span>
                    <span class="text-muted">{{ucfirst($transaction->transaction_action)}}</span>
                </div>

                <div class="col-auto col-sm-1 d-none d-sm-block text-center text-3">
                    @switch($transaction->transaction_status_id)
                        @case(1)
                            <span class="text-warning" data-toggle="tooltip" data-original-title="In Progress"><i class="fas fa-ellipsis-h"></i></span>
                            @break
                        @case(2)
                        <span class="text-success" data-toggle="tooltip" data-original-title="Completed"><i class="fas fa-check-circle"></i></span>
                            @break
                        @case(3)
                        <span class="text-danger" data-toggle="tooltip" data-original-title="Cancelled"><i class="fas fa-times-circle"></i></span>
                            @break
                        @default

                    @endswitch
                </div>

                <div class="col-3 col-sm-2 text-right text-4">
                    <span class="text-nowrap">{{$transaction->signal}} {{$transaction->show_amount}}</span>
                    {{-- <span class="text-2 text-uppercase">({{$transaction->transaction_currency}})</span> --}}
                </div>

                <div class="col-3 col-sm-5 text-right text-4">
                    @if ($transaction->transaction_action == "Received")
                    <a class="btn btn-info d-inline-block" href="{{ route('requestChangeStatus', ['transactionID'=>$transaction->id,'status'=>2]) }}">Accept</a>
                    <a class="btn btn-danger d-inline-block" href="{{ route('requestChangeStatus', ['transactionID'=>$transaction->id,'status'=>'decline']) }}">Decline</a>

                    @else
                    <a class="btn btn-outline-danger d-inline-block" href="{{ route('requestChangeStatus', ['transactionID'=>$transaction->id,'status'=>'cancel']) }}">Cancel</a>
                    @endif

                    {{-- <span class="text-nowrap">{{$transaction->signal}} {{$transaction->total}}</span> --}}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <!-- Transaction List End -->

    {{-- <!-- Pagination  -->
    <ul class="pagination justify-content-center mt-4 mb-0">
      <li class="page-item disabled"> <a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-left"></i></a> </li>
      <li class="page-item"><a class="page-link" href="#">1</a></li>
      <li class="page-item active"> <a class="page-link" href="#">2 <span class="sr-only">(current)</span></a> </li>
      <li class="page-item"><a class="page-link" href="#">3</a></li>
      <li class="page-item d-flex align-content-center flex-wrap text-muted text-5 mx-1">......</li>
      <li class="page-item"><a class="page-link" href="#">15</a></li>
      <li class="page-item"> <a class="page-link" href="#"><i class="fas fa-angle-right"></i></a> </li>
    </ul>
    <!-- Paginations end --> --}}

  </div>
