@extends('layouts.master')
@section('body-contents')
	@php
		$url = route('usermgt.index');
		$edit = isset($user);
	@endphp
	<!-- BREADCRUMB -->
	<section>
		<div class="row">
			<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
				<div class="au-breadcrumb-content">
					<div class="au-breadcrumb-left">
						<nav class="breadcrumb" style="margin-bottom: .5rem">
							<a class="breadcrumb-item" href="{{ route('home-page') }}"><i class="fa fa-home"></i>&nbsp;Home</a>
							<a class="breadcrumb-item" href="{{ $url }}">&nbsp;User Mgt</a>
							<span class="breadcrumb-item active">{{ $title }}</span>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- END BREADCRUMB -->

	<div class="row">
		<div class="col-lg-12" style="padding-left: 0; padding-right: 0">
			<div class="card">
				<form name="user-reg-form" action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
					<div class="card-header">
						<div class="row">
							<h4 class="form-title" id="mainFormLabel"><span>{{ $title }}</span></h4>
							<a href="{{ $url }}" class="close" aria-label="Close">
								<span aria-hidden="true" style="color: red">&times;</span>
							</a>
						</div>
					</div>
					<div class="card-body" style="padding: 0.25rem;">
						<div class="col-lg-12">
							<div class="div-dismissible" style="height: 75px; padding-bottom: 20px; margin-left: auto; margin-right: auto;">
								<div class="sufee-alert alert with-close alert-warning alert-dismissible fade show" style="padding-right: 19rem">
									<span class="badge badge-pill badge-warning">Hint</span>
									Hints will go here.
									<button type="button" class="close" data-dismiss="alert" aria-label="Close">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>
							</div>

							{{-- Flash messages --}}
							@include('partials._flash')

							<!-- 1. Personal Details -->
							<div class="row form-wrap">
								<div class="col-lg-12">
									<div class="card-body card-block" style="padding: 0 1.25rem">
										<strong>1. Personal Details</strong>
									</div>
								</div>
							</div>
							<div class="row form-wrap">
								<div class="col-lg-6">
									<div class="card-body card-block">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="attachments" class="form-control-label">Photo:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="file" name="attachments" class="form-control attachments">
											</div>
										</div>
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="full_name" class="form-control-label">Full Name:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="text" name="full_name" placeholder="i.e John Doe Zero" class="form-control" value="{{ $edit ? $user->full_name : '' }}" required>
												<input type="hidden" name="id" value="{{ $edit ? $user->id : '' }}">
											</div>
										</div>
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="phone" class="form-control-label">Phone:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="text" name="phone" placeholder="i.e 07XXAAABBB" class="form-control" value="{{ $edit ? $user->phone : '' }}" required>
											</div>
										</div>
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="email" class="form-control-label">Email:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="email" name="email" placeholder="i.e john.doe@email.com" class="form-control" value="{{ $edit ? $user->email : '' }}" required>
											</div>
										</div>
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="gender" class="form-control-label">Gender</label>
											</div>
											<div class="col-12 col-md-3">
												<select name="gender" id="gender" class="form-control-sm form-control">
													<option value="">&nbsp;</option>
													<option {{ $edit && $user->gender == 'MALE' ? 'selected' : '' }} value="MALE">MALE</option>
													<option {{ $edit && $user->gender == 'FEMALE' ? 'selected' : '' }} value="FEMALE">FEMALE</option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- 2. Login Credentials -->
							<div class="row form-wrap">
								<div class="col-lg-12">
									<div class="card-body card-block" style="padding: 0 1.25rem">
										<strong>2. Login Credentials</strong>
									</div>
								</div>
							</div>
							<div class="row main_form form-wrap">
								<div class="col-lg-4">
									<div class="card-body card-block" style="padding-bottom: 0">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="username" class="form-control-label">Username:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="text" name="username" placeholder="i.e john.doe" class="form-control" value="{{ $edit ? $user->username : '' }}" required>
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="card-body card-block" style="padding-bottom: 0">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="password" class="form-control-label">Password:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="password" name="password" placeholder="Enter Password" class="form-control" value="">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="card-body card-block" style="padding-bottom: 0">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="password_confirmation" class="form-control-label">Confirm Password:</label>
											</div>
											<div class="col-12 col-md-8">
												<input type="password" name="password_confirmation" placeholder="Confirm Password" class="form-control" value="">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row main_form form-wrap">
								<div class="col-lg-4">
									<div class="card-body" style="padding-top: 0; padding-bottom: 0">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-4">
												<label for="active" class="form-control-label">Active:</label>
											</div>
											<div class="col-12 col-md-8">
												<label class="switch switch-text switch-primary">
													<input type="checkbox" name="active" class="switch-input" {{ $edit && $user->is_active ? 'checked' : '' }}>
													<span data-on="On" data-off="Off" class="switch-label"></span>
													<span class="switch-handle"></span>
												</label>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div><!-- /.col-lg-12 -->
					</div><!-- /.card-body -->
					<div class="col-lg-12">
						<div class="form-actions form-group">
							<button type="submit" class="btn btn-primary btn-sm float-right" id="submit_user"><i class="fa fa-save"></i>&nbsp;&nbsp;Submit</button>
							<a href="{{ $url }}" type="button" class="btn btn-cancel btn-sm"><i class="fa fa-times"></i>&nbsp;&nbsp;Cancel</a>
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>
@endsection
@section('scripts')
<script>
    $(document).ready(function () {
        divDismissible();

        var $form      = $('form[name="user-reg-form"]');
        var $submitBtn = $('#submit_user');

        $form.on('submit', function (e) {
            e.preventDefault();

            // reset prior errors
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();

            var password = $form.find('input[name="password"]').val();
            var password_confirmation = $form.find('input[name="password_confirmation"]').val();

            if ((password || password_confirmation) && password !== password_confirmation) {
                showFieldError($form.find('input[name="password_confirmation"]'), 'Passwords do not match.');
                return;
            }

            var formData = new FormData(this);
            // Unchecked checkboxes aren't submitted — set an explicit 0/1
            formData.set('active', $form.find('input[name="active"]').is(':checked') ? 1 : 0);
            console.log(formData);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () { setLoading(true); },
                success: function (response) {
                    // If the controller returns { redirect: '...' } use it, else fall back to the index
                    window.location.href = (response && response.redirect)
                        ? response.redirect
                        : "{{ route('usermgt.index') }}";
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                        $.each(errors, function (field, messages) {
                            showFieldError($form.find('[name="' + field + '"]'), messages[0]);
                        });
                    } else {
                        alert((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
                    }
                },
                complete: function () { setLoading(false); }
            });
        });

        function setLoading(isLoading) {
            if (isLoading) {
                $submitBtn.prop('disabled', true)
                    .data('original-html', $submitBtn.html())
                    .html('<i class="fa fa-spinner fa-spin"></i>&nbsp;&nbsp;Submitting...');
            } else {
                $submitBtn.prop('disabled', false)
                    .html($submitBtn.data('original-html') || '<i class="fa fa-save"></i>&nbsp;&nbsp;Submit');
            }
        }

        function showFieldError($input, message) {
            $input.addClass('is-invalid')
                  .after('<div class="invalid-feedback d-block">' + message + '</div>');
        }
    });
</script>
@endsection
