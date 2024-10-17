@extends('layout', [
    'namePage' => 'Edit Consignment Order',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="content">
        <div class="content-header p-0">
            <div class="container">
                <div class="row pt-1">
                    <div class="col-md-12 p-0 m-0">
                        <div class="row">
                            <div class="col-2">
                                <div style="margin-bottom: -43px;">
                                    @php
                                        $redirecthref =
                                            Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                    @endphp
                                    <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i
                                            class="fas fa-arrow-left"></i></a>
                                </div>
                            </div>
                            <div class="col-10 col-lg-8 p-0">
                                <h4 class="text-center font-weight-bold m-2 text-uppercase">Edit Consignment Order</h4>
                            </div>
                        </div>

                  
                        <div class="card card-secondary card-outline">
                            <form action="/consignment_order/{{ $details->name }}/update" method="POST">
                                @csrf
                                <div class="card-header">
                                    <div class="row">
                                        @php
                                            switch ($details->consignment_status) {
                                                case 'For Approval':
                                                    $badge = 'warning';
                                                    break;
                                                case 'Approved':
                                                    $badge = 'primary';
                                                    break;
                                                case 'Delivered':
                                                    $badge = 'success';
                                                    break;
                                                case 'Cancelled':
                                                    $badge = 'danger';
                                                    break;
                                                default:
                                                    $badge = 'secondary';
                                                    break;
                                            }
                                        @endphp
                                        <div class="col-6 text-left p-1">
                                            MREQ No. <span class="font-weight-bold">{{ $details->name }}</span>
                                            <span class="badge badge-{{ $badge }}">{{ $details->consignment_status }}</span>
                                        </div>
                                       
                                        <div class="col-6 text-right">
                                            @if (in_array($details->consignment_status, ['Draft', 'For Approval']) && $details->docstatus == 0)
                                            <button class="btn btn-secondary btn-sm" name="consignment_status" value="{{ $details->consignment_status }}" type="submit"><i class="fas fa-save"></i> Save</button>
                                            @endif
                                            @if (in_array($details->consignment_status, ['For Approval']) && $details->docstatus == 0)
                                            <button class="btn btn-primary btn-sm" type="button" id="approveBtn" data-toggle="modal" data-target="#approveModal"><i class="fas fa-check"></i> Approve</button>
                                            @endif
                                            @if (in_array($details->consignment_status, ['For Approval', 'Approved']) && $details->docstatus < 2)
                                            <button class="btn btn-danger btn-sm" type="button" id="cancelBtn" data-toggle="modal" data-target="#cancelModal"><i class="fa fa-ban"></i> Cancel</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>

 
                                <!-- Modal -->
                                <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary">
                                                <h5 class="modal-title" id="exampleModalLabel">Approve Request</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="pl-5 pr-5 text-center">Approve Consignment Order Request No. <b>{{ $details->name }}</b>?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                                                <button type="submit" name="consignment_status" value="Approved" class="btn btn-primary"><i class="fa fa-check"></i> Confirm</button>
                                            </div>
                                        </div>
                                    </div> 
                                </div>              

                                <!-- Modal -->
                                <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger">
                                                <h5 class="modal-title" id="exampleModalLabel">Cancel Request</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="pl-5 pr-5 text-center">Cancel Consignment Order Request No. <b>{{ $details->name }}</b>?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                                                <button type="submit" name="consignment_status" value="Cancelled" class="btn btn-primary"><i class="fa fa-check"></i> Confirm</button>
                                            </div>
                                        </div>
                                    </div> 
                                </div>              

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12">
                                            @if(session()->has('success'))
                                            <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3">
                                                {{ session()->get('success') }}
                                            </div>
                                            @endif

                                            @if(session()->has('error'))
                                            <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3">
                                                {{ session()->get('error') }}
                                            </div>
                                            @endif
                                        </div>
                                        <div class="col-6 mb-4">
                                            <small for="customerInput" class="form-label font-weight-bold">Customer</small>
                                            <input type="text" name="customer" class="form-control form-control-sm" value="{{ $details->customer }}" id="customerInput">
                                        </div>
                                        <div class="col-3 mb-4">
                                            <small for="requestedBy" class="form-label font-weight-bold">Requested By</small>
                                            <input type="text" class="form-control form-control-sm" readonly
                                                value="{{ $details->owner }}" id="requestedBy">
                                        </div>
                                        <div class="col-3 mb-4">
                                            <small for="dateInput" class="form-label font-weight-bold">Date</small>
                                            <input type="text" class="form-control form-control-sm" readonly
                                                value="{{ \Carbon\Carbon::parse($details->creation)->format('M. d, Y - h:i A') }}"
                                                id="dateInput">
                                        </div>
                                        <div class="col-6 mb-4">
                                            <small for="branchWarehouseInput" class="form-label font-weight-bold">Branch
                                                Warehouse</small>
                                            <select name="branch" class="form-control form-control-sm" id="branchWarehouseInput">
                                                <option value="" selected>Select a Branch</option>
                                                @foreach ($consignmentStores as $store)
                                                    <option value="{{ $store }}" {{ $details->branch_warehouse == $store ? 'selected' : '' }}>{{ $store }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3 mb-4">
                                            <small for="deliveryDateInput" class="form-label font-weight-bold">Delivery Date</small>
                                            <input type="text" name="delivery_date" class="form-control form-control-sm" value="{{ $details->delivery_date }}" id="deliveryDateInput">
                                        </div>
                                        <div class="col-3 mb-4">
                                            <small for="requiredByInput" class="form-label font-weight-bold">Required By</small>
                                            <input type="text" name="required_by" class="form-control form-control-sm" value="{{ $details->required_by }}" id="requiredByInput">
                                        </div>
                                        <div class="col-6 mb-4">
                                            <small for="projectInput" class="form-label font-weight-bold">Project</small>
                                            <input type="text" name="project" class="form-control form-control-sm" value="{{ $details->project }}" id="projectInput">
                                        </div>
                                        <div class="col-6 mb-4">
                                            <small for="customerAddressInput" class="form-label font-weight-bold">Customer Address</small>
                                            <input type="text" name="customer_address" class="form-control form-control-sm" value="{{ $details->customer_address }}" id="customerAddressInput">
                                        </div>
                                        <div class="col-6 offset-6 mb-4">
                                            <small for="addressDisplayInput" class="form-label font-weight-bold">Address Display</small>
                                            <textarea readonly class="form-control form-control-sm" rows="3" id="addressDisplayInput">{{ $details->address_line . ', ' . $details->address_line2 . ', ' . $details->city_town }}</textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <table class="table">
                                                <thead>
                                                    <th class="text-center" style="width: 15%; font-size: 16px;">Item Code</th>
                                                    <th class="text-center" style="width: 45%; font-size: 16px;">Description</th>
                                                    <th class="text-center" style="width: 15%; font-size: 16px;">Quantity</th>
                                                    <th class="text-center" style="width: 15%; font-size: 16px;">Price</th>
                                                    <th class="text-center p-2" style="width: 10%;">
                                                        <button class="add-row btn btn-dark btn-sm m-0" type="button"><i class="fa fa-plus"></i> Add</button>
                                                    </th>
                                                </thead>
                                                <tbody>
                                                    @foreach ($details->items as $item)
                                                        <tr>
                                                            <td class="text-center">
                                                                <input type="text" name="item_code[]" class="form-control form-control-sm"
                                                                    style="text-align: center;" value="{{ $item->item_code }}">
                                                            </td>
                                                            <td class="text-justify">
                                                                <small>{!! strip_tags($item->description) !!}</small>
                                                            </td>

                                                            <td class="text-center">
                                                                <input type="text" name="quantity[]" class="form-control form-control-sm"
                                                                    style="text-align: center;"
                                                                    value="{{ number_format($item->qty) }}">
                                                                <small
                                                                    class="d-block mt-2 font-weight-bold">{{ $item->stock_uom }}</small>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="text" name="price[]" class="form-control form-control-sm"
                                                                    style="text-align: center;"
                                                                    value="{{ number_format($item->rate, 2) }}">
                                                            </td>
                                                            <td class="text-center">
                                                                <button class="btn btn-danger btn-xs">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $(".add-row").click(function () {
                markup = '<tr><td class="text-center">' + 
                    '<input type="text" name="item_code[]" class="form-control form-control-sm" placeholder="Enter Item Code" style="text-align: center;"></td>' +
                    '<td class="text-justify"><small></small></td><td class="text-center">' +
                        '<input type="text" name="quantity[]" class="form-control form-control-sm" placeholder="Enter Qty" style="text-align: center;">'+
                        '<small class="d-block mt-2 font-weight-bold"></small>'+
                    '</td>'+
                    '<td class="text-center">'+
                        '<input type="text" name="price[]" class="form-control form-control-sm" placeholder="Enter Price" style="text-align: center;">'+
                    '</td>'+
                    '<td class="text-center">'+
                        '<button class="btn btn-danger btn-xs">'+
                            '<i class="fa fa-trash"></i>'+
                        '</button>'+
                    '</td>'+
                '</tr>';
                tableBody = $("table tbody");
                tableBody.append(markup);
            });

            $('table').on("click", ".btn-danger", function() {
                $(this).closest("tr").remove();
            });

            $(document).on('click', '#approveBtn', function() {
                $('#approveModal').modal('show');
            });



        })
    </script>
@endsection
