@extends('layout', [
    'namePage' => 'Beginning Entries User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Beginning Entries</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Beginning Entries</h6>
                            <p>Approve Submitted Beginning Inventory Entry</p>
                            <p class="mt-2 mb-2 text-justify">After the promodiser has submitted their beginning inventory entry, a consignment supervisor must approve those entries before they (promodisers) can do other transactions such as product sold entry, inventory audit, etc.</p>
                            <p class="mt-2 mb-2 text-justify">To do this, follow these steps:</p>
                            <p class="mt-2 mb-2 text-justify">1. From the dashboard, Click "<b>Beginning Entries</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_2']) ? $images['cs_2'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. You will be redirected to the list of ALL beginning inventory entries. By default, this list is filtered by "<b>For Approval</b>" status. Click "<b>View Items</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_3']) ? $images['cs_3'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can use the filters to find specific entries faster.
                            </div>
                            <p class="mt-2 mb-2 text-justify">3. You will see the list of items of that beginning inventory.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_4']) ? $images['cs_4'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can either edit this beginning inventory entry (except for the quantity) or leave a remark and send it back to the promodiser for them to edit their entry.
                            </div>
                            <p class="mt-2 mb-2 text-justify">a. You can update the price by editing the price field.</p>
                            <p class="mt-2 mb-2 text-justify">b. Add an Item.</p>
                            <p class="mt-2 mb-2 text-justify">i. You can add an item by clicking "<b>Add Items</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_5']) ? $images['cs_5'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">ii. After selecting an item to add, you have to enter its opening qty and price. Then, click "<b>Add Item</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_6']) ? $images['cs_6'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">iii. That item will be added to the list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_7']) ? $images['cs_7'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">c. Replace an item.</p>
                            <p class="mt-2 mb-2 text-justify">i. You can replace the item by clicking "<b>Change</b>" and then selecting which item to replace it with. After item selection, click "<b>Change Item</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_8']) ? $images['cs_8'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_9']) ? $images['cs_9'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">ii. This will replace the item code. If you wish to revert it back, you can click "<b>Reset</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_10']) ? $images['cs_10'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">d. Remove an item.</p>
                            <p class="mt-2 mb-2 text-justify">i. You can remove an item by clicking the "<b>Remove</b>" button.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_11']) ? $images['cs_11'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. You can add your suggestions/notes/questions for the promodisers at the bottom of the pop up, at the "<b>Remarks</b>" field.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_12']) ? $images['cs_12'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">5. After following those steps and reviewing the beginning inventory entry, you can do ANY of the following:</p>
                            <ul>
                                <li class="mb-2">Leave remarks for the promodisers – You can do this by NOT selecting anything on the selection and directly clicking "<b>Submit</b>". This will leave the beginning inventory entry under the "<b>For Approval</b>" status but all the changes made on the list will be saved.</li>
                                <li class="mb-2">Approve the entry – You can do this by selecting "<b>Approve</b>" on the selection and then clicking "<b>Submit</b>".</li>
                                <li class="mb-2">Cancel the entry – You can do this by selecting "<b>Cancel</b>" on the selection and then clicking "<b>Submit</b>".</li>
                            </ul>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_13']) ? $images['cs_13'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection
