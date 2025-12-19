<?php

namespace App\Http\Controllers;

use App\Mail\RiskCreatedNotification;
use App\Models\DivisionMst;
use App\Models\EntityMst;
use App\Models\FinancialQuarter;
use App\Models\FinancialYear;
use App\Models\ImpactMst;
use App\Models\KriMaster;
use App\Models\Media;
use App\Models\MitigationAction;
use App\Models\ProbabilityMst;
use App\Models\ProcessMst;
use App\Models\RiskKriAssociation;
use App\Models\RiskLevelMst;
use App\Models\RiskMitigation;
use App\Models\RiskRegister;
use App\Models\RiskResponseMst;
use App\Models\RiskStatusLog;
use App\Models\RiskStatusMst;
use App\Models\RiskSubTypeMst;
use App\Models\RiskTypeMst;
use App\Models\RiskWorkflow;
use App\Models\SubEntityMst;
use App\Models\SubProcessMst;
use App\Models\User;
use App\Models\UserDivisionMapping;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Calculation\Financial;

class RiskRegisterController extends Controller
{
    public function risk_register()
    {
        $user = Auth::user();
        $user_division_ids = DB::table('user_division_mappings')
            ->where('user_id', $user->id)
            ->pluck('division_id');

        $query = RiskRegister::join('entity_msts', 'entity_msts.entity_id', 'risk_registers.entity_id')
            ->join('division_msts', 'division_msts.division_id', 'risk_registers.division_id')
            ->join('sub_entity_msts', 'sub_entity_msts.sub_entity_id', 'risk_registers.sub_entity_id')
            ->join('process_msts', 'process_msts.process_id', 'risk_registers.process_id')
            ->join('sub_process_msts', 'sub_process_msts.sub_process_id', 'risk_registers.sub_process_id')
            ->join('risk_type_msts', 'risk_type_msts.risk_type_id', 'risk_registers.risk_type_id')
            ->join('risk_sub_type_msts', 'risk_sub_type_msts.risk_sub_type_id', 'risk_registers.risk_sub_type_id')
            ->join('financial_years', 'financial_years.financial_year_id', 'risk_registers.financial_year_id')
            ->join('users as pending_user', 'pending_user.id', 'risk_registers.pending_with')
            ->join('users as owner_user', 'owner_user.id', 'risk_registers.risk_owner_id')
            ->select(
                'risk_registers.*',
                'division_msts.division_name',
                'entity_msts.entity_name',
                'sub_entity_msts.sub_entity_name',
                'process_msts.process_name',
                'sub_process_msts.sub_process_name',
                'risk_type_msts.risk_type_name',
                'risk_sub_type_msts.risk_sub_type_name',
                'financial_years.financial_year',
                'pending_user.name as pending_user_name',
                'owner_user.name as owner_user_name'
            )
            ->where('financial_years.is_default', 1);

        if ($user->user_role_id == 1) {
            $query->where('risk_registers.status', '!=', 'Draft');
        } elseif ($user->user_role_id == 3) {
            $query->where('risk_registers.created_by', $user->id);
        } elseif ($user->user_role_id == 4) {
            $query->where(function($q) use ($user) {
                $q->where('risk_registers.created_by', $user->id)
                  ->orWhere(function($q2) use ($user) {
                      $q2->where('risk_registers.risk_owner_id', $user->id)
                         ->where('risk_registers.status', '!=', 'Draft');
                  });
            });
        } else {
            $query->whereIn('risk_registers.division_id', $user_division_ids);
        }

        $query->orderBy('risk_registers.division_id', 'desc')
            ->orderBy('risk_registers.risk_id');

        $get_risk_register_data = $query->paginate(10);

        $entities = EntityMst::get();
        $processes = ProcessMst::get();
        $risk_types = RiskTypeMst::get();
        if ($user->user_role_id == 1) {
            $divisions = DivisionMst::where('status', 'Active')->get();
        } else {
            $divisions = DivisionMst::where('status', 'Active')
                ->whereIn('division_id', $user_division_ids)
                ->get();
        }
        $risk_levels = RiskLevelMst::get();
        $financial_years = FinancialYear::get();
        $current_financial_year = $financial_years->firstWhere('is_default', 1);
        $default_selected_division = $user_division_ids[0] ?? null;

        return view('risk_register.index', compact('get_risk_register_data', 'entities', 'processes', 'risk_types', 'divisions', 'risk_levels', 'financial_years', 'current_financial_year', 'default_selected_division'));
    }

    public function add_risk()
    {
        $entities = EntityMst::get();
        $sub_entities = SubEntityMst::get();
        $processes = ProcessMst::get();
        $sub_processes = SubProcessMst::get();
        $risk_types = RiskTypeMst::get();
        $risk_sub_types = RiskSubTypeMst::get();
        if (Auth::user()->user_role_id == 1) {
            $divisions = DivisionMst::where('status', 'Active')->get();
        } else {
            $get_user_assigned_division = UserDivisionMapping::where('user_id', Auth::user()->id)->pluck('division_id');
            $divisions = DivisionMst::where('status', 'Active')->whereIn('division_id', $get_user_assigned_division)->get();
        }
        $probabilities = ProbabilityMst::get();
        $impacts = ImpactMst::get();
        $financial_years = FinancialYear::get();
        $kri_masters = KriMaster::get();
        $quarters = FinancialQuarter::get();
        return view('risk_register.add', compact('entities', 'sub_entities', 'processes', 'sub_processes', 'risk_types', 'risk_sub_types', 'divisions', 'probabilities', 'impacts', 'financial_years', 'kri_masters', 'quarters'));
    }

