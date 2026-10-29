@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Notifications</h5>
    <div class="clear"></div>
</div>
<x-flash />
@include('notifications.partials.tabs')
<div class="card mt-2">
    <div class="table-responsive scrollbar">
        <table class="table mb-0 fs-10">
            <thead class="bg-200">
                <tr>
                    <th class="text-900 text-nowrap">Name</th>
                    <th class="text-900 text-nowrap">Subject</th>
                    <th class="text-900 text-nowrap">Active</th>
                    <th class="text-900 text-nowrap">Auto-send from</th>
                    <th class="text-900 text-nowrap">Last updated</th>
                    <th class="text-900 text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($templates as $template)
                <tr>
                    <td class="text-nowrap">{{ $template->name }}</td>
                    <td>{{ $template->subject }}</td>
                    <td class="text-nowrap">{!! users_status($template->is_active ? 1 : 0) !!}</td>
                    <td class="text-nowrap">{{ $template->starts_at ? $template->starts_at->format('d-m-Y') : 'All submissions' }}</td>
                    <td class="text-nowrap">{{ date('d-m-Y h:i A', strtotime($template->updated_at)) }}</td>
                    <td class="text-nowrap">
                        <a class="badge bg-info rounded rounded-circle"
                            style="padding-top: 7px; padding-bottom: 7px;"
                            href="{{ route('edit_notification_template', encrypt($template->notification_template_id)) }}"
                            title="Edit template"><i class="bi bi-pencil fs-9"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center"><?php echo no_record_found_in_table(); ?></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
