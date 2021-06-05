@extends('layouts.app_admin')

@section('content')
<div class="right_col" role="main">
    <div class="">
      <div class="clearfix"></div>

      <div class="row">

        <div class="col-md-12 col-sm-12 ">
          <div class="x_panel">
            <div class="x_title">
              <h2>Pay in-Store</h2>

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
                        <td>{{$transaction->from_user}}</td>
                        <td>{{$transaction->to_user}}</td>
                        <td>{{$transaction->transaction_amount}}</td>
                        <td>{{$transaction->transaction_currency}}</td>
                        <td>
                            <label class="badge badge-success">{{$transaction->transaction_type}}</label>
                        </td>
                        <td>
                            <label class="badge badge-success">{{$transaction->transaction_status}}</label>
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
  @push('DatatablesCSS')
  <link href="{{url('backend/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{url('backend/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{url('backend/vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{url('backend/vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{url('backend/vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css')}}" rel="stylesheet">
  @endpush

  @push('DatatablesJS')
    <script src="{{url('backend/vendors/datatables.net/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-bs/js/dataTables.bootstrap.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-buttons/js/buttons.flash.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-buttons/js/buttons.html5.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-buttons/js/buttons.print.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-keytable/js/dataTables.keyTable.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-responsive/js/dataTables.responsive.min.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js')}}"></script>
    <script src="{{url('backend/vendors/datatables.net-scroller/js/dataTables.scroller.min.js')}}"></script>
    <script src="{{url('backend/vendors/jszip/dist/jszip.min.js')}}"></script>
    <script src="{{url('backend/vendors/pdfmake/build/pdfmake.min.js')}}"></script>
    <script src="{{url('backend/vendors/pdfmake/build/vfs_fonts.js')}}"></script>
    <script>
        $('#datatable-responsive').DataTable({
            "ordering": false
        });
    </script>
    @endpush

@endsection

