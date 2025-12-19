@extends('layouts.main')
@section('content')
    <div class="content-header">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="pull-left">View risk details - <span
                                class="text-primary">{{ $risk_register_data->risk_id }}</span></h5>
                    </div>
                    <div class="col-md-6 text-end">
                        FY:<span class="text-primary me-3"> {{ $risk_register_data->financial_year }}</span>
                        Quarter:<span class="text-primary me-3"> {{ $risk_register_data->quarter_name }}</span>
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#view_kri"
                            class="btn btn-outline-primary"><i class="bi bi-eye"></i> View KRI</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-6">
                        {!! risk_level_name($risk_register_data->risk_level_id) !!}
                    </div>
                    <div class="col-md-6 text-end">
                        <x-back-btn :url="url()->previous()" btn_class="btn-outline-primary" icon="bi-arrow-left" title="Back" />
                    </div>
                </div>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    <x-flash />
    <div class="row">
        <div class="col-lg-8 pe-lg-2">
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0">Key information</h6>
                </div>
                <div class="card-body fs-10">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Risk owner</h6>
                            <p class="text-1000 mb-0">{{ $risk_register_data->risk_owner_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Division</h6>
                            <p class="text-1000 mb-0">{{ $division->division_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Entity</h6>
                            <p class="text-1000 mb-0">{{ $entity->entity_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Sub entity</h6>
                            <p class="text-1000 mb-0">{{ $sub_entity->sub_entity_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Risk type</h6>
                            <p class="text-1000 mb-0">{{ $risk_type->risk_type_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Risk sub type</h6>
                            <p class="text-1000 mb-0">{{ $risk_sub_type->risk_sub_type_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Process</h6>
                            <p class="text-1000 mb-0">{{ $process->process_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-900 mb-0">Sub process</h6>
                            <p class="text-1000 mb-0">{{ $sub_process->sub_process_name }}</p>
                            <div class="border-bottom border-dashed my-3"></div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0">Risk statements</h6>
                </div>
                <div class="card-body text-justify">
                    {{ $risk_register_data->risk_statement }}
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0">Risk description</h6>
                </div>
                <div class="card-body text-justify">
                    {{ strip_tags($risk_description_first_part) }}
                    @if ($risk_description_is_long)
                        <div class="collapse" id="risk-description">
                            {!! $risk_description_remaining_part !!}
                        </div>
                    @endif
                </div>

                @if ($risk_description_is_long)
                    <div class="card-footer bg-body-tertiary p-0 border-top">
                        <button class="btn btn-link d-block w-100" type="button" data-bs-toggle="collapse"
                            data-bs-target="#risk-description" aria-expanded="false" aria-controls="risk-description">
                            <span class="collapsed">Show full <i class="fas fa-chevron-down"></i></span>
                            <span class="expanded d-none">Show less <i class="fas fa-chevron-up"></i></span>
                        </button>
                    </div>
                @endif
            </div>
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Strategic description</h6>
                </div>
                <div class="card-body text-justify">
                    {{ strip_tags($first_part) }}
                    @if ($is_long)
                        <div class="collapse" id="strategic-description">
                            {!! $remaining_part !!}
                        </div>
                    @endif
                </div>
                @if ($is_long)
                    <div class="card-footer bg-body-tertiary p-0 border-top">
                        <button class="btn btn-link d-block w-100" type="button" data-bs-toggle="collapse"
                            data-bs-target="#strategic-description" aria-expanded="false"
                            aria-controls="strategic-description">
                            <span class="collapsed">Show full <i class="fas fa-chevron-down"></i></span>
                            <span class="expanded d-none">Show less <i class="fas fa-chevron-up"></i></span>
                        </button>
                    </div>
                @endif
            </div>
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Remarks</h6>
                </div>
                <div class="card-body text-justify">
                    {{ strip_tags($remarks_first_part) }}
                    @if ($remarks_is_long)
                        <div class="collapse" id="remarks">
                            {!! $remarks_remaining_part !!}
                        </div>
                    @endif
                </div>
                @if ($remarks_is_long)
                    <div class="card-footer bg-body-tertiary p-0 border-top">
                        <button class="btn btn-link d-block w-100" type="button" data-bs-toggle="collapse"
                            data-bs-target="#remarks" aria-expanded="false" aria-controls="remarks">
                            <span class="collapsed">Show full <i class="fas fa-chevron-down"></i></span>
                            <span class="expanded d-none">Show less <i class="fas fa-chevron-up"></i></span>
                        </button>
                    </div>
                @endif
            </div>

            {{-- @if ($risk_register_data->risk_owner_id == Auth::user()->id || Auth::user()->user_role_id == 1)
                <div class="card mb-3">
                    <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Risk assessment</h6>
                    </div>
                    <div class="card-body text-justify">
                        <form action="{{ route('risk_register_status_update', $risk_register_data->risk_register_id) }}"
        method="post" id="risk_register_status_update_form">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Financial year <span
                            class="text-danger">*</span></label>
                    <select name="financial_year" id="financial_year" class="form-select">
                        <option value="">Select</option>
                        @foreach ($financial_years as $financial_year)
                        <option value="{{ $financial_year->financial_year_id }}"
                            {{ $risk_register_data->financial_year_id == $financial_year->financial_year_id ? 'selected' : '' }}>
                            {{ $financial_year->financial_year }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Assessment quarter <span
                            class="text-danger">*</span></label>
                    <select name="quarter" id="quarter" class="form-select">
                        <option value="">Select</option>
                        @foreach ($quarters as $quarter)
                        <option value="{{ $quarter->quarter_id }}"
                            {{ $quarter->quarter_id == $latest_quarter ? 'selected' : '' }}>
                            {{ $quarter->quarter_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Likelihood <span class="text-danger">*</span></label>
                    <select name="probability" id="probability" class="form-select">
                        <option value="">Select</option>
                        @foreach ($probabilities as $probability)
                        <option value="{{ $probability->probability_id }}"
                            {{ $risk_register_data->probability_id == $probability->probability_id ? 'selected' : '' }}>
                            {{ $probability->probability_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <label class="form-label">Impact <span class="text-danger">*</span></label>
                    <select name="impact" id="impact" class="form-select">
                        <option value="">Select</option>
                        @foreach ($impacts as $impact)
                        <option value="{{ $impact->impact_id }}"
                            {{ $risk_register_data->impact_id == $impact->impact_id ? 'selected' : '' }}>
                            {{ $impact->impact_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label">Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="comment" id="comment">{!! $latest_comment !!}</textarea>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </div>
        </form>
    </div>
</div>
@endif --}}

            <div class="card mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-body-tertiary">
                            <h6 class="mb-0">Link for risk attachment</h6>
                        </div>
                        <div class="card-body fs-10">
                            <div class="row g-3">
                                @if (count($files) > 0)
                                    @foreach ($files as $file)
                                        @php
                                            $extension = strtolower($file->file_ext);
                                            $is_image = in_array($extension, ['jpg', 'jpeg', 'png']);
                                            $is_pdf = $extension === 'pdf';
                                            $icon = $is_image
                                                ? asset($file->file_path)
                                                : asset('assets/img/generic/image-file-2.png');
                                            $file_url = asset($file->file_path);
                                        @endphp
                                        <div class="col-md-3">
                                            <div class="file-box card no-shadow p-2 text-center h-100"
                                                id="file-{{ $file->media_id }}">
                                                <a href="javascript:void(0);" data-bs-toggle="modal"
                                                    data-bs-target="#file_modal" data-url="{{ $file_url }}"
                                                    data-type="{{ $is_image ? 'image' : ($is_pdf ? 'pdf' : 'other') }}">
                                                    <img src="{{ $icon }}" class="rounded"
                                                        style="height:100px; width:100%; object-fit:contain;">
                                                </a>
                                                <p class="mt-2 mb-0 text-center">
                                                    {{ basename($file->file_path) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-md-12">
                                        <p class="text-center text-muted">No files found</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 ps-lg-2">
            <div class="row">
                @if ($risk_register_data->risk_level_id != 99)
                    <div class="col-12 mb-3">
                        <div class="sticky-sidebar">
                            <div class="card mb-3">
                                <div class="card-header bg-body-tertiary">
                                    <h6 class="mb-0">Risk matrix</h6>
                                </div>
                                <div class="card-body fs-10">
                                    <x-risk-management-graph :probability_rating="$risk_register_data->probability_id" :impact_rating="$risk_register_data->impact_id" :logic="1" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-12 mb-3">
                    <div class="card mb-3">
                        <div class="card-header bg-body-tertiary">
                            <h6 class="mb-0">Risk workflow</h6>
                        </div>
                        <div class="comment-section">
                            @if (count($risk_over_flow) > 0)
                                @foreach ($risk_over_flow as $log)
                                    <div class="comment">
                                        <div class="comment-header">
                                            <div class="comment-author-info-box">
                                                <div class="profile_box">
                                                    <div class="icon_box mt-3">
                                                        <img
                                                            src="{{ asset($log->profile_pic ? $log->profile_pic : 'assets/img/team/avatar.png') }}">
                                                    </div>
                                                    <p class="m-1">{{ $log->task }} by -</p>
                                                    <h5>{{ $log->work_done_by_name }}</h5>
                                                    <p>{{ date('F d, Y H:i:s', strtotime($log->updated_date_time)) }}</p>
                                                    <div class="clear"></div>
                                                </div>

                                                {{-- @if ($log->impact_id && $log->probability_id) --}}
                                                @if ($log->task == 'Assessment completed')
                                                    @if (!empty($log->comment))
                                                        <div class="comment-text-box bg-100">
                                                            @php
                                                                $comment = $log->comment ?? 'No comment';
                                                                $words = explode(' ', strip_tags($comment));
                                                                $hasMore = count($words) > 10;
                                                                $shortComment = implode(
                                                                    ' ',
                                                                    array_slice($words, 0, 10),
                                                                );
                                                                if ($hasMore) {
                                                                    $shortComment .= '...';
                                                                }
                                                            @endphp
                                                            <p>
                                                                {!! nl2br($shortComment) !!}
                                                                @if ($hasMore)
                                                                    <a href="#" class="text-link"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#commentModal{{ $log->id }}">
                                                                        View full comment
                                                                    </a>
                                                                @endif
                                                            </p>
                                                        </div>
                                                    @endif

                                                    <!-- Modal for full comment -->
                                                    <div class="modal fade" id="commentModal{{ $log->id }}"
                                                        tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Full Comment</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    {!! nl2br($comment) !!}
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-outline-danger"
                                                                        data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label>Financial Year</label>
                                                            <p>{{ $log->financial_year }}</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label>Quarter</label>
                                                            <p>Q{{ $log->quarter_id }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label>Likelihood</label>
                                                            <p>{{ $log->probability_name }}</p>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label>Impact</label>
                                                            <p>{{ $log->impact_name }}</p>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label>Risk level</label>
                                                            <p>{!! risk_level_name($log->risk_level_id) !!}</p>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label>Financial Year</label>
                                                            <p>{{ $log->financial_year }}</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label>Quarter</label>
                                                            <p>Q{{ $log->quarter_id }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <?php echo no_record_found_in_table(); ?>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- View Modal -->
    <div class="modal fade" id="view_kri" tabindex="-1" aria-labelledby="view_kri_label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="view_kri_label">View KRI</h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table mb-0 data-table fs-10">
                        <thead class="bg-200">
                            <tr>
                                <th class="text-900 sort text-nowrap">KRI statement</th>
                                <th class="text-900 sort text-nowrap">KRI description</th>
                                <th class="text-900 sort text-nowrap">Lower threshold(%)</th>
                                <th class="text-900 sort text-nowrap">Upper threshold(%)</th>
                                <th class="text-900 sort text-nowrap">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (count($view_kri) > 0)
                                @foreach ($view_kri as $value)
                                    <tr>
                                        <td>{{ Str::limit(strip_tags($value->kri_statement), 40) }}</td>
                                        <td>{{ Str::limit(strip_tags($value->kri_description), 40) }}</td>
                                        <td>{{ $value->kri_lower_thresold }}</td>
                                        <td>{{ $value->kri_upper_thresold }}</td>
                                        <td>
                                            <a href="{{ route('view_kri_master', Crypt::encrypt($value->kri_id)) }}"><i
                                                    class="bi bi-eye fs-8 p-1 text-warning"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo no_record_found_in_table(); ?></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- View Modal -->
    <div class="modal fade" id="file_modal" tabindex="-1" aria-labelledby="file_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="file_modal_label">View file</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="modal_file_content">
                </div>
            </div>
        </div>
    </div>
    <!-- Risk workflow modal -->
    <div class="modal fade" id="riskMitigationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Mitigation Plans & Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="mitigationBody">
                    <p class="text-muted text-center">Loading...</p>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        tinymce.init({
            selector: '#comment',
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link image | alignleft aligncenter alignright alignjustify | table | code',
            menubar: false,
            branding: false,
            height: 250
        });

        document.addEventListener('DOMContentLoaded', function() {
            var file_modal = document.getElementById('file_modal');
            file_modal.addEventListener('show.bs.modal', function(event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var url = trigger.getAttribute('data-url');
                var type = trigger.getAttribute('data-type');
                var modal_body = document.getElementById('modal_file_content');

                if (type === 'image') {
                    modal_body.innerHTML = `<img src="${url}" class="img-fluid" alt="Image Preview">`;
                } else if (type === 'pdf') {
                    modal_body.innerHTML =
                        `<iframe src="${url}" width="100%" height="600px" frameborder="0"></iframe>`;
                } else {
                    modal_body.innerHTML =
                        `<p>Preview not available for this file type. <a href="${url}" target="_blank">Download</a></p>`;
                }
            });
        });

        $(document).on('click', '.view-mitigation', function() {
            let riskId = $(this).data('risk-id');
            $('#mitigationBody').html('<p class="text-center text-muted">Loading...</p>');

            $.ajax({
                url: "/get-risk-actions/" + riskId,
                type: "GET",
                success: function(plans) {

                    if (plans.length === 0) {
                        $('#mitigationBody').html(
                            '<p class="text-center text-muted">No mitigation actions found.</p>');
                        return;
                    }

                    let html = '';

                    plans.forEach(function(plan, index) {
                        html += `
                    <div class="mb-3 p-3 border rounded bg-light">
                        <h6 class="mb-2"><strong>Action Plan:</strong> ${plan.action_plan}</h6>
                        <ul class="list-group">
                `;

                        if (plan.actions.length > 0) {
                            plan.actions.forEach(function(action) {
                                html += `
                            <li class="list-group-item">
                                <strong>${action.title}</strong><br>
                                <span class="text-muted">${action.description}</span>
                            </li>
                        `;
                            });
                        } else {
                            html +=
                                `<li class="list-group-item text-muted">No actions submitted.</li>`;
                        }

                        html += `
                        </ul>
                    </div>
                `;
                    });

                    $('#mitigationBody').html(html);
                }
            });

            $('#riskMitigationModal').modal('show');
        });

        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
            const spanCollapsed = btn.querySelector('.collapsed');
            const spanExpanded = btn.querySelector('.expanded');
            btn.addEventListener('click', () => {
                setTimeout(() => {
                    const isOpen = btn.getAttribute('aria-expanded') === 'true';
                    spanCollapsed.classList.toggle('d-none', isOpen);
                    spanExpanded.classList.toggle('d-none', !isOpen);
                }, 150);
            });
        });
    </script>
@endsection
