<div class="table-responsive p-0" style="height: 650px;">
    <table class="table table-bordered" style="font-size: 0.8rem;">
        <col style="width: 10%;">
        <col style="width: 40%;">
        <col style="width: 18%;">
        <col style="width: 10%;">
        <col style="width: 10%;">
        <col style="width: 12%;">
        <thead>
            <tr>
                <th scope="col" class="text-center align-middle">Date</th>
                <th scope="col" class="text-center align-middle">Item Description</th>
                <th scope="col" class="text-center align-middle">Warehouse</th>
                <th scope="col" class="text-center align-middle">Issued Qty</th>
                <th scope="col" class="text-center align-middle">Ref. No.</th>
                <th scope="col" class="text-center align-middle p-1">Transact by</th>
            </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                <tr>
                    <td class="text-center">
                        <span class="d-block font-weight-bold">{{ \Carbon\Carbon::parse($row->transaction_date)->format('M-d-Y h:i:A') }}</span>
                        <span class="badge {{ ($row->transaction_type == "Check Out") ? 'badge-warning' : 'badge-success' }}" style="font-size: 0.7rem;">{{ $row->transaction_type }}</span>
                    </td>
                    <td class="text-justify">
                        <span class="font-weight-bold">{{ $row->item_code }}</span> - {{ str_limit($row->description, $limit = 130, $end = '...') }}</td>
                    <td class="text-center">{{ $row->source_warehouse }}</td>
                    <td class="text-center">{{ $row->issued_qty * 1 }}</td>
                    <td class="text-center">
                        <span class="d-block">{{ $row->reference_no }}</span>
                        <span class="d-block">{{ $row->reference_parent }}</span>
                    </td>
                    <td class="text-center">{{ $row->warehouse_user }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No Records Found.</td>
                </tr>
                @endforelse
            </tbody>
    </table>
  </div>
  <ul class="pagination pagination-month justify-content-center mt-3 d-none">
    <li class="page-item"><a class="page-link" href="#">«</a></li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Jan</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item active">
        <a class="page-link" href="#">
            <p class="page-month">Feb</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Mar</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Apr</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">May</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Jun</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Jul</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Aug</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Sep</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Oct</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Nov</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item">
        <a class="page-link" href="#">
            <p class="page-month">Dec</p>
            <p class="page-year">2021</p>
        </a>
    </li>
    <li class="page-item"><a class="page-link" href="#">»</a></li>
  </ul>
  <!-- /.card-body -->