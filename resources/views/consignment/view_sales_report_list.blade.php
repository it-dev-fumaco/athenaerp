@extends('layout', [
    'namePage' => 'Sales Report',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="content">
        <div class="content-header p-0">
            <div class="container">
                <div class="row pt-1">
                    <div class="col-md-12 p-0 m-0">
                        <div class="card card-lightblue">
                            <div class="card-header text-center p-2">
                                <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Sales Report</span>
                            </div>
                            <div class="card-body p-0">
                               <table class="table" id="items-table" style="font-size: 9.5pt">
                                    <thead class="border-top">
                                        <th class="text-center p-2 align-middle" style="width: 70%">Branch / Store</th>
                                        <th class="text-center p-2 align-middle" style="width: 30%">Total Sales</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($list as $i => $row)
                                        <tr>
                                            <td class="text-left p-2 align-middle">
                                                <a href="/sales_report_items/{{ $row->name }}" class="d-block font-weight-bold">{{ $row->branch_warehouse }}</a>
                                                <small class="d-block">Date: {{ \Carbon\Carbon::parse($row->transaction_date)->format('M. d, Y') }}</small>
                                            </td>
                                            <td class="text-center p-2 align-middle">{{ '₱ ' . number_format($row->grand_total, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-uppercase text-muted text-center p-1 align-middle">No damaged item(s) reported</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                               </table>
                               <div class="mt-3 ml-3 clearfix pagination">{{ $list->links() }}</div>
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
        table {
            table-layout: fixed;
            width: 100%;   
        }
        .morectnt span {
            display: none;
        }
        .modal .confirm{
            background-color: rgba(0,0,0,0.4);
        }

    </style>
@endsection

@section('script')

@endsection