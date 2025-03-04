@extends('layouts.admin.app')

@section('title',translate('messages.Pending Requests List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="col-10 mx-auto mt-3">
                <div class="card">
                    <div class="card-header">
                        <h3>Pending Items</h3>
                    </div>
                    <div class="card-body">
                        <div>
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Module Name</th>
                                        <th>Pending Order</th>
                                        <th>Pending Items</th>
                                        <th>Pending Store</th>
                                        <th>Pending Refund</th>
                                        <th>Pending Ads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($modules as $module)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $module->module_name ?? '' }}</td>
                                        <td><a href="{{ route('admin.redirect-to-pending-pages', ['module_id' => $module->id, 'type' => 'order']) }}" class="nav">{{ $module->pending_orders ?? 0 }}</a></td>
                                        <td><a href="{{ route('admin.redirect-to-pending-pages', ['module_id' => $module->id, 'type' => 'item']) }}" class="nav">{{ $module->pending_items ?? 0 }}</a></td>
                                        <td><a href="{{ route('admin.redirect-to-pending-pages', ['module_id' => $module->id, 'type' => 'store']) }}" class="nav">{{ $module->pending_stores ?? 0 }}</a></td>
                                        <td><a href="{{ route('admin.redirect-to-pending-pages', ['module_id' => $module->id, 'type' => 'refund']) }}" class="nav">{{ $module->refund_requests ?? 0 }}</a></td>
                                        <td><a href="{{ route('admin.redirect-to-pending-pages', ['module_id' => $module->id, 'type' => 'ads']) }}" class="nav">{{ $module->ads_requests ?? 0 }}</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-8 mx-auto mt-3">
                <div class="card">
                    <div class="card-header">
                        <h3>Other Pending Requests</h3>
                    </div>
                    <div class="card-body">
                        <div>
                            <table class="table" id="itemsTable">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Request Name</th>
                                    <th>Total Pending Requests</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><a href="{{ route('admin.redirect-to-pending-pages', ['module_id' => 1, 'type' => 'store_dis_req']) }}" class="nav">Pending Store Disbursement Requests</a></td>
                                    <td>{{ $disbursment_requests_count }}</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><a href="{{ url('/admin/users/delivery-man/new') }}" class="nav">Pending Deliveryman Requests</a></td>
                                    <td>{{ $pending_dm_count }}</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><a href="{{ url('/admin/transactions/store/offline_payment/company-list/pending') }}" class="nav">Company Offline Payment Requests</a></td>
                                    <td>{{ $OfflinePayments }}</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td><a href="{{ url('/admin/users/delivery-man/dm-pending-disbursement-requests') }}" class="nav">Pending DM Disbursment Requests</a></td>
                                    <td>{{ $dm_disbursment_requests_count }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection

@push('script_2')
    <link rel="stylesheet" href="//cdn.datatables.net/2.2.1/css/dataTables.dataTables.min.css">
    <script src="//cdn.datatables.net/2.2.1/js/dataTables.min.js"></script>
    <script>
        $(function () {
            let orderTable = new DataTable('#orderTable');
            let itemsTable = new DataTable('#itemsTable');
        })
    </script>
@endpush
