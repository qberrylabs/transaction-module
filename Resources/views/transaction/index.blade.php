@extends('layouts.app')

@section('content')
<div class="container">
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
    <div class="row">
        <!-- Left Panel  -->
        @include('include.left_panel')
        <!-- Left Panel End -->

        <!-- Middle Panel -->
        <div class="col-lg-9">

            @if (Request::is('request/panding'))
                <h2 class="font-weight-400 mb-3">Pending Request</h2>
                @include('transactionmodule::transaction.components.request_transactions')
            @endif

            @if (Request::is('transactions/all') || Request::is('transactions/filter'))
                <h2 class="font-weight-400 mb-3">All Transactions</h2>
                @include('transactionmodule::transaction.components.filter')
                @include('transactionmodule::transaction.components.all_transactions')
            @endif

        </div>
        <!-- Middle Panel End -->
    </div>
</div>
@endsection
