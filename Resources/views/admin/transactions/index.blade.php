@extends('layouts.app_admin')

@section('content')
<div class="right_col" role="main">
    <div class="">
      <div class="clearfix"></div>

      <div class="row">

        <div class="col-md-12 col-sm-12 ">
          <div class="x_panel">
            <div class="x_title">
              <h2>{{$transaction_type}}</h2>

              <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="row">
                    <div class="col-sm-12">
                      <div class="card-box table-responsive">
              <p class="text-muted font-13 m-b-30"></p>

              <table id="datatable-responsive" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                <thead>

                  <tr>
                    <th>id</th>
                    <th>From Account</th>
                    <th>To Account</th>
                    @if ($transaction_type == "Transfer" || $transaction_type == "Withdrawal")
                        <th>Agent Account</th>
                    @endif

                    <th>Amount</th>
                    <th>Transaction Currency</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>exchange_rate</th>
                    <th>transfer_fee</th>
                    <th>Date</th>


                  </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                    <tr>
                        <td>#{{$transaction->id}}</td>
                        <td>{{$transaction->getWalletFromInformations->getUserInformations->full_name}}</td>
                        <td>{{$transaction->getWalletInformations->getUserInformations->full_name}}</td>

                        @if ($transaction_type == "Transfer" || $transaction_type == "Withdrawal")
                        @php
                            $AgentInformation=$transaction->getAgentInformationsByWallet;
                        @endphp
                            <th>
                                @if ($AgentInformation != null)
                                {{$AgentInformation->getUserInformations->full_name}}
                                @endif
                            </th>
                        @endif


                        <td>{{$transaction->transaction_amount}}</td>
                        <td>{{$transaction->transaction_currency}}</td>
                        <td>
                            <label class="badge badge-success">{{$transaction->getTransactionType->transaction_type_name}}</label>
                        </td>
                        <td>
                            <label class="badge badge-success">{{$transaction->getTransactionStatus->transaction_status_name}}</label>
                        </td>

                        <td>{{$transaction->exchange_rate}}</td>
                        <td>{{$transaction->transfer_fee}}</td>
                        <td>{{$transaction->transaction_date}}</td>

                      </tr>
                    @empty

                    @endforelse


                </tbody>
              </table>


            </div>
          </div>
        </div>
      </div>
          </div>
        </div>
      </div>
    </div>
  </div>


@endsection

