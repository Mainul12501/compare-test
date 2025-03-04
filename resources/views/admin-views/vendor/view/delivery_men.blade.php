@extends('layouts.admin.app')

@section('title',$store->name."'s ".translate('messages.Delivery Men'))

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">

@endpush

@section('content')
<div class="content container-fluid">
    @include('admin-views.vendor.view.partials._header',['store'=>$store])
    <!-- Page Heading -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="order">
            <div class="row pt-2">
                <div class="col-md-12">
                    <div class="card w-100">
                        <!-- Header -->
                        <div class="card-header py-2 border-0">
                            <div class="search--button-wrapper justify-content-end">
                                <h5 class="card-title mr-auto">
                                    {{translate('messages.deliveryman_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$deliveryMen->total()}}</span>
                                </h5>
                                <div class="min--200">
                                    <select name="filter" class="form-control js-select2-custom set-filter" data-filter="filter"
                                            data-url="{{ url()->full() }}">
                                        <option  value="all">{{ translate('messages.All_Types') }}</option>
                                        <option {{  request()?->get('filter') == 'active' ? 'selected' : '' }}  value="active">{{ translate('messages.Online') }}</option>
                                        <option  {{  request()?->get('filter') == 'inactive' ? 'selected' : '' }} value="inactive">{{ translate('messages.Offline') }}</option>
                                        <option {{  request()?->get('filter') == 'blocked' ? 'selected' : '' }}  value="blocked">{{ translate('messages.Suspended') }}</option>
                                    </select>
                                </div>



                                <form class="search-form">
                                    <div class="input-group input--group">
                                        <input id="datatableSearch_" type="search" name="search" class="form-control h--45px"
                                               placeholder="{{translate('ex:_DM_name_email_or_phone')}}" value="{{ request()->get('search') }}" aria-label="Search" required>
                                        <button type="submit" class="btn btn--secondary h--45px"><i class="tio-search"></i></button>

                                    </div>
                                    <!-- End Search -->
                                </form>
                                @if(request()->get('search'))
                                    <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                                @endif
                            </div>
                        </div>
                        <!-- End Header -->
                        <div class="card-body p-0">
                            <!-- Table -->
                            <div class="table-responsive datatable-custom">
                                <table id="columnSearchDatatable"
                                       class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                       data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="border-0 text-capitalize">{{translate('sl')}}</th>
                                        <th class="border-0 text-capitalize">{{translate('messages.name')}}</th>
                                        <th class="border-0 text-capitalize">{{translate('messages.contact_info')}}</th>
                                        <th class="border-0 text-capitalize">{{translate('messages.zone')}}</th>
                                        <th class="border-0 text-capitalize">{{translate('messages.Total_Completed_Orders')}}</th>
                                        <th class="border-0 text-capitalize">{{translate('messages.availability_status')}}</th>
                                        <th class="border-0 text-capitalize">{{translate('messages.Status')}}</th>
                                        <th class="border-0 text-center text-capitalize">{{translate('messages.action')}}</th>
                                    </tr>
                                    </thead>

                                    <tbody id="set-rows">
                                    @foreach($deliveryMen as $key=>$dm)
                                        <tr>
                                            <td>{{$key+$deliveryMen->firstItem()}}</td>
                                            <td>
                                                <a class="table-rest-info" href="{{route('admin.users.delivery-man.preview',['id' => $dm['id'], 'req_form' => 'admin_store'])}}">
                                                    <img class="onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                                         src="{{$dm['image_full_url'] }}"
                                                         alt="{{$dm['f_name']}} {{$dm['l_name']}}">
                                                    <div class="info">
                                                        <h5 class="text-hover-primary mb-0">{{$dm['f_name'].' '.$dm['l_name']}}</h5>
                                                        <span class="d-block text-body">
                                            <span class="rating">
                                            <i class="tio-star"></i> {{count($dm->rating)>0?number_format($dm->rating[0]->average, 1, '.', ' '):0}}
                                            </span>
                                        </span>
                                                    </div>
                                                </a>
                                            </td>
                                            <td>
                                                <a class="deco-none" href="tel:{{$dm['phone']}}">{{$dm['phone']}}</a>
                                            </td>
                                            <td>
                                                @if($dm->zone)
                                                    <label class="text--title font-medium mb-0">{{$dm->zone->name}}</label>
                                                @else
                                                    <label class="text--title font-medium mb-0">{{translate('messages.zone_deleted')}}</label>
                                                @endif
                                            </td>
                                            <td>
                                                <a class="deco-none" href="{{route('admin.users.delivery-man.preview',['id'=> $dm['id'],'tab' => 'transaction', 'req_form' => 'admin_store' ])}}">{{count($dm['order_transaction'])}}</a>
                                            </td>
                                            <td>
                                                <div>
                                                    {{translate('messages.currently_assigned_orders')}} : {{$dm->current_orders}}
                                                </div>
                                                <div>
                                                    {{translate('messages.active_status')}} :
                                                    @if($dm->application_status == 'approved')
                                                        @if($dm->active)
                                                            <strong class="text-capitalize text-primary">{{translate('messages.online')}}</strong>
                                                        @else
                                                            <strong class="text-capitalize text-secondary">{{translate('messages.offline')}}</strong>
                                                        @endif
                                                    @elseif ($dm->application_status == 'denied')
                                                        <strong class="text-capitalize text-danger">{{translate('messages.denied')}}</strong>
                                                    @else
                                                        <strong class="text-capitalize text-info">{{translate('messages.pending')}}</strong>
                                                    @endif
                                                </div>
                                            </td>

                                            <td>
                                                @if ($dm->status == 1)
                                                    <strong class="text-capitalize text-primary">{{translate('messages.Active')}}</strong>
                                                @else
                                                    <strong class="text-capitalize text-danger">{{translate('messages.Suspended')}}</strong>

                                                @endif

                                            </td>
                                            <td>
                                                <div class="btn--container justify-content-center">
                                                    <a class="btn action-btn btn--warning btn-outline-warning"
                                                       href="{{route('admin.users.delivery-man.preview',['id' => $dm['id'], 'req_form' => 'admin_store'])}}"
                                                       title="{{ translate('messages.view') }}"><i
                                                            class="tio-visible-outlined"></i>
                                                    </a>
                                                    <a class="btn action-btn btn--primary btn-outline-primary" href="{{route('admin.users.delivery-man.edit',[$dm['id']])}}" title="{{translate('messages.edit')}}"><i class="tio-edit"></i>
                                                    </a>
                                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:" data-id="delivery-man-{{$dm['id']}}" data-message="{{ translate('Want to remove this deliveryman ?') }}" title="{{translate('messages.delete')}}"><i class="tio-delete-outlined"></i>
                                                    </a>
                                                    <form action="{{route('admin.users.delivery-man.delete',[$dm['id']])}}" method="post" id="delivery-man-{{$dm['id']}}">
                                                        @csrf @method('delete')
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($deliveryMen) !== 0)
                                <hr>
                            @endif
                            <div class="page-area">
                                {!! $deliveryMen->links() !!}
                            </div>
                            @if(count($deliveryMen) === 0)
                                <div class="empty--data">
                                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                                    <h5>
                                        {{translate('no_data_found')}}
                                    </h5>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script>
        "use strict";
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function () {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function () {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.users.delivery-man.search')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.count);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
