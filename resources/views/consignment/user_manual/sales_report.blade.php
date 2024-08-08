@extends('layout', [
    'namePage' => 'Sales Report Entry User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Sales Report Entry</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Sales Report Entry</h6>
                            <p class="text-justify">Once your beginning inventory entry has been approved by your consignment supervisor. You have to make sales report entry for products sold each day.</p>
                            <p class="mt-2 mb-2 text-justify">1. From the dashboard, Click "<b>Sales Report</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_01']) ? $images['sales_report_01'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">If there are more than one (1) store assigned to you, this will pop up the list of stores assigned to you. Click on the store with sold products for that day. If there is only one (1) store assigned to you, you will skip this step.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_02']) ? $images['sales_report_02'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. This will display the list of previous months and their total sales.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_03']) ? $images['sales_report_03'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <ul>
                                <li class="mb-2 text-justify">Grey indicates months where a sales report has not been submitted.</li>
                                <li class="mb-2 text-justify">Red indicates months where a draft sales report submitted.</li>
                                <li class="mb-2 text-justify">Green indicates months with submitted sales report</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">3. Enter the total amount sold per day.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_04']) ? $images['sales_report_04'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. Enter the remarks at the bottom.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_05']) ? $images['sales_report_05'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. Save.</p>
                            <p class="mt-2 mb-2 text-justify font-weight-bold">After entering the total amount sold per day. You can do one of two things;</p>
                            <p class="pl-4 mt-2 mb-2 text-justify">a. <b>Save as Draft</b> - your entry will be saved but will not be submitted. You can still edit your entry after saving</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_06']) ? $images['sales_report_06'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="pl-4 mt-2 mb-2 text-justify">b. <b>Submit</b> - your entry will submitted.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_07']) ? $images['sales_report_07'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You cannot edit your sales report after submitting.
                            </div>
                            <p class="mt-2 mb-2 text-justify">● You will see a pop up with a summary of your entry. This includes the total sales amount for the month. After verifying that this data is correct, click "<b>Confirm</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['sales_report_08']) ? $images['sales_report_08'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">After confirmation, your record will be saved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection