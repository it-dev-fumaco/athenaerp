@extends('layout', [
    'namePage' => 'Products Sold Form',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-2 pl-0 pr-0">
                <div class="col-md-12 m-0 p-0">
                    <div class="card" style="font-size: 9pt;">
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Beginning Inventory</h6>
                            <p>Beginning Inventory will serve as the basis for the stocks of your items.</p>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You only need to create a beginning inventory entry for an item ONCE.
                            </div>
                            <p>After logging in, you will be redirected to the promodiser dashboard. From there, you have to start by creating a beginning inventory for all of your items.</p>
                            <p>To do this, follow the following steps;</p>
                            <p class="mt-2 mb-2">1. From dashboard, Click "<b>Inventory</b>" then "<b>Beginning Inventory</b>".</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">2. You will see a list of beginning inventory entries, if any. Click "<b>Create</b>".</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">3. Click "<b>Select a Store</b>", you will see the list of stores assigned to you. After selecting a store, it will show you a list of items currently under that store.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">4. Optional: You can change the transaction date by clicking on it. By default, this shows the current date.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">5. You can then enter the qty of items and their price.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">6. In case you cannot find an item, you can start adding them by clicking the "<b>Add Items</b>" button. This opens up a modal in which you can search for the item you need. Enter it's quantity and price then click "<b>Add Item</b>". Then the item will be added on your list.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_7.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="m-2 font-weight-bold">Warning Messages</p>
                            <ul>
                                <li class="mb-2">"<b>Beginning Inventory for [item code] already exists.</b>" means that the item has a "<b>For Approval</b>" beginning inventory entry and you DO NOT need to create a beginning inventory entry for that item.</li>
                                <li class="mb-2">"<b>Item [item code] already exists in the list.</b>" means that you DO NOT need to add that item.</li>
                            </ul>
                            <p class="mt-2 mb-2">7. You can remove an item by clicking "<b>x</b>" beside the price field.</p>           
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_8.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">8. You can enter your suggestions, questions and/or notes at the bottom of the page at the "<b>Remarks</b>" field </p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_9.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">9. After reviewing your entry and making sure that everything is correct, you can then click "<b>Submit</b>". You will be redirected to a page with the summary of your beginning inventory.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_10.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> Only items with Opening Stock and Price values will be saved. All Items with zero (0) stock and price WILL NOT be saved. You will have to create a separate beginning inventory entry for those items.
                            </div>
                            <p class="mt-2 mb-2">10. You can edit your beginning inventory entry as long as has not been approved by you supervisor, yet.</p>
                            <p class="mt-2 mb-2">You can do this by clicking on the name of the branch of the "<b>For Approval</b>" entry.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_11.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">Edit any input and click "<b>Update</b>"</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_12.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> The cancel button will be replaced by the update button after editing any value.
                            </div>
                            <p class="mt-2 mb-2">11. You can also cancel your beginning inventory entry as long as has not been approved by your supervisor, yet. To do this, follow this;</p>
                            <p class="mt-2 mb-2">Click on the name of the branch of the "<b>For Approval</b>" entry.</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_13.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">Click "<b>Cancel</b>".</p>
                            <img src="{{ asset('storage/user_manual_img/beginning_inventory_14.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You have to create new beginning inventory entry for the items you cancelled.
                            </div>
                            <br>
                            <br>
                            <br>
                            <h6 class="font-weight-bold text-info text-uppercase">Product Sold Entry</h6>
                            <p>Once your beginning inventory entry has been approved by your consignment supervisor. You have to make product sold entry for products sold each day.</p>
                            <p class="mt-2 mb-2">1.	From the dashboard, Click "<b>Product Sold</b>".</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">If there are more than one (1) store assigned to you, this will pop up the list of stores assigned to you. Click on the store with sold products for that day. If there is only one (1) store assigned to you, you will skip this step.</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">2.	A calendar page will open, select the date with products sold.</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <ul>
                                <li class="mb-2">Green dates indicates dates with on time submission of product sold entry</li>
                                <li class="mb-2">Red dates indicates late submission of product sold entry</li>
                            </ul>
                            <p class="mt-2 mb-2">3.	Enter the quantity sold per item.</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> Only items with APPROVED Beginning inventory entry and RECEIVED items will be shown in the list. If you cannot find an item, you can search for it using the search bar or go to Inventory > Beginning Inventory and look through all the "<b>For Approval</b>" Beginning Inventory entry for the missing items.
                            </div>
                            <img src="{{ asset('storage/user_manual_img/product_sold_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">4.	After entering the quantity sold per item. Click "<b>Submit</b>" at the bottom of the page</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">5.	You will see a pop up with a summary of your entry. This includes the total sales amount and total quantity sold for that day. After verifying that this data is correct, click "<b>Confirm</b>".</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_7.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">6.	After confirmation, your record will be saved.</p>
                            <img src="{{ asset('storage/user_manual_img/product_sold_8.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">7.	You can follow this process again if you wish to edit your entry.</p>
                            <br>
                            <br>
                            <br>
                            <h6 class="font-weight-bold text-info text-uppercase">Stock Transfers</h6>
                            <p class="mt-2 mb-2">There are three (3) types of stock transfers for promodisers. They are the following:</p>
                            <ul>
                                <li>Store Transfer – Transfer an item from your store to another.</li>
                                <li>For Return – Return an item to FUMACO.</li>
                                <li>Sales Return – If a customer returns a sold item.</li>
                            </ul>
                            <p class="mt-2 mb-2">  To create a stock transfer request follow this steps;</p>
                            <p class="mt-2 mb-2">1.	Click "<b>Stock Transfer</b>" tab in the dashboard</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">2.	You will see a list of Approved and For approval stock transfer requests. Click "<b>Create</b>"</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">3.	Stock Transfer Request Form. Select purpose of your transfer</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">a.	Store Transfer – if you selected this "<b>To</b>" field will show up</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">i.	From – select from the list of stores assigned to you.</p>
                            <p class="mt-2 mb-2">ii. To – select from the list of ALL available stores you wish to transfer your item.</p>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can select your target(to) store after selecting the source(from) store.
                            </div>
                            <p class="mt-2 mb-2">b.	For Return – selecting this will automatically fill up the "<b>To</b>" field with "<b>Fumaco – Plant 2</b>"</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">i.	From – select from the list of stores assigned to you.</p>
                            <p class="mt-2 mb-2">c.	Sales Return – selecting this option will remove the "<b>From</b>" field.</p>
                            <p class="mt-2 mb-2">i.	To – select from the list of stores assigned to you.</p>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You have to complete ANY of these steps to proceed.
                            </div>
                            <p class="mt-2 mb-2">4.	After completing ANY of the steps from step 3. Click "<b>Add Item</b>".</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">5.	Use the dropdown to search and select for the item/s you wish to transfer or return</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_7.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">6.	After selecting an item, you will see how many stocks are left for that item. You can then enter the qty you want to transfer or return. Click on "<b>Add item</b>" to add the item to the list</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_8.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_9.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> If you selected "<b>Sales Return</b>", you will see the quantity sold per item instead
                            </div>
                            <p class="mt-2 mb-2">b.	Warnings:</p>
                            <ul>
                                <li class="mb-2">i.	"<b>Item [item code] already exists in the list</b>" – Item is already added on the list. You DO NOT need to add the item.</li>
                            </ul>
                            <p class="mt-2 mb-2">7.	You can also edit the quantity you wish to transfer on the list</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_10.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">8.	After reviewing your entry and making sure everything is in order, you can then click submit. After submitting the form, a new "<b>For Approval</b>" stock transfer request will be added to the list.</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_11.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">9.	You can see the status of your request by clicking "<b>Store Transfer</b>" from the dashboard. </p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_12.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> List of Sales Returns are in the Sales Returns Report. Go to Report > Sales Returns Report
                            </div>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_13.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">Then you will see the list of Approved and For Approval Sales Returns. Click "<b>View Items</b>" to verify if the request is correct.</p>
                            <img src="{{ asset('storage/user_manual_img/stock_transfer_14.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <br>
                            <br>
                            <br>
                            <h6 class="font-weight-bold text-info text-uppercase">Damaged Items Entry</h6>
                            <p class="mt-2 mb-2">If you received an item with any damages or issues. You need to submit a damaged items entry.</p>
                            <p class="mt-2 mb-2">1.	From the dashboard, click Inventory > Damaged Items Entry </p>
                                 
                            <img src="{{ asset('storage/user_manual_img/damaged_items_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">2.	Then, you will be redirected to the form. You have to select a store from the list of assigned stores to you</p>
                                  
                            <img src="{{ asset('storage/user_manual_img/damaged_items_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                           
                            <p class="mt-2 mb-2">3.	Click on ‘+ Add Item’ to open up item selection. Then, you have to enter the quantity of damaged items (per item code) and a little description of the damage. CRlick ‘Confirm’</p>
                            
<img src="{{ asset('storage/user_manual_img/damaged_items_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">a.	Repeat this process if there are more than one damaged item</p>
                            <p class="mt-2 mb-2">b.	Warnings:</p>
                            <ul>
                                <li>‘Item [item code] already exists in the list’ – means that the item is already added in the list. You DO NOT need to add the item.</li>
                            </ul>
                            <p class="mt-2 mb-2">4.	Review your entry and then click ‘Submit’</p>
                            <img src="{{ asset('storage/user_manual_img/damaged_items_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">5.	After you’ve physically returned the damaged item to Fumaco – Plant 2. Click Report > Damaged Item Report</p>
                            
<img src="{{ asset('storage/user_manual_img/damaged_items_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">6.	Look for the item and then click ‘View’. Item status is ‘For Return’</p>
                            <img src="{{ asset('storage/user_manual_img/damaged_items_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                            <p class="mt-2 mb-2">7.	You will see a summary of the report. After reviewing and confirming that the report is correct, click ‘Return to Plant’</p>
                            <img src="{{ asset('storage/user_manual_img/damaged_items_7.png') }}" style="width: 70%; margin-bottom: 30px;">

                            <p class="mt-2 mb-2">8.	A confirmation pop up will be shown, click ‘Confirm’. This will update item status in the list to ‘Returned’ and the quantity will be deducted to your current stocks.</p>
                            
                       
                      

                            
























                            
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