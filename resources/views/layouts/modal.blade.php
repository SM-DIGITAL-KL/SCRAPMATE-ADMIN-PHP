
{{-- BASIC MODAL --}}
<script type="text/javascript">
    function basic_modal(id,page,head) {
        $("#modal_body").html('Loading......');
        $(".modal-title").text(head);
        $.get("{{url('/')}}/"+page+"/"+id)

        .done(function(data){
            $("#modal_body").html(data);
        })
    }
</script>
<div class="modal fade" id="basicModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body" id="modal_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light"
                    data-bs-dismiss="modal">Close</button>
                {{-- <button type="button" class="btn btn-primary">Save changes</button> --}}
            </div>
        </div>
    </div>
</div>

{{-- LARGE MODAL --}}
<script type="text/javascript">
    function large_modal(id,page,head) {
        $(".modal-body").html('Loading......');
        $(".modal-title").text(head);
        $.get("{{url('/')}}/"+page+"/"+id)

        .done(function(data){
            $("#large_modal_body").html(data);
        })
    }
</script>
<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body" id="large_modal_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light"
                    data-bs-dismiss="modal">Close</button>
                {{-- <button type="button" class="btn btn-primary">Save changes</button> --}}
            </div>
        </div>
    </div>
</div>

{{-- COMMON DELETE MODAL --}}
<script type="text/javascript">
    function custom_delete(path) {
        var url = "<?= url('/') ?>";
        var fullurl = (path.indexOf('/') === 0) ? url + path : url + '/' + path;
        $("#conf_true").attr("href", fullurl);
    }
</script>

<div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body text-center" >
                <img src="{{ asset('assets/images/icons.gif') }}"><br>
                <h5 style="display: block;" >Are you sure want to delete this?</h5><br>
                <button  data-bs-dismiss="modal" class="btn btn-primary cancel-btn ">No, Cancel</button>
                <a href="javascript:;" class="btn btn-danger yes-btn" id="conf_true">Yes, Delete</a>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
