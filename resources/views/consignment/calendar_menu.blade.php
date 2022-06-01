@extends('layout', [
    'namePage' => 'Calendar Menu',
    'activePage' => 'dashboard',
    'nameDesc' => ''
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
        <div class="container">
            <div class="row pt-3">
                <div class="col-md-12 p-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center font-weight-bold">
                            <h6>{{ Auth::user()->full_name }}</h6>
                            <span id="branch-name">{{ $branch }}</span>
                        </div>
                        <div class="card-body p-2">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
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
  
      /* initialize the external events
    //    -----------------------------------------------------------------*/
    //   function ini_events(ele) {
    //     ele.each(function () {
  
    //       // create an Event Object (https://fullcalendar.io/docs/event-object)
    //       // it doesn't need to have a start or end
    //       var eventObject = {
    //         title: $.trim($(this).text()) // use the element's text as the event title
    //       }
  
    //       // store the Event Object in the DOM element so we can get to it later
    //       $(this).data('eventObject', eventObject)
  
    //       // make the event draggable using jQuery UI
    //       $(this).draggable({
    //         zIndex        : 1070,
    //         revert        : true, // will cause the event to go back to its
    //         revertDuration: 0  //  original position after the drag
    //       })
  
    //     })
    //   }
  
    //   ini_events($('#external-events div.external-event'))
  
      /* initialize the calendar
       -----------------------------------------------------------------*/
      //Date for the calendar events (dummy data)
      var date = new Date()
      var d    = date.getDate(),
          m    = date.getMonth(),
          y    = date.getFullYear()
  
      var Calendar = FullCalendar.Calendar;
      var Draggable = FullCalendar.Draggable;
  
    //   var containerEl = document.getElementById('external-events');
    //   var checkbox = document.getElementById('drop-remove');
      var calendarEl = document.getElementById('calendar');
  
      // initialize the external events
      // -----------------------------------------------------------------
  
    //   new Draggable(containerEl, {
    //     itemSelector: '.external-event',
    //     eventData: function(eventEl) {
    //       return {
    //         title: eventEl.innerText,
    //         backgroundColor: window.getComputedStyle( eventEl ,null).getPropertyValue('background-color'),
    //         borderColor: window.getComputedStyle( eventEl ,null).getPropertyValue('background-color'),
    //         textColor: window.getComputedStyle( eventEl ,null).getPropertyValue('color'),
    //       };
    //     }
    //   });
  
      var calendar = new Calendar(calendarEl, {
        height: 650,
        headerToolbar: {
          left  : '',
          center: 'title',
          right : 'prev,next dayGridMonth,timeGridWeek'
        },
        themeSystem: 'bootstrap',
        //Random default events
        events: [
        //   {
        //     title          : 'All Day Event',
        //     start          : new Date(y, m, 1),
        //     backgroundColor: '#f56954', //red
        //     borderColor    : '#f56954', //red
        //     allDay         : true
        //   },
        //   {
        //     title          : 'Long Event',
        //     start          : new Date(y, m, d - 5),
        //     end            : new Date(y, m, d - 2),
        //     backgroundColor: '#f39c12', //yellow
        //     borderColor    : '#f39c12' //yellow
        //   },
        //   {
        //     title          : 'Meeting',
        //     start          : new Date(y, m, d, 10, 30),
        //     allDay         : false,
        //     backgroundColor: '#0073b7', //Blue
        //     borderColor    : '#0073b7' //Blue
        //   },
        //   {
        //     title          : 'Lunch',
        //     start          : new Date(y, m, d, 12, 0),
        //     end            : new Date(y, m, d, 14, 0),
        //     allDay         : false,
        //     backgroundColor: '#00c0ef', //Info (aqua)
        //     borderColor    : '#00c0ef' //Info (aqua)
        //   },
        //   {
        //     title          : 'Birthday Party',
        //     start          : new Date(y, m, d + 1, 19, 0),
        //     end            : new Date(y, m, d + 1, 22, 30),
        //     allDay         : false,
        //     backgroundColor: '#00a65a', //Success (green)
        //     borderColor    : '#00a65a' //Success (green)
        //   },
        //   {
        //     title          : 'Click for Google',
        //     start          : new Date(y, m, 28),
        //     end            : new Date(y, m, 29),
        //     url            : 'https://www.google.com/',
        //     backgroundColor: '#3c8dbc', //Primary (light-blue)
        //     borderColor    : '#3c8dbc' //Primary (light-blue)
        //   }
        ],
        // editable  : true,
        // droppable : true, // this allows things to be dropped onto the calendar !!!
        // drop      : function(info) {
        //   // is the "remove after drop" checkbox checked?
        //   if (checkbox.checked) {
        //     // if so, remove the element from the "Draggable Events" list
        //     info.draggedEl.parentNode.removeChild(info.draggedEl);
        //   }
        // }
        dateClick: function(info) {
            window.location.href='/view_product_sold_form/' + $('#branch-name').text() + '/' + info.dateStr;
        }
      });
  
      calendar.render();
      // $('#calendar').fullCalendar()
  
    //   /* ADDING EVENTS */
    //   var currColor = '#3c8dbc' //Red by default
    //   // Color chooser button
    //   $('#color-chooser > li > a').click(function (e) {
    //     e.preventDefault()
    //     // Save color
    //     currColor = $(this).css('color')
    //     // Add color effect to button
    //     $('#add-new-event').css({
    //       'background-color': currColor,
    //       'border-color'    : currColor
    //     })
    //   })
    //   $('#add-new-event').click(function (e) {
    //     e.preventDefault()
    //     // Get value and make sure it is not null
    //     var val = $('#new-event').val()
    //     if (val.length == 0) {
    //       return
    //     }
  
    //     // Create events
    //     var event = $('<div />')
    //     event.css({
    //       'background-color': currColor,
    //       'border-color'    : currColor,
    //       'color'           : '#fff'
    //     }).addClass('external-event')
    //     event.text(val)
    //     $('#external-events').prepend(event)
  
    //     // Add draggable funtionality
    //     ini_events(event)
  
    //     // Remove event from text input
    //     $('#new-event').val('')
    //   })
    })
  </script>
@endsection
