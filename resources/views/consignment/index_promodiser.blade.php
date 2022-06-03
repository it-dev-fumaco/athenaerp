@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container m-0 p-0">
            <div class="row p-0 m-0">
                <div class="col-6 p-1">
                    <div class="info-box bg-gradient-primary m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text" style="font-size: 8pt;">Sales Report Submission</span>
                            <span class="info-box-number">{{ $sales_report_submission_percentage }}%</span>
                            <span class="progress-description" style="font-size: 7pt;">{{ $duration }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 p-1">
                    <div class="info-box bg-gradient-info m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text" style="font-size: 8pt;">Stock Transfer Request</span>
                            <span class="info-box-number">0</span>
                            <div class="progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 p-1">
                    <div class="info-box bg-gradient-warning m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text" style="font-size: 8pt;">Damaged Item Report</span>
                            <span class="info-box-number">0</span>
                            <div class="progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 p-1">
                    <div class="info-box bg-gradient-secondary m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text" style="font-size: 8pt;">Stock Adjustments</span>
                            <span class="info-box-number">0</span>
                            <div class="progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row p-0 m-0">
                <div class="col-md-12 p-1">
                    <div class="card card-secondary card-outline mt-2 mb-2">
                        <div class="card-header text-center font-weight-bold p-1">Inventory Summary</div>
                        <div class="card-body p-0">
                            <table class="table table-bordered" style="font-size: 8pt;">
                                <thead class="text-uppercase">
                                    <th class="text-center p-1 align-middle" style="width: 60%;">Branch</th>
                                    <th class="text-center p-1 align-middle" style="width: 20%;">Items on Hand</th>
                                    <th class="text-center p-1 align-middle" style="width: 20%;">To Receive</th>
                                </thead>
                                <tbody>
                                    @forelse ($assigned_consignment_store as $branch)
                                    <tr>
                                        <td class="text-justify pt-2 pb-2 pr-1 pl-1 align-middle">{{ $branch }}</td>
                                        <td class="text-center pt-2 pb-2 pr-1 pl-1 align-middle font-weight-bold">0</td>
                                        <td class="text-center pt-2 pb-2 pr-1 pl-1 align-middle font-weight-bold">0</td>
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
                                <select id="" class="form-control form-control-sm">
                                    @foreach ($assigned_consignment_store as $branch)
                                    <option value="">{{ $branch }}</option>
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
            <div class="row pt-3">
                <div class="col-md-12 p-1">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center font-weight-bold">
                            Assigned Consignment Branch
                        </div>
                        <div class="card-header text-right">
                            <a href="/beginning_inventory" class="btn btn-primary font-responsive float-right">Beginning Inventory</a>
                        </div>
                        <div class="card-body p-1">

                            <table class="table table-bordered" style="font-size: 8pt;">
                                <thead>
                                    <th class="text-center p-1">Branch Name</th>
                                    <th class="text-center p-1">Action</th>
                                </thead>
                                <tbody>
                                    @forelse ($assigned_consignment_store as $branch)
                                    <tr>
                                        <td class="text-justify p-2 align-middle">{{ $branch }}</td>
                                        <td class="text-center p-2">
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
        </div>
	</div>
</div>
@endsection

@section('script')

<script>
    /* global Chart:false */

$(function () {
  'use strict'

  var ticksStyle = {
    fontColor: '#495057',
    fontStyle: 'bold'
  }

  var mode = 'index'
  var intersect = true

  var $salesChart = $('#sales-chart')
  // eslint-disable-next-line no-unused-vars
  var salesChart = new Chart($salesChart, {
    type: 'bar',
    data: {
      labels: ['JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
      datasets: [
        {
          backgroundColor: '#007bff',
          borderColor: '#007bff',
          data: [1000, 2000, 3000, 2500, 2700, 2500, 3000]
        },
        {
          backgroundColor: '#ced4da',
          borderColor: '#ced4da',
          data: [700, 1700, 2700, 2000, 1800, 1500, 2000]
        }
      ]
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
          // display: false,
          gridLines: {
            display: true,
            lineWidth: '4px',
            color: 'rgba(0, 0, 0, .2)',
            zeroLineColor: 'transparent'
          },
          ticks: $.extend({
            beginAtZero: true,

            // Include a dollar sign in the ticks
            callback: function (value) {
              if (value >= 1000) {
                value /= 1000
                value += 'k'
              }

              return '$' + value
            }
          }, ticksStyle)
        }],
        xAxes: [{
          display: true,
          gridLines: {
            display: false
          },
          ticks: ticksStyle
        }]
      }
    }
  })

  var $visitorsChart = $('#visitors-chart')
  // eslint-disable-next-line no-unused-vars
  var visitorsChart = new Chart($visitorsChart, {
    data: {
      labels: ['18th', '20th', '22nd', '24th', '26th', '28th', '30th'],
      datasets: [{
        type: 'line',
        data: [100, 120, 170, 167, 180, 177, 160],
        backgroundColor: 'transparent',
        borderColor: '#007bff',
        pointBorderColor: '#007bff',
        pointBackgroundColor: '#007bff',
        fill: false
        // pointHoverBackgroundColor: '#007bff',
        // pointHoverBorderColor    : '#007bff'
      },
      {
        type: 'line',
        data: [60, 80, 70, 67, 80, 77, 100],
        backgroundColor: 'tansparent',
        borderColor: '#ced4da',
        pointBorderColor: '#ced4da',
        pointBackgroundColor: '#ced4da',
        fill: false
        // pointHoverBackgroundColor: '#ced4da',
        // pointHoverBorderColor    : '#ced4da'
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
          // display: false,
          gridLines: {
            display: true,
            lineWidth: '4px',
            color: 'rgba(0, 0, 0, .2)',
            zeroLineColor: 'transparent'
          },
          ticks: $.extend({
            beginAtZero: true,
            suggestedMax: 200
          }, ticksStyle)
        }],
        xAxes: [{
          display: true,
          gridLines: {
            display: false
          },
          ticks: ticksStyle
        }]
      }
    }
  })
})

// lgtm [js/unused-local-variable]
</script>
@endsection