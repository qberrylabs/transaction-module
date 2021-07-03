@push('Scripts')
    <script>
        function showTransactionDetails(transactionID) {
            $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    method: 'GET',
                    url: "/transaction/details/"+transactionID,
                    cache: false,
                    data: {

                    },
                    success: function(transaction) {
                        $('#transaction-detail').modal('show');
                        var transactionID=transaction.id;

                        var fromAmount=transaction.from_amount;
                        var toAmount=transaction.to_amount;

                        var fromCurrency=transaction.transaction_currency;
                        var toCurrency=transaction.to_currency;

                        var total=transaction.total;

                        var fee=transaction.transfer_fee;
                        var exchangeRate=transaction.exchange_rate;

                        var note=transaction.note;
                        var transactionDate=transaction.transaction_date;

                        $("#transaction-ID").text(transactionID);

                        $("#from-amount").text(fromAmount);
                        $("#to-amount").text(toAmount);

                        $(".from-currency").text(fromCurrency);
                        $(".to-currency").text(toCurrency);

                        $("#total").text(total);
                        $("#total-site").text(total);

                        $("#fee").text(fee);
                        $("#exchange-rate").text(exchangeRate);
                        $("#transaction-date").text(transactionDate);

                        // alert("success");
                        //console.log(transaction);
                    },
                    error: function(transaction) {
                        console.log(transaction);
                        alert("error");
                    }

                });
        }
    </script>
    @endpush

    <!-- Transaction Item Details Modal -->
    <div id="transaction-detail" class="modal fade" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered transaction-details" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row no-gutters">
                        <div class="col-sm-5 d-flex justify-content-center bg-primary rounded-left py-4">
                            <div class="my-auto text-center">
                                <div class="text-17 text-white my-3"><i class="fas fa-building"></i></div>
                                <span style="color: #FFF" class="float-right text-1 ml-1 from-currency"></span>
                                <div  id="total-site" class="text-8 font-weight-500 text-white my-4"></div>
                                <p id="transaction-date" class="text-white"></p>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <h5 class="text-5 font-weight-400 m-3">Transaction Details #<span id="transaction-ID" class="text-muted"></span>
                                <button type="button" class="close font-weight-400" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
                            </h5>
                            <hr>
                            <div class="px-3">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        From Amount
                                        <span class="float-right text-1 ml-1 from-currency"></span>
                                        <span id="from-amount" class="float-right text-3"></span>

                                    </li>
                                    <li class="mb-2">
                                        To Amount
                                        <span class="float-right text-1 ml-1 to-currency"></span>
                                        <span id="to-amount" class="float-right text-3"></span>
                                    </li>
                                    <hr class="mb-2">
                                    <li class="mb-2">Fee
                                        <span class="float-right text-1 ml-1 from-currency"></span>
                                        <span id="fee" class="float-right text-3"></span>
                                    </li>
                                    <li class="mb-2">ExchangeRate
                                        <span id="exchange-rate" class="float-right text-3"></span>
                                    </li>
                                </ul>
                                <hr class="mb-2">
                                <p class="d-flex align-items-center font-weight-500 mb-4">
                                    Total Amount

                                    <span  id="total" class="text-3 ml-auto"></span>
                                    <span class="float-right text-1 ml-1 from-currency"></span>
                                </p>




                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Transaction Item Details Modal End -->
