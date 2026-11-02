<?php

namespace App\Http\Controllers;

use App\Helpers\EmailTemplateRenderer;
use App\Helpers\NotificationSender;
use App\Mail\NotificationMail;
use App\Models\EmailLayout;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function email_templates(Request $request)
    {
        $templates = EmailTemplate::orderBy('name')
            ->when($request->filled('project'), fn($q) => $q->whereIn('project', [$request->project, 'all']))
            ->when($request->type === 'system', fn($q) => $q->where('is_system', 1))
            ->when($request->type === 'custom', fn($q) => $q->where('is_system', 0))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('template_key', 'like', '%' . $request->q . '%')
                        ->orWhere('subject', 'like', '%' . $request->q . '%');
                });
            })
            ->get();

        // Emails sent per template (from the admin's own log) in one query
        $sent_counts = NotificationLog::where('status', 'sent')->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('email_templates.index', compact('templates', 'sent_counts'));
    }

    public function email_template_create()
    {
        $template = new EmailTemplate([
            'project'      => 'admin',
            'trigger_type' => 'manual',
            'is_active'    => true,
            'variables'    => [
                ['name' => 'BusinessOwnerName', 'sample' => 'Priya Sharma', 'description' => 'Business owner full name'],
                ['name' => 'BusinessName', 'sample' => 'Sunrise Bakery', 'description' => 'Business name'],
            ],
        ]);

        return view('email_templates.form', compact('template'));
    }

    public function email_template_store(Request $request)
    {
        $this->validate_template($request, null);

        $template = new EmailTemplate();
        $template->template_key = $request->template_key;
        $template->is_system = 0;
        $this->fill_template($template, $request);

        return redirect()->route('edit_email_template', encrypt($template->email_template_id))->with('success', 'Template "' . $template->name . '" created.');
    }

    public function edit_email_template($id)
    {
        $template = EmailTemplate::findOrFail($this->decrypt_id($id));
        return view('email_templates.form', compact('template'));
    }

    public function edit_email_template_action(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($this->decrypt_id($id));
        $this->validate_template($request, $template);

        // System template keys are used by code in the three projects and never change
        if (!$template->is_system) {
            $template->template_key = $request->template_key;
        }
        $this->fill_template($template, $request);

        return redirect()->back()->with('success', 'Template "' . $template->name . '" saved.');
    }

    public function delete_email_template($id)
    {
        $template = EmailTemplate::findOrFail($this->decrypt_id($id));
        if ($template->is_system) {
            return redirect()->back()->with('error', 'System templates are used by the WWD projects and can\'t be deleted. Switch it off instead.');
        }
        $template->delete();

        return redirect()->route('email_templates')->with('success', 'Template "' . $template->name . '" deleted.');
    }

    // Live preview of the unsaved form, filled with each variable's sample value
    public function email_template_preview(Request $request)
    {
        $email = $this->render_from_request($request);
        return response()->json($email);
    }

    public function email_template_test(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        $email = $this->render_from_request($request);
        try {
            Mail::to($request->test_email)->send(new NotificationMail('[TEST] ' . $email['subject'], $email['html']));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not send: ' . Str::limit($e->getMessage(), 300)], 500);
        }

        return response()->json(['message' => 'Test email sent to ' . $request->test_email . '.']);
    }

    public function email_layout()
    {
        $layout = EmailLayout::where('is_default', 1)->firstOrFail();
        return view('email_templates.layout', compact('layout'));
    }

    public function email_layout_action(Request $request)
    {
        $request->validate([
            'html'          => ['required', 'string', function ($attribute, $value, $fail) {
                if (!str_contains($value, '{{Content}}')) {
                    $fail('The layout must contain {{Content}}, where each template\'s body goes.');
                }
            }],
            'button_html'   => 'required|string',
            'logo_url'      => 'nullable|url|max:255',
            'brand_color'   => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site_url'      => 'nullable|url|max:255',
            'support_email' => 'nullable|email|max:255',
            'footer_text'   => 'nullable|string|max:255',
        ]);

        $layout = EmailLayout::where('is_default', 1)->firstOrFail();
        $layout->fill($request->only('html', 'button_html', 'logo_url', 'brand_color', 'site_url', 'support_email', 'footer_text'));
        $layout->updated_by = Auth::id();
        $layout->save();

        return redirect()->back()->with('success', 'Master layout saved. All emails now use it.');
    }

    // Layout page preview: the edited layout around the "business approved" template
    public function email_layout_preview(Request $request)
    {
        $template = EmailTemplate::where('template_key', NotificationSender::BUSINESS_APPROVED)->first() ?? EmailTemplate::first();
        $layout = $request->only('html', 'button_html', 'logo_url', 'brand_color', 'site_url', 'support_email', 'footer_text');

        return response()->json(EmailTemplateRenderer::render($template->getAttributes(), $layout, $this->sample_data($template->variables ?? [])));
    }

    private function validate_template(Request $request, ?EmailTemplate $template)
    {
        $rules = [
            'name'                  => 'required|string|max:150',
            'description'           => 'nullable|string|max:1000',
            'project'               => 'required|in:' . implode(',', array_keys(EmailTemplate::PROJECTS)),
            'trigger_type'          => 'required|in:' . implode(',', array_keys(EmailTemplate::TRIGGERS)),
            'subject'               => 'required|string|max:255',
            'preheader'             => 'nullable|string|max:255',
            'header_title'          => 'nullable|string|max:255',
            'header_subtitle'       => 'nullable|string|max:255',
            'body'                  => 'required|string',
            'button_text'           => 'nullable|string|max:100|required_with:button_url',
            'button_url'            => 'nullable|string|max:255|required_with:button_text',
            'footer_note'           => 'nullable|string|max:255',
            'variables'             => 'nullable|array',
            'variables.*.name'      => ['nullable', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/', 'max:50'],
            'variables.*.sample'    => 'nullable|string|max:255',
            'variables.*.description' => 'nullable|string|max:255',
        ];
        if (!$template || !$template->is_system) {
            $rules['template_key'] = ['required', 'regex:/^[a-z][a-z0-9_]*$/', 'max:80',
                'unique:email_templates,template_key' . ($template ? ',' . $template->email_template_id . ',email_template_id' : '')];
        }

        $request->validate($rules, [
            'template_key.regex'      => 'Use lowercase letters, numbers and underscores, e.g. spring_offer.',
            'template_key.unique'     => 'Another template already uses this key.',
            'variables.*.name.regex'  => 'Placeholder names use letters, numbers and underscores, e.g. BusinessName.',
        ]);
    }

    private function fill_template(EmailTemplate $template, Request $request)
    {
        $template->fill($request->only('name', 'description', 'project', 'trigger_type', 'subject', 'preheader',
            'header_title', 'header_subtitle', 'body', 'button_text', 'button_url', 'footer_note'));
        $template->is_active = $request->boolean('is_active');

        // System templates' placeholders are defined by the code that sends them; only custom ones edit the list
        if (!$template->is_system) {
            $template->variables = collect($request->input('variables', []))
                ->filter(fn($row) => !empty($row['name']))
                ->map(fn($row) => ['name' => $row['name'], 'sample' => $row['sample'] ?? '', 'description' => $row['description'] ?? ''])
                ->values()
                ->all();
        }

        $template->updated_by = Auth::id();
        $template->save();
    }

    private function render_from_request(Request $request): array
    {
        $saved = $request->filled('email_template_id') ? EmailTemplate::find($request->email_template_id) : null;
        $variables = $saved && $saved->is_system ? ($saved->variables ?? []) : $request->input('variables', []);

        $template = $request->only('subject', 'preheader', 'header_title', 'header_subtitle', 'body', 'button_text', 'button_url', 'footer_note');
        $layout = ($saved ? NotificationSender::layout_for($saved) : EmailLayout::where('is_default', 1)->first());

        return EmailTemplateRenderer::render($template, $layout ? $layout->getAttributes() : [], $this->sample_data($variables));
    }

    private function sample_data(array $variables): array
    {
        $data = [];
        foreach ($variables as $variable) {
            if (!empty($variable['name'])) {
                $data[$variable['name']] = $variable['sample'] ?? '';
            }
        }
        return $data;
    }

    private function decrypt_id($id)
    {
        try {
            return decrypt($id);
        } catch (DecryptException $e) {
            abort(404);
        }
    }
}
