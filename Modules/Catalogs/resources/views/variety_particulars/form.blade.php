@php $edit = isset($itemVarietyParticular); @endphp

<style>
    #main_modal {
        width: 50%;
        left: 25%;
    }
</style>

<form name="variety-partcular-reg-form" action="{{ route('variety-particulars-store') }}" method="POST" class="form-horizontal variety-partcular-form" autocomplete="off">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title">Add Variety Particular</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="col-lg-12">
            <div class="card-body card-block">
                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3">
                        <label for="name" class="form-control-label">Name:</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input name="name" type="text" class="form-control" aria-required="true" value="{{ $edit ? $itemVarietyParticular->name : '' }}">
                    </div>
                </div>
                <div class="row form-group" style="margin-bottom: 0.2rem;">
                    <div class="col col-md-3">
                        <label for="descriptions" class="form-control-label">Descriptions:</label>
                    </div>
                    <div class="col col-md-9">
                        <textarea name="descriptions" rows="3" placeholder="Any descriptions..?" class="form-control">{{ $edit ? $itemVarietyParticular->descriptions : '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary btn-sm submit_itemVarietyParticular">
                <i class="fa fa-save"></i> Submit
            </button>
        </div>
    </div>
</form>