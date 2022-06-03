@extends('layout', [
    'namePage' => 'Calendar Menu',
    'activePage' => 'dashboard',
    'nameDesc' => ''
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-secondary card-outline">
                      <div class="card-header text-left">
                        <span class="d-block" style="font-size: 11pt;">{{ Auth::user()->full_name }}</span>
                        <span id="branch-name" class="font-weight-bold d-block">{{ $branch }}</span>
                    </div>
                        <div class="card-body p-2">
                            <div id="calendar"></div>

                            <div class="d-flex flex-row mt-4">
                              <div class="p-1 text-success"><i class="fas fa-square" style="font-size: 15pt;"></i></div>
                              <div class="p-1" style="font-size: 9pt;">Submitted Sales Report</div>
                            </div>
                            <div class="d-flex flex-row">
                              <div class="p-1 text-warning"><i class="fas fa-square" style="font-size: 15pt;"></i></div>
                              <div class="p-1" style="font-size: 9pt;">Pending</div>
                            </div>
                            <div class="d-flex flex-row">
                              <div class="p-1 text-danger"><i class="fas fa-square" style="font-size: 15pt;"></i></div>
                              <div class="p-1" style="font-size: 9pt;">Late</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
  .fc .fc-daygrid-day.fc-day-future {
    background-color: rgba(23, 32, 42, 0.15);
    background-color: var(--fc-today-bg-color, rgba(23, 32, 42, 0.15));
    cursor: disabled;
  }
</style>
@endsection

@section('script')
<!-- fullCalendar 2.2.5 -->
<script src="{{ asset('/updated/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('/updated/plugins/fullcalendar/main.js') }}"></script>
<!-- jQuery UI -->
<script src="{{ asset('/updated/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Page specific script -->
<script>
  $(function () {  
    /* initialize the calendar -----------------------------------------------------------------*/
    var Calendar = FullCalendar.Calendar;
    var calendarEl = document.getElementById('calendar');

    var calendar = new Calendar(calendarEl, {
      height: 650,
      headerToolbar: {
        left  : '',
        center: 'title',
        right : 'prev,next dayGridMonth,timeGridWeek'
      },
      themeSystem: 'bootstrap',
      //Random default events
      eventSources: [
        {
          url: '/calendar_data/' + $('#branch-name').text(),
        }
      ],
      dateClick: function(info) {
        if (new Date(info.dateStr) > moment()) {
          showNotification("warning", 'Cannot select this date.', "fa fa-info");
        } else {
          window.location.href='/view_product_sold_form/' + $('#branch-name').text() + '/' + info.dateStr;
        }
      },
    });
  
    calendar.render();
  
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
  </script>
@endsection
