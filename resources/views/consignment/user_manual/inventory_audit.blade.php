@extends('layout', [
    'namePage' => 'Inventory Audit User Manual',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-2 pl-0 pr-0">
                <div class="col-md-12 m-0 p-0">
                    <div class="card card-info card-outline" style="font-size: 9pt;">
                        <div class="card-header p-1">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb ml-2">
                                  <li class="breadcrumb-item"><a href="/">Home</a></li>
                                  <li class="breadcrumb-item"><a href="/user_manual">User Manuals</a></li>
                                  <li class="breadcrumb-item active" aria-current="page">Inventory Audit</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Inventory Audit</h6>
                            <p class="mt-2 mb-2 text-justify">You have to enter your inventory audit ONCE per cutoff. Inventory audit is your physical actual count of items to your assigned store/s.</p>
                            <p class="mt-2 mb-2 text-justify">To enter your Inventory Audit, follow these steps:</p>
                            <p class="mt-2 mb-2 text-justify">1. From the dashboard, click "<b>Inventory Report</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/inventory_audit_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. You will see the list of stores assigned to you without inventory audit entry for the current cutoff. Click "<b>Create</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/inventory_audit_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">3. From there, you will enter the ACTUAL PHYSICAL COUNT of your items. You have to do this on ALL of your items.</p>                          
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/inventory_audit_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">If you see this warning, this means that there is an item without an input.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/inventory_audit_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can copy that code and paste it on the search bar to easily find the item.
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. After clicking "<b>Submit</b>". A confirmation pop up will be shown with the summary of your input.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/inventory_audit_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="m-2 font-weight-bold">This includes the following:</p>
                            <ul>
                                <li class="mb-2 text-justify">Total quantity sold for the current cutoff.</li>
                                <li class="mb-2 text-justify">Total sales amount for the current cutoff.</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">5. Review your input and make sure everything is correct. Then click "<b>Confirm</b>".</p>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You CANNOT edit inventory edit report once it has been submitted.
                            </div>
                            <p class="mt-2 mb-2 text-justify">6. A new record will be shown in the Inventory Report History.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection

@section('script')

@endsection