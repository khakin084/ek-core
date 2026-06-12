@if(session('success'))
    <div class="row alert au-alert-success alert-dismissible fade show au-alert au-alert--70per" role="alert">
        <i class="zmdi zmdi-check-circle"></i>
        <span style="padding-left: 1rem;" class="content">{{ session('success') }}</span>
        <button class="close" type="button" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">
                <i class="zmdi zmdi-close-circle"></i>
            </span>
        </button>
    </div>
@elseif(session('error'))
    <div class="row alert au-alert-danger alert-dismissible fade show au-alert au-alert--70per" role="alert">
        <i class="zmdi zmdi-close-circle"></i>
        <span style="padding-left: 1rem;" class="content">{{ session('error') }}</span>
        <button class="close" type="button" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">
                <i class="zmdi zmdi-close-circle"></i>
            </span>
        </button>
    </div>
@endif