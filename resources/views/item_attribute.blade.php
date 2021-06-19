@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-10 text-center" style="margin: 0 auto !important;">
            <form action="/item_attribute" class="form-inline mb-2" method="GET">
                <div class="form-group" style="margin: 0 auto !important;">   
                    <label>Item Code </label>
                    <input type="text" class="form-control m-2" id="itemCode" name="item_code" value="{{ request('item_code') }}"/>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <div class="col-md-8 card bg-white p-2" style="margin: 0 auto !important;">
            @if (\Session::has('success'))
                <div class="col-md-12 alert alert-success text-center" style="margin: 0 auto !important;">
                    <span id="successMessage">{!! \Session::get('success') !!}</span>
                </div>
            @endif
            @if(count($itemAttrib) <= 0)
                <div class="col-md-12 alert alert-warning text-center" style="margin: 0 auto !important;">
                    <span>Item is not a Stock Item!</span>
                </div>
            @endif
            @if(count($itemAttrib) > 0)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ request('item_code') }} - Item Attributes</h3>
                </div>
                <div class="box-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="text-center">Attribute</th>
                                <th class="text-center">Attribute Value</th>
                                <th class="text-center">Attribute Value Update</th>
                            </tr>
                            <form id="updateForm" action="/update_attribute" method="POST">
                                @csrf
                                @forelse($itemAttrib as $itemAttribute)
                                    <input type="text" id="itemCodeValue" name="itemCode" value="{{ request('item_code') }}" readonly hidden/>
                                    <input type="text" name="attribName[]" value="{{ $itemAttribute->attribute }}" readonly hidden/>
                                    <tr>
                                        <td>{{ $itemAttribute->attribute }}</td>
                                        <td>
                                            <input type="text" name="currentAttrib[]" value="{{ $itemAttribute->attribute_value }}" readonly hidden/>
                                            {{ $itemAttribute->attribute_value }}
                                        </td>
                                        <td class="p-1"><input type="text" id="attribVal" class="form-control" name="attrib[]" value="{{ $itemAttribute->attribute_value }}" required/></td>
                                    </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center">No result(s) found.</td>
                                    <input type="text" id="emptyVal" class="form-control" name="empty" value="" required hidden/>
                                </tr>
                                @endforelse
                                <tr>
                                    <td colspan="12"><button id="submitBtn" type="submit" class="submitBtn btn btn-primary float-right">Update Attribute</button></td>
                                </tr>
                            </form>       
                        </tbody>
                    </table> 
                </div>
            </div>
            @endif
        </div>
    </div>

@endsection

@section('script')

<script>
    if(document.getElementById('emptyVal').value === ""){
        document.getElementById('submitBtn').style.display = "none";
    }
</script>

@endsection