@extends('item_attrib_layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center p-5">
        <div class="col-md-10 text-center" style="margin: 0 auto !important;">
            <form id="insertForm" action="/insert_attribute" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8 text-center" style="margin: 0 auto !important;">
                        <span>Add New Attributes to {{ $item_code }}</span>

                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="text-center">Attribute</th>
                                    <th class="text-center">Attribute Value</th>
                                </tr>
                                <form id="updateForm" action="/update_attribute" method="POST">
                                    @csrf
                                    @forelse($itemAttrib as $itemAttribute)
                                        <tr>
                                            <td>{{ $itemAttribute->attribute }}</td>
                                            <td>{{ $itemAttribute->attribute_value }}</td>
                                        </tr>
                                    @empty
                                    <tr>
                                        <td colspan="12" class="text-center">No result(s) found.</td>
                                        <input type="text" id="emptyVal" class="form-control" name="empty" value="" required hidden/>
                                    </tr>
                                    @endforelse
                                </form>       
                            </tbody>
                        </table> 

                        <input type="text" name="new_idx" value="{{ $idx + 1 }}" readonly hidden/>
                        <input type="text" id="itemCodeValue" name="item_code" value="{{ $item_code }}" readonly hidden/>
                        <select name="selected_attribute_name" id="attributeName" class="form-control custom-select2" required>
                            <option value="" selected disabled>- Select Attribute -</option>
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
                        <br/>
                        <hr/>
                        <button type="button" class="btn btn-secondary btn-lg pull-left" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-lg">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
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

    </script>
@endsection