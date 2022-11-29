@extends('layout', [
    'namePage' => $purpose == 'Material Transfer' ? 'Stock Transfers List' : 'Sales Returns List',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-2">
                            <div class="d-flex flex-row align-items-center justify-content-between">
                                <div class="p-0 col-8 mx-auto text-center" style="display: flex; justify-content: center; align-items: center;">
                                    <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">{{ $purpose == 'Material Transfer' ? 'Stock Transfers List' : 'Sales Returns List'}}</span>
                                </div>
                                @if (Auth::user()->user_group == 'Promodiser')
                                    <!-- Tablet/Desktop -->
                                    <div class="dropdown d-none d-md-block">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Create
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Store Transfer" style="color: #000 !important">Create Store Transfer</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=For Return" style="color: #000 !important">Create Return to Plant</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Sales Return" style="color: #000 !important">Create Sales Return</a>
                                        </div>
                                    </div>
                                    <!-- Tablet/Desktop -->
                                    <!-- Mobile -->
                                    <div class="dropdown dropleft d-md-none">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Create
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Store Transfer" style="color: #000 !important">Create Store Transfer</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=For Return" style="color: #000 !important">Create Return to Plant</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Sales Return" style="color: #000 !important">Create Sales Return</a>
                                        </div>
                                    </div>
                                    <!-- Mobile -->
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-1">
                            <div class="d-flex flex-row align-items-center justify-content-between">
                                <div class="p-0 col-8 mx-auto text-center">
                                    <span class="font-responsive text-uppercase d-inline-block">{{ \Carbon\Carbon::now()->format('F d, Y') }}</span>
                                </div>
                                {{-- @if (Auth::user()->user_group == 'Promodiser')
                                    <!-- Tablet/Desktop -->
                                    <div class="dropdown d-none d-md-block">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Create
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Store Transfer">Create Store Transfer</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=For Return">Create Return to Plant</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Sales Return">Create Sales Return</a>
                                        </div>
                                    </div>
                                    <!-- Tablet/Desktop -->
                                    <!-- Mobile -->
                                    <div class="dropdown dropleft d-md-none">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Create
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Store Transfer">Create Store Transfer</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=For Return">Create Return to Plant</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Sales Return">Create Sales Return</a>
                                        </div>
                                    </div>
                                    <!-- Mobile -->
                                @endif --}}
                            </div>
                            @if(session()->has('success'))
                                <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <!-- Nav tabs -->
                            <ul class="nav nav-pills mt-2" id="tabs" role="tablist" style="font-size: 8pt;">
                                <li class="nav-item">
                                    <a class="nav-link nav-trigger font-weight-bold active"
                                    data-toggle="tab"
                                    data-target="store-transfer"
                                    data-purpose="Store Transfer"
                                    >Store Transfer</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-trigger font-weight-bold"
                                    data-toggle="tab"
                                    data-target="return"
                                    data-purpose="For Return"
                                    >Return to Plant</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-trigger font-weight-bold"
                                    data-toggle="tab"
                                    data-target="sales-return"
                                    data-purpose="Sales Return"
                                    >Sales Return</a>
                                </li>
                            </ul>
                        
                            <!-- Tab panes -->
                            <div class="tab-content"> 
                                <div id="store-transfer" class="container tab-pane active" style="padding: 8px 0 0 0;"></div>
                                <div id="return" class="container tab-pane" style="padding: 8px 0 0 0;"></div>
                                <div id="sales-return" class="container tab-pane" style="padding: 8px 0 0 0;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('style')
    <style>
        .morectnt span {
            display: none;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
        @media (max-width: 575.98px) {
            .mobile-first-row{
                width: 70%;
            }
        }
        @media (max-width: 767.98px) {
            .mobile-first-row{
                width: 70%;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .mobile-first-row{
                width: 70%;
            }
        }

    </style>
@endsection

@section('script')
    <script>
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

        var list_action = 'Store Transfer';
        $(document).on('click', '.nav-trigger', function (e){
            e.preventDefault();
            var table = '#' + $(this).data('target');
            list_action = $(this).data('purpose');

            loadTable(list_action, table, 1);

            $('.callout').addClass('d-none');

            $('.nav-link').removeClass('active');
            $('.tab-pane').removeClass('active');

            $(this).addClass('active');
            $(table).addClass('active');
        });

        $(document).on('click', '#transfers-pagination a', function(event){
            event.preventDefault();
            switch (list_action) {
                case 'For Return':
                    var table = '#return';
                    break;
                case 'Sales Return':
                    var table = '#sales-return';
                    break;
                default:
                    var table = '#store-transfer';
                    break;
            }

            var page = $(this).attr('href').split('page=')[1];
            loadTable(list_action, table, page);
        });

        loadTable('Store Transfer', '#store-transfer', 1);
        function loadTable(purpose, table, page) {
            $.ajax({
                type: "GET",
                url: "/stock_transfer/list/?page=" + page,
                data: {
                    purpose: purpose
                },
                success: function (response) {
                    $(table).html(response);
                }
            });
        }
    </script>    
@endsection