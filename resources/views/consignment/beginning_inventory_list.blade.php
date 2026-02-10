@extends('layout', [
    'namePage' => 'Stock Adjustments List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
		<div class="container">
			<div class="row pt-1">
				<div class="col-md-12 p-0 m-0">
					<div class="card card-lightblue">
						<div class="card-header p-2">
                            <div class="d-flex flex-row align-items-center justify-content-between" style="font-size: 9pt;">
                                <div class="p-0">
                                    <span class="font-responsive font-weight-bold text-uppercase m-0 p-0">Beginning Inventory List</span>
                                </div>
                                <div class="p-0">
                                    <a href="/beginning_inventory" class="btn btn-sm btn-primary m-0"><i class="fas fa-plus"></i> Create</a>
                                </div>
                            </div>
                        </div>
						<div class="card-body p-0">
							@if(session()->has('success'))
							<div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{!! session()->get('success') !!}</div>
							@endif
							@if(session()->has('error'))
							<div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{!! session()->get('error') !!}</div>
							@endif
							<div id="consignment-beginning-inventory-list" data-stores='@json($consignmentStores ?? [])' data-earliest-date="{{ $earliestDate ?? '' }}"></div>
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
        .last-row{
            width: 15% !important;
        }
		.mobile-first{
			width: 35% !important;
		}
        .filters-font{
            font-size: 13px !important;
        }
        .item-code-container{
            text-align: justify;
            padding: 10px;
        }
		.modal{
			background-color: rgba(0,0,0,0.4);
		}
        @media (max-width: 575.98px) {
			.mobile-first{
				width: 50% !important;
			}
            .last-row{
                width: 20%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media (max-width: 767.98px) {
			.mobile-first{
				width: 50% !important;
			}
            .last-row{
                width: 20%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
			.mobile-first{
				width: 50% !important;
			}
            .last-row{
                width: 20%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            var showChar = "Show more", hideChar = "Show less";
            $(document).on('click', '.show-more', function(e) {
                e.preventDefault();
                if ($(this).hasClass("sample")) {
                    $(this).removeClass("sample").text(showChar);
                } else {
                    $(this).addClass("sample").text(hideChar);
                }
                $(this).parent().prev().toggle();
                $(this).prev().toggle();
                return false;
            });

            $(document).on('click', '.allow-edit', function (){
                var target = $(this).data('target');
                var inventory = $(this).data('inv');
                var allowed_users = ['jave.kulong@fumaco.local', 'albert.gregorio@fumaco.local', 'clynton.manaois@fumaco.local', 'arjie.villanueva@fumaco.local', 'jefferson.ignacio@fumaco.local'];
                var allowed_user_group = ['Director', 'Consignment Supervisor'];
                var user = '{{ Auth::user()->wh_user }}';
                var user_group = '{{ Auth::user()->user_group }}';
                if (allowed_users.indexOf(user) > -1 || allowed_user_group.indexOf(user_group) > -1) {
                    $('#' + target + '-qty').addClass('d-none');
                    $('#' + target + '-new-qty').removeClass('d-none');
                    $('#' + target + '-price').addClass('d-none');
                    $('#' + target + '-new-price').removeClass('d-none');
                    $('#' + inventory + '-update').removeClass('d-none');
                }
            });

            $(document).on('click', '.update-btn', function (){
                var form = $(this).data('form');
                $(form).submit();
            });

            $(document).on('keyup', '.item-search-input', function() {
                var value = $(this).val().toLowerCase();
                var rows = $(this).closest('.modal-body').find('.items-table-in-modal tbody tr');
                rows.each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>
@endsection