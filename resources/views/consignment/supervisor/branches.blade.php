@extends('layout', [
    'namePage' => 'Branch List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="container pt-3">
    <div class="card card-primary">
        <div class="card-header p-3">
            Consignment Branch List
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-4">
                    <input type="text" class="form-control search" placeholder="Search...">
                </div>
                <div class="col-2">
                    <button class="btn btn-primary search-btn">Search <i class="fa fa-search"></i></button>
                </div>
                <div class="col-12 pt-2">
                    <div id="branches-tbl"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        $(document).ready(function (){
            $(document).on('click', '#reload', function (e){
                e.preventDefault();
                load_tbl(1);
            });

            $(document).on('click', '#pagination a', function(e){
                e.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                load_tbl(page);
            });

            $(document).on('click', '.search-btn', function (e){
                e.preventDefault();
                load_tbl(1);
            });

            load_tbl(1);
            function load_tbl(page){
                $.ajax({
                    type:'GET',
                    url:'/consignment/branches',
                    data: {
                        page: page,
                        search: $('.search').val()
                    },
                    success: function (response) {
                        $('#branches-tbl').html(response);
                    }
                });
            }
        });
    </script>
@endsection