@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
    <div class="container p-0">
      <div class="row p-0 m-0">
        <div class="col-6 p-1">
          @if (count($assigned_consignment_store) > 1)
          <a href="#" data-toggle="modal" data-target="#select-branch-modal">
          @else
          <a href="/view_calendar_menu/{{ $assigned_consignment_store[0] }}">
          @endif
            <div class="info-box bg-gradient-primary m-0">
              <div class="info-box-content p-1">
                <span class="info-box-text" style="font-size: 9pt;">Product Sold</span>
                <span class="info-box-number">{{ number_format($total_item_sold) }}</span>
                <span class="progress-description" style="font-size: 7pt;">{{ $duration }}</span>
              </div>
            </div>
          </a>
        </div>
        <div class="col-6 p-1">
          <a href="/inventory_audit">
            <div class="info-box bg-gradient-info m-0">
              <div class="info-box-content p-1">
                <span class="info-box-text" style="font-size: 9pt;">Inventory Audit</span>
                <span class="info-box-number">{{ number_format($total_pending_inventory_audit) }}</span>
                <span class="progress-description" style="font-size: 7pt;">{{ $duration }}</span>
              </div>
            </div>
          </a>
        </div>
        <div class="col-6 p-1">
          <a href="/stock_transfer/list">
            <div class="info-box bg-gradient-warning m-0">
              <div class="info-box-content p-1">
                <span class="info-box-text" style="font-size: 9pt;">Stock Transfer</span>
                <span class="info-box-number">{{ number_format($total_stock_transfer) }}</span>
                <div class="progress">
                  <div class="progress-bar"></div>
                </div>
              </div>
            </div>
          </a>
        </div>
        <div class="col-6 p-1">
          <a href="/beginning_inv_list">
            <div class="info-box bg-gradient-secondary m-0">
              <div class="info-box-content p-1">
                <span class="info-box-text" style="font-size: 9pt;">Stock Adjustment</span>
                <span class="info-box-number">0</span>
                <div class="progress">
                  <div class="progress-bar"></div>
                </div>
              </div>
            </div>
          </a>
        </div>
      </div>
      <div class="row p-0 m-0">
        <div class="col-md-12 p-1">
          <div class="card card-secondary card-outline mt-2 mb-2">
            <div class="card-header text-center font-weight-bold p-1">Inventory Summary</div>
            <div class="card-body p-0">
              <table class="table table-bordered" style="font-size: 8pt;">
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
                    <td class="text-justify pt-2 pb-2 pr-1 pl-1 align-middle">{{ $branch }}</td>
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
        <div class="col-md-12 p-1 mb-5">
          <div class="card m-0 p-1">
            <div class="card-header text-center font-weight-bold p-1">
              <span class="d-block">Sales Report</span>
              <div class="form-group pl-2 pr-2 m-1">
                <select id="sr-branch-warehouse-select" class="form-control form-control-sm {{ count($assigned_consignment_store) > 1 ? '' : 'd-none' }}">
                  @foreach ($assigned_consignment_store as $branch)
                  <option value="{{ $branch }}">{{ $branch }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="card-body p-0 mt-2">
              <div class="position-relative mb-4">
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
                <a href="/view_calendar_menu/{{ $branch }}">{{ $branch }}</a>
              </td>
              <td class="text-center p-2 align-middle">
                <a href="/view_calendar_menu/{{ $branch }}" class="btn btn-primary btn-xs"><i class="fas fa-search"></i></a>
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
@endsection

@section('script')
<script>
  $(function () {
    'use strict'
    var ticksStyle = {
      fontColor: '#495057',
      fontStyle: 'bold'
    }

    var mode = 'index'
    var intersect = true

    $(document).on('change', '#sr-branch-warehouse-select', function(){
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