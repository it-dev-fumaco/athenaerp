@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
    <div class="container p-0">
      <div class="row p-0 m-0">
        @if ($branches_with_pending_beginning_inventory)
          <div class="modal fade" id="pendingBeginningInventoryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header bg-navy">
                  <h5 class="modal-title" id="exampleModalLabel"><i class="fa fa-info-circle"></i> Reminder</h5>
                  <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body p-1" style="font-size: 10pt;">
                  <span class="d-block text-center mt-2">Please enter your beginning inventory for the ff:</span>
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th class="text-center p-1 text-uppercase" style="width: 70%;">Branch / Store</th>
                        <th class="text-center p-1">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach (($branches_with_pending_beginning_inventory) as $branch)
                        <tr>
                          <td class="p-1 align-middle">{{ $branch }}</td>
                          <td class="text-center p-2"><a href="/beginning_inventory?branch={{ $branch }}" class="btn btn-primary btn-xs" style="font-size: 9pt"><i class="fa fa-plus"></i> Create</a></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <script>
            $(document).ready(function(){
              $('#pendingBeginningInventoryModal').modal('show');
            });
          </script>
        @endif
        @if (session()->has('success') && isset(session()->get('success')['message']))
          @php
              $received = session()->get('success');
          @endphp
          <div class="modal fade" id="receivedDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog" role="document">
                  <div class="modal-content">
                      <div class="modal-header bg-navy">
                          <h5 class="modal-title" id="exampleModalLabel">Item(s) Received</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true" style="color: #fff">&times;</span>
                          </button>
                      </div>
                      <div class="modal-body" style="font-size: 10pt;">
                        <div class="row">
                          <div class="col-2">
                            <center>
                              <p class="text-success text-center mb-0" style="font-size: 4rem;">
                                <i class="fas fa-check-circle"></i>
                              </p>
                            </center>
                          </div>
                          <div class="col-10">
                            <span>{{ $received['message'] }}</span> <br>
                            <span>Branch: <b>{{ $received['branch'] }}</b></span> <br>
                            <span>Total Amount: <b>₱ {{ number_format(collect($received)->sum('amount'), 2) }}</b></span>
                          </div>
                        </div>
                      </div>
                  </div>
              </div>
          </div>
          <script>
              $(document).ready(function(){
                  $('#receivedDeliveryModal').modal('show');
              });
          </script>
        @endif
        <div class="col-6 p-1">
          @if (count($assigned_consignment_store) > 1)
          <a href="#" data-toggle="modal" data-target="#select-branch-modal">
          @else
          <a href="/sales_report_list/{{ $assigned_consignment_store[0] }}">
          @endif
            <div class="info-box bg-gradient-primary m-0">
              <div class="info-box-content p-0">
                <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                  <div class="p-1 text-center" style="font-size: 30px !important;"><i class="fas fa-file-invoice"></i></div>
                  <div class="p-1 text-center" style="font-size: 9pt;">Sales Report <span class="d-block" style="font-size: 7pt;">{{ $due }}</span></div>
                </div>
              </div>
            </div>
          </a>
        </div>
        <div class="col-6 p-1">
          <a href="/inventory_audit">
            <div class="info-box bg-gradient-info m-0">
              <div class="info-box-content p-0">
                <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                  <div class="p-1 text-center" style="font-size: 30px !important;"><i class="fas fa-dolly-flatbed"></i></div>
                  <div class="p-1 text-center" style="font-size: 9pt;">Inventory Report <span class="d-block" style="font-size: 7pt;">{{ $due }}</span></div>
                </div>
              </div>
            </div>
          </a>
        </div>
        <div class="col-6 p-1">
          <a href="/stock_transfer/list">
            <div class="info-box bg-gradient-warning m-0">
              <div class="info-box-content p-0">
                <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                  <div class="p-1 text-center" style="font-size: 30px !important;"><i class="fas fa-truck-loading"></i> </div>
                  <div class="p-1 text-center" style="font-size: 9pt;">Stock Transfer <span class="d-block" style="font-size: 7pt;">Pending: {{ number_format($total_stock_transfer) }}</span></div>
                </div>
              </div>
            </div>
          </a>
        </div>
        <div class="col-6 p-1">
          <a href="/consignment/replenish">
            <div class="info-box bg-gradient-secondary m-0">
              <div class="info-box-content p-0">
                <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                  <div class="p-1 text-center" style="font-size: 30px !important;"><i class="fas fa-boxes"></i></div>
                  <div class="p-1 text-center" style="font-size: 9pt;">Consignment Order <span class="d-block" style="font-size: 7pt;">Pending: {{ number_format($total_consignment_orders) }}</span></div>
                </div>
              </div>
            </div>
          </a>
        </div>
      </div>
      <div class="row p-0 m-0">
        <div class="col-md-12 p-1">
          <div class="card card-secondary card-outline mt-2 mb-2">
            <div class="card-header text-center font-weight-bold p-1 font-responsive text-uppercase">Inventory Summary</div>
            <div class="card-body p-0">
              @if (count($assigned_consignment_store) > 1)     
              <div class="p-2">
                <input type="text" class="form-control" id="store-list-search" placeholder="Search..." style="font-size: 8pt;">
              </div>
              @endif
              <ul class="list-group list-group-flush mb-2 mt-1" style="font-size: 12px;">
                @forelse ($assigned_consignment_store as $branch)
                @php
                  $items_on_hand = array_key_exists($branch, $inventory_summary) ? $inventory_summary[$branch]['items_on_hand'] : 0;
                  $total_qty = array_key_exists($branch, $inventory_summary) ? $inventory_summary[$branch]['total_qty'] : 0;
                @endphp
                <li class="list-group-item p-0">
                  <div class="d-flex flex-row align-items-center pb-1 pt-1">
                    <div class="col-8">
                      <a href="/inventory_items/{{ $branch }}">{{ $branch }}</a>
                    </div>
                    <div class="col-2 text-center">
                        <span class="d-block font-weight-bold">{{ number_format($items_on_hand) }}</span>
                        <small class="text-muted">Item(s)</small>
                    </div>
                    <div class="col-2 text-center">
                        <span class="d-block font-weight-bold">{{ number_format($total_qty) }}</span>
                        <small class="text-muted">Total Qty</small>
                    </div>
                  </div>
                </li>
                @empty
                <li class="list-group-item text-center text-muted text-uppercase">No assigned consignment branch</li>
                @endforelse
              </ul>
              <table class="table table-bordered d-none" id="store-list" style="font-size: 8pt;">
                <thead class="text-uppercase">
                  <th class="text-center p-1 align-middle" style="width: 64%;">Store</th>
                  <th class="text-center p-1 align-middle" style="width: 18%;">Items on Hand</th>
                  <th class="text-center p-1 align-middle" style="width: 18%;">Total Qty</th>
                </thead>
                <tbody>
                  @forelse ($assigned_consignment_store as $branch)
                  @php
                    $items_on_hand = array_key_exists($branch, $inventory_summary) ? $inventory_summary[$branch]['items_on_hand'] : 0;
                    $total_qty = array_key_exists($branch, $inventory_summary) ? $inventory_summary[$branch]['total_qty'] : 0;
                  @endphp
                  <tr>
                    <td class="text-justify pt-2 pb-2 pr-1 pl-1 align-middle">
                      <a href="/inventory_items/{{ $branch }}">{{ $branch }}</a>
                    </td>
                    <td class="text-center pt-2 pb-2 pr-1 pl-1 align-middle font-weight-bold">{{ number_format($items_on_hand) }}</td>
                    <td class="text-center pt-2 pb-2 pr-1 pl-1 align-middle font-weight-bold">{{ number_format($total_qty) }}</td>
                  </tr> 
                  @empty
                  <tr>
                    <td class="text-center font-weight-bold p-2 text-uppercase" colspan="3">No assigned consignment branch</td>
                  </tr> 
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>


      <div class="row p-0 m-0">
        <div class="col-md-12 p-1">
          <div class="card card-warning card-outline mt-2 mb-2">
            <div class="card-header text-center font-weight-bold p-1 font-responsive text-uppercase">To Receive Item(s)</div>
            <div class="card-body p-0" id="pending-to-receive-container"></div>
          </div>
        </div>
      </div>
      <div class="row p-0 m-0">
        <div class="col-md-12 p-1 mb-5">
          <div class="card m-0 p-1">
            <div class="card-header text-center font-weight-bold p-1">
              <span class="d-block">Sales Report</span>
              <div class="form-group pl-2 pr-2 m-1 {{ count($assigned_consignment_store) > 1 ? '' : 'd-none' }}">
                <select id="sr-branch-warehouse-select" class="form-control selection form-control-sm">
                  @foreach ($assigned_consignment_store as $branch)
                  <option value="{{ $branch }}">{{ $branch }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="card-body p-0 mt-2">
              <div class="position-relative mb-4" id="chart-container">
                <canvas id="sales-chart" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="select-branch-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header pt-2 pb-2 bg-navy">
        <h5 class="modal-title">Select Store</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" style="color: #fff">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <table class="table" style="font-size: 10pt;">
          <tbody>
            @forelse ($assigned_consignment_store as $branch)
            <tr>
              <td class="text-justify p-2 align-middle">
                <a href="/sales_report_list/{{ $branch }}">{{ $branch }}</a>
              </td>
              <td class="text-center p-2 align-middle">
                <a href="/sales_report_list/{{ $branch }}" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></a>
              </td>
            </tr> 
            @empty
            <tr>
              <td class="text-center font-weight-bold" colspan="2">No assigned consignment branch</td>
            </tr> 
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<style>
  .morectnt span {
      display: none;
  }
</style>
@endsection

@section('script')
<script>
  $(document).ready(function (){
    $('.selection').select2();
    $("#store-list-search").on("keyup", function() {
      var value = $(this).val().toLowerCase();
      $("#store-list tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
    });
        
    load_pending_to_receive(1);
    function load_pending_to_receive(page){
      $.ajax({
        type: 'GET',
        url: '/promodiser/delivery_report/pending_to_receive?page='+page,
        success: function(response){
            $('#pending-to-receive-container').html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            showNotification("danger", 'Error in getting pending to receive stock entries.', "fa fa-info");
        }
      });
    }

    $(document).on('submit', '.receive-form', function (e){
        e.preventDefault();
        $('.submit-btn').addClass('submitted').prop('disabled', true);
        var modal = $(this).data('modal-container');

        $.ajax({
            type: 'GET',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function(response){
                if(response.success){
                    load_pending_to_receive('{{ request()->has("page") ? request()->get("page") : 1 }}');
                    $(modal).modal('hide');
                    showNotification("success", response.message, "fa fa-check");
                }else{
                    showNotification("danger", response.message, "fa fa-info");
                }
                $('.submit-btn').removeClass('submitted').prop('disabled', false);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
                $('.submit-btn').removeClass('submitted').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '#delivery-report-pagination a', function(event){
        event.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        load_pending_to_receive(page);
    });

    function showNotification(color, message, icon){
      $.notify({
        icon: icon,
        message: message
      },{
        type: color,
        timer: 500,
        z_index: 1060,
        placement: {
        from: 'top',
        align: 'center'
        }
      });
    }
  });
  $(function () {
    $('.price').keyup(function(){
        var item_code = $(this).data('item-code');
        var price = $(this).val().replace(/,/g, '');
        if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
            var qty = parseInt($('#'+item_code+'-qty').text());
            var total_amount = price * qty;

            const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
            $('#'+item_code+'-amount').text(amount);
        }else{
            $('#'+item_code+'-amount').text('0');
            // $(this).val('');
        }
    });

    var showTotalChar = 150, showChar = "Show more", hideChar = "Show less";
    $('.item-description').each(function() {
      var content = $(this).text();
      if (content.length > showTotalChar) {
          var con = content.substr(0, showTotalChar);
          var hcon = content.substr(showTotalChar, content.length - showTotalChar);
          var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
          $(this).html(txt);
      }
    });

    $(".show-more").click(function(e) {
      e.preventDefault();
      if ($(this).hasClass("sample")) {
          $(this).removeClass("sample");
          $(this).text(showChar);
      } else {
          $(this).addClass("sample");
          $(this).text(hideChar);
      }

      $(this).parent().prev().toggle();
      $(this).prev().toggle();
      return false;
    });


    'use strict'
    var ticksStyle = {
      fontColor: '#495057',
      fontStyle: 'bold'
    }

    var mode = 'index'
    var intersect = true

    $(document).on('change', '#sr-branch-warehouse-select', function(){
      $('#sales-chart').remove();
      $('#chart-container').append('<canvas id="sales-chart" height="200"></canvas>');
      loadChart();
    });

    loadChart();
    function loadChart() {
      $.ajax({
        type: "GET",
        url: "/consignment_sales/" + $('#sr-branch-warehouse-select').val(),
        success: function (data) {
          new Chart($('#sales-chart'), {
            type: 'bar',
            data: {
              labels: data.labels,
              datasets: [{
                backgroundColor: '#0774C0',
                borderColor: '#0774C0',
                data: data.data
              }]
            },
            options: {
              maintainAspectRatio: false,
              tooltips: {
                mode: mode,
                intersect: intersect
              },
              hover: {
                mode: mode,
                intersect: intersect
              },
              legend: {
                display: false
              },
              scales: {
                yAxes: [{
                  ticks: $.extend({
                    beginAtZero: true,
                    // Include a dollar sign in the ticks
                    callback: function (value) {
                      if (value >= 1000) {
                        value /= 1000
                        value += 'k'
                      }

                      return '₱' + value;
                    }
                  }, ticksStyle)
                }],
                xAxes: [{
                  display: true,
                  ticks: ticksStyle
                }]
              },
              tooltips: {
                callbacks: {
                  label: function(tooltipItem) {
                    return "₱ " + tooltipItem.yLabel.toLocaleString();
                  }
                }
              }
            }
          });
        }
      });
    }
  });
</script>
@endsection