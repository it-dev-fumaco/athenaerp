@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center">
        <div class="col-md-10 text-center" style="margin: 0 auto !important;">
            <form action="/update" class="form-inline mb-2" method="GET">
                <div class="form-group" style="margin: 0 auto !important;">   
                    <label>Item Code </label>
                    <input type="text" class="form-control m-2" id="itemCode" name="item_code" value="{{ request('item_code') }}"/>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <div class="col-md-8 card bg-white p-2" style="margin: 0 auto !important;">
            @if (\Session::has('success'))
                <div class="col-md-12 alert alert-success text-center">
                    <span id="successMessage">{!! \Session::get('success') !!}</span>
                </div>
                <br/>
            @elseif (\Session::has('insertSuccess'))
                <div class="col-md-12 alert alert-success text-center">
                    <span id="insertSuccess">{!! \Session::get('insertSuccess') !!}</span>
                </div>
                <br/>
            @elseif (\Session::has('updateFailed'))
                <div class="col-md-12 alert alert-warning text-center">
                    <span id="updateFailed">{!! \Session::get('updateFailed') !!}</span>
                </div>
                <br/>
            @endif
            @if(count($itemAttrib) <= 0)
                <div class="col-md-12 alert alert-warning text-center" style="margin: 0 auto !important;">
                    <span>Item is not a Stock Item!</span>
                </div>
            @endif
            @if(count($itemAttrib) > 0)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title float-left">{{ request('item_code') }} - Item Attributes Update Form</h3>
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#modal-info">
                        Add New
                    </button>
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
                                    {{-- <input type="text" name="attribParent[]" value="{{ $itemAttribute->variant_of }}" readonly hidden/> --}}
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

    <!-- 'Add New' Modal -->
    <div class="modal modal-info fade" id="modal-info">
        <div class="modal-dialog">
            <div class="modal-content">
                @if (\Session::has('duplicateValue'))
                    &nbsp;<br/>
                    <div class="col-md-11 alert alert-warning text-center" style="margin: 0 auto;">
                        <span id="duplicateValue">{!! \Session::get('duplicateValue') !!}</span>
                    </div>
                    <br/>
                @endif
                <div class="modal-header"><h4 class="modal-title">{{ request('item_code') }} - Add New Item Attribute</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                </div>
                <form id="insertForm" action="/insert_attribute" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="text" name="new_idx" value="{{ $idx + 1 }}" readonly hidden/>
                        <input type="text" id="itemCodeValue" name="cc_item_code" value="{{ request('item_code') }}" readonly hidden/>
                        <select name="selected_attribute_name" id="attributeName" class="form-control custom-select2" required>
                            <option value="" selected disabled>- Select Attribute -</option>
                            {{-- <option value="# of Slots"># of Slot(s)</option>
                            <option value="%23%20of%20Slots">#try</option>
                            <option value="try">try</option> --}}
                            @forelse($attribSelect as $select)
                                <option value="{{ $select->parent }}">{{ $select->parent }}</option>
                            @empty
                                <p>No result(s) found.</p>
                            @endforelse
                        </select>

                        <br/>&nbsp;
                        <select name="selected_attribute_value" id="attributeValue" class="form-control custom-select2" required>
                            <option value="" selected hidden disabled>- Select Attribute Value -</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary pull-left" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection

@section('script')
    @if (\Session::has('duplicateValue'))
        <script>
            $(function() {
                $('#modal-info').modal('show');
            });
        </script>
    @endif
<script>
    // var $attrib_name = $('#attributeName');
    // var $attrib_value = $('#attributeValue');

    $(document).ready(function() {
        $(".custom-select2").select2();
    });

    $('#attributeName').change(function(){
        var attValID = $(this).val();
        if(attValID){
            $.ajax({
                type:"GET",
                url:"{{url('attribute_dropdown')}}?attribute_name="+encodeURIComponent(attValID),
                success:function(res){        
                    if(res){
                        $('#attributeValue').empty();
                        $('#attributeValue').append('<option value="" selected disabled>- Select Attribute Value -</option>');
                        $.each(res,function(key,value){
                            $('#attributeValue').append('<option value="'+value+'">'+value+'</option>');
                        });
                    
                    }else{
                        $('#attributeValue').empty();
                    }
                }
            });
        }else{
            $('#attributeValue').empty();
        }   
    });
    
    // if(document.getElementById('emptyVal').value === ""){
    //     document.getElementById('submitBtn').style.display = "none";
    // }
    
</script>

@endsection