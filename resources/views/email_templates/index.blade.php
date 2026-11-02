@extends('layouts.main')
@section('content')
<div class="content-header">
    <div>
        <h5>Email templates</h5>
        <p class="page-subtitle">One place for every email sent by WWD Admin, WWD Business and WWD Listing. Each project loads a template by its key and sends it.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('email_layout') }}" class="btn btn-light"><i class="bi bi-layout-text-window"></i> Master layout</a>
        <a href="{{ route('email_template_create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New template</a>
    </div>
</div>
<x-flash />

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('email_templates') }}" class="users-toolbar" id="templateFilter">
            <div class="cat-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, key or subject…">
            </div>
            <select name="project" class="form-select form-select-sm" aria-label="Project">
                <option value="">All projects</option>
                @foreach (\App\Models\EmailTemplate::PROJECTS as $key => $label)
                @if ($key !== 'all')
                <option value="{{ $key }}" @selected(request('project') === $key)>{{ $label }}</option>
                @endif
                @endforeach
            </select>
            <select name="type" class="form-select form-select-sm" aria-label="Type">
                <option value="">System + custom</option>
                <option value="system" @selected(request('type') === 'system')>System</option>
                <option value="custom" @selected(request('type') === 'custom')>Custom</option>
            </select>
            @if (request()->hasAny(['q', 'project', 'type']) && (request('q') || request('project') || request('type')))
            <a href="{{ route('email_templates') }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
            <span class="users-count ms-auto">{{ $templates->count() }} {{ Str::plural('template', $templates->count()) }}</span>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Sent by</th>
                    <th>When</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($templates as $template)
                <tr>
                    <td>
                        <a href="{{ route('edit_email_template', encrypt($template->email_template_id)) }}" class="d-block">{{ $template->name }}</a>
                        <code class="tpl-key">{{ $template->template_key }}</code>
                        @if ($template->is_system)
                        <span class="tpl-tag" title="Used by code: content is editable, key can't change">System</span>
                        @else
                        <span class="tpl-tag is-custom">Custom</span>
                        @endif
                    </td>
                    <td><span class="project-pill is-{{ $template->project }}">{{ \App\Models\EmailTemplate::PROJECTS[$template->project] ?? $template->project }}</span></td>
                    <td class="text-600" style="white-space: nowrap;">{{ \App\Models\EmailTemplate::TRIGGERS[$template->trigger_type] ?? $template->trigger_type }}</td>
                    <td>{{ Str::limit($template->subject, 70) }}</td>
                    <td>
                        @if ($template->is_active)
                        <span class="badge rounded-pill d-block badge-subtle-success">Active</span>
                        @else
                        <span class="badge rounded-pill d-block badge-subtle-dark">Off</span>
                        @endif
                        @if ($sent_counts->get($template->template_key))
                        <div class="user-sub mt-1">{{ number_format($sent_counts->get($template->template_key)) }} sent by Admin</div>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">{{ $template->updated_at ? $template->updated_at->diffForHumans() : '–' }}</td>
                    <td>
                        <a class="badge bg-info rounded rounded-circle" href="{{ route('edit_email_template', encrypt($template->email_template_id)) }}" title="Edit template"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-600"><i class="bi bi-envelope d-block fs-6 mb-2"></i>No templates match.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@section('scripts')
<script>
    $('#templateFilter select').on('change', function() {
        this.form.submit();
    });
</script>
@endsection