    public function add_risk_action(Request $request){
        try {
            DB::beginTransaction();

            $division = DivisionMst::where('division_id', $request->division_id)->first();
            $abbr = $division->abbreviation;
            $year = date('Y');

            $last_risk = RiskRegister::where('division_id', $request->division_id)
                ->whereYear('created_at', $year)
                ->orderBy('risk_register_id', 'desc')
                ->first();

            if ($last_risk && $last_risk->risk_id) {
                $last_seq = intval(substr($last_risk->risk_id, -3));
                $next_seq = $last_seq + 1;
            } else {
                $next_seq = 1;
            }

            $sequence_no = str_pad($next_seq, 3, '0', STR_PAD_LEFT);
            $risk_code = "RSK-" . $abbr . "-" . $year . "-" . $sequence_no;

            $add_risk = new RiskRegister();
            $add_risk->financial_year_id = $request->financial_year;
            $add_risk->quarter_id = $request->quarter;
            $add_risk->division_id = $request->division_id;
            $add_risk->entity_id = $request->entity;
            $add_risk->sub_entity_id = $request->sub_entity;
            $add_risk->process_id = $request->process;
            $add_risk->sub_process_id = $request->sub_process;
            $add_risk->risk_id = $risk_code;
            $add_risk->risk_type_id = $request->risk_type;
            $add_risk->risk_sub_type_id = $request->risk_sub_type;
            $add_risk->risk_status_id = $request->risk_status ?? 99;
            $add_risk->probability_id = $request->probability ?? 0;
            $add_risk->impact_id = $request->impact_id ?? 0;
            $add_risk->risk_rating = 0;
            $add_risk->risk_level_id = 99;
            $add_risk->risk_response_id = $request->risk_response ?? 99;
            $add_risk->strategic_description = $request->strategic_description;
            $add_risk->risk_statement = $request->risk_statement;
            $add_risk->risk_description = $request->risk_description;
            $add_risk->remarks = $request->remarks;
            $add_risk->created_by = Auth::user()->id;
            $add_risk->risk_owner_id = $request->risk_owner_id;
            $add_risk->pending_task = 'Draft';
            $add_risk->status = 'Draft';
            $add_risk->pending_with = Auth::user()->id;
            $add_risk->save();

            if ($request->has('kri_ids') && is_array($request->kri_ids)) {
                $risk_kri_data = [];
                foreach ($request->kri_ids as $kri_id) {
                    $risk_kri_data[] = [
                        'risk_id' => $add_risk->risk_register_id,
                        'kri_id' => $kri_id,
                        'assessment_date_time' => now()
                    ];
                }
                DB::table('risk_kri_associations')->insert($risk_kri_data);
            }

            if ($request->hasFile('data_file_names')) {
                $files = $request->file('data_file_names');
                foreach ($files as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $file_name = rand() . '.' . $extension;
                    $destination_path = 'assets/img/risk';

                    if (!file_exists($destination_path)) {
                        mkdir($destination_path, 0755, true);
                    }

                    $file->move($destination_path, $file_name);

                    $file_path = 'assets/img/risk/' . $file_name;

                    Media::create([
                        'ref_id'     => $add_risk->risk_register_id,
                        'ref_table'  => 'risk_registers',
                        'file_path'  => $file_path,
                        'file_ext'   => $extension,
                    ]);
                }
            }

            $financial_year = FinancialYear::find($request->financial_year);
            $quarter_dates = get_quarter_dates($request->quarter, $financial_year->financial_year);


            $risk_owner = User::where('id', $add_risk->risk_owner_id)->first();
            DB::commit();
            try {
                Mail::to($risk_owner->email)->send(new RiskCreatedNotification($add_risk, $risk_owner->name));
                Log::info('Risk creation notification sent to ' . $risk_owner->email);
            } catch (\Throwable $th) {
                //throw $th;
                Log::error('Risk creation notification sent to ' . $risk_owner->email);
            }

            return redirect()->route('risk_register')->with('success', 'Risk details drafted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function risk_send_to_rw(Request $request){
        // dd($request->all());
        try {
            $risk_order = RiskRegister::find($request->risk_id);
            $risk_order->pending_task = 'Registered';
            $risk_order->status = 'Registered';
            $risk_order->pending_with = $risk_order->risk_owner_id;
            $risk_order->probability_id = 0;
            $risk_order->impact_id = 0;
            $risk_order->risk_level_id = 99;
            $risk_order->save();

            $check_risk_workflow = RiskWorkflow::where('risk_id', $risk_order->risk_register_id)
                                            ->where('status', 'Registered')
                                            ->where('task', 'Risk has been created')
                                            ->first();
            if (!$check_risk_workflow) {
                $workflow = new RiskWorkflow();
                $workflow->risk_id = $risk_order->risk_register_id;
                $workflow->probability_id = $risk_order->probability_id;
                $workflow->impact_id = $risk_order->impact_id;
                $workflow->risk_rating = $risk_order->risk_rating;
                $workflow->risk_level_id = $risk_order->risk_level_id;
                $workflow->financial_year_id = $risk_order->financial_year_id;
                $workflow->from_date = $risk_order['from_date'];
                $workflow->to_date = $risk_order['to_date'];
                $workflow->quarter_id = $risk_order->quarter_id;
                $workflow->work_done_by = Auth::user()->id;
                $workflow->updated_date_time = now();
                $workflow->user_id = $risk_order->pending_with;
                $workflow->task = 'Risk has been created';
                $workflow->status = 'Registered';
                $workflow->save();
            }

        } catch (Exception $e) {
            Log::error('Failed to send risk to Risk Send to Risk Owner: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send risk to Risk Send to Risk Owner: ' . $e->getMessage());
        }
        return redirect()->route('risk_register')->with('success', 'Risk sent to Risk Workflow successfully.');
    }

    public function edit_risk($risk_register_id)
    {
        $decrypt_id = Crypt::decrypt($risk_register_id);
        $edit_risk_details = RiskRegister::where('risk_register_id', $decrypt_id)->first();
        $entities = EntityMst::get();
        $sub_entities = SubEntityMst::get();
        $processes = ProcessMst::get();
        $sub_processes = SubProcessMst::get();
        $risk_types = RiskTypeMst::get();
        $risk_sub_types = RiskSubTypeMst::get();
        if (Auth::user()->user_role_id == 1) {
            $divisions = DivisionMst::where('status', 'Active')->get();
        } else {
            $get_user_assigned_division = UserDivisionMapping::where('user_id', Auth::user()->id)->pluck('division_id');
            $divisions = DivisionMst::where('status', 'Active')->whereIn('division_id', $get_user_assigned_division)->get();
        }
        $probabilities = ProbabilityMst::get();
        $impacts = ImpactMst::get();
        $selected_kris = RiskKriAssociation::where('risk_id', $decrypt_id)->pluck('kri_id')->toArray();
        $kri_masters = KriMaster::get();
        $financial_years = FinancialYear::get();
        $quarters = FinancialQuarter::get();
        $risk_log = RiskWorkflow::where('risk_id', $edit_risk_details->risk_register_id)
            ->latest('updated_date_time')
            ->first();
        $latest_comment = $risk_log ? $risk_log->comment : '';
        $latest_quarter = $risk_log ? $risk_log->quarter_id : '';
        $files = Media::where('ref_id', $edit_risk_details->risk_register_id)->where('ref_table', 'risk_registers')->get();
        return view('risk_register.edit', compact('edit_risk_details', 'entities', 'sub_entities', 'processes', 'sub_processes', 'risk_types', 'risk_sub_types', 'divisions', 'probabilities', 'impacts', 'financial_years', 'selected_kris', 'kri_masters', 'financial_years', 'quarters', 'latest_comment', 'latest_quarter', 'files'));
    }

    public function edit_risk_action(Request $request, $risk_register_id)
    {
        try {

            if ($request->action === 'publish') {
                $validated = $request->validate([
                    'ass_financial_year' => 'required|integer',
                    'ass_quarter'        => 'required|integer',
                    'probability'        => 'required|integer',
                    'impact'             => 'required|integer',
                    'comment'            => 'required|string|max:2000',
                ],[
                    'ass_financial_year.required' => 'Please select financial year.',
                    'ass_quarter.required'        => 'Please select assessment quarter.',
                    'probability.required'        => 'Please select likelihood.',
                    'impact.required'             => 'Please select impact.',
                    'comment.required'            => 'Comment field is mandatory.',
                ]);
            }

            DB::beginTransaction();

            $update_risk = RiskRegister::findOrFail($risk_register_id);

            $old_risk_owner_id = $update_risk->risk_owner_id;

            $update_risk->financial_year_id = $request->financial_year;
            $update_risk->quarter_id = $request->quarter_id;
            $update_risk->division_id = $request->division_id;
            $update_risk->entity_id = $request->entity;
            $update_risk->sub_entity_id = $request->sub_entity;
            $update_risk->process_id = $request->process;
            $update_risk->sub_process_id = $request->sub_process;
            $update_risk->risk_type_id = $request->risk_type;
            $update_risk->risk_sub_type_id = $request->risk_sub_type;
            $update_risk->risk_status_id = $request->risk_status ?? 99;

            $update_risk->probability_id = $request->probability ?? $update_risk->probability_id;
            $update_risk->impact_id = $request->impact_id ?? $update_risk->impact_id;

            $update_risk->risk_response_id = $request->risk_response ?? 99;
            $update_risk->strategic_description = $request->strategic_description;
            $update_risk->risk_statement = $request->risk_statement;
            $update_risk->risk_description = $request->risk_description;
            $update_risk->remarks = $request->remarks;
            $update_risk->risk_owner_id = $request->risk_owner_id;
            $update_risk->pending_with = Auth::user()->id;

            $update_risk->probability_id = $request->probability??0;
            $update_risk->impact_id = $request->impact??0;
            $update_risk->ass_quater_id = $request->ass_quarter;
            $update_risk->ass_comment = $request->comment;
            $update_risk->ass_fy = $request->ass_financial_year;
            $update_risk->pending_with = Auth::user()->id;
            if ($update_risk->probability_id && $update_risk->impact_id) {
                $update_risk->risk_rating = calculate_risk_rating($update_risk->probability_id, $update_risk->impact_id);
                // $update_risk->risk_level_id = get_risk_level_id($update_risk->risk_rating);
            }
            $update_risk->risk_level_id = get_risk_level_id($update_risk->risk_rating)??99;
            $update_risk->update();

            if (isset($request->kri_ids)) {
                $selected_kri_ids = $request->kri_ids;

                RiskKriAssociation::where('risk_id', $update_risk->risk_register_id)
                    ->whereNotIn('kri_id', $selected_kri_ids)
                    ->delete();

                foreach ($selected_kri_ids as $kri_id) {
                    $existing_association = RiskKriAssociation::where('risk_id', $update_risk->risk_register_id)
                        ->where('kri_id', $kri_id)
                        ->first();

                    if (!$existing_association) {
                        RiskKriAssociation::create([
                            'risk_id' => $update_risk->risk_register_id,
                            'kri_id' => $kri_id,
                            'assessment_date_time' => now()
                        ]);
                    }
                }
            } else {
                RiskKriAssociation::where('risk_id', $update_risk->risk_register_id)->delete();
            }

            if ($request->hasFile('data_file_names')) {
                foreach ($request->file('data_file_names') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $file_name = rand() . '.' . $extension;
                    $destination_path = 'assets/img/risk';

                    if (!file_exists($destination_path)) {
                        mkdir($destination_path, 0755, true);
                    }

                    $file->move($destination_path, $file_name);

                    $file_path = 'assets/img/risk/' . $file_name;

                    Media::create([
                        'ref_id'     => $update_risk->risk_register_id,
                        'ref_table'  => 'risk_registers',
                        'file_path'  => $file_path,
                        'file_ext'   => $extension,
                    ]);
                }
            }

            // if ($old_risk_owner_id != $request->risk_owner_id) {
            //     $risk_owner = User::where('id', $update_risk->risk_owner_id)->first();
            //     if ($risk_owner && $risk_owner->email) {
            //         try {
            //             Mail::to($risk_owner->email)->send(new RiskCreatedNotification($update_risk, $risk_owner->name));
            //         } catch (\Throwable $th) {
            //             Log::error('Failed to send notification: ' . $th->getMessage());
            //         }
            //     }
            // }

            // Financial year & quarter dates
            $financial_year = FinancialYear::find($request->financial_year);
            $quarter_dates  = get_quarter_dates($request->quarter, $financial_year->financial_year);

            if($request->action === 'publish'){

                $update_all_old_workflows = RiskWorkflow::where('risk_id', $risk_register_id)
                    ->whereIN('task', ['Risk has been created', 'Assessment completed'])
                    ->update(['task_status' => 'Completed']);

                // Get or create workflow entry
                $workflow = RiskWorkflow::where([
                    'risk_id' => $risk_register_id,
                    'status'  => 'Assessed',
                    'task'    => 'Assessment completed',
                ])->first();

                if (!$workflow) {
                    $workflow = new RiskWorkflow();
                    $workflow->risk_id = $update_risk->risk_register_id;
                }

                // Common workflow updates
                $workflow->probability_id   = $request->probability;
                $workflow->impact_id        = $request->impact;
                $workflow->risk_rating      = calculate_risk_rating($request->probability, $request->impact);
                $workflow->risk_level_id    = get_risk_level_id($update_risk->risk_rating);
                $workflow->financial_year_id= $request->financial_year;
                $workflow->quarter_id       = $request->ass_quarter;
                $workflow->from_date        = $quarter_dates['from_date'];
                $workflow->to_date          = $quarter_dates['to_date'];
                $workflow->comment          = $request->comment;
                $workflow->work_done_by     = Auth::user()->id;
                $workflow->user_id          = Auth::user()->id;
                $workflow->updated_date_time = now();

                $workflow->task   = 'Assessment completed';
                $workflow->status = 'Assessed';

                $update_risk->pending_task = "Assessed";
                $update_risk->status       = "Assessed";
                $update_risk->save();

                $workflow->save();
            }

            $risk_owner = User::where('id', $update_risk->risk_owner_id)->first();
            if ($risk_owner && $risk_owner->email) {
                try {
                    Mail::to($risk_owner->email)->send(new RiskCreatedNotification($update_risk, $risk_owner->name));
                } catch (\Throwable $th) {
                    Log::error('Failed to send notification: ' . $th->getMessage());
                }
            }
            DB::commit();
            return redirect()->route('risk_register')->with('success', 'Risk details updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function create_assessment(){

    }

    public function get_sub_entitites($entity_id)
    {
        $sub_entities = SubEntityMst::where('entity_type_id', $entity_id)->pluck('sub_entity_name', 'sub_entity_id');
        return response()->json($sub_entities);
    }

    public function get_sub_process($process_id)
    {
        $sub_process = SubProcessMst::where('process_type_id', $process_id)->pluck('sub_process_name', 'sub_process_id');
        return response()->json($sub_process);
    }

    public function get_risk_sub_type($risk_sub_type_id)
    {
        $sub_risk_type = RiskSubTypeMst::where('type_id', $risk_sub_type_id)->pluck('risk_sub_type_name', 'risk_sub_type_id');
        return response()->json($sub_risk_type);
    }

    public function risk_register_filter(Request $request)
    {
        $currentPage = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $query = RiskRegister::join('entity_msts', 'entity_msts.entity_id', 'risk_registers.entity_id')
            ->join('division_msts', 'division_msts.division_id', 'risk_registers.division_id')
            ->join('sub_entity_msts', 'sub_entity_msts.sub_entity_id', 'risk_registers.sub_entity_id')
            ->join('process_msts', 'process_msts.process_id', 'risk_registers.process_id')
            ->join('sub_process_msts', 'sub_process_msts.sub_process_id', 'risk_registers.sub_process_id')
            ->join('risk_type_msts', 'risk_type_msts.risk_type_id', 'risk_registers.risk_type_id')
            ->join('risk_sub_type_msts', 'risk_sub_type_msts.risk_sub_type_id', 'risk_registers.risk_sub_type_id')
            ->join('risk_level_msts', 'risk_level_msts.risk_level_id', 'risk_registers.risk_level_id')
            ->join('financial_years', 'financial_years.financial_year_id', 'risk_registers.financial_year_id')
            ->join('users as pending_user', 'pending_user.id', 'risk_registers.pending_with')
            ->join('users as owner_user', 'owner_user.id', 'risk_registers.risk_owner_id')
            ->select('risk_registers.*', 'division_msts.division_name', 'entity_msts.entity_name', 'sub_entity_msts.sub_entity_name', 'process_msts.process_name', 'sub_process_msts.sub_process_name', 'risk_type_msts.risk_type_name', 'risk_sub_type_msts.risk_sub_type_name', 'risk_level_msts.level_name', 'financial_years.financial_year', 'pending_user.name as pending_user_name', 'owner_user.name as risk_owner_name');

        if ($request->division_id) {
            $query->where('risk_registers.division_id', $request->division_id);
        }
        if ($request->entity) {
            $query->where('risk_registers.entity_id', $request->entity);
        }
        if ($request->sub_entity) {
            $query->where('risk_registers.sub_entity_id', $request->sub_entity);
        }
        if ($request->process) {
            $query->where('risk_registers.process_id', $request->process);
        }
        if ($request->sub_process) {
            $query->where('risk_registers.sub_process_id', $request->sub_process);
        }
        if ($request->risk_type) {
            $query->where('risk_registers.risk_type_id', $request->risk_type);
        }
        if ($request->risk_sub_type) {
            $query->where('risk_registers.risk_sub_type_id', $request->risk_sub_type);
        }
        if ($request->risk_level_id) {
            $query->where('risk_registers.risk_level_id', $request->risk_level_id);
        }

        if ($request->financial_year) {
            $query->where('risk_registers.financial_year_id', $request->financial_year);
        }

        $data = $query->orderBy('risk_registers.division_id', 'desc')->orderBy('risk_registers.risk_id')->paginate(10);
        // dd($data);
        $html = '';
        if ($data->count() > 0) {
            foreach ($data as $row) {
                $html .= '<tr>
                        <td>' . $row->risk_id . '</td>
                        <td>' . $row->division_name . '</td>
                        <td>' . $row->entity_name . '</td>
                        <td>' . $row->sub_entity_name . '</td>
                        <td hidden>' . $row->process_name . '</td>
                        <td hidden>' . $row->sub_process_name . '</td>
                        <td class="noExl">' . Str::limit(strip_tags($row->risk_statement), 40) . '</td>
                        <td hidden>' . $row->risk_statement . '</td>
                        <td hidden>' . $row->risk_description . '</td>
                        <td hidden>' . $row->strategic_description . '</td>
                        <td>' . $row->risk_type_name . '</td>
                        <td hidden>' . $row->risk_sub_type_name . '</td>
                        <td hidden>' . $row->risk_rating . '</td>
                        <td>' . risk_level_name($row->risk_level_id) . '</td>
                        <td>' . $row->risk_owner_name . '</td>
                        <td>' . status_badge($row->status) . '</td>
                        <td hidden>' . $row->remarks . '</td>
                        <td class="noExl">
                            <a href="' . route('view_risk_register', Crypt::encrypt($row->risk_register_id)) . '"><i class="bi bi-eye fs-8 p-1 text-warning"></i></a>
                            <a href="' . route('edit_risk', Crypt::encrypt($row->risk_register_id)) . '"><i class="bi bi-pencil fs-8 p-1 text-primary"></i></a>
                        </td>
                      </tr>';
            }
        } else {
            $html = '<tr><td colspan="10" class="text-center">' . no_record_found_in_table() . '</td></tr>';
        }

        $pagination = $data
            ->appends(array_merge($request->all(), ['page' => $currentPage]))
            ->links('pagination::bootstrap-5')
            ->render();

        return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => $currentPage]);
    }

    public function view_risk_register($risk_register_id)
    {

        $decrypt_id = Crypt::decrypt($risk_register_id);
        $risk_register_data = RiskRegister::join('financial_years', 'financial_years.financial_year_id', 'risk_registers.financial_year_id')
            ->join('financial_quarters', 'financial_quarters.quarter_id', 'risk_registers.quarter_id')
            ->join('users as owner_user', 'owner_user.id', 'risk_registers.risk_owner_id')
            ->select('owner_user.name as risk_owner_name','risk_registers.*', 'financial_years.financial_year', 'financial_quarters.quarter_name')
            ->where('risk_registers.risk_register_id', $decrypt_id)->first();
        $division = DivisionMst::where('division_id', $risk_register_data->division_id)->first();
        $entity = EntityMst::where('entity_id', $risk_register_data->entity_id)->first();
        $sub_entity = SubEntityMst::where('sub_entity_id', $risk_register_data->sub_entity_id)->first();
        $process = ProcessMst::where('process_id', $risk_register_data->process_id)->first();
        $sub_process = SubProcessMst::where('sub_process_id', $risk_register_data->sub_process_id)->first();
        $risk_type = RiskTypeMst::where('risk_type_id', $risk_register_data->risk_type_id)->first();
        $risk_sub_type = RiskSubTypeMst::where('risk_sub_type_id', $risk_register_data->risk_sub_type_id)->first();
        $probabilities = ProbabilityMst::get();
        $impacts = ImpactMst::get();
        $financial_years = FinancialYear::get();
        $quarters = FinancialQuarter::get();
        $files = Media::where('ref_id', $risk_register_data->risk_register_id)->where('ref_table', 'risk_registers')->get();

        $risk_log = RiskStatusLog::where('risk_id', $risk_register_data->risk_register_id)
            ->latest('updated_date_time')
            ->first();
        $latest_comment = $risk_log ? $risk_log->comment : '';
        $latest_quarter = $risk_log ? $risk_log->quarter_id : '';


        // risk Overflow logics
        $risk_over_flow_query = RiskWorkflow::query();
        $risk_over_flow_query->where('risk_id', $decrypt_id);
        $risk_over_flow_query->join('users', 'users.id', 'risk_workflows.work_done_by');
        $risk_over_flow_query->join('financial_years', 'financial_years.financial_year_id', 'risk_workflows.financial_year_id');

        $risk_over_flow_query->leftJoin('probability_msts', function($join) {
            $join->on('probability_msts.probability_id', '=', 'risk_workflows.probability_id');
        });
        $risk_over_flow_query->leftJoin('impact_msts', function($join) {
            $join->on('impact_msts.impact_id', '=', 'risk_workflows.impact_id');
        });

        $risk_over_flow_query->select(
            'risk_workflows.*',
            'users.name as work_done_by_name',
            'users.profile_pic',
            'financial_years.financial_year',
            DB::raw("IF(risk_workflows.probability_id>0, probability_msts.probability_name, '') as probability_name"),
            DB::raw("IF(risk_workflows.impact_id>0, impact_msts.impact_name, '') as impact_name")
        );

        $risk_over_flow = $risk_over_flow_query->orderBy('risk_workflows.risk_workflow_id')->get();

        $view_kri = RiskKriAssociation::join('kri_masters', 'kri_masters.kri_id', 'risk_kri_associations.kri_id')
            ->select('kri_masters.*', 'risk_kri_associations.kri_id')
            ->where('risk_id', $decrypt_id)
            ->get();

        $preview_limit = 250;
        $full_text = $risk_register_data->strategic_description;
        $is_long = strlen(strip_tags($full_text)) > $preview_limit;

        if ($is_long) {
            $cut_position = mb_strrpos(mb_substr($full_text, 0, $preview_limit), ' ');
            $first_part = mb_substr($full_text, 0, $cut_position);
            $remaining_part = mb_substr($full_text, $cut_position);
        } else {
            $first_part = $full_text;
            $remaining_part = '';
        }

        $risk_description_full_text = $risk_register_data->risk_description;
        $risk_description_is_long = strlen(strip_tags($risk_description_full_text)) > $preview_limit;

        if ($risk_description_is_long) {
            $risk_description_cut_position = mb_strrpos(mb_substr($risk_description_full_text, 0, $preview_limit), ' ');
            $risk_description_first_part = mb_substr($risk_description_full_text, 0, $risk_description_cut_position);
            $risk_description_remaining_part = mb_substr($risk_description_full_text, $risk_description_cut_position);
        } else {
            $risk_description_first_part = $risk_description_full_text;
            $risk_description_remaining_part = '';
        }

        $remarks_full_text = $risk_register_data->remarks;
        $remarks_is_long = strlen(strip_tags($remarks_full_text)) > $preview_limit;

        if ($remarks_is_long) {
            $remarks_cut_position = mb_strrpos(mb_substr($remarks_full_text, 0, $preview_limit), ' ');
            $remarks_first_part = mb_substr($remarks_full_text, 0, $remarks_cut_position);
            $remarks_remaining_part = mb_substr($remarks_full_text, $remarks_cut_position);
        } else {
            $remarks_first_part = $remarks_full_text;
            $remarks_remaining_part = '';
        }

        $risk_workflows = RiskWorkflow::select(
            'risk_workflows.*',
            'users.name',
            'financial_years.financial_year',
            'financial_quarters.quarter_name',
            DB::raw('(SELECT COUNT(*) FROM mitigation_actions
                  WHERE mitigation_actions.mitigation_id = risk_workflows.mitigation_id) AS actions_count')
        )
            ->leftJoin('risk_registers', 'risk_registers.risk_register_id', 'risk_workflows.risk_id')
            ->leftjoin('risk_status_logs', 'risk_status_logs.risk_id', 'risk_workflows.risk_id')
            ->leftjoin('financial_years', 'financial_years.financial_year_id', 'risk_registers.financial_year_id')
            ->leftjoin('financial_quarters', 'financial_quarters.quarter_id', 'risk_registers.quarter_id')
            ->leftJoin('users', 'users.id', 'risk_workflows.user_id')
            ->where('risk_workflows.risk_id', $decrypt_id)
            ->get();

        return view('risk_register.view', compact('risk_register_data', 'is_long', 'first_part', 'remaining_part', 'risk_description_is_long', 'risk_description_first_part', 'risk_description_remaining_part', 'division', 'entity', 'sub_entity', 'process', 'sub_process', 'risk_type', 'risk_sub_type', 'probabilities', 'impacts', 'remarks_is_long', 'remarks_first_part', 'remarks_remaining_part', 'latest_comment', 'view_kri', 'financial_years', 'quarters', 'latest_quarter', 'risk_workflows', 'risk_over_flow', 'files'));
    }

    public function risk_register_status_update(Request $request, $risk_register_id)
    {
        $update_risk_status = RiskRegister::find($risk_register_id);
        $user = Auth::user();

        $check_quarter_wise_risk_exists = RiskStatusLog::where('risk_id', $risk_register_id)
            ->where('quarter_id', $request->quarter)
            ->where('financial_year_id', $request->financial_year)
            ->first();

        if ($check_quarter_wise_risk_exists && $user->user_role_id == 4) {
            return redirect()->back()->with('error', 'Risk assessment already updated for this quarter.');
        }

        $latest_log = RiskStatusLog::where('risk_id', $risk_register_id)
            ->latest('updated_date_time')
            ->first();

        $old_quarter = $latest_log ? $latest_log->quarter_id : null;
        $old_financial_year = $latest_log ? $latest_log->financial_year_id : null;

        $update_risk_status->probability_id = $request->probability;
        $update_risk_status->quarter_id = $request->quarter;
        $update_risk_status->impact_id = $request->impact;
        $update_risk_status->risk_rating = calculate_risk_rating($request->probability, $request->impact);
        $update_risk_status->risk_level_id = get_risk_level_id($update_risk_status->risk_rating);
        $update_risk_status->save();

        $has_changes = $old_quarter != $request->quarter || $old_financial_year != $request->financial_year;

        $financial_year = FinancialYear::find($request->financial_year);
        $quarter_dates = get_quarter_dates($request->quarter, $financial_year->financial_year);

        if ($has_changes) {
            RiskWorkflow::where('risk_id', $update_risk_status->risk_register_id)
                ->where('task', 'Risk has been created')
                ->update(['task_status' => 'Completed']);

            $risk_status_log = new RiskStatusLog();
            $risk_status_log->risk_id = $update_risk_status->risk_register_id;
            $risk_status_log->financial_year_id = $request->financial_year;
            $risk_status_log->quarter_id = $request->quarter;
            $risk_status_log->from_date = $quarter_dates['from_date'];
            $risk_status_log->to_date   = $quarter_dates['to_date'];
            $risk_status_log->probability_id = $request->probability;
            $risk_status_log->impact_id = $request->impact;
            $risk_status_log->risk_rating = calculate_risk_rating($request->probability, $request->impact);
            $risk_status_log->risk_level_id = get_risk_level_id($risk_status_log->risk_rating);
            $risk_status_log->updated_by = Auth::user()->id;
            $risk_status_log->updated_date_time = Carbon::now();
            $risk_status_log->comment = $request->comment;
            $risk_status_log->save();

            RiskRegister::where('risk_register_id', $update_risk_status->risk_register_id)->update(['pending_task' => 'Assessed', 'pending_with' => 1]);

            $workflow = new RiskWorkflow();
            $workflow->risk_id = $update_risk_status->risk_register_id;
            $workflow->probability_id = $request->probability;
            $workflow->impact_id = $request->impact;
            $workflow->risk_rating = calculate_risk_rating($request->probability, $request->impact);
            $workflow->risk_level_id = get_risk_level_id($risk_status_log->risk_rating);
            $workflow->financial_year_id = $request->financial_year;
            $workflow->quarter_id = $request->quarter;
            $workflow->from_date = $quarter_dates['from_date'];
            $workflow->to_date   = $quarter_dates['to_date'];
            $workflow->comment = $request->comment;
            $workflow->work_done_by = Auth::user()->id;
            $workflow->updated_date_time = Carbon::now();
            $workflow->user_id = 1;
            $workflow->task = 'Assessment completed';
            $workflow->status = 'Assessed';
            $workflow->save();
        } else {
            $existing_log = RiskStatusLog::where('risk_id', $update_risk_status->risk_register_id)
                ->latest('updated_date_time')
                ->first();

            if ($existing_log) {
                $existing_log->probability_id = $request->probability;
                $existing_log->impact_id = $request->impact;
                $existing_log->risk_rating = $update_risk_status->risk_rating;
                $existing_log->risk_level_id = $update_risk_status->risk_level_id;
                $existing_log->updated_by = Auth::user()->id;
                $existing_log->updated_date_time = Carbon::now();
                $existing_log->comment = $request->comment;
                $existing_log->save();
            }
        }

        return redirect()->route('risk_register')->with('success', 'Risk status updated successfully.');
    }

    public function get_users($division_id)
    {
        $get_all_user_id_by_division = UserDivisionMapping::where('division_id', $division_id)->pluck('user_id');
        $managers = User::whereIn('id', $get_all_user_id_by_division)
            ->where('status', 'Active')
            ->where('user_role_id', 4)
            ->get();

        return response()->json($managers);
    }

    public function delete_risk_file($id)
    {
        $file = Media::find($id);

        if ($file) {
            $file_path = $file->file_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $file->delete();
        }
        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }

    public function get_risk_actions($risk_id)
    {
        $plans = RiskMitigation::where('risk_id', $risk_id)
            ->select('mitigation_id', 'action_plan')
            ->get();

        foreach ($plans as $plan) {
            $plan->actions = MitigationAction::where('mitigation_id', $plan->mitigation_id)
                ->select('title', 'description')
                ->get();
        }

        return response()->json($plans);
    }

    public function assessment()
    {
        $user = Auth::user();
        $user_division_ids = DB::table('user_division_mappings')
            ->where('user_id', $user->id)
            ->pluck('division_id');

        $query = RiskRegister::join('entity_msts', 'entity_msts.entity_id', 'risk_registers.entity_id')
            ->join('division_msts', 'division_msts.division_id', 'risk_registers.division_id')
            ->join('sub_entity_msts', 'sub_entity_msts.sub_entity_id', 'risk_registers.sub_entity_id')
            ->join('process_msts', 'process_msts.process_id', 'risk_registers.process_id')
            ->join('sub_process_msts', 'sub_process_msts.sub_process_id', 'risk_registers.sub_process_id')
            ->join('risk_type_msts', 'risk_type_msts.risk_type_id', 'risk_registers.risk_type_id')
            ->join('risk_sub_type_msts', 'risk_sub_type_msts.risk_sub_type_id', 'risk_registers.risk_sub_type_id')
            ->join('financial_years', 'financial_years.financial_year_id', 'risk_registers.financial_year_id')
            ->join('users as pending_user', 'pending_user.id', 'risk_registers.pending_with')
            ->join('users as owner_user', 'owner_user.id', 'risk_registers.risk_owner_id')
            ->select(
                'risk_registers.*',
                'division_msts.division_name',
                'entity_msts.entity_name',
                'sub_entity_msts.sub_entity_name',
                'process_msts.process_name',
                'sub_process_msts.sub_process_name',
                'risk_type_msts.risk_type_name',
                'risk_sub_type_msts.risk_sub_type_name',
                'financial_years.financial_year',
                'pending_user.name as pending_user_name',
                'owner_user.name as owner_user_name'
            )
            ->where('financial_years.is_default', 1)
            ->where('pending_task', 'Registered')
            ->orderBy('risk_registers.division_id', 'desc')
            ->orderBy('risk_registers.risk_id');

        if ($user->user_role_id != 1) {
            $query->whereIn('risk_registers.division_id', $user_division_ids);
        }

        $get_risk_register_data = $query->paginate(10);

        $entities = EntityMst::get();
        $processes = ProcessMst::get();
        $risk_types = RiskTypeMst::get();
        if ($user->user_role_id == 1) {
            $divisions = DivisionMst::where('status', 'Active')->get();
        } else {
            $divisions = DivisionMst::where('status', 'Active')
                ->whereIn('division_id', $user_division_ids)
                ->get();
        }
        $risk_levels = RiskLevelMst::get();
        $financial_years = FinancialYear::get();
        $current_financial_year = $financial_years->firstWhere('is_default', 1);
        $default_selected_division = $user_division_ids[0] ?? null;

        return view('assessment.index', compact('get_risk_register_data', 'entities', 'processes', 'risk_types', 'divisions', 'risk_levels', 'financial_years', 'current_financial_year', 'default_selected_division'));
    }

    public function assessment_filter(Request $request)
    {
        $currentPage = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $query = RiskRegister::join('entity_msts', 'entity_msts.entity_id', 'risk_registers.entity_id')
            ->join('division_msts', 'division_msts.division_id', 'risk_registers.division_id')
            ->join('sub_entity_msts', 'sub_entity_msts.sub_entity_id', 'risk_registers.sub_entity_id')
            ->join('process_msts', 'process_msts.process_id', 'risk_registers.process_id')
            ->join('sub_process_msts', 'sub_process_msts.sub_process_id', 'risk_registers.sub_process_id')
            ->join('risk_type_msts', 'risk_type_msts.risk_type_id', 'risk_registers.risk_type_id')
            ->join('risk_sub_type_msts', 'risk_sub_type_msts.risk_sub_type_id', 'risk_registers.risk_sub_type_id')
            ->join('risk_level_msts', 'risk_level_msts.risk_level_id', 'risk_registers.risk_level_id')
            ->join('financial_years', 'financial_years.financial_year_id', 'risk_registers.financial_year_id')
            ->join('users as pending_user', 'pending_user.id', 'risk_registers.pending_with')
            ->join('users as owner_user', 'owner_user.id', 'risk_registers.risk_owner_id')
            ->select('risk_registers.*', 'division_msts.division_name', 'entity_msts.entity_name', 'sub_entity_msts.sub_entity_name', 'process_msts.process_name', 'sub_process_msts.sub_process_name', 'risk_type_msts.risk_type_name', 'risk_sub_type_msts.risk_sub_type_name', 'risk_level_msts.level_name', 'financial_years.financial_year', 'pending_user.name as pending_user_name', 'owner_user.name as owner_user_name');

        if ($request->division_id) {
            $query->where('risk_registers.division_id', $request->division_id);
        }
        if ($request->entity) {
            $query->where('risk_registers.entity_id', $request->entity);
        }
        if ($request->sub_entity) {
            $query->where('risk_registers.sub_entity_id', $request->sub_entity);
        }
        if ($request->process) {
            $query->where('risk_registers.process_id', $request->process);
        }
        if ($request->sub_process) {
            $query->where('risk_registers.sub_process_id', $request->sub_process);
        }
        if ($request->risk_type) {
            $query->where('risk_registers.risk_type_id', $request->risk_type);
        }
        if ($request->risk_sub_type) {
            $query->where('risk_registers.risk_sub_type_id', $request->risk_sub_type);
        }
        if ($request->risk_level_id) {
            $query->where('risk_registers.risk_level_id', $request->risk_level_id);
        }

        if ($request->financial_year) {
            $query->where('risk_registers.financial_year_id', $request->financial_year);
        }

        if ($request->financial_year) {
            $query->where('risk_registers.pending_task', 'Registered');
        }

        $data = $query->orderBy('risk_registers.division_id', 'desc')->orderBy('risk_registers.risk_id')->paginate(10);
        // dd($data);
        $html = '';
        if ($data->count() > 0) {
            foreach ($data as $row) {
                $html .= '<tr>
                        <td>' . $row->risk_id . '</td>
                        <td>' . $row->division_name . '</td>
                        <td>' . $row->entity_name . '</td>
                        <td>' . $row->sub_entity_name . '</td>
                        <td hidden>' . $row->process_name . '</td>
                        <td hidden>' . $row->sub_process_name . '</td>
                        <td class="noExl">' . Str::limit(strip_tags($row->risk_statement), 40) . '</td>
                        <td hidden>' . $row->risk_statement . '</td>
                        <td hidden>' . $row->risk_description . '</td>
                        <td hidden>' . $row->strategic_description . '</td>
                        <td>' . $row->risk_type_name . '</td>
                        <td hidden>' . $row->risk_sub_type_name . '</td>
                        <td hidden>' . $row->risk_rating . '</td>
                        <td>' . risk_level_name($row->risk_level_id) . '</td>
                        <td>' . status_badge($row->status) . '</td>
                        <td hidden>' . $row->remarks . '</td>
                        <td class="noExl">
                            <a href="' . route('view_risk_register', Crypt::encrypt($row->risk_register_id)) . '"><i class="bi bi-eye fs-8 p-1 text-warning"></i></a>
                        </td>
                      </tr>';
            }
        } else {
            $html = '<tr><td colspan="10" class="text-center">' . no_record_found_in_table() . '</td></tr>';
        }

        $pagination = $data
            ->appends(array_merge($request->all(), ['page' => $currentPage]))
            ->links('pagination::bootstrap-5')
            ->render();

        return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => $currentPage]);
    }
}
