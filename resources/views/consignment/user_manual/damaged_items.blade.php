@extends('layout', [
    'namePage' => 'Damaged Item Report User Manual',
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
                                    <li class="breadcrumb-item active" aria-current="page">Damaged Items</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Damaged Items Entry</h6>
                            <p class="mt-2 mb-2 text-justify">If you received an item with any damages or issues. You need to submit a damaged items entry.</p>
                            <p class="mt-2 mb-2 text-justify">1. From the dashboard, click Inventory > Damaged Items Entry.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>                                 
                            <p class="mt-2 mb-2 text-justify">2. Then, you will be redirected to the form. You have to select a store from the list of assigned stores to you.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">3. Click on "<b>Add Item</b>" to open up item selection. Then, you have to enter the quantity of damaged items (per item code) and a little description of the damage. Click "<b>Confirm</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">Repeat this process if there are more than one damaged item.</p>
                            <p class="mt-2 mb-2 text-justify">Warnings:</p>
                            <ul>
                                <li class="mb-2">"<b>Item [item code] already exists in the list</b>" – means that the item is already added in the list. You <u>DO NOT</u> need to add the item.</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">4. Review your entry and then click "<b>Submit</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">5. After you've physically returned the damaged item to Fumaco – Plant 2. Click Report > Damaged Item Report.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">6. Look for the item and then click "<b>View</b>". Item status is "<b>For Return</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">7. You will see a summary of the report. After reviewing and confirming that the report is correct, click "<b>Return to Plant</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/damaged_items_7.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">8. A confirmation pop up will be shown, click "<b>Confirm</b>". This will update item status in the list to "<b>Returned</b>" and the quantity will be deducted to your current stocks.</p>
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