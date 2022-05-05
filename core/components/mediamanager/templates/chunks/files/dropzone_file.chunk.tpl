<div class="hidden" data-dropzone-file-template>
    <div class="dz-preview dz-file-preview">
        <div class="row">
            <div class="col-sm-2 col-lg-1">
                <img data-dz-thumbnail />
            </div>
            <div class="col-sm-3 col-lg-4">
                <p><span data-dz-name></span></p>
                <p><span data-dz-size></span></p>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <select name="c[]" class="form-control" multiple="multiple" data-placeholder="[[%mediamanager.global.categories]]" data-file-categories></select>
                </div>
                <div class="form-group">
                    <select name="t[]" class="form-control" multiple="multiple" data-placeholder="[[%mediamanager.global.tags]]" data-file-tags></select>
                </div>

                [[+metaFields]]

                <p>
                    <button type="button" class="btn btn-primary" data-copy-values>[[%mediamanager.files.copy_values]]</button>
                </p>

                [[+licensingFields]]
            </div>
            <div class="col-sm-2">
                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-bar progress-bar-success" style="width:0%;" data-dz-uploadprogress></div>
                </div>
            </div>
            <div class="col-sm-1">
                <p>
                    <button type="button" class="btn btn-danger dz-remove pull-right" data-dz-remove="">[[%mediamanager.global.delete]]</button>
                </p>
            </div>
        </div>
    </div>
</div>