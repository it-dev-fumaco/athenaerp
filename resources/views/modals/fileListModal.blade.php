<div class="modal fade" id="fileListModal" tabindex="-1" role="dialog" aria-labelledby="Product Files">
    <form method="POST" action="/uploadFiles" enctype="multipart/form-data">
			@csrf
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="fileListModalTitle"><i class="fas fa-folder-open"></i> Product / Item Files</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group" id="upload_edit_form">
                            <input type="hidden" name="item_code" class="item-code">
                            <input type="hidden" name="fileType" class="file-type">

                            <div class="fileUpload btn btn-primary upload-btn mb-3">
                                <span><i class="fas fa-folder-open"></i> Browse File(s)</span>
                                <input type="file" name="itemFile[]" class="upload" id="browse-file" multiple />
                            </div>
                            <div class="row">
                                <div class="col-md-12" id="file-previews"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Save</button>
            </div>
        </div>
    </div>
    </form>
</div>