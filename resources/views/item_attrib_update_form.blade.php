@extends('item_attrib_layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-8 card bg-white p-2" style="margin: 0 auto !important;">
            <div class="box">
                <div class="box-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="text-center">Attribute</th>
                                <th class="text-center">Attribute Value</th>
                                <th class="text-center">Attribute Value Update</th>
                            </tr>
                            <form id="updateForm" action="/update_attribute" method="POST">
                                <input type="text" id="itemCodeValue" name="itemCode" value="{{ $item_code }}" readonly hidden/>
                                <span>Update {{ $item_code }} Attributes</span>
                                @csrf
                                @forelse($itemAttrib as $itemAttribute)
                                    <tr>
                                        <td>
                                            <input type="text" name="attribName[]" value="{{ $itemAttribute->attribute }}" readonly hidden/>
                                            {{ $itemAttribute->attribute }}
                                        </td>
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
        </div>
    </div>
@endsection

@section('script')

@endsection