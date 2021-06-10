<div class="bg-light shadow-sm rounded py-4 mb-4">
    <h3 class="text-5 font-weight-400 d-flex align-items-center px-4 mb-3">All Transactions</h3>
    <!-- Title -->
    <div class="transaction-title py-2 px-4">
        <div class="row">
            <div class="col-2 col-sm-1 text-center"><span class="">Date</span></div>
            <div class="col col-sm-7">Description</div>
            <div class="col-auto col-sm-2 d-none d-sm-block text-center">Status</div>
            <div class="col-3 col-sm-2 text-right">Amount</div>
        </div>
    </div>
    <!-- Title End -->

    <!-- Transaction List -->
    <div class="transaction-list">
        @foreach ($transactions as $transaction)
        <div class="transaction-item px-4 py-3" onclick="showTransactionDetails({{$transaction->id}})">
            <div class="row align-items-center flex-row">
                <div class="col-2 col-sm-1 text-center">
                    <span class="d-block text-1 font-weight-300 text-uppercase">{{$transaction->transaction_date}}</span>
                </div>
                <div class="col col-sm-7">
                    <span class="d-block text-4">

                        @if ($transaction->from_wallet_id == $wallet->id)
                            {{$transaction->getWalletInformations->getUserInformations->name}}
                        @elseif($transaction->to_wallet_id == $wallet->id)
                            {{$transaction->getWalletInformationsFrom->getUserInformations->name}}
                        @endif

                    </span>
                    <span class="text-muted">{{ucfirst($transaction->getTransactionType->transaction_type_name)}}</span>
                </div>
                <div class="col-auto col-sm-2 d-none d-sm-block text-center text-3">
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
                    <span class="text-nowrap">{{$transaction->signal}} {{$transaction->total}}</span>
                    <span class="text-2 text-uppercase">({{$transaction->transaction_currency}})</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <!-- Transaction List End -->

    @push('Scripts')
    <script src="{{ asset('vendor/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(function() {
        'use strict';

        // Date Range Picker
        $(function() {
            var start = moment().subtract(29, 'days');
            var end = moment();
            function cb(start, end) {
                $('#dateRange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
            $('#dateRange').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);
            cb(start, end);
        });
        });
    </script>
    @endpush

    <!-- Transaction Item Details Modal -->
    @include('transactionmodule::transaction.components.transaction_item_details')
    <!-- Transaction Item Details Modal End -->

    @if (Request::is('transactions/all'))
        <!-- Pagination  -->
        <ul class="pagination justify-content-center mt-4 mb-0">
            {!! $transactions->links() !!}
        </ul>
        <!-- Paginations end -->
    @endif


  </div>
