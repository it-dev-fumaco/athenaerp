@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-10" style="margin: 0 auto !important;">
            <form action="/item_attribute" class="form-inline mb-2" method="GET">
                <div class="form-group">   
                    <label>Item Code </label>
                    <input type="text" class="form-control m-2" id="itemCode" name="item_code" value="{{ request('item_code') }}"/>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
        <div class="col-md-10" style="margin: 0 auto !important;">
            <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">Attribute</th>
                    <th scope="col">Attribute Value</th>
                    <th scope="col">Attribute Value Update</th>
                </tr>
            </thead>
            <tbody>
                <form id="updateForm" action="/update_attribute" method="POST">
                    @csrf
                    @forelse($itemAttrib as $itemAttribute)
                    <input type="text" name="itemCode" value="{{ $itemAttribute->parent }}" readonly hidden/>
                    <input type="text" name="attribName[]" value="{{ $itemAttribute->attribute }}" readonly hidden/>
                        <tr>
                            {{-- <!-- <td>{{ $itemAttrib['attribute'] }}</td>
                            <td>{{ $itemAttrib['attribute_value'] }}</td> --> --}}
                            <td>{{ $itemAttribute->attribute }}</td>
                            <td>{{ $itemAttribute->attribute_value }}</td>
                            <td><input type="text" id="attribVal" class="form-control" name="attrib[]" value="{{ $itemAttribute->attribute_value }}" required/></td>
                        </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center">No result(s) found.</td>
                        <input type="text" id="emptyVal" class="form-control" name="empty" value="" required hidden/>
                        <script>
                            $(document).ready(function(){
                                $("#submitBtn").addClass("empty");
                            });
                            // $(document).ready(function(){ 
                            //     $("#submitBtn").prop("disabled",true); 
                            // });
                            $(document).ready(function(){
                                $('#submitBtn').attr("disabled", "disabled");
                            });
                        </script>
                    </tr>
                    @endforelse
                    <tr>
                        <td colspan="12"><button id="submitBtn" type="submit" class="submitBtn btn btn-primary float-right">Update Attribute</button></td>
                        {{-- <td colspan="12"><input id="submitBtn" type="submit" class="btn btn-primary float-right" value="Update Attribute"/></td> --}}
                    </tr>
                </form>
            </tbody>
            </table>
        </div>
    </div>

    <style>
        .empty{
            display: none !important;
        }
    </style>

@endsection

@section('script')

@endsection