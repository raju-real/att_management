<?php

namespace App\Http\Controllers;

use App\Models\FeeLot;
use App\Models\Student;
use App\Models\StudentFee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeLotController extends Controller
{
    public function index()
    {
        $feeLots = FeeLot::latest()->paginate(20);
        return view('fee_lots.fee_lot_list', compact('feeLots'));
    }

    public function create()
    {
        $route = route('fee-lots.store');
        return view('fee_lots.fee_lot_add_edit', compact('route'));
    }

    public function edit($id)
    {
        $feeLot = FeeLot::findOrFail(encrypt_decrypt($id, 'decrypt'));
        $route = route('fee-lots.update', $feeLot->id);
        return view('fee_lots.fee_lot_add_edit', compact('route', 'feeLot'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            // 'class_name' => 'nullable|string',
        ]);

        $exists = FeeLot::where('is_active', true)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })->exists();

        if ($exists) {
             return back()->with(warningMessage('warning', 'A fee lot already exists in this date range!'));
        }

        try {
            DB::beginTransaction();

            $lot = FeeLot::create([
                'title' => $request->title,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => true,
            ]);

            // Calculate months difference
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);
            $months = (($end->year - $start->year) * 12) + ($end->month - $start->month) + 1;
            if ($months < 1) $months = 1;

            $settings = feeSettings();
            $classFees = isset($settings->class_fees) ? (array)$settings->class_fees : [];

            $query = Student::query();
            if ($request->filled('class_name')) {
                $query->where('class', $request->class_name);
            }
            
            $studentFeesBatch = [];
            $batchSize = 500; // Safe limit for placeholders (500 * 8 params = 4000 << 65535)

            $query->chunk(500, function ($students) use ($lot, $months, $classFees, &$studentFeesBatch, $batchSize) {
                foreach ($students as $student) {
                    $monthlyFee = isset($classFees[$student->class]) ? (float)$classFees[$student->class] : 0;
                    $totalAmount = $monthlyFee * $months;

                    $studentFeesBatch[] = [
                        'fee_lot_id' => $lot->id,
                        'student_id' => $student->id,
                        'amount' => $totalAmount,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($studentFeesBatch) >= $batchSize) {
                        StudentFee::insert($studentFeesBatch);
                        $studentFeesBatch = []; // Reset batch
                    }
                }
            });

            // Insert remaining
            if (!empty($studentFeesBatch)) {
                StudentFee::insert($studentFeesBatch);
            }

            DB::commit();

            return redirect()->route('fee-lots.index')
                ->with(successMessage('success', 'Fee Lot created successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(dangerMessage('danger', 'Something went wrong: ' . $e->getMessage()));
        }
    }

    public function show($id)
    {
        $feeLot = FeeLot::findOrFail($id);
        
        $query = StudentFee::with('student')->where('fee_lot_id', $id);
        
        // Apply filters
        if (request()->filled('student_id')) {
            $query->whereHas('student', function($q) {
                $q->where('student_id', 'like', '%' . request('student_id') . '%');
            });
        }
        
        if (request()->filled('class')) {
            $query->whereHas('student', function($q) {
                $q->where('class', request('class'));
            });
        }
        
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
        
        $studentFees = $query->paginate(50);
        
        return view('fee_lots.show', compact('feeLot', 'studentFees'));
    }

    public function update(Request $request, FeeLot $feeLot)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Check for overlapping active lots (excluding current lot)
        $exists = FeeLot::where('is_active', true)
            ->where('id', '!=', $feeLot->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })->exists();

        if ($exists) {
            return back()->with(warningMessage('warning', 'Another fee lot already exists in this date range!'));
        }

        try {
            $feeLot->update([
                'title' => $request->title,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return redirect()->route('fee-lots.index')
                ->with(successMessage('success', 'Fee Lot updated successfully!'));

        } catch (\Exception $e) {
            return back()->with(dangerMessage('danger', 'Something went wrong: ' . $e->getMessage()));
        }
    }

    public function destroy($id)
    {
        try {
            $feeLot = FeeLot::findOrFail($id);
            // Check if any payment has been made for this lot
            $hasPaidFees = StudentFee::where('fee_lot_id', $id)
                ->whereIn('status', ['paid', 'partial'])
                ->exists();

            if ($hasPaidFees) {
                return response()->json([
                    'type' => 'warning',
                    'message' => 'Cannot delete this lot! Some students have already made payments.'
                ], 422);
            }

            // Delete all associated student fees
            StudentFee::where('fee_lot_id', $id)->delete();
            // Delete the lot
            $feeLot->delete();
            return redirect()->route('fee-lots.index')->with(deleteMessage());

        } catch (\Exception $e) {
            return back()->with(dangerMessage('danger', 'Something went wrong: ' . $e->getMessage()));
        }
    }
}
