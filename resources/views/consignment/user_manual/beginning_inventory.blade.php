@extends('layout', [
    'namePage' => 'Beginning Inventory User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Beginning Inventory</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Beginning Inventory</h6>
                            <p>Beginning Inventory will serve as the basis for the stocks of your items.</p>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You only need to create a beginning inventory entry for an item ONCE.
                            </div>
                            <p class="text-justify">After logging in, you will be redirected to the promodiser dashboard. From there, you have to start by creating a beginning inventory for all of your items.</p>
                            <p class="text-justify">To do this, follow the following steps;</p>
                            <p class="mt-2 mb-2 text-justify">1. From dashboard, Click "<b>Inventory</b>" then "<b>Beginning Inventory</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. You will see a list of beginning inventory entries, if any. Click "<b>Create</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">3. Click "<b>Select a Store</b>", you will see the list of stores assigned to you. After selecting a store, it will show you a list of items currently under that store.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. Optional: You can change the transaction date by clicking on it. By default, this shows the current date.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">5. You can then enter the qty of items and their price.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">6. In case you cannot find an item, you can start adding them by clicking the "<b>Add Items</b>" button. This opens up a modal in which you can search for the item you need. Enter it's quantity and price then click "<b>Add Item</b>". Then the item will be added on your list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_7.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="m-2 font-weight-bold">Warning Messages</p>
                            <ul>
                                <li class="mb-2 text-justify">"<b>Beginning Inventory for [item code] already exists.</b>" means that the item has a "<b>For Approval</b>" beginning inventory entry and you <u>DO NOT</u> need to create a beginning inventory entry for that item.</li>
                                <li class="mb-2 text-justify">"<b>Item [item code] already exists in the list.</b>" means that you <u>DO NOT</u> need to add that item.</li>
                            </ul>
                            <p class="mt-2 mb-2">7. You can remove an item by clicking "<b>x</b>" beside the price field.</p>   
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_8.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>        
                            <p class="mt-2 mb-2">8. You can enter your suggestions, questions and/or notes at the bottom of the page at the "<b>Remarks</b>" field.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_9.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2">9. After reviewing your entry and making sure that everything is correct, you can then click "<b>Submit</b>". You will be redirected to a page with the summary of your beginning inventory.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_10.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> Only items with Opening Stock and Price values will be saved. All Items with zero (0) stock and price WILL NOT be saved. You will have to create a separate beginning inventory entry for those items.
                            </div>
                            <p class="mt-2 mb-2">10. You can edit your beginning inventory entry as long as has not been approved by you supervisor, yet.</p>
                            <p class="mt-2 mb-2">You can do this by clicking on the name of the branch of the "<b>For Approval</b>" entry.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_11.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2">Edit any input and click "<b>Update</b>"</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_12.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> The cancel button will be replaced by the update button after editing any value.
                            </div>
                            <p class="mt-2 mb-2">11. You can also cancel your beginning inventory entry as long as has not been approved by your supervisor, yet. To do this, follow this;</p>
                            <p class="mt-2 mb-2">Click on the name of the branch of the "<b>For Approval</b>" entry.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_13.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2">Click "<b>Cancel</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/beginning_inventory_14.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You have to create new beginning inventory entry for the items you cancelled.
                            </div>
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